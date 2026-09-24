<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;

class PagoPartidaConciliacionService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: \Config\Database::connect();
    }

    public function conciliarPendientes(int $actorUserId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $rows = $this->db->query(
            "SELECT p.id_pago
             FROM pagos p
             INNER JOIN solicitud_pago sp
                ON sp.id_solicitud_pago = p.id_solicitud_pago
             LEFT JOIN pago_partida_movimiento ppm
                ON ppm.id_pago = p.id_pago
               AND ppm.tipo_movimiento = 'aplicacion'
               AND ppm.visible = 1
             WHERE p.visible = 1
               AND sp.estatus = 'autorizado'
               AND ppm.id_pago_partida_movimiento IS NULL
             ORDER BY p.fec_reg ASC, p.id_pago ASC
             LIMIT {$limit}"
        )->getResultArray();

        $resultados = [
            'detectados' => count($rows),
            'aplicados' => 0,
            'omitidos' => 0,
            'errores' => 0,
            'detalle' => [],
        ];

        foreach ($rows as $row) {
            $idPago = (int) ($row['id_pago'] ?? 0);
            $resultado = $this->conciliarPago($idPago, $actorUserId);
            if (!empty($resultado['applied'])) {
                $resultados['aplicados']++;
            } elseif (!empty($resultado['error'])) {
                $resultados['errores']++;
            } else {
                $resultados['omitidos']++;
            }
            $resultados['detalle'][] = $resultado;
        }

        return $resultados;
    }

    public function conciliarPago(int $idPago, int $actorUserId, ?string $fechaAhora = null): array
    {
        if ($idPago <= 0) {
            return ['id_pago' => $idPago, 'applied' => false, 'message' => 'Pago invalido.'];
        }

        $fechaAhora = $fechaAhora ?: date('Y-m-d H:i:s');
        $this->db->transBegin();

        try {
            $pago = $this->db->query(
                "SELECT
                    p.id_pago,
                    p.id_solicitud_pago,
                    p.id_usuario AS id_usuario_pago,
                    p.monto,
                    p.total,
                    p.visible,
                    sp.id_usuario AS id_usuario_solicitud,
                    sp.estatus AS solicitud_estatus,
                    up.id_partida AS id_partida_pago,
                    us.id_partida AS id_partida_solicitud
                 FROM pagos p
                 INNER JOIN solicitud_pago sp
                    ON sp.id_solicitud_pago = p.id_solicitud_pago
                 LEFT JOIN usuario up
                    ON up.id_usuario = p.id_usuario
                 LEFT JOIN usuario us
                    ON us.id_usuario = sp.id_usuario
                 WHERE p.id_pago = ?
                   AND p.visible = 1
                 FOR UPDATE",
                [$idPago]
            )->getRowArray();

            if (empty($pago)) {
                return $this->rollbackResult($idPago, 'Pago no encontrado o no visible.');
            }

            $estatusSolicitud = strtolower(trim((string) ($pago['solicitud_estatus'] ?? '')));
            if ($estatusSolicitud !== 'autorizado') {
                return $this->rollbackResult($idPago, 'La solicitud del pago no esta autorizada.');
            }

            $movimientoExistente = $this->db->query(
                "SELECT id_pago_partida_movimiento
                 FROM pago_partida_movimiento
                 WHERE id_pago = ?
                   AND tipo_movimiento = 'aplicacion'
                   AND visible = 1
                 LIMIT 1
                 FOR UPDATE",
                [$idPago]
            )->getRowArray();

            if (!empty($movimientoExistente)) {
                return $this->rollbackResult($idPago, 'El pago ya fue conciliado.', ['idempotent' => true]);
            }

            $idPartida = (int) ($pago['id_partida_pago'] ?? 0);
            if ($idPartida <= 0) {
                $idPartida = (int) ($pago['id_partida_solicitud'] ?? 0);
            }
            if ($idPartida <= 0) {
                return $this->rollbackResult($idPago, 'El pago no tiene partida valida para impactar.');
            }

            $monto = round((float) ($pago['monto'] ?? 0), 2);
            if ($monto <= 0) {
                return $this->rollbackResult($idPago, 'El pago no tiene monto aplicable a partida.');
            }

            $partida = $this->db->query(
                "SELECT id_partida, partida, monto_disponible
                 FROM cat_partida
                 WHERE id_partida = ?
                   AND visible = 1
                 FOR UPDATE",
                [$idPartida]
            )->getRowArray();

            if (empty($partida)) {
                return $this->rollbackResult($idPago, 'La partida del pago no existe o no esta visible.');
            }

            $saldoAnterior = round((float) ($partida['monto_disponible'] ?? 0), 2);
            if ($monto > $saldoAnterior) {
                throw new RuntimeException(
                    'Saldo insuficiente en partida ' . ($partida['partida'] ?? $idPartida) .
                    '. Disponible: $' . number_format($saldoAnterior, 2, '.', ',') .
                    ', requerido: $' . number_format($monto, 2, '.', ',')
                );
            }

            $saldoNuevo = round($saldoAnterior - $monto, 2);
            $this->db->table('cat_partida')
                ->where('id_partida', $idPartida)
                ->update([
                    'monto_disponible' => number_format($saldoNuevo, 2, '.', ''),
                    'fec_act' => $fechaAhora,
                    'usu_act' => $actorUserId,
                ]);

            $this->db->table('pago_partida_movimiento')->insert([
                'id_pago' => $idPago,
                'id_solicitud_pago' => (int) ($pago['id_solicitud_pago'] ?? 0),
                'id_partida' => $idPartida,
                'tipo_movimiento' => 'aplicacion',
                'monto' => number_format($monto, 2, '.', ''),
                'saldo_anterior' => number_format($saldoAnterior, 2, '.', ''),
                'saldo_nuevo' => number_format($saldoNuevo, 2, '.', ''),
                'descripcion' => 'Conciliacion de pago autorizado en partida',
                'fec_reg' => $fechaAhora,
                'usu_reg' => $actorUserId,
                'visible' => 1,
            ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transaccion de conciliacion de pago no pudo completarse.');
            }

            $this->db->transCommit();

            return [
                'id_pago' => $idPago,
                'applied' => true,
                'id_partida' => $idPartida,
                'monto' => $monto,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
            ];
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'PagoPartidaConciliacionService.conciliarPago: ' . $e->getMessage());
            return [
                'id_pago' => $idPago,
                'applied' => false,
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function rollbackResult(int $idPago, string $message, array $extra = []): array
    {
        $this->db->transRollback();
        return array_merge([
            'id_pago' => $idPago,
            'applied' => false,
            'message' => $message,
        ], $extra);
    }
}

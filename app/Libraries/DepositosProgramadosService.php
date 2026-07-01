<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use stdClass;

class DepositosProgramadosService
{
    private const TZ = 'America/Mexico_City';

    private BaseConnection $db;
    private UsuarioPerfilResolver $resolver;

    public function __construct(?BaseConnection $db = null, ?UsuarioPerfilResolver $resolver = null)
    {
        $this->db = $db ?? Database::connect();
        $this->resolver = $resolver ?? new UsuarioPerfilResolver();
    }

    public function reserveNewUser(array $dataInsert, int $actorUserId, string $scriptName): object
    {
        $response = new stdClass();
        $response->error = true;
        $response->respuesta = 'Error | No fue posible guardar el usuario';

        $vigenciaInicio = $this->resolveUserDate($dataInsert, ['fec_vigencia_desde', 'fecha_check_in']);
        $vigenciaFin = $this->resolveUserDate($dataInsert, ['fec_vigencia_hasta', 'fecha_check_out']);
        if ($vigenciaInicio === null || $vigenciaFin === null) {
            $response->respuesta = 'Error | Debes capturar vigencia inicial y final.';
            return $response;
        }
        if ($vigenciaFin < $vigenciaInicio) {
            $response->respuesta = 'Error | La vigencia final no puede ser menor a la inicial.';
            return $response;
        }

        $days = $this->countInclusiveDays($vigenciaInicio, $vigenciaFin);
        $dailyAmount = $this->resolveDailyAmount($dataInsert);
        $foodReserve = (int) ($dataInsert['tiene_alimentos'] ?? 0) === 1 ? round($dailyAmount * $days, 2) : 0.00;
        $hotelAmount = (int) ($dataInsert['tiene_hospedaje'] ?? 0) === 1 ? $this->resolveHospedajeAmount($dataInsert) : 0.00;
        $totalReserve = round($foodReserve + $hotelAmount, 2);
        $allocations = $this->buildPartidaDepositAllocations($dataInsert, $foodReserve, $hotelAmount);
        if (!empty($allocations['error'])) {
            $response->respuesta = (string) ($allocations['respuesta'] ?? 'Error | No fue posible calcular la partida presupuestal.');
            return $response;
        }

        $allocationRows = $allocations['data'] ?? [];
        $userRow = $dataInsert;
        $userRow['monto_deposito'] = 0.00;
        $userRow['monto_deposito_hotel'] = round($hotelAmount, 2);
        $userRow['monto_deposito_reservado'] = $totalReserve;
        $userRow['monto_deposito_operativo'] = 0.00;
        $userRow['deposito_programado_estatus'] = $totalReserve > 0 ? 'reservado' : 'sin_programa';
        $userRow['fec_reg'] = $userRow['fec_reg'] ?? date('Y-m-d H:i:s');
        $userRow['fec_act'] = $userRow['fec_act'] ?? $userRow['fec_reg'];
        $userRow['usu_reg'] = $actorUserId;
        $userRow['usu_act'] = $actorUserId;

        $this->db->transBegin();
        try {
            $idUsuario = $this->insertUser($userRow);
            if ($idUsuario <= 0) {
                throw new RuntimeException('No fue posible resolver el usuario creado.');
            }

            $this->applyPartidaReservations($allocationRows, $actorUserId);

            $programId = 0;
            if ($totalReserve > 0) {
                $programId = $this->insertProgramSummaryRow([
                    'id_usuario' => $idUsuario,
                    'id_qr_cliente' => $idUsuario,
                    'tipo_evento' => 'alta',
                    'periodo_inicio' => $vigenciaInicio->format('Y-m-d'),
                    'periodo_fin' => $vigenciaFin->format('Y-m-d'),
                    'fecha_ejecucion_programada' => $userRow['fec_reg'],
                    'monto_diario' => $dailyAmount,
                    'dias_programados' => $days,
                    'monto_total_reservado' => $totalReserve,
                    'monto_total_aplicado' => 0.00,
                    'estatus' => 'reservado',
                    'observaciones' => 'Reserva inicial al alta del usuario.',
                    'usu_reg' => $actorUserId,
                ]);
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Error de transacción al reservar el depósito del usuario.');
            }

            $this->db->transCommit();

            $response->error = false;
            $response->respuesta = 'Registro guardado correctamente';
            $response->idRegistro = $idUsuario;
            $response->programa_id = $programId;
            $response->depositos_programados = $allocationRows;
            $response->monto_reservado = $totalReserve;
            $response->monto_operativo = 0.00;
            $response->script = $scriptName;
            return $response;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'DepositosProgramadosService.reserveNewUser: ' . $e->getMessage());
            $response->respuesta = 'Error | ' . $e->getMessage();
            return $response;
        }
    }

    public function activateQrAndApplyDeposits(int $idUsuario, int $actorUserId = 0, ?string $referenceDate = null): object
    {
        $response = new stdClass();
        $response->error = true;
        $response->respuesta = 'Error | No fue posible activar el QR';

        $user = $this->getUserRow($idUsuario);
        if (empty($user)) {
            $response->respuesta = 'Error | El usuario no existe o no está visible.';
            return $response;
        }

        $this->db->transBegin();
        try {
            $now = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));
            $this->db->table('usuario')
                ->where('id_usuario', $idUsuario)
                ->update([
                    'activo_qr' => 1,
                    'deposito_programado_estatus' => 'operativo',
                    'fec_act' => $now->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            $result = $this->applyCurrentWindow($user, 'activacion', $now, $actorUserId);
            if (!$result['applied']) {
                throw new RuntimeException($result['message'] ?? 'No se pudo aplicar el depósito de activación.');
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Error de transacción al activar el QR.');
            }

            $this->db->transCommit();

            $response->error = false;
            $response->respuesta = 'QR activado y depósito aplicado correctamente';
            $response->id_usuario = $idUsuario;
            $response->aplicado = $result['applied_amount'];
            $response->programa = $result['program_row'] ?? null;
            return $response;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->markProgramError($idUsuario, 'activacion', $referenceDate, $actorUserId, $e->getMessage());
            log_message('error', 'DepositosProgramadosService.activateQrAndApplyDeposits: ' . $e->getMessage());
            $response->respuesta = 'Error | ' . $e->getMessage();
            return $response;
        }
    }

    public function processWeeklyDeposits(?string $referenceDate = null, int $actorUserId = 0): array
    {
        $now = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));
        $users = $this->db->table('usuario')
            ->select('id_usuario, id_establecimiento, id_partida, id_perfil, id_tipo_proveedor, id_fic_perfil, id_secul_perfil, id_ug_perfil, id_secturi_perfil, monto_deposito, monto_deposito_hotel, monto_deposito_reservado, monto_deposito_operativo, deposito_programado_estatus, activo_qr, fec_vigencia_desde, fec_vigencia_hasta, fecha_check_in, fecha_check_out, tarifa_total, tarifa_noche, noche, tiene_alimentos, tiene_hospedaje, id_nivel_cliente, visible')
            ->where('visible', 1)
            ->where('activo_qr', 1)
            ->groupStart()
                ->where('deposito_programado_estatus', 'reservado')
                ->orWhere('deposito_programado_estatus', 'parcial')
                ->orWhere('deposito_programado_estatus', 'operativo')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $result = [
            'ok' => true,
            'processed' => 0,
            'applied' => 0,
            'errors' => [],
        ];

        foreach ($users as $user) {
            $apply = $this->applyCurrentWindow($user, 'semanal', $now, $actorUserId);
            if ($apply['applied']) {
                $result['processed']++;
                $result['applied'] += 1;
                continue;
            }

            if (!empty($apply['message'])) {
                $result['errors'][] = [
                    'id_usuario' => (int) ($user['id_usuario'] ?? 0),
                    'message' => $apply['message'],
                ];
            }
        }

        return $result;
    }

    private function applyCurrentWindow(array $user, string $tipoEvento, DateTimeImmutable $referenceDate, int $actorUserId): array
    {
        $master = $this->getMasterProgramRow((int) ($user['id_usuario'] ?? 0));
        if (empty($master)) {
            return ['applied' => false, 'message' => 'No existe un programa de depósito reservado para el usuario.'];
        }

        $vigenciaInicio = $this->resolveUserDate($user, ['fec_vigencia_desde', 'fecha_check_in']);
        $vigenciaFin = $this->resolveUserDate($user, ['fec_vigencia_hasta', 'fecha_check_out']);
        if ($vigenciaInicio === null || $vigenciaFin === null) {
            return ['applied' => false, 'message' => 'El usuario no tiene vigencia completa para aplicar depósitos.'];
        }

        $lastApplication = $this->getLatestApplicationRow((int) $master['id_usuario_deposito_programado']);
        if ($tipoEvento === 'semanal' && $lastApplication === null) {
            return ['applied' => false, 'message' => 'El usuario aún no tiene una activación previa.'];
        }

        $start = $tipoEvento === 'activacion'
            ? $referenceDate->setTime(0, 0, 0)
            : ($lastApplication !== null
                ? $this->resolveDateTime((string) $lastApplication['periodo_fin'])?->add(new DateInterval('P1D'))
                : $vigenciaInicio);

        if ($start === null) {
            $start = $vigenciaInicio;
        }

        $start = $this->normalizeDateToStart($start);
        if ($start < $vigenciaInicio) {
            $start = $vigenciaInicio;
        }
        if ($start > $vigenciaFin) {
            return ['applied' => false, 'message' => 'La vigencia ya concluyó.'];
        }

        $end = $tipoEvento === 'activacion'
            ? $this->endOfWeekSunday($start)
            : $this->endOfWeekSunday($start);
        if ($end > $vigenciaFin) {
            $end = $vigenciaFin;
        }

        $days = $this->countInclusiveDays($start, $end);
        if ($days <= 0) {
            return ['applied' => false, 'message' => 'No hay días pendientes para aplicar.'];
        }

        $alreadyApplied = $this->db->table('usuario_deposito_programado_aplicacion')
            ->select('id_usuario_deposito_programado_aplicacion')
            ->where('id_usuario_deposito_programado', (int) $master['id_usuario_deposito_programado'])
            ->where('tipo_evento', $tipoEvento)
            ->where('periodo_inicio', $start->format('Y-m-d'))
            ->where('periodo_fin', $end->format('Y-m-d'))
            ->where('estatus_aplicacion', 'aplicado')
            ->where('visible', 1)
            ->limit(1)
            ->get()
            ->getRowArray();
        if (!empty($alreadyApplied)) {
            return ['applied' => false, 'message' => 'Ese periodo ya fue aplicado.'];
        }

        $dailyAmount = $this->resolveDailyAmount($user);
        $foodAmount = (int) ($user['tiene_alimentos'] ?? 0) === 1 ? round($dailyAmount * $days, 2) : 0.00;
        $hotelAmount = $tipoEvento === 'activacion' && empty($lastApplication) && (int) ($user['tiene_hospedaje'] ?? 0) === 1
            ? $this->resolveHospedajeAmount($user)
            : 0.00;
        $totalApplied = round($foodAmount + $hotelAmount, 2);
        if ($totalApplied <= 0) {
            return ['applied' => false, 'message' => 'El monto calculado es cero.'];
        }

        $saldoAnteriorAlimentos = round((float) ($user['monto_deposito'] ?? 0), 2);
        $saldoAnteriorHotel = round((float) ($user['monto_deposito_hotel'] ?? 0), 2);
        $saldoAnteriorReservado = round((float) ($user['monto_deposito_reservado'] ?? 0), 2);
        $saldoAnteriorOperativo = round((float) ($user['monto_deposito_operativo'] ?? 0), 2);

        $saldoNuevoAlimentos = round($saldoAnteriorAlimentos + $foodAmount, 2);
        $saldoNuevoHotel = $saldoAnteriorHotel > 0 ? $saldoAnteriorHotel : round($hotelAmount, 2);
        $saldoNuevoReservado = round(max(0.00, $saldoAnteriorReservado - $totalApplied), 2);
        $saldoNuevoOperativo = round($saldoAnteriorOperativo + $totalApplied, 2);
        $programStatus = $saldoNuevoReservado > 0 ? 'parcial' : 'aplicado';

        $this->db->transBegin();
        try {
            $paymentId = $this->insertPago([
                'id_usuario' => (int) $user['id_usuario'],
                'id_establecimiento' => (int) ($user['id_establecimiento'] ?? 0),
                'monto' => $totalApplied,
                'propina' => 0.00,
                'total' => $totalApplied,
                'usu_reg' => $actorUserId,
            ]);

            $movementId = $this->insertMovimiento([
                'id_usuario' => (int) $user['id_usuario'],
                'id_pago' => $paymentId,
                'tipo_movimiento' => 'abono',
                'tipo_origen' => $tipoEvento === 'activacion' ? 'deposito_activacion' : 'deposito_semanal',
                'creditos' => $totalApplied,
                'saldo_anterior' => $saldoAnteriorOperativo,
                'saldo_nuevo' => $saldoNuevoOperativo,
                'descripcion' => $this->buildMovementDescription($tipoEvento, $start, $end, $foodAmount, $hotelAmount),
                'usu_reg' => $actorUserId,
            ]);

            $applicationId = $this->insertApplicationLog([
                'id_usuario_deposito_programado' => (int) $master['id_usuario_deposito_programado'],
                'tipo_evento' => $tipoEvento,
                'periodo_inicio' => $start->format('Y-m-d'),
                'periodo_fin' => $end->format('Y-m-d'),
                'fecha_aplicacion' => $referenceDate->format('Y-m-d H:i:s'),
                'monto_aplicado' => $totalApplied,
                'id_pago' => $paymentId,
                'id_movimiento' => $movementId,
                'estatus_aplicacion' => 'aplicado',
                'detalle_error' => null,
                'intento' => $this->nextAttempt((int) $master['id_usuario_deposito_programado']),
                'usu_reg' => $actorUserId,
            ]);

            $this->db->table('usuario')
                ->where('id_usuario', (int) $user['id_usuario'])
                ->update([
                    'monto_deposito' => number_format($saldoNuevoAlimentos, 2, '.', ''),
                    'monto_deposito_hotel' => number_format($saldoNuevoHotel, 2, '.', ''),
                    'monto_deposito_reservado' => number_format($saldoNuevoReservado, 2, '.', ''),
                    'monto_deposito_operativo' => number_format($saldoNuevoOperativo, 2, '.', ''),
                    'deposito_programado_estatus' => $programStatus,
                    'fec_act' => $referenceDate->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            $this->db->table('usuario_deposito_programado')
                ->where('id_usuario_deposito_programado', (int) $master['id_usuario_deposito_programado'])
                ->update([
                    'monto_total_aplicado' => number_format($saldoAnteriorOperativo + $totalApplied, 2, '.', ''),
                    'estatus' => $programStatus,
                    'fec_act' => $referenceDate->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transacción de aplicación no pudo completarse.');
            }

            $this->db->transCommit();

            return [
                'applied' => true,
                'applied_amount' => $totalApplied,
                'program_row' => [
                    'id_usuario_deposito_programado' => (int) $master['id_usuario_deposito_programado'],
                    'id_usuario_deposito_programado_aplicacion' => $applicationId,
                    'periodo_inicio' => $start->format('Y-m-d'),
                    'periodo_fin' => $end->format('Y-m-d'),
                    'tipo_evento' => $tipoEvento,
                ],
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->markProgramError((int) ($user['id_usuario'] ?? 0), $tipoEvento, $referenceDate->format('Y-m-d H:i:s'), $actorUserId, $e->getMessage());
            log_message('error', 'DepositosProgramadosService.applyCurrentWindow: ' . $e->getMessage());
            return ['applied' => false, 'message' => $e->getMessage()];
        }
    }

    private function markProgramError(int $idUsuario, string $tipoEvento, string $referenceDate, int $actorUserId, string $message): void
    {
        $master = $this->getMasterProgramRow($idUsuario);
        if (empty($master)) {
            return;
        }

        $now = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));
        $this->db->transStart();
        $this->db->table('usuario_deposito_programado')
            ->where('id_usuario_deposito_programado', (int) $master['id_usuario_deposito_programado'])
            ->update([
                'estatus' => 'error',
                'observaciones' => trim('Error en ' . $tipoEvento . ': ' . $message),
                'fec_act' => $now->format('Y-m-d H:i:s'),
                'usu_act' => $actorUserId,
            ]);

        $this->db->table('usuario_deposito_programado_aplicacion')->insert([
            'id_usuario_deposito_programado' => (int) $master['id_usuario_deposito_programado'],
            'tipo_evento' => $tipoEvento,
            'periodo_inicio' => $now->format('Y-m-d'),
            'periodo_fin' => $now->format('Y-m-d'),
            'fecha_aplicacion' => $now->format('Y-m-d H:i:s'),
            'monto_aplicado' => 0.00,
            'id_pago' => null,
            'id_movimiento' => null,
            'estatus_aplicacion' => 'error',
            'detalle_error' => $message,
            'intento' => $this->nextAttempt((int) $master['id_usuario_deposito_programado']),
            'fec_reg' => $now->format('Y-m-d H:i:s'),
            'usu_reg' => $actorUserId,
            'visible' => 0,
        ]);
        $this->db->transComplete();
    }

    private function insertUser(array $data): int
    {
        $this->db->table('usuario')->insert($data);
        return (int) $this->db->insertID();
    }

    private function insertProgramSummaryRow(array $data): int
    {
        $payload = [
            'id_usuario' => (int) ($data['id_usuario'] ?? 0),
            'id_qr_cliente' => $data['id_qr_cliente'] ?? null,
            'tipo_evento' => (string) ($data['tipo_evento'] ?? 'alta'),
            'periodo_inicio' => $data['periodo_inicio'],
            'periodo_fin' => $data['periodo_fin'],
            'fecha_ejecucion_programada' => $data['fecha_ejecucion_programada'],
            'monto_diario' => number_format((float) ($data['monto_diario'] ?? 0), 2, '.', ''),
            'dias_programados' => (int) ($data['dias_programados'] ?? 0),
            'monto_total_reservado' => number_format((float) ($data['monto_total_reservado'] ?? 0), 2, '.', ''),
            'monto_total_aplicado' => number_format((float) ($data['monto_total_aplicado'] ?? 0), 2, '.', ''),
            'estatus' => (string) ($data['estatus'] ?? 'reservado'),
            'observaciones' => $data['observaciones'] ?? null,
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => (int) ($data['usu_reg'] ?? 0),
            'fec_act' => date('Y-m-d H:i:s'),
            'usu_act' => (int) ($data['usu_reg'] ?? 0),
            'visible' => 1,
        ];

        $existing = $this->db->table('usuario_deposito_programado')
            ->select('id_usuario_deposito_programado')
            ->where('id_usuario', $payload['id_usuario'])
            ->where('periodo_inicio', $payload['periodo_inicio'])
            ->where('periodo_fin', $payload['periodo_fin'])
            ->where('tipo_evento', $payload['tipo_evento'])
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (!empty($existing)) {
            $this->db->table('usuario_deposito_programado')
                ->where('id_usuario_deposito_programado', (int) $existing['id_usuario_deposito_programado'])
                ->update($payload);
            return (int) $existing['id_usuario_deposito_programado'];
        }

        $this->db->table('usuario_deposito_programado')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function insertApplicationLog(array $data): int
    {
        $payload = [
            'id_usuario_deposito_programado' => (int) ($data['id_usuario_deposito_programado'] ?? 0),
            'tipo_evento' => (string) ($data['tipo_evento'] ?? 'semanal'),
            'periodo_inicio' => $data['periodo_inicio'],
            'periodo_fin' => $data['periodo_fin'],
            'fecha_aplicacion' => $data['fecha_aplicacion'],
            'monto_aplicado' => number_format((float) ($data['monto_aplicado'] ?? 0), 2, '.', ''),
            'id_pago' => $data['id_pago'] ?? null,
            'id_movimiento' => $data['id_movimiento'] ?? null,
            'estatus_aplicacion' => (string) ($data['estatus_aplicacion'] ?? 'aplicado'),
            'detalle_error' => $data['detalle_error'] ?? null,
            'intento' => (int) ($data['intento'] ?? 1),
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => (int) ($data['usu_reg'] ?? 0),
            'fec_act' => date('Y-m-d H:i:s'),
            'usu_act' => (int) ($data['usu_reg'] ?? 0),
            'visible' => (int) ($data['visible'] ?? 1),
        ];

        $this->db->table('usuario_deposito_programado_aplicacion')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function insertPago(array $data): int
    {
        $payload = [
            'id_tipo_pago' => 1,
            'id_usuario' => (int) ($data['id_usuario'] ?? 0),
            'id_establecimiento' => (int) ($data['id_establecimiento'] ?? 0),
            'id_solicitud_pago' => null,
            'monto' => number_format((float) ($data['monto'] ?? 0), 2, '.', ''),
            'propina' => number_format((float) ($data['propina'] ?? 0), 2, '.', ''),
            'total' => number_format((float) ($data['total'] ?? 0), 2, '.', ''),
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => (int) ($data['usu_reg'] ?? 0),
            'visible' => 1,
        ];

        $this->db->table('pagos')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function insertMovimiento(array $data): int
    {
        $payload = [
            'id_usuario' => (int) ($data['id_usuario'] ?? 0),
            'id_pago' => $data['id_pago'] ?? null,
            'tipo_movimiento' => (string) ($data['tipo_movimiento'] ?? 'abono'),
            'tipo_origen' => (string) ($data['tipo_origen'] ?? 'deposito_programado'),
            'creditos' => number_format((float) ($data['creditos'] ?? 0), 2, '.', ''),
            'saldo_anterior' => number_format((float) ($data['saldo_anterior'] ?? 0), 2, '.', ''),
            'saldo_nuevo' => number_format((float) ($data['saldo_nuevo'] ?? 0), 2, '.', ''),
            'descripcion' => $data['descripcion'] ?? null,
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => (int) ($data['usu_reg'] ?? 0),
            'visible' => 1,
        ];

        $this->db->table('detalle_movimiento')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function buildMovementDescription(string $tipoEvento, DateTimeImmutable $inicio, DateTimeImmutable $fin, float $foodAmount, float $hotelAmount): string
    {
        $parts = [
            'Depósito programado ' . $tipoEvento,
            'Periodo ' . $inicio->format('Y-m-d') . ' a ' . $fin->format('Y-m-d'),
            'Alimentos $' . number_format($foodAmount, 2, '.', ','),
        ];

        if ($hotelAmount > 0) {
            $parts[] = 'Hospedaje $' . number_format($hotelAmount, 2, '.', ',');
        }

        return implode(' | ', $parts);
    }

    private function buildPartidaDepositAllocations(array $dataInsert, float $foodReserve, float $hotelReserve): array
    {
        $context = $this->resolver->resolve($dataInsert);

        $allocations = [];
        if ($foodReserve > 0 && (int) ($dataInsert['tiene_alimentos'] ?? 0) === 1) {
            $foodPartida = $this->resolveFoodPartidaByContext($context);
            if ($foodPartida === null) {
                return ['error' => true, 'respuesta' => 'No hay partida de alimentos configurada para el grupo del usuario.'];
            }

            $allocations[] = [
                'id_partida' => $foodPartida,
                'tipo' => 'alimentos',
                'monto' => $foodReserve,
            ];
        }

        if ($hotelReserve > 0 && (int) ($dataInsert['tiene_hospedaje'] ?? 0) === 1) {
            $allocations[] = [
                'id_partida' => 2,
                'tipo' => 'hospedaje',
                'monto' => $hotelReserve,
            ];
        }

        return [
            'error' => false,
            'data' => $this->mergePartidaAllocations($allocations),
        ];
    }

    private function resolveFoodPartidaByContext(array $context): ?int
    {
        $group = (string) ($context['active_group'] ?? '');
        $idTipoProveedor = (int) ($context['id_tipo_proveedor'] ?? 0);
        $idPerfil = (int) ($context['id_perfil'] ?? 0);

        if (in_array($group, ['secturi', 'secul'], true)) {
            return 1;
        }
        if ($group === 'fic') {
            return 3;
        }

        if ($idTipoProveedor > 0 || in_array($idPerfil, [2, 5, 7], true)) {
            return 0;
        }

        return 0;
    }

    private function mergePartidaAllocations(array $allocations): array
    {
        $merged = [];
        foreach ($allocations as $allocation) {
            $idPartida = (int) ($allocation['id_partida'] ?? 0);
            if ($idPartida <= 0) {
                continue;
            }

            if (!isset($merged[$idPartida])) {
                $merged[$idPartida] = [
                    'id_partida' => $idPartida,
                    'monto' => 0.00,
                    'tipos' => [],
                ];
            }

            $merged[$idPartida]['monto'] = round($merged[$idPartida]['monto'] + (float) ($allocation['monto'] ?? 0), 2);
            $tipo = trim((string) ($allocation['tipo'] ?? ''));
            if ($tipo !== '') {
                $merged[$idPartida]['tipos'][$tipo] = true;
            }
        }

        return array_values(array_map(static function (array $row): array {
            $row['tipo'] = implode('+', array_keys($row['tipos']));
            unset($row['tipos']);
            return $row;
        }, $merged));
    }

    private function applyPartidaReservations(array $allocations, int $actorUserId): void
    {
        foreach ($allocations as $allocation) {
            $idPartida = (int) ($allocation['id_partida'] ?? 0);
            $monto = round((float) ($allocation['monto'] ?? 0), 2);
            if ($idPartida <= 0 || $monto <= 0) {
                continue;
            }

            $partida = $this->db->query(
                'SELECT id_partida, partida, monto_presupuesto, monto_ejercido, monto_disponible, estatus, visible
                 FROM cat_partida
                 WHERE id_partida = ?
                 FOR UPDATE',
                [$idPartida]
            )->getRowArray();

            if (empty($partida)) {
                throw new RuntimeException('La partida presupuestal no existe o no está visible: ' . $idPartida);
            }

            $disponible = round((float) ($partida['monto_disponible'] ?? 0), 2);
            if ($monto > $disponible) {
                throw new RuntimeException(
                    'Presupuesto insuficiente en partida ' . ($partida['partida'] ?? $idPartida) .
                    '. Disponible: $' . number_format($disponible, 2, '.', ',') .
                    ', requerido: $' . number_format($monto, 2, '.', ',')
                );
            }

            $nuevoEjercido = round((float) ($partida['monto_ejercido'] ?? 0) + $monto, 2);
            $nuevoDisponible = round($disponible - $monto, 2);
            $presupuesto = round((float) ($partida['monto_presupuesto'] ?? 0), 2);
            $porcentaje = $presupuesto > 0 ? round(($nuevoEjercido / $presupuesto) * 100, 2) : 0.00;

            $this->db->table('cat_partida')
                ->where('id_partida', $idPartida)
                ->update([
                    'monto_ejercido' => number_format($nuevoEjercido, 2, '.', ''),
                    'monto_disponible' => number_format($nuevoDisponible, 2, '.', ''),
                    'porcentaje_ejercido' => number_format($porcentaje, 2, '.', ''),
                    'estatus' => $nuevoDisponible <= 0 ? 'agotada' : ($partida['estatus'] === 'agotada' ? 'activa' : $partida['estatus']),
                    'fec_act' => date('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);
        }
    }

    private function resolveDailyAmount(array $data): float
    {
        $amount = round((float) ($data['monto_deposito'] ?? 0), 2);
        if ($amount > 0) {
            return $amount;
        }

        $idNivel = (int) ($data['id_nivel_cliente'] ?? 0);
        if ($idNivel <= 0) {
            return 0.00;
        }

        $row = $this->db->table('cat_nivel_cliente')
            ->select('monto_deposito')
            ->where('id_nivel_cliente', $idNivel)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        return round((float) ($row['monto_deposito'] ?? 0), 2);
    }

    private function resolveHospedajeAmount(array $data): float
    {
        $amount = round((float) ($data['monto_deposito_hotel'] ?? 0), 2);
        if ($amount > 0) {
            return $amount;
        }

        $tarifaTotal = round((float) ($data['tarifa_total'] ?? 0), 2);
        if ($tarifaTotal > 0) {
            return $tarifaTotal;
        }

        $tarifaNoche = round((float) ($data['tarifa_noche'] ?? 0), 2);
        $noches = max(0, (int) ($data['noche'] ?? 0));

        return round($tarifaNoche * $noches, 2);
    }

    private function resolveUserDate(array $data, array $keys): ?DateTimeImmutable
    {
        foreach ($keys as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value === '') {
                continue;
            }

            $date = $this->resolveDateTime($value);
            if ($date !== null) {
                return $date->setTime(0, 0, 0);
            }
        }

        return null;
    }

    private function resolveDateTime(?string $value): ?DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value, new DateTimeZone(self::TZ));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeDateToStart(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(0, 0, 0);
    }

    private function endOfWeekSunday(DateTimeImmutable $date): DateTimeImmutable
    {
        $dayOfWeek = (int) $date->format('N');
        $offset = 7 - $dayOfWeek;

        return $date->modify('+' . $offset . ' days')->setTime(23, 59, 59);
    }

    private function countInclusiveDays(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        if ($end < $start) {
            return 0;
        }

        return $start->diff($end)->days + 1;
    }

    private function getUserRow(int $idUsuario): array
    {
        $row = $this->db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    private function getMasterProgramRow(int $idUsuario): array
    {
        $row = $this->db->table('usuario_deposito_programado')
            ->where('id_usuario', $idUsuario)
            ->where('tipo_evento', 'alta')
            ->where('visible', 1)
            ->orderBy('id_usuario_deposito_programado', 'DESC')
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    private function getLatestApplicationRow(int $idUsuarioDepositoProgramado): ?array
    {
        $row = $this->db->table('usuario_deposito_programado_aplicacion')
            ->where('id_usuario_deposito_programado', $idUsuarioDepositoProgramado)
            ->where('visible', 1)
            ->where('estatus_aplicacion', 'aplicado')
            ->orderBy('fecha_aplicacion', 'DESC')
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    private function nextAttempt(int $idUsuarioDepositoProgramado): int
    {
        $row = $this->db->table('usuario_deposito_programado_aplicacion')
            ->select('MAX(intento) AS intento')
            ->where('id_usuario_deposito_programado', $idUsuarioDepositoProgramado)
            ->get()
            ->getRowArray();

        return ((int) ($row['intento'] ?? 0)) + 1;
    }
}

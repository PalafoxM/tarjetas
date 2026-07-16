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
        $idEstablecimiento = $this->resolveRequiredEstablecimientoId($userRow);
        if ($idEstablecimiento <= 0) {
            $response->respuesta = 'Error | Debes seleccionar un establecimiento antes de guardar el usuario.';
            return $response;
        }
        $userRow['id_establecimiento'] = $idEstablecimiento;
        $userRow['monto_deposito'] = 0.00;
        $userRow['monto_deposito_hotel'] = round($hotelAmount, 2);
        $userRow['monto_deposito_reservado'] = $totalReserve;
        $userRow['monto_deposito_operativo'] = 0.00;
        $userRow['deposito_programado_estatus'] = $totalReserve > 0 ? 'reservado' : 'sin_programa';
        $userRow['fec_reg'] = $userRow['fec_reg'] ?? date('Y-m-d H:i:s');
        $userRow['fec_act'] = $userRow['fec_act'] ?? $userRow['fec_reg'];
        $userRow['usu_reg'] = $actorUserId;
        $userRow['usu_act'] = $actorUserId;
        $resolvedSequence = $this->resolveNextPaxSecuencia($userRow);
        $userRow['pax_secuencia'] = $resolvedSequence;
        $userRow['es_titular_folio'] = $resolvedSequence === 1 ? 1 : 0;
        if ($resolvedSequence > 1 && (int) ($userRow['id_usuario_padre'] ?? 0) <= 0) {
            $userRow['id_usuario_padre'] = $this->resolveFolioTitularUserId($userRow);
        }

        $this->db->transBegin();
        try {
            $idUsuario = $this->insertUser($userRow);
            if ($idUsuario <= 0) {
                throw new RuntimeException('No fue posible resolver el usuario creado.');
            }

            $this->applyPartidaReservations($allocationRows, $actorUserId);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Error de transaccion al reservar el deposito del usuario.');
            }

            $this->db->transCommit();

            $response->error = false;
            $response->respuesta = 'Registro guardado correctamente';
            $response->idRegistro = $idUsuario;
            $response->pax_secuencia = $resolvedSequence;
            $response->id_usuario_padre = $userRow['id_usuario_padre'] ?? null;
            $response->programa_id = 0;
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
        if (
            (int) ($user['activo_qr'] ?? 0) === 1
            && round((float) ($user['monto_deposito_operativo'] ?? 0), 2) > 0
        ) {
            $response->error = false;
            $response->respuesta = 'QR ya estaba activo y el deposito inicial ya fue aplicado.';
            $response->id_usuario = $idUsuario;
            $response->aplicado = 0.00;
            return $response;
        }
        if (empty($user)) {
            $response->respuesta = 'Error | El usuario no existe o no esta visible.';
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
                $result = [
                    'applied' => true,
                    'applied_amount' => 0.00,
                    'program_row' => null,
                    'message' => $result['message'] ?? 'QR activado sin deposito programado por aplicar.',
                ];
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Error de transaccion al activar el QR.');
            }

            $this->db->transCommit();

            $response->error = false;
            $response->respuesta = (float) ($result['applied_amount'] ?? 0) > 0
                ? 'QR activado y deposito aplicado correctamente'
                : 'QR activado correctamente. ' . (string) ($result['message'] ?? 'Sin deposito programado por aplicar.');
            $response->id_usuario = $idUsuario;
            $response->aplicado = $result['applied_amount'];
            $response->programa = $result['program_row'] ?? null;
            return $response;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->markProgramError($idUsuario, 'activacion', $referenceDate ?? date('Y-m-d H:i:s'), $actorUserId, $e->getMessage());
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
        $vigenciaInicio = $this->resolveUserDate($user, ['fec_vigencia_desde', 'fecha_check_in']);
        $vigenciaFin = $this->resolveUserDate($user, ['fec_vigencia_hasta', 'fecha_check_out']);
        if ($vigenciaInicio === null || $vigenciaFin === null) {
            return ['applied' => false, 'message' => 'El usuario no tiene vigencia completa para aplicar depositos.'];
        }

        $start = $tipoEvento === 'activacion'
            ? $referenceDate->setTime(0, 0, 0)
            : $referenceDate->modify('+1 day')->setTime(0, 0, 0);
        $start = $this->normalizeDateToStart($start);
        if ($start < $vigenciaInicio) {
            $start = $vigenciaInicio;
        }
        if ($start > $vigenciaFin) {
            return ['applied' => false, 'message' => 'La vigencia ya concluyo.'];
        }

        $end = $this->endOfWeekSunday($start);
        if ($end > $vigenciaFin) {
            $end = $vigenciaFin;
        }

        $days = $this->countInclusiveDays($start, $end);
        if ($days <= 0) {
            return ['applied' => false, 'message' => 'No hay dias pendientes para aplicar.'];
        }

        $dailyAmount = $this->resolveDailyAmount($user);
        $foodAmount = (int) ($user['tiene_alimentos'] ?? 0) === 1 ? round($dailyAmount * $days, 2) : 0.00;
        $hotelAmount = $tipoEvento === 'activacion' && (int) ($user['tiene_hospedaje'] ?? 0) === 1
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
        $saldoPendienteLiberar = round(max(0.00, $saldoAnteriorReservado - $saldoAnteriorOperativo), 2);
        if ($saldoPendienteLiberar <= 0) {
            return ['applied' => false, 'message' => 'No hay saldo programado pendiente por aplicar.'];
        }

        if ($totalApplied > $saldoPendienteLiberar) {
            if ($hotelAmount >= $saldoPendienteLiberar) {
                $hotelAmount = $saldoPendienteLiberar;
                $foodAmount = 0.00;
            } else {
                $foodAmount = round($saldoPendienteLiberar - $hotelAmount, 2);
            }
            $totalApplied = $saldoPendienteLiberar;
        }

        // El saldo reservado se conserva hasta que venza la vigencia;
        // lo que crece es el saldo operativo disponible para consumo.
        $saldoNuevoAlimentos = round($foodAmount, 2);
        $saldoNuevoHotel = round(max($saldoAnteriorHotel, $hotelAmount), 2);
        $saldoNuevoReservado = $saldoAnteriorReservado;
        $saldoNuevoOperativo = round($saldoAnteriorOperativo + $totalApplied, 2);
        $programStatus = $saldoNuevoOperativo >= $saldoAnteriorReservado ? 'aplicado' : 'operativo';

        $this->db->transBegin();
        try {
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

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transaccion de aplicacion no pudo completarse.');
            }

            $this->db->transCommit();

            return [
                'applied' => true,
                'applied_amount' => $totalApplied,
                'program_row' => [
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
        $now = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));
        $this->db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->update([
                'deposito_programado_estatus' => 'error',
                'fec_act' => $now->format('Y-m-d H:i:s'),
                'usu_act' => $actorUserId,
            ]);
    }
    private function insertUser(array $data): int
    {
        $this->db->table('usuario')->insert($data);
        return (int) $this->db->insertID();
    }

    private function resolveNextPaxSecuencia(array $data): int
    {
        $folioGrupo = trim((string) ($data['folio_grupo'] ?? ''));
        $requested = max(1, (int) ($data['pax_secuencia'] ?? 1));
        if ($folioGrupo === '') {
            return $requested;
        }

        $row = $this->db->table('usuario')
            ->selectMax('pax_secuencia', 'max_secuencia')
            ->where('visible', 1)
            ->where('folio_grupo', $folioGrupo)
            ->get()
            ->getRowArray();

        $maxSecuencia = (int) ($row['max_secuencia'] ?? 0);
        if ($maxSecuencia >= $requested) {
            return $maxSecuencia + 1;
        }

        return $requested;
    }

    private function resolveFolioTitularUserId(array $data): int
    {
        $folioGrupo = trim((string) ($data['folio_grupo'] ?? ''));
        if ($folioGrupo === '') {
            return 0;
        }

        $row = $this->db->table('usuario')
            ->select('id_usuario')
            ->where('visible', 1)
            ->where('folio_grupo', $folioGrupo)
            ->where('pax_secuencia', 1)
            ->orderBy('id_usuario', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return (int) ($row['id_usuario'] ?? 0);
    }

    private function resolveRequiredEstablecimientoId(array $data): int
    {
        $idEstablecimiento = (int) ($data['id_establecimiento'] ?? 0);
        if ($idEstablecimiento > 0) {
            return $idEstablecimiento;
        }

        $idPerfil = (int) ($data['id_perfil'] ?? 0);
        $idFic = (int) ($data['id_fic_perfil'] ?? 0);
        $idUg = (int) ($data['id_ug_perfil'] ?? 0);
        $idSecul = (int) ($data['id_secul_perfil'] ?? 0);
        $idSecturi = (int) ($data['id_secturi_perfil'] ?? 0);

        if ($idFic > 0 || $idUg > 0) {
            if ($idPerfil === 9) {
                return 90;
            }
            if ($idPerfil === 10) {
                return 91;
            }
        }

        if ($idSecul > 0) {
            return 89;
        }

        if ($idSecturi > 0) {
            return 85;
        }

        return 0;
    }

    private function insertProgramSummaryRow(array $data): int
    {
        return 0;
    }

    private function insertApplicationLog(array $data): int
    {
        return 0;
    }

    private function insertPago(array $data): int
    {
        return 0;
    }

    private function insertMovimiento(array $data): int
    {
        return 0;
    }

    private function buildMovementDescription(string $tipoEvento, DateTimeImmutable $inicio, DateTimeImmutable $fin, float $foodAmount, float $hotelAmount): string
    {
        $parts = [
            'Deposito programado ' . $tipoEvento,
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

    public function applyHospedajeCheckInConsumption(int $idUsuario, int $actorUserId = 0, ?string $referenceDate = null): object
    {
        $response = new stdClass();
        $response->error = true;
        $response->respuesta = 'Error | No fue posible aplicar el consumo de hospedaje.';

        $user = $this->getUserRow($idUsuario);
        if (empty($user)) {
            $response->respuesta = 'Error | El usuario no existe o no esta visible.';
            return $response;
        }

        if ((int) ($user['tiene_hospedaje'] ?? 0) !== 1) {
            $response->error = false;
            $response->respuesta = 'El usuario no tiene hospedaje programado.';
            $response->aplicado = 0.00;
            return $response;
        }

        $tarifaNoche = round((float) ($user['tarifa_noche'] ?? 0), 2);
        if ($tarifaNoche <= 0) {
            $totalHospedaje = round((float) ($user['monto_deposito_hotel'] ?? 0), 2);
            $nochesProgramadas = max(0, (int) ($user['noche'] ?? 0));
            if ($totalHospedaje > 0 && $nochesProgramadas > 0) {
                $tarifaNoche = round($totalHospedaje / $nochesProgramadas, 2);
            }
        }

        if ($tarifaNoche <= 0) {
            $response->respuesta = 'Error | No fue posible resolver la tarifa de hospedaje.';
            return $response;
        }

        $nochesProgramadas = max(1, (int) ($user['noche'] ?? 0));
        $hotelRestante = round((float) ($user['monto_deposito_hotel'] ?? 0), 2);
        if ($hotelRestante <= 0) {
            $hotelRestante = round($tarifaNoche * $nochesProgramadas, 2);
        }
        $montoAplicar = min($tarifaNoche, $hotelRestante);
        if ($montoAplicar <= 0) {
            $response->error = false;
            $response->respuesta = 'No hay saldo de hospedaje pendiente por aplicar.';
            $response->aplicado = 0.00;
            return $response;
        }

        $result = $this->consumeHospedajeAmount($user, $montoAplicar, $actorUserId, $referenceDate);
        if (!empty($result['error'])) {
            $response->respuesta = (string) ($result['message'] ?? 'Error | No fue posible aplicar el consumo de hospedaje.');
            return $response;
        }

        $response->error = false;
        $response->respuesta = 'Consumo de hospedaje aplicado correctamente.';
        $response->aplicado = $montoAplicar;
        $response->id_partida = 2;
        return $response;
    }

    public function processDailyProgrammedMaintenance(?string $referenceDate = null, int $actorUserId = 0): array
    {
        $now = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));
        $users = $this->db->table('usuario')
            ->select('id_usuario, id_establecimiento, id_partida, id_perfil, id_tipo_proveedor, id_fic_perfil, id_secul_perfil, id_ug_perfil, id_secturi_perfil, monto_deposito, monto_deposito_hotel, monto_deposito_reservado, monto_deposito_operativo, deposito_programado_estatus, activo_qr, fec_vigencia_desde, fec_vigencia_hasta, fecha_check_in, fecha_check_out, tarifa_total, tarifa_noche, noche, tiene_alimentos, tiene_hospedaje, id_nivel_cliente, visible')
            ->where('visible', 1)
            ->where('activo_qr', 1)
            ->groupStart()
                ->where('tiene_hospedaje', 1)
                ->orWhere('tiene_alimentos', 1)
            ->groupEnd()
            ->get()
            ->getResultArray();

        $result = [
            'ok' => true,
            'processed' => 0,
            'consumed' => 0,
            'released' => 0,
            'errors' => [],
        ];

        foreach ($users as $user) {
            $checkIn = trim((string) ($user['fecha_check_in'] ?? ''));
            $checkOut = trim((string) ($user['fecha_check_out'] ?? ''));
            $vigenciaFin = $this->resolveUserDate($user, ['fec_vigencia_hasta']);

            if ((int) ($user['tiene_hospedaje'] ?? 0) === 1 && $checkIn !== '') {
                if ($checkOut !== '') {
                    $released = $this->releaseHospedajePendingOnCheckout($user, $actorUserId, $now);
                    if (!empty($released['applied'])) {
                        $result['processed']++;
                        $result['released']++;
                    }
                } else {
                    $consumed = $this->processHospedajeNightlyConsumption($user, $actorUserId, $now);
                    if (!empty($consumed['applied'])) {
                        $result['processed']++;
                        $result['consumed']++;
                    }
                }
            }

            if ((int) ($user['tiene_alimentos'] ?? 0) === 1 && $vigenciaFin !== null && $now > $vigenciaFin) {
                $released = $this->releaseFoodExpiredBalance($user, $actorUserId, $now);
                if (!empty($released['applied'])) {
                    $result['processed']++;
                    $result['released']++;
                }
            }
        }

        return $result;
    }

    private function processHospedajeNightlyConsumption(array $user, int $actorUserId, DateTimeImmutable $referenceDate): array
    {
        $checkIn = $this->resolveUserDate($user, ['fecha_check_in']);
        if ($checkIn === null) {
            return ['applied' => false, 'message' => 'El usuario no tiene check in registrado.'];
        }

        if (!empty($user['fecha_check_out'])) {
            return ['applied' => false, 'message' => 'El hospedaje ya fue cerrado.'];
        }

        $tarifaNoche = round((float) ($user['tarifa_noche'] ?? 0), 2);
        $nochesProgramadas = max(1, (int) ($user['noche'] ?? 0));
        if ($tarifaNoche <= 0) {
            $totalHospedaje = round($nochesProgramadas * (float) ($user['monto_deposito_hotel'] ?? 0), 2);
            if ($totalHospedaje <= 0) {
                return ['applied' => false, 'message' => 'No fue posible resolver la tarifa de hospedaje.'];
            }
            $tarifaNoche = round($totalHospedaje / $nochesProgramadas, 2);
        }

        $hotelTotal = round($tarifaNoche * $nochesProgramadas, 2);
        $hotelRestante = round(max(0.00, (float) ($user['monto_deposito_hotel'] ?? 0)), 2);
        if ($hotelRestante <= 0) {
            $hotelRestante = $hotelTotal;
        }
        $hotelConsumido = round(max(0.00, $hotelTotal - $hotelRestante), 2);
        $nochesEsperadas = min($nochesProgramadas, $this->countInclusiveDays($checkIn, $referenceDate));
        $montoDeseado = round($nochesEsperadas * $tarifaNoche, 2);
        $montoAplicar = round(max(0.00, min($hotelRestante, $montoDeseado - $hotelConsumido)), 2);

        if ($montoAplicar <= 0) {
            return ['applied' => false, 'message' => 'No hay noches subsecuentes por consumir.'];
        }

        $result = $this->consumeHospedajeAmount($user, $montoAplicar, $actorUserId, $referenceDate->format('Y-m-d H:i:s'));
        if (!empty($result['error'])) {
            return ['applied' => false, 'message' => (string) ($result['message'] ?? 'No fue posible aplicar el consumo de hospedaje.')];
        }

        return ['applied' => true, 'applied_amount' => $montoAplicar];
    }

    public function releaseHospedajePendingOnCheckout(array $user, int $actorUserId, DateTimeImmutable $referenceDate): array
    {
        $idUsuario = (int) ($user['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return ['applied' => false, 'message' => 'Usuario invalido.'];
        }

        $hotelRestante = round(max(0.00, (float) ($user['monto_deposito_hotel'] ?? 0)), 2);
        if ($hotelRestante <= 0) {
            return ['applied' => false, 'message' => 'No hay saldo de hospedaje pendiente por liberar.'];
        }

        $saldoReservado = round((float) ($user['monto_deposito_reservado'] ?? 0), 2);
        $nuevoReservado = round(max(0.00, $saldoReservado - $hotelRestante), 2);

        $this->db->transBegin();
        try {
            $this->releasePartidaAmount(2, $hotelRestante, $actorUserId);

            $this->db->table('usuario')
                ->where('id_usuario', $idUsuario)
                ->update([
                    'monto_deposito_hotel' => number_format(0, 2, '.', ''),
                    'monto_deposito_reservado' => number_format($nuevoReservado, 2, '.', ''),
                    'deposito_programado_estatus' => 'cerrado',
                    'fec_act' => $referenceDate->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transaccion de cierre de hospedaje no pudo completarse.');
            }

            $this->db->transCommit();

            return ['applied' => true, 'applied_amount' => $hotelRestante];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'DepositosProgramadosService.releaseHospedajePendingOnCheckout: ' . $e->getMessage());
            return ['applied' => false, 'message' => $e->getMessage()];
        }
    }

    private function releaseFoodExpiredBalance(array $user, int $actorUserId, DateTimeImmutable $referenceDate): array
    {
        $idUsuario = (int) ($user['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return ['applied' => false, 'message' => 'Usuario invalido.'];
        }

        // Separamos primero el hospedaje pendiente para no devolverlo por la partida de alimentos.
        $saldoComidaPendiente = $this->resolveFoodPendingBalance($user);
        if ($saldoComidaPendiente <= 0) {
            return ['applied' => false, 'message' => 'No hay saldo de alimentos pendiente por liberar.'];
        }

        $saldoReservado = round((float) ($user['monto_deposito_reservado'] ?? 0), 2);
        $hotelRestante = round(max(0.00, (float) ($user['monto_deposito_hotel'] ?? 0)), 2);
        $context = $this->resolver->resolve($user);
        $idPartida = $this->resolveFoodPartidaByContext($context);
        if ($idPartida === null) {
            return ['applied' => false, 'message' => 'No hay partida de alimentos configurada para el grupo del usuario.'];
        }

        $this->db->transBegin();
        try {
            $this->releasePartidaAmount($idPartida, $saldoComidaPendiente, $actorUserId);

            $nuevoReservado = round(max(0.00, $saldoReservado - $saldoComidaPendiente), 2);
            $nuevoEstado = $hotelRestante > 0 ? 'operativo' : 'vencido';
            $this->db->table('usuario')
                ->where('id_usuario', $idUsuario)
                ->update([
                    'monto_deposito_reservado' => number_format($nuevoReservado, 2, '.', ''),
                    'deposito_programado_estatus' => $nuevoEstado,
                    'fec_act' => $referenceDate->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transaccion de liberacion de alimentos no pudo completarse.');
            }

            $this->db->transCommit();

            return ['applied' => true, 'applied_amount' => $saldoComidaPendiente, 'id_partida' => $idPartida];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'DepositosProgramadosService.releaseFoodExpiredBalance: ' . $e->getMessage());
            return ['applied' => false, 'message' => $e->getMessage()];
        }
    }

    private function resolveFoodPendingBalance(array $user): float
    {
        $saldoReservado = round((float) ($user['monto_deposito_reservado'] ?? 0), 2);
        $saldoOperativo = round((float) ($user['monto_deposito_operativo'] ?? 0), 2);
        $hotelRestante = round(max(0.00, (float) ($user['monto_deposito_hotel'] ?? 0)), 2);

        $saldoAlimentosReservado = round(max(0.00, $saldoReservado - $hotelRestante), 2);

        return round(max(0.00, $saldoAlimentosReservado - $saldoOperativo), 2);
    }

    private function consumeHospedajeAmount(array $user, float $montoAplicar, int $actorUserId, ?string $referenceDate = null): array
    {
        $idUsuario = (int) ($user['id_usuario'] ?? 0);
        if ($idUsuario <= 0 || $montoAplicar <= 0) {
            return ['error' => true, 'message' => 'No fue posible resolver el consumo de hospedaje.'];
        }

        $saldoReservado = round((float) ($user['monto_deposito_reservado'] ?? 0), 2);
        $saldoOperativo = round((float) ($user['monto_deposito_operativo'] ?? 0), 2);
        $hotelRestante = round(max(0.00, (float) ($user['monto_deposito_hotel'] ?? 0)), 2);
        $montoAplicar = round(min($montoAplicar, $hotelRestante), 2);
        if ($montoAplicar <= 0) {
            return ['error' => true, 'message' => 'No hay saldo de hospedaje pendiente por aplicar.'];
        }

        $partidaHospedaje = 2;
        $fechaAct = $this->resolveDateTime($referenceDate) ?? new DateTimeImmutable('now', new DateTimeZone(self::TZ));

        $this->db->transBegin();
        try {
            $nuevoHotelRestante = round(max(0.00, $hotelRestante - $montoAplicar), 2);
            $nuevoOperativo = round($saldoOperativo + $montoAplicar, 2);
            $nuevoEstado = $nuevoHotelRestante > 0 ? 'operativo' : 'aplicado';

            $this->db->table('usuario')
                ->where('id_usuario', $idUsuario)
                ->update([
                    'monto_deposito_hotel' => number_format($nuevoHotelRestante, 2, '.', ''),
                    'monto_deposito_operativo' => number_format($nuevoOperativo, 2, '.', ''),
                    'deposito_programado_estatus' => $nuevoEstado,
                    'fec_act' => $fechaAct->format('Y-m-d H:i:s'),
                    'usu_act' => $actorUserId,
                ]);

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('La transaccion de consumo de hospedaje no pudo completarse.');
            }

            $this->db->transCommit();

            return [
                'error' => false,
                'applied' => true,
                'applied_amount' => $montoAplicar,
                'id_partida' => $partidaHospedaje,
                'saldo_reservado' => $saldoReservado,
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'DepositosProgramadosService.consumeHospedajeAmount: ' . $e->getMessage());
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    private function releasePartidaAmount(int $idPartida, float $monto, int $actorUserId): void
    {
        $idPartida = (int) $idPartida;
        $monto = round($monto, 2);
        if ($idPartida <= 0 || $monto <= 0) {
            return;
        }

        $partida = $this->db->query(
            'SELECT id_partida, partida, monto_presupuesto, monto_ejercido, monto_disponible, estatus, visible
             FROM cat_partida
             WHERE id_partida = ?
             FOR UPDATE',
            [$idPartida]
        )->getRowArray();

        if (empty($partida)) {
            throw new RuntimeException('La partida presupuestal no existe o no esta visible: ' . $idPartida);
        }

        $disponible = round((float) ($partida['monto_disponible'] ?? 0), 2);
        $ejercido = round((float) ($partida['monto_ejercido'] ?? 0), 2);
        $nuevoEjercido = round(max(0.00, $ejercido - $monto), 2);
        $nuevoDisponible = round($disponible + $monto, 2);
        $presupuesto = round((float) ($partida['monto_presupuesto'] ?? 0), 2);
        $porcentaje = $presupuesto > 0 ? round(($nuevoEjercido / $presupuesto) * 100, 2) : 0.00;

        $this->db->table('cat_partida')
            ->where('id_partida', $idPartida)
            ->update([
                'monto_ejercido' => number_format($nuevoEjercido, 2, '.', ''),
                'monto_disponible' => number_format($nuevoDisponible, 2, '.', ''),
                'porcentaje_ejercido' => number_format($porcentaje, 2, '.', ''),
                'estatus' => $nuevoDisponible > 0 ? 'activa' : ($partida['estatus'] ?? 'activa'),
                'fec_act' => date('Y-m-d H:i:s'),
                'usu_act' => $actorUserId,
            ]);
    }

    private function resolveFoodPartidaByContext(array $context): ?int
    {
        $group = (string) ($context['active_group'] ?? '');
        $idTipoProveedor = (int) ($context['id_tipo_proveedor'] ?? 0);
        $idPerfil = (int) ($context['id_perfil'] ?? 0);

        if (in_array($group, ['secturi', 'secul'], true)) {
            return 1;
        }
        if (in_array($group, ['fic', 'ug'], true)) {
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
            if ($idPartida < 0) {
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
            if ($idPartida < 0 || $monto <= 0) {
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
                throw new RuntimeException('La partida presupuestal no existe o no esta visible: ' . $idPartida);
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
        return [];
    }

    private function getLatestApplicationRow(int $idUsuarioDepositoProgramado): ?array
    {
        return null;
    }

    private function nextAttempt(int $idUsuarioDepositoProgramado): int
    {
        return 1;
    }
}

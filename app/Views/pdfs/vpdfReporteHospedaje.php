<?php
$titulo = (string) ($titulo ?? 'Reporte de hospedaje');
$subtitulo = (string) ($subtitulo ?? 'Establecimiento');
$periodoLabel = (string) ($periodo_label ?? 'Sin registros de hospedaje');
$rows = array_values(is_array($rows ?? null) ? $rows : []);
$resumen = is_array($resumen ?? null) ? $resumen : [];

$formatFecha = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
};
$formatTipoHabitacion = static function ($value): string {
    $texto = trim((string) $value);
    if ($texto === '') {
        return 'Sin definir';
    }

    if (!is_numeric($texto)) {
        return $texto;
    }

    $mapa = [
        1 => 'SENCILLA',
        2 => 'DOBLE',
        3 => 'TRIPLE',
        4 => 'CUÁDRUPLE',
        5 => 'SUITE',
    ];

    $id = (int) $texto;
    return $mapa[$id] ?? 'Habitacion #' . $id;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($titulo) ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; padding: 15px 10px; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 15px; margin-bottom: 18px; text-align: center; }
        .title-main { font-size: 22px; font-weight: bold; color: #000000; }
        .title-sub { font-size: 18px; font-weight: bold; color: #000000; margin-top: 2px; }
        .subtitle { font-size: 14px; color: #475569; margin-top: 6px; }
        .subtitle-bold { font-size: 14px; font-weight: bold; color: #475569; margin-top: 6px; }
        .period { text-align: center; font-size: 12pt; margin-top: 14px; margin-bottom: 0; color: #475569; }

        .summary { width: 100%; border-collapse: collapse; margin: 14px 0 14px; }
        .summary td { border: 1px solid #d1d5db; padding: 6px 8px; }
        .summary .label { background: #f3f4f6; font-weight: bold; color: #111827; width: 20%; }
        .summary .value { width: 30%; }

        .table-container { margin-top: 14px; }
        table.report { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        table.report th, table.report td { border: 1px solid #9ca3af; padding: 4px 6px; vertical-align: top; }
        table.report th { background: #0f172a; color: #ffffff; text-align: left; font-weight: bold; }
        .money { text-align: right; font-weight: bold; }
        .empty { border: 1px solid #d1d5db; background: #f9fafb; padding: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-main">SECRETARÍA DE TURISMO E IDENTIDAD</div>
        <div class="title-sub">54 FESTIVAL INTERNACIONAL CERVANTINO</div>
        <div class="title-sub"><?= esc(strtoupper($titulo)) ?></div>
        <div class="subtitle-bold"><?= esc($subtitulo) ?></div>
        <div class="period"><?= esc($periodoLabel) ?></div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Total registros</td>
            <td class="value"><?= esc((string) ($resumen['total_registros'] ?? 0)) ?></td>
            <td class="label">Check-in</td>
            <td class="value"><?= esc((string) ($resumen['check_in'] ?? 0)) ?></td>
        </tr>
        <tr>
            <td class="label">Check-out</td>
            <td class="value"><?= esc((string) ($resumen['check_out'] ?? 0)) ?></td>
            <td class="label">Total tarifa</td>
            <td class="value money">$<?= number_format((float) ($resumen['total_tarifa'] ?? 0), 2) ?></td>
        </tr>
    </table>

    <div class="table-container">
        <?php if (empty($rows)): ?>
            <div class="empty">Sin registros de hospedaje para el establecimiento seleccionado.</div>
        <?php else: ?>
            <table class="report">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Huésped</th>
                        <th>Fecha check-in</th>
                        <th>Fecha check-out</th>
                        <th>Tipo habitación</th>
                        <th>Tarifa noche</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $folio = trim((string) ($row['folio'] ?? $row['id_usuario'] ?? ''));
                            $huesped = trim((string) ($row['nombre_completo'] ?? ''));
                            $tipoHabitacion = $formatTipoHabitacion($row['tipo_habitacion'] ?? $row['id_tipo_habitacion'] ?? '');
                            $observaciones = trim((string) ($row['observaciones_hospedaje'] ?? '-'));
                        ?>
                        <tr>
                            <td><?= esc($folio !== '' ? $folio : 'Sin folio') ?></td>
                            <td><?= esc($huesped !== '' ? $huesped : 'Sin nombre') ?></td>
                            <td><?= esc($formatFecha($row['fecha_check_in'] ?? '')) ?></td>
                            <td><?= esc($formatFecha($row['fecha_check_out'] ?? '')) ?></td>
                            <td><?= esc($tipoHabitacion !== '' ? $tipoHabitacion : 'Sin definir') ?></td>
                            <td class="money">$<?= number_format((float) ($row['tarifa_noche'] ?? 0), 2) ?></td>
                            <td><?= esc($observaciones !== '' ? $observaciones : '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
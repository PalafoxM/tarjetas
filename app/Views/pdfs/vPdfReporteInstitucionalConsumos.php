<?php
$titulo = (string) ($titulo ?? 'Reporte institucional de hospedaje y alimentos');
$grupo = (string) ($grupo ?? 'Institucional');
$generadoEn = (string) ($generado_en ?? date('Y-m-d H:i:s'));
$hospedaje = is_array($hospedaje ?? null) ? $hospedaje : [];
$alimentos = is_array($alimentos ?? null) ? $alimentos : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$hospedajeRows = array_values(is_array($hospedaje['rows'] ?? null) ? $hospedaje['rows'] : []);
$alimentosRows = array_values(is_array($alimentos['rows'] ?? null) ? $alimentos['rows'] : []);
$hospedajeResumen = is_array($hospedaje['resumen'] ?? null) ? $hospedaje['resumen'] : [];
$alimentosResumen = is_array($alimentos['resumen'] ?? null) ? $alimentos['resumen'] : [];

$formatMoney = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};

$formatNumber = static function ($value): string {
    return number_format((float) $value, 0);
};

$formatDateTime = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Sin fecha';
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($titulo) ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; padding: 10px 6px; font-size: 8.5pt; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 12px; text-align: center; }
        .title-main { font-size: 19px; font-weight: bold; color: #000000; }
        .title-sub { font-size: 14px; font-weight: bold; color: #111827; margin-top: 3px; }
        .meta { font-size: 9pt; color: #475569; margin-top: 4px; }
        .summary { width: 100%; border-collapse: collapse; margin: 8px 0 12px; }
        .summary td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: center; }
        .summary .label { background: #eff6ff; font-weight: bold; color: #111827; width: 16%; }
        .summary .value { width: 17%; }
        .section-title { font-size: 12px; font-weight: bold; color: #0f172a; margin: 12px 0 6px; padding: 5px 7px; background: #e2e8f0; border: 1px solid #cbd5e1; }
        table.report { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 10px; }
        table.report th, table.report td { border: 1px solid #94a3b8; padding: 4px 5px; vertical-align: top; }
        table.report th { background: #0f172a; color: #ffffff; text-align: center; font-weight: bold; font-size: 7.3pt; }
        table.report td { font-size: 7.2pt; }
        table.report tr:nth-child(even) td { background: #f8fafc; }
        .money { text-align: right; font-weight: bold; white-space: nowrap; }
        .center { text-align: center; }
        .empty { border: 1px solid #d1d5db; background: #f9fafb; padding: 14px; text-align: center; margin-bottom: 10px; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-main">SECRETARIA DE TURISMO E IDENTIDAD</div>
        <div class="title-sub"><?= esc(strtoupper($titulo)) ?></div>
        <div class="title-sub">PERFIL <?= esc(strtoupper($grupo)) ?></div>
        <div class="meta">Generado: <?= esc($formatDateTime($generadoEn)) ?></div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Hospedaje reservado</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_hospedaje_reservado'] ?? 0)) ?></td>
            <td class="label">Hospedaje consumido</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_hospedaje_consumido'] ?? 0)) ?></td>
            <td class="label">Alimentos consumido</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_alimentos_consumido'] ?? 0)) ?></td>
        </tr>
        <tr>
            <td class="label">Total reservado</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_reservado'] ?? 0)) ?></td>
            <td class="label">Total consumido</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_consumido'] ?? 0)) ?></td>
            <td class="label">Total general</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_general'] ?? 0)) ?></td>
        </tr>
    </table>

    <div class="section-title">Hospedaje por hotel y tipo de habitacion</div>
    <table class="summary">
        <tr>
            <td class="label">Hoteles</td>
            <td class="value"><?= esc((string) ($hospedajeResumen['total_hoteles'] ?? 0)) ?></td>
            <td class="label">Habitaciones reservadas</td>
            <td class="value"><?= esc($formatNumber($hospedajeResumen['total_habitaciones_reservadas'] ?? 0)) ?></td>
            <td class="label">Habitaciones utilizadas</td>
            <td class="value"><?= esc($formatNumber($hospedajeResumen['total_habitaciones_utilizadas'] ?? 0)) ?></td>
        </tr>
        <tr>
            <td class="label">Pax reservado</td>
            <td class="value"><?= esc($formatNumber($hospedajeResumen['total_pax_reservado'] ?? 0)) ?></td>
            <td class="label">Pax utilizado</td>
            <td class="value"><?= esc($formatNumber($hospedajeResumen['total_pax_utilizado'] ?? 0)) ?></td>
            <td class="label">Noches consumidas</td>
            <td class="value"><?= esc($formatNumber($hospedajeResumen['total_noches_consumidas'] ?? 0)) ?></td>
        </tr>
    </table>

    <?php if (empty($hospedajeRows)): ?>
        <div class="empty">Sin hospedaje asignado para el perfil seleccionado.</div>
    <?php else: ?>
        <table class="report">
            <thead>
                <tr>
                    <th width="19%">Hotel</th>
                    <th width="12%">Habitacion</th>
                    <th width="7%">Reservadas</th>
                    <th width="7%">Utilizadas</th>
                    <th width="7%">Pax res.</th>
                    <th width="7%">Pax util.</th>
                    <th width="8%">Noches res.</th>
                    <th width="8%">Noches cons.</th>
                    <th width="9%">Tarifa</th>
                    <th width="8%">Reservado</th>
                    <th width="8%">Consumido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hospedajeRows as $row): ?>
                    <tr>
                        <td><?= esc((string) ($row['hotel_nombre'] ?? 'Hotel sin definir')) ?></td>
                        <td><?= esc((string) ($row['tipo_habitacion'] ?? 'Sin definir')) ?></td>
                        <td class="center"><?= esc($formatNumber($row['habitaciones_reservadas'] ?? $row['habitaciones'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['habitaciones_utilizadas'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['pax_reservado'] ?? $row['pax_asignado'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['pax_utilizado'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['noches_reservadas'] ?? $row['noches'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['noches_consumidas'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['tarifa_noche'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['monto_reservado'] ?? $row['monto_total'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['monto_consumido'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="section-title">Alimentos por restaurante</div>
    <table class="summary">
        <tr>
            <td class="label">Restaurantes</td>
            <td class="value"><?= esc((string) ($alimentosResumen['total_restaurantes'] ?? 0)) ?></td>
            <td class="label">Usuarios</td>
            <td class="value"><?= esc($formatNumber($alimentosResumen['total_usuarios'] ?? 0)) ?></td>
            <td class="label">Consumos</td>
            <td class="value"><?= esc($formatNumber($alimentosResumen['total_consumos'] ?? 0)) ?></td>
        </tr>
    </table>

    <?php if (empty($alimentosRows)): ?>
        <div class="empty">Sin consumos de alimentos para el perfil seleccionado.</div>
    <?php else: ?>
        <table class="report">
            <thead>
                <tr>
                    <th width="33%">Restaurante</th>
                    <th width="17%">Tipo</th>
                    <th width="10%">Usuarios</th>
                    <th width="10%">Consumos</th>
                    <th width="10%">Consumo</th>
                    <th width="10%">Propina</th>
                    <th width="10%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alimentosRows as $row): ?>
                    <tr>
                        <td><?= esc((string) ($row['restaurante_nombre'] ?? 'Restaurante sin definir')) ?></td>
                        <td><?= esc((string) ($row['tipo_establecimiento'] ?? '')) ?></td>
                        <td class="center"><?= esc($formatNumber($row['usuarios'] ?? 0)) ?></td>
                        <td class="center"><?= esc($formatNumber($row['consumos'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['monto_consumo'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['propina'] ?? 0)) ?></td>
                        <td class="money"><?= esc($formatMoney($row['total_alimentos'] ?? $row['monto_consumido'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="muted">
        Hospedaje reservado corresponde a habitaciones asignadas. Hospedaje consumido considera registros con check-in.
        Alimentos muestra solo consumos realizados/autorizados para el perfil seleccionado.
    </div>
</body>
</html>

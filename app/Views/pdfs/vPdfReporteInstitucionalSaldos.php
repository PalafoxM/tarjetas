<?php
$titulo = (string) ($titulo ?? 'Reporte de saldos institucionales');
$grupo = (string) ($grupo ?? 'Institucional');
$generadoEn = (string) ($generado_en ?? date('Y-m-d H:i:s'));
$periodoLabel = (string) ($periodo_label ?? 'Todos los usuarios visibles del grupo');
$rows = array_values(is_array($rows ?? null) ? $rows : []);
$resumen = is_array($resumen ?? null) ? $resumen : [];

$formatMoney = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};

$formatDate = static function ($value): string {
    $value = trim((string) $value);
    if ($value === '') {
        return 'Sin fecha';
    }

    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : $value;
};

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($titulo) ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; padding: 12px 8px; text-align: center; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 14px; text-align: center; }
        .title-main { font-size: 20px; font-weight: bold; color: #000000; }
        .title-sub { font-size: 15px; font-weight: bold; color: #111827; margin-top: 3px; }
        .meta { font-size: 10.5pt; color: #475569; margin-top: 5px; }
        .summary { width: 100%; border-collapse: collapse; margin: 12px 0 14px; }
        .summary td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        .summary .label { background: #eff6ff; font-weight: bold; color: #111827; width: 18%; }
        .summary .value { width: 32%; text-align: center; }
        .money { text-align: center; font-weight: bold; white-space: nowrap; }
        .table-container { margin-top: 10px; }
        table.report { width: 100%; border-collapse: collapse; font-size: 7.6pt; table-layout: fixed; }
        table.report th, table.report td { border: 1px solid #94a3b8; padding: 4px 5px; vertical-align: top; }
        table.report th { background: #0f172a; color: #ffffff; text-align: center; font-weight: bold; }
        table.report tr:nth-child(even) td { background: #f8fafc; }
        .empty { border: 1px solid #d1d5db; background: #f9fafb; padding: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title-main">SECRETARÍA DE TURISMO E IDENTIDAD</div>
        <div class="title-sub"><?= esc(strtoupper($titulo)) ?></div>
        <div class="title-sub">PERFIL <?= esc(strtoupper($grupo)) ?></div>
        <div class="meta"><?= esc($periodoLabel) ?></div>
        <div class="meta">Generado: <?= esc($formatDate($generadoEn)) ?></div>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Total usuarios</td>
            <td class="value"><?= esc((string) ($resumen['total_usuarios'] ?? 0)) ?></td>
            <td class="label">Total reservado</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_reservado'] ?? 0)) ?></td>
        </tr>
        <tr>
            <td class="label">Total operativo</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_operativo'] ?? 0)) ?></td>
            <td class="label">Total pendiente</td>
            <td class="value money"><?= esc($formatMoney($resumen['total_pendiente'] ?? 0)) ?></td>
        </tr>
    </table>

    <div class="table-container">
        <?php if (empty($rows)): ?>
            <div class="empty">Sin usuarios visibles para generar el reporte.</div>
        <?php else: ?>
            <table class="report">
                <thead>
                    <tr>
                        <th width="6%">Folio</th>
                        <th width="7%">Usuario</th>
                        <th width="17%">Nombre</th>
                        <th width="7%">Perfil</th>
                        <th width="9%">Beneficios</th>
                        <th width="12%">Tarifa diaria</th>
                        <th width="12%">Vigencia</th>
                        <th width="5%">Días</th>
                        <th width="8%">Reservado</th>
                        <th width="8%">Operativo</th>
                        <th width="9%">Pendiente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $folio = trim((string) ($row['folio'] ?? ''));
                            $subFolio = trim((string) ($row['sub_folio'] ?? ''));
                            $folioLabel = trim($folio . ($subFolio !== '' ? '-' . $subFolio : ''));
                            $vigencia = $formatDate($row['vigencia_desde'] ?? '') . ' al ' . $formatDate($row['vigencia_hasta'] ?? '');
                        ?>
                        <tr>
                            <td><?= esc($folioLabel !== '' ? $folioLabel : 'Sin folio') ?></td>
                            <td><?= esc((string) ($row['usuario'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['nombre_completo'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['perfil'] ?? '')) ?></td>
                            <td><?= esc((string) ($row['beneficios'] ?? '')) ?></td>
                            <td class="money"><?= esc($formatMoney($row['tarifa_diaria'] ?? 0)) ?></td>
                            <td><?= esc($vigencia) ?></td>
                            <td class="money"><?= esc((string) ($row['dias_vigencia'] ?? 0)) ?></td>
                            <td class="money"><?= esc($formatMoney($row['monto_reservado'] ?? 0)) ?></td>
                            <td class="money"><?= esc($formatMoney($row['monto_operativo'] ?? 0)) ?></td>
                            <td class="money"><?= esc($formatMoney($row['monto_pendiente'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$establecimiento = is_object($establecimientoConsumos ?? null)
    ? get_object_vars($establecimientoConsumos)
    : (array) ($establecimientoConsumos ?? []);
$pagos = array_values(array_map(static function ($pago): array {
    return is_object($pago) ? get_object_vars($pago) : (array) $pago;
}, is_array($pagosConsumos ?? null) ? $pagosConsumos : []));
$factura = is_object($facturaConsumos ?? null)
    ? get_object_vars($facturaConsumos)
    : (array) ($facturaConsumos ?? []);

$resumen = array_reduce($pagos, static function (array $total, array $pago): array {
    $total['monto'] += (float) ($pago['monto'] ?? 0);
    $total['propina'] += (float) ($pago['propina'] ?? 0);
    $total['total'] += (float) ($pago['total'] ?? 0);
    return $total;
}, ['monto' => 0.0, 'propina' => 0.0, 'total' => 0.0]);

$moneda = static fn ($valor): string => '$' . number_format((float) $valor, 2, '.', ',');
$fecha = static function ($valor): string {
    $timestamp = strtotime((string) $valor);
    return $timestamp !== false ? date('d/m/Y H:i:s', $timestamp) : 'Sin fecha';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de consumos</title>
    <style>
        body {
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
        }
        h1 {
            margin: 0 0 3mm;
            font-size: 16pt;
            text-align: center;
        }
        .subtitle {
            margin-bottom: 5mm;
            color: #374151;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 0.2mm solid #6b7280;
            padding: 2mm;
            vertical-align: middle;
        }
        th {
            background: #1f2937;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
        }
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        .summary {
            margin-bottom: 5mm;
        }
        .summary td {
            width: 25%;
            background: #f3f4f6;
            text-align: center;
        }
        .summary-label {
            display: block;
            color: #4b5563;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .summary-value {
            display: block;
            margin-top: 1mm;
            font-size: 11pt;
            font-weight: bold;
        }
        .number {
            text-align: right;
            white-space: nowrap;
        }
        .center {
            text-align: center;
        }
        .empty {
            padding: 12mm;
            border: 0.2mm solid #9ca3af;
            color: #4b5563;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>LISTADO DE CONSUMOS</h1>
    <div class="subtitle">
        <strong><?= esc((string) ($establecimiento['dsc_establecimiento'] ?? 'Establecimiento')) ?></strong>
        <?php if (!empty($establecimiento['no_proveedor'])): ?>
            &nbsp; | &nbsp; No. proveedor: <?= esc((string) $establecimiento['no_proveedor']) ?>
        <?php endif; ?>
        <?php if (!empty($factura['id_factura'])): ?>
            &nbsp; | &nbsp; Factura interna: #<?= (int) $factura['id_factura'] ?>
        <?php endif; ?>
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="summary-label">Pagos</span>
                <span class="summary-value"><?= count($pagos) ?></span>
            </td>
            <td>
                <span class="summary-label">Consumo</span>
                <span class="summary-value"><?= esc($moneda($resumen['monto'])) ?></span>
            </td>
       
            <td>
                <span class="summary-label">Total pagado</span>
                <span class="summary-value"><?= esc($moneda($resumen['total'])) ?></span>
            </td>
        </tr>
    </table>

    <?php if (!empty($pagos)): ?>
        <table>
            <thead>
                <tr>
                    <th width="7%">Pago</th>
                    <th width="10%">Folio</th>
                    <th width="10%">Cliente</th>
                    <th width="11%">Consumo</th>
                    <th width="11%">Total</th>
                    <th width="13%">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $pago): ?>
                    <?php $metodo = strtoupper(trim((string) ($pago['metodo_autorizacion'] ?? ''))); ?>
                    <tr>
                        <td class="center">#<?= (int) ($pago['id_pago'] ?? 0) ?></td>
                        <td><?= esc((string) ($pago['folio_solicitud'] ?? $pago['id_solicitud_pago'] ?? 'Sin folio')) ?></td>
                        <td><?= esc(trim((string) ($pago['cliente'] ?? '')) ?: 'Sin nombre') ?></td>
                        <td class="number"><?= esc($moneda($pago['monto'] ?? 0)) ?></td>
                        
                        <td class="number"><strong><?= esc($moneda($pago['total'] ?? 0)) ?></strong></td>
                        <td class="center"><?= esc($fecha($pago['fec_reg'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">Este establecimiento no tiene pagos registrados.</div>
    <?php endif; ?>
</body>
</html>

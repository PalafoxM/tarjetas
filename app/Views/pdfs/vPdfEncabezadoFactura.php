<?php
$facturaXmlContext = is_array($facturaXmlContext ?? null) ? $facturaXmlContext : [];
$xmlInfo = is_array($facturaXmlContext['xml_info'] ?? null) ? $facturaXmlContext['xml_info'] : [];

$fechaXml = trim((string) ($xmlInfo['fecha'] ?? $facturaXmlContext['fecha_emision'] ?? date('Y-m-d H:i:s')));
$fechaGasto = $fechaXml !== '' && strtotime($fechaXml) !== false ? date('d/m/Y', strtotime($fechaXml)) : date('d/m/Y');
$folioFactura = trim((string) ($xmlInfo['folio'] ?? $facturaXmlContext['folio_formato'] ?? ''));
$montoTotal = (float) ($xmlInfo['total'] ?? $facturaXmlContext['monto_total'] ?? 0);
$importe = $montoTotal > 0 ? number_format($montoTotal, 2, '.', ',') : '0.00';
$partida = trim((string) ($partida ?? '3390'));
$dscPartida = trim((string) ($dsc_partida ?? 'Servicios integrales'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            margin: 8mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 7mm;
        }
        th, td {
            border: 1px solid #000;
            padding: 2.2mm;
            text-align: left;
            vertical-align: middle;
        }
        .header-bg {
            background-color: #000;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 3mm;
            font-size: 13pt;
        }
        .label-cell {
            background-color: #ccc;
            font-weight: bold;
            width: 30%;
            font-size: 9.5pt;
        }
        .value-cell {
            width: 70%;
            font-size: 9.5pt;
        }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="2" class="header-bg">ENCABEZADO DE FACTURA</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="label-cell">RESPONSABLE / CARGO / AREA:</td>
                <td class="value-cell">
                    HUGO RAMÍREZ DUARTE
                </td>
            </tr>
            <tr>
                <td class="label-cell">COMISION / REUNION / EVENTO:</td>
                <td class="value-cell">PAGOS DEL FESTIVAL INTERNACIONAL CERVANTINO</td>
            </tr>
            <tr>
                <td class="label-cell">CONCEPTO DEL PAGO:</td>
                <td class="value-cell">PAGOS FIC</td>
            </tr>
            <tr>
                <td class="label-cell">PARTIDA:</td>
                <td class="value-cell"><?= esc($partida) ?> - <?= esc($dscPartida) ?></td>
            </tr>
            <tr>
                <td class="label-cell">FACTURA / RECIBO No:</td>
                <td class="value-cell"><?= esc($folioFactura !== '' ? $folioFactura : 'N/D') ?></td>
            </tr>
            <tr>
                <td class="label-cell">FECHA DEL GASTO:</td>
                <td class="value-cell">
                    <?= esc($fechaGasto) ?>
                </td>
            </tr>
            <tr>
                <td class="label-cell">IMPORTANTE EN PESOS (MXN):</td>
                <td class="value-cell">
                    $<?= esc($importe) ?> M.N.
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>

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
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 20mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: middle;
        }
        .header-bg {
            background-color: #000;
            color: #fff;
            text-align: center;
            font-weight: bold;
            padding: 8px;
            font-size: 12pt;
        }
        .label-cell {
            background-color: #ccc;
            font-weight: bold;
            width: 30%;
            font-size: 9pt;
        }
        .value-cell {
            width: 70%;
            font-size: 9pt;
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
                    MTRO. DAVID AYALA SAUCEDO - SUBSECRETARIO DE IDENTIDAD Y DESARROLLO TURÍSTICO
                </td>
            </tr>
            <tr>
                <td class="label-cell">COMISION / REUNION / EVENTO:</td>
                <td class="value-cell">Consumo de alimentos durante la edición 54 del FIC del 10 de Sep al 26 Oct. 2026</td>
            </tr>
            <tr>
                <td class="label-cell">CONCEPTO DEL PAGO:</td>
                <td class="value-cell">Consumo de alimentos durante la edición 54 del FIC del 10 de Sep al 26 Oct. 2026</td>
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
                    del 10 de septiembre al 26 de octubre de 2026
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

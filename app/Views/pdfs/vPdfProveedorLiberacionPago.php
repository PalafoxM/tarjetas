<?php
$proveedorPerfil = is_array($proveedorPerfil ?? null) ? $proveedorPerfil : [];
$proveedorEstablecimiento = is_array($proveedorEstablecimiento ?? null) ? $proveedorEstablecimiento : [];
$facturaXmlContext = is_array($facturaXmlContext ?? null) ? $facturaXmlContext : [];
$xmlInfo = is_array($facturaXmlContext['xml_info'] ?? null) ? $facturaXmlContext['xml_info'] : [];
$proveedorXml = is_array($facturaXmlContext['proveedor'] ?? null) ? $facturaXmlContext['proveedor'] : [];
$establecimientoXml = is_array($facturaXmlContext['establecimiento'] ?? null) ? $facturaXmlContext['establecimiento'] : [];
$solicitudPago = is_array($solicitudPago ?? null) ? $solicitudPago : [];
$fechaEmision = !empty($fecha_emision) ? date('d/m/Y', strtotime((string) $fecha_emision)) : date('d/m/Y');
$folioFormato = (string) ($folio_formato ?? '');
$documentoTitulo = (string) ($documentoTitulo ?? 'LiberacionPago');
$documentoDescripcion = trim((string) ($documentoDescripcion ?? ''));
$documentoObjetivo = trim((string) ($documentoObjetivo ?? ''));
$razonSocial = trim((string) ($proveedorXml['razon_social'] ?? $proveedorPerfil['razon_social'] ?? 'Proveedor autenticado'));
$establecimientoNombre = trim((string) ($establecimientoXml['dsc_establecimiento'] ?? $proveedorEstablecimiento['dsc_establecimiento'] ?? ''));
$fechaRespuesta = trim((string) ($solicitudPago['fecha_respuesta'] ?? ''));
$estatusSolicitud = !empty($facturaXmlContext['folio_formato'])
    ? 'XML cargado'
    : trim((string) ($solicitudPago['estatus'] ?? 'N/D'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>LiberacionPago</title>
    <style>
        body {
            font-family: dejavusans, Arial, sans-serif;
            font-size: 11pt;
            color: #1f2937;
        }
        .header {
            border-bottom: 1px solid #7c3aed;
            padding-bottom: 6mm;
            margin-bottom: 6mm;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            margin: 0 0 2mm 0;
            color: #0f172a;
        }
        .subtitle {
            margin: 0;
            color: #475569;
        }
        table.meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6mm;
        }
        table.meta td {
            padding: 2.5mm 3mm;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .label {
            width: 28%;
            background: #f8fafc;
            font-weight: bold;
        }
        .section {
            margin-bottom: 5mm;
        }
        .section h2 {
            font-size: 12.5pt;
            margin: 0 0 2mm 0;
            color: #0f172a;
        }
        .box {
            border: 1px solid #cbd5e1;
            border-radius: 2mm;
            padding: 4mm;
        }
        .muted {
            color: #64748b;
        }
        .footer-note {
            margin-top: 8mm;
            font-size: 9.5pt;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Liberación de pago</p>
        <p class="subtitle">Documento de autorización vinculada al establecimiento seleccionado.</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Folio</td>
            <td><?= esc($folioFormato !== '' ? $folioFormato : 'Sin folio') ?></td>
            <td class="label">Fecha de emisión</td>
            <td><?= esc($fechaEmision) ?></td>
        </tr>
        <tr>
            <td class="label">Proveedor</td>
            <td><?= esc($razonSocial) ?></td>
            <td class="label">Establecimiento</td>
            <td><?= esc($establecimientoNombre !== '' ? $establecimientoNombre : 'N/D') ?></td>
        </tr>
        <tr>
            <td class="label">Documento</td>
            <td><?= esc($documentoTitulo) ?></td>
            <td class="label">Estatus</td>
            <td><?= esc($estatusSolicitud) ?></td>
        </tr>
    </table>

    <div class="section">
        <h2>Resumen</h2>
        <div class="box">
            <?= nl2br(esc($documentoDescripcion !== '' ? $documentoDescripcion : 'Liberación de pago vinculada al establecimiento seleccionado.')) ?>
            <?php if ($fechaRespuesta !== ''): ?>
                <br><br>
                <span class="muted">Fecha de respuesta:</span> <?= esc($fechaRespuesta) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <h2>Indicaciones</h2>
        <div class="box">
            <?= nl2br(esc($documentoObjetivo !== '' ? $documentoObjetivo : 'Validar monto, estatus y respuesta antes de proceder con la liberación.')) ?>
        </div>
    </div>

    <div class="section">
        <h2>Control administrativo</h2>
        <table class="meta">
            <tr>
                <td class="label">Razón social</td>
                <td><?= esc($razonSocial) ?></td>
                <td class="label">Fecha de respuesta</td>
                <td><?= esc($fechaRespuesta !== '' ? $fechaRespuesta : 'N/D') ?></td>
            </tr>
        </table>
    </div>

    <p class="footer-note">
        Por instrucciones del C. Secretario de Salud, remito a usted el documento siguiente. Solicito que la respuesta sea generada
        en un plazo no mayor a los 10 días hábiles o bien, respetando el plazo que el documento de origen establezca y marcando copia
        a este despacho, indicando el presente número de folio.
    </p>
</body>
</html>

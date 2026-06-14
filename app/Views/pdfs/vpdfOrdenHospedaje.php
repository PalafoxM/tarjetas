<?php
$beneficios = is_array($beneficios ?? null) ? $beneficios : [];
$fechaEmision = !empty($fecha_emision) ? date('d/m/Y H:i', strtotime((string) $fecha_emision)) : date('d/m/Y H:i');
$firmaUsuarioUrl = trim((string) ($firma_usuario_url ?? ''));
$vigenciaLabel = 'Sin vigencia';
if (!empty($vigente_desde) && !empty($vigente_hasta)) {
    $vigenciaLabel = date('d/m/Y H:i', strtotime((string) $vigente_desde)) . ' al ' . date('d/m/Y H:i', strtotime((string) $vigente_hasta));
}

$checkInLabel = !empty($beneficios['fecha_check_in']) ? date('d/m/Y H:i', strtotime((string) $beneficios['fecha_check_in'])) : 'Sin definir';
$checkOutLabel = !empty($beneficios['fecha_check_out']) ? date('d/m/Y H:i', strtotime((string) $beneficios['fecha_check_out'])) : 'Sin definir';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de hospedaje</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #172033; font-size: 11px; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { font-size: 11px; color: #475569; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: bold; color: #0f172a; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #d7dee8; padding: 8px; }
        th { background: #e2e8f0; color: #0f172a; text-align: left; }
        .label { width: 25%; background: #f8fafc; font-weight: bold; color: #334155; }
        .money { text-align: right; font-weight: bold; }
        .note { margin-top: 18px; padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .signature { margin-top: 36px; width: 280px; border-top: 1px solid #64748b; padding-top: 6px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Orden de hospedaje</div>
        <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
        <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
    </div>

    <div class="section-title">Datos del beneficiario</div>
    <table>
        <tr>
            <td class="label">Nombre</td>
            <td><?= esc($nombre_completo ?: 'Sin nombre') ?></td>
            <td class="label">Usuario</td>
            <td><?= esc((string) ($usuario->usuario ?? '')) ?></td>
        </tr>
        <tr>
            <td class="label">Folio</td>
            <td><?= esc((string) ($folio_entrega ?? '')) ?></td>
            <td class="label">Codigo QR</td>
            <td><?= esc((string) ($codigo_qr ?? '')) ?></td>
        </tr>
        <tr>
            <td class="label">Beneficio asignado</td>
            <td><?= esc((string) ($beneficios['beneficio_qr_label'] ?? 'Solo hospedaje')) ?></td>
            <td class="label">Vigencia</td>
            <td><?= esc($vigenciaLabel) ?></td>
        </tr>
    </table>

    <div class="section-title">Detalle de hospedaje</div>
    <table>
        <tr>
            <td class="label">Hotel</td>
            <td><?= esc((string) ($beneficios['hotel_nombre'] ?? 'Sin hotel asignado')) ?></td>
            <td class="label">Tipo de habitacion</td>
            <td><?= esc((string) ($beneficios['tipo_habitacion'] ?? 'Sin definir')) ?></td>
        </tr>
        <tr>
            <td class="label">Check-in</td>
            <td><?= esc($checkInLabel) ?></td>
            <td class="label">Check-out</td>
            <td><?= esc($checkOutLabel) ?></td>
        </tr>
        <tr>
            <td class="label">Noches</td>
            <td><?= esc((string) ($beneficios['noches'] ?? 0)) ?></td>
            <td class="label">Folio de hospedaje</td>
            <td><?= esc((string) ($beneficios['folio_hospedaje'] ?? ($folio_entrega ?? ''))) ?></td>
        </tr>
    </table>

    <div class="section-title">Importe autorizado</div>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Noches</th>
                <th>Tarifa por noche</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= esc((string) ($beneficios['tipo_habitacion'] ?? 'Hospedaje')) ?> en <?= esc((string) ($beneficios['hotel_nombre'] ?? 'Hotel asignado')) ?></td>
                <td><?= esc((string) ($beneficios['noches'] ?? 0)) ?></td>
                <td class="money">$<?= number_format((float) ($beneficios['tarifa_noche'] ?? 0), 2) ?></td>
                <td class="money">$<?= number_format((float) ($beneficios['tarifa_total_hospedaje'] ?? 0), 2) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($beneficios['observaciones_hospedaje'])): ?>
        <div class="section-title">Observaciones</div>
        <div><?= nl2br(esc((string) $beneficios['observaciones_hospedaje'])) ?></div>
    <?php endif; ?>

    <div class="note">
        Este documento acredita la orden de hospedaje asociada al beneficiario para su periodo de estancia autorizado.
        Cualquier ajuste debera realizarse por SECTURI antes de la ocupacion.
    </div>

    <div class="signature">
        <?php if ($firmaUsuarioUrl !== ''): ?>
            <div style="margin-bottom:8px;"><img src="<?= esc($firmaUsuarioUrl) ?>" alt="Firma del usuario" style="max-width:220px; max-height:72px;"></div>
        <?php endif; ?>
        Recibi orden de hospedaje impresa
    </div>
</body>
</html>

<?php
$beneficios = is_array($beneficios ?? null) ? $beneficios : [];
$tarifaResumen = is_array($tarifa_resumen ?? null) ? $tarifa_resumen : [];
$fechaEmision = !empty($fecha_emision) ? date('d/m/Y H:i', strtotime((string) $fecha_emision)) : date('d/m/Y H:i');
$firmaUsuarioUrl = trim((string) ($firma_usuario_url ?? ''));
$nombreCompleto = trim((string) ($nombre_completo ?? ''));
$usuarioRaw = $usuario_login ?? '';
if ($usuarioRaw === '' && isset($usuario)) {
    $usuarioRaw = is_object($usuario) ? ($usuario->usuario ?? '') : $usuario;
}
$usuarioLogin = trim((string) $usuarioRaw);
$folioEntrega = trim((string) ($folio_entrega ?? ($folio ?? '')));
$subFolioEntrega = trim((string) ($sub_folio ?? ''));
$paxEntrega = max(1, (int) ($pax_total ?? ($pax ?? 1)));
$codigoQrImpreso = (int) ($id_usuario ?? 0) > 0 ? 'FIC-' . (int) $id_usuario . '-QR' : '';
$codigoQr = trim((string) ($codigo_qr ?? ($qr ?? '')));
$nipUsuario = trim((string) ($nip ?? ''));
$qrUsuarioUrl = trim((string) ($qr_usuario_url ?? ''));
$vigenciaLabel = 'Sin vigencia';
if (!empty($vigente_desde) && !empty($vigente_hasta)) {
    $vigenciaLabel = date('d/m/Y H:i', strtotime((string) $vigente_desde)) . ' al ' . date('d/m/Y H:i', strtotime((string) $vigente_hasta));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de alimentos</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #172033; font-size: 11px; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { font-size: 11px; color: #475569; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: bold; color: #0f172a; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #d7dee8; padding: 8px; }
        th { background: #e2e8f0; color: #0f172a; text-align: left; }
        .label { width: 22%; background: #f8fafc; font-weight: bold; color: #334155; white-space: nowrap; }
        .value-wide { width: 78%; }
        .value-half { width: 28%; }
        .qr-value { font-size: 8px; line-height: 1.35; word-break: break-all; }
        .money { text-align: right; font-weight: bold; }
        .note { margin-top: 18px; padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; }
        .signature { margin-top: 34px; width: 320px; text-align: center; color: #475569; }
        .signature img { display: block; margin: 0 auto 4px; max-width: 220px; max-height: 72px; }
        .signature-line { border-top: 1px solid #64748b; padding-top: 6px; }
        .access-block { margin-top: 16px; border: 1px solid #cbd5e1; background: #f8fafc; padding: 10px; }
        .nip-value { font-size: 18px; font-weight: bold; letter-spacing: 2px; text-align: center; }
        .qr-image-cell { width: 140px; text-align: center; }
        .qr-image { width: 118px; height: 118px; object-fit: contain; }
        .qr-caption { font-size: 8px; color: #475569; margin-top: 4px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Orden de alimentos</div>
        <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
        <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
    </div>

    <div class="section-title">Datos del beneficiario</div>
    <table>
        <tr>
            <td class="label">Nombre</td>
            <td class="value-wide" colspan="3"><?= esc($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre') ?></td>
        </tr>
        <tr>
            <td class="label">Usuario</td>
            <td class="value-half"><?= esc($usuarioLogin !== '' ? $usuarioLogin : 'Sin usuario') ?></td>
            <td class="label">Folio</td>
            <td class="value-half"><?= esc($folioEntrega !== '' ? $folioEntrega : 'Sin folio') ?></td>
        </tr>
        <tr>
            <td class="label">Subfolio</td>
            <td class="value-half"><?= esc($subFolioEntrega !== '' ? $subFolioEntrega : 'Sin subfolio') ?></td>
            <td class="label">Pax</td>
            <td class="value-half"><?= esc((string) $paxEntrega) ?></td>
        </tr>
        <tr>
            <td class="label">Código QR</td>
            <td class="qr-value" colspan="3"><?= esc($codigoQrImpreso !== '' ? $codigoQrImpreso : ($codigoQr !== '' ? $codigoQr : 'Sin QR')) ?></td>
        </tr>
        <tr>
            <td class="label">Beneficio asignado</td>
            <td><?= esc((string) ($beneficios['beneficio_qr_label'] ?? 'Solo alimentos')) ?></td>
            <td class="label">Vigencia</td>
            <td><?= esc($vigenciaLabel) ?></td>
        </tr>
    </table>

    <div class="section-title">Detalle del beneficio</div>
    <table>
        <tr>
            <td class="label">Concepto</td>
            <td>Consumo de alimentos autorizado durante la vigencia del QR.</td>
            <td class="label">Tarifa diaria</td>
            <td class="money">$<?= number_format((float) ($tarifaResumen['monto_diario'] ?? 0), 2) ?></td>
        </tr>
        <tr>
            <td class="label">Dias autorizados</td>
            <td><?= esc((string) ($tarifaResumen['dias_vigencia'] ?? 0)) ?></td>
            <td class="label">Total autorizado</td>
            <td class="money">$<?= number_format((float) ($tarifaResumen['tarifa_total'] ?? 0), 2) ?></td>
        </tr>
    </table>

    <div class="section-title">Importe autorizado</div>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th>Tarifa diaria</th>
                <th>Días</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Orden de alimentos FIC</td>
                <td class="money">$<?= number_format((float) ($tarifaResumen['monto_diario'] ?? 0), 2) ?></td>
                <td><?= esc((string) ($tarifaResumen['dias_vigencia'] ?? 0)) ?></td>
                <td class="money">$<?= number_format((float) ($tarifaResumen['tarifa_total'] ?? 0), 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="note">
        Este documento acredita la orden de alimentos asociada al beneficiario para el periodo de vigencia autorizado.
        El consumo debera realizarse únicamente conforme a las reglas operativas vigentes del programa.
    </div>

    <div class="access-block">
        <div class="section-title" style="margin-top:0;">Acceso del usuario</div>
        <table>
            <tr>
                <td class="label">NIP</td>
                <td class="nip-value"><?= esc($nipUsuario !== '' ? $nipUsuario : 'Sin NIP') ?></td>
                <td class="label">QR asignado</td>
                <td class="qr-image-cell">
                    <?php if ($qrUsuarioUrl !== ''): ?>
                        <img class="qr-image" src="<?= esc($qrUsuarioUrl) ?>" alt="QR del usuario">
                    <?php else: ?>
                        <div style="font-size:10px; color:#64748b;">Sin imagen QR</div>
                    <?php endif; ?>
                    <div class="qr-caption"><?= esc($codigoQrImpreso !== '' ? $codigoQrImpreso : ($codigoQr !== '' ? $codigoQr : 'Sin QR')) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="signature">
        <?php if ($firmaUsuarioUrl !== ''): ?>
            <img src="<?= esc($firmaUsuarioUrl) ?>" alt="Firma del usuario">
        <?php endif; ?>
        <div class="signature-line">Recibí orden de alimentos impresa</div>
    </div>
</body>
</html>

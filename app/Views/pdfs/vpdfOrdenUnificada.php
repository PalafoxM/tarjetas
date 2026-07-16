<?php
date_default_timezone_set('America/Mexico_City');

function formatearFecha($fecha, $formato = 'd/m/Y H:i') {
    if (empty($fecha)) return 'Sin definir';
    try {
        $dt = new DateTime($fecha, new DateTimeZone('America/Mexico_City'));
        return $dt->format($formato);
    } catch (Exception $e) {
        return $fecha;
    }
}

$beneficios = is_array($beneficios ?? null) ? $beneficios : [];
$tarifaResumen = is_array($tarifa_resumen ?? null) ? $tarifa_resumen : [];

$fechaEmision = !empty($fecha_emision) 
    ? formatearFecha($fecha_emision) 
    : date('d/m/Y H:i');

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
$tieneHospedaje = (int) ($tiene_hospedaje ?? 0) === 1;
$tieneAlimentos = (int) ($tiene_alimentos ?? 0) === 1;


if ($tieneHospedaje && $tieneAlimentos) {
    $tituloOrden = 'Orden de hospedaje y alimentos';
    $leyendaDocumento = 'Este documento acredita la orden de hospedaje y alimentos asociada al beneficiario para su periodo de estancia autorizado. Cualquier ajuste deberá realizarse por SECTURI antes de la ocupación. El consumo de alimentos deberá realizarse únicamente conforme a las reglas operativas vigentes del programa.';
    $textoFirma = 'Recibí orden de hospedaje y alimentos impresa';
} elseif ($tieneHospedaje) {
    $tituloOrden = 'Orden de hospedaje';
    $leyendaDocumento = 'Este documento acredita la orden de hospedaje asociada al beneficiario para su periodo de estancia autorizado. Cualquier ajuste deberá realizarse por SECTURI antes de la ocupación.';
    $textoFirma = 'Recibí orden de hospedaje impresa';
} elseif ($tieneAlimentos) {
    $tituloOrden = 'Orden de alimentos';
    $leyendaDocumento = 'Este documento acredita la orden de alimentos asociada al beneficiario para su periodo de estancia autorizado. El consumo de alimentos deberá realizarse únicamente conforme a las reglas operativas vigentes del programa.';
    $textoFirma = 'Recibí orden de alimentos impresa';
} else {
    $tituloOrden = 'Orden FIC - Documento informativo';
    $leyendaDocumento = 'Documento informativo del beneficiario del Festival Internacional Cervantino.';
    $textoFirma = 'Recibí documento informativo';
}

$beneficioLabel = (string) ($beneficios['beneficio_qr_label'] ?? 'Sin beneficio asignado');

$formatDateRange = static function ($from, $to): string {
    if (!empty($from) && !empty($to)) {
        return formatearFecha($from) . ' al ' . formatearFecha($to);
    }
    return 'Sin vigencia';
};

$checkInLabel = !empty($beneficios['fecha_check_in']) 
    ? formatearFecha($beneficios['fecha_check_in']) 
    : 'Sin definir';
$checkOutLabel = !empty($beneficios['fecha_check_out']) 
    ? formatearFecha($beneficios['fecha_check_out']) 
    : 'Sin definir';

$vigenciaHospedaje = $formatDateRange($vigente_desde_hosp ?? '', $vigente_hasta_hosp ?? '');
$vigenciaAlimentos = $formatDateRange($vigente_desde ?? '', $vigente_hasta ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($tituloOrden) ?> - FIC</title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #172033; font-size: 11px; margin: 12px 15px; padding: 0; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 14px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { font-size: 11px; color: #475569; margin-top: 3px; }
        .section-title { font-size: 13px; font-weight: bold; color: #0f172a; margin: 10px 0 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        td, th { border: 1px solid #d7dee8; padding: 6px 8px; }
        th { background: #e2e8f0; color: #0f172a; text-align: left; font-size: 10px; padding: 5px 8px; }
        .label { background: #f8fafc; font-weight: bold; color: #334155; white-space: nowrap; font-size: 10px; padding: 5px 8px; width: 18%; }
        .label-small { background: #f8fafc; font-weight: bold; color: #334155; white-space: nowrap; font-size: 9px; padding: 4px 6px; width: 14%; }
        .qr-value { font-size: 8px; line-height: 1.3; word-break: normal; overflow-wrap: break-word; }
        .money { text-align: right; font-weight: bold; }
        .note { margin-top: 10px; padding: 8px 10px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 10px; }
        .signature { margin-top: 150px; width: 400px; text-align: center; color: #475569; margin-left: auto; margin-right: auto; padding-top: 15px; }
        .signature img { display: block; margin: 0 auto 10px; max-width: 220px; max-height: 80px; }
        .signature-line { border-top: 2px solid #64748b; padding-top: 12px; font-size: 12px; font-weight: bold; color: #0f172a; }
        .beneficiary-section { margin-bottom: 8px; }
        .vigencia-row { display: flex; gap: 20px; margin-top: 4px; font-size: 10px; padding: 3px 0; }
        .vigencia-item { flex: 1; padding: 4px 8px; background: #f8fafc; border-radius: 4px; }
        .vigencia-label { font-weight: bold; color: #0f172a; display: block; margin-bottom: 2px; }
        .two-columns { display: flex; gap: 20px; margin-top: 6px; }
        .column { flex: 1; }
        .signature-name { font-size: 11px; color: #475569; margin-top: 8px; font-weight: normal; }
        .obs-text { font-size: 9px; margin-top: 3px; color: #475569; padding: 3px 6px; background: #f1f5f9; border-radius: 3px; }
        .signature-space { height: 60px; }
        .beneficiario-label { font-size: 12px; padding: 6px 10px; }
        .beneficiario-value { font-size: 12px; padding: 6px 10px; }
        .detalle-label { width: 22%; font-size: 9px; padding: 4px 6px; }
        .detalle-value { font-size: 10px; padding: 4px 6px; }
        .firma-texto { font-size: 11px; color: #475569; margin-top: 5px; }
        @page { margin: 10mm 12mm 10mm 12mm; }
    </style>
</head>
<body>
    <section>
        <div class="header">
            <div class="title"><?= esc($tituloOrden) ?></div>
            <div class="subtitle">Festival Internacional Cervantino / SECTURI &nbsp;|&nbsp; Emitido: <?= esc($fechaEmision) ?></div>
        </div>

        <div class="beneficiary-section">
            <div class="section-title">Datos del beneficiario</div>
            <table>
                <tr>
                    <td class="label" style="width:12%; font-size:12px; padding:8px 10px;">Nombre completo</td>
                    <td style="width:30%; font-size:13px; padding:8px 10px;"><?= esc($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre') ?></td>
                    <td class="label" style="width:10%; font-size:12px; padding:8px 10px;">Folio</td>
                    <td style="width:15%; font-size:12px; padding:8px 10px; "><?= esc($folioEntrega !== '' ? $folioEntrega : 'Sin folio') ?></td>
                    <td class="label" style="width:8%; font-size:12px; padding:8px 10px;">Pax</td>
                    <td style="width:10%; font-size:12px; padding:8px 10px; font-weight:bold;"><?= esc((string) $paxEntrega) ?></td>
                </tr>
                <tr>
                    <td class="label" style="font-size:12px; padding:8px 10px;">Usuario</td>
                    <td style="font-size:12px; padding:8px 10px;"><?= esc($usuarioLogin !== '' ? $usuarioLogin : 'Sin usuario') ?></td>
                    <td class="label" style="font-size:12px; padding:8px 10px;">Subfolio</td>
                    <td style="font-size:12px; padding:8px 10px;"><?= esc($subFolioEntrega !== '' ? $subFolioEntrega : 'Sin subfolio') ?></td>
                    <td class="label" style="font-size:12px; padding:8px 10px;">Código QR</td>
                    <td class="qr-value" style="font-size:10px; padding:8px 10px; word-break: normal;"><?= esc($codigoQrImpreso !== '' ? $codigoQrImpreso : ($codigoQr !== '' ? $codigoQr : 'Sin QR')) ?></td>
                </tr>
                <tr>
                    <td class="label" style="font-size:12px; padding:8px 10px;">Beneficio asignado</td>
                    <td colspan="2" style="font-size:12px; padding:8px 10px; "><?= esc($beneficioLabel) ?></td>
                    <td class="label" colspan="1" style="font-size:12px; padding:8px 10px;">Vigencias</td>
                    <td colspan="2" style="padding:6px 10px;">
                        <div class="vigencia-row">
                            <div class="vigencia-item">
                                <span class="vigencia-label">Hospedaje</span>
                                <?= esc($vigenciaHospedaje) ?>
                            </div>
                            <div class="vigencia-item">
                                <span class="vigencia-label">Alimentos</span>
                                <?= esc($vigenciaAlimentos) ?>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="two-columns">
            <div class="column">
                <?php if ($tieneHospedaje): ?>
                    <div class="section-title">Detalle de hospedaje</div>
                    <table>
                        <tr>
                            <td class="label-small" style="width:25%;">Hotel</td>
                            <td style="width:25%;"><?= esc((string) ($beneficios['hotel_nombre'] ?? 'Sin hotel')) ?></td>
                            <td class="label-small" style="width:20%;">Tipo de habitación</td>
                            <td style="width:30%;"><?= esc((string) ($beneficios['tipo_habitacion'] ?? 'Sin definir')) ?></td>
                        </tr>
                        <tr>
                            <td class="label-small">Check-in</td>
                            <td><?= esc($checkInLabel) ?></td>
                            <td class="label-small">Check-out</td>
                            <td><?= esc($checkOutLabel) ?></td>
                        </tr>
                        <tr>
                            <td class="label-small">Noches</td>
                            <td><?= esc((string) ($beneficios['noches'] ?? 0)) ?></td>
                            <td class="label-small">Folio de hospedaje</td>
                            <td><?= esc((string) ($beneficios['folio_hospedaje'] ?? ($folio_entrega ?? ''))) ?></td>
                        </tr>
                    </table>

                    <div class="section-title" style="font-size:11px; margin-top:8px;">Importe autorizado - Hospedaje</div>
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
                        <div class="obs-text">
                            <strong>Observaciones:</strong> <?= nl2br(esc((string) $beneficios['observaciones_hospedaje'])) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="color:#94a3b8; font-size:11px; text-align:center; padding:20px 0; border:1px dashed #cbd5e1; border-radius:4px;">
                        Sin beneficio de hospedaje asignado
                    </div>
                <?php endif; ?>
            </div>

            <div class="column">
                <?php if ($tieneAlimentos): ?>
                    <div class="section-title">Detalle de alimentos</div>
                    <table>
                        <tr>
                            <td class="label-small" style="width:30%;">Concepto</td>
                            <td colspan="3">Consumo de alimentos autorizado durante la vigencia del QR.</td>
                        </tr>
                        <tr>
                            <td class="label-small">Tarifa diaria</td>
                            <td class="money" style="width:25%;">$<?= number_format((float) ($tarifaResumen['monto_diario'] ?? 0), 2) ?></td>
                            <td class="label-small" style="width:20%;">Días autorizados</td>
                            <td style="width:25%;"><?= esc((string) ($tarifaResumen['dias_vigencia'] ?? 0)) ?></td>
                        </tr>
                        <tr>
                            <td class="label-small">Total autorizado</td>
                            <td colspan="3" class="money" style="font-weight:bold; font-size:12px; color:#0f172a;">
                                $<?= number_format((float) ($tarifaResumen['tarifa_total'] ?? 0), 2) ?>
                            </td>
                        </tr>
                    </table>

                    <div class="section-title" style="font-size:11px; margin-top:8px;">Importe autorizado - Alimentos</div>
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
                <?php else: ?>
                    <div style="color:#94a3b8; font-size:11px; text-align:center; padding:20px 0; border:1px dashed #cbd5e1; border-radius:4px;">
                        Sin beneficio de alimentos asignado
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="note">
            <?= nl2br(esc($leyendaDocumento)) ?>
        </div>

        <div class="signature-space"></div>

        <div class="signature">
            <?php if ($firmaUsuarioUrl !== ''): ?>
                <img src="<?= esc($firmaUsuarioUrl) ?>" alt="Firma del usuario">
            <?php endif; ?>
            <div class="signature-line"><?= esc($textoFirma) ?></div>
        </div>
    </section>
</body>
</html>
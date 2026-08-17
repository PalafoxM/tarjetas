<?php
date_default_timezone_set('America/Mexico_City');

$formatearFecha = static function ($fecha, $formato = 'd/m/Y H:i'): string {
    if (empty($fecha)) return 'Sin definir';
    try {
        $dt = new DateTime($fecha, new DateTimeZone('America/Mexico_City'));
        return $dt->format($formato);
    } catch (Exception $e) {
        return (string) $fecha;
    }
};

$beneficios = is_array($beneficios ?? null) ? $beneficios : [];
$tarifaResumen = is_array($tarifa_resumen ?? null) ? $tarifa_resumen : [];

$fechaEmision = !empty($fecha_emision)
    ? $formatearFecha($fecha_emision)
    : date('d/m/Y H:i');

$firmaUsuarioLocalPath = trim((string) ($firma_usuario_local_path ?? ($firma_usuario_url ?? '')));
$nombreCompleto = trim((string) ($nombre_completo ?? ''));
$usuarioRaw = $usuario_login ?? '';
if ($usuarioRaw === '' && isset($usuario)) {
    $usuarioRaw = is_object($usuario) ? ($usuario->usuario ?? '') : $usuario;
}
$usuarioLogin = trim((string) $usuarioRaw);
$folioEntrega = trim((string) ($folio_entrega ?? ($folio ?? '')));
$subFolioEntrega = trim((string) ($sub_folio ?? ''));
$paxEntrega = max(1, (int) ($pax_total ?? ($pax ?? 1)));
$codigoQrImpreso = trim((string) ($codigo_qr_impreso ?? ''));
if ($codigoQrImpreso === '') {
    $codigoQrImpreso = (int) ($id_usuario ?? 0) > 0 ? 'FIC-' . (int) $id_usuario . '-QR' : '';
}
$codigoQr = trim((string) ($codigo_qr ?? ($qr ?? '')));
$nipUsuario = trim((string) ($nip ?? ''));
$qrUsuarioLocalPath = trim((string) ($qr_usuario_local_path ?? ($qr_usuario_url ?? '')));
$tieneHospedaje = (int) ($tiene_hospedaje ?? 0) === 1;
$tieneAlimentos = (int) ($tiene_alimentos ?? 0) === 1;

if ($tieneHospedaje && $tieneAlimentos) {
    $tituloOrden = 'Orden de hospedaje y alimentos';
    $leyendaDocumento = 'Este documento acredita la orden de hospedaje y alimentos asociada al beneficiario para su período de estancia autorizado. Cualquier ajuste deberá realizarse por SECTURI antes de la ocupación. El consumo de alimentos deberá realizarse únicamente conforme a las reglas operativas vigentes del programa.';
    $textoFirma = 'Recibí orden de hospedaje y alimentos impresa';
} elseif ($tieneHospedaje) {
    $tituloOrden = 'Orden de hospedaje';
    $leyendaDocumento = 'Este documento acredita la orden de hospedaje asociada al beneficiario para su período de estancia autorizado. Cualquier ajuste deberá realizarse por SECTURI antes de la ocupación.';
    $textoFirma = 'Recibí orden de hospedaje impresa';
} elseif ($tieneAlimentos) {
    $tituloOrden = 'Orden de alimentos';
    $leyendaDocumento = 'Este documento acredita la orden de alimentos asociada al beneficiario para su período de estancia autorizado. El consumo de alimentos deberá realizarse únicamente conforme a las reglas operativas vigentes del programa.';
    $textoFirma = 'Recibí orden de alimentos impresa';
} else {
    $tituloOrden = 'Orden FIC - Documento informativo';
    $leyendaDocumento = 'Documento informativo del beneficiario del Festival Internacional Cervantino.';
    $textoFirma = 'Recibí documento informativo';
}

$beneficioLabel = (string) ($beneficios['beneficio_qr_label'] ?? 'Sin beneficio asignado');

$formatDateRange = static function ($from, $to): string {
    if (!empty($from) && !empty($to)) {
        return date('d/m/Y H:i', strtotime((string) $from)) . ' al ' . date('d/m/Y H:i', strtotime((string) $to));
    }

    return 'Sin vigencia';
};

$renderBeneficiario = static function (string $vigenciaLabel) use (
    $nombreCompleto,
    $usuarioLogin,
    $folioEntrega,
    $subFolioEntrega,
    $paxEntrega,
    $codigoQrImpreso,
    $codigoQr,
    $qrUsuarioLocalPath,
    $nipUsuario,
    $beneficioLabel
): void {
?>
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
            <td class="qr-value">
                <?php if ($qrUsuarioLocalPath !== '' && is_file($qrUsuarioLocalPath)): ?>
                    <img class="qr-image" src="<?= esc($qrUsuarioLocalPath) ?>" alt="Código QR del beneficiario">
                <?php endif; ?>
                <div><?= esc($codigoQrImpreso !== '' ? $codigoQrImpreso : ($codigoQr !== '' ? $codigoQr : 'QR no disponible')) ?></div>
            </td>
            <td class="label">NIP</td>
            <td><?= esc($nipUsuario !== '' ? $nipUsuario : 'Sin definir') ?></td>
        </tr>
        <tr>
            <td class="label">Beneficio asignado</td>
            <td><?= esc($beneficioLabel) ?></td>
            <td class="label">Vigencia</td>
            <td><?= esc($vigenciaLabel) ?></td>
        </tr>
    </table>
<?php
};

$renderFirma = static function (string $texto) use ($firmaUsuarioLocalPath): void {
?>
    <div class="signature">
        <?php if ($firmaUsuarioLocalPath !== '' && is_file($firmaUsuarioLocalPath)): ?>
            <div class="signature-image-wrap">
                <img class="signature-image" src="<?= esc($firmaUsuarioLocalPath) ?>" alt="Firma del usuario" width="120" height="26">
            </div>
        <?php else: ?>
            <div class="signature-space"></div>
        <?php endif; ?>
        <div class="signature-line"><?= esc($texto) ?></div>
    </div>
<?php
};

$checkInLabel = !empty($beneficios['fecha_check_in']) ? date('d/m/Y H:i', strtotime((string) $beneficios['fecha_check_in'])) : 'Sin definir';
$checkOutLabel = !empty($beneficios['fecha_check_out']) ? date('d/m/Y H:i', strtotime((string) $beneficios['fecha_check_out'])) : 'Sin definir';
$vigenciaHospedaje = $formatDateRange($vigente_desde_hosp ?? '', $vigente_hasta_hosp ?? '');
$vigenciaAlimentos = $formatDateRange($vigente_desde ?? '', $vigente_hasta ?? '');
$vigenciaGeneral = $vigenciaHospedaje === $vigenciaAlimentos
    ? $vigenciaHospedaje
    : 'Hospedaje: ' . $vigenciaHospedaje . ' | Alimentos: ' . $vigenciaAlimentos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= esc($tituloOrden) ?></title>
    <style>
        body { font-family: dejavusans, sans-serif; color: #172033; font-size: 11px; line-height: 1.25; }
        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 9px; margin-bottom: 13px; }
        .title { font-size: 20px; font-weight: bold; color: #0f172a; }
        .subtitle { font-size: 11px; color: #475569; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: bold; color: #0f172a; margin: 14px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #d7dee8; padding: 7px 8px; }
        th { background: #e2e8f0; color: #0f172a; text-align: left; }
        .label { width: 22%; background: #f8fafc; font-weight: bold; color: #334155; white-space: nowrap; }
        .value-wide { width: 78%; }
        .value-half { width: 28%; }
        .qr-value { font-size: 8px; line-height: 1.35; word-break: break-word; }
        .qr-image { display: block; width: 82px; height: 82px; object-fit: contain; margin: 0 0 5px; }
        .money { text-align: right; font-weight: bold; }
        .note { margin-top: 10px; padding: 7px 9px; border: 1px solid #cbd5e1; background: #f8fafc; line-height: 1.22; }
        .signature { margin: 13px auto 0; width: 245px; text-align: center; color: #475569; page-break-inside: avoid; }
        .signature-image-wrap { max-width: 140px; max-height: 32px; margin: 0 auto 4px; line-height: 0; }
        .signature-image { display: block; margin: 0 auto; max-width: 140px; max-height: 32px; width: auto; height: auto; }
        .signature-space { height: 22px; }
        .signature-line { border-top: 1px solid #64748b; padding-top: 4px; font-size: 8.75px; line-height: 1.18; width: 245px; margin: 0 auto; }
        .page-break { page-break-before: always; }
        .detail-block { page-break-inside: avoid; }
    </style>
</head>
<body>
    <?php if ($tieneHospedaje && $tieneAlimentos): ?>
        <section>
            <div class="header">
                <div class="title">Orden de hospedaje y alimentos</div>
                <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
                <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
            </div>

            <?php $renderBeneficiario($vigenciaGeneral); ?>

            <div class="detail-block">
                <div class="section-title">Detalle de hospedaje</div>
                <table>
                    <tr>
                        <td class="label">Hotel</td>
                        <td><?= esc((string) ($beneficios['hotel_nombre'] ?? 'Sin hotel asignado')) ?></td>
                        <td class="label">Tipo de habitación</td>
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
                        <td><?= esc((string) ($beneficios['folio_hospedaje'] ?? ($folio_entrega ?? 'Sin folio'))) ?></td>
                    </tr>
                </table>

                <div class="section-title">Importe autorizado de hospedaje</div>
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
                    <div class="section-title">Observaciones de hospedaje</div>
                    <div><?= nl2br(esc((string) $beneficios['observaciones_hospedaje'])) ?></div>
                <?php endif; ?>
            </div>

            <div class="detail-block">
                <div class="section-title">Detalle de alimentos</div>
                <table>
                    <tr>
                        <td class="label">Concepto</td>
                        <td>Consumo de alimentos autorizado durante la vigencia del QR.</td>
                        <td class="label">Tarifa diaria</td>
                        <td class="money">$<?= number_format((float) ($tarifaResumen['monto_diario'] ?? 0), 2) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Días autorizados</td>
                        <td><?= esc((string) ($tarifaResumen['dias_vigencia'] ?? 0)) ?></td>
                        <td class="label">Total autorizado</td>
                        <td class="money">$<?= number_format((float) ($tarifaResumen['tarifa_total'] ?? 0), 2) ?></td>
                    </tr>
                </table>

                <div class="section-title">Importe autorizado de alimentos</div>
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
            </div>

            <div class="note">
                Este documento acredita la orden de hospedaje y alimentos asociada al beneficiario para el período autorizado.
                Cualquier ajuste deberá realizarse por SECTURI conforme a las reglas operativas vigentes.
            </div>

            <?php $renderFirma('Recibí orden de hospedaje y alimentos impresa'); ?>
        </section>
    <?php elseif ($tieneHospedaje): ?>
        <section>
            <div class="header">
                <div class="title">Orden de hospedaje</div>
                <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
                <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
            </div>

            <?php $renderBeneficiario($vigenciaHospedaje); ?>

            <div class="section-title">Detalle de hospedaje</div>
            <table>
                <tr>
                    <td class="label">Hotel</td>
                    <td><?= esc((string) ($beneficios['hotel_nombre'] ?? 'Sin hotel asignado')) ?></td>
                    <td class="label">Tipo de habitación</td>
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
                Este documento acredita la orden de hospedaje asociada al beneficiario para su período de estancia autorizado.
                Cualquier ajuste deberá realizarse por SECTURI antes de la ocupación.
            </div>

            <?php $renderFirma('Recibí orden de hospedaje impresa'); ?>
        </section>
    <?php elseif ($tieneAlimentos): ?>
        <section>
            <div class="header">
                <div class="title">Orden de alimentos</div>
                <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
                <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
            </div>

            <?php $renderBeneficiario($vigenciaAlimentos); ?>

            <div class="section-title">Detalle del beneficio</div>
            <table>
                <tr>
                    <td class="label">Concepto</td>
                    <td>Consumo de alimentos autorizado durante la vigencia del QR.</td>
                    <td class="label">Tarifa diaria</td>
                    <td class="money">$<?= number_format((float) ($tarifaResumen['monto_diario'] ?? 0), 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Días autorizados</td>
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
                Este documento acredita la orden de alimentos asociada al beneficiario para el período de vigencia autorizado.
                El consumo deberá realizarse únicamente conforme a las reglas operativas vigentes del programa.
            </div>

            <?php $renderFirma('Recibí orden de alimentos impresa'); ?>
        </section>
    <?php else: ?>
        <section>
            <div class="header">
                <div class="title">Orden FIC</div>
                <div class="subtitle">Festival Internacional Cervantino / SECTURI</div>
                <div class="subtitle">Emitido: <?= esc($fechaEmision) ?></div>
            </div>

            <?php $renderBeneficiario('Sin vigencia'); ?>

            <div class="note">
                Sin beneficio asignado para hospedaje o alimentos.
            </div>

            <?php $renderFirma('Recibí orden impresa'); ?>
        </section>
    <?php endif; ?>
</body>
</html>

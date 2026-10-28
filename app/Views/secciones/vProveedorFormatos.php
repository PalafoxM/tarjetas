<?php
$proveedorPerfil = is_object($proveedorPerfil ?? null) ? get_object_vars($proveedorPerfil) : (is_array($proveedorPerfil ?? null) ? $proveedorPerfil : []);
$proveedorEstablecimientos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($proveedorEstablecimientos ?? null) ? $proveedorEstablecimientos : []));
$proveedorPagos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($proveedorPagos ?? null) ? $proveedorPagos : []));
$proveedorNombre = (string) ($proveedorPerfil['razon_social'] ?? $proveedorPerfil['dsc_establecimiento'] ?? 'Proveedor');
$proveedorNumero = (string) ($proveedorPerfil['no_proveedor'] ?? '');
$establecimientoActivo = $proveedorEstablecimientos[0] ?? [];
$ultimaSolicitudPago = $proveedorPagos[0] ?? [];
$primerCorteProveedor = is_object($primerCorteProveedor ?? null) ? get_object_vars($primerCorteProveedor) : (is_array($primerCorteProveedor ?? null) ? $primerCorteProveedor : []);
$primerCorteActivo = !empty($primerCorteProveedor['activo']);
$primerCorteCerrado = !empty($primerCorteProveedor['cerrado']);
$recepcionDocumentalProveedorHabilitada = !empty($recepcionDocumentalProveedorHabilitada);
$primerCortePeriodos = is_array($primerCorteProveedorPeriodos ?? null) ? $primerCorteProveedorPeriodos : [];
?>
<style>
    .provider-formats-page {
        background: linear-gradient(180deg, #101827 0%, #111827 46%, #172033 100%);
        min-height: calc(100vh - 70px);
        color: #f8fafc;
        padding: 28px 28px 42px;
    }

    .provider-formats-shell {
        max-width: 1480px;
        margin: 0 auto;
    }

    .provider-formats-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1.25rem;
        align-items: center;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(30, 41, 59, .96), rgba(15, 23, 42, .98));
        box-shadow: 0 18px 42px rgba(2, 6, 23, .22);
    }

    .provider-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .55rem;
        color: #93c5fd;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .provider-formats-title {
        margin: 0;
        color: #ffffff;
        font-size: clamp(1.45rem, 2vw, 2rem);
        line-height: 1.15;
    }

    .provider-formats-subtitle {
        margin: .45rem 0 0;
        color: #cbd5e1;
    }

    .provider-formats-meta {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-top: .85rem;
    }

    .provider-formats-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .65rem;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 999px;
        background: rgba(15, 23, 42, .72);
        color: #e2e8f0;
        font-size: .86rem;
        font-weight: 600;
    }

    .provider-action {
        min-height: 42px;
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .2);
    }

    .provider-formats-card {
        background: rgba(17, 24, 39, .96);
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 12px;
        box-shadow: 0 14px 34px rgba(2, 6, 23, .18);
    }

    .provider-formats-section {
        margin-top: 1.25rem;
    }

    .provider-formats-section-title {
        margin: 0;
        color: #ffffff;
        font-size: 1.08rem;
        font-weight: 800;
    }

    .provider-formats-section-copy {
        margin: .25rem 0 0;
        color: #94a3b8;
        font-size: .9rem;
    }

    .provider-formats-tabs .nav-link {
        color: #cbd5e1;
        background: rgba(15, 23, 42, .65);
        border: 1px solid rgba(148, 163, 184, .16);
        margin-right: .5rem;
        border-radius: 10px 10px 0 0;
    }

    .provider-formats-tabs .nav-link.active {
        color: #fff;
        background: #2563eb;
        border-color: #2563eb;
    }

    .provider-formats-panel {
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 0 12px 12px 12px;
        background: rgba(17, 24, 39, .96);
        padding: 1rem;
    }

    .provider-formats-actions {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .provider-formats-action {
        min-height: 70px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .2);
        background: rgba(15, 23, 42, .7);
        color: #f8fafc;
        font-weight: 700;
        text-align: left;
    }

    .provider-formats-upload {
        position: relative;
        display: flex;
        align-items: center;
        gap: .45rem;
        width: 100%;
        min-height: 70px;
        padding: .85rem 1rem;
        cursor: pointer;
    }

    .provider-formats-upload input[type="file"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .provider-formats-link {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: .45rem;
        width: 100%;
        min-height: 70px;
        padding: .85rem 1rem;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .2);
        background: rgba(37, 99, 235, .18);
        color: #f8fafc;
        font-weight: 700;
        text-decoration: none;
    }

    .provider-formats-link:hover {
        color: #ffffff;
        border-color: rgba(96, 165, 250, .85);
        background: rgba(37, 99, 235, .28);
    }

    .provider-formats-empty {
        padding: 18px;
        text-align: center;
        color: #cbd5e1;
        background: rgba(30, 41, 59, .55);
        border-radius: 14px;
        border: 1px dashed rgba(148, 163, 184, .2);
    }

    .provider-formats-empty.is-success {
        color: #bbf7d0;
        background: rgba(22, 101, 52, .2);
        border-color: rgba(74, 222, 128, .35);
    }

    .provider-formats-empty.is-warning {
        color: #fde68a;
        background: rgba(146, 64, 14, .18);
        border-color: rgba(251, 191, 36, .35);
    }

    .provider-formats-empty.is-info {
        color: #bfdbfe;
        background: rgba(30, 64, 175, .18);
        border-color: rgba(96, 165, 250, .35);
    }

    .provider-cut-notice {
        margin: 1.25rem 0;
        padding: 1rem 1.15rem;
        border-radius: 12px;
        border: 1px solid rgba(250, 204, 21, .35);
        background: rgba(113, 63, 18, .32);
        color: #fef3c7;
    }

    .provider-cut-notice h4 {
        margin: 0 0 .55rem;
        color: #ffffff;
        font-size: 1.05rem;
        font-weight: 800;
    }

    .provider-cut-notice p {
        margin: .25rem 0;
    }

    .provider-formats-summary {
        display: grid;
        gap: .8rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 1rem;
    }

    .provider-stat-label {
        color: #94a3b8;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .provider-stat-value {
        color: #ffffff;
        font-size: clamp(1.2rem, 2vw, 1.75rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .provider-stat-note {
        color: #cbd5e1;
        font-size: .88rem;
    }

    @media (max-width: 991px) {
        .provider-formats-hero {
            grid-template-columns: 1fr;
        }

        .provider-formats-actions {
            grid-template-columns: 1fr;
        }

        .provider-formats-summary {
            grid-template-columns: 1fr;
        }
    }
</style>

<div
    class="container-fluid provider-formats-page"
    id="providerFormatsPage"
    data-primer-corte-activo="<?= $primerCorteActivo ? '1' : '0' ?>"
    data-primer-corte-cerrado="<?= $primerCorteCerrado ? '1' : '0' ?>"
    data-recepcion-documental-habilitada="<?= $recepcionDocumentalProveedorHabilitada ? '1' : '0' ?>"
    data-segundo-periodo-disponible="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')) >= new DateTimeImmutable('2026-10-19 00:00:00', new DateTimeZone('America/Mexico_City'))) ? '1' : '0' ?>">
    <div class="provider-formats-shell">
        <section class="provider-formats-hero">
            <div>
                <div class="provider-eyebrow">
                    <i class="mdi mdi-file-document-multiple-outline"></i>
                    Formatos del proveedor
                </div>
                <h3 class="provider-formats-title"><?= esc($proveedorNombre) ?></h3>
                <p class="provider-formats-subtitle">Selecciona un establecimiento y prepara el formato que vas a generar.</p>
                <div class="provider-formats-meta">
                    <span class="provider-formats-chip"><i class="mdi mdi-pound"></i> No. proveedor: <?= esc($proveedorNumero !== '' ? $proveedorNumero : 'Sin asignar') ?></span>
                    <span class="provider-formats-chip"><i class="mdi mdi-office-building"></i> Establecimientos: <?= (int) count($proveedorEstablecimientos) ?></span>
                </div>
            </div>
            <a class="btn btn-outline-light provider-action" href="<?= esc(base_url('index.php/Inicio'), 'attr') ?>">
                <i class="mdi mdi-arrow-left me-1"></i> Volver al proveedor
            </a>
        </section>

        <!--<?php if ($primerCorteActivo): ?>
            <section class="provider-cut-notice">
                <h4>DOCUMENTACIÓN PARA PROCESO DE FACTURACIÓN</h4>
             
                <br><p><strong>PASO 1</strong><br>Carga el reporte de ventas correspondiente al periodo que deseas facturar, el formato lo descargas en la pantalla principal.</p>
                <p><strong>PASO 2</strong><br>Carga el PDF de la factura.</p>
                <p><strong>PASO 3</strong><br>Carga el XML de la factura.</p>
                <p><strong>PASO 4</strong><br>Cuando los tres documentos estén cargados, pulsa “Enviar factura” para cerrar el periodo actual.</p>
                <br><p><strong>IMPORTANTE:</strong> Puedes emitir una o dos facturas como máximo. No es obligatorio utilizar las dos.</p>
            </section>
        <?php elseif ($primerCorteCerrado): ?>
            <section class="provider-cut-notice">
                <h4>Ciclo de cortes finalizado</h4>
                <p>El periodo de recepción de documentación ha finalizado.</p>
            </section>
        <?php else: ?>
            <section class="provider-cut-notice">
                <h4>Ciclo de cortes pendiente</h4>
                <p>La recepción de documentación aún no está disponible.</p>
            </section>
        <?php endif; ?>-->

        <section class="provider-formats-section">
            <div class="card provider-formats-card">
                <div class="card-body">
                    <h5 class="provider-formats-section-title">Establecimientos asignados</h5>
                    <p class="provider-formats-section-copy">Cada pestaña representa un establecimiento ligado al proveedor autenticado.</p>

                    <?php if (!empty($proveedorEstablecimientos)): ?>
                        <ul class="nav nav-tabs provider-formats-tabs mt-3" role="tablist">
                            <?php foreach ($proveedorEstablecimientos as $index => $establecimiento): ?>
                                <?php $idEst = (string) ($establecimiento['id_establecimiento'] ?? $index); ?>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link<?= $index === 0 ? ' active' : '' ?>"
                                        id="tab-formato-<?= esc($idEst, 'attr') ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#panel-formato-<?= esc($idEst, 'attr') ?>"
                                        type="button"
                                        role="tab"
                                        aria-controls="panel-formato-<?= esc($idEst, 'attr') ?>"
                                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                                        <?= esc((string) ($establecimiento['dsc_establecimiento'] ?? 'Establecimiento')) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="tab-content provider-formats-panel mt-0">
                            <?php foreach ($proveedorEstablecimientos as $index => $establecimiento): ?>
                                <?php
                                $idEst = (string) ($establecimiento['id_establecimiento'] ?? $index);
                                $idEstInt = (int) $idEst;
                                $periodoCorteActual = is_array($primerCortePeriodos[$idEstInt] ?? null) ? $primerCortePeriodos[$idEstInt] : [];
                                $reporteCargadoPrimerCorte = trim((string) ($periodoCorteActual['reporte_url'] ?? '')) !== '';
                                $numeroPeriodoActual = (int) ($periodoCorteActual['numero_periodo_resuelto'] ?? 1);
                                $facturasEmitidas = (int) ($periodoCorteActual['facturas_emitidas'] ?? 0);
                                $cicloFinalizadoEstablecimiento = !empty($periodoCorteActual['ciclo_finalizado']);
                                $esSegundoPeriodo = $numeroPeriodoActual === 2 && !$cicloFinalizadoEstablecimiento;
                                $estadoTextoPeriodo = $cicloFinalizadoEstablecimiento
                                    ? ('Ciclo finalizado. Facturas emitidas: ' . $facturasEmitidas)
                                    : ('Cortes utilizados: ' . $facturasEmitidas . ' de 2. Periodo actual: ' . ($esSegundoPeriodo ? 'segundo y último' : 'primero'));
                                ?>
                                <div
                                    class="tab-pane fade<?= $index === 0 ? ' show active' : '' ?>"
                                    id="panel-formato-<?= esc($idEst, 'attr') ?>"
                                    role="tabpanel"
                                    aria-labelledby="tab-formato-<?= esc($idEst, 'attr') ?>">
                                    <div class="row g-3 align-items-start">
                                        <div class="col-12 col-xl-4">
                                            <div class="provider-formats-empty h-100 text-start">
                                                <div class="provider-stat-label">Establecimiento</div>
                                                <div class="provider-stat-value mt-1"><?= esc((string) ($establecimiento['dsc_establecimiento'] ?? 'Sin nombre')) ?></div>
                                                <div class="provider-stat-note mt-2"><?= esc((string) ($establecimiento['dsc_tipo'] ?? 'Sin tipo')) ?></div>
                                                <div class="provider-stat-note mt-1">No. proveedor: <?= esc((string) ($establecimiento['no_proveedor'] ?? '')) ?></div>
                                                <div class="provider-stat-note mt-1">ID: <?= esc((string) ($establecimiento['id_establecimiento'] ?? '')) ?></div>
                                                <div class="provider-stat-note mt-2"><?= esc($estadoTextoPeriodo) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-xl-8">
                                            <div class="provider-formats-actions">
                                                <label class="btn provider-formats-action provider-formats-upload" for="encabezado_factura_xml_<?= esc($idEst, 'attr') ?>">
                                                    <i class="mdi mdi-file-xml-box me-1"></i> SUBIR XML
                                                    <input
                                                        id="encabezado_factura_xml_<?= esc($idEst, 'attr') ?>"
                                                        name="encabezado_factura_xml[<?= esc($idEst, 'attr') ?>]"
                                                        type="file"
                                                        <?= ($recepcionDocumentalProveedorHabilitada && !$cicloFinalizadoEstablecimiento) ? '' : 'disabled' ?>
                                                        accept="application/xml,text/xml,.xml">
                                                </label>
                                                <label class="btn provider-formats-action provider-formats-upload" for="formato_pt_pdf_<?= esc($idEst, 'attr') ?>">
                                                    <i class="mdi mdi-file-pdf-box me-1"></i> SUBIR PDF
                                                    <input
                                                        id="formato_pt_pdf_<?= esc($idEst, 'attr') ?>"
                                                        name="formato_pt_pdf[<?= esc($idEst, 'attr') ?>]"
                                                        type="file"
                                                        <?= ($recepcionDocumentalProveedorHabilitada && !$cicloFinalizadoEstablecimiento) ? '' : 'disabled' ?>
                                                        accept="application/pdf,.pdf">
                                                </label>
                                                <?php
                                                $tipoReporte = 'ventas';
                                                $dscTipoEstablecimiento = strtolower(trim((string) ($establecimiento['dsc_tipo'] ?? '')));
                                                $idTipoEstablecimiento = (int) ($establecimiento['id_tipo'] ?? 0);
                                                if ($idTipoEstablecimiento === 2 || ($dscTipoEstablecimiento !== '' && (str_contains($dscTipoEstablecimiento, 'hotel') || str_contains($dscTipoEstablecimiento, 'recep')))) {
                                                    $tipoReporte = 'hospedaje';
                                                }
                                                $labelReporte = $tipoReporte === 'hospedaje' ? 'SUBIR REPORTE DE HOSPEDAJE' : 'SUBIR REPORTE DE VENTAS';
                                                ?>
                                                <label class="btn provider-formats-action provider-formats-upload" for="reporte_proveedor_pdf_<?= esc($idEst, 'attr') ?>">
                                                    <i class="mdi mdi-cloud-upload-outline me-1"></i> <?= esc($labelReporte) ?>
                                                    <input
                                                        id="reporte_proveedor_pdf_<?= esc($idEst, 'attr') ?>"
                                                        name="reporte_proveedor_pdf[<?= esc($idEst, 'attr') ?>]"
                                                        type="file"
                                                        <?= ($recepcionDocumentalProveedorHabilitada && !$cicloFinalizadoEstablecimiento) ? '' : 'disabled' ?>
                                                        accept="application/pdf,.pdf">
                                                </label>
                                                <button
                                                    type="button"
                                                    class="btn provider-formats-action js-enviar-factura"
                                                    data-id-establecimiento="<?= esc($idEst, 'attr') ?>"
                                                    data-reporte-primer-corte="<?= $reporteCargadoPrimerCorte ? '1' : '0' ?>"
                                                    data-periodo-actual="<?= esc((string) $numeroPeriodoActual, 'attr') ?>"
                                                    disabled>
                                                    <i class="mdi mdi-send-check-outline me-1"></i> Enviar factura
                                                </button>
                                            </div>
                                            <div
                                                class="provider-formats-empty mt-3 js-provider-document-status"
                                                data-id-establecimiento="<?= esc($idEst, 'attr') ?>">
                                                <?php if ($cicloFinalizadoEstablecimiento): ?>
                                                    Ciclo finalizado para este establecimiento.
                                                <?php elseif ($esSegundoPeriodo): ?>
                                                    Tu segundo y último periodo está activo. Podrás enviar esta factura el 19 de octubre.
                                                <?php else: ?>
                                                    <?= $recepcionDocumentalProveedorHabilitada ? 'Selecciona el reporte de ventas, el XML y el PDF de la factura para habilitar el envío.' : 'La recepción de documentación no está activa.' ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="provider-formats-empty mt-3">Este proveedor aún no tiene establecimientos visibles ligados.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    var realtime = window.ficRealtime || null;
    var estadoDocumentalUrl = '<?= esc(base_url('index.php/Inicio/getEstadoDocumentalEstablecimiento'), 'js') ?>';
    var page = document.getElementById('providerFormatsPage');
    var primerCorteActivo = !!(page && page.getAttribute('data-primer-corte-activo') === '1');
    var primerCorteCerrado = !!(page && page.getAttribute('data-primer-corte-cerrado') === '1');
    var recepcionDocumentalHabilitada = !!(page && page.getAttribute('data-recepcion-documental-habilitada') === '1');
    var segundoPeriodoDisponible = !!(page && page.getAttribute('data-segundo-periodo-disponible') === '1');

    function getFacturaInputs(idEstablecimiento) {
        return {
            xml: document.getElementById('encabezado_factura_xml_' + idEstablecimiento),
            pdf: document.getElementById('formato_pt_pdf_' + idEstablecimiento),
            button: document.querySelector('.js-enviar-factura[data-id-establecimiento="' + idEstablecimiento + '"]')
        };
    }

    function getReporteInputs(idEstablecimiento) {
        return {
            input: document.getElementById('reporte_proveedor_pdf_' + idEstablecimiento)
        };
    }

    function emitRealtimeEvent(name, detail) {
        if (realtime && typeof realtime.emit === 'function') {
            realtime.emit(name, detail || {});
            return;
        }

        document.dispatchEvent(new CustomEvent(name, { detail: detail || {} }));
    }

    function getStatusBox(idEstablecimiento) {
        return document.querySelector('.js-provider-document-status[data-id-establecimiento="' + idEstablecimiento + '"]');
    }

    function setStatusMessage(idEstablecimiento, message, tone) {
        var box = getStatusBox(idEstablecimiento);
        if (!box) return;

        box.classList.remove('is-success', 'is-warning', 'is-info');
        if (tone) {
            box.classList.add('is-' + tone);
        }
        box.textContent = message;
    }

    function fileMatches(file, extension, mimeTypes) {
        if (!file) return false;
        var name = String(file.name || '');
        var type = String(file.type || '').toLowerCase();
        return name.toLowerCase().endsWith(extension) || mimeTypes.indexOf(type) !== -1;
    }

    function pintarEstadoDocumento(control, listo) {
        if (!control) return;

        var target = control.closest('label') || control;
        target.classList.toggle('btn-success', !!listo);
    }

    function actualizarEstadoVisualDocumentos(idEstablecimiento, data) {
        var documentos = data && data.documentos ? data.documentos : {};
        var facturaControls = getFacturaInputs(idEstablecimiento);
        var reporteControls = getReporteInputs(idEstablecimiento);
        var xmlFile = facturaControls.xml && facturaControls.xml.files ? facturaControls.xml.files[0] : null;
        var pdfFile = facturaControls.pdf && facturaControls.pdf.files ? facturaControls.pdf.files[0] : null;
        var reporteDisponible = facturaControls.button
            && facturaControls.button.getAttribute('data-reporte-primer-corte') === '1';
        var xmlDisponible = !!(documentos.xml && documentos.xml.disponible)
            || fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']);
        var pdfDisponible = !!(documentos.pdf && documentos.pdf.disponible)
            || fileMatches(pdfFile, '.pdf', ['application/pdf']);

        pintarEstadoDocumento(reporteControls.input, reporteDisponible);
        pintarEstadoDocumento(facturaControls.xml, xmlDisponible);
        pintarEstadoDocumento(facturaControls.pdf, pdfDisponible);
        updateEnviarFactura(idEstablecimiento);
    }

    function updateEnviarFactura(idEstablecimiento) {
        var controls = getFacturaInputs(idEstablecimiento);
        if (!controls.button) return;

        var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
        var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;
        var validXml = fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']);
        var validPdf = fileMatches(pdfFile, '.pdf', ['application/pdf']);
        var reporteCargado = controls.button.getAttribute('data-reporte-primer-corte') === '1';
        var periodoActual = controls.button.getAttribute('data-periodo-actual') || '1';
        controls.button.disabled = !(recepcionDocumentalHabilitada && validXml && validPdf && reporteCargado && (periodoActual !== '2' || segundoPeriodoDisponible));
        controls.button.classList.toggle('btn-success', !controls.button.disabled);
    }

    function aplicarEstadoDocumental(idEstablecimiento, data, fallbackMessage) {
        var documentos = data && data.documentos ? data.documentos : {};
        var pdfDisponible = !!(documentos.pdf && documentos.pdf.disponible);
        var xmlDisponible = !!(documentos.xml && documentos.xml.disponible);
        var reporteDisponible = !!(documentos.reporte && documentos.reporte.disponible);
        var formatos = documentos.formatos || {};
        var formatosDisponibles = Object.keys(formatos).some(function (key) {
            return !!(formatos[key] && formatos[key].disponible);
        });

        if (pdfDisponible && xmlDisponible && formatosDisponibles) {
            setStatusMessage(
                idEstablecimiento,
                'Factura recibida: XML y PDF disponibles. Los formatos administrativos ya pueden consultarse.',
                'success'
            );
            return;
        }

        if (pdfDisponible || xmlDisponible) {
            setStatusMessage(
                idEstablecimiento,
                'Documentación parcial recibida. Falta completar XML y PDF para habilitar los formatos administrativos.',
                'warning'
            );
            return;
        }

        if (reporteDisponible) {
            setStatusMessage(
                idEstablecimiento,
                'Reporte disponible para revisión. Selecciona XML y PDF para habilitar el envío de factura.',
                'info'
            );
            return;
        }

        setStatusMessage(
            idEstablecimiento,
            fallbackMessage || (recepcionDocumentalHabilitada ? 'Selecciona el reporte de ventas, el XML y el PDF de la factura para habilitar el envío.' : 'La recepción de documentación no está activa.'),
            fallbackMessage ? 'info' : ''
        );
    }

    function consultarEstadoDocumental(idEstablecimiento, extraDetail, fallbackMessage) {
        var requestOptions = {
            url: estadoDocumentalUrl,
            method: 'GET',
            dataType: 'json',
            data: { id_establecimiento: idEstablecimiento },
            showError: false
        };
        var request = realtime && typeof realtime.request === 'function'
            ? realtime.request(requestOptions)
            : $.ajax(requestOptions);

        return request.done(function (response) {
            if (!response || response.ok === false || !response.data) {
                actualizarEstadoVisualDocumentos(idEstablecimiento, null);
                aplicarEstadoDocumental(idEstablecimiento, null, fallbackMessage);
                return;
            }

            actualizarEstadoVisualDocumentos(idEstablecimiento, response.data);
            aplicarEstadoDocumental(idEstablecimiento, response.data, fallbackMessage);
            emitRealtimeEvent('fic:estado-documental-actualizado', {
                id_establecimiento: idEstablecimiento,
                estado: response.data,
                origen: extraDetail || null
            });
        }).fail(function () {
            actualizarEstadoVisualDocumentos(idEstablecimiento, null);
            aplicarEstadoDocumental(idEstablecimiento, null, fallbackMessage);
        });
    }

    function subirReporteProveedor(idEstablecimiento) {
        if (!recepcionDocumentalHabilitada) {
            Swal.fire('Atención', primerCorteCerrado ? 'El periodo de recepción de documentación ha finalizado.' : 'La recepción de documentación aún no está disponible.', 'warning');
            return;
        }

        var controls = getReporteInputs(idEstablecimiento);
        var file = controls.input && controls.input.files ? controls.input.files[0] : null;

        if (!fileMatches(file, '.pdf', ['application/pdf'])) {
            Swal.fire('Atención', 'Selecciona un PDF válido para el reporte.', 'warning');
            return;
        }

        var data = new FormData();
        data.append('id_establecimiento', idEstablecimiento);
        data.append('reporte', file);

        Swal.fire({
            title: 'Subiendo reporte',
            text: 'Espera un momento...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '<?= esc(base_url('index.php/Inicio/subirReporteProveedor'), 'js') ?>',
            type: 'POST',
            dataType: 'json',
            data: data,
            processData: false,
            contentType: false
        }).done(function (response) {
            if (!response || response.error) {
                Swal.fire('Atención', response && response.respuesta ? response.respuesta : 'No fue posible subir el reporte.', 'warning');
                return;
            }

            if (controls.input) controls.input.value = '';
            var facturaControls = getFacturaInputs(idEstablecimiento);
            if (facturaControls.button) {
                facturaControls.button.setAttribute('data-reporte-primer-corte', '1');
            }
            actualizarEstadoVisualDocumentos(idEstablecimiento, null);
            setStatusMessage(idEstablecimiento, 'Reporte subido. Actualizando estado documental...', 'info');
            emitRealtimeEvent('fic:reporte-subido', {
                id_establecimiento: idEstablecimiento,
                response: response
            });
            consultarEstadoDocumental(
                idEstablecimiento,
                { tipo: 'reporte', response: response },
                response.respuesta || 'Reporte subido correctamente.'
            );
            Swal.fire('Correcto', response.respuesta || 'Reporte subido correctamente.', 'success');
        }).fail(function (request) {
            if (controls.input) controls.input.value = '';
            actualizarEstadoVisualDocumentos(idEstablecimiento, null);
            consultarEstadoDocumental(idEstablecimiento, { tipo: 'reporte-error' });
            var response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible subir el reporte.', 'error');
        });
    }

    document.querySelectorAll('[id^="encabezado_factura_xml_"], [id^="formato_pt_pdf_"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var idEstablecimiento = this.id.replace('encabezado_factura_xml_', '').replace('formato_pt_pdf_', '');
            actualizarEstadoVisualDocumentos(idEstablecimiento, null);
        });
    });

    document.querySelectorAll('[id^="reporte_proveedor_pdf_"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var idEstablecimiento = this.id.replace('reporte_proveedor_pdf_', '');
            subirReporteProveedor(idEstablecimiento);
        });
    });

    document.querySelectorAll('.js-enviar-factura').forEach(function (button) {
        button.addEventListener('click', function () {
            var idEstablecimiento = this.getAttribute('data-id-establecimiento') || '';
            var controls = getFacturaInputs(idEstablecimiento);
            var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
            var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;

            if (!recepcionDocumentalHabilitada) {
                Swal.fire('Atención', primerCorteCerrado ? 'El periodo de recepción de documentación ha finalizado.' : 'La recepción de documentación aún no está disponible.', 'warning');
                return;
            }

            if (this.getAttribute('data-reporte-primer-corte') !== '1') {
                Swal.fire('Atención', 'Sube primero el reporte de ventas PDF del periodo actual.', 'warning');
                return;
            }

            if ((this.getAttribute('data-periodo-actual') || '1') === '2' && !segundoPeriodoDisponible) {
                Swal.fire('Atención', 'Tu segundo periodo ya está activo. La segunda y última factura podrá enviarse el 19 de octubre.', 'warning');
                return;
            }

            if (!fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']) || !fileMatches(pdfFile, '.pdf', ['application/pdf'])) {
                Swal.fire('Atención', 'Selecciona un XML y un PDF válidos.', 'warning');
                return;
            }

            var data = new FormData();
            data.append('id_establecimiento', idEstablecimiento);
            data.append('xml', xmlFile);
            data.append('pdf', pdfFile);

            Swal.fire({
                title: 'Enviando factura',
                text: 'Espera un momento...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '<?= esc(base_url('index.php/Inicio/enviarFacturaProveedor'), 'js') ?>',
                type: 'POST',
                dataType: 'json',
                data: data,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (!response || response.error) {
                    Swal.fire('Atención', response && response.respuesta ? response.respuesta : 'No fue posible enviar la factura.', 'warning');
                    return;
                }

                if (controls.xml) controls.xml.value = '';
                if (controls.pdf) controls.pdf.value = '';
                actualizarEstadoVisualDocumentos(idEstablecimiento, null);
                setStatusMessage(idEstablecimiento, 'Factura enviada. Actualizando documentos disponibles...', 'info');
                emitRealtimeEvent('fic:factura-subida', {
                    id_establecimiento: idEstablecimiento,
                    response: response
                });
                consultarEstadoDocumental(
                    idEstablecimiento,
                    { tipo: 'factura', response: response },
                    response.respuesta || 'Factura enviada correctamente.'
                );
                Swal.fire('Correcto', response.respuesta || 'Factura enviada correctamente.', 'success');
            }).fail(function (request) {
                if (controls.xml) controls.xml.value = '';
                if (controls.pdf) controls.pdf.value = '';
                actualizarEstadoVisualDocumentos(idEstablecimiento, null);
                consultarEstadoDocumental(idEstablecimiento, { tipo: 'factura-error' });
                var response = request.responseJSON || {};
                Swal.fire('Error', response.respuesta || 'No fue posible enviar la factura.', 'error');
            });
        });
    });

    document.querySelectorAll('.js-provider-document-status').forEach(function (box) {
        var idEstablecimiento = box.getAttribute('data-id-establecimiento') || '';
        if (idEstablecimiento) {
            consultarEstadoDocumental(idEstablecimiento, { tipo: 'carga-inicial' });
        }
    });
})();
</script>

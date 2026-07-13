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

<div class="container-fluid provider-formats-page" id="providerFormatsPage">
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
                                <?php $idEst = (string) ($establecimiento['id_establecimiento'] ?? $index); ?>
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
                                                        accept="application/xml,text/xml,.xml">
                                                </label>
                                                <label class="btn provider-formats-action provider-formats-upload" for="formato_pt_pdf_<?= esc($idEst, 'attr') ?>">
                                                    <i class="mdi mdi-file-pdf-box me-1"></i> SUBIR PDF
                                                    <input
                                                        id="formato_pt_pdf_<?= esc($idEst, 'attr') ?>"
                                                        name="formato_pt_pdf[<?= esc($idEst, 'attr') ?>]"
                                                        type="file"
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
                                                        accept="application/pdf,.pdf">
                                                </label>
                                                <button
                                                    type="button"
                                                    class="btn provider-formats-action js-enviar-factura"
                                                    data-id-establecimiento="<?= esc($idEst, 'attr') ?>"
                                                    disabled>
                                                    <i class="mdi mdi-send-check-outline me-1"></i> Enviar factura
                                                </button>
                                            </div>
                                            <div class="provider-formats-empty mt-3">
                                                Selecciona el XML y el PDF de la factura para habilitar el envio.
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

    function fileMatches(file, extension, mimeTypes) {
        if (!file) return false;
        var name = String(file.name || '');
        var type = String(file.type || '').toLowerCase();
        return name.toLowerCase().endsWith(extension) || mimeTypes.indexOf(type) !== -1;
    }

    function updateEnviarFactura(idEstablecimiento) {
        var controls = getFacturaInputs(idEstablecimiento);
        if (!controls.button) return;

        var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
        var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;
        var validXml = fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']);
        var validPdf = fileMatches(pdfFile, '.pdf', ['application/pdf']);
        controls.button.disabled = !(validXml && validPdf);
    }

    function subirReporteProveedor(idEstablecimiento) {
        var controls = getReporteInputs(idEstablecimiento);
        var file = controls.input && controls.input.files ? controls.input.files[0] : null;

        if (!fileMatches(file, '.pdf', ['application/pdf'])) {
            Swal.fire('Atencion', 'Selecciona un PDF valido para el reporte.', 'warning');
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
                Swal.fire('Atencion', response && response.respuesta ? response.respuesta : 'No fue posible subir el reporte.', 'warning');
                return;
            }

            if (controls.input) controls.input.value = '';
            Swal.fire('Correcto', response.respuesta || 'Reporte subido correctamente.', 'success');
        }).fail(function (request) {
            var response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible subir el reporte.', 'error');
        });
    }

    document.querySelectorAll('[id^="encabezado_factura_xml_"], [id^="formato_pt_pdf_"]').forEach(function (input) {
        input.addEventListener('change', function () {
            var idEstablecimiento = this.id.replace('encabezado_factura_xml_', '').replace('formato_pt_pdf_', '');
            updateEnviarFactura(idEstablecimiento);
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

            if (!fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']) || !fileMatches(pdfFile, '.pdf', ['application/pdf'])) {
                Swal.fire('Atencion', 'Selecciona un XML y un PDF validos.', 'warning');
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
                    Swal.fire('Atencion', response && response.respuesta ? response.respuesta : 'No fue posible enviar la factura.', 'warning');
                    return;
                }

                if (controls.xml) controls.xml.value = '';
                if (controls.pdf) controls.pdf.value = '';
                updateEnviarFactura(idEstablecimiento);
                Swal.fire('Correcto', response.respuesta || 'Factura enviada correctamente.', 'success');
            }).fail(function (request) {
                var response = request.responseJSON || {};
                Swal.fire('Error', response.respuesta || 'No fue posible enviar la factura.', 'error');
            });
        });
    });
})();
</script>

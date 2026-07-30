<?php
$cajeroAccesoTiInicio = !empty($cajeroAccesoTiInicio);
$cajeroSoloConsulta = !empty($cajeroSoloConsulta);
$cajeroPuedeGestionarQr = !empty($cajeroPuedeGestionarQr);
$cajeroPuedeActivarQr = !empty($cajeroPuedeActivarQr);
$cajeroPuedeRechazarQr = !empty($cajeroPuedeRechazarQr);
$cajeroRegresarUrl = $cajeroRegresarUrl ?? base_url('index.php/Inicio');
?>
<style>
    .cajero-doc-preview {
        min-height: 320px;
        border: 1px solid rgba(148, 163, 184, .25);
        border-radius: 10px;
        background: #0f172a;
        overflow: hidden;
    }

    .cajero-doc-preview img,
    .cajero-doc-preview iframe {
        display: block;
        width: 100%;
        height: min(70vh, 620px);
        border: 0;
        object-fit: contain;
    }

    .cajero-doc-preview--firma {
        background: #ffffff;
        padding: 18px;
    }

    .cajero-doc-list .btn {
        justify-content: flex-start;
        text-align: left;
    }
</style>
    <div class="container-fluid py-4"
     id="cajeroPage"
     data-solo-consulta="<?= $cajeroSoloConsulta ? '1' : '0' ?>"
     data-documento-url="<?= esc(base_url('index.php/Usuario/verDocumentoUsuario'), 'attr') ?>"
     data-export-xlsx-url="<?= esc(base_url('index.php/Usuario/exportarCajerosOrdenDiaXlsx'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white"><?= $cajeroSoloConsulta ? 'Consulta de usuarios y folios' : 'Administración de cajeros' ?></h3>
            <p class="text-muted mb-0"><?= $cajeroSoloConsulta ? 'Consulta llegadas, documentos y órdenes sin modificar usuarios.' : 'Consulta, registra, edita o elimina cajeros.' ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <?php if ($cajeroAccesoTiInicio): ?>
            <a href="<?= esc($cajeroRegresarUrl, 'attr') ?>" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Atrás
            </a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-info" id="descargar_cajeros_xlsx">
                <i class="mdi mdi-download me-1"></i> Descargar orden del d&iacute;a
            </button>
            <?php if (!$cajeroSoloConsulta): ?>
            <!--<button type="button" class="btn btn-primary" onclick="cajeros.nuevo()">
                <i class="mdi mdi-account-plus me-1"></i> Nuevo cajero
            </button>-->
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cajeroSoloConsulta): ?>
        <div class="alert alert-info" role="status">Modo consulta: las acciones de carga, activación y eliminación están deshabilitadas para este perfil.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="filtro_dia_llegada">Dí­a de llegada</label>
                    <input type="date" class="form-control" id="filtro_dia_llegada">
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-outline-light" id="limpiar_filtro_dia_llegada" disabled>Todos los dí­as</button>
                </div>
                <div class="col-12 col-md">
                    <div class="text-muted small" id="filtro_dia_llegada_estado">Mostrando todos los folios por dí­a de llegada.</div>
                </div>
            </div>
            <table id="cajerosTable"
                   class="table table-dark table-hover align-middle"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="50"
                   data-page-list="[5,10,25,50,100]"
                   data-show-columns="true"
                   data-locale="es-MX">
                <thead>
                    <tr>
                        <th data-field="id_usuario" data-sortable="true">ID</th>
                        <th data-field="usuario" data-sortable="true">Usuario</th>
                        <th data-field="nombre_completo" data-sortable="true">Nombre Completo</th>
                        <th data-field="folio" data-sortable="true">Folio</th>
                        <th data-field="dsc_perfil" data-sortable="true">Perfil</th>
                        <th data-field="tiene_hospedaje" data-formatter="cajeros.estado" data-align="center">Hospedaje</th>
                        <th data-field="tiene_alimentos" data-formatter="cajeros.estado" data-align="center">Alimentos</th>
                        <th data-field="monto_deposito_reservado" data-formatter="cajeros.moneda" data-align="center">Saldo reservado</th>
                        <th data-field="monto_deposito_operativo" data-formatter="cajeros.moneda" data-align="center">Saldo operativo</th>

                        <th data-field="documentos" data-formatter="cajeros.documentos" data-align="center">Documentos</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cajeroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cajeroForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="cajeroModalTitle">Nuevo cajero</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="id_usuario">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input class="form-control" name="nombre" id="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="primer_apellido">Primer apellido</label>
                            <input class="form-control" name="primer_apellido" id="primer_apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="segundo_apellido">Segundo apellido</label>
                            <input class="form-control" name="segundo_apellido" id="segundo_apellido">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="correo">Correo</label>
                            <input type="email" class="form-control" name="correo" id="correo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario">Usuario</label>
                            <input class="form-control" name="usuario" id="usuario" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contrasenia">Contraseña</label>
                            <input type="password" class="form-control" name="contrasenia" id="contrasenia">
                            <small class="text-muted">En edición, dí©jala vací­a para conservar la actual.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardarCajero">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cajeroDocumentosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-secondary">
                <div>
                    <h5 class="modal-title" id="cajeroDocumentosModalTitle">Documentos</h5>
                    <small class="text-muted" id="cajeroDocumentosModalSubtitle"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-lg-3">
                        <div class="d-grid gap-2 cajero-doc-list" id="cajeroDocumentosList"></div>
                    </div>
                    <div class="col-12 col-lg-9">
                        <div id="cajeroDocumentosPreview" class="cajero-doc-preview d-flex align-items-center justify-content-center text-muted p-3">
                            Selecciona un documento.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <a href="#" id="cajeroDocumentosOpen" class="btn btn-outline-info disabled" target="_blank" rel="noopener">Abrir en nueva pestaña</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
const cajeroSoloConsulta = <?= json_encode($cajeroSoloConsulta) ?>;
const cajeroPuedeGestionarQr = <?= json_encode($cajeroPuedeGestionarQr ?? false) ?>;
const cajeroPuedeActivarQr = <?= json_encode($cajeroPuedeActivarQr ?? false) ?>;
const S3_PUBLIC_BASE_URL = 'https://sectur-audiovisuales-509634423753-us-east-1-an.s3.amazonaws.com/';
window.cajeros = Object.assign(window.cajeros || {}, {
    modal: null,
    documentosModal: null,
    documentoActualUrl: '',
    documentoUsuarioId: 0,
    documentosActuales: [],
    rowsBaseDiaLlegada: [],
    diaLlegadaActual: '',

    iniciar() {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#cajerosTable').bootstrapTable({
            search: true,
            searchHighlight: true,
            sidePagination: 'client',
            url: base_url + 'index.php/Usuario/getUsuarios',
            responseHandler: (response) => {
                let rows = [];
                if (Array.isArray(response)) {
                    rows = response;
                } else if (response && Array.isArray(response.data)) {
                    rows = response.data;
                } else if (response && Array.isArray(response.rows)) {
                    rows = response.rows;
                } else {
                    console.error('Respuesta inválida al cargar cajeros:', response);
                    return [];
                }

                this.establecerRegistrosBaseDiaLlegada(rows);
                this.actualizarEstadoFiltroDiaLlegada();
                return this.aplicarFiltroDiaLlegada(rows);
            },
            onLoadError: (status, request) => {
                console.error('Error al cargar cajeros:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los cajeros.', 'error');
            }
        });

        this.inicializarFiltroDiaLlegada();
        setTimeout(() => {
            this.actualizarEstadoFiltroDiaLlegada();
        }, 0);

        $('#descargar_cajeros_xlsx').on('click', (event) => {
            event.preventDefault();
            this.descargarXlsx();
        });

        if (window.bootstrap && bootstrap.Modal) {
            this.modal = new bootstrap.Modal(document.getElementById('cajeroModal'));
            this.documentosModal = new bootstrap.Modal(document.getElementById('cajeroDocumentosModal'));
        }

        $('#cajeroForm').on('submit', (event) => {
            event.preventDefault();
            this.guardar();
        });

        $('#cajeroDocumentosList').on('click', '.js-cajero-doc-open', (event) => {
            const field = String($(event.currentTarget).data('field') || '');
            this.abrirDocumento(field);
        });
    },

    escapeHtml(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    estado(value) {
        if (Number(value) === 1) return '<span class="badge bg-success">Sí­</span>';
        if (Number(value) === 2 || Number(value) === 0) return '<span class="badge bg-danger">No</span>';
        return '<span class="badge bg-secondary">Pendiente</span>';
    },

    moneda(value) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
    },

    descargarXlsx() {
        const pagina = document.getElementById('cajeroPage');
        const baseUrl = pagina ? String(pagina.dataset.exportXlsxUrl || '').trim() : '';
        if (!baseUrl) {
            Swal.fire('Atención', 'No fue posible resolver la ruta de descarga.', 'warning');
            return;
        }

        const dia = saeg.principal.normalizarFechaISO($('#filtro_dia_llegada').val());
        const params = new URLSearchParams();
        if (dia) {
            params.set('dia_llegada', dia);
        }

        const href = params.toString() ? baseUrl + '?' + params.toString() : baseUrl;
        if (typeof this.descargarArchivoSinNavegar === 'function') {
            this.descargarArchivoSinNavegar(href);
            return;
        }

        const previousFrame = document.getElementById('ficDownloadFrame');
        if (previousFrame && previousFrame.parentNode) {
            previousFrame.setAttribute('src', 'about:blank');
            previousFrame.parentNode.removeChild(previousFrame);
        }

        const iframe = document.createElement('iframe');
        iframe.id = 'ficDownloadFrame';
        iframe.style.display = 'none';
        iframe.setAttribute('aria-hidden', 'true');
        iframe.src = href;
        document.body.appendChild(iframe);
        setTimeout(() => {
            if (window.FicLoading && typeof window.FicLoading.hide === 'function') {
                window.FicLoading.hide();
            }
        }, 1000);
    },

    escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    },

    normalizarUrlDocumento(value) {
        let ruta = String(value || '').trim();
        if (!ruta) return '';
        if (/^https?:\/\//i.test(ruta)) return ruta;
        if (/^\/\//.test(ruta)) return 'https:' + ruta;

        ruta = ruta.replace(/^\/+/, '');
        return S3_PUBLIC_BASE_URL + ruta.split('/').map(encodeURIComponent).join('/');
    },

    documentos(value, row) {
        row = row || {};
        const archivos = [
            { campo: 'ine_frontal', titulo: 'INE frontal', icono: 'mdi-card-account-details' },
            { campo: 'ine_trasera', titulo: 'INE trasera', icono: 'mdi-card-account-details-outline' },
            { campo: 'firma', titulo: 'Firma', icono: 'mdi-draw-pen' }
        ];

        const botones = archivos.map((archivo) => {
            const url = cajeros.normalizarUrlDocumento(row && row[archivo.campo] ? row[archivo.campo] : '');
            if (!url) {
                return `<button class="btn btn-outline-secondary" type="button" title="${archivo.titulo} no disponible" disabled>
                    <i class="mdi ${archivo.icono}"></i>
                </button>`;
            }

            const encodedUrl = encodeURIComponent(url);
            return `<button class="btn btn-outline-info" type="button" title="${archivo.titulo}" onclick="cajeros.abrirDocumento(decodeURIComponent('${cajeros.escapeHtml(encodedUrl)}'))">
                <i class="mdi ${archivo.icono}"></i>
            </button>`;
        });

        return `<div class="cajero-documents">${botones.join('')}</div>`;
    },

    abrirDocumento(url) {
        const documentoUrl = cajeros.normalizarUrlDocumento(url);
        if (!documentoUrl) {
            Swal.fire('Atención', 'No hay documento disponible.', 'warning');
            return;
        }
        window.open(documentoUrl, '_blank', 'noopener');
    },

    documentos(value, row) {
        row = row || {};
        const docs = cajeros.obtenerDocumentos(row);
        const count = docs.filter((doc) => doc.path !== '').length;
        if (!count) {
            return '<span class="text-muted">Sin documentos</span>';
        }

        return `
            <button class="btn btn-outline-info btn-sm" type="button" title="Ver documentos" onclick="cajeros.verDocumentos(${Number(row.id_usuario || 0)})">
                <i class="mdi mdi-folder-eye me-1"></i>${count}
            </button>`;
    },

    obtenerDocumentos(row) {
        row = row || {};
        return [
            { field: 'qr', label: 'QR', path: String(row.qr || '') },
            { field: 'ine_firma_cajero', label: 'PDF INE y firma', path: String(row.ine_firma_cajero || '') },
            { field: 'ine_frontal', label: 'INE frontal', path: String(row.ine_frontal || '') },
            { field: 'ine_trasera', label: 'INE trasera', path: String(row.ine_trasera || '') },
            { field: 'firma', label: 'Firma', path: String(row.firma || '') }
        ];
    },

    verDocumentos(idUsuario) {
        const rows = $('#cajerosTable').bootstrapTable('getData', { useCurrentPage: false }) || [];
        const row = rows.find((item) => Number(item.id_usuario || 0) === Number(idUsuario || 0));
        if (!row) {
            Swal.fire('Atención', 'No fue posible resolver los documentos del usuario.', 'warning');
            return;
        }

        this.documentosActuales = this.obtenerDocumentos(row);
        this.documentoUsuarioId = Number(row.id_usuario || 0);
        $('#cajeroDocumentosModalTitle').text('Documentos de usuario');
        $('#cajeroDocumentosModalSubtitle').text((row.nombre_completo || row.usuario || '') + ' | ID ' + String(row.id_usuario || ''));

        const buttons = this.documentosActuales.map((doc) => {
            const disabled = doc.path === '' ? ' disabled' : '';
            const badge = doc.path === '' ? '<span class="badge bg-secondary ms-auto">Sin archivo</span>' : '<span class="badge bg-success ms-auto">Disponible</span>';
            return '<button type="button" class="btn btn-outline-light d-flex align-items-center gap-2 js-cajero-doc-open"' + disabled + ' data-field="' + this.escapeHtml(doc.field) + '"><i class="mdi mdi-file-document-outline"></i><span>' + this.escapeHtml(doc.label) + '</span>' + badge + '</button>';
        }).join('');

        $('#cajeroDocumentosList').html(buttons);
        $('#cajeroDocumentosPreview').attr('class', 'cajero-doc-preview d-flex align-items-center justify-content-center text-muted p-3').html('Selecciona un documento.');
        $('#cajeroDocumentosOpen').attr('href', '#').addClass('disabled');
        this.documentoActualUrl = '';

        if (this.documentosModal) {
            this.documentosModal.show();
        }
    },

    abrirDocumento(field) {
        const doc = (this.documentosActuales || []).find((item) => item.field === field);
        if (!doc || doc.path === '') {
            Swal.fire('Atención', 'No hay archivo disponible.', 'warning');
            return;
        }

        const userId = String(this.documentoUsuarioId || '');
        const baseUrl = $('#cajeroPage').data('documento-url') || '';
        if (!baseUrl || !userId) {
            Swal.fire('Error', 'No fue posible generar la ruta del documento.', 'error');
            return;
        }

        const url = baseUrl + '?id_usuario=' + encodeURIComponent(userId) + '&campo=' + encodeURIComponent(doc.field);
        const ext = this.obtenerExtension(doc.path);
        const safeUrl = this.escapeHtml(url);
        const safeLabel = this.escapeHtml(doc.label);
        let preview = '';
        let previewClass = 'cajero-doc-preview';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1) {
            previewClass += doc.field === 'firma' ? ' cajero-doc-preview--firma' : '';
            preview = '<img src="' + safeUrl + '" alt="' + safeLabel + '">';
        } else if (ext === 'pdf') {
            preview = '<iframe src="' + safeUrl + '" title="' + safeLabel + '"></iframe>';
        } else {
            preview = '<div class="alert alert-info m-3">No hay vista previa para este tipo de archivo. Usa el botón para abrirlo en una nueva pestaña.</div>';
        }

        $('#cajeroDocumentosPreview').attr('class', previewClass).html(preview);
        $('#cajeroDocumentosOpen').attr('href', url).removeClass('disabled');
        this.documentoActualUrl = url;
    },

    obtenerExtension(path) {
        const clean = String(path || '').split('?')[0].split('#')[0];
        const parts = clean.split('.');
        return parts.length > 1 ? String(parts.pop() || '').toLowerCase() : '';
    },

    actualizarFilaLocal(idUsuario, cambios) {
        const table = $('#cajerosTable');
        const rows = table.bootstrapTable('getData', { useCurrentPage: false, includeHiddenRows: true }) || [];
        const index = rows.findIndex((item) => Number(item.id_usuario || item.ID_USUARIO || 0) === Number(idUsuario || 0));
        if (index === -1) return;

        const row = Object.assign({}, rows[index], cambios || {});
        table.bootstrapTable('updateRow', {
            index,
            row
        });
    },

    removerFilaLocal(idUsuario) {
        const table = $('#cajerosTable');
        const rows = table.bootstrapTable('getData', { useCurrentPage: false, includeHiddenRows: true }) || [];
        const nextRows = rows.filter((item) => Number(item.id_usuario || item.ID_USUARIO || 0) !== Number(idUsuario || 0));
        table.bootstrapTable('load', nextRows);
    },

    acciones(value, row) {
        row = row || {};
        const idUsuario = Number(row.id_usuario || 0);
        const puedeGestionarQr = !!cajeroPuedeGestionarQr;
        const expedienteCompleto = cajeros.tieneExpedienteCompleto(row);
        const qrActivo = Number(row.activo_qr || row.qr_activo || 0) === 1;
        const botonActivarQr = !expedienteCompleto
            ? `<button class="btn btn-outline-secondary" type="button" title="No se puede validar QR sin documentos cargados" disabled><i class="mdi mdi-check-circle-outline"></i></button>`
            : qrActivo
                ? `<button class="btn btn-success" type="button" title="QR validado" disabled><i class="mdi mdi-check-circle-outline"></i></button>`
                : `<button class="btn btn-outline-success" type="button" title="Validar QR" onclick="cajeros.activarQr(${idUsuario})"><i class="mdi mdi-check-circle-outline"></i></button>`;
        let botonRechazarQr = !expedienteCompleto
            ? `<button class="btn btn-outline-secondary" type="button" title="No se puede declinar QR sin documentos cargados" disabled><i class="mdi mdi-close-circle-outline"></i></button>`
            : `<button class="btn btn-outline-danger" type="button" title="Rechazar activación QR" onclick="cajeros.rechazarActivacionQr(${idUsuario})"><i class="mdi mdi-qrcode-remove"></i> Rechazar QR</button>`;
        if (expedienteCompleto) {
            botonRechazarQr = `<button class="btn btn-outline-danger" type="button" title="Declinar QR" onclick="cajeros.rechazarActivacionQr(${idUsuario})"><i class="mdi mdi-close-circle-outline"></i></button>`;
        }

        let botones = `
            <div class="cajero-actions">
              
                <button class="btn btn-primary" type="button" title="Orden de Hospedaje y Alimentos" onclick="st.agregar.verPdf(${idUsuario})">
                    <i class="mdi mdi-file-pdf-box"></i>
                </button>
                ${puedeGestionarQr ? botonActivarQr : ''}
                ${puedeGestionarQr ? botonRechazarQr : ''}`;

        if (!cajeroSoloConsulta) {
            botones += `
                <button class="btn btn-outline-info" type="button" title="Subir PDF INE y firma" onclick="cajeros.seleccionarFirmaCajero(${idUsuario})">
                    <i class="mdi mdi-file-upload-outline"></i>
                </button>
                <button class="btn btn-outline-warning" type="button" title="Editar" onclick="cajeros.editar(${idUsuario})">
                    <i class="mdi mdi-account-edit"></i>
                </button>
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(${idUsuario})">
                    <i class="mdi mdi-account-remove"></i>
                </button>`;
        }

        return botones + '</div>';
    },

    tieneExpedienteCompleto(row) {
        row = row || {};
        return ['ine_firma_cajero', 'ine_frontal', 'ine_trasera', 'firma'].some((field) => {
            return String(row[field] || '').trim() !== '';
        });
    },

    seleccionarFirmaCajero(idUsuario) {
        if (!idUsuario) return;

        Swal.fire({
            title: 'Subir PDF INE y firma',
            html: `
                <div class="text-start">
                    <label class="form-label" for="swal_archivo_documento_cajero">Archivo PDF</label>
                    <input id="swal_archivo_documento_cajero" type="file" class="form-control" accept="application/pdf,.pdf">
                    <div class="form-text text-muted mt-2">El PDF debe integrar INE y firma en una sola hoja. Maximo 10 MB.</div>
                </div>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Subir',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const inputArchivo = document.getElementById('swal_archivo_documento_cajero');
                const archivo = inputArchivo && inputArchivo.files && inputArchivo.files[0] ? inputArchivo.files[0] : null;

                if (!archivo) {
                    Swal.showValidationMessage('Selecciona un PDF.');
                    return false;
                }

                if (archivo.size > 10 * 1024 * 1024) {
                    Swal.showValidationMessage('El archivo no debe pesar mas de 10 MB.');
                    return false;
                }

                const nombre = archivo.name || '';
                const tipo = archivo.type || '';
                const permitido = /\.pdf$/i.test(nombre) || tipo === 'application/pdf';

                if (!permitido) {
                    Swal.showValidationMessage('El archivo debe ser PDF.');
                    return false;
                }

                return { archivo };
            }
        }).then((result) => {
            if (!result.isConfirmed || !result.value) return;
            this.subirFirmaCajero(idUsuario, result.value.archivo);
        });
    },

    subirFirmaCajero(idUsuario, archivo) {
        const nombreArchivo = archivo && archivo.name ? archivo.name : '';
        const tipoArchivo = archivo && archivo.type ? archivo.type : '';
        const esArchivoValido = archivo && (
            tipoArchivo === 'application/pdf'
            || /\.pdf$/i.test(nombreArchivo)
        );

        if (!esArchivoValido) {
            Swal.fire('Atencion', 'Solo puedes subir archivos PDF.', 'warning');
            return;
        }

        const data = new FormData();
        data.append('id_usuario', idUsuario);
        data.append('ine_firma_cajero', archivo);

        Swal.fire({
            title: 'Subiendo PDF',
            text: 'Espera un momento...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: base_url + 'index.php/Usuario/subirIneFirmaCajero',
            type: 'POST',
            dataType: 'json',
            data,
            processData: false,
            contentType: false
        }).done((response) => {
            if (!response || response.error) {
                Swal.fire('Atencion', response && response.respuesta ? response.respuesta : 'No fue posible subir el archivo.', 'warning');
                return;
            }

            cajeros.actualizarFilaLocal(idUsuario, {
                [response.campo || 'ine_firma_cajero']: response.ruta || '',
                expediente_completo: true
            });
            if (window.ficRealtime && typeof window.ficRealtime.emit === 'function') {
                window.ficRealtime.emit('fic:usuario-documentos-subidos', {
                    id_usuario: idUsuario,
                    campo: response.campo || 'ine_firma_cajero',
                    ruta: response.ruta || ''
                });
            }
            Swal.fire('Correcto', response.respuesta || 'Archivo guardado correctamente.', 'success');
        }).fail((request) => {
            const response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible subir el archivo.', 'error');
        });
    },

    activarQr(idUsuario) {
        if (!idUsuario) return;

        const rows = $('#cajerosTable').bootstrapTable('getData', { useCurrentPage: false, includeHiddenRows: true }) || [];
        const row = rows.find((item) => Number(item.id_usuario || item.ID_USUARIO || 0) === Number(idUsuario || 0));
        if (!cajeros.tieneExpedienteCompleto(row || {})) {
            Swal.fire('Atencion', 'No se puede activar el usuario porque no tiene documentos cargados.', 'warning');
            return;
        }

        Swal.fire({
            title: '¿Estas seguro de activar QR?',
            text: 'Se marcará el QR del usuario como activo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí­, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: base_url + 'index.php/Inicio/activarQrUsuarioFic',
                type: 'POST',
                dataType: 'json',
                data: { id_usuario: idUsuario }
            }).done((response) => {
                if (!response || response.success !== true) {
                    Swal.fire('AtenciÃƒÂ³n', response && response.message ? response.message : 'No fue posible activar el QR.', 'warning');
                    return;
                }

                Swal.fire('Correcto', response.message || 'QR activado correctamente.', 'success');
                cajeros.actualizarFilaLocal(idUsuario, {
                    activo_qr: 1,
                    qr_activo: 1
                });
                if (window.ficRealtime && typeof window.ficRealtime.emit === 'function') {
                    window.ficRealtime.emit('fic:usuario-qr-actualizado', {
                        id_usuario: idUsuario,
                        activo_qr: 1,
                        accion: 'activar'
                    });
                }
            }).fail((request) => {
                const response = request.responseJSON || {};
                Swal.fire('Error', response.message || 'No fue posible activar el QR.', 'error');
            });
        });
    },

    rechazarActivacionQr(idUsuario) {
        if (!idUsuario) return;

        Swal.fire({
            title: '¿Rechazar activación QR?',
            text: 'Se retirará la activación y el usuario podrá iniciar nuevamente su proceso.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, rechazar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: base_url + 'index.php/Inicio/rechazarActivacionQrUsuarioFic',
                type: 'POST',
                dataType: 'json',
                data: { id_usuario: idUsuario }
            }).done((response) => {
                if (!response || response.success !== true) {
                    Swal.fire('Atención', response && response.message ? response.message : 'No fue posible rechazar la activación del QR.', 'warning');
                    return;
                }

                Swal.fire('Correcto', response.message || 'La activaciÃ³n fue rechazada.', 'success');
                cajeros.actualizarFilaLocal(idUsuario, {
                    activo_qr: 0,
                    qr_activo: 0,
                    qr: '',
                    ine_firma_cajero: '',
                    ine_frontal: '',
                    ine_trasera: '',
                    firma: '',
                    expediente_completo: false
                });
                if (window.ficRealtime && typeof window.ficRealtime.emit === 'function') {
                    window.ficRealtime.emit('fic:usuario-qr-actualizado', {
                        id_usuario: idUsuario,
                        activo_qr: 0,
                        accion: 'rechazar'
                    });
                }
            }).fail((request) => {
                const response = request.responseJSON || {};
                Swal.fire('Error', response.message || 'No fue posible rechazar la activación del QR.', 'error');
            });
        });
    },

    nuevo() {
        if (!this.modal) return;
        document.getElementById('cajeroForm').reset();
        $('#id_usuario').val('');
        $('#contrasenia').prop('required', true);
        $('#cajeroModalTitle').text('Nuevo cajero');
        this.modal.show();
    },

    editar(idUsuario) {
        if (!idUsuario) return;
        window.location.href = base_url + 'index.php/Inicio/AltaUsuario/' + encodeURIComponent(idUsuario);
    },

    verPdf(idUsuario) {
        window.open(base_url + 'index.php/Usuario/generarPdfHospedaje/' + idUsuario, '_blank');
    },

    guardar() {
        const boton = $('#guardarCajero').prop('disabled', true);
        $.ajax({
            url: base_url + 'index.php/Usuario/saveCajero',
            type: 'POST',
            dataType: 'json',
            data: $('#cajeroForm').serialize()
        }).done((response) => {
            if (response.error) {
                Swal.fire('Atención', response.respuesta, 'warning');
                return;
            }
            if (this.modal) this.modal.hide();
            $('#cajerosTable').bootstrapTable('refresh');
            Swal.fire('Correcto', 'Cajero guardado correctamente.', 'success');
        }).fail(() => Swal.fire('Error', 'No fue posible guardar el cajero.', 'error'))
          .always(() => boton.prop('disabled', false));
    },

    eliminar(idUsuario) {
        Swal.fire({
            title: '¿Eliminar cajero?',
            text: 'El registro dejará de mostrarse en la tabla.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.post(base_url + 'index.php/Usuario/deleteUsuario', { id_usuario: idUsuario }, (response) => {
                if (response.error) {
                    Swal.fire('Atención', response.respuesta, 'warning');
                    return;
                }
                cajeros.removerFilaLocal(idUsuario);
                Swal.fire('Correcto', 'Cajero eliminado correctamente.', 'success');
            }, 'json').fail(() => Swal.fire('Error', 'No fue posible eliminar el cajero.', 'error'));
        });
    }
});

$(function () {
    cajeros.iniciar();
    if (window.ficRealtime && typeof window.ficRealtime.on === 'function') {
        window.ficRealtime.on('fic:usuario-documentos-subidos', function () {
            $('#cajerosTable').bootstrapTable('refresh', { silent: true });
        });
        window.ficRealtime.on('fic:usuario-qr-actualizado', function () {
            $('#cajerosTable').bootstrapTable('refresh', { silent: true });
        });
    }
});
</script>

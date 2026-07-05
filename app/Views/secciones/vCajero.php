<?php $session = \Config\Services::session(); ?>
<style>
    .cajero-documents,
    .cajero-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: .25rem;
        white-space: nowrap;
    }

    .cajero-documents .btn,
    .cajero-actions .btn {
        display: inline-flex;
        min-width: 32px;
        height: 30px;
        align-items: center;
        justify-content: center;
        padding: 0 .45rem;
    }
</style>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administración de cajeros</h3>
            <p class="text-muted mb-0">Consulta, registra, edita o elimina cajeros.</p>
        </div>
       <?php if ($session->get('id_perfil') == 1): ?> 
        <button type="button" class="btn btn-primary" onclick="cajeros.nuevo()">
            <i class="mdi mdi-account-plus me-1"></i> Nuevo cajero
        </button>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="cajerosTable"
                   class="table table-dark table-hover align-middle"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="50"
                   data-page-list="[5,10,25,50,100]"
                   data-show-columns="true"
                   data-show-refresh="true"
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
                            <small class="text-muted">En edición, déjala vacía para conservar la actual.</small>
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

<script>
const id_perfil = <?= json_encode($session->get('id_perfil')) ?>;
const S3_PUBLIC_BASE_URL = 'https://sectur-audiovisuales-509634423753-us-east-1-an.s3.amazonaws.com/';
window.cajeros = {
    modal: null,

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
                if (Array.isArray(response)) return response;
                if (response && Array.isArray(response.data)) return response.data;
                if (response && Array.isArray(response.rows)) return response.rows;
                console.error('Respuesta inválida al cargar cajeros:', response);
                return [];
            },
            onLoadError: (status, request) => {
                console.error('Error al cargar cajeros:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los cajeros.', 'error');
            }
        });

        if (window.bootstrap && bootstrap.Modal) {
            this.modal = new bootstrap.Modal(document.getElementById('cajeroModal'));
        }

        $('#cajeroForm').on('submit', (event) => {
            event.preventDefault();
            this.guardar();
        });
    },

    estado(value) {
        if (Number(value) === 1) return '<span class="badge bg-success">Sí</span>';
        if (Number(value) === 2 || Number(value) === 0) return '<span class="badge bg-danger">No</span>';
        return '<span class="badge bg-secondary">Pendiente</span>';
    },

    moneda(value) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
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

    acciones(value, row) {
        row = row || {};
        const idUsuario = Number(row.id_usuario || 0);
        const qrActivo = Number(row.activo_qr || row.qr_activo || 0) === 1;
        let botones = `
            <div class="cajero-actions">
                <button class="btn btn-warning" type="button" title="Editar" onclick="cajeros.editar(${idUsuario})">
                    <i class="mdi mdi-account-edit"></i>
                </button>
                <button class="btn btn-primary" type="button" title="Orden de Hospedaje" onclick="st.agregar.verPdf(${idUsuario})">
                    <i class="mdi mdi-file-pdf-box"></i>
                </button>
                <button class="btn btn-secondary" type="button" title="Orden de Alimentos no disponible" onclick="st.agregar.verPdfAlimentos(${idUsuario})">
                    <i class="mdi mdi-file-pdf"></i>
                </button>
                <button class="btn btn-outline-light" type="button" title="Subir PDF firma cajero" onclick="cajeros.seleccionarFirmaCajero(${idUsuario})">
                    <i class="mdi mdi-file-upload-outline"></i>
                </button>
                ${qrActivo
                    ? `<button class="btn btn-success" type="button" title="QR activo" disabled><i class="mdi mdi-qrcode-check"></i></button>`
                    : `<button class="btn btn-outline-success" type="button" title="Activar QR" onclick="cajeros.activarQr(${idUsuario})">Activar QR</button>`}`;

        if (Number(id_perfil) === 1) {
            botones += `
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(${idUsuario})">
                    <i class="mdi mdi-account-remove"></i>
                </button>`;
        }

        return botones + '</div>';
    },

    seleccionarFirmaCajero(idUsuario) {
        if (!idUsuario) return;

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'application/pdf,.pdf';
        input.onchange = () => {
            const archivo = input.files && input.files[0] ? input.files[0] : null;
            if (!archivo) return;
            this.subirFirmaCajero(idUsuario, archivo);
        };
        input.click();
    },

    subirFirmaCajero(idUsuario, archivo) {
        const nombreArchivo = archivo && archivo.name ? archivo.name : '';
        const esPdf = archivo && (archivo.type === 'application/pdf' || /\.pdf$/i.test(nombreArchivo));

        if (!esPdf) {
            Swal.fire('Atención', 'Solo puedes subir archivos PDF.', 'warning');
            return;
        }

        const data = new FormData();
        data.append('id_usuario', idUsuario);
        data.append('ine_firma_cajero', archivo);

        $.ajax({
            url: base_url + 'index.php/Usuario/subirIneFirmaCajero',
            type: 'POST',
            dataType: 'json',
            data,
            processData: false,
            contentType: false
        }).done((response) => {
            if (!response || response.error) {
                Swal.fire('Atención', response && response.respuesta ? response.respuesta : 'No fue posible subir el PDF.', 'warning');
                return;
            }

            $('#cajerosTable').bootstrapTable('refresh');
            Swal.fire('Correcto', response.respuesta || 'PDF guardado correctamente.', 'success');
        }).fail((request) => {
            const response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible subir el PDF.', 'error');
        });
    },

    activarQr(idUsuario) {
        if (!idUsuario) return;

        Swal.fire({
            title: '¿Estas seguro de activar QR?',
            text: 'Se marcará el QR del usuario como activo.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, activar',
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
                    Swal.fire('AtenciÃ³n', response && response.message ? response.message : 'No fue posible activar el QR.', 'warning');
                    return;
                }

                Swal.fire('Correcto', response.message || 'QR activado correctamente.', 'success');
                $('#cajerosTable').bootstrapTable('refresh');
            }).fail((request) => {
                const response = request.responseJSON || {};
                Swal.fire('Error', response.message || 'No fue posible activar el QR.', 'error');
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
        $.post(base_url + 'index.php/Usuario/getUsuario', { id_usuario: idUsuario }, (data) => {
            $('#id_usuario').val(data.id_usuario);
            $('#nombre').val(data.nombre);
            $('#primer_apellido').val(data.primer_apellido);
            $('#segundo_apellido').val(data.segundo_apellido);
            $('#correo').val(data.correo);
            $('#usuario').val(data.usuario);
            $('#contrasenia').val('').prop('required', false);
            $('#cajeroModalTitle').text('Editar cajero');
            if (this.modal) this.modal.show();
        }, 'json').fail(() => Swal.fire('Error', 'No fue posible obtener el cajero.', 'error'));
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
                $('#cajerosTable').bootstrapTable('refresh');
                Swal.fire('Correcto', 'Cajero eliminado correctamente.', 'success');
            }, 'json').fail(() => Swal.fire('Error', 'No fue posible eliminar el cajero.', 'error'));
        });
    }
};

$(function () {
    cajeros.iniciar();
});
</script>

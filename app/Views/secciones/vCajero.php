<?php $session = \Config\Services::session(); ?>
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
                        <th data-field="deposito_programado_estatus" data-formatter="cajeros.estadoProgramaDeposito" data-align="center">Estado del programa</th>
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
window.cajeros = {
    modal: null,

    iniciar() {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#cajerosTable').bootstrapTable({
            url: base_url + 'index.php/Usuario/getUsuarios',
            responseHandler: (response) => {
                if (Array.isArray(response)) return response;
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

    estadoProgramaDeposito(value) {
        var estado = String(value || '').trim().toLowerCase();
        if (estado === 'reservado') return '<span class="badge bg-warning text-dark">Reservado</span>';
        if (estado === 'operativo') return '<span class="badge bg-success">Operativo</span>';
        if (estado === 'parcial') return '<span class="badge bg-info text-dark">Parcial</span>';
        if (estado === 'aplicado') return '<span class="badge bg-primary">Aplicado</span>';
        if (estado === 'error') return '<span class="badge bg-danger">Error</span>';
        if (estado === 'cancelado') return '<span class="badge bg-secondary">Cancelado</span>';
        if (estado === 'sin_programa') return '<span class="badge bg-light text-dark">Sin programa</span>';
        return '<span class="badge bg-secondary">Sin definir</span>';
    },

    acciones(value, row) {
        let botones = `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" type="button" title="Editar" onclick="cajeros.editar(${row.id_usuario})">
                    <i class="mdi mdi-account-edit"></i>
                </button>
                <button class="btn btn-primary" type="button" title="Orden de Hospedaje" onclick="st.agregar.verPdf(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf-box"></i>
                </button>
                <button class="btn btn-secondary" type="button" title="Orden de Alimentos no disponible" onclick="st.agregar.verPdfAlimentos(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf"></i>
                </button>`;

        if (Number(id_perfil) === 1) {
            botones += `
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(${row.id_usuario})">
                    <i class="mdi mdi-account-remove"></i>
                </button>`;
        }

        return botones + '</div>';
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

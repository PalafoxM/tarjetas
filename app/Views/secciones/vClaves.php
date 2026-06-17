<?php $session = \Config\Services::session(); ?>
<div class="container-fluid py-4" id="clavesPage" data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administración de claves</h3>
            <p class="text-muted mb-0">Consulta, registra, edita o elimina claves del catálogo.</p>
        </div>
        <?php if ((int) $session->get('id_perfil') === 1): ?>
            <button type="button" class="btn btn-primary" id="nuevaClave">
                <i class="mdi mdi-key-plus me-1"></i> Nueva clave
            </button>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Atrás
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="clavesTable"
                   class="table table-dark table-hover align-middle"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-page-list="[5,10,25,50,100]"
                   data-show-columns="true"
                   data-show-refresh="true"
                   data-locale="es-MX">
                <thead>
                    <tr>
                        <th data-field="id_clave" data-sortable="true" data-align="center">ID</th>
                        <th data-field="clave" data-sortable="true">Clave</th>
                        <th data-field="dsc_clave"  data-sortable="true">Descripción</th>
                        <th data-field="direccion" data-formatter="claves.descripcion" data-sortable="true">Dirección</th>
                        <th data-field="acciones" data-formatter="claves.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="claveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="claveForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="claveModalTitle">Nueva clave</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_clave" id="id_clave">
                    <div class="mb-3">
                        <label class="form-label" for="clave">Clave</label>
                        <input class="form-control" name="clave" id="clave" maxlength="80" autocomplete="off" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="dsc_clave">Descripción</label>
                        <textarea class="form-control" name="dsc_clave" id="dsc_clave" rows="3" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="direccion">Dirección</label>
                        <textarea class="form-control" name="direccion" id="direccion" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardarClave">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.claves = {
    modal: null,
    idPerfil: Number(document.getElementById('clavesPage')?.dataset.idPerfil || 0),

    iniciar: function () {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#clavesTable').bootstrapTable({
            url: base_url + 'index.php/Principal/ObtenerClaves',
            responseHandler: function (response) {
                if (Array.isArray(response)) return response;
                if (response && Array.isArray(response.data)) return response.data;
                console.error('Respuesta inválida al cargar claves:', response);
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar claves:', status, request && request.responseText);
                Swal.fire('Error', 'No fue posible consultar las claves.', 'error');
            }
        });

        if (window.bootstrap && bootstrap.Modal) {
            this.modal = new bootstrap.Modal(document.getElementById('claveModal'));
        }

        $('#nuevaClave').on('click', this.nueva.bind(this));
        $('#claveForm').on('submit', function (event) {
            event.preventDefault();
            claves.guardar();
        });
    },

    id: function (row) {
        return Number(row.id_clave || row.id || row.id_cat_clave || 0);
    },

    descripcion: function (value, row) {
        return value || row.descripcion || row.dsc_descripcion || '';
    },

    acciones: function (value, row) {
        if (claves.idPerfil !== 1) return '<span class="text-muted">Sin permisos</span>';

        var idClave = claves.id(row);
        return `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" type="button" title="Editar" onclick="claves.editar(${idClave})">
                    <i class="mdi mdi-pencil"></i>
                </button>
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="claves.eliminar(${idClave})">
                    <i class="mdi mdi-trash-can"></i>
                </button>
            </div>`;
    },

    nueva: function () {
        document.getElementById('claveForm').reset();
        $('#id_clave').val('');
        $('#claveModalTitle').text('Nueva clave');
        if (this.modal) this.modal.show();
    },

    editar: function (idClave) {
        if (!idClave) return;

        $.post(base_url + 'index.php/Principal/ObtenerClave', { id_clave: idClave }, function (data) {
            $('#id_clave').val(data.id_clave || data.id || data.id_cat_clave || idClave);
            $('#clave').val(data.clave || '');
            $('#dsc_clave').val(data.dsc_clave || data.descripcion || data.dsc_descripcion || '');
            $('#direccion').val(data.direccion || data.descripcion || data.dsc_descripcion || '');
            $('#claveModalTitle').text('Editar clave');
            if (claves.modal) claves.modal.show();
        }, 'json').fail(function () {
            Swal.fire('Error', 'No fue posible obtener la clave.', 'error');
        });
    },

    guardar: function () {
        var boton = $('#guardarClave').prop('disabled', true);
        $.ajax({
            url: base_url + 'index.php/Principal/GuardarClave',
            type: 'POST',
            dataType: 'json',
            data: $('#claveForm').serialize()
        }).done(function (response) {
            if (response.error) {
                Swal.fire('Atención', response.respuesta || 'No fue posible guardar la clave.', 'warning');
                return;
            }

            if (claves.modal) claves.modal.hide();
            $('#clavesTable').bootstrapTable('refresh');
            Swal.fire('Correcto', 'Clave guardada correctamente.', 'success');
        }).fail(function () {
            Swal.fire('Error', 'No fue posible guardar la clave.', 'error');
        }).always(function () {
            boton.prop('disabled', false);
        });
    },

    eliminar: function (idClave) {
        if (!idClave) return;

        Swal.fire({
            title: '¿Eliminar clave?',
            text: 'El registro dejará de mostrarse en la tabla.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.post(base_url + 'index.php/Principal/EliminarClave', { id_clave: idClave }, function (response) {
                if (response.error) {
                    Swal.fire('Atención', response.respuesta || 'No fue posible eliminar la clave.', 'warning');
                    return;
                }

                $('#clavesTable').bootstrapTable('refresh');
                Swal.fire('Correcto', 'Clave eliminada correctamente.', 'success');
            }, 'json').fail(function () {
                Swal.fire('Error', 'No fue posible eliminar la clave.', 'error');
            });
        });
    }
};

$(function () {
    claves.iniciar();
});
</script>

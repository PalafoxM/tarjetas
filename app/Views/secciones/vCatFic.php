<?php $session = \Config\Services::session(); ?>
<div class="container-fluid py-4" id="catFicPage" data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administración de FIC</h3>
            <p class="text-muted mb-0">Consulta, registra, edita o elimina perfiles del catálogo FIC.</p>
        </div>
        <?php if ((int) $session->get('id_perfil') === 1): ?>
            <button type="button" class="btn btn-primary" id="nuevoFic">
                <i class="mdi mdi-plus-circle me-1"></i> Nuevo perfil FIC
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
            <table id="catFicTable"
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
                        <th data-field="id_perfil_fic" data-sortable="true" data-align="center">ID</th>
                        <th data-field="dsc_perfil" data-formatter="catFic.descripcion" data-sortable="true">Perfil</th>
                        <th data-field="acciones" data-formatter="catFic.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="ficModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="ficForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="ficModalTitle">Nuevo perfil FIC</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_perfil_fic" id="id_perfil_fic">
                    <div class="mb-0">
                        <label class="form-label" for="dsc_perfil">Perfil FIC</label>
                        <input class="form-control" name="dsc_perfil" id="dsc_perfil" maxlength="150" autocomplete="off" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardarFic">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.catFic = {
    modal: null,
    idPerfil: Number(document.getElementById('catFicPage')?.dataset.idPerfil || 0),

    iniciar: function () {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#catFicTable').bootstrapTable({
            url: base_url + 'index.php/Principal/ObtenerCatFic',
            responseHandler: function (response) {
                if (Array.isArray(response)) return response;
                if (response && Array.isArray(response.data)) return response.data;
                console.error('Respuesta inválida al cargar perfiles FIC:', response);
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar perfiles FIC:', status, request && request.responseText);
                Swal.fire('Error', 'No fue posible consultar los perfiles FIC.', 'error');
            }
        });

        if (window.bootstrap && bootstrap.Modal) {
            this.modal = new bootstrap.Modal(document.getElementById('ficModal'));
        }

        $('#nuevoFic').on('click', this.nuevo.bind(this));
        $('#ficForm').on('submit', function (event) {
            event.preventDefault();
            catFic.guardar();
        });
    },

    id: function (row) {
        return Number(row.id_perfil_fic || row.id || row.id_cat_fic || 0);
    },

    descripcion: function (value, row) {
        return value || row.descripcion || row.dsc_descripcion || '';
    },

    acciones: function (value, row) {
        if (catFic.idPerfil !== 1) return '<span class="text-muted">Sin permisos</span>';

        var idFic = catFic.id(row);
        return `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" type="button" title="Editar" onclick="catFic.editar(${idFic})">
                    <i class="mdi mdi-pencil"></i>
                </button>
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="catFic.eliminar(${idFic})">
                    <i class="mdi mdi-trash-can"></i>
                </button>
            </div>`;
    },

    nuevo: function () {
        document.getElementById('ficForm').reset();
        $('#id_perfil_fic').val('');
        $('#ficModalTitle').text('Nuevo perfil FIC');
        if (this.modal) this.modal.show();
    },

    editar: function (idFic) {
        if (!idFic) return;

        $.post(base_url + 'index.php/Principal/ObtenerFic', { id_perfil_fic: idFic }, function (data) {
            $('#id_perfil_fic').val(data.id_perfil_fic || data.id || data.id_cat_fic || idFic);
            $('#dsc_perfil').val(data.dsc_perfil || data.descripcion || data.dsc_descripcion || '');
            $('#ficModalTitle').text('Editar perfil FIC');
            if (catFic.modal) catFic.modal.show();
        }, 'json').fail(function () {
            Swal.fire('Error', 'No fue posible obtener el perfil FIC.', 'error');
        });
    },

    guardar: function () {
        var boton = $('#guardarFic').prop('disabled', true);
        $.ajax({
            url: base_url + 'index.php/Principal/GuardarFic',
            type: 'POST',
            dataType: 'json',
            data: $('#ficForm').serialize()
        }).done(function (response) {
            if (response.error) {
                Swal.fire('Atención', response.respuesta || 'No fue posible guardar el perfil FIC.', 'warning');
                return;
            }

            if (catFic.modal) catFic.modal.hide();
            $('#catFicTable').bootstrapTable('refresh');
            Swal.fire('Correcto', 'Perfil FIC guardado correctamente.', 'success');
        }).fail(function () {
            Swal.fire('Error', 'No fue posible guardar el perfil FIC.', 'error');
        }).always(function () {
            boton.prop('disabled', false);
        });
    },

    eliminar: function (idFic) {
        if (!idFic) return;

        Swal.fire({
            title: '¿Eliminar perfil FIC?',
            text: 'El registro dejará de mostrarse en la tabla.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.post(base_url + 'index.php/Principal/EliminarFic', { id_perfil_fic: idFic }, function (response) {
                if (response.error) {
                    Swal.fire('Atención', response.respuesta || 'No fue posible eliminar el perfil FIC.', 'warning');
                    return;
                }

                $('#catFicTable').bootstrapTable('refresh');
                Swal.fire('Correcto', 'Perfil FIC eliminado correctamente.', 'success');
            }, 'json').fail(function () {
                Swal.fire('Error', 'No fue posible eliminar el perfil FIC.', 'error');
            });
        });
    }
};

$(function () {
    catFic.iniciar();
});
</script>

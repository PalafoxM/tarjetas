<?php $session = \Config\Services::session(); ?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administración de hospedaje</h3>
            <p class="text-muted mb-0">Consulta hospedaje.</p>
        </div>
      
    </div>
     <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Atrás
    </a>

    <div class="card">
        <div class="card-body">
            <table id="hospedajeTable"
                           class="table table-dark table-hover align-middle"
                           data-search="true"
                           data-search-align="right"
                           data-pagination="true"
                           data-page-size="25"
                           data-page-list="[5,10,25,50,100]"
                           data-locale="es-MX"
                           data-show-refresh="true">
                        <thead>
                            <tr>
                                <th data-field="folio_hospedaje" data-sortable="true">Folio</th>
                                <th data-field="nombre_completo" data-sortable="true">Huésped</th>
                                <th data-field="usuario" data-sortable="true">Usuario</th>
                                <th data-field="tipo_habitacion" data-formatter="establecimientos.valorHospedaje" data-sortable="true">Tipo habitación</th>
                                <th data-field="tarifa_noche" data-formatter="establecimientos.monedaHospedaje" data-sortable="true">Tarifa</th>
                                <th data-field="noches_programadas" data-formatter="establecimientos.numeroHospedaje" data-sortable="true">Noches programadas</th>
                                <th data-field="noches_ocupadas" data-formatter="establecimientos.numeroHospedaje" data-sortable="true">Noches ocupadas</th>
                                <th data-field="total_asignado" data-formatter="establecimientos.monedaHospedaje" data-sortable="true">Total asignado</th>
                                <th data-field="total_devengado" data-formatter="establecimientos.monedaHospedaje" data-sortable="true">Devengado</th>
                                <th data-field="estado_hospedaje" data-formatter="establecimientos.estado" data-align="center">Estado</th>
                                <th data-field="nombre_completo" data-formatter="establecimientos.acciones" data-align="center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
        </div>
    </div>
</div>

<script>
window.hospedaje = {
    iniciar: function () {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#hospedajeTable').bootstrapTable({
            url: base_url + 'index.php/Inicio/ObtenerHospedaje',
            method: 'GET',
            dataType: 'json',
            responseHandler: (response) => {
                if (Array.isArray(response)) return response;
                if (response && Array.isArray(response.data)) return response.data;
                console.error('Respuesta inválida al cargar hospedaje:', response);
                return [];
            },
            onLoadError: (status, request) => {
                console.error('Error al cargar hospedaje:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los hospedajes.', 'error');
            }
        });
    },

    estado: function (value) {
        if (Number(value) === 1) return '<span class="badge bg-success">Sí</span>';
        if (Number(value) === 2 || Number(value) === 0) return '<span class="badge bg-danger">No</span>';
        return '<span class="badge bg-secondary">Pendiente</span>';
    },

    acciones: function (value, row) {
        let botones = '<div class="btn-group btn-group-sm">';
        
        if (row.id_usuario) {
            botones += '<button class="btn btn-primary" type="button" title="Orden de Hospedaje" ' +
                'onclick="window.open(base_url + \'index.php/Usuario/generarPdfHospedaje/' + row.id_usuario + '\', \'_blank\')">' +
                '<i class="mdi mdi-file-pdf-box"></i></button>';
            
            botones += '<button class="btn btn-info" type="button" title="Check in" ' +
                'onclick="hospedaje.confirmarCheckIn(' + row.id_usuario + ', ' + JSON.stringify(row.nombre_completo || '') + ')">' +
                '<i class="mdi mdi-login-variant"></i></button>';
        }
        
        botones += '</div>';
        return botones;
    },

    confirmarCheckIn: function (idUsuario, nombreCompleto) {
        if (!idUsuario) return;

        Swal.fire({
            title: 'Registrar check in',
            text: 'Se registrará el check in del hospedaje para ' + (nombreCompleto || 'el huésped') + '.',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Observaciones (opcional)',
            inputPlaceholder: 'Ej. huésped llegó con identificación verificada',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            preConfirm: function (observaciones) {
                return $.ajax({
                    url: base_url + 'index.php/Usuario/checkInHospedaje',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_usuario: idUsuario,
                        observaciones: observaciones
                    }
                }).then(function (response) {
                    if (response.error) {
                        throw new Error(response.respuesta || 'No fue posible registrar el check in.');
                    }
                    return response;
                }).catch(function (error) {
                    Swal.showValidationMessage(error.message || 'Error en la petición.');
                });
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire('Check in registrado', 'El check in se ha registrado correctamente.', 'success');
                $('#hospedajeTable').bootstrapTable('refresh');
            }
        });
    }
};

$(function () {
    hospedaje.iniciar();
});
</script>


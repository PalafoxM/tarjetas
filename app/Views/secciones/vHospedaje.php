<?php $session = \Config\Services::session(); ?>
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administracion de hospedaje</h3>
            <p class="text-muted mb-0">Consulta hospedaje.</p>
        </div>
    </div>

    <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary mb-3">
        <i class="mdi mdi-arrow-left me-1"></i> Atras
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
                        <th data-field="nombre_completo" data-sortable="true">Huesped</th>
                        <th data-field="fecha_check_in" data-formatter="establecimientos.fecha" data-sortable="true">Fecha check in</th>
                        <th data-field="fecha_check_out" data-formatter="establecimientos.fecha" data-sortable="true">Fecha check out</th>
                        <th data-field="id_tipo_habitacion" data-formatter="establecimientos.valorHospedaje" data-sortable="true">Tipo habitacion</th>
                        <th data-field="tarifa_noche" data-formatter="establecimientos.moneda" data-sortable="true">Tarifa noche</th>
                        <th data-field="observaciones_hospedaje" data-sortable="true">Observaciones</th>
                        <th data-field="acciones" data-formatter="establecimientos.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
window.establecimientos = {
    iniciar: function () {
        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no esta disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#hospedajeTable')
            .off('click.hospedaje')
            .on('click.hospedaje', '.js-checkin', function () {
                establecimientos.confirmarCheckIn(
                    Number(this.dataset.idUsuario || 0),
                    this.dataset.nombre || ''
                );
            })
            .on('click.hospedaje', '.js-checkout', function () {
                establecimientos.confirmarCheckOut(
                    Number(this.dataset.idUsuario || 0),
                    this.dataset.nombre || ''
                );
            });

        $('#hospedajeTable').bootstrapTable({
            url: base_url + 'index.php/Inicio/ObtenerHospedaje',
            method: 'GET',
            dataType: 'json',
            rowStyle: this.estiloFila,
            responseHandler: function (response) {
                if (Array.isArray(response)) return response;
                if (response && Array.isArray(response.data)) return response.data;
                console.error('Respuesta invalida al cargar hospedaje:', response);
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar hospedaje:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los hospedajes.', 'error');
            }
        });
    },

    valorHospedaje: function (value) {
        var tipos = {
            1: 'SENCILLA',
            2: 'DOBLE',
            3: 'TRIPLE',
            4: 'CUADRUPLE',
            5: 'SUITE'
        };

        return '<span class="badge bg-secondary">' + (tipos[Number(value)] || 'N/A') + '</span>';
    },

    fecha: function (value) {
        if (!value) return '';

        var texto = String(value);
        var match = texto.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})?$/i);

        if (match) {
            return match[3] + '/' + match[2] + '/' + match[1] + ' ' + match[4] + ':' + match[5] + ':' + match[6];
        }

        var fecha = new Date(value);
        if (isNaN(fecha.getTime())) return texto;

        var pad = function (numero) {
            return String(numero).padStart(2, '0');
        };

        return pad(fecha.getDate()) + '/' + pad(fecha.getMonth() + 1) + '/' + fecha.getFullYear() +
            ' ' + pad(fecha.getHours()) + ':' + pad(fecha.getMinutes()) + ':' + pad(fecha.getSeconds());
    },

    moneda: function (value) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value) || 0);
    },

    valorEstadoHospedaje: function (row) {
        var estado = String(row.estatus_hospedaje || row.estado_hospedaje || '').toLowerCase();
        var fechaCheckIn = row.fecha_check_in || row.fecha_check_int || row.fecha_check_in_real || row.fecha_checkin || '';
        var fechaCheckOut = row.fecha_check_out || row.fecha_check_out_real || row.fecha_checkout || '';

        if (estado.indexOf('check_out') !== -1 || estado.indexOf('checkout') !== -1 || estado.indexOf('salida') !== -1 || fechaCheckOut) {
            return 'check_out';
        }

        if (estado.indexOf('check_in') !== -1 || estado.indexOf('checkin') !== -1 || Number(row.check_in) === 1 || fechaCheckIn) {
            return 'check_in';
        }

        return 'pendiente';
    },

    estiloFila: function (row) {
        var estado = establecimientos.valorEstadoHospedaje(row);
        return {
            classes: estado === 'check_in' || estado === 'check_out' ? 'table-success' : ''
        };
    },

    acciones: function (value, row) {
        var estado = establecimientos.valorEstadoHospedaje(row);
        var tieneCheckIn = estado === 'check_in' || estado === 'check_out';
        var tieneCheckOut = estado === 'check_out';
        var idUsuario = Number(row.id_usuario || 0);
        var nombre = establecimientos.escaparAtributo(row.nombre_completo || '');
        var botones = '<div class="btn-group btn-group-sm" role="group" aria-label="Acciones de hospedaje">';

        if (idUsuario) {
            botones += '<button class="btn btn-success js-checkin" type="button" title="Check in" data-id-usuario="' + idUsuario +
                '" data-nombre="' + nombre + '"' + (tieneCheckIn ? ' disabled' : '') + '>' +
                '<i class="mdi mdi-login-variant"></i></button>';

            botones += '<button class="btn btn-warning js-checkout" type="button" title="Check out" data-id-usuario="' + idUsuario +
                '" data-nombre="' + nombre + '"' + (!tieneCheckIn || tieneCheckOut ? ' disabled' : '') + '>' +
                '<i class="mdi mdi-logout-variant"></i></button>';
        }

        botones += '</div>';
        return botones;
    },

    escaparAtributo: function (valor) {
        return String(valor || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    },

    confirmarCheckIn: function (idUsuario, nombreCompleto) {
        if (!idUsuario) return;

        Swal.fire({
            title: 'Registrar check in',
            text: 'Se registrara el check in del hospedaje para ' + (nombreCompleto || 'el huesped') + '.',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Observaciones (opcional)',
            inputPlaceholder: 'Ej. huesped llego con identificacion verificada',
            showCancelButton: true,
            confirmButtonText: 'Si, registrar',
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
                    Swal.showValidationMessage(error.message || 'Error en la peticion.');
                });
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire('Check in registrado', 'El check in se ha registrado correctamente.', 'success');
                $('#hospedajeTable').bootstrapTable('refresh');
            }
        });
    },

    confirmarCheckOut: function (idUsuario, nombreCompleto) {
        if (!idUsuario) return;

        Swal.fire({
            title: 'Registrar check out',
            text: 'Se registrara el check out del hospedaje para ' + (nombreCompleto || 'el huesped') + '.',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Observaciones (opcional)',
            inputPlaceholder: 'Ej. entrega de habitacion verificada',
            showCancelButton: true,
            confirmButtonText: 'Si, registrar',
            cancelButtonText: 'Cancelar',
            preConfirm: function (observaciones) {
                return $.ajax({
                    url: base_url + 'index.php/Usuario/checkOutHospedaje',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id_usuario: idUsuario,
                        observaciones: observaciones
                    }
                }).then(function (response) {
                    if (response.error) {
                        throw new Error(response.respuesta || 'No fue posible registrar el check out.');
                    }
                    return response;
                }).catch(function (error) {
                    Swal.showValidationMessage(error.message || 'Error en la peticion.');
                });
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                Swal.fire('Check out registrado', 'El check out se ha registrado correctamente.', 'success');
                $('#hospedajeTable').bootstrapTable('refresh');
            }
        });
    }
};

$(function () {
    establecimientos.iniciar();
});
</script>

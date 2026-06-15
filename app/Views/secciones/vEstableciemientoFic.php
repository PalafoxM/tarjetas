<?php
$establecimientos = is_array($datosEstablecimiento ?? null) ? $datosEstablecimiento : [];
?>
<link rel="stylesheet" href="<?= base_url('css/fic-hotel.css') ?>?filever=<?= time() ?>">

<div class="container-fluid py-4 hotel-app" id="establecimientoApp">
    <div class="module-actions">
        <button type="button" class="btn btn-outline-primary" id="personalEstablecimiento">
            <i class="mdi mdi-account-group me-1"></i> Personal del establecimiento
        </button>
    </div>
    <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Atrás
    </a>

    <?php if (empty($establecimientos)): ?>
        <section class="module-shell">
            <div class="module-body">
                <div class="hotel-summary">
                    <div>
                        <h2 class="hotel-section-title">Sin establecimientos registrados</h2>
                        <p class="hotel-section-copy">No hay establecimientos relacionados con este proveedor.</p>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <nav class="establecimiento-tabs" aria-label="Establecimientos">
            <?php foreach ($establecimientos as $index => $establecimiento): ?>
                <button
                    type="button"
                    class="establecimiento-tab<?= $index === 0 ? ' is-active' : '' ?>"
                    data-id-establecimiento="<?= esc((string) ($establecimiento->id_establecimiento ?? ''), 'attr') ?>"
                    data-nombre="<?= esc((string) ($establecimiento->dsc_establecimiento ?? 'Establecimiento'), 'attr') ?>"
                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                    <?= esc((string) ($establecimiento->dsc_establecimiento ?? 'Establecimiento')) ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <section class="module-shell">
            <div class="module-body">
                <div class="hotel-summary">
                    <div>
                        <h2 class="hotel-section-title" id="establecimientoNombre">
                            <?= esc((string) ($establecimientos[0]->dsc_establecimiento ?? 'Establecimiento')) ?>
                        </h2>
                        <p class="hotel-section-copy">La tabla muestra únicamente las órdenes activas ligadas al establecimiento seleccionado.</p>
                    </div>
                    <div class="hotel-summary-badges">
                        <span class="badge bg-primary">Recepción</span>
                        <span class="badge bg-info">Check in controlado</span>
                    </div>
                </div>

                <div class="ventas-corte-card">
                    <div class="ventas-corte-card__header">
                        <div>
                            <span class="ventas-corte-card__kicker">Consumo acumulado</span>
                            <h3 class="ventas-corte-card__amount" id="consumoAcumulado">$0.00</h3>
                        </div>
                        <div class="ventas-corte-card__meta">
                            <span class="ventas-corte-card__count" id="totalOrdenes">0 órdenes</span>
                            <span class="ventas-corte-card__window" id="totalNoches">0 noches acumuladas</span>
                        </div>
                    </div>
                    <div class="ventas-corte-card__body">
                        <strong class="ventas-corte-card__status" id="estadoOrdenes">0 pendientes / 0 check in</strong>
                        <p class="ventas-corte-card__message">El total suma solo las noches efectivamente ocupadas. El remanente sigue reservado al usuario hasta su vencimiento.</p>
                    </div>
                </div>

                <div class="hotel-table-shell">
                    <table id="RecepcionTable"
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
        </section>
    <?php endif; ?>
</div>

<script>
window.establecimientos = {
    filas: [],

    iniciar: function () {
        var app = document.getElementById('establecimientoApp');
        var primeraPestana = app ? app.querySelector('.establecimiento-tab') : null;
        if (!app || !primeraPestana || typeof $.fn.bootstrapTable !== 'function') return;

        app.querySelectorAll('.establecimiento-tab').forEach(function (pestana) {
            pestana.addEventListener('click', function () {
                establecimientos.seleccionar(pestana);
            });
        });

        $('#RecepcionTable')
            .off('click.establecimientos')
            .on('click.establecimientos', '.js-ver-orden', function () {
                establecimientos.verOrden(Number(this.dataset.idUsuario || 0));
            })
            .on('click.establecimientos', '.js-checkin', function () {
                establecimientos.confirmarCheckIn(
                    Number(this.dataset.idUsuario || 0),
                    this.dataset.nombre || ''
                );
            });

        document.getElementById('personalEstablecimiento').addEventListener('click', function () {
            document.querySelector('.hotel-table-shell').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        $('#RecepcionTable').bootstrapTable({
            data: [],
            onRefresh: function () {
                establecimientos.cargar();
            }
        });

        this.seleccionar(primeraPestana);
    },

    seleccionar: function (pestana) {
        document.querySelectorAll('.establecimiento-tab').forEach(function (item) {
            var activo = item === pestana;
            item.classList.toggle('is-active', activo);
            item.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });

        document.getElementById('establecimientoNombre').textContent = pestana.dataset.nombre;
        this.idEstablecimiento = pestana.dataset.idEstablecimiento;
        this.cargar();
    },

    cargar: function () {
        if (!this.idEstablecimiento) return;

        $('#RecepcionTable').bootstrapTable('showLoading');
        $.ajax({
            url: base_url + 'index.php/Usuario/getRecepcion',
            type: 'GET',
            dataType: 'json',
            data: { id_establecimiento: this.idEstablecimiento }
        }).done(function (respuesta) {
            establecimientos.filas = Array.isArray(respuesta) ? respuesta.map(establecimientos.normalizar.bind(establecimientos)) : [];
            $('#RecepcionTable').bootstrapTable('load', establecimientos.filas);
            establecimientos.actualizarResumen();
        }).fail(function () {
            establecimientos.filas = [];
            $('#RecepcionTable').bootstrapTable('load', []);
            establecimientos.actualizarResumen();
            Swal.fire('Error', 'No fue posible consultar las órdenes del establecimiento.', 'error');
        }).always(function () {
            $('#RecepcionTable').bootstrapTable('hideLoading');
        });
    },

    beneficios: function (row) {
        var datos = row && row.beneficios ? row.beneficios : {};
        if (typeof datos === 'string') {
            try { datos = JSON.parse(datos); } catch (error) { datos = {}; }
        }
        return datos && typeof datos === 'object' ? datos : {};
    },

    normalizar: function (row) {
        var fila = Object.assign({}, row);
        var beneficios = this.beneficios(fila);
        var usuario = fila.usuario;

        fila.folio = fila.folio || fila.folio_entrega || beneficios.folio_hospedaje || '';
        fila.codigo_qr = fila.codigo_qr || beneficios.codigo_qr || '';
        fila.nombre_completo = fila.nombre_completo ||
            [fila.nombre, fila.primer_apellido, fila.segundo_apellido].filter(Boolean).join(' ');
        fila.usuario = usuario && typeof usuario === 'object' ? (usuario.usuario || '') : (usuario || '');

        return fila;
    },

    obtener: function (row, campos, defecto) {
        var beneficios = this.beneficios(row);
        for (var i = 0; i < campos.length; i++) {
            if (row[campos[i]] !== undefined && row[campos[i]] !== null && row[campos[i]] !== '') return row[campos[i]];
            if (beneficios[campos[i]] !== undefined && beneficios[campos[i]] !== null && beneficios[campos[i]] !== '') return beneficios[campos[i]];
        }
        return defecto;
    },

    valorHospedaje: function (value, row, index, field) {
        return establecimientos.obtener(row, [field, 'tipo_habitacion'], 'Sin definir');
    },

    numeroHospedaje: function (value, row, index, field) {
        var campos = field === 'noches_programadas' ? ['noches_programadas', 'noches'] : ['noches_ocupadas', 'noches_consumidas'];
        return Number(establecimientos.obtener(row, campos, 0)) || 0;
    },

    monedaHospedaje: function (value, row, index, field) {
        var campos = {
            tarifa_noche: ['tarifa_noche', 'monto_deposito'],
            total_asignado: ['total_asignado', 'tarifa_total_hospedaje'],
            total_devengado: ['total_devengado', 'monto_devengado']
        };
        return establecimientos.moneda(establecimientos.obtener(row, campos[field] || [field], 0));
    },

    moneda: function (valor) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(valor) || 0);
    },

    estadoValor: function (row) {
        var estado = String(this.obtener(row, ['estado_hospedaje', 'estatus_hospedaje'], '')).toLowerCase();
        if (estado.indexOf('check') !== -1 || Number(row.check_in) === 1) return 'checkin';
        if (estado.indexOf('cancel') !== -1) return 'cancelado';
        if (estado.indexOf('salida') !== -1 || estado.indexOf('checkout') !== -1) return 'checkout';
        return 'pendiente';
    },

    estado: function (value, row) {
        var estado = establecimientos.estadoValor(row);
        var etiquetas = { pendiente: 'Pendiente', checkin: 'Check in', checkout: 'Check out', cancelado: 'Cancelado' };
        return '<span class="status-badge status-hotel-' + estado + '">' + etiquetas[estado] + '</span>';
    },

    acciones: function (value, row) {
        var idUsuario = Number(row.id_usuario || 0);
        var nombre = establecimientos.escaparAtributo(row.nombre_completo || '');
        var botones = '<button type="button" class="btn btn-sm btn-outline-info js-ver-orden" data-id-usuario="' +
            idUsuario + '"><i class="mdi mdi-file-pdf-box me-1"></i> Orden</button>';

        if (idUsuario) {
            botones += '<button type="button" class="btn btn-sm btn-outline-success ms-1 js-checkin" data-id-usuario="' +
                idUsuario + '" data-nombre="' + nombre +
                '"><i class="mdi mdi-login-variant me-1"></i> Check in</button>';
        }

        return '<div class="table-actions">' + botones + '</div>';
    },

    escaparAtributo: function (valor) {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    },

    verOrden: function (idUsuario) {
        if (!idUsuario) return;
        window.open(base_url + 'index.php/Usuario/generarPdfHospedaje/' + idUsuario, '_blank');
    },

    confirmarCheckIn: function (idUsuario, nombre_completo) {
        if (!idUsuario) return;

        Swal.fire({
            title: 'Registrar check in',
            text: 'Se registrará el check in del hospedaje para el huésped ' + nombre_completo + '. ¿Deseas continuar?',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Observaciones (opcional)',
            inputPlaceholder: 'Ej. huésped llegó con identificación verificada',
            inputAttributes: {
                'aria-label': 'Observaciones para el check in'
            },
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
                establecimientos.cargar();
            }
        });
    },

    actualizarResumen: function () {
        var resumen = this.filas.reduce(function (total, row) {
            var estado = establecimientos.estadoValor(row);
            var noches = Number(establecimientos.obtener(row, ['noches_ocupadas', 'noches_consumidas'], 0)) || 0;
            var devengado = Number(establecimientos.obtener(row, ['total_devengado', 'monto_devengado'], 0)) || 0;
            total.noches += noches;
            total.devengado += devengado;
            total[estado] += 1;
            return total;
        }, { noches: 0, devengado: 0, pendiente: 0, checkin: 0, checkout: 0, cancelado: 0 });

        document.getElementById('consumoAcumulado').textContent = this.moneda(resumen.devengado);
        document.getElementById('totalOrdenes').textContent = this.filas.length + (this.filas.length === 1 ? ' orden' : ' órdenes');
        document.getElementById('totalNoches').textContent = resumen.noches + (resumen.noches === 1 ? ' noche acumulada' : ' noches acumuladas');
        document.getElementById('estadoOrdenes').textContent = resumen.pendiente + ' pendientes / ' + resumen.checkin + ' check in';
    }
};

$(function () {
    establecimientos.iniciar();
});
</script>

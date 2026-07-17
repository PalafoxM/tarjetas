<?php
$establecimientos = is_array($datosEstablecimiento ?? null) ? $datosEstablecimiento : [];
$modoEstablecimientosFic = !empty($modoEstablecimientosFic);
$esAdministradorEstablecimientosFic = !empty($esAdministradorEstablecimientosFic);
$soloConsultaEstablecimientosFic = !empty($soloConsultaEstablecimientosFic);
$altaProveedorUrl = $altaProveedorUrl ?? base_url('index.php/Inicio/AltaUsuario?modo=proveedor');
$usuariosUrl = $usuariosUrl ?? base_url('index.php/Inicio/Usuarios');
?>
<?php if ($modoEstablecimientosFic): ?>
<div class="container-fluid py-4" id="establecimientos-fic-root">
    <div class="row mb-3 g-3 align-items-center">
        <div class="col-lg-8">
            <h3 class="mb-1 text-white">Establecimientos FIC</h3>
            <p class="text-muted mb-0">Homologación institucional para dar de alta proveedores y consultar los establecimientos relacionados dentro de este workspace.</p>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                <a class="btn btn-outline-secondary" href="<?= base_url('index.php/Inicio') ?>">
                    <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
                </a>
                <a class="btn btn-outline-info" href="<?= esc($usuariosUrl, 'attr') ?>">
                    <i class="mdi mdi-account-multiple-outline me-1"></i> Usuarios institucionales
                </a>
                <?php if ($esAdministradorEstablecimientosFic): ?>
                    <a class="btn btn-primary" href="<?= esc($altaProveedorUrl, 'attr') ?>">
                        <i class="mdi mdi-account-plus-outline me-1"></i> Agregar proveedor
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="alert alert-info mb-3" role="alert">
        Primero se registra el proveedor como usuario y despues se liga o administra su padron de establecimientos desde este modulo.
    </div>

    <?php if ($soloConsultaEstablecimientosFic): ?>
        <div class="alert alert-secondary mb-3" role="alert">
            Tu perfil está en modo consulta dentro de esta vista. Solo un perfil con permisos de administración puede dar de alta proveedores en este módulo.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Establecimiento</th>
                            <th>Tipo</th>
                            <th>Proveedor</th>
                            <th>Padron</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($establecimientos)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay establecimientos visibles para mostrar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($establecimientos as $establecimiento): ?>
                                <tr>
                                    <td><?= esc((string) ($establecimiento->id_establecimiento ?? '')) ?></td>
                                    <td><?= esc((string) ($establecimiento->dsc_establecimiento ?? '')) ?></td>
                                    <td><?= esc((string) ($establecimiento->dsc_tipo ?? '')) ?></td>
                                    <td><?= esc((string) ($establecimiento->dsc_proveedor ?? 'Sin proveedor')) ?></td>
                                    <td><?= esc((string) ($establecimiento->no_proveedor ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php return; ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('css/fic-hotel.css') ?>?filever=<?= time() ?>">

<div class="container-fluid py-4 hotel-app" id="establecimientoApp"
     data-id-establecimiento="<?= esc((string) ($establecimientos[0]->id_establecimiento ?? ''), 'attr') ?>"
     data-nombre="<?= esc((string) ($establecimientos[0]->dsc_establecimiento ?? 'Establecimiento'), 'attr') ?>">
    <div class="module-actions">
        <button
            type="button"
            class="btn btn-outline-primary"
            id="personalEstablecimiento"
            data-solicitud-personal-url="<?= esc(base_url('index.php/Inicio') . '?solicitud_personal=1&id_establecimiento=' . (int) ($establecimientos[0]->id_establecimiento ?? 0), 'attr') ?>">
            <i class="mdi mdi-account-group me-1"></i> Personal del establecimiento
        </button>
        <button
            type="button"
            class="btn btn-outline-info"
            id="descargar_reporte_ventas_hotel"
            data-download-base-url="<?= esc(base_url('index.php/Inicio/exportarReporteHospedajePdf') . '?download=1', 'attr') ?>">
            <i class="mdi mdi-file-pdf-box me-1"></i> Descargar reporte
        </button>
    </div>
   <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary mb-3">
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
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($establecimientos as $index => $establecimiento): ?>
                <?php $idEst = (int) ($establecimiento->id_establecimiento ?? 0); ?>
                <button
                    type="button"
                    class="btn btn-outline-info establecimiento-tab<?= $index === 0 ? ' is-active active' : '' ?>"
                    data-id-establecimiento="<?= esc((string) $idEst, 'attr') ?>"
                    data-nombre="<?= esc((string) ($establecimiento->dsc_establecimiento ?? 'Establecimiento'), 'attr') ?>"
                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                    <?= esc((string) ($establecimiento->dsc_establecimiento ?? 'Establecimiento')) ?>
                </button>
            <?php endforeach; ?>
        </div>

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

                <div class="ventas-corte-card">
                    <div class="ventas-corte-card__header">
                        <div>
                            <span class="ventas-corte-card__kicker">Facturación</span>
                            <h3 class="ventas-corte-card__amount">Formatos FIC</h3>
                        </div>
                        <div class="ventas-corte-card__meta">
                            <span class="ventas-corte-card__count">XML + PDF</span>
                            <span class="ventas-corte-card__window">Generación de formatos</span>
                        </div>
                    </div>
                    <div class="ventas-corte-card__body">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <label class="btn btn-outline-info mb-0" for="hotel_factura_xml">
                                <i class="mdi mdi-file-xml-box me-1"></i> SUBIR XML
                                <input id="hotel_factura_xml" type="file" accept="application/xml,text/xml,.xml" hidden>
                            </label>
                            <label class="btn btn-outline-info mb-0" for="hotel_factura_pdf">
                                <i class="mdi mdi-file-pdf-box me-1"></i> SUBIR PDF
                                <input id="hotel_factura_pdf" type="file" accept="application/pdf,.pdf" hidden>
                            </label>
                            <button type="button" class="btn btn-primary" id="hotel_enviar_factura" disabled>
                                <i class="mdi mdi-send-check-outline me-1"></i> Enviar factura
                            </button>
                            <a class="btn btn-outline-light hotel-formato-link" id="hotel_formato_encabezado" target="_blank" rel="noopener">
                                <i class="mdi mdi-file-document-outline me-1"></i> Encabezado
                            </a>
                            <a class="btn btn-outline-light hotel-formato-link" id="hotel_formato_pt" target="_blank" rel="noopener">
                                <i class="mdi mdi-file-pdf-box me-1"></i> Formato PT
                            </a>
                            <a class="btn btn-outline-light hotel-formato-link" id="hotel_formato_liberacion" target="_blank" rel="noopener">
                                <i class="mdi mdi-file-check-outline me-1"></i> Liberación
                            </a>
                        </div>
                        <p class="ventas-corte-card__message mt-2 mb-0">Selecciona el XML y PDF de la factura para habilitar el envío. Después podrás generar los formatos del establecimiento seleccionado.</p>
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
                           >
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
        var tabla = document.getElementById('RecepcionTable');
        if (!app || !tabla || typeof $.fn.bootstrapTable !== 'function') return;

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

        var botonPersonal = document.getElementById('personalEstablecimiento');
        if (botonPersonal) {
            botonPersonal.addEventListener('click', function () {
                var urlSolicitudPersonal = String(this.dataset.solicitudPersonalUrl || '').trim();
                if (urlSolicitudPersonal) {
                    window.location.href = urlSolicitudPersonal;
                }
            });
        }

        var botonReporte = document.getElementById('descargar_reporte_ventas_hotel');
        if (botonReporte) {
            botonReporte.addEventListener('click', function () {
                var baseUrl = String(this.dataset.downloadBaseUrl || '').trim();
                if (!baseUrl || !establecimientos.idEstablecimiento) {
                    return;
                }

                var joiner = baseUrl.indexOf('?') === -1 ? '?' : '&';
                var downloadUrl = baseUrl + joiner + 'id_establecimiento=' + encodeURIComponent(establecimientos.idEstablecimiento);

                if (window.cajeros && typeof window.cajeros.descargarArchivoSinNavegar === 'function') {
                    window.cajeros.descargarArchivoSinNavegar(downloadUrl);
                    return;
                }

                window.location.href = downloadUrl;
            });
        }

        var xmlInput = document.getElementById('hotel_factura_xml');
        var pdfInput = document.getElementById('hotel_factura_pdf');
        var enviarFactura = document.getElementById('hotel_enviar_factura');
        if (xmlInput) {
            xmlInput.addEventListener('change', function () {
                establecimientos.actualizarFacturaControls();
            });
        }
        if (pdfInput) {
            pdfInput.addEventListener('change', function () {
                establecimientos.actualizarFacturaControls();
            });
        }
        if (enviarFactura) {
            enviarFactura.addEventListener('click', function () {
                establecimientos.enviarFactura();
            });
        }

        $('#RecepcionTable').bootstrapTable({
            data: [],
            formatNoMatches: function () {
                return 'Este hotel aún no tiene huéspedes asignados.';
            }
        });

        if (primeraPestana) {
            this.seleccionar(primeraPestana);
            return;
        }

        this.idEstablecimiento = app.dataset.idEstablecimiento || '';
        var nombreEstablecimiento = app.dataset.nombre || '';
        if (nombreEstablecimiento && document.getElementById('establecimientoNombre')) {
            document.getElementById('establecimientoNombre').textContent = nombreEstablecimiento;
        }
        this.cargar();
    },

    seleccionar: function (pestana) {
        document.querySelectorAll('.establecimiento-tab').forEach(function (item) {
            var activo = item === pestana;
            item.classList.toggle('is-active', activo);
            item.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });

        document.getElementById('establecimientoNombre').textContent = pestana.dataset.nombre;
        this.idEstablecimiento = pestana.dataset.idEstablecimiento;
        this.actualizarFormatoUrls();
        this.actualizarFacturaControls();
        this.cargar();
    },

    fileMatches: function (file, extension, mimeTypes) {
        if (!file) return false;
        var name = String(file.name || '').toLowerCase();
        var type = String(file.type || '').toLowerCase();
        return name.endsWith(extension) || mimeTypes.indexOf(type) !== -1;
    },

    facturaControls: function () {
        return {
            xml: document.getElementById('hotel_factura_xml'),
            pdf: document.getElementById('hotel_factura_pdf'),
            button: document.getElementById('hotel_enviar_factura')
        };
    },

    actualizarFacturaControls: function () {
        var controls = this.facturaControls();
        if (!controls.button) return;
        var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
        var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;
        var validXml = this.fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']);
        var validPdf = this.fileMatches(pdfFile, '.pdf', ['application/pdf']);
        controls.button.disabled = !(this.idEstablecimiento && validXml && validPdf);
    },

    actualizarFormatoUrls: function () {
        var id = encodeURIComponent(this.idEstablecimiento || '');
        var links = {
            hotel_formato_encabezado: base_url + 'index.php/Inicio/pdfProveedorEncabezadoFactura/' + id,
            hotel_formato_pt: base_url + 'index.php/Inicio/pdfProveedorFormatoPT/' + id,
            hotel_formato_liberacion: base_url + 'index.php/Inicio/pdfProveedorLiberacionPago/' + id
        };

        Object.keys(links).forEach(function (key) {
            var link = document.getElementById(key);
            if (!link) return;
            if (id) {
                link.href = links[key];
                link.classList.remove('disabled');
            } else {
                link.removeAttribute('href');
                link.classList.add('disabled');
            }
        });
    },

    enviarFactura: function () {
        var controls = this.facturaControls();
        var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
        var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;

        if (!this.idEstablecimiento || !this.fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']) || !this.fileMatches(pdfFile, '.pdf', ['application/pdf'])) {
            Swal.fire('Atencion', 'Selecciona un XML y un PDF validos.', 'warning');
            return;
        }

        var data = new FormData();
        data.append('id_establecimiento', this.idEstablecimiento);
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
            url: base_url + 'index.php/Inicio/enviarFacturaProveedor',
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
            establecimientos.actualizarFacturaControls();
            Swal.fire('Correcto', response.respuesta || 'Factura enviada correctamente.', 'success');
        }).fail(function (request) {
            var response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible enviar la factura.', 'error');
        });
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
        window.open(base_url + 'index.php/Usuario/generarPdfOrden/' + idUsuario, '_blank');
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
                        id_establecimiento: establecimientos.idEstablecimiento,
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

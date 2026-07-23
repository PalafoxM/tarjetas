<?php
$establecimientos = is_array($datosEstablecimiento ?? null) ? $datosEstablecimiento : [];
$modoEstablecimientosFic = !empty($modoEstablecimientosFic);
$esAdministradorEstablecimientosFic = !empty($esAdministradorEstablecimientosFic);
$soloConsultaEstablecimientosFic = !empty($soloConsultaEstablecimientosFic);
$altaProveedorUrl = $altaProveedorUrl ?? base_url('index.php/Inicio/AltaUsuario?modo=proveedor');
$usuariosUrl = $usuariosUrl ?? base_url('index.php/Inicio/Usuarios');

$idEstablecimientoFactura = 0;
$nombreEstablecimiento = '';
if (!empty($establecimientos)) {
    $primerEstablecimiento = is_object($establecimientos[0]) ? get_object_vars($establecimientos[0]) : $establecimientos[0];
    $idEstablecimientoFactura = (int) ($primerEstablecimiento['id_establecimiento'] ?? 0);
    $nombreEstablecimiento = (string) ($primerEstablecimiento['dsc_establecimiento'] ?? 'Establecimiento');
}
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

    <?php
        $proveedoresLigadosTab = array_values(array_map(static function ($item) {
            return is_object($item) ? get_object_vars($item) : (array) $item;
        }, is_array($proveedoresLigados ?? null) ? $proveedoresLigados : []));
        $fullName = static function (array $row): string {
            $nombreCompleto = trim(implode(' ', array_filter([
                trim((string) ($row['nombre'] ?? '')),
                trim((string) ($row['primer_apellido'] ?? '')),
                trim((string) ($row['segundo_apellido'] ?? '')),
            ])));

            return $nombreCompleto !== '' ? $nombreCompleto : trim((string) ($row['usuario'] ?? 'Sin nombre'));
        };
    ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="establecimientos-tab" data-bs-toggle="tab" data-bs-target="#establecimientos-pane" type="button" role="tab" aria-controls="establecimientos-pane" aria-selected="true">
                        Establecimientos participantes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proveedores-tab" data-bs-toggle="tab" data-bs-target="#proveedores-pane" type="button" role="tab" aria-controls="proveedores-pane" aria-selected="false">
                        Proveedores y usuarios ligados
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="establecimientos-pane" role="tabpanel" aria-labelledby="establecimientos-tab">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Establecimiento</th>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th>Padrón</th>
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

                <div class="tab-pane fade" id="proveedores-pane" role="tabpanel" aria-labelledby="proveedores-tab">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Padrón</th>
                                    <th>Proveedor</th>
                                    <th>Usuario</th>
                                    <th>Tipo usuario</th>
                                    <th>Tipo establecimiento</th>
                                    <th>RFC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($proveedoresLigadosTab)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No hay usuarios ligados para mostrar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($proveedoresLigadosTab as $item): ?>
                                        <tr>
                                            <td><?= esc((string) ($item['dsc_establecimiento'] ?? '')) ?></td>
                                            <td><?= esc((string) ($item['no_proveedor'] ?? '')) ?></td>
                                            <td><?= esc((string) ($item['razon_social'] ?? 'Sin proveedor')) ?></td>
                                            <td>
                                                <div class="fw-semibold"><?= esc($fullName($item)) ?></div>
                                                <div class="text-muted small"><?= esc((string) ($item['usuario'] ?? '')) ?></div>
                                            </td>
                                            <td><?= esc((string) ($item['tipo_usuario_label'] ?? 'Usuario ligado')) ?></td>
                                            <td><?= esc((string) ($item['dsc_tipo'] ?? '')) ?></td>
                                            <td><?= esc((string) ($item['rfc'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php return; ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('css/fic-hotel.css') ?>?filever=<?= time() ?>">

<div class="container-fluid py-4 hotel-app" id="establecimientoApp"
     data-id-establecimiento="<?= esc((string) ($idEstablecimientoFactura), 'attr') ?>"
     data-nombre="<?= esc((string) ($nombreEstablecimiento), 'attr') ?>">
    
   
    <div class="module-actions d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Atrás
        </a>
        <button
            type="button"
            class="btn btn-outline-primary"
            id="personalEstablecimiento"
            data-solicitud-personal-url="<?= esc(base_url('index.php/Inicio') . '?solicitud_personal=1&id_establecimiento=' . (int) ($idEstablecimientoFactura), 'attr') ?>">
            <i class="mdi mdi-account-group me-1"></i> Personal del establecimiento
        </button>
        <label class="btn btn-outline-info mb-0" for="hotel_factura_xml">
            <i class="mdi mdi-file-xml-box me-1"></i> Subir XML
            <input id="hotel_factura_xml" type="file" accept="application/xml,text/xml,.xml" hidden>
        </label>
        <label class="btn btn-outline-info mb-0" for="hotel_factura_pdf">
            <i class="mdi mdi-file-pdf-box me-1"></i> Subir PDF
            <input id="hotel_factura_pdf" type="file" accept="application/pdf,.pdf" hidden>
        </label>
        <button
            type="button"
            class="btn btn-primary"
            id="hotel_enviar_factura"
            data-id-establecimiento="<?= esc((string) ($idEstablecimientoFactura), 'attr') ?>"
            disabled>
            <i class="mdi mdi-send-check-outline me-1"></i> Cargar documentos
        </button>
        <button
            type="button"
            class="btn btn-outline-info"
            id="descargar_reporte_ventas_hotel"
            data-download-base-url="<?= esc(base_url('index.php/Inicio/exportarReporteHospedajePdf') . '?download=1', 'attr') ?>"
            data-id-establecimiento="<?= esc((string) ($idEstablecimientoFactura), 'attr') ?>">
            <i class="mdi mdi-file-pdf-box me-1"></i> Descargar reporte
        </button>
    </div>


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
        <section class="module-shell">
            <div class="module-body">
                <div class="hotel-summary">
                    <div>
                        <h2 class="hotel-section-title" id="establecimientoNombre">
                            <?= esc((string) ($nombreEstablecimiento)) ?>
                        </h2>
                        <p class="hotel-section-copy">La tabla muestra únicamente las órdenes activas ligadas al establecimiento seleccionado.</p>
                    </div>
                    <div class="hotel-summary-badges">
                        <span class="badge bg-primary">Recepción</span>
                        <span class="badge bg-info">Check in controlado</span>
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
                           data-id-establecimiento="<?= esc((string) ($idEstablecimientoFactura), 'attr') ?>">
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
        var tabla = document.getElementById('RecepcionTable');
        if (!app || !tabla || typeof $.fn.bootstrapTable !== 'function') return;

       
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
                var idEstablecimiento = String(this.dataset.idEstablecimiento || '').trim();

                if (!baseUrl || !idEstablecimiento) {
                    return;
                }

                var joiner = baseUrl.indexOf('?') === -1 ? '?' : '&';
                var downloadUrl = baseUrl + joiner + 'id_establecimiento=' + encodeURIComponent(idEstablecimiento);

                if (window.cajeros && typeof window.cajeros.descargarArchivoSinNavegar === 'function') {
                    window.cajeros.descargarArchivoSinNavegar(downloadUrl);
                    return;
                }

                window.location.href = downloadUrl;
            });
        }

        this.idEstablecimiento = app.dataset.idEstablecimiento || '';
        var nombreEstablecimiento = app.dataset.nombre || '';
        if (nombreEstablecimiento && document.getElementById('establecimientoNombre')) {
            document.getElementById('establecimientoNombre').textContent = nombreEstablecimiento;
        }

        var url = base_url + 'index.php/Usuario/getRecepcion';
        if (this.idEstablecimiento) {
            url += '?id_establecimiento=' + encodeURIComponent(this.idEstablecimiento);
        }

        $('#RecepcionTable').bootstrapTable({
            url: url,
            method: 'GET',
            dataType: 'json',
            formatNoMatches: function () {
                return 'Este hotel aún no tiene huéspedes asignados.';
            },
            responseHandler: function (response) {
                if (Array.isArray(response)) {
                    establecimientos.filas = response.map(establecimientos.normalizar.bind(establecimientos));
                    establecimientos.actualizarResumen();
                    return establecimientos.filas;
                }
                if (response && Array.isArray(response.data)) {
                    establecimientos.filas = response.data.map(establecimientos.normalizar.bind(establecimientos));
                    establecimientos.actualizarResumen();
                    return establecimientos.filas;
                }
                console.error('Respuesta invalida al cargar RecepcionTable:', response);
                establecimientos.filas = [];
                establecimientos.actualizarResumen();
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar RecepcionTable:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar las órdenes del establecimiento.', 'error');
                establecimientos.filas = [];
                establecimientos.actualizarResumen();
            }
        });

        this.actualizarFacturaControls();
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
        var idEstablecimiento = String(controls.button.dataset.idEstablecimiento || '').trim();
        var validXml = this.fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']);
        var validPdf = this.fileMatches(pdfFile, '.pdf', ['application/pdf']);
        controls.button.disabled = !(idEstablecimiento && validXml && validPdf);
    },

    enviarFactura: function () {
        var controls = this.facturaControls();
        var xmlFile = controls.xml && controls.xml.files ? controls.xml.files[0] : null;
        var pdfFile = controls.pdf && controls.pdf.files ? controls.pdf.files[0] : null;
        var idEstablecimiento = controls.button ? String(controls.button.dataset.idEstablecimiento || '').trim() : '';

        if (!idEstablecimiento) {
            Swal.fire('Atencion', 'No se encontro el establecimiento de la sesion.', 'warning');
            return;
        }

        if (!this.fileMatches(xmlFile, '.xml', ['application/xml', 'text/xml']) || !this.fileMatches(pdfFile, '.pdf', ['application/pdf'])) {
            Swal.fire('Atencion', 'Selecciona un XML y un PDF validos.', 'warning');
            return;
        }

        var data = new FormData();
        data.append('id_establecimiento', idEstablecimiento);
        data.append('xml', xmlFile);
        data.append('pdf', pdfFile);

        controls.button.disabled = true;
        Swal.fire({
            title: 'Subiendo archivos',
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
                Swal.fire('Atencion', response && response.respuesta ? response.respuesta : 'No fue posible guardar la factura.', 'warning');
                establecimientos.actualizarFacturaControls();
                return;
            }

            if (controls.xml) controls.xml.value = '';
            if (controls.pdf) controls.pdf.value = '';
            establecimientos.actualizarFacturaControls();
            Swal.fire('Correcto', response.respuesta || 'Factura guardada correctamente.', 'success');
        }).fail(function (request) {
            var response = request.responseJSON || {};
            Swal.fire('Error', response.respuesta || 'No fue posible guardar la factura.', 'error');
            establecimientos.actualizarFacturaControls();
        });
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

    beneficios: function (row) {
        var datos = row && row.beneficios ? row.beneficios : {};
        if (typeof datos === 'string') {
            try { datos = JSON.parse(datos); } catch (error) { datos = {}; }
        }
        return datos && typeof datos === 'object' ? datos : {};
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
                $('#RecepcionTable').bootstrapTable('refresh');
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

        var consumoAcumulado = document.getElementById('consumoAcumulado');
        var totalOrdenes = document.getElementById('totalOrdenes');
        var totalNoches = document.getElementById('totalNoches');
        var estadoOrdenes = document.getElementById('estadoOrdenes');

        if (consumoAcumulado) {
            consumoAcumulado.textContent = this.moneda(resumen.devengado);
        }
        if (totalOrdenes) {
            totalOrdenes.textContent = this.filas.length + (this.filas.length === 1 ? ' orden' : ' órdenes');
        }
        if (totalNoches) {
            totalNoches.textContent = resumen.noches + (resumen.noches === 1 ? ' noche acumulada' : ' noches acumuladas');
        }
        if (estadoOrdenes) {
            estadoOrdenes.textContent = resumen.pendiente + ' pendientes / ' + resumen.checkin + ' check in';
        }
    }
};

$(function () {
    establecimientos.iniciar();
});
</script>
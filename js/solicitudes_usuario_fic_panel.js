var solicitudesUsuarioFicPanel = (function () {
    var S3_PUBLIC_BASE_URL = 'https://sectur-audiovisuales-509634423753-us-east-1-an.s3.amazonaws.com/';
    var state = {
        root: null,
        qrTable: null,
        folioTable: null,
        qrListUrl: '',
        qrFileUrl: '',
        qrApproveUrl: '',
        qrRejectUrl: '',
        folioListUrl: '',
        folioDetailUrl: '',
        folioCancelUrl: '',
        previewModal: null,
        previewModalEl: null
    };

    function esc(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatFecha(value) {
        if (!value) return '';
        if (window.saeg && saeg.principal && typeof saeg.principal.fecha === 'function') {
            return saeg.principal.fecha(value);
        }
        return value;
    }

    function badgeEstado(value) {
        var estado = String(value || '').toLowerCase();
        if (estado === 'pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        if (estado === 'aprobada') return '<span class="badge bg-success">Aprobada</span>';
        if (estado === 'rechazada') return '<span class="badge bg-danger">Rechazada</span>';
        if (estado === 'cancelada') return '<span class="badge bg-secondary">Cancelada</span>';
        return '<span class="badge bg-secondary">' + esc(estado || 'Sin definir') + '</span>';
    }

    function construirUrlArchivo(config) {
        if (!config) {
            return '';
        }

        var directo = String(config.url || '').trim();
        if (directo !== '') {
            return directo;
        }

        var idUsuario = String(config.idUsuario || '').trim();
        var campo = String(config.field || '').trim();
        if (!state.qrFileUrl || idUsuario === '' || campo === '') {
            return '';
        }

        return state.qrFileUrl
            + '?id_usuario=' + encodeURIComponent(idUsuario)
            + '&campo=' + encodeURIComponent(campo);
    }

    function getExtension(url) {
        var cleanUrl = String(url || '').split('?')[0].split('#')[0];
        var parts = cleanUrl.split('.');
        return parts.length > 1 ? String(parts.pop() || '').toLowerCase() : '';
    }

    function getPreviewBody(url, label, fileName, field) {
        var ext = getExtension(fileName) || getExtension(url);
        var safeUrl = esc(url);
        var safeLabel = esc(label || 'Archivo');
        var esFirma = String(field || '').toLowerCase() === 'firma';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].indexOf(ext) !== -1) {
            if (esFirma) {
                return '<div class="text-center bg-white rounded border border-secondary p-4"><img src="' + safeUrl + '" alt="' + safeLabel + '" class="img-fluid" style="max-height:70vh;object-fit:contain;"></div>';
            }
            return '<div class="text-center"><img src="' + safeUrl + '" alt="' + safeLabel + '" class="img-fluid rounded border border-secondary" style="max-height:70vh;object-fit:contain;"></div>';
        }

        if (ext === 'pdf') {
            return '<iframe src="' + safeUrl + '" title="' + safeLabel + '" style="width:100%;height:70vh;border:0;border-radius:12px;"></iframe>';
        }

        return '<div class="alert alert-info mb-0">No hay vista previa embebida para este tipo de archivo. Usa el botón <strong>Abrir en nueva pestaña</strong>.</div>';
    }

    function openPreviewModal(config) {
        var url = construirUrlArchivo(config || {});
        if (!url) {
            Swal.fire('Atención', 'No hay archivo disponible para previsualizar.', 'warning');
            return;
        }

        if (!state.previewModal) {
            var modalEl = document.getElementById('modalPreviewArchivoQrFic');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                state.previewModalEl = modalEl;
                state.previewModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            }
        }

        if (!state.previewModal) {
            Swal.fire('Atención', 'No fue posible abrir el visor de archivos.', 'warning');
            return;
        }

        $('#modalPreviewArchivoQrFicTitle').text(config.title || 'Previsualización de archivo');
        $('#modalPreviewArchivoQrFicSubtitle').text(config.subtitle || '');
        $('#modalPreviewArchivoQrFicBody').html(getPreviewBody(url, config.title || 'Archivo', config.fileName || '', config.field || ''));
        $('#modalPreviewArchivoQrFicOpen').attr('href', url);
        state.previewModal.show();
    }

    function refreshTable($table) {
        if (!$table || !$table.length || !$table.data('bootstrap.table')) {
            return;
        }

        $table.bootstrapTable('refresh', {
            pageNumber: 1,
            silent: false
        });
    }

    function getCsrfPayload() {
        var payload = {};
        $('input[type="hidden"][name]').each(function () {
            var name = this.name || '';
            if (name && name.toLowerCase().indexOf('csrf') !== -1) {
                payload[name] = this.value;
            }
        });
        return payload;
    }

    function loadFolioDetail(idSolicitud, callback) {
        if (!state.folioDetailUrl || !idSolicitud) return;
        $.getJSON(state.folioDetailUrl, { id_solicitud_usuario: idSolicitud })
            .done(function (response) {
                if (!response || response.ok !== true || !response.data) {
                    Swal.fire('Atención', response && response.message ? response.message : 'No fue posible cargar la solicitud.', 'warning');
                    return;
                }
                if (typeof callback === 'function') {
                    callback(response.data);
                }
            })
            .fail(function () {
                Swal.fire('Error', 'No fue posible cargar la solicitud.', 'error');
            });
    }

    window.queryParamsSolicitudesActivacionQrFic = function (params) {
        return $.extend({
            limit: params.limit || params.pageSize || 10,
            offset: params.offset || 0,
            search: params.searchText || '',
            sort: params.sort || '',
            order: params.order || '',
            estatus_activacion: $('#filtroSolicitudQrFicEstatus').val() || ''
        }, getCsrfPayload());
    };

    window.responseHandlerSolicitudesActivacionQrFic = function (response) {
        if (response && (response.ok === true || response.success === true) && Array.isArray(response.rows)) {
            return {
                total: Number(response.total || 0),
                rows: response.rows
            };
        }
        return { total: 0, rows: [] };
    };

    function initQrTable() {
        var table = state.qrTable;
        if (!table || !table.length || typeof $.fn.bootstrapTable !== 'function' || !state.qrListUrl) {
            return;
        }

        if (table.data('bootstrap.table')) {
            table.bootstrapTable('destroy');
        }

        table.bootstrapTable({
            url: state.qrListUrl,
            method: 'post',
            locale: 'es-MX',
            search: true,
            searchAlign: 'left',
            pagination: true,
            sidePagination: 'server',
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            queryParams: function (params) {
                return $.extend({
                    limit: params.limit || params.pageSize || 10,
                    offset: params.offset || 0,
                    search: params.searchText || '',
                    sort: params.sort || '',
                    order: params.order || '',
                    estatus_activacion: $('#filtroSolicitudQrFicEstatus').val() || ''
                }, getCsrfPayload());
            },
            responseHandler: function (response) {
                if (response && (response.ok === true || response.success === true) && Array.isArray(response.rows)) {
                    return {
                        total: Number(response.total || 0),
                        rows: response.rows
                    };
                }
                return { total: 0, rows: [] };
            },
            onLoadSuccess: function (data) {
                $('#solicitudesQrPlaceholder').toggleClass('d-none', !((data && data.total) > 0));
            },
            onLoadError: function () {
                Swal.fire('Error', 'No fue posible cargar las solicitudes de activación QR.', 'error');
            }
        });
    }

    function initFolioTable() {
        var table = state.folioTable;
        if (!table || !table.length || typeof $.fn.bootstrapTable !== 'function' || !state.folioListUrl) {
            return;
        }

        if (table.data('bootstrap.table')) {
            table.bootstrapTable('destroy');
        }

        table.bootstrapTable({
            url: state.folioListUrl,
            method: 'get',
            locale: 'es-MX',
            search: true,
            searchAlign: 'left',
            pagination: true,
            sidePagination: 'server',
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            queryParams: function (params) {
                return {
                    limit: params.limit || params.pageSize || 10,
                    offset: params.offset || 0,
                    search: params.searchText || '',
                    sort: params.sort || '',
                    order: params.order || '',
                    estatus: $('#filtroSolicitudFolioFicEstatus').val() || ''
                };
            },
            responseHandler: function (response) {
                if (response && response.ok === true && Array.isArray(response.rows)) {
                    return {
                        total: Number(response.total || 0),
                        rows: response.rows
                    };
                }
                if (response && Array.isArray(response.data)) {
                    return {
                        total: Number(response.total || response.data.length),
                        rows: response.data
                    };
                }
                return { total: 0, rows: [] };
            },
            onLoadError: function () {
                Swal.fire('Error', 'No fue posible cargar las solicitudes de folio.', 'error');
            }
        });
    }

    window.solicitudesQrFicArchivoFormatter = function (value, row, index) {
        var archivo = String(value || '').trim();
        if (!archivo) {
            return '<span class="text-muted">Sin archivo</span>';
        }

        return '<button type="button" class="btn btn-outline-info btn-sm js-qr-fic-preview" data-field="' + esc(this.field || '') + '" data-title="' + esc(this.title || 'Archivo') + '" data-id-usuario="' + esc(row && row.id_usuario ? row.id_usuario : '') + '" data-archivo="' + esc(archivo) + '">Ver</button>';
    };

    window.solicitudesQrFicExpedienteFormatter = function (value) {
        var completo = value === true || value === 1 || value === '1';
        return completo
            ? '<span class="badge bg-success">Completo</span>'
            : '<span class="badge bg-warning text-dark">Incompleto</span>';
    };

    window.solicitudesQrFicEstadoFormatter = function (value, row) {
        var estado = Number(value !== undefined && value !== null ? value : (row && row.activo_qr ? row.activo_qr : 0));
        return estado === 1
            ? '<span class="badge bg-success">QR activo</span>'
            : '<span class="badge bg-warning text-dark">Pendiente</span>';
    };

    window.solicitudesQrFicFechaFormatter = function (value) {
        return formatFecha(value);
    };

    window.solicitudesQrFicAccionesFormatter = function (value, row) {
        if (!row) return '';

        var activo = Number(row.activo_qr || row.qr_activo || 0) === 1;
        var buttons = [];
        buttons.push('<button type="button" class="btn btn-outline-info btn-sm js-qr-fic-preview" data-field="qr" data-title="QR" data-id-usuario="' + esc(row.id_usuario || '') + '" data-archivo="' + esc(row.qr || '') + '" title="Ver QR"><i class="mdi mdi-eye"></i></button>');

        if (!activo) {
            buttons.push('<button type="button" class="btn btn-outline-success btn-sm js-qr-fic-activar" data-id-usuario="' + esc(row.id_usuario || '') + '" title="Aceptar / activar QR"><i class="mdi mdi-check"></i></button>');
            buttons.push('<button type="button" class="btn btn-outline-danger btn-sm js-qr-fic-rechazar" data-id-usuario="' + esc(row.id_usuario || '') + '" title="Rechazar solicitud"><i class="mdi mdi-times"></i></button>');
        } else {
            buttons.push('<span class="badge bg-success align-self-center">QR activo</span>');
        }

        return '<div class="usuario-actions">' + buttons.join('') + '</div>';
    };

    window.solicitudesFicPanelEstadoFormatter = function (value) {
        return badgeEstado(value);
    };

    window.solicitudesFicPanelFechaFormatter = function (value) {
        return formatFecha(value);
    };

    window.solicitudesFicPanelUsuarioFormatter = function (value) {
        var usuario = String(value || '').trim();
        return usuario !== '' ? esc(usuario) : '<span class="text-muted">Por asignar</span>';
    };

    window.solicitudesFicPanelAccionesFormatter = function (value, row) {
        if (!row) return '';

        var buttons = [];
        buttons.push('<button type="button" class="btn btn-outline-info btn-sm js-fic-panel-ver" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Ver"><i class="mdi mdi-eye"></i></button>');

        if (String(row.estatus || '').toLowerCase() === 'pendiente') {
            buttons.push('<button type="button" class="btn btn-outline-warning btn-sm js-fic-panel-cancelar" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Cancelar"><i class="mdi mdi-close"></i></button>');
        }

        return '<div class="usuario-actions">' + buttons.join('') + '</div>';
    };

    function bindEvents() {
        $('#filtroSolicitudFolioFicEstatus, #filtroSolicitudQrFicEstatus')
            .off('change.solicitudesFicPanel')
            .on('change.solicitudesFicPanel', function () {
                refreshTable(state.folioTable);
                refreshTable(state.qrTable);
            });

        $(document)
            .off('click.solicitudesFicPanel')
            .on('click.solicitudesFicPanel', '.js-fic-panel-ver', function () {
                var idSolicitud = Number($(this).data('id-solicitud') || 0);
                loadFolioDetail(idSolicitud, function (data) {
                    var comentario = String(data.comentario_ti || '').trim();
                    var comentarioHtml = comentario !== ''
                        ? '<hr class="border-secondary my-3"><div><strong>Comentario TI:</strong><br><pre class="bg-transparent text-light border-0 p-0 m-0" style="white-space:pre-wrap;font-family:inherit;">' + esc(comentario) + '</pre></div>'
                        : '';

                    Swal.fire({
                        title: 'Solicitud de folio FIC',
                        html: '<div class="text-start"><strong>Perfil:</strong> ' + esc(data.perfil_solicitado || '') + '<br><strong>Usuario:</strong> ' + (String(data.usuario || '').trim() !== '' ? esc(data.usuario || '') : 'Por asignar') + '<br><strong>Nombre:</strong> ' + esc(data.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(data.correo || '') + '<br><strong>Estatus:</strong> ' + esc(data.estatus || '') + comentarioHtml + '</div>',
                        confirmButtonText: 'Cerrar'
                    });
                });
            })
            .on('click.solicitudesFicPanel', '.js-fic-panel-cancelar', function () {
                var idSolicitud = Number($(this).data('id-solicitud') || 0);
                if (!idSolicitud || !state.folioCancelUrl) return;

                Swal.fire({
                    title: 'Cancelar solicitud',
                    text: 'La solicitud se marcará como cancelada.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Cancelar solicitud',
                    cancelButtonText: 'Volver'
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: state.folioCancelUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: $.extend({ id_solicitud_usuario: idSolicitud }, getCsrfPayload())
                    }).done(function (response) {
                        if (!response || response.ok !== true) {
                            Swal.fire('Atención', response && response.message ? response.message : 'No fue posible cancelar la solicitud.', 'warning');
                            return;
                        }

                        Swal.fire('Listo', response.message || 'Solicitud cancelada.', 'success');
                        refreshTable(state.folioTable);
                    }).fail(function () {
                        Swal.fire('Error', 'No fue posible cancelar la solicitud.', 'error');
                    });
                });
            })
            .on('click.solicitudesFicPanel', '.js-qr-fic-preview', function () {
                openPreviewModal({
                    title: String($(this).data('title') || 'Archivo'),
                    subtitle: 'ID usuario: ' + String($(this).data('id-usuario') || ''),
                    idUsuario: String($(this).data('id-usuario') || ''),
                    field: String($(this).data('field') || ''),
                    fileName: String($(this).data('archivo') || ''),
                    url: String($(this).data('url') || '')
            });
            })
            .on('click.solicitudesFicPanel', '.js-qr-fic-activar', function () {
                var idUsuario = Number($(this).data('id-usuario') || 0);
                if (!idUsuario || !state.qrApproveUrl) return;

                Swal.fire({
                    title: '¿Deseas activar el QR de este usuario?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, activar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: state.qrApproveUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: $.extend({ id_usuario: idUsuario }, getCsrfPayload())
                    }).done(function (response) {
                        if (!response || response.success !== true) {
                            Swal.fire('Atención', response && response.message ? response.message : 'No fue posible activar el QR.', 'warning');
                            return;
                        }

                        Swal.fire('Listo', response.message || 'QR activado correctamente.', 'success');
                        refreshTable(state.qrTable);
                    }).fail(function () {
                        Swal.fire('Error', 'No fue posible activar el QR.', 'error');
                    });
                });
            })
            .on('click.solicitudesFicPanel', '.js-qr-fic-rechazar', function () {
                var idUsuario = Number($(this).data('id-usuario') || 0);
                if (!idUsuario || !state.qrRejectUrl) return;

                Swal.fire({
                    title: '¿Deseas rechazar esta solicitud?',
                    text: 'El usuario tendrá que cargar nuevamente sus evidencias.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, rechazar',
                    cancelButtonText: 'Cancelar'
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: state.qrRejectUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: $.extend({ id_usuario: idUsuario }, getCsrfPayload())
                    }).done(function (response) {
                        if (!response || response.success !== true) {
                            Swal.fire('Atención', response && response.message ? response.message : 'No fue posible rechazar la solicitud.', 'warning');
                            return;
                        }

                        Swal.fire('Listo', response.message || 'Solicitud rechazada.', 'success');
                        refreshTable(state.qrTable);
                    }).fail(function () {
                        Swal.fire('Error', 'No fue posible rechazar la solicitud.', 'error');
                    });
                });
            });
    }

    return {
        iniciar: function () {
            var root = $('#solicitudesUsuarioFicPanelRoot');
            if (!root.length) return;

            state.root = root;
            state.qrListUrl = root.data('qr-list-url') || '';
            state.qrFileUrl = root.data('qr-file-url') || '';
            state.qrApproveUrl = root.data('qr-approve-url') || '';
            state.qrRejectUrl = root.data('qr-reject-url') || '';
            state.folioListUrl = root.data('folio-list-url') || '';
            state.folioDetailUrl = root.data('folio-detail-url') || '';
            state.folioCancelUrl = root.data('folio-cancel-url') || '';
            state.qrTable = $('#tablaSolicitudesActivacionQrFic');
            state.folioTable = $('#tablaSolicitudesFoliosFic');
            state.previewModalEl = document.getElementById('modalPreviewArchivoQrFic');
            state.previewModal = state.previewModalEl && window.bootstrap && bootstrap.Modal
                ? bootstrap.Modal.getOrCreateInstance(state.previewModalEl)
                : null;

            initQrTable();
            initFolioTable();
            bindEvents();
        }
    };
})();

$(function () {
    if (typeof solicitudesUsuarioFicPanel !== 'undefined' && typeof solicitudesUsuarioFicPanel.iniciar === 'function') {
        solicitudesUsuarioFicPanel.iniciar();
    }
});


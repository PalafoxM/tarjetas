var solicitudesUsuarioFicPanel = (function () {
    var state = {
        root: null,
        qrTable: null,
        folioTable: null,
        qrListUrl: '',
        folioListUrl: '',
        folioDetailUrl: '',
        folioCancelUrl: ''
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
                    estatus: $('#filtroSolicitudQrFicEstatus').val() || ''
                };
            },
            responseHandler: function (response) {
                if (response && response.ok === true && Array.isArray(response.rows)) {
                    return {
                        total: Number(response.total || 0),
                        rows: response.rows
                    };
                }
                return { total: 0, rows: [] };
            },
            onLoadSuccess: function () {
                $('#solicitudesQrPlaceholder').addClass('d-none');
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

    window.solicitudesQrFicEstadoFormatter = function (value) {
        return badgeEstado(value);
    };

    window.solicitudesQrFicFechaFormatter = function (value) {
        return formatFecha(value);
    };

    window.solicitudesQrFicAccionesFormatter = function (value, row) {
        if (!row) return '';
        return '<div class="usuario-actions">' +
            '<button type="button" class="btn btn-outline-info btn-sm js-qr-fic-ver" data-id-usuario="' + esc(row.id_usuario || '') + '" title="Ver"><i class="mdi mdi-eye"></i></button>' +
            '</div>';
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
            .on('click.solicitudesFicPanel', '.js-qr-fic-ver', function () {
                var idUsuario = Number($(this).data('id-usuario') || 0);
                if (!idUsuario) return;

                var row = {};
                if (state.qrTable && state.qrTable.length && state.qrTable.data('bootstrap.table')) {
                    var data = state.qrTable.bootstrapTable('getData') || [];
                    row = data.find(function (item) {
                        return Number(item.id_usuario || 0) === idUsuario;
                    }) || {};
                }

                Swal.fire({
                    title: 'Solicitud de activación QR',
                    html: '<div class="text-start"><strong>Usuario:</strong> ' + esc(row.usuario || '') + '<br><strong>Nombre:</strong> ' + esc(row.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(row.correo || '') + '<br><strong>Estatus:</strong> ' + esc(row.estatus || '') + '</div>',
                    confirmButtonText: 'Cerrar'
                });
            });
    }

    return {
        iniciar: function () {
            var root = $('#solicitudesUsuarioFicPanelRoot');
            if (!root.length) return;

            state.root = root;
            state.qrListUrl = root.data('qr-list-url') || '';
            state.folioListUrl = root.data('folio-list-url') || '';
            state.folioDetailUrl = root.data('folio-detail-url') || '';
            state.folioCancelUrl = root.data('folio-cancel-url') || '';
            state.qrTable = $('#tablaSolicitudesActivacionQrFic');
            state.folioTable = $('#tablaSolicitudesFoliosFic');

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

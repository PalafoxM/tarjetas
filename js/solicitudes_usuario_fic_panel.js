var solicitudesUsuarioFicPanel = (function () {
    var state = {
        root: null,
        folioTable: null,
        folioListUrl: '',
        folioDetailUrl: '',
        folioCancelUrl: ''
    };

    function esc(value) {
        return String(value  '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatFecha(value) {
        if (!value) return '';
        if (window.saeg && saeg.principal && typeof saeg.principal.fecha === 'function') {
            return saeg.principal.fecha(value);
        }
        return value;
    }

    function badgeEstado(estatus) {
        var value = String(estatus || '').toLowerCase();
        if (value === 'pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        if (value === 'aprobada') return '<span class="badge bg-success">Aprobada</span>';
        if (value === 'rechazada') return '<span class="badge bg-danger">Rechazada</span>';
        if (value === 'cancelada') return '<span class="badge bg-secondary">Cancelada</span>';
        return '<span class="badge bg-secondary">' + esc(value || 'Sin definir') + '</span>';
    }

    function refreshFolios() {
        if (!state.folioTable || !state.folioTable.length || !state.folioTable.data('bootstrap.table')) {
            return;
        }

        state.folioTable.bootstrapTable('refresh', {
            pageNumber: 1,
            silent: false
        });
    }

    function loadFolioDetail(idSolicitud, callback) {
        if (!state.folioDetailUrl || !idSolicitud) return;

        $.getJSON(state.folioDetailUrl, { id_solicitud_usuario: idSolicitud })
            .done(function (response) {
                if (!response || response.ok !== true || !response.data) {
                    Swal.fire('Atención', response && response.message  response.message : 'No fue posible cargar la solicitud.', 'warning');
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

    function getCsrfPayload() {
        var token = $('input[name][type="hidden"]').filter(function () {
            return this.name && this.name.indexOf('csrf') !== -1;
        }).first();

        if (!token.length) {
            return {};
        }

        var payload = {};
        payload[token.attr('name')] = token.val();
        return payload;
    }

    function initQrPlaceholderTable() {
        var table = $('#tablaSolicitudesActivacionQrFic');
        if (!table.length || typeof $.fn.bootstrapTable !== 'function') {
            return;
        }

        if (table.data('bootstrap.table')) {
            table.bootstrapTable('destroy');
        }

        table.bootstrapTable({
            locale: 'es-MX',
            search: true,
            searchAlign: 'left',
            pagination: true,
            pageSize: 10,
            pageList: [10, 25, 50, 100],
            data: [],
            undefinedText: '',
            formatNoMatches: function () {
                return 'Sin fuente de datos conectada para activación QR.';
            }
        });
    }

    window.solicitudesFicPanelEstadoFormatter = function (value) {
        return badgeEstado(value);
    };

    window.solicitudesFicPanelFechaFormatter = function (value) {
        return formatFecha(value);
    };

    window.solicitudesFicPanelUsuarioFormatter = function (value) {
        var usuario = String(value || '').trim();
        return usuario !== ''  esc(usuario) : '<span class="text-muted">Por asignar</span>';
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

    return {
        iniciar: function () {
            var root = $('#solicitudesUsuarioFicPanelRoot');
            if (!root.length) return;

            state.root = root;
            state.folioListUrl = root.data('folio-list-url') || '';
            state.folioDetailUrl = root.data('folio-detail-url') || '';
            state.folioCancelUrl = root.data('folio-cancel-url') || '';
            state.folioTable = $('#tablaSolicitudesFoliosFic');

            initQrPlaceholderTable();
            this.initFoliosTable();
            this.bindEvents();
        },

        initFoliosTable: function () {
            if (!state.folioTable.length || !state.folioListUrl || typeof $.fn.bootstrapTable !== 'function') {
                return;
            }

            if (state.folioTable.data('bootstrap.table')) {
                state.folioTable.bootstrapTable('destroy');
            }

            state.folioTable.bootstrapTable({
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

                    return {
                        total: 0,
                        rows: []
                    };
                },
                onLoadError: function () {
                    Swal.fire('Error', 'No fue posible cargar las solicitudes de folio.', 'error');
                }
            });
        },

        bindEvents: function () {
            $('#filtroSolicitudFolioFicEstatus')
                .off('change.solicitudesFicPanel')
                .on('change.solicitudesFicPanel', function () {
                    refreshFolios();
                });

            $(document)
                .off('click.solicitudesFicPanel')
                .on('click.solicitudesFicPanel', '.js-fic-panel-ver', function () {
                    var idSolicitud = Number($(this).data('id-solicitud') || 0);
                    loadFolioDetail(idSolicitud, function (data) {
                        var comentario = String(data.comentario_ti || '').trim();
                        var comentarioHtml = comentario !== ''
                             '<hr class="border-secondary my-3"><div><strong>Comentario TI:</strong><br>' + esc(comentario) + '</div>'
                            : '';

                        Swal.fire({
                            title: 'Solicitud de folio FIC',
                            html: '<div class="text-start"><strong>Perfil:</strong> ' + esc(data.perfil_solicitado || '') + '<br><strong>Usuario:</strong> ' + (String(data.usuario || '').trim() !== ''  esc(data.usuario || '') : 'Por asignar') + '<br><strong>Nombre:</strong> ' + esc(data.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(data.correo || '') + '<br><strong>Estatus:</strong> ' + esc(data.estatus || '') + comentarioHtml + '</div>',
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

                        var payload = $.extend({
                            id_solicitud_usuario: idSolicitud
                        }, getCsrfPayload());

                        $.ajax({
                            url: state.folioCancelUrl,
                            method: 'POST',
                            dataType: 'json',
                            data: payload
                        }).done(function (response) {
                            if (!response || response.ok !== true) {
                                Swal.fire('Atención', response && response.message  response.message : 'No fue posible cancelar la solicitud.', 'warning');
                                return;
                            }

                            Swal.fire('Listo', response.message || 'Solicitud cancelada.', 'success');
                            refreshFolios();
                        }).fail(function () {
                            Swal.fire('Error', 'No fue posible cancelar la solicitud.', 'error');
                        });
                    });
                });
        }
    };
})();

$(function () {
    if (typeof solicitudesUsuarioFicPanel !== 'undefined' && typeof solicitudesUsuarioFicPanel.iniciar === 'function') {
        solicitudesUsuarioFicPanel.iniciar();
    }
});

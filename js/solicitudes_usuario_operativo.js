var solicitudesUsuarioOperativo = (function () {
    var state = {
        root: null,
        table: null,
        listUrl: '',
        detailUrl: '',
        approveUrl: '',
        rejectUrl: '',
        currentSolicitud: null
    };

    function esc(value) {
        return String(value ?? '')
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
        var fecha = new Date(value);
        if (isNaN(fecha.getTime())) return value;
        return fecha.toLocaleString('es-MX');
    }

    function resolveLabelTipoUsuario(row) {
        var idPerfil = Number(row && (row.id_perfil_solicitado || row.id_perfil || 0));
        if (idPerfil === 5) return 'GERENTE';
        if (idPerfil === 7) return 'RECEPCIÓN';
        return row && row.tipo_usuario_solicitado ? row.tipo_usuario_solicitado : 'SIN DEFINIR';
    }

    function getEstatusBadge(estatus) {
        var value = String(estatus || '').toLowerCase();
        if (value === 'pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        if (value === 'aprobada') return '<span class="badge bg-success">Aprobada</span>';
        if (value === 'rechazada') return '<span class="badge bg-danger">Rechazada</span>';
        return '<span class="badge bg-secondary">' + esc(value || 'Sin definir') + '</span>';
    }

    function fillReadonly(selector, value) {
        $(selector).val(value == null ? '' : String(value));
    }

    function resetApprovalModal() {
        state.currentSolicitud = null;
        $('#solicitud_usuario_id_aprobar').val('0');
        $('#solicitudUsuarioOperativoTitulo').text('Revisar solicitud');
        fillReadonly('#solicitud_proveedor_aprobar', '');
        fillReadonly('#solicitud_razon_social_aprobar', '');
        fillReadonly('#solicitud_establecimiento_aprobar', '');
        fillReadonly('#solicitud_tipo_establecimiento_aprobar', '');
        fillReadonly('#solicitud_tipo_usuario_aprobar', '');
        fillReadonly('#solicitud_nombre_aprobar', '');
        fillReadonly('#solicitud_primer_apellido_aprobar', '');
        fillReadonly('#solicitud_segundo_apellido_aprobar', '');
        fillReadonly('#solicitud_correo_aprobar', '');
        $('#solicitud_usuario_operativo').val('');
        $('#solicitud_contrasenia_aprobar').val('');
    }

    function resetRejectModal() {
        state.currentSolicitud = null;
        $('#solicitud_usuario_id_rechazar').val('0');
        $('#solicitudUsuarioOperativoRechazoTitulo').text('Rechazar solicitud');
        fillReadonly('#solicitud_proveedor_rechazo', '');
        fillReadonly('#solicitud_establecimiento_rechazo', '');
        fillReadonly('#solicitud_tipo_usuario_rechazo', '');
        fillReadonly('#solicitud_nombre_rechazo', '');
        fillReadonly('#solicitud_correo_rechazo', '');
        $('#solicitud_motivo_rechazo').val('');
    }

    function pintarSolicitudEnModal(data, modo) {
        state.currentSolicitud = data || null;
        var solicitud = state.currentSolicitud || {};
        var proveedorSolicitante = String(solicitud.proveedor_solicitante || '').trim();
        var proveedorRazn = String(solicitud.proveedor_razon_social || '').trim();
        var proveedorTexto = proveedorSolicitante;
        if (proveedorRazn) {
            proveedorTexto += proveedorTexto ? ' - ' + proveedorRazn : proveedorRazn;
        }

        var nombreCompleto = String(solicitud.nombre_completo || '').trim();
        var tipoUsuario = String(solicitud.tipo_usuario_solicitado || '').trim();
        var establecimiento = String(solicitud.dsc_establecimiento || '').trim();
        var tipoEstablecimiento = String(solicitud.dsc_tipo || '').trim();
        var correo = String(solicitud.correo || '').trim();

        $('#solicitud_usuario_id_aprobar').val(String(solicitud.id_solicitud_usuario || '0'));
        $('#solicitud_usuario_id_rechazar').val(String(solicitud.id_solicitud_usuario || '0'));
        fillReadonly('#solicitud_proveedor_aprobar', proveedorSolicitante);
        fillReadonly('#solicitud_razon_social_aprobar', proveedorRazn);
        fillReadonly('#solicitud_establecimiento_aprobar', establecimiento);
        fillReadonly('#solicitud_tipo_establecimiento_aprobar', tipoEstablecimiento);
        fillReadonly('#solicitud_tipo_usuario_aprobar', tipoUsuario);
        fillReadonly('#solicitud_nombre_aprobar', nombreCompleto);
        fillReadonly('#solicitud_primer_apellido_aprobar', solicitud.primer_apellido || '');
        fillReadonly('#solicitud_segundo_apellido_aprobar', solicitud.segundo_apellido || '');
        fillReadonly('#solicitud_correo_aprobar', correo);
        $('#solicitud_usuario_operativo').val('');
        $('#solicitud_contrasenia_aprobar').val('');

        fillReadonly('#solicitud_proveedor_rechazo', proveedorTexto);
        fillReadonly('#solicitud_establecimiento_rechazo', establecimiento);
        fillReadonly('#solicitud_tipo_usuario_rechazo', tipoUsuario);
        fillReadonly('#solicitud_nombre_rechazo', nombreCompleto);
        fillReadonly('#solicitud_correo_rechazo', correo);

        if (modo === 'aprobar') {
            $('#solicitudUsuarioOperativoTitulo').text('Aprobar solicitud');
            $('#solicitudUsuarioOperativoAprobarAlert').removeClass('d-none').text('Completa el usuario y la contraseña para crear el acceso operativo.');
        } else {
            $('#solicitudUsuarioOperativoTitulo').text('Revisar solicitud');
            $('#solicitudUsuarioOperativoAprobarAlert').removeClass('d-none').text('Revisa la información y decide si aprobar o rechazar.');
        }
    }

    function pintarSolicitudRechazo(data) {
        state.currentSolicitud = data || null;
        var solicitud = state.currentSolicitud || {};
        var proveedorSolicitante = String(solicitud.proveedor_solicitante || '').trim();
        var proveedorRazn = String(solicitud.proveedor_razon_social || '').trim();
        var proveedorTexto = proveedorSolicitante;
        if (proveedorRazn) {
            proveedorTexto += proveedorTexto ? ' - ' + proveedorRazn : proveedorRazn;
        }

        $('#solicitud_usuario_id_rechazar').val(String(solicitud.id_solicitud_usuario || '0'));
        fillReadonly('#solicitud_proveedor_rechazo', proveedorTexto);
        fillReadonly('#solicitud_establecimiento_rechazo', solicitud.dsc_establecimiento || '');
        fillReadonly('#solicitud_tipo_usuario_rechazo', solicitud.tipo_usuario_solicitado || '');
        fillReadonly('#solicitud_nombre_rechazo', solicitud.nombre_completo || '');
        fillReadonly('#solicitud_correo_rechazo', solicitud.correo || '');
        $('#solicitud_motivo_rechazo').val('');
        $('#solicitudUsuarioOperativoRechazoTitulo').text('Rechazar solicitud');
    }

    function cargarSolicitud(idSolicitud, callback) {
        return $.getJSON(state.detailUrl + '/' + encodeURIComponent(idSolicitud))
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

    function refrescarTabla() {
        if (!state.table || !state.table.length) {
            return;
        }

        if (!state.table.data('bootstrap.table')) {
            console.error('La tabla de solicitudes no está inicializada.');
            return;
        }

        state.table.bootstrapTable('refresh', {
            pageNumber: 1,
            silent: false
        });
    }

    return {
        iniciar: function () {
            var root = $('#solicitudesUsuarioOperativoRoot');
            if (!root.length) return;

            state.root = root;
            state.listUrl = root.data('list-url') || '';
            state.detailUrl = root.data('detail-url') || '';
            state.approveUrl = root.data('approve-url') || '';
            state.rejectUrl = root.data('reject-url') || '';

            this.inicializarTabla();
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            $('#filtroSolicitudUsuarioOperativoEstatus')
                .off('change.solicitudesUsuarioOperativo')
                .on('change.solicitudesUsuarioOperativo', function () {
                    refrescarTabla();
                });

            $('#formAprobarSolicitudUsuarioOperativo')
                .off('submit.solicitudesUsuarioOperativo')
                .on('submit.solicitudesUsuarioOperativo', function (event) {
                    event.preventDefault();
                    self.aprobarSolicitud();
                });

            $('#formRechazarSolicitudUsuarioOperativo')
                .off('submit.solicitudesUsuarioOperativo')
                .on('submit.solicitudesUsuarioOperativo', function (event) {
                    event.preventDefault();
                    self.rechazarSolicitud();
                });

            $('#btnAbrirRechazoSolicitudUsuarioOperativo')
                .off('click.solicitudesUsuarioOperativo')
                .on('click.solicitudesUsuarioOperativo', function () {
                    var idSolicitud = Number(
                        $('#solicitud_usuario_id_aprobar').val() || 0
                    );

                    if (!idSolicitud) {
                        return;
                    }

                    $('#modalRevisionSolicitudUsuarioOperativo').modal('hide');
                    self.abrirRechazo(idSolicitud);
                });

            $('#solicitud_usuario_operativo')
                .off('input.solicitudesUsuarioOperativo')
                .on('input.solicitudesUsuarioOperativo', function () {
                    this.value = String(this.value || '').toLowerCase();
                });

            $('#solicitud_motivo_rechazo')
                .off('input.solicitudesUsuarioOperativo')
                .on('input.solicitudesUsuarioOperativo', function () {
                    this.value = String(this.value || '').trimStart();
                });

            $('#modalRevisionSolicitudUsuarioOperativo')
                .off('hidden.bs.modal.solicitudesUsuarioOperativo')
                .on('hidden.bs.modal.solicitudesUsuarioOperativo', function () {
                    resetApprovalModal();
                });

            $('#modalRechazoSolicitudUsuarioOperativo')
                .off('hidden.bs.modal.solicitudesUsuarioOperativo')
                .on('hidden.bs.modal.solicitudesUsuarioOperativo', function () {
                    resetRejectModal();
                });
        },

        inicializarTabla: function () {
            if (!state.root.length) {
                return;
            }

            if (typeof $.fn.bootstrapTable !== 'function') {
                console.error('Bootstrap Table no está disponible para solicitudes TI.');

                if (typeof Swal !== 'undefined') {
                    Swal.fire(
                        'Error',
                        'No fue posible cargar la tabla de solicitudes.',
                        'error'
                    );
                }

                return;
            }

            state.table = $('#tablaSolicitudesUsuarioOperativo');

            if (!state.table.length) {
                console.error('No se encontró la tabla de solicitudes.');
                return;
            }

            if (!state.listUrl) {
                console.error('La URL del listado de solicitudes está vacía.');
                return;
            }

            if (state.table.data('bootstrap.table')) {
                state.table.bootstrapTable('destroy');
            }

            console.info('Consultando solicitudes en:', state.listUrl);

            state.table.bootstrapTable({
                url: state.listUrl,
                method: 'get',
                locale: 'es-MX',
                search: true,
                searchAlign: 'left',
                pagination: true,
                sidePagination: 'server',
                pageSize: 10,
                pageList: [10, 25, 50, 100],

                queryParams: function (params) {
                    return solicitudesUsuarioOperativo.queryParams(params);
                },

                responseHandler: function (response) {
                    console.info('Respuesta solicitudes:', response);

                    if (response && Array.isArray(response.rows)) {
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

                    console.error(
                        'Formato de respuesta no válido para Bootstrap Table:',
                        response
                    );

                    return {
                        total: 0,
                        rows: []
                    };
                },

                onLoadError: function (status, request) {
                    console.error(
                        'Error al cargar solicitudes:',
                        status,
                        request ? request.responseText : ''
                    );

                    if (typeof Swal !== 'undefined') {
                        Swal.fire(
                            'Error',
                            'No fue posible cargar la bandeja de solicitudes.',
                            'error'
                        );
                    }
                }
            });
        },

        queryParams: function (params) {
            var limit = Number(params.limit || params.pageSize || 10);
            var offset = Number(
                params.offset != null
                    ? params.offset
                    : 0
            );

            var filtroEstatus =
                $('#filtroSolicitudUsuarioOperativoEstatus').val();

            return {
                limit: limit,
                offset: offset,

                search: String(
                    params.search || params.searchText || ''
                ).trim(),

                estatus: filtroEstatus == null
                    ? 'pendiente'
                    : String(filtroEstatus).trim()
            };
        },

        abrirRevision: function (idSolicitud, modo) {
            var self = this;
            cargarSolicitud(idSolicitud, function (data) {
                pintarSolicitudEnModal(data, modo || 'revisar');
                $('#modalRevisionSolicitudUsuarioOperativo').modal('show');
                $('#solicitud_usuario_operativo').trigger('focus');
            });
        },

        abrirAprobacion: function (idSolicitud) {
            this.abrirRevision(idSolicitud, 'aprobar');
        },

        abrirRechazo: function (idSolicitud) {
            var self = this;
            cargarSolicitud(idSolicitud, function (data) {
                pintarSolicitudRechazo(data);
                $('#modalRechazoSolicitudUsuarioOperativo').modal('show');
                $('#solicitud_motivo_rechazo').trigger('focus');
            });
        },

        aprobarSolicitud: function () {
            var boton = $('#btnConfirmarAprobarSolicitudUsuarioOperativo');
            var textoOriginal = boton.html();
            var idSolicitud = Number($('#solicitud_usuario_id_aprobar').val() || 0);
            var usuario = String($('#solicitud_usuario_operativo').val() || '').trim().toLowerCase();
            var contrasenia = String($('#solicitud_contrasenia_aprobar').val() || '').trim();

            if (!idSolicitud || !usuario || !contrasenia) {
                Swal.fire('Atención', 'Completa usuario y contraseña.', 'warning');
                return;
            }

            boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...');

            $.ajax({
                url: state.approveUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    id_solicitud_usuario: idSolicitud,
                    usuario: usuario,
                    contrasenia: contrasenia
                }
            }).done(function (response) {
                if (!response || response.ok !== true) {
                    Swal.fire('Atención', response && (response.message || response.respuesta) ? (response.message || response.respuesta) : 'No fue posible aprobar la solicitud.', 'warning');
                    return;
                }

                $('#modalRevisionSolicitudUsuarioOperativo').modal('hide');
                Swal.fire('Correcto', response.message || 'Solicitud aprobada correctamente.', 'success');
                refrescarTabla();
            }).fail(function (jqXHR) {
                var message = 'No fue posible aprobar la solicitud.';
                if (jqXHR && jqXHR.responseJSON && (jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta)) {
                    message = jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta;
                }
                Swal.fire('Error', message, 'error');
            }).always(function () {
                boton.prop('disabled', false).html(textoOriginal);
            });
        },

        rechazarSolicitud: function () {
            var boton = $('#btnConfirmarRechazoSolicitudUsuarioOperativo');
            var textoOriginal = boton.html();
            var idSolicitud = Number($('#solicitud_usuario_id_rechazar').val() || 0);
            var motivo = String($('#solicitud_motivo_rechazo').val() || '').trim();

            if (!idSolicitud || !motivo) {
                Swal.fire('Atención', 'Captura el motivo del rechazo.', 'warning');
                return;
            }

            boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...');

            $.ajax({
                url: state.rejectUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    id_solicitud_usuario: idSolicitud,
                    comentario_ti: motivo
                }
            }).done(function (response) {
                if (!response || response.ok !== true) {
                    Swal.fire('Atención', response && (response.message || response.respuesta) ? (response.message || response.respuesta) : 'No fue posible rechazar la solicitud.', 'warning');
                    return;
                }

                $('#modalRechazoSolicitudUsuarioOperativo').modal('hide');
                Swal.fire('Correcto', response.message || 'Solicitud rechazada correctamente.', 'success');
                refrescarTabla();
            }).fail(function (jqXHR) {
                var message = 'No fue posible rechazar la solicitud.';
                if (jqXHR && jqXHR.responseJSON && (jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta)) {
                    message = jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta;
                }
                Swal.fire('Error', message, 'error');
            }).always(function () {
                boton.prop('disabled', false).html(textoOriginal);
            });
        },

        escapeHtml: esc,
        formatFecha: formatFecha,
        resolveLabelTipoUsuario: resolveLabelTipoUsuario,
        getEstatusBadge: getEstatusBadge
    };
})();

window.queryParamsSolicitudesUsuarioOperativo = function (params) {
    return solicitudesUsuarioOperativo.queryParams(params || {});
};

window.formatterSolicitudUsuarioOperativoProveedor = function (value, row) {
    var proveedorSolicitante = String(row && row.proveedor_solicitante ? row.proveedor_solicitante : '').trim();
    var proveedorUsuario = String(row && row.proveedor_usuario ? row.proveedor_usuario : '').trim();
    var extra = proveedorUsuario && proveedorUsuario !== proveedorSolicitante ? '<div class="text-muted small">' + solicitudesUsuarioOperativo.escapeHtml(proveedorUsuario) + '</div>' : '';
    return '<div><strong>' + solicitudesUsuarioOperativo.escapeHtml(proveedorSolicitante) + '</strong>' + extra + '</div>';
};

window.formatterSolicitudUsuarioOperativoRazonSocial = function (value, row) {
    return '<div class="text-white">' + solicitudesUsuarioOperativo.escapeHtml(row && row.proveedor_razon_social ? row.proveedor_razon_social : '') + '</div>';
};

window.formatterSolicitudUsuarioOperativoTipoUsuario = function (value, row) {
    return '<span class="badge bg-info text-dark">' + solicitudesUsuarioOperativo.escapeHtml(solicitudesUsuarioOperativo.resolveLabelTipoUsuario(row)) + '</span>';
};

window.formatterSolicitudUsuarioOperativoFecha = function (value) {
    return solicitudesUsuarioOperativo.formatFecha(value);
};

window.formatterSolicitudUsuarioOperativoStatus = function (value) {
    return solicitudesUsuarioOperativo.getEstatusBadge(value);
};

window.formatterSolicitudUsuarioOperativoAcciones = function (value, row) {
    var idSolicitud = Number(row && row.id_solicitud_usuario ? row.id_solicitud_usuario : 0);
    if (!idSolicitud) return '';

    var isPending = String(row && row.estatus ? row.estatus : '').toLowerCase() === 'pendiente';
    var review = '<button type="button" class="btn btn-outline-info btn-sm" title="Revisar" onclick="solicitudesUsuarioOperativo.abrirRevision(' + idSolicitud + ')"><i class="mdi mdi-eye"></i></button>';
    var approve = '<button type="button" class="btn btn-outline-success btn-sm" title="Aprobar" onclick="solicitudesUsuarioOperativo.abrirAprobacion(' + idSolicitud + ')"><i class="mdi mdi-check"></i></button>';
    var reject = '<button type="button" class="btn btn-outline-danger btn-sm" title="Rechazar" onclick="solicitudesUsuarioOperativo.abrirRechazo(' + idSolicitud + ')"><i class="mdi mdi-close"></i></button>';

    if (!isPending) {
        return '<div class="d-inline-flex gap-1">' + review + '</div>';
    }

    return '<div class="d-inline-flex gap-1">' + review + approve + reject + '</div>';
};

window.solicitudesUsuarioOperativo = solicitudesUsuarioOperativo;

$(function () {
    try {
        solicitudesUsuarioOperativo.iniciar();
        if (window.console && typeof console.info === "function") {
            console.info('solicitudesUsuarioOperativo inicializado');
        }
    } catch (error) {
        console.error('No se pudo inicializar solicitudesUsuarioOperativo:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire('Error', 'No fue posible inicializar la bandeja de solicitudes.', 'error');
        }
    }
});

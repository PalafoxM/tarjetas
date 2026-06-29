var solicitudesUsuarioCatalogo = (function () {
    var state = {
        root: null,
        listUrl: '',
        detailUrl: '',
        saveUrl: '',
        cancelUrl: '',
        modal: null,
        form: null,
        table: null,
        currentSolicitud: null
    };

    function esc(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalize(value, upper) {
        var output = String(value || '').trim();
        if (upper) {
            output = output.toUpperCase();
        }
        return output;
    }

    function clearAlert() {
        var alert = $('#solicitudUsuarioCatalogoAlert');
        if (!alert.length) return;
        alert.addClass('d-none').text('');
    }

    function setAlert(message, type) {
        var alert = $('#solicitudUsuarioCatalogoAlert');
        if (!alert.length) return;
        alert.removeClass('d-none alert-info alert-warning alert-danger alert-success')
            .addClass('alert-' + (type || 'info'))
            .text(message || '');
    }

    function clearForm() {
        if (state.form && state.form.length && state.form[0]) {
            state.form[0].reset();
        }
        $('#solicitud_usuario_catalogo_id, #solicitud_usuario_catalogo_establecimiento').val('');
        $('#solicitud_usuario_catalogo_perfil').val('').trigger('change');
        $('#solicitud_usuario_catalogo_nombre, #solicitud_usuario_catalogo_primer_apellido, #solicitud_usuario_catalogo_segundo_apellido, #solicitud_usuario_catalogo_correo, #solicitud_usuario_catalogo_observaciones').val('');
        $('#solicitud_usuario_catalogo_beneficios').val('ninguno');
    }

    function fillSolicitud(row) {
        if (!row) return;
        $('#solicitud_usuario_catalogo_id').val(String(row.id_solicitud_usuario || '0'));
        $('#solicitud_usuario_catalogo_perfil').val(String(row.id_perfil_solicitado || '')).trigger('change');
        $('#solicitud_usuario_catalogo_nombre').val(String(row.nombre || '').toUpperCase());
        $('#solicitud_usuario_catalogo_primer_apellido').val(String(row.primer_apellido || '').toUpperCase());
        $('#solicitud_usuario_catalogo_segundo_apellido').val(String(row.segundo_apellido || '').toUpperCase());
        $('#solicitud_usuario_catalogo_correo').val(String(row.correo || '').toLowerCase());
        $('#solicitud_usuario_catalogo_observaciones').val(String(row.comentario_ti || ''));
    }

    function openModal() {
        clearForm();
        clearAlert();
        if (state.modal && typeof state.modal.show === 'function') {
            state.modal.show();
            return;
        }
        $('#modalSolicitudUsuarioCatalogo').modal('show');
    }

    function renderEstado(value) {
        var estado = String(value || '').toLowerCase();
        var cls = 'secondary';
        if (estado === 'pendiente') cls = 'warning';
        if (estado === 'aprobada') cls = 'success';
        if (estado === 'cancelada' || estado === 'rechazada') cls = 'danger';
        return '<span class="badge bg-' + cls + '">' + esc(estado || 'sin estado') + '</span>';
    }

    window.catalogoSolicitudUsuarioFormatter = function (value) {
        var usuario = String(value || '').trim();
        return usuario !== '' ? esc(usuario) : '<span class="text-muted">Por asignar por TI</span>';
    };

    window.catalogoSolicitudEstadoFormatter = function (value) {
        return renderEstado(value);
    };

    window.catalogoSolicitudAccionesFormatter = function (value, row) {
        if (!row) return '';
        var buttons = [];
        buttons.push('<button type="button" class="btn btn-outline-info btn-sm js-catalogo-ver-solicitud" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Ver"><i class="mdi mdi-eye"></i></button>');
        if (String(row.estatus || '').toLowerCase() === 'pendiente') {
            buttons.push('<button type="button" class="btn btn-outline-warning btn-sm js-catalogo-cancelar-solicitud" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Cancelar"><i class="mdi mdi-close"></i></button>');
        }
        return '<div class="usuario-actions">' + buttons.join('') + '</div>';
    };

    function refrescarTabla() {
        if (state.table && state.table.length && state.table.data('bootstrap.table')) {
            state.table.bootstrapTable('refresh');
        }
    }

    function cargarSolicitud(idSolicitud, callback) {
        if (!idSolicitud || !state.detailUrl) return;
        $.getJSON(state.detailUrl, { id_solicitud_usuario: idSolicitud })
            .done(function (response) {
                if (!response || response.ok !== true || !response.data) {
                    Swal.fire('Atenci?n', response && response.message ? response.message : 'No fue posible cargar la solicitud.', 'warning');
                    return;
                }
                if (typeof callback === 'function') callback(response.data);
            })
            .fail(function () {
                Swal.fire('Error', 'No fue posible cargar la solicitud.', 'error');
            });
    }

    return {
        iniciar: function () {
            var root = $('#usuariosPage');
            if (!root.length) return;

            state.root = root;
            state.listUrl = root.data('solicitudes-url') || '';
            state.detailUrl = root.data('solicitud-detail-url') || '';
            state.saveUrl = root.data('solicitud-save-url') || '';
            state.cancelUrl = root.data('solicitud-cancel-url') || '';
            state.form = $('#formSolicitudUsuarioCatalogo');
            state.table = $('#tablaSolicitudesCatalogo');
            var modalEl = document.getElementById('modalSolicitudUsuarioCatalogo');
            state.modal = modalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

            this.inicializarTabla();
            this.bindEvents();
        },

        inicializarTabla: function () {
            if (!state.table.length || !state.listUrl) return;
            if (state.table.data('bootstrap.table')) {
                state.table.bootstrapTable('destroy');
            }

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
                    return {
                        limit: params.limit || params.pageSize || 10,
                        offset: params.offset || 0,
                        search: params.searchText || '',
                        sort: params.sort || '',
                        order: params.order || ''
                    };
                },
                responseHandler: function (response) {
                    if (response && response.ok === true && Array.isArray(response.rows)) {
                        return { total: Number(response.total || 0), rows: response.rows };
                    }
                    if (response && Array.isArray(response.data)) {
                        return { total: Number(response.total || response.data.length), rows: response.data };
                    }
                    return { total: 0, rows: [] };
                },
                onLoadError: function () {
                    Swal.fire('Error', 'No fue posible cargar las solicitudes.', 'error');
                }
            });
        },

        bindEvents: function () {
            $(document)
                .on('click.catalogoSolicitud', '.js-catalogo-ver-solicitud', function () {
                    var idSolicitud = Number($(this).data('id-solicitud') || 0);
                    cargarSolicitud(idSolicitud, function (data) {
                        var comentario = String(data.comentario_ti || '').trim();
                        var detalleExtra = comentario !== ''
                            ? '<hr class="border-secondary my-3"><div><strong>Observaciones:</strong><pre class="bg-transparent text-light border-0 p-0 m-0" style="white-space:pre-wrap;font-family:inherit;">' + esc(comentario) + '</pre></div>'
                            : '';
                        Swal.fire({
                            title: 'Solicitud de folio',
                            html: '<div class="text-start"><strong>Perfil:</strong> ' + esc(data.perfil_solicitado || '') + '<br><strong>Usuario:</strong> ' + (String(data.usuario || '').trim() !== '' ? esc(data.usuario || '') : 'Por asignar por TI') + '<br><strong>Nombre:</strong> ' + esc(data.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(data.correo || '') + '<br><strong>Estatus:</strong> ' + esc(data.estatus || '') + detalleExtra + '</div>',
                            confirmButtonText: 'Cerrar'
                        });
                    });
                })
                .on('click.catalogoSolicitud', '.js-catalogo-cancelar-solicitud', function () {
                    var idSolicitud = Number($(this).data('id-solicitud') || 0);
                    if (!idSolicitud) return;

                    Swal.fire({
                        title: 'Cancelar solicitud',
                        text: 'La solicitud se marcará como cancelada.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Cancelar solicitud',
                        cancelButtonText: 'Volver'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        var payload = $.extend({ id_solicitud_usuario: idSolicitud }, getCsrfPayload());
                        $.ajax({
                            url: state.cancelUrl,
                            method: 'POST',
                            dataType: 'json',
                            data: payload
                        }).done(function (response) {
                            if (!response || response.ok !== true) {
                                Swal.fire('Atención', response && response.message ? response.message : 'No fue posible cancelar la solicitud.', 'warning');
                                return;
                            }
                            Swal.fire('Listo', response.message || 'Solicitud cancelada.', 'success');
                            refrescarTabla();
                        }).fail(function () {
                            Swal.fire('Error', 'No fue posible cancelar la solicitud.', 'error');
                        });
                    });
                });

            state.form
                .off('submit.catalogoSolicitud')
                .on('submit.catalogoSolicitud', function (event) {
                    event.preventDefault();
                    if (!state.saveUrl) return;

                    var payload = {
                        id_perfil_solicitado: Number($('#solicitud_usuario_catalogo_perfil').val() || 0),
                        beneficios: String($('#solicitud_usuario_catalogo_beneficios').val() || 'ninguno'),
                        nombre: normalize($('#solicitud_usuario_catalogo_nombre').val(), true),
                        primer_apellido: normalize($('#solicitud_usuario_catalogo_primer_apellido').val(), true),
                        segundo_apellido: normalize($('#solicitud_usuario_catalogo_segundo_apellido').val(), true),
                        correo: normalize($('#solicitud_usuario_catalogo_correo').val(), false),
                        observaciones: $('#solicitud_usuario_catalogo_observaciones').val() || ''
                    };
                    payload = $.extend(payload, getCsrfPayload());

                    if (!payload.id_perfil_solicitado || !payload.beneficios || !payload.nombre || !payload.primer_apellido) {
                        setAlert('Completa los campos obligatorios.', 'warning');
                        return;
                    }

                    $.ajax({
                        url: state.saveUrl,
                        method: 'POST',
                        dataType: 'json',
                        data: payload
                    }).done(function (response) {
                        if (!response || response.ok !== true) {
                            setAlert((response && response.message) ? response.message : 'No fue posible guardar la solicitud.', 'danger');
                            return;
                        }
                        Swal.fire('Listo', response.message || 'Solicitud enviada.', 'success');
                        if (state.modal && typeof state.modal.hide === 'function') {
                            state.modal.hide();
                        } else {
                            $('#modalSolicitudUsuarioCatalogo').modal('hide');
                        }
                        clearForm();
                        clearAlert();
                        refrescarTabla();
                    }).fail(function () {
                        setAlert('No fue posible guardar la solicitud.', 'danger');
                    });
                });

            $(document)
                .off('input.catalogoSolicitudTransform')
                .on('input.catalogoSolicitudTransform', '#solicitud_usuario_catalogo_correo', function () {
                    this.value = String(this.value || '').toLowerCase();
                })
                .on('input.catalogoSolicitudTransform', '#solicitud_usuario_catalogo_nombre, #solicitud_usuario_catalogo_primer_apellido, #solicitud_usuario_catalogo_segundo_apellido', function () {
                    this.value = String(this.value || '').toUpperCase();
                });
        }
    };
})();

$(function () {
    if (typeof solicitudesUsuarioCatalogo !== 'undefined' && typeof solicitudesUsuarioCatalogo.iniciar === 'function') {
        solicitudesUsuarioCatalogo.iniciar();
    }
});

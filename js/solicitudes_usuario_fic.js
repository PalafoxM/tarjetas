var solicitudesUsuarioFic = (function () {
    var state = {
        root: null,
        table: null,
        listUrl: '',
        detailUrl: '',
        saveUrl: '',
        cancelUrl: '',
        modal: null,
        form: null,
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

    function badgeEstado(estatus) {
        var value = String(estatus || '').toLowerCase();
        if (value === 'pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        if (value === 'aprobada') return '<span class="badge bg-success">Aprobada</span>';
        if (value === 'rechazada') return '<span class="badge bg-danger">Rechazada</span>';
        if (value === 'cancelada') return '<span class="badge bg-secondary">Cancelada</span>';
        return '<span class="badge bg-secondary">' + esc(value || 'Sin definir') + '</span>';
    }

    function formatFecha(value) {
        if (!value) return '';
        if (window.saeg && saeg.principal && typeof saeg.principal.fecha === 'function') {
            return saeg.principal.fecha(value);
        }
        return value;
    }

    function setAlert(message, type) {
        var alert = $('#solicitudUsuarioFicAlert');
        if (!alert.length) return;
        alert.removeClass('d-none alert-info alert-danger alert-success alert-warning');
        alert.addClass('alert-' + (type || 'info')).text(message || '');
    }

    function clearAlert() {
        var alert = $('#solicitudUsuarioFicAlert');
        if (!alert.length) return;
        alert.addClass('d-none').text('');
    }

    function clearForm() {
        if (state.form && state.form.length && state.form[0]) {
            state.form[0].reset();
        }
        $('#solicitud_usuario_id_fic').val('0');
        $('#solicitud_usuario_establecimiento_fic').val(String(state.root ? state.root.data('solicitud-establecimiento-id') || '' : ''));
        $('#solicitud_usuario_fic_usuario, #solicitud_usuario_fic_nombre, #solicitud_usuario_fic_primer_apellido, #solicitud_usuario_fic_segundo_apellido, #solicitud_usuario_fic_correo, #solicitud_usuario_fic_observaciones').val('');
        $('#solicitud_usuario_fic_perfil').val('').trigger('change');
    }

    function getCsrfPayload() {
        var payload = {};
        if (!state.form || !state.form.length) {
            return payload;
        }

        state.form.find('input[type="hidden"][name]').each(function () {
            var name = this.name || '';
            if (!name || name === 'id_solicitud_usuario' || name === 'id_establecimiento') {
                return;
            }
            payload[name] = this.value;
        });

        return payload;
    }

    function openModal(data) {
        state.currentSolicitud = data || null;
        var solicitud = state.currentSolicitud || {};
        clearAlert();
        if (solicitud.id_solicitud_usuario) {
            $('#solicitud_usuario_id_fic').val(String(solicitud.id_solicitud_usuario));
        }
        $('#solicitud_usuario_fic_usuario').val(String(solicitud.usuario || '').toLowerCase());
        $('#solicitud_usuario_fic_nombre').val(String(solicitud.nombre || '').toUpperCase());
        $('#solicitud_usuario_fic_perfil').val(String(solicitud.id_perfil_solicitado || '')).trigger('change');
        $('#solicitud_usuario_fic_primer_apellido').val(String(solicitud.primer_apellido || '').toUpperCase());
        $('#solicitud_usuario_fic_segundo_apellido').val(String(solicitud.segundo_apellido || '').toUpperCase());
        $('#solicitud_usuario_fic_correo').val(String(solicitud.correo || '').toLowerCase());
        $('#solicitud_usuario_fic_observaciones').val(String(solicitud.comentario_ti || ''));
        if (state.modal && typeof state.modal.show === 'function') {
            state.modal.show();
        } else {
            $('#modalSolicitudUsuarioFic').modal('show');
        }
    }

    function closeModal() {
        clearAlert();
        state.currentSolicitud = null;
        clearForm();
        if (state.modal && typeof state.modal.hide === 'function') {
            state.modal.hide();
        } else {
            $('#modalSolicitudUsuarioFic').modal('hide');
        }
    }

    function refrescarTabla() {
        if (!state.table || !state.table.length || !state.table.data('bootstrap.table')) {
            return;
        }
        state.table.bootstrapTable('refresh', { pageNumber: 1, silent: false });
    }

    function cargarSolicitud(idSolicitud, callback) {
        if (!state.detailUrl || !idSolicitud) return;
        $.getJSON(state.detailUrl, { id_solicitud_usuario: idSolicitud })
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

    function normalize(value, upper) {
        var text = String(value ?? '').trim();
        return upper ? text.toUpperCase() : text.toLowerCase();
    }

    window.ficSolicitudEstadoFormatter = function (value) {
        return badgeEstado(value);
    };

    window.ficSolicitudAccionesFormatter = function (value, row) {
        if (!row) return '';
        var buttons = [];
        buttons.push('<button type="button" class="btn btn-outline-info btn-sm js-fic-ver-solicitud" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Ver"><i class="mdi mdi-eye"></i></button>');
        if (String(row.estatus || '').toLowerCase() === 'pendiente') {
            buttons.push('<button type="button" class="btn btn-outline-warning btn-sm js-fic-cancelar-solicitud" data-id-solicitud="' + esc(row.id_solicitud_usuario || '') + '" title="Cancelar"><i class="mdi mdi-close"></i></button>');
        }
        return '<div class="usuario-actions">' + buttons.join('') + '</div>';
    };

    return {
        iniciar: function () {
            var root = $('#usuariosPage');
            if (!root.length) return;

            state.root = root;
            state.listUrl = root.data('solicitudes-url') || '';
            state.detailUrl = root.data('solicitud-detail-url') || '';
            state.saveUrl = root.data('solicitud-save-url') || '';
            state.cancelUrl = root.data('solicitud-cancel-url') || '';
            state.form = $('#formSolicitudUsuarioFic');
            state.table = $('#tablaSolicitudesFic');
            var modalEl = document.getElementById('modalSolicitudUsuarioFic');
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
                    Swal.fire('Error', 'No fue posible cargar las solicitudes FIC.', 'error');
                }
            });
        },

        bindEvents: function () {
            var self = this;

            $(document)
                .off('click.ficSolicitud')
                .on('click.ficSolicitud', '#btnNuevaSolicitudUsuarioFic, #btnAbrirSolicitudUsuarioFic', function () {
                    clearForm();
                    clearAlert();
                    if (state.modal && typeof state.modal.show === 'function') {
                        state.modal.show();
                    } else {
                        $('#modalSolicitudUsuarioFic').modal('show');
                    }
                })
                .on('click.ficSolicitud', '.js-fic-ver-solicitud', function () {
                    var idSolicitud = Number($(this).data('id-solicitud') || 0);
                    cargarSolicitud(idSolicitud, function (data) {
                        Swal.fire({
                            title: 'Solicitud FIC',
                            html: '<div class="text-start"><strong>Perfil:</strong> ' + esc(data.perfil_solicitado || '') + '<br><strong>Usuario:</strong> ' + esc(data.usuario || '') + '<br><strong>Nombre:</strong> ' + esc(data.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(data.correo || '') + '<br><strong>Estatus:</strong> ' + esc(data.estatus || '') + '</div>',
                            confirmButtonText: 'Cerrar'
                        });
                    });
                })
                .on('click.ficSolicitud', '.js-fic-cancelar-solicitud', function () {
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
                .off('submit.ficSolicitud')
                .on('submit.ficSolicitud', function (event) {
                    event.preventDefault();
                    if (!state.saveUrl) return;

                    var payload = {
                        id_perfil_solicitado: Number($('#solicitud_usuario_fic_perfil').val() || 0),
                        usuario: normalize($('#solicitud_usuario_fic_usuario').val(), false),
                        nombre: normalize($('#solicitud_usuario_fic_nombre').val(), true),
                        primer_apellido: normalize($('#solicitud_usuario_fic_primer_apellido').val(), true),
                        segundo_apellido: normalize($('#solicitud_usuario_fic_segundo_apellido').val(), true),
                        correo: normalize($('#solicitud_usuario_fic_correo').val(), false),
                        observaciones: $('#solicitud_usuario_fic_observaciones').val() || ''
                    };
                    payload = $.extend(payload, getCsrfPayload());

                    if (!payload.id_perfil_solicitado || !payload.usuario || !payload.nombre || !payload.primer_apellido) {
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
                        closeModal();
                        refrescarTabla();
                    }).fail(function () {
                        setAlert('No fue posible guardar la solicitud.', 'danger');
                    });
                });

            $(document)
                .off('input.ficSolicitudTransform')
                .on('input.ficSolicitudTransform', '#solicitud_usuario_fic_usuario, #solicitud_usuario_fic_correo', function () {
                    this.value = String(this.value || '').toLowerCase();
                })
                .on('input.ficSolicitudTransform', '#solicitud_usuario_fic_nombre, #solicitud_usuario_fic_primer_apellido, #solicitud_usuario_fic_segundo_apellido', function () {
                    this.value = String(this.value || '').toUpperCase();
                });
        }
    };
})();

$(function () {
    if (typeof solicitudesUsuarioFic !== 'undefined' && typeof solicitudesUsuarioFic.iniciar === 'function') {
        solicitudesUsuarioFic.iniciar();
    }
});
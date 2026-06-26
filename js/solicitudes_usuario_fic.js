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
        currentSolicitud: null,
        catalogos: { categorias: [], disciplinas: [], paises: [] },
        catalogosCargados: false
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

    function populateSelect(selector, items, valueKey, labelKey) {
        var select = $(selector);
        if (!select.length) return;
        var current = select.val();
        select.empty();
        select.append(new Option('Seleccione', '', false, false));
        (items || []).forEach(function (item) {
            select.append(new Option(item[labelKey] || '', item[valueKey] || '', false, false));
        });
        if (current !== undefined && current !== null && current !== '') {
            select.val(String(current));
        }
        select.trigger('change.select2');
    }

    function initCatalogSelects() {
        if (typeof $.fn.select2 !== 'function') return;
        $('#solicitud_fic_categoria, #solicitud_fic_pais, #solicitud_fic_disciplina').each(function () {
            var select = $(this);
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.select2({
                width: '100%',
                dropdownParent: $('#modalSolicitudUsuarioFic'),
                placeholder: 'Seleccione',
                allowClear: true
            });
        });
    }

    function updateClave() {
        var selected = Number($('#solicitud_fic_categoria').val() || 0);
        var categoria = (state.catalogos.categorias || []).find(function (item) {
            return Number(item.id_clave || 0) === selected;
        }) || null;
        $('#solicitud_fic_id_clave').val(categoria ? String(categoria.id_clave || '') : '');
        $('#solicitud_fic_clave').val(categoria ? String(categoria.clave || '') : '');
    }

    function loadCatalogos(callback) {
        if (state.catalogosCargados) {
            if (typeof callback === 'function') callback();
            return;
        }
        $.getJSON(base_url + 'index.php/Usuario/getCatalogosCrud')
            .done(function (response) {
                var data = response && response.data ? response.data : response;
                state.catalogos.categorias = Array.isArray(data && data.categorias) ? data.categorias : [];
                state.catalogos.disciplinas = Array.isArray(data && data.disciplinas) ? data.disciplinas : [];
                state.catalogos.paises = Array.isArray(data && data.paises) ? data.paises : [];
                populateSelect('#solicitud_fic_categoria', state.catalogos.categorias, 'id_clave', 'dsc_clave');
                populateSelect('#solicitud_fic_disciplina', state.catalogos.disciplinas, 'id_diciplina', 'des_diciplina');
                populateSelect('#solicitud_fic_pais', state.catalogos.paises, 'id_pais', 'dsc_pais');
                initCatalogSelects();
                updateClave();
                state.catalogosCargados = true;
                if (typeof callback === 'function') callback();
            })
            .fail(function () {
                Swal.fire('Error', 'No fue posible cargar los catÃ¡logos de la solicitud.', 'error');
            });
    }

    function clearForm() {
        if (state.form && state.form.length && state.form[0]) {
            state.form[0].reset();
        }
        $('#solicitud_usuario_id_fic').val('0');
        $('#solicitud_usuario_establecimiento_fic').val(String(state.root ? state.root.data('solicitud-establecimiento-id') || '' : ''));
        $('#solicitud_fic_clave, #solicitud_fic_folio, #solicitud_fic_subfolio, #solicitud_fic_pax, #solicitud_fic_anfitrion, #solicitud_usuario_fic_nombre, #solicitud_usuario_fic_primer_apellido, #solicitud_usuario_fic_segundo_apellido, #solicitud_usuario_fic_correo, #solicitud_usuario_fic_observaciones').val('');
        $('#solicitud_fic_beneficios').val('ninguno');
        $('#solicitud_fic_categoria, #solicitud_fic_pais, #solicitud_fic_disciplina, #solicitud_usuario_fic_perfil').val('').trigger('change');
        $('#solicitud_fic_id_clave').val('');
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
        $('#solicitud_fic_categoria, #solicitud_fic_pais, #solicitud_fic_disciplina').val('').trigger('change');
        $('#solicitud_fic_id_clave').val('');
        $('#solicitud_fic_clave, #solicitud_fic_folio, #solicitud_fic_subfolio, #solicitud_fic_pax, #solicitud_fic_anfitrion').val('');
        $('#solicitud_fic_beneficios').val('ninguno');
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
                    Swal.fire('AtenciÃ³n', response && response.message ? response.message : 'No fue posible cargar la solicitud.', 'warning');
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

    window.ficSolicitudUsuarioFormatter = function (value) {
        var usuario = String(value || '').trim();
        return usuario !== '' ? esc(usuario) : '<span class="text-muted">Por asignar por TI</span>';
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
            loadCatalogos();
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
            $(document)
                .off('click.ficSolicitud')
                .on('click.ficSolicitud', '.js-fic-ver-solicitud', function () {
                    var idSolicitud = Number($(this).data('id-solicitud') || 0);
                    cargarSolicitud(idSolicitud, function (data) {
                        var comentario = String(data.comentario_ti || '').trim();
                        var detalleExtra = comentario !== ''
                            ? '<hr class="border-secondary my-3"><div><strong>Detalle de solicitud:</strong><pre class="bg-transparent text-light border-0 p-0 m-0" style="white-space:pre-wrap;font-family:inherit;">' + esc(comentario) + '</pre></div>'
                            : '';
                        Swal.fire({
                            title: 'Solicitud FIC',
                            html: '<div class="text-start"><strong>Perfil:</strong> ' + esc(data.perfil_solicitado || '') + '<br><strong>Usuario:</strong> ' + (String(data.usuario || '').trim() !== '' ? esc(data.usuario || '') : 'Por asignar por TI') + '<br><strong>Nombre:</strong> ' + esc(data.nombre_completo || '') + '<br><strong>Correo:</strong> ' + esc(data.correo || '') + '<br><strong>Estatus:</strong> ' + esc(data.estatus || '') + detalleExtra + '</div>',
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
                        id_clave: Number($('#solicitud_fic_id_clave').val() || 0),
                        categoria_label: $('#solicitud_fic_categoria option:selected').text() || '',
                        id_pais: Number($('#solicitud_fic_pais').val() || 0),
                        pais_label: $('#solicitud_fic_pais option:selected').text() || '',
                        id_diciplina: Number($('#solicitud_fic_disciplina').val() || 0),
                        disciplina_label: $('#solicitud_fic_disciplina option:selected').text() || '',
                        clave: String($('#solicitud_fic_clave').val() || '').trim().toLowerCase(),
                        folio: String($('#solicitud_fic_folio').val() || '').replace(/\D/g, '').trim(),
                        sub_folio: normalize($('#solicitud_fic_subfolio').val(), true),
                        pax: Number($('#solicitud_fic_pax').val() || 0),
                        anf_gto: normalize($('#solicitud_fic_anfitrion').val(), true),
                        id_perfil_solicitado: Number($('#solicitud_usuario_fic_perfil').val() || 0),
                        beneficios: String($('#solicitud_fic_beneficios').val() || 'ninguno'),
                        nombre: normalize($('#solicitud_usuario_fic_nombre').val(), true),
                        primer_apellido: normalize($('#solicitud_usuario_fic_primer_apellido').val(), true),
                        segundo_apellido: normalize($('#solicitud_usuario_fic_segundo_apellido').val(), true),
                        correo: normalize($('#solicitud_usuario_fic_correo').val(), false),
                        observaciones: $('#solicitud_usuario_fic_observaciones').val() || ''
                    };
                    payload = $.extend(payload, getCsrfPayload());

                    if (!payload.id_clave || !payload.id_pais || !payload.id_diciplina || !payload.clave || !payload.folio || !payload.sub_folio || payload.pax <= 0 || !payload.anf_gto || !payload.beneficios || !payload.id_perfil_solicitado || !payload.nombre || !payload.primer_apellido) {
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
                .on('change.ficSolicitudCatalogos', '#solicitud_fic_categoria', function () {
                    updateClave();
                })
                .on('input.ficSolicitudTransform', '#solicitud_fic_folio', function () {
                    this.value = String(this.value || '').replace(/\D/g, '');
                })
                .on('input.ficSolicitudTransform', '#solicitud_fic_subfolio, #solicitud_fic_anfitrion', function () {
                    this.value = String(this.value || '').toUpperCase();
                })
                .on('input.ficSolicitudTransform', '#solicitud_usuario_fic_correo', function () {
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

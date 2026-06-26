var solicitudAlta = (function () {
    var state = {
        root: null,
        form: null,
        grupo: '',
        saveUrl: '',
        backUrl: '',
        catalogosUrl: '',
        catalogos: { categorias: [], paises: [], disciplinas: [] },
        catalogosCargados: false
    };

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalize(value, upper) {
        var text = String(value == null ? '' : value).trim();
        if (!text) return '';
        return upper ? text.toUpperCase() : text.toLowerCase();
    }

    function getCsrfPayload() {
        var payload = {};
        if (!state.form || !state.form.length) return payload;
        state.form.find('input[type="hidden"][name]').each(function () {
            if (this.name) payload[this.name] = this.value;
        });
        return payload;
    }

    function setAlert(message, type) {
        var alert = $('#solicitudUsuarioFicAlert, #solicitudUsuarioCatalogoAlert');
        if (!alert.length) return;
        alert.removeClass('d-none alert-info alert-danger alert-success alert-warning');
        alert.addClass('alert-' + (type || 'info')).text(message || '');
    }

    function clearAlert() {
        $('#solicitudUsuarioFicAlert, #solicitudUsuarioCatalogoAlert').addClass('d-none').text('');
    }

    function clearForm() {
        if (!state.form || !state.form.length) return;
        state.form[0].reset();
        if (state.grupo === 'fic') {
            $('#solicitud_usuario_id_fic').val('0');
            $('#solicitud_fic_id_clave').val('');
            $('#solicitud_fic_clave').val('');
            $('#solicitud_fic_beneficios').val('ninguno');
            $('#solicitud_fic_categoria, #solicitud_fic_pais, #solicitud_fic_disciplina').val('').trigger('change');
        } else {
            $('#solicitud_usuario_catalogo_id').val('0');
            $('#solicitud_usuario_catalogo_beneficios').val('ninguno');
            $('#solicitud_usuario_catalogo_perfil').val('');
        }
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
        select.trigger('change');
    }

    function initSelect2() {
        if (typeof $.fn.select2 !== 'function') return;
        var parent = state.root && state.root.length ? state.root : $(document.body);
        $('#solicitud_fic_categoria, #solicitud_fic_pais, #solicitud_fic_disciplina').each(function () {
            var select = $(this);
            if (!select.length) return;
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }
            select.select2({ width: '100%', dropdownParent: parent, placeholder: 'Seleccione', allowClear: true });
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
        if (state.grupo !== 'fic') {
            if (typeof callback === 'function') callback();
            return;
        }
        if (state.catalogosCargados) {
            if (typeof callback === 'function') callback();
            return;
        }
        $.getJSON(state.catalogosUrl)
            .done(function (response) {
                var data = response && response.data ? response.data : response;
                state.catalogos.categorias = Array.isArray(data && data.categorias) ? data.categorias : [];
                state.catalogos.paises = Array.isArray(data && data.paises) ? data.paises : [];
                state.catalogos.disciplinas = Array.isArray(data && data.disciplinas) ? data.disciplinas : [];
                populateSelect('#solicitud_fic_categoria', state.catalogos.categorias, 'id_clave', 'dsc_clave');
                populateSelect('#solicitud_fic_pais', state.catalogos.paises, 'id_pais', 'dsc_pais');
                populateSelect('#solicitud_fic_disciplina', state.catalogos.disciplinas, 'id_diciplina', 'des_diciplina');
                initSelect2();
                updateClave();
                state.catalogosCargados = true;
                if (typeof callback === 'function') callback();
            })
            .fail(function () {
                Swal.fire('Error', 'No fue posible cargar los catálogos de la solicitud.', 'error');
            });
    }

    function bindEvents() {
        if (!state.form || !state.form.length) return;

        state.form.off('submit.solicitudAlta').on('submit.solicitudAlta', function (event) {
            event.preventDefault();
            if (!state.saveUrl) return;

            var payload = {};
            if (state.grupo === 'fic') {
                payload = {
                    id_clave: Number($('#solicitud_fic_id_clave').val() || 0),
                    categoria_label: $('#solicitud_fic_categoria option:selected').text() || '',
                    id_pais: Number($('#solicitud_fic_pais').val() || 0),
                    pais_label: $('#solicitud_fic_pais option:selected').text() || '',
                    id_diciplina: Number($('#solicitud_fic_disciplina').val() || 0),
                    disciplina_label: $('#solicitud_fic_disciplina option:selected').text() || '',
                    clave: normalize($('#solicitud_fic_clave').val(), false),
                    folio: normalize($('#solicitud_fic_folio').val(), false),
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
            } else {
                payload = {
                    id_perfil_solicitado: Number($('#solicitud_usuario_catalogo_perfil').val() || 0),
                    beneficios: String($('#solicitud_usuario_catalogo_beneficios').val() || 'ninguno'),
                    nombre: normalize($('#solicitud_usuario_catalogo_nombre').val(), true),
                    primer_apellido: normalize($('#solicitud_usuario_catalogo_primer_apellido').val(), true),
                    segundo_apellido: normalize($('#solicitud_usuario_catalogo_segundo_apellido').val(), true),
                    correo: normalize($('#solicitud_usuario_catalogo_correo').val(), false),
                    observaciones: $('#solicitud_usuario_catalogo_observaciones').val() || ''
                };
            }

            payload = $.extend(payload, getCsrfPayload());

            if (state.grupo === 'fic') {
                if (!payload.id_clave || !payload.id_pais || !payload.id_diciplina || !payload.clave || !payload.folio || !payload.sub_folio || !payload.pax || !payload.anf_gto || !payload.id_perfil_solicitado || !payload.nombre || !payload.primer_apellido) {
                    setAlert('Completa los campos obligatorios.', 'warning');
                    return;
                }
            } else if (!payload.id_perfil_solicitado || !payload.beneficios || !payload.nombre || !payload.primer_apellido) {
                setAlert('Completa los campos obligatorios.', 'warning');
                return;
            }

            var boton = state.form.find('button[type="submit"]');
            var textoOriginal = boton.html();
            boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando...');

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
                Swal.fire('Listo', response.message || 'Solicitud enviada.', 'success').then(function () {
                    if (state.backUrl) {
                        window.location.href = state.backUrl;
                    }
                });
                clearForm();
                clearAlert();
            }).fail(function () {
                setAlert('No fue posible guardar la solicitud.', 'danger');
            }).always(function () {
                boton.prop('disabled', false).html(textoOriginal);
            });
        });

        if (state.grupo === 'fic') {
            $(document)
                .off('change.solicitudAltaFic')
                .on('change.solicitudAltaFic', '#solicitud_fic_categoria', function () {
                    updateClave();
                })
                .on('input.solicitudAltaFic', '#solicitud_fic_folio', function () {
                    this.value = String(this.value || '').replace(/\D+/g, '');
                })
                .on('input.solicitudAltaFic', '#solicitud_fic_subfolio, #solicitud_fic_anfitrion, #solicitud_usuario_fic_nombre, #solicitud_usuario_fic_primer_apellido, #solicitud_usuario_fic_segundo_apellido', function () {
                    this.value = String(this.value || '').toUpperCase();
                })
                .on('input.solicitudAltaFic', '#solicitud_usuario_fic_correo', function () {
                    this.value = String(this.value || '').toLowerCase();
                });
        } else {
            $(document)
                .off('input.solicitudAltaCatalogo')
                .on('input.solicitudAltaCatalogo', '#solicitud_usuario_catalogo_nombre, #solicitud_usuario_catalogo_primer_apellido, #solicitud_usuario_catalogo_segundo_apellido', function () {
                    this.value = String(this.value || '').toUpperCase();
                })
                .on('input.solicitudAltaCatalogo', '#solicitud_usuario_catalogo_correo', function () {
                    this.value = String(this.value || '').toLowerCase();
                });
        }
    }

    return {
        iniciar: function () {
            state.root = $('#solicitudAltaPage');
            if (!state.root.length) return;

            state.form = state.root.find('form').first();
            state.grupo = String(state.root.data('grupo') || '').toLowerCase();
            state.saveUrl = String(state.root.data('save-url') || '');
            state.backUrl = String(state.root.data('back-url') || '');
            state.catalogosUrl = String(state.root.data('catalogos-url') || '');

            clearForm();
            loadCatalogos(function () {
                bindEvents();
            });
            if (state.grupo !== 'fic') {
                bindEvents();
            }
        }
    };
})();

$(function () {
    if (typeof solicitudAlta !== 'undefined' && typeof solicitudAlta.iniciar === 'function') {
        solicitudAlta.iniciar();
    }
});
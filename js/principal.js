var saeg = window.ssa || {};

saeg.principal = (function () {
    return {
        cargar_documento: function () {
            $("#frmDocumento").submit(function (event) {
                event.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: base_url + '/index.php/Principal/SubiendoDocumento',
                    type: "post",
                    dataType: "html",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        if (response == 'correcto') {
                            Swal.fire("", "Se agreg\u00f3 correctamente el logotipo", "success");
                            location.reload();
                        } else {
                            Swal.fire("Error", response, "warning");
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        alert('Error');
                        console.log('error:' + textStatus, errorThrown);
                    }
                });
                event.preventDefault();
                event.stopImmediatePropagation();
            });
        },

        alimentos: function (value, row) {
            var accion = '';
            if (row.tiene_alimentos == 1) accion += '<span class="badge bg-success">S\u00ed</span>';
            if (row.tiene_alimentos == 0) accion += '<span class="badge bg-danger">No</span>';
            if (row.tiene_alimentos == '') accion += '<span class="badge bg-danger">Pendiente</span>';
            return accion;
        },

        login: function () {
            var usuario = $('#usuario').val();
            var contrasenia = $('#contrasenia').val();

            if (!usuario || !contrasenia) {
                Swal.fire("Atenci\u00f3n", "Favor de capturar usuario y contrase\u00f1a.", "error");
                return;
            }

            $('#btn_login').hide();
            $('#btn_load').show();
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Login/validar_usuario",
                data: { usuario: usuario, contrasenia: contrasenia },
                dataType: "json",
                success: function (response) {
                    if (!response.error) {
                        Swal.fire("Bienvenido!", "Ingresando...", "success");
                        window.location.href = base_url + "index.php/Inicio";
                    } else {
                        Swal.fire("Usuario incorrecto!", "Favor de verificar sus credenciales de acceso", "error");
                    }
                },
                complete: function () {
                    $('#btn_login').show();
                    $('#btn_load').hide();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    Swal.fire("Error!", textStatus, "error");
                    console.log('Error:', textStatus, errorThrown);
                }
            });
        },

        activo: function (value, row) {
            var activo = value;
            if (activo === undefined && row && row.activo_qr !== undefined) {
                activo = row.activo_qr;
            }
            if (Number(activo) === 1) {
                return '<span class="badge bg-success">S\u00ed</span>';
            }

            return '<span class="badge bg-danger">No</span>';
        },

        fecha: function (value) {
            if (!value) return '';
            var fecha = new Date(value);
            var dia = String(fecha.getDate()).padStart(2, '0');
            var mes = String(fecha.getMonth() + 1).padStart(2, '0');
            var anio = fecha.getFullYear();
            var hora = String(fecha.getHours()).padStart(2, '0');
            var minuto = String(fecha.getMinutes()).padStart(2, '0');
            return dia + '/' + mes + '/' + anio + ' ' + hora + ':' + minuto;
        }
    };
})();

window.cajeros = {
    modal: null,
    idPerfil: null,
    isAltaPage: false,
    isProviderMode: false,
    listUrl: '',
    altaUrl: '',
    providerSelection: null,
    contexto: {},
    roleOptions: {},
    catalogos: {
        categorias: [],
        disciplinas: [],
        paises: [],
        perfiles: [],
        tarifas: [],
        estados: [],
        partidas: [],
        hotel_tarifas: [],
        tipos_habitacion: [],
        establecimientos: [],
        proveedores: []
    },

    iniciar: function () {
        var pagina = document.getElementById('usuariosPage');
        var altaPagina = document.getElementById('altaUsuarioPage');
        var proveedorPagina = document.getElementById('proveedorPage');
        if (!pagina && !altaPagina && !proveedorPagina) return;

        this.isAltaPage = !!altaPagina;
        this.isProviderDashboard = !!proveedorPagina;

        if (this.isProviderDashboard) {
            this.inicializarProveedorDashboard();
            return;
        }

        var contenedor = altaPagina || pagina;

        this.idPerfil = Number(contenedor.dataset.idPerfil);
        this.contexto = this.parseJSON(contenedor.dataset.catalogContext, {});
        this.roleOptions = this.parseJSON(contenedor.dataset.roleOptions, {});
        this.listUrl = contenedor.dataset.listUrl || (base_url + 'index.php/Inicio/Usuarios');
        this.altaUrl = contenedor.dataset.altaUrl || (base_url + 'index.php/Inicio/AltaUsuario');
        this.isProviderMode = this.isAltaPage && String(contenedor.dataset.providerMode || '') === '1';

        if (this.isAltaPage) {
            this.inicializarSelect2();

            var idUsuario = Number(altaPagina.dataset.idUsuario || 0);

            if (this.isProviderMode) {
                this.inicializarFlujoProveedor();

                if (idUsuario > 0) {
                    this.cargarUsuario(idUsuario);
                } else {
                    this.prepararNuevoProveedorFormulario();
                }

                $('#formAltaProveedorFic').on('submit', function (event) {
                    event.preventDefault();
                    cajeros.guardarProveedorFic();
                });

                return;
            }

            this.cargarCatalogosBase(function () {
                if (idUsuario > 0) {
                    cajeros.cargarUsuario(idUsuario);
                } else {
                    cajeros.prepararNuevoFormulario();
                }
            });

            $('#categoria_ui').on('change', this.onCategoriaChange.bind(this));
            $('#id_perfil_catalogo').on('change', this.onPerfilBaseChange.bind(this));
            $('#id_pais').on('change', this.onPaisChange.bind(this));
            $('#perfil_grupo').on('change', this.actualizarFlujoBeneficios.bind(this));
            $('#tiene_alimentos, #tiene_hospedaje').on('change', this.actualizarFlujoBeneficios.bind(this));
            $('#id_nivel_cliente, #fecha_check_in, #fecha_check_out').on('change', this.actualizarCalculoAlimentos.bind(this));
            $('#id_establecimiento_hotel, #id_tipo_habitacion, #fec_vigencia_desde, #fec_vigencia_hasta').on('change', this.actualizarCalculoHospedaje.bind(this));
            $('#folio_ui').on('input', this.normalizarFolio.bind(this));
            $('#subf_ui, #anf_gto_ui').on('input', this.normalizarSoloLetrasMayusculas.bind(this));

            $('#cajeroForm').on('submit', function (event) {
                event.preventDefault();
                cajeros.guardar();
            });

            return;
        }

        var usuariosUrl = contenedor.dataset.usuariosUrl || (base_url + 'index.php/Usuario/getVistaUsuario');

        $('#cajerosTable').bootstrapTable({
            url: usuariosUrl,
            responseHandler: function (response) {
                if (Array.isArray(response)) return response;
                console.error('Respuesta inválida al cargar usuarios:', response);
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar usuarios:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los usuarios.', 'error');
            }
        });

    },

    inicializarProveedorDashboard: function () {
        var pagina = $('#proveedorPage');
        if (!pagina.length) return;

        this.inicializarSelect2();
        $('.crud-ui-upper').off('input.proveedor').on('input.proveedor', this.normalizarMayusculas.bind(this));
        $('.crud-ui-lower').off('input.proveedor').on('input.proveedor', this.normalizarMinusculas.bind(this));

        $('#modalSolicitudPersonal')
            .off('show.bs.modal.proveedor hidden.bs.modal.proveedor')
            .on('show.bs.modal.proveedor', function () {
                cajeros.cargarEstablecimientosSolicitudProveedor();
            })
            .on('hidden.bs.modal.proveedor', function () {
                cajeros.limpiarSolicitudProveedor();
            });

        $('#solicitud_establecimiento')
            .off('change.proveedor')
            .on('change.proveedor', this.actualizarTipoSolicitudProveedor.bind(this));

        $('#formSolicitudProveedor')
            .off('submit.proveedor')
            .on('submit.proveedor', function (event) {
                event.preventDefault();
                cajeros.enviarSolicitudProveedor();
            });

        this.cargarEstablecimientosSolicitudProveedor();
    },

    cargarEstablecimientosSolicitudProveedor: function () {
        var select = $('#solicitud_establecimiento');
        var tipoInput = $('#solicitud_tipo_usuario');
        var boton = $('#btnEnviarSolicitudProveedor');
        var url = $('#proveedorPage').data('establecimientos-url') || '';

        if (!select.length || !url) return;

        select.prop('disabled', true).empty().append(new Option('Cargando establecimientos...', '', false, false));
        tipoInput.val('');
        boton.prop('disabled', true);

        $.getJSON(url).done(function (response) {
            var items = response && Array.isArray(response.establecimientos) ? response.establecimientos : [];
            select.empty();
            select.append(new Option('Selecciona un establecimiento', '', false, false));

            if (!response || response.ok !== true || !items.length) {
                var mensaje = (response && response.message) ? response.message : 'No hay establecimientos ligados a este proveedor.';
                select.empty().append(new Option(mensaje, '', false, false));
                tipoInput.val(mensaje);
                select.prop('disabled', true).trigger('change.select2');
                boton.prop('disabled', true);
                return;
            }

            items.forEach(function (item) {
                var label = String(item.dsc_establecimiento || '').trim();
                var tipo = String(item.dsc_tipo || '').trim();
                var texto = label + (tipo ? ' - ' + tipo : '');
                var option = new Option(texto, String(item.id_establecimiento || ''), false, false);
                option.setAttribute('data-id-tipo', String(item.id_tipo || ''));
                option.setAttribute('data-dsc-tipo', tipo);
                select.append(option);
            });

            select.prop('disabled', false).trigger('change.select2');
        }).fail(function () {
            var mensaje = 'No fue posible cargar los establecimientos.';
            select.empty().append(new Option(mensaje, '', false, false));
            tipoInput.val(mensaje);
            select.prop('disabled', true).trigger('change.select2');
            boton.prop('disabled', true);
        }).always(function () {
            cajeros.actualizarTipoSolicitudProveedor();
        });
    },

    actualizarTipoSolicitudProveedor: function () {
        var select = $('#solicitud_establecimiento');
        var tipoInput = $('#solicitud_tipo_usuario');
        var boton = $('#btnEnviarSolicitudProveedor');
        var option = select.find('option:selected').get(0);
        var idTipo = option ? Number(option.getAttribute('data-id-tipo') || 0) : 0;
        var label = '';

        if (idTipo === 1) {
            label = 'GERENTE';
        } else if (idTipo === 2) {
            label = 'RECEPCIÓN';
        }

        tipoInput.val(label);
        boton.prop('disabled', label === '');
    },

    limpiarSolicitudProveedor: function () {
        var form = $('#formSolicitudProveedor')[0];
        if (form) {
            form.reset();
        }
        $('#solicitud_establecimiento').val('').trigger('change.select2');
        $('#solicitud_tipo_usuario').val('');
        $('#btnEnviarSolicitudProveedor').prop('disabled', true);
    },

    enviarSolicitudProveedor: function () {
        var boton = $('#btnEnviarSolicitudProveedor');
        var form = $('#formSolicitudProveedor');
        var textoOriginal = boton.html();
        var establecimiento = $('#solicitud_establecimiento').find('option:selected').get(0);
        var idTipo = establecimiento ? Number(establecimiento.getAttribute('data-id-tipo') || 0) : 0;

        if (idTipo !== 1 && idTipo !== 2) {
            Swal.fire('Atención', 'Selecciona un establecimiento válido.', 'warning');
            return;
        }

        $('#solicitud_nombre').val(String($('#solicitud_nombre').val() || '').toUpperCase());
        $('#solicitud_primer_apellido').val(String($('#solicitud_primer_apellido').val() || '').toUpperCase());
        $('#solicitud_segundo_apellido').val(String($('#solicitud_segundo_apellido').val() || '').toUpperCase());
        $('#solicitud_correo').val(String($('#solicitud_correo').val() || '').toLowerCase());

        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando...');

        $.ajax({
            url: $('#proveedorPage').data('solicitud-url') || '',
            type: 'POST',
            dataType: 'json',
            data: form.serialize()
        }).done(function (response) {
            if (!response || response.ok !== true) {
                Swal.fire('Atención', (response && (response.message || response.respuesta)) ? (response.message || response.respuesta) : 'No fue posible enviar la solicitud.', 'warning');
                return;
            }

            Swal.fire('Correcto', response.message || 'Solicitud enviada correctamente.', 'success').then(function () {
                $('#modalSolicitudPersonal').modal('hide');
                cajeros.limpiarSolicitudProveedor();
            });
        }).fail(function (jqXHR) {
            var message = 'No fue posible enviar la solicitud.';
            if (jqXHR && jqXHR.responseJSON && (jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta)) {
                message = jqXHR.responseJSON.message || jqXHR.responseJSON.respuesta;
            }
            Swal.fire('Error', message, 'error');
        }).always(function () {
            boton.prop('disabled', false).html(textoOriginal);
            cajeros.actualizarTipoSolicitudProveedor();
        });
    },
    parseJSON: function (value, fallback) {
        if (!value) return fallback;
        try {
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
    },

    normalizarFolio: function (event) {
        var input = event && event.target ? event.target : document.getElementById('folio_ui');
        if (!input) return;
        input.value = String(input.value || '').replace(/\D+/g, '');
    },

    normalizarSoloLetrasMayusculas: function (event) {
        var input = event && event.target ? event.target : null;
        if (!input) return;
        input.value = String(input.value || '')
            .replace(/[^\p{L}\s]/gu, '')
            .toUpperCase();
    },

    inicializarSelect2: function () {
        if (typeof $.fn.select2 !== 'function') {
            return;
        }

        $('.js-select2-catalog').each(function () {
            var select = $(this);
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                width: '100%',
                dropdownParent: select.closest('.modal').length ? select.closest('.modal') : ($('#altaUsuarioPage').length ? $('#altaUsuarioPage') : $(document.body)),
                placeholder: select.data('placeholder') || 'Seleccione',
                allowClear: true
            });
        });
    },

    cargarCatalogosBase: function (callback) {
        $.getJSON(base_url + 'index.php/Usuario/getCatalogosCrud', function (response) {
            var data = response && response.data ? response.data : response;
            cajeros.catalogos = $.extend(true, {}, cajeros.catalogos, data || {});
            cajeros.poblarSelect('#categoria_ui', cajeros.catalogos.categorias, 'id_clave', 'dsc_clave');
            cajeros.poblarSelect('#disciplina_ui', cajeros.catalogos.disciplinas, 'id_diciplina', 'des_diciplina', function (item) {
                return $.trim(item.des_diciplina || '');
            });
            cajeros.poblarSelect('#id_pais', cajeros.catalogos.paises, 'id_pais', 'dsc_pais');
            cajeros.poblarSelect('#id_estado', cajeros.catalogos.estados, 'id_estado', 'dsc_estado');
            cajeros.actualizarEstadoPais();
            cajeros.poblarSelect('#id_perfil_catalogo', cajeros.catalogos.perfiles, 'id_perfil', 'dsc_perfil', function (item) {
                var shortLabels = {
                    4: 'SECTURI',
                    8: 'SECUL',
                    9: 'FIC',
                    10: 'UG'
                };
                return shortLabels[Number(item.id_perfil)] || item.dsc_perfil || '';
            });
            cajeros.poblarSelect('#id_nivel_cliente', cajeros.catalogos.tarifas, 'id_nivel_cliente', 'dsc_nivel_cliente');
            cajeros.poblarSelect('#id_establecimiento', cajeros.catalogos.establecimientos, 'id_establecimiento', 'dsc_establecimiento');
            cajeros.poblarSelect('#proveedor_catalogo', cajeros.catalogos.proveedores, 'id_proveedor', 'search_label');
            if (!cajeros.isProviderMode) {
                cajeros.aplicarPerfilPorContexto();
                cajeros.actualizarFlujoBeneficios();
            }
            if (typeof callback === 'function') {
                callback();
            }
        }).fail(function () {
            Swal.fire('Error', 'No fue posible cargar los cat\u00e1logos del formulario.', 'error');
        });
    },
    poblarSelect: function (selector, items, valueKey, labelKey, formatter) {
        var select = $(selector);
        if (!select.length) return;

        var valorActual = select.val();
        select.empty();
        select.append(new Option('Seleccione', '', false, false));

        (items || []).forEach(function (item) {
            var label = typeof formatter === 'function'
                ? formatter(item)
                : (item[labelKey] || item.descripcion || item.nombre || item[valueKey] || '');
            select.append(new Option(label, item[valueKey], false, false));
        });

        if (valorActual !== undefined && valorActual !== null && valorActual !== '') {
            select.val(String(valorActual));
        }
        select.trigger('change.select2');
    },

    inicializarFlujoProveedor: function () {
        var select = $('#proveedor_catalogo');
        var dropdownParent = $('#altaUsuarioPage').length ? $('#altaUsuarioPage') : $(document.body);

        if (typeof $.fn.select2 === 'function') {
            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                width: '100%',
                dropdownParent: dropdownParent,
                placeholder: select.data('placeholder') || 'Seleccione',
                allowClear: true,
                ajax: {
                    url: base_url + 'index.php/Inicio/buscarProveedoresPadronFic',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: $.trim(params.term || '')
                        };
                    },
                    processResults: function (response) {
                        return response && Array.isArray(response.results)
                            ? response
                            : { results: [] };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });
        }

        select
            .off('select2:select.proveedor select2:clear.proveedor change.proveedor')
            .on('select2:select.proveedor', this.onProveedorSelected.bind(this))
            .on('select2:clear.proveedor', this.limpiarProveedorSeleccionado.bind(this))
            .on('change.proveedor', this.onProveedorChange.bind(this));
        $('#usuario, #correo').on('input', this.normalizarMinusculas.bind(this));
    },

    normalizarTextoSinAcentos: function (value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, "")
            .toUpperCase();
    },

    esMexicoSeleccionado: function () {
        var texto = $('#id_pais option:selected').text() || '';
        return this.normalizarTextoSinAcentos(texto).indexOf('MEXICO') !== -1;
    },

    onPaisChange: function () {
        this.actualizarEstadoPais();
    },

    actualizarEstadoPais: function () {
        var wrapper = $('.estado-field');
        var select = $('#id_estado');
        var esMexico = this.esMexicoSeleccionado();

        if (esMexico) {
            wrapper.removeClass('d-none');
            select.prop("disabled", false);
            if (select.find('option').length <= 1) {
                this.poblarSelect('#id_estado', this.catalogos.estados, 'id_estado', 'dsc_estado');
            }
        } else {
            select.val('').trigger('change.select2');
            select.prop("disabled", true);
            wrapper.addClass('d-none');
        }
    },

    normalizarMinusculas: function (event) {
        var input = event && event.target ? event.target : null;
        if (!input) return;
        input.value = String(input.value || '').toLowerCase();
    },

    normalizarMayusculas: function (event) {
        var input = event && event.target ? event.target : null;
        if (!input) return;
        input.value = String(input.value || '').toUpperCase();
    },

    prepararNuevoProveedorFormulario: function () {
        var form = document.getElementById('formAltaProveedorFic');
        if (form) {
            form.reset();
        }
        this.providerSelection = null;
        $('#id_usuario').val('');
        $('#id_proveedor').val('');
        $('#id_establecimiento').val('');
        $('#id_tipo_proveedor').val('1');
        $('#no_proveedor_padron').val('');
        $('#proveedor_catalogo').val('').trigger('change.select2');
        $('#nombre').val('');
        $('#contrasenia').prop('required', true);
        this.renderProviderEstablishments([]);
        this.aplicarModoFormulario(false);
    },

    onProveedorChange: function () {
        var selectedId = String($('#proveedor_catalogo').val() || '');
        if (selectedId === '') {
            this.limpiarProveedorSeleccionado();
            return;
        }

        if (this.providerSelection && String(this.providerSelection.id_proveedor || '') === selectedId) {
            this.aplicarProveedorSeleccionado(this.providerSelection);
        }
    },

    llenarProveedorFormulario: function (data) {
        $('#id_usuario').val(data.id_usuario);
        $('#usuario').val(data.usuario || '');
        $('#correo').val(data.correo || '');
        $('#contrasenia').val('').prop('required', false);
        $('#nombre').val(data.nombre || '');

        if (data.id_proveedor) {
            var optionText = data.proveedor_option_text || data.nombre || '';
            var option = new Option(optionText, String(data.id_proveedor), true, true);
            $('#proveedor_catalogo').append(option).trigger('change');
            this.providerSelection = {
                id_proveedor: data.id_proveedor,
                id_tipo_proveedor: data.id_tipo_proveedor || 1,
                razon_social: data.nombre || '',
                no_proveedor: data.no_proveedor_padron || '',
                establecimientos: data.establecimientos_relacionados || [],
                establecimiento_nombres_ui: data.establecimiento_nombre_ui || '',
                tipo_establecimiento_ui: data.tipo_establecimiento_ui || '',
                id_establecimiento_principal: data.id_establecimiento || ''
            };
            this.aplicarProveedorSeleccionado(this.providerSelection);
        } else {
            $('#id_establecimiento').val(data.id_establecimiento || '');
            this.renderProviderEstablishments(data.establecimientos_relacionados || [], data.establecimiento_nombre_ui || '');
        }

        var soloConsulta = Number(data.permiso_editar || 0) !== 1;
        this.aplicarModoFormulario(soloConsulta);
        $('#cajeroPageTitle').text(soloConsulta ? 'Consultar proveedor' : 'Editar proveedor');
    },

    onProveedorSelected: function (event) {
        var selected = event && event.params ? event.params.data : null;
        var idProveedor = Number(selected && selected.id_proveedor ? selected.id_proveedor : (selected ? selected.id : 0));
        if (idProveedor <= 0) {
            this.limpiarProveedorSeleccionado();
            return;
        }

        var baseData = {
            id_proveedor: idProveedor,
            id_tipo_proveedor: selected && selected.id_tipo_proveedor ? selected.id_tipo_proveedor : 1,
            no_proveedor: selected && selected.no_proveedor ? selected.no_proveedor : '',
            razon_social: selected && selected.razon_social ? selected.razon_social : '',
            text: selected && selected.text ? selected.text : ''
        };

        this.consultarProveedorSeleccionado(baseData);
    },

    consultarProveedorSeleccionado: function (baseData) {
        $.ajax({
            url: base_url + 'index.php/Inicio/getProveedorPadronFic',
            type: 'GET',
            dataType: 'json',
            data: {
                id_proveedor: baseData.id_proveedor
            }
        }).done(function (response) {
            if (!response || response.ok !== true) {
                Swal.fire('Atención', response && response.message ? response.message : 'No fue posible cargar el proveedor.', 'warning');
                cajeros.limpiarProveedorSeleccionado();
                return;
            }

            var proveedor = response.proveedor || {};
            cajeros.providerSelection = {
                id_proveedor: proveedor.id_proveedor || baseData.id_proveedor,
                id_tipo_proveedor: proveedor.id_tipo_proveedor || baseData.id_tipo_proveedor || 1,
                no_proveedor: proveedor.no_proveedor || baseData.no_proveedor || '',
                razon_social: proveedor.razon_social || baseData.razon_social || '',
                text: baseData.text || '',
                establecimientos: Array.isArray(response.establecimientos) ? response.establecimientos : [],
                mensaje_establecimientos_vacio: response.message || ''
            };
            cajeros.aplicarProveedorSeleccionado(cajeros.providerSelection);
        }).fail(function () {
            Swal.fire('Error', 'No fue posible consultar la informacion del proveedor.', 'error');
            cajeros.limpiarProveedorSeleccionado();
        });
    },

    aplicarProveedorSeleccionado: function (selection) {
        selection = selection || {};

        var establecimientos = Array.isArray(selection.establecimientos) ? selection.establecimientos : [];
        var principal = establecimientos.length ? establecimientos[0] : null;
        var nombres = selection.establecimiento_nombres_ui || establecimientos.map(function (item) {
            return item.dsc_establecimiento || '';
        }).filter(Boolean).join(', ');
        var tipos = selection.tipo_establecimiento_ui || this.obtenerTiposProveedorUI(establecimientos);
        var mensajeVacio = selection.mensaje_establecimientos_vacio || 'No hay establecimientos ligados a este proveedor.';

        if (!nombres) {
            nombres = mensajeVacio;
        }

        $('#id_proveedor').val(selection.id_proveedor || '');
        $('#id_tipo_proveedor').val(selection.id_tipo_proveedor || '1');
        $('#no_proveedor_padron').val(selection.no_proveedor || '');
        $('#id_establecimiento').val(selection.id_establecimiento_principal || (principal ? (principal.id_establecimiento || '') : ''));
        $('#nombre').val(selection.razon_social || '');
        this.renderProviderEstablishments(establecimientos, nombres, tipos);
    },

    renderProviderEstablishments: function (establecimientos, fallbackNombres) {
        var contenedor = $('#proveedorEstablecimientosList');
        if (!contenedor.length) {
            return;
        }

        establecimientos = Array.isArray(establecimientos) ? establecimientos : [];
        contenedor.empty();

        if (!establecimientos.length) {
            var mensaje = fallbackNombres || 'No hay establecimientos ligados a este proveedor.';
            contenedor.append([
                '<div class="col-12">',
                '  <label class="form-label">Establecimiento</label>',
                '  <input type="text" class="form-control crud-ui-upper" readonly value="' + this.escapeHtml(mensaje) + '">',
                '</div>'
            ].join(''));
            return;
        }

        establecimientos.forEach(function (item, index) {
            var nombre = $.trim((item && item.dsc_establecimiento) || '');
            var tipo = $.trim((item && item.dsc_tipo) || '');
            var sufijo = index === 0 ? '' : ' ' + (index + 1);

            contenedor.append([
                '<div class="col-md-6">',
                '  <label class="form-label">Establecimiento' + cajeros.escapeHtml(sufijo) + '</label>',
                '  <input type="text" class="form-control crud-ui-upper" readonly value="' + cajeros.escapeHtml(nombre) + '">',
                '</div>',
                '<div class="col-md-6">',
                '  <label class="form-label">Tipo establecimiento' + cajeros.escapeHtml(sufijo) + '</label>',
                '  <input type="text" class="form-control crud-ui-upper" readonly value="' + cajeros.escapeHtml(tipo) + '">',
                '</div>'
            ].join(''));
        });
    },

    escapeHtml: function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    limpiarProveedorSeleccionado: function () {
        this.providerSelection = null;
        $('#id_proveedor').val('');
        $('#id_tipo_proveedor').val('1');
        $('#no_proveedor_padron').val('');
        $('#id_establecimiento').val('');
        $('#nombre').val('');
        this.renderProviderEstablishments([]);
    },

    obtenerTiposProveedorUI: function (establecimientos) {
        var index = {};
        (establecimientos || []).forEach(function (item) {
            var key = $.trim(item.dsc_tipo || '');
            if (key !== '') {
                index[key] = true;
            }
        });

        return Object.keys(index).join(', ');
    },

    aplicarPerfilPorContexto: function () {
        if (this.contexto.is_ti_master) {
            this.onPerfilBaseChange();
            return;
        }

        var perfilPorGrupo = {
            fic: 9,
            secul: 8,
            ug: 10,
            secturi: 4
        };
        var perfilBase = perfilPorGrupo[this.contexto.active_group] || '';
        if (perfilBase) {
            $('#id_perfil_catalogo').val(String(perfilBase)).trigger('change.select2');
        }
        this.onPerfilBaseChange();
    },

    getEstablecimientoPorPerfil: function (perfilId) {
        var mapping = {
            4: '85',
            8: '89',
            9: '90',
            10: '91'
        };
        return mapping[String(perfilId || '')] || '';
    },

    getPerfilBasePorGrupo: function (groupKey) {
        var mapping = {
            secturi: '4',
            secul: '8',
            fic: '9',
            ug: '10'
        };
        return mapping[String(groupKey || '')] || '';
    },

    mapPerfil: function (idPerfil) {
        idPerfil = Number(idPerfil || 0);
        var defaultMap = { group: '', allowVisibleRole: false };
        var mapping = {
            1: { group: 'secturi', allowVisibleRole: true },
            3: { group: '', allowVisibleRole: false },
            4: { group: 'secturi', allowVisibleRole: true },
            6: { group: 'secturi', allowVisibleRole: true },
            8: { group: 'secul', allowVisibleRole: true },
            9: { group: 'fic', allowVisibleRole: true },
            10: { group: 'ug', allowVisibleRole: true }
        };
        return mapping[idPerfil] || defaultMap;
    },

    onCategoriaChange: function () {
        var categoria = this.buscarPorId(this.catalogos.categorias, 'id_clave', $('#categoria_ui').val());
        $('#id_clave').val(categoria ? (categoria.id_clave || '') : '');
        $('#clave_ui').val(categoria ? (categoria.clave || '') : '');
    },

    onPerfilBaseChange: function (perfilVisibleSeleccionado) {
        var selectedProfile = $('#id_perfil_catalogo').val();
        var mapping = this.mapPerfil(selectedProfile);
        var perfilVisible = $('#perfil_grupo');

        $('#grupo_usuario').val(mapping.group || '');

        perfilVisible.empty();
        perfilVisible.append(new Option('Seleccione', '', false, false));

        if (mapping.allowVisibleRole && mapping.group && this.roleOptions[mapping.group]) {
            Object.keys(this.roleOptions[mapping.group].roles).forEach(function (roleId) {
                perfilVisible.append(new Option(cajeros.roleOptions[mapping.group].roles[roleId], roleId, false, false));
            });
            perfilVisible.prop('disabled', false);
        } else {
            perfilVisible.prop('disabled', true).val('');
        }

        if (perfilVisibleSeleccionado !== undefined && perfilVisibleSeleccionado !== null && perfilVisibleSeleccionado !== '') {
            perfilVisible.val(String(perfilVisibleSeleccionado));
        }
        perfilVisible.trigger('change.select2');

        this.aplicarEstablecimientoPorPerfil(selectedProfile);
        this.actualizarFlujoBeneficios();
    },

    esPerfilCliente: function (idPerfil) {
        var perfilId = Number(idPerfil || $('#id_perfil_catalogo').val() || 0);
        if (perfilId === 3) {
            return true;
        }
        var perfilTexto = $('#id_perfil_catalogo option:selected').text() || '';
        return perfilTexto.trim().toUpperCase() === 'CLIENTE';
    },

    esRolVisibleCliente: function () {
        var perfilTexto = $('#perfil_grupo option:selected').text() || '';
        return perfilTexto.trim().toUpperCase() === 'CLIENTE';
    },

    esProveedorLike: function () {
        return ($('#grupo_usuario').val() || '') === 'proveedor';
    },

    aplicarEstablecimientoPorPerfil: function (perfilId) {
        var idEstablecimiento = this.getEstablecimientoPorPerfil(perfilId || $('#id_perfil_catalogo').val());
        if (idEstablecimiento) {
            $('#id_establecimiento').val(String(idEstablecimiento)).trigger('change.select2');
        }
    },

    buscarPorId: function (items, key, value) {
        var match = null;
        (items || []).some(function (item) {
            if (String(item[key]) === String(value)) {
                match = item;
                return true;
            }
            return false;
        });
        return match;
    },

    obtenerLabelPartida: function (idPartida) {
        var partida = this.buscarPorId(this.catalogos.partidas, 'id_partida', idPartida);
        if (!partida) return '';
        var descripcion = partida.des_partida ? ' - ' + partida.des_partida : '';
        return (partida.partida || '') + descripcion;
    },

    obtenerGrupoInstitucional: function () {
        var selectedProfile = $('#id_perfil_catalogo').val();
        var mapping = this.mapPerfil(selectedProfile);
        return mapping.group || this.contexto.active_group || '';
    },

    obtenerMontoCatalogo: function (item) {
        if (!item) return 0;

        var preferredKeys = ['monto_diario', 'tarifa_diaria', 'tarifa', 'precio', 'costo', 'importe', 'monto', 'valor'];
        for (var i = 0; i < preferredKeys.length; i += 1) {
            var preferredValue = item[preferredKeys[i]];
            if (preferredValue !== undefined && preferredValue !== null && preferredValue !== '' && !isNaN(Number(preferredValue))) {
                return Number(preferredValue);
            }
        }

        var keys = Object.keys(item);
        for (var j = 0; j < keys.length; j += 1) {
            var key = keys[j];
            var value = item[key];
            if (/(monto|tarifa|precio|costo|importe|valor)/i.test(key) && value !== undefined && value !== null && value !== '' && !isNaN(Number(value))) {
                return Number(value);
            }
        }

        return 0;
    },

    calcularDiasVigencia: function (fechaInicio, fechaFin, inclusive) {
        if (!fechaInicio || !fechaFin) {
            return 0;
        }

        var inicio = new Date(fechaInicio + 'T00:00:00');
        var fin = new Date(fechaFin + 'T00:00:00');
        if (isNaN(inicio.getTime()) || isNaN(fin.getTime()) || fin < inicio) {
            return 0;
        }

        var diff = Math.round((fin.getTime() - inicio.getTime()) / 86400000);
        return inclusive ? diff + 1 : diff;
    },

    actualizarCalculoAlimentos: function () {
        var tieneAlimentos = $('#tiene_alimentos').val() === '1';
        var tieneHospedaje = $('#tiene_hospedaje').val() === '1';
        if (!tieneAlimentos || this.esProveedorLike()) {
            $('#monto_deposito').val('');
            $('#monto_total_alimentos_ui').val('');
            if (!tieneHospedaje) {
                $('#tarifa_total').val('');
            }
            return;
        }

        var tarifa = this.buscarPorId(this.catalogos.tarifas, 'id_nivel_cliente', $('#id_nivel_cliente').val());
        var montoDiario = this.obtenerMontoCatalogo(tarifa);
        var dias = this.calcularDiasVigencia($('#fecha_check_in').val(), $('#fecha_check_out').val(), true);
        var total = montoDiario * dias;

        $('#monto_deposito').val(montoDiario > 0 ? montoDiario.toFixed(2) : '');
        $('#monto_total_alimentos_ui').val(total > 0 ? total.toFixed(2) : '');
        if (!tieneHospedaje) {
            $('#tarifa_total').val(total > 0 ? total.toFixed(2) : '');
        }
    },

    actualizarCalculoHospedaje: function () {
        var tieneHospedaje = $('#tiene_hospedaje').val() === '1';
        if (!tieneHospedaje || this.esProveedorLike()) {
            $('#tarifa_noche, #tarifa_total, #noche').val('');
            return;
        }

        var hotelId = $('#id_establecimiento_hotel').val();
        var habitacionId = $('#id_tipo_habitacion').val();
        var tarifaHotel = (this.catalogos.hotel_tarifas || [])
            .filter(function (item) {
                return String(item.id_establecimiento) === String(hotelId)
                    && String(item.id_tipo_habitacion) === String(habitacionId);
            })
            .sort(function (left, right) {
                return Number(right.hotel_tarifa_id || 0) - Number(left.hotel_tarifa_id || 0);
            })[0] || null;
        var habitacionOption = $('#id_tipo_habitacion option:selected');
        var tarifaNoche = Number((tarifaHotel && tarifaHotel.tarifa_noche) || habitacionOption.data('tarifa') || 0);
        var noches = this.calcularDiasVigencia($('#fec_vigencia_desde').val(), $('#fec_vigencia_hasta').val(), false);
        var total = tarifaNoche * noches;

        $('#tarifa_noche').val(tarifaNoche > 0 ? tarifaNoche.toFixed(2) : '').prop('readonly', true);
        $('#noche').val(noches > 0 ? noches : '');
        $('#tarifa_total').val(total > 0 ? total.toFixed(2) : '').prop('readonly', true);
    },

    actualizarFlujoBeneficios: function () {
        var esProveedor = this.esProveedorLike();
        var tieneAlimentos = !esProveedor && $('#tiene_alimentos').val() === '1';
        var tieneHospedaje = !esProveedor && $('#tiene_hospedaje').val() === '1';
        var grupo = this.obtenerGrupoInstitucional();
        var partida = '';

        if (tieneHospedaje) {
            partida = '2';
        } else if (tieneAlimentos) {
            if (grupo === 'fic' || grupo === 'ug' || this.esRolVisibleCliente() || this.esPerfilCliente()) {
                partida = '3';
            } else {
                partida = '1';
            }
        }

        $('.alimentos-field').toggle(tieneAlimentos);
        $('.hospedaje-field').toggle(tieneHospedaje);
        $('#partidaAlimentosWrapper').toggle(tieneAlimentos);
        $('#partidaHospedajeWrapper').toggle(tieneHospedaje);

        if (!tieneHospedaje) {
            $('#id_establecimiento_hotel, #id_tipo_habitacion, #fec_vigencia_desde, #fec_vigencia_hasta, #tarifa_noche, #tarifa_total, #noche').val('');
        }
        if (!tieneAlimentos) {
            $('#fecha_check_in, #fecha_check_out').val('');
            $('#id_nivel_cliente').val('').trigger('change.select2');
            $('#monto_deposito').val('');
            $('#monto_total_alimentos_ui').val('');
        }

        if (esProveedor) {
            $('#tiene_alimentos, #tiene_hospedaje').val('0');
            partida = '';
        }

        var partidaLabel = this.obtenerLabelPartida(partida);
        $('#id_partida').val(partida);
        $('#id_partida_alimentos_ui').val(partidaLabel);
        $('#id_partida_hospedaje_ui').val(partidaLabel);
        this.actualizarCalculoHospedaje();
        this.actualizarCalculoAlimentos();
    },

    estadoBooleano: function (value) {
        if (Number(value) === 1) return '<span class="badge bg-success">S\u00ed</span>';
        if (Number(value) === 0) return '<span class="badge bg-danger">No</span>';
        return '<span class="badge bg-secondary">Sin definir</span>';
    },

    qrActivo: function (value, row) {
        row = row || {};
        var badge = saeg.principal.activo(value, row);
        var idUsuario = Number(row.id_usuario || row.ID_USUARIO || 0);
        var disabled = idUsuario > 0 ? '' : ' disabled';
        var button = '<button class="btn btn-outline-info btn-sm ms-2" type="button" title="Previsualizar QR" onclick="cajeros.previewQrById(' + idUsuario + ')"' + disabled + '><i class="mdi mdi-qrcode"></i></button>';
        return '<div class="d-inline-flex align-items-center justify-content-center gap-1">' + badge + button + '</div>';
    },

    resolveQrPreview: function (row) {
        row = row || {};
        var qrPath = String(row.qr || '').trim();
        if (qrPath !== '') {
            return {
                url: base_url + qrPath.replace(/^\/+/, ''),
                label: qrPath
            };
        }

        var codigo = String(row.codigo_qr || row.api_token || ('FIC-' + String(row.id_usuario || ''))).trim();
        return {
            url: 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&margin=12&data=' + encodeURIComponent(codigo),
            label: codigo
        };
    },

    previewQrById: function (idUsuario) {
        idUsuario = Number(idUsuario || 0);
        if (!idUsuario) {
            Swal.fire('Atenci\u00f3n', 'No fue posible identificar el usuario del QR.', 'warning');
            return;
        }

        var rows = $('#cajerosTable').bootstrapTable('getData', { useCurrentPage: false, includeHiddenRows: true }) || [];
        var row = rows.find(function (item) {
            return Number(item.id_usuario || item.ID_USUARIO || 0) === idUsuario;
        });

        if (!row) {
            Swal.fire('Atenci\u00f3n', 'No fue posible encontrar los datos del QR.', 'warning');
            return;
        }

        var preview = this.resolveQrPreview(row);
        Swal.fire({
            title: 'Previsualizar QR',
            text: preview.label,
            imageUrl: preview.url,
            imageAlt: 'QR del usuario',
            imageWidth: 280,
            imageHeight: 280,
            confirmButtonText: 'Cerrar'
        });
    },

    moneda: function (value) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
    },

    estadoDepositoProgramado: function (value) {
        var estado = String(value || '').trim().toLowerCase();
        if (estado === 'reservado') return '<span class="badge bg-warning text-dark">Reservado</span>';
        if (estado === 'operativo') return '<span class="badge bg-success">Operativo</span>';
        if (estado === 'parcial') return '<span class="badge bg-info text-dark">Parcial</span>';
        if (estado === 'aplicado') return '<span class="badge bg-primary">Aplicado</span>';
        if (estado === 'error') return '<span class="badge bg-danger">Error</span>';
        if (estado === 'cancelado') return '<span class="badge bg-secondary">Cancelado</span>';
        if (estado === 'sin_programa') return '<span class="badge bg-light text-dark">Sin programa</span>';
        return '<span class="badge bg-secondary">Sin definir</span>';
    },

    acciones: function (value, row) {
        row = row || {};
        var idUsuario = Number(row.id_usuario || row.ID_USUARIO || 0);
        var puedeEditar = Number(row.permiso_editar || 0) === 1 || cajeros.idPerfil === 1;
        var puedeEliminar = Number(row.permiso_eliminar || 0) === 1 || cajeros.idPerfil === 1;
        var botones = '<div class="usuario-actions">';

        if (!idUsuario) {
            return '<span class="text-muted">Sin acciones</span>';
        }

        if (puedeEditar) {
            botones += '<button class="btn btn-warning" type="button" title="Editar" onclick="cajeros.editar(' + idUsuario + ')"><i class="mdi mdi-account-edit"></i></button>';
        } else {
            botones += '<button class="btn btn-outline-light" type="button" title="Consultar" onclick="cajeros.editar(' + idUsuario + ')"><i class="mdi mdi-eye"></i></button>';
        }

        if (puedeEliminar) {
            botones += '<button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(' + idUsuario + ')"><i class="mdi mdi-account-remove"></i></button>';
        }

        return botones + '</div>';
    },

    nuevo: function () {
        window.location.href = this.altaUrl;
    },

    prepararNuevoFormulario: function () {
        document.getElementById('cajeroForm').reset();
        $('#id_usuario').val('');
        $('#contrasenia').prop('required', true);
        $('#nip').val('');
        $('#clave_ui').val('');
        $('.js-select2-catalog').val('').trigger('change.select2');
        this.aplicarPerfilPorContexto();
        this.actualizarEstadoPais();
        this.actualizarFlujoBeneficios();
        $('#cajeroPageTitle').text('Nuevo usuario');
        this.aplicarModoFormulario(false);
    },

    editar: function (idUsuario) {
        window.location.href = this.altaUrl + '/' + encodeURIComponent(idUsuario);
    },

    cargarUsuario: function (idUsuario) {
        $.post(base_url + 'index.php/Usuario/getUsuario', { id_usuario: idUsuario }, function (data) {
            if (cajeros.isProviderMode) {
                cajeros.llenarProveedorFormulario(data);
                return;
            }
            cajeros.llenarFormulario(data);
        }, 'json').fail(function () {
            Swal.fire('Error', 'No fue posible obtener el usuario.', 'error');
        });
    },
    llenarFormulario: function (data) {
        $('#id_usuario').val(data.id_usuario);
        $('#nombre').val(data.nombre);
        $('#primer_apellido').val(data.primer_apellido);
        $('#segundo_apellido').val(data.segundo_apellido);
        $('#correo').val(data.correo);
        $('#usuario').val(data.usuario);
        $('#contrasenia').val('').prop('required', false);
        $('#nip').val(data.nip || '');
        $('#tiene_alimentos').val(data.tiene_alimentos);
        $('#tiene_hospedaje').val(data.tiene_hospedaje);
        $('#id_nivel_cliente').val(data.id_nivel_cliente || '').trigger('change.select2');
        $('#id_clave').val(data.id_clave || '');
        $('#clave_ui').val(data.clave || '');
        $('#categoria_ui').val(data.id_clave || '').trigger('change.select2');
        $('#id_establecimiento').val(data.id_establecimiento || '').trigger('change.select2');
        $('#id_establecimiento_hotel').val(data.id_establecimiento_hotel || '').trigger('change.select2');
        $('#id_tipo_habitacion').val(data.id_tipo_habitacion || '').trigger('change.select2');
        $('#fecha_check_in').val(data.fecha_check_in || '');
        $('#fecha_check_out').val(data.fecha_check_out || '');
        $('#fec_vigencia_desde').val(data.fec_vigencia_desde || '');
        $('#fec_vigencia_hasta').val(data.fec_vigencia_hasta || '');
        $('#tarifa_noche').val(data.tarifa_noche || '');
        $('#tarifa_total').val(data.tarifa_total || '');
        $('#monto_deposito').val(data.monto_deposito || '');
        $('#noche').val(data.noche || '');
        $('#id_partida').val(data.id_partida || '');
        $('#id_partida_alimentos_ui').val(this.obtenerLabelPartida(data.id_partida || ''));
        $('#id_partida_hospedaje_ui').val(this.obtenerLabelPartida(data.id_partida || ''));
        $('#id_pais').val(data.id_pais || '').trigger('change.select2');
        $('#id_estado').val(data.id_estado || '').trigger('change.select2');
        $('#grupo_usuario').val(data.grupo_usuario || '');
        $('#id_perfil_catalogo').val(this.getPerfilBasePorGrupo(data.grupo_usuario) || data.id_perfil || '').trigger('change.select2');
        this.onCategoriaChange();
        this.onPerfilBaseChange(data.perfil_grupo || '');
        this.onPaisChange();
        this.actualizarFlujoBeneficios();

        $('#categoria_ui').val(data.id_clave || '').trigger('change.select2');
        $('#id_clave').val(data.id_clave || '');
        $('#id_perfil_catalogo').val(this.getPerfilBasePorGrupo(data.grupo_usuario) || data.id_perfil || '').trigger('change.select2');
        $('#perfil_grupo').val(data.perfil_grupo || '').trigger('change.select2');
        $('#id_establecimiento').val(data.id_establecimiento || '').trigger('change.select2');
        $('#id_pais').val(data.id_pais || '').trigger('change.select2');
        $('#id_estado').val(data.id_estado || '').trigger('change.select2');
        $('#id_establecimiento_hotel').val(data.id_establecimiento_hotel || '').trigger('change.select2');
        $('#id_tipo_habitacion').val(data.id_tipo_habitacion || '').trigger('change.select2');
        $('#id_nivel_cliente').val(data.id_nivel_cliente || '').trigger('change.select2');

        var soloConsulta = Number(data.permiso_editar || 0) !== 1;
        this.aplicarModoFormulario(soloConsulta);
        $('#cajeroPageTitle').text(soloConsulta ? 'Consultar usuario' : 'Editar usuario');
    },

    aplicarModoFormulario: function (soloConsulta) {
        if (this.isProviderMode) {
            $('#formAltaProveedorFic')
                .find('input, select')
                .not('#id_usuario, #grupo_usuario, #id_tipo_proveedor, #id_perfil, #id_establecimiento, #id_proveedor, #no_proveedor_padron')
                .prop('disabled', soloConsulta);
            $('#guardarProveedorFic').toggle(!soloConsulta);
            return;
        }

        $('#cajeroForm').find('input, select').not('#id_usuario, #grupo_usuario, #id_clave').prop('disabled', soloConsulta);
        $('#guardarCajero').toggle(!soloConsulta);
    },

    guardarProveedorFic: function () {
        var boton = $('#guardarProveedorFic');
        var textoOriginal = boton.html();

        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...');

        $.ajax({
            url: base_url + 'index.php/Usuario/saveUsuario',
            type: 'POST',
            dataType: 'json',
            data: $('#formAltaProveedorFic').serialize()
        }).done(function (response) {
            if (response.error) {
                Swal.fire('Atenci\u00f3n', response.respuesta, 'warning');
                return;
            }

            Swal.fire('Correcto', 'Proveedor guardado correctamente.', 'success').then(function () {
                window.location.href = cajeros.listUrl;
            });
        }).fail(function () {
            Swal.fire('Error', 'No fue posible guardar el proveedor.', 'error');
        }).always(function () {
            boton.prop('disabled', false).html(textoOriginal);
        });
    },

    guardar: function () {
        var boton = $('#guardarCajero');
        var textoOriginal = boton.html();
        
        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...');
        
        $.ajax({
            url: base_url + 'index.php/Usuario/saveUsuario',
            type: 'POST',
            dataType: 'json',
            data: $('#cajeroForm').serialize()
        }).done(function (response) {
            if (response.error) {
                Swal.fire('Atenci\u00f3n', response.respuesta, 'warning');
                return;
            }
            Swal.fire('Correcto', 'Usuario guardado correctamente.', 'success').then(function () {
                window.location.href = cajeros.listUrl;
            });
        }).fail(function () {
            Swal.fire('Error', 'No fue posible guardar el usuario.', 'error');
        }).always(function () {
            boton.prop('disabled', false).html(textoOriginal);
        });
    },

    eliminar: function (idUsuario) {
        Swal.fire({
            title: '\u00bfDeseas eliminar este usuario?',
            text: 'El registro dejar\u00e1 de mostrarse en la tabla.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.post(base_url + 'index.php/Usuario/deleteUsuario', { id_usuario: idUsuario }, function (response) {
                if (response.error) {
                    Swal.fire('Atenci\u00f3n', response.respuesta, 'warning');
                    return;
                }
                $('#cajerosTable').bootstrapTable('refresh');
                Swal.fire('Correcto', 'Usuario eliminado correctamente.', 'success');
            }, 'json').fail(function () {
                Swal.fire('Error', 'No fue posible eliminar el usuario.', 'error');
            });
        });
    }
};

$(function () {
    cajeros.iniciar();
});


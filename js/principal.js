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
                            Swal.fire("", "Se agregó correctamente el logotipo", "success");
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
            if (row.tiene_alimentos == 1) accion += '<span class="badge bg-success">Sí</span>';
            if (row.tiene_alimentos == 0) accion += '<span class="badge bg-danger">No</span>';
            if (row.tiene_alimentos == '') accion += '<span class="badge bg-danger">Pendiente</span>';
            return accion;
        },

        login: function () {
            var usuario = $('#usuario').val();
            var contrasenia = $('#contrasenia').val();

            if (!usuario || !contrasenia) {
                Swal.fire("Ã‚Â¡Atención!", "Es requerido el usuario y contraseña", "error");
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
                return '<span class="badge bg-success">Sí</span>';
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
    listUrl: '',
    altaUrl: '',
    contexto: {},
    roleOptions: {},
    catalogos: {
        categorias: [],
        disciplinas: [],
        paises: [],
        perfiles: [],
        tarifas: [],
        partidas: [],
        tipos_habitacion: [],
        establecimientos: []
    },

    iniciar: function () {
        var pagina = document.getElementById('usuariosPage');
        var altaPagina = document.getElementById('altaUsuarioPage');
        if (!pagina && !altaPagina) return;

        this.isAltaPage = !!altaPagina;
        var contenedor = altaPagina || pagina;

        this.idPerfil = Number(contenedor.dataset.idPerfil);
        this.contexto = this.parseJSON(contenedor.dataset.catalogContext, {});
        this.roleOptions = this.parseJSON(contenedor.dataset.roleOptions, {});
        this.listUrl = contenedor.dataset.listUrl || (base_url + 'index.php/Inicio/Usuarios');
        this.altaUrl = contenedor.dataset.altaUrl || (base_url + 'index.php/Inicio/AltaUsuario');

        if (this.isAltaPage) {
            this.inicializarSelect2();
            this.cargarCatalogosBase(function () {
                var idUsuario = Number(altaPagina.dataset.idUsuario || 0);
                if (idUsuario > 0) {
                    cajeros.cargarUsuario(idUsuario);
                } else {
                    cajeros.prepararNuevoFormulario();
                }
            });

            $('#categoria_ui').on('change', this.onCategoriaChange.bind(this));
            $('#id_perfil_catalogo').on('change', this.onPerfilBaseChange.bind(this));
            $('#tiene_alimentos, #tiene_hospedaje').on('change', this.actualizarFlujoBeneficios.bind(this));
            $('#cajeroForm').on('submit', function (event) {
                event.preventDefault();
                cajeros.guardar();
            });
            return;
        }

        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#cajerosTable').bootstrapTable({
            url: base_url + 'index.php/Usuario/getVistaUsuario',
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

    parseJSON: function (value, fallback) {
        if (!value) return fallback;
        try {
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
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
                dropdownParent: $('#altaUsuarioPage').length ? $('#altaUsuarioPage') : $(document.body),
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
            cajeros.poblarSelect('#id_perfil_catalogo', cajeros.catalogos.perfiles, 'id_perfil', 'dsc_perfil');
            cajeros.poblarSelect('#id_nivel_cliente', cajeros.catalogos.tarifas, 'id_nivel_cliente', 'dsc_nivel_cliente');
            cajeros.poblarSelect('#id_partida', cajeros.catalogos.partidas, 'id_partida', 'partida', function (item) {
                var descripcion = item.des_partida ? ' - ' + item.des_partida : '';
                return (item.partida || '') + descripcion;
            });
            cajeros.poblarSelect('#id_establecimiento', cajeros.catalogos.establecimientos, 'id_establecimiento', 'dsc_establecimiento');
            cajeros.aplicarPerfilPorContexto();
            cajeros.actualizarFlujoBeneficios();
            if (typeof callback === 'function') {
                callback();
            }
        }).fail(function () {
            Swal.fire('Error', 'No fue posible cargar los catálogos del formulario.', 'error');
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

        this.aplicarEstablecimientoCliente();
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

    aplicarEstablecimientoCliente: function () {
        if (this.esPerfilCliente()) {
            $('#id_establecimiento').val('86').trigger('change.select2');
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

    obtenerGrupoInstitucional: function () {
        var selectedProfile = $('#id_perfil_catalogo').val();
        var mapping = this.mapPerfil(selectedProfile);
        return mapping.group || this.contexto.active_group || '';
    },

    actualizarFlujoBeneficios: function () {
        var tieneAlimentos = $('#tiene_alimentos').val() === '1';
        var tieneHospedaje = $('#tiene_hospedaje').val() === '1';
        var grupo = this.obtenerGrupoInstitucional();
        var partida = '';

        if (tieneHospedaje) {
            partida = '3';
        } else if (tieneAlimentos) {
            if (grupo === 'fic') {
                partida = '2';
            } else if (grupo === 'ug' || grupo === 'secul' || grupo === 'secturi') {
                partida = '1';
            }
        }

        $('#partidaWrapper').show();
        $('.hospedaje-field').toggle(tieneHospedaje);

        if (!tieneHospedaje) {
            $('#id_establecimiento_hotel, #id_tipo_habitacion, #fec_vigencia_desde, #fec_vigencia_hasta, #tarifa_noche, #tarifa_total, #noche').val('');
        }

        if (partida) {
            $('#id_partida').val(partida).trigger('change.select2');
        }
    },

    estadoBooleano: function (value) {
        if (Number(value) === 1) return '<span class="badge bg-success">Sí</span>';
        if (Number(value) === 0) return '<span class="badge bg-danger">No</span>';
        return '<span class="badge bg-secondary">Sin definir</span>';
    },

    moneda: function (value) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
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
        this.actualizarFlujoBeneficios();
        $('#cajeroPageTitle').text('Nuevo usuario');
        this.aplicarModoFormulario(false);
    },

    editar: function (idUsuario) {
        window.location.href = this.altaUrl + '/' + encodeURIComponent(idUsuario);
    },

    cargarUsuario: function (idUsuario) {
        $.post(base_url + 'index.php/Usuario/getUsuario', { id_usuario: idUsuario }, function (data) {
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
        $('#id_establecimiento_hotel').val(data.id_establecimiento_hotel || '');
        $('#id_tipo_habitacion').val(data.id_tipo_habitacion || '');
        $('#fecha_check_in').val(data.fecha_check_in || '');
        $('#fecha_check_out').val(data.fecha_check_out || '');
        $('#fec_vigencia_desde').val(data.fec_vigencia_desde || '');
        $('#fec_vigencia_hasta').val(data.fec_vigencia_hasta || '');
        $('#tarifa_noche').val(data.tarifa_noche || '');
        $('#tarifa_total').val(data.tarifa_total || '');
        $('#monto_deposito').val(data.monto_deposito || '');
        $('#noche').val(data.noche || '');
        $('#id_partida').val(data.id_partida || '').trigger('change.select2');
        $('#id_pais').val(data.id_pais || '').trigger('change.select2');
        $('#grupo_usuario').val(data.grupo_usuario || '');
        $('#id_perfil_catalogo').val(data.id_perfil || '').trigger('change.select2');
        this.onCategoriaChange();
        this.onPerfilBaseChange(data.perfil_grupo || '');
        this.aplicarEstablecimientoCliente();
        this.actualizarFlujoBeneficios();

        var soloConsulta = Number(data.permiso_editar || 0) !== 1;
        this.aplicarModoFormulario(soloConsulta);
        $('#cajeroPageTitle').text(soloConsulta ? 'Consultar usuario' : 'Editar usuario');
    },

    aplicarModoFormulario: function (soloConsulta) {
        $('#cajeroForm').find('input, select').not('#id_usuario, #grupo_usuario, #id_clave').prop('disabled', soloConsulta);
        $('#guardarCajero').toggle(!soloConsulta);
    },

    guardar: function () {
        var boton = $('#guardarCajero');
        var textoOriginal = boton.html();
        
        boton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Guardando...');
        
        $.ajax({
            url: base_url + 'index.php/Usuario/saveCajero',
            type: 'POST',
            dataType: 'json',
            data: $('#cajeroForm').serialize()
        }).done(function (response) {
            if (response.error) {
                Swal.fire('Atención', response.respuesta, 'warning');
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
            title: 'Ã‚Â¿Eliminar usuario?',
            text: 'El registro dejará de mostrarse en la tabla.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.post(base_url + 'index.php/Usuario/deleteUsuario', { id_usuario: idUsuario }, function (response) {
                if (response.error) {
                    Swal.fire('Atención', response.respuesta, 'warning');
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

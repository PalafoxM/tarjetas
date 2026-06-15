var saeg = window.ssa || {};

saeg.principal = (function () {
    return {
        cargar_documento: function () {
            $("#frmDocumento").submit(function (event) {
                //disable the default form submission                
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
                    //data: $("#frmAsuntoEntradaNuevo").serialize(),
                    success: function (response, textStatus, jqXHR) {
                        //console.log(response);
                        if(response == 'correcto'){
                            Swal.fire("", "Se agregó correctamente el logotipo", "success");
                            location.reload();
                        }else{
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

         alimentos: function(value,row){
            let accion = ``;
            if(row.tiene_alimentos == 1){
              accion += `<span class="badge bg-success">Sí</span>`;
            }
            if(row.tiene_alimentos == 0){
              accion += `<span class="badge bg-danger">No</span>`;
            }
            if(row.tiene_alimentos == ''){
              accion += `<span class="badge bg-danger">Pendiente</span>`;
            }
        
            // return `<button type="button" onclick="ini.inicio.abrirVentanaPdf(${row.id_turno})" class="btn btn-info"><i class="mdi mdi-file-pdf"></i> </button>`;
            return accion;
        },
    
        login: function(){
            let usuario = $('#usuario').val();              
            let contrasenia = $('#contrasenia').val(); 
        
            if (!usuario || !contrasenia) {
                Swal.fire("¡Atención!", "Es requerido el usuario y contraseña", "error");
                return;
            }  
            // Corrección de selectores
            $('#btn_login').hide();           
            $('#btn_load').show();           
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Login/validar_usuario",
                data: {usuario, contrasenia},
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    if (!response.error) { 
                       /*  Swal.fire("Bienvenido!", "Ingresando...", "success").then(() => {
                             
                        }); */
                        Swal.fire("Bienvenido!", "Ingresando...", "success");
                        window.location.href = base_url + "index.php/Inicio"; 
                    } else {
                        Swal.fire("Usuario incorrecto!", "Favor de verificar sus credenciales de acceso", "error");                            
                    } 
                },
                complete: function(){
                    $('#btn_login').show();           
                    $('#btn_load').hide();  
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    Swal.fire("Error!", textStatus, "error");  
                    console.log('Error:', textStatus, errorThrown);
                }
            });
        },
        activo: function(value,row){
            let boton = ``;
            if(row.activo == 1){
              boton = `<span class="badge bg-success">Sí</span>`;
            }else{
                boton = `<span class="badge bg-danger">No</span>`;
            }
          

            return boton;
        },
        fecha: function(value,row){
            if(!value) return '';
            let fecha = new Date(value);
            let dia = String(fecha.getDate()).padStart(2, '0');
            let mes = String(fecha.getMonth() + 1).padStart(2, '0');
            let anio = fecha.getFullYear();
            let hora = String(fecha.getHours()).padStart(2, '0');
            let minuto = String(fecha.getMinutes()).padStart(2, '0');
            return `${dia}/${mes}/${anio} ${hora}:${minuto}`;
        }
    }
})();

window.cajeros = {
    modal: null,
    idPerfil: null,

    iniciar: function () {
        var pagina = document.getElementById('usuariosPage');
        if (!pagina) return;

        this.idPerfil = Number(pagina.dataset.idPerfil);

        if (typeof $.fn.bootstrapTable !== 'function') {
            console.error('Bootstrap Table no está disponible.');
            Swal.fire('Error', 'No fue posible cargar el componente de la tabla.', 'error');
            return;
        }

        $('#cajerosTable').bootstrapTable({
            url: base_url + 'index.php/Usuario/getVistaUsuario',
            responseHandler: function (response) {
                if (Array.isArray(response)) return response;
                console.error('Respuesta inválida al cargar cajeros:', response);
                return [];
            },
            onLoadError: function (status, request) {
                console.error('Error al cargar cajeros:', status, request.responseText);
                Swal.fire('Error', 'No fue posible consultar los cajeros.', 'error');
            }
        });

        if (window.bootstrap && bootstrap.Modal) {
            this.modal = new bootstrap.Modal(document.getElementById('cajeroModal'));
        }

        $('#nuevoCajero').on('click', this.nuevo.bind(this));
        $('#cajeroForm').on('submit', function (event) {
            event.preventDefault();
            cajeros.guardar();
        });
    },

    acciones: function (value, row) {
        var botones = `
            <div class="btn-group btn-group-sm">
                <button class="btn btn-warning" type="button" title="Editar" onclick="cajeros.editar(${row.id_usuario})">
                    <i class="mdi mdi-account-edit"></i>
                </button>
                <button class="btn btn-primary" type="button" title="Orden de Hospedaje" onclick="st.agregar.verPdf(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf-box"></i>
                </button>
                <button class="btn btn-secondary" type="button" title="Orden de Alimentos no disponible" onclick="st.agregar.verPdfAlimentos(${row.id_usuario})">
                    <i class="mdi mdi-file-pdf"></i>
                </button>`;

        if (cajeros.idPerfil === 1) {
            botones += `
                <button class="btn btn-danger" type="button" title="Eliminar" onclick="cajeros.eliminar(${row.id_usuario})">
                    <i class="mdi mdi-account-remove"></i>
                </button>`;
        }

        return botones + '</div>';
    },

    nuevo: function () {
        if (!this.modal) return;
        document.getElementById('cajeroForm').reset();
        $('#id_usuario').val('');
        $('#contrasenia').prop('required', true);
        $('#cajeroModalTitle').text('Nuevo cajero');
        this.modal.show();
    },

    editar: function (idUsuario) {
        $.post(base_url + 'index.php/Usuario/getUsuario', { id_usuario: idUsuario }, function (data) {
            $('#id_usuario').val(data.id_usuario);
            $('#nombre').val(data.nombre);
            $('#primer_apellido').val(data.primer_apellido);
            $('#segundo_apellido').val(data.segundo_apellido);
            $('#correo').val(data.correo);
            $('#usuario').val(data.usuario);
            $('#contrasenia').val('').prop('required', false);
            $('#cajeroModalTitle').text('Editar cajero');
            if (cajeros.modal) cajeros.modal.show();
        }, 'json').fail(function () {
            Swal.fire('Error', 'No fue posible obtener el cajero.', 'error');
        });
    },

    guardar: function () {
        var boton = $('#guardarCajero').prop('disabled', true);
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
            if (cajeros.modal) cajeros.modal.hide();
            $('#cajerosTable').bootstrapTable('refresh');
            Swal.fire('Correcto', 'Cajero guardado correctamente.', 'success');
        }).fail(function () {
            Swal.fire('Error', 'No fue posible guardar el cajero.', 'error');
        }).always(function () {
            boton.prop('disabled', false);
        });
    },

    eliminar: function (idUsuario) {
        Swal.fire({
            title: '¿Eliminar cajero?',
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
                Swal.fire('Correcto', 'Cajero eliminado correctamente.', 'success');
            }, 'json').fail(function () {
                Swal.fire('Error', 'No fue posible eliminar el cajero.', 'error');
            });
        });
    }
};

$(function () {
    cajeros.iniciar();
});

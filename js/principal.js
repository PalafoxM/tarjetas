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
        }        
        
    }
})();
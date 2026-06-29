var jgob = window.jgob || {};

jgob.usuario = (function () {
    return {
        initRegistro: function(){
            $('select').select2({
                language: {
                    noResults: function() {return "No hay resultado";},
                    searching: function() {return "Buscando..";}
                }
            });
            
            $(".open-left").click()
        },
        //Función en desuso
        getClues:function(){
            $("#id_clues").select2({
                language: "es",
                allowClear: true,
                minimumInputLength: 2,
                placeholder: "Busque CLUES por clave o nombre ",
                language: {
                    errorLoading: function (params) {
                        return "No se encontró al diagnóstico";
                    },
                    inputTooShort: function () {
                        return "Ingrese minimo 2 caracteres";
                    }
                },
                //dropdownParent: $("#createEventModal"),
                //templateSelection:formatState,
                //templateResult:formatState,
                width: "100%",
                ajax: {
                    url: base_url + "/index.php/Usuario/getClues",
                    dataType: "json",
                    delay: 600,
                    data: function (params) {
                        return {
                            q: params.term, // search term
                            page: params.page,
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data,
                        };
                    },
                    cache: true,
                },
            });
        },
        getCarpetaUnidad: function(){
            let idUnidadResponsable = $("#id_unidad_responsable").val();
            if (idUnidadResponsable == "") return false;
            if ($("#id_perfil").val() == "") {
                Toast.fire({icon: 'warning',title: "Favor de seleccionar un perfil de usuario antes de seleccionar la unidad resposable"});
                $("#id_unidad_responsable").val("").change();
                return false;
            }
            if ($("#id_perfil").val() != 4) return false;

            $.ajax({
                url: base_url + "/index.php/Usuario/getCarpetaUnidad",
                type: "post",
                dataType: "json", //expect return data as html from server
                data: { idUnidadResponsable: idUnidadResponsable },
                success: function (response, textStatus, jqXHR) {
                    $('#id_carpeta_raiz').find('option').remove().end();
                    var newOption = new Option("Seleccione..", "", false, false);
                    $('#id_carpeta_raiz').append(newOption).trigger('change');

                    if (response.error || response.data.length == 0) {
                        Toast.fire({icon: 'warning',title: response.respuesta});
                        return false;
                    }   
                    $("#id_carpeta_raiz").select2({data: response.data})
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("error(s):" + XMLHttpRequest);
                    if (textStatus == "timeout") {
                        Swal.fire({
                            title: "Error de red. Verifique su conexión.",
                            icon: "error",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "Aceptar",
                        });
                    } else {
                        console.log("error(s):" + textStatus, errorThrown);
                        Swal.fire({
                            title: textStatus + "," + errorThrown,
                            text: "Ocurrió un error inesperado!",
                            icon: "error",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "Aceptar",
                        });
                    }
                },
            });
        },
        cambiarContrasenia:function(element){
            if (element.checked && $("#contrasenia")[0].hasAttribute("readonly"))
                $("#contrasenia").attr("readonly",false).val('')
            else if (parseInt($("#titulo_usuario").attr("data-id-usuario")) == 0 && !element.checked)
                element.checked = true;
            else
                $("#contrasenia").attr("readonly",true)
        },
        cambiarPerfil:function(){
            /* $("#id_equipo_trabajo")[0].required = false;
            if ($("#id_perfil").val() == '4'){
                $("#id_carpeta_raiz_div").show();
                $("#id_carpeta_raiz")[0].required = true;
                $("#carpeta_unica_div").show();
                $("#equipo_dsa_div").hide();
                $("#id_equipo_trabajo").val("").change();
                $("#id_equipo_trabajo")[0].required = false;
                saeg.usuario.getCarpetaUnidad();
                return
            }
            if ($("#id_perfil").val() == '2' || $("#id_perfil").val() == '7'){
                $("#equipo_dsa_div").show();
                $("#id_equipo_trabajo").val("").change();
                $("#id_equipo_trabajo")[0].required = true;
                return
            }
            $("#id_carpeta_raiz").val('').change()
            $("#carpeta_unica")[0].checked = false;
            $("#id_carpeta_raiz_div").hide();
            $("#id_carpeta_raiz")[0].required = false;
            $("#carpeta_unica_div").hide();
            $("#equipo_dsa_div").hide();
            $("#id_equipo_trabajo").val("").change();
            $("#id_equipo_trabajo")[0].required = false; */
        },
        saveUsuario:function(element){
            if (!$("#frm_usuario").parsley().validate()) return false;
            var data = new FormData(document.getElementById("frm_usuario"));
            data.append('id_usuario',$("#titulo_usuario").attr("data-id-usuario"));
            
            $.ajax({
                url: base_url + "/index.php/Usuario/saveUsuario",
                type: "post",
                dataType: "json",
                contentType: false,
                processData: false,
                cache: false,
                data: data,
                beforeSend: function () {
                    element.disabled = true;
                },
                complete: function () {
                    element.disabled = false;
                },
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }
                    Swal.fire("Correcto", "Registro exitoso", "success");

                    window.location.href = `${base_url}/index.php/Usuario`;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("error(s):" + XMLHttpRequest);
                    if (textStatus == "timeout") {
                        Swal.fire({
                            title: "Error de red. Verifique su conexión.",
                            icon: "error",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "Aceptar",
                        });
                    } else {
                        console.log("error(s):" + textStatus, errorThrown);
                        Swal.fire({
                            title: textStatus + "," + errorThrown,
                            text: "Ocurrió un error inesperado!",
                            icon: "error",
                            confirmButtonColor: "#3085d6",
                            confirmButtonText: "Aceptar",
                        });
                    }
                },
            });
        },
        formatterUsuarioListadoUnidad:function(value,row){
            let unidad = `${row.unidad_responsable}<br>${value}`
            return unidad;
        },
        formatterUsuarioListadoAccion:function(value,row){
            let accion = `<a class="btn btn-warning text-white" href="${base_url}/index.php/Usuario/registroUsuario/${value}" title="Editar"><i class="fa-solid fa-edit"></i></a>`;
            if (row.delete)
            accion += `<a class="btn btn-danger text-white" onclick="jgob.usuario.deleteUsuario(this,${value})" title="Borrar"><i class="fa-solid fa-trash"></i></a>`;
            return accion;
        },
        deleteUsuario: function(element,idUsuario){
            Swal.fire({
                title: "¿Está seguro?",
                text: "¿Desea eliminar el registro?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "Cancelar",
                confirmButtonText: "Eliminar",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: base_url + "/index.php/Usuario/deleteUsuario",
                        type: "post",
                        dataType: "json", //expect return data as html from server
                        data: { idUsuario: idUsuario },
                        beforeSend: function () {
                            element.disabled = true;
                        },
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }

                            Toast.fire({icon: 'success',title: 'Registro eliminado correctamente'});
                            $("#table").bootstrapTable("refresh");
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            alert("error");
                            console.log("error(s):" + textStatus, errorThrown);
                            $("#mensajes").html("");
                        },
                    });
                }
            });
        },
    }
})();
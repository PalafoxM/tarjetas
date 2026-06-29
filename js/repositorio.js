var jgob = window.jgob || {};

jgob.repositorio = (function () {
    return {
        copiarAlPortapapeles: function (element) {
           /*  element.select();
            document.execCommand("copy"); */
            navigator.clipboard.writeText(element.dataset.ruta);
            Toast.fire({title:"Link del documento copiado al portapapeles", icon:"success"})
            return true;
        },
        formatterComentarioAccion: function(value, row){
            // permitimos que solamente el autor del comentario pueda eliminarlo
            if (row.fechaLimite && parseInt(row.id_tipo_documento) == 1) return "-";
            if(row.usuario_registro == row.id_usuario_sesion) {
                return `<a class="btn btn-danger text-white" data-id-comentario="${value}" onclick="jgob.repositorio.deleteComentario(this)" title="Borrar"><i class="fa-solid fa-trash"></i></a>`;
            } else {
                return '';
            }
        },
        formatterComentarioDescripcion: function(value, row){
            let texto = jgob.repositorio.nl2br(value, false);
            return `<span style="color: #7aa9f5">${row.fecha_registro_formato2}</span><br><b>${row.usuario} dijo:</b><br>${texto}`;
        },
        formatterVotoAprobado: function(value, row){
            if(row.aprobado == 1) {
                return `<span class="badge bg-success" style="font-size:120%">${value}</span>`;
            }
            if(row.aprobado == 0) {
                return `<span class="badge bg-danger" style="font-size:120%">${value}</span>`;
            }
            return value;
        },
        nl2br: function (str, is_xhtml) {
            if (typeof str === 'undefined' || str === null) {
                return '';
            }
            var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        },
        deleteComentario: function(element){
            Swal.fire({
                title: "Atención",
                text: "¿Desea eliminar el comentario seleccionada?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "Cancelar",
                confirmButtonText: "Aceptar",
            }).then((result) => {
                if (result.isConfirmed) {
                    //revisar_tareas_por_unidad();
                    $.ajax({
                        url: base_url + "/index.php/Repositorio/deleteComentario",
                        type: "post",
                        dataType: "json",
                        data: { idComentario: element.dataset.idComentario },                        
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }                                               
                            Toast.fire({icon: "success", title:"Comentario eliminado exitosamente"});
                            $("#table").bootstrapTable('refresh');                     
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
                }
            }); 
        },
        saveComentario: function(){
            if ($("#comentario").val().length == 0){
                Toast.fire({icon:"warning", title:"Favor de escribir un comentario"});
                return false;
            }
            
            $.ajax({
                url: base_url + "/index.php/Junta/saveComentario",
                dataType: "json",
                type: "post",
                data: { 
                    comentario: $("#comentario").val(),
                    idFichero: $("#id_fichero").val(),
                },                        
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }                          
                    $("#comentario").val("")                     
                    Toast.fire({icon: "success", title:"Comentario guardado exitosamente"});               
                    $("#table").bootstrapTable('refresh');                     
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
        modalFichero: function(element){
            if (parseInt(element.dataset.fichero) == 1){
                $("#mdl_fichero_titulo")[0].innerText = "Crear carpeta";
                $("dsc_fihero").val("");
                $("#frm_carpeta").show();
                $("#frm_documento").hide();
            }
            else{
                $("#mdl_fichero_titulo")[0].innerText = "Subir archivo";
                $('#input_doc').fileinput('clear');
                $("#frm_carpeta").hide();
                $("#frm_documento").show();
            }

            //Actualizar nodos
            jgob.repositorio.getCarpetasNodo();

            if (element.dataset.idFichero != undefined)
                $("#btn_save_fichero")[0].dataset.idFichero = element.dataset.idFichero;

            $("#btn_save_fichero")[0].dataset.fichero = element.dataset.fichero;
            $("#mdl_fichero").modal("show");
        },
        saveFichero:function (element){
            let frm = "frm_carpeta";
            if (parseInt(element.dataset.fichero) === 2)
                frm = "frm_documento";
            
            if (!$(`#${frm}`).parsley().validate()) return false;
            var data = new FormData(document.getElementById(frm));
            data.append('encode',$("#encabezado").attr("data-encode"));
            data.append('idNodo',$("#id_nodo").val());
            data.append('fichero',element.dataset.fichero);
            data.append('idFichero',(element.dataset.idFichero != undefined)? element.dataset.idFichero:0);

            let btnText = element.innerHTML
            element.innerHTML = "";

            $.ajax({
                url: base_url + "/index.php/Repositorio/saveFichero",
                type: "post",
                dataType: "json",
                contentType: false,
                processData: false,
                cache: false,
                data: data,
                beforeSend: function () {
                    element.disabled = true;
                    $(element).addClass("spinner-grow text-primary m-2");
                },
                complete: function () {
                    $(element).removeClass("spinner-grow text-primary m-2");
                    element.innerHTML = btnText;
                    element.disabled = false;
                    if ($("#id_nodo")[0].disabled) $("#id_nodo")[0].disabled = false;
                },
                success: function (response, textStatus, jqXHR) {
                    if (response.error){
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }

                    $("#mdl_fichero").modal("hide");
                    if (parseInt(element.dataset.fichero) === 1){
                        Toast.fire({icon:'succes', title:"Carpeta creada correctamente"});
                        window.location.reload();
                        return true;
                    }
                    Toast.fire({icon:'success', title:"Archivo guardado correctamente"});
                    window.location.reload();
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
        deleteFichero:function (element){
            Swal.fire({
                title: "Atención",
                text: "¿Desea eliminar el registro?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "Cancelar",
                confirmButtonText: "Aceptar",
            }).then((result) => {
                if (result.isConfirmed) {
                    //revisar_tareas_por_unidad();
                    $.ajax({
                        url: base_url + "/index.php/Repositorio/deleteFichero",
                        type: "post",
                        dataType: "json",
                        data: { idFichero: element.dataset.idFichero },                        
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }                                               
                            Toast.fire({icon: "success", title:"Registro eliminado exitosamente"});
                            window.location.reload();                 
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
                }
            }); 
        },
        modalRenombrarFichero:function (element){
            $("#btn_save_renombrar_fichero")[0].dataset.idFichero = element.dataset.idFichero;
            $("#nuevo_nombre").val((element.dataset.nombre != undefined)? element.dataset.nombre:"");
            $("#es_votable")[0].checked = parseInt(element.dataset.votable);
            $("#mdl_renombrar_fichero").modal('show')
        },
        saveRenombrarFichero:function (element){
            var votable = null;
            if($("#es_votable")[0].checked) {
                votable = 1;
            } else {
                votable = 0;
            }
            $.ajax({
                url: base_url + "/index.php/Repositorio/saveRenombrarFichero",
                type: "post",
                dataType: "json",
                data: { 
                    idFichero: element.dataset.idFichero ,
                    nuevoNombre: $("#nuevo_nombre").val(),
                    esVotable: votable,
                },                        
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }                                               
                    Toast.fire({icon: "success", title:"Registro editado exitosamente"});
                    window.location.reload();                 
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
        modalReemplazarArchivo:function (element){
            $("#btn_save_reemplazar_fichero")[0].dataset.idFichero = element.dataset.idFichero;
            $('#mdl_reemplazar_archivo').modal("show");
        },
        saveReemplazarFichero:function (element){
            if (!$(`#frm_reemplazar_archivo`).parsley().validate()) return false;
            var data = new FormData(document.getElementById("frm_reemplazar_archivo"));
            data.append('idFichero',element.dataset.idFichero);

            let btnText = element.innerHTML
            element.innerHTML = "";

            $.ajax({
                url: base_url + "/index.php/Repositorio/saveReemplazarFichero",
                type: "post",
                dataType: "json",
                contentType: false,
                processData: false,
                cache: false,
                data: data,
                beforeSend: function () {
                    element.disabled = true;
                    $(element).addClass("spinner-grow text-primary m-2");
                },
                complete: function () {
                    $(element).removeClass("spinner-grow text-primary m-2");
                    element.innerHTML = btnText;
                    element.disabled = false;
                    if ($("#id_nodo")[0].disabled) $("#id_nodo")[0].disabled = false;
                },
                success: function (response, textStatus, jqXHR) {
                    if (response.error){
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }

                    $("#mdl_fichero").modal("hide");
                    if (parseInt(element.dataset.fichero) === 1){
                        Toast.fire({icon:'succes', title:"Carpeta creada correctamente"});
                        window.location.reload();
                        return true;
                    }
                    Toast.fire({icon:'success', title:"Archivo guardado correctamente"});
                    window.location.reload();
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
        getCarpetasNodo:function (){
            $("#id_nodo").find("option").remove()
            $.ajax({
                url: base_url + "/index.php/Repositorio/getCarpetasNodo",
                type: "post",
                dataType: "json",
                data: { encode: $("#encabezado").attr("data-encode") },                        
                success: function (response, textStatus, jqXHR) {
                    
                    var newOption = new Option("Carpeta principal", 0, true, true);
                    $('#id_nodo').append(newOption);
                    response.forEach(element => {
                        var newOption = new Option(element.dsc_fichero, element.id_fichero, true, true);
                        $('#id_nodo').append(newOption);
                    });
                    $('#id_nodo').val(0).change();
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

            return true;
        },
        saveVotacionActiva: function(element){
            $.ajax({
                url: base_url + "/index.php/Repositorio/saveVotacionActiva",
                type: "post",
                dataType: "json",
                data: { 
                    encode: $("#encabezado").attr("data-encode"),
                    votacionActiva: element.checked,
                },                        
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }                                               
                    Toast.fire({icon: "success", title:"Cambio de estatus de votación realizado correctamente"});
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
    }
})();
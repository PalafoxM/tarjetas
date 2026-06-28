var jgob = window.jgob || {};

jgob.junta = (function () {
    return {
        init: function(){
            $("select").select2();
        },
        saveJunta: function(element){
            if (!$("#frm_junta").parsley().validate()) return false;
            var data = new FormData(document.getElementById("frm_junta"));
            data.append('idJunta',$("#encabezado").attr("data-id-junta"));
            
            if ($("#id_tipo_junta")[0].disabled)
            data.append('id_tipo_junta',$("#id_tipo_junta").val());

            $.ajax({
                url: base_url + "/index.php/Junta/saveJunta",
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
                    window.location.href = base_url+"/index.php/Junta/index/"+response.listado;
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
        cambiarMinuta:function (element){
            (element.checked)? $("#minuta_div").show() : $("#minuta_div").hide();
            return true;
        },
        modal_agregar_documento: function (value){
            $('#mdl_subir_documento_maestro').modal('show');
            $("#identificador_documento_maestro").val(value);
        },
        guardar_documento_maestro: function(){
            var idJunta = $("#identificador_documento_maestro").val();
            // console.log(idJunta);
            // ! guardar el documento mestro 
            if (!$("#form_subir_documento").parsley().validate()) return false;
            var data = new FormData(document.getElementById("form_subir_documento"));
            data.append("idJunta", idJunta);
            // data.append('idJunta',$("#encabezado").attr("data-id-junta"));

            $.ajax({
                url: base_url + "/index.php/Junta/saveDocumentoMaestro",
                type: "post",
                dataType: "json",
                contentType: false,
                processData: false,
                cache: false,
                data: data,
                beforeSend: function () {
                   $("#btnGuardarDoc").prop("disabled", true);
                },
                complete: function () {
                    $("#btnGuardarDoc").prop("disabled", false);
                },
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }
                    Swal.fire("Correcto", "Registro exitoso", "success");
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

        formatterJuntaListadoAccion: function(value,row){
            console.log(value);
            console.log(row);
            let accion ="";
            if (parseInt(row.idPerfil) === 1 && [1,3,5].includes(parseInt(row.id_estatus_junta)))
            accion += `<a class="btn btn-warning text-white mr-1" href="${base_url}/index.php/junta/registro/${row.encode}" title="Editar"><i class="fa-solid fa-edit"></i></a> `;

            if (parseInt(row.idPerfil) === 1 && [1,3].includes(parseInt(row.id_estatus_junta)))
            accion += `<a class="btn btn-info text-white mr-1" title="Registro de asistentes a la junta" onclick="jgob.junta.modalAsistente(this)" data-encode="${row.encode}"><i class="fa-solid fa-user-group"></i></a> `;

            if (parseInt(row.idPerfil) === 1 && !parseInt(row.finalizada) && parseInt(row.id_estatus_junta) == 3)
            accion += `<a class="btn btn-success text-white mr-1" title="Cambiar a juntas activas" onclick="jgob.junta.cambiarEstatus(this)" data-id-estatus="1" data-encode="${row.encode}" ><i class="fa-solid fa-circle-check"></i></a> `;
            
            if (parseInt(row.idPerfil) === 1 && !parseInt(row.finalizada) && parseInt(row.id_estatus_junta) == 1)
                accion += `<a class="btn btn-danger text-white mr-1" title="Cambiar a juntas en construcción" onclick="jgob.junta.cambiarEstatus(this)" data-id-estatus="3" data-encode="${row.encode}"><i class="fa-solid fa-circle-xmark"></i></a> `;

            if (parseInt(row.idPerfil) === 1 && parseInt(row.finalizada) && parseInt(row.id_estatus_junta) == 1)
                accion += `<a class="btn btn-dark text-white mr-1" title="Cambiar a juntas finalizadas" onclick="jgob.junta.cambiarEstatus(this)" data-id-estatus="5" data-encode="${row.encode}"><i class="fa-solid fa-circle-check"></i></a> `;
            
            if (row.visualizarCarpeta)
            accion += `<a class="btn btn-primary text-white mr-1" href="${base_url}/index.php/repositorio/tablaContenido/${row.encode}" title="Tabla de contenido"><i class="fa-solid fa-clipboard-list"></i></a> `;

            if (row.id_minuta)
            accion += `<a class="btn btn-success text-white m-1" href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_minuta}" title="Minuta de la junta" data-toggle="tooltip" data-placement="top" ><i class="fa-solid fa-file-invoice"></i></a>`;
            return accion;
        },
        formatterJuntaListadoNombre: function(value,row){
            if (row.visualizarCarpeta)
                return `<a href="${base_url}/index.php/repositorio/tablaContenido/${row.encode}" data-toggle="tooltip" data-placement="top">${value}</a>`;
            return value;
        },

        formatterVotacionesAprobados: function(value) {
            if (parseInt(value) > 0) {
                return '<div class="text-center"><span class="badge bg-success" style="font-size:120%">' + value + '</span></div>';
            } else {
                return ''; 
            }
        },
        
        formatterVotacionesNoAprobados: function(value) {
            if (parseInt(value) > 0) {
                return '<div class="text-center"><span class="badge bg-danger" style="font-size:120%">' + value + '</span></div>';
            } else {
                return ''; 
            }
        },
       

        formatterIndiceTipo: function(value,row) {
            if(row.es_directorio == '1') {
                return `<img src="${base_url}/assets/images/folder_icon.svg" width="30">`;
            } else {
                if(row.formato == 'pdf') {
                    return `<img src="${base_url}/assets/images/pdf_icon.svg" width="30">`;
                }else {
                    return row.formato.toUpperCase();
                }
            }
            return '';
        },
        formatterIndiceDscFichero: function(value,row) {
            if(row.es_directorio == '1') {
                return `<img src="${base_url}/assets/images/folder_icon.svg" width="30"> ` + value;
            } else {
                if(row.formato == 'pdf') {
                    return `<div class="row"><div class="col"><img src="${base_url}/assets/images/pdf_icon.svg" width="30"> <a href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_fichero}" data-toggle="tooltip" data-placement="top">${value}</a></div> ${(parseInt(row.es_votable))? '<div class="col-2 d-flex justify-content-end text-success">Votable</div>':""}</div>`;
                }else {
                    return `<div class="row"><div class="col"><a href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_fichero}" data-toggle="tooltip" data-placement="top">${value}</a></div> ${(parseInt(row.es_votable))? '<div class="col-2 d-flex justify-content-end text-success">Votable</div>':""}</div>`;
                }
            }
            return '';
        },
        formatterDscFichero: function(value,row) {
            return `<a href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_fichero}" data-toggle="tooltip" data-placement="top">${value}</a>`;
        },
        formatterDscJunta: function(value,row) {
            return `<a href="${base_url}/index.php/repositorio/tablaContenido/${row.encode}" data-toggle="tooltip" data-placement="top">${value}</a>`;
        },
        formatterIndiceAcciones: function(value,row) {
            let accion = ``;
            // 1 = ADMINISTRADOR
            if(row.id_perfil == '1') {
                if(row.es_directorio == 0) {
                    accion += `<a class="btn btn-primary text-white" onclick="jgob.repositorio.modalReemplazarArchivo(this)" data-id-fichero="${row.id_fichero}" title="Reemplazar" data-toggle="tooltip" data-placement="top" title="Reemplazar"><i class="fa-solid fa-exchange"></i></a>`;
                    accion += `<a class="btn btn-warning text-white m-1" onclick="jgob.repositorio.modalRenombrarFichero(this)" data-id-fichero="${row.id_fichero}" data-nombre="${row.dsc_fichero}" data-votable="${row.es_votable}" title="Renombrar" data-toggle="tooltip" data-placement="top" title="Renombrar"><i class="fa-solid fa-edit"></i></a>`;
                    accion += `<a class="btn btn-success text-white m-1" href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_fichero}" title="Revisión de documento" data-toggle="tooltip" data-placement="top" title="Renombrar"><i class="fa-solid fa-eye"></i></a>`;
                    accion += `<a class="btn btn-danger text-white" onclick="jgob.repositorio.deleteFichero(this)" data-id-fichero="${row.id_fichero}" title="Eliminar" data-toggle="tooltip" data-placement="top" title="Eliminar"><i class="fa-solid fa-trash"></i></a>`;
                    return accion;
                } else {
                    accion += `<a class="btn btn-warning text-white m-1" onclick="jgob.repositorio.modalRenombrarFichero(this)" data-id-fichero="${row.id_fichero}" data-nombre="${row.dsc_fichero}" data-votable="${row.es_votable}" title="Renombrar" data-toggle="tooltip" data-placement="top" title="Renombrar"><i class="fa-solid fa-edit"></i></a>`;
                    accion += `<a class="btn btn-danger text-white" onclick="jgob.repositorio.deleteFichero(this)" data-id-fichero="${row.id_fichero}" title="Eliminar" data-toggle="tooltip" data-placement="top" title="Eliminar"><i class="fa-solid fa-trash"></i></a>`;
                }
            }
            
            // 2 = VISOR
            if(row.id_perfil == '2') {
                if(row.es_directorio == 0) {
                    accion += `<a class="btn btn-success text-white m-1" href="${base_url}/index.php/Junta/visualizarDocumento/${row.id_fichero}" title="Revisión de documento" data-toggle="tooltip" data-placement="top" title="Renombrar"><i class="fa-solid fa-eye"></i></a>`;
                }
            }
            
            return accion;
        },
        
        formatterReporteComentarios: function(value,row) {
            if(value) {
                return this.nl2br(value);
            }else {
                return value;
            }
        },
        
        nl2br: function (str, is_xhtml) {
            if (typeof str === 'undefined' || str === null) {
                return '';
            }
            var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
            return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
        },
        cambiarEstatus: function(element){
            Swal.fire({
                title: "Atención",
                text: "¿Cambiar el estatus de la junta?",
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
                        url: base_url + "/index.php/Junta/cambiarEstatus",
                        type: "post",
                        dataType: "json",
                        data: { 
                            encode: element.dataset.encode,
                            idEstatus: element.dataset.idEstatus,
                        },
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
        modalAsistente:function (element){
            $("#btn_agregar_asistente")[0].dataset.encode = element.dataset.encode;
            $("#tbl_asistente").bootstrapTable({
                url: $("#tbl_asistente")[0].dataset.link+"/"+element.dataset.encode,
                locale: "es-MX"
            });  
            $("#mdl_asistente_junta").modal("show");
        },
        saveAsistente:function (element){
            $.ajax({
                url: base_url + "/index.php/Junta/saveAsistente",
                type: "post",
                dataType: "json",
                data: { 
                    encode: element.dataset.encode,
                    idUsuario: $("#id_asistente").val(),
                },
                success: function (response, textStatus, jqXHR) {
                    if (response.error) {
                        Swal.fire("Atención", response.respuesta, "warning");
                        return false;
                    }                                  
                    Toast.fire({icon: "success", title:"Asistente agregado a la junta"});
                    $("#id_asistente").val("").change();
                    $("#tbl_asistente").bootstrapTable("destroy");
                    $("#tbl_asistente").bootstrapTable({
                        url: $("#tbl_asistente")[0].dataset.link+"/"+element.dataset.encode,
                        locale: "es-MX"
                    });                 
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
        formatterAsistenteNombre:function (value,row){
            return (parseInt(row.es_consejero))? `<div class="row"><div class="col">${value}</div><div class="col-2 text-success d-flex justify-content-end">Puede votar</div></div>`: value;
        },
        formatterAsistenteAcciones:function (value,row){
            let acciones = `<a class="btn btn-danger text-white mr-1" title="Eliminar asistente" onclick="jgob.junta.deleteAsistente(this)" data-id-asistente="${row.id_junta_usuario}""><i class="fa-solid fa-trash"></i></a>`;
            return acciones;
        },
        deleteAsistente:function (element){
            Swal.fire({
                title: "Atención",
                text: "¿Desea eliminar al asistente de la junta?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                cancelButtonText: "Cancelar",
                confirmButtonText: "Aceptar",
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: base_url + "/index.php/Junta/deleteAsistente",
                        type: "post",
                        dataType: "json",
                        data: {idJuntaUsuario: element.dataset.idAsistente},
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }                                               
                            Toast.fire({icon: "success", title:"Asistente eliminado exitosamente"});
                            $("#tbl_asistente").bootstrapTable("destroy");
                            $("#tbl_asistente").bootstrapTable({
                                url: $("#tbl_asistente")[0].dataset.link+"/"+$("#btn_agregar_asistente")[0].dataset.encode,
                                locale: "es-MX"
                            });               
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

    }
})();
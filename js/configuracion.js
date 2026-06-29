var jgob = window.jgob || {};

jgob.configuracion = (function () {
    return {
        saveConfiguracion: function(element){
            if (!$(`#frm_configuracion`).parsley().validate()) return false;
            var data = new FormData(document.getElementById("frm_configuracion"));

            let btnText = element.innerHTML
            element.innerHTML = "";

            Swal.fire({
                title: "Atención",
                text: "¿Desea guardar la configuración del sistema?",
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
                        url: base_url + "/index.php/Configuracion/saveConfiguracion",
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
                }
            }); 
        }
    }
})();
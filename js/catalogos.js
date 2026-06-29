var saeg = window.saeg || {};

saeg.catalogos = (function () {
    return {
        mdlUnidad: function(element){
            $("#dsc_unidad_responsable").val("");
            $("#clave").val("");

            if (parseInt(element.dataset.idUnidad) > 0){
                $("#dsc_unidad_responsable").val(element.dataset.dscUnidad);
                $("#clave").val(element.dataset.clave);
            }

            $("#btn_unidad")[0].dataset.idUnidad = element.dataset.idUnidad;
            $("#mdl_unidad").modal("show");
        },
        formatterUnidadesAccion: function(value,row){
            let accion = `<a class="btn btn-warning text-white" data-id-unidad="${value}" data-dsc-unidad="${row.dsc_unidad_responsable}" data-clave="${row.clave}" onclick="saeg.catalogos.mdlUnidad(this)" title="Editar unidad"><i class="fa-solid fa-pen-to-square"></i></a>`;
            accion += `<a class="btn btn-danger text-white" data-id-unidad="${value}" onclick="saeg.catalogos.deleteUnidad(this)" title="Eliminar unidad" ><i class="fa-solid fa-trash"></i></a>`;

            return accion;
        },
        saveUnidad: function(element){

            if ($("#dsc_unidad_responsable").val() == ""){
                Toast.fire({icon: 'warning', title: 'Favor de agregar un nombre de unidad'})
                return false;
            }
            if ($("#clave").val() == ""){
                Toast.fire({icon: 'warning', title: 'Favor de agregar una clave de unidad'})
                return false;
            }

            $.ajax({
                url: base_url + "/index.php/Catalogos/saveUnidad",
                type: "post",
                dataType: "json",
                data: {
                    idUnidad:element.dataset.idUnidad,
                    dscUnidad: $("#dsc_unidad_responsable").val(),
                    clave: $("#clave").val()
                },
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
                    $("#table").bootstrapTable('refresh');
                    Toast.fire({icon: 'success',title: 'Registro guardado correctamente'});
                    $("#mdl_unidad").modal("hide");
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
        deleteUnidad: function(element){
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
                        url: base_url + "/index.php/Catalogos/deleteUnidad",
                        type: "post",
                        dataType: "json",
                        data: {
                            idUnidad:element.dataset.idUnidad
                        },
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
                            Toast.fire({icon:"success",title:"Registro eliminado correctamente"});
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
                            return false;
                        },
                    });
                }
            });  
        }
    }
})();
var ini = window.ssa || {};

ini.opciones = (function () {
    return {
        
        abrirVentanaPdf: function(idTurno) {
            var pdfUrl = base_url + "index.php/Inicio/pdfTurno?id_turno=" + idTurno;
            var opcionesVentana = 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=800, height=800';
            window.open(pdfUrl, '_blank', opcionesVentana);
        },
        obtenerNombreMes: function(indiceMes) {
            var meses = [
              "enero", "febrero", "marzo", "abril", "mayo", "junio",
              "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
            ];
            return meses[indiceMes];
          },
        calculaFecha: function(valor,dias){
            var fechaReferencia = new Date(valor); 
            var fechaActual = new Date();
            var diferenciaMilisegundos = fechaActual - fechaReferencia;
            var diferenciaDias = Math.floor(diferenciaMilisegundos / (1000 * 60 * 60 * 24));
            var diasParaVerificar = dias;
            if (diferenciaDias >= diasParaVerificar) {
                return true;
            } else {
                return false;
            }
        },
        
        formatterAccionesTurno: function(value,row){
            let accion = ``;
            accion += `<button type="button" onclick="ini.inicio.abrirVentanaPdf(${row.id_turno})" class="btn btn-warning" title="Mostrar"><i class="mdi mdi-file-pdf"></i> </button>`
            accion += `<button type="button"  class="btn btn-secondary" title="Modificar" style="margin-left:5px"><i class="mdi mdi-lead-pencil"></i> </button>`
                // return `<button type="button" onclick="ini.inicio.abrirVentanaPdf(${row.id_turno})" class="btn btn-info"><i class="mdi mdi-file-pdf"></i> </button>`;
            return accion;
        },
        formatterTruncaTexto:function(value, row) {
            if(value === null) return "";
            var maxLength = 30;
            var truncatedValue = value.length > maxLength ? value.substring(0, maxLength) + '...' : value;
            return '<span data-toggle="tooltip" title="' + value + '">' + truncatedValue + '</span>';
        },
        formatteStatus: function(value, row){
            // TODO lo se es una mala practica hacer esto pero en este caso me es de mucha ayuda I'm sorry
            // opcion 1  
            // if(value ==1){
            //     let clase = ini.inicio.calculaFecha(row.fecha_recepcion, 10) ? '#fa5c7c' : (ini.inicio.calculaFecha(row.fecha_recepcion, 5)) ? '#f9bc0d': '#47d420';
            //     let titulo = ini.inicio.calculaFecha(row.fecha_recepcion, 10) ? 'Vencido' :ini.inicio.calculaFecha(row.fecha_recepcion, 5) ? 'Por vencer':'En proceso';
            //     return `<button type="button" class="btn" style="background:${clase}; color:#1D438A;" data-toggle="tooltip" title="${titulo}">En proceso </button>`;
            // }
            // if(value ==2){
            //     return '<button type="button" class="btn" style="background:#baddfd;color:#1D438A;" data-toggle="tooltip" title="Resuelta">Resuelta</button>';
            // }
            // opcion 2  
            if (value === '1') {
                let opciones = {
                    10: { clase: '#fa5c7c', titulo: 'Vencido' },
                    5: { clase: '#f9bc0d', titulo: 'Por vencer' },
                    default: { clase: '#47d420', titulo: 'En proceso' }
                };
                let key = ini.inicio.calculaFecha(row.fecha_recepcion, 10) ? 10 : ini.inicio.calculaFecha(row.fecha_recepcion, 5) ? 5 : 'default';
                let { clase, titulo } = opciones[key];
                return `<button type="button" class="btn" style="background:${clase}; color:#1D438A;" data-toggle="tooltip" title="${titulo}">${titulo}</button>`;
            }
            if (value === '2') {
                return '<button type="button" class="btn" style="background:#baddfd;color:#1D438A;" data-toggle="tooltip" title="Resuelta">Resuelta</button>';
            }     
        },
        formattFechaRecepcion: function(value,row){
           
            var fechaOriginalString = value;
            var fechaOriginal = new Date(fechaOriginalString);
            fechaOriginal.setMinutes(fechaOriginal.getTimezoneOffset());
            var dia = fechaOriginal.getDate();
            var mes = ini.inicio.obtenerNombreMes(fechaOriginal.getMonth()); // Sumar 1 al índice del mes
            var año = fechaOriginal.getFullYear();
            var nuevoFormato = dia + " de " + mes + " de " + año;
            return '<strong>' + nuevoFormato + '</strong>';
        },
        formattAcciones: function(value,row){
            let Botones = "<div class='contenedor'>" +
            "<button type='button' class='btn btn-danger' title='Remover' id='remover' onclick='ini.opciones.deleteUsuario(" + row.id_destinatario + ")'><i class='mdi mdi-account-off'></i></button>" +
            "<button type='button' title='Editar' data-bs-toggle='modal' data-bs-target='#staticBackdrop' class='btn btn-warning' onclick='ini.opciones.getUsuario(" + row.id_destinatario + ")'><i class='mdi mdi-account-edit'></i></button>" +
            "</div>";
           return Botones;
        },
        getUsuario: function(id){
            
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Opciones/getUsuario",
                dataType: "json",
                data:{'id_destinatario':id},
                success: function(data) {
                    if (data) {
                        console.log(data);
                        
                        $('#staticBackdropLabel').text('Editar Usuario');
                        
                        $('#editar').prop('disabled', true);
                        $('#editar').val('');

                        $('#id_destinatario').prop('disabled', false);
                        $('#id_destinatario').val(data.id_destinatario);
                        $('#nombre_destinatario').val(data.nombre_destinatario);
                        $('#cargo').val(data.cargo);
                        $('#dsc_cargo').val(data.id_tipo_cargo);
                      

                    } else {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        updateUsuario: function(){
            $('#formDestinatario').submit(function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                console.log(formData);
                
                // var params = new URLSearchParams(formData);
                // var editar = params.get('editar');

                // console.log('Valor de editar:', editar);
                //    if( editar===1 ){

                //    }     
                $.ajax({
                    url: base_url + "index.php/Opciones/UpdateUsuario",
                    type: "post",
                    dataType: "json",
                    data: formData,
                    beforeSend: function () {
                        // element.disabled = true;
                        $('#btnGuardar').prop('disabled', true);
                    },
                    complete: function () {
                        // element.disabled = false;
                        $('#btnGuardar').prop('disabled', false);
                    },
                    success: function (response, textStatus, jqXHR) {
                        if (response.error) {
                            Swal.fire("Atención", response.respuesta, "warning");
                            return false;
                        }
                        Swal.fire("Correcto", "Registro exitoso", "success");
                        window.location.href = `${base_url}index.php/Opciones`;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log("error(s):" + jqXHR);
                    },
                });

            });
        },
        deleteUsuario: function(id){
            // TODO preguntar si desea borrar o no con un swal 

            Swal.fire({
                title: "Estas Seguro?",
                text: "No podras revertir esto!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si, Eliminar"
              }).then((result) => {
                if (result.isConfirmed) {
                  
                    console.log(id);
                    $.ajax({
                        url: base_url + "index.php/Opciones/deleteUsuario",
                        type: "post",
                        dataType: "json",
                        data: {'id_destinatario':id},
                        beforeSend: function () {
                            // element.disabled = true;
                            $('#remover').prop('disabled', true);
                        },
                        complete: function () {
                            // element.disabled = false;
                            $('#remover').prop('disabled', false);
                        },
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }
                            Swal.fire("Correcto", "Registro eliminado con exito", "success");
                            window.location.href = `${base_url}index.php/opciones`;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log("error(s):" + jqXHR);
                        },
                    });

                }
              });



            
        },
        limpiaModal:function(){
             $('#formDestinatario')[0].reset();
            $('#staticBackdropLabel').text('Agregar Usuario');
            $('#id_destinatario').prop('disabled', true);
            $('#editar').prop('disabled', false);
            $('#editar').val(1);

        },


        
        
    }
})();
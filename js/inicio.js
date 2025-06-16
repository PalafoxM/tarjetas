var ini = window.ssa || {};

ini.inicio = (function () {
    return {
        
        abrirVentanaPdf: function(idTurno) {
            var pdfUrl = base_url + "index.php/Inicio/pdfTurno?id_turno=" + idTurno;
            var opcionesVentana = 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=800, height=800';
            window.open(pdfUrl, '_blank', opcionesVentana);
        },
        obtenerNombreMes: function(indiceMes) {
            var meses = [
              "ENERO", "FEBRERO", "MARZO", "ABRIL", "MAYO", "JUNIO",
              "JULIO", "AGOSTO", "SEPTIEMBRE", "OCTUBRE", "NOVIEMBRE", "DICIEMBRE"
            ];
            return meses[indiceMes - 1];
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
            let accion = "<div class='contenedor'>"+
                "<button type='button' onclick='ini.inicio.abrirVentanaPdf("+ row.id_turno+")' class='btn btn-secondary' title='Mostrar'><i class='mdi mdi-file-pdf'></i> </button>"+
                "<button type='button'  class='btn btn-warning' title='Modificar' style='margin-left:5px'><i class='mdi mdi-lead-pencil'></i> </button>"+
                "</div>";
            return accion;
        },
        formatterTruncaTexto:function(value, row) {
            if(value === null) return "";
            var maxLength = 30;
            var truncatedValue = value.length > maxLength ? value.substring(0, maxLength) + '...' : value;
            return '<span data-toggle="tooltip" title="' + value + '">' + truncatedValue + '</span>';
        },
        formatteStatusResultadoTurno:function(value,row){
            if (value === '1') {
                return '<span  title="CON RESULTADO">CON RESULTADO</span>';
            }else if (value ==='2'){
                return '<span  title="SIN RESULTADO">SIN RESULTADO</span>';
            }else if (value ==='3'){
                return '<span  title="AMBOS">AMBOS</span>';
            }else{
                return '<span  title="SIN RESULTADO">SIN RESULTADO</span>';
            }
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
            "<button type='button' class='btn btn-danger' title='Remover' id='remover' onclick='ini.inicio.deleteUsuario(" + row.id_usuario + ")'><i class='mdi mdi-account-off'></i></button>" +
            "<button type='button' title='Editar' data-bs-toggle='modal' data-bs-target='#staticBackdrop' class='btn btn-warning' onclick='ini.inicio.getUsuario(" + row.id_usuario + ")'><i class='mdi mdi-account-edit'></i></button>" +
            "</div>";
           return Botones;
        },
        deleteParticipante: function(id){
    
        Swal.fire({
            title: "Atención",
            text: "Desea eliminar Usuario de la tabla",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Eliminar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Usuario/deleteParticipante",
                    dataType: "json",
                    data:{id_participante:id},
                    success: function(data) {
                        if (!data.error) {
                            Swal.fire("Éxito", data.respuesta, "success");
                            window.location.reload();
                        } else {
                            Swal.fire("info",  data.respuesta , "info");
                        }
                    },
                    error: function() {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                });
            }
        });
          
        },
        enviarCorreo: function(id_participante)
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/enviarCorreo",
                dataType: "json",
                data:{id_participante},
                success: function(data) {
                    if (!data.error) {
                        Swal.fire("Éxito", data.respuesta, "success");
                        window.location.href = base_url + 'index.php/Inicio/usuarios';
                    } else {
                        Swal.fire("info",  data.respuesta , "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        deleteDetenido: function(id){
    
        Swal.fire({
            title: "Atención",
            text: "Desea eliminar Usuario de la tabla",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Eliminar"
        }).then((result) => {
            if (result.isConfirmed) {
        
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Usuario/deleteDetenido",
                    dataType: "json",
                    data:{id_detenido:id},
                    success: function(data) {
                        if (!data.error) {
                            Swal.fire("Éxito", data.respuesta, "success");
                            window.location.reload();
                        } else {
                            Swal.fire("info",  data.respuesta , "info");
                        }
                    },
                    error: function() {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                });
            }
        });
          
        },
        getUsuario: function(id){
            
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getUsuario",
                dataType: "json",
                data:{id_usuario:id},
                success: function(data) {
                    if (data) {
                        console.log(data);
                        
                        $('#staticBackdropLabel').text('Editar Usuario');
                        
                        $('#editar').prop('disabled', true);
                        $('#editar').val('');

                        $('#id_usuario').prop('disabled', false);
                        $('#id_usuario').val(data.id_usuario);
                        $('#usuario').val(data.usuario);
                        $('#contrasenia').val(data.contrasenia);
                        $('#nombre').val(data.nombre);
                        $('#primer_apellido').val(data.primer_apellido);
                        $('#segundo_apellido').val(data.segundo_apellido);
                        $('#sexo').val(data.id_sexo);
                        $('#id_clues').val(data.id_clues).change();
                        $('#correo').val(data.correo);
                        $('#id_perfil').val(data.id_perfil);

                    } else {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        cargaCsv: function()
        {
         $('#standard-modal').modal('show');
        },
       
        subirCsv: function()
        {
            let formData = new FormData();
            let csvFile = $('#fileParticipantes')[0].files[0];
            formData.append('fileParticipantes', $('#fileParticipantes')[0].files[0]);
        
            if (!csvFile) {
                Swal.fire("Error", "Es requerido el archivo CSV", "error");
                return;
            }
        
            Swal.fire({
                title: "Atención",
                text: "Esta operación puede regresar información, que no sea correcta",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Proceder"
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#btn_csv").hide();
                    $("#load_csv").show();
                    $.ajax({
                        url: base_url + "index.php/Principal/uploadCSV",
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (!response.error) {
        
                                Swal.fire("Éxito", "Los datos se guardaron correctamente.", "success");
                                window.location.href = base_url + "index.php/Inicio/Preinscritos";
                               // window.location.reload();
                            } else {
                                Swal.fire({
                                    title: "Error",
                                    text: response.respuesta,
                                    icon: "error",
                                    confirmButtonText: "Descargar archivo de ejemplo",
                                    showCancelButton: true,
                                    cancelButtonText: "Cerrar"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Redirige a la URL del archivo de ejemplo
                                        window.location.href =  base_url+'ejemplo.csv';
                                    }
                                });
                            }
                        },
                        complete: function(){
                            $("#btn_csv").show();
                            $("#load_csv").hide();
                        },
                        error: function(xhr, status, error) {
                            console.log(error);
                            Swal.fire("Error", "Favor de llamar al Administrador", "error")
                            $("#btn_csv").show();
                            $("#load_csv").hide();
                            //alert("Error en la solicitud: " + error);
                        }
                    });
                }
            });
        },
        updateUsuario: function(){
                $('#formUsuario').submit(function(event) {
                    event.preventDefault();

                    var formData = $(this).serialize();
                  
                    console.log(formData);
                    
                    // var params = new URLSearchParams(formData);
                    // var editar = params.get('editar');

                    // console.log('Valor de editar:', editar);
                    //    if( editar===1 ){

                    //    }     
                    $.ajax({
                        url: base_url + "index.php/Usuario/UpdateUsuario",
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
                            window.location.href = `${base_url}index.php/Usuario`;
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
                        url: base_url + "index.php/Usuario/deleteUsuario",
                        type: "post",
                        dataType: "json",
                        data: {'id_usuario':id},
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
                            window.location.href = `${base_url}index.php/Usuario`;
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log("error(s):" + jqXHR);
                        },
                    });

                }
              });



            
        },
        limpiaModal:function(){
            $('#formUsuario')[0].reset();
            $('#id_clues').val('').change();
            $('#staticBackdropLabel').text('Agregar Usuario');
            $('#id_usuario').prop('disabled', true);
            $('#editar').prop('disabled', false);
            $('#editar').val(1);
            $("#contrasenia").prop("readonly", false);
        },
        obtenerCursosSac: function() {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/obtenerCursosSac",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    let html = ''; // Variable para almacenar el HTML de todas las filas
        
                    // Verifica si data es un array y itera sobre él
                    if (Array.isArray(data)) {
                        data.forEach(function(e) {
                            let icon = '';
                            let boton = '';
                            let idMoodle = '';
                  
                            if(e.id_moodle != null ){
                                idMoodle = e.id_moodle;
                            }
                            // Define el ícono según el valor de "visible"
                            if (e.activo == 1) {
                                icon += `<i class="mdi mdi-eye text-success font-18"></i>`;
                            } else if (e.activo == 0) {
                                icon += `<i class="mdi mdi-eye-off text-danger font-18"></i>`;
                            }
        
                            boton += `<button title="Ver detalle"
                                          onclick="ini.inicio.verDetalle(${e.id_cursos_sac})"
                                          class="btn btn-gradient-info px-4"><i
                                              class="mdi mdi-file-document-box font-21"></i>
                                      </button>
                                      <button title="editar"
                                          onclick="ini.inicio.editarCursoSac(${e.id_cursos_sac})"
                                          class="btn btn-gradient-warning px-4"><i
                                              class="dripicons-pencil font-21"></i>
                                      </button>`;
                            if (e.activo == 1) {
                                boton += `
                                   <button title="Desactivar"
                                         onclick="ini.inicio.activarCursoSac(${e.id_cursos_sac},3)"
                                         class="btn btn-gradient-success px-4 "><i
                                             class="mdi mdi-eye font-21"></i>
                                     </button>`;
                            } else if (e.activo == 0) {
                                boton += `
                                     <button title="Activar"
                                         onclick="ini.inicio.activarCursoSac(${e.id_cursos_sac},4)"
                                         class="btn btn-gradient-danger px-4 "><i
                                             class="mdi mdi-eye-off font-21"></i>
                                     </button>`;
                            }
                            boton +=`  <button title="eliminar"
                                           onclick="ini.inicio.eliminarCursoSac(${e.id_cursos_sac})"
                                           class="btn btn-gradient-danger px-4 "><i
                                               class="dripicons-trash font-21"></i>
                                       </button>`;
        
                            // Construye la fila
                            html += `
                                <tr>
                                    <td class="text-center">${e.id_cursos_sac}</td>
                                    <td class="text-center">${idMoodle}</td>
                                    <td class="text-center">${e.dsc_curso}</td>
                                    <td class="text-center">${icon}</td>
                                    <td class="text-center">${boton}</td>
                                </tr>`;
                        });
                    } else {
                        console.error("Error: Los datos no son un array.");
                    }
        
                    // Reemplaza el contenido del tbody con el nuevo HTML
                    $('#datatableCursos tbody').html(html);
                },
                error: function() {
                    Swal.fire("Error", "Error al obtener las categorías.", "error");
                }
            });
        },
        obtenerCategorias: function() {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/obtenerCategorias",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    let html = ''; // Variable para almacenar el HTML de todas las filas
        
                    // Verifica si data es un array y itera sobre él
                    if (Array.isArray(data)) {
                        data.forEach(function(e) {
                            let icon = '';
                            let boton = '';
                            let idMoodle = '';
                            if(e.id_moodle == null ){
                                idMoodle += '';
                            }else{
                                idMoodle = e.id_moodle;
                            }
        
                            // Define el ícono según el valor de "visible"
                            if (e.activo == 1) {
                                icon += `<i class="mdi mdi-eye text-success font-18"></i>`;
                            } else if (e.activo == 0) {
                                icon += `<i class="mdi mdi-eye-off text-danger font-18"></i>`;
                            }
        
                            // Define los botones según el valor de "visible"
                            boton += `<button title="editar" onclick="ini.inicio.editarCat(${e.id_categoria_sac})" class="btn btn-gradient-warning px-4">
                                        <i class="dripicons-pencil font-21"></i>
                                    </button>`;
                            if (e.activo == 1) {
                                boton += `
                                     <button title="desactivar" onclick="ini.inicio.activarCat(${e.id_categoria_sac},3)" class="btn btn-gradient-success px-4">
                                        <i class="mdi mdi-eye font-21"></i>
                                    </button>`;
                            }
                            if (e.activo == 0) {
                                boton += `
                                    <button title="Activar" onclick="ini.inicio.activarCat(${e.id_categoria_sac},4)" class="btn btn-gradient-danger px-4">
                                        <i class="mdi mdi-eye-off font-21"></i>
                                    </button>`;
                            }
                            boton += `<button title="eliminar" onclick="ini.inicio.eliminarCat(${e.id_categoria_sac})" class="btn btn-gradient-danger px-4">
                                        <i class="dripicons-trash font-21"></i>
                                    </button>`;
                           
        
                            // Construye la fila
                            html += `
                                <tr>
          
                                    <td class="text-center">${e.dsc_categoria_sac}</td>
                                    <td class="text-center">${idMoodle}</td>
                                    <td class="text-center">${icon}</td>
                                    <td class="text-center">${boton}</td>
                                </tr>`;
                        });
                    } else {
                        console.error("Error: Los datos no son un array.");
                    }
        
                    // Reemplaza el contenido del tbody con el nuevo HTML
                    $('#datatableCategorias tbody').html(html);
                },
                error: function() {
                    Swal.fire("Error", "Error al obtener las categorías.", "error");
                }
            });
        },
        activarCursoSac: function(id, editar )
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/activarCursoSac",
                dataType: "json",
                data:{id_curso_sac:id, editar},
                success: function(data) {
                    console.log(data);
                    if (!data.error) {
                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                       
                    } else {
                        Swal.fire("Error", "Error al guardar comentario.", "error");
                    }
                    ini.inicio.obtenerCursosSac();
                    
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
        },
        activarCat: function(id_cat,id)
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/guardarCategoria",
                dataType: "json",
                data:{id_cat, editar:id},
                success: function(data) {
                    console.log(data);
                    if (data) {
                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                       
                    } else {
                        Swal.fire("Error", "Error al guardar comentario.", "error");
                    }
                    ini.inicio.obtenerCategorias();  
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
        },
        eliminarCursoSac: function(id)
        {
            Swal.fire({
                title: "La categoria se eliminará",
                text: "¿Estas seguro de eliminar?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si"
              }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: base_url + "index.php/Usuario/guardarCursoSac",
                        dataType: "json",
                        data:{id_cat:id, editar:2},
                        success: function(data) {
                            console.log(data);
                            if (!data.error) {
                                Swal.fire("Éxito", "Se guardo correctamente.", "success")
                               
                            } else {
                                Swal.fire("Error", "Error al guardar comentario.", "error");
                            }
                            ini.inicio.obtenerCursosSac();  
                           
                        },
                        error: function() {
                            Swal.fire("Error", "Error al guardar comentario.", "error")
                        }
                    });
            
                }
              });
        },
        eliminarCat: function(id)
        {
            Swal.fire({
                title: "La categoria se eliminará",
                text: "¿Estas seguro de eliminar?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si"
              }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: base_url + "index.php/Usuario/guardarCategoria",
                        dataType: "json",
                        data:{id_cat:id, editar:2},
                        success: function(data) {
                            console.log(data);
                            if (data) {
                                Swal.fire("Éxito", "Se guardo correctamente.", "success")
                               
                            } else {
                                Swal.fire("Error", "Error al guardar comentario.", "error");
                            }
                            ini.inicio.obtenerCategorias();  
                        },
                        error: function() {
                            Swal.fire("Error", "Error al guardar comentario.", "error")
                        }
                    });
              
                }
              });
        },
        editarCursoSac: function(id)
        {
            $('#modalAgregarCategoria').modal('show');
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getCursoSac",
                dataType: "json",
                data:{id_curso:id},
                success: function(result) {
                    console.log(result);
                    const datos= result.data;
               
                    if (datos.categoria && datos.categoria.length > 0) {
                        let categoriasSeleccionadas = datos.categoria.map(cat => cat.id_categoria);
                        $('#categoria').val(categoriasSeleccionadas).change();
                    }
                    if (datos.periodo && datos.periodo.length > 0) {
                        let periodoSeleccionadas = datos.periodo.map(cat => cat.periodo);
                        console.log(periodoSeleccionadas);
                        $('#periodos').val(periodoSeleccionadas).change();
                    }
                    $('#nombre_curso').val(datos.curso[0].dsc_curso);
                    $('#id_moodle').val(datos.curso[0].id_moodle);
                    $('#descripcion').val(datos.curso[0].descripcion);
                    $('#des_larga').val(datos.curso[0].des_larga);
                    $('#detalle_dirigido').val(datos.curso[0].dirigido);
                    $('#detalle_duracion').val(datos.curso[0].duracion);
                    $('#detalle_autogestivo').val(datos.curso[0].autogestivo);
                    $('#detalle_horas').val(datos.curso[0].horas);
                    $('#detalle_curso_linea').val(datos.curso[0].curso_linea);
                    $('#detalle_informacion').val(datos.curso[0].informacion);
                    $('#editar_curso').val(id);
                    $('#editar').val(1);
                    if (datos.curso[0].img_deta_ruta) {
                        let html = `<img src="${base_url}${datos.curso[0].img_deta_ruta}" alt="Imagen detalle" style="max-width: 100%;">`;
                        $('#vista_img_deta_ruta').html(html);
                    }
                
                    if (datos.curso[0].img_ruta) {
                        let html2 = `<img src="${base_url}${datos.curso[0].img_ruta}" alt="Imagen principal" style="max-width: 100%;">`;
                        $('#vista_img_ruta').html(html2);
                    }
                    $('#summernote').summernote('code', datos.curso[0].des_larga);
                    // Marcar o desmarcar el checkbox según el valor de new_curso
                    if (datos.curso[0].nuevo === 1) {
                        $('#new_curso').prop('checked', true);
                    } else {
                        $('#new_curso').prop('checked', false);
                    }


                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            }); 
        },
        editarCat: function(id)
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getCategoria",
                dataType: "json",
                data:{id_cat:id},
                success: function(data) {
                    console.log(data);
                    Swal.fire({
                        title: "<strong>NOMBRE DE LA CATEGORIA</strong>",
                        icon: "info",
                        html: `<textarea id="comentarioInput" class="form-control" placeholder="Escriba la Categoria">${data.dsc_categoria_sac}</textarea>
                                <br><input id="idMoodle" type="number" class="form-control" placeholder="ID MOODLE" value=${data.id_moodle}>`,
                        showCloseButton: true,
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: "Guardar",
                        cancelButtonText: "Cancelar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const comentario = document.getElementById("comentarioInput").value.trim();       
                            const idMoodle = document.getElementById("idMoodle").value;       
                            if (comentario === "") {
                                Swal.fire("Error", "El campo no puede estar vacío.", "error");
                                return;
                            }
                            const data = {comentario, editar:1, id_cat:id, idMoodle:idMoodle  };
                            $.ajax({
                                type: "POST",
                                url: base_url + "index.php/Usuario/guardarCategoria",
                                dataType: "json",
                                data:data,
                                success: function(data) {
                                    console.log(data);
                                    if (data) {
                                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                                       
                                    } else {
                                        Swal.fire("Error", "Error al guardar comentario.", "error");
                                    }
                                    ini.inicio.obtenerCategorias(); 
                                },
                                error: function() {
                                    Swal.fire("Error", "Error al guardar comentario.", "error")
                                }
                            });
                        }
                    });
                   
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
        },
        editarCursosSac: function()
        {
            let categoria    =$('#categoria').val();
            let nombre_curso =$('#nombre_curso').val();
            let id_moodle    =$('#id_moodle').val();
            let editar_curso    =$('#editar_curso').val();
            if(categoria == 0 || nombre_curso == ''){
                Swal.fire("Error", "El campo categoria o nombre del curso son requeridos", "error");
                return;
            }
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/guardarCursoSac",
                dataType: "json",
                data:{categoria, nombre_curso , id_moodle, editar:1,editar_curso:editar_curso},
                success: function(data) {
                    console.log(data);
                    if (data) {
                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                       
                    } else {
                        Swal.fire("Error", "Error al guardar comentario.", "error");
                    }
                  //  ini.inicio.obtenerCategorias(); 
                  ini.inicio.obtenerCursosSac(); 
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });

        },
        guardarCursos: function () {
            $("#formCurso").submit(function (e) {
                e.preventDefault(); 
                let Url = '';
                var formData = new FormData(this); 
                formData.append('editar_curso', $("#editar_curso").val());
                let editar = $('#editar').val();
        
                if(editar == 1){
                    Url = base_url + "index.php/Usuario/editarCurso";
                }else{
                    Url = base_url + "index.php/Usuario/guardarCursos";
                }
                $("#guardarCursos").hide();
                $("#load_curso").show();
        
                $.ajax({
                    type: "POST",
                    url: Url,
                    data: formData,
                    processData: false,  
                    contentType: false,  
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        if(response.error){
                            Swal.fire("Error", `${response.respuesta}`,"error");
                        }else{
                            Swal.fire("Éxito", "Se guardó correctamente", "success");
                            $("#formCurso")[0].reset();
                            $("#categoria").val('');
                            $("#periodo").val('');
                        }
                    },
                    complete: function(){
                        $('#modalAgregarCategoria').modal('hide');
                        $("#guardarCursos").show();
                        $("#load_curso").hide();
                        $('#editar').val(0);
                        ini.inicio.getCursos();
                    },
                    error: function (response, jqXHR, textStatus, errorThrown) {
                        var res = JSON.parse(response.responseText);
                        Swal.fire("Error", '<p>' + res.message + '</p>');  
                    }
                });
            });
        },        
        
        agregarPeriodo: function()
        {
            $('#modalAgregarPeriodo').modal('show');
            $("#dia_inicio").val(0);
            $("#dia_fin").val(0);
            $("#mes").val(0);
            $("#periodo").val(0);
        },
        eliminarPeriodo: function(id)
        {
           if(id){
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
                        type: "POST",
                        url: base_url + "index.php/Usuario/eliminarPeriodo",
                        dataType: "json",
                        data:{id_periodo:id, editar:2},
                        success: function(data) {
                            console.log(data);
                            ini.inicio.getPeriodos()
                            
                        },
                        error: function() {
                            Swal.fire("Error", "Error al guardar comentario.", "error")
                        }
                    });

                }
              });
       
           }
            
        },
        editarPeriodo: function(id)
        {
           if(id){
            $('#modalAgregarPeriodo').modal('show');
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/optenerPeriodo",
                dataType: "json",
                data:{id_periodo:id, editar:0},
                success: function(data) {
                    console.log(data);
                    $("#dia_inicio").val(data.dia_inicio).change();
                    $("#dia_fin").val(data.dia_fin).change();
                    $("#mes").val(data.mes).change();
                    $("#periodo").val(data.periodo).change();
                    $("#editar_periodo").val(1);
                    $("#id_periodo").val(id);
                    
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
           }
            
        },
        guardarPeriodo: function()
        {

           
            let diaInicio = $("#dia_inicio").val();
            let diaFin     = $("#dia_fin").val();
            let mes       = $("#mes").val();
            let editar_periodo       = $("#editar_periodo").val();

            let id_periodo       = $("#id_periodo").val();
            let periodo       = $("#periodo").val();
            // Convertir a números
            diaInicio = parseInt(diaInicio, 10);
            diaFin = parseInt(diaFin, 10);

            // Validar que las fechas sean números válidos
            if (isNaN(diaInicio) || isNaN(diaFin)) {
                Swal.fire("Error", "Las fechas deben ser números válidos.", "error");
                return;
            }

            // Validar que el día de inicio no sea mayor que el día de fin
            if (diaInicio > diaFin) {
                Swal.fire("Error", "El día de inicio no puede ser mayor que el día de fin.", "error");
                return;
            }
            if(!diaInicio || !diaFin || !mes){
                Swal.fire("Error", "Todos los campos son requeridos", "error");
                return;
            }
            $("#guardar_periodo").hide();
            $("#load_periodo").show();
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/guardarPeriodo",
                dataType: "json",
                data:{diaInicio, diaFin, mes, periodo, editar_periodo, id_periodo},
                success: function(data) {
                    console.log(data);
                    if (!data.error) {
                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                       
                    } else {
                        Swal.fire("Error", data.respuesta , "error");
                    }
                    ini.inicio.getPeriodos()
                },
                complete: function(){
                 //   $('#modalAgregarPeriodo').modal('hide');
                    $("#load_periodo").hide();
                    $("#guardar_periodo").show();
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
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
                confirmButtonText: "Si, Eliminar",
                cancelButtonText: "Cancelar"
              }).then((result) => {
                if (result.isConfirmed) {
                  
                    console.log(id);
                    $.ajax({
                        url: base_url + "index.php/Usuario/deleteUsuario",
                        type: "post",
                        dataType: "json",
                        data: {'id_usuario':id},
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }
                            Swal.fire("Correcto", "Registro eliminado con exito", "success");
                            //window.location.href = `${base_url}index.php/Usuario`;
                            // $('#datatableUsuario').bootstrapTable('refresh');
                            ini.inicio.getUsuarios();
                            //$('#usuariosTable').DataTable().reload(); // Refresca la tabla
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log("error(s):" + jqXHR);
                        },
                    });

                }
              });
        },
        borrarDatos: function(){
            console.log('entro ');
            $('#editar').val(0);
            $("#formCurso")[0].reset();
            $("#categoria").val(null).trigger('change');
            $("#periodos").val(null).trigger('change');
            $("#periodos").val(null).trigger('change');
            $('#vista_img_deta_ruta').html('');
            $('#vista_img_ruta').html('');
            $('#summernote').summernote('code', '<p>Escribe aquí el contenido de la descripción larga</p>');
            $('#detalle_dirigido').val('');
            $('#detalle_duracion').val('');
            $('#detalle_autogestivo').val('');
            $('#detalle_horas').val('');
            $('#detalle_curso_linea').val('');
            $('#detalle_informacion').val('');
        
            
        },
        passwordForm: function(){
            $('#passwordForm').on('submit', function (e) {
                e.preventDefault(); // Evita que el formulario se envíe
                var contrasenia = $('#contrasenia').val();
                $id_usuario = $("#id_usuario").val();
                var confirmar_contrasenia = $('#confirmar_contrasenia').val();
                if(!contrasenia || !confirmar_contrasenia){
                    Swal.fire("Campo vacios", 'Favor de ingresar contraseña' ,"error");
                    return;
                }
                if (contrasenia != confirmar_contrasenia) { // Cambia "contraseñaCorrecta" por tu contraseña válida
                    Swal.fire("error", 'La contraseñas no son identicas, Favor de verificar' ,"error");
                    return;
                } 
                $("#btnCambioPass").hide();
                $("#load_btnCambioPass").show();
                
                var formData = $("#passwordForm").serialize();
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Agregar/cambioPassword",
                    data:formData,
                    dataType: "json",
                    success: function (response) {
                        if(!response.error){
                            Swal.fire("Éxito", '<p> '+ response.respuesta + '</p>', 'success'); 
                            window.location.href = base_url + 'index.php/Login/cerrar';
                        }else{
                            Swal.fire("Atención", '<p> '+ response.respuesta + '</p>', 'error'); 
                        }
                       
                    },
                    complete: function(){
                        $("#btnCambioPass").show();
                        $("#load_btnCambioPass").hide();
                    },
                    error: function (response,jqXHR, textStatus, errorThrown) {
                         var res= JSON.parse (response.responseText);
                         Swal.fire("Error", '<p> '+ res.message + '</p>', 'error');  
                    }
                });
            });

        },
        agregarUsuario: function(){
            $("#formAgregarUsuarioTsi").submit(function (e) {
                e.preventDefault(); 
                $("#id_dependencia").prop("disabled", false);
                var formData = $("#formAgregarUsuarioTsi").serialize();
                $("#id_dependencia").prop("disabled", true);
                $("#btn_save").hide();
                $("#btn_load").show();
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Agregar/guardaUsuarioSti",
                    data:formData,
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        if(response.error){
                            Swal.fire("error", response.respuesta ,"error");
                        }else{
                        Swal.fire("success", "Se guardo con exito", 'success');
                        $("#formAgregarUsuarioTsi")[0].reset();
                        $("#btn_save").show();
                        $("#btn_load").hide();
                        window.location.href = base_url + "index.php/Inicio/usuarios";
                    }
                       
                    },
                    error: function (response,jqXHR, textStatus, errorThrown) {
                         var res= JSON.parse (response.responseText);
                        //  console.log(res.message);
                         Swal.fire("Error", '<p> '+ res.message + '</p>', 'error');  
                         $("#btn_save").show();
                         $("#btn_load").hide();
                    }
                });
            });
        },
        cleanDetenidos: function()
        {
            $("#btn_clean_detenidos").hide();
            $("#btn_clean_load").show();
            $.ajax({
                type: "GET",
                url: base_url + "index.php/Agregar/cleanDetenidos",
                dataType: "json",
                success: function (response) {
                    console.log(response);
                    if(!response.error){
                        Swal.fire("Éxito", '<p> '+ response.respuesta + '</p>', 'success');  
                        window.location.reload();
                    }else{
                        Swal.fire("Error", '<p> '+ response.respuesta + '</p>', 'error');  
                    }

                },
                complete: function(){
                    $("#btn_clean_load").hide();
                    $("#btn_clean_detenidos").show();
                },
                error: function (response,jqXHR, textStatus, errorThrown) {
                     var res= JSON.parse (response.responseText);
                     Swal.fire("Error", '<p> '+ res.message + '</p>', 'error');  
                     $("#btn_save").show();
                     $("#btn_load").hide();
                }
            });
        },
        getParticipante: function(id){
            
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getParticipante",
                dataType: "json",
                data:{id_usuario:id},
                success: function(data) {
                    console.log(data);
                    if (data) {

                        $('#rfc').val(data.rfc);;
                        $('#id_dependencia').val(data.id_dependencia).trigger('change');
                        $('#id_perfil').val(data.id_perfil).trigger('change');
                      //  const fechaCompleta = data.fec_nac; // Ejemplo de fecha
                      //  const fechaFormateada = fechaCompleta.split('T')[0]; // Extrae "1983-10-10"
                      //  $('#fec_nac').val(fechaFormateada); // Asigna la fecha al campo
                        $('#usuario').val(data.usuario);

                        $("#nombre").val(data.nombre);
                        $("#primer_apellido").val(data.primer_apellido);
                        $("#segundo_apellido").val(data.segundo_apellido);
                        //$("#id_municipio").val(data.id_municipio);
                        $("#id_municipio").val(data.id_municipio).trigger('change');
                        $("#curp").val(data.curp);
                        $("#curp_viejo").val(data.curp);
                        $("#fec_nac").val(data.fec_nac);
                        $("#centro_gestor").val(data.centro_gestor);
                        $("#jefe_inmediato").val(data.jefe_inmediato);
                        $("#area").val(data.area);
                        $("#correo_enlace").val(data.correo_enlace);
                        $("#correo").val(data.correo);
                        $("#denominacion_funcional").val(data.denominacion_funcional);
                        $("#funcion").val(data.funcion);
                        $("#id_nivel").val(data.id_nivel).trigger('change');
                        $("#id_sexo").val(data.id_sexo).trigger('change');
                        //Swal.fire("Exitó",response.respuesta , "success")
                        $("#editar").val(1);
                        $("#id_detenido").val(0);
                        $("#id_participante").val(id);
                        st.agregar.validarCURP();
                      

                    } else {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        getDetenido: function(id){
            
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getDetenido",
                dataType: "json",
                data:{id_usuario:id},
                success: function(data) {
                    console.log(data);
                    if (data) {

                        $('#rfc').val(data.rfc);;
                        $('#id_dependencia').val(data.id_dependencia).trigger('change');
                        $('#id_perfil').val(data.id_perfil).trigger('change');
                      //  const fechaCompleta = data.fec_nac; // Ejemplo de fecha
                      //  const fechaFormateada = fechaCompleta.split('T')[0]; // Extrae "1983-10-10"
                      //  $('#fec_nac').val(fechaFormateada); // Asigna la fecha al campo
                        $('#usuario').val(data.usuario);

                        $("#nombre").val(data.nombre);
                        $("#primer_apellido").val(data.primer_apellido);
                        $("#segundo_apellido").val(data.segundo_apellido);
                        //$("#id_municipio").val(data.id_municipio);
                        $("#id_municipio").val(data.id_municipio).trigger('change');
                        $("#curp").val(data.curp);
                        $("#curp_viejo").val(data.curp);
                        $("#fec_nac").val(data.fec_nac);
                        $("#centro_gestor").val(data.centro_gestor);
                        $("#jefe_inmediato").val(data.jefe_inmediato);
                        $("#area").val(data.area);
                        $("#correo_enlace").val(data.correo_enlace);
                        $("#correo").val(data.correo);
                        $("#denominacion_funcional").val(data.denominacion_funcional);
                        $("#funcion").val(data.funcion);
                        $("#id_nivel").val(data.id_nivel).trigger('change');
                        $("#id_sexo").val(data.id_sexo).trigger('change');
                        //Swal.fire("Exitó",response.respuesta , "success")
                        $("#editar").val(1);
                        $("#id_detenido").val(id);
                        $("#id_participante").val(0);
                        st.agregar.validarCURP();
                      

                    } else {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        getUsuario: function(id){
            
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getUsuario",
                dataType: "json",
                data:{id_usuario:id},
                success: function(data) {
                    console.log(data);
                    if (data) {

                        $('#id_usuario').val(data.id_usuario);
                        $('#editar').val(1);
                        $('#nombre').val(data.nombre);
                        $('#primer_apellido').val(data.primer_apellido);
                        $('#segundo_apellido').val(data.segundo_apellido);
                        $('#correo').val(data.correo);
                        $('#rfc').val(data.rfc);
                        $('#curp').val(data.curp);
                        $('#area').val(data.area);
                        $('#jefe_inmediato').val(data.jefe_inmediato);
                        $('#denominacion_funcional').val(data.denominacion_funcional);
                        $('#id_sexo').val(data.id_sexo).trigger('change');
                        $('#id_nivel').val(data.id_nivel).trigger('change');
                        $('#id_dependencia').val(data.id_dependencia).trigger('change');
                        $('#id_perfil').val(data.id_perfil).trigger('change');
                        const fechaCompleta = data.fec_nac; // Ejemplo de fecha
                        const fechaFormateada = fechaCompleta.split('T')[0]; // Extrae "1983-10-10"
                        $('#fec_nac').val(fechaFormateada); // Asigna la fecha al campo
                        $('#usuario').val(data.usuario);

                    } else {
                        Swal.fire("info", "No se encontraron datos del usuario.", "info");
                    }
                },
                error: function() {
                    Swal.fire("info", "No se encontraron datos del usuario.", "info");
                }
            });
        },
        getUsuarios: function()
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getUsuarios",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    let html = ''; // Variable para almacenar el HTML de todas las filas
                    if (Array.isArray(data)) {
                        data.forEach(function(e) {
                           let boton =`
                              <a href="javascript:void(0);" data-toggle="modal" data-animation="bounce"
                                                data-target=".bs-example" onclick="ini.inicio.getUsuario(${e.id_usuario})"><i
                                                        class="mdi mdi-pencil text-success font-18"></i></a>
                              <a href="javascript:void(0);" onclick="ini.inicio.deleteUsuario(${e.id_usuario})"><i
                                                        class="mdi mdi-trash-can text-danger font-18"></i></a>`;                
                            html += `
                                <tr>
                                    <td class="text-center">P${e.nombre_completo}</td>
                                    <td class="text-center">${e.curp}</td>
                                    <td class="text-center">${e.dsc_perfil}</td>
                                    <td class="text-center">${e.dsc_sexo}</td>
                                    <td class="text-center">${boton}</td>
                                </tr>`;
                        });
                    } else {
                        console.error("Error: Los datos no son un array.");
                    }

                    $('#datatable tbody').html(html);
                },
                error: function() {
                    Swal.fire("Error", "Error al obtener las categorías.", "error");
                }
            });

        },
        activarPeriodo: function(id_periodo, id)
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/activarPeriodo",
                dataType: "json",
                data:{id_periodo, id},
                success: function(data) {
                    console.log(data);
                    if (data) {
                        Swal.fire("Éxito", "Se guardo correctamente.", "success")
                       
                    } else {
                        Swal.fire("Error", "Error al guardar comentario.", "error");
                    }
                  //  ini.inicio.obtenerCategorias(); 
                  ini.inicio.getPeriodos(); 
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
        },
        getSelectPeriodos: function(){
            $.ajax({
                type: "GET",
                url: base_url + "index.php/Usuario/getSelectPeriodos",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    $('#periodos').empty();
                    $.each(data.periodo, function(index, p) {
                        $('#periodos').append(
                            $('<option>', {
                                value: p.id_periodo_sac,
                                text: p.dia_inicio + ' AL ' + p.dia_fin + ' DE ' + ini.inicio.obtenerNombreMes(p.mes) + ' P' + p.periodo
                            })
                        );
                    });
                    $('#periodos').trigger('change.select2');
                    $('#categoria').empty();
                    $.each(data.categoria, function(index, p) {
                        $('#categoria').append(
                            $('<option>', {
                                value: p.id_categoria_sac,
                                text: p.dsc_categoria_sac
                            })
                        );
                    });
                    $('#categoria').trigger('change.select2');
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error")
                }
            });
        },
        verDetalle: function(id) {
            $.ajax({
                type: "POST",
                url: `${base_url}index.php/Usuario/verDetalle`,
                dataType: "json",
                data: { id_curso: id },
                success: function(response) {
                    const datos = response.data;
                    console.log(datos);
                    const generarLista = (items, textoVacio, callback) => {
                        if (!items || items.length === 0) {
                            return `<li class="list-group-item">${textoVacio}</li>`;
                        }
                        return items.map(item => `<li class="list-group-item">${callback(item)}</li>`).join('');
                    };
                    const htmlCategorias = generarLista(datos.categoria, "No hay categorías disponibles", 
                        cat => `<strong>${cat.dsc_categoria_sac}</strong>`
                    );
        
                    // Generar HTML para períodos
                    const htmlPeriodos = generarLista(datos.periodo, "No hay períodos disponibles", 
                        per => `<strong>${per.dia_inicio} DE ${ini.inicio.obtenerNombreMes(per.mes)} AL ${per.dia_fin} PERIODO ${per.periodo}</strong>`
                    );
        
                    // Generar HTML para detalles del curso
                    const htmlCurso = datos.curso && datos.curso.length > 0 
                        ? `
                            <strong>Autogestivo:</strong> ${datos.curso[0].autogestivo} <br>
                            <strong>Curso de línea:</strong> ${datos.curso[0].curso_linea} <br>
                            <strong>Duración:</strong> ${datos.curso[0].duracion} <br>
                            <strong>Dirigido:</strong> ${datos.curso[0].dirigido} <br>
                            <strong>Horas:</strong> ${datos.curso[0].horas} <br>
                        `
                        : `<span class="list-group-item">No hay cursos disponibles</span>`;
        
                    // Agregar el contenido al modal
                    $('#detalleCurso').html(`<ul>${htmlCategorias}</ul>`);
                    $('#detallePeriodo').html(`<ul>${htmlPeriodos}</ul>`);
                    $('#detalles').html(`<div>${htmlCurso}</div>`);
        
                    // Mostrar el modal
                    $('#verDetalleCurso').modal('show');
                },
                error: function() {
                    Swal.fire("Error", "Error al cargar los detalles del curso.", "error");
                }
            });
        },      
        getCursos: function()
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getCursos",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    let html = ''; // Variable para almacenar el HTML de todas las filas
        
                    // Verifica si data es un array y itera sobre él
                    if (Array.isArray(data)) {
                        data.forEach(function(e) {
                            let IdMoodle = '';
                            let boton = '';
                            let icon = '';
                            if(e.id_moodle != null){
                                IdMoodle = e.id_moodle ;
                            }  
                            if (e.activo == 1) {
                                icon += `<i class="mdi mdi-eye text-success font-18"></i>`;
                            } else if (e.activo == 0) {
                                icon += `<i class="mdi mdi-eye-off text-danger font-18"></i>`;
                            }                   
                            // Define los botones según el valor de "visible"
                            boton += `
                               <button title="Ver detalle"
                                   onclick="ini.inicio.verDetalle(${e.id_cursos_sac})"
                                   class="btn btn-gradient-info px-4"><i
                                       class="mdi mdi-file-document-box font-21"></i>
                               </button>
                              <button title="editar"
                                  onclick="ini.inicio.editarCursoSac(${e.id_cursos_sac})"
                                  class="btn btn-gradient-warning px-4"><i
                                      class="dripicons-pencil font-21"></i>
                              </button>`;
                            if (e.activo == 1) {
                                boton += `<button title="Desactivar"
                                               onclick="ini.inicio.activarCursoSac(${e.id_cursos_sac},3)"
                                               class="btn btn-gradient-success px-4 "><i
                                                   class="mdi mdi-eye font-21"></i>
                                           </button>`;                            
                            } 
                            if (e.activo == 0) {
                                boton += `<button title="Desactivar"
                                               onclick="ini.inicio.activarCursoSac(${e.id_cursos_sac},4)"
                                               class="btn btn-gradient-success px-4 "><i
                                                   class="mdi mdi-eye font-21"></i>
                                           </button>`;
                            }
                            boton += `<button title="eliminar"
                                          onclick="ini.inicio.eliminarCursoSac(<?= $e->id_cursos_sac?>)"
                                          class="btn btn-gradient-danger px-4 "><i
                                              class="dripicons-trash font-21"></i>
                                      </button>`;
        
                            // Construye la fila
                            html += `
                                <tr>
                                    <td class="text-center">P${e.id_cursos_sac}</td>
                                    <td class="text-center">${IdMoodle}</td>
                                    <td class="text-center">${e.dsc_curso}</td>
                                    <td class="text-center">${icon}</td>
                                    <td class="text-center">${boton}</td>
                                </tr>`;
                        });
                    } else {
                        console.error("Error: Los datos no son un array.");
                    }
        
                    // Reemplaza el contenido del tbody con el nuevo HTML
                    $('#datatableCursos tbody').html(html);
                },
                error: function() {
                    Swal.fire("Error", "Error al obtener las categorías.", "error");
                }
            });

        },
      
        getPeriodos: function()
        {
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Usuario/getPeriodos",
                dataType: "json",
                success: function(data) {
                    console.log(data);
                    let html = ''; // Variable para almacenar el HTML de todas las filas
        
                    // Verifica si data es un array y itera sobre él
                    if (Array.isArray(data)) {
                        data.forEach(function(e) {
                            let icon = '';
                            let boton = '';
                            // Define el ícono según el valor de "visible"
                            if (e.activo == 1) {
                                icon += `<i class="mdi mdi-eye text-success font-18"></i>`;
                            } else if (e.activo == 0) {
                                icon += `<i class="mdi mdi-eye-off text-danger font-18"></i>`;
                            }
                            // Define los botones según el valor de "visible"
                            boton += `
                            <button title="editar" onclick="ini.inicio.editarPeriodo(${e.id_periodo_sac})" class="btn btn-gradient-warning px-4">
                                <i class="dripicons-pencil font-21"></i>
                            </button>`;
                            if (e.activo == 1) {
                                boton += `<button title="Desactivar"
                                              onclick="ini.inicio.activarPeriodo(${e.id_periodo_sac}, 1)"
                                              class="btn btn-gradient-success px-4 "><i
                                                  class="mdi mdi-eye font-21"></i>
                                         </button>`;                            
                            } 
                            if (e.activo == 0) {
                                boton += `<button title="Activar" onclick="ini.inicio.activarPeriodo(${e.id_periodo_sac},2)" class="btn btn-gradient-danger px-4">
                                        <i class="mdi mdi-eye-off font-21"></i>
                                    </button>`;
                            }
                            boton += ` <button title="eliminar" onclick="ini.inicio.eliminarPeriodo(${e.id_periodo_sac})" class="btn btn-gradient-danger px-4">
                                        <i class="dripicons-trash font-21"></i>
                                    </button>`;
        
                            // Construye la fila
                            html += `
                                <tr>
                                    <td class="text-center">P${e.periodo}</td>
                                    <td class="text-center">${ini.inicio.obtenerNombreMes(e.mes)}</td>
                                    <td class="text-center">${e.dia_inicio}</td>
                                    <td class="text-center">${e.dia_fin}</td>
                                    <td class="text-center">${icon}</td>
                                    <td class="text-center">${boton}</td>
                                </tr>`;
                        });
                    } else {
                        console.error("Error: Los datos no son un array.");
                    }
        
                    // Reemplaza el contenido del tbody con el nuevo HTML
                    $('#datatablePeriodos tbody').html(html);
                },
                error: function() {
                    Swal.fire("Error", "Error al obtener las categorías.", "error");
                }
            });
        },
        agregarCategoria: function()
        {
            $('#modalAgregarCategoria').modal('show');
            Swal.fire({
                title: "<strong>NOMBRE DEL curs</strong>",
                icon: "info",
                html: `<textarea id="comentarioInput" class="form-control" placeholder="Escriba la Categoria"></textarea>`,
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "Guardar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    const comentario = document.getElementById("comentarioInput").value.trim();       
                    if (comentario === "") {
                        Swal.fire("Error", "El campo no puede estar vacío.", "error");
                        return;
                    }
                    const data = {comentario };
                    $.ajax({
                        type: "POST",
                        url: base_url + "index.php/Usuario/guardarCategoria",
                        dataType: "json",
                        data:data,
                        success: function(data) {
                            console.log(data);
                            if (data) {
                                Swal.fire("Éxito", "Se guardo correctamente.", "success")
                               
                            } else {
                                Swal.fire("Error", "Error al guardar comentario.", "error");
                            }
                            ini.inicio.obtenerCategorias(); 
                        },
                        error: function() {
                            Swal.fire("Error", "Error al guardar comentario.", "error")
                        }
                    });
                }
            });
        },
        agregar: function()
        {
            Swal.fire({
                title: "<strong>NOMBRE DE LA CATEGORIA</strong>",
                icon: "info",
                html: `<textarea id="comentarioInput" class="form-control" placeholder="Escriba la Categoria"></textarea>`,
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "Guardar",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    const comentario = document.getElementById("comentarioInput").value.trim();       
                    if (comentario === "") {
                        Swal.fire("Error", "El campo no puede estar vacío.", "error");
                        return;
                    }
                    const data = {comentario };
                    $.ajax({
                        type: "POST",
                        url: base_url + "index.php/Usuario/guardarCategoria",
                        dataType: "json",
                        data:data,
                        success: function(data) {
                            console.log(data);
                            if (data) {
                                Swal.fire("Éxito", "Se guardo correctamente.", "success")
                               
                            } else {
                                Swal.fire("Error", "Error al guardar comentario.", "error");
                            }
                            ini.inicio.obtenerCategorias(); 
                        },
                        error: function() {
                            Swal.fire("Error", "Error al guardar comentario.", "error")
                        }
                    });
                }
            });
        },
        agregarCategoria: function(){
            $("#formAgregarCurso").submit(function (e) {
                e.preventDefault(); 
                var formData = $("#formAgregarCurso").serialize();
                console.log(formData);
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Agregar/guardaCategoria",
                    data:formData,
                    dataType: "json",
                    success: function (response) {
                        console.log(response);
                        if(response.respuesta.error){
                            Swal.fire("error", "Solicite apoyo al area de sistemas","error" );
                        }
                        Swal.fire("success", "Se guardo con exito", "success");
                        $("#formAgregarCurso")[0].reset();
                        $('#categoryTree').jstree(true).refresh();
                        //window.location.href = base_url + "index.php/Agregar/Curso";
                    },
                    error: function (response,jqXHR, textStatus, errorThrown) {
                         var res= JSON.parse (response.responseText);
                        //  console.log(res.message);
                         Swal.fire("Error", '<p> '+ res.message + '</p>');  
                    }
                });
            });
        },
        formEditCurso: function(){
            $('#formEditCurso').submit(function(event) {
                event.preventDefault();

                var formData = $(this).serialize();
                console.log(formData);   
                $.ajax({
                    url: base_url + "index.php/Usuario/UpdateCurso",
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
                        window.location.href = `${base_url}index.php/Agregar/Curso`;
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        console.log("error(s):" + jqXHR);
                    },
                });

            });
    },  
        
    }
})();
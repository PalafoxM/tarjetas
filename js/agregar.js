var st = window.ssa || {};

st.agregar = (function () {
    return {
        sha256: function(str) {
            var buffer = new TextEncoder("utf-8").encode(str);
            return crypto.subtle.digest("SHA-256", buffer).then(function(hash) {
                return Array.prototype.map.call(new Uint8Array(hash), function(x) {
                    return ('00' + x.toString(16)).slice(-2);
                }).join('');
            });
        },
        crearEvento: function() {
            $.ajax({
                url: base_url + "index.php/Principal/crearEvento",
                type: 'POST',
                data: formData,
                success: function(response) {
                    
                    Swal.fire("Éxito", "Datos enviados correctamente", "success");
                    form[0].reset(); // Limpiar el formulario después del envío
                },
                error: function(xhr, status, error) {
                    alert('Error al enviar los datos: ' + error);
                }
            });
        },
        agregarUsuario: function(){
            $("#formAgregarUsuarioTsi").submit(function (e) {
                e.preventDefault(); 
                $("#id_dependencia").prop("disabled", false);
                $("#id_perfil").prop("disabled", false);
                var formData = $("#formAgregarUsuarioTsi").serialize();
                $("#id_dependencia").prop("disabled", true);
                $("#id_perfil").prop("disabled", true);
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
        
        validarCURP: function()
        {
            const btnBuscar = document.getElementById('icono');
            const inputCurp = document.getElementById('curp');

            const curp = inputCurp.value.trim().toUpperCase();
            inputCurp.value = curp; // Convertir a mayúsculas
        
            if (curp.length >= 18) {
                st.agregar.toggleButtonState('check');
                inputCurp.style.color = "black";
                st.agregar.consultarCURP();
            } else if (curp.length === 0) {
                st.agregar.toggleButtonState('search');
            } else {
                // Estado de "cargando" mientras se escribe
                btnBuscar.classList.remove('dripicons-loading');
                st.agregar.toggleButtonState('loading');
                inputCurp.style.color = "red";
            }
        },
        btnEliminar :function(id){
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
                        url: base_url + "index.php/Usuario/estudianteCurso",
                        type: "post",
                        dataType: "json", //expect return data as html from server
                        data: { id_estudiante_curso: id },
                        success: function (response, textStatus, jqXHR) {
                            if (response.error) {
                                Swal.fire("Atención", response.respuesta, "warning");
                                return false;
                            }
                            Swal.fire("Éxito", response.respuesta, "success");
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
        programar: function(id) {
            const radioSeleccionado = document.querySelector('input[name="periodo"]:checked');
            if (!radioSeleccionado) {
                Swal.fire('Atención', 'Por favor, selecciona un período antes de guardar.', 'info'); // Mensaje de error
                return;
            }
            const periodoSeleccionado = radioSeleccionado.value;
           $('#guardar_programa').hide();
           $('#load_programar_curso').show();
           let editar = $("#editar_detalle").val();
           let id_periodo_editar = $("#id_periodo_editar").val();
            $.ajax({
                type: "POST",
                url: base_url + "index.php/Agregar/guardarCursoPrograma",
                dataType: "json",
                data: {
                    id_curso_sac: id,
                    periodo: periodoSeleccionado, // Enviar el valor del período seleccionado
                    editar,
                    id_periodo_editar
                },
                success: function(data) {
                    console.log(data);
                    if (!data.error) {
                        Swal.fire("Éxito", data.respuesta, "success");
                        window.location.href = base_url + 'index.php/Agregar/ProgramarCurso';
                    } else {
                        Swal.fire("Error", data.respuesta, "error");
                    }
                },
                complete: function(){
                    $('#guardar_programa').show();
                    $('#load_programar_curso').hide();
                },
                error: function() {
                    Swal.fire("Error", "Error al guardar comentario.", "error");
                }
            });
        },
        toggleButtonState: function(state) {
            const spinner = document.getElementById('spinner');
            const btnBuscar = document.getElementById('icono');
            spinner.style.display = state === 'loading' ? "block" : "none";
            btnBuscar.classList.remove('dripicons-search', 'dripicons-checkmark', 'dripicons-loading');
        
            if (state === 'check') btnBuscar.classList.add('dripicons-checkmark');
            //else if (state === 'loading') btnBuscar.classList.add('dripicons-loading');
            //else btnBuscar.classList.add('dripicons-search');
        },
        consultarCURP: function() {
            const inputCurp = document.getElementById('curp');
            const curp = inputCurp.value;
        
            if (curp.length !== 18) {
                Swal.fire("Error", 'Ingresa una CURP válida.', "error");
                $("#formParticipante")[0].reset();
                return;
            }
        
            $.ajax({
                url: api,
                type: 'POST',
                dataType: 'json',
                data: {
                    curp: curp,
                    script: 'Bitacora->Script:001/15',
                    id_clues: '0780',
                    id_usuario: 7
                },
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                success: function(result) {
                    console.log(result)
                    if (result.datos) {
                        Swal.fire({
                            position: "top-end",
                            icon: "success",
                            title: "Validado por RENAPO",
                            showConfirmButton: false,
                            timer: 1500
                        });
                        inputCurp.style.color = "green";
                        st.agregar.toggleButtonState('check');
                        st.agregar.mostrarCamposDatos(result.datos);
                    }
                    if (result.error) {
                        inputCurp.style.color = "red";
                        st.agregar.toggleButtonState('search');
                        Swal.fire({
                            position: "top-end",
                            icon: "error",
                            title: result.respuesta,
                            showConfirmButton: false,
                            timer: 1500
                        });
                        inputCurp.style.color = "red";
                        st.agregar.toggleButtonState('check');
                        st.agregar.mostrarCamposDatos(result.datos);
                    }
        
        
                },
                error: function(xhr) {
                    console.log("Error:", xhr.responseText);
                    inputCurp.style.color = "red";
                }
            });
        },
        mostrarCamposDatos: function (datos) {
            // Rellenar los campos con los datos obtenidos
            document.getElementById('nombre').value = `${datos.nombre}`;
            document.getElementById('primer_apellido').value = `${datos.primerApellido}`;
            document.getElementById('segundo_apellido').value = `${datos.segundoApellido}`;
            document.getElementById('id_sexo').value = datos.sexo;
            document.getElementById('fec_nac').value = datos.fechaNacimiento;
            document.getElementById('rfc').value = datos.CURP.substring(0, 10);
            document.getElementById('usuario').value = datos.CURP;
            document.getElementById('contrasenia').value = datos.CURP;
            document.getElementById('confirmar_contrasenia').value = datos.CURP;
        },
        cancelarTurno: function(){
            Swal.fire({
                title: "¿Está seguro de que desea cancelar?",
                showDenyButton: true,
                showCancelButton: false,
                confirmButtonText: "Si",
                
              }).then((result) => {
                if (result.isConfirmed) {
                    $("#formAgregarTurno")[0].reset();
                    window.location.href = base_url + "index.php/Inicio";
                } else if (result.isDenied) {
                  Swal.fire("Ok", "", "info");
                }
              });
               
           
        },
        saveTempNombreTurno: function(){
            $('#nombre_turno').on('change', function() {
                // Obtener los valores y textos de las opciones seleccionadas
                var selectedValues = $(this).val();
                var selectedTexts = $('#nombre_turno option:selected').map(function() {
                    return $(this).text();
                }).get();
                updateTable(selectedValues, selectedTexts);
            });

            // Función para actualizar la tabla
            function updateTable(values, texts) {
                // Limpiar la tabla
                $('#selectedValuesNombreTurno tbody').empty();
                $('#selectedValuesNombreTurno1 tbody').empty();

                // Mostrar los valores y textos seleccionados en la tabla
                if (values && values.length > 0) {
                    for (var i = 0; i < values.length; i++) {
                        $('#selectedValuesNombreTurno tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                        $('#selectedValuesNombreTurno1 tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                    }
                } else {
                    $('#selectedValuesNombreTurno tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                    $('#selectedValuesNombreTurno1 tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                }
            }
        },
 
        saveTempccp: function(){
            
            $('#cpp').on('change', function() {
                // Obtener los valores y textos de las opciones seleccionadas
                var selectedValues = $(this).val();
                var selectedTexts = $('#cpp option:selected').map(function() {
                    return $(this).text();
                }).get();

                // Actualizar la tabla
                updateTable(selectedValues, selectedTexts);
            });

            // Función para actualizar la tabla
            function updateTable(values, texts) {
                // Limpiar la tabla
                $('#selectedValuesTable tbody').empty();
                $('#selectedValuesTable1 tbody').empty();

                // Mostrar los valores y textos seleccionados en la tabla
                if (values && values.length > 0) {
                    for (var i = 0; i < values.length; i++) {
                        $('#selectedValuesTable tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                        $('#selectedValuesTable1 tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                    }
                } else {
                    $('#selectedValuesTable tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                    $('#selectedValuesTable1 tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                }
            }
        },
        saveTempIndicacion: function(){
            $('#indicacion').on('change', function() {
                // Obtener los valores y textos de las opciones seleccionadas
                var selectedValues = $(this).val();
                var selectedTexts = $('#indicacion option:selected').map(function() {
                    return $(this).text();
                }).get();
                updateTable(selectedValues, selectedTexts);
            });

            // Función para actualizar la tabla
            function updateTable(values, texts) {
                // Limpiar la tabla
                $('#selectedValuesIndicacion tbody').empty();
                $('#selectedValuesIndicacion1 tbody').empty();

                // Mostrar los valores y textos seleccionados en la tabla
                if (values && values.length > 0) {
                    for (var i = 0; i < values.length; i++) {
                        $('#selectedValuesIndicacion tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                        $('#selectedValuesIndicacion1 tbody').append('<tr><td>' + values[i] + '</td><td>' + texts[i] + '</td></tr>');
                    }
                } else {
                    $('#selectedValuesIndicacion tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                    $('#selectedValuesIndicacion1 tbody').append('<tr><td colspan="2">No hay elementos seleccionados</td></tr>');
                }
            }
        },
        validarEntrada:function(input) {
            var resumen = input.val();
            var regex = /^[a-zA-Z0-9\s.,!?()-]+$/;
            $pattern = "/^([a-zA-ZáéíóúüñÁÉÍÓÚÜÑ 0-9]+)$/";
            if (resumen.length > 0 && resumen.length <= 600 && regex.test(resumen)) {
              input.removeClass("invalid-input");
              return true;  
            } else {
              input.addClass("invalid-input");
              return false;
              
            }
          },
          // convioerte todo los de los inputs a mayusculas
          toUpperCase:function(element){
            element.value = element.value.toUpperCase();
        },
        formConfigurarCurso: function(){
            $('#btn_guardar_conf').on('click', function() {
                $("#btn_guardar_conf").hide();
                $("#btn_guardar_load").show();
                let tableData = [];
            
                // Itera sobre cada fila en el cuerpo de la tabla
                $('tbody tr').each(function() {
                    let rowData = {
                        name: $(this).find('td:first').text(),  // Nombre del curso
                        id_curso: $(this).find('input[name^="id_curso"]').val(), // Fecha de inicio
                        timeopen: $(this).find('input[name^="timeopen"]').val(), // Fecha de inicio
                        timeclose: $(this).find('input[name^="timeclose"]').val(), // Fecha de fin
                       // timelimit: $(this).find('td:nth-child(4)').text(), // Límite de tiempo
                       // visible: $(this).find('input[type="checkbox"]').is(':checked') ? 1 : 0 // Si está visible
                    };
            
                    tableData.push(rowData);
                });
                let id_curso = $("#id_curso").val();
                let fec_inicio = $("#fec_inicio").val();
                let fec_fin = $("#fec_fin").val();
                if(fec_inicio > fec_fin){
                    Swal.fire("Error", "Fecha inicio debe ser mayor a Fecha fin", "error");
                    $("#btn_guardar_conf").show();
                    $("#btn_guardar_load").hide();
                    return
                }
                $.ajax({
                    url:  base_url + "index.php/Agregar/formConfigurarCurso",
                    type: 'POST',
                    data: { tableData: tableData, id_curso:id_curso, fec_inicio, fec_fin },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.error) {
                            Swal.fire("Éxito", "Datos guardados correctamente.", "success");
                            //window.location.reload();
                            window.location.href = base_url + "index.php/Principal/Matricular/";
                        } else {
                            Swal.fire("Error", "No se pudo guardar la configuración.", "error");
                        }
                        $("#btn_guardar_conf").show();
                        $("#btn_guardar_load").hide();
                    },
                    error: function(xhr, status, error) {
                        Swal.fire("Error", "Ocurrió un error en la solicitud: " + error, "error");
                    }
                });
             
             
            });
            
        },
        formParticipante: function(){
            $("#formParticipante").submit(function (e) {
                e.preventDefault();   
                $("#btn_guardar_detenido").hide();             
                $("#btn_load_detenido").show();             
                $.ajax({
                    type: "POST",
                    url: base_url + "index.php/Principal/guardarParticipantes",
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function (response) {
                        console.log(response);
                        console.log(response.error);
                        console.log(response.respuesta);
                        if(response.error == false){
                            Swal.fire("Exitó", response.respuesta, "success");
                            $('#formParticipante')[0].reset();
                            $('#modalDetenidos').modal('hide');
                            window.location.reload();
                                                    
                        }else{
                            Swal.fire("Error", response.respuesta , "error"); 
                            //$("#formParticipante")[0].reset();                         
                            return false;
                        } 
                    },
                    complete: function(){
                        $("#btn_guardar_detenido").show();             
                        $("#btn_load_detenido").hide();   
                    },
                    error: function (response,jqXHR, textStatus, errorThrown) {
                        var res= JSON.parse (response.responseText);
                       //  console.log(res.message);
                        Swal.fire("Error", '<p> '+ res.message + '</p>');  
                   }
                });
            });
        },
        
    }
})();
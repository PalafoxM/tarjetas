<?php  $session = \Config\Services::session();    ?>


<style>
.neon {
    display: inline-block;
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
    padding: 10px;
    border: none;
    font: normal 20px/normal "Warnes", Helvetica, sans-serif;
    color: rgba(255, 255, 255, 1);
    text-decoration: normal;
    text-align: center;
    -o-text-overflow: clip;
    text-overflow: clip;
    white-space: pre;
    text-shadow: 0 0 10px rgba(255, 255, 255, 1), 0 0 20px rgba(255, 255, 255, 1), 0 0 30px rgba(255, 255, 255, 1), 0 0 40px #ff00de, 0 0 70px #ff00de, 0 0 80px #ff00de, 0 0 100px #ff00de;
    -webkit-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
    -moz-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
    -o-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
    transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
}

.neon:hover {
    text-shadow: 0 0 10px rgba(255, 255, 255, 1), 0 0 20px rgba(255, 255, 255, 1), 0 0 30px rgba(255, 255, 255, 1), 0 0 40px #00ffff, 0 0 70px #00ffff, 0 0 80px #00ffff, 0 0 100px #00ffff;
}

body {
    background-color: #d1d7d9;
}

section {
    border: 2px solid darkgray;
    padding: 20px;
    margin-top: 10px;
}

.enLiniea {
    display: flex;
    align-items: stretch;
}

.item {
    flex-grow: 4;
    /* default 0 */
}

table {
    border-collapse: collapse;
    width: 100%;
}

th,
td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

/* Estilo para todas las opciones */
.opciones {
    font-weight: bold;
    color: black;
}

/* Estilo para las dos primeras opciones en el select */
.primeras2 {
    font-weight: bold;
    color: blue;
}

.primeras2:hover {
    color: #d1d7d9;
}

.icono {
    font-weight: bold;
    color: yellow;
}

.campoObligatorio::after {
    content: "*";
    color: red;
    margin-left: 5px;
}

.invalid-input {
    border: 2px solid red;

}

#curp_valido {
    color: 'green';
    display: 'none';

}

#curp_invalido {
    color: 'red';
    display: 'none';
}
</style>

<div class=" mt-3">
    <form id="formAgregarUsuarioTsi" name="formAgregarUsuarioTsi">
        <div class="row">
            <!-- seccion izquierdo incio -->
            <div class="col-md-12 ">
                <div class="card">
                    <!--init card -->
                    <div class="card-body">
                        <blockquote class="blockquote">
                            <h3 class="textoNegro">Alta Usuario SAC</h3>
                        </blockquote>

                        <div class="row">
                            <div class="col-md-3">
                                <label for="curp" class="form-label">CURP</label>
                                <div class="input-group flex-nowrap">
                                    <span class="input-group-text" id="basic-addon1"><i id="icono"
                                            class="dripicons-search"></i>
                                        <div style="display:none;" id="spinner" class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </span>
                                    <input type="text" class="form-control" oninput="validarCURP()" placeholder="CURP"
                                        aria-label="Username" id="curp" name="curp" aria-describedby="basic-addon1"
                                        autocomplete="off">
                                </div>

                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="nombre" class="form-label campoObligatorio">NOMBRE</label>
                                    <input type="text" autocomplete="off" class="form-control" id="nombre" name="nombre"
                                        placeholder="NOMBRE">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="primer_apellido" class="form-label campoObligatorio">PRIMER
                                        APELLIDO</label>
                                    <input type="text" autocomplete="off" class="form-control" id="primer_apellido"
                                        name="primer_apellido" placeholder="PRIMER APELLIDO">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="segundo_apellido" class="form-label campoObligatorio">SEGUNDO
                                        APELLIDO</label>
                                    <input type="text" autocomplete="off" class="form-control" id="segundo_apellido"
                                        name="segundo_apellido" placeholder="SEGUNDO APELLIDO">
                                </div>
                            </div>


                        </div>
                        <div class="row">

                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="fec_nac" class="form-label campoObligatorio">FECHA
                                        NACIMIENTO</label>
                                    <input type="date" autocomplete="off" class="form-control" id="fec_nac"
                                        name="fec_nac" placeholder="FEC. NACIMIENTO">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="rfc" class="form-label campoObligatorio">RFC</label>
                                    <input type="text" autocomplete="off" class="form-control" id="rfc" name="rfc"
                                        placeholder="NOMBRE COMPLETO">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="correo" class="form-label campoObligatorio">CORREO</label>
                                    <input type="text" autocomplete="off" class="form-control" id="correo" name="correo"
                                        placeholder="CORREO ELECTRONICO">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="id_sexo" class="form-label">SEXO</label>
                                    <select class="form-control" id="id_sexo" name="id_sexo"
                                        data-placeholder="seleccione" style="z-index:100;">
                                        <option value="0">seleccione</option>
                                        <option value="1">HOMBRE</option>
                                        <option value="2">MUJER</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-6 position-relative" id="">
                                    <label for="id_nivel" class="form-label">NIVEL TABULAR</label>
                                    <select class="form-control select2" data-toggle="select2" id="id_nivel"
                                        name="id_nivel" data-placeholder="Seleccione" style="z-index:100;">
                                        <option value="0">Seleccione</option>
                                        <?php foreach ($cat_nivel as $g): ?>
                                        <option value="<?php echo $g->id_nivel; ?>">
                                            <?php echo $g->dsc_nivel.' '.$g->denominacion_tabular; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-6 position-relative" id="">
                                    <label for="c" class="form-label">DEPENDENCIA</label>
                                    <select class="form-control select2" data-toggle="select2" id="id_dependencia"
                                        name="id_dependencia" data-placeholder="Seleccione" style="z-index:100;">
                                        <option value="0">Seleccione</option>
                                        <?php foreach ($cat_dependencia as $dep): ?>
                                        <option value="<?php echo $dep->id_dependencia; ?>">
                                            <?php echo $dep->dsc_dependencia ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="denominacion_funcional"
                                        class="form-label campoObligatorio">FUNCION</label>
                                    <input type="text" autocomplete="off" class="form-control"
                                        id="denominacion_funcional" name="denominacion_funcional"
                                        placeholder="DENOMINACION FUNCIONAL"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="area" class="form-label campoObligatorio">AREA PERSONAL</label>
                                    <input type="text" autocomplete="off" class="form-control" id="area" name="area"
                                        placeholder="GRUPO" oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-6 position-relative" id="">
                                    <label for="jefe_inmediato" class="form-label campoObligatorio">FEJE/A
                                        INMEDIATO</label>
                                    <input type="text" autocomplete="off" class="form-control" id="jefe_inmediato"
                                        name="jefe_inmediato" placeholder="SUPERVISOR"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="id_perfil" class="form-label">PERFIL</label>
                                    <select class="form-control select2" data-toggle="select2" id="id_perfil"
                                        name="id_perfil" data-placeholder="Seleccione" style="z-index:100;">
                                        <option value="0">Seleccione</option>
                                        <?php if ($session->get('id_perfil') == 1): ?>
                                            <?php foreach ($cat_perfil as $p): ?>
                                                <option value="<?php echo htmlspecialchars($p->id_perfil); ?>">
                                                    <?php echo htmlspecialchars($p->dsc_perfil); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php elseif ($session->get('id_perfil') == 4): ?>
                                            <option value="6">ENLACE RH NO SAP</option>
                                        <?php elseif ($session->get('id_perfil') == 5): ?>
                                            <option value="5">ENLACE RH SAP</option>
                                        <?php endif; ?>


                                    </select>

                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="usuario" class="form-label campoObligatorio">USUARIO</label>
                                    <input type="text" autocomplete="off" class="form-control" id="usuario"
                                        name="usuario" placeholder="USUARIO"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="contrasenia" class="form-label campoObligatorio">CONTRASEÑA</label>
                                    <input type="password" autocomplete="off" class="form-control" id="contrasenia"
                                        name="contrasenia" placeholder="CONTRASEÑA"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="confirmar_contrasenia" class="form-label campoObligatorio">CONFIRMAR
                                        CONTRASEÑA</label>
                                    <input type="password" autocomplete="off" class="form-control"
                                        id="confirmar_contrasenia" name="confirmar_contrasenia" placeholder="CONFIRMAR"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end card -->
            </div>
            <!-- seccion izquierdo fin-->
            <!-- seccion derecha incio -->
        </div>


        <div class="row mb-5" id="btn_save">
            <div class="col-md-12 text-center ">
                <button class="btn btn-info" type="submit"><i class="mdi mdi-content-save"></i> Guardar </button>
                <button class="btn btn-warning" type="button" onclick="st.agregar.cancelarTurno();"><i
                        class="mdi mdi-content-save-off-outline" id="cancelarTurno"></i> Cancelar </button>
            </div>
        </div>
        <div class="row mb-5" id="btn_load" style="display:none;">
            <div class="col-md-12 text-center ">
                <button class="btn btn-info" type="button" disabled>
                    <span class="spinner-grow spinner-grow-sm me-1" role="status" aria-hidden="true"></span>
                    Guardando...
                </button>
            </div>
        </div>

    </form>

</div>

<script>
$(document).ready(function() {
    st.agregar.agregarTurno();


});


// Constantes generales
//const API_CURP = "http://172.31.187.142:5600/api-curp";
//const API_CURP = "http://localhost:5600/api-curp";
const AUTH_TOKEN = "<?php echo env('TOKEN_API'); ?>"; 
const API_CURP = "<?php echo env('NODE_API_CURP'); ?>";

// Selectores de elementos DOM
const inputCurp = document.getElementById('curp');
const btnBuscar = document.getElementById('icono');
const spinner = document.getElementById('spinner');

// Función para validar CURP
function validarCURP() {
    console.log('entro a validar curp');
    const curp = inputCurp.value.trim().toUpperCase();
    inputCurp.value = curp; // Convertir a mayúsculas

    if (curp.length >= 18) {
        // Estado de "check" si la CURP tiene longitud suficiente
        toggleButtonState('check');

        inputCurp.style.color = "black";
        consultarCURP();
    } else if (curp.length === 0) {
        // Reiniciar estado del botón si el campo está vacío
        toggleButtonState('search');
    } else {
        // Estado de "cargando" mientras se escribe
        btnBuscar.classList.remove('dripicons-loading');
        toggleButtonState('loading');
        inputCurp.style.color = "red";
    }
}

// Función para alternar el estado del botón y spinner
function toggleButtonState(state) {
    spinner.style.display = state === 'loading' ? "block" : "none";
    btnBuscar.classList.remove('dripicons-search', 'dripicons-checkmark', 'dripicons-loading');

    if (state === 'check') btnBuscar.classList.add('dripicons-checkmark');
    //else if (state === 'loading') btnBuscar.classList.add('dripicons-loading');
    //else btnBuscar.classList.add('dripicons-search');
}

// Función para consultar la CURP
function consultarCURP() {
    const curp = inputCurp.value;

    if (curp.length !== 18) {
        Swal.fire("Error", 'Ingresa una CURP válida.', "error");
        $("#formParticipante")[0].reset();
        return;
    }

    $.ajax({
        url: API_CURP,
        type: 'POST',
        dataType: 'json',
        data: {
            curp: curp,
            script: 'Bitacora->Script:001/15',
            id_clues: '0780',
            id_usuario: 7
        },
        headers: {
            'Authorization': `Bearer ${AUTH_TOKEN}`
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
                toggleButtonState('check');
                mostrarCamposDatos(result.datos);
            }
            if (result.error) {
                inputCurp.style.color = "red";
                toggleButtonState('search');
                Swal.fire({
                    position: "top-end",
                    icon: "error",
                    title: result.respuesta,
                    showConfirmButton: false,
                    timer: 1500
                });
                inputCurp.style.color = "red";
                toggleButtonState('check');
                mostrarCamposDatos(result.datos);
            }


        },
        error: function(xhr) {
            console.log("Error:", xhr.responseText);
            inputCurp.style.color = "red";
        }
    });
}

// Función para mostrar los campos con la clase "datos"
function mostrarCamposDatos(datos) {
    // Rellenar los campos con los datos obtenidos
    document.getElementById('nombre').value = `${datos.nombre}`;
    document.getElementById('primer_apellido').value = `${datos.primerApellido}`;
    document.getElementById('segundo_apellido').value = `${datos.segundoApellido}`;
    document.getElementById('id_sexo').value = datos.sexo;
    document.getElementById('fec_nac').value = datos.fechaNacimiento;
    document.getElementById('rfc').value = datos.CURP.substring(0, 10);
}
</script>
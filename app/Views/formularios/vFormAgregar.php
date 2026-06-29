<?php  $session = \Config\Services::session();    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar</title>
    
<style>
    .neon {
        display: inline-block;
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        padding: 10px;
        border: none;
        font: normal 20px/normal "Warnes", Helvetica, sans-serif;
        color: rgba(255,255,255,1);
        text-decoration: normal;
        text-align: center;
        -o-text-overflow: clip;
        text-overflow: clip;
        white-space: pre;
        text-shadow: 0 0 10px rgba(255,255,255,1) , 0 0 20px rgba(255,255,255,1) , 0 0 30px rgba(255,255,255,1) , 0 0 40px #ff00de , 0 0 70px #ff00de , 0 0 80px #ff00de , 0 0 100px #ff00de ;
        -webkit-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        -moz-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        -o-transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1);
        transition: all 200ms cubic-bezier(0.42, 0, 0.58, 1); 
    }
    .neon:hover {
    text-shadow: 0 0 10px rgba(255,255,255,1) , 0 0 20px rgba(255,255,255,1) , 0 0 30px rgba(255,255,255,1) , 0 0 40px #00ffff , 0 0 70px #00ffff , 0 0 80px #00ffff , 0 0 100px #00ffff ;
    }
    body{
        background-color: #d1d7d9;
    }
    section{
        border: 2px solid darkgray;
        padding: 20px;
        margin-top: 10px;
    }
    .enLiniea{
        display: flex;
        align-items: stretch;
    }
    .item {
        flex-grow: 4; /* default 0 */
    }
    table {
            border-collapse: collapse;
            width: 100%;
        }
    th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

    /* Estilo para todas las opciones */
    .opciones {
        font-weight: bold;
        color:black ;
    }

    /* Estilo para las dos primeras opciones en el select */
    .primeras2 {
        font-weight: bold;
        color: blue;
    }
    .primeras2:hover{
        color:#d1d7d9;
    }
    .icono{
        font-weight: bold;
        color:yellow;
    }
   
    .campoObligatorio::after {
            content: "*";
            color: red;
            margin-left: 5px; 
        }
    .invalid-input  {
      border: 2px solid red;

    }
   
</style>
<body>
    <div class=" mt-3">
        <form id="formAgregarTurno" name="formAgregarTurno" >
            <div class="row">
                <!-- seccion izquierdo incio -->
                <div class="col-md-12 ">
                    <div class="card"><!--init card -->
                        <div class="card-body">
                            <blockquote class="blockquote">
                                <h3 class="textoNegro">DATOS GENERALES:</h3>
                                <small>Los camppos marcados con<strong class="campoObligatorio"></strong>     son obigatorios</small>
                            </blockquote>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="asunto" class="form-label campoObligatorio">ASUNTO</label>
                                        <select class="form-select form-control-sm " id="asunto" name="asunto" required>
                                                <option ></option>
                                            <?php foreach ($cat_asuntos as $opcion) : ?>
                                                <option value="<?= $opcion->id_asunto ?>"><?= strtoupper($opcion->dsc_asunto) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <?php
                                $fechaActual = date('d/m/Y');
                                ?> 
                                <div class="col-md-3">
                                    <div class="mb-3 position-relative" id="datepicker1">
                                        <label for="fecha_peticion" class="form-label">FECHA PETICIÓN</label>
                                        <input type="text" class="form-control" data-provide="datepicker" data-date-autoclose="true" data-date-container="#datepicker1" id="fecha_peticion" name="fecha_peticion" placeholder="dd/mm/aaaa" value="<?php echo $fechaActual; ?>">
                                        <div id="fecha-error" style="color: red; display: none;">No se pueden ingresar fechas futuras.</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3 position-relative" id="datepicker2">
                                        <label for="fecha_recepcion" class="form-label">FECHA RECEPCIÓN</label>
                                        <input type="text" class="form-control" data-provide="datepicker" data-date-autoclose="true" data-date-container="#datepicker2" id="fecha_recepcion" name="fecha_recepcion" placeholder="dd/mm/aaaa" value="<?php echo $fechaActual; ?>" required>
                                        <div id="fecha-error2" style="color: red; display: none;">No se pueden ingresar fechas futuras.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="titulo_inv" class="form-label">TITULO</label>
                                        <input type="text" id="titulo_inv" name="titulo_inv" class="form-control form-control-sm" placeholder="titulo" pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="nombre_t" class="form-label campoObligatorio">NOMBRE</label>
                                        <input type="text" id="nombre_t" name="nombre_t" class="form-control form-control-sm " placeholder="NOMBRE" required pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="primer_apellido" class="form-label campoObligatorio">PRIMER APELLIDO</label>
                                        <input type="text" id="primer_apellido" name="primer_apellido" class="form-control form-control-sm " placeholder="PRIMER APELLIDO" required pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="segundo_apellido" class="form-label">SEGUNDO APELLIDO</label>
                                        <input type="text" id="segundo_apellido" name="segundo_apellido" class="form-control form-control-sm" placeholder="SEGUNDO APELLIDO" pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="cargo_inv" class="form-label">CARGO</label>
                                        <input type="text" id="cargo_inv" name="cargo_inv" class="form-control form-control-sm" placeholder="CARGO" pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="razon_social_inv" class="form-label campoObligatorio">RAZON SOCIAL</label>
                                        <input type="text" id="razon_social_inv" name="razon_social_inv" class="form-control form-control-sm" placeholder="RAZON SOCIAL" required pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="mb-3">
                                    <label class="form-label campoObligatorio">SINTESIS ASUNTO</label>
                                    <textarea name="resumen" id="resumen" data-toggle="maxlength" class="form-control" maxlength="1000" rows="5" 
                                        placeholder="Tiene un limite 1000 caracteres." required pattern="[a-zA-Z0-9\s]+" onkeyup="st.agregar.toUpperCase(this)"></textarea>
                                </div>
                            </div>
                        </div>    
                    </div><!--end card -->
                </div>
                <!-- seccion izquierdo fin-->
                <!-- seccion derecha incio -->
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card"><!--init card -->
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <h3 class="textoNegro">TURNAR A:</h3>
                                <button  type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modalTurnarA"> <i class="dripicons-plus icono"></i> AGREGAR</button>
                            </div>                    
                            <div id="modalTurnarA" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fullWidthModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-full-width">
                                    <div class="modal-content">
                                        <div class="modal-header modal-colored-header bg-secondary">
                                            <h4 class="modal-title" id="fullWidthModalLabel">TURNAR A:</h4>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="mb-3">
                                                        <label for="nombre_turno" class="form-label">NOMBRES:</label>
                                                        <select class="select2 form-select form-control-sm" id="nombre_turno" name="nombre_turno[]" multiple="multiple">
                                                        <option></option>
                                                            <?php foreach ($turnado as $opcion) : ?>
                                                                <option value="<?= $opcion->id_destinatario ?>"><?= strtoupper($opcion->nombre_destinatario ." - ". $opcion->cargo) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-check mt-4">
                                                    <label class="form-check-label" for="confirmacion">CON ATENCIÓN A:</label>
                                                    <input type="test" class="form-control" id="observaciones" name = "observaciones" onkeyup="st.agregar.toUpperCase(this)">
                                                   
                                                </div>
                                            </div>

                                            </div>
                                            <div class="container mt-3">
                                                    <table id="selectedValuesNombreTurno">
                                                        <thead>
                                                            <tr>
                                                                <th>IDENTIFICADOR</th>
                                                                <th>NOMBRE</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->
                            <div class="container">
                                    <table id="selectedValuesNombreTurno1">
                                        <thead>
                                            <tr>
                                                <th>IDENTIFICADOR</th>
                                                <th>NOMBRE</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                        </div>
                    </div><!--END CARD -->
                    

                    <div class="card"><!--init card -->
                        <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h3 class="textoNegro">CON COPIA PARA:</h3>
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modal_cpp"> <i class="dripicons-plus icono"></i> AGREGAR</button>
                                </div>
                                <div id="modal_cpp" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="info-header-modalLabel" aria-hidden="true">
                                    <div class="modal-dialog  modal-full-width">
                                        <div class="modal-content">
                                            <div class="modal-header modal-colored-header bg-info">
                                                <h4 class="modal-title" id="info-header-modalLabel">CON COPIA PARA:</h4>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                            </div>
                                            <div class="modal-body">
                                               <div class="mb-3">
                                                    <label for="cpp" class="form-label">SELECCIONE LOS NOMBRES A LOS QUE DESEA COPIAR</label> <small class=""><strong>Nota:</strong> puedes seleccionar todos los nombres que necesites:</small>
                                                    <select class="form-select form-control-sm select2" id="cpp" name="cpp[]" multiple="multiple">
                                                    <option></option>
                                                    <?php $count = 0; ?>
                                                        <?php foreach ($turnado as $opcion) : ?>
                                                            <option value="<?= $opcion->id_destinatario ?>" <?php echo ($count < 2) ? 'class="primeras2"' :'class="opciones"' ?>>
                                                                <?= strtoupper($opcion->nombre_destinatario ." - ". $opcion->cargo) ?>
                                                            </option>
                                                            <?php $count++; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div> 
                                                <div class="container">
                                                    <table id="selectedValuesTable">
                                                        <thead>
                                                            <tr>
                                                                <th>IDENTIFICADOR </th>
                                                                <th>NOMBRE</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">CERRAR</button>
                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->
                                <!-- tablas con nombres  -->
                                <div class="container">
                                    <table id="selectedValuesTable1">
                                        <thead>
                                            <tr>
                                                <th>IDENTIFICADOR </th>
                                                <th>NOMBRE</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                        </div>
                    </div><!--END CARD -->

                    <div class="card"><!--init card -->
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <h3 class="textoNegro">INDICACIONES:</h3>
                                <!-- Full width modal -->
                                <button  type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal_indicacion"><i class="dripicons-plus icono"></i> AGREGAR</button>
                            </div>    
                            <div id="modal_indicacion" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="fullWidthModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-full-width">
                                    <div class="modal-content">
                                        <div class="modal-header modal-colored-header bg-primary">
                                            <h4 class="modal-title" id="fullWidthModalLabel">AGREGAR INDICACIONES:</h4>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label for="indicacion" class="form-label">SELECCIONE LAS INDICACIONES NECESARIAS:</label>
                                                <select class="form-select form-control-sm select2" id="indicacion" name="indicacion[]" multiple="multiple">
                                                <option></option>
                                                    <?php foreach ($cat_indicaciones as $opcion) : ?>
                                                        <option value="<?= $opcion->id_indicacion ?>"><?= strtoupper($opcion->dsc_indicacion) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                                <div class="container">
                                                    <table id="selectedValuesIndicacion">
                                                        <thead>
                                                            <tr>
                                                                <th>IDENTIFICADOR</th>
                                                                <th>NOMBRE</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">CERRAR</button>
                                            <!-- <button type="button" class="btn btn-primary">GUARDAR</button> -->
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div><!-- /.modal -->
                            <div class="container">
                                <table id="selectedValuesIndicacion1">
                                    <thead>
                                        <tr>
                                            <th>IDENTIFICADOR</th>
                                            <th>NOMBRE</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div><!--END CARD -->
                </div>    
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="card"><!--init card -->
                        <div class="card-body">
                            <div class="row">
                                <h3 class="textoNegro">QUIEN TRAMITÓ:</h3>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tramito" class="form-label">TRAMITÓ</label>
                                        <span class="form-control form-control-sm" id="tramito"><?php echo strtoupper(htmlspecialchars($nombre_completo)); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="firma_turno" class="form-label">FIRMA DEL TURNO</label>
                                        <select class="form-select form-control-sm select2" id="firma_turno" name="firma_turno" >
                                        <!-- <option value="">SELECCCIONE..</option> -->
                                            <option></option>
                                            <?php foreach ($firmaTurno as $opcion) : ?>
                                                <option value="<?= $opcion->id_destinatario ?>"><?=  strtoupper($opcion->nombre_destinatario) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">ESTATUS</label>
                                        <select class="form-select form-control-sm " id="status" name="status">
                                                <!-- <option value="">SELECCCIONE..</option> -->
                                            <option></option>
                                            <?php foreach ($cat_estatus as $opcion) : ?>
                                                <?php
                                                    $selected = ($opcion->id_estatus == 1) ? 'selected' : '';
                                                ?>
                                                <option value="<?= $opcion->id_estatus ?>" <?= $selected ?>>
                                                    <?= strtoupper($opcion->dsc_status) ?>
                                                </option>
                                                <!-- <option value="<?= $opcion->id_estatus ?>"><?=  strtoupper($opcion->dsc_status) ?></option> -->
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--END CARD -->    
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card"><!--init card -->
                        <div class="card-body">

                            <h3 class="textoNegro">RESPUESTA DEL TURNO:</h3>
                                <div class="row">
                                    <div class="mb-3">
                                    <label for="id_resultado_turno" class="form-label">ESTATUS TURNO</label>
                                            <select class="form-select form-control-sm" id="id_resultado_turno" name="id_resultado_turno" >
                                            <!-- <option value="">SELECCCIONE..</option> -->
                                                <option></option>
                                                <?php foreach ($cat_resultado_turno as $opcion) : ?>
                                                    <?php
                                                        $selected = ($opcion->id_resultado_turno == 2) ? 'selected' : '';
                                                    ?>
                                                    <option value="<?= $opcion->id_resultado_turno ?>" <?= $selected ?>><?=  strtoupper($opcion->descripcion) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                    </div>
                                </div> 
                                <div class="row">  
                                    <div class="mb-3">
                                        <label for="resultado_turno" class="form-label">RESULTADO DEL TURNO</label>
                                        <textarea data-toggle="maxlength" class="form-control" maxlength="225" rows="5" 
                                            placeholder="Tiene un limite 225 caracteres." id="resultado_turno" name="resultado_turno" onkeyup="st.agregar.toUpperCase(this)"></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="mb-3">
                                        <label for="formFile" class="form-label">AGREGAR ARCHIVO</label>
                                        <input class="form-control" type="file" id="formFile">
                                    </div>
                                </div>

                        </div>
                    </div><!--END CARD -->
                </div>
            </div>
                <div class="row mb-5 ">
                        <div class="col-md-12 text-center ">
                            <button class="btn btn-info" type="submit"><i class="mdi mdi-content-save"></i> Guardar </button>
                            <button class="btn btn-warning" type="button" onclick="st.agregar.cancelarTurno();"><i class="mdi mdi-content-save-off-outline" id="cancelarTurno" ></i> Cancelar </button>
                        </div>
                </div>
        </form>    
    </div>
<script>
    $(document).ready(function(){
        st.agregar.saveTempccp();
        st.agregar.saveTempIndicacion();
        st.agregar.saveTempNombreTurno();
        st.agregar.agregarTurno();
        $('#nombre_turno').select2({
            placeholder: "SELECCCIONE..",
            maximumSelectionLength: 3,
            dropdownParent: $("#modalTurnarA") ,
        });
        $('#firma_turno').select2({
            placeholder: "SELECCCIONE..",
        });
        $('#asunto').select2({
            placeholder: "SELECCCIONE..",
        });
        $('#status').select2({
            placeholder: "SELECCCIONE..",
        });
        $('#cpp').select2({
            placeholder: "SELECCCIONE..",
            dropdownParent: $("#modal_cpp") ,
            maximumSelectionLength: 4,
            templateResult: function (data) {    
                if (!data.element) {
                return data.text;
                }
                var $element = $(data.element);
                var $wrapper = $('<span></span>');
                $wrapper.addClass($element[0].className);
                $wrapper.text(data.text);
                return $wrapper;
            }
        });
        $('#indicacion').select2({
            placeholder: "SELECCCIONE..",
            dropdownParent: $("#modal_indicacion"),
         });
       
       
       
        $('#fecha_peticion, #fecha_recepcion').datepicker({
            language: 'es'
        });
                
        $("#resumen, #titulo_inv,#segundo_apellido,#primer_apellido,#nombre_t,#cargo_inv ,#razon_social_inv").on("input", function() {
            console.log(st.agregar.validarEntrada($(this)));
        });


        $('#fecha_peticion').on('change', function() {
            var fechaIngresada = $(this).val().split('/').reverse().join('-');
            var fechaActual = new Date().toISOString().split('T')[0];

            if (new Date(fechaIngresada) > new Date(fechaActual)) {
                $('#fecha-error').show();
                $(this).val('');
            } else {
                $('#fecha-error').hide();
            }
        });
        $('#fecha_recepcion').on('change', function() {
            var fechaIngresada = $(this).val().split('/').reverse().join('-');
            var fechaActual = new Date().toISOString().split('T')[0];

            if (new Date(fechaIngresada) > new Date(fechaActual)) {
                $('#fecha-error2').show();
                $(this).val('');
            } else {
                $('#fecha-error2').hide();
            }
        });
        
       
        
       
    });
</script>
</body>
</html>
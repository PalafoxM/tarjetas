<!-- Page Content-->
<?php $session = \Config\Services::session(); ?>
<div class="page-content-tab">

    <div class="container-fluid">
        <!-- Page-Title -->
        <div class="row">
            <div class="col-sm-12">
                <div class="page-title-box">
                    <div class="float-right">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Metrica</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics</a></li>
                            <li class="breadcrumb-item active">Gestion de Uusarios</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Usuarios</h4>

                </div>
                <!--end page-title-box-->
            </div>
            <!--end col-->
        </div>

        <div class="row">

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-pills nav-justified" role="tablist">
                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link active" data-toggle="tab" href="#home-1" role="tab">Usuarios
                                    sin inconsistencias</a>
                            </li>

                            <li class="nav-item waves-effect waves-light">
                                <a class="nav-link" data-toggle="tab" href="#settings-1" role="tab">Usuarios con
                                    inconsistencias</a>
                            </li>
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane active p-3" id="home-1" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="d-flex justify-content-start">
                                            <button onclick="ini.inicio.enviarCorreo2()" id="btn_clean_detenidos"
                                                class="btn btn-success me-2">Enviar Correo Masivo</button>
                                            <button id="btn_clean_load" style="display:none"
                                                class="btn btn-gradient-success" type="button" disabled>
                                                <span class="spinner-border spinner-border-sm" role="status"
                                                    aria-hidden="true"></span>
                                                Enviando...
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-3">

                                    </div>
                                </div>
                                <br>
                                <table id="preinscritosTable" class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center">NOMBRE</th>
                                            <th class="text-center">CURP</th>
                                            <th class="text-center">RFC</th>
                                            <th class="text-center">CORREO</th>
                                            <th class="text-center">ACCIONES</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>

                                    <tbody>
                                        <?php if(isset($participantes) && !empty($participantes)): ?>
                                        <?php foreach($participantes as $u): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= $u->nombre.' '. $u->primer_apellido.' '. $u->segundo_apellido?></td>
                                            <td class="text-center"><?= $u->curp?></td>
                                            <td class="text-center"><?= $u->rfc?></td>
                                            <td class="text-center"><?= $u->correo?></td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" title="Editar" data-toggle="modal"
                                                    data-animation="bounce" data-target=".bs-example-modal-lg"
                                                    onclick="ini.inicio.getParticipante(<?=$u->id_participante?>)"><i
                                                        class="mdi mdi-pencil text-success font-18"></i></a>

                                                <a href="javascript:void(0);" title="Eliminar"
                                                    onclick="ini.inicio.deleteParticipante(<?=$u->id_participante?>)"><i
                                                        class="mdi mdi-trash-can text-danger font-18"></i></a>
                                                <a href="javascript:void(0);" title="Enviar credenciales"
                                                    onclick="ini.inicio.enviarCorreo(<?=$u->id_participante?>)"><i
                                                        class="mdi mdi-email-mark-as-unread text-warning font-18"></i></a>


                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="tab-pane p-3" id="settings-1" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-3">
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="d-flex justify-content-start">
                                            <button onclick="ini.inicio.cleanDetenidos()" id="btn_clean_detenidos"
                                                class="btn btn-danger me-2">Limpiar
                                                Inconsistencias</button>
                                            <button id="btn_clean_load" style="display:none"
                                                class="btn btn-gradient-danger" type="button" disabled>
                                                <span class="spinner-border spinner-border-sm" role="status"
                                                    aria-hidden="true"></span>
                                                Limpiando..
                                            </button>
                                        </div>
                                    </div>

                                </div>
                                <br>
                                <table id="detenidoTable" class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center">NOMBRE</th>
                                            <th class="text-center">CURP</th>
                                            <th class="text-center">CORREO</th>
                                            <th class="text-center">OBSERVACIONES</th>
                                            <th class="text-center">ACCIONES</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>

                                    <tbody>
                                        <?php if(isset($detenidos) && !empty($detenidos)): ?>
                                        <?php foreach($detenidos as $u): ?>
                                        <tr>
                                            <td class="text-center">
                                                <?= $u->nombre.' '. $u->primer_apellido.' '. $u->segundo_apellido?></td>
                                            <td class="text-center"><?= $u->curp?></td>
                                            <td class="text-center"><?= $u->correo?></td>
                                            <td class="text-center"><span
                                                    class="text-danger"><?= $u->observaciones?></span></td>
                                            <td class="text-center">
                                                <a href="javascript:void(0);" data-toggle="modal"
                                                    data-animation="bounce" data-target=".bs-example-modal-lg"
                                                    onclick="ini.inicio.getDetenido(<?=$u->id_detenido?>)"><i
                                                        class="mdi mdi-pencil text-success font-18"></i></a>
                                                <a href="javascript:void(0);"
                                                    onclick="ini.inicio.deleteDetenido(<?=$u->id_detenido?>)"><i
                                                        class="mdi mdi-trash-can text-danger font-18"></i></a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--end card-body-->
                </div>
                <!--end card-->
            </div>
            <!--end col-->
        </div>
        <!--end row-->
    </div><!-- container -->
</div>

<!--MODAL -->
<div id="modalDetenidos" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body">
                <form id="formParticipante" name="formParticipante">
                    <input type="hidden" value="0" id="editar" name="editar">
                    <input type="hidden" value="0" id="id_detenido" name="id_detenido">
                    <input type="hidden" value="0" id="id_participante" name="id_participante">
                    <input type="hidden" id="curp_viejo" name="curp_viejo">
                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-3">
                                <label for="curp" class="form-label">CURP</label>
                                <div class="input-group flex-nowrap">
                                    <span class="input-group-text" id="basic-addon1"><i id="icono"
                                            class="dripicons-search"></i>
                                        <div style="display:none;" id="spinner" class="spinner-border" role="status">
                                            <span class="visually-hidden"></span>
                                        </div>
                                    </span>
                                    <input type="text" class="form-control" oninput="st.agregar.validarCURP()"
                                        placeholder="CURP" aria-label="Username" id="curp" name="curp"
                                        aria-describedby="basic-addon1" autocomplete="off">
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
                                        <option>seleccione</option>
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
                                        <option>Seleccione</option>
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
                                    <label for="id_dependencia" class="form-label">DEPENDENCIA</label>
                                    <select class="form-control select2" id="id_dependencia" name="id_dependencia"
                                        style="z-index:100;" disabled>
                                        <option>Seleccione</option>
                                        <?php foreach ($cat_dependencia as $dep): ?>
                                        <option value="<?php echo $dep->id_dependencia; ?>"
                                            <?php echo ($dep->id_dependencia == $session->get('id_dependencia')) ? 'selected' : ''; ?>>
                                            <?php echo $dep->dsc_dependencia; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="funcion" class="form-label campoObligatorio">FUNCION</label>
                                    <input type="text" autocomplete="off" class="form-control" id="funcion"
                                        name="funcion" placeholder="DENOMINACION FUNCIONAL"
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
                                    <label for="id_p" class="form-label">MUNICIPIO</label>
                                    <select class="form-control select2" data-toggle="select2" id="id_municipio"
                                        name="id_municipio" data-placeholder="Seleccione" style="z-index:100;">
                                        <option>Seleccione</option>
                                        <?php foreach ($cat_municipio as $p): ?>
                                        <option value="<?php echo $p->id_municipio; ?>">
                                            <?php echo $p->dsc_municipio ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="centro_gestor" class="form-label campoObligatorio">CENTRO GESTOR</label>
                                    <input type="text" autocomplete="off" class="form-control" id="centro_gestor"
                                        name="centro_gestor" placeholder="CENTRO GESTOR"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="correo_enlace" class="form-label campoObligatorio">CORREO ENLACE</label>
                                    <input type="text" autocomplete="off" class="form-control" id="correo_enlace"
                                        name="correo_enlace" placeholder="CORREO ENLACE">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3 position-relative" id="">
                                    <label for="denominacion_funcional" class="form-label campoObligatorio">DEMONINACION
                                        FUNCIONAL</label>
                                    <input type="text" autocomplete="off" class="form-control"
                                        id="denominacion_funcional" name="denominacion_funcional"
                                        placeholder="DEMONINACION FUNCIONAL"
                                        oninput="this.value = this.value.toUpperCase();">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" id="btn_guardar_detenido">
                        <button type="button" class="btn btn-light" id="closeModalButton"
                            data-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>

                    </div>
                    <div class="modal-footer" style="display:none" id="btn_load_detenido">
                        <button class="btn btn-primary" type="button" disabled>
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Loading...
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<link href="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet"
    type="text/css" />

<!-- App css -->
<link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
<link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
<link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />



<!-- jQuery  -->
<script src="<?php echo base_url(); ?>assets/js/jquery.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/jquery-ui.min.js"></script>
<script src="<?php echo base_url(); ?>assets/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo base_url(); ?>assets/js/jquery.slimscroll.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/apexcharts/apexcharts.min.js"></script>

<!-- Required datatable js -->
<script src="<?php echo base_url(); ?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.js"></script>

<script src="<?php echo base_url(); ?>assets/pages/jquery.analytics_customers.init.js"></script>
<script>
st.agregar.formParticipante();
$('#preinscritosTable,#detenidoTable').DataTable({
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' // Ruta al archivo de localización
    },
    destroy: true,
    searching: true,
});
</script>
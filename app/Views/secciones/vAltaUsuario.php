<!-- Top Bar End -->
<?php  $session = \Config\Services::session();    ?>
<div class="page-wrapper">

    <!-- Page Content-->
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
                                <li class="breadcrumb-item active">Usuarios</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Usuario</h4>

                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive dash-social">
                                <form id="formAgregarUsuarioTsi" name="formAgregarUsuarioTsi">
                                    <input type="hidden" value=<?= $editar ?> name="editar">
                                    <input type="hidden" value="0" name="id_usuario">
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
                                                                <span class="input-group-text" id="basic-addon1"><i
                                                                        id="icono" class="dripicons-search"></i>
                                                                    <div style="display:none;" id="spinner"
                                                                        class="spinner-border" role="status">
                                                                        <span class="visually-hidden"></span>
                                                                    </div>
                                                                </span>
                                                                <input type="text" class="form-control"
                                                                    oninput="st.agregar.validarCURP()"
                                                                    placeholder="CURP" aria-label="Username" id="curp"
                                                                    name="curp" aria-describedby="basic-addon1"
                                                                    autocomplete="off">
                                                            </div>

                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="nombre"
                                                                    class="form-label campoObligatorio">NOMBRE</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="nombre" name="nombre"
                                                                    placeholder="NOMBRE">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="primer_apellido"
                                                                    class="form-label campoObligatorio">PRIMER
                                                                    APELLIDO</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="primer_apellido"
                                                                    name="primer_apellido"
                                                                    placeholder="PRIMER APELLIDO">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="segundo_apellido"
                                                                    class="form-label campoObligatorio">SEGUNDO
                                                                    APELLIDO</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="segundo_apellido"
                                                                    name="segundo_apellido"
                                                                    placeholder="SEGUNDO APELLIDO">
                                                            </div>
                                                        </div>


                                                    </div>
                                                    <div class="row">

                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="fec_nac"
                                                                    class="form-label campoObligatorio">FECHA
                                                                    NACIMIENTO</label>
                                                                <input type="date" autocomplete="off"
                                                                    class="form-control" id="fec_nac" name="fec_nac"
                                                                    placeholder="FEC. NACIMIENTO">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="rfc"
                                                                    class="form-label campoObligatorio">RFC</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="rfc" name="rfc"
                                                                    placeholder="NOMBRE COMPLETO">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="correo"
                                                                    class="form-label campoObligatorio">CORREO</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="correo" name="correo"
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
                                                                <label for="id_nivel" class="form-label">NIVEL
                                                                    TABULAR</label>
                                                                <select class="form-control select2"
                                                                    data-toggle="select2" id="id_nivel" name="id_nivel"
                                                                    data-placeholder="Seleccione" style="z-index:100;">
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
                                                            <div class="mb-6 position-relative">
                                                                <label for="id_dependencia"
                                                                    class="form-label">DEPENDENCIA</label>
                                                                <select class="form-control select2"
                                                                    data-toggle="select2" id="id_dependencia"
                                                                    name="id_dependencia" data-placeholder="Seleccione"
                                                                    style="z-index:100;"
                                                                    <?= ($session->id_perfil == 1 || $session->id_perfil == 4 ) ? '' : 'disabled' ?>>
                                                                    <?php foreach ($cat_dependencia as $dep): ?>
                                                                    <option value="<?= $dep->id_dependencia; ?>"
                                                                        <?= ($session->id_perfil != 1 && $session->id_dependencia == $dep->id_dependencia) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($dep->dsc_dependencia) ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <br>
                                                                <label for="denominacion_funcional"
                                                                    class="form-label campoObligatorio">FUNCION</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="denominacion_funcional"
                                                                    name="denominacion_funcional"
                                                                    placeholder="DENOMINACION FUNCIONAL"
                                                                    oninput="this.value = this.value.toUpperCase();">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <br>
                                                                <label for="area"
                                                                    class="form-label campoObligatorio">AREA
                                                                    PERSONAL</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="area" name="area"
                                                                    placeholder="GRUPO"
                                                                    oninput="this.value = this.value.toUpperCase();">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-6 position-relative" id="">
                                                                <br>
                                                                <label for="jefe_inmediato"
                                                                    class="form-label campoObligatorio">FEJE/A
                                                                    INMEDIATO</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="jefe_inmediato"
                                                                    name="jefe_inmediato" placeholder="SUPERVISOR"
                                                                    oninput="this.value = this.value.toUpperCase();">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="id_perfil" class="form-label">PERFIL</label>
                                                                <select class="form-control select2"
                                                                    data-toggle="select2" id="id_perfil"
                                                                    name="id_perfil" data-placeholder="Seleccione"
                                                                    style="z-index:100;"
                                                                    <?= ($session->id_perfil == 1) ? '' : 'disabled' ?>>
                                                                    <option value="0">Seleccione</option>
                                                                    <?php if ($session->id_perfil == 1): ?>
                                                                    <?php foreach ($cat_perfil as $p): ?>
                                                                    <option
                                                                        value="<?php echo htmlspecialchars($p->id_perfil); ?>">
                                                                        <?php echo htmlspecialchars($p->dsc_perfil); ?>
                                                                    </option>
                                                                    <?php endforeach; ?>
                                                                    <?php elseif ($session->id_perfil == 4): ?>
                                                                    <option value="6" selected>ENLACE RH NO SAP</option>
                                                                    <?php elseif ($session->id_perfil == 6): ?>
                                                                    <option value="8" selected>ESTUDIANTE</option>
                                                                    <?php endif; ?>


                                                                </select>

                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="usuario"
                                                                    class="form-label campoObligatorio">USUARIO</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control" id="usuario" name="usuario"
                                                                    placeholder="USUARIO"
                                                                    oninput="this.value = this.value.toUpperCase();">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="contrasenia"
                                                                    class="form-label campoObligatorio">CONTRASEÑA</label>
                                                                <input type="password" autocomplete="off"
                                                                    class="form-control" id="contrasenia"
                                                                    name="contrasenia" placeholder="CONTRASEÑA"
                                                                    oninput="this.value = this.value.toUpperCase();">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="mb-3 position-relative" id="">
                                                                <label for="confirmar_contrasenia"
                                                                    class="form-label campoObligatorio">CONFIRMAR
                                                                    CONTRASEÑA</label>
                                                                <input type="password" autocomplete="off"
                                                                    class="form-control" id="confirmar_contrasenia"
                                                                    name="confirmar_contrasenia" placeholder="CONFIRMAR"
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
                                            <button class="btn btn-info" type="submit"><i
                                                    class="mdi mdi-content-save"></i> Guardar
                                            </button>
                                            <button class="btn btn-warning" type="button"
                                                onclick="window.history.back();"><i
                                                    class="mdi mdi-content-save-off-outline" id="cancelarTurno"></i>
                                                Atrás
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row mb-5" id="btn_load" style="display:none;">
                                        <div class="col-md-12 text-center ">
                                            <button class="btn btn-info" type="button" disabled>
                                                <span class="spinner-grow spinner-grow-sm me-1" role="status"
                                                    aria-hidden="true"></span>
                                                Guardando...
                                            </button>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    <div id="agregarUsuario" class="modal fade bs-example" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alta Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="detalleCurso" style="max-height: 70vh; overflow-y: auto;">



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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    $(document).ready(function() {
        st.agregar.agregarUsuario();
        $('#id_dependencia').select2({
            placeholder: "Seleccione",
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true,
            minimumResultsForSearch: 10 // Muestra la barra de búsqueda si hay más de 10 opciones
        });
        $('#id_nivel').select2({
            placeholder: "Seleccione",
            allowClear: true,
            width: '100%',
            dropdownAutoWidth: true,
            minimumResultsForSearch: 10 // Muestra la barra de búsqueda si hay más de 10 opciones
        });

    });
    </script>
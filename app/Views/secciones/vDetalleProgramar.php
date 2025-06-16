<style>
.jumbotron-with-bg {
    background-image: url('<?php echo base_url().$curso->img_ruta ?>');
    /* Cambia la ruta por la de tu imagen */
    background-size: cover;
    /* Ajusta la imagen para cubrir todo el fondo */
    background-position: center;
    /* Centra la imagen */
    color: white;
    /* Cambia el color del texto para que sea legible sobre la imagen */
    padding: 100px 0;
    /* Ajusta el padding para dar más espacio */
}
</style>
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
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
                                <li class="breadcrumb-item active">Detalle Curso</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Detalle Curso</h4>
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>
            <!-- end page title end breadcrumb -->
            <div class="row">
                <div class="col-12">
                    <div class="card" id="tourJumbotron">
                        <div class="jumbotron bg-light mb-0 jumbotron-with-bg">
                            <!-- Agregué la clase "jumbotron-with-bg" -->
                            <h1 class="display-5 font-weight-normal"><?= $curso->dsc_curso?></h1>

                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <input type="hidden" value="0" id="editar_detalle" >
            <input type="hidden" value="<?php echo (isset($id_periodo_editar) && !empty($id_periodo_editar))?$id_periodo_editar:0 ?>" id="id_periodo_editar" >
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($registro): ?>
                            <div class="row" id="mensajePrograma">
                                <div class="col-lg-6">
                                    <div class="alert alert-info border-0" role="alert">
                                        <strong>¡Atención!</strong> Usted ya tiene programado este curso.
                                    </div>
                                </div>
                                <div class="col-lg-3">
                                    <button type="button" id="btnEditar"
                                        class="btn btn-primary waves-effect waves-light">Editar</button>
                                    <button type="button" onclick="st.agregar.btnEliminar(<?php echo (isset($id_periodo_editar) && !empty($id_periodo_editar))?$id_periodo_editar:0 ?>)"
                                        class="btn btn-danger waves-effect waves-light">Eliminar</button>
                                </div>
                                <div class="col-lg-3"></div>
                            </div>
                            <?php endif; ?>

                            <!-- Contenedor de los radios buttons (oculto inicialmente si $registro es true) -->
                            <div id="radiosContainer" style="<?= $registro ? 'display: none;' : '' ?>">
                                <p class="text-muted font-13 mt-3 mb-2">Seleccione Periodo</p>
                                <?php if ($periodo): ?>
                                <?php foreach ($periodo as $p): ?>
                                <div class="radio radio-info form-check-inline">
                                    <input type="radio" id="periodo_<?= $p->id_periodo_sac ?>"
                                        value="<?= $p->id_periodo_sac ?>" name="periodo">
                                    <label for="periodo_<?= $p->id_periodo_sac ?>">
                                        <strong><?= $p->dia_inicio ?> AL <?= $p->dia_fin; ?> DE <?= $p->dsc_mes ?> /
                                            P<?= $p->periodo ?></strong>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div> <!-- end card-body-->
                    </div> <!-- end card-->
                </div><!-- end col -->
            </div><!-- end row -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0 mb-3">Descripción del Curso</h4>
                            <div class="table-responsive">
                                <?= $curso->des_larga;?>
                            </div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mt-0 mb-3">Detalles</h4>
                            <div class="slimscroll crm-dash-activity">
                                <div class="activity">
                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-alert-decagram bg-soft-success"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0 w-75">Autogestivo</h6>
                                            </div>
                                            <p class="text-muted mt-3">
                                                <?= $curso->autogestivo;?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-checkbox-marked-circle-outline bg-soft-pink"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0  w-75">Curso de linea</h6>

                                            </div>
                                            <p class="text-muted mt-3">
                                                <?= $curso->curso_linea;?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-timer-off bg-soft-purple"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0  w-75">Duración</h6>
                                            </div>
                                            <p class="text-muted mt-3">
                                                <?= $curso->duracion;?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-timer-off bg-soft-warning"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0">Horas</h6>
                                            </div>
                                            <p class="text-muted mt-3">
                                                <?= $curso->horas;?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-clipboard-alert bg-soft-secondary"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0">Dirigido</h6>
                                            </div>
                                            <p class="text-muted mt-3">
                                                <?= $curso->dirigido;?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!--end activity-->
                            </div>
                            <!--end crm-dash-activity-->
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <!--end col-->
            </div>
            <div class="modal-footer" id="guardar_programa">
                <a href="<?php echo base_url() . 'index.php/Agregar/ProgramarCurso' ?>"
                    class="btn btn-secondary">Atrás</a>

                <div id="btnYaProgramado" style="<?= !$registro ? 'display: none;' : '' ?>">
                    <button type="button"  class="btn btn-info" disabled>Usted ya ha programado este
                        curso</button>
                </div>
                <div id="btnProgramarCurso" style="<?= $registro ? 'display: none;' : '' ?>">
                    <button type="button" class="btn btn-primary"
                        onclick="st.agregar.programar(<?= $curso->id_cursos_sac ?>);">Programar Curso</button>
                </div>
            </div>
            <div class="modal-footer" id="load_programar_curso" style="display:none;">
                <button class="btn btn-gradient-primary" type="button" disabled>
                    <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                    Loading...
                </button>
            </div>
        </div>
        <!--end row-->
    </div><!-- container -->
    <!-- end page content -->
</div>



<script src="<?= base_url()?>assets/js/jquery.min.js"></script>
<script src="<?= base_url()?>assets/js/jquery-ui.min.js"></script>
<script src="<?= base_url()?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url()?>assets/js/metismenu.min.js"></script>
<script src="<?= base_url()?>assets/js/waves.js"></script>
<script src="<?= base_url()?>assets/js/feather.min.js"></script>
<script src="<?= base_url()?>assets/js/jquery.slimscroll.min.js"></script>
<script src="<?= base_url()?>plugins/apexcharts/apexcharts.min.js"></script>


<script>
document.getElementById('btnEditar').addEventListener('click', function() {
    // Mostrar el contenedor de los radios buttons
    $('#btnProgramarCurso').show();
    $('#radiosContainer').show();
    $('#mensajePrograma').hide();
    $('#btnYaProgramado').hide();
    $("#editar_detalle").val(1);
});
</script>
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
                                <li class="breadcrumb-item active">Programar Curso</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Programar Curso</h4>
                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>
            <!-- end page title end breadcrumb -->
            <div class="row">
                <?php foreach($cursos as $c): ?>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="blog-card">
                                <img src="<?= base_url().$c->img_ruta;?>" alt="" style="width: 100%; height: 100%;" />
                                <?php if($c->nuevo==1): ?>
                                <span
                                    class="badge badge-purple px-3 py-2 bg-soft-secondary font-weight-semibold mt-3">Nuevo</span>
                                <?php endif; ?>
                                <h4 class="my-3">
                                    <a href="" class=""><?= $c->dsc_curso ?></a>
                                </h4>
                                <p class="text-muted"><?= $c->descripcion ?></p>
                                <hr class="hr-dashed">
                                <div class="d-flex justify-content-between">
                                    <div class="meta-box">

                                    </div>
                                    <!--end meta-box-->
                                    <div class="align-self-center">
                                        <a href="<?php echo base_url().'index.php/Agregar/detalleCurso/'.$c->id_cursos_sac ?>"
                                            type="button" class="btn btn-secondary">Ver Detalles</a>
                                    </div>
                                </div>
                            </div>
                            <!--end blog-card-->

                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!--end row-->
    </div><!-- container -->
    <!-- end page content -->
</div>

<div id="modalProgramar" class="modal fade bs-example-modal-lg2" tabindex="-1" role="dialog"
    aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">
                <div class="row">
                    <div class="col-12">
                        <div class="card" id="tourJumbotron">
                            <div class="jumbotron bg-light mb-0 jumbotron-with-bg" id="jumbotron-con-imagen">
                                <h1 class="display-5 font-weight-normal" id="nombre_curso"></h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="header-title mt-0 mb-3">Descripción del Curso</h4>
                                <div class="table-responsive" id="textEditorCurso">

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
                                                <p class="text-muted mt-3" id="autogestivo_detalle">

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
                                                <p class="text-muted mt-3" id="curso_linea_detalle">

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
                                                <p class="text-muted mt-3" id="duracion_detalle">

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
                                                <p class="text-muted mt-3" id="horas_detalle">

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
                                                <p class="text-muted mt-3" id="dirigido_detalle">
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
                <!--end row-->
                <!--end row-->

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="programarCurso"
                    onclick="alerta_prueba()">Programar</button>
            </div>

        </div>
    </div>
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
function alerta_prueba() {
    Swal.fire("Error", "Error al consultar la base de datos", "success");
}
</script>
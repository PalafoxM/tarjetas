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
                                <li class="breadcrumb-item active">Programación por usuario</li>
                            </ol>
                        </div>
                        <h4 class="page-title">Tabla de Programación</h4>

                    </div>
                    <!--end page-title-box-->
                </div>
                <!--end col-->
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <a data-toggle="modal" style="color:white" data-animation="bounce"
                                data-target=".bs-solicitud"
                                class="btn btn-gradient-primary px-4 float-right mt-0 mb-3"><i
                                    class="mdi mdi-arrow-right-thick mr-2"></i>Solicitar Curso</a>
                            <div class="table-responsive dash-social">
                                <table id="tablaProgramacion" class="table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th class="text-center">P1</th>
                                            <th class="text-center">P2</th>
                                            <th class="text-center">P3</th>
                                            <th class="text-center">P4</th>
                                            <th class="text-center">P5</th>
                                            <th class="text-center">P6</th>
                                            <th class="text-center">P7</th>
                                            <th class="text-center">P8</th>
                                            <th class="text-center">P9</th>
                                        </tr>
                                        <!--end tr-->
                                    </thead>

                                    <tbody>
                                        <?php foreach($usuario as $u): ?>
                                        <tr>
                                            <td class="text-center"><?= $u['nombre']?></td>
                                            <td class="text-center"><?= $u['P1']?></td>
                                            <td class="text-center"><?= $u['P2']?></td>
                                            <td class="text-center"><?= $u['P3']?></td>
                                            <td class="text-center"><?= $u['P4']?></td>
                                            <td class="text-center"><?= $u['P5']?></td>
                                            <td class="text-center"><?= $u['P6']?></td>
                                            <td class="text-center"><?= $u['P7']?></td>
                                            <td class="text-center"><?= $u['P8']?></td>
                                            <td class="text-center"><?= $u['P9']?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--Modal -->
    <div id="solicitudCurso" class="modal fade bs-solicitud" tabindex="-1" role="dialog"
        aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Solicitud del Curso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="accordion" id="accordionExample">
                                <?php if (isset($cursos_sac) && !empty($cursos_sac)): ?>
                                <?php foreach ($cursos_sac as $s): ?>
                                <form id="formCurso<?= $s['id_cursos_sac'] ?>" class="mt-3">
                                    <div class="card border mb-1 shadow-none">
                                        <div class="card-header" id="heading<?= $s['id_cursos_sac'] ?>">
                                            <a href="" class="text-dark" data-toggle="collapse"
                                                data-target="#collapse<?= $s['id_cursos_sac'] ?>" aria-expanded="true"
                                                aria-controls="collapse<?= $s['id_cursos_sac'] ?>">
                                                <?= $s['dsc_curso'] ?>

                                                <button type="button" class="btn btn-secondary btn-circle"
                                                    data-toggle="tooltip" data-placement="top"
                                                    title="Estudiantes">
                                                    <i class="fas fa-user-friends"></i>
                                                </button>
                                            </a>
                                        </div>
                                        <div id="collapse<?= $s['id_cursos_sac'] ?>" class="collapse"
                                            aria-labelledby="heading<?= $s['id_cursos_sac'] ?>"
                                            data-parent="#accordionExample">
                                            <div class="card-body">
                                                <!-- Iterar sobre los periodos del curso -->
                                                <?php if (!empty($s['contador'])): ?>
                                                    <?php $i = 1; ?>
                                                <?php foreach ($s['contador'] as $k): ?>
                                                    <?php if($k != 0): ?>
                                                    <?php $plural = ($k == 1)?'PARTICIPANTE':'PARTICIPANTES' ?>
                                                <p class="mb-0 text-muted"><?= 'PERIODO ' . $i .' = '. $k .' '. $plural  ?></p>
                                                    <?php endif; ?>
                                                <?php $i++ ?>
                                                <?php endforeach; ?>
                                                <?php else: ?>
                                                <p class="mb-0 text-muted">No hay periodos registrados.</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" id="btn_csv">
                        <button type="button" class="btn btn-light" data-dismiss="modal"
                            aria-label="Close">Cerrar</button>
                        <button type="submit" class="btn btn-primary" onclick="st.agregar.crearEvento()">Crear
                            Eventos</button>
                    </div>
                    <div class="modal-footer" id="load_csv" style="display:none">
                        <button class="btn btn-primary mt-3">
                            <div class="spinner-grow" role="status">
                                <span class="visually-hidden">.</span>
                            </div>
                        </button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        <!-- modal -->
        <!--Modal -->



        <link href="<?php echo base_url(); ?>plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet"
            type="text/css" />

        <!-- App css -->
        <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
        <link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />


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


        <script src="<?php echo base_url(); ?>assets/js/metismenu.min.js"></script>
        <script src="<?php echo base_url(); ?>assets/js/waves.js"></script>
        <script src="<?php echo base_url(); ?>assets/js/feather.min.js"></script>



        <!-- App js -->
        <script src="<?php echo base_url(); ?>assets/js/jquery.core.js"></script>




        <script>
        $('#tablaProgramacion').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' // Ruta al archivo de localización
            },
            destroy: true,
            searching: true,
        });
        </script>
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
                            <li class="breadcrumb-item active">Cursos listos</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Cursos listo para Inscripción</h4>

                </div>
                <!--end page-title-box-->
            </div>
            <!--end col-->
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <h4 class="header-title mt-0">Cursos listos</h4>
                        <?php $fec_ini = (isset($fec_inicio))?date("Y-m-d", strtotime($fec_inicio)):''; ?>

                        <div class="table-responsive dash-social">

                            <table class="table table-centered mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>NOMBRE</th>
                                        <th>FECHA INICIO</th>
                                        <th>FECHA FIN</th>
                                        <th>ACCION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($curso['id']) ?></td>
                                        <td><?= htmlspecialchars($curso['fullname']) ?></td>
                                        <td><?= htmlspecialchars($curso['startdate']) ?></td>
                                        <td><?= htmlspecialchars($curso['enddate']) ?></td>
                                        <td>
                                            <?php if ($session->id_perfil !== 3 && $session->id_perfil !== 4): ?>
                                            <button type="button" class="btn btn-secondary rounded-pill"
                                                onclick="matricular(<?= $curso['id'] ?>)">Preinscribir</button>
                                            <?php endif; ?>

                                            <a href="<?= base_url('index.php/Principal/Preinscritos/'.$curso['id']) ?>"
                                                type="button" class="btn btn-light  rounded-pill">Preinscritos</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>


                            </table>

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
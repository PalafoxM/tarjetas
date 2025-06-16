<?php $session     = \Config\Services::session();?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>SAC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Sistema de Administración de Capacitación" name="description" />
    <meta content="SAC" name="author" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.ico">

    <!-- jvectormap -->
    <link href="<?php echo base_url(); ?>plugins/jvectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet">

    <!-- App css -->
    <link href="<?php echo base_url(); ?>assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/jquery-ui.min.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="<?php echo base_url(); ?>assets/css/app.min.css" rel="stylesheet" type="text/css" />

    <?php if (isset($scripts)): foreach ($scripts as $js): ?>
    <script src="<?php echo base_url() . "/js/{$js}.js" ?>?filever=<?php echo time() ?>" type="text/javascript">
    </script>
    <?php endforeach;
            endif;
        ?>

</head>

<body>
    <script>
    var base_url = "<?php echo base_url();?>";
    var token = "<?php echo env('TOKEN_API'); ?>";
    var api = "<?php echo env('NODE_API_CURP'); ?>";
    </script>

    <!-- leftbar-tab-menu -->
    <div class="leftbar-tab-menu">
        <div class="main-icon-menu">
            <a href="<?php echo base_url(); ?>analytics/analytics-index.html"
                class="logo logo-metrica d-block text-center">
                <span>
                    <img src="<?php echo base_url(); ?>assets/images/logo-sm.png" alt="logo-small" class="logo-sm">
                </span>
            </a>
            <nav class="nav">
                <?php if($session->id_perfil != 8): ?>
                <a href="#MetricaAnalytics" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                    title="" data-original-title="Dashboard" data-trigger="hover">
                    <i data-feather="bar-chart-2" class="align-self-center menu-icon icon-dual"></i>
                </a>
                <!--end MetricaAnalytics-->
                <?php endif; ?>

                <?php if($session->id_perfil != 8 && $session->id_perfil != 6): ?>
                <a href="#MetricaApps" class="nav-link" data-toggle="tooltip-custom" data-placement="right" title=""
                    data-original-title="Categorias/Cursos" data-trigger="hover">
                    <i data-feather="grid" class="align-self-center menu-icon icon-dual"></i>
                </a>
                <!--end MetricaApps-->
                <?php endif; ?>
                <?php if($session->id_perfil==1): ?>
                <a href="#MetricaUikit" class="nav-link" data-toggle="tooltip-custom" data-placement="right" title=""
                    data-original-title="Administrador" data-trigger="hover">
                    <i data-feather="package" class="align-self-center menu-icon icon-dual"></i>
                </a>
                <!--end MetricaUikit-->
                <?php endif; ?>
                <a href="#MetricaPages" class="nav-link" data-toggle="tooltip-custom" data-placement="right" title=""
                    data-original-title="Programar" data-trigger="hover">
                    <i data-feather="copy" class="align-self-center menu-icon icon-dual"></i>
                </a>
                <!--end MetricaPages-->


            </nav>
            <!--end nav-->
            <div class="pro-metrica-end">
                <a href="" class="help" data-toggle="tooltip-custom" data-placement="right" title=""
                    data-original-title="Chat">
                    <i data-feather="message-circle" class="align-self-center menu-icon icon-md icon-dual mb-4"></i>
                </a>
                <a href="" class="profile">
                    <img src="<?php echo base_url(); ?>assets/images/users/user-4.jpg" alt="profile-user"
                        class="rounded-circle thumb-sm">
                </a>
            </div>
        </div>
        <!--end main-icon-menu-->

        <div class="main-menu-inner">
            <!-- LOGO -->
            <div class="topbar-left">
                <a href="<?php echo base_url(); ?>index.php/Inicio" class="logo">
                    <h2>SAC</h2>
                </a>
            </div>
            <!--end logo-->
            <div class="menu-body slimscroll">
                <div id="MetricaAnalytics" class="main-icon-menu-pane">
                    <div class="title-box">
                        <h6 class="menu-title">Dashboard</h6>
                    </div>

                    <ul class="nav">
                        <?php if($session->id_perfil != 8): ?>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/usuarios">Lista
                                <?=($session->id_perfil==4)?'Enlace':'Estudiantes'?></a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/Preinscritos">Detenidos y/o
                                Preinscritos</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>analytics/analytics-customers.html">Calificaciones</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>analytics/analytics-reports.html">No aprobados</a></li>
                    </ul>
                </div><!-- end Analytic -->

                <div id="MetricaApps" class="main-icon-menu-pane">
                    <div class="title-box">
                        <h6 class="menu-title">Categoria/Curso</h6>
                    </div>
                    <ul class="nav metismenu">

                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/categorias">Categorias</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Principal/Matricular">Cursos</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>apps/calendar.html">Pre-Inscripción</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>apps/invoice.html">Inscritos</a></li>
                    </ul>
                </div><!-- end Crypto -->
                <?php if($session->id_perfil==1): ?>
                <div id="MetricaUikit" class="main-icon-menu-pane">
                    <div class="title-box">
                        <h6 class="menu-title">Admin</h6>
                    </div>
                    <ul class="nav">

                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/adminCategorias">Administración</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/usuarios">Lista Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Inicio/altaUsuario">Alta usuario</a></li>

                    </ul>
                </div><!-- end Others -->
                <?php endif; ?>
                <div id="MetricaPages" class="main-icon-menu-pane">
                    <div class="title-box">
                        <h6 class="menu-title">Programar Cursos</h6>
                    </div>
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Agregar/ProgramarCurso">Programar Cursos</a>
                        </li>
                        <?php if($session->id_perfil != 8): ?>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Agregar/TablaPrograma">Progamación Por Usuario</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>index.php/Agregar/TablaProgramaCurso">Progamación Por Curso</a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>pages/pages-treeview.html">Constancias</a></li>

                    </ul>
                </div><!-- end Pages -->
                <div id="MetricaAuthentication" class="main-icon-menu-pane">
                    <div class="title-box">
                        <h6 class="menu-title">Authentication</h6>
                    </div>
                    <ul class="nav">
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-login.html">Log in</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-login-alt.html">Log in alt</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-register.html">Register</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-register-alt.html">Register-alt</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-recover-pw.html">Re-Password</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-recover-pw-alt.html">Re-Password-alt</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-lock-screen.html">Lock Screen</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-lock-screen-alt.html">Lock Screen</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-404.html">Error 404</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-404-alt.html">Error 404-alt</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-500.html">Error 500</a></li>
                        <li class="nav-item"><a class="nav-link"
                                href="<?php echo base_url(); ?>authentication/auth-500-alt.html">Error 500-alt</a></li>
                    </ul>
                </div><!-- end Authentication-->
            </div>
            <!--end menu-body-->
        </div><!-- end main-menu-inner-->
    </div>
    <!-- end leftbar-tab-menu-->

    <!-- Top Bar Start -->
    <div class="topbar">
        <!-- Navbar -->
        <nav class="navbar-custom">
            <ul class="list-unstyled topbar-nav float-right mb-0">
                <li class="hidden-sm">
                    <a class="nav-link dropdown-toggle waves-effect waves-light" data-toggle="dropdown"
                        href="javascript: void(0);" role="button" aria-haspopup="false" aria-expanded="false">
                        <i
                            class="fas fa-cart-arrow-down font-20 <?php echo (isset($dscCursos) && !empty($dscCursos))?'text-success':''?>"></i>
                        <i class="mdi mdi-chevron-down"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php if(isset($dscCursos) && !empty($dscCursos)):?>
                        <?php foreach($dscCursos as $k):?>
                        <a class="dropdown-item uitooltip" data-toggle="tooltip" data-placement="top"
                            title="<?= 'PERIODO '.$k['periodo']?>"
                            href="<?= base_url().'index.php/Agregar/detalleCurso/'.$k['id'] ?>"><span><?= $k['dsc_curso']?></span><img
                                src="<?php echo base_url().$k['img']; ?>" alt="" class="ml-2 float-right"
                                height="14" /></a>
                        <?php endforeach;?>
                        <?php endif; ?>
                        <?php if(isset($dscCursos) && empty($dscCursos)):?>
                        <span class="dropdown-item">Sin cursos en el carrito</span>
                        <?php endif; ?>
                    </div>
                </li>

                <li class="dropdown notification-list">
                    <a class="nav-link dropdown-toggle arrow-none waves-light waves-effect" data-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti-bell noti-icon"></i>
                        <span class="badge badge-danger badge-pill noti-icon-badge">2</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-lg pt-0">

                        <h6
                            class="dropdown-item-text font-15 m-0 py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                            Notifications <span class="badge badge-light badge-pill">2</span>
                        </h6>
                        <div class="slimscroll notification-list">
                            <!-- item-->
                            <a href="#" class="dropdown-item py-3">
                                <small class="float-right text-muted pl-2">2 min ago</small>
                                <div class="media">
                                    <div class="avatar-md bg-primary">
                                        <i class="la la-cart-arrow-down text-white"></i>
                                    </div>
                                    <div class="media-body align-self-center ml-2 text-truncate">
                                        <h6 class="my-0 font-weight-normal text-dark">Your order is placed</h6>
                                        <small class="text-muted mb-0">Dummy text of the printing and industry.</small>
                                    </div>
                                    <!--end media-body-->
                                </div>
                                <!--end media-->
                            </a>
                            <!--end-item-->
                            <!-- item-->
                            <a href="#" class="dropdown-item py-3">
                                <small class="float-right text-muted pl-2">10 min ago</small>
                                <div class="media">
                                    <div class="avatar-md bg-success">
                                        <i class="la la-group text-white"></i>
                                    </div>
                                    <div class="media-body align-self-center ml-2 text-truncate">
                                        <h6 class="my-0 font-weight-normal text-dark">Meeting with designers</h6>
                                        <small class="text-muted mb-0">It is a long established fact that a
                                            reader.</small>
                                    </div>
                                    <!--end media-body-->
                                </div>
                                <!--end media-->
                            </a>
                            <!--end-item-->
                            <!-- item-->
                            <a href="#" class="dropdown-item py-3">
                                <small class="float-right text-muted pl-2">40 min ago</small>
                                <div class="media">
                                    <div class="avatar-md bg-pink">
                                        <i class="la la-list-alt text-white"></i>
                                    </div>
                                    <div class="media-body align-self-center ml-2 text-truncate">
                                        <h6 class="my-0 font-weight-normal text-dark">UX 3 Task complete.</h6>
                                        <small class="text-muted mb-0">Dummy text of the printing.</small>
                                    </div>
                                    <!--end media-body-->
                                </div>
                                <!--end media-->
                            </a>
                            <!--end-item-->
                            <!-- item-->
                            <a href="#" class="dropdown-item py-3">
                                <small class="float-right text-muted pl-2">1 hr ago</small>
                                <div class="media">
                                    <div class="avatar-md bg-warning">
                                        <i class="la la-truck text-white"></i>
                                    </div>
                                    <div class="media-body align-self-center ml-2 text-truncate">
                                        <h6 class="my-0 font-weight-normal text-dark">Your order is placed</h6>
                                        <small class="text-muted mb-0">It is a long established fact that a
                                            reader.</small>
                                    </div>
                                    <!--end media-body-->
                                </div>
                                <!--end media-->
                            </a>
                            <!--end-item-->
                            <!-- item-->
                            <a href="#" class="dropdown-item py-3">
                                <small class="float-right text-muted pl-2">2 hrs ago</small>
                                <div class="media">
                                    <div class="avatar-md bg-info">
                                        <i class="la la-check-circle text-white"></i>
                                    </div>
                                    <div class="media-body align-self-center ml-2 text-truncate">
                                        <h6 class="my-0 font-weight-normal text-dark">Payment Successfull</h6>
                                        <small class="text-muted mb-0">Dummy text of the printing.</small>
                                    </div>
                                    <!--end media-body-->
                                </div>
                                <!--end media-->
                            </a>
                            <!--end-item-->
                        </div>
                        <!-- All-->
                        <a href="javascript:void(0);" class="dropdown-item text-center text-primary">
                            View all <i class="fi-arrow-right"></i>
                        </a>
                    </div>
                </li>

                <li class="dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="<?php echo base_url(); ?>assets/images/users/user-4.jpg" alt="profile-user"
                            class="rounded-circle" />
                        <span class="ml-1 nav-user-name hidden-sm"><?= $session->nombre_completo?>
                            <i class="mdi mdi-chevron-down"></i>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#"><i class="dripicons-user text-muted mr-2"></i> Profile</a>
                        <a class="dropdown-item" href="#"><i class="dripicons-wallet text-muted mr-2"></i> My Wallet</a>
                        <a class="dropdown-item" href="#"><i class="dripicons-gear text-muted mr-2"></i> Settings</a>
                        <a class="dropdown-item" href="#"><i class="dripicons-lock text-muted mr-2"></i> Lock screen</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo base_url()?>index.php/Login/cerrar"><i
                                class="dripicons-exit text-muted mr-2"></i> Salir</a>
                    </div>
                </li>


                <li class="mr-2">
                    <a href="#" class="nav-link" data-toggle="modal" data-animation="fade"
                        data-target=".modal-rightbar">
                        <i data-feather="align-right" class="align-self-center"></i>
                    </a>
                </li>
            </ul>
            <!--end topbar-nav-->

            <ul class="list-unstyled topbar-nav mb-0">
                <li>
                    <a href="<?php echo base_url(); ?>analytics/analytics-index.html">
                        <span class="responsive-logo">
                            <img src="<?php echo base_url(); ?>assets/images/logo-sm.png" alt="logo-small"
                                class="logo-sm align-self-center" height="34">
                        </span>
                    </a>
                </li>
                <li>
                    <button class="button-menu-mobile nav-link waves-effect waves-light">
                        <i data-feather="menu" class="align-self-center"></i>
                    </button>
                </li>
                <li class="dropdown">
                    <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <span class="ml-1 p-2 bg-soft-classic nav-user-name hidden-sm rounded">System <i
                                class="mdi mdi-chevron-down"></i> </span>
                    </a>
                    <div class="dropdown-menu dropdown-xl dropdown-menu-left p-0">
                        <div class="row no-gutters">
                            <div class="col-12 col-lg-6">
                                <div class="text-center system-text">
                                    <h4 class="text-white">The Poworfull Dashboard</h4>
                                    <p class="text-white">See all the pages Metrica.</p>
                                    <a href="https://mannatthemes.com/metrica/" class="btn btn-sm btn-pink mt-2">See
                                        Dashboard</a>
                                </div>
                                <div id="carouselExampleFade" class="carousel slide carousel-fade" data-ride="carousel">
                                    <div class="carousel-inner">
                                        <div class="carousel-item active">
                                            <img src="<?php echo base_url(); ?>assets/images/dashboard/dash-1.png"
                                                class="d-block img-fluid" alt="...">
                                        </div>
                                        <div class="carousel-item">
                                            <img src="<?php echo base_url(); ?>assets/images/dashboard/dash-4.png"
                                                class="d-block img-fluid" alt="...">
                                        </div>
                                        <div class="carousel-item">
                                            <img src="<?php echo base_url(); ?>assets/images/dashboard/dash-2.png"
                                                class="d-block img-fluid" alt="...">
                                        </div>
                                        <div class="carousel-item">
                                            <img src="<?php echo base_url(); ?>assets/images/dashboard/dash-3.png"
                                                class="d-block img-fluid" alt="...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->
                            <div class="col-12 col-lg-6">
                                <div class="divider-custom mb-0">
                                    <div class="divider-text bg-light">All Dashboard</div>
                                    </divi>
                                    <div class="p-4 text-left">
                                        <div class="row">
                                            <div class="col-6">
                                                <a class="dropdown-item mb-2"
                                                    href="<?php echo base_url(); ?>analytics/analytics-index.html">
                                                    Analytics</a>
                                                <a class="dropdown-item mb-2"
                                                    href="<?php echo base_url(); ?>crypto/crypto-index.html"> Crypto</a>
                                                <a class="dropdown-item mb-2"
                                                    href="<?php echo base_url(); ?>crm/crm-index.html"> CRM</a>
                                                <a class="dropdown-item"
                                                    href="<?php echo base_url(); ?>projects/projects-index.html">
                                                    Project</a>
                                            </div>
                                            <div class="col-6">
                                                <a class="dropdown-item mb-2"
                                                    href="<?php echo base_url(); ?>ecommerce/ecommerce-index.html">
                                                    Ecommerce</a>
                                                <a class="dropdown-item mb-2"
                                                    href="<?php echo base_url(); ?>helpdesk/helpdesk-index.html">
                                                    Helpdesk</a>
                                                <a class="dropdown-item"
                                                    href="<?php echo base_url(); ?>hospital/hospital-index.html">
                                                    Hospital</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end col-->
                            </div>
                            <!--end row-->
                        </div>
                </li>
                <li class="hide-phone app-search">
                    <h6>
                        <?php 
                        switch($session->id_perfil){
                            case 1: echo '<i class="mdi mdi-account-card-details font-18"></i> Administrador'; break;
                            case 4: echo '<i class="mdi mdi-account-card-details font-18"></i> Gestor'; break;
                            case 6: echo '<i class="mdi mdi-account-card-details font-18"></i> Enlace'; break;
                            case 8: echo '<i class="mdi mdi-account-card-details font-18"></i> Estudiante'; break;
                            default: echo 'Sin dato';
                        }  
                        ?>
                    </h6>
                </li>
                <li class="hide-phone">
                    <?php 
                    $hoy = date("Y-m-d H:i:s");  
                    $fecha_nac = new DateTime($session->fec_nac); // Convierte la fecha de nacimiento a objeto DateTime
                    $hoy_solo_fecha = date("m-d"); // Obtiene solo mes y día actual
                    $fecha_nac_solo_fecha = $fecha_nac->format("m-d"); // Extrae mes y día de la fecha de nacimiento
                    ?>
                    <?php if ($fecha_nac_solo_fecha === $hoy_solo_fecha): ?>
                    <div id="caja" style="cursor:pointer"><a class="nav-link" onclick="lanzarConfeti()"><i class="ti-gift text-info font-22"></i></a></div>
                    <div id="pastel" style="display:none"  data-toggle="tooltip" data-placement="right"  data-trigger="hover" title="Feliz Compleaños">
                        <a class="nav-link"><i title="Feliz Compleaños" class="mdi mdi-cake-layered text-success font-22"></i></a>
                    </div>
                    <?php endif; ?>
                </li>

            </ul>
        </nav>
        <!-- end navbar-->
    </div>
    <!-- Top Bar End -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1"></script>

    <script>


    function lanzarConfeti() {
        confetti({
        particleCount: 100,
        spread: 70,
        origin: { y: 0.6 },
        scalar: 1.2, // Tamaño del confeti
        shapes: ["circle", "square"], // Formas del confeti
        colors: ["#ff0000", "#ff8000", "#ffff00", "#00ff00", "#0000ff"], // Colores del confeti
    });
     $("#caja").hide();
     $("#pastel").show();
    }
    </script>
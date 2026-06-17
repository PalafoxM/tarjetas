<?php $session = \Config\Services::session();?>

<div class="leftside-menu">

        <!-- LOGO -->
        <a href="#" class="logo text-center logo-light mt-2">
            <span class="logo-lg">
                <img src="<?php echo base_url()?>/assets/images/st4.png" alt="" height="90">
            </span>
            <span class="logo-sm">
                <img src="<?php echo base_url();?>/assets/images/st4.png" alt="" height="48">
            </span>
        </a>

        <!-- LOGO -->
        <a href="#" class="logo text-center logo-dark">
            <span class="logo-lg">
                <img src="<?php echo base_url();?>/assets/images/st4.png" alt="" height="16">
            </span>
            <span class="logo-sm">
                <img src="<?php echo base_url();?>/assets/images/st4.png" alt="" height="16">
            </span>
        </a>

    <div class="h-100" id="leftside-menu-container" data-simplebar>

        <!--- Sidemenu -->
        <ul class="side-nav mt-3">
                <li class="side-nav-title side-nav-item">MENÚ</li>
<!--                
                <?php //if((int)$session->id_perfil == -1): ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#configuracion" aria-expanded="false" aria-controls="configuracion" class="side-nav-link">
                            <i class="uil-server"></i>
                            <span> Configuración </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="configuracion">
                            <ul class="side-nav-second-level">
                                <li>
                                    <a href="<?= base_url("/index.php/Configuracion")?>">Configuración del sistema</a>
                                </li>
                            </ul>
                        </div>
                    </li> 
                <?php //endif?>  -->
                
                <?php if((int)$session->id_perfil == 1): ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#gestion" aria-expanded="false" aria-controls="gestion" class="side-nav-link">
                            <i class="dripicons-user-group"></i>
                            <span> Usuarios </span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="gestion">
                            <ul class="side-nav-second-level">
                                <li>
                                    <a href="<?= base_url("/index.php/Usuario/listaUsuario")?>"><i class="dripicons-user"></i> Usuarios </a>
                                </li>
                            </ul>
                        </div>
                    </li> 
                <?php endif?> 
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#catalogos" aria-expanded="false" aria-controls="gestion" class="side-nav-link">
                        <i class="dripicons-archive"></i>
                        <span> Catálogos </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="catalogos">
                        <ul class="side-nav-second-level">
                            <li>
                                <a href="<?= base_url("/index.php/Inicio/Claves")?>"><i class="dripicons-keys"></i> Claves</a>
                            </li>
                            <li>
                                <a href="<?= base_url("/index.php/Inicio/CatFic")?>"><i class="dripicons-home"></i> Fic</a>
                            </li>
                            <li>
                                <a href="<?= base_url("/index.php/Opciones")?>"><i class="dripicons-gear"></i> Secturi</a>
                            </li>
                            <li>
                                <a href="<?= base_url("/index.php/Reportes")?>"><i class="dripicons-document"></i> Secul</a>
                            </li>
                            <li>
                                <a href="<?= base_url("/index.php/Reportes")?>"><i class="dripicons-briefcase"></i> Partida</a>
                            </li>
                            <li>
                                <a href="<?= base_url("/index.php/Reportes")?>"><i class="dripicons-user-id"></i> Perfil</a>
                            </li>
                        </ul>
                    </div>
                </li> 
                <li class="side-nav-item">
                    <a data-bs-toggle="collapse" href="#llamadas" aria-expanded="false" aria-controls="junta" class="side-nav-link">
                        <i class="dripicons-phone"></i>
                        <span> Llamadas </span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse" id="llamadas">
                        <ul class="side-nav-second-level">
                            <li>
                                <a href="#"><i class="dripicons-plus"></i> Agregar</a>
                            </li>
                            <li>
                                <a href="#"><i class="dripicons-search"></i> Buscar</a>
                            </li>
                            <li>
                                <a href="#"><i class="dripicons-gear"></i> Opciones</a>
                            </li>
                            <li>
                                <a href="#"><i class="dripicons-document"></i> Reportes</a>
                            </li>
                        </ul>
                    </div>
                </li>   
            
                <!-- <?php //if((int)$session->id_perfil == -1): ?>
                    <li class="side-nav-item">
                        <a data-bs-toggle="collapse" href="#reportes" aria-expanded="false" aria-controls="reportes" class="side-nav-link">
                            <i class="uil-clipboard-alt"></i>
                            <span>Reportes</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <div class="collapse" id="reportes">
                            <ul class="side-nav-second-level">
                                <li>
                                    <a href="<?php echo base_url('index.php/Junta/reporteComentarios') ?>"> Reporte</a>
                                </li>
                                <li>
                                    <a href="<?php echo base_url('index.php/Junta/reporteVotaciones') ?>"> Reporte de votaciones</a>
                                </li>
                                <li>
                                    <a href="<?php echo base_url('index.php/Junta/reporteVisualizaciones') ?>"> Reporte de visualizaciones</a>
                                </li>
                            </ul>
                        </div>
                    </li>   
                <?php //endif?>  -->
        </ul>
    <div class="clearfix"></div>

</div>
<!-- Sidebar -left -->

</div>
<!-- Left Sidebar End -->

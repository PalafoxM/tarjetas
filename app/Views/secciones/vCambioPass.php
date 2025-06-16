<?php $session = \Config\Services::session(); ?>
<div class="leftbar-tab-menu">
    <div class="main-icon-menu">
        <a href="<?php echo base_url();?>crm/crm-index.html" class="logo logo-metrica d-block text-center">
            <span>
                <img src="<?php echo base_url();?>assets/images/logo-sm.png" alt="logo-small" class="logo-sm">
            </span>
        </a>
        <nav class="nav">
            <a href="#MetricaDashboard" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                data-trigger="hover" title="" data-original-title="Dashboard">
                <i data-feather="monitor" class="align-self-center menu-icon icon-dual"></i>
            </a>
            <!--end MetricaDashboards-->

            <a href="#MetricaApps" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                data-trigger="hover" title="" data-original-title="Apps">
                <i data-feather="grid" class="align-self-center menu-icon icon-dual"></i>
            </a>
            <!--end MetricaApps-->

            <a href="#MetricaUikit" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                data-trigger="hover" title="" data-original-title="UI Kit">
                <i data-feather="package" class="align-self-center menu-icon icon-dual"></i>
            </a>
            <!--end MetricaUikit-->

            <a href="#MetricaPages" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                data-trigger="hover" title="" data-original-title="Pages">
                <i data-feather="copy" class="align-self-center menu-icon icon-dual"></i>
            </a>
            <!--end MetricaPages-->

            <a href="#MetricaAuthentication" class="nav-link" data-toggle="tooltip-custom" data-placement="right"
                data-trigger="hover" title="" data-original-title="Authentication">
                <i data-feather="lock" class="align-self-center menu-icon icon-dual"></i>
            </a>
            <!--end MetricaAuthentication-->

        </nav>
        <!--end nav-->
        <div class="pro-metrica-end">
            <a href="" class="help" data-toggle="tooltip-custom" data-placement="right" data-trigger="hover" title=""
                data-original-title="Chat">
                <i data-feather="message-circle" class="align-self-center menu-icon icon-md icon-dual mb-4"></i>
            </a>
            <a href="" class="profile">
                <img src="<?php echo base_url();?>assets/images/users/user-4.jpg" alt="profile-user"
                    class="rounded-circle thumb-sm">
            </a>
        </div>
    </div>
    <!--end main-icon-menu-->

</div>
<!-- end leftbar-tab-menu-->


<!-- Top Bar End -->

<div class="page-wrapper">
    <div class="modal fade hide-modal" id="passwordModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Por motivos de seguridad es requerido cambiar tu
                        contraseña</h5>

                </div>
                <div class="modal-body">
                    <div class="p-3">
                        <form id="passwordForm" class="form-horizontal">
                            <input type="hidden" value="<?php echo $session->id_usuario?>" id="id_usuario"
                                name="id_usuario">
                            <div class="text-center mb-4">
                                <div class="avatar-box thumb-xl align-self-center mr-2">
                                    <span class="avatar-title bg-light rounded-circle text-danger">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="input-group">
                                <input type="password" id="contrasenia" name="contrasenia" class="form-control"
                                    placeholder="Contraseña" aria-label="Contraseña" aria-describedby="HideCard"
                                    autocomplete="off">

                                <div class="input-group-append">
                                    <a class="btn btn-gradient-primary" id="HideCard">
                                        <i class="mdi mdi-key"></i>
                                    </a>
                                </div>
                            </div>
                            <br>
                            <br>
                            <div class="input-group">
                                <input type="password" id="confirmar_contrasenia" name="confirmar_contrasenia"
                                    class="form-control" placeholder="Confirmar Contraseña" aria-label="Password"
                                    aria-describedby="HideCard">

                                <div class="input-group-append">
                                    <a class="btn btn-gradient-primary" id="HideCard">
                                        <i class="mdi mdi-key"></i>
                                    </a>
                                </div>
                            </div>
                            <br>
                            <br>
                            <div id="btnCambioPass">
                                <button type="submit" class="btn btn-gradient-primary" href="#"
                                    role="button">Enviar</button>
                            </div>
                            <div id="load_btnCambioPass" style="display:none">
                                <button class="btn btn-gradient-primary" type="button" disabled>
                                    <span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>
                                    Procesando ...
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    ini.inicio.passwordForm();
    $('#passwordModal').modal({
        backdrop: 'static', // Evita que el modal se cierre al hacer clic fuera
        keyboard: false // Evita que el modal se cierre al presionar la tecla Esc
    });
    $('#passwordModal').modal('show'); // Muestra el modal
});
</script>
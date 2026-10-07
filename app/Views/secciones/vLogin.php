<link rel="stylesheet"
      href="<?= base_url('css/fic-login-view.css') ?>?v=<?= filemtime(FCPATH . 'css/fic-login-view.css') ?>">

<div id="login_page_loader" class="login-page-loader">
    <div class="login-page-loader__mark"></div>
    <div>Cargando...</div>
</div>

<div id="container" class="login-stage">
    <ul id="scene" class="scene unselectable" data-friction-x="0.08" data-friction-y="0.08" data-scalar-x="18" data-scalar-y="12">
        <li class="layer" data-depth="0.00"></li>
        <li class="layer" data-depth="0.10"><div class="background"></div></li>
        <li class="layer" data-depth="0.10"><div class="light orange b phase-4"></div></li>
        <li class="layer" data-depth="0.10"><div class="light purple c phase-5"></div></li>
        <li class="layer" data-depth="0.10"><div class="light orange d phase-3"></div></li>
        <li class="layer" data-depth="0.15">
            <ul class="rope depth-10">
                <li><img src="<?= base_url() ?>images/rope.png" alt="Rope"></li>
                <li class="hanger position-2">
                    <div class="board cloud-2 swing-1"></div>
                </li>
                <li class="hanger position-4">
                    <div class="board cloud-1 swing-3"></div>
                </li>
                <li class="hanger position-8">
                    <div class="board birds swing-5"></div>
                </li>
            </ul>
        </li>
        <li class="layer" data-depth="0.20"><h1 class="title"><em>EN UN LUGAR DE LA MANCHA</em></h1></li>
        <li class="layer" data-depth="0.30">
            <ul class="rope depth-30">
                <li><img src="<?= base_url() ?>images/rope.png" alt="Rope"></li>
                <li class="hanger position-1">
                    <div class="board cloud-1 swing-3"></div>
                </li>
                <li class="hanger position-5">
                    <div class="board cloud-4 swing-1"></div>
                </li>
            </ul>
        </li>
        <li class="layer" data-depth="0.30"><div class="wave paint depth-30"></div></li>
        <li class="layer" data-depth="0.40"><div class="wave plain depth-40"></div></li>
        <li class="layer" data-depth="0.50"><div class="wave paint depth-50"></div></li>
        <li class="layer" data-depth="0.60"><div class="lighthouse depth-60"></div></li>
        <li class="layer" data-depth="0.60"><div class="wave plain depth-60"></div></li>
        <li class="layer" data-depth="0.80"><div class="wave plain depth-80"></div></li>
        <li class="layer" data-depth="1.00"><div class="wave paint depth-100"></div></li>
    </ul>



    <div class="login-brand-logos login-brand-logos--left">
        <img src="<?= base_url() ?>assets/images/logo-guanajuato.png" alt="Marca Guanajuato">
    </div>
    <div class="login-brand-logos login-brand-logos--right">
        <img src="<?= base_url() ?>assets/images/ggt-2006.png" alt="Gobierno de Guanajuato">
    </div>

    <div class="login-auth-panel">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <div id="login_traditional_access" class="login-traditional-form is-active">
              <div class="login-overlay">
                <div class="login-card">
                    <div class="login-card__panel">
                        <div class="login-card__header">
                            <h5 class="login-card__title">En un lugar de la Mancha</h5>
                        </div>
                        <div class="login-card__body">
                            <input type="hidden" id="csrf_login_name" value="<?= esc(csrf_token(), 'attr') ?>">
                            <input type="hidden" id="csrf_login_hash" value="<?= esc(csrf_hash(), 'attr') ?>">

                            <div class="form-group">
                                <label for="usuario">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="usuario" placeholder="Ingresa tu nombre" autocomplete="username">
                            </div>

                            <div class="form-group">
                                <label for="contrasenia">Contraseña</label>
                                <div class="login-password-wrap">
                                    <input type="password" class="form-control login-password-input" id="contrasenia" placeholder="Ingresa tu contraseña" autocomplete="current-password">
                                    <button type="button" id="togglePasswordBtn" class="login-password-toggle" title="Mostrar u ocultar contraseña">
                                        <svg id="icon-eye"  width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg id="icon-eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fic-hidden">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button type="button" id="btnAcceder" class="btn btn-primary" >Acceder</button>

                            <div class="login-panel-actions" aria-label="Accesos auxiliares">
                                <!-- <a class="login-panel-access" href="<?= esc(base_url('index.php/ConsultaSaldo'), 'attr') ?>">
                                    Revisa tu saldo aquí
                                </a> -->
                                <a class="login-panel-access" href="https://tarjetasfic.guanajuato.gob.mx/lista/">
                                    Lista de establecimientos participantes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>

<button type="button" class="login-help-floating" data-bs-toggle="modal" data-bs-target="#modalSoporteLogin">
    <span aria-hidden="true">🎧</span>
    Ayuda
</button>

<div class="modal fade login-support-modal" id="modalSoporteLogin" tabindex="-1" aria-labelledby="modalSoporteLoginLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalSoporteLoginLabel">Ayuda</h5>
                <button type="button" class="login-support-modal-close" data-bs-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div class="support-contact-item">
                    <span class="support-contact-label">Teléfono</span>
                    <a href="tel:+524731391180">473 139 1180</a><br>
                    <a href="tel:+524731391180">473 122 6698</a>
                </div>
                <div class="support-contact-item">
                    <span class="support-contact-label">Correo</span>
                    <a href="mailto:a.palafoxm@guanajuato.gob.mx">a.palafoxm@guanajuato.gob.mx</a><br>
                    <a href="mailto:a.palafoxm@guanajuato.gob.mx">rsalbap@guanajuato.gob.mx</a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="login-support-modal-action" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
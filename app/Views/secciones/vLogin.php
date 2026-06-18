<style>
    .fic-login-body {
        background: url("<?= base_url('images/fondo_susi.png') ?>") center center / cover no-repeat fixed !important;
    }

    .login-stage {
        min-height: 100vh;
        position: relative;
        overflow: hidden;
    }

    .login-page-loader {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 16px;
        color: #d4af37;
        background: #080808;
        font-family: Arial, sans-serif;
        font-size: 14px;
        font-weight: 700;
        opacity: 1;
        visibility: visible;
        transition: opacity 0.35s ease, visibility 0.35s ease;
    }

    .login-page-loader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .login-page-loader__mark {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        border: 6px solid rgba(212, 175, 55, 0.2);
        border-top-color: #d4af37;
        border-right-color: #f4d675;
        animation: login-loader-spin 0.85s linear infinite;
    }

    @keyframes login-loader-spin {
        to { transform: rotate(360deg); }
    }

    .login-stage .background {
        background: url("<?= base_url('images/fondo_susi.png') ?>") center center / cover no-repeat !important;
        inset: -2% !important;
        width: 104% !important;
        opacity: 1;
    }

    .login-brand-logos {
        position: fixed;
        top: 18px;
        bottom: auto;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: min(30vw, 260px);
        pointer-events: none;
    }

    .login-brand-logos--left {
        left: 22px;
    }

    .login-brand-logos--right {
        right: 22px;
    }

    .login-brand-logos img {
        display: block;
        max-width: 100%;
        max-height: 92px;
        object-fit: contain;
        filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.7));
    }

    .login-auth-panel {
        position: fixed;
        top: 50%;
        left: 50%;
        z-index: 25;
        width: min(92vw, 420px);
        padding: 28px;
        border: 2px solid #d4af37;
        border-radius: 16px;
        color: #d4af37;
        background: rgba(0, 0, 0, 0.78);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.65), 0 0 22px rgba(212, 175, 55, 0.2);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transform: translate(-50%, -50%);
    }

    .login-auth-title {
        margin: 0 0 18px;
        color: #d4af37;
        font-family: Arial, sans-serif;
        font-size: 22px;
        font-weight: 700;
        text-align: center;
    }

    .login-auth-panel .alert {
        margin-bottom: 14px;
        padding: 10px 12px;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }

    .login-auth-panel .alert-danger {
        color: #842029;
        background: #f8d7da;
        border: 1px solid #f5c2c7;
    }

    .login-auth-panel .alert-success {
        color: #0f5132;
        background: #d1e7dd;
        border: 1px solid #badbcc;
    }

    .login-auth-panel .btn {
        display: inline-block;
        width: 100%;
        border: 0;
        padding: 13px 18px;
        border-radius: 999px;
        border: 1px solid #d4af37;
        color: #0a0a0a;
        font-family: Arial, sans-serif;
        font-size: 15px;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
    }

    .login-auth-panel .btn-gradient-primary {
        background: linear-gradient(135deg, #b8860b, #f4d675);
        box-shadow: 0 12px 28px rgba(212, 175, 55, 0.22);
    }

    .login-auth-toggle {
        display: block;
        width: fit-content;
        margin: 0 auto 14px;
        border: 0;
        background: transparent;
        color: #d4af37;
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        text-decoration: underline;
        cursor: pointer;
    }

    .login-traditional-form {
        display: block;
    }

    .login-traditional-form.is-active {
        display: block;
    }

    .login-google-access.is-hidden {
        display: none;
    }

    .login-field {
        margin-bottom: 13px;
    }

    .login-field label {
        display: block;
        margin-bottom: 6px;
        color: #d4af37;
        font-family: Arial, sans-serif;
        font-size: 13px;
        font-weight: 700;
    }

    .login-field input {
        box-sizing: border-box;
        width: 100%;
        border: 1px solid #d4af37;
        border-radius: 999px;
        padding: 12px 44px 12px 15px;
        color: #f4d675;
        background: rgba(0, 0, 0, 0.72);
        font-family: Arial, sans-serif;
        font-size: 14px;
        outline: none;
    }

    .login-password-wrap {
        position: relative;
    }

    .login-password-toggle {
        position: absolute;
        top: 50%;
        right: 12px;
        width: 30px;
        height: 30px;
        border: 0;
        border-radius: 50%;
        color: #d4af37;
        background: rgba(212, 175, 55, 0.12);
        transform: translateY(-50%);
        cursor: pointer;
    }

    .login-secondary-action {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 12px;
        font-family: Arial, sans-serif;
        font-size: 12px;
    }

    .login-secondary-action button {
        border: 0;
        padding: 0;
        color: #d4af37;
        background: transparent;
        font-weight: 700;
        cursor: pointer;
    }

    .login-auth-panel .btn:disabled {
        cursor: wait;
        opacity: 0.82;
    }

    .login-auth-panel .spinner-border {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        margin-right: 8px;
        vertical-align: -2px;
        border: 2px solid rgba(255, 255, 255, 0.45);
        border-top-color: #fff;
        border-radius: 50%;
        animation: login-spinner 0.7s linear infinite;
    }

    .login-auth-panel .login-overlay {
        min-height: 0;
        padding: 0;
        background: transparent;
    }

    .login-auth-panel .login-card,
    .login-auth-panel .login-card__panel {
        width: 100%;
    }

    .login-auth-panel .login-card__panel {
        padding: 0;
        border: 0;
        color: #d4af37;
        background: transparent;
        box-shadow: none;
        backdrop-filter: none;
    }

    .login-auth-panel .login-card__header {
        border-bottom-color: rgba(212, 175, 55, 0.45);
    }

    .login-auth-panel .login-card__title,
    .login-auth-panel .login-overlay label {
        color: #d4af37;
    }

    .login-auth-panel .login-overlay .form-control {
        border-color: #d4af37;
        color: #f4d675;
        background: rgba(0, 0, 0, 0.68);
    }

    .login-auth-panel .login-overlay .form-control:focus {
        border-color: #f4d675;
        color: #fff2bd;
        background: rgba(0, 0, 0, 0.9);
        box-shadow: 0 0 0 .18rem rgba(212, 175, 55, 0.2);
    }

    .login-auth-panel .login-overlay .form-control::placeholder {
        color: rgba(244, 214, 117, 0.62);
    }

    .login-auth-panel .login-overlay .btn-primary {
        border-color: #d4af37;
        color: #090909;
        background: linear-gradient(135deg, #b8860b, #f4d675);
    }

    .login-auth-panel .login-overlay .btn-primary:hover {
        border-color: #f4d675;
        color: #000;
        background: linear-gradient(135deg, #d4af37, #fff0a8);
    }

    .login-auth-panel .login-google-access {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid rgba(212, 175, 55, 0.45);
    }

    @keyframes login-spinner {
        to { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
        .login-brand-logos {
            top: 14px;
            max-width: 42vw;
        }

        .login-brand-logos--left {
            left: 12px;
        }

        .login-brand-logos--right {
            right: 12px;
        }

        .login-brand-logos img {
            max-height: 58px;
        }

        .login-auth-panel {
            top: 54%;
            padding: 22px;
        }

        .login-secondary-action {
            flex-direction: column;
            align-items: center;
        }
    }
</style>

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
                <li><img src="<?= base_url() ?>parallax.js_files/rope.png" alt="Rope"></li>
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
                <li><img src="<?= base_url() ?>parallax.js_files/rope.png" alt="Rope"></li>
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
                                    <button type="button" id="togglePasswordBtn" class="login-password-toggle" title="Mostrar u ocultar contraseña" onclick="togglePasswordVisibility()">
                                        <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fic-hidden">
                                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <button type="button" id="btnAcceder" class="btn btn-primary" onclick="loginTradicional();">Acceder</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="login_google_access" class="login-google-access">
            <div class="form-group mb-0 row" id="btn_login">
                <div class="col-12 mt-2">
                    <button class="btn btn-gradient-primary btn-round btn-block waves-effect waves-light"
                        onclick="iniciarGoogle();" type="button">Ingresar a SUSI con Google</button>
                </div>
            </div>
            <div class="form-group mb-0 row" id="btn_load" style="display:none;">
                <div class="col-12 mt-2">
                    <button class="btn btn-gradient-primary btn-round btn-block waves-effect waves-light"
                        type="button" disabled>
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Validando...
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function ocultarLoaderLogin() {
    const loader = document.getElementById('login_page_loader');

    if (!loader) {
        return;
    }

    loader.classList.add('is-hidden');

    setTimeout(() => {
        if (loader && loader.parentNode) {
            loader.parentNode.removeChild(loader);
        }
    }, 450);
}

window.addEventListener('load', function() {
    setTimeout(ocultarLoaderLogin, 250);
});

setTimeout(ocultarLoaderLogin, 6000);

function togglePasswordVisibility() {
  const input = document.getElementById("contrasenia");
  const eye = document.getElementById("icon-eye");
  const eyeOff = document.getElementById("icon-eye-off");

  if (!input || !eye || !eyeOff) {
    return;
  }

  const show = input.type === "password";
  input.type = show ? "text" : "password";
  eye.classList.toggle("fic-hidden", show);
  eyeOff.classList.toggle("fic-hidden", !show);
}

function losePass() {
    Swal.fire("Para restablecer la contraseña", '<p>Favor de comunicarte con el Administrador</p>', 'info');
}

function iniciarGoogle() {
    $('#btn_login').hide();
    $('#btn_load').show();

    setTimeout(() => {
        window.location.href = '<?= base_url("index.php/Auth/login") ?>';
    }, 300);
}

function loginTradicionalEnter(event) {
    if (event.key === 'Enter') {
        loginTradicional();
    }
}

function loginTradicional() {
    const usuario = $('#usuario').val();
    const contrasenia = $('#contrasenia').val();

    if (!usuario || !contrasenia) {
        Swal.fire('Atencion', 'Es requerido el usuario y contraseña', 'error');
        return;
    }

    const boton = $('#btnAcceder');
    boton.prop('disabled', true).text('Validando...');

    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/Login/validar_usuario',
        data: {
            usuario: usuario,
            contrasenia: contrasenia
        },
        dataType: 'json',
        success: function(response) {
            if (!response.error) {
                Swal.fire('Acceso correcto', 'Bienvenida, persona servidora publica.', 'success');
                setTimeout(() => {
                    window.location.href = base_url + 'index.php/Inicio';
                }, 1000);
            } else {
                Swal.fire('Usuario incorrecto', 'Favor de verificar sus credenciales', 'error');
            }
        },
        error: function() {
            Swal.fire('Error en la conexion', 'No fue posible validar el usuario.', 'error');
        },
        complete: function() {
            boton.prop('disabled', false).text('Acceder');
        }
    });
}

document.getElementById('usuario')?.addEventListener('keydown', loginTradicionalEnter);
document.getElementById('contrasenia')?.addEventListener('keydown', loginTradicionalEnter);
</script>

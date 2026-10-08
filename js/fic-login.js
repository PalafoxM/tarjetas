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



function loginTradicionalEnter(event) {
    if (event.key === 'Enter') {
        loginTradicional();
    }
}

let turnstileWidgetId = null;
let turnstileScriptPromise = null;

function cargarTurnstile() {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (turnstileScriptPromise) {
        return turnstileScriptPromise;
    }

    turnstileScriptPromise = new Promise(function(resolve, reject) {
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = function() {
            if (window.turnstile) {
                resolve(window.turnstile);
                return;
            }
            turnstileScriptPromise = null;
            reject(new Error('Turnstile no está disponible.'));
        };
        script.onerror = function() {
            turnstileScriptPromise = null;
            reject(new Error('No fue posible cargar Turnstile.'));
        };
        document.head.appendChild(script);
    });

    return turnstileScriptPromise;
}

function mostrarCaptcha() {
    const container = document.getElementById('turnstile_container');
    const siteKey = container?.dataset.sitekey || '';

    if (!container || !siteKey) {
        Swal.fire('Error de configuración', 'No fue posible cargar la validación CAPTCHA.', 'error');
        return Promise.reject(new Error('TURNSTILE_SITE_KEY no está configurada.'));
    }

    container.hidden = false;

    return cargarTurnstile().then(function(turnstile) {
        if (turnstileWidgetId === null) {
            turnstileWidgetId = turnstile.render('#turnstile_widget', {
                sitekey: siteKey,
                theme: 'dark',
                size: 'flexible',
                action: 'login'
            });
        }

        return turnstileWidgetId;
    }).catch(function(error) {
        Swal.fire('CAPTCHA no disponible', 'Recarga la página e inténtalo nuevamente.', 'warning');
        return null;
    });
}

function reiniciarCaptcha() {
    if (window.turnstile && turnstileWidgetId !== null) {
        window.turnstile.reset(turnstileWidgetId);
    }
}

function captchaEsRequerido() {
    const container = document.getElementById('turnstile_container');
    return Boolean(container && !container.hidden);
}

function loginTradicional() {
    const boton = $('#btnAcceder');

    if (boton.prop('disabled')) {
        return;
    }

    const usuario = $('#usuario').val();
    const contrasenia = $('#contrasenia').val();
    const csrfName = $('#csrf_login_name').val();
    const csrfHash = $('#csrf_login_hash').val();

    if (!usuario || !contrasenia) {
        Swal.fire(
            'Atención',
            'Es requerido el usuario y contraseña',
            'error'
        );
        return;
    }

    if (!csrfName || !csrfHash) {
        Swal.fire(
            'Actualiza la página',
            'No fue posible obtener el token de seguridad.',
            'warning'
        );
        return;
    }

    const datos = {
        usuario: usuario,
        contrasenia: contrasenia
    };

    if (captchaEsRequerido()) {
        const captchaToken = window.turnstile && turnstileWidgetId !== null
            ? window.turnstile.getResponse(turnstileWidgetId)
            : '';

        if (!captchaToken) {
            Swal.fire('Validación requerida', 'Completa el CAPTCHA para continuar.', 'warning');
            return;
        }

        datos['cf-turnstile-response'] = captchaToken;
    }

    datos[csrfName] = csrfHash;

    boton.prop('disabled', true).text('Validando...');

    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/Login/validar_usuario',
        data: datos,
        dataType: 'json',

        success: function(response) {
            // El controlador debe devolver el token vigente.
            if (response.csrfName && response.csrfHash) {
                $('#csrf_login_name').val(response.csrfName);
                $('#csrf_login_hash').val(response.csrfHash);

                $('meta[name="csrf-token-name"]')
                    .attr('content', response.csrfName);

                $('meta[name="csrf-token"]')
                    .attr('content', response.csrfHash);
            }

            if (!response.error) {
                Swal.fire(
                    'Acceso correcto',
                    'Bienvenido al sistema',
                    'success'
                );

                setTimeout(() => {
                    window.location.href =
                        base_url + 'index.php/Inicio';
                }, 1000);
            } else {
                if (response.captchaRequired) {
                    mostrarCaptcha();
                    reiniciarCaptcha();
                }
                Swal.fire(
                    'Usuario incorrecto',
                    response.respuesta || 'Favor de verificar sus credenciales',
                    'error'
                );
            }
        },
      error: function(xhr) {
        const respuesta = xhr.responseJSON;

        if (respuesta && respuesta.csrfName && respuesta.csrfHash) {
            $('#csrf_login_name').val(respuesta.csrfName);
            $('#csrf_login_hash').val(respuesta.csrfHash);

            $('meta[name="csrf-token-name"]')
                .attr('content', respuesta.csrfName);

            $('meta[name="csrf-token"]')
                .attr('content', respuesta.csrfHash);
        }

        if (respuesta && respuesta.captchaRequired) {
            mostrarCaptcha();
            reiniciarCaptcha();
        }

        if (xhr.status === 429) {
            const espera = xhr.getResponseHeader('Retry-After') || '60';

            Swal.fire(
                'Demasiados intentos',
                'Espera ' + espera + ' segundos antes de intentar nuevamente.',
                'warning'
            );
        } else if (xhr.status === 422) {
            Swal.fire(
                'Validación requerida',
                (respuesta && respuesta.respuesta) || 'Completa nuevamente el CAPTCHA.',
                'warning'
            );
        } else if (xhr.status === 403) {
            Swal.fire(
                'Actualiza la página',
                'La validación de seguridad falló. Recarga la página e intenta nuevamente.',
                'warning'
            );
        } else {
            Swal.fire(
                'Error en la conexión',
                'No fue posible validar el usuario. Recarga la página antes de intentar nuevamente.',
                'error'
            );
        }
    },

        complete: function() {
            boton.prop('disabled', false).text('Acceder');
        }
    });
}

document.getElementById('usuario')?.addEventListener('keydown', loginTradicionalEnter);
document.getElementById('contrasenia')?.addEventListener('keydown', loginTradicionalEnter);

document.getElementById('togglePasswordBtn')
    ?.addEventListener('click', togglePasswordVisibility);

document.getElementById('btnAcceder')
    ?.addEventListener('click', loginTradicional);

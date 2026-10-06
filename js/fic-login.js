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
                Swal.fire(
                    'Usuario incorrecto',
                    'Favor de verificar sus credenciales',
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

        if (xhr.status === 429) {
            const espera = xhr.getResponseHeader('Retry-After') || '60';

            Swal.fire(
                'Demasiados intentos',
                'Espera ' + espera + ' segundos antes de intentar nuevamente.',
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
<?php namespace App\Controllers;
use CodeIgniter\Controller;

use App\Libraries\Fechas;
use App\Libraries\TurnstileValidator;
use App\Models\Mglobal;
//use App\Libraries\Validasesion;
//use App\Libraries\Globals;
use stdClass;
use CodeIgniter\API\ResponseTrait;

class Login extends BaseController {

    use ResponseTrait;
    private $defaultData = array(
        'title' => 'Sitema de Turnos 2.0',
        'layout' => 'plantilla/lytDefault',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    );
    public function __construct()
    {
        //fechas php en espanol
        setlocale(LC_TIME, 'es_ES.utf8', 'es_MX.UTF-8', 'es_MX', 'esp_esp', 'Spanish'); // usar solo LC_TIME para evitar que los decimales los separe con coma en lugar de punto y fallen los inserts de peso y talla
        date_default_timezone_set('America/Mexico_City');  
        $session = \Config\Services::session();        
    }

    private function _renderView($data = array()) {
        /*if(isset($data['scripts'])){
            array_push($data['scripts'], "notificaciones");
        }*/    
        $data = array_merge($this->defaultData, $data);
        return view($data['layout'], $data);
    }

    public function index()
    {        
        $session = \Config\Services::session();
        $data = array();
        if ($session->get('logueado')==1) {
            return redirect()->to(base_url('index.php/Inicio'));
        }
        //$data['scripts'] = array('principal','somatometria');        
        $data['scripts'] = array('principal');
        $data['turnstileSiteKey'] = trim((string) env('TURNSTILE_SITE_KEY'));
        $data['layout'] = 'plantilla/lytLogin';
        $data['contentView'] = 'secciones/vLogin';                
        return $this->_renderView($data);        
    }
    public function validar_usuario()
    {
        $response = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error al validar usuario';

        $session = \Config\Services::session();

        // Mantiene el limitador existente por IP.
        $throttler = \Config\Services::throttler();
        $throttleKey = 'fic_login_' . hash(
            'sha256',
            $this->request->getIPAddress()
        );

        if ($throttler->check($throttleKey, 5, 60) === false) {
            $retryAfter = max(1, (int) $throttler->getTokentime());

            return $this->response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $retryAfter)
                ->setJSON([
                    'error' => true,
                    'respuesta' => 'Demasiados intentos. Espera antes de intentar nuevamente.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
        }

        $usuario = $this->request->getPost('usuario');
        $contrasenia = $this->request->getPost('contrasenia');

        if (
            !is_string($usuario)
            || !is_string($contrasenia)
            || trim($usuario) === ''
            || $contrasenia === ''
            || strlen($usuario) > 255
        ) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => true,
                'respuesta' => 'Ingresa usuario y contraseña válidos.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        try {
            $loginGuard = new \App\Libraries\LoginAttemptGuard($usuario);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'No fue posible iniciar el control de intentos de login.'
            );

            return $this->response
                ->setStatusCode(503)
                ->setHeader('Retry-After', '2')
                ->setJSON([
                    'error' => true,
                    'respuesta' => 'No fue posible validar el acceso. Inténtalo nuevamente.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
        }

        try {
            $retryAfter = $loginGuard->retryAfter();

            if ($retryAfter > 0) {
                return $this->response
                    ->setStatusCode(429)
                    ->setHeader('Retry-After', (string) $retryAfter)
                    ->setJSON([
                        'error' => true,
                        'respuesta' => 'Acceso temporalmente bloqueado por intentos fallidos.',
                        'captchaRequired' => true,
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
            }

            $captchaRequired = $loginGuard->failures() >= 3;
            if ($captchaRequired) {
                $turnstileToken = $this->request->getPost('cf-turnstile-response');
                $turnstileToken = is_string($turnstileToken) ? $turnstileToken : '';
                $turnstileResult = (new TurnstileValidator())->validate(
                    $turnstileToken,
                    $this->request->getIPAddress()
                );

                if (empty($turnstileResult['success'])) {
                    log_message(
                        'warning',
                        'Turnstile rechazo: ' . json_encode([
                            'tokenPresente' => $turnstileToken !== '',
                            'errorCodes' => $turnstileResult['errorCodes'] ?? [],
                        ])
                    );
                }

                if (!empty($turnstileResult['unavailable'])) {
                    return $this->response
                        ->setStatusCode(503)
                        ->setHeader('Retry-After', '5')
                        ->setJSON([
                            'error' => true,
                            'respuesta' => 'No fue posible validar el CAPTCHA. Inténtalo nuevamente.',
                            'captchaRequired' => true,
                            'csrfName' => csrf_token(),
                            'csrfHash' => csrf_hash(),
                        ]);
                }

                if (empty($turnstileResult['success'])) {
                    return $this->response
                        ->setStatusCode(422)
                        ->setJSON([
                            'error' => true,
                            'respuesta' => 'Completa nuevamente la validación CAPTCHA.',
                            'captchaRequired' => true,
                            'csrfName' => csrf_token(),
                            'csrfHash' => csrf_hash(),
                        ]);
                }
            }

            $catalogos = new Mglobal();
            $client = \Config\Services::curlrequest();

            // Conserva el contrato actual con backSti.
            $data = [
                'where' => [
                    'usuario' => $usuario,
                    'contrasenia' => $contrasenia,
                    'visible' => 1,
                ],
                'tabla' => 'usuario',
            ];

            $baseUrl = env('BACK_STI_API_BASE_URL') ?: env('NODE_API_BASE_URL');
            $baseUrl = rtrim((string) $baseUrl, '/') . '/';

            if ($baseUrl === '/') {
                throw new \RuntimeException(
                    'No está configurada la URL base de la API.'
                );
            }

            $apiResponse = $client->post($baseUrl . 'login', [
                'timeout' => 12,
                'connect_timeout' => 5,
                'json' => ['data' => $data],
            ]);

            $result = json_decode((string) $apiResponse->getBody());

            if (!is_object($result)) {
                throw new \RuntimeException('Respuesta inválida de backSti.');
            }

            if (
                isset($result->error)
                && $result->error === false
                && isset($result->data[0])
                && is_object($result->data[0])
            ) {
                $usuarioSesion = get_object_vars($result->data[0]);

                unset(
                    $usuarioSesion['contrasenia'],
                    $usuarioSesion['password'],
                    $usuarioSesion['token']
                );

                $usuarioSesion['logueado'] = 1;
                $usuarioSesion['nombre_completo'] = trim(implode(
                    ' ',
                    array_filter([
                        $usuarioSesion['nombre'] ?? '',
                        $usuarioSesion['primer_apellido'] ?? '',
                        $usuarioSesion['segundo_apellido'] ?? '',
                    ])
                ));

                $usuarioLocal = null;
                $idUsuarioSesion = (int) ($usuarioSesion['id_usuario'] ?? 0);

                if ($idUsuarioSesion > 0) {
                    $usuarioLocalResponse = $catalogos->getTabla([
                        'tabla' => 'usuario',
                        'where' => [
                            'visible' => 1,
                            'id_usuario' => $idUsuarioSesion,
                        ],
                    ]);

                    if (!empty($usuarioLocalResponse->data[0])) {
                        $usuarioLocal = get_object_vars(
                            $usuarioLocalResponse->data[0]
                        );
                    }
                }

                if (
                    empty($usuarioLocal)
                    && !empty($usuarioSesion['usuario'])
                ) {
                    $usuarioLocalResponse = $catalogos->getTabla([
                        'tabla' => 'usuario',
                        'where' => [
                            'visible' => 1,
                            'usuario' => $usuarioSesion['usuario'],
                        ],
                    ]);

                    if (!empty($usuarioLocalResponse->data[0])) {
                        $usuarioLocal = get_object_vars(
                            $usuarioLocalResponse->data[0]
                        );
                    }
                }

                if (!empty($usuarioLocal)) {
                    $usuarioSesion = array_merge(
                        $usuarioSesion,
                        $usuarioLocal
                    );

                    $usuarioSesion['nombre_completo'] = trim(implode(
                        ' ',
                        array_filter([
                            $usuarioSesion['nombre'] ?? '',
                            $usuarioSesion['primer_apellido'] ?? '',
                            $usuarioSesion['segundo_apellido'] ?? '',
                        ])
                    ));
                }

                unset(
                    $usuarioSesion['contrasenia'],
                    $usuarioSesion['password'],
                    $usuarioSesion['token']
                );

                $usuarioSesion['logueado'] = 1;

                $loginGuard->reset();
                $session->regenerate();
                $session->set($usuarioSesion);

                $response->error = false;
                $response->respuesta = 'Acceso correcto';
            } else {
                $message = (string) ($result->respuesta ?? '');

                // Solo cuenta el rechazo explícito de credenciales.
                // Errores de conexión o respuestas inesperadas no suman fallos.
                $credentialsRejected = isset($result->error)
                    && $result->error === true
                    && preg_match('/^Usuario o contrase/i', $message) === 1;

                if ($credentialsRejected) {
                    $loginGuard->recordFailure();
                    $response->respuesta = 'Usuario o contraseña incorrectos';
                    $response->captchaRequired = $loginGuard->failures() >= 3;

                    $retryAfter = $loginGuard->retryAfter();

                    if ($retryAfter > 0) {
                        $this->response
                            ->setStatusCode(429)
                            ->setHeader('Retry-After', (string) $retryAfter);

                        $response->respuesta = 'Acceso temporalmente bloqueado por intentos fallidos.';
                    }
                } else {
                    $this->response->setStatusCode(502);
                    $response->respuesta = 'No fue posible validar el acceso. Inténtalo más tarde.';
                }
            }
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Error en la validación del login: ' . $e->getMessage()
            );

            $this->response->setStatusCode(503);
            $response->error = true;
            $response->respuesta = 'No fue posible validar el acceso. Inténtalo más tarde.';
        } finally {
            // También se ejecuta cuando existe un return dentro del try.
            $loginGuard->close();
        }

        $response->csrfName = csrf_token();
        $response->csrfHash = csrf_hash();
        $response->captchaRequired = !empty($response->captchaRequired)
            || ($response->error && $loginGuard->failures() >= 3);

        return $this->respond(
            $response,
            $this->response->getStatusCode()
        );
    }
    public function cerrar() {
        $session = \Config\Services::session();  
        $session->destroy();
        return redirect()->to(base_url());
    }
    
    /**
     * Obtiene el nombre del navegador que esta usando el usuario
     * @param type $user_agent La variable del servidor $_SERVER['HTTP_USER_AGENT']
     * @return string El nombre del navegador
     */
    function get_browser_name($user_agent) {
        if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/'))
            return 'Opera';
        elseif (strpos($user_agent, 'Edge'))
            return 'Edge';
        elseif (strpos($user_agent, 'Chrome'))
            return 'Chrome';
        elseif (strpos($user_agent, 'Safari'))
            return 'Safari';
        elseif (strpos($user_agent, 'Firefox'))
            return 'Firefox';
        elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7'))
            return 'Internet Explorer';

        return $user_agent;
    }
    
    function ServerVar($Name) {
        $str = @$_SERVER[$Name];
        if (empty($str)) $str = @$_ENV[$Name];
        return $str;
    }
    
    function miDebug($msg) {
        $filename = ".debug.txt";
        if (!$handle = fopen($filename, 'a'))
                exit;
        if (is_writable($filename)) {
                $separador = "================================================================================";
                fwrite($handle, "" . $msg . "\n" . $separador . "\n\n");
        }
        fclose($handle);
    }
    
}

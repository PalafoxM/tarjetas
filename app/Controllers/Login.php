<?php namespace App\Controllers;
use CodeIgniter\Controller;

use App\Libraries\Fechas;
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
        $data['layout'] = 'plantilla/lytLogin';
        $data['contentView'] = 'secciones/vLogin';                
        return $this->_renderView($data);        
    }
    public function validar_usuario(){
        $response = new \stdClass();
        $response->error = true;
        $response->respuesta = "Error al validar usuario";
        $session = \Config\Services::session();
        $catalogos = new Mglobal;
         $client = \Config\Services::curlrequest();
        
        $usuario     = $this->request->getPost('usuario');
        $contrasenia = $this->request->getPost('contrasenia');

        $data = [
           'where' =>["usuario" => $usuario, "contrasenia" => $contrasenia, "visible" => 1],
           "tabla" => "usuario"
        ];


          try {
            // Hacemos la peticion POST a backSti.
            $baseUrl = env('BACK_STI_API_BASE_URL') ?: env('NODE_API_BASE_URL');
            $baseUrl = rtrim((string) $baseUrl, '/') . '/';

            if ($baseUrl === '/') {
                throw new \RuntimeException('No está configurada la URL base de la API.');
            }

            $apiResponse = $client->post($baseUrl . 'login', [
                'json' => ['data'=> $data]
            ]);
    
            $result = json_decode($apiResponse->getBody());

            if (isset($result->error) && $result->error === false && isset($result->data[0]) && is_object($result->data[0])) {
                $usuarioSesion = get_object_vars($result->data[0]);
                unset($usuarioSesion['contrasenia'], $usuarioSesion['password'], $usuarioSesion['token']);

                $usuarioSesion['logueado'] = 1;
                $usuarioSesion['nombre_completo'] = trim(implode(' ', array_filter([
                    $usuarioSesion['nombre'] ?? '',
                    $usuarioSesion['primer_apellido'] ?? '',
                    $usuarioSesion['segundo_apellido'] ?? '',
                ])));


                $usuarioLocal = null;
                $idUsuarioSesion = (int) ($usuarioSesion['id_usuario'] ?? 0);
                if ($idUsuarioSesion > 0) {
                    $usuarioLocalResponse = $catalogos->getTabla([
                        'tabla' => 'usuario',
                        'where' => ['visible' => 1, 'id_usuario' => $idUsuarioSesion],
                    ]);
                    if (!empty($usuarioLocalResponse->data[0])) {
                        $usuarioLocal = get_object_vars($usuarioLocalResponse->data[0]);
                    }
                }

                if (empty($usuarioLocal) && !empty($usuarioSesion['usuario'])) {
                    $usuarioLocalResponse = $catalogos->getTabla([
                        'tabla' => 'usuario',
                        'where' => ['visible' => 1, 'usuario' => $usuarioSesion['usuario']],
                    ]);
                    if (!empty($usuarioLocalResponse->data[0])) {
                        $usuarioLocal = get_object_vars($usuarioLocalResponse->data[0]);
                    }
                }

                if (!empty($usuarioLocal)) {
                    $usuarioSesion = array_merge($usuarioSesion, $usuarioLocal);
                    $usuarioSesion['nombre_completo'] = trim(implode(' ', array_filter([
                        $usuarioSesion['nombre'] ?? '',
                        $usuarioSesion['primer_apellido'] ?? '',
                        $usuarioSesion['segundo_apellido'] ?? '',
                    ])));
                }

                $session->regenerate();
                $session->set($usuarioSesion);

                $response->error = false;
                $response->respuesta = $result->respuesta ?? 'Operación exitosa';
            } else {
                $response->respuesta = $result->respuesta ?? 'Error desconocido en la respuesta';
            }
        
            } catch (\Exception $e) {
            log_message('error', 'Error al conectar con la API de backSti: ' . $e->getMessage());
            $response->respuesta = 'Error | Conexión fallida con backSti';
        }       
        return $this->respond($response);
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

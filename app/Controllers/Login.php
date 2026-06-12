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
        echo view($data['layout'], $data);               
    }

    public function index()
    {        
        $session = \Config\Services::session();
        $data = array();
        if ($session->get('logueado')==1) {
            header('Location:' . base_url() . 'index.php/Inicio');
            die();
        }
        //$data['scripts'] = array('principal','somatometria');        
        $data['scripts'] = array('principal');
        $data['layout'] = 'plantilla/lytLogin';
        $data['contentView'] = 'secciones/vLogin';                
        $this->_renderView($data);        
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
            // Hacemos la petición POST a Node.js
            $baseUrl = env('NODE_API_BASE_URL');
            $apiResponse = $client->post($baseUrl.'login', [
                'json' => ['data'=> $data]
            ]);
    
            $result = json_decode($apiResponse->getBody());
      
            if (isset($result->error) && $result->error === false) {
                $response->error = false;
                $response->respuesta = $result->respuesta ?? 'Operación exitosa';
                $response->data = $result->data ?? [];
                $session->set('logueado', 1);
                $session->set('id_usuario',$result->data[0]->id_usuario);
                $session->set('id_sexo',$result->data[0]->id_sexo);
                $session->set('usuario',$result->data[0]->usuario);
                $session->set('nombre_completo',$result->data[0]->nombre." ".$result->data[0]->primer_apellido." ".$result->data[0]->segundo_apellido);
                $session->set('id_perfil',$result->data[0]->id_perfil);
                $session->set('fec_nac',$result->data[0]->fec_nac);
                $session->set('correo',$result->data[0]->correo);
                $session->set('foto',$result->data[0]->ruta_foto_relativa);
                $session->set('id_tipo_empleado',$result->data[0]->id_tipo_empleado);
                $session->set('no_empleado',$result->data[0]->no_empleado);
                $this->activarActividad($result->data[0]->id_usuario);
                $subordinados = $catalogos->getTabla(['tabla' => 'vw_usuario', 'where' => ['visible' => 1, 'id_jefe_inmediato' => $result->data[0]->id_usuario]])->data;
                $esJefe = (!empty($subordinados))?true:false;
                $session->set('esJefe', $esJefe);
                $response->error     = $result->error;
                $response->respuesta = $result->respuesta;
            } else {
                $response->respuesta = $result->respuesta ?? 'Error desconocido en la respuesta';
            }
        
            } catch (\Exception $e) {
            log_message('error', 'Error al conectar con la API de Node.js: ' . $e->getMessage());
            $response->respuesta = 'Error|Conexión fallida con Node.js';
        }       
        return $this->respond($response);
    }
    public function cerrar() {
        $session = \Config\Services::session();  
        $session->destroy();
        $session->set('logueado', 0);        
        header('Location:'.base_url());
        die();
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

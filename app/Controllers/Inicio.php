<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;

use stdClass;
use CodeIgniter\API\ResponseTrait;
require_once FCPATH . '/mpdf/autoload.php';
class Inicio extends BaseController {

    use ResponseTrait;
    private $defaultData = array(
        'title' => 'Turnos 2.0',
        'layout' => 'plantilla/lytDefault',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    );
    public function __construct()
    {
        setlocale(LC_TIME, 'es_ES.utf8', 'es_MX.UTF-8', 'es_MX', 'esp_esp', 'Spanish'); // usar solo LC_TIME para evitar que los decimales los separe con coma en lugar de punto y fallen los inserts de peso y talla
        date_default_timezone_set('America/Mexico_City');  
        $session = \Config\Services::session();
        if($session->get('logueado')!= 1){
            header('Location:'.base_url().'index.php/Login/cerrar?inactividad=1');            
            die();
        }
    }

    private function _renderView($data = array()) { 
        $session = \Config\Services::session();
        $Mglobal = new Mglobal;   

        $data = array_merge($this->defaultData, $data);
        echo view($data['layout'], $data); 
                      
    }

    public function index()
    {        
        $session = \Config\Services::session();
        $Mglobal = new Mglobal; 
        $data        = array();
        $data['scripts'] = array('principal','inicio');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->get('nombre_completo');
        $vista = null;
        $datos = $Mglobal->getTabla(['tabla' => "vw_usuario", "where"=> ['visible' => 1, "id_usuario" => $session->get('id_usuario')]]);
        $data['datosUsuario'] = $datos->data[0];
        $data['allUser'] = ""; 
        if($session->id_perfil == 1){
            $vista= 'secciones/vInicio';
            
        }
        if($session->id_perfil == 2){
            $vista= 'secciones/vInicio';
        }
        if($session->id_perfil == 3){
            $vista= 'secciones/vInicio';
        }
        if($session->id_perfil == 4){
            $vista= 'secciones/vInicio';
        }
        if($session->id_perfil == 6){
            $data['allUser'] = $Mglobal->getTabla(['tabla'=>'vw_usuario', 'where'=>['visible'=> 1]])->data;
            $vista= 'secciones/vCajero';
        }
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = $vista;                
        $this->_renderView($data);
        
    }
  

   

    function encode_img_base64($img_path = false, $img_type = 'png')
    {
        if ($img_path) {
            //convert image into Binary data
            $img_data = fopen($img_path, 'rb');
            $img_size = filesize($img_path);
            $binary_image = fread($img_data, $img_size);
            fclose($img_data);
            //Build the src string to place inside your img tag
            $img_src = "data:image/" . $img_type . ";base64," . str_replace("\n", "", base64_encode($binary_image));
            return $img_src;
        }
        return false;
    }

    
}

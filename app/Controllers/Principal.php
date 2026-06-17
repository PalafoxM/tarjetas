<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;

use stdClass;
use CodeIgniter\API\ResponseTrait;

class Principal extends BaseController {

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
        $data = array_merge($this->defaultData, $data);
        echo view($data['layout'], $data);               
    }
   
    public function index()
    {  
       
        $session = \Config\Services::session();
        $data = array();
        $data['scripts'] = array('principal');
        $data['edita'] = 0;
        $data['contentView'] = 'secciones/vVacio';                
        $this->_renderView($data);
        
    }
    public function ObtenerCatFic()
    {
        $Mglobal = new Mglobal;
        $catFic = $Mglobal->getTabla(['tabla' => "cat_fic", "where"=> ['visible' => 1]]);

        if ($catFic->error) {
            return $this->response
                ->setStatusCode(502)
                ->setJSON([
                    'error' => true,
                    'respuesta' => $catFic->respuesta,
                    'data' => [],
                ]);
        }

        return $this->respond($catFic->data ?? []);
    }

    public function ObtenerFic()
    {
        $idFic = (int) $this->request->getPost('id_perfil_fic');
        if ($idFic <= 0) {
            return $this->fail('Identificador de perfil FIC no válido', 400);
        }

        $Mglobal = new Mglobal;
        $fic = $Mglobal->getTabla([
            'tabla' => 'cat_fic',
            'where' => ['visible' => 1, 'id_perfil_fic' => $idFic],
        ]);

        if ($fic->error || empty($fic->data)) {
            return $this->failNotFound('Perfil FIC no encontrado');
        }

        return $this->respond($fic->data[0]);
    }

    public function GuardarFic()
    {
        $session = \Config\Services::session();
        $idFic = (int) $this->request->getPost('id_perfil_fic');
        $descripcion = trim((string) $this->request->getPost('dsc_perfil'));

        if ($descripcion === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El perfil FIC es requerido',
            ]);
        }

        $Mglobal = new Mglobal;
        $dataInsert = [
            'dsc_perfil' => $descripcion,
        ];

        if ($idFic <= 0) {
            $dataInsert['visible'] = 1;
            $dataInsert['fec_reg'] = date('Y-m-d H:i:s');
            $dataInsert['usu_reg'] = (int) $session->get('id_usuario');
        }

        $response = $Mglobal->saveTabla(
            $dataInsert,
            [
                'tabla' => 'cat_fic',
                'editar' => $idFic > 0 ? 'true' : 'false',
                'idEditar' => $idFic > 0 ? ['id_perfil_fic' => $idFic] : null,
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Principal.GuardarFic',
            ]
        );

        return $this->respond($response);
    }

    public function EliminarFic()
    {
        $session = \Config\Services::session();
        $idFic = (int) $this->request->getPost('id_perfil_fic');

        if ($idFic <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de perfil FIC no válido',
            ]);
        }

        $Mglobal = new Mglobal;
        $response = $Mglobal->saveTabla(
            ['visible' => 0],
            [
                'tabla' => 'cat_fic',
                'editar' => 'true',
                'idEditar' => ['id_perfil_fic' => $idFic],
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Principal.EliminarFic',
            ]
        );

        return $this->respond($response);
    }
    public function ObtenerClaves()
    {
        $Mglobal = new Mglobal;
        $claves = $Mglobal->getTabla(['tabla' => "cat_claves", "where"=> ['visible' => 1]]);

        if ($claves->error) {
            return $this->response
                ->setStatusCode(502)
                ->setJSON([
                    'error' => true,
                    'respuesta' => $claves->respuesta,
                    'data' => [],
                ]);
        }

        return $this->respond($claves->data ?? []);
    }

    public function ObtenerClave()
    {
        $idClave = (int) $this->request->getPost('id_clave');
        if ($idClave <= 0) {
            return $this->fail('Identificador de clave no válido', 400);
        }

        $Mglobal = new Mglobal;
        $clave = $Mglobal->getTabla([
            'tabla' => 'cat_claves',
            'where' => ['visible' => 1, 'id_clave' => $idClave],
        ]);

        if ($clave->error || empty($clave->data)) {
            return $this->failNotFound('Clave no encontrada');
        }

        return $this->respond($clave->data[0]);
    }

    public function GuardarClave()
    {
        $session = \Config\Services::session();
        $idClave = (int) $this->request->getPost('id_clave');
        $clave = trim((string) $this->request->getPost('clave'));
        $descripcion = trim((string) $this->request->getPost('dsc_clave'));
        $direccion = trim((string) $this->request->getPost('direccion'));

        if ($clave === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'La clave es requerida',
            ]);
        }

        if ($descripcion === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'La descripción es requerida',
            ]);
        }

        $Mglobal = new Mglobal;
        $dataInsert = [
            'clave' => $clave,
            'dsc_clave' => $descripcion,
            'direccion' => $direccion,
        ];

        if ($idClave <= 0) {
            $dataInsert['visible'] = 1;
            $dataInsert['fec_reg'] = date('Y-m-d H:i:s');
            $dataInsert['usu_reg'] = (int) $session->get('id_usuario');
        }

        $response = $Mglobal->saveTabla(
            $dataInsert,
            [
                'tabla' => 'cat_claves',
                'editar' => $idClave > 0 ? 'true' : 'false',
                'idEditar' => $idClave > 0 ? ['id_clave' => $idClave] : null,
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Principal.GuardarClave',
            ]
        );

        return $this->respond($response);
    }

    public function EliminarClave()
    {
        $session = \Config\Services::session();
        $idClave = (int) $this->request->getPost('id_clave');

        if ($idClave <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de clave no válido',
            ]);
        }

        $Mglobal = new Mglobal;
        $response = $Mglobal->saveTabla(
            ['visible' => 0],
            [
                'tabla' => 'cat_claves',
                'editar' => 'true',
                'idEditar' => ['id_clave' => $idClave],
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Principal.EliminarClave',
            ]
        );

        return $this->respond($response);
    }
  
}

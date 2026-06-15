<?php 
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Models\Mglobal;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use stdClass;
use CodeIgniter\API\ResponseTrait;

require_once FCPATH . 'app/Libraries/PHPMailer/Exception.php';
require_once FCPATH . 'app/Libraries/PHPMailer/PHPMailer.php';
require_once FCPATH . 'app/Libraries/PHPMailer/SMTP.php';

require_once FCPATH . '/mpdf/autoload.php';

class Usuario extends BaseController 
{

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
        $this->globals = new Mglobal();
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
        $data['unidad'] = $this->globals->getTabla(["tabla"=>"cat_clues","select"=>"id_clues, NOMBRE_UNIDAD", "where"=>["visible"=>1],'limit' => 10]); 
        $data['perfiles'] = $this->globals->getTabla(["tabla"=>"seg_perfiles", "where"=>["visible"=>1]]); 
        $data['cat_sexo'] = $this->globals->getTabla(["tabla"=>"cat_sexo", "where"=>["visible"=>1]]); 
        $data['scripts'] = array('principal','inicio');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->nombre_completo; 
        $data['contentView'] = 'secciones/vUsuarios';                
        $this->_renderView($data);
        
    }

    public function getUsuarios()
    {
        $response = $this->globals->getTabla([
            "tabla" => "vw_usuario_qr",
            "where" => ["visible" => 1],
        ]);

        if ($response->error) {
            $response = $this->globals->getTabla([
                "tabla" => "vw_usuario",
                "where" => ["visible" => 1],
            ]);
        }

        if ($response->error) {
            return $this->response
                ->setStatusCode(502)
                ->setJSON([
                    'error' => true,
                    'respuesta' => $response->respuesta,
                    'data' => [],
                ]);
        }

        return $this->respond($response->data ?? []);
    }
    public function getVistaUsuario()
    {
     
         $session = \Config\Services::session();
            $response = $this->globals->getTabla([
                "tabla" => "vw_usuario",
                "where" => ["visible" => 1],
            ]);
        

        return $this->respond($response->data ?? []);
    }

    public function getUsuario()
    {
        $idUsuario = (int) $this->request->getPost('id_usuario');
        if ($idUsuario <= 0) {
            return $this->fail('Identificador de usuario no válido', 400);
        }

        $response = $this->globals->getTabla([
            "tabla" => "vw_usuario",
            "where" => ["visible" => 1, "id_usuario" => $idUsuario],
        ]);

        if (empty($response->data)) {
            return $this->failNotFound('Cajero no encontrado');
        }

        return $this->respond($response->data[0]);
    }

    public function saveCajero()
    {
        $session = \Config\Services::session();
        $data = $this->request->getPost();
        $idUsuario = (int) ($data['id_usuario'] ?? 0);

        foreach (['usuario', 'nombre', 'primer_apellido', 'correo'] as $campo) {
            if (trim((string) ($data[$campo] ?? '')) === '') {
                return $this->respond([
                    'error' => true,
                    'respuesta' => "El campo {$campo} es requerido",
                ]);
            }
        }

        if ($idUsuario === 0 && trim((string) ($data['contrasenia'] ?? '')) === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'La contraseña es requerida para un cajero nuevo',
            ]);
        }

        $dataInsert = [
            'usuario' => trim($data['usuario']),
            'nombre' => trim($data['nombre']),
            'primer_apellido' => trim($data['primer_apellido']),
            'segundo_apellido' => trim((string) ($data['segundo_apellido'] ?? '')),
            'correo' => trim($data['correo']),
            'id_perfil' => 6,
        ];

        if (!empty($data['contrasenia'])) {
            $dataInsert['contrasenia'] = md5($data['contrasenia']);
        }

        if ($idUsuario === 0) {
            $dataInsert['id_padre'] = (int) $session->get('id_perfil');
            $dataInsert['visible'] = 1;
            $dataInsert['fec_registro'] = date('Y-m-d H:i:s');
        }

        $response = $this->globals->saveTabla(
            $dataInsert,
            [
                'tabla' => 'usuario',
                'editar' => $idUsuario > 0,
                'idEditar' => $idUsuario > 0 ? ['id_usuario' => $idUsuario] : null,
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Usuario.saveCajero',
            ]
        );

        return $this->respond($response);
    }

    public function deleteUsuario()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $this->request->getPost('id_usuario');

        if ($idUsuario <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de usuario no válido',
            ]);
        }

        $response = $this->globals->saveTabla(
            ['visible' => 0],
            [
                'tabla' => 'usuario',
                'editar' => true,
                'idEditar' => ['id_usuario' => $idUsuario],
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Usuario.deleteUsuario',
            ]
        );

        return $this->respond($response);
    }
    public function generarPdfHospedaje($id_usuario)
    {
        $response = $this->globals->getTabla([
            'tabla' => 'vw_usuario',
            'where' => ['id_usuario' => (int) $id_usuario, 'visible' => 1],
        ]);

        if ($response->error || empty($response->data)) {
            return $this->failNotFound('Cajero no encontrado');
        }

        $html = view('pdfs/vpdfOrdenHospedaje', (array) $response->data[0]);
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'Letter',
            'margin_top' => 18,
            'margin_bottom' => 18,
            'margin_left' => 16,
            'margin_right' => 16,
            'default_font' => 'dejavusans',
            'tempDir' => WRITEPATH . 'cache',
        ]);
        $mpdf->SetTitle('Orden de hospedaje');
        $mpdf->WriteHTML($html);
        $mpdf->Output('orden-hospedaje-' . (int) $id_usuario . '.pdf', 'I');
        exit;
    }
      public function generarPdfAlimentos($id_usuario)
    {
        $session = \Config\Services::session();
      

        $response = $this->globals->getTabla([
            'tabla' => 'vw_usuario',
            'where' => ['id_usuario' => (int) $id_usuario, 'visible' => 1],
        ]);    
            $pdfData = $response->data[0];

    
            $html = view('pdfs/vpdfOrdenAlimentos', (array)$pdfData);
            $mpdf = new \Mpdf\Mpdf([
                'format' => 'Letter',
                'margin_top' => 18,
                'margin_bottom' => 18,
                'margin_left' => 16,
                'margin_right' => 16,
                'default_font' => 'dejavusans',
               'tempDir' => WRITEPATH . 'cache',
            ]);
            $mpdf->SetTitle('Orden de alimentos');
            $mpdf->WriteHTML($html);
            $mpdf->Output('orden-alimentos-' . (int) $id_usuario . '.pdf', 'I');
            exit;
        }
}

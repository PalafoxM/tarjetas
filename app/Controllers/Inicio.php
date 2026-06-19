<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Libraries\UsuarioPerfilResolver;
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
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $data        = array();
        $data['scripts'] = array('principal','inicio');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->get('nombre_completo');
        $data['contextoUsuario'] = $contextoUsuario;
        $vista = null;
        $datos = $Mglobal->getTabla(['tabla' => "vw_usuario", "where"=> ['visible' => 1, "id_usuario" => $session->get('id_usuario')]]);
        $usuarioBase = $Mglobal->getTabla(['tabla' => "usuario", "where"=> ['visible' => 1, "id_usuario" => $session->get('id_usuario')]]);
        $usuarioBaseRow = !empty($usuarioBase->data) ? (array) $usuarioBase->data[0] : [];
        $data['datosUsuario'] = !empty($datos->data)
            ? (object) array_merge((array) $datos->data[0], $usuarioBaseRow)
            : (!empty($usuarioBaseRow) ? (object) $usuarioBaseRow : null);
        $data['allUser'] = [];
        if($contextoUsuario['is_provider_flow']){
            $establecimiento = $Mglobal->getTabla(['tabla' => "establecimiento", "where"=> ['visible' => 1, "no_proveedor" => $session->get('id_usuario')]]);
            if(!empty($establecimiento->data)){
                $data['datosEstablecimiento'] = $establecimiento->data ?? null;
            }
           
            $vista= 'secciones/vEstablecimiento';
        }
        if($contextoUsuario['is_client_like']){
            $clientes = $Mglobal->getTabla(['tabla' => "vw_usuario", "where"=> ['visible' => 1, "id_usuario" => $session->get('id_usuario')]]);
            $solicitud_pago = $Mglobal->getTabla(['tabla' => "solicitud_pago", "where"=> ['visible' => 1, "id_usuario" => $session->get('id_usuario')]]);
          
            if(!empty($clientes->data)){
                $data['datosCliente'] = (object) array_merge((array) $clientes->data[0], $usuarioBaseRow);
            } elseif (!empty($usuarioBaseRow)) {
                $data['datosCliente'] = (object) $usuarioBaseRow;
            }
            if(!empty($solicitud_pago->data)){
                $data['saldo'] = $solicitud_pago->data[0] ?? 0;
            }
         //  die( var_dump($data['datosCliente']));

            $vista= 'secciones/vCliente';
        }
        if($contextoUsuario['is_cajero_flow']){
            $vista= 'secciones/vCajero';
        }
        if($contextoUsuario['is_recepcion_flow']){

            $vista= 'secciones/vHospedaje';
        }
        if ($vista === null) {
            $vista = 'secciones/vInicio';
        }
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = $vista;                
        $this->_renderView($data);
        
    }
    public function Claves()
    {
        $session = \Config\Services::session();
        $Mglobal = new Mglobal; 
        $data        = array();
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = 'secciones/vClaves';                
        $this->_renderView($data);
    }
    public function CatFic()
    {
        $session = \Config\Services::session();
        $Mglobal = new Mglobal; 
        $data        = array();
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = 'secciones/vCatFic';                
        $this->_renderView($data);
    }

      public function Establecimiento()
    {        
        $session = \Config\Services::session();
        $Mglobal = new Mglobal; 
        $data        = array();
   
            $establecimiento = $Mglobal->getTabla(['tabla' => "establecimiento", "where"=> ['visible' => 1, "no_proveedor" => $session->get('id_usuario')]]);
            if(!empty($establecimiento->data)){
                $data['datosEstablecimiento'] = $establecimiento->data ?? null;
            }
           
            $vista= 'secciones/vEstablecimiento';
        
    
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = $vista;                
        $this->_renderView($data);
        
    }
    public function EstablecimientosFic()
    {
        $session = \Config\Services::session();
        $Mglobal = new Mglobal;
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        if (!$contextoUsuario['can_access_user_catalog']) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $establecimientosResponse = $Mglobal->getTabla([
            'tabla' => 'establecimiento',
            'where' => ['visible' => 1],
            'order' => 'id_tipo ASC, dsc_establecimiento ASC',
        ]);
        $usuariosResponse = $Mglobal->getTabla([
            'tabla' => 'usuario',
            'where' => ['visible' => 1],
        ]);

        $proveedoresIndex = [];
        foreach (($usuariosResponse->data ?? []) as $usuario) {
            $usuarioArray = (array) $usuario;
            $idTipoProveedor = (int) ($usuarioArray['id_tipo_proveedor'] ?? 0);
            if ($idTipoProveedor <= 0) {
                continue;
            }

            $nombreCompleto = trim(implode(' ', array_filter([
                $usuarioArray['nombre'] ?? '',
                $usuarioArray['primer_apellido'] ?? '',
                $usuarioArray['segundo_apellido'] ?? '',
            ])));
            $proveedoresIndex[(int) ($usuarioArray['id_usuario'] ?? 0)] = [
                'nombre' => $nombreCompleto !== '' ? $nombreCompleto : (string) ($usuarioArray['usuario'] ?? 'Proveedor'),
                'tipo' => $idTipoProveedor,
            ];
        }

        $typeLabels = [
            1 => 'ESTABLECIMIENTO',
            2 => 'HOTEL',
            3 => 'INSTITUCIONAL',
            4 => 'INSTITUCIONAL',
            5 => 'INSTITUCIONAL',
            6 => 'INSTITUCIONAL',
            7 => 'INSTITUCIONAL',
        ];

        $establecimientos = [];
        foreach (($establecimientosResponse->data ?? []) as $row) {
            $item = (array) $row;
            $noProveedor = (int) ($item['no_proveedor'] ?? 0);
            $proveedor = $proveedoresIndex[$noProveedor] ?? null;
            $item['dsc_tipo'] = $typeLabels[(int) ($item['id_tipo'] ?? 0)] ?? ('TIPO ' . (int) ($item['id_tipo'] ?? 0));
            $item['dsc_proveedor'] = $proveedor['nombre'] ?? ($noProveedor > 0 ? 'PADRON ' . $noProveedor : 'Sin proveedor');
            $establecimientos[] = (object) $item;
        }

        $data = array();
        $data['scripts'] = array('principal','agregar');
        $data['contextoUsuario'] = $contextoUsuario;
        $data['modoEstablecimientosFic'] = true;
        $data['esAdministradorEstablecimientosFic'] = !empty($contextoUsuario['is_ti_master']);
        $data['soloConsultaEstablecimientosFic'] = empty($contextoUsuario['is_ti_master']);
        $data['altaProveedorUrl'] = base_url('index.php/Inicio/AltaUsuario?modo=proveedor');
        $data['usuariosUrl'] = base_url('index.php/Inicio/Usuarios');
        $data['datosEstablecimiento'] = $establecimientos;
        $data['contentView'] = 'secciones/vEstablecimiento';
        $this->_renderView($data);
    }

    public function buscarProveedoresPadronFic()
    {
        $session = \Config\Services::session();

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_ti_master'])) {
            return $this->response->setJSON([
                'results' => [],
            ]);
        }

        $term = trim((string) $this->request->getGet('term'));

        $db = \Config\Database::connect();

        $builder = $db->table('tarjetas.proveedor p')
            ->select('
                p.id_proveedor,
                p.id_tipo_proveedor,
                p.no_proveedor,
                p.razon_social,
                p.rfc
            ')
            ->where('p.visible', 1)
            ->orderBy('p.razon_social', 'ASC')
            ->limit(20);

        if ($term !== '') {
            $builder->groupStart()
                ->like('p.no_proveedor', $term)
                ->orLike('p.razon_social', $term)
                ->orLike('p.rfc', $term)
                ->groupEnd();
        }

        $rows = $builder->get()->getResult();

        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'id' => (string) $row->id_proveedor,
                'text' => trim(
                    (string) $row->no_proveedor
                    . ' - '
                    . (string) $row->razon_social
                    . ' - '
                    . (string) $row->rfc
                ),
                'id_proveedor' => (int) $row->id_proveedor,
                'id_tipo_proveedor' => (int) $row->id_tipo_proveedor,
                'no_proveedor' => (string) $row->no_proveedor,
                'razon_social' => (string) $row->razon_social,
                'rfc' => (string) $row->rfc,
            ];
        }

        return $this->response->setJSON([
            'results' => $results,
        ]);
    }

    public function getProveedorPadronFic()
    {
        $session = \Config\Services::session();

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_ti_master'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar proveedores.',
            ]);
        }

        $idProveedor = (int) $this->request->getGet('id_proveedor');

        if ($idProveedor <= 0) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Proveedor inválido.',
            ]);
        }

        $db = \Config\Database::connect();

        $proveedor = $db->table('tarjetas.proveedor p')
            ->select('
                p.id_proveedor,
                p.id_tipo_proveedor,
                p.no_proveedor,
                p.razon_social,
                p.rfc
            ')
            ->where('p.id_proveedor', $idProveedor)
            ->where('p.visible', 1)
            ->get()
            ->getRow();

        if (!$proveedor) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'No se encontró el proveedor seleccionado.',
            ]);
        }

        $establecimientos = $db->table('tarjetas.establecimiento e')
            ->select('
                e.id_establecimiento,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo,
                e.no_proveedor
            ')
            ->join('tarjetas.cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->where('e.visible', 1)
            ->where('e.no_proveedor', $proveedor->no_proveedor)
            ->orderBy('e.dsc_establecimiento', 'ASC')
            ->get()
            ->getResult();

        return $this->response->setJSON([
            'ok' => true,
            'proveedor' => [
                'id_proveedor' => (int) $proveedor->id_proveedor,
                'id_tipo_proveedor' => (int) $proveedor->id_tipo_proveedor,
                'no_proveedor' => (string) $proveedor->no_proveedor,
                'razon_social' => (string) $proveedor->razon_social,
                'rfc' => (string) $proveedor->rfc,
            ],
            'establecimientos' => array_map(static function ($row) {
                return [
                    'id_establecimiento' => (int) $row->id_establecimiento,
                    'dsc_establecimiento' => (string) $row->dsc_establecimiento,
                    'id_tipo' => (int) $row->id_tipo,
                    'dsc_tipo' => (string) ($row->dsc_tipo ?? ''),
                    'no_proveedor' => (string) $row->no_proveedor,
                ];
            }, $establecimientos),
        ]);
    }

    public function Usuarios()
    {        
        $session = \Config\Services::session();   
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        if (!$contextoUsuario['can_access_user_catalog']) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = array();
        $data['scripts'] = array('principal','agregar');
        $data['contextoUsuario'] = $contextoUsuario;
        $data['catalogRoleOptions'] = $resolver->getAllowedRoleOptions($contextoUsuario);
        $data['providerTypeOptions'] = $resolver->getProviderTypes();
        $data['contentView'] = 'secciones/vUsuario';                
        $this->_renderView($data);
        
    }

    public function AltaUsuario($idUsuario = null)
    {
        $session = \Config\Services::session();

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (!$contextoUsuario['can_access_user_catalog']) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $modoAltaProveedor = $this->request->getGet('modo') === 'proveedor';

        if ($modoAltaProveedor && empty($contextoUsuario['is_ti_master'])) {
            return redirect()->to(base_url('index.php/Inicio/EstablecimientosFic'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['contextoUsuario'] = $contextoUsuario;
        $data['idUsuarioEditar'] = (int) ($idUsuario ?? 0);
        $data['modoAltaProveedor'] = $modoAltaProveedor;
        $data['regresarUrl'] = $modoAltaProveedor
            ? base_url('index.php/Inicio/EstablecimientosFic')
            : base_url('index.php/Inicio/Usuarios');
        $data['contentView'] = 'secciones/vAltaUsuario';

        if ($modoAltaProveedor) {
            $data['catalogRoleOptions'] = [];
            $data['providerTypeOptions'] = [];
            $data['hotelOptions'] = [];
            $data['catTipoHabitacion'] = [];

            $this->_renderView($data);
            return;
        }

        $Mglobal = new Mglobal();

        $hotelOptions = $Mglobal->getTabla([
            'tabla' => 'establecimiento',
            'where' => [
                'visible' => 1,
                'id_tipo' => 2,
            ],
        ]);

        $catTipoHabitacion = $Mglobal->getTabla([
            'tabla' => 'cat_tipo_habitacion',
            'where' => [
                'visible' => 1,
            ],
        ]);

        $data['hotelOptions'] = $hotelOptions->data ?? [];
        $data['catTipoHabitacion'] = $catTipoHabitacion->data ?? [];
        $data['catalogRoleOptions'] = $resolver->getAllowedRoleOptions($contextoUsuario);
        $data['providerTypeOptions'] = $resolver->getProviderTypes();

        $this->_renderView($data);
    }

    public function ObtenerHospedaje()
    {        
        $session = \Config\Services::session();
        $Mglobal = new Mglobal;

        $idUsuario = $Mglobal->getTabla([
            'tabla' => 'vw_usuario',
            'where' => ['visible' => 1, 'id_usuario' => $session->get('id_usuario')]
        ]);
  
        $response = $Mglobal->getTabla([
            'tabla' => 'vw_usuario_hospedaje',
            'where' => ['visible' => 1, 'id_establecimiento_hotel' => $idUsuario->data[0]->id_establecimiento]
        ]);
      
        $data = array();
        if (!empty($response->data)) {
            $data = $response->data;
        }

        return $this->respond($data);
        
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



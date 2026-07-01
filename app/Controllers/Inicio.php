<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\DepositosProgramadosService;
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
        if (($contextoUsuario['active_group'] ?? '') === 'fic' && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2, 4], true)) {
            return $this->renderPerfilFicHub(((int) ($contextoUsuario['group_role'] ?? 0) === 1) ? 'admin' : 'consulta');
        } elseif (($contextoUsuario['active_group'] ?? '') === 'secul' && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2, 4], true)) {
            return $this->renderPerfilSeculHub(((int) ($contextoUsuario['group_role'] ?? 0) === 1) ? 'admin' : 'consulta');
        } elseif (($contextoUsuario['active_group'] ?? '') === 'ug' && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2, 4], true)) {
            return $this->renderPerfilUgHub(((int) ($contextoUsuario['group_role'] ?? 0) === 1) ? 'admin' : 'consulta');
        } elseif (!empty($session->get('id_proveedor')) || !empty($contextoUsuario['is_provider_flow'])) {
      

            //$data = array_merge($data, $this->buildProviderDashboardData((int) $session->get('id_usuario')));
           // var_dump( $data);
            $tablaProveedor = [ "tabla" => 'vw_usuario', "where" => ['visible' => 1, 'id_usuario' =>$session->get('id_usuario')]];
            $datosProveedor = $Mglobal->getTabla($tablaProveedor);
              //  die('ok');
            if(!empty($datosProveedor->data)){
                $idEstablecimiento = $datosProveedor->data[0]->id_establecimiento;
                $tabla = ["tabla" => "establecimiento", "where" => ['visible' => 1, 'id_establecimiento' => $idEstablecimiento ]];
                $proveedor = $Mglobal->getTabla($tabla);
                $rfc = $Mglobal->getTabla(['tabla' => "proveedor", "where" =>['visible' =>1, "no_proveedor" =>$proveedor->data[0]->no_proveedor]]);
              
                $data['rfc'] = (!empty($rfc->data) && isset($rfc->data))?$rfc->data[0]->rfc:'Sin RFC';
                $noEstablecimientos = ["tabla" => "usuario_establecimiento", "where" => ['visible' => 1, "id_usuario" =>$session->get('id_usuario')]];
                $e = $Mglobal->getTabla($noEstablecimientos);
                $data['establecimiento'] = (!empty($e->data) && isset($e->data))?count($e->data):'0';
                $pagos = $Mglobal->getTabla(['tabla' =>"solicitud_pago", "where" =>['visible' =>1, "id_establecimiento" =>$idEstablecimiento]]);
                if(!empty($pagos->data) && isset($pagos->data)){
                    $data['total'] = 0;
                    $data['aprobados'] = [];
                    $data['pendiente'] = [];
                    $data['rechazado'] = [];

                    
                    foreach($pagos->data as $a){
                        switch($a->estatus){
                            case 'autorizado':
                                $data['aprobados'][] = $a->estatus;
                                 $data['total'] += $a->monto_solicitado; //11.00

                                break;
                            case 'pendiente':
                                $data['pendiente'][] = $a->estatus;
                                break;
                            case 'rechazado':
                                $data['rechazado'][] = $a->estatus;
                                break;
                        }

                    }

                }

                  
                $pagos = $Mglobal->getTabla(["tabla" =>"pagos", "where" => ['visible' => 1, "id_establecimiento" => $idEstablecimiento]]);
            //    die( var_dump( $pagos  ) );
                $solicitudPago = $Mglobal->getTabla(["tabla" =>"solicitud_pago", "where" => ['visible' => 1, "id_establecimiento" => $idEstablecimiento]]);
                if(!empty($pagos->data) && isset($pagos->data)){
                     $data['proveedorPagos'] = $pagos->data;
                }
                if(!empty($solicitudPago->data) && isset($solicitudPago->data)){
                     $data['solicitudPago'] = $solicitudPago->data;
                }

                //die( var_dump($data['proveedorPagos']) );
                $data['datosProveedor'] = $proveedor->data[0];

            }
            //die( var_dump( $data['solicitudPago'] ) );
            $vista = 'secciones/vProveedor';
            
           // die('ok');
        } elseif ($contextoUsuario['is_client_like']) {
            $clientes = $Mglobal->getTabla(['tabla' => 'vw_usuario', 'where' => ['visible' => 1, 'id_usuario' => $session->get('id_usuario')]]);
            $solicitud_pago = $Mglobal->getTabla(['tabla' => 'solicitud_pago', 'where' => ['visible' => 1, 'id_usuario' => $session->get('id_usuario')]]);

            if (!empty($clientes->data)) {
                $data['datosCliente'] = (object) array_merge((array) $clientes->data[0], $usuarioBaseRow);
            } elseif (!empty($usuarioBaseRow)) {
                $data['datosCliente'] = (object) $usuarioBaseRow;
            }
            if (!empty($solicitud_pago->data)) {
                $data['saldo'] = $solicitud_pago->data[0] ?? 0;
            }
         //  die( var_dump($data['datosCliente']));

            $vista = 'secciones/vCliente';
        }
        if ($contextoUsuario['is_cajero_flow']) {
            $vista = 'secciones/vCajero';
        }
        if ($contextoUsuario['is_recepcion_flow']) {
           if($session->id_usuario == 1){
            $clientes = $Mglobal->getTabla(['tabla' => 'vw_usuario', 'where' => ['visible' => 1, 'id_usuario' => $session->get('id_usuario')]])->data;
            
           }else{
             $tablaProveedor = [ "tabla" => 'vw_usuario', "where" => ['visible' => 1, 'id_usuario' =>$session->get('id_usuario')]];
            $datosProveedor = $Mglobal->getTabla($tablaProveedor);
            $idEstablecimiento = $datosProveedor->data[0]->id_establecimiento;
           /*  $tabla = ["tabla" => "vw_usuario", "where" => ['visible' => 1, 'id_establecimiento' => $idEstablecimiento ]];
            $cliente = $Mglobal->getTabla($tabla);
            $data['usuarioHotel'] = (!empty($cliente->data) && isset($cliente->data))?$cliente->data:[]; */
           // var_dump($cliente);
            //die(  );
            
           }

            //die('ok');
            $vista = 'secciones/vHospedaje';
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
    public function Hospedaje()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['contentView'] = 'secciones/vHospedaje';
        $this->_renderView($data);
    }

    public function PartidasFic()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['styles'] = ['partidas_fic'];
        $data['scripts'] = ['principal', 'agregar', 'partidas_fic'];
        $data['partidasDashboardSeed'] = $this->buildPartidasDashboardSeed();
        $data['previewInterfaceActiva'] = true;
        $data['previewInterfaceLabel'] = 'Vista de referencia';
        $data['previewInterfaceDescripcion'] = 'Estás consultando la vista de partidas sin cambiar la sesión autenticada.';
        $data['contentView'] = 'secciones/vPartidasFic';
        $this->_renderView($data);
    }

    public function PagosFic()
    {
        $session = \Config\Services::session();

        $tiUsuario = $session->id_perfil;

        if ($tiUsuario != 1) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['pagosFicDashboard'] = $this->buildPagosFicDashboardData();
        $data['previewInterfaceActiva'] = true;
        $data['previewInterfaceLabel'] = 'Vista de referencia';
        $data['previewInterfaceDescripcion'] = 'Estás consultando el historial global de pagos sin cambiar la sesión autenticada.';
        $data['contentView'] = 'secciones/vPagosFic';
        $this->_renderView($data);
    }


    public function PerfilFic()
    {
        return $this->renderPerfilFicHub('admin');
    }

    public function PerfilFicConsulta()
    {
        return $this->renderPerfilFicHub('consulta');
    }

    private function renderPerfilFicHub(string $modo = 'admin')
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esFic = (string) ($contextoUsuario['active_group'] ?? '') === 'fic';
        $rolFic = (int) ($contextoUsuario['group_role'] ?? 0);

        if (empty($tiUsuario) && (!$esFic || !in_array($rolFic, [1, 2, 4], true))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }
        if (empty($tiUsuario) && $esFic && $modo === 'admin' && in_array($rolFic, [2, 4], true)) {
            return redirect()->to(base_url('index.php/Inicio/PerfilFicConsulta'));
        }
        if (empty($tiUsuario) && $esFic && $modo === 'consulta' && $rolFic === 1) {
            return redirect()->to(base_url('index.php/Inicio/PerfilFic'));
        }

        $db = \Config\Database::connect();
        $perfiles = $db->table('cat_fic')
            ->select('id_perfil_fic, dsc_perfil')
            ->where('visible', 1)
            ->whereIn('id_perfil_fic', [1, 2, 3, 4])
            ->orderBy('id_perfil_fic', 'ASC')
            ->get()
            ->getResultArray();

        $data = [];
        $data['scripts'] = ['principal', 'agregar', 'solicitudes_usuario_fic'];
        $data['perfilFicMode'] = $modo === 'consulta' ? 'consulta' : 'admin';
        $data['hubTitle'] = 'Perfil FIC';
        $data['hubSubtitle'] = $modo === 'consulta'
            ? 'Acceso institucional Festival Internacional Cervantino. Vista de consulta para capturista y administrativo.'
            : 'Acceso institucional Festival Internacional Cervantino. Panel operativo para perfil FIC Admin.';
        $data['ficSolicitudPuedeCrear'] = $modo === 'admin' && (int) ($contextoUsuario['group_role'] ?? 0) === 1;
        $data['ficSolicitudPerfilOptions'] = array_map(static function (array $row): array {
            return [
                'id_perfil_fic' => (int) ($row['id_perfil_fic'] ?? 0),
                'dsc_perfil' => (string) ($row['dsc_perfil'] ?? ''),
            ];
        }, $perfiles);
        $data['ficSolicitudListadoUrl'] = base_url('index.php/Inicio/getSolicitudesUsuarioFicPerfil');
        $data['ficSolicitudDetalleUrl'] = base_url('index.php/Inicio/getSolicitudUsuarioFicPerfil');
        $data['ficSolicitudGuardarUrl'] = base_url('index.php/Inicio/guardarSolicitudUsuarioFicPerfil');
        $data['ficSolicitudCancelarUrl'] = base_url('index.php/Inicio/cancelarSolicitudUsuarioFicPerfil');
        $data['ficSolicitudEstablecimientoId'] = (int) ($session->get('id_establecimiento') ?? 0);
        $data['contentView'] = 'secciones/vPerfilFic';
        $this->_renderView($data);
    }

    public function SolicitudAlta(string $grupo = 'fic')
    {
        $grupo = strtolower(trim($grupo));
        if (!in_array($grupo, ['fic', 'secul', 'ug'], true)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        $session = \Config\Services::session();
        $tiUsuario = $this->resolveTiMasterUsuario();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $cfg = $grupo === 'fic' ? [] : $this->getSolicitudCatalogoConfig($grupo);

        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        if ($grupo === 'fic' && empty($tiUsuario) && $esGrupo && $rolGrupo !== 1) {
            return redirect()->to(base_url('index.php/Inicio/PerfilFicConsulta'));
        }
        if ($grupo !== 'fic' && empty($tiUsuario) && $esGrupo && $rolGrupo === 1) {
            return redirect()->to(base_url('index.php/Inicio/' . ucfirst($grupo)));
        }
        if ($grupo !== 'fic' && empty($tiUsuario) && $esGrupo && in_array($rolGrupo, [2, 4], true)) {
            return redirect()->to(base_url('index.php/Inicio/' . ucfirst($grupo) . 'Consulta'));
        }

        $backUrl = $grupo === 'fic'
            ? base_url('index.php/Inicio/PerfilFic')
            : base_url('index.php/Inicio/' . ucfirst($grupo));

        $saveUrl = $grupo === 'fic'
            ? base_url('index.php/Inicio/guardarSolicitudUsuarioFicPerfil')
            : base_url('index.php/Inicio/guardarSolicitudUsuario' . ucfirst($grupo) . 'Perfil');

        $data = [];
        $data['scripts'] = ['principal', 'solicitud_alta'];
        $data['contentView'] = 'secciones/vSolicitudAlta';
        $data['solicitudAlta'] = [
            'grupo' => $grupo,
            'title' => 'Solicitud de folio de usuario',
            'subtitle' => 'Captura los datos del usuario y el perfil solicitado dentro del catálogo ' . strtoupper($grupo) . '.',
            'back_url' => $backUrl,
            'save_url' => $saveUrl,
            'catalogos_url' => base_url('index.php/Usuario/getCatalogosCrud'),
            'establecimiento_id' => (int) ($session->get('id_establecimiento') ?? 0),
        ];

        if ($grupo === 'fic') {
            $db = \Config\Database::connect();
            $perfiles = $db->table('cat_fic')
                ->select('id_perfil_fic, dsc_perfil')
                ->where('visible', 1)
                ->whereIn('id_perfil_fic', [1, 2, 3, 4])
                ->orderBy('id_perfil_fic', 'ASC')
                ->get()
                ->getResultArray();
            $data['solicitudAlta']['perfil_options'] = array_map(static function (array $row): array {
                return [
                    'id_perfil_fic' => (int) ($row['id_perfil_fic'] ?? 0),
                    'dsc_perfil' => (string) ($row['dsc_perfil'] ?? ''),
                ];
            }, $perfiles);
        } else {
            $perfiles = $this->getSolicitudCatalogoConfig($grupo);
            $db = \Config\Database::connect();
            $rows = $db->table($cfg['catalog_table'])
                ->select($cfg['catalog_id'] . ', ' . $cfg['catalog_label'])
                ->where('visible', 1)
                ->orderBy($cfg['catalog_id'], 'ASC')
                ->get()
                ->getResultArray();
            $data['solicitudAlta']['perfil_options'] = array_map(static function (array $row) use ($cfg): array {
                return [
                    'id_perfil' => (int) ($row[$cfg['catalog_id']] ?? 0),
                    'dsc_perfil' => (string) ($row[$cfg['catalog_label']] ?? ''),
                ];
            }, $rows);
        }

        $this->_renderView($data);
    }
    public function getSolicitudesUsuarioFicPerfil()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esFic = (string) ($contextoUsuario['active_group'] ?? '') === 'fic';
        $rolFic = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($tiUsuario) && (!$esFic || !in_array($rolFic, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'total' => 0,
                'rows' => [],
                'message' => 'No tienes permisos para consultar solicitudes FIC.',
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, cf.dsc_perfil AS perfil_solicitado')
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado', 'left')
            ->where('su.visible', 1)
            ->where('su.tipo_solicitud', 'alta_usuario_fic');

        if (empty($tiUsuario)) {
            $builder->where('su.usu_reg', $idSesionUsuario);
        }

        $search = trim((string) ($this->request->getGet('search') ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('su.usuario', $search)
                ->orLike('su.nombre', $search)
                ->orLike('su.primer_apellido', $search)
                ->orLike('su.segundo_apellido', $search)
                ->orLike('su.correo', $search)
                ->orLike('su.estatus', $search)
                ->orLike('cf.dsc_perfil', $search)
                ->groupEnd();
        }

        $estatus = trim((string) ($this->request->getGet('estatus') ?? ''));
        if ($estatus !== '' && !in_array(strtolower($estatus), ['todos', 'all'], true)) {
            $builder->where('su.estatus', $estatus);
        }

        $total = (clone $builder)->countAllResults();
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 10));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));
        $rows = $builder
            ->orderBy('su.fec_reg', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'total' => $total,
            'rows' => array_map(function (array $row): array {
                return $this->mapSolicitudUsuarioFicPerfilRow($row);
            }, $rows),
        ]);
    }

    public function getSolicitudUsuarioFicPerfil()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esFic = (string) ($contextoUsuario['active_group'] ?? '') === 'fic';
        $rolFic = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($tiUsuario) && (!$esFic || !in_array($rolFic, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar solicitudes FIC.',
            ]);
        }

        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Solicitud no v?lida.',
            ]);
        }

        $db = \Config\Database::connect();
        $row = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, cf.dsc_perfil AS perfil_solicitado')
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado', 'left')
            ->where('su.id_solicitud_usuario', $idSolicitud)
            ->where('su.visible', 1)
            ->where('su.tipo_solicitud', 'alta_usuario_fic')
            ->groupStart()
                ->where('su.usu_reg', $idSesionUsuario)
                ->orWhereNotIn('su.usu_reg', [$idSesionUsuario])
            ->groupEnd()
            ->get()
            ->getRowArray();


        if (empty($tiUsuario) && (int) ($row['usu_reg'] ?? 0) !== $idSesionUsuario) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar esta solicitud.',
            ]);
        }
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'No se encontr? la solicitud.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'data' => $this->mapSolicitudUsuarioFicPerfilRow($row),
        ]);
    }

    public function guardarSolicitudUsuarioFicPerfil()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esFic = (string) ($contextoUsuario['active_group'] ?? '') === 'fic';
        $rolFic = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($tiUsuario) && (!$esFic || $rolFic !== 1)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Solo un administrador FIC puede enviar solicitudes.',
            ]);
        }

        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405)->setJSON([
                'ok' => false,
                'message' => 'M?todo no permitido.',
            ]);
        }
        $idClave = (int) ($this->request->getPost('id_clave') ?? 0);
        $categoriaLabel = trim((string) ($this->request->getPost('categoria_label') ?? ''));
        $idPais = (int) ($this->request->getPost('id_pais') ?? 0);
        $paisLabel = trim((string) ($this->request->getPost('pais_label') ?? ''));
        $idDiciplina = (int) ($this->request->getPost('id_diciplina') ?? 0);
        $disciplinaLabel = trim((string) ($this->request->getPost('disciplina_label') ?? ''));
        $clave = trim((string) ($this->request->getPost('clave') ?? ''));
        $folio = trim((string) ($this->request->getPost('folio') ?? ''));
        $subFolio = trim((string) ($this->request->getPost('sub_folio') ?? ''));
        $pax = (int) ($this->request->getPost('pax') ?? 0);
        $anfGto = trim((string) ($this->request->getPost('anf_gto') ?? ''));
        $idPerfilSolicitado = (int) ($this->request->getPost('id_perfil_solicitado') ?? 0);
        $usuario = '';
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        $primerApellido = trim((string) ($this->request->getPost('primer_apellido') ?? ''));
        $segundoApellido = trim((string) ($this->request->getPost('segundo_apellido') ?? ''));
        $correo = trim((string) ($this->request->getPost('correo') ?? ''));
        $beneficios = trim((string) ($this->request->getPost('beneficios') ?? 'ninguno'));
        $observaciones = trim((string) ($this->request->getPost('observaciones') ?? ''));

        $clave = function_exists('mb_strtolower') ? mb_strtolower($clave, 'UTF-8') : strtolower($clave);
        $folio = preg_replace('/\D+/', '', $folio);
        $subFolio = function_exists('mb_strtoupper') ? mb_strtoupper($subFolio, 'UTF-8') : strtoupper($subFolio);
        $anfGto = function_exists('mb_strtoupper') ? mb_strtoupper($anfGto, 'UTF-8') : strtoupper($anfGto);
        $correo = function_exists('mb_strtolower') ? mb_strtolower($correo, 'UTF-8') : strtolower($correo);
        $nombre = function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre);
        $primerApellido = function_exists('mb_strtoupper') ? mb_strtoupper($primerApellido, 'UTF-8') : strtoupper($primerApellido);
        $segundoApellido = function_exists('mb_strtoupper') ? mb_strtoupper($segundoApellido, 'UTF-8') : strtoupper($segundoApellido);

        if ($idClave <= 0 || $idPais <= 0 || $idDiciplina <= 0 || $clave === '' || $folio === '' || $subFolio === '' || $pax <= 0 || $anfGto === '' || $idPerfilSolicitado <= 0 || $nombre === '' || $primerApellido === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Completa los campos obligatorios.',
            ]);
        }

        $db = \Config\Database::connect();
        $perfilSolicitado = $db->table('cat_fic')
            ->where('id_perfil_fic', $idPerfilSolicitado)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($perfilSolicitado)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'El perfil solicitado no existe.',
            ]);
        }

        $idEstablecimiento = (int) ($session->get('id_establecimiento') ?? 0);
        if ($idEstablecimiento <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'No fue posible resolver el establecimiento de sesión.',
            ]);
        }

        $solicitudDuplicada = $db->table('solicitud_usuario')
            ->select('id_solicitud_usuario')
            ->where('visible', 1)
            ->where('estatus', 'pendiente')
            ->where('tipo_solicitud', 'alta_usuario_fic')
            ->where('id_establecimiento', $idEstablecimiento)
            ->where('id_perfil_solicitado', $idPerfilSolicitado)
            ->where('LOWER(nombre) = LOWER(' . $db->escape($nombre) . ')', null, false)
            ->where('LOWER(primer_apellido) = LOWER(' . $db->escape($primerApellido) . ')', null, false)
            ->where('LOWER(IFNULL(segundo_apellido, "")) = LOWER(' . $db->escape($segundoApellido) . ')', null, false)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($solicitudDuplicada)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe una solicitud pendiente para este perfil y este nombre.',
            ]);
        }

        $usuarioExistente = $db->query(
            'SELECT id_usuario FROM usuario WHERE visible = 1 AND id_establecimiento = ? AND id_fic_perfil = ? AND UPPER(nombre) = UPPER(?) AND UPPER(primer_apellido) = UPPER(?) AND UPPER(IFNULL(segundo_apellido, "")) = UPPER(?) LIMIT 1',
            [$idEstablecimiento, $idPerfilSolicitado, $nombre, $primerApellido, $segundoApellido]
        )->getRowArray();

        if (!empty($usuarioExistente)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe un usuario activo con este nombre para el perfil solicitado.',
            ]);
        }

        $detalleSolicitud = [];
        $beneficiosKey = function_exists('mb_strtolower') ? mb_strtolower($beneficios, 'UTF-8') : strtolower($beneficios);
        $beneficiosLabel = [
            'ninguno' => 'Ninguno',
            'hospedaje' => 'Hospedaje',
            'alimentos' => 'Alimentos',
            'ambos' => 'Hospedaje y alimentos',
        ][$beneficiosKey] ?? 'Ninguno';
        $detalleSolicitud[] = 'Beneficios: ' . $beneficiosLabel;
        if (in_array($beneficiosKey, ['hospedaje', 'ambos'], true)) {
            $detalleSolicitud[] = 'Hospedaje: sí';
            $detalleSolicitud[] = 'Partida automática hospedaje: 3390A';
        }
        if (in_array($beneficiosKey, ['alimentos', 'ambos'], true)) {
            $detalleSolicitud[] = 'Alimentos: sí';
            $detalleSolicitud[] = 'Partida automática alimentos: 3390B';
        }
        if ($categoriaLabel !== '') $detalleSolicitud[] = 'Categoría: ' . $categoriaLabel;
        if ($paisLabel !== '') $detalleSolicitud[] = 'País o región: ' . $paisLabel;
        if ($disciplinaLabel !== '') $detalleSolicitud[] = 'Disciplina: ' . $disciplinaLabel;
        if ($clave !== '') $detalleSolicitud[] = 'Clave: ' . $clave;
        if ($folio !== '') $detalleSolicitud[] = 'Folio: ' . $folio;
        if ($subFolio !== '') $detalleSolicitud[] = 'Subfolio: ' . $subFolio;
        if ($pax > 0) $detalleSolicitud[] = 'Pax: ' . $pax;
        if ($anfGto !== '') $detalleSolicitud[] = 'Anfitrión Gto: ' . $anfGto;
        if ($observaciones !== '') {
            $detalleSolicitud[] = '';
            $detalleSolicitud[] = 'Observaciones:';
            $detalleSolicitud[] = $observaciones;
        }
        $comentarioSolicitud = !empty($detalleSolicitud) ? implode("\n", $detalleSolicitud) : null;
        $fechaAhora = date('Y-m-d H:i:s');
        $insertOk = $db->table('solicitud_usuario')->insert([
            'tipo_solicitud' => 'alta_usuario_fic',
            'id_proveedor' => 0,
            'id_establecimiento' => $idEstablecimiento,
            'id_perfil_solicitado' => $idPerfilSolicitado,
            'usuario' => $usuario,
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido,
            'correo' => $correo,
            'estatus' => 'pendiente',
            'comentario_ti' => $comentarioSolicitud,
            'id_usuario_creado' => null,
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
            'visible' => 1,
        ]);

        if (!$insertOk) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible guardar la solicitud.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud enviada correctamente.',
            'data' => [
                'id_solicitud_usuario' => (int) $db->insertID(),
            ],
        ]);
    }

    public function cancelarSolicitudUsuarioFicPerfil()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esFic = (string) ($contextoUsuario['active_group'] ?? '') === 'fic';
        $rolFic = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($tiUsuario) && (!$esFic || !in_array($rolFic, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para cancelar solicitudes FIC.',
            ]);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Solicitud no v?lida.',
            ]);
        }

        $db = \Config\Database::connect();
        $solicitud = $db->table('solicitud_usuario')
            ->where('id_solicitud_usuario', $idSolicitud)
            ->where('visible', 1)
            ->where('tipo_solicitud', 'alta_usuario_fic')
            ->where('usu_reg', $idSesionUsuario)
            ->get()
            ->getRowArray();

        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente') {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Solo se pueden cancelar solicitudes pendientes.',
            ]);
        }

        $fechaAhora = date('Y-m-d H:i:s');
        $updateOk = $db->table('solicitud_usuario')->update([
            'estatus' => 'cancelada',
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
        ], [
            'id_solicitud_usuario' => $idSolicitud,
        ]);

        if (!$updateOk) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible cancelar la solicitud.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud cancelada correctamente.',
        ]);
    }

    private function mapSolicitudUsuarioFicPerfilRow(array $row): array
    {
        $nombreCompleto = trim(implode(' ', array_filter([
            trim((string) ($row['nombre'] ?? '')),
            trim((string) ($row['primer_apellido'] ?? '')),
            trim((string) ($row['segundo_apellido'] ?? '')),
        ])));

        return [
            'id_solicitud_usuario' => (int) ($row['id_solicitud_usuario'] ?? 0),
            'tipo_solicitud' => (string) ($row['tipo_solicitud'] ?? ''),
            'id_proveedor' => (int) ($row['id_proveedor'] ?? 0),
            'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
            'id_perfil_solicitado' => (int) ($row['id_perfil_solicitado'] ?? 0),
            'perfil_solicitado' => (string) ($row['perfil_solicitado'] ?? ''),
            'usuario' => (string) ($row['usuario'] ?? ''),
            'nombre' => (string) ($row['nombre'] ?? ''),
            'primer_apellido' => (string) ($row['primer_apellido'] ?? ''),
            'segundo_apellido' => (string) ($row['segundo_apellido'] ?? ''),
            'correo' => (string) ($row['correo'] ?? ''),
            'nombre_completo' => $nombreCompleto,
            'estatus' => (string) ($row['estatus'] ?? ''),
            'comentario_ti' => (string) ($row['comentario_ti'] ?? ''),
            'fec_reg' => (string) ($row['fec_reg'] ?? ''),
            'visible' => (int) ($row['visible'] ?? 0),
        ];
    }
    private function buildPartidasDashboardSeed(): array
    {
        $defaultSeed = [
            'resumen' => [
                'monto_presupuesto' => '$0.00',
                'monto_ejercido' => '$0.00',
                'monto_disponible' => '$0.00',
                'usuarios_asignados' => '0',
                'usuarios_qr_activo' => '0',
                'movimientos_cobro' => '0',
                'consumo_operativo' => '$0.00',
                'porcentaje_ejercido' => '0%',
                'estatus' => 'Sin datos',
                'ultima_actualizacion' => date('Y-m-d H:i:s'),
            ],
            'partidas' => [],
            'meta' => [
                'ultima_actualizacion' => date('Y-m-d H:i:s'),
            ],
        ];

        $session = \Config\Services::session();
        $jwt = new \App\Libraries\Funciones();
        $token = $jwt->generateToken([
            'id' => (int) ($session->get('id_perfil') ?? 0),
            'nombre' => (string) ($session->get('nombre_completo') ?? ''),
        ]);

        $client = \Config\Services::curlrequest();
        $baseUrl = rtrim((string) env('BACK_STI_API_BASE_URL'), '/');
        $urls = [
            $baseUrl . '/api/partidas-fic/seed',
            $baseUrl . '/partidas-fic/seed',
        ];

        foreach ($urls as $url) {
            if ($url === '/') {
                continue;
            }

            try {
                $response = $client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 10,
                ]);

                $payload = json_decode((string) $response->getBody(), true);
                if (is_array($payload) && !empty($payload['ok']) && isset($payload['partidas']) && is_array($payload['partidas'])) {
                    $partidasRemotas = array_values(array_map(static function ($item) {
                        return is_array($item) ? $item : (array) $item;
                    }, $payload['partidas']));

                    $partidasLocales = $this->buildPartidasDashboardSeedFromLocal()['partidas'] ?? [];
                    $partidasLocalesMap = [];
                    foreach ($partidasLocales as $partidaLocal) {
                        $partidasLocalesMap[(int) ($partidaLocal['id_partida'] ?? 0)] = $partidaLocal;
                    }

                    $partidasFinales = [];
                    foreach ($partidasRemotas as $partidaRemota) {
                        $partidasFinales[(int) ($partidaRemota['id_partida'] ?? 0)] = $partidaRemota;
                    }
                    foreach ($partidasLocalesMap as $idPartida => $partidaLocal) {
                        if (!isset($partidasFinales[$idPartida])) {
                            $partidasFinales[$idPartida] = $partidaLocal;
                        }
                    }

                    usort($partidasFinales, static function (array $a, array $b) {
                        return (int) ($a['id_partida'] ?? 0) <=> (int) ($b['id_partida'] ?? 0);
                    });

                    return [
                        'resumen' => is_array($payload['resumen'] ?? null) ? $payload['resumen'] : $defaultSeed['resumen'],
                        'partidas' => $partidasFinales,
                        'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : $defaultSeed['meta'],
                    ];
                }
            } catch (\Throwable $e) {
                log_message('error', 'No fue posible consultar el seed de partidas en backSti (' . $url . '): ' . $e->getMessage());
            }
        }

        return $this->buildPartidasDashboardSeedFromLocal();
    }

    private function buildPartidasDashboardSeedFromLocal(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('cat_partida')
            ->select('id_partida, partida, des_partida, monto_presupuesto, monto_ejercido, monto_disponible, porcentaje_ejercido, proyecto, estatus, color_dashboard, orden_dashboard, fec_reg, fec_act, usu_reg, usu_act, visible')
            ->whereIn('id_partida', [0, 1, 2, 3])
            ->orderBy('orden_dashboard', 'ASC')
            ->get()
            ->getResultArray();

        $partidas = [];
        $montoPresupuesto = 0.0;
        $montoEjercido = 0.0;
        $montoDisponible = 0.0;
        $usuariosAsignados = 0;
        $usuariosQrActivo = 0;
        $movimientosCobro = 0;
        $consumoOperativo = 0.0;
        $fechaActualizacion = '';

        foreach ($rows as $row) {
            $presupuesto = (float) ($row['monto_presupuesto'] ?? 0);
            $ejercido = (float) ($row['monto_ejercido'] ?? 0);
            $disponible = (float) ($row['monto_disponible'] ?? 0);
            $porcentaje = (float) ($row['porcentaje_ejercido'] ?? 0);
            $consumo = max(0, $presupuesto - $disponible);
            $montoPresupuesto += $presupuesto;
            $montoEjercido += $ejercido;
            $montoDisponible += $disponible;
            $consumoOperativo += $consumo;
            $usuariosAsignados += 0;
            $usuariosQrActivo += 0;
            $movimientosCobro += 0;
            $fechaAct = trim((string) ($row['fec_act'] ?? ''));
            if ($fechaAct !== '') {
                $fechaActualizacion = max($fechaActualizacion, $fechaAct);
            }

            $partidas[] = [
                'id_partida' => (int) ($row['id_partida'] ?? 0),
                'partida' => (string) ($row['partida'] ?? ''),
                'des_partida' => (string) ($row['des_partida'] ?? ''),
                'monto_presupuesto' => '$' . number_format($presupuesto, 2),
                'monto_ejercido' => '$' . number_format($ejercido, 2),
                'monto_disponible' => '$' . number_format($disponible, 2),
                'consumo_operativo' => '$' . number_format($consumo, 2),
                'porcentaje_ejercido' => number_format($porcentaje, 2) . '%',
                'usuarios_asignados' => 0,
                'usuarios_qr_activo' => 0,
                'movimientos_cobro' => 0,
                'estatus' => (string) ($row['estatus'] ?? 'Sin definir'),
                'fec_act' => $fechaAct,
                'color_dashboard' => (string) ($row['color_dashboard'] ?? ''),
            ];
        }

        $porcentajeGlobal = $montoPresupuesto > 0 ? (($montoEjercido / $montoPresupuesto) * 100) : 0;

        return [
            'resumen' => [
                'monto_presupuesto' => '$' . number_format($montoPresupuesto, 2),
                'monto_ejercido' => '$' . number_format($montoEjercido, 2),
                'monto_disponible' => '$' . number_format($montoDisponible, 2),
                'usuarios_asignados' => (string) $usuariosAsignados,
                'usuarios_qr_activo' => (string) $usuariosQrActivo,
                'movimientos_cobro' => (string) $movimientosCobro,
                'consumo_operativo' => '$' . number_format($consumoOperativo, 2),
                'porcentaje_ejercido' => number_format($porcentajeGlobal, 2) . '%',
                'estatus' => !empty($partidas) ? 'Con datos locales' : 'Sin datos',
                'ultima_actualizacion' => $fechaActualizacion !== '' ? $fechaActualizacion : date('Y-m-d H:i:s'),
            ],
            'partidas' => $partidas,
            'meta' => [
                'ultima_actualizacion' => $fechaActualizacion !== '' ? $fechaActualizacion : date('Y-m-d H:i:s'),
                'source' => 'local',
            ],
        ];
    }
    private function buildProviderDashboardData(int $idUsuario): array
    {
        $db = \Config\Database::connect();

        $usuario = $db->table('usuario u')
            ->select('
                u.id_usuario,
                u.id_proveedor,
                u.id_tipo_proveedor,
                u.usuario,
                u.nombre,
                u.primer_apellido,
                u.segundo_apellido,
                u.correo,
                u.qr,
                u.nip,
                u.visible,
                p.no_proveedor,
                p.razon_social,
                p.rfc,
                p.fic
            ')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'left')
            ->where('u.id_usuario', $idUsuario)
            ->where('u.visible', 1)
            ->where('p.visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return [
                'proveedorPerfil' => [],
                'proveedorEstablecimientos' => [],
                'proveedorPagos' => [],
                'ventasCorteContexto' => [
                    'monto_total' => 0,
                    'monto_pendiente' => 0,
                    'total_registros' => 0,
                    'fecha_corte_desde' => '',
                    'fecha_corte_hasta' => '',
                    'estado_corte' => 'Sin movimientos',
                ],
            ];
        }

        $proveedor = (object) $usuario;
        $establecimientos = $this->resolveProviderEstablishments($db, $proveedor);
        $establecimientoIds = array_values(array_filter(array_map(static function ($row) {
            return (int) ($row->id_establecimiento ?? 0);
        }, $establecimientos)));

        $pagosRows = [];
        if (!empty($establecimientoIds)) {
            $builder = $db->table('solicitud_pago sp')
                ->select('
                    sp.id_solicitud_pago,
                    sp.folio_solicitud,
                    sp.id_usuario,
                    sp.id_establecimiento,
                    sp.monto_solicitado,
                    sp.estatus,
                    sp.fecha_respuesta,
                    sp.fec_reg,
                    sp.observaciones,
                    e.dsc_establecimiento,
                    cte.dsc_tipo
                ')
                ->join('establecimiento e', 'e.id_establecimiento = sp.id_establecimiento', 'left')
                ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
                ->where('sp.visible', 1)
                ->groupStart()
                    ->where('sp.id_usuario', $idUsuario)
                    ->orWhereIn('sp.id_establecimiento', $establecimientoIds)
                ->groupEnd()
                ->orderBy('sp.fec_reg', 'DESC')
                ->limit(25);

            $pagosRows = $builder->get()->getResultArray();
        }

        foreach ($pagosRows as &$row) {
            $montoSolicitado = (float) ($row['monto_solicitado'] ?? 0);
            $observaciones = json_decode((string) ($row['observaciones'] ?? ''), true);
            $montoConsumo = $montoSolicitado;
            $propina = 0.0;

            if (is_array($observaciones)) {
                $montoJson = $observaciones['monto'] ?? null;
                $propinaJson = $observaciones['propina'] ?? null;

                if (is_numeric($montoJson)) {
                    $montoConsumo = (float) $montoJson;
                }
                if (is_numeric($propinaJson)) {
                    $propina = (float) $propinaJson;
                }
            }

            $row['monto_consumo'] = $montoConsumo;
            $row['propina'] = $propina;
            $row['monto_total'] = $montoConsumo + $propina;
        }
        unset($row);

        $montoTotal = 0.0;
        $montoPendiente = 0.0;
        $fechas = [];
        foreach ($pagosRows as $row) {
            $monto = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
            $montoTotal += $monto;
            $estatus = strtolower(trim((string) ($row['estatus'] ?? '')));
            if ($estatus === '' || in_array($estatus, ['pendiente', 'solicitado', 'en_revision'], true)) {
                $montoPendiente += $monto;
            }
            foreach (['fec_reg', 'fecha_respuesta'] as $campoFecha) {
                $valorFecha = trim((string) ($row[$campoFecha] ?? ''));
                if ($valorFecha !== '') {
                    $fechas[] = $valorFecha;
                }
            }
        }

        sort($fechas);
        $fechaDesde = $fechas[0] ?? '';
        $fechaHasta = !empty($fechas) ? end($fechas) : '';

        return [
            'proveedorPerfil' => $usuario,
            'proveedorEstablecimientos' => $establecimientos,
            'proveedorPagos' => $pagosRows,
            'ventasCorteContexto' => [
                'monto_total' => $montoTotal,
                'monto_pendiente' => $montoPendiente,
                'total_registros' => count($pagosRows),
                'fecha_corte_desde' => $fechaDesde,
                'fecha_corte_hasta' => $fechaHasta,
                'estado_corte' => !empty($pagosRows) ? 'Con movimientos' : 'Sin movimientos',
            ],
        ];
    }


    private function buildPagosFicDashboardData(): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('solicitud_pago sp')
            ->select('
                sp.id_solicitud_pago,
                sp.folio_solicitud,
                sp.id_usuario,
                sp.id_establecimiento,
                sp.monto_solicitado,
                sp.metodo_autorizacion,
                sp.estatus,
                sp.token_autorizacion,
                sp.fecha_respuesta,
                sp.motivo_rechazo,
                sp.observaciones,
                sp.fec_reg,
                sp.usu_reg,
                sp.fec_act,
                sp.usu_act,
                sp.visible,
                u.usuario AS usuario_solicitante,
                u.nombre AS nombre_solicitante,
                u.primer_apellido AS primer_apellido_solicitante,
                u.segundo_apellido AS segundo_apellido_solicitante,
                p.no_proveedor,
                p.razon_social,
                p.rfc,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo
            ')
            ->join('usuario u', 'u.id_usuario = sp.id_usuario', 'left')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'left')
            ->join('establecimiento e', 'e.id_establecimiento = sp.id_establecimiento', 'left')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->where('sp.visible', 1)
            ->orderBy('sp.fec_reg', 'DESC')
            ->get()
            ->getResultArray();

        $montoTotal = 0.0;
        $montoPendiente = 0.0;
        $montoAprobado = 0.0;
        $montoRechazado = 0.0;
        $fechas = [];
        $pendientes = 0;
        $aprobados = 0;
        $rechazados = 0;

        foreach ($rows as $row) {
            $monto = (float) ($row['monto_solicitado'] ?? 0);
            $estatus = strtolower(trim((string) ($row['estatus'] ?? '')));

            $montoTotal += $monto;

            if (in_array($estatus, ['pendiente', 'solicitado', 'en_revision'], true)) {
                $montoPendiente += $monto;
                $pendientes++;
            } elseif (in_array($estatus, ['aprobada', 'autorizada', 'pagada', 'finalizada'], true)) {
                $montoAprobado += $monto;
                $aprobados++;
            } elseif (in_array($estatus, ['rechazada', 'cancelada'], true)) {
                $montoRechazado += $monto;
                $rechazados++;
            }

            foreach (['fec_reg', 'fecha_respuesta'] as $campoFecha) {
                $valorFecha = trim((string) ($row[$campoFecha] ?? ''));
                if ($valorFecha !== '') {
                    $fechas[] = $valorFecha;
                }
            }
        }

        sort($fechas);

        return [
            'resumen' => [
                'monto_total' => $montoTotal,
                'monto_pendiente' => $montoPendiente,
                'monto_aprobado' => $montoAprobado,
                'monto_rechazado' => $montoRechazado,
                'total_registros' => count($rows),
                'pendientes' => $pendientes,
                'aprobados' => $aprobados,
                'rechazados' => $rechazados,
                'fecha_corte_desde' => $fechas[0] ?? '',
                'fecha_corte_hasta' => !empty($fechas) ? end($fechas) : '',
                'estado_corte' => !empty($rows) ? 'Con movimientos' : 'Sin movimientos',
            ],
            'pagos' => array_map(static function (array $row): array {
                $nombreSolicitante = trim(implode(' ', array_filter([
                    trim((string) ($row['nombre_solicitante'] ?? '')),
                    trim((string) ($row['primer_apellido_solicitante'] ?? '')),
                    trim((string) ($row['segundo_apellido_solicitante'] ?? '')),
                ])));

                return [
                    'id_solicitud_pago' => (int) ($row['id_solicitud_pago'] ?? 0),
                    'folio_solicitud' => (string) ($row['folio_solicitud'] ?? ''),
                    'id_usuario' => (int) ($row['id_usuario'] ?? 0),
                    'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
                    'monto_solicitado' => (float) ($row['monto_solicitado'] ?? 0),
                    'metodo_autorizacion' => (string) ($row['metodo_autorizacion'] ?? ''),
                    'estatus' => (string) ($row['estatus'] ?? ''),
                    'token_autorizacion' => (string) ($row['token_autorizacion'] ?? ''),
                    'fecha_respuesta' => (string) ($row['fecha_respuesta'] ?? ''),
                    'motivo_rechazo' => (string) ($row['motivo_rechazo'] ?? ''),
                    'observaciones' => (string) ($row['observaciones'] ?? ''),
                    'fec_reg' => (string) ($row['fec_reg'] ?? ''),
                    'usu_reg' => (int) ($row['usu_reg'] ?? 0),
                    'fec_act' => (string) ($row['fec_act'] ?? ''),
                    'usu_act' => (int) ($row['usu_act'] ?? 0),
                    'visible' => (int) ($row['visible'] ?? 0),
                    'usuario_solicitante' => (string) ($row['usuario_solicitante'] ?? ''),
                    'nombre_solicitante' => $nombreSolicitante,
                    'no_proveedor' => (string) ($row['no_proveedor'] ?? ''),
                    'razon_social' => (string) ($row['razon_social'] ?? ''),
                    'rfc' => (string) ($row['rfc'] ?? ''),
                    'dsc_establecimiento' => (string) ($row['dsc_establecimiento'] ?? ''),
                    'id_tipo_establecimiento' => (int) ($row['id_tipo'] ?? 0),
                    'dsc_tipo' => (string) ($row['dsc_tipo'] ?? ''),
                ];
            }, $rows),
        ];
    }    public function EstablecimientosFic()
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

        $builder = $db->table('proveedor p')
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
                'message' => 'Proveedor invalido.',
            ]);
        }

        $db = \Config\Database::connect();

        $proveedor = $db->table('proveedor p')
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
                'message' => 'No se encontro el proveedor seleccionado.',
            ]);
        }

        $establecimientos = $this->resolveProviderEstablishments($db, $proveedor);

        return $this->response->setJSON([
            'ok' => true,
            'message' => empty($establecimientos)
                ? 'No hay establecimientos ligados a este proveedor.'
                : '',
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

    private function resolveProviderEstablishments($db, object $proveedor): array
    {
        $noProveedor = trim((string) ($proveedor->no_proveedor ?? ''));
        if ($noProveedor === '') {
            return [];
        }

        return $db->table('establecimiento e')
            ->select('
                e.id_establecimiento,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo,
                e.no_proveedor
            ')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->where('e.visible', 1)
            ->where('e.no_proveedor', $noProveedor)
            ->orderBy('e.dsc_establecimiento', 'ASC')
            ->get()
            ->getResult();
    }

    public function getEstablecimientosProveedor()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'establecimientos' => [],
                'message' => 'No tienes permisos para consultar establecimientos de proveedor.',
            ]);
        }

        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => false,
                'establecimientos' => [],
                'message' => 'Sesión inválida.',
            ]);
        }

        $db = \Config\Database::connect();
        $rows = $db->query(
            "SELECT
                e.id_establecimiento,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo,
                e.no_proveedor
            FROM usuario AS u
            INNER JOIN proveedor AS p
                ON p.id_proveedor = u.id_proveedor
            INNER JOIN establecimiento AS e
                ON e.no_proveedor = p.no_proveedor
            LEFT JOIN cat_tipo_establecimiento AS cte
                ON cte.id_tipo = e.id_tipo
            WHERE u.id_usuario = ?
              AND u.visible = 1
              AND p.visible = 1
              AND e.visible = 1
            ORDER BY cte.dsc_tipo, e.dsc_establecimiento",
            [$idUsuario]
        )->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'establecimientos' => array_map(static function (array $row) {
                return [
                    'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
                    'dsc_establecimiento' => (string) ($row['dsc_establecimiento'] ?? ''),
                    'id_tipo' => (int) ($row['id_tipo'] ?? 0),
                    'dsc_tipo' => (string) ($row['dsc_tipo'] ?? ''),
                    'no_proveedor' => (string) ($row['no_proveedor'] ?? ''),
                ];
            }, $rows),
        ]);
    }

    public function guardarSolicitudUsuarioProveedor()
    {
        $session = \Config\Services::session();
        if ($idSesionUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Solo un proveedor autenticado puede enviar solicitudes.',
            ]);
        }

        $db = \Config\Database::connect();
        $usuarioProveedor = $db->table('usuario u')
            ->select('u.id_usuario, u.usuario, u.nombre, u.id_proveedor, u.id_tipo_proveedor, p.no_proveedor, p.razon_social')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'inner')
            ->where('u.id_usuario', $idSesionUsuario)
            ->where('u.visible', 1)
            ->where('u.id_proveedor >', 0)
            ->where('u.id_tipo_proveedor', 1)
            ->where('p.visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuarioProveedor) || (int) ($usuarioProveedor['id_proveedor'] ?? 0) <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No fue posible resolver el proveedor autenticado.',
            ]);
        }

        $idProveedor = (int) ($usuarioProveedor['id_proveedor'] ?? 0);
        $noProveedor = trim((string) ($usuarioProveedor['no_proveedor'] ?? ''));
        if ($noProveedor === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'El proveedor autenticado no tiene un no_proveedor valido.',
            ]);
        }

        $idEstablecimiento = (int) ($this->request->getPost('id_establecimiento') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        $primerApellido = trim((string) ($this->request->getPost('primer_apellido') ?? ''));
        $segundoApellido = trim((string) ($this->request->getPost('segundo_apellido') ?? ''));
        $correo = trim((string) ($this->request->getPost('correo') ?? ''));

        $nombre = function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre);
        $primerApellido = function_exists('mb_strtoupper') ? mb_strtoupper($primerApellido, 'UTF-8') : strtoupper($primerApellido);
        $segundoApellido = function_exists('mb_strtoupper') ? mb_strtoupper($segundoApellido, 'UTF-8') : strtoupper($segundoApellido);
        $correo = function_exists('mb_strtolower') ? mb_strtolower($correo, 'UTF-8') : strtolower($correo);

        if ($idEstablecimiento <= 0 || $nombre === '' || $primerApellido === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Completa los campos obligatorios.',
            ]);
        }

        $establecimiento = $db->table('establecimiento e')
            ->select('e.id_establecimiento, e.id_tipo, e.no_proveedor, e.dsc_establecimiento, cte.dsc_tipo')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->where('e.id_establecimiento', $idEstablecimiento)
            ->where('e.visible', 1)
            ->where('e.no_proveedor', $noProveedor)
            ->get()
            ->getRowArray();

        if (empty($establecimiento)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'El establecimiento no pertenece al proveedor autenticado.',
            ]);
        }

        $idTipoEstablecimiento = (int) ($establecimiento['id_tipo'] ?? 0);
        $idPerfilSolicitado = 0;
        $tipoSolicitud = '';
        $tipoUsuarioLabel = '';

        if ($idTipoEstablecimiento === 1) {
            $idPerfilSolicitado = 5;
            $tipoSolicitud = 'alta_gerente';
            $tipoUsuarioLabel = 'GERENTE';
        } elseif ($idTipoEstablecimiento === 2) {
            $idPerfilSolicitado = 7;
            $tipoSolicitud = 'alta_recepcion';
            $tipoUsuarioLabel = 'RECEPCI?N';
        }

        if ($idPerfilSolicitado <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'No fue posible resolver el perfil solicitado para este establecimiento.',
            ]);
        }

        $usuarioOperativo = $db->table('usuario')
            ->select('id_usuario')
            ->where('id_establecimiento', $idEstablecimiento)
            ->where('id_perfil', $idPerfilSolicitado)
            ->where('visible', 1)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($usuarioOperativo)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe un usuario operativo activo para este establecimiento y perfil.',
            ]);
        }

        $solicitudDuplicada = $db->table('solicitud_usuario')
            ->select('id_solicitud_usuario')
            ->where('id_establecimiento', $idEstablecimiento)
            ->where('id_perfil_solicitado', $idPerfilSolicitado)
            ->where('estatus', 'pendiente')
            ->where('visible', 1)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($solicitudDuplicada)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe una solicitud pendiente para este establecimiento y perfil.',
            ]);
        }

        $insertData = [
            'tipo_solicitud' => $tipoSolicitud,
            'id_proveedor' => $idProveedor,
            'id_establecimiento' => $idEstablecimiento,
            'id_perfil_solicitado' => $idPerfilSolicitado,
            'usuario' => (string) ($usuarioProveedor['usuario'] ?? ''),
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido !== '' ? $segundoApellido : null,
            'correo' => $correo !== '' ? $correo : null,
            'estatus' => 'pendiente',
            'comentario_ti' => null,
            'id_usuario_creado' => null,
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => $idSesionUsuario,
            'visible' => 1,
        ];

        $ok = $db->table('solicitud_usuario')->insert($insertData);
        if (!$ok) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible guardar la solicitud.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud enviada correctamente.',
            'data' => [
                'id_solicitud_usuario' => $db->insertID(),
                'tipo_solicitud' => $tipoSolicitud,
                'tipo_usuario' => $tipoUsuarioLabel,
                'id_perfil_solicitado' => $idPerfilSolicitado,
                'id_establecimiento' => $idEstablecimiento,
            ],
        ]);
    }

    public function SolicitudesUsuarioFic()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        $data = [];
        $data['scripts'] = ['principal', 'agregar', 'solicitudes_usuario_operativo', 'solicitudes_usuario_fic_panel'];
        $data['contextoUsuario'] = $contextoUsuario;
        $data['ficSolicitudListadoUrl'] = base_url('index.php/Inicio/getSolicitudesUsuarioFicPerfil');
        $data['ficSolicitudDetalleUrl'] = base_url('index.php/Inicio/getSolicitudUsuarioFicPerfil');
        $data['ficSolicitudCancelarUrl'] = base_url('index.php/Inicio/cancelarSolicitudUsuarioFicPerfil');
        $data['qrSolicitudListadoUrl'] = base_url('index.php/Inicio/getSolicitudesActivacionQrFic');
        $data['operativoSolicitudListadoUrl'] = base_url('index.php/Inicio/getSolicitudesUsuarioOperativo');
        $data['operativoSolicitudDetalleUrl'] = base_url('index.php/Inicio/getSolicitudUsuarioOperativo');
        $data['operativoSolicitudAprobarUrl'] = base_url('index.php/Inicio/aprobarSolicitudUsuarioOperativo');
        $data['operativoSolicitudRechazarUrl'] = base_url('index.php/Inicio/rechazarSolicitudUsuarioOperativo');
        $data['contentView'] = 'secciones/vSolicitudesUsuarioFic';
        $this->_renderView($data);
    }

    public function getSolicitudesActivacionQrFic()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'total' => 0,
                'rows' => [],
                'message' => 'No tienes permisos para consultar solicitudes de activaciÃ³n QR.',
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuario u')
            ->select('
                u.id_usuario,
                u.usuario,
                CONCAT_WS(" ", u.nombre, u.primer_apellido, u.segundo_apellido) AS nombre_completo,
                u.correo,
                u.activo_qr,
                u.deposito_programado_estatus,
                u.fec_reg,
                u.fec_act,
                u.visible
            ')
            ->where('u.visible', 1)
            ->where('u.activo_qr', 0);

        $search = trim((string) ($this->request->getGet('search') ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('u.usuario', $search)
                ->orLike('u.nombre', $search)
                ->orLike('u.primer_apellido', $search)
                ->orLike('u.segundo_apellido', $search)
                ->orLike('u.correo', $search)
                ->orLike('u.deposito_programado_estatus', $search)
                ->groupEnd();
        }

        $estatus = trim((string) ($this->request->getGet('estatus') ?? ''));
        if ($estatus !== '' && !in_array(strtolower($estatus), ['todos', 'all'], true)) {
            if ($estatus === 'activo') {
                $builder->where('u.activo_qr', 1);
            } elseif ($estatus === 'pendiente') {
                $builder->where('u.activo_qr', 0);
            } else {
                $builder->where('u.deposito_programado_estatus', $estatus);
            }
        }

        $total = (clone $builder)->countAllResults();
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 10));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        $rows = $builder
            ->orderBy('u.fec_reg', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        $mapped = array_map(static function (array $row): array {
            $activoQr = (int) ($row['activo_qr'] ?? 0);
            $estatus = $activoQr === 1 ? 'aprobada' : 'pendiente';
            return [
                'id_usuario' => (int) ($row['id_usuario'] ?? 0),
                'usuario' => (string) ($row['usuario'] ?? ''),
                'nombre_completo' => (string) ($row['nombre_completo'] ?? ''),
                'correo' => (string) ($row['correo'] ?? ''),
                'estatus' => $estatus,
                'deposito_programado_estatus' => (string) ($row['deposito_programado_estatus'] ?? ''),
                'fec_reg' => (string) ($row['fec_reg'] ?? ''),
                'fec_act' => (string) ($row['fec_act'] ?? ''),
                'activo_qr' => $activoQr,
                'acciones' => '',
            ];
        }, $rows);

        return $this->response->setJSON([
            'ok' => true,
            'total' => $total,
            'rows' => $mapped,
        ]);
    }

    private function solicitudUsuarioOperativoBaseBuilder($db)
    {
        return $db->table('solicitud_usuario su')
            ->select('
                su.id_solicitud_usuario,
                su.tipo_solicitud,
                su.id_proveedor,
                su.id_establecimiento,
                su.id_perfil_solicitado,
                su.usuario AS proveedor_usuario,
                su.nombre,
                su.primer_apellido,
                su.segundo_apellido,
                su.correo,
                su.estatus,
                su.comentario_ti,
                su.id_usuario_creado,
                su.fec_reg,
                su.usu_reg,
                su.fec_act,
                su.usu_act,
                su.visible,
                p.no_proveedor,
                p.razon_social,
                p.rfc,
                p.id_tipo_proveedor,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo
            ')
            ->join('proveedor p', 'p.id_proveedor = su.id_proveedor', 'left')
            ->join('establecimiento e', 'e.id_establecimiento = su.id_establecimiento', 'left')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left');
    }

    private function resolveSolicitudUsuarioOperativoTipo(int $idTipoEstablecimiento): array
    {
        if ($idTipoEstablecimiento === 1) {
            return [
                'id_perfil_solicitado' => 5,
                'tipo_solicitud' => 'alta_gerente',
                'tipo_usuario_solicitado' => 'GERENTE',
            ];
        }

        if ($idTipoEstablecimiento === 2) {
            return [
                'id_perfil_solicitado' => 7,
                'tipo_solicitud' => 'alta_recepcion',
                'tipo_usuario_solicitado' => 'RECEPCI?N',
            ];
        }

        return [
            'id_perfil_solicitado' => 0,
            'tipo_solicitud' => '',
            'tipo_usuario_solicitado' => 'SIN DEFINIR',
        ];
    }

    private function mapSolicitudUsuarioOperativoRow(array $row): array
    {
        $nombreCompleto = trim(implode(' ', array_filter([
            trim((string) ($row['nombre'] ?? '')),
            trim((string) ($row['primer_apellido'] ?? '')),
            trim((string) ($row['segundo_apellido'] ?? '')),
        ])));

        $tipoInfo = $this->resolveSolicitudUsuarioOperativoTipo((int) ($row['id_tipo'] ?? 0));
        $proveedorSolicitante = trim((string) ($row['no_proveedor'] ?? ''));
        $proveedorRazn = trim((string) ($row['razon_social'] ?? ''));
        if ($proveedorRazn === '') {
            $proveedorRazn = trim((string) ($row['proveedor_usuario'] ?? ''));
        }

        return [
            'id_solicitud_usuario' => (int) ($row['id_solicitud_usuario'] ?? 0),
            'tipo_solicitud' => (string) ($row['tipo_solicitud'] ?? ''),
            'id_proveedor' => (int) ($row['id_proveedor'] ?? 0),
            'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
            'id_perfil_solicitado' => (int) ($row['id_perfil_solicitado'] ?? 0),
            'proveedor_solicitante' => $proveedorSolicitante,
            'proveedor_usuario' => (string) ($row['proveedor_usuario'] ?? ''),
            'proveedor_razon_social' => $proveedorRazn,
            'dsc_establecimiento' => (string) ($row['dsc_establecimiento'] ?? ''),
            'id_tipo_establecimiento' => (int) ($row['id_tipo'] ?? 0),
            'dsc_tipo' => (string) ($row['dsc_tipo'] ?? ''),
            'tipo_usuario_solicitado' => $tipoInfo['tipo_usuario_solicitado'],
            'nombre_completo' => $nombreCompleto,
            'nombre' => (string) ($row['nombre'] ?? ''),
            'primer_apellido' => (string) ($row['primer_apellido'] ?? ''),
            'segundo_apellido' => (string) ($row['segundo_apellido'] ?? ''),
            'correo' => (string) ($row['correo'] ?? ''),
            'estatus' => (string) ($row['estatus'] ?? ''),
            'comentario_ti' => (string) ($row['comentario_ti'] ?? ''),
            'id_usuario_creado' => (int) ($row['id_usuario_creado'] ?? 0),
            'fec_reg' => (string) ($row['fec_reg'] ?? ''),
            'usu_reg' => (int) ($row['usu_reg'] ?? 0),
            'fec_act' => (string) ($row['fec_act'] ?? ''),
            'usu_act' => (int) ($row['usu_act'] ?? 0),
            'visible' => (int) ($row['visible'] ?? 0),
        ];
    }

    private function resolveTiMasterUsuario(): array
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, id_perfil, id_proveedor, id_tipo_proveedor, visible')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return [];
        }

        $idProveedor = $usuario['id_proveedor'] ?? null;
        $idTipoProveedor = $usuario['id_tipo_proveedor'] ?? null;

        $sinProveedor = $idProveedor === null || $idProveedor === '' || (int) $idProveedor === 0;
        $sinTipoProveedor = $idTipoProveedor === null || $idTipoProveedor === '' || (int) $idTipoProveedor === 0;

        if ((int) ($usuario['id_perfil'] ?? 0) !== 1 || !$sinProveedor || !$sinTipoProveedor) {
            return [];
        }

        return $usuario;
    }

    public function getSolicitudesUsuarioOperativo()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'total' => 0,
                'rows' => [],
                'message' => 'No tienes permisos para consultar esta bandeja.',
            ]);
        }

        $db = \Config\Database::connect();
        $builder = $this->solicitudUsuarioOperativoBaseBuilder($db)
            ->where('su.visible', 1);

        $estatus = trim((string) ($this->request->getGet('estatus') ?? 'pendiente'));
        if ($estatus !== '' && !in_array(strtolower($estatus), ['todos', 'all'], true)) {
            $builder->where('su.estatus', $estatus);
        }

        $search = trim((string) ($this->request->getGet('search') ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('su.usuario', $search)
                ->orLike('su.nombre', $search)
                ->orLike('su.primer_apellido', $search)
                ->orLike('su.segundo_apellido', $search)
                ->orLike('su.correo', $search)
                ->orLike('p.no_proveedor', $search)
                ->orLike('p.razon_social', $search)
                ->orLike('e.dsc_establecimiento', $search)
                ->orLike('cte.dsc_tipo', $search)
                ->orLike('su.estatus', $search)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults();
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 10));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));

        $rows = $builder
            ->orderBy('su.fec_reg', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'total' => $total,
            'rows' => array_map(function (array $row) {
                return $this->mapSolicitudUsuarioOperativoRow($row);
            }, $rows),
        ]);
    }

    public function getSolicitudUsuarioOperativo($idSolicitudUsuario = null)
    {
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar solicitudes.',
            ]);
        }

        $idSolicitud = (int) ($idSolicitudUsuario ?? $this->request->getGet('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Solicitud no v?lida.',
            ]);
        }

        $db = \Config\Database::connect();
        $row = $this->solicitudUsuarioOperativoBaseBuilder($db)
            ->where('su.id_solicitud_usuario', $idSolicitud)
            ->where('su.visible', 1)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'No se encontr? la solicitud.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'data' => $this->mapSolicitudUsuarioOperativoRow($row),
        ]);
    }

    public function aprobarSolicitudUsuarioOperativo()
    {
        $session = \Config\Services::session();
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para aprobar solicitudes.',
            ]);
        }

        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        $usuario = trim((string) ($this->request->getPost('usuario') ?? ''));
        $contrasenia = trim((string) ($this->request->getPost('contrasenia') ?? ''));

        $usuario = function_exists('mb_strtolower') ? mb_strtolower($usuario, 'UTF-8') : strtolower($usuario);

        if ($idSolicitud <= 0 || $usuario === '' || $contrasenia === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Completa usuario y contraseña.',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $solicitud = $db->query(
            'SELECT
                su.id_solicitud_usuario,
                su.tipo_solicitud,
                su.id_proveedor,
                su.id_establecimiento,
                su.id_perfil_solicitado,
                su.usuario AS proveedor_usuario,
                su.nombre,
                su.primer_apellido,
                su.segundo_apellido,
                su.correo,
                su.estatus,
                su.comentario_ti,
                su.id_usuario_creado,
                su.fec_reg,
                su.usu_reg,
                su.fec_act,
                su.usu_act,
                su.visible,
                p.no_proveedor,
                p.razon_social,
                p.id_tipo_proveedor,
                e.id_tipo,
                e.dsc_establecimiento,
                e.no_proveedor AS establecimiento_no_proveedor,
                cte.dsc_tipo
             FROM solicitud_usuario su
             INNER JOIN proveedor p ON p.id_proveedor = su.id_proveedor
             INNER JOIN establecimiento e ON e.id_establecimiento = su.id_establecimiento
             LEFT JOIN cat_tipo_establecimiento cte ON cte.id_tipo = e.id_tipo
             WHERE su.id_solicitud_usuario = ?
             FOR UPDATE',
            [$idSolicitud]
        )->getRowArray();

        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente' || (int) ($solicitud['visible'] ?? 0) !== 1) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'La solicitud ya no est? pendiente.',
            ]);
        }

        if (trim((string) ($solicitud['no_proveedor'] ?? '')) === '' || trim((string) ($solicitud['establecimiento_no_proveedor'] ?? '')) === '' || (string) ($solicitud['no_proveedor'] ?? '') !== (string) ($solicitud['establecimiento_no_proveedor'] ?? '')) {
            $db->transRollback();
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'El establecimiento no pertenece al proveedor autenticado.',
            ]);
        }

        $tipoInfo = $this->resolveSolicitudUsuarioOperativoTipo((int) ($solicitud['id_tipo'] ?? 0));
        if ((int) ($tipoInfo['id_perfil_solicitado'] ?? 0) <= 0) {
            $db->transRollback();
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'No fue posible resolver el perfil solicitado.',
            ]);
        }

        $idPerfil = (int) $tipoInfo['id_perfil_solicitado'];
        $usuarioExistente = $db->table('usuario')
            ->select('id_usuario')
            ->where('usuario', $usuario)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($usuarioExistente)) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'El nombre de usuario ya existe.',
            ]);
        }

        $usuarioOperativo = $db->table('usuario')
            ->select('id_usuario')
            ->where('id_establecimiento', (int) ($solicitud['id_establecimiento'] ?? 0))
            ->where('id_perfil', $idPerfil)
            ->where('visible', 1)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($usuarioOperativo)) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe un usuario operativo activo para este establecimiento y perfil.',
            ]);
        }

        $fechaAhora = date('Y-m-d H:i:s');
        $insertData = [
            'id_proveedor' => (int) ($solicitud['id_proveedor'] ?? 0),
            'id_tipo_proveedor' => (int) ($solicitud['id_tipo_proveedor'] ?? 0),
            'id_establecimiento' => (int) ($solicitud['id_establecimiento'] ?? 0),
            'id_perfil' => $idPerfil,
            'nombre' => (string) ($solicitud['nombre'] ?? ''),
            'primer_apellido' => (string) ($solicitud['primer_apellido'] ?? ''),
            'segundo_apellido' => (string) ($solicitud['segundo_apellido'] ?? ''),
            'correo' => (string) ($solicitud['correo'] ?? ''),
            'usuario' => $usuario,
            'contrasenia' => password_hash($contrasenia, PASSWORD_DEFAULT),
            'tiene_alimentos' => 0,
            'tiene_hospedaje' => 0,
            'activo_qr' => 0,
            'visible' => 1,
            'id_nivel_cliente' => 0,
            'id_partida' => 0,
            'id_fic_perfil' => null,
            'id_ug_perfil' => null,
            'id_secul_perfil' => null,
            'id_secturi_perfil' => null,
            'id_estatus_hotel' => null,
            'id_establecimiento_hotel' => null,
            'id_tipo_habitacion' => null,
            'id_pais' => null,
            'id_clave' => null,
            'id_diciplina' => null,
            'id_estado' => null,
            'pax' => null,
            'anf_gto' => null,
            'monto_deposito' => null,
            'monto_deposito_reservado' => 0.00,
            'monto_deposito_operativo' => 0.00,
            'deposito_programado_estatus' => 'sin_programa',
            'qr' => null,
            'nip' => null,
            'folio' => null,
            'sub_folio' => null,
            'ruta_foto_relativa' => null,
            'fecha_check_in' => null,
            'fecha_check_out' => null,
            'fec_vigencia_desde' => null,
            'fec_vigencia_hasta' => null,
            'noche' => null,
            'tarifa_noche' => null,
            'tarifa_total' => null,
            'api_token' => null,
            'api_token_expira' => null,
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
        ];

        $insertOk = $db->table('usuario')->insert($insertData);
        if (!$insertOk) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible crear el usuario operativo.',
            ]);
        }

        $idUsuarioCreado = (int) $db->insertID();
        $updateOk = $db->table('solicitud_usuario')->update([
            'estatus' => 'aprobada',
            'comentario_ti' => null,
            'id_usuario_creado' => $idUsuarioCreado,
            'fecha_respuesta' => $fechaAhora,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
        ], [
            'id_solicitud_usuario' => $idSolicitud,
        ]);

        if (!$updateOk || $db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible finalizar la aprobación.',
            ]);
        }

        $db->transCommit();

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud aprobada correctamente.',
            'data' => [
                'id_usuario' => $idUsuarioCreado,
                'id_solicitud_usuario' => $idSolicitud,
                'id_perfil' => $idPerfil,
                'tipo_usuario_solicitado' => $tipoInfo['tipo_usuario_solicitado'],
            ],
        ]);
    }

    public function rechazarSolicitudUsuarioOperativo()
    {
        $session = \Config\Services::session();
        $tiUsuario = $this->resolveTiMasterUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para rechazar solicitudes.',
            ]);
        }

        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        $motivo = trim((string) ($this->request->getPost('comentario_ti') ?? ''));

        if ($idSolicitud <= 0 || $motivo === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Debes capturar el motivo del rechazo.',
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $solicitud = $db->query(
            'SELECT id_solicitud_usuario, estatus, visible
             FROM solicitud_usuario
             WHERE id_solicitud_usuario = ?
             FOR UPDATE',
            [$idSolicitud]
        )->getRowArray();

        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente' || (int) ($solicitud['visible'] ?? 0) !== 1) {
            $db->transRollback();
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'La solicitud ya no est? pendiente.',
            ]);
        }

        $fechaAhora = date('Y-m-d H:i:s');
        $updateOk = $db->table('solicitud_usuario')->update([
            'estatus' => 'rechazada',
            'comentario_ti' => $motivo,
            'fecha_respuesta' => $fechaAhora,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
        ], [
            'id_solicitud_usuario' => $idSolicitud,
        ]);

        if (!$updateOk || $db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible rechazar la solicitud.',
            ]);
        }

        $db->transCommit();

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud rechazada correctamente.',
            'data' => [
                'id_solicitud_usuario' => $idSolicitud,
            ],
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

    public function activarQrDepositosProgramados()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($this->request->getPost('id_usuario') ?? $session->get('id_usuario') ?? 0);

        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Debes indicar el usuario a activar.',
            ]);
        }

        $service = new DepositosProgramadosService();
        $result = $service->activateQrAndApplyDeposits($idUsuario, (int) $session->get('id_usuario'));

        return $this->response->setStatusCode($result->error ? 422 : 200)->setJSON($result);
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
            'tabla' => 'vw_usuario',
            'where' => ['visible' => 1, 'id_establecimiento' => $idUsuario->data[0]->id_establecimiento]
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

    public function PerfilSecul()
    {
        return $this->renderPerfilSeculHub('admin');
    }

    public function PerfilSeculConsulta()
    {
        return $this->renderPerfilSeculHub('consulta');
    }

    public function PerfilUg()
    {
        return $this->renderPerfilUgHub('admin');
    }

    public function PerfilUgConsulta()
    {
        return $this->renderPerfilUgHub('consulta');
    }

    private function renderPerfilSeculHub(string $modo = 'admin')
    {
        return $this->renderPerfilCatalogoHub('secul', $modo);
    }

    private function renderPerfilUgHub(string $modo = 'admin')
    {
        return $this->renderPerfilCatalogoHub('ug', $modo);
    }

    private function renderPerfilCatalogoHub(string $grupo, string $modo = 'admin')
    {
        $session = ConfigServices::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $cfg = $this->getSolicitudCatalogoConfig($grupo);

        if (empty($cfg)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }
        if (empty($tiUsuario) && $esGrupo && $modo === 'consulta' && $rolGrupo === 1) {
            return redirect()->to(base_url('index.php/Inicio/' . ucfirst($grupo)));
        }
        if (empty($tiUsuario) && $esGrupo && $modo === 'admin' && in_array($rolGrupo, [2, 4], true)) {
            return redirect()->to(base_url('index.php/Inicio/' . ucfirst($grupo) . 'Consulta'));
        }

        $db = ConfigDatabase::connect();
        $perfiles = $db->table($cfg['catalog_table'])
            ->select($cfg['catalog_id'] . ', ' . $cfg['catalog_label'])
            ->where('visible', 1)
            ->orderBy($cfg['catalog_id'], 'ASC')
            ->get()
            ->getResultArray();

        $data = [];
        $data['scripts'] = ['principal', 'agregar', 'solicitudes_usuario_catalogo'];
        $data[$cfg['mode_key']] = $modo === 'consulta' ? 'consulta' : 'admin';
        $data['hubTitle'] = 'Perfil ' . $cfg['label'];
        $data['hubSubtitle'] = $modo === 'consulta'
            ? 'Vista de consulta para revisar solicitudes de folio y perfiles visibles del cat?logo ' . $cfg['label'] . '.'
            : 'Panel operativo para solicitar folios del cat?logo ' . $cfg['label'] . '.';
        $data[$cfg['can_create_key']] = $modo === 'admin' && (int) ($contextoUsuario['group_role'] ?? 0) === 1;
        $data[$cfg['perfil_options_key']] = array_map(static function (array $row) use ($cfg): array {
            return [
                'id_perfil' => (int) ($row[$cfg['catalog_id']] ?? 0),
                'dsc_perfil' => (string) ($row[$cfg['catalog_label']] ?? ''),
            ];
        }, $perfiles);
        $base = base_url('index.php/Inicio');
        $methodSuffix = ucfirst($grupo);
        $data[$cfg['list_url_key']] = $base . '/getSolicitudesUsuario' . $methodSuffix . 'Perfil';
        $data[$cfg['detail_url_key']] = $base . '/getSolicitudUsuario' . $methodSuffix . 'Perfil';
        $data[$cfg['save_url_key']] = $base . '/guardarSolicitudUsuario' . $methodSuffix . 'Perfil';
        $data[$cfg['cancel_url_key']] = $base . '/cancelarSolicitudUsuario' . $methodSuffix . 'Perfil';
        $data[$cfg['establecimiento_id_key']] = (int) ($session->get('id_establecimiento') ?? 0);
        $data['contentView'] = $cfg['view'];
        $this->_renderView($data);
    }

    public function getSolicitudesUsuarioSeculPerfil()
    {
        return $this->getSolicitudesUsuarioCatalogoPerfil('secul');
    }

    public function getSolicitudUsuarioSeculPerfil()
    {
        return $this->getSolicitudUsuarioCatalogoPerfil('secul');
    }

    public function guardarSolicitudUsuarioSeculPerfil()
    {
        return $this->guardarSolicitudUsuarioCatalogoPerfil('secul');
    }

    public function cancelarSolicitudUsuarioSeculPerfil()
    {
        return $this->cancelarSolicitudUsuarioCatalogoPerfil('secul');
    }

    public function getSolicitudesUsuarioUgPerfil()
    {
        return $this->getSolicitudesUsuarioCatalogoPerfil('ug');
    }

    public function getSolicitudUsuarioUgPerfil()
    {
        return $this->getSolicitudUsuarioCatalogoPerfil('ug');
    }

    public function guardarSolicitudUsuarioUgPerfil()
    {
        return $this->guardarSolicitudUsuarioCatalogoPerfil('ug');
    }

    public function cancelarSolicitudUsuarioUgPerfil()
    {
        return $this->cancelarSolicitudUsuarioCatalogoPerfil('ug');
    }

    private function getSolicitudesUsuarioCatalogoPerfil(string $grupo)
    {
        $session = ConfigServices::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'total' => 0, 'rows' => [], 'message' => 'Cat?logo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'total' => 0, 'rows' => [], 'message' => 'No tienes permisos para consultar solicitudes.']);
        }

        $db = ConfigDatabase::connect();
        $builder = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, c.' . $cfg['catalog_label'] . ' AS perfil_solicitado')
            ->join($cfg['catalog_table'] . ' c', 'c.' . $cfg['catalog_id'] . ' = su.id_perfil_solicitado', 'left')
            ->where('su.visible', 1)
            ->where('su.tipo_solicitud', $cfg['tipo_solicitud']);

        if (empty($tiUsuario)) {
            $builder->where('su.usu_reg', $idSesionUsuario);
        }

        $search = trim((string) ($this->request->getGet('search') ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('su.usuario', $search)
                ->orLike('su.nombre', $search)
                ->orLike('su.primer_apellido', $search)
                ->orLike('su.segundo_apellido', $search)
                ->orLike('su.correo', $search)
                ->orLike('su.estatus', $search)
                ->orLike('c.' . $cfg['catalog_label'], $search)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults();
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 10));
        $offset = max(0, (int) ($this->request->getGet('offset') ?? 0));
        $rows = $builder->orderBy('su.fec_reg', 'DESC')->limit($limit, $offset)->get()->getResultArray();

        return $this->response->setJSON([
            'ok' => true,
            'total' => $total,
            'rows' => array_map(function (array $row) use ($grupo): array {
                return $this->mapSolicitudUsuarioCatalogoPerfilRow($row, $grupo);
            }, $rows),
        ]);
    }

    private function getSolicitudUsuarioCatalogoPerfil(string $grupo)
    {
        $session = ConfigServices::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Cat?logo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para consultar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no v?lida.']);
        }

        $db = ConfigDatabase::connect();
        $row = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, c.' . $cfg['catalog_label'] . ' AS perfil_solicitado')
            ->join($cfg['catalog_table'] . ' c', 'c.' . $cfg['catalog_id'] . ' = su.id_perfil_solicitado', 'left')
            ->where('su.id_solicitud_usuario', $idSolicitud)
            ->where('su.visible', 1)
            ->where('su.tipo_solicitud', $cfg['tipo_solicitud'])
            ->where($tiUsuario ? '1=1' : 'su.usu_reg = ' . $db->escape($idSesionUsuario), null, false)
            ->get()
            ->getRowArray();

        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'No se encontr? la solicitud.']);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $this->mapSolicitudUsuarioCatalogoPerfilRow($row, $grupo)]);
    }

    private function guardarSolicitudUsuarioCatalogoPerfil(string $grupo)
    {
        $session = ConfigServices::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $usuario = '';

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Cat?logo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'Solo un administrador del cat?logo puede enviar solicitudes.']);
        }
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['ok' => false, 'message' => 'M?todo no permitido.']);
        }

        $idPerfilSolicitado = (int) ($this->request->getPost('id_perfil_solicitado') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        $primerApellido = trim((string) ($this->request->getPost('primer_apellido') ?? ''));
        $segundoApellido = trim((string) ($this->request->getPost('segundo_apellido') ?? ''));
        $correo = trim((string) ($this->request->getPost('correo') ?? ''));
        $observaciones = trim((string) ($this->request->getPost('observaciones') ?? ''));

        $nombre = function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre);
        $primerApellido = function_exists('mb_strtoupper') ? mb_strtoupper($primerApellido, 'UTF-8') : strtoupper($primerApellido);
        $segundoApellido = function_exists('mb_strtoupper') ? mb_strtoupper($segundoApellido, 'UTF-8') : strtoupper($segundoApellido);
        $correo = function_exists('mb_strtolower') ? mb_strtolower($correo, 'UTF-8') : strtolower($correo);

        if ($idPerfilSolicitado <= 0 || $nombre === '' || $primerApellido === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Completa los campos obligatorios.']);
        }

        $db = ConfigDatabase::connect();
        $perfilSolicitado = $db->table($cfg['catalog_table'])
            ->where($cfg['catalog_id'], $idPerfilSolicitado)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($perfilSolicitado)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'El perfil solicitado no existe.']);
        }

        $solicitudDuplicada = $db->table('solicitud_usuario')
            ->select('id_solicitud_usuario')
            ->where('visible', 1)
            ->where('estatus', 'pendiente')
            ->where('tipo_solicitud', $cfg['tipo_solicitud'])
            ->where('id_perfil_solicitado', $idPerfilSolicitado)
            ->where('LOWER(nombre) = LOWER(' . $db->escape($nombre) . ')', null, false)
            ->where('LOWER(primer_apellido) = LOWER(' . $db->escape($primerApellido) . ')', null, false)
            ->where('LOWER(IFNULL(segundo_apellido, "")) = LOWER(' . $db->escape($segundoApellido) . ')', null, false)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($solicitudDuplicada)) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Ya existe una solicitud pendiente para este perfil y este nombre.']);
        }

        $usuarioExistente = $db->table('usuario')
            ->select('id_usuario')
            ->where('visible', 1)
            ->where($cfg['usuario_group_field'], $idPerfilSolicitado)
            ->where('UPPER(nombre) = UPPER(' . $db->escape($nombre) . ')', null, false)
            ->where('UPPER(primer_apellido) = UPPER(' . $db->escape($primerApellido) . ')', null, false)
            ->where('UPPER(IFNULL(segundo_apellido, "")) = UPPER(' . $db->escape($segundoApellido) . ')', null, false)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($usuarioExistente)) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Ya existe un usuario activo con este nombre para el perfil solicitado.']);
        }

        $detalleSolicitud = [];
        $beneficiosKey = function_exists('mb_strtolower') ? mb_strtolower($beneficios, 'UTF-8') : strtolower($beneficios);
        $beneficiosLabel = [
            'ninguno' => 'Ninguno',
            'hospedaje' => 'Hospedaje',
            'alimentos' => 'Alimentos',
            'ambos' => 'Hospedaje y alimentos',
        ][$beneficiosKey] ?? 'Ninguno';
        $detalleSolicitud[] = 'Beneficios: ' . $beneficiosLabel;
        if (in_array($beneficiosKey, ['hospedaje', 'ambos'], true)) {
            $detalleSolicitud[] = 'Hospedaje: sí';
        }
        if (in_array($beneficiosKey, ['alimentos', 'ambos'], true)) {
            $detalleSolicitud[] = 'Alimentos: sí';
        }
        if ($observaciones !== '') {
            $detalleSolicitud[] = '';
            $detalleSolicitud[] = 'Observaciones:';
            $detalleSolicitud[] = $observaciones;
        }
        $comentarioSolicitud = !empty($detalleSolicitud) ? implode("\n", $detalleSolicitud) : null;
        $fechaAhora = date('Y-m-d H:i:s');
        $insertOk = $db->table('solicitud_usuario')->insert([
            'tipo_solicitud' => $cfg['tipo_solicitud'],
            'id_proveedor' => null,
            'id_establecimiento' => (int) ($session->get('id_establecimiento') ?? 0),
            'id_perfil_solicitado' => $idPerfilSolicitado,
            'usuario' => $usuario,
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido,
            'correo' => $correo,
            'estatus' => 'pendiente',
            'comentario_ti' => $comentarioSolicitud,
            'id_usuario_creado' => null,
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
            'visible' => 1,
        ]);

        if (!$insertOk) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'message' => 'No fue posible guardar la solicitud.']);
        }

        return $this->response->setJSON(['ok' => true, 'message' => 'Solicitud enviada correctamente.', 'data' => ['id_solicitud_usuario' => (int) $db->insertID()]]);
    }

    private function cancelarSolicitudUsuarioCatalogoPerfil(string $grupo)
    {
        $session = ConfigServices::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Cat?logo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para cancelar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no v?lida.']);
        }

        $db = ConfigDatabase::connect();
        $solicitud = $db->table('solicitud_usuario')
            ->where('id_solicitud_usuario', $idSolicitud)
            ->where('visible', 1)
            ->where('tipo_solicitud', $cfg['tipo_solicitud'])
            ->where('usu_reg', $idSesionUsuario)
            ->get()
            ->getRowArray();

        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente') {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Solo se pueden cancelar solicitudes pendientes.']);
        }

        $fechaAhora = date('Y-m-d H:i:s');
        $updateOk = $db->table('solicitud_usuario')->update([
            'estatus' => 'cancelada',
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
        ], ['id_solicitud_usuario' => $idSolicitud]);

        if (!$updateOk) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'message' => 'No fue posible cancelar la solicitud.']);
        }

        return $this->response->setJSON(['ok' => true, 'message' => 'Solicitud cancelada correctamente.']);
    }

    private function mapSolicitudUsuarioCatalogoPerfilRow(array $row, string $grupo): array
    {
        $nombreCompleto = trim(implode(' ', array_filter([
            trim((string) ($row['nombre'] ?? '')),
            trim((string) ($row['primer_apellido'] ?? '')),
            trim((string) ($row['segundo_apellido'] ?? '')),
        ])));

        return [
            'id_solicitud_usuario' => (int) ($row['id_solicitud_usuario'] ?? 0),
            'tipo_solicitud' => (string) ($row['tipo_solicitud'] ?? ''),
            'id_proveedor' => (int) ($row['id_proveedor'] ?? 0),
            'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
            'id_perfil_solicitado' => (int) ($row['id_perfil_solicitado'] ?? 0),
            'perfil_solicitado' => (string) ($row['perfil_solicitado'] ?? ''),
            'usuario' => (string) ($row['usuario'] ?? ''),
            'nombre' => (string) ($row['nombre'] ?? ''),
            'primer_apellido' => (string) ($row['primer_apellido'] ?? ''),
            'segundo_apellido' => (string) ($row['segundo_apellido'] ?? ''),
            'correo' => (string) ($row['correo'] ?? ''),
            'nombre_completo' => $nombreCompleto,
            'estatus' => (string) ($row['estatus'] ?? ''),
            'comentario_ti' => (string) ($row['comentario_ti'] ?? ''),
            'fec_reg' => (string) ($row['fec_reg'] ?? ''),
            'visible' => (int) ($row['visible'] ?? 0),
            'catalogo_grupo' => $grupo,
        ];
    }

    private function getSolicitudCatalogoConfig(string $grupo): array
    {
        $configs = [
            'secul' => [
                'label' => 'SECUL',
                'view' => 'secciones/vPerfilSecul',
                'mode_key' => 'perfilSeculMode',
                'can_create_key' => 'seculSolicitudPuedeCrear',
                'perfil_options_key' => 'seculSolicitudPerfilOptions',
                'list_url_key' => 'seculSolicitudListadoUrl',
                'detail_url_key' => 'seculSolicitudDetalleUrl',
                'save_url_key' => 'seculSolicitudGuardarUrl',
                'cancel_url_key' => 'seculSolicitudCancelarUrl',
                'establecimiento_id_key' => 'seculSolicitudEstablecimientoId',
                'catalog_table' => 'cat_secul',
                'catalog_id' => 'id_secul_perfil',
                'catalog_label' => 'des_perfil',
                'tipo_solicitud' => 'alta_usuario_secul',
                'usuario_group_field' => 'id_secul_perfil',
            ],
            'ug' => [
                'label' => 'UG',
                'view' => 'secciones/vPerfilUG',
                'mode_key' => 'perfilUgMode',
                'can_create_key' => 'ugSolicitudPuedeCrear',
                'perfil_options_key' => 'ugSolicitudPerfilOptions',
                'list_url_key' => 'ugSolicitudListadoUrl',
                'detail_url_key' => 'ugSolicitudDetalleUrl',
                'save_url_key' => 'ugSolicitudGuardarUrl',
                'cancel_url_key' => 'ugSolicitudCancelarUrl',
                'establecimiento_id_key' => 'ugSolicitudEstablecimientoId',
                'catalog_table' => 'cat_ug',
                'catalog_id' => 'id_ug_perfil',
                'catalog_label' => 'dsc_perfil',
                'tipo_solicitud' => 'alta_usuario_ug',
                'usuario_group_field' => 'id_ug_perfil',
            ],
        ];

        return $configs[$grupo] ?? [];
    }

}

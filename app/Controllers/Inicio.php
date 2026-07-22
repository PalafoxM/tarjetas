<?php namespace App\Controllers;
use CodeIgniter\Controller;
use App\Libraries\Curps;
use App\Libraries\DepositosProgramadosService;
use App\Libraries\Fechas;
use App\Libraries\Funciones;
use App\Libraries\UsuarioPerfilResolver;
use App\Models\Mglobal;
use Box\Spout\Common\Entity\Style\CellAlignment;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

use stdClass;
use CodeIgniter\API\ResponseTrait;
require_once FCPATH . '/mpdf/autoload.php';
require_once FCPATH . 'spout/src/Spout/Autoloader/autoload.php';
class Inicio extends BaseController {

    use ResponseTrait;
    private $defaultData = array(
        'title' => 'Turnos 2.0',
        'layout' => 'plantilla/lytDefault',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    );
    private $lastS3Error = '';
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

    private function isNotificationDbRetryableError(\Throwable $error): bool
    {
        $message = strtolower((string) $error->getMessage());
        $code = (int) $error->getCode();

        return in_array($code, [2006, 2013, 2055], true)
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection')
            || str_contains($message, 'error connecting to the database');
    }

    private function runNotificationDbWithRetry(callable $callback, string $context = '')
    {
        $attempts = 0;

        while ($attempts < 2) {
            $attempts++;
            $db = \Config\Database::connect(null, false);

            try {
                return $callback($db);
            } catch (\Throwable $error) {
                if ($attempts >= 2 || !$this->isNotificationDbRetryableError($error)) {
                    throw $error;
                }

                log_message(
                    'warning',
                    'Inicio.notificaciones: reintentando conexion para ' . $context . ' tras error: ' . $error->getMessage()
                );
            }
        }

        throw new \RuntimeException('No fue posible ejecutar la operacion de notificaciones.');
    }

    private function _renderView($data = array()) { 
        $session = \Config\Services::session();
        $Mglobal = new Mglobal;   

        $data = array_merge($this->defaultData, $data);
        if (!isset($data['scripts']) || !is_array($data['scripts'])) {
            $data['scripts'] = [];
        }
        if (!in_array('notificaciones', $data['scripts'], true)) {
            $data['scripts'][] = 'notificaciones';
        }
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
        } elseif (($contextoUsuario['active_group'] ?? '') === 'secturi' && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2], true)) {
            return $this->renderPerfilSecturiHub(((int) ($contextoUsuario['group_role'] ?? 0) === 1) ? 'admin' : 'consulta');
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
                $proveedorEstablecimientos = $this->resolveProviderEstablishments(\Config\Database::connect(), $proveedor->data[0] ?? (object) []);
                $rfc = $Mglobal->getTabla(['tabla' => "proveedor", "where" =>['visible' =>1, "no_proveedor" =>$proveedor->data[0]->no_proveedor]]);
              
                $data['rfc'] = (!empty($rfc->data) && isset($rfc->data))?$rfc->data[0]->rfc:'Sin RFC';
                $noEstablecimientos = ["tabla" => "usuario_establecimiento", "where" => ['visible' => 1, "id_usuario" =>$session->get('id_usuario')]];
                $e = $Mglobal->getTabla($noEstablecimientos);
                $data['establecimiento'] = (!empty($e->data) && isset($e->data))?count($e->data):'0';
                $data['proveedorEstablecimientos'] = array_values(array_map(static function ($item) {
                    return is_object($item) ? get_object_vars($item) : (array) $item;
                }, $proveedorEstablecimientos));
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
             
            if(!empty($datosProveedor->data[0]->id_tipo_proveedor) && $datosProveedor->data[0]->id_tipo_proveedor == 1){
               $vista = 'secciones/vProveedor';
            }else{
               $vista = 'secciones/vHospedaje';
            }
       // die( var_dump( $data['datosProveedor'] ) );
          
            
            
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
         //die( var_dump($data['datosCliente']));
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
            $idEstablecimiento = !empty($datosProveedor->data[0]->id_establecimiento)
                ? (int) $datosProveedor->data[0]->id_establecimiento
                : 0;
            if ($idEstablecimiento > 0) {
                $data['hospedajeEstablecimientoId'] = $idEstablecimiento;
            }
           /*  $tabla = ["tabla" => "vw_usuario", "where" => ['visible' => 1, 'id_establecimiento' => $idEstablecimiento ]];
            $cliente = $Mglobal->getTabla($tabla);
            $data['usuarioHotel'] = (!empty($cliente->data) && isset($cliente->data))?$cliente->data:[]; */
           // var_dump($cliente);
            //die(  );
            
           }

           // die('ok');
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

    public function Establecimiento($idEstablecimiento = null)
    {        
        $session = \Config\Services::session();
        $data        = array();
        $idEstablecimiento = (int) $idEstablecimiento;

        $establecimientos = $this->resolveSessionEstablecimientos($idEstablecimiento);
        if (!empty($establecimientos)) {
            $data['datosEstablecimiento'] = array_map(static function (array $row): object {
                return (object) $row;
            }, $establecimientos);
        }

        $vista = 'secciones/vEstablecimiento';
        
    
        $data['scripts'] = array('principal','agregar');
        $data['contentView'] = $vista;                
        $this->_renderView($data);
        
    }

    private function resolveSessionEstablecimientos(int $idEstablecimiento = 0): array
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('establecimiento e')
            ->select('
                e.id_establecimiento,
                e.dsc_establecimiento,
                e.id_tipo,
                cte.dsc_tipo,
                e.no_proveedor
            ')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->join('usuario u', 'u.id_usuario = ' . $idUsuario, 'left')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'left')
            ->join('usuario_establecimiento ue', 'ue.id_establecimiento = e.id_establecimiento AND ue.id_usuario = ' . $idUsuario . ' AND ue.visible = 1', 'left')
            ->where('e.visible', 1)
            ->groupStart()
                ->where('e.no_proveedor = p.no_proveedor', null, false)
                ->orWhere('e.no_proveedor', (string) $idUsuario)
                ->orWhere('ue.id_usuario IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy('e.dsc_establecimiento', 'ASC');

        if ($idEstablecimiento > 0) {
            $builder->where('e.id_establecimiento', $idEstablecimiento);
        }

        return $builder->get()->getResultArray();
    }

    public function ProveedorFormatos()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($contextoUsuario['is_recepcion_flow']) && empty($session->get('id_proveedor'))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = $this->buildProviderDashboardData((int) $session->get('id_usuario'));
        $data['scripts'] = ['principal', 'agregar'];
        $data['contextoUsuario'] = $contextoUsuario;
        $data['contentView'] = 'secciones/vProveedorFormatos';
        $this->_renderView($data);
    }

    private function resolveProveedorReporteConfig(array $establecimiento): array
    {
        $tipoDetectado = strtolower(trim((string) ($establecimiento['dsc_tipo'] ?? '')));
        $idTipo = (int) ($establecimiento['id_tipo'] ?? 0);

        if ($idTipo === 2 || ($tipoDetectado !== '' && (str_contains($tipoDetectado, 'hotel') || str_contains($tipoDetectado, 'recep')))) {
            return [
                'tipo' => 'hospedaje',
                'label' => 'reporte de hospedaje',
                'prefix' => 'ACTIVACIONESFIC/REPORTES/HOSPEDAJE',
            ];
        }

        return [
            'tipo' => 'ventas',
            'label' => 'reporte de ventas',
            'prefix' => 'ACTIVACIONESFIC/REPORTES/VENTAS',
        ];
    }

    public function enviarFacturaProveedor()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        $idEstablecimientoSesion = (int) ($session->get('id_establecimiento') ?? 0);
        if (empty($contextoUsuario['is_provider_flow']) && empty($contextoUsuario['is_recepcion_flow']) && empty($session->get('id_proveedor')) && $idEstablecimientoSesion <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'No tienes permisos para enviar facturas.',
            ]);
        }

        $idEstablecimiento = (int) ($this->request->getPost('id_establecimiento') ?? 0);
        if ($idEstablecimiento <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Selecciona un establecimiento valido.',
            ]);
        }

        $establecimientosPermitidos = [];
        if ($idEstablecimientoSesion > 0) {
            $establecimientosPermitidos[] = $idEstablecimientoSesion;
        }

        $dashboard = $this->buildProviderDashboardData((int) $session->get('id_usuario'));
        $establecimientosPermitidos = array_merge($establecimientosPermitidos, array_map(static function ($item): int {
            $row = is_object($item) ? get_object_vars($item) : (array) $item;
            return (int) ($row['id_establecimiento'] ?? 0);
        }, is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : []));

        if (!empty($contextoUsuario['is_recepcion_flow'])) {
            $establecimientosPermitidos = array_merge($establecimientosPermitidos, array_map(static function (array $row): int {
                return (int) ($row['id_establecimiento'] ?? 0);
            }, $this->resolveSessionEstablecimientos()));
        }

        $establecimientosPermitidos = array_values(array_unique(array_filter($establecimientosPermitidos)));

        if (!in_array($idEstablecimiento, $establecimientosPermitidos, true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'El establecimiento no pertenece a la sesion.',
            ]);
        }

        $xml = $this->request->getFile('xml');
        $pdf = $this->request->getFile('pdf');
        if (!$xml || !$xml->isValid() || !$pdf || !$pdf->isValid()) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Selecciona el XML y el PDF de la factura.',
            ]);
        }

        $xmlExtension = strtolower((string) $xml->getClientExtension());
        $pdfExtension = strtolower((string) $pdf->getClientExtension());
        if ($xmlExtension !== 'xml') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El archivo de encabezado debe ser XML.',
            ]);
        }

        if ($pdfExtension !== 'pdf') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El formato PT debe ser PDF.',
            ]);
        }

        if ($xml->getSize() > 10 * 1024 * 1024 || $pdf->getSize() > 10 * 1024 * 1024) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Cada archivo debe pesar maximo 10 MB.',
            ]);
        }

        $tmpDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'facturas';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No se puede escribir el archivo temporal.',
            ]);
        }

        $timestamp = date('YmdHis');
        $suffix = bin2hex(random_bytes(4));
        $xmlName = 'factura_' . $idEstablecimiento . '_' . $timestamp . '_' . $suffix . '.xml';
        $pdfName = 'factura_' . $idEstablecimiento . '_' . $timestamp . '_' . $suffix . '.pdf';
        $xml->move($tmpDir, $xmlName, true);
        $pdf->move($tmpDir, $pdfName, true);

        $xmlPath = $tmpDir . DIRECTORY_SEPARATOR . $xmlName;
        $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . $pdfName;
        $prefix = 'ACTIVACIONESFIC/FACTURAS';
        $xmlUrl = $this->uploadFileToS3($xmlPath, $prefix . '/' . $xmlName, 'application/xml');
        $pdfUrl = $this->uploadFileToS3($pdfPath, $prefix . '/' . $pdfName, 'application/pdf');
        $facturaGuardadaLocalmente = false;

        if (($xmlUrl === null || $pdfUrl === null) && ENVIRONMENT !== 'production') {
            $xmlLocalUrl = $this->persistFacturaLocalFile($xmlPath, $xmlName);
            $pdfLocalUrl = $this->persistFacturaLocalFile($pdfPath, $pdfName);
            if ($xmlLocalUrl !== '' && $pdfLocalUrl !== '') {
                $xmlUrl = $xmlLocalUrl;
                $pdfUrl = $pdfLocalUrl;
                $facturaGuardadaLocalmente = true;
                log_message('warning', 'Inicio.enviarFacturaProveedor: usando fallback local para factura. ' . $this->lastS3Error);
            }
        }

        @unlink($xmlPath);
        @unlink($pdfPath);

        if ($xmlUrl === null || $pdfUrl === null) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No fue posible subir la factura a S3.' . ($this->lastS3Error !== '' ? ' Detalle: ' . $this->lastS3Error : ''),
            ]);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('facturas')) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No existe la tabla facturas.',
            ]);
        }

        $guardado = $db->table('facturas')->insert([
            'xml' => $xmlUrl,
            'pdf' => $pdfUrl,
            'id_estableciemiento' => $idEstablecimiento,
            'id_estatus' => 1,
            'fec_reg' => date('Y-m-d H:i:s'),
            'usu_reg' => (int) ($session->get('id_usuario') ?? 0),
            'visible' => 1,
        ]);

        if (!$guardado) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'Los archivos se almacenaron, pero no se pudo guardar la factura.',
            ]);
        }

        return $this->response->setJSON([
            'error' => false,
            'respuesta' => $facturaGuardadaLocalmente
                ? 'Factura enviada correctamente en almacenamiento local de pruebas.'
                : 'Factura enviada correctamente.',
            'id_factura' => (int) $db->insertID(),
            'xml' => $xmlUrl,
            'pdf' => $pdfUrl,
        ]);
    }

    public function subirReporteProveedor()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($contextoUsuario['is_recepcion_flow']) && empty($session->get('id_proveedor'))) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'No tienes permisos para subir reportes.',
            ]);
        }

        $idEstablecimiento = (int) ($this->request->getPost('id_establecimiento') ?? 0);
        if ($idEstablecimiento <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Selecciona un establecimiento valido.',
            ]);
        }

        $dashboard = $this->buildProviderDashboardData((int) $session->get('id_usuario'));
        $establecimiento = [];
        foreach (is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : [] as $item) {
            $row = is_object($item) ? get_object_vars($item) : (array) $item;
            if ((int) ($row['id_establecimiento'] ?? 0) === $idEstablecimiento) {
                $establecimiento = $row;
                break;
            }
        }

        if (empty($establecimiento)) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'El establecimiento no pertenece al proveedor en sesion.',
            ]);
        }

        $configReporte = $this->resolveProveedorReporteConfig($establecimiento);

        $reporte = $this->request->getFile('reporte');
        if (!$reporte || !$reporte->isValid()) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Selecciona un PDF valido para el reporte.',
            ]);
        }

        $reporteExtension = strtolower((string) $reporte->getClientExtension());
        if ($reporteExtension !== 'pdf') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El reporte debe ser PDF.',
            ]);
        }

        if ($reporte->getSize() > 20 * 1024 * 1024) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El reporte debe pesar maximo 20 MB.',
            ]);
        }

        $tmpDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'reportes_proveedor';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No se puede escribir el archivo temporal del reporte.',
            ]);
        }

        $timestamp = date('YmdHis');
        $suffix = bin2hex(random_bytes(4));
        $tipoReporte = (string) ($configReporte['tipo'] ?? 'ventas');
        $fileName = 'reporte_' . $tipoReporte . '_' . $idEstablecimiento . '_' . $timestamp . '_' . $suffix . '.pdf';
        $reporte->move($tmpDir, $fileName, true);

        $localPath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
        $objectKey = rtrim((string) ($configReporte['prefix'] ?? 'ACTIVAVIONESFIC/REPORTES'), '/') . '/' . $fileName;
        $url = $this->uploadFileToS3($localPath, $objectKey, 'application/pdf');
        @unlink($localPath);

        if ($url === null) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No fue posible subir el reporte a S3.' . ($this->lastS3Error !== '' ? ' Detalle: ' . $this->lastS3Error : ''),
            ]);
        }

        return $this->response->setJSON([
            'error' => false,
            'respuesta' => 'Reporte de ' . ($configReporte['label'] ?? 'ventas') . ' subido correctamente.',
            'url' => $url,
            'tipo_reporte' => $tipoReporte,
            'id_establecimiento' => $idEstablecimiento,
        ]);
    }

    public function ProveedorEstablecimiento($idEstablecimiento = null)
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($session->get('id_proveedor'))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $idUsuario = (int) $session->get('id_usuario');
        $idEstablecimiento = (int) $idEstablecimiento;
        if ($idUsuario <= 0 || $idEstablecimiento <= 0) {
            return redirect()->to(base_url('index.php/Inicio/ProveedorFormatos'));
        }

        $dashboard = $this->buildProviderDashboardData($idUsuario);
        $data = $this->filterProviderDashboardByEstablecimiento($dashboard, $idEstablecimiento);
        if (empty($data['proveedorEstablecimientos'])) {
            return redirect()->to(base_url('index.php/Inicio/ProveedorFormatos'));
        }

        $data['scripts'] = ['principal', 'agregar'];
        $data['contextoUsuario'] = $contextoUsuario;
        $data['idEstablecimientoActual'] = $idEstablecimiento;
        $data['contentView'] = 'secciones/vProveedor';
        $this->_renderView($data);
    }

    public function pdfProveedorEncabezadoFactura($idEstablecimiento = null)
    {
        return $this->renderProveedorFormatoPdf('encabezado_factura', (int) $idEstablecimiento);
    }

    public function pdfProveedorFormatoPT($idEstablecimiento = null)
    {
        return $this->renderProveedorFormatoPdf('formato_pt', (int) $idEstablecimiento);
    }

    public function pdfProveedorLiberacionPago($idEstablecimiento = null)
    {
        return $this->renderProveedorFormatoPdf('liberacion_pago', (int) $idEstablecimiento);
    }

    public function exportarReporteVentasProveedorXlsx()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($contextoUsuario['is_recepcion_flow']) && empty($session->get('id_proveedor'))) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar el reporte de ventas.');
        }

        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(401)->setBody('Sesión inválida.');
        }

        $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $dashboard = $this->buildProviderDashboardData($idUsuario);
        if ($idEstablecimiento > 0) {
            $dashboard = $this->filterProviderDashboardByEstablecimiento($dashboard, $idEstablecimiento);
        }

        $rows = $this->buildReporteVentasProveedorRows($dashboard);
        $filename = 'reporte_consumos_facturados_' . ($idEstablecimiento > 0 ? $idEstablecimiento : 'general') . '.xlsx';

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser($filename);

        $titleStyle = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(11)
            ->setCellAlignment(CellAlignment::CENTER)
            ->build();
        $headerStyle = (new StyleBuilder())
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('7F7F7F')
            ->setCellAlignment(CellAlignment::CENTER)
            ->build();
        $totalStyle = (new StyleBuilder())
            ->setFontBold()
            ->build();

        $writer->addRow(WriterEntityFactory::createRowFromArray(['SECRETARíA DE TURISMO E IDENTIDAD', '', '', '', '', ''], $titleStyle));
        $writer->addRow(WriterEntityFactory::createRowFromArray(['53 FESTIVAL INTERNACIONAL CERVANTINO', '', '', '', '', ''], $titleStyle));
        $writer->addRow(WriterEntityFactory::createRowFromArray(['REPORTE DE CONSUMOS FACTURADOS', '', '', '', '', ''], $titleStyle));
        $writer->addRow(WriterEntityFactory::createRowFromArray([$this->buildReporteVentasPeriodoLabel($rows), '', '', '', '', ''], $titleStyle));
        $writer->addRow(WriterEntityFactory::createRowFromArray(['', '', '', '', '', '']));
        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'Orden Pago',
            'Fecha',
            'Restaurante',
            'Partida',
            'Item',
            'Transaccion',
            'Importe',
        ], $headerStyle));

        $rowsByOrdenPago = [];
        foreach ($rows as $row) {
            $ordenPago = $this->resolveReporteVentasOrdenPago($row);
            $rowsByOrdenPago[$ordenPago][] = $row;
        }

        ksort($rowsByOrdenPago, SORT_NATURAL);

        if (empty($rowsByOrdenPago)) {
            $writer->addRow(WriterEntityFactory::createRowFromArray(['Sin consumos facturados', '', '', '', '', '']));
        }

        foreach ($rowsByOrdenPago as $ordenPago => $ordenRows) {
            usort($ordenRows, static function ($a, $b) {
                $fechaA = strtotime((string) ($a['fec_reg'] ?? $a['fecha_respuesta'] ?? '')) ?: 0;
                $fechaB = strtotime((string) ($b['fec_reg'] ?? $b['fecha_respuesta'] ?? '')) ?: 0;

                return $fechaA <=> $fechaB;
            });

            $totalOrden = 0;
            foreach ($ordenRows as $row) {
                $importe = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
                $totalOrden += $importe;

                $writer->addRow(WriterEntityFactory::createRowFromArray([
                    $ordenPago,
                    $this->formatReporteVentasFecha($row['fec_reg'] ?? $row['fecha_respuesta'] ?? ''),
                    (string) ($row['dsc_establecimiento'] ?? ''),
                    $this->resolveReporteVentasPartida($row),
                    'Consumo',
                    (string) ($row['id_solicitud_pago'] ?? ''),
                    '$ ' . number_format($importe, 2, '.', ','),
                ]));
            }

            $writer->addRow(WriterEntityFactory::createRowFromArray([
                'Total de orden de pago ' . $ordenPago,
                '',
                '',
                '',
                '',
                '$ ' . number_format($totalOrden, 2, '.', ','),
            ], $totalStyle));
        }

        $writer->close();
        exit;
    }

    public function exportarReporteVentasProveedorPdf()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($contextoUsuario['is_recepcion_flow']) && empty($session->get('id_proveedor'))) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar el reporte de ventas.');
        }

        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(401)->setBody('SesiÃƒÆ’Ã‚Â³n invÃƒÆ’Ã‚Â¡lida.');
        }

        $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $dashboard = $this->buildProviderDashboardData($idUsuario);
        if ($idEstablecimiento > 0) {
            $dashboard = $this->filterProviderDashboardByEstablecimiento($dashboard, $idEstablecimiento);
        }

        $rows = $this->buildReporteVentasProveedorRows($dashboard);
        $periodoLabel = $this->buildReporteVentasPeriodoLabel($rows);
        $filename = 'reporte_consumos_facturados_' . ($idEstablecimiento > 0 ? $idEstablecimiento : 'general') . '.pdf';

        $tempDir = WRITEPATH . 'mpdf-temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'Letter',
                'orientation' => 'L',
                'tempDir' => $tempDir,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 12,
                'margin_bottom' => 12,
            ]);

            $mpdf->SetTitle('Reporte de consumos facturados');
            $mpdf->WriteHTML($this->buildReporteVentasProveedorPdfHtml($rows, $periodoLabel));
            $mpdf->Output($filename, 'I');
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF de reporte de ventas proveedor: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el PDF solicitado.');
        }

        exit;
    }

    private function resolveReporteVentasOrdenPago(array $row): string
    {
        $folioSolicitud = trim((string) ($row['folio_solicitud'] ?? ''));
        if ($folioSolicitud !== '') {
            return $folioSolicitud;
        }

        $idSolicitudPago = trim((string) ($row['id_solicitud_pago'] ?? ''));

        return $idSolicitudPago !== '' ? $idSolicitudPago : 'Sin orden';
    }

    private function resolveReporteVentasPartida(array $row): string
    {
        $partida = trim((string) ($row['partida_usuario'] ?? ''));
        if ($partida !== '') {
            return $partida;
        }

        $idPartida = (int) ($row['id_partida_usuario'] ?? 0);
        if ($idPartida > 0) {
            $mapaPartidas = [
                1 => '2210',
                2 => '3390A',
                3 => '3390B'
            ];
            return $mapaPartidas[$idPartida] ?? 'Sin partida';
        }

        return 'Sin partida';
    }

    private function formatReportePartidaPublicLabel(string $partida): string
    {
        $partida = trim($partida);
        return in_array(strtoupper($partida), ['3390A', '3390B'], true) ? '3390' : $partida;
    }

    private function buildReporteVentasProveedorRows(array $dashboard): array
    {
        return array_values(is_array($dashboard['proveedorPagos'] ?? null) ? $dashboard['proveedorPagos'] : []);
    }

    private function buildReporteVentasProveedorExportPayload(): ?array
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (
            empty($contextoUsuario['is_provider_flow'])
            && empty($contextoUsuario['is_recepcion_flow'])
            && empty($contextoUsuario['can_access_secturi_dashboard'])
            && empty($session->get('id_proveedor'))
        ) {
            return null;
        }

        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return null;
        }

        $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $dashboard = $this->buildProviderDashboardData($idUsuario);
        if ($idEstablecimiento > 0) {
            $dashboard = $this->filterProviderDashboardByEstablecimiento($dashboard, $idEstablecimiento);
        }

        return [
            'dashboard' => $dashboard,
            'rows' => $this->buildReporteVentasProveedorRows($dashboard),
            'id_establecimiento' => $idEstablecimiento,
        ];
    }

    private function resolveReporteVentasLayout(array $dashboard, int $idEstablecimiento): array
    {
        $layout = [
            'slug' => 'general',
            'titulo' => 'Reporte de consumos facturados',
            'subtitulo' => 'Proveedor',
            'etiqueta_establecimiento' => 'Restaurante / Hotel',
            'accent' => '#4b5563',
            'accent_soft' => '#e5e7eb',
        ];

        $establecimientos = array_values(array_map(
            static function ($item): array {
                return is_object($item) ? get_object_vars($item) : (array) $item;
            },
            is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : []
        ));
        $tipoDetectado = '';
        $nombreDetectado = '';

        foreach ($establecimientos as $establecimiento) {
            if ((int) ($establecimiento['id_establecimiento'] ?? 0) !== $idEstablecimiento) {
                continue;
            }

            $tipoDetectado = strtolower(trim((string) ($establecimiento['dsc_tipo'] ?? '')));
            $nombreDetectado = trim((string) ($establecimiento['dsc_establecimiento'] ?? ''));
            break;
        }

        if ($tipoDetectado === '' && count($establecimientos) === 1) {
            $unico = $establecimientos[0];
            $tipoDetectado = strtolower(trim((string) ($unico['dsc_tipo'] ?? '')));
            $nombreDetectado = trim((string) ($unico['dsc_establecimiento'] ?? ''));
        }

        if ($tipoDetectado !== '' && (str_contains($tipoDetectado, 'hotel') || str_contains($tipoDetectado, 'recep'))) {
            $layout['slug'] = 'hotel';
            $layout['titulo'] = 'Reporte de hoteles';
            $layout['subtitulo'] = $nombreDetectado !== '' ? $nombreDetectado : 'Recepción';
            $layout['etiqueta_establecimiento'] = 'Hotel';
            $layout['accent'] = '#1d4ed8';
            $layout['accent_soft'] = '#dbeafe';
            return $layout;
        }

        if ($tipoDetectado !== '' && (str_contains($tipoDetectado, 'rest') || str_contains($tipoDetectado, 'comida') || str_contains($tipoDetectado, 'alimento'))) {
            $layout['slug'] = 'restaurante';
            $layout['titulo'] = 'Reporte de restaurantes';
            $layout['subtitulo'] = $nombreDetectado !== '' ? $nombreDetectado : 'Cobros';
            $layout['etiqueta_establecimiento'] = 'Restaurante';
            $layout['accent'] = '#0f766e';
            $layout['accent_soft'] = '#ccfbf1';
            return $layout;
        }

        if ($nombreDetectado !== '') {
            $layout['subtitulo'] = $nombreDetectado;
        }

        return $layout;
    }

    private function buildReporteVentasProveedorPdfHtml(array $rows, string $periodoLabel, array $layout = []): string
    {
        $layout = is_array($layout) ? $layout : [];
        $titulo = (string) ($layout['titulo'] ?? 'Reporte de consumos facturados');
        $subtitulo = (string) ($layout['subtitulo'] ?? 'Proveedor');
        $etiquetaEstablecimiento = (string) ($layout['etiqueta_establecimiento'] ?? 'Restaurante / Hotel');
        $partida = $this->formatReportePartidaPublicLabel((string) ($layout['partida'] ?? ''));

        $rowsByOrdenPago = [];
        foreach ($rows as $row) {
            $ordenPago = $this->resolveReporteVentasOrdenPago($row);
            $rowsByOrdenPago[$ordenPago][] = $row;
        }

        ksort($rowsByOrdenPago, SORT_NATURAL);

        $flattenedRows = [];
        $totalImporte = 0.0;
        foreach ($rowsByOrdenPago as $ordenPago => $ordenRows) {
            usort($ordenRows, static function ($a, $b) {
                $fechaA = strtotime((string) ($a['fec_reg'] ?? $a['fecha_respuesta'] ?? '')) ?: 0;
                $fechaB = strtotime((string) ($b['fec_reg'] ?? $b['fecha_respuesta'] ?? '')) ?: 0;
                return $fechaA <=> $fechaB;
            });
            foreach ($ordenRows as $row) {
                $flattenedRows[] = $row;
                $totalImporte += (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
            }
        }

        $summaryHtml = '<table class="summary">';
        $summaryHtml .= '<tr>';
        $summaryHtml .= '<td class="label">Total registros</td>';
        $summaryHtml .= '<td class="value">' . count($flattenedRows) . '</td>';
        $summaryHtml .= '<td class="label">Total importe</td>';
        $summaryHtml .= '<td class="value money">$' . number_format($totalImporte, 2) . '</td>';
        $summaryHtml .= '</tr>';
        if ($partida !== '') {
            $summaryHtml .= '<tr>';
            $summaryHtml .= '<td class="label">Partida</td>';
            $summaryHtml .= '<td class="value" colspan="3">' . htmlspecialchars($partida, ENT_QUOTES, 'UTF-8') . '</td>';
            $summaryHtml .= '</tr>';
        }
        $summaryHtml .= '</table>';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; padding: 15px 10px; }
                .header { border-bottom: 2px solid #0f766e; padding-bottom: 15px; margin-bottom: 18px; text-align: center; }
                .title-main { font-size: 22px; font-weight: bold; color: #000000; }
                .title-sub { font-size: 18px; font-weight: bold; color: #000000; margin-top: 2px; }
                .subtitle { font-size: 14px; color: #475569; margin-top: 6px; }
                .subtitle-bold { font-size: 14px; font-weight: bold; color: #475569; margin-top: 6px; }
                .period { text-align: center; font-size: 12pt; margin-top: 14px; margin-bottom: 0; color: #475569; }

                .summary { width: 100%; border-collapse: collapse; margin: 14px 0 14px; }
                .summary td { border: 1px solid #d1d5db; padding: 8px 12px; font-size: 11pt; }
                .summary .label { background: #f3f4f6; font-weight: bold; color: #111827; width: 20%; }
                .summary .value { width: 30%; font-size: 11pt; }

                .table-container { margin-top: 14px; }
                table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
                th, td { border: 1px solid #9ca3af; padding: 4px 6px; vertical-align: top; }
                th { background: #0f172a; color: #ffffff; text-align: left; font-weight: bold; }
                .money { text-align: right; font-weight: bold; }
                .empty { border: 1px solid #d1d5db; background: #f9fafb; padding: 16px; text-align: center; }
                
                table th:nth-child(1) { width: 28%; }
                table th:nth-child(2) { width: 10%; }
                table th:nth-child(3) { width: 22%; }
                table th:nth-child(4) { width: 10%; }
                table th:nth-child(5) { width: 10%; }
                table th:nth-child(6) { width: 20%; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title-main">SECRETARíA DE TURISMO E IDENTIDAD</div>
                <div class="title-sub">54 FESTIVAL INTERNACIONAL CERVANTINO</div>
                <div class="title-sub">' . htmlspecialchars(strtoupper($titulo), ENT_QUOTES, 'UTF-8') . '</div>
                <div class="subtitle">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</div>
                <div class="subtitle-bold">' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</div>
                <div class="period">' . htmlspecialchars($periodoLabel, ENT_QUOTES, 'UTF-8') . '</div>
            </div>

            ' . $summaryHtml . '

            <div class="table-container">';

        if (empty($flattenedRows)) {
            $html .= '<div class="empty">Sin consumos facturados</div>';
        } else {
            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>Orden pago</th>
                            <th>Fecha</th>
                            <th>' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</th>
                            <th>ítem</th>
                            <th>Transacción</th>
                            <th>Importe</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($flattenedRows as $row) {
                $importe = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
                $ordenPago = $this->resolveReporteVentasOrdenPago($row);
                $fecha = $this->formatReporteVentasFecha($row['fec_reg'] ?? $row['fecha_respuesta'] ?? '');
                $establecimiento = (string) ($row['dsc_establecimiento'] ?? '');
                $transaccion = (string) ($row['id_solicitud_pago'] ?? '');

                $html .= '<tr>
                    <td>' . htmlspecialchars($ordenPago, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($establecimiento, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>Consumo</td>
                    <td>' . htmlspecialchars($transaccion, ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="money">$' . number_format($importe, 2) . '</td>
                </tr>';
            }

            $html .= '
                    </tbody>
                </table>';
        }

        $html .= '
            </div>
        </body>
        </html>';

        return $html;
    }

    private function buildReporteVentasPeriodoLabel(array $rows): string
    {
        $timestamps = [];
        foreach ($rows as $row) {
            $fecha = trim((string) ($row['fec_reg'] ?? $row['fecha_respuesta'] ?? ''));
            $timestamp = $fecha !== '' ? strtotime($fecha) : false;
            if ($timestamp !== false) {
                $timestamps[] = $timestamp;
            }
        }

        if (empty($timestamps)) {
            return 'Periodo sin movimientos';
        }

        return 'Periodo del ' . date('d/m/Y', min($timestamps)) . ' al ' . date('d/m/Y', max($timestamps));
    }

    private function formatReporteVentasFecha($fecha): string
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return '';
        }

        $timestamp = strtotime($fecha);

        return $timestamp !== false ? date('d/m/Y', $timestamp) : $fecha;
    }

    public function exportarReporteVentasProveedorPdfFormato()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(401)->setBody('SesiÃƒÆ’Ã‚Â³n invÃƒÆ’Ã‚Â¡lida.');
        }

        $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $dashboard = $this->buildProviderDashboardData($idUsuario);
        if ($idEstablecimiento > 0) {
            $dashboard = $this->filterProviderDashboardByEstablecimiento($dashboard, $idEstablecimiento);
        }

        $rows = $this->buildReporteVentasProveedorRows($dashboard);
        
        if (empty($rows)) {
            return $this->response->setStatusCode(404)->setBody('No hay datos para generar el reporte.');
        }

        $partidasPermitidas = ['2210', '3390B'];
        $resultados = [];

        foreach ($partidasPermitidas as $partida) {
            $rowsFiltrados = array_filter($rows, function($row) use ($partida) {
                $partidaRow = $this->resolveReporteVentasPartida($row);
                return $partidaRow === $partida;
            });

            if (empty($rowsFiltrados)) {
                continue;
            }

            $dashboardFiltrado = $dashboard;
            $dashboardFiltrado['proveedorPagos'] = array_values($rowsFiltrados);
            
            $periodoLabel = $this->buildReporteVentasPeriodoLabel($rowsFiltrados);
            $layout = $this->resolveReporteVentasLayout($dashboardFiltrado, (int) $idEstablecimiento);
            $layout['partida'] = $partida;
            
            $filename = 'reporte_consumos_facturados_' . $partida . '_' . ($idEstablecimiento > 0 ? $idEstablecimiento : 'general') . '.pdf';

            $tempDir = WRITEPATH . 'mpdf-temp';
            if (!is_dir($tempDir)) {
                @mkdir($tempDir, 0775, true);
            }

            try {
                $mpdf = new \Mpdf\Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'Letter',
                    'orientation' => 'L',
                    'tempDir' => $tempDir,
                    'margin_left' => 10,
                    'margin_right' => 10,
                    'margin_top' => 12,
                    'margin_bottom' => 12,
                ]);

                $mpdf->SetTitle((string) ($layout['titulo'] ?? 'Reporte de consumos facturados'));
                $mpdf->WriteHTML($this->buildReporteVentasProveedorPdfHtmlHomologado($rowsFiltrados, $periodoLabel, $layout));
                
                $output = $mpdf->Output($filename, 'S');
                $resultados[] = [
                    'filename' => $filename,
                    'content' => $output,
                    'partida' => $partida
                ];
            } catch (\Throwable $e) {
                log_message('error', 'Error al generar PDF de reporte de ventas proveedor partida ' . $partida . ': ' . $e->getMessage());
                return $this->response->setStatusCode(500)->setBody('No fue posible generar el PDF para la partida ' . $partida . '.');
            }
        }

        if (empty($resultados)) {
            return $this->response->setStatusCode(404)->setBody('No hay datos para las partidas solicitadas.');
        }

        if (count($resultados) === 1) {
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $resultados[0]['filename'] . '"')
                ->setHeader('Content-Length', (string) strlen($resultados[0]['content']))
                ->setBody($resultados[0]['content']);
        }

        $zipFilename = 'reportes_consumos_facturados_' . date('Ymd_His') . '.zip';
        $zipPath = WRITEPATH . 'uploads/' . $zipFilename;
        
        if (!is_dir(WRITEPATH . 'uploads/')) {
            @mkdir(WRITEPATH . 'uploads/', 0775, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return $this->response->setStatusCode(500)->setBody('No fue posible crear el archivo ZIP.');
        }

        foreach ($resultados as $resultado) {
            $zip->addFromString($resultado['filename'], $resultado['content']);
        }
        $zip->close();

        $zipContent = file_get_contents($zipPath);
        @unlink($zipPath);

        return $this->response
            ->setHeader('Content-Type', 'application/zip')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $zipFilename . '"')
            ->setHeader('Content-Length', (string) strlen($zipContent))
            ->setBody($zipContent);
    }

    public function exportarReporteInstitucionalSaldosPdf(string $grupo = 'fic')
    {
        $payload = $this->buildReporteInstitucionalSaldosPayload($grupo);
        if ($payload === null) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar este reporte.');
        }

        $tempDir = WRITEPATH . 'mpdf-temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $filename = 'reporte_saldos_' . strtolower((string) ($payload['grupo'] ?? $grupo)) . '_' . date('Ymd_His') . '.pdf';

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'Letter',
                'orientation' => 'L',
                'tempDir' => $tempDir,
                'margin_left' => 8,
                'margin_right' => 8,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);

            $mpdf->SetTitle((string) ($payload['titulo'] ?? 'Reporte de saldos institucionales'));
            $mpdf->WriteHTML(view('pdfs/vPdfReporteInstitucionalSaldos', $payload));
            $mpdf->Output($filename, 'D');
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF de reporte institucional de saldos: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el PDF solicitado.');
        }

        exit;
    }

    private function buildReporteInstitucionalSaldosPayload(string $grupo): ?array
    {
        $grupo = strtolower(trim($grupo));
        $config = $this->getReporteInstitucionalGrupoConfig($grupo);
        if (empty($config)) {
            return null;
        }

        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        if (!$this->canExportReporteInstitucional($grupo, $contextoUsuario)) {
            return null;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuario u')
            ->select("
                u.id_usuario,
                u.usuario,
                u.nombre,
                u.primer_apellido,
                u.segundo_apellido,
                CONCAT_WS(' ', u.nombre, u.primer_apellido, u.segundo_apellido) AS nombre_completo,
                u.folio,
                u.folio_grupo,
                u.sub_folio,
                u.pax_total,
                u.pax_secuencia,
                u.{$config['field']} AS rol_grupo_id,
                u.id_nivel_cliente,
                COALESCE(cnc.dsc_nivel_cliente, '') AS tarifa_descripcion,
                COALESCE(cnc.monto_deposito, u.monto_deposito, 0) AS tarifa_diaria,
                u.fec_vigencia_desde,
                u.fec_vigencia_hasta,
                u.tiene_alimentos,
                u.tiene_hospedaje,
                u.monto_deposito_reservado,
                u.monto_deposito_operativo,
                u.visible
            ", false)
            ->join('cat_nivel_cliente cnc', 'cnc.id_nivel_cliente = u.id_nivel_cliente', 'left')
            ->where('u.visible', 1)
            ->where('u.' . $config['field'] . ' >', 0);

        $vigenciaDesdeFiltro = trim((string) ($this->request->getGet('vigencia_desde') ?? ''));
        $vigenciaHastaFiltro = trim((string) ($this->request->getGet('vigencia_hasta') ?? ''));
        if ($vigenciaDesdeFiltro !== '') {
            $builder->where('DATE(u.fec_vigencia_hasta) >= ' . $db->escape($vigenciaDesdeFiltro), null, false);
        }
        if ($vigenciaHastaFiltro !== '') {
            $builder->where('DATE(u.fec_vigencia_desde) <= ' . $db->escape($vigenciaHastaFiltro), null, false);
        }

        $rows = $builder
            ->orderBy('u.folio_grupo', 'ASC')
            ->orderBy('u.pax_secuencia', 'ASC')
            ->orderBy('u.id_usuario', 'ASC')
            ->get()
            ->getResultArray();

        $hoy = new \DateTimeImmutable('today', new \DateTimeZone('America/Mexico_City'));
        $resumen = [
            'total_usuarios' => 0,
            'total_reservado' => 0.00,
            'total_operativo' => 0.00,
            'total_pendiente' => 0.00,
        ];

        $roles = $config['roles'];
        $mappedRows = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $diasVigencia = $this->calculateReporteInstitucionalDiasVigencia(
                (string) ($row['fec_vigencia_desde'] ?? ''),
                (string) ($row['fec_vigencia_hasta'] ?? '')
            );
            $tarifaDiaria = round((float) ($row['tarifa_diaria'] ?? 0), 2);
            $tieneAlimentos = (int) ($row['tiene_alimentos'] ?? 0) === 1;
            $reservadoCalculado = $tieneAlimentos && $tarifaDiaria > 0 && $diasVigencia > 0
                ? round($tarifaDiaria * $diasVigencia, 2)
                : round((float) ($row['monto_deposito_reservado'] ?? 0), 2);
            $operativoActual = round((float) ($row['monto_deposito_operativo'] ?? 0), 2);
            $vigenciaVencida = $this->isReporteInstitucionalVigenciaVencida((string) ($row['fec_vigencia_hasta'] ?? ''), $hoy);
            $pendiente = $vigenciaVencida ? round(max(0.00, $operativoActual), 2) : 0.00;
            $rolId = (int) ($row['rol_grupo_id'] ?? 0);

            $mappedRows[] = [
                'id_usuario' => (int) ($row['id_usuario'] ?? 0),
                'folio' => trim((string) ($row['folio_grupo'] ?? $row['folio'] ?? '')),
                'sub_folio' => trim((string) ($row['sub_folio'] ?? '')),
                'usuario' => (string) ($row['usuario'] ?? ''),
                'nombre_completo' => trim((string) ($row['nombre_completo'] ?? '')),
                'perfil' => $roles[$rolId] ?? ('Perfil ' . $rolId),
                'tarifa_descripcion' => trim((string) ($row['tarifa_descripcion'] ?? '')),
                'tarifa_diaria' => $tarifaDiaria,
                'vigencia_desde' => (string) ($row['fec_vigencia_desde'] ?? ''),
                'vigencia_hasta' => (string) ($row['fec_vigencia_hasta'] ?? ''),
                'dias_vigencia' => $diasVigencia,
                'beneficios' => $this->buildReporteInstitucionalBeneficiosLabel($row),
                'monto_reservado' => $reservadoCalculado,
                'monto_operativo' => $operativoActual,
                'monto_pendiente' => $pendiente,
            ];

            $resumen['total_usuarios']++;
            $resumen['total_reservado'] = round($resumen['total_reservado'] + $reservadoCalculado, 2);
            $resumen['total_operativo'] = round($resumen['total_operativo'] + $operativoActual, 2);
            $resumen['total_pendiente'] = round($resumen['total_pendiente'] + $pendiente, 2);
        }

        return [
            'titulo' => 'Reporte de saldos institucionales',
            'grupo' => $config['label'],
            'generado_en' => date('Y-m-d H:i:s'),
            'periodo_label' => $this->buildReporteInstitucionalPeriodoLabel($vigenciaDesdeFiltro, $vigenciaHastaFiltro),
            'rows' => $mappedRows,
            'resumen' => $resumen,
        ];
    }

    private function getReporteInstitucionalGrupoConfig(string $grupo): array
    {
        $definitions = (new UsuarioPerfilResolver())->getDefinitions();
        $configs = [
            'fic' => ['label' => 'FIC', 'field' => 'id_fic_perfil', 'roles' => $definitions['fic']['roles'] ?? []],
            'ug' => ['label' => 'UG', 'field' => 'id_ug_perfil', 'roles' => $definitions['ug']['roles'] ?? []],
            'secul' => ['label' => 'SECUL', 'field' => 'id_secul_perfil', 'roles' => $definitions['secul']['roles'] ?? []],
        ];

        return $configs[$grupo] ?? [];
    }

    private function canExportReporteInstitucional(string $grupo, array $contextoUsuario): bool
    {
        if (!empty($contextoUsuario['is_ti_master'])) {
            return true;
        }

        return (string) ($contextoUsuario['active_group'] ?? '') === $grupo
            && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2, 4], true);
    }

    private function calculateReporteInstitucionalDiasVigencia(string $desde, string $hasta): int
    {
        $desde = trim($desde);
        $hasta = trim($hasta);
        if ($desde === '' || $hasta === '') {
            return 0;
        }

        try {
            $inicio = new \DateTimeImmutable(substr($desde, 0, 10));
            $fin = new \DateTimeImmutable(substr($hasta, 0, 10));
        } catch (\Throwable $e) {
            return 0;
        }

        if ($fin < $inicio) {
            return 0;
        }

        return ((int) $inicio->diff($fin)->format('%a')) + 1;
    }

    private function isReporteInstitucionalVigenciaVencida(string $hasta, \DateTimeImmutable $hoy): bool
    {
        $hasta = trim($hasta);
        if ($hasta === '') {
            return false;
        }

        try {
            $fin = new \DateTimeImmutable(substr($hasta, 0, 10), new \DateTimeZone('America/Mexico_City'));
        } catch (\Throwable $e) {
            return false;
        }

        return $fin < $hoy;
    }

    private function buildReporteInstitucionalBeneficiosLabel(array $row): string
    {
        $beneficios = [];
        if ((int) ($row['tiene_alimentos'] ?? 0) === 1) {
            $beneficios[] = 'Alimentos';
        }
        if ((int) ($row['tiene_hospedaje'] ?? 0) === 1) {
            $beneficios[] = 'Hospedaje';
        }

        return !empty($beneficios) ? implode(' / ', $beneficios) : 'Sin beneficios';
    }

    private function buildReporteInstitucionalPeriodoLabel(string $desde, string $hasta): string
    {
        $desde = trim($desde);
        $hasta = trim($hasta);
        if ($desde === '' && $hasta === '') {
            return 'Todos los usuarios visibles del grupo';
        }
        if ($desde !== '' && $hasta !== '') {
            return 'Vigencias entre ' . $this->formatReporteVentasFecha($desde) . ' y ' . $this->formatReporteVentasFecha($hasta);
        }
        if ($desde !== '') {
            return 'Vigencias desde ' . $this->formatReporteVentasFecha($desde);
        }

        return 'Vigencias hasta ' . $this->formatReporteVentasFecha($hasta);
    }

    public function exportarReporteHospedajePdf()
    {
        $payload = $this->buildHospedajeReporteExportPayload();
        if ($payload === null) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar el reporte de hospedaje.');
        }

        $tempDir = WRITEPATH . 'mpdf-temp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $idEstablecimiento = (int) ($payload['id_establecimiento'] ?? 0);
        $filename = 'reporte_hospedaje_' . ($idEstablecimiento > 0 ? $idEstablecimiento : 'general') . '.pdf';

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'Letter',
                'orientation' => 'L',
                'tempDir' => $tempDir,
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 12,
                'margin_bottom' => 12,
            ]);

            $mpdf->SetTitle('Reporte de hospedaje');
            $mpdf->WriteHTML(view('pdfs/vpdfReporteHospedaje', $payload));
            $salida = $this->request->getGet('download') ? 'D' : 'I';
            $mpdf->Output($filename, $salida);
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF de reporte de hospedaje: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el PDF de hospedaje solicitado.');
        }

        exit;
    }

    private function buildReporteVentasProveedorPdfHtmlHomologado(array $rows, string $periodoLabel, array $layout = []): string
    {
        $layout = is_array($layout) ? $layout : [];
        $titulo = (string) ($layout['titulo'] ?? 'Reporte de consumos facturados');
        $subtitulo = (string) ($layout['subtitulo'] ?? 'Proveedor');
        $etiquetaEstablecimiento = (string) ($layout['etiqueta_establecimiento'] ?? 'Restaurante / Hotel');
        $partida = $this->formatReportePartidaPublicLabel((string) ($layout['partida'] ?? ''));

        $rowsByOrdenPago = [];
        foreach ($rows as $row) {
            $ordenPago = $this->resolveReporteVentasOrdenPago($row);
            $rowsByOrdenPago[$ordenPago][] = $row;
        }

        ksort($rowsByOrdenPago, SORT_NATURAL);

        $flattenedRows = [];
        $totalImporte = 0.0;
        foreach ($rowsByOrdenPago as $ordenPago => $ordenRows) {
            usort($ordenRows, static function ($a, $b) {
                $fechaA = strtotime((string) ($a['fec_reg'] ?? $a['fecha_respuesta'] ?? '')) ?: 0;
                $fechaB = strtotime((string) ($b['fec_reg'] ?? $b['fecha_respuesta'] ?? '')) ?: 0;
                return $fechaA <=> $fechaB;
            });
            foreach ($ordenRows as $row) {
                $flattenedRows[] = $row;
                $totalImporte += (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
            }
        }

        $summaryHtml = '<table class="summary">';
        $summaryHtml .= '<tr>';
        $summaryHtml .= '<td class="label">Total registros</td>';
        $summaryHtml .= '<td class="value">' . count($flattenedRows) . '</td>';
        $summaryHtml .= '<td class="label">Total importe</td>';
        $summaryHtml .= '<td class="value money">$' . number_format($totalImporte, 2) . '</td>';
        $summaryHtml .= '</tr>';
        if ($partida !== '') {
            $summaryHtml .= '<tr>';
            $summaryHtml .= '<td class="label">Partida</td>';
            $summaryHtml .= '<td class="value" colspan="3">' . htmlspecialchars($partida, ENT_QUOTES, 'UTF-8') . '</td>';
            $summaryHtml .= '</tr>';
        }
        $summaryHtml .= '</table>';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; color: #172033; padding: 15px 10px; }
                .header { border-bottom: 2px solid #0f766e; padding-bottom: 15px; margin-bottom: 18px; text-align: center; }
                .title-main { font-size: 22px; font-weight: bold; color: #000000; }
                .title-sub { font-size: 18px; font-weight: bold; color: #000000; margin-top: 2px; }
                .subtitle { font-size: 14px; color: #475569; margin-top: 6px; }
                .subtitle-bold { font-size: 14px; font-weight: bold; color: #475569; margin-top: 6px; }
                .period { text-align: center; font-size: 12pt; margin-top: 14px; margin-bottom: 0; color: #475569; }

                .summary { width: 100%; border-collapse: collapse; margin: 14px 0 14px; }
                .summary td { border: 1px solid #d1d5db; padding: 8px 12px; font-size: 11pt; }
                .summary .label { background: #f3f4f6; font-weight: bold; color: #111827; width: 20%; }
                .summary .value { width: 30%; font-size: 11pt; }

                .table-container { margin-top: 14px; }
                table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
                th, td { border: 1px solid #9ca3af; padding: 4px 6px; vertical-align: top; }
                th { background: #0f172a; color: #ffffff; text-align: left; font-weight: bold; }
                .money { text-align: right; font-weight: bold; }
                .empty { border: 1px solid #d1d5db; background: #f9fafb; padding: 16px; text-align: center; }

                table th:nth-child(1) { width: 28%; }
                table th:nth-child(2) { width: 10%; }
                table th:nth-child(3) { width: 22%; }
                table th:nth-child(4) { width: 10%; }
                table th:nth-child(5) { width: 10%; }
                table th:nth-child(6) { width: 20%; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title-main">SECRETARíA DE TURISMO E IDENTIDAD</div>
                <div class="title-sub">54 FESTIVAL INTERNACIONAL CERVANTINO</div>
                <div class="title-sub">' . htmlspecialchars(strtoupper($titulo), ENT_QUOTES, 'UTF-8') . '</div>
                <div class="subtitle">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</div>
                <div class="subtitle-bold">' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</div>
                <div class="period">' . htmlspecialchars($periodoLabel, ENT_QUOTES, 'UTF-8') . '</div>
            </div>

            ' . $summaryHtml . '

            <div class="table-container">';

        if (empty($flattenedRows)) {
            $html .= '<div class="empty">Sin consumos facturados</div>';
        } else {
            $html .= '
                <table>
                    <thead>
                        <tr>
                            <th>Orden pago</th>
                            <th>Fecha</th>
                            <th>' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</th>
                            <th>ítem</th>
                            <th>Transacción</th>
                            <th>Importe</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($flattenedRows as $row) {
                $importe = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
                $ordenPago = $this->resolveReporteVentasOrdenPago($row);
                $fecha = $this->formatReporteVentasFecha($row['fec_reg'] ?? $row['fecha_respuesta'] ?? '');
                $establecimiento = (string) ($row['dsc_establecimiento'] ?? '');
                $transaccion = (string) ($row['id_solicitud_pago'] ?? '');

                $html .= '<tr>
                    <td>' . htmlspecialchars($ordenPago, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($establecimiento, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>Consumo</td>
                    <td>' . htmlspecialchars($transaccion, ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="money">$' . number_format($importe, 2) . '</td>
                </tr>';
            }

            $html .= '
                    </tbody>
                </table>';
        }

        $html .= '
            </div>
        </body>
        </html>';

        return $html;
    }

    private function buildHospedajeReporteExportPayload(): ?array
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        $puedeExportarHospedaje = !empty($session->get('id_proveedor'))
            || !empty($contextoUsuario['is_provider_flow'])
            || !empty($contextoUsuario['is_recepcion_flow'])
            || !empty($contextoUsuario['can_access_secturi_dashboard'])
            || !empty($contextoUsuario['is_ti_master']);

        if (!$puedeExportarHospedaje) {
            return null;
        }

        $Mglobal = new Mglobal();
        $usuario = $Mglobal->getTabla([
            'tabla' => 'vw_usuario',
            'where' => [
                'visible' => 1,
                'id_usuario' => (int) $session->get('id_usuario'),
            ],
        ]);

        if ($usuario->error || empty($usuario->data)) {
            return null;
        }

        $usuarioRow = (array) $usuario->data[0];
        $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        if ($idEstablecimiento <= 0) {
            $idEstablecimiento = (int) ($usuarioRow['id_establecimiento'] ?? 0);
        }
        if ($idEstablecimiento <= 0) {
            return null;
        }

        $db = \Config\Database::connect();
        $establecimientoPermitido = $this->resolveSessionEstablecimientoProveedorHospedaje($idEstablecimiento, $contextoUsuario);
        if (empty($establecimientoPermitido)) {
            return null;
        }

        $establecimientoRow = $db->table('establecimiento')
            ->select('dsc_establecimiento')
            ->where('visible', 1)
            ->where('id_establecimiento', $idEstablecimiento)
            ->get()
            ->getRowArray();
        $nombreEstablecimiento = trim((string) ($establecimientoRow['dsc_establecimiento'] ?? $usuarioRow['dsc_establecimiento'] ?? ''));

        $rows = $db->table('usuario u')
            ->select("
                u.id_usuario,
                u.folio,
                u.folio_grupo,
                u.sub_folio,
                CONCAT_WS(' ', u.nombre, u.primer_apellido, u.segundo_apellido) AS nombre_completo,
                u.fecha_check_in,
                u.fecha_check_out,
                u.id_tipo_habitacion,
                COALESCE(th.dsc_tipo_habitacion, u.id_tipo_habitacion) AS tipo_habitacion,
                u.tarifa_noche,
                u.tarifa_total,
                u.observaciones_hospedaje,
                u.id_partida AS id_partida_usuario,
                cp.partida AS partida_usuario
            ", false)
            ->join('cat_tipo_habitacion th', 'th.id_tipo_habitacion = u.id_tipo_habitacion', 'left')
            ->join('cat_partida cp', 'cp.id_partida = u.id_partida', 'left')
            ->where('u.visible', 1)
            ->where('u.tiene_hospedaje', 1)
            ->where('u.id_establecimiento_hotel', $idEstablecimiento)
            ->where('u.fecha_check_in IS NOT NULL', null, false)
            ->where("TRIM(COALESCE(u.fecha_check_in, '')) <> ''", null, false)
            ->orderBy('u.fecha_check_in', 'ASC')
            ->orderBy('u.id_usuario', 'ASC')
            ->get()
            ->getResultArray();

        $fechas = [];
        $totalTarifa = 0.0;
        $checkInCount = 0;
        $checkOutCount = 0;
        $partidaUnica = '';

        foreach ($rows as $row) {
            $fechaCheckIn = trim((string) ($row['fecha_check_in'] ?? ''));
            $fechaCheckOut = trim((string) ($row['fecha_check_out'] ?? ''));
            if ($fechaCheckIn !== '') {
                $fechas[] = $fechaCheckIn;
                $checkInCount++;
            }
            if ($fechaCheckOut !== '') {
                $fechas[] = $fechaCheckOut;
                $checkOutCount++;
            }
            $totalTarifa += (float) ($row['tarifa_noche'] ?? 0);

            $partidaActual = $this->formatReportePartidaPublicLabel((string) ($row['partida_usuario'] ?? ''));
            if ($partidaActual !== '' && $partidaUnica === '') {
                $partidaUnica = $partidaActual;
            }
        }

        sort($fechas);
        $periodoLabel = empty($fechas)
            ? 'Sin registros de hospedaje'
            : 'Periodo del ' . $this->formatReporteVentasFecha((string) reset($fechas)) . ' al ' . $this->formatReporteVentasFecha((string) end($fechas));

        return [
            'titulo' => 'Reporte de hospedaje',
            'subtitulo' => $nombreEstablecimiento !== ''
                ? $nombreEstablecimiento
                : 'Establecimiento',
            'id_establecimiento' => $idEstablecimiento,
            'establecimiento' => $nombreEstablecimiento,
            'periodo_label' => $periodoLabel,
            'rows' => $rows,
            'partida' => $partidaUnica,
            'resumen' => [
                'total_registros' => count($rows),
                'check_in' => $checkInCount,
                'check_out' => $checkOutCount,
                'total_tarifa' => $totalTarifa,
            ],
        ];
    }

    private function resolveSessionEstablecimientoProveedorHospedaje(int $idEstablecimiento, array $contextoUsuario): array
    {
        if ($idEstablecimiento <= 0) {
            return [];
        }

        $session = \Config\Services::session();
        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        if (!empty($contextoUsuario['can_access_secturi_dashboard']) || !empty($contextoUsuario['is_ti_master'])) {
            $row = $db->table('establecimiento e')
                ->select('e.id_establecimiento, e.dsc_establecimiento, e.id_tipo, e.no_proveedor')
                ->where('e.visible', 1)
                ->where('e.id_establecimiento', $idEstablecimiento)
                ->get()
                ->getRowArray();

            return is_array($row) ? $row : [];
        }

        $builder = $db->table('establecimiento e')
            ->select('e.id_establecimiento, e.dsc_establecimiento, e.id_tipo, e.no_proveedor')
            ->join('usuario u', 'u.id_usuario = ' . $idUsuario, 'left')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'left')
            ->join('usuario_establecimiento ue', 'ue.id_establecimiento = e.id_establecimiento AND ue.id_usuario = ' . $idUsuario . ' AND ue.visible = 1', 'left')
            ->where('e.visible', 1)
            ->where('e.id_establecimiento', $idEstablecimiento)
            ->groupStart()
                ->where('e.no_proveedor = p.no_proveedor', null, false)
                ->orWhere('e.no_proveedor', (string) $idUsuario)
                ->orWhere('ue.id_usuario IS NOT NULL', null, false);

        if (!empty($contextoUsuario['is_recepcion_flow'])) {
            $builder->orWhere('u.id_establecimiento', $idEstablecimiento);
        }

        $row = $builder
            ->groupEnd()
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    public function Hospedaje()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['hospedajeEstablecimientoId'] = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        $data['contentView'] = 'secciones/vHospedaje';
        $this->_renderView($data);
    }

    public function Cajero()
    {
        $usuarioDashboard = $this->resolveSecturiDashboardUsuario();
        $usuarioCapazQr = $this->resolveUsuarioCapazQr();
        $tiUsuario = $this->resolveTiMasterUsuario();
        $secturiAdminUsuario = $this->resolveSecturiAdminUsuario();

        if (empty($usuarioDashboard) && empty($usuarioCapazQr)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['cajeroAccesoTiInicio'] = true;
        $data['cajeroPuedeRechazarQr'] = !empty($usuarioCapazQr);
        $data['cajeroPuedeActivarQr'] = !empty($usuarioCapazQr);
        $data['cajeroPuedeGestionarQr'] = !empty($data['cajeroPuedeRechazarQr']) || !empty($data['cajeroPuedeActivarQr']);
        $data['cajeroSoloConsulta'] = empty($data['cajeroPuedeGestionarQr']);
        $data['cajeroRegresarUrl'] = base_url('index.php/Inicio');
        $data['contentView'] = 'secciones/vCajero';
        $this->_renderView($data);
    }

    public function PartidasFic()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

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

    public function getDashboardPartidasFic()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar el dashboard de partidas.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Dashboard de partidas consultado correctamente.',
            'data' => $this->buildPartidasDashboardSeed(),
        ]);
    }

    public function PagosFic()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['pagosFicDashboard'] = $this->buildPagosFicDashboardData();
        $data['establecimientosBandeja'] = $this->buildPagosFicEstablecimientosData();
        $data['previewInterfaceActiva'] = true;
        $data['previewInterfaceLabel'] = 'Vista de referencia';
        $data['previewInterfaceDescripcion'] = 'Estás consultando el historial global de pagos sin cambiar la sesión autenticada.';
        $data['contentView'] = 'secciones/vPagosFic';
        $this->_renderView($data);
    }

    public function FacturasFic()
    {
        if (empty($this->resolveSecturiDashboardUsuario())) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['pagosFicDashboard'] = $this->buildPagosFicDashboardData();
        $data['establecimientosBandeja'] = $this->buildPagosFicEstablecimientosData();
        $data['facturasListadoUrl'] = base_url('index.php/Inicio/getFacturasFic');
        $data['facturasArchivoUrl'] = base_url('index.php/Inicio/verFacturaProveedorArchivo');
        $data['previewInterfaceActiva'] = true;
        $data['previewInterfaceLabel'] = 'Vista de referencia';
        $data['previewInterfaceDescripcion'] = 'Estás consultando el historial global de facturas sin cambiar la sesión autenticada.';
        $data['contentView'] = 'secciones/vFacturasFic';
        $this->_renderView($data);
    }

    public function ReportesInstitucionales()
    {
        if (empty($this->resolveSecturiDashboardUsuario())) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['contentView'] = 'secciones/vReportesInstitucionales';
        $data['reportesInstitucionalesTabs'] = [
            [
                'key' => 'fic',
                'label' => 'FIC',
                'title' => 'Festival Internacional Cervantino',
                'description' => 'Reporte de saldos y consulta de movimientos de usuarios FIC.',
                'download_url' => base_url('index.php/Inicio/exportarReporteInstitucionalSaldosPdf/fic'),
                'profile_url' => base_url('index.php/Inicio/PerfilFic'),
            ],
            [
                'key' => 'ug',
                'label' => 'UG',
                'title' => 'Universidad de Guanajuato',
                'description' => 'Reporte de saldos y consulta de movimientos de usuarios UG.',
                'download_url' => base_url('index.php/Inicio/exportarReporteInstitucionalSaldosPdf/ug'),
                'profile_url' => base_url('index.php/Inicio/PerfilUg'),
            ],
            [
                'key' => 'secul',
                'label' => 'SECUL',
                'title' => 'Secretarí­a de Cultura',
                'description' => 'Reporte de saldos y consulta de movimientos de usuarios SECUL.',
                'download_url' => base_url('index.php/Inicio/exportarReporteInstitucionalSaldosPdf/secul'),
                'profile_url' => base_url('index.php/Inicio/PerfilSecul'),
            ],
        ];

        $this->_renderView($data);
    }

    public function getFacturasFic()
    {
        if (empty($this->resolveSecturiDashboardUsuario())) {
            return $this->response->setStatusCode(403)->setJSON([
                'total' => 0,
                'rows' => [],
                'error' => true,
                'respuesta' => 'No tienes permisos para consultar facturas.',
            ]);
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('facturas')) {
            return $this->response->setJSON([
                'total' => 0,
                'rows' => [],
            ]);
        }

        $rows = $db->table('facturas f')
            ->select('
                f.id_factura,
                f.xml,
                f.pdf,
                f.id_estableciemiento AS id_establecimiento,
                f.id_estatus,
                f.fec_reg,
                f.usu_reg,
                f.visible,
                e.dsc_establecimiento,
                e.no_proveedor,
                p.razon_social,
                p.rfc
            ')
            ->join('establecimiento e', 'e.id_establecimiento = f.id_estableciemiento', 'left')
            ->join('proveedor p', 'p.no_proveedor = e.no_proveedor', 'left')
            ->where('f.visible', 1)
            ->orderBy('f.fec_reg', 'DESC')
            ->get()
            ->getResultArray();

        $mapped = array_map(static function (array $row): array {
            $activoQr = (int) ($row['activo_qr'] ?? 0);
            $ineFirmaCajero = trim((string) ($row['ine_firma_cajero'] ?? '')) !== '';
            $ineCompleta = trim((string) ($row['ine_frontal'] ?? '')) !== ''
                && trim((string) ($row['ine_trasera'] ?? '')) !== ''
                && trim((string) ($row['firma'] ?? '')) !== '';
            $tieneDocumentoCargado = $ineFirmaCajero || $ineCompleta;

            return [
                'id_usuario' => (int) ($row['id_usuario'] ?? 0),
                'folio' => (string) ($row['folio'] ?? ''),
                'usuario' => (string) ($row['usuario'] ?? ''),
                'nombre' => (string) ($row['nombre'] ?? ''),
                'primer_apellido' => (string) ($row['primer_apellido'] ?? ''),
                'segundo_apellido' => (string) ($row['segundo_apellido'] ?? ''),
                'nombre_completo' => trim((string) ($row['nombre_completo'] ?? '')),
                'correo' => (string) ($row['correo'] ?? ''),
                'qr' => trim((string) ($row['qr'] ?? '')),
                'ine_firma_cajero' => trim((string) ($row['ine_firma_cajero'] ?? '')),
                'ine_frontal' => trim((string) ($row['ine_frontal'] ?? '')),
                'ine_trasera' => trim((string) ($row['ine_trasera'] ?? '')),
                'firma' => trim((string) ($row['firma'] ?? '')),
                'expediente_completo' => $tieneDocumentoCargado,
                'activo_qr' => $activoQr,
                'qr_activo' => $activoQr,
                'fec_reg' => (string) ($row['fec_reg'] ?? ''),
                'fec_act' => (string) ($row['fec_act'] ?? ''),
                'visible' => (int) ($row['visible'] ?? 0),
            ];
        }, $rows);

        return $this->response->setJSON([
            'ok' => true,
            'success' => true,
            'total' => $total,
            'rows' => $mapped,
        ]);
    }

    public function activarQrUsuarioFic()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();
        $usuarioCapazQr = $this->resolveUsuarioCapazQr();
        if (empty($tiUsuario) && empty($usuarioCapazQr)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'No tienes permisos para activar usuarios.',
            ]);
        }

        $idUsuario = (int) ($this->request->getPost('id_usuario') ?? $this->request->getGet('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El usuario es requerido.',
            ]);
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, visible, qr, ine_firma_cajero, ine_frontal, ine_trasera, firma, activo_qr')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'No fue posible resolver el usuario.',
            ]);
        }
        if ((int) ($usuario['visible'] ?? 0) !== 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El usuario no está visible.',
            ]);
        }

        if (!$this->usuarioTieneFolioQrCompleto($usuario)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Falta un folio documental completo para activar el QR.',
            ]);
        }

        $service = new DepositosProgramadosService($db);
        $actorId = (int) (($tiUsuario['id_usuario'] ?? 0) ?: ($usuarioCapazQr['id_usuario'] ?? 0));
        $result = $service->activateQrAndApplyDeposits($idUsuario, $actorId);
        if (!empty($result->error)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => preg_replace('/^Error \| /', '', (string) ($result->respuesta ?? 'No fue posible activar el QR.')),
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => (string) ($result->respuesta ?? 'QR activado correctamente.'),
            'aplicado' => (float) ($result->aplicado ?? 0),
            'programa' => $result->programa ?? null,
        ]);
    }

    public function rechazarActivacionQrUsuarioFic()
    {
        $tiUsuario = $this->resolveTiMasterUsuario();
        $usuarioCapazQr = $this->resolveUsuarioCapazQr();
        if (empty($tiUsuario) && empty($usuarioCapazQr)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'No tienes permisos para rechazar solicitudes de QR.',
            ]);
        }

        $idUsuario = (int) ($this->request->getPost('id_usuario') ?? $this->request->getGet('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El usuario es requerido.',
            ]);
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, visible, qr')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'No fue posible resolver el usuario.',
            ]);
        }
        if ((int) ($usuario['visible'] ?? 0) !== 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El usuario no está visible.',
            ]);
        }

        $db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->update([
                'activo_qr' => 0,
                'qr' => $usuario['qr'] ?? null,
                'ine_firma_cajero' => null,
                'ine_frontal' => null,
                'ine_trasera' => null,
                'firma' => null,
                'fec_act' => date('Y-m-d H:i:s'),
                'usu_act' => (int) (($tiUsuario['id_usuario'] ?? 0) ?: ($usuarioCapazQr['id_usuario'] ?? 0)),
            ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Solicitud rechazada. El usuario podrá iniciar nuevamente el proceso.',
        ]);
    }

    public function verArchivoSolicitudQrFic()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();
        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para consultar este archivo.');
        }

        $idUsuario = (int) ($this->request->getGet('id_usuario') ?? 0);
        $campo = trim((string) ($this->request->getGet('campo') ?? ''));
        $camposPermitidos = ['qr', 'ine_frontal', 'ine_trasera', 'firma'];

        if ($idUsuario <= 0 || !in_array($campo, $camposPermitidos, true)) {
            return $this->response->setStatusCode(422)->setBody('Solicitud invalida.');
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, visible, qr, ine_frontal, ine_trasera, firma')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado.');
        }

        $archivo = trim((string) ($usuario[$campo] ?? ''));
        if ($archivo === '') {
            return $this->response->setStatusCode(404)->setBody('Archivo no disponible.');
        }

        $url = $this->buildS3PresignedGetUrl($archivo, 300);
        if ($url === '') {
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el acceso temporal al archivo.');
        }

        return redirect()->to($url);
    }

    public function verQrCliente()
    {
        $session = \Config\Services::session();
        $idSesion = (int) ($session->get('id_usuario') ?? 0);
        $idUsuario = (int) ($this->request->getGet('id_usuario') ?? $idSesion);

        if ($idSesion <= 0 || $idUsuario <= 0 || $idUsuario !== $idSesion) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para consultar este QR.');
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, visible, qr')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado.');
        }

        $qr = trim((string) ($usuario['qr'] ?? ''));
        if ($qr === '') {
            return $this->response->setStatusCode(404)->setBody('QR no disponible.');
        }

        $url = $this->buildS3PresignedGetUrl($qr, 300);
        if ($url === '') {
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el acceso temporal al QR.');
        }

        return redirect()->to($url);
    }

    private function uploadFileToS3(string $absolutePath, string $objectKey, string $contentType): ?string
    {
        $this->lastS3Error = '';
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            $this->lastS3Error = 'No se puede leer el archivo temporal.';
            log_message('error', 'Inicio.uploadFileToS3: local file is not readable: ' . $absolutePath);
            return null;
        }

        $bucket = $this->envFirst(['AWS_BUCKET', 'AWS_S3_BUCKET', 'S3_BUCKET', 'S3_BUCKET_NAME']);
        $region = $this->envFirst(['AWS_REGION', 'AWS_DEFAULT_REGION', 'S3_REGION'], 'us-east-1');
        $accessKey = $this->envFirst(['AWS_ACCESS_KEY_ID', 'AWS_ACCESS_KEY', 'S3_ACCESS_KEY', 'S3_KEY']);
        $secretKey = $this->envFirst(['AWS_SECRET_ACCESS_KEY', 'AWS_SECRET_KEY', 'S3_SECRET_KEY', 'S3_SECRET']);
        $sessionToken = $this->envFirst(['AWS_SESSION_TOKEN', 'S3_SESSION_TOKEN']);
        $acl = $this->envFirst(['AWS_S3_ACL', 'S3_ACL']);

        if ($bucket === '' || $accessKey === '' || $secretKey === '') {
            $this->lastS3Error = 'Faltan variables de S3 en .env: bucket, access key o secret key.';
            log_message('error', 'Inicio.uploadFileToS3: missing S3 env vars.');
            return null;
        }

        $body = file_get_contents($absolutePath);
        if ($body === false) {
            $this->lastS3Error = 'No se pudo leer el contenido del archivo temporal.';
            log_message('error', 'Inicio.uploadFileToS3: could not read local file body.');
            return null;
        }

        $encodedKey = $this->encodeS3Key($objectKey);
        $host = $region === 'us-east-1'
            ? $bucket . '.s3.amazonaws.com'
            : $bucket . '.s3.' . $region . '.amazonaws.com';
        $url = 'https://' . $host . '/' . $encodedKey;

        $payloadHash = hash('sha256', $body);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        $headers = [
            'content-type' => $contentType,
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ];

        if ($acl !== '') {
            $headers['x-amz-acl'] = $acl;
        }

        if ($sessionToken !== '') {
            $headers['x-amz-security-token'] = $sessionToken;
        }

        ksort($headers);
        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim((string) $value) . "\n";
        }
        $signedHeaders = implode(';', array_keys($headers));

        $canonicalRequest = implode("\n", [
            'PUT',
            '/' . $encodedKey,
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = $dateStamp . '/' . $region . '/s3/aws4_request';
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getAwsSignatureKey($secretKey, $dateStamp, $region, 's3');
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorization = 'AWS4-HMAC-SHA256 Credential=' . $accessKey . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

        $requestHeaders = [];
        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }
        $requestHeaders[] = 'Authorization: ' . $authorization;
        $requestHeaders[] = 'Content-Length: ' . strlen($body);

        if (!function_exists('curl_init')) {
            $this->lastS3Error = 'La extension cURL de PHP no esta disponible.';
            log_message('error', 'Inicio.uploadFileToS3: cURL extension is not available.');
            return null;
        }

        $sslVerifyValue = strtolower($this->envFirst(['AWS_SSL_VERIFY', 'S3_SSL_VERIFY'], 'true'));
        $sslVerify = !in_array($sslVerifyValue, ['0', 'false', 'no'], true);
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ];

        $caInfo = $this->resolveCurlCaInfo();
        if ($sslVerify && $caInfo !== '') {
            $curlOptions[CURLOPT_CAINFO] = $caInfo;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);

        $rawResponse = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($rawResponse === false || $httpCode < 200 || $httpCode >= 300) {
            $this->lastS3Error = trim('HTTP ' . $httpCode . ' ' . $curlError . ' ' . $this->extractS3ErrorMessage((string) $rawResponse));
            log_message('error', 'Inicio.uploadFileToS3: upload failed. HTTP ' . $httpCode . ' ' . $curlError . ' Response: ' . substr((string) $rawResponse, 0, 500));
            return null;
        }

        $publicBaseUrl = rtrim($this->envFirst(['AWS_S3_PUBLIC_URL', 'S3_PUBLIC_URL']), '/');
        if ($publicBaseUrl !== '') {
            return $publicBaseUrl . '/' . $encodedKey;
        }

        return $url;
    }

    private function extractS3ErrorMessage(string $rawResponse): string
    {
        if ($rawResponse === '') {
            return '';
        }

        if (preg_match('/<Code>([^<]+)<\/Code>.*<Message>([^<]+)<\/Message>/s', $rawResponse, $matches)) {
            return trim($matches[1] . ': ' . html_entity_decode($matches[2], ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        return '';
    }

    private function resolveCurlCaInfo(): string
    {
        $configured = $this->envFirst(['AWS_CA_BUNDLE', 'CURL_CA_BUNDLE', 'SSL_CERT_FILE']);
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $iniCandidates = [ini_get('curl.cainfo'), ini_get('openssl.cafile')];
        foreach ($iniCandidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }

        $fileCandidates = [
            ROOTPATH . 'cacert.pem',
            WRITEPATH . 'cacert.pem',
            'C:\wamp64\apps\phpmyadmin5.2.1\vendor\composer\ca-bundle\res\cacert.pem',
        ];

        foreach ($fileCandidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function buildS3PresignedGetUrl(string $storedPath, int $expires = 300): string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $storedPath) && stripos($storedPath, '.amazonaws.com/') === false) {
            return $storedPath;
        }

        $bucket = $this->envFirst(['AWS_BUCKET', 'AWS_S3_BUCKET', 'S3_BUCKET', 'S3_BUCKET_NAME']);
        $region = $this->envFirst(['AWS_REGION', 'AWS_DEFAULT_REGION', 'S3_REGION'], 'us-east-1');
        $accessKey = $this->envFirst(['AWS_ACCESS_KEY_ID', 'AWS_ACCESS_KEY', 'S3_ACCESS_KEY', 'S3_KEY']);
        $secretKey = $this->envFirst(['AWS_SECRET_ACCESS_KEY', 'AWS_SECRET_KEY', 'S3_SECRET_KEY', 'S3_SECRET']);
        $sessionToken = $this->envFirst(['AWS_SESSION_TOKEN', 'S3_SESSION_TOKEN']);

        if ($bucket === '' || $accessKey === '' || $secretKey === '') {
            log_message('error', 'Inicio.buildS3PresignedGetUrl: missing S3 env vars.');
            return '';
        }

        $objectKey = $this->resolveS3ObjectKey($storedPath, $bucket);
        if ($objectKey === '') {
            return '';
        }

        $expires = max(60, min(3600, $expires));
        $encodedKey = $this->encodeS3Key($objectKey);
        $host = $region === 'us-east-1'
            ? $bucket . '.s3.amazonaws.com'
            : $bucket . '.s3.' . $region . '.amazonaws.com';

        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = $dateStamp . '/' . $region . '/s3/aws4_request';

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $accessKey . '/' . $credentialScope,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];

        if ($sessionToken !== '') {
            $query['X-Amz-Security-Token'] = $sessionToken;
        }

        ksort($query);
        $canonicalQuery = [];
        foreach ($query as $name => $value) {
            $canonicalQuery[] = rawurlencode($name) . '=' . rawurlencode((string) $value);
        }
        $canonicalQueryString = implode('&', $canonicalQuery);

        $canonicalRequest = implode("\n", [
            'GET',
            '/' . $encodedKey,
            $canonicalQueryString,
            'host:' . $host . "\n",
            'host',
            'UNSIGNED-PAYLOAD',
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getAwsSignatureKey($secretKey, $dateStamp, $region, 's3');
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return 'https://' . $host . '/' . $encodedKey . '?' . $canonicalQueryString . '&X-Amz-Signature=' . $signature;
    }

    private function resolveS3ObjectKey(string $storedPath, string $bucket): string
    {
        $path = trim($storedPath);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            $parts = parse_url($path);
            $host = (string) ($parts['host'] ?? '');
            $urlPath = ltrim((string) ($parts['path'] ?? ''), '/');

            if ($host === '') {
                return '';
            }

            if (stripos($host, $bucket . '.s3') === 0) {
                return rawurldecode($urlPath);
            }

            if (stripos($host, 's3') === 0) {
                $prefix = $bucket . '/';
                if (strpos($urlPath, $prefix) === 0) {
                    return rawurldecode(substr($urlPath, strlen($prefix)));
                }
            }

            return rawurldecode($urlPath);
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function envFirst(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = env($key);
            if ($value !== null && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $default;
    }

    private function encodeS3Key(string $key): string
    {
        $segments = array_map('rawurlencode', explode('/', str_replace('\\', '/', $key)));
        return implode('/', $segments);
    }

    private function getAwsSignatureKey(string $secretKey, string $dateStamp, string $regionName, string $serviceName): string
    {
        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $regionName, $kDate, true);
        $kService = hash_hmac('sha256', $serviceName, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
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
        return $this->resolveUsuarioPorCapacidad('is_ti_master');
    }

    private function resolveSecturiAdminUsuario(): array
    {
        $usuario = $this->resolveSecturiDashboardUsuario();
        if (empty($usuario)) {
            return [];
        }

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($usuario);
        if (($contextoUsuario['active_group'] ?? '') === 'secturi' && (int) ($contextoUsuario['group_role'] ?? 0) === 1) {
            return $usuario;
        }

        if (!empty($contextoUsuario['is_ti_master'])) {
            return $usuario;
        }

        return [];
    }

    private function resolveSecturiDashboardUsuario(): array
    {
        return $this->resolveUsuarioPorCapacidad('can_access_secturi_dashboard');
    }

    private function resolveUsuarioCapazQr(): array
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, id_perfil, id_proveedor, id_tipo_proveedor, id_fic_perfil, id_ug_perfil, id_secul_perfil, id_secturi_perfil, visible')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return [];
        }

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($usuario);
        $grupo = (string) ($contextoUsuario['active_group'] ?? '');
        $rol = (int) ($contextoUsuario['group_role'] ?? 0);

        if (!empty($contextoUsuario['is_ti_master'])) {
            return $usuario;
        }

        if ($grupo === 'secturi' && in_array($rol, [1, 2, 4], true)) {
            return $usuario;
        }

        return [];
    }


    private function usuarioTieneFolioQrCompleto(array $usuario): bool
    {
        if (empty($usuario)) {
            return false;
        }

        if (in_array(($usuario['expediente_completo'] ?? null), [true, 1, '1'], true)) {
            return true;
        }

        $pdfIneYFirma = trim((string) ($usuario['ine_firma_cajero'] ?? '')) !== '';
        $ineCompleta = trim((string) ($usuario['ine_frontal'] ?? '')) !== ''
            && trim((string) ($usuario['ine_trasera'] ?? '')) !== ''
            && trim((string) ($usuario['firma'] ?? '')) !== '';

        return $pdfIneYFirma || $ineCompleta;
    }
    private function resolveFolioDecisionUsuario(): array
    {
        return $this->resolveUsuarioPorCapacidad('can_decide_institutional_folios');
    }

    private function resolveUsuarioPorCapacidad(string $capacidad): array
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $usuario = $db->table('usuario')
            ->select('id_usuario, id_perfil, id_proveedor, id_tipo_proveedor, id_fic_perfil, id_ug_perfil, id_secul_perfil, id_secturi_perfil, visible')
            ->where('id_usuario', $idUsuario)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuario)) {
            return [];
        }

        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($usuario);
        if (empty($contextoUsuario[$capacidad])) {
            return [];
        }

        return $usuario;
    }

    public function getSolicitudesUsuarioOperativo()
{
    $tiUsuario = $this->resolveSecturiDashboardUsuario();

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
        ->where('su.visible', 1)
        ->whereIn('su.tipo_solicitud', ['alta_gerente', 'alta_recepcion']); 

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
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

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
                'message' => 'Completa usuario y contraseí±a.',
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
            'SELECT id_solicitud_usuario, tipo_solicitud, estatus, visible
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
        if (!empty($solicitud)) {
            if (!$this->createSolicitudRechazadaNotification($solicitud, $motivo, $motivo)) {
                log_message('warning', 'Inicio.rechazarSolicitudUsuarioOperativo: no se pudo crear la notificación para la solicitud ' . $idSolicitud);
            }
        }

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

        if ($modoAltaProveedor && empty($this->resolveSecturiAdminUsuario())) {
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
        $data['saveUrl'] = base_url('index.php/Usuario/saveAltaUsuario');
        $data['contentView'] = 'secciones/vAltaUsuario';

        if ($modoAltaProveedor) {
            $data['catalogRoleOptions'] = [];
            $data['providerTypeOptions'] = [];
            $data['hotelOptions'] = [];
            $data['catTipoHabitacion'] = [];
            $data['partidaOptions'] = [];

            $this->_renderView($data);
            return;
        }

        $data = array_merge($data, $this->buildAltaUsuarioFormData($contextoUsuario, $resolver));

        $this->_renderView($data);
    }

    public function getSolicitudesNuevoFolioTi()
{
    if (empty($this->resolveSecturiDashboardUsuario())) {
        return $this->response->setStatusCode(403)->setJSON([
            'ok' => false,
            'total' => 0,
            'rows' => [],
            'message' => 'No tienes permisos para consultar esta bandeja.',
        ]);
    }

    $db = \Config\Database::connect();
    $builder = $db->table('solicitud_usuario su')
        ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, COALESCE(cf.dsc_perfil, cs.des_perfil, cu.dsc_perfil) AS perfil_solicitado')
        ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado AND su.tipo_solicitud IN ("alta_usuario_fic", "edicion_usuario_fic")', 'left')
        ->join('cat_secul cs', 'cs.id_secul_perfil = su.id_perfil_solicitado AND su.tipo_solicitud IN ("alta_usuario_secul", "edicion_usuario_secul")', 'left')
        ->join('cat_ug cu', 'cu.id_ug_perfil = su.id_perfil_solicitado AND su.tipo_solicitud IN ("alta_usuario_ug", "edicion_usuario_ug")', 'left')
        ->where('su.visible', 1)
        ->whereIn('su.tipo_solicitud', $this->getTiposSolicitudFolioInstitucional());

    $search = trim((string) ($this->request->getGet('search') ?? ''));
    if ($search !== '') {
        $builder->groupStart()
            ->like('su.usuario', $search)
            ->orLike('su.nombre', $search)
            ->orLike('su.primer_apellido', $search)
            ->orLike('su.segundo_apellido', $search)
            ->orLike('su.correo', $search)
            ->orLike('su.estatus', $search)
            ->orLike('su.tipo_solicitud', $search)
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

    public function getSolicitudNuevoFolioTi()
    {
        if (empty($this->resolveSecturiDashboardUsuario())) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para consultar esta solicitud.']);
        }

        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        $row = $this->findSolicitudNuevoFolioTi($idSolicitud);
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'No se encontró la solicitud.']);
        }

        return $this->response->setJSON(['ok' => true, 'data' => $this->mapSolicitudUsuarioFicPerfilRow($row)]);
    }

    public function aprobarSolicitudNuevoFolioTi()
    {
        $tiUsuario = $this->resolveFolioDecisionUsuario();
        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para aprobar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        $idPartidaAprobacion = (int) ($this->request->getPost('id_partida') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        $db = \Config\Database::connect();
        $solicitud = $this->findSolicitudNuevoFolioTi($idSolicitud);
        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente') {
            $estatusActual = trim((string) ($solicitud['estatus'] ?? 'desconocido'));
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'La solicitud institucional ya no está pendiente. Estatus actual: ' . $estatusActual . '.']);
        }

        $esSolicitudEdicion = $this->isSolicitudEdicionInstitucional($solicitud);
        if (!$esSolicitudEdicion && !in_array($idPartidaAprobacion, [1, 2, 3], true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Selecciona la partida que se asignará al folio antes de aprobar la solicitud.']);
        }

        $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($solicitud['comentario_ti'] ?? ''));
        $payload = $payloadInfo['payload'];
        if (empty($payload)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Esta solicitud fue capturada con el formato anterior y no contiene los datos completos para crear el folio. Recházala y solicita que se capture nuevamente desde Solicitud de nuevo folio.',
            ]);
        }
        $grupoSolicitud = $this->resolveSolicitudFolioGrupo($solicitud, (string) ($payloadInfo['grupo'] ?? ''), $payload);
        $payload = $this->normalizeSolicitudFolioPayload($grupoSolicitud, $payload);
        if ($esSolicitudEdicion) {
            $session = \Config\Services::session();
            $resolver = new UsuarioPerfilResolver();
            $actorContext = $resolver->resolve($session->get());
            if (empty($actorContext['is_ti_master'])) {
                $actorContext['is_ti_master'] = true;
                $actorContext['can_edit_user_catalog'] = true;
            }

            $usuarioController = new Usuario();
            $usuarioController->initController($this->request, $this->response, \Config\Services::logger());
            $saveResponse = $usuarioController->applyInstitutionalUserEditPayload(
                $payload,
                $actorContext,
                (int) ($session->get('id_usuario') ?? 0),
                'Inicio.aprobarSolicitudNuevoFolioTi.edicion'
            );
            $saveBody = json_decode((string) $saveResponse->getBody(), true);

            if (!is_array($saveBody) || !empty($saveBody['error'])) {
                return $this->response->setStatusCode(409)->setJSON([
                    'ok' => false,
                    'message' => (string) ($saveBody['respuesta'] ?? 'No fue posible aplicar la edición solicitada.'),
                ]);
            }

            $payloadLimpio = $this->removeSensitiveSolicitudFolioPayload($payload);
            $fechaAhora = date('Y-m-d H:i:s');
            $db->table('solicitud_usuario')->where('id_solicitud_usuario', $idSolicitud)->update([
                'estatus' => 'aprobada',
                'comentario_ti' => $this->encodeSolicitudFolioPayload($grupoSolicitud, $payloadLimpio),
                'fec_act' => $fechaAhora,
                'usu_act' => (int) ($session->get('id_usuario') ?? 0),
            ]);

            return $this->response->setJSON(['ok' => true, 'message' => 'Solicitud aprobada y edición aplicada correctamente.']);
        }
        $payload['id_partida'] = $idPartidaAprobacion;
        if ((int) ($payload['perfil_grupo'] ?? 0) <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'La solicitud no tiene un perfil visible valido. Edita la solicitud y selecciona un perfil visible antes de aprobarla.',
            ]);
        }

        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $actorContext = $resolver->resolve($session->get());
        if (empty($actorContext['is_ti_master'])) {
            // La elevación es local a esta aprobación y conserva el grupo solicitado en el payload.
            $actorContext['is_ti_master'] = true;
            $actorContext['can_edit_user_catalog'] = true;
        }
        $usuarioController = new Usuario();
        $usuarioController->initController($this->request, $this->response, \Config\Services::logger());
        $saveResponse = $usuarioController->saveAltaUsuarioPayload($payload, $actorContext, (int) ($session->get('id_usuario') ?? 0), 'Inicio.aprobarSolicitudNuevoFolioTi');
        $saveBody = json_decode((string) $saveResponse->getBody(), true);

        if (!is_array($saveBody) || !empty($saveBody['error'])) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => (string) ($saveBody['respuesta'] ?? 'No fue posible crear el usuario desde la solicitud.'),
            ]);
        }

        $ids = $saveBody['data']['ids'] ?? [];
        $idUsuarioCreado = is_array($ids) && !empty($ids) ? (int) reset($ids) : (int) ($saveBody['id_usuario'] ?? 0);
        $payloadLimpio = $this->removeSensitiveSolicitudFolioPayload($payload);
        $fechaAhora = date('Y-m-d H:i:s');
        $db->table('solicitud_usuario')->where('id_solicitud_usuario', $idSolicitud)->update([
            'estatus' => 'aprobada',
            'id_usuario_creado' => $idUsuarioCreado > 0 ? $idUsuarioCreado : null,
            'comentario_ti' => $this->encodeSolicitudFolioPayload($grupoSolicitud, $payloadLimpio),
            'fec_act' => $fechaAhora,
            'usu_act' => (int) ($session->get('id_usuario') ?? 0),
        ]);

        return $this->response->setJSON(['ok' => true, 'message' => 'Solicitud aprobada y folio creado correctamente.']);
    }

    public function actualizarSolicitudNuevoFolioTi()
    {
        $usuarioAdmin = $this->resolveSecturiAdminUsuario();
        if (empty($usuarioAdmin)) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para editar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        $payloadJson = trim((string) ($this->request->getPost('payload_json') ?? ''));
        if ($idSolicitud <= 0 || $payloadJson === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud o formulario no válido.']);
        }

        $solicitud = $this->findSolicitudNuevoFolioTi($idSolicitud);
        if (empty($solicitud) || !in_array((string) ($solicitud['estatus'] ?? ''), ['pendiente', 'rechazada'], true)) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Solo se pueden editar solicitudes pendientes o rechazadas.']);
        }

        $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($solicitud['comentario_ti'] ?? ''));
        if (empty($payloadInfo['payload'])) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Esta solicitud no contiene formulario completo editable.']);
        }

        $payloadEditado = json_decode($payloadJson, true);
        if (!is_array($payloadEditado)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'El formulario editado no tiene formato JSON válido.']);
        }

        $grupo = $this->resolveSolicitudFolioGrupo($solicitud, (string) ($payloadInfo['grupo'] ?? ''), $payloadEditado);
        $payload = $this->normalizeSolicitudFolioPayload($grupo, $payloadEditado);
        $payload['comentario_usuario'] = trim((string) ($payload['comentario_usuario'] ?? ''));
        if ($estatusActual === 'rechazada') {
            $payloadAnterior = is_array($payloadInfo['payload'] ?? null) ? $payloadInfo['payload'] : [];
            $comentarioUsuarioAnterior = trim((string) ($payloadAnterior['comentario_usuario'] ?? ''));
            $motivoRechazoAnterior = trim((string) ($payloadAnterior['motivo_rechazo'] ?? ''));
            if ($comentarioUsuarioAnterior !== '') {
                $payload['comentario_usuario_anterior'] = $comentarioUsuarioAnterior;
            }
            if ($motivoRechazoAnterior !== '') {
                $payload['motivo_rechazo_anterior'] = $motivoRechazoAnterior;
            }
            unset($payload['motivo_rechazo']);
        }
        $perfilGrupo = (int) ($payload['perfil_grupo'] ?? 0);
        $idPerfilSolicitado = $perfilGrupo;
        $usuario = strtolower(trim((string) ($payload['usuario'] ?? '')));
        $nombre = trim((string) ($payload['nombre'] ?? ''));
        $primerApellido = trim((string) ($payload['primer_apellido'] ?? ''));
        $segundoApellido = trim((string) ($payload['segundo_apellido'] ?? ''));
        $correo = strtolower(trim((string) ($payload['correo'] ?? '')));

        if ($idPerfilSolicitado <= 0 || $usuario === '' || $nombre === '' || $primerApellido === '' || empty($payload['folio_grupo'])) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Completa perfil, usuario, nombre, primer apellido y folio antes de guardar cambios.']);
        }
        if ($perfilGrupo <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Captura perfil_grupo como valor numerico antes de guardar cambios.']);
        }

        $db = \Config\Database::connect();
        $fechaAhora = date('Y-m-d H:i:s');
        $estatusActual = strtolower(trim((string) ($solicitud['estatus'] ?? '')));
        $nuevoEstatus = $estatusActual === 'rechazada' ? 'pendiente' : (string) ($solicitud['estatus'] ?? 'pendiente');
        $db->table('solicitud_usuario')
            ->where('id_solicitud_usuario', $idSolicitud)
            ->update([
                'id_perfil_solicitado' => $idPerfilSolicitado,
                'usuario' => $usuario,
                'nombre' => $nombre,
                'primer_apellido' => $primerApellido,
                'segundo_apellido' => $segundoApellido,
                'correo' => $correo,
                'estatus' => $nuevoEstatus,
                'comentario_ti' => $this->encodeSolicitudFolioPayload($grupo, $payload),
                'fec_act' => $fechaAhora,
                'usu_act' => (int) (\Config\Services::session()->get('id_usuario') ?? 0),
            ]);

        return $this->response->setJSON([
            'ok' => true,
            'message' => $nuevoEstatus === 'pendiente'
                ? 'Solicitud actualizada y reabierta correctamente.'
                : 'Solicitud actualizada correctamente.',
        ]);
    }

    public function rechazarSolicitudNuevoFolioTi()
    {
        if (empty($this->resolveFolioDecisionUsuario())) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para rechazar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        $motivo = trim((string) ($this->request->getPost('motivo') ?? ''));
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }
        if ($motivo === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Captura el motivo de rechazo.']);
        }

        $db = \Config\Database::connect();
        $solicitud = $this->findSolicitudNuevoFolioTi($idSolicitud);
        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente') {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'Solo se pueden rechazar solicitudes pendientes.']);
        }

        $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($solicitud['comentario_ti'] ?? ''));
        $comentario = (string) ($solicitud['comentario_ti'] ?? '');
        if (!empty($payloadInfo['payload'])) {
            $payload = $this->removeSensitiveSolicitudFolioPayload($payloadInfo['payload']);
            $payload['motivo_rechazo'] = $motivo;
            $comentario = $this->encodeSolicitudFolioPayload((string) ($payloadInfo['grupo'] ?? ''), $payload);
        } else {
            $comentario .= "\n\nMotivo de rechazo:\n" . $motivo;
        }

        $db->table('solicitud_usuario')->where('id_solicitud_usuario', $idSolicitud)->update([
            'estatus' => 'rechazada',
            'comentario_ti' => $comentario,
            'fec_act' => date('Y-m-d H:i:s'),
            'usu_act' => (int) (\Config\Services::session()->get('id_usuario') ?? 0),
        ]);

        $this->createSolicitudRechazadaNotification($solicitud, $motivo, $comentario);

        return $this->response->setJSON(['ok' => true, 'message' => 'Solicitud rechazada correctamente.']);
    }

    private function createSolicitudRechazadaNotification(array $solicitud, string $motivo, string $comentario = ''): bool
    {
        $idSolicitud = (int) ($solicitud['id_solicitud_usuario'] ?? 0);
        if ($idSolicitud <= 0) {
            return false;
        }

        $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($comentario !== '' ? $comentario : ($solicitud['comentario_ti'] ?? '')));
        $payload = is_array($payloadInfo['payload'] ?? null) ? $payloadInfo['payload'] : [];
        $grupo = $this->resolveSolicitudFolioGrupo($solicitud, (string) ($payloadInfo['grupo'] ?? ''), $payload);
        $grupoLabel = strtoupper($grupo !== '' ? $grupo : 'FIC');
        $fechaAhora = date('Y-m-d H:i:s');
        $urlEdicion = $this->buildSolicitudFolioEditUrl($grupo !== '' ? $grupo : 'fic', $idSolicitud);
        $destinatarios = $this->getNotificationAudienceRecipients($grupo);

        if (empty($destinatarios)) {
            log_message('warning', 'Inicio.createSolicitudRechazadaNotification: sin destinatarios para grupo ' . ($grupo !== '' ? $grupo : 'fic') . ' solicitud ' . $idSolicitud);
            return false;
        }

        $notificationData = [
            'type' => 'SOLICITUD_ALTA_RECHAZADA',
            'grupo' => $grupo !== '' ? $grupo : 'fic',
            'id_solicitud_usuario' => $idSolicitud,
            'tipo_solicitud' => (string) ($solicitud['tipo_solicitud'] ?? ''),
            'estatus' => 'rechazada',
            'motivo' => $motivo,
            'url' => $urlEdicion,
            'created_at' => $fechaAhora,
            'scope' => 'group_admin_capturista',
            'roles' => [1, 2],
        ];

        try {
            $db = \Config\Database::connect(null, false);
            $insertData = [
                'titulo' => 'Solicitud rechazada',
                'mensaje' => 'Hay una solicitud ' . $grupoLabel . ' rechazada pendiente de revisión.',
                'tipo' => 'SOLICITUD_ALTA_RECHAZADA',
                'data_json' => json_encode($notificationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'visible' => 1,
                'created_at' => $fechaAhora,
                'read_at' => null,
            ];

            foreach ($destinatarios as $idUsuarioDestino) {
                $db->table('notification')->insert($insertData + ['id_usuario' => (int) $idUsuarioDestino]);
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'Inicio.createSolicitudRechazadaNotification: ' . $e->getMessage());
            return false;
        }
    }

    private function buildAltaUsuarioFormData(array $contextoUsuario, UsuarioPerfilResolver $resolver): array
    {
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

        $catPartida = $Mglobal->getTabla([
            'tabla' => 'cat_partida',
            'where' => [
                'visible' => 1,
            ],
            'order' => 'id_partida ASC',
        ]);

        return [
            'contextoUsuario' => $contextoUsuario,
            'hotelOptions' => $hotelOptions->data ?? [],
            'catTipoHabitacion' => $catTipoHabitacion->data ?? [],
            'partidaOptions' => $catPartida->data ?? [],
            'catalogRoleOptions' => $resolver->getAllowedRoleOptions($contextoUsuario),
            'providerTypeOptions' => $resolver->getProviderTypes(),
        ];
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

    public function PerfilSecul()
    {
        return $this->renderPerfilSeculHub('admin');
    }

    public function PerfilSecturi()
    {
        return $this->renderPerfilSecturiHub('admin');
    }

    public function PerfilSecturiConsulta()
    {
        return $this->renderPerfilSecturiHub('consulta');
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

    private function renderPerfilSecturiHub(string $modo = 'admin')
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_ti_master']) && !(($contextoUsuario['active_group'] ?? '') === 'secturi' && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2, 4], true))) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        if ((string) ($contextoUsuario['active_group'] ?? '') === 'secturi' && (int) ($contextoUsuario['group_role'] ?? 0) === 2) {
            $modo = 'consulta';
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['hubTitle'] = 'Centro de Acceso SECTURI';
        $data['hubSubtitle'] = $modo === 'consulta'
            ? 'Consulta institucional SECTURI con autorización para aprobar o rechazar solicitudes de folio.'
            : 'Acceso institucional SECTURI con capacidades operativas equivalentes al perfil TI.';
        $data['inicioModoConsulta'] = $modo === 'consulta';
        $data['contentView'] = 'secciones/vPerfilSecturi';
        $this->_renderView($data);
    }

    private function renderPerfilCatalogoHub(string $grupo, string $modo = 'admin')
    {
        $session = \Config\Services::session();
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

        $db = \Config\Database::connect();
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
            ? 'Vista de consulta para revisar solicitudes de folio y perfiles visibles del catálogo ' . $cfg['label'] . '.'
            : 'Panel operativo para solicitar folios del catálogo ' . $cfg['label'] . '.';
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
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'total' => 0, 'rows' => [], 'message' => 'catálogo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'total' => 0, 'rows' => [], 'message' => 'No tienes permisos para consultar solicitudes.']);
        }

        $db = \Config\Database::connect();
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
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'catálogo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para consultar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no v?lida.']);
        }

        $db = \Config\Database::connect();
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
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $usuario = '';

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'catálogo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'Solo un administrador del catálogo puede enviar solicitudes.']);
        }
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['ok' => false, 'message' => 'M?todo no permitido.']);
        }

        if ($this->isSolicitudFolioAltaPayload($this->request->getPost())) {
            return $this->guardarSolicitudFolioDesdeAlta($grupo, $cfg);
        }

        $idPerfilSolicitado = (int) ($this->request->getPost('id_perfil_solicitado') ?? 0);
        $nombre = trim((string) ($this->request->getPost('nombre') ?? ''));
        $primerApellido = trim((string) ($this->request->getPost('primer_apellido') ?? ''));
        $segundoApellido = trim((string) ($this->request->getPost('segundo_apellido') ?? ''));
        $correo = trim((string) ($this->request->getPost('correo') ?? ''));
        $beneficios = trim((string) ($this->request->getPost('beneficios') ?? 'ninguno'));
        $observaciones = trim((string) ($this->request->getPost('observaciones') ?? ''));

        $nombre = function_exists('mb_strtoupper') ? mb_strtoupper($nombre, 'UTF-8') : strtoupper($nombre);
        $primerApellido = function_exists('mb_strtoupper') ? mb_strtoupper($primerApellido, 'UTF-8') : strtoupper($primerApellido);
        $segundoApellido = function_exists('mb_strtoupper') ? mb_strtoupper($segundoApellido, 'UTF-8') : strtoupper($segundoApellido);
        $correo = function_exists('mb_strtolower') ? mb_strtolower($correo, 'UTF-8') : strtolower($correo);

        if ($idPerfilSolicitado <= 0 || $nombre === '' || $primerApellido === '') {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Completa los campos obligatorios.']);
        }

        $db = \Config\Database::connect();
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
            $detalleSolicitud[] = 'Hospedaje: sí­Ã‚Â­';
        }
        if (in_array($beneficiosKey, ['alimentos', 'ambos'], true)) {
            $detalleSolicitud[] = 'Alimentos: sí­Ã‚Â­';
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
            'id_proveedor' => 0,
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
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $cfg = $this->getSolicitudCatalogoConfig($grupo);
        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);

        if (empty($cfg)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'catálogo no v?lido.']);
        }
        if (empty($tiUsuario) && (!$esGrupo || !in_array($rolGrupo, [1, 2, 4], true))) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para cancelar solicitudes.']);
        }

        $idSolicitud = (int) ($this->request->getPost('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no v?lida.']);
        }

        $db = \Config\Database::connect();
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


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
            $idEstablecimiento = $datosProveedor->data[0]->id_establecimiento;
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
                'prefix' => 'ACTIVAVIONESFIC/REPORTES/HOSPEDAJE',
            ];
        }

        return [
            'tipo' => 'ventas',
            'label' => 'reporte de ventas',
            'prefix' => 'ACTIVAVIONESFIC/REPORTES/VENTAS',
        ];
    }

    public function enviarFacturaProveedor()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        if (empty($contextoUsuario['is_provider_flow']) && empty($session->get('id_proveedor'))) {
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

        $dashboard = $this->buildProviderDashboardData((int) $session->get('id_usuario'));
        $establecimientosPermitidos = array_map(static function ($item): int {
            $row = is_object($item) ? get_object_vars($item) : (array) $item;
            return (int) ($row['id_establecimiento'] ?? 0);
        }, is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : []);

        if (!in_array($idEstablecimiento, $establecimientosPermitidos, true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'El establecimiento no pertenece al proveedor en sesion.',
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
        $prefix = 'ACTIVAVIONESFIC/FACTURAS';
        $xmlUrl = $this->uploadFileToS3($xmlPath, $prefix . '/' . $xmlName, 'application/xml');
        $pdfUrl = $this->uploadFileToS3($pdfPath, $prefix . '/' . $pdfName, 'application/pdf');
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
                'respuesta' => 'Los archivos subieron a S3, pero no se pudo guardar la factura.',
            ]);
        }

        return $this->response->setJSON([
            'error' => false,
            'respuesta' => 'Factura enviada correctamente.',
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

        $writer->addRow(WriterEntityFactory::createRowFromArray(['SECRETARÍA DE TURISMO E IDENTIDAD', '', '', '', '', ''], $titleStyle));
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
            return $this->response->setStatusCode(401)->setBody('SesiÃ³n invÃ¡lida.');
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

        $idPartida = trim((string) ($row['id_partida_usuario'] ?? ''));
        if ($idPartida !== '') {
            return $idPartida;
        }

        return 'Sin partida';
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
        $accent = (string) ($layout['accent'] ?? '#4b5563');
        $accentSoft = (string) ($layout['accent_soft'] ?? '#e5e7eb');

        // Agrupar por orden de pago para mantener el orden cronológico
        $rowsByOrdenPago = [];
        foreach ($rows as $row) {
            $ordenPago = $this->resolveReporteVentasOrdenPago($row);
            $rowsByOrdenPago[$ordenPago][] = $row;
        }

        ksort($rowsByOrdenPago, SORT_NATURAL);

        // Aplanar las filas manteniendo el orden de los grupos
        $flattenedRows = [];
        foreach ($rowsByOrdenPago as $ordenPago => $ordenRows) {
            // Ordenar por fecha dentro de cada grupo
            usort($ordenRows, static function ($a, $b) {
                $fechaA = strtotime((string) ($a['fec_reg'] ?? $a['fecha_respuesta'] ?? '')) ?: 0;
                $fechaB = strtotime((string) ($b['fec_reg'] ?? $b['fecha_respuesta'] ?? '')) ?: 0;
                return $fechaA <=> $fechaB;
            });
            foreach ($ordenRows as $row) {
                $flattenedRows[] = $row;
            }
        }

        // Construir HTML con codificación UTF-8 correcta
        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { 
                        font-family: DejaVu Sans, Arial, sans-serif; 
                        color: #172033; 
                        padding: 15px 10px;
                    }
                    .header { 
                        border-bottom: 2px solid ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . '; 
                        padding-bottom: 15px; 
                        margin-bottom: 18px; 
                        text-align: center;
                    }
                    .title-main { 
                        font-size: 22px; 
                        font-weight: bold; 
                        color: #000000; 
                    }
                    .title-sub { 
                        font-size: 18px; 
                        font-weight: bold; 
                        color: #000000; 
                        margin-top: 2px;
                    }
                    .subtitle { 
                        font-size: 14px; 
                        color: #475569; 
                        margin-top: 6px; 
                    }
                    .subtitle-bold { 
                        font-size: 14px; 
                        font-weight: bold; 
                        color: #475569; 
                        margin-top: 6px; 
                    }
                    .period { 
                        text-align: center; 
                        font-size: 12pt; 
                        margin-top: 14px; 
                        margin-bottom: 0;
                        color: #475569;
                    }
                    .table-container {
                        margin-top: 14px;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        font-size: 8.5pt;
                    }
                    th, td { 
                        border: 1px solid #000000; 
                        padding: 4px 6px; 
                        vertical-align: middle; 
                    }
                    th { 
                        background: #bbbbbb; 
                        color: #000000; 
                        text-align: left; 
                        font-weight: bold;
                    }
                    .money { 
                        text-align: right; 
                        font-weight: bold; 
                    }
                    .empty { 
                        padding: 12px; 
                        text-align: center; 
                        border: 1px solid #d1d5db; 
                        background: #f9fafb; 
                    }
                    .spacer { 
                        height: 6px; 
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title-main">SECRETARÍA DE TURISMO E IDENTIDAD</div>
                    <div class="title-sub">54 FESTIVAL INTERNACIONAL CERVANTINO</div>
                    <div class="title-sub">' . htmlspecialchars(strtoupper($titulo), ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="subtitle">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="subtitle-bold">' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="period">' . htmlspecialchars($periodoLabel, ENT_QUOTES, 'UTF-8') . '</div>
                </div>';

        if (empty($flattenedRows)) {
            $html .= '<div class="empty">Sin consumos facturados</div>';
        } else {
            $html .= '
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Orden Pago</th>
                                <th>Fecha</th>
                                <th>' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</th>
                                <th>Partida</th>
                                <th>Item</th>
                                <th>Transacción</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>';

            // Renderizar todas las filas sin totales por orden de pago
            foreach ($flattenedRows as $row) {
                $importe = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
                $ordenPago = $this->resolveReporteVentasOrdenPago($row);
                $fecha = $this->formatReporteVentasFecha($row['fec_reg'] ?? $row['fecha_respuesta'] ?? '');
                $establecimiento = (string) ($row['dsc_establecimiento'] ?? '');
                $partida = $this->resolveReporteVentasPartida($row);
                $transaccion = (string) ($row['id_solicitud_pago'] ?? '');

                $html .= '<tr>
                    <td>' . htmlspecialchars($ordenPago, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($establecimiento, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($partida, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>Consumo</td>
                    <td>' . htmlspecialchars($transaccion, ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="money">$ ' . number_format($importe, 2, '.', ',') . '</td>
                </tr>';
            }

            $html .= '
                        </tbody>
                    </table>
                </div>';
        }

        $html .= '
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
        $payload = $this->buildReporteVentasProveedorExportPayload();
        if ($payload === null) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar el reporte de ventas.');
        }

        $rows = $payload['rows'];
        $periodoLabel = $this->buildReporteVentasPeriodoLabel($rows);
        $layout = $this->resolveReporteVentasLayout($payload['dashboard'], (int) $payload['id_establecimiento']);
        $filename = 'reporte_consumos_facturados_' . ($layout['slug'] ?? 'general') . '_' . ($payload['id_establecimiento'] > 0 ? $payload['id_establecimiento'] : 'general') . '.pdf';

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
            $mpdf->WriteHTML($this->buildReporteVentasProveedorPdfHtmlHomologado($rows, $periodoLabel, $layout));
            $salida = $this->request->getGet('download') ? 'D' : 'I';
            $mpdf->Output($filename, $salida);
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF de reporte de ventas proveedor: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el PDF solicitado.');
        }

        exit;
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
        $etiquetaEstablecimiento = (string) ($layout['etiqueta_establecimiento'] ?? 'Establecimiento');
        $accent = (string) ($layout['accent'] ?? '#1d4ed8');
        $accentSoft = (string) ($layout['accent_soft'] ?? '#dbeafe');

        // Agrupar por orden de pago para mantener el orden, pero sin mostrar totales
        $rowsByOrdenPago = [];
        foreach ($rows as $row) {
            $ordenPago = $this->resolveReporteVentasOrdenPago($row);
            $rowsByOrdenPago[$ordenPago][] = $row;
        }

        ksort($rowsByOrdenPago, SORT_NATURAL);

        // Aplanar las filas manteniendo el orden de los grupos
        $flattenedRows = [];
        foreach ($rowsByOrdenPago as $ordenPago => $ordenRows) {
            usort($ordenRows, static function ($a, $b) {
                $fechaA = strtotime((string) ($a['fec_reg'] ?? $a['fecha_respuesta'] ?? '')) ?: 0;
                $fechaB = strtotime((string) ($b['fec_reg'] ?? $b['fecha_respuesta'] ?? '')) ?: 0;
                return $fechaA <=> $fechaB;
            });
            foreach ($ordenRows as $row) {
                $flattenedRows[] = $row;
            }
        }

        // Construir HTML con codificación UTF-8 correcta
        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { 
                        font-family: DejaVu Sans, Arial, sans-serif; 
                        color: #172033; 
                        padding: 20px;
                    }
                    .header { 
                        border-bottom: 2px solid ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . '; 
                        padding-bottom: 15px; 
                        margin-bottom: 18px; 
                        text-align: center;
                    }
                    .title-main { 
                        font-size: 22px; 
                        font-weight: bold; 
                        color: #000000; 
                    }
                    .title-sub { 
                        font-size: 18px; 
                        font-weight: bold; 
                        color: #000000; 
                        margin-top: 2px;
                    }
                    .subtitle { 
                        font-size: 14px; 
                        color: #475569; 
                        margin-top: 6px; 
                    }
                    .subtitle-bold { 
                        font-size: 14px; 
                        font-weight: bold; 
                        color: #475569; 
                        margin-top: 6px; 
                    }
                    .period { 
                        text-align: center; 
                        font-size: 12pt; 
                        margin-top: 14px; 
                        margin-bottom: 0;
                        color: #475569;
                    }
                    .table-container {
                        margin-top: 14px;
                    }
                    table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        font-size: 8.8pt;
                    }
                    th, td { 
                        border: 1px solid #000000; 
                        padding: 5px 6px; 
                        vertical-align: top; 
                    }
                    th { 
                        background: #bbbbbb; 
                        color: #000000; 
                        text-align: left; 
                        font-weight: bold;
                    }
                    .money { 
                        text-align: right; 
                        font-weight: bold; 
                    }
                    .empty { 
                        padding: 12px; 
                        text-align: center; 
                        border: 1px solid #d1d5db; 
                        background: #f9fafb; 
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <div class="title-main">SECRETARÍA DE TURISMO E IDENTIDAD</div>
                    <div class="title-sub">54 FESTIVAL INTERNACIONAL CERVANTINO</div>
                    <div class="title-sub">' . htmlspecialchars(strtoupper($titulo), ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="subtitle">' . htmlspecialchars($subtitulo, ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="subtitle-bold">' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</div>
                    <div class="period">' . htmlspecialchars($periodoLabel, ENT_QUOTES, 'UTF-8') . '</div>
                </div>';

        if (empty($flattenedRows)) {
            $html .= '<div class="empty">Sin consumos facturados</div>';
        } else {
            $html .= '
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Orden Pago</th>
                                <th>Fecha</th>
                                <th>' . htmlspecialchars($etiquetaEstablecimiento, ENT_QUOTES, 'UTF-8') . '</th>
                                <th>Partida</th>
                                <th>Item</th>
                                <th>Transacción</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>';

            // Renderizar todas las filas sin totales
            foreach ($flattenedRows as $row) {
                $importe = (float) ($row['monto_total'] ?? $row['monto_solicitado'] ?? 0);
                $ordenPago = $this->resolveReporteVentasOrdenPago($row);
                
                $html .= '<tr>
                    <td>' . htmlspecialchars($ordenPago, ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($this->formatReporteVentasFecha($row['fec_reg'] ?? $row['fecha_respuesta'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars((string) ($row['dsc_establecimiento'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($this->resolveReporteVentasPartida($row), ENT_QUOTES, 'UTF-8') . '</td>
                    <td>Consumo</td>
                    <td>' . htmlspecialchars((string) ($row['id_solicitud_pago'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                    <td class="money">$ ' . number_format($importe, 2, '.', ',') . '</td>
                </tr>';
            }

            $html .= '
                        </tbody>
                    </table>
                </div>';
        }

        $html .= '
            </body>
            </html>';

        return $html;
    }

    private function buildHospedajeReporteExportPayload(): ?array
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());

        $usuarioAutorizado = $this->resolveSecturiDashboardUsuario();
        $puedeExportarHospedaje = !empty($usuarioAutorizado)
            || !empty($session->get('id_proveedor'))
            || !empty($contextoUsuario['is_provider_flow'])
            || !empty($contextoUsuario['is_recepcion_flow'])
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
        $idEstablecimiento = (int) ($usuarioRow['id_establecimiento'] ?? 0);
        if ($idEstablecimiento <= 0) {
            $idEstablecimiento = (int) ($this->request->getGet('id_establecimiento') ?? 0);
        }
        if ($idEstablecimiento <= 0) {
            return null;
        }

        $hospedaje = $Mglobal->getTabla([
            'tabla' => 'vw_usuario',
            'where' => [
                'visible' => 1,
                'id_establecimiento_hotel' => $idEstablecimiento,
            ],
            'order' => 'fecha_check_in ASC, id_usuario ASC',
        ]);

        $rows = [];
        if (!empty($hospedaje->data)) {
            foreach ($hospedaje->data as $row) {
                $rows[] = is_object($row) ? get_object_vars($row) : (array) $row;
            }
        }

        $fechas = [];
        $totalTarifa = 0.0;
        $checkInCount = 0;
        $checkOutCount = 0;
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
        }

        sort($fechas);
        $periodoLabel = empty($fechas)
            ? 'Sin registros de hospedaje'
            : 'Periodo del ' . $this->formatReporteVentasFecha((string) reset($fechas)) . ' al ' . $this->formatReporteVentasFecha((string) end($fechas));

        return [
            'titulo' => 'Reporte de hospedaje',
            'subtitulo' => trim((string) ($usuarioRow['dsc_establecimiento'] ?? '')) !== ''
                ? trim((string) ($usuarioRow['dsc_establecimiento'] ?? ''))
                : 'Establecimiento',
            'id_establecimiento' => $idEstablecimiento,
            'establecimiento' => trim((string) ($usuarioRow['dsc_establecimiento'] ?? '')),
            'periodo_label' => $periodoLabel,
            'rows' => $rows,
            'resumen' => [
                'total_registros' => count($rows),
                'check_in' => $checkInCount,
                'check_out' => $checkOutCount,
                'total_tarifa' => $totalTarifa,
            ],
        ];
    }

    public function Hospedaje()
    {
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

        if (empty($tiUsuario)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['contentView'] = 'secciones/vHospedaje';
        $this->_renderView($data);
    }

    public function Cajero()
    {
        $usuarioDashboard = $this->resolveSecturiDashboardUsuario();

        if (empty($usuarioDashboard)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = [];
        $data['scripts'] = ['principal', 'agregar'];
        $data['cajeroAccesoTiInicio'] = true;
        $data['cajeroSoloConsulta'] = empty($this->resolveSecturiAdminUsuario());
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
        $data['facturasListadoUrl'] = base_url('index.php/Inicio/getFacturasFic');
        $data['facturasArchivoUrl'] = base_url('index.php/Inicio/verFacturaProveedorArchivo');
        $data['contentView'] = 'secciones/vFacturasFic';
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
            $idEstatus = (int) ($row['id_estatus'] ?? 0);
            return [
                'id_factura' => (int) ($row['id_factura'] ?? 0),
                'id_establecimiento' => (int) ($row['id_establecimiento'] ?? 0),
                'establecimiento' => (string) ($row['dsc_establecimiento'] ?? 'Sin establecimiento'),
                'no_proveedor' => (string) ($row['no_proveedor'] ?? ''),
                'proveedor' => (string) ($row['razon_social'] ?? 'Sin proveedor'),
                'rfc' => (string) ($row['rfc'] ?? ''),
                'id_estatus' => $idEstatus,
                'estatus' => $idEstatus === 1 ? 'Registrada' : 'Estatus ' . $idEstatus,
                'fec_reg' => (string) ($row['fec_reg'] ?? ''),
                'usu_reg' => (int) ($row['usu_reg'] ?? 0),
                'tiene_xml' => trim((string) ($row['xml'] ?? '')) !== '' ? 1 : 0,
                'tiene_pdf' => trim((string) ($row['pdf'] ?? '')) !== '' ? 1 : 0,
            ];
        }, $rows);

        return $this->response->setJSON([
            'total' => count($mapped),
            'rows' => $mapped,
        ]);
    }

    public function verFacturaProveedorArchivo()
    {
        if (empty($this->resolveSecturiDashboardUsuario())) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para consultar facturas.');
        }

        $idFactura = (int) ($this->request->getGet('id_factura') ?? 0);
        $tipo = strtolower(trim((string) ($this->request->getGet('tipo') ?? '')));
        if ($idFactura <= 0 || !in_array($tipo, ['xml', 'pdf'], true)) {
            return $this->response->setStatusCode(422)->setBody('Solicitud invalida.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('facturas')) {
            return $this->response->setStatusCode(404)->setBody('No existe la tabla facturas.');
        }

        $factura = $db->table('facturas')
            ->select('id_factura, xml, pdf, visible')
            ->where('id_factura', $idFactura)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($factura)) {
            return $this->response->setStatusCode(404)->setBody('Factura no encontrada.');
        }

        $archivo = trim((string) ($factura[$tipo] ?? ''));
        if ($archivo === '') {
            return $this->response->setStatusCode(404)->setBody('Archivo no disponible.');
        }

        $url = $this->buildS3PresignedGetUrl($archivo, 300);
        if ($url === '') {
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el acceso temporal al archivo.');
        }

        return redirect()->to($url);
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

        if (empty($tiUsuario) && (!$esGrupo || $rolGrupo !== 1)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $idSolicitudEdicion = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        $esRevisionAdministrativa = $idSolicitudEdicion > 0
            && strtolower(trim((string) ($this->request->getGet('origen') ?? ''))) === 'revision';
        $backUrl = $grupo === 'fic'
            ? base_url('index.php/Inicio/PerfilFic')
            : base_url('index.php/Inicio/' . ucfirst($grupo));
        if ($esRevisionAdministrativa && (string) ($contextoUsuario['active_group'] ?? '') === 'secturi') {
            $backUrl = base_url('index.php/Inicio/SolicitudesUsuarioFic');
        }

        $saveUrl = $grupo === 'fic'
            ? base_url('index.php/Inicio/guardarSolicitudUsuarioFicPerfil')
            : base_url('index.php/Inicio/guardarSolicitudUsuario' . ucfirst($grupo) . 'Perfil');

        $data = $this->buildAltaUsuarioFormData($contextoUsuario, $resolver);
        $data['scripts'] = ['principal', 'agregar'];
        $data['contentView'] = 'secciones/vAltaUsuario';
        $data['idUsuarioEditar'] = 0;
        $data['modoAltaProveedor'] = false;
        $data['modoSolicitudFolio'] = true;
        $data['solicitudFolioGrupo'] = strtoupper($grupo);
        $data['solicitudFolioId'] = $idSolicitudEdicion;
        $data['regresarUrl'] = $backUrl;
        $data['saveUrl'] = $saveUrl;
        $data['solicitudDetalleUrl'] = base_url('index.php/Inicio/getSolicitudFolioEditable');
        $data['solicitudAlta'] = [
            'grupo' => $grupo,
            'title' => 'Solicitud de folio de usuario',
            'subtitle' => 'Captura los datos del usuario y el perfil solicitado dentro del catálogo ' . strtoupper($grupo) . '.',
            'back_url' => $backUrl,
            'save_url' => $saveUrl,
            'detail_url' => base_url('index.php/Inicio/getSolicitudFolioEditable'),
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

    public function getSugerenciasFolioInstitucional()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $grupo = strtolower(trim((string) ($this->request->getGet('grupo') ?? '')));

        if ($grupo === '') {
            $grupo = strtolower((string) ($contextoUsuario['active_group'] ?? ''));
        }

        if (!in_array($grupo, ['fic', 'secul', 'ug'], true)) {
            return $this->response->setJSON([
                'ok' => true,
                'data' => [
                    'sugerencias' => [],
                    'mensaje' => 'Sin folio previo para sugerir.',
                ],
            ]);
        }

        $esGrupo = (string) ($contextoUsuario['active_group'] ?? '') === $grupo;
        $rolGrupo = (int) ($contextoUsuario['group_role'] ?? 0);
        if (empty($tiUsuario) && (!$esGrupo || $rolGrupo !== 1)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No tienes permisos para consultar sugerencias de folio.',
            ]);
        }

        $db = \Config\Database::connect();
        $pares = $this->collectFolioInstitucionalPairs($db);
        $ultimo = $this->resolveUltimoFolioInstitucional($pares);

        if (empty($ultimo)) {
            return $this->response->setJSON([
                'ok' => true,
                'data' => [
                    'sugerencias' => [],
                    'mensaje' => 'Sin folio previo para sugerir.',
                ],
            ]);
        }

        $sugerencias = $this->buildSugerenciasFolioInstitucional($ultimo, $pares);

        return $this->response->setJSON([
            'ok' => true,
            'data' => [
                'ultimo' => $ultimo,
                'ultimo_label' => $ultimo['folio'] . $ultimo['sub_folio'],
                'sugerencias' => $sugerencias,
                'mensaje' => empty($sugerencias) ? 'Sin folio previo para sugerir.' : '',
            ],
        ]);
    }

    private function collectFolioInstitucionalPairs($db, int $excludeSolicitudId = 0): array
    {
        $pares = [];
        $usuarioRows = $db->table('usuario')
            ->select('folio, sub_folio')
            ->where('visible', 1)
            ->where('folio IS NOT NULL', null, false)
            ->where('folio <>', '')
            ->get()
            ->getResultArray();

        foreach ($usuarioRows as $row) {
            $this->appendFolioInstitucionalPair($pares, $row['folio'] ?? '', $row['sub_folio'] ?? '');
        }

        $solicitudesBuilder = $db->table('solicitud_usuario')
            ->select('comentario_ti')
            ->where('visible', 1)
            ->where('estatus', 'pendiente')
            ->whereIn('tipo_solicitud', ['alta_usuario_fic', 'alta_usuario_secul', 'alta_usuario_ug']);
        if ($excludeSolicitudId > 0) {
            $solicitudesBuilder->where('id_solicitud_usuario !=', $excludeSolicitudId);
        }
        $solicitudes = $solicitudesBuilder->get()
            ->getResultArray();

        foreach ($solicitudes as $solicitud) {
            $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($solicitud['comentario_ti'] ?? ''));
            $payload = is_array($payloadInfo['payload'] ?? null) ? $payloadInfo['payload'] : [];
            if (!empty($payload)) {
                $payload = $this->synchronizeSolicitudFolioFields($payload);
                $this->appendFolioInstitucionalPair(
                    $pares,
                    $payload['folio'] ?? $payload['folio_ui'] ?? $payload['folio_grupo'] ?? '',
                    $payload['sub_folio'] ?? $payload['subf_ui'] ?? ''
                );
            }
        }

        return $pares;
    }

    private function appendFolioInstitucionalPair(array &$pares, $folio, $subFolio): void
    {
        $folio = preg_replace('/\D+/', '', (string) $folio);
        $subFolio = strtoupper(trim((string) $subFolio));
        $subFolio = preg_replace('/[^A-Z]/', '', $subFolio);

        if ($folio === '') {
            return;
        }

        $pares[] = [
            'folio' => (int) $folio,
            'sub_folio' => $subFolio !== '' ? substr($subFolio, 0, 1) : '',
        ];
    }

    private function resolveUltimoFolioInstitucional(array $pares): array
    {
        $ultimo = [];
        foreach ($pares as $par) {
            $folio = (int) ($par['folio'] ?? 0);
            if ($folio <= 0) {
                continue;
            }

            $subFolio = strtoupper((string) ($par['sub_folio'] ?? ''));
            $subOrden = preg_match('/^[A-Z]$/', $subFolio) ? (ord($subFolio) - ord('A') + 1) : 0;
            $ultimoOrden = preg_match('/^[A-Z]$/', (string) ($ultimo['sub_folio'] ?? '')) ? (ord($ultimo['sub_folio']) - ord('A') + 1) : 0;

            if (empty($ultimo) || $folio > (int) $ultimo['folio'] || ($folio === (int) $ultimo['folio'] && $subOrden > $ultimoOrden)) {
                $ultimo = [
                    'folio' => $folio,
                    'sub_folio' => $subFolio,
                ];
            }
        }

        return $ultimo;
    }

    private function buildSugerenciasFolioInstitucional(array $ultimo, array $pares): array
    {
        $folio = (int) ($ultimo['folio'] ?? 0);
        $subFolio = strtoupper((string) ($ultimo['sub_folio'] ?? ''));
        $usados = [];
        foreach ($pares as $par) {
            $usados[(int) ($par['folio'] ?? 0) . '|' . strtoupper((string) ($par['sub_folio'] ?? ''))] = true;
        }

        $sugerencias = [];
        if ($folio <= 0) {
            return $sugerencias;
        }

        if (!preg_match('/^[A-Z]$/', $subFolio)) {
            $continuarSub = 'A';
        } elseif ($subFolio !== 'Z') {
            $continuarSub = chr(ord($subFolio) + 1);
        } else {
            $continuarSub = '';
        }

        if ($continuarSub !== '') {
            while ($continuarSub <= 'Z' && isset($usados[$folio . '|' . $continuarSub])) {
                $continuarSub = chr(ord($continuarSub) + 1);
            }

            if ($continuarSub <= 'Z') {
                $sugerencias[] = [
                    'tipo' => 'continuar',
                    'label' => 'Continuar folio: ' . $folio . $continuarSub,
                    'folio' => (string) $folio,
                    'sub_folio' => $continuarSub,
                ];
            }
        }

        $nuevoFolio = $folio + 1;
        $intentos = 0;
        while (isset($usados[$nuevoFolio . '|A']) && $intentos < 1000) {
            $nuevoFolio++;
            $intentos++;
        }

        $sugerencias[] = [
            'tipo' => 'nuevo',
            'label' => 'Nuevo folio: ' . $nuevoFolio . 'A',
            'folio' => (string) $nuevoFolio,
            'sub_folio' => 'A',
        ];

        return $sugerencias;
    }

    private function resolveSiguienteFolioInstitucionalDisponible(array $pares, int $folio, string $subFolio): array
    {
        $usados = [];
        foreach ($pares as $par) {
            $folioExistente = (int) ($par['folio'] ?? 0);
            if ($folioExistente <= 0) {
                continue;
            }

            $subExistente = strtoupper(trim((string) ($par['sub_folio'] ?? '')));
            $subExistente = preg_replace('/[^A-Z]/', '', $subExistente);
            $usados[$folioExistente . '|' . ($subExistente !== '' ? substr($subExistente, 0, 1) : '')] = true;
        }

        $folio = max(1, (int) $folio);
        $subFolio = strtoupper(trim((string) $subFolio));
        $subFolio = preg_replace('/[^A-Z]/', '', $subFolio);
        $subFolio = $subFolio !== '' ? substr($subFolio, 0, 1) : 'A';

        $intentos = 0;
        while ($intentos < 1000) {
            $clave = $folio . '|' . $subFolio;
            if (!isset($usados[$clave])) {
                return [
                    'folio' => $folio,
                    'sub_folio' => $subFolio,
                ];
            }

            if ($subFolio !== 'Z') {
                $subFolio = chr(ord($subFolio) + 1);
            } else {
                $folio++;
                $subFolio = 'A';
            }

            $intentos++;
        }

        return [
            'folio' => $folio,
            'sub_folio' => $subFolio,
        ];
    }

    public function getNotificacionesUsuario()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes una sesión válida.']);
        }

        try {
            $limit = max(1, min(20, (int) ($this->request->getGet('limit') ?? 10)));
            $result = $this->runNotificationDbWithRetry(function ($db) use ($idUsuario, $limit) {
                $rows = $db->table('notification')
                    ->where('visible', 1)
                    ->where('id_usuario', $idUsuario)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->get()
                    ->getResultArray();

                $unread = $db->table('notification')
                    ->where('visible', 1)
                    ->where('id_usuario', $idUsuario)
                    ->where('read_at IS NULL', null, false)
                    ->countAllResults();

                return [
                    'rows' => $rows,
                    'unread' => $unread,
                ];
            }, 'getNotificacionesUsuario');

            return $this->response->setJSON([
                'ok' => true,
                'total' => count($result['rows']),
                'unread' => (int) $result['unread'],
                'rows' => array_map(function (array $row): array {
                    return $this->mapNotificationRow($row);
                }, $result['rows']),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => $e->getMessage() ?: 'Error consultando notificaciones.',
            ]);
        }
    }

    public function getNotificacionesNoLeidas()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes una sesión válida.']);
        }

        try {
            $unread = $this->runNotificationDbWithRetry(function ($db) use ($idUsuario) {
                return $db->table('notification')
                    ->where('visible', 1)
                    ->where('id_usuario', $idUsuario)
                    ->where('read_at IS NULL', null, false)
                    ->countAllResults();
            }, 'getNotificacionesNoLeidas');

            return $this->response->setJSON([
                'ok' => true,
                'unread' => (int) $unread,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => $e->getMessage() ?: 'Error consultando notificaciones no leidas.',
            ]);
        }
    }

    public function marcarNotificacionLeida()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes una sesión válida.']);
        }

        $idNotification = (int) ($this->request->getGet('id_notification') ?? $this->request->getPost('id_notification') ?? 0);
        if ($idNotification <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Notificación no válida.']);
        }

        $db = \Config\Database::connect(null, false);
        $notification = $db->table('notification')
            ->where('visible', 1)
            ->where('id_notification', $idNotification)
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getRowArray();

        if (empty($notification)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'No se encontró la notificación.']);
        }

        $db->table('notification')->where('id_notification', $idNotification)->update([
            'read_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['ok' => true, 'message' => 'Notificación marcada como leída.']);
    }

    public function resolverUrlEdicionSolicitud()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) ($session->get('id_usuario') ?? 0);
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes una sesión válida.']);
        }

        $grupo = strtolower(trim((string) ($this->request->getGet('grupo') ?? $this->request->getPost('grupo') ?? '')));
        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? $this->request->getPost('id_solicitud_usuario') ?? 0);
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        if (!in_array($grupo, ['fic', 'secul', 'ug'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Grupo no válido.']);
        }

        return $this->response->setJSON([
            'ok' => true,
            'grupo' => $grupo,
            'id_solicitud_usuario' => $idSolicitud,
            'url' => $this->buildSolicitudFolioEditUrl($grupo, $idSolicitud),
        ]);
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
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, COALESCE(cf.dsc_perfil, cs.des_perfil, cu.dsc_perfil) AS perfil_solicitado')
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_fic"', 'left')
            ->join('cat_secul cs', 'cs.id_secul_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_secul"', 'left')
            ->join('cat_ug cu', 'cu.id_ug_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_ug"', 'left')
            ->where('su.visible', 1)
            ->whereIn('su.tipo_solicitud', empty($tiUsuario) ? ['alta_usuario_fic'] : ['alta_usuario_fic', 'alta_usuario_secul', 'alta_usuario_ug']);

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
                ->orLike('cs.des_perfil', $search)
                ->orLike('cu.dsc_perfil', $search)
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

    public function getSolicitudFolioEditable()
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $idSolicitud = (int) ($this->request->getGet('id_solicitud_usuario') ?? 0);
        $grupo = strtolower(trim((string) ($this->request->getGet('grupo') ?? '')));

        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        $db = \Config\Database::connect(null, false);
        $builder = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, su.usu_reg, COALESCE(cf.dsc_perfil, cs.des_perfil, cu.dsc_perfil) AS perfil_solicitado')
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_fic"', 'left')
            ->join('cat_secul cs', 'cs.id_secul_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_secul"', 'left')
            ->join('cat_ug cu', 'cu.id_ug_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_ug"', 'left')
            ->where('su.id_solicitud_usuario', $idSolicitud)
            ->where('su.visible', 1)
            ->whereIn('su.tipo_solicitud', ['alta_usuario_fic', 'alta_usuario_secul', 'alta_usuario_ug']);

        if ($grupo !== '' && in_array($grupo, ['fic', 'secul', 'ug'], true)) {
            $builder->where('su.tipo_solicitud', 'alta_usuario_' . $grupo);
        }

        $row = $builder->get()->getRowArray();
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'No se encontró la solicitud.']);
        }

        $grupoRow = str_replace('alta_usuario_', '', (string) ($row['tipo_solicitud'] ?? ''));
        $estatusRow = strtolower(trim((string) ($row['estatus'] ?? '')));
        $esPropietario = (int) ($row['usu_reg'] ?? 0) === $idSesionUsuario;
        $esAdminDelGrupo = empty($tiUsuario)
            && (string) ($contextoUsuario['active_group'] ?? '') === $grupoRow
            && (int) ($contextoUsuario['group_role'] ?? 0) === 1;

        if (empty($tiUsuario) && !$esPropietario && !$esAdminDelGrupo) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => false, 'message' => 'No tienes permisos para consultar esta solicitud.']);
        }

        if ($grupo !== '' && $grupo !== $grupoRow) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'La solicitud no corresponde al grupo solicitado.']);
        }

        if (!in_array((string) ($row['estatus'] ?? ''), ['rechazada', 'pendiente', 'aprobada'], true)) {
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'La solicitud no se puede editar en este estado.']);
        }

        $data = $this->mapSolicitudUsuarioFicPerfilRow($row);
        $data['solicitud_propietario'] = $esPropietario ? 1 : 0;
        $data['grupo_solicitud'] = $grupoRow;

        return $this->response->setJSON(['ok' => true, 'data' => $data]);
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

        if ($this->isSolicitudFolioAltaPayload($this->request->getPost())) {
            return $this->guardarSolicitudFolioDesdeAlta('fic');
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
            $detalleSolicitud[] = 'Hospedaje: sí­';
            $detalleSolicitud[] = 'Partida automática hospedaje: 3390A';
        }
        if (in_array($beneficiosKey, ['alimentos', 'ambos'], true)) {
            $detalleSolicitud[] = 'Alimentos: sí­';
            $detalleSolicitud[] = 'Partida automática alimentos: 3390B';
        }
        if ($categoriaLabel !== '') $detalleSolicitud[] = 'Categorí­a: ' . $categoriaLabel;
        if ($paisLabel !== '') $detalleSolicitud[] = 'Paí­s o región: ' . $paisLabel;
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
        $payloadInfo = $this->decodeSolicitudFolioPayload((string) ($row['comentario_ti'] ?? ''));
        $payloadSolicitud = is_array($payloadInfo['payload'] ?? null)
            ? $this->synchronizeSolicitudFolioFields($payloadInfo['payload'])
            : [];
        $grupoSolicitud = $payloadInfo['grupo'] !== ''
            ? $payloadInfo['grupo']
            : str_replace('alta_usuario_', '', (string) ($row['tipo_solicitud'] ?? 'fic'));
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
            'comentario_ti' => !empty($payloadSolicitud)
                ? $this->buildSolicitudFolioSummary($payloadSolicitud)
                : (string) ($row['comentario_ti'] ?? ''),
            'payload_solicitud' => $payloadSolicitud,
            'tiene_payload_completo' => !empty($payloadSolicitud) ? 1 : 0,
            'catalogo_grupo' => $grupoSolicitud !== '' ? $grupoSolicitud : 'fic',
            'fec_reg' => (string) ($row['fec_reg'] ?? ''),
            'visible' => (int) ($row['visible'] ?? 0),
        ];
    }

    private function mapNotificationRow(array $row): array
    {
        $dataJson = trim((string) ($row['data_json'] ?? ''));
        $data = [];
        if ($dataJson !== '') {
            $decoded = json_decode($dataJson, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $tipo = (string) ($row['tipo'] ?? '');
        $mensaje = (string) ($row['mensaje'] ?? '');
        $grupo = strtolower(trim((string) ($data['grupo'] ?? '')));
        $actionUrl = trim((string) ($data['url'] ?? ''));
        if ($tipo === 'SOLICITUD_ALTA_RECHAZADA') {
            $idSolicitud = (int) ($data['id_solicitud_usuario'] ?? 0);
            if ($grupo !== '' && $idSolicitud > 0) {
                $actionUrl = $this->buildSolicitudFolioEditUrl($grupo, $idSolicitud);
            }
        }

        return [
            'id_notification' => (int) ($row['id_notification'] ?? 0),
            'id_usuario' => (int) ($row['id_usuario'] ?? 0),
            'titulo' => (string) ($row['titulo'] ?? ''),
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'data_json' => $data,
            'action_url' => $actionUrl,
            'visible' => (int) ($row['visible'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'read_at' => (string) ($row['read_at'] ?? ''),
            'is_read' => !empty($row['read_at']) ? 1 : 0,
        ];
    }

    private function buildSolicitudFolioEditUrl(string $grupo, int $idSolicitud): string
    {
        $grupo = strtolower(trim($grupo));
        $idSolicitud = (int) $idSolicitud;
        if (!in_array($grupo, ['fic', 'secul', 'ug'], true) || $idSolicitud <= 0) {
            return '';
        }

        return base_url('index.php/Inicio/SolicitudAlta/' . $grupo) . '?id_solicitud_usuario=' . $idSolicitud;
    }

    private function getNotificationAudienceRecipients(string $grupo): array
    {
        $grupo = strtolower(trim($grupo));
        $fieldMap = [
            'fic' => 'id_fic_perfil',
            'secul' => 'id_secul_perfil',
            'ug' => 'id_ug_perfil',
            'secturi' => 'id_secturi_perfil',
        ];

        if (!isset($fieldMap[$grupo])) {
            return [];
        }

        $db = \Config\Database::connect(null, false);
        $rows = $db->table('usuario')
            ->select('id_usuario')
            ->where('visible', 1)
            ->whereIn($fieldMap[$grupo], [1, 2])
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $row) {
            $idUsuario = (int) ($row['id_usuario'] ?? 0);
            if ($idUsuario > 0) {
                $ids[$idUsuario] = $idUsuario;
            }
        }

        return array_values($ids);
    }

    /**
     * Campos usados por el módulo de notificaciones:
     * notification.id_notification, notification.id_usuario, notification.titulo,
     * notification.mensaje, notification.tipo, notification.data_json,
     * notification.visible, notification.created_at y notification.read_at.
     * En data_json se usan keys como type, grupo, id_solicitud_usuario,
     * tipo_solicitud, estatus, motivo, url y created_at.
     */

    private function isSolicitudFolioAltaPayload(array $post): bool
    {
        return isset($post['perfil_grupo'])
            || isset($post['id_perfil_catalogo'])
            || isset($post['folio_ui'])
            || isset($post['pax_ui']);
    }

    private function guardarSolicitudFolioDesdeAlta(string $grupo, array $cfg = [])
    {
        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $post = $this->request->getPost();
        $db = \Config\Database::connect();
        $idSesionUsuario = (int) ($session->get('id_usuario') ?? 0);
        $idSolicitudEdicion = (int) ($post['id_solicitud_usuario'] ?? 0);
        $tipoSolicitud = $grupo === 'fic' ? 'alta_usuario_fic' : (string) ($cfg['tipo_solicitud'] ?? ('alta_usuario_' . $grupo));
        $idPerfilBase = (int) ($post['id_perfil_catalogo'] ?? $post['id_perfil'] ?? 0);
        if ($idPerfilBase <= 0) {
            $idPerfilBase = $this->getSolicitudPerfilBaseId($grupo);
        }
        $idPerfilSolicitado = $this->resolveSolicitudPerfilGrupo($grupo, $post);
        $usuario = strtolower(trim((string) ($post['usuario'] ?? '')));
        $nombre = trim((string) ($post['nombre'] ?? ''));
        $primerApellido = trim((string) ($post['primer_apellido'] ?? ''));
        $segundoApellido = trim((string) ($post['segundo_apellido'] ?? ''));
        $correo = strtolower(trim((string) ($post['correo'] ?? '')));
        $folioFuente = trim((string) ($post['folio'] ?? ''));
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($post['folio_ui'] ?? ''));
        }
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($post['folio_grupo'] ?? ''));
        }
        $folio = preg_replace('/\D+/', '', $folioFuente);
        $folioGrupo = $folio;
        $subFolio = strtoupper(trim((string) ($post['sub_folio'] ?? $post['subf_ui'] ?? '')));

        if ($idPerfilSolicitado <= 0 || $usuario === '' || $nombre === '' || $primerApellido === '' || $folioGrupo === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Completa perfil, usuario, nombre, primer apellido y folio.',
            ]);
        }

        $solicitudEdicion = [];
        if ($idSolicitudEdicion > 0) {
            $solicitudEdicion = $db->table('solicitud_usuario')
                ->select('id_solicitud_usuario, tipo_solicitud, estatus, visible, usu_reg')
                ->where('id_solicitud_usuario', $idSolicitudEdicion)
                ->where('visible', 1)
                ->where('tipo_solicitud', $tipoSolicitud)
                ->limit(1)
                ->get()
                ->getRowArray();

            $estatusEdicion = strtolower(trim((string) ($solicitudEdicion['estatus'] ?? '')));
            $esPropietario = (int) ($solicitudEdicion['usu_reg'] ?? 0) === $idSesionUsuario;
            $esAdminGrupo = empty($tiUsuario)
                && (string) ($contextoUsuario['active_group'] ?? '') === $grupo
                && in_array((int) ($contextoUsuario['group_role'] ?? 0), [1, 2], true);

            if (empty($solicitudEdicion) || !in_array($estatusEdicion, ['rechazada', 'pendiente'], true)) {
                return $this->response->setStatusCode(409)->setJSON([
                    'ok' => false,
                    'message' => 'La solicitud ya no está pendiente o rechazada para reenviarse.',
                ]);
            }

            if (empty($tiUsuario) && !$esPropietario && !$esAdminGrupo) {
                return $this->response->setStatusCode(403)->setJSON([
                    'ok' => false,
                    'message' => 'No tienes permisos para reenviar esta solicitud.',
                ]);
            }
        }

        $paresInstitucionales = $this->collectFolioInstitucionalPairs($db, $idSolicitudEdicion);
        $folioDisponible = $this->resolveSiguienteFolioInstitucionalDisponible(
            $paresInstitucionales,
            (int) $folioGrupo,
            $subFolio !== '' ? $subFolio : 'A'
        );

        if ((int) $folioGrupo !== (int) ($folioDisponible['folio'] ?? 0) || $subFolio !== (string) ($folioDisponible['sub_folio'] ?? '')) {
            $folioGrupo = (string) ($folioDisponible['folio'] ?? $folioGrupo);
            $folio = $folioGrupo;
            $subFolio = (string) ($folioDisponible['sub_folio'] ?? $subFolio);
        }

        $solicitudUsuarioDuplicada = $db->table('solicitud_usuario')
            ->select('id_solicitud_usuario')
            ->where('visible', 1)
            ->where('estatus', 'pendiente')
            ->where('tipo_solicitud', $tipoSolicitud)
            ->where('usuario', $usuario)
            ->where('id_solicitud_usuario !=', $idSolicitudEdicion)
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!empty($solicitudUsuarioDuplicada)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Ya existe una solicitud pendiente para este usuario.',
            ]);
        }

        $intentosFolio = 0;
        do {
            $solicitudFolioDuplicada = $db->table('solicitud_usuario')
                ->select('id_solicitud_usuario')
                ->where('visible', 1)
                ->where('estatus', 'pendiente')
                ->where('tipo_solicitud', $tipoSolicitud)
                ->where('id_solicitud_usuario !=', $idSolicitudEdicion);
            if ($subFolio !== '') {
                $solicitudFolioDuplicada
                    ->where('comentario_ti LIKE', '%"folio_grupo":"' . $db->escapeLikeString($folioGrupo) . '"%')
                    ->where('comentario_ti LIKE', '%"sub_folio":"' . $db->escapeLikeString($subFolio) . '"%');
            } else {
                $solicitudFolioDuplicada->where('comentario_ti LIKE', '%"folio_grupo":"' . $db->escapeLikeString($folioGrupo) . '"%');
            }
            $duplicada = $solicitudFolioDuplicada
                ->limit(1)
                ->get()
                ->getRowArray();

            if (empty($duplicada)) {
                break;
            }

            $paresInstitucionales[] = [
                'folio' => (int) $folioGrupo,
                'sub_folio' => $subFolio,
            ];
            $folioDisponible = $this->resolveSiguienteFolioInstitucionalDisponible(
                $paresInstitucionales,
                (int) $folioGrupo,
                $subFolio !== '' ? $subFolio : 'A'
            );
            $folioGrupo = (string) ($folioDisponible['folio'] ?? $folioGrupo);
            $folio = $folioGrupo;
            $subFolio = (string) ($folioDisponible['sub_folio'] ?? $subFolio);
            $intentosFolio++;
        } while ($intentosFolio < 1000);

        if (!empty($duplicada)) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'No fue posible encontrar un folio disponible. Intenta nuevamente.',
            ]);
        }

        $payload = $post;
        $payload['grupo_usuario'] = $grupo;
        $payload['id_perfil_catalogo'] = $idPerfilBase;
        $payload['id_perfil_solicitado'] = $idPerfilSolicitado;
        $payload['perfil_grupo'] = $idPerfilSolicitado;
        $payload['id_solicitud_usuario'] = $idSolicitudEdicion > 0 ? $idSolicitudEdicion : '';
        $payload['folio'] = $folio !== '' ? $folio : $folioGrupo;
        $payload['folio_grupo'] = $folioGrupo;
        $payload['sub_folio'] = $subFolio;
        $payload['pax_total'] = (int) ($payload['pax_total'] ?? $payload['pax'] ?? $payload['pax_ui'] ?? 1);
        $payload = $this->normalizeSolicitudFolioPayload($grupo, $payload);

        $comentario = $this->encodeSolicitudFolioPayload($grupo, $payload);
        $fechaAhora = date('Y-m-d H:i:s');
        $persistData = [
            'tipo_solicitud' => $tipoSolicitud,
            'id_proveedor' => 0,
            'id_establecimiento' => (int) ($post['id_establecimiento'] ?? $session->get('id_establecimiento') ?? 0),
            'id_perfil_solicitado' => $idPerfilSolicitado,
            'usuario' => $usuario,
            'nombre' => $nombre,
            'primer_apellido' => $primerApellido,
            'segundo_apellido' => $segundoApellido,
            'correo' => $correo,
            'estatus' => 'pendiente',
            'comentario_ti' => $comentario,
            'id_usuario_creado' => null,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
            'visible' => 1,
        ];

        if (!empty($solicitudEdicion)) {
            $updateOk = $db->table('solicitud_usuario')
                ->where('id_solicitud_usuario', $idSolicitudEdicion)
                ->update($persistData);

            if (!$updateOk) {
                return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'message' => 'No fue posible reenviar la solicitud.']);
            }

            return $this->response->setJSON([
                'ok' => true,
                'message' => $estatusEdicion === 'rechazada'
                    ? 'Solicitud corregida y reenviada correctamente.'
                    : 'Solicitud actualizada correctamente.',
                'data' => ['id_solicitud_usuario' => $idSolicitudEdicion],
            ]);
        }

        $insertOk = $db->table('solicitud_usuario')->insert($persistData + [
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
        ]);

        if (!$insertOk) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => false, 'message' => 'No fue posible guardar la solicitud.']);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Solicitud enviada correctamente.',
            'data' => ['id_solicitud_usuario' => (int) $db->insertID()],
        ]);
    }

    private function encodeSolicitudFolioPayload(string $grupo, array $payload): string
    {
        return "__SOLICITUD_FOLIO_PAYLOAD__\n" . json_encode([
            'grupo' => $grupo,
            'payload' => $payload,
            'summary' => $this->buildSolicitudFolioSummary($payload),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function decodeSolicitudFolioPayload(string $comentario): array
    {
        $comentario = ltrim($comentario);
        $prefix = '__SOLICITUD_FOLIO_PAYLOAD__';
        if (strpos($comentario, $prefix) !== 0) {
            return ['grupo' => '', 'payload' => [], 'summary' => ''];
        }

        $decoded = json_decode(ltrim(substr($comentario, strlen($prefix))), true);
        if (!is_array($decoded)) {
            return ['grupo' => '', 'payload' => [], 'summary' => ''];
        }

        return [
            'grupo' => (string) ($decoded['grupo'] ?? ''),
            'payload' => is_array($decoded['payload'] ?? null) ? $decoded['payload'] : [],
            'summary' => (string) ($decoded['summary'] ?? ''),
        ];
    }

    private function buildSolicitudFolioSummary(array $payload): string
    {
        $lines = [];
        foreach ([
            'perfil_grupo' => 'Grupo',
            'folio_grupo' => 'Folio',
            'sub_folio' => 'Subfolio',
            'pax_total' => 'Pax',
            'tiene_alimentos' => 'Alimentos',
            'tiene_hospedaje' => 'Hospedaje',
            'id_nivel_cliente' => 'Tarifa diaria',
            'id_partida' => 'Partida',
            'fec_vigencia_desde' => 'Vigencia desde',
            'fec_vigencia_hasta' => 'Vigencia hasta',
            'fec_vigencia_desde_hos' => 'Hospedaje desde',
            'fec_vigencia_hasta_hos' => 'Hospedaje hasta',
            'motivo_rechazo' => 'Motivo de rechazo',
        ] as $key => $label) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                $value = $payload[$key];
                if (in_array($key, ['tiene_alimentos', 'tiene_hospedaje'], true)) {
                    $value = (int) $value === 1 ? 'Sí' : 'No';
                }
                $lines[] = $label . ': ' . $value;
            }
        }

        $hospedajePlanJson = trim((string) ($payload['hospedaje_plan_json'] ?? ''));
        if ($hospedajePlanJson !== '') {
            $plan = json_decode($hospedajePlanJson, true);
            if (is_array($plan)) {
                $habitaciones = is_array($plan['habitaciones'] ?? null) ? count($plan['habitaciones']) : 0;
                $lines[] = 'Plan de hospedaje: ' . $habitaciones . ' habitaciones';
                $lines[] = 'Sobre-reserva: ' . ((int) ($plan['sobrerreserva'] ?? 0) === 1 ? 'Sí' : 'No');
            }
        }

        return implode("\n", $lines);
    }

    private function removeSensitiveSolicitudFolioPayload(array $payload): array
    {
        unset($payload['contrasenia']);

        if (isset($payload['usuarios']) && is_array($payload['usuarios'])) {
            foreach ($payload['usuarios'] as $index => $usuario) {
                if (is_array($usuario)) {
                    unset($usuario['contrasenia']);
                    $payload['usuarios'][$index] = $usuario;
                }
            }
        }

        return $payload;
    }

    private function getSolicitudPerfilBaseId(string $grupo): int
    {
        $map = [
            'fic' => 9,
            'secul' => 8,
            'ug' => 10,
            'secturi' => 4,
        ];

        return (int) ($map[strtolower(trim($grupo))] ?? 0);
    }

    private function getSolicitudDefaultPerfilGrupo(string $grupo): int
    {
        $map = [
            'fic' => 3,
            'secul' => 3,
            'ug' => 3,
            'secturi' => 5,
        ];

        return (int) ($map[strtolower(trim($grupo))] ?? 0);
    }

    private function isSolicitudPerfilGrupoValido(string $grupo, int $perfilGrupo): bool
    {
        $roles = [
            'fic' => [1, 2, 3, 4],
            'secul' => [1, 2, 3, 4],
            'ug' => [1, 2, 3, 4],
            'secturi' => [1, 2, 4, 5],
        ];
        $grupo = strtolower(trim($grupo));

        return in_array($perfilGrupo, $roles[$grupo] ?? [], true);
    }

    private function resolveSolicitudPerfilGrupo(string $grupo, array $payload, ?array $solicitud = null): int
    {
        $grupo = strtolower(trim($grupo));
        $basePerfil = $this->getSolicitudPerfilBaseId($grupo);
        $candidatos = [
            $payload['perfil_grupo'] ?? null,
            $payload['id_perfil_solicitado'] ?? null,
            $solicitud['id_perfil_solicitado'] ?? null,
            $payload['id_perfil_catalogo'] ?? null,
        ];

        foreach ($candidatos as $candidato) {
            if (!is_numeric($candidato)) {
                continue;
            }

            $perfilGrupo = (int) $candidato;
            if ($perfilGrupo === $basePerfil) {
                continue;
            }
            if ($this->isSolicitudPerfilGrupoValido($grupo, $perfilGrupo)) {
                return $perfilGrupo;
            }
        }

        return $this->getSolicitudDefaultPerfilGrupo($grupo);
    }

    private function resolveSolicitudFolioGrupo(array $solicitud, string $grupo = '', array $payload = []): string
    {
        $candidatos = [
            $grupo,
            $payload['grupo_usuario'] ?? '',
        ];
        $tipoSolicitud = strtolower((string) ($solicitud['tipo_solicitud'] ?? ''));
        if (strpos($tipoSolicitud, 'alta_usuario_') === 0) {
            $candidatos[] = substr($tipoSolicitud, strlen('alta_usuario_'));
        }

        foreach ($candidatos as $candidato) {
            $candidato = strtolower(trim((string) $candidato));
            if (in_array($candidato, ['fic', 'ug', 'secul', 'secturi'], true)) {
                return $candidato;
            }
        }

        return '';
    }

    private function synchronizeSolicitudFolioFields(array $payload): array
    {
        $folioFuente = trim((string) ($payload['folio'] ?? ''));
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($payload['folio_ui'] ?? ''));
        }
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($payload['folio_grupo'] ?? ''));
        }

        $folioCanonico = preg_replace('/\D+/', '', $folioFuente);
        $payload['folio'] = $folioCanonico;
        $payload['folio_grupo'] = $folioCanonico;

        return $payload;
    }

    private function normalizeSolicitudFolioPayload(string $grupo, array $payload): array
    {
        $grupo = strtolower(trim($grupo));
        $grupo = $grupo !== '' ? $grupo : strtolower((string) ($payload['grupo_usuario'] ?? ''));
        if (!in_array($grupo, ['fic', 'ug', 'secul', 'secturi'], true)) {
            $grupo = strtolower((string) ($payload['perfil_grupo_key'] ?? ''));
        }
        $payload['grupo_usuario'] = $grupo;
        if (empty($payload['id_perfil_catalogo']) || !is_numeric($payload['id_perfil_catalogo'])) {
            $payload['id_perfil_catalogo'] = $this->getSolicitudPerfilBaseId($grupo);
        }
        $perfilGrupo = $this->resolveSolicitudPerfilGrupo($grupo, $payload);
        if ($perfilGrupo > 0) {
            $payload['perfil_grupo'] = $perfilGrupo;
            $payload['id_perfil_solicitado'] = $perfilGrupo;
        }
        $payload['pax_total'] = max(1, (int) ($payload['pax_total'] ?? $payload['pax'] ?? $payload['pax_ui'] ?? 1));

        $tieneHospedaje = (int) ($payload['tiene_hospedaje'] ?? 0) === 1;
        $tieneAlimentos = (int) ($payload['tiene_alimentos'] ?? 0) === 1;

        if ($tieneHospedaje) {
            $payload['id_partida'] = 2;
        } elseif ($tieneAlimentos) {
            $payload['id_partida'] = 3;
        } else {
            $partida = (int) ($payload['id_partida'] ?? 0);
            $payload['id_partida'] = in_array($partida, [1, 2, 3], true) ? $partida : 3;
        }

        $payload = $this->synchronizeSolicitudFolioFields($payload);

        if (isset($payload['sub_folio'])) {
            $payload['sub_folio'] = strtoupper(trim((string) $payload['sub_folio']));
        }
        if (isset($payload['subf_ui']) && empty($payload['sub_folio'])) {
            $payload['sub_folio'] = strtoupper(trim((string) $payload['subf_ui']));
        }

        foreach (['nombre', 'primer_apellido', 'segundo_apellido', 'anf_gto'] as $campo) {
            if (isset($payload[$campo])) {
                $payload[$campo] = function_exists('mb_strtoupper') ? mb_strtoupper(trim((string) $payload[$campo]), 'UTF-8') : strtoupper(trim((string) $payload[$campo]));
            }
        }
        foreach (['usuario', 'correo'] as $campo) {
            if (isset($payload[$campo])) {
                $payload[$campo] = function_exists('mb_strtolower') ? mb_strtolower(trim((string) $payload[$campo]), 'UTF-8') : strtolower(trim((string) $payload[$campo]));
            }
        }

        return $payload;
    }

    private function findSolicitudNuevoFolioTi(int $idSolicitud): array
    {
        if ($idSolicitud <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $row = $db->table('solicitud_usuario su')
            ->select('su.id_solicitud_usuario, su.tipo_solicitud, su.id_proveedor, su.id_establecimiento, su.id_perfil_solicitado, su.usuario, su.nombre, su.primer_apellido, su.segundo_apellido, su.correo, su.estatus, su.comentario_ti, su.fec_reg, su.visible, COALESCE(cf.dsc_perfil, cs.des_perfil, cu.dsc_perfil) AS perfil_solicitado')
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_fic"', 'left')
            ->join('cat_secul cs', 'cs.id_secul_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_secul"', 'left')
            ->join('cat_ug cu', 'cu.id_ug_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_ug"', 'left')
            ->where('su.id_solicitud_usuario', $idSolicitud)
            ->where('su.visible', 1)
            ->whereIn('su.tipo_solicitud', ['alta_usuario_fic', 'alta_usuario_secul', 'alta_usuario_ug'])
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : [];
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
                    u.id_partida AS id_partida_usuario,
                    cp.partida AS partida_usuario,
                    sp.monto_solicitado,
                    sp.estatus,
                    sp.fecha_respuesta,
                    sp.fec_reg,
                    sp.observaciones,
                    e.dsc_establecimiento,
                    cte.dsc_tipo
                ')
                ->join('usuario u', 'u.id_usuario = sp.id_usuario', 'left')
                ->join('cat_partida cp', 'cp.id_partida = u.id_partida', 'left')
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

    private function filterProviderDashboardByEstablecimiento(array $dashboard, int $idEstablecimiento): array
    {
        if ($idEstablecimiento <= 0) {
            return $dashboard;
        }

        $establecimientos = array_values(array_filter(
            is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : [],
            static function ($item) use ($idEstablecimiento): bool {
                $row = is_object($item) ? get_object_vars($item) : (array) $item;
                return (int) ($row['id_establecimiento'] ?? 0) === $idEstablecimiento;
            }
        ));

        if (empty($establecimientos)) {
            return [];
        }

        $pagos = array_values(array_filter(
            is_array($dashboard['proveedorPagos'] ?? null) ? $dashboard['proveedorPagos'] : [],
            static function ($item) use ($idEstablecimiento): bool {
                $row = is_object($item) ? get_object_vars($item) : (array) $item;
                return (int) ($row['id_establecimiento'] ?? 0) === $idEstablecimiento;
            }
        ));

        $solicitudes = array_values(array_filter(
            is_array($dashboard['solicitudPago'] ?? null) ? $dashboard['solicitudPago'] : [],
            static function ($item) use ($idEstablecimiento): bool {
                $row = is_object($item) ? get_object_vars($item) : (array) $item;
                return (int) ($row['id_establecimiento'] ?? 0) === $idEstablecimiento;
            }
        ));

        $montoTotal = 0.0;
        $montoPendiente = 0.0;
        $fechas = [];
        foreach ($pagos as $row) {
            $item = is_object($row) ? get_object_vars($row) : (array) $row;
            $monto = (float) ($item['monto_total'] ?? $item['monto_solicitado'] ?? 0);
            $montoTotal += $monto;
            $estatus = strtolower(trim((string) ($item['estatus'] ?? '')));
            if ($estatus === '' || in_array($estatus, ['pendiente', 'solicitado', 'en_revision'], true)) {
                $montoPendiente += $monto;
            }
            foreach (['fec_reg', 'fecha_respuesta'] as $campoFecha) {
                $valorFecha = trim((string) ($item[$campoFecha] ?? ''));
                if ($valorFecha !== '') {
                    $fechas[] = $valorFecha;
                }
            }
        }

        sort($fechas);

        $dashboard['proveedorEstablecimientos'] = $establecimientos;
        $dashboard['proveedorPagos'] = $pagos;
        $dashboard['solicitudPago'] = $solicitudes;
        $dashboard['datosProveedor'] = (object) ($establecimientos[0] ?? []);
        $dashboard['establecimiento'] = count($establecimientos);
        $dashboard['total'] = $montoTotal;
        $dashboard['pendiente'] = [];
        $dashboard['aprobados'] = [];
        $dashboard['rechazado'] = [];
        foreach ($pagos as $row) {
            $item = is_object($row) ? get_object_vars($row) : (array) $row;
            $estatus = strtolower(trim((string) ($item['estatus'] ?? '')));
            if (in_array($estatus, ['pendiente', 'solicitado', 'en_revision'], true)) {
                $dashboard['pendiente'][] = $estatus;
            } elseif (in_array($estatus, ['aprobada', 'aprobado', 'aceptada', 'aceptado', 'aceptados', 'autorizada', 'autorizado', 'pagada', 'pagado', 'finalizada', 'finalizado'], true)) {
                $dashboard['aprobados'][] = $estatus;
            } elseif (in_array($estatus, ['rechazada', 'rechazado', 'rechazados', 'cancelada', 'cancelado'], true)) {
                $dashboard['rechazado'][] = $estatus;
            }
        }
        $dashboard['ventasCorteContexto'] = [
            'monto_total' => $montoTotal,
            'monto_pendiente' => $montoPendiente,
            'total_registros' => count($pagos),
            'fecha_corte_desde' => $fechas[0] ?? '',
            'fecha_corte_hasta' => !empty($fechas) ? end($fechas) : '',
            'estado_corte' => !empty($pagos) ? 'Con movimientos' : 'Sin movimientos',
        ];

        return $dashboard;
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
    }

    private function buildPagosFicEstablecimientosData(): array
    {
        $db = \Config\Database::connect();
        $tableName = null;

        if ($db->tableExists('establecimientos')) {
            $tableName = 'establecimientos';
        } elseif ($db->tableExists('establecimiento')) {
            $tableName = 'establecimiento';
        }

        if ($tableName === null) {
            return [];
        }

        $facturasPorEstablecimiento = [];
        if ($db->tableExists('facturas')) {
            $facturas = $db->table('facturas')
                ->select('id_factura, id_estableciemiento AS id_establecimiento, xml, pdf, fec_reg, visible')
                ->where('visible', 1)
                ->orderBy('fec_reg', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($facturas as $factura) {
                $idEstFactura = (int) ($factura['id_establecimiento'] ?? 0);
                if ($idEstFactura <= 0 || isset($facturasPorEstablecimiento[$idEstFactura])) {
                    continue;
                }

                $facturasPorEstablecimiento[$idEstFactura] = [
                    'id_factura' => (int) ($factura['id_factura'] ?? 0),
                    'tiene_xml' => trim((string) ($factura['xml'] ?? '')) !== '',
                    'tiene_pdf' => trim((string) ($factura['pdf'] ?? '')) !== '',
                ];
            }
        }

        $rows = $db->table($tableName . ' e')
            ->select('e.id_establecimiento, e.dsc_establecimiento, e.no_proveedor, e.id_tipo, cte.dsc_tipo, e.visible')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->where('e.visible', 1)
            ->orderBy('e.dsc_establecimiento', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $row) use ($facturasPorEstablecimiento): array {
            $idEstablecimiento = (int) ($row['id_establecimiento'] ?? 0);
            $tipoDetectado = strtolower(trim((string) ($row['dsc_tipo'] ?? '')));
            $idTipo = (int) ($row['id_tipo'] ?? 0);
            $esHospedaje = $idTipo === 2 || ($tipoDetectado !== '' && (str_contains($tipoDetectado, 'hotel') || str_contains($tipoDetectado, 'recep')));
            $factura = is_array($facturasPorEstablecimiento[$idEstablecimiento] ?? null) ? $facturasPorEstablecimiento[$idEstablecimiento] : [];
            $idFactura = (int) ($factura['id_factura'] ?? 0);

            return [
                'id_establecimiento' => $idEstablecimiento,
                'establecimiento' => (string) ($row['dsc_establecimiento'] ?? 'Sin establecimiento'),
                'no_proveedor' => (string) ($row['no_proveedor'] ?? ''),
                'id_tipo' => $idTipo,
                'dsc_tipo' => $tipoDetectado,
                'reporte_url' => $esHospedaje
                    ? base_url('index.php/Inicio/exportarReporteHospedajePdf?id_establecimiento=' . $idEstablecimiento)
                    : base_url('index.php/Inicio/exportarReporteVentasProveedorPdfFormato?id_establecimiento=' . $idEstablecimiento),
                'factura_id' => $idFactura,
                'xml_url' => $idFactura > 0
                    ? base_url('index.php/Inicio/verFacturaProveedorArchivo?id_factura=' . $idFactura . '&tipo=xml')
                    : '',
                'pdf_url' => $idFactura > 0
                    ? base_url('index.php/Inicio/verFacturaProveedorArchivo?id_factura=' . $idFactura . '&tipo=pdf')
                    : '',
                'tiene_xml' => $idFactura > 0 && !empty($factura['tiene_xml']),
                'tiene_pdf' => $idFactura > 0 && !empty($factura['tiene_pdf']),
                'visible' => (int) ($row['visible'] ?? 0),
            ];
        }, $rows);
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
        $data['esAdministradorEstablecimientosFic'] = !empty($this->resolveSecturiAdminUsuario());
        $data['soloConsultaEstablecimientosFic'] = empty($this->resolveSecturiAdminUsuario());
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

        if (empty($this->resolveSecturiAdminUsuario())) {
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

        if (empty($this->resolveSecturiAdminUsuario())) {
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

    private function renderProveedorFormatoPdf(string $tipoDocumento, int $idEstablecimiento)
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $session->get('id_usuario');
        if ($idUsuario <= 0) {
            return redirect()->to(base_url('index.php/Login/cerrar?inactividad=1'));
        }

        $data = $this->buildProviderFormatPdfData($idUsuario, $idEstablecimiento, $tipoDocumento);
        if (empty($data['proveedorPerfil']) || empty($data['proveedorEstablecimiento'])) {
            return redirect()->to(base_url('index.php/Inicio/ProveedorFormatos'));
        }

        $templateMap = [
            'encabezado_factura' => [
                'title' => 'EncabezadoFactura',
                'file' => 'EncabezadoFactura',
                'template' => FCPATH . 'assets/images/EncabezadoFactura_43.pdf',
            ],
            'formato_pt' => [
                'title' => 'FormatPagoTerceros',
                'file' => 'FormatPagoTerceros',
                'template' => FCPATH . 'assets/images/FormatPagoTerceros_43.pdf',
            ],
            'liberacion_pago' => [
                'title' => 'LiberacionPago',
                'file' => 'LiberacionPago',
                'template' => FCPATH . 'assets/images/LiberacionPago_43.pdf',
            ],
        ];

        $config = $templateMap[$tipoDocumento] ?? $templateMap['encabezado_factura'];
        $templatePath = (string) ($config['template'] ?? '');
        if ($templatePath === '' || !is_file($templatePath) || !is_readable($templatePath)) {
            log_message('error', 'No se encontro la plantilla PDF proveedor: ' . $templatePath);
            return redirect()
                ->to(base_url('index.php/Inicio/ProveedorFormatos'))
                ->with('error', 'No fue posible encontrar la plantilla del PDF solicitado.');
        }

        $tempDir = WRITEPATH . 'mpdf-temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'Letter',
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
                'default_font' => 'dejavusans',
                'tempDir' => $tempDir,
            ]);

            $mpdf->SetTitle($config['title']);
            $this->writeProviderTemplatePdf($mpdf, $templatePath, $tipoDocumento, $data);
            $mpdf->Output($config['file'] . '_' . $idEstablecimiento . '.pdf', 'I');
            exit;
        } catch (\Throwable $e) {
            log_message('error', 'Error al generar PDF proveedor: ' . $e->getMessage());
            if (ENVIRONMENT !== 'production') {
                throw $e;
            }

            return redirect()
                ->to(base_url('index.php/Inicio/ProveedorFormatos'))
                ->with('error', 'No fue posible generar el PDF solicitado.');
        }
    }

    private function writeProviderTemplatePdf(\Mpdf\Mpdf $mpdf, string $templatePath, string $tipoDocumento, array $data): void
    {
        $pageCount = $mpdf->setSourceFile($templatePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $mpdf->importPage($pageNumber);
            $size = $mpdf->getTemplateSize($templateId);

            $mpdf->AddPageByArray([
                'orientation' => $size['orientation'] ?? 'P',
                'sheet-size' => [$size['width'], $size['height']],
                'margin-left' => 0,
                'margin-right' => 0,
                'margin-top' => 0,
                'margin-bottom' => 0,
                'margin-header' => 0,
                'margin-footer' => 0,
            ]);
            $mpdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

            if ($pageNumber === 1) {
                $this->writeProviderTemplateOverlay($mpdf, $tipoDocumento, $data);
            }
        }

        if ($tipoDocumento === 'encabezado_factura') {
            $invoicePdfPath = $this->resolveProviderInvoicePdfPath($data);
            if ($invoicePdfPath !== '') {
                $this->appendProviderPdfPages($mpdf, $invoicePdfPath);
            }
        }
    }

    private function appendProviderPdfPages(\Mpdf\Mpdf $mpdf, string $pdfPath): void
    {
        $pageCount = $mpdf->setSourceFile($pdfPath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $mpdf->importPage($pageNumber);
            $size = $mpdf->getTemplateSize($templateId);

            $mpdf->AddPageByArray([
                'orientation' => $size['orientation'] ?? 'P',
                'sheet-size' => [$size['width'], $size['height']],
                'margin-left' => 0,
                'margin-right' => 0,
                'margin-top' => 0,
                'margin-bottom' => 0,
                'margin-header' => 0,
                'margin-footer' => 0,
            ]);
            $mpdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);
        }
    }

    private function resolveProviderInvoicePdfPath(array $data): string
    {
        $facturaXmlContext = is_array($data['facturaXmlContext'] ?? null) ? $data['facturaXmlContext'] : [];
        $pdfCandidates = [];

        if (!empty($facturaXmlContext['pdf'])) {
            $pdfCandidates[] = (string) $facturaXmlContext['pdf'];
        }

        $solicitudPago = is_array($data['solicitudPago'] ?? null) ? $data['solicitudPago'] : [];
        $observaciones = is_array($solicitudPago['observaciones_json'] ?? null) ? $solicitudPago['observaciones_json'] : [];
        $candidateKeys = [
            'factura_pdf',
            'pdf_factura',
            'archivo_factura_pdf',
            'archivo_factura',
            'ruta_factura_pdf',
            'ruta_pdf_factura',
            'comprobante_pdf',
            'pdf',
        ];

        foreach ($candidateKeys as $key) {
            $candidate = trim((string) ($observaciones[$key] ?? ''));
            if ($candidate !== '') {
                $pdfCandidates[] = $candidate;
            }
        }

        foreach ($pdfCandidates as $candidate) {
            if ($candidate === '' || preg_match('#^https?://#i', $candidate)) {
                continue;
            }

            $paths = [
                $candidate,
                FCPATH . ltrim($candidate, '/\\'),
                WRITEPATH . ltrim($candidate, '/\\'),
            ];

            foreach ($paths as $path) {
                if (is_file($path) && is_readable($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                    return $path;
                }
            }
        }

        return '';
    }

    private function writeProviderTemplateOverlay(\Mpdf\Mpdf $mpdf, string $tipoDocumento, array $data): void
    {
        $overlay = $this->buildProviderTemplateOverlayData($tipoDocumento, $data);
        if (empty($overlay)) {
            return;
        }

        foreach ($overlay as $item) {
            $html = '<div style="font-family: dejavusans; font-size: ' . (float) $item['fontSize'] . 'pt; color: #111;">'
                . esc((string) $item['text'])
                . '</div>';
            $mpdf->WriteFixedPosHTML($html, (float) $item['x'], (float) $item['y'], (float) $item['w'], (float) $item['h']);
        }
    }

    private function buildProviderTemplateOverlayData(string $tipoDocumento, array $data): array
    {
        $proveedorPerfil = is_array($data['proveedorPerfil'] ?? null) ? $data['proveedorPerfil'] : [];
        $proveedorEstablecimiento = is_array($data['proveedorEstablecimiento'] ?? null) ? $data['proveedorEstablecimiento'] : [];
        $facturaXmlContext = is_array($data['facturaXmlContext'] ?? null) ? $data['facturaXmlContext'] : [];
        $xmlInfo = is_array($facturaXmlContext['xml_info'] ?? null) ? $facturaXmlContext['xml_info'] : [];
        $proveedorXml = is_array($facturaXmlContext['proveedor'] ?? null) ? $facturaXmlContext['proveedor'] : [];
        $establecimientoXml = is_array($facturaXmlContext['establecimiento'] ?? null) ? $facturaXmlContext['establecimiento'] : [];

        $fechaEmision = !empty($facturaXmlContext['fecha_emision'])
            ? date('d/m/Y', strtotime((string) $facturaXmlContext['fecha_emision']))
            : (!empty($data['fecha_emision']) ? date('d/m/Y', strtotime((string) $data['fecha_emision'])) : date('d/m/Y'));
        $folio = (string) ($facturaXmlContext['folio_formato'] ?? $data['folio_formato'] ?? '');
        $razonSocial = trim((string) ($proveedorXml['razon_social'] ?? $proveedorPerfil['razon_social'] ?? ''));
        $establecimiento = trim((string) ($establecimientoXml['dsc_establecimiento'] ?? $proveedorEstablecimiento['dsc_establecimiento'] ?? ''));
        $monto = !empty($xmlInfo['total'])
            ? '$' . number_format((float) $xmlInfo['total'], 2)
            : (!empty($facturaXmlContext['monto_total']) ? '$' . number_format((float) $facturaXmlContext['monto_total'], 2) : '');

        $common = [
            ['text' => $fechaEmision, 'x' => 154, 'y' => 12, 'w' => 42, 'h' => 6, 'fontSize' => 8],
            ['text' => $folio, 'x' => 147, 'y' => 24, 'w' => 52, 'h' => 6, 'fontSize' => 7],
        ];

        if ($tipoDocumento === 'encabezado_factura') {
            return array_merge($common, [
                ['text' => $razonSocial, 'x' => 35, 'y' => 55, 'w' => 140, 'h' => 8, 'fontSize' => 8],
                ['text' => $establecimiento, 'x' => 35, 'y' => 64, 'w' => 140, 'h' => 8, 'fontSize' => 8],
            ]);
        }

        if ($tipoDocumento === 'formato_pt') {
            return array_merge($common, [
                ['text' => $razonSocial, 'x' => 42, 'y' => 50, 'w' => 125, 'h' => 8, 'fontSize' => 8],
                ['text' => $monto, 'x' => 145, 'y' => 78, 'w' => 40, 'h' => 8, 'fontSize' => 8],
            ]);
        }

        if ($tipoDocumento === 'liberacion_pago') {
            return array_merge($common, [
                ['text' => $razonSocial, 'x' => 38, 'y' => 52, 'w' => 130, 'h' => 8, 'fontSize' => 8],
                ['text' => $establecimiento, 'x' => 38, 'y' => 61, 'w' => 130, 'h' => 8, 'fontSize' => 8],
            ]);
        }

        return $common;
    }

    private function buildProviderFormatPdfData(int $idUsuario, int $idEstablecimiento, string $tipoDocumento): array
    {
        $dashboard = $this->buildProviderDashboardData($idUsuario);
        $proveedorPerfil = is_array($dashboard['proveedorPerfil'] ?? null) ? $dashboard['proveedorPerfil'] : [];
        $establecimientos = array_values(array_map(static function ($item) {
            return is_object($item) ? get_object_vars($item) : (array) $item;
        }, is_array($dashboard['proveedorEstablecimientos'] ?? null) ? $dashboard['proveedorEstablecimientos'] : []));

        $establecimientoSeleccionado = [];
        foreach ($establecimientos as $establecimiento) {
            if ((int) ($establecimiento['id_establecimiento'] ?? 0) === $idEstablecimiento) {
                $establecimientoSeleccionado = $establecimiento;
                break;
            }
        }

        if (empty($establecimientoSeleccionado) && !empty($establecimientos)) {
            $establecimientoSeleccionado = $establecimientos[0];
        }

        if (empty($proveedorPerfil) || empty($establecimientoSeleccionado)) {
            return [];
        }

        $facturaXmlContext = $this->buildLatestFacturaXmlContextForEstablecimiento($idEstablecimiento);

        $db = \Config\Database::connect();
        $solicitudPago = $db->table('solicitud_pago sp')
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
                sp.fec_act,
                sp.visible
            ')
            ->where('sp.visible', 1)
            ->where('sp.id_usuario', $idUsuario)
            ->where('sp.id_establecimiento', $idEstablecimiento)
            ->orderBy('sp.fec_reg', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        $solicitudContexto = [];
        if (!empty($solicitudPago)) {
            $observacionesRaw = trim((string) ($solicitudPago['observaciones'] ?? ''));
            $observacionesJson = json_decode($observacionesRaw, true);
            $solicitudContexto = [
                'id_solicitud_pago' => (int) ($solicitudPago['id_solicitud_pago'] ?? 0),
                'folio_solicitud' => (string) ($solicitudPago['folio_solicitud'] ?? ''),
                'monto_solicitado' => (float) ($solicitudPago['monto_solicitado'] ?? 0),
                'metodo_autorizacion' => (string) ($solicitudPago['metodo_autorizacion'] ?? ''),
                'estatus' => (string) ($solicitudPago['estatus'] ?? ''),
                'fecha_respuesta' => (string) ($solicitudPago['fecha_respuesta'] ?? ''),
                'motivo_rechazo' => (string) ($solicitudPago['motivo_rechazo'] ?? ''),
                'observaciones' => $observacionesRaw,
                'observaciones_json' => is_array($observacionesJson) ? $observacionesJson : [],
                'fecha_registro' => (string) ($solicitudPago['fec_reg'] ?? ''),
                'fecha_actualizacion' => (string) ($solicitudPago['fec_act'] ?? ''),
            ];
        }

        $documentos = [
            'encabezado_factura' => [
                'titulo' => 'Encabezado de factura',
                'descripcion' => 'Documento de referencia para identificar al proveedor y al establecimiento antes de emitir comprobantes o formatos de pago.',
                'objetivo' => 'Sirve como portada operativa para validar datos fiscales y administrativos.',
            ],
            'formato_pt' => [
                'titulo' => 'Formato PT',
                'descripcion' => 'Formato operativo para concentrar datos base del proveedor y su establecimiento seleccionado.',
                'objetivo' => 'Permite capturar y validar la trazabilidad del tramite.',
            ],
            'liberacion_pago' => [
                'titulo' => 'Liberacion de pago',
                'descripcion' => 'Documento para formalizar la autorizacion de pago al proveedor de acuerdo con el establecimiento elegido.',
                'objetivo' => 'Resume la informacion necesaria para la liberacion administrativa.',
            ],
        ];

        $documento = $documentos[$tipoDocumento] ?? $documentos['encabezado_factura'];
        $fechaEmision = !empty($facturaXmlContext['fecha_emision'])
            ? (string) $facturaXmlContext['fecha_emision']
            : date('Y-m-d H:i:s');
        $folioFormato = !empty($facturaXmlContext['folio_formato'])
            ? (string) $facturaXmlContext['folio_formato']
            : 'PROV-' . $idUsuario . '-' . $idEstablecimiento . '-' . date('YmdHis');

        return [
            'documentoCodigo' => $tipoDocumento,
            'documentoTitulo' => $documento['titulo'],
            'documentoDescripcion' => $documento['descripcion'],
            'documentoObjetivo' => $documento['objetivo'],
            'fecha_emision' => $fechaEmision,
            'folio_formato' => $folioFormato,
            'proveedorPerfil' => $proveedorPerfil,
            'proveedorEstablecimiento' => $establecimientoSeleccionado,
            'proveedorEstablecimientos' => $establecimientos,
            'conteo_establecimientos' => count($establecimientos),
            'facturaXmlContext' => $facturaXmlContext,
            'solicitudPago' => $solicitudContexto,
        ];
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
        $idSesionUsuario = (int) $session->get('id_usuario');
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

    public function guardarPagoSinQrProveedor()
    {
        $session = \Config\Services::session();
        $idSesionUsuario = (int) $session->get('id_usuario');
        if ($idSesionUsuario <= 0) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Solo un proveedor autenticado puede aplicar pagos.',
            ]);
        }

        $db = \Config\Database::connect();
        $usuarioProveedor = $db->table('usuario u')
            ->select('u.id_usuario, u.id_proveedor, p.no_proveedor')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'inner')
            ->where('u.id_usuario', $idSesionUsuario)
            ->where('u.visible', 1)
            ->where('u.id_proveedor >', 0)
            ->where('p.visible', 1)
            ->get()
            ->getRowArray();

        if (empty($usuarioProveedor)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'No fue posible resolver el proveedor autenticado.',
            ]);
        }

        $noProveedor = trim((string) ($usuarioProveedor['no_proveedor'] ?? ''));
        $establecimientoProveedor = $db->table('establecimiento')
            ->select('id_establecimiento')
            ->where('visible', 1)
            ->where('no_proveedor', $noProveedor)
            ->orderBy('id_establecimiento', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (empty($establecimientoProveedor)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'El proveedor autenticado no tiene establecimiento visible ligado.',
            ]);
        }

        $folio = strtoupper(trim((string) ($this->request->getPost('folio') ?? '')));
        $monto = round((float) ($this->request->getPost('monto') ?? 0), 2);
        $propinaPorcentaje = (int) ($this->request->getPost('propina_porcentaje') ?? 0);
        $nip = trim((string) ($this->request->getPost('nip') ?? ''));
        $porcentajesPermitidos = [0, 5, 10, 15, 20];

        if (!preg_match('/^FIC-(\d+)-QR$/', $folio, $matches)) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'El folio debe tener el formato FIC-745-QR.',
            ]);
        }

        $idUsuarioCliente = (int) ($matches[1] ?? 0);
        if ($idUsuarioCliente <= 0 || $monto <= 0 || !in_array($propinaPorcentaje, $porcentajesPermitidos, true) || $nip === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Completa folio, monto, propina y NIP con valores validos.',
            ]);
        }

        $cliente = $db->table('usuario')
            ->select('id_usuario, nip, monto_deposito')
            ->where('id_usuario', $idUsuarioCliente)
            ->where('visible', 1)
            ->get()
            ->getRowArray();

        if (empty($cliente)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'No se encontro el cliente del folio capturado.',
            ]);
        }

        if ((string) ($cliente['nip'] ?? '') !== $nip) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'El NIP no corresponde al cliente del folio.',
            ]);
        }

        $propinaMonto = round($monto * $propinaPorcentaje / 100, 2);
        $total = round($monto + $propinaMonto, 2);
        $saldoActual = round((float) ($cliente['monto_deposito'] ?? 0), 2);

        if ($total > $saldoActual) {
            return $this->response->setStatusCode(409)->setJSON([
                'ok' => false,
                'message' => 'Saldo insuficiente para aplicar el pago.',
            ]);
        }

         $globals = new Mglobal();
        
        $id_establecimiento = $globals->getTabla(['tabla' => 'vw_usuario', "where" => ["id_usuario" => $session->get('id_usuario')]]);
       // die( var_dump($idProveedor));

        $fechaAhora = date('Y-m-d H:i:s');
        $saldoNuevo = round($saldoActual - $total, 2);
        $idEstablecimientoProveedor = (!empty($id_establecimiento->data) && isset($id_establecimiento->data)) ? (int) $id_establecimiento->data[0]->id_establecimiento : 0;
        $folioSolicitud = 'FIC-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $observacionesPago = json_encode([
            'monto' => $monto,
            'propina' => $propinaMonto,
            'propina_porcentaje' => $propinaPorcentaje,
            'total' => $total,
            'descripcion' => 'Pago sin QR',
            'folio_cliente' => $folio,
            'proveedor_id' => (int) ($usuarioProveedor['id_proveedor'] ?? 0),
        ]);

        $db->transStart();
        $db->table('solicitud_pago')->insert([
            'folio_solicitud' => $folioSolicitud,
            'id_usuario' => $idUsuarioCliente,
            'id_establecimiento' => $idEstablecimientoProveedor,
            'monto_solicitado' => number_format($total, 2, '.', ''),
            'metodo_autorizacion' => 'web',
            'estatus' => 'autorizado',
            'token_autorizacion' => bin2hex(random_bytes(16)),
            'fecha_respuesta' => $fechaAhora,
            'motivo_rechazo' => null,
            'observaciones' => $observacionesPago,
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
            'fec_act' => $fechaAhora,
            'usu_act' => $idSesionUsuario,
            'visible' => 1,
        ]);
        $idSolicitudPago = (int) $db->insertID();

        $db->table('usuario')
            ->where('id_usuario', $idUsuarioCliente)
            ->where('visible', 1)
            ->update([
                'monto_deposito' => number_format($saldoNuevo, 2, '.', ''),
                'fec_act' => $fechaAhora,
                'usu_act' => $idSesionUsuario,
            ]);

        $db->table('pagos')->insert([
            'id_tipo_pago' => 2,
            'id_usuario' => $idUsuarioCliente,
            'id_establecimiento' => $idEstablecimientoProveedor,
            'id_solicitud_pago' => 3,
            'monto' => number_format($monto, 2, '.', ''),
            'propina' => number_format($propinaMonto, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'fec_reg' => $fechaAhora,
            'usu_reg' => $idSesionUsuario,
            'visible' => 1,
        ]);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'No fue posible aplicar el pago.',
            ]);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Pago aplicado correctamente.',
            'data' => [
                'id_usuario' => $idUsuarioCliente,
                'folio' => $folio,
                'id_solicitud_pago' => $idSolicitudPago,
                'folio_solicitud' => $folioSolicitud,
                'monto' => number_format($monto, 2, '.', ''),
                'propina' => number_format($propinaMonto, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'saldo_anterior' => number_format($saldoActual, 2, '.', ''),
                'saldo_nuevo' => number_format($saldoNuevo, 2, '.', ''),
            ],
        ]);
    }

    public function pdfPagoTerceros()
    {
        $id = (int) ($this->request->getGet('id_factura') ?? $this->request->getGet('id') ?? 0);
        $data = $this->buildFacturaFormatoData($id);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('Factura no encontrada.');
        }

        $html = view('pdfs/vPdfFormatoPT', $data);

        $mpdf = new \Mpdf\Mpdf([
            'margin_top' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
            'format' => 'Letter',
            'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf'
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('FormatPagoTerceros_' . $id . '.pdf', 'I');
        exit;
    }

    public function pdfLiberacionPago()
    {
        $id = (int) ($this->request->getGet('id_factura') ?? $this->request->getGet('id') ?? 0);
        $data = $this->buildFacturaFormatoData($id);
        if ($data === null) {
            return $this->response->setStatusCode(404)->setBody('Factura no encontrada.');
        }

        $data['norma'] = FCPATH . 'assets/Norma.png';
        $html = view('pdfs/vPdfLiberacionPago', $data);

        $mpdf = new \Mpdf\Mpdf([
            'margin_top' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10,
            'format' => 'Letter',
            'tempDir' => sys_get_temp_dir().DIRECTORY_SEPARATOR.'mpdf'
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('LiberacionPago_' . $id . '.pdf', 'I');
        exit;
      
    }

    private function buildFacturaFormatoData(int $idFactura): ?array
    {
        if ($idFactura <= 0) {
            return null;
        }

        if (empty($this->resolveSecturiDashboardUsuario())) {
            return null;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('facturas')) {
            return null;
        }

        $factura = $db->table('facturas f')
            ->select('
                f.id_factura,
                f.xml,
                f.pdf,
                f.id_estableciemiento AS id_establecimiento,
                f.id_estatus,
                f.fec_reg,
                f.usu_reg,
                e.dsc_establecimiento,
                e.no_proveedor,
                p.id_proveedor,
                p.razon_social,
                p.rfc
            ')
            ->join('establecimiento e', 'e.id_establecimiento = f.id_estableciemiento', 'left')
            ->join('proveedor p', 'p.no_proveedor = e.no_proveedor', 'left')
            ->where('f.id_factura', $idFactura)
            ->where('f.visible', 1)
            ->get()
            ->getRowArray();

        if (empty($factura)) {
            return null;
        }

        $xmlInfo = $this->extractFacturaXmlInfo((string) ($factura['xml'] ?? ''));
        $fecha = $xmlInfo['fecha'] !== '' ? $xmlInfo['fecha'] : (string) ($factura['fec_reg'] ?? date('Y-m-d H:i:s'));
        $folio = $xmlInfo['folio'] !== '' ? $xmlInfo['folio'] : ('FAC-' . $idFactura);
        $total = $xmlInfo['total'] > 0 ? $xmlInfo['total'] : 0.00;
        $proveedorNombre = $xmlInfo['emisor_nombre'] !== '' ? $xmlInfo['emisor_nombre'] : (string) ($factura['razon_social'] ?? 'Proveedor');
        $proveedorRfc = $xmlInfo['emisor_rfc'] !== '' ? $xmlInfo['emisor_rfc'] : (string) ($factura['rfc'] ?? '');
        $concepto = $xmlInfo['concepto'] !== '' ? $xmlInfo['concepto'] : 'Servicios registrados en factura';
        $partida = '3390';
        $proyecto = 'FIC';

        $registro = (object) [
            'fecha_tramite' => $fecha,
            'no_consecutivo' => $folio,
            'no_proveedor' => (string) ($factura['no_proveedor'] ?? ''),
            'rfc_proveedor' => $proveedorRfc,
            'nombre_proveedor_1' => $proveedorNombre,
            'no_cuenta' => '',
            'banco' => '',
            'clabe' => '',
            'no_convenio' => 'NO APLICA',
            'no_reserva' => '',
            'importe_total_num' => number_format($total, 2, '.', ','),
            'importe_letra' => $total > 0 ? ('IMPORTE POR $' . number_format($total, 2, '.', ',') . ' M.N.') : '',
            'nombre_autoriza' => '',
            'cargo_autoriza' => '',
            'nombre_responsable' => '',
            'cargo_responsable' => '',
            'nombre_responsable_2' => 'RESPONSABLE ADMINISTRATIVO',
            'cargo_responsable_2' => 'COMISION DE ALIMENTOS Y HOSPEDAJES',
            'clausula' => 'NO APLICA',
            'concepto' => $concepto,
            'id_factura' => $idFactura,
            'id_establecimiento' => (int) ($factura['id_establecimiento'] ?? 0),
            'establecimiento' => (string) ($factura['dsc_establecimiento'] ?? ''),
        ];

        $row = (object) [
            'no_comprobante' => $folio,
            'proyecto' => $proyecto,
            'dsc_proyecto' => 'Festival Internacional Cervantino',
            'partida' => $partida,
            'dsc_partida' => 'Servicios integrales',
            'importe' => number_format($total, 2, '.', ','),
            'nombre_proveedor_1' => $proveedorNombre,
        ];

        return [
            'registro_pt' => $registro,
            'periodo_factura_rows' => [$row],
            'proveedor' => (object) [
                'id_proveedor' => (int) ($factura['id_proveedor'] ?? 0),
                'no_proveedor' => (string) ($factura['no_proveedor'] ?? ''),
                'razon_social' => $proveedorNombre,
                'rfc' => $proveedorRfc,
            ],
            'factura' => (object) $factura,
            'facturaXmlContext' => $this->buildFacturaXmlContextFromRow($factura, $xmlInfo),
            'edit' => 1,
            'logo' => FCPATH . 'assets/logo-guanajuato.png',
        ];
    }

    private function buildFacturaXmlContextFromRow(array $factura, ?array $xmlInfo = null): array
    {
        $xmlInfo = is_array($xmlInfo) ? $xmlInfo : $this->extractFacturaXmlInfo((string) ($factura['xml'] ?? ''));
        $idFactura = (int) ($factura['id_factura'] ?? 0);
        $idEstablecimiento = (int) ($factura['id_establecimiento'] ?? $factura['id_estableciemiento'] ?? 0);
        $proveedorNombre = trim((string) ($xmlInfo['emisor_nombre'] ?? ''));
        $proveedorRfc = trim((string) ($xmlInfo['emisor_rfc'] ?? ''));
        $concepto = trim((string) ($xmlInfo['concepto'] ?? ''));
        $monto = (float) ($xmlInfo['total'] ?? 0);

        return [
            'id_factura' => $idFactura,
            'id_establecimiento' => $idEstablecimiento,
            'folio_formato' => (string) ($xmlInfo['folio'] ?? ''),
            'fecha_emision' => (string) ($xmlInfo['fecha'] ?? ($factura['fec_reg'] ?? date('Y-m-d H:i:s'))),
            'monto_total' => $monto,
            'xml' => (string) ($factura['xml'] ?? ''),
            'pdf' => (string) ($factura['pdf'] ?? ''),
            'proveedor' => [
                'id_proveedor' => (int) ($factura['id_proveedor'] ?? 0),
                'no_proveedor' => (string) ($factura['no_proveedor'] ?? ''),
                'razon_social' => $proveedorNombre !== '' ? $proveedorNombre : (string) ($factura['razon_social'] ?? 'Proveedor'),
                'rfc' => $proveedorRfc !== '' ? $proveedorRfc : (string) ($factura['rfc'] ?? ''),
            ],
            'establecimiento' => [
                'id_establecimiento' => $idEstablecimiento,
                'dsc_establecimiento' => (string) ($factura['dsc_establecimiento'] ?? ''),
                'no_proveedor' => (string) ($factura['no_proveedor'] ?? ''),
            ],
            'xml_info' => [
                'folio' => (string) ($xmlInfo['folio'] ?? ''),
                'fecha' => (string) ($xmlInfo['fecha'] ?? ''),
                'total' => $monto,
                'emisor_nombre' => $proveedorNombre,
                'emisor_rfc' => $proveedorRfc,
                'concepto' => $concepto,
            ],
        ];
    }

    private function buildLatestFacturaXmlContextForEstablecimiento(int $idEstablecimiento): array
    {
        if ($idEstablecimiento <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('facturas')) {
            return [];
        }

        $factura = $db->table('facturas f')
            ->select('
                f.id_factura,
                f.xml,
                f.pdf,
                f.id_estableciemiento AS id_establecimiento,
                f.fec_reg,
                e.dsc_establecimiento,
                e.no_proveedor,
                p.id_proveedor,
                p.razon_social,
                p.rfc
            ')
            ->join('establecimiento e', 'e.id_establecimiento = f.id_estableciemiento', 'left')
            ->join('proveedor p', 'p.no_proveedor = e.no_proveedor', 'left')
            ->where('f.visible', 1)
            ->where('f.id_estableciemiento', $idEstablecimiento)
            ->orderBy('f.fec_reg', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if (empty($factura)) {
            return [];
        }

        return $this->buildFacturaXmlContextFromRow($factura);
    }

    private function extractFacturaXmlInfo(string $storedXml): array
    {
        $info = [
            'folio' => '',
            'fecha' => '',
            'total' => 0.00,
            'emisor_nombre' => '',
            'emisor_rfc' => '',
            'concepto' => '',
        ];

        $xmlBody = $this->readStoredFileContents($storedXml);
        if ($xmlBody === '') {
            return $info;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlBody);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$xml) {
            return $info;
        }

        $attrs = $xml->attributes();
        $serie = trim((string) ($attrs['Serie'] ?? ''));
        $folio = trim((string) ($attrs['Folio'] ?? ''));
        $info['folio'] = trim($serie . ($serie !== '' && $folio !== '' ? '-' : '') . $folio);
        $info['fecha'] = trim((string) ($attrs['Fecha'] ?? ''));
        $info['total'] = (float) ($attrs['Total'] ?? 0);

        $namespaces = $xml->getNamespaces(true);
        $cfdiNs = $namespaces['cfdi'] ?? null;
        $root = $cfdiNs ? $xml->children($cfdiNs) : $xml;
        $emisor = $root->Emisor ?? null;
        if ($emisor) {
            $emisorAttrs = $emisor->attributes();
            $info['emisor_nombre'] = trim((string) ($emisorAttrs['Nombre'] ?? ''));
            $info['emisor_rfc'] = trim((string) ($emisorAttrs['Rfc'] ?? ''));
        }

        $conceptos = [];
        if (isset($root->Conceptos)) {
            foreach ($root->Conceptos->Concepto as $concepto) {
                $conceptoAttrs = $concepto->attributes();
                $descripcion = trim((string) ($conceptoAttrs['Descripcion'] ?? ''));
                if ($descripcion !== '') {
                    $conceptos[] = $descripcion;
                }
            }
        }
        $info['concepto'] = implode(', ', array_unique($conceptos));

        if ($info['folio'] === '') {
            $info['folio'] = 'XML-' . substr(sha1($xmlBody), 0, 8);
        }

        return $info;
    }

    private function readStoredFileContents(string $storedPath): string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return '';
        }

        $url = $this->buildS3PresignedGetUrl($storedPath, 300);
        if ($url === '') {
            return '';
        }

        if (!function_exists('curl_init')) {
            $body = @file_get_contents($url);
            return is_string($body) ? $body : '';
        }

        $sslVerifyValue = strtolower($this->envFirst(['AWS_SSL_VERIFY', 'S3_SSL_VERIFY'], 'true'));
        $sslVerify = !in_array($sslVerifyValue, ['0', 'false', 'no'], true);
        $curl = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ];
        $caInfo = $this->resolveCurlCaInfo();
        if ($sslVerify && $caInfo !== '') {
            $options[CURLOPT_CAINFO] = $caInfo;
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return is_string($body) && $httpCode >= 200 && $httpCode < 300 ? $body : '';
    }


    public function SolicitudesUsuarioFic()
    {
        $usuarioDashboard = $this->resolveSecturiDashboardUsuario();

        if (empty($usuarioDashboard)) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $session = \Config\Services::session();
        $resolver = new UsuarioPerfilResolver();
        $contextoUsuario = $resolver->resolve($session->get());
        $tiUsuario = $this->resolveTiMasterUsuario();
        $secturiAdminUsuario = $this->resolveSecturiAdminUsuario();

        $data = [];
        $data['scripts'] = ['principal', 'agregar', 'solicitudes_usuario_operativo', 'solicitudes_usuario_fic_panel'];
        $data['contextoUsuario'] = $contextoUsuario;
        $data['ficSolicitudListadoUrl'] = base_url('index.php/Inicio/getSolicitudesNuevoFolioTi');
        $data['ficSolicitudDetalleUrl'] = base_url('index.php/Inicio/getSolicitudNuevoFolioTi');
        $data['ficSolicitudCancelarUrl'] = base_url('index.php/Inicio/cancelarSolicitudUsuarioFicPerfil');
        $data['ficSolicitudAprobarUrl'] = base_url('index.php/Inicio/aprobarSolicitudNuevoFolioTi');
        $data['ficSolicitudRechazarUrl'] = base_url('index.php/Inicio/rechazarSolicitudNuevoFolioTi');
        $data['ficSolicitudEditorMode'] = !empty($tiUsuario) || !empty($secturiAdminUsuario) ? 'json' : 'visual';
        $data['ficSolicitudEditorVisualBaseUrl'] = base_url('index.php/Inicio/SolicitudAlta');
        $data['solicitudesPuedeEditarFolios'] = !empty($tiUsuario) || !empty($secturiAdminUsuario);
        $data['solicitudesPuedeDecidirFolios'] = !empty($this->resolveFolioDecisionUsuario());
        $data['solicitudesPuedeGestionarQr'] = !empty($tiUsuario) || !empty($secturiAdminUsuario);
        $data['solicitudesPuedeGestionarOperativo'] = !empty($tiUsuario) || !empty($secturiAdminUsuario);
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
        $tiUsuario = $this->resolveSecturiDashboardUsuario();

        if (empty($tiUsuario)) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'success' => false,
                'total' => 0,
                'rows' => [],
                'message' => 'No tienes permisos para consultar solicitudes de activación QR.',
            ]);
        }

        $db = \Config\Database::connect();
        $request = $this->request;
        $builder = $db->table('usuario u')
            ->select('
                u.id_usuario,
                u.folio,
                u.usuario,
                u.nombre,
                u.primer_apellido,
                u.segundo_apellido,
                CONCAT_WS(" ", u.nombre, u.primer_apellido, u.segundo_apellido) AS nombre_completo,
                u.correo,
                u.qr,
                u.ine_frontal,
                u.ine_trasera,
                u.firma,
                u.activo_qr,
                u.fec_reg,
                u.fec_act,
                u.visible
            ')
            ->where('u.visible', 1);

        $estatusActivacion = trim((string) ($request->getPost('estatus_activacion') ?? $request->getGet('estatus_activacion') ?? $request->getPost('estatus') ?? $request->getGet('estatus') ?? ''));
        if ($estatusActivacion !== '' && !in_array(strtolower($estatusActivacion), ['todos', 'all'], true)) {
            if ($estatusActivacion === 'pendiente') {
                $builder->where('u.activo_qr', 0);
            } elseif ($estatusActivacion === 'aprobada') {
                $builder->where('u.activo_qr', 1);
            }
        }

        $search = trim((string) ($request->getPost('search') ?? $request->getGet('search') ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('u.id_usuario', $search)
                ->orLike('u.folio', $search)
                ->orLike('u.usuario', $search)
                ->orLike('u.nombre', $search)
                ->orLike('u.primer_apellido', $search)
                ->orLike('u.segundo_apellido', $search)
                ->orLike('u.correo', $search)
                ->groupEnd();
        }

        $sort = trim((string) ($request->getPost('sort') ?? $request->getGet('sort') ?? ''));
        $order = strtolower(trim((string) ($request->getPost('order') ?? $request->getGet('order') ?? 'desc')));
        $allowedSorts = [
            'id_usuario' => 'u.id_usuario',
            'folio' => 'u.folio',
            'usuario' => 'u.usuario',
            'nombre_completo' => 'nombre_completo',
            'correo' => 'u.correo',
            'qr_activo' => 'u.activo_qr',
            'activo_qr' => 'u.activo_qr',
            'fec_reg' => 'u.fec_reg',
            'fec_act' => 'u.fec_act',
        ];
        if (!isset($allowedSorts[$sort])) {
            $sort = 'fec_reg';
        }
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        $total = (clone $builder)->countAllResults();
        $limit = max(1, (int) ($request->getPost('limit') ?? $request->getGet('limit') ?? 10));
        $offset = max(0, (int) ($request->getPost('offset') ?? $request->getGet('offset') ?? 0));

        $rows = $builder
            ->orderBy($allowedSorts[$sort], $order)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        $mapped = array_map(static function (array $row): array {
            $activoQr = (int) ($row['activo_qr'] ?? 0);
            $qr = trim((string) ($row['qr'] ?? ''));
            $ineFrontal = trim((string) ($row['ine_frontal'] ?? ''));
            $ineTrasera = trim((string) ($row['ine_trasera'] ?? ''));
            $firma = trim((string) ($row['firma'] ?? ''));

            return [
                'id_usuario' => (int) ($row['id_usuario'] ?? 0),
                'folio' => (string) ($row['folio'] ?? ''),
                'usuario' => (string) ($row['usuario'] ?? ''),
                'nombre' => (string) ($row['nombre'] ?? ''),
                'primer_apellido' => (string) ($row['primer_apellido'] ?? ''),
                'segundo_apellido' => (string) ($row['segundo_apellido'] ?? ''),
                'nombre_completo' => trim((string) ($row['nombre_completo'] ?? '')),
                'correo' => (string) ($row['correo'] ?? ''),
                'qr' => $qr,
                'ine_frontal' => $ineFrontal,
                'ine_trasera' => $ineTrasera,
                'firma' => $firma,
                'expediente_completo' => ($qr !== '' && $ineFrontal !== '' && $ineTrasera !== '' && $firma !== ''),
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
        if (empty($tiUsuario)) {
            $resolver = new UsuarioPerfilResolver();
            $contextoUsuario = $resolver->resolve(\Config\Services::session()->get());
            $esCajeroSecturi = (string) ($contextoUsuario['active_group'] ?? '') === 'secturi'
                && (int) ($contextoUsuario['group_role'] ?? 0) === 4;
            if (!$esCajeroSecturi) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'No tienes permisos para activar usuarios.',
                ]);
            }
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
            ->select('id_usuario, visible, qr, ine_frontal, ine_trasera, firma, activo_qr')
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

        $qr = trim((string) ($usuario['qr'] ?? ''));
        $ineFrontal = trim((string) ($usuario['ine_frontal'] ?? ''));
        $ineTrasera = trim((string) ($usuario['ine_trasera'] ?? ''));
        $firma = trim((string) ($usuario['firma'] ?? ''));
      /*   if ($qr === '' || $ineFrontal === '' || $ineTrasera === '' || $firma === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El expediente está incompleto o falta el QR generado.',
            ]);
        }
 */
        if ($qr === '' || $ineFrontal === '' || $ineTrasera === '' || $firma === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El expediente esta incompleto o falta el QR generado.',
            ]);
        }
        $service = new DepositosProgramadosService($db);
        $result = $service->activateQrAndApplyDeposits($idUsuario, (int) ($tiUsuario['id_usuario'] ?? 0));
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
        if (empty($tiUsuario)) {
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
            ->select('id_usuario, visible')
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
                'ine_frontal' => null,
                'ine_trasera' => null,
                'firma' => null,
                'fec_act' => date('Y-m-d H:i:s'),
                'usu_act' => (int) ($tiUsuario['id_usuario'] ?? 0),
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
            ->join('cat_fic cf', 'cf.id_perfil_fic = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_fic"', 'left')
            ->join('cat_secul cs', 'cs.id_secul_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_secul"', 'left')
            ->join('cat_ug cu', 'cu.id_ug_perfil = su.id_perfil_solicitado AND su.tipo_solicitud = "alta_usuario_ug"', 'left')
            ->where('su.visible', 1)
            ->whereIn('su.tipo_solicitud', ['alta_usuario_fic', 'alta_usuario_secul', 'alta_usuario_ug']);

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
        if ($idSolicitud <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => false, 'message' => 'Solicitud no válida.']);
        }

        $db = \Config\Database::connect();
        $solicitud = $this->findSolicitudNuevoFolioTi($idSolicitud);
        if (empty($solicitud) || (string) ($solicitud['estatus'] ?? '') !== 'pendiente') {
            $estatusActual = trim((string) ($solicitud['estatus'] ?? 'desconocido'));
            return $this->response->setStatusCode(409)->setJSON(['ok' => false, 'message' => 'La solicitud institucional ya no está pendiente. Estatus actual: ' . $estatusActual . '.']);
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
        $session = ConfigServices::session();
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
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'catálogo no v?lido.']);
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
            $detalleSolicitud[] = 'Hospedaje: sí­';
        }
        if (in_array($beneficiosKey, ['alimentos', 'ambos'], true)) {
            $detalleSolicitud[] = 'Alimentos: sí­';
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
        $session = ConfigServices::session();
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


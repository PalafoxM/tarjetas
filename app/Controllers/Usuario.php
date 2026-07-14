<?php
namespace App\Controllers;

use App\Libraries\DepositosProgramadosService;
use App\Libraries\UsuarioPerfilResolver;
use App\Models\Mglobal;
use CodeIgniter\API\ResponseTrait;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

require_once FCPATH . 'app/Libraries/PHPMailer/Exception.php';
require_once FCPATH . 'app/Libraries/PHPMailer/PHPMailer.php';
require_once FCPATH . 'app/Libraries/PHPMailer/SMTP.php';
require_once FCPATH . '/mpdf/autoload.php';
require_once FCPATH . 'spout/src/Spout/Autoloader/autoload.php';
require_once FCPATH . "qr_code/autoload.php";

class Usuario extends BaseController
{
    use ResponseTrait;

    private $defaultData = array(
        'title' => 'Turnos 2.0',
        'layout' => 'plantilla/lytDefault',
        'contentView' => 'vUndefined',
        'stylecss' => '',
    );
    private $globals;
    private $resolver;
    private $lastS3Error = '';
    private $saveUserScript = 'Usuario.saveCajero';

    public function __construct()
    {
        setlocale(LC_TIME, 'es_ES.utf8', 'es_MX.UTF-8', 'es_MX', 'esp_esp', 'Spanish');
        date_default_timezone_set('America/Mexico_City');
        $session = \Config\Services::session();
        $this->globals = new Mglobal();
        $this->resolver = new UsuarioPerfilResolver();
        if ($session->get('logueado') != 1) {
            header('Location:' . base_url() . 'index.php/Login/cerrar?inactividad=1');
            die();
        }
    }

    private function _renderView($data = array())
    {
        $data = array_merge($this->defaultData, $data);
        echo view($data['layout'], $data);
    }

    public function index()
    {
        $session = \Config\Services::session();
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return redirect()->to(base_url('index.php/Inicio'));
        }

        $data = array();
        $data['scripts'] = array('principal', 'agregar');
        $data['contextoUsuario'] = $actorContext;
        $data['catalogRoleOptions'] = $this->resolver->getAllowedRoleOptions($actorContext);
        $data['contentView'] = 'secciones/vUsuario';
        $this->_renderView($data);
    }

    public function listaUsuario()
    {
        return $this->index();
    }

    public function getUsuarios()
    {
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return $this->response->setStatusCode(403)->setJSON([
                "error" => true,
                "respuesta" => "No tienes permisos para consultar usuarios.",
                "data" => [],
            ]);
        }

        $catalog = $this->buildCatalogRows($actorContext);
        if ($catalog['error']) {
            return $this->response->setStatusCode(502)->setJSON([
                "error" => true,
                "respuesta" => $catalog['respuesta'],
                "data" => [],
            ]);
        }

        return $this->respond($catalog['data']);
    }

    public function getVistaUsuario()
    {
        return $this->getUsuarios();
    }

    public function exportarCajerosXlsx()
    {
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar usuarios.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $catalog = $this->buildCatalogRows($actorContext);
        if (!empty($catalog['error'])) {
            return $this->response->setStatusCode(502)->setBody((string) ($catalog['respuesta'] ?? 'No fue posible consultar usuarios.'));
        }

        $diaLlegada = $this->normalizeExportDate((string) ($this->request->getGet('dia_llegada') ?? ''));
        $rows = $this->filterRowsByDiaLlegada((array) ($catalog['data'] ?? []), $diaLlegada);

        $filename = 'cajeros_' . ($diaLlegada !== '' ? $diaLlegada : 'todos') . '.XLSX';
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser($filename);

        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'ID',
            'Usuario',
            'Nombre completo',
            'Folio',
            'Día de llegada',
            'Vigencia hasta',
            'Perfil',
            'Hospedaje',
            'Alimentos',
            'Saldo reservado',
            'Saldo operativo',
            'Estado del programa',
        ]));

        foreach ($rows as $row) {
            $writer->addRow(WriterEntityFactory::createRowFromArray([
                (int) ($row['id_usuario'] ?? 0),
                (string) ($row['usuario'] ?? ''),
                (string) ($row['nombre_completo'] ?? ''),
                (string) ($row['folio'] ?? ''),
                (string) ($row['fec_vigencia_desde'] ?? ''),
                (string) ($row['fec_vigencia_hasta'] ?? ''),
                (string) ($row['dsc_perfil'] ?? ''),
                ((int) ($row['tiene_hospedaje'] ?? 0) === 1) ? 'Sí' : 'No',
                ((int) ($row['tiene_alimentos'] ?? 0) === 1) ? 'Sí' : 'No',
                number_format((float) ($row['monto_deposito_reservado'] ?? 0), 2, '.', ''),
                number_format((float) ($row['monto_deposito_operativo'] ?? 0), 2, '.', ''),
                $this->labelDepositoProgramado((string) ($row['deposito_programado_estatus'] ?? '')),
            ]));
        }

        $writer->close();
        exit;
    }

    public function exportarCajerosOrdenDiaXlsx()
    {
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para exportar usuarios.');
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $catalog = $this->buildCatalogRows($actorContext);
        if (!empty($catalog['error'])) {
            return $this->response->setStatusCode(502)->setBody((string) ($catalog['respuesta'] ?? 'No fue posible consultar usuarios.'));
        }

        $diaLlegada = $this->normalizeExportDate((string) ($this->request->getGet('dia_llegada') ?? ''));
        $rows = $this->filterRowsByDiaLlegada((array) ($catalog['data'] ?? []), $diaLlegada);

        $filename = 'cajeros_' . ($diaLlegada !== '' ? $diaLlegada : 'todos') . '.xlsx';
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser($filename);

        $writer->addRow(WriterEntityFactory::createRowFromArray([
            'ID',
            'Usuario',
            'Nombre completo',
            'Folio',
            'Vigencia desde',
            'Vigencia hasta',
            'Perfil',
            'Hospedaje',
            'Alimentos',
            'Saldo reservado',
            'Saldo operativo',
            'Documentos',
        ]));

        foreach ($rows as $row) {
            $writer->addRow(WriterEntityFactory::createRowFromArray([
                (int) ($row['id_usuario'] ?? 0),
                (string) ($row['usuario'] ?? ''),
                (string) ($row['nombre_completo'] ?? ''),
                (string) ($row['folio'] ?? ''),
                (string) ($row['fec_vigencia_desde'] ?? ''),
                (string) ($row['fec_vigencia_hasta'] ?? ''),
                (string) ($row['dsc_perfil'] ?? ''),
                ((int) ($row['tiene_hospedaje'] ?? 0) === 1) ? 'Sí' : 'No',
                ((int) ($row['tiene_alimentos'] ?? 0) === 1) ? 'Sí' : 'No',
                number_format((float) ($row['monto_deposito_reservado'] ?? 0), 2, '.', ''),
                number_format((float) ($row['monto_deposito_operativo'] ?? 0), 2, '.', ''),
                $this->summarizeDocumentosExport((array) $row),
            ]));
        }

        $writer->close();
        exit;
    }

    public function verDocumentoUsuario()
    {
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para consultar documentos.');
        }

        $idUsuario = (int) ($this->request->getGet('id_usuario') ?? 0);
        $campo = trim((string) ($this->request->getGet('campo') ?? ''));
        $camposPermitidos = ['qr', 'ine_firma_cajero', 'ine_frontal', 'ine_trasera', 'firma'];

        if ($idUsuario <= 0 || !in_array($campo, $camposPermitidos, true)) {
            return $this->response->setStatusCode(422)->setBody('Solicitud invalida.');
        }

        $row = $this->getBaseUserRow($idUsuario);
        if (!$row) {
            return $this->response->setStatusCode(404)->setBody('Usuario no encontrado.');
        }

        if (!$this->resolver->canViewRow($actorContext, $row)) {
            return $this->response->setStatusCode(403)->setBody('No tienes permisos para consultar este usuario.');
        }

        $archivo = trim((string) ($row[$campo] ?? ''));
        if ($archivo === '') {
            return $this->response->setStatusCode(404)->setBody('Archivo no disponible.');
        }

        $url = $this->buildS3PresignedGetUrl($archivo, 300);
        if ($url === '') {
            return $this->response->setStatusCode(500)->setBody('No fue posible generar el acceso temporal al archivo.');
        }

        return redirect()->to($url);
    }

    public function getUsuariosFic()
    {
        return $this->getUsuariosPorGrupo('fic');
    }

    public function getVistaUsuarioFic()
    {
        return $this->getUsuariosFic();
    }

    public function getUsuariosSecul()
    {
        return $this->getUsuariosPorGrupo('secul');
    }

    public function getVistaUsuarioSecul()
    {
        return $this->getUsuariosSecul();
    }

    public function getUsuariosUg()
    {
        return $this->getUsuariosPorGrupo('ug');
    }

    public function getVistaUsuarioUg()
    {
        return $this->getUsuariosUg();
    }

    public function getUsuario()
    {
        $actorContext = $this->getActorContext();
        if (!$actorContext['can_access_user_catalog']) {
            return $this->failForbidden('No tienes permisos para consultar usuarios.');
        }

        $idUsuario = (int) $this->request->getPost('id_usuario');
        if ($idUsuario <= 0) {
            return $this->fail('Identificador de usuario no valido', 400);
        }

        $row = $this->getBaseUserRow($idUsuario);
        if (!$row) {
            return $this->failNotFound('Usuario no encontrado');
        }

        if (!$this->resolver->canViewRow($actorContext, $row)) {
            return $this->failForbidden('No tienes permisos para consultar este usuario.');
        }

        $targetContext = $this->resolver->resolve($row);
        $row['grupo_usuario'] = $targetContext['id_tipo_proveedor'] > 0
            ? 'proveedor'
            : ($targetContext['active_group'] ?? '');
        $row['perfil_grupo'] = $targetContext['id_tipo_proveedor'] > 0
            ? 0
            : ($targetContext['group_role'] ?? 0);
        $row = $this->resolver->decorateRow($row, $actorContext);

        if ((int) ($row['id_tipo_proveedor'] ?? 0) > 0 || (int) ($row['id_perfil'] ?? 0) === 2) {
            $row = array_merge($row, $this->getProviderProfileDataForUser($row));
        }

        return $this->respond($row);
    }

    public function saveCajero()
    {
        $scriptName = $this->saveUserScript ?: 'Usuario.saveCajero';
        $this->saveUserScript = 'Usuario.saveCajero';
        $session = \Config\Services::session();
        $actorContext = $this->getActorContext();
        if (!$actorContext['can_edit_user_catalog']) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Tu perfil solo puede consultar usuarios.',
            ]);
        }

        $data = $this->request->getPost();
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $usuarioActual = null;
        $usuarioInput = $this->resolveUsuarioInput($data);

        if ($idUsuario > 0) {
            $usuarioActual = $this->getBaseUserRow($idUsuario);
            if (!$usuarioActual) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'El usuario que intentas editar no existe.',
                ]);
            }

            if (!$this->resolver->canMutateRow($actorContext, $usuarioActual)) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'No tienes permisos para editar este usuario.',
                ]);
            }
        }

        $isProviderUser = (($data['grupo_usuario'] ?? '') === 'proveedor')
            || (int) ($data['id_tipo_proveedor'] ?? 0) > 0
            || (int) ($data['id_perfil'] ?? 0) === 2
            || (int) ($usuarioActual['id_tipo_proveedor'] ?? 0) > 0;

        if ($isProviderUser) {
            foreach (['usuario', 'nombre'] as $campo) {
                if (trim((string) ($data[$campo] ?? '')) === '') {
                    return $this->respond([
                        'error' => true,
                        'respuesta' => "El campo {$campo} es requerido",
                    ]);
                }
            }

            $idProveedor = (int) ($data['id_proveedor'] ?? 0);
            if ($idProveedor <= 0) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'Debes seleccionar un proveedor válido del catálogo.',
                ]);
            }

            if ($idUsuario === 0 && trim((string) ($data['contrasenia'] ?? '')) === '') {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'La contraseña es requerida para un proveedor nuevo',
                ]);
            }

            $db = \Config\Database::connect();
            $proveedorQuery = $db->table('proveedor')
                ->select('id_proveedor, id_tipo_proveedor, no_proveedor, razon_social')
                ->where('id_proveedor', $idProveedor)
                ->where('visible', 1)
                ->get();

            if ($proveedorQuery === false) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'No fue posible consultar el proveedor seleccionado.',
                ]);
            }

            $proveedor = $proveedorQuery->getRowArray();

            if (!$proveedor) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'No se encontro el proveedor seleccionado.',
                ]);
            }

            $establecimientoIds = $this->resolveProviderEstablishmentIds($db, $proveedor);
            $idEstablecimientoSolicitado = $this->nullableInt($data['id_establecimiento'] ?? null);
            $idEstablecimiento = null;

            if ($idEstablecimientoSolicitado !== null && in_array((int) $idEstablecimientoSolicitado, $establecimientoIds, true)) {
                $idEstablecimiento = (int) $idEstablecimientoSolicitado;
            } elseif (!empty($establecimientoIds)) {
                $idEstablecimiento = (int) $establecimientoIds[0];
            }

            if ($idEstablecimiento === null || $idEstablecimiento <= 0) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'El proveedor seleccionado no tiene establecimientos visibles ligados.',
                ]);
            }

            $usuarioNormalizado = $usuarioInput;
            if ($usuarioNormalizado === '') {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'El campo usuario es requerido',
                ]);
            }

            if ($this->usuarioExists($usuarioNormalizado, $idUsuario > 0 ? $idUsuario : null)) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => 'El usuario ya existe. Elige otro nombre de usuario.',
                ]);
            }

            $dataInsert = array(
                'usuario' => $usuarioNormalizado,
                'nombre' => trim((string) ($proveedor['razon_social'] ?? ($data['nombre'] ?? ''))),
                'primer_apellido' => '',
                'segundo_apellido' => '',
                'correo' => $this->nullableString(strtolower(trim((string) ($data['correo'] ?? '')))),
                'id_perfil' => 2,
                'id_tipo_proveedor' => (int) ($proveedor['id_tipo_proveedor'] ?? 1),
                'id_proveedor' => (int) ($proveedor['id_proveedor'] ?? 0),
                'id_establecimiento' => $idEstablecimiento,
                'id_nivel_cliente' => null,
                'id_partida' => 0,
                'id_pais' => null,
                'id_clave' => null,
                'monto_deposito' => null,
                'tiene_alimentos' => 0,
                'tiene_hospedaje' => 0,
                'id_establecimiento_hotel' => null,
                'id_tipo_habitacion' => null,
                'fecha_check_in' => null,
                'fecha_check_out' => null,
                'fec_vigencia_desde' => null,
                'fec_vigencia_hasta' => null,
                'noche' => null,
                'tarifa_noche' => null,
                'tarifa_total' => null,
                'nip' => null,
                'qr' => null,
                'api_token' => null,
                'activo_qr' => 0,
                'visible' => 1,
            );
            if (!empty($data['contrasenia'])) {
                $dataInsert['contrasenia'] = password_hash((string) $data['contrasenia'], PASSWORD_BCRYPT);
            }

            if ($idUsuario === 0) {
                $dataInsert['fec_reg'] = date('Y-m-d H:i:s');
                $dataInsert['usu_reg'] = (int) $session->get('id_usuario');
            } else {
                $dataInsert['fec_act'] = date('Y-m-d H:i:s');
                $dataInsert['usu_act'] = (int) $session->get('id_usuario');
            }

            $response = $this->globals->saveTabla(
                $dataInsert,
                [
                    'tabla' => 'usuario',
                    'editar' => $idUsuario > 0 ? 'true' : 'false',
                    'idEditar' => $idUsuario > 0 ? ['id_usuario' => $idUsuario] : null,
                ],
                [
                    'id_user' => (int) $session->get('id_usuario'),
                    'script' => $scriptName,
                ]
            );

            if (!$response->error) {
                $targetUserId = $this->resolveSavedProviderUserId(
                    $response,
                    $idUsuario,
                    (string) ($dataInsert['usuario'] ?? '')
                );

                if ($targetUserId <= 0) {
                    $response->respuesta .= ' El usuario se guardo, pero no fue posible resolver su identificador para sincronizar establecimientos.';
                    return $this->respond($response);
                }

                $syncOk = $this->syncProviderEstablishments(
                    $targetUserId,
                    (int) ($proveedor['id_proveedor'] ?? 0),
                    (int) ($dataInsert['id_tipo_proveedor'] ?? 1),
                    $idEstablecimiento
                );

                if (!$syncOk) {
                    $response->respuesta .= ' El usuario se guardo, pero no fue posible sincronizar la relacion con establecimientos.';
                }
            }

            return $this->respond($response);
        }

        foreach (['nombre', 'primer_apellido', 'correo'] as $campo) {
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
                'respuesta' => 'La contraseña es requerida para un usuario nuevo',
            ]);
        }

        if ($usuarioInput === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El campo usuario es requerido',
            ]);
        }

        $assignment = $this->resolver->applyAssignment($data, $actorContext, $usuarioActual ?? []);
        $selectedProfile = $this->nullableInt($data['id_perfil_catalogo'] ?? $data['id_perfil'] ?? null);
        $legacyProfile = $this->resolveLegacyProfileAlta($selectedProfile, $assignment, $usuarioActual ?? []);
        $grupoUsuario = $this->resolveGrupoUsuarioAlta($data, $assignment, $usuarioActual ?? []);
        $partidaUsuario = $this->resolvePartidaAlta($data, $grupoUsuario, $usuarioActual ?? []);
        $idEstablecimientoAlta = $this->resolveEstablecimientoAlta($data, $grupoUsuario, $selectedProfile, $usuarioActual ?? []);
        $esPerfilTi = (int) ($legacyProfile ?? 0) === 1 || (int) ($selectedProfile ?? 0) === 1;

        if ($esPerfilTi) {
            $grupoUsuario = '';
            $partidaUsuario = null;
            $idEstablecimientoAlta = null;
        }

        if (!$esPerfilTi && $partidaUsuario === null) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes seleccionar una partida para este perfil.',
            ]);
        }

        if (!$esPerfilTi && $idEstablecimientoAlta === null) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes seleccionar un establecimiento para este usuario.',
            ]);
        }

        $dataInsert = [
            'usuario' => $usuarioInput,
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'primer_apellido' => trim((string) ($data['primer_apellido'] ?? '')),
            'segundo_apellido' => trim((string) ($data['segundo_apellido'] ?? '')),
            'correo' => trim((string) ($data['correo'] ?? '')),
            'id_perfil' => $legacyProfile,
            'id_establecimiento' => $idEstablecimientoAlta,
            'id_nivel_cliente' => $this->nullableInt($data['id_nivel_cliente'] ?? null),
            'id_partida' => $partidaUsuario,
            'id_pais' => $this->nullableInt($data['id_pais'] ?? null),
            'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
            'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
            'id_clave' => $this->nullableInt($data['id_clave'] ?? null),
            'folio' => $this->nullableString($data['folio'] ?? $data['folio_ui'] ?? null),
            'sub_folio' => $this->nullableString($data['sub_folio'] ?? $data['subf_ui'] ?? null),
            'folio_grupo' => $this->nullableString($data['folio_grupo'] ?? $data['folio'] ?? $data['folio_ui'] ?? null),
            'pax' => $this->nullableInt($data['pax'] ?? $data['pax_ui'] ?? null),
            'pax_total' => max(1, (int) ($data['pax_total'] ?? $data['pax'] ?? $data['pax_ui'] ?? 1)),
            'pax_secuencia' => (int) ($data['pax_secuencia'] ?? 1),
            'es_titular_folio' => (int) ($data['es_titular_folio'] ?? 1),
            'monto_deposito' => $this->nullableNumeric($data['monto_deposito'] ?? null),
            'monto_deposito_hotel' => $this->nullableNumeric($data['monto_deposito_hotel'] ?? null),
            'monto_deposito_reservado' => 0.00,
            'monto_deposito_operativo' => 0.00,
            'deposito_programado_estatus' => 'sin_programa',
            'tiene_alimentos' => $this->nullableBoolInt($data['tiene_alimentos'] ?? null),
            'tiene_hospedaje' => $this->nullableBoolInt($data['tiene_hospedaje'] ?? null),
            'id_establecimiento_hotel' => $this->nullableInt($data['id_establecimiento_hotel'] ?? null),
            'id_tipo_habitacion' => $this->nullableInt($data['id_tipo_habitacion'] ?? null),
            'fecha_check_in' => $this->nullableString($data['fecha_check_in'] ?? null),
            'fecha_check_out' => $this->nullableString($data['fecha_check_out'] ?? null),
            'fec_vigencia_desde' => $this->nullableString($data['fec_vigencia_desde'] ?? null),
            'fec_vigencia_hasta' => $this->nullableString($data['fec_vigencia_hasta'] ?? null),
            'fec_vigencia_desde_hos' => $this->nullableString($data['fec_vigencia_desde_hos'] ?? null),
            'fec_vigencia_hasta_hos' => $this->nullableString($data['fec_vigencia_hasta_hos'] ?? null),
            'noche' => $this->nullableInt($data['noche'] ?? null),
            'tarifa_noche' => $this->nullableNumeric($data['tarifa_noche'] ?? null),
            'tarifa_total' => $this->nullableNumeric($data['tarifa_total'] ?? null),
        ];
        $dataInsert = array_merge($dataInsert, $assignment);

        if ($esPerfilTi) {
            $dataInsert['tiene_alimentos'] = 0;
            $dataInsert['tiene_hospedaje'] = 0;
            $dataInsert['id_establecimiento_hotel'] = null;
            $dataInsert['id_tipo_habitacion'] = null;
            $dataInsert['fecha_check_in'] = null;
            $dataInsert['fecha_check_out'] = null;
            $dataInsert['fec_vigencia_desde'] = null;
            $dataInsert['fec_vigencia_hasta'] = null;
            $dataInsert['fec_vigencia_desde_hos'] = null;
            $dataInsert['fec_vigencia_hasta_hos'] = null;
            $dataInsert['noche'] = null;
            $dataInsert['tarifa_noche'] = null;
            $dataInsert['tarifa_total'] = 0.00;
            $dataInsert['monto_deposito'] = 0.00;
            $dataInsert['monto_deposito_hotel'] = 0.00;
            $dataInsert['monto_deposito_reservado'] = 0.00;
            $dataInsert['monto_deposito_operativo'] = 0.00;
            $dataInsert['deposito_programado_estatus'] = 'sin_programa';
        }

        if (!empty($data['contrasenia'])) {
            $dataInsert['contrasenia'] = password_hash((string) $data['contrasenia'], PASSWORD_BCRYPT);
        }

        if ($idUsuario === 0) {
            if ((int) ($dataInsert['tiene_hospedaje'] ?? 0) === 1) {
                $dataInsert['fecha_check_in'] = null;
            }
            $dataInsert['nip'] = $this->generateUniquePlainToken('nip', 4, true);
            $dataInsert['api_token'] = $this->generateUniquePlainToken('api_token', 32, false);
            $dataInsert['activo_qr'] = 0;
            $dataInsert['visible'] = 1;
            $dataInsert['fec_reg'] = date('Y-m-d H:i:s');
            $dataInsert['usu_reg'] = (int) $session->get('id_usuario');
        } else {
            $dataInsert['fec_act'] = date('Y-m-d H:i:s');
            $dataInsert['usu_act'] = (int) $session->get('id_usuario');
            unset($dataInsert['activo_qr']);
        }

        if ($idUsuario > 0) {
            $budgetEditError = $this->validateBudgetImmutableOnEdit($usuarioActual ?? [], $dataInsert);
            if ($budgetEditError !== null) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => $budgetEditError,
                ]);
            }
        }

        if ($idUsuario === 0) {
            $response = (new DepositosProgramadosService())->reserveNewUser(
                $dataInsert,
                (int) $session->get('id_usuario'),
                $scriptName
            );
        } else {
            $response = $this->globals->saveTabla(
                $dataInsert,
                [
                    'tabla' => 'usuario',
                    'editar' => 'true',
                    'idEditar' => ['id_usuario' => $idUsuario],
                ],
                [
                    'id_user' => (int) $session->get('id_usuario'),
                    'script' => $scriptName,
                ]
            );
        }

        if (!$response->error) {
            $targetUserId = $idUsuario;
            if ($idUsuario === 0) {
                $targetUserId = $this->resolveSavedUserId($response, $idUsuario, (string) $dataInsert['api_token']);
                if ($targetUserId <= 0) {
                    $response->respuesta .= ' El usuario se guardo, pero no fue posible resolver su id para generar el QR.';
                    return $this->respond($response);
                }
            }

            $apiTokenToUse = trim((string) ($dataInsert['api_token'] ?? $usuarioActual['api_token'] ?? ''));
            $personalData = [
                'usuario' => $dataInsert['usuario'],
                'nombre' => $dataInsert['nombre'],
                'primer_apellido' => $dataInsert['primer_apellido'],
                'segundo_apellido' => $dataInsert['segundo_apellido'],
                'correo' => $dataInsert['correo'],
            ];

            $qrPath = $this->generateInstitutionalQrForUser($targetUserId, $apiTokenToUse, $personalData);
            if ($qrPath !== null) {
                $updateQr = $this->globals->saveTabla(
                    ['qr' => $qrPath],
                    [
                        'tabla' => 'usuario',
                        'editar' => 'true',
                        'idEditar' => ['id_usuario' => $targetUserId],
                    ],
                    [
                        'id_user' => (int) $session->get('id_usuario'),
                        'script' => $scriptName . '.qr',
                    ]
                );

                if ($updateQr->error) {
                    $response->respuesta .= ' El usuario se guardo, pero no se pudo persistir la ruta del QR.';
                } else {
                    $response->qr = $qrPath;
                }
            } else {
                $response->respuesta .= ' El usuario se guardo, pero no se pudo generar/subir el archivo QR.' . ($this->lastS3Error !== '' ? ' Detalle S3: ' . $this->lastS3Error : '');
            }
        }

        return $this->respond($response);
    }
    public function saveUsuario()
    {
        $this->saveUserScript = 'Usuario.saveUsuario';
        return $this->saveCajero();
    }


    public function saveAltaUsuario()
    {
        $scriptName = 'Usuario.saveAltaUsuario';
        $session = \Config\Services::session();
        $actorContext = $this->getActorContext();

        if (!$actorContext['can_edit_user_catalog']) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Tu perfil solo puede consultar usuarios.',
            ]);
        }

        return $this->saveAltaUsuarioPayload($this->request->getPost(), $actorContext, (int) ($session->get('id_usuario') ?? 0), $scriptName);
    }

    private function normalizeHospedajePlanJson($value): ?string
    {
        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($encoded) && $encoded !== '' ? $encoded : null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return null;
        }

        $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) && $encoded !== '' ? $encoded : null;
    }

    private function normalizeHospedajeSobrerreserva($value, ?string $planJson = null): int
    {
        if (is_numeric($value)) {
            return (int) $value === 1 ? 1 : 0;
        }

        if ($planJson !== null && $planJson !== '') {
            $decoded = json_decode($planJson, true);
            if (is_array($decoded) && isset($decoded['sobrerreserva'])) {
                return (int) $decoded['sobrerreserva'] === 1 ? 1 : 0;
            }
        }

        return 0;
    }

    public function saveAltaUsuarioPayload(array $data, array $actorContext, int $idSesionUsuario, string $scriptName = 'Usuario.saveAltaUsuario')
    {
        $db = \Config\Database::connect();
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $usuarioActual = $idUsuario > 0 ? $this->getBaseUserRow($idUsuario) : null;

        if ($idUsuario > 0 && !$usuarioActual) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El usuario que intentas editar no existe.',
            ]);
        }

        if (($data['grupo_usuario'] ?? '') === 'proveedor') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Este flujo es exclusivo para usuarios institucionales.',
            ]);
        }

        $assignment = $this->resolver->applyAssignment($data, $actorContext, $usuarioActual ?? []);
        $selectedProfile = $this->nullableInt($data['id_perfil_catalogo'] ?? $data['id_perfil'] ?? null);
        $legacyProfile = $this->resolveLegacyProfileAlta($selectedProfile, $assignment, $usuarioActual ?? []);
        $grupoUsuario = $this->resolveGrupoUsuarioAlta($data, $assignment, $usuarioActual ?? []);
        $partidaUsuario = $this->resolvePartidaAlta($data, $grupoUsuario, $usuarioActual ?? []);
        $idEstablecimientoAlta = $this->resolveEstablecimientoAlta($data, $grupoUsuario, $selectedProfile, $usuarioActual ?? []);
        $esPerfilTi = (int) ($legacyProfile ?? 0) === 1 || (int) ($selectedProfile ?? 0) === 1;

        if ($esPerfilTi) {
            $grupoUsuario = '';
            $partidaUsuario = null;
            $idEstablecimientoAlta = null;
        }

        if (!$esPerfilTi && $partidaUsuario === null) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes seleccionar una partida para este usuario.',
            ]);
        }

        if (!$esPerfilTi && $idEstablecimientoAlta === null) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes seleccionar un establecimiento para este usuario.',
            ]);
        }

        $folioFuente = trim((string) ($data['folio'] ?? ''));
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($data['folio_ui'] ?? ''));
        }
        if ($folioFuente === '') {
            $folioFuente = trim((string) ($data['folio_grupo'] ?? ''));
        }
        $folio = preg_replace('/\D+/', '', $folioFuente);
        $subFolioBase = trim((string) ($data['sub_folio'] ?? $data['subf_ui'] ?? ''));
        $folioGrupo = in_array($grupoUsuario, ['fic', 'ug', 'secul', 'secturi'], true)
            ? $folio
            : preg_replace('/\D+/', '', (string) ($data['folio_grupo'] ?? $folio));
        $paxTotal = (int) ($data['pax_total'] ?? $data['pax'] ?? $data['pax_ui'] ?? 1);
        $subFoliosComparar = [];
        if ($paxTotal > 1) {
            for ($sequence = 1; $sequence <= $paxTotal; $sequence++) {
                $subFoliosComparar[] = trim($subFolioBase !== '' ? $subFolioBase . '-' . $sequence : (string) $sequence);
            }
        } elseif ($subFolioBase !== '') {
                $subFoliosComparar[] = $subFolioBase;
        }
        $tieneAlimentos = (int) ($data['tiene_alimentos'] ?? 0) === 1;
        $tieneHospedaje = (int) ($data['tiene_hospedaje'] ?? 0) === 1;
        if ($esPerfilTi) {
            $tieneAlimentos = false;
            $tieneHospedaje = false;
        }
        $vigenciaDesde = trim((string) ($data['fec_vigencia_desde'] ?? ''));
        $vigenciaHasta = trim((string) ($data['fec_vigencia_hasta'] ?? ''));
        $vigenciaDesdeHosp = trim((string) ($data['fec_vigencia_desde_hos'] ?? ''));
        $vigenciaHastaHosp = trim((string) ($data['fec_vigencia_hasta_hos'] ?? ''));
        $vigenciaReservaDesde = $vigenciaDesde !== '' ? $vigenciaDesde : $vigenciaDesdeHosp;
        $vigenciaReservaHasta = $vigenciaHasta !== '' ? $vigenciaHasta : $vigenciaHastaHosp;
        $montoDiarioAlimentos = round((float) ($data['monto_deposito'] ?? 0), 2);
        if ($montoDiarioAlimentos <= 0) {
            $montoDiarioAlimentos = $this->resolveNivelClienteMontoDeposito((int) ($data['id_nivel_cliente'] ?? 0));
        }
        if (!$tieneAlimentos) {
            $montoDiarioAlimentos = 0.00;
        }
        $diasAlimentos = $this->calculateDateSpanDays($vigenciaDesde, $vigenciaHasta);
        $tarifaNoche = round((float) ($data['tarifa_noche'] ?? 0), 2);
        if (!$tieneHospedaje) {
            $tarifaNoche = 0.00;
        }
        $noches = max(0, (int) ($data['noche'] ?? 0));
        if (!$tieneHospedaje) {
            $noches = 0;
        }
        if ($noches <= 0 && $vigenciaDesdeHosp !== '' && $vigenciaHastaHosp !== '') {
            $noches = $this->calculateDateSpanDays($vigenciaDesdeHosp, $vigenciaHastaHosp) - 1;
            if ($noches < 0) {
                $noches = 0;
            }
        }
        if ($tarifaNoche <= 0 && (float) ($data['monto_deposito_hotel'] ?? 0) > 0) {
            $tarifaNoche = round((float) ($data['monto_deposito_hotel'] ?? 0), 2);
        }

        $personas = [];
        $personas[] = [
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'primer_apellido' => trim((string) ($data['primer_apellido'] ?? '')),
            'segundo_apellido' => trim((string) ($data['segundo_apellido'] ?? '')),
            'correo' => trim((string) ($data['correo'] ?? '')),
            'usuario' => $this->resolveUsuarioInput($data),
            'contrasenia' => trim((string) ($data['contrasenia'] ?? '')),
        ];

        $usuariosExtra = $data['usuarios'] ?? [];
        if (is_array($usuariosExtra)) {
            foreach (array_values($usuariosExtra) as $personaExtra) {
                if (!is_array($personaExtra)) {
                    continue;
                }

                $persona = [
                    'nombre' => trim((string) ($personaExtra['nombre'] ?? '')),
                    'primer_apellido' => trim((string) ($personaExtra['primer_apellido'] ?? '')),
                    'segundo_apellido' => trim((string) ($personaExtra['segundo_apellido'] ?? '')),
                    'correo' => trim((string) ($personaExtra['correo'] ?? '')),
                    'usuario' => strtolower(trim((string) ($personaExtra['usuario'] ?? ''))),
                    'contrasenia' => trim((string) ($personaExtra['contrasenia'] ?? '')),
                ];

                $personaLlena = implode('', $persona) !== '';
                if ($personaLlena) {
                    $personas[] = $persona;
                }
            }
        }

        $personas = array_values(array_filter($personas, static function (array $persona): bool {
            return trim((string) ($persona['nombre'] ?? '')) !== ''
                || trim((string) ($persona['primer_apellido'] ?? '')) !== ''
                || trim((string) ($persona['correo'] ?? '')) !== ''
                || trim((string) ($persona['usuario'] ?? '')) !== '';
        }));

        if ($paxTotal <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes capturar un numero de pax valido.',
            ]);
        }

        if ($idUsuario > 0 && $paxTotal > 1) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'La edicion multi-pax todavia se gestiona como un solo usuario desde este formulario.',
            ]);
        }

        if ($paxTotal !== count($personas)) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El numero de pax no coincide con los usuarios capturados.',
            ]);
        }

        if (!$esPerfilTi && $folio === '') {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes capturar el folio.',
            ]);
        }

        if ($folioGrupo === '') {
            $folioGrupo = $folio;
        }

        if ($tieneAlimentos && ($vigenciaDesde === '' || $vigenciaHasta === '' || $diasAlimentos <= 0)) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes capturar la vigencia de alimentos completa.',
            ]);
        }

        if ($tieneHospedaje && ($vigenciaDesdeHosp === '' || $vigenciaHastaHosp === '' || $tarifaNoche <= 0 || $noches <= 0)) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Debes capturar la vigencia y tarifa de hospedaje completas.',
            ]);
        }

        $usuariosNormalizados = [];
        foreach ($personas as $index => $persona) {
            $persona['nombre'] = trim((string) ($persona['nombre'] ?? ''));
            $persona['primer_apellido'] = trim((string) ($persona['primer_apellido'] ?? ''));
            $persona['segundo_apellido'] = trim((string) ($persona['segundo_apellido'] ?? ''));
            $persona['correo'] = strtolower(trim((string) ($persona['correo'] ?? '')));
            $persona['usuario'] = strtolower(trim((string) ($persona['usuario'] ?? '')));
            $persona['contrasenia'] = trim((string) ($persona['contrasenia'] ?? ''));

            foreach (['nombre', 'primer_apellido', 'correo', 'usuario'] as $campoRequerido) {
                if ($persona[$campoRequerido] === '') {
                    return $this->respond([
                        'error' => true,
                        'respuesta' => sprintf('El campo %s es requerido para el pax %d.', $campoRequerido, $index + 1),
                    ]);
                }
            }

            if ($persona['contrasenia'] === '') {
                return $this->respond([
                    'error' => true,
                    'respuesta' => sprintf('La contrasena es requerida para el pax %d.', $index + 1),
                ]);
            }

            if (isset($usuariosNormalizados[$persona['usuario']])) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => sprintf('El usuario %s esta repetido dentro del mismo folio.', $persona['usuario']),
                ]);
            }

            $usuariosNormalizados[$persona['usuario']] = true;

            if ($idUsuario <= 0 && $this->usuarioExists($persona['usuario'])) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => sprintf('El usuario %s ya existe. Elige otro nombre de usuario.', $persona['usuario']),
                ]);
            }

            $personas[$index] = $persona;
        }

        $montoTotalAlimentosPax = $tieneAlimentos ? round($montoDiarioAlimentos * $diasAlimentos, 2) : 0.00;
        $montoTotalHospedajePax = $tieneHospedaje ? round($tarifaNoche * $noches, 2) : 0.00;
        $montoTotalPax = round($montoTotalAlimentosPax + $montoTotalHospedajePax, 2);
        $montoTotalGrupo = round($montoTotalPax * $paxTotal, 2);
        $hospedajePlanJson = $this->normalizeHospedajePlanJson($data['hospedaje_plan_json'] ?? null);
        $hospedajeSobrerreserva = $this->normalizeHospedajeSobrerreserva($data['hospedaje_sobrerreserva'] ?? null, $hospedajePlanJson);

        if ($idUsuario > 0) {
            $fechaAhora = date('Y-m-d H:i:s');
            $updateData = [
                'nombre' => $personas[0]['nombre'],
                'primer_apellido' => $personas[0]['primer_apellido'],
                'segundo_apellido' => $personas[0]['segundo_apellido'],
                'correo' => $personas[0]['correo'],
                'usuario' => $personas[0]['usuario'],
                'id_perfil' => $legacyProfile,
                'id_establecimiento' => $idEstablecimientoAlta,
                'id_nivel_cliente' => $this->nullableInt($data['id_nivel_cliente'] ?? null),
                'id_partida' => $partidaUsuario,
                'id_pais' => $this->nullableInt($data['id_pais'] ?? null),
                'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
                'id_clave' => $this->nullableInt($data['id_clave'] ?? null),
                'monto_deposito' => $montoDiarioAlimentos,
                'monto_deposito_hotel' => $montoTotalHospedajePax,
                'monto_deposito_reservado' => $montoTotalPax,
                'monto_deposito_operativo' => 0.00,
                'deposito_programado_estatus' => $montoTotalPax > 0 ? 'reservado' : 'sin_programa',
                'tiene_alimentos' => $tieneAlimentos ? 1 : 0,
                'tiene_hospedaje' => $tieneHospedaje ? 1 : 0,
                'id_establecimiento_hotel' => $this->nullableInt($data['id_establecimiento_hotel'] ?? null),
                'id_tipo_habitacion' => $this->nullableInt($data['id_tipo_habitacion'] ?? null),
                'fecha_check_in' => $this->nullableString($data['fecha_check_in'] ?? null),
                'fecha_check_out' => $this->nullableString($data['fecha_check_out'] ?? null),
                'hospedaje_plan_json' => $hospedajePlanJson,
                'hospedaje_sobrerreserva' => $hospedajeSobrerreserva,
                'fec_vigencia_desde' => $vigenciaDesde !== '' ? $vigenciaDesde : null,
                'fec_vigencia_hasta' => $vigenciaHasta !== '' ? $vigenciaHasta : null,
                'fec_vigencia_desde_hos' => $vigenciaDesdeHosp !== '' ? $vigenciaDesdeHosp : null,
                'fec_vigencia_hasta_hos' => $vigenciaHastaHosp !== '' ? $vigenciaHastaHosp : null,
                'noche' => $noches > 0 ? $noches : null,
                'tarifa_noche' => $tarifaNoche > 0 ? $tarifaNoche : null,
                'tarifa_total' => $montoTotalPax,
                'pax' => $paxTotal,
                'pax_total' => $paxTotal,
                'pax_secuencia' => 1,
                'es_titular_folio' => 1,
                'folio' => $folio,
                'folio_grupo' => $folioGrupo,
                'sub_folio' => $subFolioBase !== '' ? $subFolioBase : null,
                'contrasenia' => password_hash($personas[0]['contrasenia'], PASSWORD_BCRYPT),
                'fec_act' => $fechaAhora,
                'usu_act' => $idSesionUsuario,
            ];

            if (!empty($data['contrasenia'])) {
                $updateData['contrasenia'] = password_hash((string) $data['contrasenia'], PASSWORD_BCRYPT);
            } else {
                unset($updateData['contrasenia']);
            }

            $response = $this->globals->saveTabla(
                $updateData,
                [
                    'tabla' => 'usuario',
                    'editar' => 'true',
                    'idEditar' => ['id_usuario' => $idUsuario],
                ],
                [
                    'id_user' => $idSesionUsuario,
                    'script' => $scriptName,
                ]
            );

            if ($response->error) {
                return $this->respond($response);
            }

            $apiTokenToUse = trim((string) ($usuarioActual['api_token'] ?? ''));
            if ($apiTokenToUse === '') {
                $apiTokenToUse = $this->generateUniquePlainToken('api_token', 32, false);
                $this->globals->saveTabla(
                    [
                        'api_token' => $apiTokenToUse,
                        'fec_act' => $fechaAhora,
                        'usu_act' => $idSesionUsuario,
                    ],
                    [
                        'tabla' => 'usuario',
                        'editar' => true,
                        'idEditar' => ['id_usuario' => $idUsuario],
                    ],
                    [
                        'id_user' => $idSesionUsuario,
                        'script' => $scriptName . '.api_token',
                    ]
                );
            }

            $qrPath = null;
            try {
                $qrPath = $this->generateInstitutionalQrForUser($idUsuario, $apiTokenToUse, $personas[0]);
                if ($qrPath !== null) {
                    $this->globals->saveTabla(
                        [
                            'qr' => $qrPath,
                            'fec_act' => $fechaAhora,
                            'usu_act' => $idSesionUsuario,
                        ],
                        [
                            'tabla' => 'usuario',
                            'editar' => true,
                            'idEditar' => ['id_usuario' => $idUsuario],
                        ],
                        [
                            'id_user' => $idSesionUsuario,
                            'script' => $scriptName . '.qr',
                        ]
                    );
                }
            } catch (\Throwable $e) {
                log_message('error', 'Usuario.saveAltaUsuario.qr: ' . $e->getMessage());
            }

            $mirrorData = $updateData;
            $mirrorData['api_token'] = $apiTokenToUse;
            if (!empty($qrPath)) {
                $mirrorData['qr'] = $qrPath;
            }
            $db->table('usuario')
                ->where('id_usuario', $idUsuario)
                ->update($mirrorData);

            return $this->respond([
                'error' => false,
                'respuesta' => 'Usuario guardado correctamente.',
                'id_usuario' => $idUsuario,
                'pax_total' => 1,
                'monto_total_pax' => $montoTotalPax,
                'monto_total_grupo' => $montoTotalPax,
            ]);
        }

        $duplicadoQuery = $db->table('usuario')
            ->select('id_usuario')
            ->where('visible', 1);

        if (!empty($subFoliosComparar)) {
            $duplicadoQuery->groupStart();
            foreach ($subFoliosComparar as $index => $subFolioComparable) {
                if ($index === 0) {
                    $duplicadoQuery->groupStart()
                        ->where('folio_grupo', $folioGrupo)
                        ->where('sub_folio', $subFolioComparable)
                    ->groupEnd();
                } else {
                    $duplicadoQuery->orGroupStart()
                        ->where('folio_grupo', $folioGrupo)
                        ->where('sub_folio', $subFolioComparable)
                    ->groupEnd();
                }
            }
            $duplicadoQuery->groupEnd();
        } else {
            $duplicadoQuery->groupStart()
                ->where('folio_grupo', $folioGrupo)
                ->orWhere('folio', $folio)
            ->groupEnd();
        }

        if ($duplicadoQuery->limit(1)->get()->getRowArray()) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Ya existe un grupo de usuarios con ese folio.',
            ]);
        }

        $depositosService = new DepositosProgramadosService($db, $this->resolver);
        $fechaAhora = date('Y-m-d H:i:s');
        $idsCreados = [];
        $idUsuarioPadre = null;

        $db->transBegin();
        try {
            foreach ($personas as $index => $persona) {
                $sequence = $index + 1;
                $apiToken = $this->generateUniquePlainToken('api_token', 32, false);
                $nip = $this->generateUniquePlainToken('nip', 4, true);
                $subFolio = $subFolioBase;
                if ($paxTotal > 1) {
                    $subFolio = trim($subFolioBase !== '' ? $subFolioBase . '-' . $sequence : (string) $sequence);
                }

                $insertData = [
                    'id_proveedor' => null,
                    'id_tipo_proveedor' => null,
                    'id_establecimiento' => $idEstablecimientoAlta,
                    'id_perfil' => $legacyProfile,
                    'nombre' => $persona['nombre'],
                    'primer_apellido' => $persona['primer_apellido'],
                    'segundo_apellido' => $persona['segundo_apellido'],
                    'correo' => $persona['correo'],
                    'usuario' => $persona['usuario'],
                    'contrasenia' => password_hash($persona['contrasenia'], PASSWORD_BCRYPT),
                    'tiene_alimentos' => $tieneAlimentos ? 1 : 0,
                    'tiene_hospedaje' => $tieneHospedaje ? 1 : 0,
                    'activo_qr' => 0,
                    'visible' => 1,
                    'id_nivel_cliente' => $this->nullableInt($data['id_nivel_cliente'] ?? null),
                    'id_partida' => $partidaUsuario,
                    'id_fic_perfil' => $this->nullableInt($assignment['id_fic_perfil'] ?? null),
                    'id_ug_perfil' => $this->nullableInt($assignment['id_ug_perfil'] ?? null),
                    'id_secul_perfil' => $this->nullableInt($assignment['id_secul_perfil'] ?? null),
                    'id_secturi_perfil' => $this->nullableInt($assignment['id_secturi_perfil'] ?? null),
                    'id_estatus_hotel' => null,
                    'id_establecimiento_hotel' => $this->nullableInt($data['id_establecimiento_hotel'] ?? null),
                    'id_tipo_habitacion' => $this->nullableInt($data['id_tipo_habitacion'] ?? null),
                    'hospedaje_plan_json' => $hospedajePlanJson,
                    'hospedaje_sobrerreserva' => $hospedajeSobrerreserva,
                    'id_pais' => $this->nullableInt($data['id_pais'] ?? null),
                    'id_clave' => $this->nullableInt($data['id_clave'] ?? null),
                    'id_diciplina' => $this->nullableInt($data['id_diciplina'] ?? null),
                    'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
                    'pax' => $paxTotal,
                    'pax_total' => $paxTotal,
                    'pax_secuencia' => $sequence,
                    'es_titular_folio' => $sequence === 1 ? 1 : 0,
                    'anf_gto' => trim((string) ($data['anf_gto'] ?? $data['anf_gto_ui'] ?? '')) ?: null,
                    'monto_deposito' => $montoDiarioAlimentos,
                    'monto_deposito_hotel' => $montoTotalHospedajePax,
                    'monto_deposito_reservado' => $montoTotalPax,
                    'monto_deposito_operativo' => 0.00,
                    'deposito_programado_estatus' => $montoTotalPax > 0 ? 'reservado' : 'sin_programa',
                    'qr' => null,
                    'nip' => $nip,
                    'folio' => $folio,
                    'folio_grupo' => $folioGrupo,
                    'sub_folio' => $subFolio !== '' ? $subFolio : null,
                    'ruta_foto_relativa' => null,
                    'fecha_check_in' => null,
                    'fecha_check_out' => null,
                    'fec_vigencia_desde' => $vigenciaReservaDesde !== '' ? $vigenciaReservaDesde : null,
                    'fec_vigencia_hasta' => $vigenciaReservaHasta !== '' ? $vigenciaReservaHasta : null,
                    'fec_vigencia_desde_hos' => $vigenciaDesdeHosp !== '' ? $vigenciaDesdeHosp : null,
                    'fec_vigencia_hasta_hos' => $vigenciaHastaHosp !== '' ? $vigenciaHastaHosp : null,
                    'noche' => $noches > 0 ? $noches : null,
                    'tarifa_noche' => $tarifaNoche > 0 ? $tarifaNoche : null,
                    'tarifa_total' => $montoTotalPax,
                    'api_token' => $apiToken,
                    'api_token_expira' => null,
                    'fec_reg' => $fechaAhora,
                    'usu_reg' => $idSesionUsuario,
                    'fec_act' => $fechaAhora,
                    'usu_act' => $idSesionUsuario,
                    'id_usuario_padre' => $sequence === 1 ? null : $idUsuarioPadre,
                ];

                $response = $depositosService->reserveNewUser($insertData, $idSesionUsuario, $scriptName . '.reserve');
                if ($response->error || empty($response->idRegistro)) {
                    throw new \RuntimeException((string) ($response->respuesta ?? 'No fue posible guardar el usuario.'));
                }

                $currentId = (int) $response->idRegistro;
                if ($idUsuarioPadre === null) {
                    $idUsuarioPadre = $currentId;
                }

                $updateData = [
                    'monto_deposito' => number_format($montoDiarioAlimentos, 2, '.', ''),
                    'monto_deposito_hotel' => number_format($montoTotalHospedajePax, 2, '.', ''),
                    'monto_deposito_reservado' => number_format($montoTotalPax, 2, '.', ''),
                    'monto_deposito_operativo' => number_format(0, 2, '.', ''),
                    'deposito_programado_estatus' => $montoTotalPax > 0 ? 'reservado' : 'sin_programa',
                    'pax' => $paxTotal,
                    'pax_total' => $paxTotal,
                    'pax_secuencia' => $sequence,
                    'es_titular_folio' => $sequence === 1 ? 1 : 0,
                    'folio' => $folio,
                    'folio_grupo' => $folioGrupo,
                    'sub_folio' => $subFolio !== '' ? $subFolio : null,
                    'tarifa_noche' => $tarifaNoche > 0 ? number_format($tarifaNoche, 2, '.', '') : null,
                    'tarifa_total' => number_format($montoTotalPax, 2, '.', ''),
                    'id_usuario_padre' => $sequence === 1 ? null : $idUsuarioPadre,
                    'fec_act' => $fechaAhora,
                    'usu_act' => $idSesionUsuario,
                ];

                $db->table('usuario')->where('id_usuario', $currentId)->update($updateData);

                $qrPath = $this->generateInstitutionalQrForUser($currentId, $apiToken, $persona);
                if ($qrPath === null) {
                    throw new \RuntimeException('No fue posible generar el QR del usuario ' . $persona['usuario'] . '.');
                }

                $db->table('usuario')->where('id_usuario', $currentId)->update([
                    'qr' => $qrPath,
                    'fec_act' => $fechaAhora,
                    'usu_act' => $idSesionUsuario,
                ]);

                $idsCreados[] = $currentId;
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Error de transaccion al guardar el grupo de usuarios.');
            }

            $db->transCommit();

            return $this->respond([
                'error' => false,
                'respuesta' => 'Usuarios guardados correctamente.',
                'data' => [
                    'ids' => $idsCreados,
                    'folio_grupo' => $folioGrupo,
                    'pax_total' => $paxTotal,
                    'monto_total_pax' => $montoTotalPax,
                    'monto_total_grupo' => $montoTotalGrupo,
                ],
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Usuario.saveAltaUsuario: ' . $e->getMessage());

            return $this->respond([
                'error' => true,
                'respuesta' => 'No fue posible guardar el grupo de usuarios.',
            ]);
        }
    }
    public function deleteUsuario()
    {
        $session = \Config\Services::session();
        $actorContext = $this->getActorContext();
        if (!$actorContext['can_edit_user_catalog']) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Tu perfil no puede eliminar usuarios.',
            ]);
        }

        $idUsuario = (int) $this->request->getPost('id_usuario');
        if ($idUsuario <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de usuario no valido',
            ]);
        }

        $usuarioActual = $this->getBaseUserRow($idUsuario);
        if (!$usuarioActual) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El usuario no existe o ya no esta disponible.',
            ]);
        }

        if (!$this->resolver->canMutateRow($actorContext, $usuarioActual)) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'No tienes permisos para eliminar este usuario.',
            ]);
        }

        $response = $this->globals->saveTabla(
            [
                'visible' => 0,
                'fec_act' => date('Y-m-d H:i:s'),
                'usu_act' => (int) $session->get('id_usuario'),
            ],
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

    public function subirIneFirmaCajero()
    {
        $session = \Config\Services::session();
        $actorContext = $this->getActorContext();
     

        $idUsuario = (int) $this->request->getPost('id_usuario');
        if ($idUsuario <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Identificador de usuario no valido.',
            ]);
        }

        $usuarioActual = $this->getBaseUserRow($idUsuario);
        if (!$usuarioActual) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => true,
                'respuesta' => 'El usuario no existe o ya no esta disponible.',
            ]);
        }

    

        $campoDocumento = 'ine_firma_cajero';
        $objectPrefix = 'ACTIVACIONESFIC/CAJERO';
        $archivo = $this->request->getFile('ine_firma_cajero');
        if (!$archivo || !$archivo->isValid()) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'Selecciona un archivo valido.',
            ]);
        }

        $extension = strtolower((string) $archivo->getClientExtension());
        $mimeType = strtolower((string) $archivo->getMimeType());
        $mimePermitidos = [
            'pdf' => 'application/pdf',
        ];

        if (!isset($mimePermitidos[$extension])) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El archivo debe ser PDF.',
            ]);
        }

        if ($archivo->getSize() > 10 * 1024 * 1024) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => true,
                'respuesta' => 'El archivo no debe pesar mas de 10 MB.',
            ]);
        }

        $db = \Config\Database::connect();
        if (!$db->fieldExists($campoDocumento, 'usuario')) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'Falta la columna usuario.' . $campoDocumento . ' en la base de datos.',
            ]);
        }

        $tmpDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'cajero';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        if (!is_dir($tmpDir) || !is_writable($tmpDir)) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No se puede escribir el archivo temporal.',
            ]);
        }

        $fileName = $campoDocumento . '_' . $idUsuario . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $archivo->move($tmpDir, $fileName, true);
        $absolutePath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
        $objectKey = rtrim($objectPrefix, '/') . '/' . $fileName;
        $contentType = $mimePermitidos[$extension] ?? ($mimeType !== '' ? $mimeType : 'application/octet-stream');
        $s3Url = $this->uploadFileToS3($absolutePath, $objectKey, $contentType);
        @unlink($absolutePath);

        if ($s3Url === null) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'No fue posible subir el archivo a S3.' . ($this->lastS3Error !== '' ? ' Detalle: ' . $this->lastS3Error : ''),
            ]);
        }

        $actualizado = $db->table('usuario')
            ->where('id_usuario', $idUsuario)
            ->update([
                $campoDocumento => $s3Url,
                'fec_act' => date('Y-m-d H:i:s'),
                'usu_act' => (int) $session->get('id_usuario'),
            ]);

        if (!$actualizado) {
            return $this->response->setStatusCode(500)->setJSON([
                'error' => true,
                'respuesta' => 'El PDF subio a S3, pero no se pudo guardar la ruta en usuario.',
            ]);
        }

        return $this->respond([
            'error' => false,
            'respuesta' => 'PDF INE y firma guardado correctamente.',
            'ruta' => $s3Url,
            'campo' => $campoDocumento,
        ]);
    }

    public function getCatalogosCrud()
    {
        $actorContext = $this->getActorContext();
        if (!$actorContext['can_access_user_catalog']) {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => true,
                'respuesta' => 'No tienes permisos para consultar catálogos.',
            ]);
        }

        return $this->respond([
            'error' => false,
            'respuesta' => 'Consulta exitosa',
            'data' => [
                'categorias' => $this->getCatalogData('cat_claves', ['visible' => 1], 'dsc_clave ASC'),
                'disciplinas' => $this->getCatalogData('cat_diciplina', ['visible' => 1], 'des_diciplina ASC'),
                'paises' => $this->getCatalogData('cat_pais', ['visible' => 1], 'id_pais ASC'),
                'estados' => $this->getCatalogData('cat_estado', ['visible' => 1], 'dsc_estado ASC'),
                'perfiles' => $this->filterPerfilesCatalogo(
                    $this->getCatalogData('cat_perfil', ['visible' => 1], 'id_perfil ASC'),
                    $actorContext
                ),
                'tarifas' => $this->getCatalogData('cat_nivel_cliente', ['visible' => 1], 'id_nivel_cliente ASC'),
                'partidas' => $this->getCatalogData('cat_partida', ['visible' => 1], 'id_partida ASC'),
                'tipos_habitacion' => $this->getCatalogData('cat_tipo_habitacion', ['visible' => 1], 'id_tipo_habitacion ASC'),
                'hotel_tarifas' => $this->getHotelTarifasCatalog(),
                'establecimientos' => $this->getCatalogData('establecimiento', ['visible' => 1], 'dsc_establecimiento ASC'),
                'proveedores' => $this->getProviderCatalog(),
            ],
        ]);
    }

    public function generarPdfOrden($id_usuario)
    {
        $response = $this->globals->getTabla([
            'tabla' => 'vw_usuario',
            'where' => ['id_usuario' => (int) $id_usuario, 'visible' => 1],
        ]);

        if ($response->error || empty($response->data)) {
            return $this->failNotFound('Cajero no encontrado');
        }

        $pdfData = $this->buildUsuarioOrdenPdfData((int) $id_usuario, (array) $response->data[0]);
        $pdfData['firma_usuario_url'] = $this->resolveUsuarioFirmaPdfSrc((int) $id_usuario, $pdfData['firma'] ?? null);

        $html = view('pdfs/vpdfOrdenUnificada', $pdfData);
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'Letter',
            'margin_top' => 18,
            'margin_bottom' => 18,
            'margin_left' => 16,
            'margin_right' => 16,
            'default_font' => 'dejavusans',
            'tempDir' => $this->getMpdfOrdenesTempDir(),
        ]);
        $mpdf->SetTitle('Orden FIC');
        $mpdf->WriteHTML($html);
        $mpdf->Output('orden-fic-' . (int) $id_usuario . '.pdf', 'I');
        exit;
    }

    public function generarPdfHospedaje($id_usuario)
    {
        return $this->generarPdfOrden($id_usuario);
    }

    public function generarPdfAlimentos($id_usuario)
    {
        return $this->generarPdfOrden($id_usuario);
    }

    public function getRecepcion()
    {
        $idEstablecimiento = (int) $this->request->getGet('id_establecimiento');

        if ($idEstablecimiento <= 0) {
            return $this->respond([]);
        }

        if (empty($this->resolveSessionEstablecimiento($idEstablecimiento))) {
            return $this->respond([]);
        }

        $response = $this->globals->getTabla([
            'tabla' => 'vw_usuario_hospedaje',
            'where' => ['visible' => 1, 'id_establecimiento_hotel' => $idEstablecimiento],
        ]);

        return $this->respond($response->data ?? []);
    }

    public function checkInHospedaje()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $this->request->getPost('id_usuario');
        $idEstablecimiento = (int) $this->request->getPost('id_establecimiento');
        $observaciones = trim((string) $this->request->getPost('observaciones', FILTER_SANITIZE_STRING));

        if ($idUsuario <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de usuario no valido',
            ]);
        }

        if ($idEstablecimiento <= 0 || empty($this->resolveSessionEstablecimiento($idEstablecimiento))) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'No tienes acceso al hotel seleccionado.',
            ]);
        }

        $usuarioHospedaje = $this->globals->getTabla([
            'tabla' => 'usuario',
            'where' => [
                'visible' => 1,
                'id_usuario' => $idUsuario,
            ],
        ]);

        if ($usuarioHospedaje->error || empty($usuarioHospedaje->data)) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'No fue posible localizar al huésped.',
            ]);
        }

        $hotelAsignado = (int) ($usuarioHospedaje->data[0]->id_establecimiento_hotel ?? 0);
        if ($hotelAsignado !== $idEstablecimiento) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Este huésped está asignado a otro hotel. No se puede registrar check in en este establecimiento.',
            ]);
        }

        $fechaAhora = $this->fechaHoraGuanajuato();
        $response = $this->globals->saveTabla(
            [
                'fecha_check_in' => $fechaAhora,
                'observaciones_hospedaje' => $observaciones,
                'fec_act' => $fechaAhora,
                'usu_act' => (int) $session->get('id_usuario'),
            ],
            [
                'tabla' => 'usuario',
                'editar' => true,
                'idEditar' => ['id_usuario' => $idUsuario, 'visible' => 1],
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Usuario.checkInHospedaje.usuario',
            ]
        );

        if (!$response->error) {
            $this->globals->saveTabla(
                [
                    'estatus_hospedaje' => 'check_in',
                    'observaciones' => $observaciones
                ],
                [
                    'tabla' => 'usuario_hospedaje',
                    'editar' => true,
                    'idEditar' => ['id_usuario' => $idUsuario, 'visible' => 1],
                ],
                [
                    'id_user' => (int) $session->get('id_usuario'),
                    'script' => 'Usuario.checkInHospedaje',
                ]
            );

            $depositosService = new DepositosProgramadosService();
            $consumoHospedaje = $depositosService->applyHospedajeCheckInConsumption(
                $idUsuario,
                (int) $session->get('id_usuario'),
                $fechaAhora
            );
            if (!empty($consumoHospedaje->error)) {
                log_message('error', 'Usuario.checkInHospedaje consumo hospedaje: ' . ($consumoHospedaje->respuesta ?? 'Error desconocido'));
            }
        }

        return $this->respond($response);
    }

    private function normalizeExportDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches)) {
            return $matches[1];
        }

        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('America/Mexico_City')))->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function filterRowsByDiaLlegada(array $rows, string $diaLlegada): array
    {
        $diaLlegada = $this->normalizeExportDate($diaLlegada);
        $filtered = array_filter($rows, function (array $row) use ($diaLlegada): bool {
            if ($diaLlegada === '') {
                return true;
            }

            return $this->normalizeExportDate((string) ($row['fec_vigencia_desde'] ?? '')) === $diaLlegada;
        });

        return $this->sortRowsByDiaLlegada(array_values($filtered));
    }

    private function sortRowsByDiaLlegada(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $diaA = $this->normalizeExportDate((string) ($a['fec_vigencia_desde'] ?? '')) ?: '9999-12-31';
            $diaB = $this->normalizeExportDate((string) ($b['fec_vigencia_desde'] ?? '')) ?: '9999-12-31';

            if ($diaA < $diaB) {
                return -1;
            }

            if ($diaA > $diaB) {
                return 1;
            }

            $folioA = (string) ($a['folio'] ?? '');
            $folioB = (string) ($b['folio'] ?? '');
            $folioCmp = strnatcasecmp($folioA, $folioB);
            if ($folioCmp !== 0) {
                return $folioCmp;
            }

            return (int) ($a['id_usuario'] ?? 0) <=> (int) ($b['id_usuario'] ?? 0);
        });

        return $rows;
    }

    private function labelDepositoProgramado(string $value): string
    {
        $value = strtolower(trim($value));
        return match ($value) {
            'reservado' => 'Reservado',
            'operativo' => 'Operativo',
            'parcial' => 'Parcial',
            'aplicado' => 'Aplicado',
            'cerrado' => 'Cerrado',
            'vencido' => 'Vencido',
            'error' => 'Error',
            'cancelado' => 'Cancelado',
            'sin_programa' => 'Sin programa',
            default => 'Sin definir',
        };
    }

    private function summarizeDocumentosExport(array $row): string
    {
        $fields = ['qr', 'ine_frontal', 'ine_trasera', 'firma'];
        $count = 0;
        foreach ($fields as $field) {
            if (trim((string) ($row[$field] ?? '')) !== '') {
                $count++;
            }
        }

        return $count > 0 ? (string) $count : 'Sin documentos';
    }

    private function resolveSessionEstablecimiento(int $idEstablecimiento): array
    {
        $session = \Config\Services::session();
        $idSesionUsuario = (int) $session->get('id_usuario');
        if ($idSesionUsuario <= 0 || $idEstablecimiento <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $row = $db->table('establecimiento e')
            ->select('e.id_establecimiento, e.dsc_establecimiento, e.id_tipo, e.no_proveedor')
            ->join('usuario u', 'u.id_usuario = ' . $idSesionUsuario, 'left')
            ->join('proveedor p', 'p.id_proveedor = u.id_proveedor', 'left')
            ->join('usuario_establecimiento ue', 'ue.id_establecimiento = e.id_establecimiento AND ue.id_usuario = ' . $idSesionUsuario . ' AND ue.visible = 1', 'left')
            ->where('e.visible', 1)
            ->where('e.id_establecimiento', $idEstablecimiento)
            ->groupStart()
                ->where('e.no_proveedor = p.no_proveedor', null, false)
                ->orWhere('e.no_proveedor', (string) $idSesionUsuario)
                ->orWhere('ue.id_usuario IS NOT NULL', null, false)
            ->groupEnd()
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : [];
    }

    public function checkOutHospedaje()
    {
        $session = \Config\Services::session();
        $idUsuario = (int) $this->request->getPost('id_usuario');
        $observaciones = trim((string) $this->request->getPost('observaciones', FILTER_SANITIZE_STRING));

        if ($idUsuario <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de usuario no valido',
            ]);
        }

        $fechaAhora = $this->fechaHoraGuanajuato();
        $response = $this->globals->saveTabla(
            [
                'fecha_check_out' => $fechaAhora,
                'fec_act' => $fechaAhora,
                'usu_act' => (int) $session->get('id_usuario'),
            ],
            [
                'tabla' => 'usuario',
                'editar' => true,
                'idEditar' => ['id_usuario' => $idUsuario, 'visible' => 1],
            ],
            [
                'id_user' => (int) $session->get('id_usuario'),
                'script' => 'Usuario.checkOutHospedaje.usuario',
            ]
        );

        if (!$response->error) {
            $this->globals->saveTabla(
                [
                    'estatus_hospedaje' => 'check_out',
                    'observaciones' => $observaciones
                ],
                [
                    'tabla' => 'usuario_hospedaje',
                    'editar' => true,
                    'idEditar' => ['id_usuario' => $idUsuario, 'visible' => 1],
                ],
                [
                    'id_user' => (int) $session->get('id_usuario'),
                    'script' => 'Usuario.checkOutHospedaje',
                ]
            );

            $depositosService = new DepositosProgramadosService();
            $usuarioCheckout = $this->globals->getTabla([
                'tabla' => 'usuario',
                'where' => [
                    'visible' => 1,
                    'id_usuario' => $idUsuario,
                ],
            ]);
            $filaCheckout = (!$usuarioCheckout->error && !empty($usuarioCheckout->data)) ? (array) $usuarioCheckout->data[0] : [];
            if (!empty($filaCheckout)) {
                $depositosService->releaseHospedajePendingOnCheckout($filaCheckout, (int) $session->get('id_usuario'), $fechaAhora);
            }
        }

        return $this->respond($response);
    }

    private function getActorContext(): array
    {
        $session = \Config\Services::session();
        return $this->resolver->resolve($session->get());
    }

    private function fechaHoraGuanajuato(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('America/Mexico_City')))->format('Y-m-d H:i:s');
    }

    private function getBaseUserRow(int $idUsuario): ?array
    {
        $response = $this->globals->getTabla([
            'tabla' => 'usuario',
            'where' => ['visible' => 1, 'id_usuario' => $idUsuario],
        ]);

        if ($response->error || empty($response->data)) {
            return null;
        }

        return (array) $response->data[0];
    }

    private function buildUsuarioOrdenPdfData(int $idUsuario, array $viewData): array
    {
        $usuarioRow = $this->getBaseUserRow($idUsuario) ?? [];
        $data = array_merge($viewData, $usuarioRow);
        $sources = [$data, $usuarioRow, $viewData];

        $nombreCompleto = trim(implode(' ', array_filter([
            trim((string) ($data['nombre'] ?? '')),
            trim((string) ($data['primer_apellido'] ?? '')),
            trim((string) ($data['segundo_apellido'] ?? '')),
        ], static fn($value) => $value !== '')));

        $vigenciaDesde = $this->firstNonEmpty($data, ['fec_vigencia_desde', 'vigente_desde', 'fecha_check_in']);
        $vigenciaHasta = $this->firstNonEmpty($data, ['fec_vigencia_hasta', 'vigente_hasta', 'fecha_check_out']);
        $vigenciaDesdeHosp = $this->firstNonEmpty($data, ['fec_vigencia_desde_hos', 'vigente_desde_hos']);
        $vigenciaHastaHosp = $this->firstNonEmpty($data, ['fec_vigencia_hasta_hos', 'vigente_hasta_hos']);
        $diasVigencia = $this->calculateDateSpanDays($vigenciaDesde, $vigenciaHasta);
        $diasVigenciaHosp = $this->calculateDateSpanDays($vigenciaDesdeHosp, $vigenciaHastaHosp);
        $tarifaDiariaAlimentos = $this->resolveNivelClienteMontoDeposito((int) ($data['id_nivel_cliente'] ?? 0));
        if ($tarifaDiariaAlimentos <= 0) {
            $tarifaDiariaAlimentos = $this->firstPositiveFloatFromSources($sources, [
                'monto_deposito_diario',
                'monto_diario',
                'tarifa_diaria',
                'monto_deposito',
            ]);
        }
        $totalAutorizadoAlimentos = $tarifaDiariaAlimentos > 0 && $diasVigencia > 0
            ? round($tarifaDiariaAlimentos * $diasVigencia, 2)
            : 0.00;
        if ($totalAutorizadoAlimentos <= 0) {
            $totalAutorizadoAlimentos = $this->firstPositiveFloatFromSources($sources, [
                'monto_total_alimentos',
                'monto_deposito_operativo',
            ]);
            if ($tarifaDiariaAlimentos <= 0 && $totalAutorizadoAlimentos > 0 && $diasVigencia > 0) {
                $tarifaDiariaAlimentos = round($totalAutorizadoAlimentos / $diasVigencia, 2);
            }
        }
        $tarifaNocheHospedaje = $this->firstPositiveFloatFromSources($sources, ['tarifa_noche']);
        $nochesHospedaje = max(0, (int) ($data['noche'] ?? 0));
        if ($nochesHospedaje <= 0 && $tarifaNocheHospedaje > 0 && $diasVigenciaHosp > 0) {
            $nochesHospedaje = max(0, $diasVigenciaHosp - 1);
        }
        $totalHospedaje = ($tarifaNocheHospedaje > 0 && $nochesHospedaje > 0)
            ? round($tarifaNocheHospedaje * $nochesHospedaje, 2)
            : $this->firstPositiveFloatFromSources($sources, ['monto_deposito_hotel', 'tarifa_total']);
        if ($totalHospedaje <= 0 && $tarifaNocheHospedaje > 0 && $diasVigenciaHosp > 0) {
            $totalHospedaje = round($tarifaNocheHospedaje * max(0, $diasVigenciaHosp - 1), 2);
        }

        $data['nombre_completo'] = $nombreCompleto !== '' ? $nombreCompleto : trim((string) ($viewData['nombre_completo'] ?? ''));
        $data['usuario_login'] = trim((string) ($data['usuario'] ?? ''));
        $data['folio_entrega'] = $this->firstNonEmptyFromSources($sources, ['folio', 'sub_folio', 'folio_entrega', 'folio_hospedaje']);
        $data['folio'] = $this->firstNonEmptyFromSources($sources, ['folio']);
        $data['sub_folio'] = $this->firstNonEmptyFromSources($sources, ['sub_folio']);
        $data['pax_total'] = max(1, (int) ($data['pax_total'] ?? $data['pax'] ?? 1));
        $data['codigo_qr'] = $this->firstNonEmptyFromSources($sources, ['qr', 'codigo_qr']);
        $data['vigente_desde'] = $vigenciaDesde;
        $data['vigente_hasta'] = $vigenciaHasta;
        $data['vigente_desde_hosp'] = $vigenciaDesdeHosp;
        $data['vigente_hasta_hosp'] = $vigenciaHastaHosp;
        $data['tarifa_resumen'] = [
            'monto_diario' => $tarifaDiariaAlimentos,
            'dias_vigencia' => $diasVigencia,
            'tarifa_total' => $totalAutorizadoAlimentos,
        ];

        $data['beneficios'] = array_merge(is_array($data['beneficios'] ?? null) ? $data['beneficios'] : [], [
            'beneficio_qr_label' => $this->buildUsuarioOrdenBeneficioLabel($data),
            'hotel_nombre' => $this->resolveEstablecimientoNombre((int) ($data['id_establecimiento_hotel'] ?? 0)),
            'tipo_habitacion' => $this->resolveTipoHabitacionNombre((int) ($data['id_tipo_habitacion'] ?? 0)),
            'fecha_check_in' => $data['fecha_check_in'] ?? null,
            'fecha_check_out' => $data['fecha_check_out'] ?? null,
            'vigente_desde_hosp' => $vigenciaDesdeHosp,
            'vigente_hasta_hosp' => $vigenciaHastaHosp,
            'noches' => (int) ($data['noche'] ?? 0),
            'tarifa_noche' => $tarifaNocheHospedaje,
            'tarifa_total_hospedaje' => $totalHospedaje,
            'folio_hospedaje' => $data['folio_entrega'],
            'sub_folio' => $data['sub_folio'],
            'pax_total' => $data['pax_total'],
            'observaciones_hospedaje' => $data['observaciones_hospedaje'] ?? '',
        ]);

        return $data;
    }

    private function firstNonEmpty(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '' && strtoupper($value) !== '\N') {
                return $value;
            }
        }

        return '';
    }

    private function firstPositiveFloat(array $data, array $keys): float
    {
        foreach ($keys as $key) {
            $value = (float) ($data[$key] ?? 0);
            if ($value > 0) {
                return $value;
            }
        }

        return 0.0;
    }

    private function firstNonEmptyFromSources(array $sources, array $keys): string
    {
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }

            $value = $this->firstNonEmpty($source, $keys);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function firstPositiveFloatFromSources(array $sources, array $keys): float
    {
        foreach ($sources as $source) {
            if (!is_array($source)) {
                continue;
            }

            $value = $this->firstPositiveFloat($source, $keys);
            if ($value > 0) {
                return $value;
            }
        }

        return 0.0;
    }

    private function resolveNivelClienteMontoDeposito(int $idNivelCliente): float
    {
        if ($idNivelCliente <= 0) {
            return 0.00;
        }

        $response = $this->globals->getTabla([
            'tabla' => 'cat_nivel_cliente',
            'where' => ['visible' => 1, 'id_nivel_cliente' => $idNivelCliente],
        ]);

        if ($response->error || empty($response->data)) {
            return 0.00;
        }

        return round((float) ($response->data[0]->monto_deposito ?? 0), 2);
    }

    private function calculateDateSpanDays(string $from, string $to): int
    {
        if ($from === '' || $to === '') {
            return 0;
        }

        try {
            $fromDate = new \DateTimeImmutable(date('Y-m-d', strtotime($from)));
            $toDate = new \DateTimeImmutable(date('Y-m-d', strtotime($to)));
        } catch (\Throwable $e) {
            return 0;
        }

        if ($toDate < $fromDate) {
            return 0;
        }

        return (int) $fromDate->diff($toDate)->days + 1;
    }

    private function buildUsuarioOrdenBeneficioLabel(array $data): string
    {
        $tieneAlimentos = (int) ($data['tiene_alimentos'] ?? 0) === 1;
        $tieneHospedaje = (int) ($data['tiene_hospedaje'] ?? 0) === 1;

        if ($tieneAlimentos && $tieneHospedaje) {
            return 'Alimentos y hospedaje';
        }
        if ($tieneHospedaje) {
            return 'Solo hospedaje';
        }
        if ($tieneAlimentos) {
            return 'Solo alimentos';
        }

        return 'Sin beneficio asignado';
    }

    private function resolveEstablecimientoNombre(int $idEstablecimiento): string
    {
        if ($idEstablecimiento <= 0) {
            return 'Sin hotel asignado';
        }

        $response = $this->globals->getTabla([
            'tabla' => 'establecimiento',
            'where' => ['visible' => 1, 'id_establecimiento' => $idEstablecimiento],
        ]);

        if ($response->error || empty($response->data)) {
            return 'Hotel #' . $idEstablecimiento;
        }

        return trim((string) ($response->data[0]->dsc_establecimiento ?? '')) ?: 'Hotel #' . $idEstablecimiento;
    }

    private function resolveTipoHabitacionNombre(int $idTipoHabitacion): string
    {
        if ($idTipoHabitacion <= 0) {
            return 'Sin definir';
        }

        $response = $this->globals->getTabla([
            'tabla' => 'cat_tipo_habitacion',
            'where' => ['visible' => 1, 'id_tipo_habitacion' => $idTipoHabitacion],
        ]);

        if ($response->error || empty($response->data)) {
            return 'Habitacion #' . $idTipoHabitacion;
        }

        return trim((string) ($response->data[0]->dsc_tipo_habitacion ?? '')) ?: 'Habitacion #' . $idTipoHabitacion;
    }

    private function resolveUsuarioFirmaPdfSrc(int $idUsuario, $storedPath = null): string
    {
        $firma = trim((string) $storedPath);
        if ($firma === '' && $idUsuario > 0) {
            $row = $this->getBaseUserRow($idUsuario);
            $firma = trim((string) ($row['firma'] ?? ''));
        }

        if ($firma === '') {
            return '';
        }

        $url = $this->buildS3PresignedGetUrl($firma, 300);
        if ($url === '') {
            return '';
        }

        $imageBody = $this->downloadRemoteFile($url);
        if ($imageBody === '') {
            return '';
        }

        $localPath = $this->persistFirmaPdfImage((int) $idUsuario, $firma, $imageBody);
        return $localPath !== '' ? $localPath : '';
    }

    private function downloadRemoteFile(string $url): string
    {
        if ($url === '') {
            return '';
        }

        if (!function_exists('curl_init')) {
            $body = @file_get_contents($url);
            return is_string($body) ? $body : '';
        }

        $sslVerifyValue = strtolower($this->envFirst(['AWS_SSL_VERIFY', 'S3_SSL_VERIFY'], 'true'));
        $sslVerify = !in_array($sslVerifyValue, ['0', 'false', 'no'], true);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
        ];

        $caInfo = $this->resolveCurlCaInfo();
        if ($sslVerify && $caInfo !== '') {
            $curlOptions[CURLOPT_CAINFO] = $caInfo;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $curlOptions);

        $body = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if (!is_string($body) || $httpCode < 200 || $httpCode >= 300) {
            return '';
        }

        return $body;
    }

    private function persistFirmaPdfImage(int $idUsuario, string $sourceKey, string $imageBody): string
    {
        $directory = $this->getMpdfOrdenesTempDir() . DIRECTORY_SEPARATOR . 'firmas';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (!is_dir($directory) || !is_writable($directory)) {
            return '';
        }

        $hash = substr(hash('sha256', $sourceKey . '|' . $imageBody), 0, 16);
        $jpgPath = $directory . DIRECTORY_SEPARATOR . 'firma_usuario_' . $idUsuario . '_' . $hash . '.jpg';
        if (is_file($jpgPath)) {
            return str_replace('\\', '/', $jpgPath);
        }

        if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
            $source = @imagecreatefromstring($imageBody);
            if ($source !== false) {
                $width = imagesx($source);
                $height = imagesy($source);
                $canvas = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
                imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height);
                imagejpeg($canvas, $jpgPath, 92);
                imagedestroy($canvas);
                imagedestroy($source);

                return is_file($jpgPath) ? str_replace('\\', '/', $jpgPath) : '';
            }
        }

        $fallbackPath = $directory . DIRECTORY_SEPARATOR . 'firma_usuario_' . $idUsuario . '_' . $hash . '.img';
        return file_put_contents($fallbackPath, $imageBody) !== false ? str_replace('\\', '/', $fallbackPath) : '';
    }

    private function getMpdfOrdenesTempDir(): string
    {
        $directory = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf_ordenes';
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory;
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
            return '';
        }

        $objectKey = $this->resolveS3ObjectKey($storedPath, $bucket);
        if ($objectKey === '') {
            return '';
        }

        $service = 's3';
        $host = $bucket . '.s3.' . $region . '.amazonaws.com';
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = $dateStamp . '/' . $region . '/' . $service . '/aws4_request';
        $signedHeaders = 'host';

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $accessKey . '/' . $credentialScope,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) max(60, $expires),
            'X-Amz-SignedHeaders' => $signedHeaders,
        ];

        if ($sessionToken !== '') {
            $query['X-Amz-Security-Token'] = $sessionToken;
        }

        ksort($query);
        $canonicalQuery = [];
        foreach ($query as $key => $value) {
            $canonicalQuery[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }
        $canonicalQueryString = implode('&', $canonicalQuery);
        $canonicalUri = '/' . $this->encodeS3Key($objectKey);
        $canonicalHeaders = 'host:' . $host . "\n";

        $canonicalRequest = implode("\n", [
            'GET',
            $canonicalUri,
            $canonicalQueryString,
            $canonicalHeaders,
            $signedHeaders,
            'UNSIGNED-PAYLOAD',
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = $this->getAwsSignatureKey($secretKey, $dateStamp, $region, $service);
        $query['X-Amz-Signature'] = hash_hmac('sha256', $stringToSign, $signingKey);
        ksort($query);

        $finalQuery = [];
        foreach ($query as $key => $value) {
            $finalQuery[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $value);
        }

        return 'https://' . $host . $canonicalUri . '?' . implode('&', $finalQuery);
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

            if (stripos($host, $bucket . '.s3') === 0 || stripos($host, 's3') === 0) {
                return rawurldecode($urlPath);
            }

            return '';
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }

    private function getProviderProfileDataForUser(array $row): array
    {
        $idUsuario = (int) ($row['id_usuario'] ?? 0);
        if ($idUsuario <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        $relationRows = $db->table('usuario_establecimiento ue')
            ->select('ue.id_establecimiento, ue.id_tipo_proveedor, e.dsc_establecimiento, e.id_tipo, cte.dsc_tipo, e.no_proveedor, p.id_proveedor, p.razon_social, p.rfc')
            ->join('establecimiento e', 'e.id_establecimiento = ue.id_establecimiento', 'left')
            ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
            ->join('proveedor p', 'p.no_proveedor = e.no_proveedor', 'left')
            ->where('ue.id_usuario', $idUsuario)
            ->where('ue.visible', 1)
            ->orderBy('e.dsc_establecimiento', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($relationRows) && (int) ($row['id_establecimiento'] ?? 0) > 0) {
            $relationRows = $db->table('establecimiento e')
                ->select('e.id_establecimiento, ' . ((int) ($row['id_tipo_proveedor'] ?? 0) > 0 ? (int) ($row['id_tipo_proveedor'] ?? 0) : 1) . ' AS id_tipo_proveedor, e.dsc_establecimiento, e.id_tipo, cte.dsc_tipo, e.no_proveedor, p.id_proveedor, p.razon_social, p.rfc')
                ->join('cat_tipo_establecimiento cte', 'cte.id_tipo = e.id_tipo', 'left')
                ->join('proveedor p', 'p.no_proveedor = e.no_proveedor', 'left')
                ->where('e.id_establecimiento', (int) $row['id_establecimiento'])
                ->where('e.visible', 1)
                ->get()
                ->getResultArray();
        }

        if (empty($relationRows)) {
            return [];
        }

        $first = $relationRows[0];
        $names = [];
        $types = [];
        $related = [];

        foreach ($relationRows as $item) {
            $name = trim((string) ($item['dsc_establecimiento'] ?? ''));
            $type = trim((string) ($item['dsc_tipo'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
            if ($type !== '') {
                $types[$type] = true;
            }

            $related[] = [
                'id_establecimiento' => (int) ($item['id_establecimiento'] ?? 0),
                'dsc_establecimiento' => $name,
                'id_tipo' => (int) ($item['id_tipo'] ?? 0),
                'dsc_tipo' => $type,
            ];
        }

        $noProveedor = trim((string) ($first['no_proveedor'] ?? ''));
        $razonSocial = trim((string) ($first['razon_social'] ?? ($row['nombre'] ?? '')));
        $rfc = trim((string) ($first['rfc'] ?? ''));

        return [
            'id_proveedor' => (int) ($first['id_proveedor'] ?? 0),
            'id_tipo_proveedor' => (int) ($first['id_tipo_proveedor'] ?? ($row['id_tipo_proveedor'] ?? 1)),
            'no_proveedor_padron' => $noProveedor,
            'establecimiento_nombre_ui' => implode(', ', array_keys($names)),
            'tipo_establecimiento_ui' => implode(', ', array_keys($types)),
            'establecimientos_relacionados' => $related,
            'proveedor_option_text' => trim(implode(' - ', array_filter([$noProveedor, $razonSocial, $rfc]))),
        ];
    }

    private function usuarioExists(string $usuario, ?int $excludeIdUsuario = null): bool
    {
        $usuario = trim(strtolower($usuario));
        if ($usuario === '') {
            return false;
        }

        $db = \Config\Database::connect();
        $builder = $db->table('usuario')
            ->select('id_usuario')
            ->where('usuario', $usuario);

        if ($excludeIdUsuario !== null && $excludeIdUsuario > 0) {
            $builder->where('id_usuario !=', $excludeIdUsuario);
        }

        return $builder->countAllResults() > 0;
    }

    private function resolveUsuarioInput(array $data): string
    {
        $candidates = [
            (string) ($data['usuario'] ?? ''),
            (string) ($data['nombre_usuario'] ?? ''),
            (string) ($data['usuario_login'] ?? ''),
            (string) ($data['usr_usuario'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim($candidate));
            if ($candidate !== '') {
                return $candidate;
            }
        }

        $correo = strtolower(trim((string) ($data['correo'] ?? '')));
        if ($correo !== '' && str_contains($correo, '@')) {
            $correoUsuario = trim((string) strstr($correo, '@', true));
            if ($correoUsuario !== '') {
                return $correoUsuario;
            }
        }

        return '';
    }

    private function resolveGrupoUsuarioAlta(array $data, array $assignment, array $existingRow = []): string
    {
        $grupo = strtolower(trim((string) ($data['grupo_usuario'] ?? '')));
        if (in_array($grupo, ['fic', 'ug', 'secul', 'secturi', 'proveedor'], true)) {
            return $grupo;
        }

        if ((int) ($assignment['id_tipo_proveedor'] ?? 0) > 0) {
            return 'proveedor';
        }
        if ((int) ($assignment['id_fic_perfil'] ?? 0) > 0) {
            return 'fic';
        }
        if ((int) ($assignment['id_ug_perfil'] ?? 0) > 0) {
            return 'ug';
        }
        if ((int) ($assignment['id_secul_perfil'] ?? 0) > 0) {
            return 'secul';
        }
        if ((int) ($assignment['id_secturi_perfil'] ?? 0) > 0) {
            return 'secturi';
        }

        if ((int) ($existingRow['id_tipo_proveedor'] ?? 0) > 0) {
            return 'proveedor';
        }
        if ((int) ($existingRow['id_fic_perfil'] ?? 0) > 0) {
            return 'fic';
        }
        if ((int) ($existingRow['id_ug_perfil'] ?? 0) > 0) {
            return 'ug';
        }
        if ((int) ($existingRow['id_secul_perfil'] ?? 0) > 0) {
            return 'secul';
        }
        if ((int) ($existingRow['id_secturi_perfil'] ?? 0) > 0) {
            return 'secturi';
        }

        return '';
    }

    private function resolveLegacyProfileAlta(?int $selectedProfile, array $assignment, array $existingRow = []): int
    {
        $selectedProfile = (int) ($selectedProfile ?? 0);
        if ($selectedProfile > 0 && !in_array($selectedProfile, [4, 8, 9, 10], true)) {
            return $selectedProfile;
        }

        return $this->resolver->inferLegacyProfile($assignment, $existingRow);
    }

    private function resolvePartidaAlta(array $data, string $grupoUsuario, array $existingRow = []): ?int
    {
        $idPartidaActual = $this->nullableInt($data['id_partida'] ?? null);
        $idPartidaExistente = $this->nullableInt($existingRow['id_partida'] ?? null);
        $tieneAlimentos = (int) ($data['tiene_alimentos'] ?? 0) === 1;
        $tieneHospedaje = (int) ($data['tiene_hospedaje'] ?? 0) === 1;

        if (in_array($grupoUsuario, ['fic', 'ug'], true)) {
            if ($tieneHospedaje) {
                return 2;
            }

            if ($tieneAlimentos) {
                return 3;
            }

            if ($idPartidaActual !== null && $idPartidaActual > 0) {
                return $idPartidaActual;
            }

            if ($idPartidaExistente !== null && $idPartidaExistente > 0) {
                return $idPartidaExistente;
            }

            return 3;
        }

        if ($idPartidaActual !== null && $idPartidaActual > 0) {
            return $idPartidaActual;
        }

        if ($idPartidaExistente !== null && $idPartidaExistente > 0) {
            return $idPartidaExistente;
        }

        return null;
    }

    private function resolveEstablecimientoAlta(array $data, string $grupoUsuario, ?int $selectedProfile, array $existingRow = []): ?int
    {
        $idEstablecimiento = $this->nullableInt($data['id_establecimiento'] ?? null);
        if ($idEstablecimiento !== null && $idEstablecimiento > 0) {
            return $idEstablecimiento;
        }

        $idEstablecimiento = $this->nullableInt($existingRow['id_establecimiento'] ?? null);
        if ($idEstablecimiento !== null && $idEstablecimiento > 0) {
            return $idEstablecimiento;
        }

        $groupProfileMap = [
            4 => 85,
            8 => 89,
            9 => 90,
            10 => 91,
        ];

        if ($selectedProfile !== null && isset($groupProfileMap[$selectedProfile])) {
            return $groupProfileMap[$selectedProfile];
        }

        if (in_array($grupoUsuario, ['fic', 'ug', 'secul', 'secturi'], true) && isset($groupProfileMap[$selectedProfile ?? 0])) {
            return $groupProfileMap[$selectedProfile ?? 0];
        }

        $session = \Config\Services::session();
        $sessionEstablecimiento = $this->nullableInt($session->get('id_establecimiento') ?? null);
        if ($sessionEstablecimiento !== null && $sessionEstablecimiento > 0) {
            return $sessionEstablecimiento;
        }

        return null;
    }

    private function resolveSavedProviderUserId(object $response, int $currentId, string $usuario): int
    {
        if ($currentId > 0) {
            return $currentId;
        }

        $responseId = (int) ($response->idRegistro ?? 0);
        if ($responseId > 0) {
            return $responseId;
        }

        $usuario = trim($usuario);
        if ($usuario === '') {
            return 0;
        }

        $result = $this->globals->getTabla([
            'tabla' => 'usuario',
            'where' => [
                'usuario' => $usuario,
                'id_perfil' => 2,
                'visible' => 1,
            ],
            'order' => 'id_usuario DESC',
        ]);

        if ($result->error || empty($result->data)) {
            return 0;
        }

        return (int) ($result->data[0]->id_usuario ?? 0);
    }

    private function syncProviderEstablishments(int $idUsuario, int $idProveedor, int $idTipoProveedor, ?int $fallbackEstablecimientoId = null): bool
    {
        if ($idUsuario <= 0) {
            return false;
        }

        $db = \Config\Database::connect();
        $establecimientoIds = [];

        if ($idProveedor > 0) {
            $proveedorQuery = $db->table('proveedor')
                ->select('id_proveedor, no_proveedor')
                ->where('id_proveedor', $idProveedor)
                ->where('visible', 1)
                ->get();

            if ($proveedorQuery === false) {
                return false;
            }

            $proveedor = $proveedorQuery->getRowArray();

            if (!empty($proveedor['no_proveedor'])) {
                $rows = $db->table('establecimiento')
                    ->select('id_establecimiento')
                    ->where('visible', 1)
                    ->where('no_proveedor', $proveedor['no_proveedor'])
                    ->get()
                    ->getResultArray();

                foreach ($rows as $item) {
                    $idEstablecimiento = (int) ($item['id_establecimiento'] ?? 0);
                    if ($idEstablecimiento > 0) {
                        $establecimientoIds[$idEstablecimiento] = $idEstablecimiento;
                    }
                }
            }
        }

        if ($fallbackEstablecimientoId !== null && (int) $fallbackEstablecimientoId > 0) {
            $establecimientoIds[(int) $fallbackEstablecimientoId] = (int) $fallbackEstablecimientoId;
        }

        if (empty($establecimientoIds)) {
            return false;
        }

        $relationTable = $db->table('usuario_establecimiento');
        $relationTable->where('id_usuario', $idUsuario)->update(['visible' => 0]);

        $existingRows = $db->table('usuario_establecimiento')
            ->select('id_usuario_establecimiento, id_establecimiento, id_tipo_proveedor')
            ->where('id_usuario', $idUsuario)
            ->get()
            ->getResultArray();

        $existingIndex = [];
        foreach ($existingRows as $item) {
            $key = (int) ($item['id_establecimiento'] ?? 0) . '|' . (int) ($item['id_tipo_proveedor'] ?? 0);
            $existingIndex[$key] = (int) ($item['id_usuario_establecimiento'] ?? 0);
        }

        foreach (array_values($establecimientoIds) as $idEstablecimiento) {
            $key = $idEstablecimiento . '|' . $idTipoProveedor;
            if (!empty($existingIndex[$key])) {
                $db->table('usuario_establecimiento')
                    ->where('id_usuario_establecimiento', $existingIndex[$key])
                    ->update([
                        'visible' => 1,
                        'id_estatus' => null,
                    ]);
                continue;
            }

            $db->table('usuario_establecimiento')->insert([
                'id_usuario' => $idUsuario,
                'id_establecimiento' => $idEstablecimiento,
                'id_tipo_proveedor' => $idTipoProveedor > 0 ? $idTipoProveedor : 1,
                'id_estatus' => null,
                'visible' => 1,
            ]);
        }

        return true;
    }

    private function resolveProviderEstablishmentIds($db, array $proveedor): array
    {
        $noProveedor = trim((string) ($proveedor['no_proveedor'] ?? ''));
        if ($noProveedor === '') {
            return [];
        }

        $rows = $db->table('establecimiento')
            ->select('id_establecimiento')
            ->where('visible', 1)
            ->where('no_proveedor', $noProveedor)
            ->get()
            ->getResultArray();

        $ids = [];
        foreach ($rows as $item) {
            $idEstablecimiento = (int) ($item['id_establecimiento'] ?? 0);
            if ($idEstablecimiento > 0) {
                $ids[$idEstablecimiento] = $idEstablecimiento;
            }
        }

        return array_values($ids);
    }

    private function getUsuariosPorGrupo(string $catalogoGrupo)
    {
        $actorContext = $this->getActorContext();
        if (empty($actorContext['can_access_user_catalog'])) {
            return $this->response->setStatusCode(403)->setJSON([
                "error" => true,
                "respuesta" => "No tienes permisos para consultar usuarios.",
                "data" => [],
            ]);
        }

        $catalog = $this->buildCatalogRows($actorContext, $catalogoGrupo);
        if ($catalog['error']) {
            return $this->response->setStatusCode(502)->setJSON([
                "error" => true,
                "respuesta" => $catalog['respuesta'],
                "data" => [],
            ]);
        }

        return $this->respond($catalog['data']);
    }

    private function buildCatalogRows(array $actorContext, ?string $catalogoGrupo = null): array
    {
        $localRows = $this->getLocalCatalogRows();
        $baseRows = [];

        if (!empty($localRows)) {
            foreach ($localRows as $row) {
                $idUsuario = (int) ($row['id_usuario'] ?? 0);
                if ($idUsuario <= 0) {
                    continue;
                }

                $baseRows[$idUsuario] = $row;
            }
        } else {
            $baseResponse = $this->globals->getTabla([
                'tabla' => 'vw_usuario',
                'where' => ['visible' => 1],
            ]);

            if ($baseResponse->error) {
                return [
                    'error' => true,
                    'respuesta' => $baseResponse->respuesta,
                    'data' => [],
                ];
            }

            foreach (($baseResponse->data ?? []) as $row) {
                $baseRows[(int) ($row->id_usuario ?? 0)] = (array) $row;
            }
        }

        $displayIndex = [];
        foreach ($baseRows as $idUsuario => $row) {
            $displayIndex[(int) $idUsuario] = (array) $row;
        }

        $documentIndex = [];
        foreach ($localRows as $row) {
            $documentIndex[(int) ($row['id_usuario'] ?? 0)] = [
                'qr' => (string) ($row['qr'] ?? ''),
                'ine_firma_cajero' => (string) ($row['ine_firma_cajero'] ?? ''),
                'ine_frontal' => (string) ($row['ine_frontal'] ?? ''),
                'ine_trasera' => (string) ($row['ine_trasera'] ?? ''),
                'firma' => (string) ($row['firma'] ?? ''),
            ];
        }

        $rows = [];
        foreach (array_values($baseRows) as $baseRow) {
            $baseRow = (array) $baseRow;
            if (!$this->resolver->canViewRow($actorContext, $baseRow)) {
                continue;
            }
            if ($this->isExcludedCatalogUser($baseRow)) {
                continue;
            }

            if ($catalogoGrupo === 'fic' && (int) ($baseRow['id_fic_perfil'] ?? 0) <= 0) {
                continue;
            }
            if ($catalogoGrupo === 'secul' && (int) ($baseRow['id_secul_perfil'] ?? 0) <= 0) {
                continue;
            }
            if ($catalogoGrupo === 'ug' && (int) ($baseRow['id_ug_perfil'] ?? 0) <= 0) {
                continue;
            }

            $idUsuario = (int) ($baseRow['id_usuario'] ?? 0);
            $displayRow = $displayIndex[$idUsuario] ?? [];
            $documentRow = $documentIndex[$idUsuario] ?? [];
            $mergedRow = array_merge($displayRow, $baseRow, $documentRow);
            $mergedRow['expediente_completo'] = trim((string) ($mergedRow['qr'] ?? '')) !== ''
                || trim((string) ($mergedRow['ine_firma_cajero'] ?? '')) !== ''
                || trim((string) ($mergedRow['ine_frontal'] ?? '')) !== ''
                || trim((string) ($mergedRow['ine_trasera'] ?? '')) !== ''
                || trim((string) ($mergedRow['firma'] ?? '')) !== '';
            $mergedRow['nombre_completo'] = trim(implode(' ', array_filter([
                $mergedRow['nombre'] ?? '',
                $mergedRow['primer_apellido'] ?? '',
                $mergedRow['segundo_apellido'] ?? '',
            ])));
            $decoratedRow = $this->resolver->decorateRow($mergedRow, $actorContext);
            if (trim((string) ($decoratedRow['dsc_perfil'] ?? '')) === '') {
                $decoratedRow['dsc_perfil'] = trim(implode(' - ', array_filter([
                    (string) ($decoratedRow['grupo_visible'] ?? ''),
                    (string) ($decoratedRow['rol_visible'] ?? ''),
                ])));
            }
            $rows[] = $decoratedRow;
        }

        return [
            'error' => false,
            'respuesta' => 'Consulta exitosa',
            'data' => $rows,
        ];
    }

    private function getLocalCatalogRows(): array
    {
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('usuario u')
                ->select('u.*')
                ->where('u.visible', 1)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Usuario.getLocalCatalogRows: ' . $e->getMessage());
            return [];
        }

        return array_map(function (array $row): array {
            $row['id'] = $row['id'] ?? ($row['id_usuario'] ?? null);
            $row['nombre_completo'] = trim(implode(' ', array_filter([
                (string) ($row['nombre'] ?? ''),
                (string) ($row['primer_apellido'] ?? ''),
                (string) ($row['segundo_apellido'] ?? ''),
            ])));
            $row['codigo_qr'] = $row['codigo_qr'] ?? ($row['qr'] ?? '');
            $row['monto'] = $row['monto'] ?? ($row['monto_deposito'] ?? 0);
            $row['monto_hotel'] = $row['monto_hotel'] ?? ($row['monto_deposito_hotel'] ?? 0);
            $row['tarifa_hotel'] = $row['tarifa_hotel'] ?? ($row['tarifa_noche'] ?? 0);
            $row['saldo_disponible'] = $row['saldo_disponible'] ?? ($row['monto_deposito_operativo'] ?? 0);
            $row['current_balance'] = $row['current_balance'] ?? ($row['monto_deposito_operativo'] ?? 0);
            $row['created_at'] = $row['created_at'] ?? ($row['fec_reg'] ?? null);
            $row['updated_at'] = $row['updated_at'] ?? ($row['fec_act'] ?? null);

            return $row;
        }, $rows);
    }

    private function getCatalogData(string $table, array $where = [], ?string $order = null): array
    {
        $config = ['tabla' => $table];
        if (!empty($where)) {
            $config['where'] = $where;
        }
        if ($order) {
            $config['order'] = $order;
        }

        $response = $this->globals->getTabla($config);
        if ($response->error) {
            return [];
        }

        return array_map(static function ($row) {
            return (array) $row;
        }, $response->data ?? []);
    }

    private function getHotelTarifasCatalog(): array
    {
        $candidates = [
            'hotel_tipo_habitacion_tarifa',
            'id_hotel_tipo_habitacion_tarifa',
        ];

        foreach ($candidates as $table) {
            $rows = $this->getCatalogData($table, ['visible' => 1, 'activo' => 1], 'id_establecimiento ASC');
            if (!empty($rows)) {
                return array_map(static function (array $row) {
                    $row['hotel_tarifa_id'] = $row['id_hotel_tipo_habitacion']
                        ?? $row['id_hotel_tipo_habitacion_tarifa']
                        ?? null;
                    return $row;
                }, $rows);
            }
        }

        return [];
    }

    private function getProviderCatalog(): array
    {
        $proveedores = $this->getCatalogData('proveedor', ['visible' => 1, 'id_tipo_proveedor' => 1], 'razon_social ASC');
        if (empty($proveedores)) {
            return [];
        }

        $establecimientos = $this->getCatalogData('establecimiento', ['visible' => 1], 'dsc_establecimiento ASC');
        $tipos = $this->getCatalogData('cat_tipo_establecimiento', [], 'dsc_tipo ASC');

        $establecimientosPorProveedor = [];
        foreach ($establecimientos as $establecimiento) {
            $establecimientosPorProveedor[(string) ($establecimiento['no_proveedor'] ?? '')] = $establecimiento;
        }

        $tiposIndex = [];
        foreach ($tipos as $tipo) {
            $tiposIndex[(int) ($tipo['id_tipo'] ?? 0)] = $tipo;
        }

        return array_map(static function (array $proveedor) use ($establecimientosPorProveedor, $tiposIndex) {
            $noProveedor = (string) ($proveedor['no_proveedor'] ?? '');
            $establecimiento = $establecimientosPorProveedor[$noProveedor] ?? [];
            $idTipo = (int) ($establecimiento['id_tipo'] ?? 0);
            $tipo = $tiposIndex[$idTipo] ?? [];

            $proveedor['id_establecimiento'] = $establecimiento['id_establecimiento'] ?? null;
            $proveedor['dsc_establecimiento'] = $establecimiento['dsc_establecimiento'] ?? '';
            $proveedor['id_tipo'] = $idTipo ?: null;
            $proveedor['dsc_tipo'] = $tipo['dsc_tipo'] ?? '';
            $proveedor['search_label'] = trim(implode(' - ', array_filter([
                $proveedor['no_proveedor'] ?? '',
                $proveedor['razon_social'] ?? '',
                $proveedor['rfc'] ?? '',
            ])));

            return $proveedor;
        }, $proveedores);
    }

    private function filterPerfilesCatalogo(array $perfiles, array $actorContext): array
    {
        if ($actorContext['is_ti_master'] || (int) ($actorContext['id_perfil'] ?? 0) === 1) {
            return array_values(array_filter($perfiles, static function ($perfil) {
                return in_array((int) ($perfil['id_perfil'] ?? 0), [1, 4, 8, 9, 10], true);
            }));
        }

        $perfiles = array_values(array_filter($perfiles, static function ($perfil) {
            return in_array((int) ($perfil['id_perfil'] ?? 0), [4, 8, 9, 10], true);
        }));

        // Cajero SECTURI debe poder ver todo el catálogo visible, incluidos subperfiles.
        if (!empty($actorContext['is_secturi_cajero'])) {
            return $perfiles;
        }

        $allowedByGroup = [
            'fic' => [9],
            'secul' => [8],
            'ug' => [10],
            'secturi' => [4],
        ];

        $allowed = $allowedByGroup[$actorContext['active_group'] ?? ''] ?? [];
        if (empty($allowed)) {
            return [];
        }

        return array_values(array_filter($perfiles, static function ($perfil) use ($allowed) {
            return in_array((int) ($perfil['id_perfil'] ?? 0), $allowed, true);
        }));
    }

    private function isExcludedCatalogUser(array $row): bool
    {
        return in_array((int) ($row['id_perfil'] ?? 0), [2, 5, 7], true)
            || (int) ($row['id_tipo_proveedor'] ?? 0) > 0;
    }

    private function resolveSavedUserId(object $response, int $currentId, string $apiToken): int
    {
        if ($currentId > 0) {
            return $currentId;
        }

        $responseId = (int) ($response->idRegistro ?? 0);
        if ($responseId > 0) {
            return $responseId;
        }

        return $this->findUserIdByApiToken($apiToken);
    }

    private function findUserIdByApiToken(string $apiToken): int
    {
        if ($apiToken === '') {
            return 0;
        }

        $result = $this->globals->getTabla([
            'tabla' => 'usuario',
            'where' => ['api_token' => $apiToken, 'visible' => 1],
        ]);

        if ($result->error || empty($result->data)) {
            return 0;
        }

        return (int) ($result->data[0]->id_usuario ?? 0);
    }

    private function validateBudgetImmutableOnEdit(array $usuarioActual, array $dataInsert): ?string
    {
        $fields = [
            'id_partida' => 'Partida presupuestal',
            'monto_deposito' => 'Deposito alimentos',
            'monto_deposito_hotel' => 'Deposito hospedaje',
            'tiene_alimentos' => 'Beneficio alimentos',
            'tiene_hospedaje' => 'Beneficio hospedaje',
            'tarifa_total' => 'Tarifa total hospedaje',
            'tarifa_noche' => 'Tarifa por noche',
            'noche' => 'Noches',
        ];

        $changed = [];
        foreach ($fields as $field => $label) {
            if (!$this->budgetFieldEquals($field, $usuarioActual[$field] ?? null, $dataInsert[$field] ?? null)) {
                $changed[] = $label;
            }
        }

        if (empty($changed)) {
            return null;
        }

        return 'Los campos presupuestales de un usuario ya creado no se pueden editar desde este flujo para evitar dobles descuentos en cat_partida. Campos detectados: '
            . implode(', ', $changed)
            . '.';
    }

    private function budgetFieldEquals(string $field, $current, $next): bool
    {
        if (in_array($field, ['monto_deposito', 'monto_deposito_hotel', 'tarifa_total', 'tarifa_noche'], true)) {
            return round((float) ($current ?? 0), 2) === round((float) ($next ?? 0), 2);
        }

        return (int) ($current ?? 0) === (int) ($next ?? 0);
    }

    private function generateInstitutionalQrForUser(int $idUsuario, string $apiToken, array $personalData = []): ?string
    {
        if ($idUsuario <= 0) {
            return null;
        }

        $payloadToken = trim($apiToken) !== '' ? $apiToken : ('USR-' . $idUsuario);
        $qrPayload = json_encode(array_filter([
            'id_usuario' => $idUsuario,
            'token' => $payloadToken,
            'tipo' => 'usuario_institucional',
            'usuario' => trim((string) ($personalData['usuario'] ?? '')),
            'nombre' => trim((string) ($personalData['nombre'] ?? '')),
            'primer_apellido' => trim((string) ($personalData['primer_apellido'] ?? '')),
            'segundo_apellido' => trim((string) ($personalData['segundo_apellido'] ?? '')),
            'correo' => trim((string) ($personalData['correo'] ?? '')),
        ]), JSON_UNESCAPED_UNICODE);

        if ($qrPayload === false) {
            log_message('error', 'Usuario.generateInstitutionalQrForUser: json_encode payload failed for user ' . $idUsuario);
            return null;
        }

        $tmpDir = WRITEPATH . 'tmp';
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            log_message('error', 'Usuario.generateInstitutionalQrForUser: unable to create tmp dir ' . $tmpDir);
            return null;
        }

        $fileName = 'usuario-' . $idUsuario . '-' . time() . '.png';
        $absolutePath = rtrim($tmpDir, '\/') . DIRECTORY_SEPARATOR . $fileName;

        $result = Builder::create()
            ->data($qrPayload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelMedium())
            ->size(420)
            ->margin(12)
            ->build();

        try {
            $result->saveToFile($absolutePath);
        } catch (\Throwable $e) {
            log_message('error', 'Usuario.generateInstitutionalQrForUser: could not save PNG locally: ' . $e->getMessage());
            return null;
        }

        $keyPrefix = $this->envFirst(['AWS_S3_PREFIX', 'S3_PREFIX', 'AWS_BUCKET_PREFIX'], 'qr_fic');
        $objectKey = trim($keyPrefix, '/');
        $objectKey = ($objectKey !== '' ? $objectKey . '/' : '') . $fileName;
        $qrUrl = $this->uploadFileToS3($absolutePath, $objectKey, 'image/png', false);
        if ($qrUrl === null) {
            $qrUrl = $this->persistInstitutionalQrLocalFallback($absolutePath, $fileName);
        }
        @unlink($absolutePath);

        return $qrUrl;
    }

    private function persistInstitutionalQrLocalFallback(string $absolutePath, string $fileName): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $safeFileName = preg_replace('/[^A-Za-z0-9._-]/', '', $fileName);
        if ($safeFileName === '') {
            $safeFileName = 'usuario-qr-' . time() . '.png';
        }

        $relativeDir = 'uploads/qr_fic';
        $targetDir = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $this->lastS3Error = trim($this->lastS3Error . ' Fallback local no pudo crear directorio QR.');
            log_message('error', 'Usuario.generateInstitutionalQrForUser: local fallback dir unavailable: ' . $targetDir);
            return null;
        }

        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $safeFileName;
        if (!copy($absolutePath, $targetPath)) {
            $this->lastS3Error = trim($this->lastS3Error . ' Fallback local no pudo copiar QR.');
            log_message('error', 'Usuario.generateInstitutionalQrForUser: local fallback copy failed: ' . $targetPath);
            return null;
        }

        log_message('warning', 'Usuario.generateInstitutionalQrForUser: S3 no disponible, QR guardado localmente en ' . $targetPath);
        $relativePath = $relativeDir . '/' . $safeFileName;

        return function_exists('base_url') ? base_url($relativePath) : $relativePath;
    }

    private function uploadFileToS3(string $absolutePath, string $objectKey, string $contentType, bool $logFailureAsError = true): ?string
    {
        $this->lastS3Error = '';
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            $this->lastS3Error = 'No se puede leer el archivo temporal del QR.';
            log_message($logFailureAsError ? 'error' : 'warning', 'Usuario.uploadFileToS3: local file is not readable: ' . $absolutePath);
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
            log_message($logFailureAsError ? 'error' : 'warning', 'Usuario.uploadFileToS3: missing S3 env vars.');
            return null;
        }

        $body = file_get_contents($absolutePath);
        if ($body === false) {
            $this->lastS3Error = 'No se pudo leer el contenido del QR temporal.';
            log_message($logFailureAsError ? 'error' : 'warning', 'Usuario.uploadFileToS3: could not read local file body.');
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
            log_message($logFailureAsError ? 'error' : 'warning', 'Usuario.uploadFileToS3: cURL extension is not available.');
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
            log_message($logFailureAsError ? 'error' : 'warning', 'Usuario.uploadFileToS3: upload failed. HTTP ' . $httpCode . ' ' . $curlError . ' Response: ' . substr((string) $rawResponse, 0, 500));
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

    private function envFirst(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = env($key);
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (trim((string) $value) !== '') {
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

    private function generateUniquePlainToken(string $field, int $length, bool $digitsOnly): string
    {
        $alphabet = $digitsOnly ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxAttempts = 25;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $token = $this->randomTokenFromAlphabet($alphabet, $length);
            $exists = $this->globals->getTabla([
                'tabla' => 'usuario',
                'where' => [$field => $token],
            ]);

            if (!$exists->error && empty($exists->data)) {
                return $token;
            }
        }

        return $this->randomTokenFromAlphabet($alphabet, $length) . date('His');
    }

    private function randomTokenFromAlphabet(string $alphabet, int $length): string
    {
        $token = '';
        $maxIndex = strlen($alphabet) - 1;

        if ($length > 0 && $alphabet === '0123456789') {
            // Evita que un token numérico arranque con cero.
            $token .= $alphabet[random_int(1, $maxIndex)];
            for ($i = 1; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $maxIndex)];
            }

            return $token;
        }

        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $maxIndex)];
        }

        return $token;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableBoolInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ((int) $value) === 1 ? 1 : 0;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

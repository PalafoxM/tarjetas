<?php
namespace App\Controllers;

use App\Libraries\DepositosProgramadosService;
use App\Libraries\UsuarioPerfilResolver;
use App\Models\Mglobal;
use CodeIgniter\API\ResponseTrait;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;

require_once FCPATH . 'app/Libraries/PHPMailer/Exception.php';
require_once FCPATH . 'app/Libraries/PHPMailer/PHPMailer.php';
require_once FCPATH . 'app/Libraries/PHPMailer/SMTP.php';
require_once FCPATH . '/mpdf/autoload.php';
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
            $proveedor = $db->table('proveedor')
                ->select('id_proveedor, id_tipo_proveedor, no_proveedor, razon_social')
                ->where('id_proveedor', $idProveedor)
                ->where('visible', 1)
                ->get()
                ->getRowArray();

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

            $usuarioNormalizado = strtolower(trim((string) ($data['usuario'] ?? '')));
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
                'respuesta' => 'La contraseña es requerida para un usuario nuevo',
            ]);
        }

        $assignment = $this->resolver->applyAssignment($data, $actorContext, $usuarioActual ?? []);
        $selectedProfile = $this->nullableInt($data['id_perfil_catalogo'] ?? $data['id_perfil'] ?? null);
        $legacyProfile = $selectedProfile ?: $this->resolver->inferLegacyProfile($assignment, $usuarioActual  []);
        $dataInsert = [
            'usuario' => trim((string) ($data['usuario'] ?? '')),
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'primer_apellido' => trim((string) ($data['primer_apellido'] ?? '')),
            'segundo_apellido' => trim((string) ($data['segundo_apellido'] ?? '')),
            'correo' => trim((string) ($data['correo'] ?? '')),
            'id_perfil' => $legacyProfile,
            'id_establecimiento' => $this->nullableInt($data['id_establecimiento'] ?? null),
            'id_nivel_cliente' => $this->nullableInt($data['id_nivel_cliente'] ?? null),
            'id_partida' => $this->nullableInt($data['id_partida'] ?? null),
            'id_pais' => $this->nullableInt($data['id_pais'] ?? null),
            'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
            'id_estado' => $this->nullableInt($data['id_estado'] ?? null),
            'id_clave' => $this->nullableInt($data['id_clave'] ?? null),
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
            'noche' => $this->nullableInt($data['noche'] ?? null),
            'tarifa_noche' => $this->nullableNumeric($data['tarifa_noche'] ?? null),
            'tarifa_total' => $this->nullableNumeric($data['tarifa_total'] ?? null),
        ];
        $dataInsert = array_merge($dataInsert, $assignment);

        if (!empty($data['contrasenia'])) {
            $dataInsert['contrasenia'] = password_hash((string) $data['contrasenia'], PASSWORD_BCRYPT);
        }

        if ($idUsuario === 0) {
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
            $budgetEditError = $this->validateBudgetImmutableOnEdit($usuarioActual  [], $dataInsert);
            if ($budgetEditError !== null) {
                return $this->respond([
                    'error' => true,
                    'respuesta' => $budgetEditError,
                ]);
            }
        }

        if ($idUsuario === 0) {
            $response = $this->saveNewUserWithProgrammedDeposits(
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
        $response = $this->globals->getTabla([
            'tabla' => 'vw_usuario',
            'where' => ['id_usuario' => (int) $id_usuario, 'visible' => 1],
        ]);
        $pdfData = $response->data[0];

        $html = view('pdfs/vpdfOrdenAlimentos', (array) $pdfData);
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

    public function getRecepcion()
    {
        $session = \Config\Services::session();
        $idEstablecimiento = (int) $this->request->getGet('id_establecimiento');

        if ($idEstablecimiento <= 0) {
            return $this->respond([]);
        }

        $establecimiento = $this->globals->getTabla([
            'tabla' => 'establecimiento',
            'where' => [
                'visible' => 1,
                'id_establecimiento' => $idEstablecimiento,
                'no_proveedor' => (int) $session->get('id_usuario'),
            ],
        ]);

        if ($establecimiento->error || empty($establecimiento->data)) {
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
        $observaciones = trim((string) $this->request->getPost('observaciones', FILTER_SANITIZE_STRING));

        if ($idUsuario <= 0) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'Identificador de usuario no valido',
            ]);
        }

        $response = $this->globals->saveTabla(
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

        return $this->respond($response);
    }

    private function getActorContext(): array
    {
        $session = \Config\Services::session();
        return $this->resolver->resolve($session->get());
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
            $proveedor = $db->table('proveedor')
                ->select('id_proveedor, no_proveedor')
                ->where('id_proveedor', $idProveedor)
                ->where('visible', 1)
                ->get()
                ->getRowArray();

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

        
        
            $displayResponse = $this->globals->getTabla([
                'tabla' => 'vw_usuario',
                'where' => ['visible' => 1],
            ]);

        $displayIndex = [];
        if (!$displayResponse->error && !empty($displayResponse->data)) {
            foreach ($displayResponse->data as $row) {
                $displayIndex[(int) ($row->id_usuario ?? 0)] = (array) $row;
            }
        }

        $rows = [];
        foreach (($baseResponse->data ?? []) as $row) {
            $baseRow = (array) $row;
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
            $mergedRow = array_merge($displayRow, $baseRow);
            $mergedRow['nombre_completo'] = trim(implode(' ', array_filter([
                $mergedRow['nombre'] ?? '',
                $mergedRow['primer_apellido'] ?? '',
                $mergedRow['segundo_apellido'] ?? '',
            ])));
            $rows[] = $this->resolver->decorateRow($mergedRow, $actorContext);
        }

        return [
            'error' => false,
            'respuesta' => 'Consulta exitosa',
            'data' => $rows,
        ];
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
        $perfiles = array_values(array_filter($perfiles, static function ($perfil) {
            return in_array((int) ($perfil['id_perfil'] ?? 0), [4, 8, 9, 10], true);
        }));

        if ($actorContext['is_ti_master'] || (int) ($actorContext['id_perfil'] ?? 0) === 1) {
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

    private function saveNewUserWithProgrammedDeposits(array $dataInsert, int $actorUserId, string $scriptName): object
    {
        $service = new DepositosProgramadosService();
        return $service->reserveNewUser($dataInsert, $actorUserId, $scriptName);

        $response = new \stdClass();
        $response->error = true;
        $response->respuesta = 'Error | No fue posible guardar el usuario';

        $this->normalizeProgrammedDepositsForUser($dataInsert);

        $allocations = $this->buildPartidaDepositAllocations($dataInsert);
        if (!empty($allocations['error'])) {
            $response->respuesta = $allocations['respuesta'];
            return $response;
        }

        $allocationRows = $allocations['data'] ?? [];
        $primaryPartida = $this->resolvePrimaryPartidaForUser($dataInsert, $allocationRows);
        $dataInsert['id_partida'] = $primaryPartida;

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            foreach ($allocationRows as $allocation) {
                $partida = $db->query(
                    'SELECT id_partida, partida, monto_presupuesto, monto_ejercido, monto_disponible, estatus
                     FROM cat_partida
                     WHERE id_partida =  AND visible = 1
                     FOR UPDATE',
                    [(int) $allocation['id_partida']]
                )->getRowArray();

                if (empty($partida)) {
                    throw new \RuntimeException('La partida presupuestal no existe o no está visible: ' . $allocation['id_partida']);
                }

                $monto = round((float) $allocation['monto'], 2);
                $disponible = round((float) ($partida['monto_disponible'] ?? 0), 2);
                if ($monto > $disponible) {
                    throw new \RuntimeException(
                        'Presupuesto insuficiente en partida ' . ($partida['partida'] ?? $allocation['id_partida'])
                        . '. Disponible: $' . number_format($disponible, 2)
                        . ', requerido: $' . number_format($monto, 2)
                    );
                }

                $nuevoEjercido = round((float) ($partida['monto_ejercido'] ?? 0) + $monto, 2);
                $nuevoDisponible = round($disponible - $monto, 2);
                $presupuesto = round((float) ($partida['monto_presupuesto'] ?? 0), 2);
                $porcentaje = $presupuesto > 0 ? round(($nuevoEjercido / $presupuesto) * 100, 2) : 0.0;

                $db->table('cat_partida')
                    ->where('id_partida', (int) $allocation['id_partida'])
                    ->update([
                        'monto_ejercido' => number_format($nuevoEjercido, 2, '.', ''),
                        'monto_disponible' => number_format($nuevoDisponible, 2, '.', ''),
                        'porcentaje_ejercido' => number_format($porcentaje, 2, '.', ''),
                        'estatus' => $nuevoDisponible <= 0 ? 'agotada' : ($partida['estatus'] === 'agotada' ? 'activa' : $partida['estatus']),
                        'fec_act' => date('Y-m-d H:i:s'),
                        'usu_act' => $actorUserId,
                    ]);
            }

            $db->table('usuario')->insert($dataInsert);
            $idUsuario = (int) $db->insertID();
            if ($idUsuario <= 0) {
                throw new \RuntimeException('No fue posible resolver el usuario creado.');
            }

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Error de transaccion al guardar usuario y presupuesto.');
            }

            $db->transCommit();

            $response->error = false;
            $response->respuesta = 'Registro guardado correctamente';
            $response->idRegistro = $idUsuario;
            $response->depositos_programados = $allocationRows;
            $response->script = $scriptName;
            return $response;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Usuario.saveNewUserWithProgrammedDeposits: ' . $e->getMessage());
            $response->respuesta = 'Error | ' . $e->getMessage();
            return $response;
        }
    }

    private function normalizeProgrammedDepositsForUser(array &$dataInsert): void
    {
        if ((int) ($dataInsert['tiene_hospedaje'] ?? 0) !== 1) {
            return;
        }

        $montoHospedaje = $this->resolveHospedajeDepositAmount($dataInsert);
        if ($montoHospedaje <= 0) {
            return;
        }

        if (round((float) ($dataInsert['monto_deposito_hotel'] ?? 0), 2) <= 0) {
            $dataInsert['monto_deposito_hotel'] = $montoHospedaje;
        }

        if (round((float) ($dataInsert['tarifa_total'] ?? 0), 2) <= 0) {
            $dataInsert['tarifa_total'] = $montoHospedaje;
        }
    }

    private function buildPartidaDepositAllocations(array $dataInsert): array
    {
        $context = $this->resolver->resolve($dataInsert);
        if ((int) ($context['id_tipo_proveedor'] ?? 0) > 0 || in_array((int) ($dataInsert['id_perfil'] ?? 0), [2, 5, 7], true)) {
            return ['error' => false, 'data' => []];
        }

        $allocations = [];
        $montoAlimentos = round((float) ($dataInsert['monto_deposito'] ?? 0), 2);
        if ((int) ($dataInsert['tiene_alimentos'] ?? 0) === 1 && $montoAlimentos > 0) {
            $foodPartida = $this->resolveFoodPartidaByContext($context);
            if ($foodPartida === null) {
                return [
                    'error' => true,
                    'respuesta' => 'No hay partida de alimentos configurada para el grupo del usuario.',
                ];
            }

            $allocations[] = [
                'id_partida' => $foodPartida,
                'tipo' => 'alimentos',
                'monto' => $montoAlimentos,
            ];
        }

        $montoHospedaje = $this->resolveHospedajeDepositAmount($dataInsert);
        if ((int) ($dataInsert['tiene_hospedaje'] ?? 0) === 1 && $montoHospedaje > 0) {
            $allocations[] = [
                'id_partida' => 2,
                'tipo' => 'hospedaje',
                'monto' => $montoHospedaje,
            ];
        }

        return [
            'error' => false,
            'data' => $this->mergePartidaAllocations($allocations),
        ];
    }

    private function resolveFoodPartidaByContext(array $context): ?int
    {
        $group = (string) ($context['active_group'] ?? '');
        if (in_array($group, ['secturi', 'secul'], true)) {
            return 1;
        }
        if ($group === 'fic') {
            return 3;
        }

        return null;
    }

    private function resolveHospedajeDepositAmount(array $dataInsert): float
    {
        $monto = round((float) ($dataInsert['monto_deposito_hotel'] ?? 0), 2);
        if ($monto > 0) {
            return $monto;
        }

        $tarifaTotal = round((float) ($dataInsert['tarifa_total'] ?? 0), 2);
        if ($tarifaTotal > 0) {
            return $tarifaTotal;
        }

        $tarifaNoche = round((float) ($dataInsert['tarifa_noche'] ?? 0), 2);
        $noches = max(0, (int) ($dataInsert['noche'] ?? 0));
        return round($tarifaNoche * $noches, 2);
    }

    private function mergePartidaAllocations(array $allocations): array
    {
        $merged = [];
        foreach ($allocations as $allocation) {
            $idPartida = (int) ($allocation['id_partida'] ?? 0);
            if (!isset($merged[$idPartida])) {
                $merged[$idPartida] = [
                    'id_partida' => $idPartida,
                    'tipo' => (string) ($allocation['tipo'] ?? ''),
                    'monto' => 0.0,
                ];
            }

            $merged[$idPartida]['monto'] = round($merged[$idPartida]['monto'] + (float) ($allocation['monto'] ?? 0), 2);
            if (!str_contains($merged[$idPartida]['tipo'], (string) ($allocation['tipo'] ?? ''))) {
                $merged[$idPartida]['tipo'] = trim($merged[$idPartida]['tipo'] . '+' . (string) ($allocation['tipo'] ?? ''), '+');
            }
        }

        return array_values($merged);
    }

    private function resolvePrimaryPartidaForUser(array $dataInsert, array $allocations): int
    {
        if (empty($allocations)) {
            return 0;
        }

        $current = (int) ($dataInsert['id_partida'] ?? 0);
        foreach ($allocations as $allocation) {
            if ((int) ($allocation['id_partida'] ?? 0) === $current) {
                return $current;
            }
        }

        return (int) ($allocations[0]['id_partida'] ?? 0);
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
        $qrUrl = $this->uploadFileToS3($absolutePath, $objectKey, 'image/png');
        @unlink($absolutePath);

        return $qrUrl;
    }

    private function uploadFileToS3(string $absolutePath, string $objectKey, string $contentType): ?string
    {
        $this->lastS3Error = '';
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            $this->lastS3Error = 'No se puede leer el archivo temporal del QR.';
            log_message('error', 'Usuario.uploadFileToS3: local file is not readable: ' . $absolutePath);
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
            log_message('error', 'Usuario.uploadFileToS3: missing S3 env vars.');
            return null;
        }

        $body = file_get_contents($absolutePath);
        if ($body === false) {
            $this->lastS3Error = 'No se pudo leer el contenido del QR temporal.';
            log_message('error', 'Usuario.uploadFileToS3: could not read local file body.');
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
            log_message('error', 'Usuario.uploadFileToS3: cURL extension is not available.');
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
            log_message('error', 'Usuario.uploadFileToS3: upload failed. HTTP ' . $httpCode . ' ' . $curlError . ' Response: ' . substr((string) $rawResponse, 0, 500));
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

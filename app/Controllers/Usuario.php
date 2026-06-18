<?php
namespace App\Controllers;

use App\Libraries\UsuarioPerfilResolver;
use App\Models\Mglobal;
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
    private $globals;
    private $resolver;

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
        $data = array();
        $data['unidad'] = $this->globals->getTabla(["tabla" => "cat_clues", "select" => "id_clues, NOMBRE_UNIDAD", "where" => ["visible" => 1], 'limit' => 10]);
        $data['perfiles'] = $this->globals->getTabla(["tabla" => "seg_perfiles", "where" => ["visible" => 1]]);
        $data['cat_sexo'] = $this->globals->getTabla(["tabla" => "cat_sexo", "where" => ["visible" => 1]]);
        $data['scripts'] = array('principal', 'inicio');
        $data['edita'] = 0;
        $data['nombre_completo'] = $session->nombre_completo;
        $data['contentView'] = 'secciones/vUsuarios';
        $this->_renderView($data);
    }

    public function getUsuarios()
    {
        $session = \Config\Services::session();
        if($session->get('logueado') != 1){
            $actorContext = $this->getActorContext();
            if (!$actorContext['can_access_user_catalog']) {
                return $this->response->setStatusCode(403)->setJSON([
                    'error' => true,
                    'respuesta' => 'No tienes permisos para consultar usuarios.',
                    'data' => [],
                ]);
            }

            $catalog = $this->buildCatalogRows($actorContext);
            if ($catalog['error']) {
                return $this->response->setStatusCode(502)->setJSON([
                    'error' => true,
                    'respuesta' => $catalog['respuesta'],
                    'data' => [],
                ]);
            }

            return $this->respond($catalog['data']);
        }else{
            $Mglobal = new Mglobal;
            $datos = $Mglobal->getTabla(['tabla' => "vw_usuario", "where"=> ['visible' => 1]]);
            return $this->respond($datos->data ?? []);
        }

    }

    public function getVistaUsuario()
    {
        return $this->getUsuarios();
    }

    public function getUsuario()
    {
        $actorContext = $this->getActorContext();
        if (!$actorContext['can_access_user_catalog']) {
            return $this->failForbidden('No tienes permisos para consultar usuarios.');
        }

        $idUsuario = (int) $this->request->getPost('id_usuario');
        if ($idUsuario <= 0) {
            return $this->fail('Identificador de usuario no válido', 400);
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

        return $this->respond($row);
    }

    public function saveCajero()
    {
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
        $legacyProfile = $selectedProfile ?: $this->resolver->inferLegacyProfile($assignment, $usuarioActual ?? []);
        $dataInsert = [
            'usuario' => trim($data['usuario']),
            'nombre' => trim($data['nombre']),
            'primer_apellido' => trim($data['primer_apellido']),
            'segundo_apellido' => trim((string) ($data['segundo_apellido'] ?? '')),
            'correo' => trim($data['correo']),
            'id_perfil' => $legacyProfile,
            'id_establecimiento' => $this->nullableInt($data['id_establecimiento'] ?? null),
            'id_nivel_cliente' => $this->nullableInt($data['id_nivel_cliente'] ?? null),
            'id_partida' => $this->nullableInt($data['id_partida'] ?? null),
            'id_pais' => $this->nullableInt($data['id_pais'] ?? null),
            'id_clave' => $this->nullableInt($data['id_clave'] ?? null),
            'monto_deposito' => $this->nullableNumeric($data['monto_deposito'] ?? null),
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
                'respuesta' => 'Identificador de usuario no válido',
            ]);
        }

        $usuarioActual = $this->getBaseUserRow($idUsuario);
        if (!$usuarioActual) {
            return $this->respond([
                'error' => true,
                'respuesta' => 'El usuario no existe o ya no está disponible.',
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
                'perfiles' => $this->filterPerfilesCatalogo(
                    $this->getCatalogData('cat_perfil', ['visible' => 1], 'id_perfil ASC'),
                    $actorContext
                ),
                'tarifas' => $this->getCatalogData('cat_nivel_cliente', ['visible' => 1], 'id_nivel_cliente ASC'),
                'partidas' => $this->getCatalogData('cat_partida', ['visible' => 1], 'id_partida ASC'),
                'tipos_habitacion' => $this->getCatalogData('cat_tipo_habitacion', ['visible' => 1], 'id_tipo_habitacion ASC'),
                'establecimientos' => $this->getCatalogData('establecimiento', ['visible' => 1], 'dsc_establecimiento ASC'),
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
                'respuesta' => 'Identificador de usuario no válido',
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

    private function buildCatalogRows(array $actorContext): array
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
            'tabla' => 'vw_usuario_qr',
            'where' => ['visible' => 1],
        ]);
        if ($displayResponse->error) {
            $displayResponse = $this->globals->getTabla([
                'tabla' => 'vw_usuario',
                'where' => ['visible' => 1],
            ]);
        }

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

    private function filterPerfilesCatalogo(array $perfiles, array $actorContext): array
    {
        if ($actorContext['is_ti_master'] || (int) ($actorContext['id_perfil'] ?? 0) === 1) {
            return $perfiles;
        }

        $perfiles = array_values(array_filter($perfiles, static function ($perfil) {
            return !in_array((int) ($perfil['id_perfil'] ?? 0), [2, 5, 7], true);
        }));

        $allowedByGroup = [
            'fic' => [9],
            'secul' => [8],
            'ug' => [10],
            'secturi' => [4, 6],
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

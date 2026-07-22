<?php
$session = \Config\Services::session();
$contextoUsuario = $contextoUsuario ?? [];
$catalogRoleOptions = $catalogRoleOptions ?? [];
$idUsuarioEditar = (int) ($idUsuarioEditar ?? 0);
$esNuevo = $idUsuarioEditar <= 0;
$modoAltaProveedor = !empty($modoAltaProveedor);
$modoSolicitudFolio = !empty($modoSolicitudFolio);
$solicitudFolioGrupo = strtoupper((string) ($solicitudFolioGrupo ?? ''));
$puedeSugerirFolio = !$modoAltaProveedor && (
    $modoSolicitudFolio
    || !empty($contextoUsuario['is_ti_master'])
    || ((string) ($contextoUsuario['active_group'] ?? '') === 'secturi' && (int) ($contextoUsuario['group_role'] ?? 0) === 1)
);
$regresarUrl = $regresarUrl ?? base_url('index.php/Inicio/Usuarios');
$partidaOptions = is_array($partidaOptions ?? null) ? $partidaOptions : [];
$extractCatalogAmount = static function ($item) {
    $candidates = ['monto_diario', 'tarifa_diaria', 'tarifa_noche', 'tarifa', 'precio', 'costo', 'importe', 'monto', 'valor'];

    if (is_array($item)) {
        foreach ($candidates as $key) {
            if (isset($item[$key]) && $item[$key] !== '' && is_numeric($item[$key])) {
                return (float) $item[$key];
            }
        }
    } elseif (is_object($item)) {
        foreach ($candidates as $key) {
            if (isset($item->{$key}) && $item->{$key} !== '' && is_numeric($item->{$key})) {
                return (float) $item->{$key};
            }
        }
    }

    return 0.0;
};
$inferHabitacionCapacidad = static function ($item) {
    $label = '';
    if (is_array($item)) {
        $label = (string) ($item['dsc_tipo_habitacion'] ?? $item['descripcion'] ?? $item['nombre'] ?? '');
    } elseif (is_object($item)) {
        $label = (string) ($item->dsc_tipo_habitacion ?? $item->descripcion ?? $item->nombre ?? '');
    }

    $label = strtolower(trim($label));
    if ($label === '') {
        return 1;
    }

    $map = [
        'sencill' => 1,
        'simple' => 1,
        'doble' => 2,
        'triple' => 3,
        'cuadruple' => 4,
        'cuádruple' => 4,
        'cuatriple' => 4,
        'quadruple' => 4,
        'quintuple' => 5,
        'quíntuple' => 5,
        'sextuple' => 6,
        'séxtuple' => 6,
    ];

    foreach ($map as $needle => $capacidad) {
        if (strpos($label, $needle) !== false) {
            return $capacidad;
        }
    }

    if (preg_match('/\b([1-9])\b/', $label, $matches)) {
        return max(1, (int) $matches[1]);
    }

    return 1;
};
?>

<style>
    .crud-ui-upper {
        text-transform: uppercase;
    }

    .crud-ui-lower {
        text-transform: lowercase;
    }

    .select2-container {
        width: 100% !important;
    }
</style>

<?php if ($modoAltaProveedor): ?>
    <div
        class="container-fluid py-4"
        id="altaUsuarioPage"
        data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>"
        data-id-usuario="<?= esc((string) $idUsuarioEditar, 'attr') ?>"
        data-list-url="<?= esc($regresarUrl, 'attr') ?>"
        data-provider-mode="1">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h3 class="mb-1 text-white">
                    <?= $esNuevo ? 'Nuevo proveedor' : 'Editar proveedor' ?>
                </h3>
                <p class="text-muted mb-0">
                    Alta institucional del proveedor. Este usuario no tendr&aacute; beneficios QR, NIP, alimentos ni hospedaje.
                </p>
            </div>

            <a href="<?= esc($regresarUrl, 'attr') ?>" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left me-1"></i> Regresar
            </a>
        </div>

        <form id="formAltaProveedorFic" autocomplete="off">
            <?= csrf_field() ?>

            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="id_usuario" id="id_usuario" value="<?= esc((string) $idUsuarioEditar, 'attr') ?>">
                    <input type="hidden" name="grupo_usuario" id="grupo_usuario" value="proveedor">
                    <input type="hidden" name="id_perfil" id="id_perfil" value="2">
                    <input type="hidden" name="id_proveedor" id="id_proveedor" value="">
                    <input type="hidden" name="id_tipo_proveedor" id="id_tipo_proveedor" value="">
                    <input type="hidden" name="id_establecimiento" id="id_establecimiento" value="">
                    <input type="hidden" name="no_proveedor_padron" id="no_proveedor_padron" value="">

                    <div class="alert alert-info" role="alert">
                        Selecciona primero el proveedor del padr&oacute;n. El nombre, establecimiento y tipo de establecimiento se llenar&aacute;n autom&aacute;ticamente.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="proveedor_catalogo">Proveedor</label>
                            <select
                                class="form-control js-select2-catalog"
                                id="proveedor_catalogo"
                                data-placeholder="Buscar por n&uacute;mero, raz&oacute;n social o RFC"
                                required>
                                <option value="">Seleccione</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input
                                type="text"
                                class="form-control crud-ui-upper"
                                name="nombre"
                                id="nombre"
                                autocomplete="off"
                                readonly
                                required>
                        </div>
                        <div class="col-12">
                            <div id="proveedorEstablecimientosList" class="row g-3"></div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="usuario">Usuario</label>
                            <input
                                type="text"
                                class="form-control crud-ui-lower"
                                name="usuario"
                                id="usuario"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="correo">Correo</label>
                            <input
                                type="email"
                                class="form-control crud-ui-lower"
                                name="correo"
                                id="correo"
                                autocomplete="off"
                                autocapitalize="off"
                                spellcheck="false">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="contrasenia">Contrase&ntilde;a</label>
                            <input
                                type="password"
                                class="form-control crud-ui-lower"
                                name="contrasenia"
                                id="contrasenia"
                                autocomplete="new-password"
                                autocapitalize="off"
                                spellcheck="false"
                                <?= $esNuevo ? 'required' : '' ?>>

                            <?php if (!$esNuevo): ?>
                                <small class="text-muted">
                                    En edici&oacute;n, deja vac&iacute;a esta casilla para conservar la contrase&ntilde;a actual.
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="<?= esc($regresarUrl, 'attr') ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>

                    <button type="submit" class="btn btn-primary" id="guardarProveedorFic">
                        Guardar proveedor
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        window.modoAltaProveedorFic = true;
    </script>

    <?php return; ?>
<?php endif; ?>
<style>
    .crud-ui-upper {
        text-transform: uppercase;
    }

    .crud-ui-lower {
        text-transform: lowercase;
    }

    .crud-ui-grid-label {
        display: block;
        margin-bottom: .5rem;
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #9fb0c9;
    }

    .select2-container {
        width: 100% !important;
    }
</style>

<div
    class="container-fluid py-4"
    id="altaUsuarioPage"
    data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>"
    data-id-usuario="<?= esc((string) $idUsuarioEditar, 'attr') ?>"
    data-list-url="<?= esc($regresarUrl, 'attr') ?>"
    data-catalog-context="<?= esc(json_encode($contextoUsuario, JSON_UNESCAPED_UNICODE), 'attr') ?>"
    data-role-options="<?= esc(json_encode($catalogRoleOptions, JSON_UNESCAPED_UNICODE), 'attr') ?>"
    data-solicitud-folio-mode="<?= $modoSolicitudFolio ? '1' : '0' ?>"
    data-solicitud-id="<?= esc((string) ($solicitudFolioId ?? 0), 'attr') ?>"
    data-solicitud-grupo="<?= esc($solicitudFolioGrupo, 'attr') ?>"
    data-folio-suggestions-enabled="<?= $puedeSugerirFolio ? '1' : '0' ?>"
    data-folio-suggestions-url="<?= esc(base_url('index.php/Inicio/getSugerenciasFolioInstitucional'), 'attr') ?>"
    data-solicitud-detail-url="<?= esc((string) ($solicitudDetalleUrl ?? base_url('index.php/Inicio/getSolicitudFolioEditable')), 'attr') ?>"
    data-save-url="<?= esc($saveUrl ?? base_url('index.php/Usuario/saveAltaUsuario'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white" id="cajeroPageTitle"><?= $modoSolicitudFolio ? (!empty($solicitudFolioId) ? 'Editar solicitud de folio' : 'Solicitud de nuevo folio') : ($esNuevo ? 'Nuevo usuario' : 'Editar usuario') ?></h3>
            <p class="text-muted mb-0">
                <?= $modoSolicitudFolio
                    ? 'Captura la información completa del folio ' . esc($solicitudFolioGrupo) . '; TI la revisará antes de crear el usuario.'
                    : 'Captura la información del usuario en una vista completa para trabajar más cómodo.' ?>
            </p>
        </div>
        <a href="<?= esc($regresarUrl, 'attr') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Regresar
        </a>
    </div>

    <?php if ($modoSolicitudFolio && !empty($solicitudFolioId)): ?>
        <div class="alert alert-info border-info mb-3" role="status">
            <i class="mdi mdi-file-document-edit-outline me-1"></i>
            Estás editando la solicitud #<?= esc((string) $solicitudFolioId) ?>. Guardar actualizará la solicitud para revisión; no crea ni aprueba usuarios.
        </div>
    <?php endif; ?>

    <form id="cajeroForm">
        <div class="card">
            <div class="card-body">
                <input type="hidden" name="id_usuario" id="id_usuario" value="<?= esc((string) $idUsuarioEditar, 'attr') ?>">
                <input type="hidden" name="grupo_usuario" id="grupo_usuario">
                <input type="hidden" name="id_solicitud_usuario" id="id_solicitud_usuario" value="<?= esc((string) ($solicitudFolioId ?? 0), 'attr') ?>">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="categoria_ui">Categoria</label>
                        <select class="form-control js-select2-catalog" id="categoria_ui" data-placeholder="Buscar categoria">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_pais">País o región</label>
                        <select class="form-control js-select2-catalog" name="id_pais" id="id_pais" data-placeholder="Buscar país o región">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3 estado-field d-none">
                        <label class="form-label" for="id_estado">Estado</label>
                        <select class="form-control js-select2-catalog" name="id_estado" id="id_estado" data-placeholder="Buscar estado">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="disciplina_ui">Disciplina</label>
                        <select class="form-control js-select2-catalog" id="disciplina_ui" data-placeholder="Buscar disciplina">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_clave">Clave</label>
                        <input type="hidden" name="id_clave" id="id_clave">
                        <input type="text" class="form-control crud-ui-lower" id="clave_ui" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_perfil_catalogo">Perfil</label>
                        <select class="form-control js-select2-catalog" name="id_perfil_catalogo" id="id_perfil_catalogo" data-placeholder="Buscar perfil">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="perfil_grupo">Perfil visible</label>
                        <select class="form-control js-select2-catalog" name="perfil_grupo" id="perfil_grupo" data-placeholder="Buscar perfil visible">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_establecimiento">Establecimiento</label>
                        <select class="form-control js-select2-catalog" name="id_establecimiento" id="id_establecimiento" data-placeholder="Buscar establecimiento">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="folio_ui">Folio</label>
                        <input type="text" class="form-control" name="folio" id="folio_ui" placeholder="folio" inputmode="numeric" pattern="[0-9]*" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="subf_ui">Subfolio</label>
                        <input type="text" class="form-control crud-ui-upper" name="sub_folio" id="subf_ui" placeholder="subf" inputmode="text" maxlength="20">
                    </div>
                    <div class="col-12 d-none" id="folioSugerenciasWrapper">
                        <div class="alert alert-info solicitud-folio-suggestions mb-0">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <strong>Sugerencias de folio</strong>
                                    <div class="small" id="folioSugerenciasEstado">Consultando ultimo folio disponible...</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="folioSugerenciasChips"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="pax_ui">Pax</label>
                        <input type="number" class="form-control" name="pax" id="pax_ui" placeholder="pax" min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="anf_gto_ui">Anfitri&oacute;n Guanajuato</label>
                        <input type="text" class="form-control crud-ui-upper" name="anf_gto" id="anf_gto_ui" placeholder="anf gto" inputmode="text" maxlength="80">
                    </div>

                    <div class="col-md-12">
                        <label class="crud-ui-grid-label">Pax 1 / Titular</label>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="nombre">Nombre</label>
                                <input class="form-control crud-ui-upper" name="nombre" id="nombre" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="primer_apellido">Primer apellido</label>
                                <input class="form-control crud-ui-upper" name="primer_apellido" id="primer_apellido" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="segundo_apellido">Segundo apellido</label>
                                <input class="form-control crud-ui-upper" name="segundo_apellido" id="segundo_apellido">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="usuario">Usuario</label>
                        <input class="form-control crud-ui-lower" name="usuario" id="usuario" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="correo">Correo</label>
                        <input type="email" class="form-control crud-ui-lower" name="correo" id="correo">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contrasenia">Contrase&ntilde;a</label>
                        <input type="password" class="form-control crud-ui-lower" name="contrasenia" id="contrasenia">
                        <small class="text-muted">En edici&oacute;n, d&eacute;jala vac&iacute;a para conservar la actual.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nip">NIP</label>
                        <input class="form-control crud-ui-lower" id="nip" readonly placeholder="Se genera automaticamente en el alta">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tiene_alimentos">Tiene alimentos</label>
                        <select class="form-control" name="tiene_alimentos" id="tiene_alimentos">
                            <option value="">Seleccione</option>
                            <option value="1">S&iacute;</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="fec_vigencia_desde">Vigencia alimentos desde</label>
                        <input type="date" class="form-control" name="fec_vigencia_desde" id="fec_vigencia_desde">
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="fec_vigencia_hasta">Vigencia alimentos hasta</label>
                        <input type="date" class="form-control" name="fec_vigencia_hasta" id="fec_vigencia_hasta">
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="id_nivel_cliente">Tarifa diaria</label>
                        <select class="form-control js-select2-catalog" name="id_nivel_cliente" id="id_nivel_cliente" data-placeholder="Buscar tarifa diaria">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="monto_deposito">Monto deposito individual</label>
                        <input type="number" step="0.01" class="form-control" name="monto_deposito" id="monto_deposito">
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="monto_total_alimentos_ui">Monto total</label>
                        <input type="number" step="0.01" class="form-control" id="monto_total_alimentos_ui" readonly>
                    </div>
                    <input type="hidden" name="id_partida" id="id_partida">
                    <div class="col-md-3 solicitud-partida-visual" id="partidaManualWrapper">
                        <label class="form-label" for="id_partida_ui">Partida</label>
                        <select class="form-control js-select2-catalog" id="id_partida_ui" data-placeholder="Buscar partida">
                            <option value="">Seleccione</option>
                            <?php foreach ($partidaOptions as $partida): ?>
                                <?php
                                    $partidaCodigo = trim((string) ($partida->partida ?? ''));
                                    $partidaDescripcion = trim((string) ($partida->des_partida ?? ''));
                                    $partidaLabel = trim($partidaCodigo . ($partidaDescripcion !== '' ? ' - ' . $partidaDescripcion : ''));
                                ?>
                                <option value="<?= esc((string) ($partida->id_partida ?? ''), 'attr') ?>"><?= esc($partidaLabel, 'html') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">FIC y UG se asignan autom&aacute;ticamente a 3390A o 3390B seg&uacute;n el beneficio.</small>
                    </div>
                    <div class="col-md-3 alimentos-field solicitud-partida-visual" id="partidaAlimentosWrapper">
                        <label class="form-label" for="id_partida_alimentos_ui">Partida alimentos</label>
                        <input type="text" class="form-control" id="id_partida_alimentos_ui" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tiene_hospedaje">Tiene hospedaje</label>
                        <select class="form-control" name="tiene_hospedaje" id="tiene_hospedaje">
                            <option value="">Seleccione</option>
                            <option value="1">Si</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="id_establecimiento_hotel">Hotel</label>
                        <select class="form-control js-select2-catalog" name="id_establecimiento_hotel" id="id_establecimiento_hotel" data-placeholder="Buscar hotel">
                            <option value="">Seleccione</option>
                            <?php foreach ($hotelOptions as $hotel): ?>
                            <option value="<?= esc($hotel->id_establecimiento, 'attr') ?>"><?= esc($hotel->dsc_establecimiento, 'html') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="id_tipo_habitacion">Tipo habitación</label>
                        <select class="form-control js-select2-catalog" name="id_tipo_habitacion" id="id_tipo_habitacion" data-placeholder="Buscar tipo de habitacion">
                            <option value="">Seleccione</option>
                            <?php foreach ($catTipoHabitacion as $tipo): ?>
                            <option value="<?= esc($tipo->id_tipo_habitacion, 'attr') ?>" data-tarifa="<?= esc($extractCatalogAmount($tipo), 'attr') ?>"><?= esc($tipo->dsc_tipo_habitacion, 'html') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="fec_vigencia_desde_hos">Vigencia hospedaje desde</label>
                        <input type="date" class="form-control" name="fec_vigencia_desde_hos" id="fec_vigencia_desde_hos">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="fec_vigencia_hasta_hos">Vigencia hospedaje hasta</label>
                        <input type="date" class="form-control" name="fec_vigencia_hasta_hos" id="fec_vigencia_hasta_hos">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="tarifa_noche">Tarifa diaria de hospedaje</label>
                        <input type="number" step="0.01" class="form-control" name="tarifa_noche" id="tarifa_noche">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="noche">Noches</label>
                        <input type="number" class="form-control" name="noche" id="noche">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="tarifa_total">Tarifa total</label>
                        <input type="number" step="0.01" class="form-control" name="tarifa_total" id="tarifa_total">
                    </div>
                    <div class="col-md-3 hospedaje-field solicitud-partida-visual" id="partidaHospedajeWrapper">
                        <label class="form-label" for="id_partida_hospedaje_ui">Partida hospedaje</label>
                        <input type="text" class="form-control" id="id_partida_hospedaje_ui" readonly>
                    </div>
                    <input type="hidden" name="hospedaje_plan_json" id="hospedaje_plan_json" value="">
                    <input type="hidden" name="hospedaje_sobrerreserva" id="hospedaje_sobrerreserva" value="0">

                    <div class="col-12 hospedaje-field hospedaje-plan-field" id="hospedajePlanWrapper">
                        <div class="card border-secondary-subtle shadow-sm">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1 text-white">Plan de habitaciones</h5>
                                        <p class="text-muted mb-0">Selecciona manualmente las habitaciones. Cada fila representa una asignaci&oacute;n del folio.</p>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="hospedaje_sobrerreserva_ui">
                                            <label class="form-check-label" for="hospedaje_sobrerreserva_ui">Permitir sobre-reserva</label>
                                        </div>
                                        <button type="button" class="btn btn-outline-light btn-sm" id="agregarHabitacionHospedaje">
                                            <i class="mdi mdi-plus me-1"></i>Agregar habitación
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="limpiarPlanHospedaje">
                                            Limpiar plan
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Habitaciones</label>
                                        <input type="text" class="form-control" id="hospedajePlanHabitaciones" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Pax asignados</label>
                                        <input type="text" class="form-control" id="hospedajePlanPaxAsignados" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Capacidad total</label>
                                        <input type="text" class="form-control" id="hospedajePlanCapacidadTotal" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Estado</label>
                                        <input type="text" class="form-control" id="hospedajePlanEstado" readonly>
                                    </div>
                                </div>
                                <div class="row g-3" id="hospedajePlanContainer"></div>
                                <small class="text-muted d-block mt-3">Si una habitaci&oacute;n no alcanza para todos los pax, agrega otra fila o activa sobre-reserva.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border-secondary-subtle shadow-sm mt-2">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1 text-white">Pax adicionales</h5>
                                        <p class="text-muted mb-0">Cuando el numero de pax sea mayor a 1, aqui se agregan las cuentas individuales.</p>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary-emphasis" id="paxResumenBadge">1 pax</span>
                                </div>
                                <div class="row g-3" id="paxPersonasExtras"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <div class="card border-secondary-subtle shadow-sm">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1 text-white">Resumen de reserva</h5>
                                        <p class="text-muted mb-0">El sistema calcula el monto por pax y el total del grupo antes de guardar.</p>
                                    </div>
                                </div>
                                <div class="row g-3" id="altaUsuarioResumen">
                                    <div class="col-md-3">
                                        <label class="form-label">Monto alimentos por pax</label>
                                        <input type="text" class="form-control" id="altaResumenMontoAlimentosPax" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Monto hospedaje por pax</label>
                                        <input type="text" class="form-control" id="altaResumenMontoHospedajePax" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Monto total por pax</label>
                                        <input type="text" class="form-control" id="altaResumenMontoPax" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Monto total del grupo</label>
                                        <input type="text" class="form-control" id="altaResumenMontoGrupo" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <template id="paxPersonaTemplate">
                        <div class="col-12">
                            <div class="card border-secondary-subtle bg-body-tertiary pax-persona-card" data-person-index="__INDEX__">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div>
                                            <h6 class="mb-1 text-white">Pax __DISPLAY__</h6>
                                            <p class="text-muted mb-0">Cuenta individual vinculada al mismo folio.</p>
                                        </div>
                                        <span class="badge bg-secondary">Cuenta individual</span>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Nombre</label>
                                            <input class="form-control crud-ui-upper pax-person-field" name="usuarios[__INDEX__][nombre]" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Primer apellido</label>
                                            <input class="form-control crud-ui-upper pax-person-field" name="usuarios[__INDEX__][primer_apellido]" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Segundo apellido</label>
                                            <input class="form-control crud-ui-upper pax-person-field" name="usuarios[__INDEX__][segundo_apellido]">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Correo</label>
                                            <input type="email" class="form-control crud-ui-lower pax-person-field" name="usuarios[__INDEX__][correo]" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Usuario</label>
                                            <input class="form-control crud-ui-lower pax-person-field" name="usuarios[__INDEX__][usuario]" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Contraseña</label>
                                            <input type="password" class="form-control crud-ui-lower pax-person-field" name="usuarios[__INDEX__][contrasenia]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template id="hospedajePlanRowTemplate">
                        <div class="col-12 hospedaje-plan-row">
                            <div class="card border-secondary-subtle bg-body-tertiary">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-5">
                                            <label class="form-label">Habitaci&oacute;n</label>
                                            <select class="form-control js-select2-catalog hospedaje-plan-field" data-role="habitacion">
                                                <option value="">Seleccione</option>
                                                <?php foreach ($catTipoHabitacion as $tipo): ?>
                                                <option
                                                    value="<?= esc($tipo->id_tipo_habitacion, 'attr') ?>"
                                                    data-tarifa="<?= esc($extractCatalogAmount($tipo), 'attr') ?>"
                                                    data-capacidad="<?= esc((string) $inferHabitacionCapacidad($tipo), 'attr') ?>">
                                                    <?= esc($tipo->dsc_tipo_habitacion, 'html') ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Capacidad</label>
                                            <input type="text" class="form-control hospedaje-plan-field" data-role="capacidad" readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Pax</label>
                                            <input type="number" min="1" class="form-control hospedaje-plan-field" data-role="pax">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Tarifa</label>
                                            <input type="text" class="form-control hospedaje-plan-field" data-role="tarifa" readonly>
                                        </div>
                                        <div class="col-md-1 d-grid">
                                            <button type="button" class="btn btn-outline-danger hospedaje-plan-remove">
                                                <i class="mdi mdi-delete"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="<?= esc($regresarUrl, 'attr') ?>" class="btn btn-secondary">Cancelar</a>
                <?php if (!empty($contextoUsuario['can_edit_user_catalog'])): ?>
                <button type="submit" class="btn btn-primary" id="guardarCajero"><?= $modoSolicitudFolio ? (!empty($solicitudFolioId) ? 'Guardar cambios en la solicitud' : 'Enviar solicitud') : 'Guardar' ?></button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

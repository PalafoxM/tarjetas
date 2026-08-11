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

    .select2-container--disabled .select2-selection {
        background-color: #404954 !important;
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

        <form id="formAltaProveedorFic" autocomplete="off" method="post">
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

    <div class="card border-secondary-subtle shadow-sm mb-3 d-none" id="comentarioSolicitudInstitucionalWrapper">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="comentario_usuario">Comentario para TI/admin SECTURI</label>
                    <textarea
                        class="form-control"
                        name="comentario_usuario"
                        id="comentario_usuario"
                        rows="3"
                        placeholder="Opcional. Describe el motivo de la modificación o cualquier contexto útil para la revisión."></textarea>
                </div>
                <div class="col-12 d-none" id="comentarioSolicitudHistorialWrapper">
                    <label class="form-label">Comentarios anteriores</label>
                    <div class="border rounded p-3 bg-body-tertiary">
                        <div class="mb-3">
                            <strong>Comentario enviado</strong>
                            <pre class="mb-0 mt-1 text-break" id="comentarioSolicitudAnterior" style="white-space: pre-wrap;"></pre>
                        </div>
                        <div>
                            <strong>Motivo de rechazo</strong>
                            <pre class="mb-0 mt-1 text-break" id="comentarioSolicitudRechazo" style="white-space: pre-wrap;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <select class="form-control" name="pax" id="pax_ui">
                            <?php for ($i = 1; $i <= 999; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == 1) ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
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
                    <input type="hidden" name="id_partida_alimentos" id="id_partida_alimentos">
                 <div class="col-md-3 solicitud-partida-visual" id="partidaManualWrapper">
                    <label class="form-label" for="id_partida_ui">Partida</label>
                    <select class="form-control js-select2-catalog" name="id_partida" id="id_partida_ui" data-placeholder="Buscar partida">
                        <option value="">Seleccione</option>
                        <?php 
                       
                        $partidasPermitidas = [1, 3];
                        
                        foreach ($partidaOptions as $partida): 
                            $idPartida = (int) ($partida->id_partida ?? 0);
                            if (!in_array($idPartida, $partidasPermitidas)) {
                                continue;
                            }
                            
                            $partidaCodigo = trim((string) ($partida->partida ?? ''));
                            $partidaDescripcion = trim((string) ($partida->des_partida ?? ''));
                            $partidaLabel = trim($partidaCodigo . ($partidaDescripcion !== '' ? ' - ' . $partidaDescripcion : ''));
                        ?>
                            <option value="<?= esc((string) $idPartida, 'attr') ?>"><?= esc($partidaLabel, 'html') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted" id="partidaHelpText">Selecciona la partida presupuestal</small>
                </div>
                    <div class="col-md-3 alimentos-field solicitud-partida-visual" id="partidaAlimentosWrapper">
                        <label class="form-label" for="id_partida_alimentos_ui">Partida alimentos</label>
                        <input type="text" class="form-control" id="id_partida_alimentos_ui" readonly>
                        <small class="text-muted">Partida asignada automáticamente para alimentos</small>
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
                        <label class="form-label" for="fec_vigencia_desde_hos">Vigencia hospedaje desde</label>
                        <input type="date" class="form-control" name="fec_vigencia_desde_hos" id="fec_vigencia_desde_hos">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="fec_vigencia_hasta_hos">Vigencia hospedaje hasta</label>
                        <input type="date" class="form-control" name="fec_vigencia_hasta_hos" id="fec_vigencia_hasta_hos">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="noche">Noches</label>
                        <input type="number" class="form-control" name="noche" id="noche" readonly>
                    </div>
                    <div class="col-md-3 hospedaje-field solicitud-partida-visual" id="partidaHospedajeWrapper">
                        <label class="form-label" for="id_partida_hospedaje_ui">Partida hospedaje</label>
                        <input type="text" class="form-control" id="id_partida_hospedaje_ui" readonly value="2">
                        <small class="text-muted">Partida fija para hospedaje</small>
                    </div>


                    <div class="col-12 hospedaje-field" id="hospedajeConfiguracionHoteles">
                        <div class="card border-secondary-subtle shadow-sm mb-3">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1 text-white">Configuraci&oacute;n de hoteles</h5>
                                        <p class="text-muted mb-0">Agrega uno o m&aacute;s hoteles y define cu&aacute;ntas habitaciones de cada tipo necesitas. El plan de habitaciones se generar&aacute; autom&aacute;ticamente.</p>
                                    </div>
                                    <button type="button" class="btn btn-outline-light btn-sm" id="agregarHotelHospedaje">
                                        <i class="mdi mdi-plus me-1"></i>Agregar hotel
                                    </button>
                                </div>
                                <div class="row g-3" id="hospedajeHotelesContainer"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 hospedaje-field" id="hospedajePlanWrapper">
                        <div class="card border-secondary-subtle shadow-sm">
                            <div class="card-body">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-1 text-white">Plan de habitaciones</h5>
                                        <p class="text-muted mb-0">El sistema genera autom&aacute;ticamente las filas seg&uacute;n la configuraci&oacute;n de hoteles arriba.</p>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="hospedaje_sobrerreserva_ui">
                                            <label class="form-check-label" for="hospedaje_sobrerreserva_ui">Permitir sobre-reserva</label>
                                        </div>
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
                                <small class="text-muted d-block mt-3">Si una habitaci&oacute;n no alcanza para todos los pax, agrega otra fila en la configuraci&oacute;n de hoteles o activa sobre-reserva.</small>
                            </div>
                        </div>
                    </div>

                    <template id="hospedajeHotelBlockTemplate">
                        <div class="col-12 hospedaje-hotel-block" data-hotel-index="__HOTEL_INDEX__">
                            <div class="card border-secondary-subtle bg-body-tertiary">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end mb-2">
                                        <div class="col-md-8">
                                            <label class="form-label">Hotel</label>
                                            <select class="form-control js-select2-catalog hospedaje-hotel-field" data-role="hotel">
                                                <option value="">Seleccione</option>
                                                <?php foreach ($hotelOptions as $hotel): ?>
                                                <option value="<?= esc($hotel->id_establecimiento, 'attr') ?>"><?= esc($hotel->dsc_establecimiento, 'html') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4 d-grid">
                                            <button type="button" class="btn btn-outline-danger btn-sm hospedaje-hotel-remove">
                                                <i class="mdi mdi-delete me-1"></i>Quitar hotel
                                            </button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                        <label class="crud-ui-grid-label mb-0">Tipos de habitaci&oacute;n</label>
                                        <button type="button" class="btn btn-outline-light btn-sm hospedaje-tipo-agregar">
                                            <i class="mdi mdi-plus me-1"></i>Agregar tipo de habitaci&oacute;n
                                        </button>
                                    </div>
                                    <div class="row g-2 hospedaje-tipos-container">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template id="hospedajeTipoHabitacionRowTemplate">
                        <div class="col-12 hospedaje-tipo-row">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-7">
                                    <label class="form-label">Tipo de habitaci&oacute;n</label>
                                    <select class="form-control js-select2-catalog hospedaje-tipo-field" data-role="tipo_habitacion">
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
                                <div class="col-md-3">
                                    <label class="form-label">Cantidad</label>
                                    <select class="form-control hospedaje-tipo-field" data-role="cantidad">
                                        <?php for ($cantidad = 1; $cantidad <= 999; $cantidad++): ?>
                                        <option value="<?= $cantidad ?>"><?= $cantidad ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <button type="button" class="btn btn-outline-danger btn-sm hospedaje-tipo-remove">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template id="hospedajePlanRowTemplate">
                        <div class="col-12 hospedaje-plan-row">
                            <div class="card border-secondary-subtle bg-body-tertiary">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <input type="hidden" class="hospedaje-plan-field" data-role="hotel" value="">
                                        <div class="col-12">
                                            <h6 class="mb-2 text-white hospedaje-plan-hotel-nombre" data-role="hotel_nombre_display">Hotel</h6>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Habitaci&oacute;n</label>
                                            <select class="form-control js-select2-catalog hospedaje-plan-field" data-role="habitacion" disabled>
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
                                            <select class="form-control hospedaje-plan-field" data-role="pax">
                                                <?php for ($i = 1; $i <= 999; $i++): ?>
                                                <option value="<?= $i ?>"><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Tarifa noche</label>
                                            <input type="text" class="form-control hospedaje-plan-field" data-role="tarifa" readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Tarifa total</label>
                                            <input type="text" class="form-control hospedaje-plan-field" data-role="tarifa_total" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <!-- <div class="col-12">
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
                    </div> -->
                   <div class="col-12">
                        <div class="card border-secondary-subtle shadow-sm mt-2 card-pax-adicionales">
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

<script>
(function () {
    const page = document.getElementById('altaUsuarioPage');
    const form = document.getElementById('cajeroForm');
    const boton = document.getElementById('guardarCajero');

    if (!page || !form || !boton || typeof Swal === 'undefined' || typeof $ === 'undefined') {
        return;
    }

    if (window.cajeros && typeof window.cajeros.guardarAltaUsuario === 'function') {
        return;
    }

    const saveUrl = String(page.dataset.saveUrl || '').trim();
    const listUrl = String(page.dataset.listUrl || '').trim();
    const solicitudFolioMode = String(page.dataset.solicitudFolioMode || '0') === '1';
    const solicitudGrupo = String(page.dataset.solicitudGrupo || '').trim().toLowerCase();
    const solicitudId = Number(page.dataset.solicitudId || 0);
    let isSubmitting = false;

    const getValue = (name) => {
        const field = form.querySelector('[name="' + name + '"]');
        return field ? String(field.value || '').trim() : '';
    };

    const getErrorMessage = (request, fallback) => {
        const defaultMessage = fallback || 'No fue posible guardar el usuario.';
        if (!request) {
            return defaultMessage;
        }

        if (request.respuesta || request.message) {
            return request.respuesta || request.message;
        }

        if (request.responseJSON && (request.responseJSON.respuesta || request.responseJSON.message)) {
            return request.responseJSON.respuesta || request.responseJSON.message;
        }

        const responseText = String(request.responseText || '').trim();
        if (!responseText) {
            return defaultMessage;
        }

        try {
            const parsed = JSON.parse(responseText);
            return parsed.respuesta || parsed.message || defaultMessage;
        } catch (error) {
            const match = responseText.match(/"respuesta"\s*:\s*"([^"]+)"/);
            if (match && match[1]) {
                return match[1].replace(/\\u([0-9a-fA-F]{4})/g, function (_, code) {
                    return String.fromCharCode(parseInt(code, 16));
                }).replace(/\\"/g, '"');
            }
        }

        return defaultMessage;
    };

    const showError = (message) => {
        Swal.fire({
            icon: 'warning',
            title: 'No fue posible guardar el usuario',
            text: message || 'Revisa la informacion capturada.'
        });
    };

    const showSuccess = (message) => {
        Swal.fire('Correcto', message || 'Usuario guardado correctamente.', 'success')
            .then(() => {
                if (listUrl) {
                    window.location.href = listUrl;
                }
            });
    };

    const emitSolicitudFolio = (response) => {
        if (!solicitudFolioMode || !window.ficRealtime || typeof window.ficRealtime.emit !== 'function') {
            return;
        }

        window.ficRealtime.emit(solicitudId > 0 ? 'fic:solicitud-folio-actualizada' : 'fic:solicitud-folio-creada', {
            grupo: solicitudGrupo || getValue('grupo_usuario') || 'fic',
            id_solicitud_usuario: solicitudId || (response && response.data && response.data.id_solicitud_usuario ? response.data.id_solicitud_usuario : null),
            accion: solicitudId > 0 ? 'editar' : 'crear'
        });
    };

    const validarFechas = () => {
        const vigDesde = getValue('fec_vigencia_desde');
        const vigHasta = getValue('fec_vigencia_hasta');
        const vigDesdeHos = getValue('fec_vigencia_desde_hos');
        const vigHastaHos = getValue('fec_vigencia_hasta_hos');

        if (vigDesde && vigHasta && vigDesde > vigHasta) {
            showError('La vigencia de alimentos no puede terminar antes de iniciar.');
            return false;
        }

        if (vigDesdeHos && vigHastaHos && vigDesdeHos > vigHastaHos) {
            showError('La vigencia de hospedaje no puede terminar antes de iniciar.');
            return false;
        }

        return true;
    };

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (window.cajeros && typeof window.cajeros.guardarAltaUsuario === 'function') {
            event.stopImmediatePropagation();
            window.cajeros.guardarAltaUsuario();
            return;
        }

        if (isSubmitting) {
            return;
        }

        if (!saveUrl) {
            showError('No fue posible resolver la ruta de guardado.');
            return;
        }

        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            form.classList.add('was-validated');
            showError('Completa los campos obligatorios.');
            return;
        }

        if (!validarFechas()) {
            return;
        }

        isSubmitting = true;
        const textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = 'Guardando...';

        $.ajax({
            url: saveUrl,
            type: 'POST',
            dataType: 'json',
            data: $(form).serialize()
        }).done(function (response) {
            if (!response || response.error === true || response.ok === false) {
                showError(getErrorMessage(response));
                return;
            }

            emitSolicitudFolio(response);
            showSuccess(response.respuesta || response.message || 'Usuario guardado correctamente.');
        }).fail(function (request) {
            showError(getErrorMessage(request));
        }).always(function () {
            isSubmitting = false;
            boton.disabled = false;
            boton.innerHTML = textoOriginal;
        });
    });

    
})();

(function() {
    const page = document.getElementById('altaUsuarioPage');
    if (!page) return;

    const paxSelect = document.getElementById('pax_ui');
    const nombreInput = document.getElementById('nombre');
    const primerApellidoInput = document.getElementById('primer_apellido');
    const segundoApellidoInput = document.getElementById('segundo_apellido');
    const usuarioInput = document.getElementById('usuario');
    const contraseniaInput = document.getElementById('contrasenia');
    const correoInput = document.getElementById('correo');
    const paxContainer = document.getElementById('paxPersonasExtras');
    const paxCardContainer = document.querySelector('.pax-card-container'); // Contenedor de la tarjeta visual

    if (!paxSelect || !nombreInput || !primerApellidoInput || !usuarioInput) {
        console.warn('Autofill pax: elementos requeridos no encontrados');
        return;
    }

    let paxTimeout = null;

    function getTitularData() {
        return {
            nombre: String(nombreInput.value || '').trim(),
            primer_apellido: String(primerApellidoInput.value || '').trim(),
            segundo_apellido: String(segundoApellidoInput.value || '').trim(),
            usuario: String(usuarioInput.value || '').trim(),
            contrasenia: String(contraseniaInput.value || '').trim(),
            correo: String(correoInput.value || '').trim()
        };
    }

    function generarDatosPax(index, titular) {
        const baseUsuario = titular.usuario || 'usuario';
        const baseContrasenia = titular.contrasenia || 'password';
        const baseCorreo = titular.correo || 'usuario@dominio.com';
        const sufijo = index;

        return {
            nombre: titular.nombre,
            primer_apellido: titular.primer_apellido,
            segundo_apellido: titular.segundo_apellido || '',
            usuario: baseUsuario + sufijo,
            contrasenia: baseContrasenia + sufijo,
            correo: baseCorreo.replace(/@/, sufijo + '@')
        };
    }

    function actualizarBadgePax(total) {
        const badge = document.getElementById('paxResumenBadge');
        if (badge) {
            badge.textContent = total + ' pax';
        }
    }

   
    function renderizarPaxVisual(totalPax, titular) {
        if (!paxContainer) return;

        
        paxContainer.innerHTML = '';

        if (totalPax <= 1) {
            
            const card = document.querySelector('.card-pax-adicionales');
            if (card) card.style.display = 'none';
            actualizarBadgePax(1);
            return;
        }

  
        const card = document.querySelector('.card-pax-adicionales');
        if (card) card.style.display = 'block';

        const template = document.getElementById('paxPersonaTemplate');
        if (!template) {
            console.warn('Autofill pax: template no encontrado');
            return;
        }

   
        for (let i = 2; i <= totalPax; i++) {
            let html = template.innerHTML;
            html = html.replace(/__INDEX__/g, i);
            html = html.replace(/__DISPLAY__/g, i);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const cardElement = wrapper.firstElementChild;
            if (cardElement) {
                paxContainer.appendChild(cardElement);
            }
        }

        actualizarBadgePax(totalPax);
    }

   
    function renderizarPaxOcultos(totalPax, titular) {
        if (!paxContainer) return;

       
        paxContainer.innerHTML = '';

       
        const card = document.querySelector('.card-pax-adicionales');
        if (card) card.style.display = 'none';

        if (totalPax <= 5) {
           
            renderizarPaxVisual(totalPax, titular);
            return;
        }

        for (let i = 2; i <= totalPax; i++) {
            const datos = generarDatosPax(i, titular);

       
            const hiddenGroup = document.createElement('div');
            hiddenGroup.style.display = 'none';

            const campos = [
                { name: `usuarios[${i}][nombre]`, value: datos.nombre },
                { name: `usuarios[${i}][primer_apellido]`, value: datos.primer_apellido },
                { name: `usuarios[${i}][segundo_apellido]`, value: datos.segundo_apellido },
                { name: `usuarios[${i}][usuario]`, value: datos.usuario },
                { name: `usuarios[${i}][contrasenia]`, value: datos.contrasenia },
                { name: `usuarios[${i}][correo]`, value: datos.correo }
            ];

            campos.forEach(campo => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = campo.name;
                input.value = campo.value;
                hiddenGroup.appendChild(input);
            });

            paxContainer.appendChild(hiddenGroup);
        }

        actualizarBadgePax(totalPax);
    }

    function handlePaxChange() {
        const totalPax = parseInt(paxSelect.value, 10) || 0;
        if (totalPax < 1) {
            paxSelect.value = 1;
            renderizarPaxVisual(1, getTitularData());
            return;
        }

        const titular = getTitularData();
        
       
        if (totalPax <= 5) {
            renderizarPaxVisual(totalPax, titular);
        } else {
            renderizarPaxOcultos(totalPax, titular);
        }
    }

  
    paxSelect.addEventListener('change', handlePaxChange);
    paxSelect.addEventListener('input', function() {
        clearTimeout(paxTimeout);
        paxTimeout = setTimeout(handlePaxChange, 300);
    });

    const titularFields = [nombreInput, primerApellidoInput, segundoApellidoInput, usuarioInput, contraseniaInput, correoInput];
    titularFields.forEach(field => {
        if (field) {
            field.addEventListener('change', handlePaxChange);
            field.addEventListener('blur', handlePaxChange);
        }
    });

    function initAutofill() {
        const totalPax = parseInt(paxSelect.value, 10) || 1;
        setTimeout(handlePaxChange, 500);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        initAutofill();
    } else {
        document.addEventListener('DOMContentLoaded', initAutofill);
    }

    window.autofillPaxAdicionales = handlePaxChange;

})();
</script>
<script>
    (function() {
        let partida2 = { 
            codigo: '2', 
            partida: '', 
            descripcion: '', 
            textoCompleto: '' 
        };
        let partida3 = { 
            codigo: '3', 
            partida: '', 
            descripcion: '', 
            textoCompleto: '' 
        };
        let partida1 = { 
            codigo: '1', 
            partida: '', 
            descripcion: '', 
            textoCompleto: '' 
        };
        let partidaCatalogoCargado = false;

        function guardarTextosPartidas() {
            const select = document.getElementById('id_partida_ui');
            if (!select) return;
            
            for (let option of select.options) {
                const value = option.value;
                const text = option.text;
                const matches = text.match(/^(\d+)\s*-\s*([^-]+)\s*-\s*(.+)$/);
                
                if (matches) {
                    const codigo = matches[1].trim();
                    const partida = matches[2].trim();
                    const descripcion = matches[3].trim();
                    const textoCompleto = text.trim();
                    
                    if (value == '2') {
                        partida2 = { codigo, partida, descripcion, textoCompleto };
                    } else if (value == '3') {
                        partida3 = { codigo, partida, descripcion, textoCompleto };
                    } else if (value == '1') {
                        partida1 = { codigo, partida, descripcion, textoCompleto };
                    }
                } else {
                    if (value == '2') {
                        partida2.textoCompleto = text;
                        partida2.partida = text;
                        partida2.descripcion = text;
                    } else if (value == '3') {
                        partida3.textoCompleto = text;
                        partida3.partida = text;
                        partida3.descripcion = text;
                    } else if (value == '1') {
                        partida1.textoCompleto = text;
                        partida1.partida = text;
                        partida1.descripcion = text;
                    }
                }
            }
            
            partidaCatalogoCargado = true;
        }

        function obtenerTextoCompletoPartida(id) {
            const select = document.getElementById('id_partida_ui');
            
            if (select) {
                for (let option of select.options) {
                    if (option.value == id) {
                        return option.text;
                    }
                }
            }
            
            if (id == 2) {
                return partida2.textoCompleto || '2 - HOSPEDAJE INSTITUCIONAL';
            }
            if (id == 3) {
                return partida3.textoCompleto || '3 - 3390B ALIMENTOS';
            }
            if (id == 1) {
                return partida1.textoCompleto || '1 - 2210 ALIMENTOS';
            }
            
            return 'Partida ' + id;
        }

        function obtenerCodigoPartida(id) {
            if (id == 2) return partida2.partida || '';
            if (id == 3) return partida3.partida || '';
            if (id == 1) return partida1.partida || '';
            return '';
        }

        function obtenerDescripcionPartida(id) {
            if (id == 2) return partida2.descripcion || 'HOSPEDAJE';
            if (id == 3) return partida3.descripcion || 'ALIMENTOS';
            if (id == 1) return partida1.descripcion || 'ALIMENTOS';
            return '';
        }

        function obtenerTextoConBeneficio(id) {
            const descripcion = obtenerDescripcionPartida(id);
            if (id == 2) return `${descripcion}`;
            if (id == 3) return `${descripcion}`;
            if (id == 1) return `${descripcion}`;
            return 'Partida';
        }

        function eliminarPartida2() {
            const select = document.getElementById('id_partida_ui');
            if (!select) return;
            
            guardarTextosPartidas();
            
            const opciones = select.querySelectorAll('option[value="2"]');
            opciones.forEach(option => option.remove());
        }

        function esFicOUg() {
            const grupoUsuario = $('#grupo_usuario').val();
            const idPerfilCatalogo = parseInt($('#id_perfil_catalogo').val() || 0);
            return [9, 10].includes(idPerfilCatalogo) || ['fic', 'ug'].includes(grupoUsuario);
        }

        function actualizarPartidas() {
            const tieneAlimentos = $('#tiene_alimentos').val() === '1';
            const tieneHospedaje = $('#tiene_hospedaje').val() === '1';
            const esFicOUgActual = esFicOUg();

            guardarTextosPartidas();

            const selectPartidas = document.getElementById('id_partida_ui');
            if (selectPartidas) {
                const opcionesAEliminar = selectPartidas.querySelectorAll('option[value="2"]');
                opcionesAEliminar.forEach(opt => opt.remove());
            }

            const valorActualSelect = parseInt($('#id_partida_ui').val() || 0);
            const valorLimpio = (valorActualSelect === 2) ? 0 : valorActualSelect;

            $('#id_partida').val('');
            $('#id_partida_alimentos').val('');
            $('#id_partida_alimentos_ui').val('');
            $('#partidaManualWrapper').show();
            $('#partidaHospedajeWrapper').hide();
            $('#partidaAlimentosWrapper').hide();
            $('#id_partida_ui').prop('disabled', false);
            
            if (!tieneHospedaje) {
                $('#hospedajeConfiguracionHoteles').addClass('d-none');
                $('#hospedajePlanWrapper').addClass('d-none');
            } else {
                $('#hospedajeConfiguracionHoteles').removeClass('d-none');
                $('#hospedajePlanWrapper').removeClass('d-none');
            }

            if (tieneAlimentos && !tieneHospedaje) {
                $('#partidaAlimentosWrapper').show();
                $('#partidaHospedajeWrapper').hide();
                
                if (esFicOUgActual) {
                    const texto = obtenerTextoConBeneficio(3);
                    $('#id_partida_ui').val('3').prop('disabled', true);
                    $('#id_partida').val('3');
                    $('#id_partida_alimentos_ui').val(texto);
                    $('#id_partida_alimentos').val(3);
                    $('#partidaHelpText').text('Partida automática para FIC/UG (no editable)');
                } else {
                    if (valorLimpio > 0 && (valorLimpio === 1 || valorLimpio === 3)) {
                        $('#id_partida_alimentos_ui').val(obtenerTextoConBeneficio(valorLimpio));
                        $('#id_partida_alimentos').val(valorLimpio);
                        $('#id_partida_ui').val(valorLimpio);
                        $('#partidaHelpText').text('Partida seleccionada para alimentos');
                    } else {
                        $('#id_partida_ui').val('');
                        $('#id_partida_alimentos_ui').val('');
                        $('#id_partida_alimentos').val('');
                        $('#partidaHelpText').text('Selecciona la partida presupuestal para alimentos');
                    }
                    $('#id_partida_ui').prop('disabled', false);
                }
                
                $('#monto_deposito, #monto_total_alimentos_ui, #id_nivel_cliente, #fec_vigencia_desde, #fec_vigencia_hasta').closest('.col-md-3').show();
            } else if (tieneHospedaje && !tieneAlimentos) {
                $('#partidaHospedajeWrapper').show();
                $('#partidaAlimentosWrapper').hide();
                
                $('#partidaHospedajeWrapper input').val(obtenerTextoConBeneficio(2));
                $('#partidaHelpText').text('Partida fija para hospedaje');
                $('#partidaHospedajeWrapper small').text('Partida fija para hospedaje');
                
                if (valorActualSelect === 2) {
                    $('#id_partida_ui').val('');
                }
                
                $('#id_partida_ui').prop('disabled', false);
                
                $('#monto_deposito, #monto_total_alimentos_ui, #id_nivel_cliente, #fec_vigencia_desde, #fec_vigencia_hasta').closest('.col-md-3').hide();
            } else if (tieneAlimentos && tieneHospedaje) {
                $('#partidaHospedajeWrapper').show();
                $('#partidaAlimentosWrapper').show();
                
                $('#partidaHospedajeWrapper input').val(obtenerTextoConBeneficio(2));
                $('#partidaHospedajeWrapper small').text('Partida fija para hospedaje');
                
                $('#id_partida_ui').prop('disabled', false);
                $('#partidaHelpText').text('Selecciona la partida para alimentos');
            
                if (valorActualSelect === 2) {
                    $('#id_partida_ui').val('');
                }
                
                if (valorLimpio > 0 && (valorLimpio === 1 || valorLimpio === 3)) {
                    $('#id_partida_alimentos_ui').val(obtenerTextoConBeneficio(valorLimpio));
                    $('#id_partida_alimentos').val(valorLimpio);
                    $('#id_partida_ui').val(valorLimpio);
                    $('#partidaAlimentosWrapper small').text('Partida seleccionada para alimentos');
                } else {
                    if (esFicOUgActual) {
                        const texto = obtenerTextoConBeneficio(3);
                        $('#id_partida_alimentos_ui').val(texto);
                        $('#id_partida_alimentos').val(3);
                        $('#id_partida_ui').val(3).prop('disabled', true);
                        $('#partidaAlimentosWrapper small').text('Partida automática para FIC/UG');
                    } else {
                        $('#id_partida_alimentos_ui').val('');
                        $('#id_partida_alimentos').val('');
                        $('#partidaAlimentosWrapper small').text('Selecciona una partida');
                    }
                }
                
                $('#monto_deposito, #monto_total_alimentos_ui, #id_nivel_cliente, #fec_vigencia_desde, #fec_vigencia_hasta').closest('.col-md-3').show();
            } else {
                $('#partidaHelpText').text('Selecciona la partida presupuestal');
                $('#id_partida_ui').prop('disabled', false);
                
                if (valorActualSelect === 2) {
                    $('#id_partida_ui').val('');
                }
                
                $('#monto_deposito, #monto_total_alimentos_ui, #id_nivel_cliente, #fec_vigencia_desde, #fec_vigencia_hasta').closest('.col-md-3').hide();
            }
            
            $('#id_partida_ui').off('change.alimentos').on('change.alimentos', function() {
                const val = parseInt($(this).val() || 0);
                const tieneAlimentosActual = $('#tiene_alimentos').val() === '1';
                const esFicOUgActual2 = esFicOUg();
                
                if (val === 2) {
                    $('#id_partida_ui').val('');
                    $('#id_partida_alimentos_ui').val('');
                    $('#id_partida_alimentos').val('');
                    $('#partidaAlimentosWrapper small').text('Selecciona partida 1 o 3');
                    return;
                }
                
                if (tieneAlimentosActual && (val === 1 || val === 3)) {
                    $('#id_partida_alimentos_ui').val(obtenerTextoConBeneficio(val));
                    $('#id_partida_alimentos').val(val);
                    $('#partidaAlimentosWrapper small').text('Partida seleccionada para alimentos');
                } else if (val === 0 || val === '') {
                    $('#id_partida_alimentos_ui').val('');
                    $('#id_partida_alimentos').val('');
                }
            });
        }

        function recargarCatalogo() {
            guardarTextosPartidas();
            eliminarPartida2();
            actualizarPartidas();
        }

        $(document).ready(function() {
            setTimeout(function() {
                guardarTextosPartidas();
                eliminarPartida2();
                actualizarPartidas();
            }, 500);
            
            $('#tiene_hospedaje, #tiene_alimentos, #grupo_usuario, #id_perfil_catalogo').on('change', function() {
                setTimeout(function() {
                    guardarTextosPartidas();
                    eliminarPartida2();
                    actualizarPartidas();
                }, 150);
            });
            
            $('#pax_ui').on('change', function() {
                setTimeout(function() {
                    guardarTextosPartidas();
                    eliminarPartida2();
                    actualizarPartidas();
                }, 150);
            });
            
            $(document).on('select2:open select2:select select2:close', '#id_partida_ui', function() {
                setTimeout(function() {
                    guardarTextosPartidas();
                    eliminarPartida2();
                }, 150);
            });
            
            $('#id_partida_ui').on('change', function() {
                setTimeout(function() {
                    guardarTextosPartidas();
                    eliminarPartida2();
                }, 50);
            });
        });

        window.actualizarPartidas = actualizarPartidas;
        window.obtenerTextoCompletoPartida = obtenerTextoCompletoPartida;
        window.obtenerCodigoPartida = obtenerCodigoPartida;
        window.obtenerDescripcionPartida = obtenerDescripcionPartida;
        window.obtenerTextoConBeneficio = obtenerTextoConBeneficio;
        window.guardarTextosPartidas = guardarTextosPartidas;
        window.recargarCatalogo = recargarCatalogo;
        window.esFicOUg = esFicOUg;
        
    })();
</script>
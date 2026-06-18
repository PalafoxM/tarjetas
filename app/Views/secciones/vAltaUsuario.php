<?php
$session = \Config\Services::session();
$contextoUsuario = $contextoUsuario ?? [];
$catalogRoleOptions = $catalogRoleOptions ?? [];
$idUsuarioEditar = (int) ($idUsuarioEditar ?? 0);
$esNuevo = $idUsuarioEditar <= 0;
$extractCatalogAmount = static function ($item): string {
    $values = is_object($item) ? get_object_vars($item) : (array) $item;
    $preferredKeys = [
        'monto_diario',
        'tarifa_diaria',
        'tarifa',
        'precio',
        'costo',
        'importe',
        'monto',
        'valor',
    ];

    foreach ($preferredKeys as $key) {
        if (array_key_exists($key, $values) && is_numeric($values[$key])) {
            return (string) $values[$key];
        }
    }

    foreach ($values as $key => $value) {
        if (preg_match('/(monto|tarifa|precio|costo|importe|valor)/i', (string) $key) && is_numeric($value)) {
            return (string) $value;
        }
    }

    return '';
};
?>
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
    data-list-url="<?= esc(base_url('index.php/Inicio/Usuarios'), 'attr') ?>"
    data-catalog-context="<?= esc(json_encode($contextoUsuario, JSON_UNESCAPED_UNICODE), 'attr') ?>"
    data-role-options="<?= esc(json_encode($catalogRoleOptions, JSON_UNESCAPED_UNICODE), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white" id="cajeroPageTitle"><?= $esNuevo ? 'Nuevo usuario' : 'Editar usuario' ?></h3>
            <p class="text-muted mb-0">Captura la informacion del usuario en una vista completa para trabajar mas comodo.</p>
        </div>
        <a href="<?= base_url('index.php/Inicio/Usuarios') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Regresar
        </a>
    </div>

    <form id="cajeroForm">
        <div class="card">
            <div class="card-body">
                <input type="hidden" name="id_usuario" id="id_usuario" value="<?= esc((string) $idUsuarioEditar, 'attr') ?>">
                <input type="hidden" name="grupo_usuario" id="grupo_usuario">

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="categoria_ui">Categoria</label>
                        <select class="form-control js-select2-catalog" id="categoria_ui" data-placeholder="Buscar categoria">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_pais">Pais region</label>
                        <select class="form-control js-select2-catalog" name="id_pais" id="id_pais" data-placeholder="Buscar pais o region">
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
                        <label class="form-label" for="folio_ui">Folio</label>
                        <input type="text" class="form-control" id="folio_ui" placeholder="folio" inputmode="numeric" pattern="[0-9]*" maxlength="20">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="subf_ui">Subfolio</label>
                        <input type="text" class="form-control crud-ui-upper" id="subf_ui" placeholder="subf" inputmode="text" maxlength="20">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="pax_ui">Pax</label>
                        <input type="number" class="form-control" id="pax_ui" placeholder="pax">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="anf_gto_ui">Anfitrión Guanajuato</label>
                        <input type="text" class="form-control crud-ui-upper" id="anf_gto_ui" placeholder="anf gto" inputmode="text" maxlength="80">
                    </div>

                    <div class="col-md-12">
                        <label class="crud-ui-grid-label">Nombre</label>
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
                        <label class="form-label" for="usuario">Usuario</label>
                        <input class="form-control crud-ui-lower" name="usuario" id="usuario" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="correo">Correo</label>
                        <input type="email" class="form-control crud-ui-lower" name="correo" id="correo" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contrasenia">Contraseña</label>
                        <input type="password" class="form-control crud-ui-lower" name="contrasenia" id="contrasenia">
                        <small class="text-muted">En edición, déjala vacía para conservar la actual.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nip">NIP</label>
                        <input class="form-control crud-ui-lower" id="nip" readonly placeholder="Se genera automaticamente en el alta">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="tiene_alimentos">Tiene alimentos</label>
                        <select class="form-control" name="tiene_alimentos" id="tiene_alimentos">
                            <option value="">Seleccione</option>
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="fecha_check_in">Vigencia desde</label>
                        <input type="date" class="form-control" name="fecha_check_in" id="fecha_check_in">
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="fecha_check_out">Vigencia hasta</label>
                        <input type="date" class="form-control" name="fecha_check_out" id="fecha_check_out">
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="id_nivel_cliente">Tarifa diaria</label>
                        <select class="form-control js-select2-catalog" name="id_nivel_cliente" id="id_nivel_cliente" data-placeholder="Buscar tarifa diaria">
                            <option value="">Seleccione</option>
                        </select>
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="monto_deposito">Monto deposito individual</label>
                        <input type="number" step="0.01" class="form-control" name="monto_deposito" id="monto_deposito" readonly>
                    </div>
                    <div class="col-md-3 alimentos-field">
                        <label class="form-label" for="monto_total_alimentos_ui">Monto total</label>
                        <input type="number" step="0.01" class="form-control" id="monto_total_alimentos_ui" readonly>
                    </div>
                    <input type="hidden" name="id_partida" id="id_partida">
                    <div class="col-md-3 alimentos-field" id="partidaAlimentosWrapper">
                        <label class="form-label" for="id_partida_alimentos_ui">Partida</label>
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
                        <label class="form-label" for="id_tipo_habitacion">Tipo habitacion</label>
                        <select class="form-control js-select2-catalog" name="id_tipo_habitacion" id="id_tipo_habitacion" data-placeholder="Buscar tipo de habitacion">
                            <option value="">Seleccione</option>
                            <?php foreach ($catTipoHabitacion as $tipo): ?>
                            <option value="<?= esc($tipo->id_tipo_habitacion, 'attr') ?>" data-tarifa="<?= esc($extractCatalogAmount($tipo), 'attr') ?>"><?= esc($tipo->dsc_tipo_habitacion, 'html') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="fec_vigencia_desde">Vigencia estancia desde</label>
                        <input type="date" class="form-control" name="fec_vigencia_desde" id="fec_vigencia_desde">
                    </div>
                    <div class="col-md-3 hospedaje-field">
                        <label class="form-label" for="fec_vigencia_hasta">Vigencia estancia hasta</label>
                        <input type="date" class="form-control" name="fec_vigencia_hasta" id="fec_vigencia_hasta">
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
                    <div class="col-md-3 hospedaje-field" id="partidaHospedajeWrapper">
                        <label class="form-label" for="id_partida_hospedaje_ui">Partida</label>
                        <input type="text" class="form-control" id="id_partida_hospedaje_ui" readonly>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-wrap justify-content-end gap-2">
                <a href="<?= base_url('index.php/Inicio/Usuarios') ?>" class="btn btn-secondary">Cancelar</a>
                <?php if (!empty($contextoUsuario['can_edit_user_catalog'])): ?>
                <button type="submit" class="btn btn-primary" id="guardarCajero">Guardar</button>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<?php
$session = \Config\Services::session();
$contextoUsuario = $contextoUsuario ?? [];
$catalogRoleOptions = $catalogRoleOptions ?? [];
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
    id="usuariosPage"
    data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>"
    data-catalog-context="<?= esc(json_encode($contextoUsuario, JSON_UNESCAPED_UNICODE), 'attr') ?>"
    data-role-options="<?= esc(json_encode($catalogRoleOptions, JSON_UNESCAPED_UNICODE), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Catálogo de usuarios</h3>
            <p class="text-muted mb-0">Consulta y administra usuarios por grupo, respetando la visibilidad y el alcance del perfil autenticado.</p>
        </div>
        <?php if (!empty($contextoUsuario['can_edit_user_catalog'])): ?>
        <button type="button" class="btn btn-primary" id="nuevoCajero">
            <i class="mdi mdi-account-plus me-1"></i> Nuevo usuario
        </button>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Atrás
        </a>
        <?php if (!empty($contextoUsuario['active_group'])): ?>
        <span class="badge bg-info">Grupo: <?= esc((string) ($contextoUsuario['group_label'] ?? '')) ?></span>
        <span class="badge bg-secondary">Rol: <?= esc((string) ($contextoUsuario['group_role_label'] ?? '')) ?></span>
        <?php elseif (!empty($contextoUsuario['is_ti_master'])): ?>
        <span class="badge bg-success">TI master</span>
        <?php endif; ?>
        <?php if (!empty($contextoUsuario['is_group_capturista'])): ?>
        <span class="badge bg-warning text-dark">Modo consulta</span>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="cajerosTable"
                   class="table table-dark table-hover align-middle"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-page-list="[5,10,25,50,100]"
                   data-show-columns="true"
                   data-show-refresh="true"
                   data-locale="es-MX">
                <thead>
                    <tr>
                        <th data-field="id_usuario" data-sortable="true">ID</th>
                        <th data-field="usuario" data-sortable="true">Usuario</th>
                        <th data-field="nombre_completo" data-sortable="true">Nombre</th>
                        <th data-field="grupo_visible" data-sortable="true">Grupo</th>
                        <th data-field="rol_visible" data-sortable="true">Rol visible</th>
                        <th data-field="nip" data-align="center">NIP</th>
                        <th data-field="activo_qr" data-formatter="saeg.principal.activo" data-align="center">QR activo</th>
                        <th data-field="tiene_hospedaje" data-formatter="cajeros.estadoBooleano" data-align="center">Hospedaje</th>
                        <th data-field="fec_vigencia_hasta" data-formatter="saeg.principal.fecha" data-sortable="true">Vigencia hasta</th>
                        <th data-field="tarifa_noche" data-formatter="cajeros.moneda" data-align="center">Tarifa diaria</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cajeroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form id="cajeroForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cajeroModalTitle">Nuevo usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="id_usuario">
                    <input type="hidden" name="grupo_usuario" id="grupo_usuario">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="categoria_ui">Categoría</label>
                            <select class="form-control js-select2-catalog" id="categoria_ui" data-placeholder="Buscar categoría">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="id_pais">País región</label>
                            <select class="form-control js-select2-catalog" name="id_pais" id="id_pais" data-placeholder="Buscar país o región">
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
                            <input type="text" class="form-control crud-ui-lower" id="folio_ui" placeholder="folio">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="subf_ui">Subfolio</label>
                            <input type="text" class="form-control crud-ui-lower" id="subf_ui" placeholder="subf">
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
                            <label class="form-label" for="pax_ui">Pax</label>
                            <input type="number" class="form-control" id="pax_ui" placeholder="pax">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="anf_gto_ui">Anf gto</label>
                            <input type="text" class="form-control crud-ui-lower" id="anf_gto_ui" placeholder="anf gto">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_check_in">Vigencia desde</label>
                            <input type="date" class="form-control" name="fecha_check_in" id="fecha_check_in">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="fecha_check_out">Vigencia hasta</label>
                            <input type="date" class="form-control" name="fecha_check_out" id="fecha_check_out">
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
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="id_establecimiento_hotel">Hotel</label>
                            <input type="number" class="form-control" name="id_establecimiento_hotel" id="id_establecimiento_hotel">
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
                            <input class="form-control crud-ui-lower" id="nip" readonly placeholder="Se genera automáticamente en el alta">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="tiene_alimentos">Tiene alimentos</label>
                            <select class="form-control" name="tiene_alimentos" id="tiene_alimentos">
                                <option value="">Seleccione</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="tiene_hospedaje">Tiene hospedaje</label>
                            <select class="form-control" name="tiene_hospedaje" id="tiene_hospedaje">
                                <option value="">Seleccione</option>
                                <option value="1">Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="id_nivel_cliente">Tarifa diaria</label>
                            <select class="form-control js-select2-catalog" name="id_nivel_cliente" id="id_nivel_cliente" data-placeholder="Buscar tarifa diaria">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="id_tipo_habitacion">Tipo habitación</label>
                            <input type="number" class="form-control" name="id_tipo_habitacion" id="id_tipo_habitacion">
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="fec_vigencia_desde">Vigencia desde</label>
                            <input type="date" class="form-control" name="fec_vigencia_desde" id="fec_vigencia_desde">
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="fec_vigencia_hasta">Vigencia hasta</label>
                            <input type="date" class="form-control" name="fec_vigencia_hasta" id="fec_vigencia_hasta">
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="tarifa_noche">Tarifa diaria de hospedaje</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa_noche" id="tarifa_noche">
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="tarifa_total">Tarifa total</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa_total" id="tarifa_total">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="monto_deposito">Monto depósito</label>
                            <input type="number" step="0.01" class="form-control" name="monto_deposito" id="monto_deposito">
                        </div>
                        <div class="col-md-3 hospedaje-field">
                            <label class="form-label" for="noche">Noches</label>
                            <input type="number" class="form-control" name="noche" id="noche">
                        </div>
                        <div class="col-md-3" id="partidaWrapper">
                            <label class="form-label" for="id_partida">Partida</label>
                            <select class="form-control js-select2-catalog" name="id_partida" id="id_partida" data-placeholder="Partida asignada">
                                <option value="">Seleccione</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardarCajero">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

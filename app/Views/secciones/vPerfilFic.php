<?php
$session = \Config\Services::session();
$hubTitle = (string) ($hubTitle ?? 'Perfil FIC');
$hubSubtitle = (string) ($hubSubtitle ?? 'Usuarios visibles pertenecientes al catálogo FIC.');
$hubMode = (string) ($perfilFicMode ?? 'admin');
$hubModeLabel = $hubMode === 'consulta' ? 'Consulta' : 'Administración';
$mostrarEdicion = !empty($ficSolicitudPuedeCrear);
$ficSolicitudPerfilOptions = is_array($ficSolicitudPerfilOptions ?? null) ? $ficSolicitudPerfilOptions : [];
$ficSolicitudEstablecimientoId = (int) ($ficSolicitudEstablecimientoId ?? 0);
?>
<style>
    .crud-ui-upper { text-transform: uppercase; }
    .crud-ui-lower { text-transform: lowercase; }
    .crud-ui-grid-label { display: block; margin-bottom: .5rem; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #9fb0c9; }
    .select2-container { width: 100% !important; }
    #usuariosPage .fixed-table-body { overflow-x: auto; }
    #usuariosPage #cajerosTable { min-width: 1280px; }
    #usuariosPage .usuario-actions, #solicitudesFicPage .usuario-actions { display: inline-flex; flex-wrap: nowrap; gap: .25rem; white-space: nowrap; }
    #usuariosPage .usuario-actions .btn, #solicitudesFicPage .usuario-actions .btn { display: inline-flex; width: 32px; height: 30px; align-items: center; justify-content: center; padding: 0; }
    .fic-solicitudes-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .96), rgba(18, 24, 37, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
    }
    .fic-solicitudes-modal .modal-content {
        background: #0f172a !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(148, 163, 184, .2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
    }
    .fic-solicitudes-modal .modal-header,
    .fic-solicitudes-modal .modal-footer {
        border-color: rgba(148, 163, 184, .18) !important;
        background: rgba(15, 23, 42, .98);
    }
    .fic-solicitudes-modal .modal-title,
    .fic-solicitudes-modal .form-label,
    .fic-solicitudes-modal .text-muted,
    .fic-solicitudes-modal .small,
    .fic-solicitudes-modal .form-text { color: #cbd5e1 !important; }
    .fic-solicitudes-modal .form-control,
    .fic-solicitudes-modal .form-select,
    .fic-solicitudes-modal textarea {
        background-color: #111827 !important;
        border-color: rgba(148, 163, 184, .28) !important;
        color: #f8fafc !important;
    }
    .fic-solicitudes-modal .form-control:focus,
    .fic-solicitudes-modal .form-select:focus,
    .fic-solicitudes-modal textarea:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .18) !important;
    }
    .fic-solicitudes-modal .btn-close { filter: invert(1) grayscale(100%); opacity: .9; }
    .fic-solicitudes-table-wrap { overflow-x: auto; }
    .fic-solicitudes-table { min-width: 1280px; }
</style>

<div class="container-fluid py-4"
     id="usuariosPage"
     data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>"
     data-alta-url="<?= esc(base_url('index.php/Inicio/AltaUsuario'), 'attr') ?>"
     data-usuarios-url="<?= esc(base_url('index.php/Usuario/getVistaUsuarioFic'), 'attr') ?>"
     data-solicitudes-url="<?= esc($ficSolicitudListadoUrl ?? base_url('index.php/Inicio/getSolicitudesUsuarioFicPerfil'), 'attr') ?>"
     data-solicitud-detail-url="<?= esc($ficSolicitudDetalleUrl ?? base_url('index.php/Inicio/getSolicitudUsuarioFicPerfil'), 'attr') ?>"
     data-solicitud-save-url="<?= esc($ficSolicitudGuardarUrl ?? base_url('index.php/Inicio/guardarSolicitudUsuarioFicPerfil'), 'attr') ?>"
     data-solicitud-cancel-url="<?= esc($ficSolicitudCancelarUrl ?? base_url('index.php/Inicio/cancelarSolicitudUsuarioFicPerfil'), 'attr') ?>"
     data-solicitud-establecimiento-id="<?= esc((string) $ficSolicitudEstablecimientoId, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white"><?= esc($hubTitle) ?></h3>
            <p class="text-muted mb-0"><?= esc($hubSubtitle) ?></p>
        </div>
        <?php if ($mostrarEdicion): ?>
            <a href="javascript:void(0);" class="btn btn-primary" id="btnNuevaSolicitudUsuarioFic">
                <i class="mdi mdi-account-plus me-1"></i> Nueva solicitud
            </a>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info">Perfil FIC</span>
        <span class="badge bg-secondary"><?= esc($hubModeLabel) ?></span>
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
                        <th data-field="activo_qr" data-formatter="cajeros.qrActivo" data-align="center">QR activo</th>
                        <th data-field="tiene_hospedaje" data-formatter="cajeros.estadoBooleano" data-align="center">Hospedaje</th>
                        <th data-field="tiene_alimentos" data-formatter="cajeros.estadoBooleano" data-align="center">Alimentos</th>
                        <th data-field="fec_vigencia_desde" data-formatter="saeg.principal.fecha" data-sortable="true">Vigencia desde</th>
                        <th data-field="fec_vigencia_hasta" data-formatter="saeg.principal.fecha" data-sortable="true">Vigencia hasta</th>
                        <th data-field="monto_deposito" data-formatter="cajeros.moneda" data-align="center">Monto</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center" data-width="88" data-width-unit="px">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="card fic-solicitudes-card mt-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1 text-white">Solicitudes de usuario FIC</h4>
                    <p class="text-muted mb-0">Solicitudes de alta capturadas desde este perfil y visibles para seguimiento.</p>
                </div>
                <?php if ($mostrarEdicion): ?>
                    <button type="button" class="btn btn-outline-light" id="btnAbrirSolicitudUsuarioFic">
                        <i class="mdi mdi-file-document-plus-outline me-1"></i> Crear solicitud
                    </button>
                <?php endif; ?>
            </div>

            <div class="fic-solicitudes-table-wrap">
                <table id="tablaSolicitudesFic"
                       class="table table-dark table-hover align-middle fic-solicitudes-table"
                       data-search="true"
                       data-pagination="true"
                       data-page-size="10"
                       data-page-list="[10,25,50,100]"
                       data-show-columns="true"
                       data-show-refresh="true"
                       data-locale="es-MX">
                    <thead>
                        <tr>
                            <th data-field="id_solicitud_usuario" data-sortable="true">ID</th>
                            <th data-field="perfil_solicitado" data-sortable="true">Perfil solicitado</th>
                            <th data-field="usuario" data-sortable="true">Usuario</th>
                            <th data-field="nombre_completo" data-sortable="true">Nombre</th>
                            <th data-field="correo" data-sortable="true">Correo</th>
                            <th data-field="estatus" data-formatter="ficSolicitudEstadoFormatter" data-sortable="true">Estatus</th>
                            <th data-field="fec_reg" data-formatter="saeg.principal.fecha" data-sortable="true">Fecha</th>
                            <th data-field="acciones" data-formatter="ficSolicitudAccionesFormatter" data-align="center" data-width="120" data-width-unit="px">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade fic-solicitudes-modal" id="modalSolicitudUsuarioFic" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formSolicitudUsuarioFic" autocomplete="off">
                <?= csrf_field() ?>
                <div class="modal-header border-secondary">
                    <div>
                        <h5 class="modal-title" id="solicitudUsuarioFicTitulo">Nueva solicitud de usuario</h5>
                        <p class="text-muted mb-0 small">Captura los datos del usuario y el perfil solicitado dentro del catálogo FIC.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-none mb-3" id="solicitudUsuarioFicAlert" role="alert"></div>
                    <input type="hidden" id="solicitud_usuario_id_fic" name="id_solicitud_usuario" value="0">
                    <input type="hidden" id="solicitud_usuario_establecimiento_fic" name="id_establecimiento" value="<?= esc((string) $ficSolicitudEstablecimientoId, 'attr') ?>">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_usuario">Usuario</label>
                            <input type="text" class="form-control crud-ui-lower" id="solicitud_usuario_fic_usuario" name="usuario" required autocomplete="off" placeholder="usuario">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_nombre">Nombre</label>
                            <input type="text" class="form-control crud-ui-upper" id="solicitud_usuario_fic_nombre" name="nombre" required autocomplete="off" placeholder="NOMBRE">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_perfil">Perfil solicitado</label>
                            <select class="form-select" id="solicitud_usuario_fic_perfil" name="id_perfil_solicitado" required>
                                <option value="">Selecciona una opción</option>
                                <?php foreach ($ficSolicitudPerfilOptions as $perfilOption): ?>
                                    <option value="<?= esc((string) ($perfilOption['id_perfil_fic'] ?? 0), 'attr') ?>"><?= esc((string) ($perfilOption['dsc_perfil'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_primer_apellido">Primer apellido</label>
                            <input type="text" class="form-control crud-ui-upper" id="solicitud_usuario_fic_primer_apellido" name="primer_apellido" required autocomplete="off" placeholder="PRIMER APELLIDO">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_segundo_apellido">Segundo apellido</label>
                            <input type="text" class="form-control crud-ui-upper" id="solicitud_usuario_fic_segundo_apellido" name="segundo_apellido" autocomplete="off" placeholder="SEGUNDO APELLIDO">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label" for="solicitud_usuario_fic_correo">Correo</label>
                            <input type="email" class="form-control crud-ui-lower" id="solicitud_usuario_fic_correo" name="correo" autocomplete="off" placeholder="correo@dominio.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="solicitud_usuario_fic_observaciones">Observaciones</label>
                            <textarea class="form-control" id="solicitud_usuario_fic_observaciones" name="observaciones" rows="3" placeholder="Opcional"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarSolicitudUsuarioFic">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

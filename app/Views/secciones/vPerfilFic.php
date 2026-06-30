<?php
$session = \Config\Services::session();
$hubTitle = (string) ($hubTitle ?? 'Perfil FIC');
$hubSubtitle = (string) ($hubSubtitle ?? 'Usuarios visibles pertenecientes al catálogo FIC.');
$hubMode = (string) ($perfilFicMode ?? 'admin');
$hubModeLabel = $hubMode === 'consulta' ? 'Consulta' : 'Administración';
$mostrarEdicion = !empty($ficSolicitudPuedeCrear);
$mostrarSeguimientoSolicitudes = $hubMode === 'consulta';
$ficSolicitudPerfilOptions = is_array($ficSolicitudPerfilOptions ?? null) ? $ficSolicitudPerfilOptions : [];
$ficSolicitudEstablecimientoId = (int) ($ficSolicitudEstablecimientoId ?? 0);
?>
<style>
    .crud-ui-upper { text-transform: uppercase; }
    .crud-ui-lower { text-transform: lowercase; }
    .crud-ui-grid-label { display: block; margin-bottom: .5rem; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #9fb0c9; }
    .select2-container { width: 100% !important; }
    #usuariosPage .fixed-table-body { overflow-x: auto; }
    #usuariosPage #cajerosTable { min-width: 1560px; }
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
     data-fic-mode="<?= esc($hubMode, 'attr') ?>"
     data-solicitud-establecimiento-id="<?= esc((string) $ficSolicitudEstablecimientoId, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white"><?= esc($hubTitle) ?></h3>
            <p class="text-muted mb-0"><?= esc($hubSubtitle) ?></p>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info">Perfil FIC</span>
        <span class="badge bg-secondary"><?= esc($hubModeLabel) ?></span>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1 text-white">Usuarios FIC</h4>
                    <p class="text-muted mb-0">Consulta el catálogo visible de usuarios FIC según tu perfil.</p>
                </div>
                <?php if ($mostrarEdicion): ?>
                    <a class="btn btn-primary" href="<?= esc(base_url('index.php/Inicio/SolicitudAlta/fic'), 'attr') ?>">
                        <i class="mdi mdi-file-document-plus-outline me-1"></i> Solicitud de folio
                    </a>
                <?php endif; ?>
            </div>
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
                        <th data-field="usuario" data-formatter="ficSolicitudUsuarioFormatter" data-sortable="true">Usuario</th>
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
                        <th data-field="monto_deposito_reservado" data-formatter="cajeros.moneda" data-align="center">Saldo reservado</th>
                        <th data-field="monto_deposito_operativo" data-formatter="cajeros.moneda" data-align="center">Saldo operativo</th>
                        <th data-field="deposito_programado_estatus" data-formatter="cajeros.estadoDepositoProgramado" data-align="center">Estado del programa</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center" data-width="88" data-width-unit="px">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

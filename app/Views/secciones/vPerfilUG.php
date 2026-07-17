<?php
$session = \Config\Services::session();
$hubTitle = (string) ($hubTitle ?? 'Perfil UG');
$hubSubtitle = (string) ($hubSubtitle ?? 'Usuarios visibles pertenecientes al catálogoUG.');
$hubMode = (string) ($perfilUgMode ?? 'admin');
$hubModeLabel = $hubMode === 'consulta' ? 'Consulta' : 'Administración';
$mostrarEdicion = $hubMode === 'admin';
$mostrarSeguimientoSolicitudes = $hubMode === 'consulta';
$perfilOptions = is_array($ugSolicitudPerfilOptions ?? null) ? $ugSolicitudPerfilOptions : [];
$solicitudEstablecimientoId = (int) ($ugSolicitudEstablecimientoId ?? 0);
?>
<style>
    .crud-ui-upper { text-transform: uppercase; }
    .crud-ui-lower { text-transform: lowercase; }
    .crud-ui-grid-label { display: block; margin-bottom: .5rem; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #9fb0c9; }
    .select2-container { width: 100% !important; }
    #usuariosPage .fixed-table-body { overflow-x: auto; }
    #usuariosPage #cajerosTable { min-width: 1560px; }
    #usuariosPage .usuario-actions { display: inline-flex; flex-wrap: nowrap; gap: .25rem; white-space: nowrap; }
    #usuariosPage .usuario-actions .btn { display: inline-flex; width: 32px; height: 30px; align-items: center; justify-content: center; padding: 0; }
</style>

<div class="container-fluid py-4"
     id="usuariosPage"
     data-catalogo-grupo="ug"
     data-solicitudes-url="<?= esc($ugSolicitudListadoUrl ?? base_url('index.php/Inicio/getSolicitudesUsuarioUGPerfil'), 'attr') ?>"
     data-solicitud-detail-url="<?= esc($ugSolicitudDetalleUrl ?? base_url('index.php/Inicio/getSolicitudUsuarioUGPerfil'), 'attr') ?>"
     data-solicitud-save-url="<?= esc($ugSolicitudGuardarUrl ?? base_url('index.php/Inicio/guardarSolicitudUsuarioUGPerfil'), 'attr') ?>"
     data-solicitud-cancel-url="<?= esc($ugSolicitudCancelarUrl ?? base_url('index.php/Inicio/cancelarSolicitudUsuarioUGPerfil'), 'attr') ?>"
     data-solicitud-establecimiento-id="<?= esc((string) $solicitudEstablecimientoId, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white"><?= esc($hubTitle) ?></h3>
            <p class="text-muted mb-0"><?= esc($hubSubtitle) ?></p>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info">Perfil UG</span>
        <span class="badge bg-secondary"><?= esc($hubModeLabel) ?></span>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1 text-white">Usuarios UG</h4>
                    <p class="text-muted mb-0">Consulta el catálogo visible de usuarios UG según tu perfil.</p>
                </div>
                <?php if ($mostrarEdicion): ?>
                    <a class="btn btn-primary" href="<?= esc(base_url('index.php/Inicio/SolicitudAlta/ug'), 'attr') ?>">
                        <i class="mdi mdi-file-document-plus-outline me-1"></i> Solicitud de folio
                    </a>
                <?php endif; ?>
            </div>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="filtro_dia_llegada">Día de llegada</label>
                    <input type="date" class="form-control" id="filtro_dia_llegada">
                </div>
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-outline-light" id="limpiar_filtro_dia_llegada" disabled>Todos los días</button>
                </div>
                <div class="col-12 col-md">
                    <div class="text-muted small" id="filtro_dia_llegada_estado">Mostrando todos los folios por día de llegada.</div>
                </div>
            </div>
            <table id="cajerosTable"
                   class="table table-dark table-hover align-middle"
                   data-search="true"
                   data-pagination="true"
                   data-page-size="25"
                   data-page-list="[5,10,25,50,100]"
                   data-show-columns="true"
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
                        <th data-field="monto_deposito_reservado" data-formatter="cajeros.moneda" data-align="center">Saldo reservado</th>
                        <th data-field="monto_deposito_operativo" data-formatter="cajeros.moneda" data-align="center">Saldo operativo</th>
                        <th data-field="deposito_programado_estatus" data-formatter="cajeros.estadoDepositoProgramado" data-align="center">Estado del programa</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center" data-width="88" data-width-unit="px">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <?php if ($mostrarSeguimientoSolicitudes): ?>
    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="mb-1 text-white">Seguimiento de solicitudes UG</h4>
                    <p class="text-muted mb-0">Vista de consulta para revisar el estatus de las solicitudes capturadas desde este perfil.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table id="tablaSolicitudesCatalogo"
                       class="table table-dark table-hover align-middle"
                       data-search="true"
                       data-pagination="true"
                       data-side-pagination="server"
                       data-page-size="10"
                       data-page-list="[10,25,50,100]"
                       data-show-columns="false"
                       data-locale="es-MX">
                    <thead>
                        <tr>
                            <th data-field="id_solicitud_usuario" data-sortable="true">Solicitud</th>
                            <th data-field="perfil_solicitado" data-sortable="true">Perfil solicitado</th>
                            <th data-field="usuario" data-formatter="catalogoSolicitudUsuarioFormatter" data-sortable="true">Usuario</th>
                            <th data-field="nombre_completo" data-sortable="true">Nombre</th>
                            <th data-field="correo" data-sortable="true">Correo</th>
                            <th data-field="estatus" data-formatter="catalogoSolicitudEstadoFormatter" data-sortable="true">Estatus</th>
                            <th data-field="fec_reg" data-formatter="saeg.principal.fecha" data-sortable="true">Fecha</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$session = \Config\Services::session();
$hubTitle = (string) ($hubTitle ?? 'Perfil SECUL');
$hubSubtitle = (string) ($hubSubtitle ?? 'Usuarios visibles pertenecientes al catálogo SECUL.');
$hubMode = (string) ($perfilSeculMode ?? 'admin');
$hubModeLabel = $hubMode === 'consulta' ? 'Consulta' : 'Administración';
$mostrarEdicion = $hubMode === 'admin';
?>
<style>
    .crud-ui-upper { text-transform: uppercase; }
    .crud-ui-lower { text-transform: lowercase; }
    .crud-ui-grid-label { display: block; margin-bottom: .5rem; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #9fb0c9; }
    .select2-container { width: 100% !important; }
    #usuariosPage .fixed-table-body { overflow-x: auto; }
    #usuariosPage #cajerosTable { min-width: 1280px; }
    #usuariosPage .usuario-actions { display: inline-flex; flex-wrap: nowrap; gap: .25rem; white-space: nowrap; }
    #usuariosPage .usuario-actions .btn { display: inline-flex; width: 32px; height: 30px; align-items: center; justify-content: center; padding: 0; }
</style>

<div class="container-fluid py-4"
     id="usuariosPage"
     data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>"
     data-alta-url="<?= esc(base_url('index.php/Inicio/AltaUsuario'), 'attr') ?>"
     data-usuarios-url="<?= esc(base_url('index.php/Usuario/getVistaUsuarioSecul'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white"><?= esc($hubTitle) ?></h3>
            <p class="text-muted mb-0"><?= esc($hubSubtitle) ?></p>
        </div>
        <?php if ($mostrarEdicion): ?>
            <a href="<?= base_url('index.php/Inicio/AltaUsuario') ?>" class="btn btn-primary" id="nuevoCajero">
                <i class="mdi mdi-account-plus me-1"></i> Nuevo usuario
            </a>
        <?php endif; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-info">Perfil SECUL</span>
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
</div>

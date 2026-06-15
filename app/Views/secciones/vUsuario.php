<?php $session = \Config\Services::session(); ?>
<div class="container-fluid py-4" id="usuariosPage" data-id-perfil="<?= esc($session->get('id_perfil'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-1 text-white">Administración de cajeros</h3>
            <p class="text-muted mb-0">Consulta, registra, edita o elimina cajeros.</p>
        </div>
       <?php if ($session->get('id_perfil') == 1): ?> 
        <button type="button" class="btn btn-primary" id="nuevoCajero">
            <i class="mdi mdi-account-plus me-1"></i> Nuevo cajero
        </button>
        <?php endif; ?>
    </div>
     <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
        <i class="mdi mdi-arrow-left me-1"></i> Atrás
    </a>

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
                        <th data-field="id_usuario" data-sortable="true">ID Usuario</th>
                        <th data-field="usuario" data-sortable="true">Usuario</th>
                        <th data-field="nombre_completo" data-sortable="true">Nombre Completo</th>
                        <th data-field="monto_deposito" data-sortable="true">Tarifa diaria</th>
                        <th data-field="vigente_desde" data-formatter="saeg.principal.fecha" data-sortable="true">Vigencia desde</th>
                        <th data-field="vigente_hasta" data-formatter="saeg.principal.fecha"  data-align="center">Vigencia hasta</th>
                        <th data-field="folio" data-align="center">Folio</th>
                        <th data-field="activo" data-formatter="saeg.principal.activo" data-align="center">QR Activo</th>
                        <th data-field="acciones" data-formatter="cajeros.acciones" data-align="center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="cajeroModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="cajeroForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="cajeroModalTitle">Nuevo cajero</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="id_usuario">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="nombre">Nombre</label>
                            <input class="form-control" name="nombre" id="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="primer_apellido">Primer apellido</label>
                            <input class="form-control" name="primer_apellido" id="primer_apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="segundo_apellido">Segundo apellido</label>
                            <input class="form-control" name="segundo_apellido" id="segundo_apellido">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="correo">Correo</label>
                            <input type="email" class="form-control" name="correo" id="correo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario">Usuario</label>
                            <input class="form-control" name="usuario" id="usuario" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="contrasenia">Contraseña</label>
                            <input type="password" class="form-control" name="contrasenia" id="contrasenia">
                            <small class="text-muted">En edición, déjala vacía para conservar la actual.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="guardarCajero">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>


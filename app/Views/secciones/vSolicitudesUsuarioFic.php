<?php
$ficSolicitudListadoUrl = (string) ($ficSolicitudListadoUrl ?? base_url('index.php/Inicio/getSolicitudesUsuarioFicPerfil'));
$ficSolicitudDetalleUrl = (string) ($ficSolicitudDetalleUrl ?? base_url('index.php/Inicio/getSolicitudUsuarioFicPerfil'));
$ficSolicitudCancelarUrl = (string) ($ficSolicitudCancelarUrl ?? base_url('index.php/Inicio/cancelarSolicitudUsuarioFicPerfil'));
$operativoSolicitudListadoUrl = (string) ($operativoSolicitudListadoUrl ?? base_url('index.php/Inicio/getSolicitudesUsuarioOperativo'));
$operativoSolicitudDetalleUrl = (string) ($operativoSolicitudDetalleUrl ?? base_url('index.php/Inicio/getSolicitudUsuarioOperativo'));
$operativoSolicitudAprobarUrl = (string) ($operativoSolicitudAprobarUrl ?? base_url('index.php/Inicio/aprobarSolicitudUsuarioOperativo'));
$operativoSolicitudRechazarUrl = (string) ($operativoSolicitudRechazarUrl ?? base_url('index.php/Inicio/rechazarSolicitudUsuarioOperativo'));
?>
<style>
    .solicitudes-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .96), rgba(18, 24, 37, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
    }

    .solicitudes-section-title {
        color: #f8fafc;
        margin-bottom: .25rem;
    }

    .solicitudes-section-copy {
        color: #94a3b8;
        margin-bottom: 0;
    }

    .solicitudes-table-wrap {
        overflow-x: auto;
        padding: .25rem .25rem .5rem;
        border-radius: 14px;
    }

    .solicitudes-table {
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: .92rem;
    }

    .solicitudes-table thead th {
        background: linear-gradient(180deg, #4b5563, #404754);
        color: #f8fafc;
        border-color: rgba(255, 255, 255, .08) !important;
        padding: .95rem .85rem;
        white-space: nowrap;
    }

    .solicitudes-table tbody td {
        vertical-align: middle;
        padding: .9rem .85rem;
        border-color: rgba(148, 163, 184, .14) !important;
    }

    .solicitudes-table tbody tr:nth-child(odd) {
        background-color: rgba(255, 255, 255, .03) !important;
    }

    .solicitudes-table tbody tr:nth-child(even) {
        background-color: rgba(15, 23, 42, .58) !important;
    }

    .solicitudes-table tbody tr:hover {
        background-color: rgba(59, 130, 246, .12) !important;
    }

    .solicitudes-table .badge {
        font-size: .72rem;
        padding: .35rem .5rem;
    }

    .solicitudes-table .btn {
        min-width: 42px;
    }

    .solicitudes-table .usuario-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: .25rem;
        white-space: nowrap;
    }

    .bootstrap-table .fixed-table-container {
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 14px;
        overflow: hidden;
        background: rgba(15, 23, 42, .72);
    }

    .bootstrap-table .fixed-table-header {
        background: transparent;
    }

    .bootstrap-table .fixed-table-body {
        border-top: 1px solid rgba(148, 163, 184, .12);
    }

    .bootstrap-table .fixed-table-pagination,
    .bootstrap-table .fixed-table-pagination .pagination-info,
    .bootstrap-table .fixed-table-pagination .page-list {
        color: #cbd5e1;
    }

    .solicitudes-note {
        border: 1px solid rgba(96, 165, 250, .16);
        background: rgba(30, 41, 59, .72);
        color: #cbd5e1;
    }

    .solicitudes-empty {
        border: 1px dashed rgba(148, 163, 184, .28);
        border-radius: 14px;
        padding: 1rem;
        background: rgba(15, 23, 42, .45);
        color: #cbd5e1;
    }

    .solicitudes-modal .modal-content {
        background: #0f172a !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(148, 163, 184, .2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
    }

    .solicitudes-modal .modal-header,
    .solicitudes-modal .modal-footer {
        border-color: rgba(148, 163, 184, .18) !important;
        background: rgba(15, 23, 42, .98);
    }

    .solicitudes-modal .modal-title,
    .solicitudes-modal .form-label,
    .solicitudes-modal .text-muted,
    .solicitudes-modal .small,
    .solicitudes-modal .form-text {
        color: #cbd5e1 !important;
    }

    .solicitudes-modal .form-control,
    .solicitudes-modal .form-select,
    .solicitudes-modal textarea {
        background-color: #111827 !important;
        border-color: rgba(148, 163, 184, .28) !important;
        color: #f8fafc !important;
    }

    .solicitudes-modal .form-control:focus,
    .solicitudes-modal .form-select:focus,
    .solicitudes-modal textarea:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .18) !important;
    }

    .solicitudes-modal .btn-close {
        filter: invert(1) grayscale(100%);
        opacity: .9;
    }

    .solicitudes-summary-card {
        background: rgba(15, 23, 42, .9);
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 14px;
        padding: 1rem;
        min-height: 100%;
    }

    .solicitudes-summary-label {
        display: block;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #94a3b8;
        margin-bottom: .25rem;
    }

    .solicitudes-summary-value {
        color: #f8fafc;
        font-weight: 600;
        word-break: break-word;
    }
</style>

<div
    class="container-fluid py-4"
    id="solicitudesUsuarioFicPanelRoot"
    data-folio-list-url="<?= esc($ficSolicitudListadoUrl, 'attr') ?>"
    data-folio-detail-url="<?= esc($ficSolicitudDetalleUrl, 'attr') ?>"
    data-folio-cancel-url="<?= esc($ficSolicitudCancelarUrl, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h3 class="mb-1 text-white">Bandeja de solicitudes FIC</h3>
            <p class="text-muted mb-0">Separamos la revisión por tipo de trámite para que QR, folios y personal operativo no queden mezclados.</p>
        </div>
        <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <div class="card solicitudes-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="solicitudes-section-title">Solicitudes de activación de QR</h4>
                    <p class="solicitudes-section-copy">Sección reservada para solicitudes documentales de activación QR. En este workspace todavía no existe un endpoint específico para esta bandeja.</p>
                </div>
            </div>
            <div class="alert solicitudes-note mb-3" role="alert">
                Cuando el backend exponga la fuente de activación QR sobre esta BD, esta tabla quedará lista para conectarse sin volver a mezclarla con folios o personal operativo.
            </div>
            <div class="solicitudes-table-wrap">
                <table
                    id="tablaSolicitudesActivacionQrFic"
                    class="table table-dark table-hover align-middle solicitudes-table"
                    data-locale="es-MX"
                    data-search="true"
                    data-search-align="left"
                    data-pagination="true"
                    data-page-size="10"
                    data-page-list="[10, 25, 50, 100]"
                    data-sortable="true"
                    data-classes="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id_usuario" data-sortable="true">ID usuario</th>
                            <th data-field="usuario" data-sortable="true">Usuario</th>
                            <th data-field="nombre_completo" data-sortable="true">Nombre completo</th>
                            <th data-field="correo" data-sortable="true">Correo</th>
                            <th data-field="estatus" data-sortable="true">Estatus</th>
                            <th data-field="fec_reg" data-sortable="true">Fecha de solicitud</th>
                            <th data-field="acciones" data-align="center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="solicitudes-empty mt-3" id="solicitudesQrPlaceholder">
                No hay una tabla o endpoint de solicitudes de activación QR implementado en este workspace. Esta separación ya queda visible en UI para conectar la fuente cuando exista.
            </div>
        </div>
    </div>

    <div class="card solicitudes-card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="solicitudes-section-title">Solicitudes de nuevos folios</h4>
                    <p class="solicitudes-section-copy">Revisión de altas de folio FIC registradas en `solicitud_usuario` con tipo `alta_usuario_fic`.</p>
                </div>
            </div>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="filtroSolicitudFolioFicEstatus">Estatus</label>
                    <select id="filtroSolicitudFolioFicEstatus" class="form-select">
                        <option value="">Todas</option>
                        <option value="pendiente" selected>Pendientes</option>
                        <option value="aprobada">Aprobadas</option>
                        <option value="rechazada">Rechazadas</option>
                        <option value="cancelada">Canceladas</option>
                    </select>
                </div>
                <div class="col-12 col-md-8 col-lg-9">
                    <div class="alert solicitudes-note mb-0" role="alert">La consulta usa el flujo FIC ya existente y solo agrega el filtro por estatus para esta bandeja separada.</div>
                </div>
            </div>
            <div class="solicitudes-table-wrap">
                <table
                    id="tablaSolicitudesFoliosFic"
                    class="table table-dark table-hover align-middle solicitudes-table"
                    data-locale="es-MX"
                    data-search="true"
                    data-search-align="left"
                    data-pagination="true"
                    data-side-pagination="server"
                    data-page-size="10"
                    data-page-list="[10, 25, 50, 100]"
                    data-sortable="true"
                    data-classes="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th data-field="id_solicitud_usuario" data-sortable="true">ID solicitud</th>
                            <th data-field="perfil_solicitado" data-sortable="true">Perfil solicitado</th>
                            <th data-field="usuario" data-sortable="true" data-formatter="solicitudesFicPanelUsuarioFormatter">Usuario</th>
                            <th data-field="nombre_completo" data-sortable="true">Nombre completo</th>
                            <th data-field="correo" data-sortable="true">Correo</th>
                            <th data-field="estatus" data-sortable="true" data-formatter="solicitudesFicPanelEstadoFormatter">Estatus</th>
                            <th data-field="fec_reg" data-sortable="true" data-formatter="solicitudesFicPanelFechaFormatter">Fecha de solicitud</th>
                            <th data-field="acciones" data-align="center" data-formatter="solicitudesFicPanelAccionesFormatter">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div
        class="card solicitudes-card"
        id="solicitudesUsuarioOperativoRoot"
        data-list-url="<?= esc($operativoSolicitudListadoUrl, 'attr') ?>"
        data-detail-url="<?= esc($operativoSolicitudDetalleUrl, 'attr') ?>"
        data-approve-url="<?= esc($operativoSolicitudAprobarUrl, 'attr') ?>"
        data-reject-url="<?= esc($operativoSolicitudRechazarUrl, 'attr') ?>">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="solicitudes-section-title">Solicitudes de gerente y recepción</h4>
                    <p class="solicitudes-section-copy">Bandeja operativa para altas de personal por establecimiento. Aquí se revisan y aprueban usuarios de gerente y recepción.</p>
                </div>
            </div>

            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label" for="filtroSolicitudUsuarioOperativoEstatus">Estatus</label>
                    <select
                        id="filtroSolicitudUsuarioOperativoEstatus"
                        class="form-select">
                        <option value="pendiente" selected>Pendientes</option>
                        <option value="">Todas</option>
                        <option value="aprobada">Aprobadas</option>
                        <option value="rechazada">Rechazadas</option>
                    </select>
                </div>
                <div class="col-12 col-md-8 col-lg-9">
                    <div class="alert solicitudes-note mb-0" role="alert">Cada solicitud muestra el proveedor, establecimiento, tipo de establecimiento y el candidato propuesto para su revisión operativa.</div>
                </div>
            </div>

            <div class="solicitudes-table-wrap">
                <table
                    id="tablaSolicitudesUsuarioOperativo"
                    class="table table-dark table-hover align-middle solicitudes-table"
                    data-locale="es-MX"
                    data-search="true"
                    data-search-align="left"
                    data-pagination="true"
                    data-side-pagination="server"
                    data-page-size="10"
                    data-page-list="[10, 25, 50, 100]"
                    data-sortable="true"
                    data-classes="table table-striped table-hover"
                    data-query-params="queryParamsSolicitudesUsuarioOperativo">
                    <thead>
                        <tr>
                            <th data-field="proveedor_solicitante" data-formatter="formatterSolicitudUsuarioOperativoProveedor">Proveedor solicitante</th>
                            <th data-field="proveedor_razon_social" data-formatter="formatterSolicitudUsuarioOperativoRazonSocial">Razón social / identificador</th>
                            <th data-field="dsc_establecimiento" data-sortable="true">Establecimiento</th>
                            <th data-field="dsc_tipo" data-sortable="true">Tipo establecimiento</th>
                            <th data-field="tipo_usuario_solicitado" data-formatter="formatterSolicitudUsuarioOperativoTipoUsuario">Tipo de usuario solicitado</th>
                            <th data-field="nombre_completo" data-sortable="true">Nombre completo</th>
                            <th data-field="correo" data-sortable="true">Correo</th>
                            <th data-field="fec_reg" data-sortable="true" data-formatter="formatterSolicitudUsuarioOperativoFecha">Fecha de solicitud</th>
                            <th data-field="estatus" data-sortable="true" data-formatter="formatterSolicitudUsuarioOperativoStatus">Estatus</th>
                            <th data-field="acciones" data-formatter="formatterSolicitudUsuarioOperativoAcciones" data-align="center">Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade solicitudes-modal" id="modalRevisionSolicitudUsuarioOperativo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formAprobarSolicitudUsuarioOperativo" autocomplete="off">
                <?= csrf_field() ?>
                <div class="modal-header border-secondary">
                    <div>
                        <h5 class="modal-title" id="solicitudUsuarioOperativoTitulo">Revisar solicitud</h5>
                        <p class="text-muted mb-0 small">Confirma la información y define el usuario operativo.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-none mb-3" id="solicitudUsuarioOperativoAprobarAlert" role="alert"></div>
                    <input type="hidden" name="id_solicitud_usuario" id="solicitud_usuario_id_aprobar" value="0">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <div class="solicitudes-summary-card">
                                <span class="solicitudes-summary-label">Proveedor solicitante</span>
                                <div class="solicitudes-summary-value" id="solicitud_proveedor_aprobar"></div>
                                <div class="solicitudes-summary-label mt-3">Razón social</div>
                                <div class="solicitudes-summary-value" id="solicitud_razon_social_aprobar"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="solicitudes-summary-card">
                                <span class="solicitudes-summary-label">Establecimiento</span>
                                <div class="solicitudes-summary-value" id="solicitud_establecimiento_aprobar"></div>
                                <div class="solicitudes-summary-label mt-3">Tipo de establecimiento</div>
                                <div class="solicitudes-summary-value" id="solicitud_tipo_establecimiento_aprobar"></div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="solicitudes-summary-card">
                                <span class="solicitudes-summary-label">Tipo de usuario</span>
                                <div class="solicitudes-summary-value" id="solicitud_tipo_usuario_aprobar"></div>
                                <div class="solicitudes-summary-label mt-3">Nombre del candidato</div>
                                <div class="solicitudes-summary-value" id="solicitud_nombre_aprobar"></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Primer apellido</label>
                            <input type="text" class="form-control" id="solicitud_primer_apellido_aprobar" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Segundo apellido</label>
                            <input type="text" class="form-control" id="solicitud_segundo_apellido_aprobar" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" id="solicitud_correo_aprobar" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="solicitud_usuario_operativo">Usuario</label>
                            <input type="text" class="form-control text-lowercase-live" id="solicitud_usuario_operativo" name="usuario" required autocomplete="off" placeholder="usuario">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="solicitud_contrasenia_aprobar">Contraseña</label>
                            <input type="password" class="form-control text-lowercase-live" id="solicitud_contrasenia_aprobar" name="contrasenia" required autocomplete="new-password" placeholder="******">
                            <div class="form-text">Captura el usuario y la contraseña para completar el alta.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-outline-danger" id="btnAbrirRechazoSolicitudUsuarioOperativo">Rechazar</button>
                    <button type="submit" class="btn btn-primary" id="btnConfirmarAprobarSolicitudUsuarioOperativo">Aprobar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade solicitudes-modal" id="modalRechazoSolicitudUsuarioOperativo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formRechazarSolicitudUsuarioOperativo" autocomplete="off">
                <?= csrf_field() ?>
                <div class="modal-header border-secondary">
                    <div>
                        <h5 class="modal-title" id="solicitudUsuarioOperativoRechazoTitulo">Rechazar solicitud</h5>
                        <p class="text-muted mb-0 small">Captura el motivo para conservar el histórico de la solicitud.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_solicitud_usuario" id="solicitud_usuario_id_rechazar" value="0">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Proveedor solicitante</label>
                            <input type="text" class="form-control" id="solicitud_proveedor_rechazo" readonly>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Establecimiento</label>
                            <input type="text" class="form-control" id="solicitud_establecimiento_rechazo" readonly>
                        </div>
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Tipo de usuario</label>
                            <input type="text" class="form-control" id="solicitud_tipo_usuario_rechazo" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre del candidato</label>
                            <input type="text" class="form-control" id="solicitud_nombre_rechazo" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" id="solicitud_correo_rechazo" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="solicitud_motivo_rechazo">Motivo de rechazo</label>
                            <textarea class="form-control" id="solicitud_motivo_rechazo" name="comentario_ti" rows="4" required placeholder="Escribe el motivo del rechazo"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmarRechazoSolicitudUsuarioOperativo">Rechazar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

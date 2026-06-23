<?php
$session = \Config\Services::session();
$contextoUsuario = $contextoUsuario ?? [];
$proveedorPerfil = is_object($proveedorPerfil ?? null) ? get_object_vars($proveedorPerfil) : (is_array($proveedorPerfil ?? null) ? $proveedorPerfil : []);
$proveedorEstablecimientos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($proveedorEstablecimientos ?? null) ? $proveedorEstablecimientos : []));
$proveedorPagos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($proveedorPagos ?? null) ? $proveedorPagos : []));
$ventasCorteContexto = is_object($ventasCorteContexto ?? null) ? get_object_vars($ventasCorteContexto) : (is_array($ventasCorteContexto ?? null) ? $ventasCorteContexto : []);

$pagosTotales = count($proveedorPagos);
$pagosPendientes = 0;
$pagosAprobados = 0;
$pagosRechazados = 0;
$pagosRecientes = $proveedorPagos;
usort($pagosRecientes, static function (array $a, array $b): int {
    return strcmp((string) ($b['fec_reg'] ?? ''), (string) ($a['fec_reg'] ?? ''));
});
$pagosRecientes = array_slice($pagosRecientes, 0, 6);

foreach ($proveedorPagos as $pagoItem) {
    $estatus = strtolower(trim((string) ($pagoItem['estatus'] ?? '')));
    if (in_array($estatus, ['pendiente', 'solicitado', 'en_revision'], true)) {
        $pagosPendientes++;
    } elseif (in_array($estatus, ['aprobada', 'autorizada', 'pagada', 'finalizada'], true)) {
        $pagosAprobados++;
    } elseif (in_array($estatus, ['rechazada', 'cancelada'], true)) {
        $pagosRechazados++;
    }
}

$formatMoney = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};

$estatusBadge = static function (string $estatus): string {
    $valor = strtolower(trim($estatus));
    if (in_array($valor, ['pendiente', 'solicitado', 'en_revision'], true)) {
        return '<span class="badge bg-warning text-dark">Pendiente</span>';
    }
    if (in_array($valor, ['aprobada', 'autorizada', 'pagada', 'finalizada'], true)) {
        return '<span class="badge bg-success">Aprobada</span>';
    }
    if (in_array($valor, ['rechazada', 'cancelada'], true)) {
        return '<span class="badge bg-danger">Rechazada</span>';
    }
    return '<span class="badge bg-secondary">' . esc($estatus) . '</span>';
};
?>
<style>
    .provider-page {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, .08), transparent 24%),
            linear-gradient(180deg, #0f172a, #111827);
        min-height: calc(100vh - 70px);
        color: #f8fafc;
        padding: 28px;
    }

    .provider-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .96), rgba(18, 24, 37, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(2, 6, 23, .25);
    }

    .provider-kpi {
        min-height: 116px;
    }

    .provider-table {
        min-width: 960px;
    }

    .provider-table-wrap {
        overflow-x: auto;
    }

    .provider-history-card {
        background: rgba(15, 23, 42, .94);
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 16px;
        padding: 16px;
    }

    .provider-history-item {
        border: 1px solid rgba(148, 163, 184, .12);
        background: rgba(30, 41, 59, .9);
        border-radius: 14px;
        padding: 14px 16px;
    }

    .provider-history-item + .provider-history-item {
        margin-top: 12px;
    }

    .provider-history-title {
        font-weight: 700;
        color: #fff;
        margin-bottom: .15rem;
    }

    .provider-history-meta {
        color: #cbd5e1;
        font-size: .88rem;
    }

    .provider-history-amount {
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
        white-space: nowrap;
    }

    .provider-history-empty {
        padding: 18px;
        text-align: center;
        color: #cbd5e1;
        background: rgba(30, 41, 59, .55);
        border-radius: 14px;
        border: 1px dashed rgba(148, 163, 184, .2);
    }

    .provider-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .75rem;
        border-radius: 999px;
        background: rgba(59, 130, 246, .12);
        color: #bfdbfe;
        font-size: .8rem;
        font-weight: 700;
    }

    .provider-modal .modal-content {
        background: #0f172a !important;
        color: #e2e8f0 !important;
        border: 1px solid rgba(148, 163, 184, .2);
        box-shadow: 0 20px 60px rgba(0, 0, 0, .45);
    }

    .provider-modal .modal-header,
    .provider-modal .modal-footer {
        border-color: rgba(148, 163, 184, .18) !important;
        background: rgba(15, 23, 42, .98);
    }

    .provider-modal .modal-title,
    .provider-modal .form-label,
    .provider-modal .text-muted {
        color: #cbd5e1 !important;
    }

    .provider-modal .form-control,
    .provider-modal .form-select {
        background-color: #111827 !important;
        border-color: rgba(148, 163, 184, .28) !important;
        color: #f8fafc !important;
    }

    .provider-modal .form-control::placeholder,
    .provider-modal .form-select::placeholder {
        color: #94a3b8 !important;
    }

    .provider-modal .form-control:focus,
    .provider-modal .form-select:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .18) !important;
    }

    .provider-modal .btn-close {
        filter: invert(1) grayscale(100%);
        opacity: .9;
    }
</style>

<div class="container-fluid provider-page" id="proveedorPage" data-provider-mode="1" data-establecimientos-url="<?= esc(base_url('index.php/Inicio/getEstablecimientosProveedor'), 'attr') ?>" data-solicitud-url="<?= esc(base_url('index.php/Inicio/guardarSolicitudUsuarioProveedor'), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h3 class="mb-1 text-white">Perfil proveedor</h3>
            <p class="text-muted mb-0">Consulta tus establecimientos, pagos y solicita personal para tu negocio.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#modalSolicitudPersonal">
                <i class="mdi mdi-account-plus me-1"></i> Solicitar personal
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card provider-card provider-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Proveedor</div>
                    <h5 class="text-white mb-1"><?= esc((string) ($proveedorPerfil['razon_social'] ?? $proveedorPerfil['nombre'] ?? 'Sin proveedor')) ?></h5>
                    <div class="text-muted small">RFC: <?= esc((string) ($proveedorPerfil['rfc'] ?? '')) ?></div>
                    <div class="text-muted small">No. proveedor: <?= esc((string) ($proveedorPerfil['no_proveedor'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card provider-card provider-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Establecimientos</div>
                    <h2 class="text-white mb-0"><?= count($proveedorEstablecimientos) ?></h2>
                    <div class="text-muted small">Vinculados al proveedor</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card provider-card provider-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Pagos / cortes</div>
                    <h2 class="text-white mb-0"><?= $pagosTotales ?></h2>
                    <div class="text-muted small">Con corte desde <?= esc((string) ($ventasCorteContexto['fecha_corte_desde'] ?? '')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card provider-card provider-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Monto total</div>
                    <h2 class="text-white mb-0"><?= $formatMoney($ventasCorteContexto['monto_total'] ?? 0) ?></h2>
                    <div class="text-muted small">Estado: <?= esc((string) ($ventasCorteContexto['estado_corte'] ?? 'Sin movimientos')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-7">
            <div class="card provider-card h-100">
                <div class="card-header border-0 bg-transparent pt-3 pb-0">
                    <h5 class="text-white mb-0">Mis establecimientos</h5>
                </div>
                <div class="card-body provider-table-wrap">
                    <?php if (!empty($proveedorEstablecimientos)): ?>
                        <table class="table table-dark table-hover align-middle provider-table mb-0">
                            <thead>
                                <tr>
                                    <th>Establecimiento</th>
                                    <th>Tipo</th>
                                    <th>No. proveedor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proveedorEstablecimientos as $establecimiento): ?>
                                    <tr>
                                        <td><?= esc((string) ($establecimiento['dsc_establecimiento'] ?? '')) ?></td>
                                        <td><?= esc((string) ($establecimiento['dsc_tipo'] ?? '')) ?></td>
                                        <td><?= esc((string) ($establecimiento['no_proveedor'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-muted">No hay establecimientos ligados a este proveedor.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="provider-history-card h-100">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <span class="provider-summary-pill">Historial de pagos</span>
                        <h5 class="text-white mt-2 mb-1">Cortes y pagos</h5>
                        <p class="text-muted mb-0">Revisa el comportamiento reciente de pagos, pendientes y cortes globales.</p>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="card provider-card provider-kpi h-100">
                            <div class="card-body">
                                <div class="text-uppercase text-muted small mb-2">Pendientes</div>
                                <h3 class="text-white mb-0"><?= (int) $pagosPendientes ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card provider-card provider-kpi h-100">
                            <div class="card-body">
                                <div class="text-uppercase text-muted small mb-2">Aprobados</div>
                                <h3 class="text-white mb-0"><?= (int) $pagosAprobados ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card provider-card provider-kpi h-100">
                            <div class="card-body">
                                <div class="text-uppercase text-muted small mb-2">Rechazados</div>
                                <h3 class="text-white mb-0"><?= (int) $pagosRechazados ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($proveedorPagos)): ?>
                    <div class="provider-history-list mb-3">
                        <?php foreach ($pagosRecientes as $pago): ?>
                            <div class="provider-history-item mb-2">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div>
                                        <div class="provider-history-title"><?= esc((string) ($pago['folio_solicitud'] ?? 'Sin folio')) ?></div>
                                        <div class="provider-history-meta">
                                            <?= esc((string) ($pago['dsc_establecimiento'] ?? 'Sin establecimiento')) ?>
                                            · <?= esc((string) ($pago['dsc_tipo'] ?? 'Sin tipo')) ?>
                                        </div>
                                        <div class="provider-history-meta mt-1">
                                            <?= esc((string) ($pago['fec_reg'] ?? '')) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="provider-history-amount"><?= $formatMoney($pago['monto_solicitado'] ?? 0) ?></div>
                                        <div class="mt-2"><?= $estatusBadge((string) ($pago['estatus'] ?? '')) ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($pago['observaciones'])): ?>
                                    <div class="provider-history-meta mt-2"><?= esc((string) $pago['observaciones']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="provider-history-empty mb-3">Aún no hay pagos o solicitudes registradas.</div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Importe</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($proveedorPagos)): ?>
                                <?php foreach (array_slice($proveedorPagos, 0, 4) as $pago): ?>
                                    <tr>
                                        <td><?= esc((string) ($pago['folio_solicitud'] ?? '')) ?></td>
                                        <td><?= $formatMoney($pago['monto_solicitado'] ?? 0) ?></td>
                                        <td><?= $estatusBadge((string) ($pago['estatus'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Sin movimientos para mostrar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade provider-modal" id="modalSolicitudPersonal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <form id="formSolicitudProveedor">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Solicitud de personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Selecciona el establecimiento y completa los datos del usuario solicitado. El perfil se resolverá automáticamente desde el tipo de establecimiento.</p>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Establecimiento</label>
                            <select id="solicitud_establecimiento" name="id_establecimiento" class="form-select js-select2-catalog" data-placeholder="Selecciona un establecimiento" required></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de usuario</label>
                            <input type="text" id="solicitud_tipo_usuario" class="form-control crud-ui-upper" readonly placeholder="Se resolverá automáticamente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre</label>
                            <input type="text" id="solicitud_nombre" name="nombre" class="form-control crud-ui-upper" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Primer apellido</label>
                            <input type="text" id="solicitud_primer_apellido" name="primer_apellido" class="form-control crud-ui-upper" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Segundo apellido</label>
                            <input type="text" id="solicitud_segundo_apellido" name="segundo_apellido" class="form-control crud-ui-upper">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Correo</label>
                            <input type="email" id="solicitud_correo" name="correo" class="form-control crud-ui-lower" placeholder="correo@dominio.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" id="btnEnviarSolicitudProveedor">Enviar solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>

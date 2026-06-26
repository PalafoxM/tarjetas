<?php
$dashboard = is_array($pagosFicDashboard ?? null) ? $pagosFicDashboard : (is_object($pagosFicDashboard ?? null) ? get_object_vars($pagosFicDashboard) : []);
$resumen = is_array($dashboard['resumen'] ?? null) ? $dashboard['resumen'] : (is_object($dashboard['resumen'] ?? null) ? get_object_vars($dashboard['resumen']) : []);
$pagos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($dashboard['pagos'] ?? null) ? $dashboard['pagos'] : []));

$money = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};

$statusBadge = static function (string $estatus): string {
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

$formatDate = static function (?string $value): string {
    $value = trim((string) $value);
    return $value !== '' ? $value : 'Sin fecha';
};
?>
<style>
    .pagos-fic-page {
        min-height: calc(100vh - 70px);
        padding: 30px 28px 42px;
        background:
            radial-gradient(circle at 82% 8%, rgba(168, 85, 247, .1), transparent 24%),
            linear-gradient(180deg, #0f172a, #111827);
        color: #f8fafc;
    }

    .pagos-fic-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .97), rgba(17, 24, 39, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(2, 6, 23, .22);
    }

    .pagos-fic-kpi {
        min-height: 116px;
    }

    .pagos-fic-table {
        min-width: 1180px;
    }

    .pagos-fic-table-wrap {
        overflow-x: auto;
    }

    .pagos-fic-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .75rem;
        border-radius: 999px;
        background: rgba(168, 85, 247, .14);
        color: #e9d5ff;
        font-size: .8rem;
        font-weight: 700;
    }

    .pagos-fic-empty {
        padding: 22px;
        text-align: center;
        color: #cbd5e1;
        background: rgba(30, 41, 59, .55);
        border-radius: 14px;
        border: 1px dashed rgba(148, 163, 184, .18);
    }
</style>

<div class="container-fluid pagos-fic-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="mb-1 text-white">Pagos FIC</h3>
            <p class="text-muted mb-0">Consulta el historial global de pagos y movimientos. La vista de referencia muestra el tablero completo; proveedor solo ve los de sus establecimientos desde su propio tablero.</p>
        </div>
        <a class="btn btn-outline-light" href="<?= base_url('index.php/Inicio') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-3">
            <div class="card pagos-fic-card pagos-fic-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Pagos registrados</div>
                    <h2 class="text-white mb-0"><?= (int) ($resumen['total_registros'] ?? 0) ?></h2>
                    <div class="text-muted small">Movimientos visibles en la bandeja</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card pagos-fic-card pagos-fic-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Monto total</div>
                    <h2 class="text-white mb-0"><?= $money($resumen['monto_total'] ?? 0) ?></h2>
                    <div class="text-muted small">Suma global de solicitudes</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card pagos-fic-card pagos-fic-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Pendientes</div>
                    <h2 class="text-white mb-0"><?= (int) ($resumen['pendientes'] ?? 0) ?></h2>
                    <div class="text-muted small"><?= $money($resumen['monto_pendiente'] ?? 0) ?> en espera</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card pagos-fic-card pagos-fic-kpi h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small mb-2">Aprobadas / rechazadas</div>
                    <h2 class="text-white mb-0"><?= (int) ($resumen['aprobados'] ?? 0) ?> / <?= (int) ($resumen['rechazados'] ?? 0) ?></h2>
                    <div class="text-muted small">Estado del corte: <?= esc((string) ($resumen['estado_corte'] ?? 'Sin movimientos')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card pagos-fic-card">
        <div class="card-header border-0 bg-transparent pt-3 pb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <span class="pagos-fic-pill">Historial global</span>
                    <h5 class="text-white mt-2 mb-1">Bandeja de pagos</h5>
                    <p class="text-muted mb-0">Rango de corte desde <?= esc($formatDate((string) ($resumen['fecha_corte_desde'] ?? ''))) ?> hasta <?= esc($formatDate((string) ($resumen['fecha_corte_hasta'] ?? ''))) ?>.</p>
                </div>
            </div>
        </div>
        <div class="card-body pagos-fic-table-wrap">
            <?php if (!empty($pagos)): ?>
                <table class="table table-dark table-hover align-middle pagos-fic-table mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Proveedor</th>
                            <th>Establecimiento</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Estatus</th>
                            <th>Fecha registro</th>
                            <th>Fecha respuesta</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><?= esc((string) ($pago['folio_solicitud'] ?? 'Sin folio')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($pago['razon_social'] ?? 'Sin proveedor')) ?></div>
                                    <div class="text-muted small">No. <?= esc((string) ($pago['no_proveedor'] ?? '')) ?><?= !empty($pago['usuario_solicitante']) ? ' · ' . esc((string) $pago['usuario_solicitante']) : '' ?></div>
                                </td>
                                <td><?= esc((string) ($pago['dsc_establecimiento'] ?? 'Sin establecimiento')) ?></td>
                                <td><?= esc((string) ($pago['dsc_tipo'] ?? 'Sin tipo')) ?></td>
                                <td><?= $money($pago['monto_solicitado'] ?? 0) ?></td>
                                <td><?= $statusBadge((string) ($pago['estatus'] ?? '')) ?></td>
                                <td><?= esc($formatDate((string) ($pago['fec_reg'] ?? ''))) ?></td>
                                <td><?= esc($formatDate((string) ($pago['fecha_respuesta'] ?? ''))) ?></td>
                                <td class="text-wrap" style="min-width: 220px; max-width: 320px;">
                                    <?= esc((string) ($pago['observaciones'] ?? '')) ?>
                                    <?php if (empty($pago['observaciones']) && !empty($pago['motivo_rechazo'])): ?>
                                        <?= esc((string) $pago['motivo_rechazo']) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="pagos-fic-empty">No hay pagos visibles para mostrar.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
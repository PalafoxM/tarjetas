<?php
$dashboard = is_array($pagosFicDashboard ?? null) ? $pagosFicDashboard : (is_object($pagosFicDashboard ?? null) ? get_object_vars($pagosFicDashboard) : []);
$resumen = is_array($dashboard['resumen'] ?? null) ? $dashboard['resumen'] : (is_object($dashboard['resumen'] ?? null) ? get_object_vars($dashboard['resumen']) : []);
$pagos = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($dashboard['pagos'] ?? null) ? $dashboard['pagos'] : []));
$establecimientosBandeja = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($establecimientosBandeja ?? null) ? $establecimientosBandeja : []));

$money = static function ($value): string {
    return '$' . number_format((float) $value, 2);
};

$statusBadge = static function (string $estatus): string {
    $valor = strtolower(trim($estatus));
    if (in_array($valor, ['pendiente', 'solicitado', 'en_revision'], true)) {
        return '<span class="badge bg-warning text-dark">Pendiente</span>';
    }
    if (in_array($valor, ['aprobada', 'aprobado', 'aceptada', 'aceptado', 'aceptados', 'autorizada', 'autorizado', 'pagada', 'pagado', 'finalizada', 'finalizado'], true)) {
        return '<span class="badge bg-success">Aprobada </span>';
    }
    if (in_array($valor, ['rechazada', 'rechazado', 'rechazados', 'cancelada', 'cancelado'], true)) {
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

    .pagos-fic-establecimientos-table {
        min-width: 760px;
    }

    .pagos-fic-table-wrap {
        overflow-x: auto;
    }

    .pagos-fic-card .bootstrap-table .fixed-table-toolbar {
        margin-bottom: 1rem;
    }

    .pagos-fic-card .bootstrap-table .fixed-table-toolbar .search {
        width: min(100%, 360px);
    }

    .pagos-fic-card .bootstrap-table .fixed-table-toolbar .search input {
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .34);
        background: rgba(15, 23, 42, .92);
        color: #f8fafc;
    }

    .pagos-fic-card .bootstrap-table .fixed-table-toolbar .search input::placeholder {
        color: #94a3b8;
    }

    .pagos-fic-card .fixed-table-pagination {
        color: #cbd5e1;
        padding-top: 1rem;
    }

    .pagos-fic-card .fixed-table-pagination .btn,
    .pagos-fic-card .fixed-table-pagination .dropdown-menu {
        background: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #f8fafc;
    }

    .pagos-fic-card .fixed-table-pagination .dropdown-item {
        color: #e2e8f0;
    }

    .pagos-fic-card .fixed-table-pagination .dropdown-item:hover,
    .pagos-fic-card .fixed-table-pagination .dropdown-item:focus {
        background: rgba(59, 130, 246, .22);
        color: #ffffff;
    }

    .pagos-fic-card .fixed-table-pagination .page-link {
        background: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #e2e8f0;
    }

    .pagos-fic-card .fixed-table-pagination .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
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

    .pagos-fic-actions-slot {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        min-height: 38px;
        align-items: center;
    }

    .pagos-fic-action-btn {
        min-width: 136px;
        min-height: 36px;
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 10px 24px rgba(2, 6, 23, .14);
    }

    .pagos-fic-action-btn--pdf {
        background: linear-gradient(180deg, #fbbf24, #f59e0b);
        border-color: #f59e0b;
        color: #111827;
    }

    .pagos-fic-action-btn--pdf:hover,
    .pagos-fic-action-btn--pdf:focus {
        background: linear-gradient(180deg, #fcd34d, #f59e0b);
        border-color: #fbbf24;
        color: #111827;
    }

    .pagos-fic-action-btn--format {
        background: rgba(30, 41, 59, .72);
        border: 1px dashed rgba(96, 165, 250, .45);
        color: #cbd5e1;
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
        <!--<div class="card-header border-0 bg-transparent pt-3 pb-0">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <span class="pagos-fic-pill">Historial global</span>
                    <h5 class="text-white mt-2 mb-1">Bandeja de pagos</h5>
                    <p class="text-muted mb-0">Rango de corte desde <?= esc($formatDate((string) ($resumen['fecha_corte_desde'] ?? ''))) ?> hasta <?= esc($formatDate((string) ($resumen['fecha_corte_hasta'] ?? ''))) ?>.</p>
                </div> 
            </div>
        </div>-->
        <div class="card-body pt-3">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <span class="pagos-fic-pill">Establecimientos</span>
                    <h5 class="text-white mt-2 mb-1">Bandeja de pagos de establecimientos</h5>
                    <p class="text-muted mb-0">Listado de establecimientos visibles para preparar acciones futuras en esta bandeja.</p>
                    <p class="text-muted mb-0">Rango de corte desde <?= esc($formatDate((string) ($resumen['fecha_corte_desde'] ?? ''))) ?> hasta <?= esc($formatDate((string) ($resumen['fecha_corte_hasta'] ?? ''))) ?>.</p>
                </div>
            </div>

            <?php if (!empty($establecimientosBandeja)): ?>
                <div class="pagos-fic-table-wrap mb-4">
                    <table
                        id="tabla-establecimientos-bandeja"
                        class="table table-dark table-hover align-middle pagos-fic-establecimientos-table mb-0"
                        data-toggle="table"
                        data-search="true"
                        data-search-highlight="true"
                        data-pagination="true"
                        data-page-size="10"
                        data-page-list="[10, 25, 50, 100, All]"
                        data-locale="es-MX"
                        data-pagination-pre-text="Anterior"
                        data-pagination-next-text="Siguiente"
                        data-search-align="left">
                        <thead>
                            <tr>
                                <th data-sortable="true">Establecimiento</th>
                                <th data-sortable="true">No. proveedor</th>
                                <th data-sortable="false">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($establecimientosBandeja as $establecimiento): ?>
                                <?php
                                $reporteUrl = trim((string) ($establecimiento['reporte_url'] ?? ''));
                                $xmlUrl = trim((string) ($establecimiento['xml_url'] ?? ''));
                                $pdfUrl = trim((string) ($establecimiento['pdf_url'] ?? ''));
                                $facturaId = (int) ($establecimiento['factura_id'] ?? 0);
                                $tieneXml = !empty($establecimiento['tiene_xml']);
                                $puedeVerFormatos = $tieneXml && $facturaId > 0;
                                $encabezadoUrl = $puedeVerFormatos ? base_url('index.php/Inicio/pdfProveedorEncabezadoFactura/' . (int) $establecimiento['id_establecimiento']) : '';
                                $formatoPtUrl = $puedeVerFormatos ? base_url('index.php/Inicio/pdfPagoTerceros?id_factura=' . $facturaId) : '';
                                $liberacionUrl = $puedeVerFormatos ? base_url('index.php/Inicio/pdfLiberacionPago?id_factura=' . $facturaId) : '';
                                $liberacionProveedorUrl = $puedeVerFormatos ? base_url('index.php/Inicio/pdfProveedorLiberacionPago/' . (int) $establecimiento['id_establecimiento']) : '';
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?= esc((string) ($establecimiento['establecimiento'] ?? 'Sin establecimiento')) ?></td>
                                    <td><?= esc((string) ($establecimiento['no_proveedor'] ?? '')) ?></td>
                                    <td>
                                        <div class="pagos-fic-actions-slot">
                                            <?php if ($reporteUrl !== ''): ?>
                                                <a class="btn btn-sm btn-outline-info pagos-fic-action-btn" target="_blank" rel="noopener" href="<?= esc($reporteUrl, 'attr') ?>">
                                                    <i class="mdi mdi-file-chart-outline me-1"></i> Visualizar reporte
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-info pagos-fic-action-btn" disabled>
                                                    <i class="mdi mdi-file-chart-outline me-1"></i> Visualizar reporte
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($pdfUrl !== ''): ?>
                                                <a class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--pdf" target="_blank" rel="noopener" href="<?= esc($pdfUrl, 'attr') ?>">
                                                    <i class="mdi mdi-file-pdf-box me-1"></i> Visualizar PDF
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--pdf" disabled>
                                                    <i class="mdi mdi-file-pdf-box me-1"></i> Visualizar PDF
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($xmlUrl !== ''): ?>
                                                <a class="btn btn-sm btn-outline-warning pagos-fic-action-btn" target="_blank" rel="noopener" href="<?= esc($xmlUrl, 'attr') ?>">
                                                    <i class="mdi mdi-file-xml-box me-1"></i> Visualizar XML
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning pagos-fic-action-btn" disabled>
                                                    <i class="mdi mdi-file-xml-box me-1"></i> Visualizar XML
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($puedeVerFormatos): ?>
                                                <a class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" target="_blank" rel="noopener" href="<?= esc($formatoPtUrl, 'attr') ?>">
                                                    <i class="mdi mdi-file-document-outline me-1"></i> Formato PT
                                                </a>
                                                <a class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" target="_blank" rel="noopener" href="<?= esc($encabezadoUrl, 'attr') ?>">
                                                    <i class="mdi mdi-file-sign me-1"></i> Encabezado factura
                                                </a>
                                                <a class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" target="_blank" rel="noopener" href="<?= esc($liberacionUrl, 'attr') ?>">
                                                    <i class="mdi mdi-receipt-text-outline me-1"></i> Liberación pago
                                                </a>
                                                <a class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" target="_blank" rel="noopener" href="<?= esc($liberacionProveedorUrl, 'attr') ?>">
                                                    <i class="mdi mdi-receipt-outline me-1"></i> Liberación pago proveedor
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" disabled>
                                                    <i class="mdi mdi-file-document-outline me-1"></i> Formato PT
                                                </button>
                                                <button type="button" class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" disabled>
                                                    <i class="mdi mdi-file-sign me-1"></i> Encabezado factura
                                                </button>
                                                <button type="button" class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" disabled>
                                                    <i class="mdi mdi-receipt-text-outline me-1"></i> Liberación pago
                                                </button>
                                                <button type="button" class="btn btn-sm pagos-fic-action-btn pagos-fic-action-btn--format" disabled>
                                                    <i class="mdi mdi-receipt-outline me-1"></i> Liberación pago proveedor
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="pagos-fic-empty mb-4">No hay establecimientos visibles para mostrar.</div>
            <?php endif; ?>
        </div>
        <!--<div class="card-body pagos-fic-table-wrap">
            <?php if (!empty($pagos)): ?>
                <table
                    id="tabla-pagos-fic"
                    class="table table-dark table-hover align-middle pagos-fic-table mb-0"
                    data-toggle="table"
                    data-search="true"
                    data-search-highlight="true"
                    data-pagination="true"
                    data-page-size="10"
                    data-page-list="[10, 25, 50, 100, All]"
                    data-locale="es-MX"
                    data-pagination-pre-text="Anterior"
                    data-pagination-next-text="Siguiente"
                    data-search-align="left">
                    <thead>
                        <tr>
                            <th data-sortable="true">Folio</th>
                            <th data-sortable="true">Usuario</th>
                            <th data-sortable="true">Establecimiento</th>
                            <th data-sortable="true">Tipo</th>
                            <th data-sortable="true">Monto</th>
                            <th data-sortable="true">Estatus</th>
                            <th data-sortable="true">Fecha registro</th>
                            <th data-sortable="true">Fecha respuesta</th>
                            <th data-sortable="true">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td><?= esc((string) ($pago['folio_solicitud'] ?? 'Sin folio')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($pago['razon_social'] ?? 'Sin proveedor')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($pago['no_proveedor'] ?? '')) ?><?= !empty($pago['usuario_solicitante']) ? '  · ' . esc((string) $pago['usuario_solicitante']) : '' ?></div>
                                </td>
                                <td><?= esc((string) ($pago['dsc_establecimiento'] ?? 'Sin establecimiento')) ?></td>
                                <td><?= esc((string) ($pago['metodo_autorizacion'] ?? 'Sin tipo')) ?></td>
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
        </div>-->
    </div>
</div>

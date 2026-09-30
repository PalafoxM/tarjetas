<?php
$establecimiento = is_object($establecimientoConsumos ?? null)
    ? get_object_vars($establecimientoConsumos)
    : (array) ($establecimientoConsumos ?? []);

$pagos = array_values(array_map(static function ($pago): array {
    return is_object($pago) ? get_object_vars($pago) : (array) $pago;
}, is_array($pagosConsumos ?? null) ? $pagosConsumos : []));

$resumen = array_reduce($pagos, static function (array $acumulado, array $pago): array {
    $acumulado['monto'] += (float) ($pago['monto'] ?? 0);
    $acumulado['propina'] += (float) ($pago['propina'] ?? 0);
    $acumulado['total'] += (float) ($pago['total'] ?? 0);
    return $acumulado;
}, ['monto' => 0.0, 'propina' => 0.0, 'total' => 0.0]);

$moneda = static function ($valor): string {
    return '$' . number_format((float) $valor, 2, '.', ',');
};

$fecha = static function ($valor): string {
    $timestamp = strtotime((string) $valor);
    return $timestamp ? date('d/m/Y H:i:s', $timestamp) : 'Sin fecha';
};

$idEstablecimiento = (int) ($establecimiento['id_establecimiento'] ?? 0);
$montoHojaAzul = number_format((float) ($resumen['total'] ?? 0), 2, '.', '');
$hojaAzulUrl = base_url('index.php/Usuario/HojaAzul/' . $idEstablecimiento)
    . '?' . http_build_query(['monto' => $montoHojaAzul]);
$hojaLiberacionUrl = base_url('index.php/Usuario/HojaLiberacion/' . $idEstablecimiento)
    . '?' . http_build_query(['monto' => $montoHojaAzul]);
?>

<style>
    .consumos-detail-page {
        min-height: calc(100vh - 70px);
        padding: 30px 28px 42px;
        background: #111b2a;
        color: #f8fafc;
    }

    .consumos-detail-summary,
    .consumos-detail-table {
        background: #182436;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 8px;
        box-shadow: 0 16px 36px rgba(2, 6, 23, .18);
    }

    .consumos-detail-summary {
        min-height: 104px;
    }

    .consumos-detail-label {
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .consumos-detail-value {
        margin: .35rem 0 0;
        color: #ffffff;
        font-size: 1.55rem;
        font-weight: 700;
    }

    .consumos-detail-table .bootstrap-table .fixed-table-toolbar .search {
        width: min(100%, 360px);
    }

    .consumos-detail-table .bootstrap-table .fixed-table-toolbar .search input {
        min-height: 42px;
        border: 1px solid rgba(148, 163, 184, .34);
        border-radius: 6px;
        background: #0f172a;
        color: #f8fafc;
    }

    .consumos-detail-empty {
        padding: 28px;
        border: 1px dashed rgba(148, 163, 184, .3);
        border-radius: 8px;
        color: #cbd5e1;
        text-align: center;
    }

    @media (max-width: 767px) {
        .consumos-detail-page {
            padding: 22px 14px 32px;
        }
    }
</style>

<div class="container-fluid consumos-detail-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="text-info small fw-bold text-uppercase mb-1">Desglose de consumos</div>
            <h3 class="mb-1 text-white">
                <?= esc((string) ($establecimiento['dsc_establecimiento'] ?? 'Restaurante')) ?>
            </h3>
            <?php if (!empty($establecimiento['no_proveedor'])): ?>
                <p class="text-muted mb-0">No. de proveedor: <?= esc((string) $establecimiento['no_proveedor']) ?></p>
            <?php endif; ?>
        </div>
        <a class="btn btn-outline-light" href="<?= esc(base_url('index.php/Inicio/Consumos'), 'attr') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a consumos
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="consumos-detail-summary h-100 p-3">
                <div class="consumos-detail-label">Pagos realizados</div>
                <p class="consumos-detail-value"><?= count($pagos) ?></p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="consumos-detail-summary h-100 p-3">
                <div class="consumos-detail-label">Consumo</div>
                <p class="consumos-detail-value"><?= esc($moneda($resumen['monto'])) ?></p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="consumos-detail-summary h-100 p-3">
                <div class="consumos-detail-label">Propinas</div>
                <p class="consumos-detail-value"><?= esc($moneda($resumen['propina'])) ?></p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="consumos-detail-summary h-100 p-3">
                <div class="consumos-detail-label">Total pagado</div>
                <p class="consumos-detail-value"><?= esc($moneda($resumen['total'])) ?></p>
            </div>
        </div>
    </div>

    <a
        target="_blank"
        rel="noopener"
        class="btn btn-sm btn-outline-warning mb-3"
        href="<?= esc($hojaAzulUrl, 'attr') ?>"
        title="Generar formato PT">
        <i class="mdi mdi-receipt-text-outline me-1"></i> Hoja Azul PT
    </a>
    <a
        target="_blank"
        rel="noopener"
        class="btn btn-sm btn-outline-success mb-3"
        href="<?= esc($hojaLiberacionUrl, 'attr') ?>"
        title="Generar formato de liberación">
        <i class="mdi mdi-receipt-text-outline me-1"></i> Hoja de Liberación
    </a>

    <div class="consumos-detail-table p-3 p-lg-4">
        <?php if (!empty($pagos)): ?>
            <div class="table-responsive">
                <table
                    id="consumosDetalleTable"
                    class="table table-dark table-hover align-middle mb-0"
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
                            <th data-sortable="true">Pago</th>
                            <th data-sortable="true">Folio</th>
                            <th data-sortable="true">Cliente</th>
                            <th data-sortable="true" data-align="right">Consumo</th>
                            <th data-sortable="true" data-align="right">Propina</th>
                            <th data-sortable="true" data-align="right">Total</th>
                            <th data-sortable="true">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pago): ?>
                            <tr>
                                <td>#<?= (int) ($pago['id_pago'] ?? 0) ?></td>
                                <td><?= esc((string) ($pago['folio_solicitud'] ?? $pago['id_solicitud_pago'] ?? 'Sin folio')) ?></td>
                                <td><?= esc(trim((string) ($pago['cliente'] ?? '')) ?: 'Sin nombre') ?></td>
                                <td><?= esc($moneda($pago['monto'] ?? 0)) ?></td>
                                <td><?= esc($moneda($pago['propina'] ?? 0)) ?></td>
                                <td class="fw-bold"><?= esc($moneda($pago['total'] ?? 0)) ?></td>
                                <td><?= esc($fecha($pago['fec_reg'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="consumos-detail-empty">Este restaurante no tiene pagos registrados.</div>
        <?php endif; ?>
    </div>
</div>

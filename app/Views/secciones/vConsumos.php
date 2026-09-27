<?php
$respuestaEstablecimientos = $establecimientos ?? $establecimiento ?? null;
$registros = [];

if (is_object($respuestaEstablecimientos) && isset($respuestaEstablecimientos->data)) {
    $registros = is_array($respuestaEstablecimientos->data)
        ? $respuestaEstablecimientos->data
        : (array) $respuestaEstablecimientos->data;
} elseif (is_array($respuestaEstablecimientos)) {
    $registros = $respuestaEstablecimientos['data'] ?? $respuestaEstablecimientos;
}

$registros = array_values(array_filter(array_map(static function ($registro): array {
    return is_object($registro) ? get_object_vars($registro) : (array) $registro;
}, is_array($registros) ? $registros : []), static function (array $registro): bool {
    return !empty($registro);
}));

$totalConsumos = array_reduce($registros, static function (float $total, array $registro): float {
    return $total + (float) ($registro['monto_total'] ?? 0);
}, 0.0);

$formatearMoneda = static function ($monto): string {
    return '$' . number_format((float) $monto, 2, '.', ',');
};
?>

<style>
    .consumos-page {
        min-height: calc(100vh - 70px);
        padding: 30px 28px 42px;
        background: #111b2a;
        color: #f8fafc;
    }

    .consumos-summary,
    .consumos-table-card {
        background: #182436;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 8px;
        box-shadow: 0 16px 36px rgba(2, 6, 23, .18);
    }

    .consumos-summary {
        min-height: 108px;
    }

    .consumos-summary__label {
        color: #94a3b8;
        font-size: .78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .consumos-summary__value {
        margin: .35rem 0 0;
        color: #ffffff;
        font-size: 1.75rem;
        font-weight: 700;
    }

    .consumos-table-card .bootstrap-table .fixed-table-toolbar {
        margin-bottom: 1rem;
    }

    .consumos-table-card .bootstrap-table .fixed-table-toolbar .search {
        width: min(100%, 360px);
    }

    .consumos-table-card .bootstrap-table .fixed-table-toolbar .search input {
        min-height: 42px;
        border: 1px solid rgba(148, 163, 184, .34);
        border-radius: 6px;
        background: #0f172a;
        color: #f8fafc;
    }

    .consumos-table-card .bootstrap-table .fixed-table-toolbar .search input::placeholder {
        color: #94a3b8;
    }

    .consumos-table-card .fixed-table-pagination {
        padding-top: 1rem;
        color: #cbd5e1;
    }

    .consumos-empty {
        padding: 28px;
        border: 1px dashed rgba(148, 163, 184, .3);
        border-radius: 8px;
        color: #cbd5e1;
        text-align: center;
    }

    @media (max-width: 767px) {
        .consumos-page {
            padding: 22px 14px 32px;
        }
    }
</style>

<div class="container-fluid consumos-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="mb-1 text-white">Consumos por establecimiento</h3>
            <p class="text-muted mb-0">Consulta el importe total consumido en cada restaurante.</p>
        </div>
        <a class="btn btn-outline-light" href="<?= esc(base_url('index.php/Inicio'), 'attr') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="consumos-summary h-100 p-3">
                <div class="consumos-summary__label">Establecimientos</div>
                <p class="consumos-summary__value"><?= count($registros) ?></p>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="consumos-summary h-100 p-3">
                <div class="consumos-summary__label">Total consumido</div>
                <p class="consumos-summary__value"><?= esc($formatearMoneda($totalConsumos)) ?></p>
            </div>
        </div>
    </div>

    <div class="consumos-table-card p-3 p-lg-4">
        <?php if (!empty($registros)): ?>
            <div class="table-responsive">
                <table
                    id="consumosTable"
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
                            <th data-sortable="true">Id Establecimiento</th>
                            <th data-sortable="true">Establecimiento</th>
                            <th data-sortable="true" data-sorter="consumosOrdenarMonto" data-align="right">Total consumido</th>
                            <th data-sortable="false" data-align="center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <?php $montoTotal = (float) ($registro['monto_total'] ?? 0); ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= esc((string) ($registro['id_establecimiento'] ?? 'Sin establecimiento')) ?>
                                </td>
                                <td class="fw-semibold">
                                    <?= esc((string) ($registro['dsc_establecimiento'] ?? 'Sin establecimiento')) ?>
                                </td>
                                <td data-value="<?= esc((string) $montoTotal, 'attr') ?>">
                                    <?= esc($formatearMoneda($montoTotal)) ?>
                                </td>
                                <td>
                                    <?php if ($montoTotal > 0): ?>
                                        <a
                                            class="btn btn-sm btn-outline-info"
                                            href="<?= esc(base_url('index.php/Inicio/Consumos/' . (int) ($registro['id_establecimiento'] ?? 0)), 'attr') ?>"
                                            title="Ver desglose de pagos">
                                            <i class="mdi mdi-receipt-text-outline me-1"></i> Ver desglose
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="consumos-empty">
                No hay establecimientos con consumos para mostrar.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function consumosOrdenarMonto(a, b) {
    var normalizar = function (valor) {
        return Number(String(valor || '').replace(/[^0-9.-]/g, '')) || 0;
    };

    return normalizar(a) - normalizar(b);
}
</script>

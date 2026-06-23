<?php
$dashboardSeed = is_array($partidasDashboardSeed ?? null) ? $partidasDashboardSeed : ['resumen' => [], 'partidas' => [], 'meta' => []];
$previewBackUrl = base_url('index.php/Inicio');
$previewMode = '';
if (!empty($previewInterfaceActiva)) {
    if (stripos((string) ($previewInterfaceLabel ?? ''), 'SECUL') !== false) {
        $previewBackUrl = base_url('index.php/Inicio?preview=secul');
        $previewMode = 'secul';
    } elseif (stripos((string) ($previewInterfaceLabel ?? ''), 'FIC') !== false) {
        $previewBackUrl = base_url('index.php/Inicio?preview=fic');
        $previewMode = 'fic';
    } elseif (stripos((string) ($previewInterfaceLabel ?? ''), 'UG') !== false) {
        $previewBackUrl = base_url('index.php/Inicio?preview=ug');
        $previewMode = 'ug';
    }
}
$resumen = is_array($dashboardSeed['resumen'] ?? null) ? $dashboardSeed['resumen'] : [];
$partidas = is_array($dashboardSeed['partidas'] ?? null) ? $dashboardSeed['partidas'] : [];
$meta = is_array($dashboardSeed['meta'] ?? null) ? $dashboardSeed['meta'] : [];
?>
<style>
    .partidas-shell {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, .08), transparent 24%),
            linear-gradient(180deg, #0f172a, #111827);
        color: #f8fafc;
        min-height: calc(100vh - 90px);
        padding: 28px;
    }

    .partidas-shell .panel {
        background: rgba(15, 23, 42, .92);
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 18px;
        box-shadow: 0 18px 48px rgba(2, 6, 23, .28);
    }

    .partidas-shell .panel + .panel {
        margin-top: 18px;
    }

    .partidas-hero h1 {
        margin: 0 0 6px;
        font-size: clamp(1.7rem, 3vw, 2.2rem);
        font-weight: 800;
    }

    .partidas-hero p,
    .partidas-muted {
        color: #cbd5e1;
        margin: 0;
    }

    .partidas-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        background: rgba(59, 130, 246, .14);
        color: #93c5fd;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .partidas-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .partidas-card {
        background: rgba(30, 41, 59, .9);
        border: 1px solid rgba(148, 163, 184, .12);
        border-radius: 16px;
        padding: 16px;
        min-height: 112px;
    }

    .partidas-card__label {
        display: block;
        color: #94a3b8;
        font-size: .75rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        margin-bottom: .4rem;
    }

    .partidas-card__value {
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 800;
        line-height: 1.1;
    }

    .partidas-card__note {
        margin-top: .35rem;
        color: #cbd5e1;
        font-size: .84rem;
    }

    .partidas-chart {
        height: 220px;
        border-radius: 14px;
        background:
            linear-gradient(180deg, rgba(148, 163, 184, .08), rgba(148, 163, 184, .03)),
            repeating-linear-gradient(90deg, transparent, transparent 10%, rgba(96, 165, 250, .06) 10%, rgba(96, 165, 250, .06) 11%);
        border: 1px dashed rgba(96, 165, 250, .22);
        display: grid;
        place-items: center;
        color: #bfdbfe;
        text-align: center;
        padding: 20px;
    }

    .partidas-table-wrap {
        overflow-x: auto;
    }

    .partidas-table {
        min-width: 1280px;
    }

    .partidas-empty-state {
        padding: 24px;
        text-align: center;
        color: #cbd5e1;
    }
</style>

<div class="partidas-shell" id="partidas-fic-root" data-preview-mode="<?= esc($previewMode, 'attr') ?>">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div class="partidas-hero">
            <span class="partidas-badge">Partidas FIC</span>
            <h1>Dashboard de partidas</h1>
            <p>Monitorea el presupuesto oficial de cada partida y compáralo con la operación viva del sistema.</p>
        </div>
        <a class="btn btn-outline-light" href="<?= esc($previewBackUrl, 'attr') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <?php if (!empty($previewInterfaceActiva)): ?>
        <div class="panel p-3 mb-4">
            <div class="alert alert-info mb-0" role="alert">
                <strong><?= esc((string) ($previewInterfaceLabel ?? 'Vista de referencia TI')) ?></strong>
                <div><?= esc((string) ($previewInterfaceDescripcion ?? 'Estás consultando una interfaz de referencia sin cambiar la sesión autenticada.')) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <div class="panel p-3 mb-4">
        <p class="partidas-muted mb-0">Vista de referencia lista para conectar datos operativos de partidas, presupuesto, usuarios y movimientos sin depender de la sesión de la interfaz destino.</p>
    </div>

    <div class="panel p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <span class="partidas-badge">Última actualización</span>
                <div class="partidas-muted mt-2"><?= esc((string) ($meta['ultima_actualizacion'] ?? '--')) ?></div>
            </div>
            <div class="partidas-muted">Fuente: <?= esc((string) ($meta['source'] ?? 'node')) ?> · <?= esc((string) count($partidas)) ?> partidas</div>
        </div>

        <div class="partidas-grid">
            <div class="partidas-card">
                <span class="partidas-card__label">Presupuesto</span>
                <div class="partidas-card__value"><?= esc((string) ($resumen['monto_presupuesto'] ?? '$0.00')) ?></div>
                <div class="partidas-card__note">Monto presupuestal agregado</div>
            </div>
            <div class="partidas-card">
                <span class="partidas-card__label">Ejercido</span>
                <div class="partidas-card__value"><?= esc((string) ($resumen['monto_ejercido'] ?? '$0.00')) ?></div>
                <div class="partidas-card__note">Operación acumulada en tablero</div>
            </div>
            <div class="partidas-card">
                <span class="partidas-card__label">Disponible</span>
                <div class="partidas-card__value"><?= esc((string) ($resumen['monto_disponible'] ?? '$0.00')) ?></div>
                <div class="partidas-card__note">Saldo vigente por partida</div>
            </div>
            <div class="partidas-card">
                <span class="partidas-card__label">Usuarios</span>
                <div class="partidas-card__value"><?= esc((string) ($resumen['usuarios_asignados'] ?? '0')) ?></div>
                <div class="partidas-card__note">Asignación operativa activa</div>
            </div>
        </div>
    </div>

    <div class="panel p-3 mb-4">
        <div class="mb-3">
            <h2 class="h5 mb-1 text-white">Distribución visual por partida</h2>
            <p class="partidas-muted mb-0">Espacio preparado para el gráfico y comparativo de presupuesto.</p>
        </div>
        <div class="partidas-chart">Gráfico pendiente de enlazar con el seed o la API de partidas.</div>
    </div>

    <div class="panel p-3 mb-4">
        <div class="mb-3">
            <h2 class="h5 mb-1 text-white">Tarjetas por partida</h2>
            <p class="partidas-muted mb-0">Resumen individual listo para poblar con el catálogo de partidas.</p>
        </div>
        <div class="partidas-grid">
            <?php if (!empty($partidas)): ?>
                <?php foreach ($partidas as $partida): ?>
                    <article class="partidas-card">
                        <span class="partidas-card__label"><?= esc((string) ($partida['partida'] ?? 'Partida')) ?></span>
                        <div class="partidas-card__value"><?= esc((string) ($partida['monto_presupuesto'] ?? '$0.00')) ?></div>
                        <div class="partidas-card__note"><?= esc((string) ($partida['des_partida'] ?? 'Sin descripción')) ?></div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="partidas-card">
                    <span class="partidas-card__label">Sin datos</span>
                    <div class="partidas-card__value">--</div>
                    <div class="partidas-card__note">Cuando Node/BD entregue el seed, aquí aparecerán las partidas.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel p-3">
        <div class="partidas-table-wrap">
            <table class="table table-dark table-hover align-middle partidas-table" id="tablaPartidasFic" data-toggle="table" data-locale="es-MX" data-search="true" data-search-align="left" data-pagination="true" data-page-size="10" data-page-list="[10, 25, 50, 100]" data-sortable="true" data-classes="table table-striped table-hover">
                <thead>
                    <tr>
                        <th data-field="partida" data-sortable="true">Partida</th>
                        <th data-field="des_partida" data-sortable="true">Descripción</th>
                        <th data-field="monto_presupuesto" data-sortable="true">Presupuesto</th>
                        <th data-field="monto_disponible" data-sortable="true">Disponible tablero</th>
                        <th data-field="monto_ejercido" data-sortable="true">Ejercido</th>
                        <th data-field="consumo_operativo" data-sortable="true">Consumo clientes</th>
                        <th data-field="porcentaje_ejercido" data-sortable="true">% tablero</th>
                        <th data-field="usuarios_asignados" data-sortable="true">Usuarios</th>
                        <th data-field="usuarios_qr_activo" data-sortable="true">QR activos</th>
                        <th data-field="movimientos_cobro" data-sortable="true">Cobros</th>
                        <th data-field="estatus" data-sortable="true">Estatus</th>
                        <th data-field="fec_act" data-sortable="true">Última actividad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($partidas)): ?>
                        <?php foreach ($partidas as $partida): ?>
                            <tr>
                                <td><?= esc((string) ($partida['partida'] ?? '')) ?></td>
                                <td><?= esc((string) ($partida['des_partida'] ?? '')) ?></td>
                                <td><?= esc((string) ($partida['monto_presupuesto'] ?? '$0.00')) ?></td>
                                <td><?= esc((string) ($partida['monto_disponible'] ?? '$0.00')) ?></td>
                                <td><?= esc((string) ($partida['monto_ejercido'] ?? '$0.00')) ?></td>
                                <td><?= esc((string) ($partida['consumo_operativo'] ?? '$0.00')) ?></td>
                                <td><?= esc((string) ($partida['porcentaje_ejercido'] ?? '0%')) ?></td>
                                <td><?= esc((string) ($partida['usuarios_asignados'] ?? '0')) ?></td>
                                <td><?= esc((string) ($partida['usuarios_qr_activo'] ?? '0')) ?></td>
                                <td><?= esc((string) ($partida['movimientos_cobro'] ?? '0')) ?></td>
                                <td><?= esc((string) ($partida['estatus'] ?? 'Sin definir')) ?></td>
                                <td><?= esc((string) ($partida['fec_act'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12">
                                <div class="partidas-empty-state">No hay datos de partidas cargados todavía.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    window.__partidasDashboardSeed = <?= json_encode($dashboardSeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
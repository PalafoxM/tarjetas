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

<div class="partidas-shell" id="partidas-fic-root"
    data-preview-mode="<?= esc($previewMode, 'attr') ?>"
    data-partidas-dashboard="<?= esc(json_encode($dashboardSeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>">
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
                <strong><?= esc((string) ($previewInterfaceLabel ?? 'Vista de referencia')) ?></strong>
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
        <section class="partidas-chart-shell">
            <div class="partidas-chart-head">
                <div>
                    <h2 class="partidas-chart-title">Distribución visual por partida</h2>
                    <p class="partidas-chart-copy">Vista comparativa del presupuesto oficial por partida usando el mismo tablero presupuestal que ya ves en KPIs, cards y tabla.</p>
                </div>
            </div>
            <div id="partidasMultiPieChart" class="partidas-multi-pie-chart"></div>
        </section>
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

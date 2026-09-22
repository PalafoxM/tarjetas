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
$countPartidas = count($partidas);
$partidasLabel = $countPartidas . ' ' . ($countPartidas === 1 ? 'partida' : 'partidas');
?>

<div class="partidas-shell" id="partidas-fic-root"
    data-preview-mode="<?= esc($previewMode, 'attr') ?>"
    data-partidas-dashboard="<?= esc(json_encode($dashboardSeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div class="partidas-hero">
            <span class="partidas-badge">Partidas FIC</span>
            <h1>Dashboard de partidas</h1>
            <p>Monitorea el saldo disponible de la partida activa.</p>
        </div>
        <a class="btn btn-outline-light" href="<?= esc($previewBackUrl, 'attr') ?>">
            <i class="mdi mdi-arrow-left me-1"></i> Volver a inicio
        </a>
    </div>

    <div class="panel p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <span class="partidas-badge">Última actualización</span>
                <div class="partidas-muted mt-2"><?= esc((string) ($meta['ultima_actualizacion'] ?? '--')) ?></div>
            </div>
            <div class="partidas-muted">Fuente: <?= esc((string) ($meta['source'] ?? 'node')) ?> · <?= esc($partidasLabel) ?></div>
        </div>

        <div class="partidas-grid">
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
                    <h2 class="partidas-chart-title">Saldo disponible por partida</h2>
                    <p class="partidas-chart-copy">Consulta el saldo operativo disponible de la partida activa.</p>
                </div>
            </div>
            <div id="partidasMultiPieChart" class="partidas-multi-pie-chart"></div>
        </section>
    </div>

</div>

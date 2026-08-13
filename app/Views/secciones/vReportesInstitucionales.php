<?php
$tabs = array_values(is_array($reportesInstitucionalesTabs ?? null) ? $reportesInstitucionalesTabs : []);
$activeKey = (string) ($tabs[0]['key'] ?? 'fic');
?>

<style>
    .reportes-institucionales-page {
        min-height: calc(100vh - 70px);
        padding: 32px 24px 46px;
        background:
            radial-gradient(circle at 86% 8%, rgba(45, 212, 191, .08), transparent 30%),
            #111b2a;
        color: #f8fafc;
    }

    .reportes-institucionales-header {
        max-width: 980px;
        margin-bottom: 24px;
    }

    .reportes-institucionales-header h3 {
        color: #ffffff;
        margin-bottom: .35rem;
        font-weight: 700;
    }

    .reportes-institucionales-header p {
        color: #c9d4e5;
        margin-bottom: 0;
    }

    .reportes-card {
        background: linear-gradient(180deg, rgba(24, 31, 48, .96), rgba(18, 24, 37, .98));
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
    }

    .reportes-tabs {
        border-bottom: 1px solid rgba(148, 163, 184, .18);
        gap: .35rem;
    }

    .reportes-tabs .nav-link {
        border: 1px solid rgba(148, 163, 184, .18);
        border-bottom: 0;
        color: #cbd5e1;
        background: rgba(15, 23, 42, .58);
        font-weight: 700;
        letter-spacing: .03em;
    }

    .reportes-tabs .nav-link.active {
        background: #2dd4bf;
        border-color: #2dd4bf;
        color: #0f172a;
    }

    .reportes-panel {
        padding: 1.25rem;
    }

    .reportes-action-card {
        min-height: 220px;
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 16px;
        background: rgba(15, 23, 42, .72);
        padding: 1.25rem;
    }

    .reportes-action-card h4 {
        color: #ffffff;
        margin-bottom: .5rem;
    }

    .reportes-action-card p,
    .reportes-action-list {
        color: #cbd5e1;
    }

    .reportes-action-list {
        margin: 1rem 0;
        padding-left: 1.1rem;
    }

    .reportes-action-list li {
        margin-bottom: .35rem;
    }

    .reportes-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
    }
</style>

<div class="reportes-institucionales-page">
    <div class="reportes-institucionales-header">
        <h3>Consulta de reportes y movimientos de perfiles institucionales</h3>
        <p>Descarga reportes PDF de saldos y consulta los movimientos/catálogos visibles de FIC, UG y SECUL.</p>
    </div>

    <div class="card reportes-card">
        <div class="card-body">
            <?php if (empty($tabs)): ?>
                <div class="alert alert-warning mb-0" role="alert">No hay perfiles institucionales configurados para consulta.</div>
            <?php else: ?>
                <ul class="nav nav-tabs reportes-tabs" id="reportesInstitucionalesTabs" role="tablist">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <?php
                            $key = (string) ($tab['key'] ?? ('tab' . $index));
                            $isActive = $key === $activeKey;
                        ?>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link <?= $isActive ? 'active' : '' ?>"
                                id="tab-<?= esc($key, 'attr') ?>"
                                data-bs-toggle="tab"
                                data-bs-target="#panel-<?= esc($key, 'attr') ?>"
                                type="button"
                                role="tab"
                                aria-controls="panel-<?= esc($key, 'attr') ?>"
                                aria-selected="<?= $isActive ? 'true' : 'false' ?>">
                                <?= esc((string) ($tab['label'] ?? strtoupper($key))) ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content" id="reportesInstitucionalesTabsContent">
                    <?php foreach ($tabs as $index => $tab): ?>
                        <?php
                            $key = (string) ($tab['key'] ?? ('tab' . $index));
                            $isActive = $key === $activeKey;
                        ?>
                        <div
                            class="tab-pane fade <?= $isActive ? 'show active' : '' ?>"
                            id="panel-<?= esc($key, 'attr') ?>"
                            role="tabpanel"
                            aria-labelledby="tab-<?= esc($key, 'attr') ?>"
                            tabindex="0">
                            <div class="reportes-panel">
                                <div class="reportes-action-card">
                                    <span class="badge bg-info mb-3">Perfil <?= esc((string) ($tab['label'] ?? strtoupper($key))) ?></span>
                                    <h4><?= esc((string) ($tab['title'] ?? 'Perfil institucional')) ?></h4>
                                    <p><?= esc((string) ($tab['description'] ?? 'Consulta y descarga de reportes institucionales.')) ?></p>

                                    <ul class="reportes-action-list">
                                        <li>El PDF de usuarios incluye tarifa diaria, reservado por vigencia, saldo operativo y pendiente vencido.</li>
                                        <li>El PDF de consumos resume hospedaje por hotel/habitación y alimentos por restaurante.</li>
                                        <li>La consulta de movimientos abre el catálogo institucional correspondiente.</li>
                                        <li>No se muestra información de partida en el reporte.</li>
                                    </ul>

                                    <div class="reportes-actions">
                                        <a class="btn btn-primary js-download-no-loader" data-no-loading="1" href="<?= esc((string) ($tab['download_url'] ?? '#'), 'attr') ?>">
                                            <i class="mdi mdi-file-pdf-box me-1"></i> Reporte de usuarios
                                        </a>
                                        <a class="btn btn-outline-info js-download-no-loader" data-no-loading="1" href="<?= esc((string) ($tab['consumos_url'] ?? '#'), 'attr') ?>">
                                            <i class="mdi mdi-file-chart-outline me-1"></i> Reporte hospedaje y alimentos
                                        </a>
                                        <a class="btn btn-outline-light" href="<?= esc((string) ($tab['profile_url'] ?? '#'), 'attr') ?>">
                                            <i class="mdi mdi-table-search me-1"></i> Consultar movimientos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

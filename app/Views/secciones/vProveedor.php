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
$solicitudPago = array_values(array_map(static function ($item) {
    return is_object($item) ? get_object_vars($item) : (array) $item;
}, is_array($solicitudPago ?? null) ? $solicitudPago : []));
$ventasCorteContexto = is_object($ventasCorteContexto ?? null) ? get_object_vars($ventasCorteContexto) : (is_array($ventasCorteContexto ?? null) ? $ventasCorteContexto : []);
$idEstablecimientoActual = (int) ($idEstablecimientoActual ?? 0);
$reporteVentasUrl = base_url('index.php/Inicio/exportarReporteVentasProveedorPdfFormato');
if ($idEstablecimientoActual > 0) {
    $reporteVentasUrl .= '?id_establecimiento=' . $idEstablecimientoActual . '&download=1';
}

$perfilId = (int) $session->get('id_perfil');
$esVistaEstablecimientoEspecifico = (bool) ($esVistaEstablecimientoEspecifico ?? false);

$ocultarMisEstablecimientos = ($perfilId != 2) || $esVistaEstablecimientoEspecifico;


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
    } elseif (in_array($estatus, ['aprobada', 'aprobado', 'aceptada', 'aceptado', 'aceptados', 'autorizada', 'autorizado', 'pagada', 'pagado', 'finalizada', 'finalizado'], true)) {
        $pagosAprobados++;
    } elseif (in_array($estatus, ['rechazada', 'rechazado', 'rechazados', 'cancelada', 'cancelado'], true)) {
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
    if (in_array($valor, ['aprobada', 'aprobado', 'aceptada', 'aceptado', 'aceptados', 'autorizada', 'autorizado', 'pagada', 'pagado', 'finalizada', 'finalizado'], true)) {
        return '<span class="badge bg-success">Aprobada / aceptada</span>';
    }
    if (in_array($valor, ['rechazada', 'rechazado', 'rechazados', 'cancelada', 'cancelado'], true)) {
        return '<span class="badge bg-danger">Rechazada</span>';
    }
    return '<span class="badge bg-secondary">' . esc($estatus) . '</span>';
};

$pendientesCount = is_countable($pendiente ?? null) ? count($pendiente) : $pagosPendientes;
$aprobadosCount = is_countable($aprobados ?? null) ? count($aprobados) : $pagosAprobados;
$rechazadosCount = is_countable($rechazado ?? null) ? count($rechazado) : $pagosRechazados;
$establecimientosCount = is_numeric($establecimiento ?? null) ? (int) $establecimiento : count($proveedorEstablecimientos);
$montoTotalProveedor = (float) ($total ?? ($ventasCorteContexto['monto_total'] ?? 0));
$proveedorNombre = (string) ($datosProveedor->dsc_establecimiento ?? $proveedorPerfil['razon_social'] ?? 'Proveedor');
$proveedorNumero = (string) ($datosProveedor->no_proveedor ?? $proveedorPerfil['no_proveedor'] ?? '');
?>
<style>
    .provider-page {
        background: linear-gradient(180deg, #101827 0%, #111827 46%, #172033 100%);
        min-height: calc(100vh - 70px);
        color: #f8fafc;
        padding: 28px 28px 42px;
    }

    .provider-shell {
        max-width: 1480px;
        margin: 0 auto;
    }

    .provider-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 1.25rem;
        align-items: center;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        border: 1px solid rgba(148, 163, 184, .18);
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(30, 41, 59, .96), rgba(15, 23, 42, .98));
        box-shadow: 0 18px 42px rgba(2, 6, 23, .22);
    }

    .provider-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .55rem;
        color: #93c5fd;
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .provider-title {
        margin: 0;
        color: #ffffff;
        font-size: clamp(1.45rem, 2vw, 2rem);
        line-height: 1.15;
    }

    .provider-subtitle {
        margin: .45rem 0 0;
        color: #cbd5e1;
    }

    .provider-meta-grid {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
        margin-top: .85rem;
    }

    .provider-meta-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .65rem;
        border: 1px solid rgba(148, 163, 184, .2);
        border-radius: 999px;
        background: rgba(15, 23, 42, .72);
        color: #e2e8f0;
        font-size: .86rem;
        font-weight: 600;
    }

    .provider-action {
        min-height: 42px;
        border-radius: 10px;
        font-weight: 700;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .2);
    }

    .provider-card {
        background: rgba(17, 24, 39, .96);
        border: 1px solid rgba(148, 163, 184, .16);
        border-radius: 12px;
        box-shadow: 0 14px 34px rgba(2, 6, 23, .18);
        position: relative;
        overflow: hidden;
    }

    .provider-card-link {
        color: inherit;
        display: block;
        transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
    }

    .provider-card-link .provider-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(96, 165, 250, .34), rgba(34, 197, 94, .16) 48%, rgba(255, 255, 255, .08) 72%);
        opacity: 0;
        transition: opacity .2s ease;
        pointer-events: none;
    }

    .provider-card-link:hover .provider-card {
        background: rgba(30, 41, 59, .98);
        border-color: rgba(125, 211, 252, .9);
        box-shadow:
            0 0 0 1px rgba(125, 211, 252, .26),
            0 0 26px rgba(59, 130, 246, .38),
            0 26px 56px rgba(2, 6, 23, .46);
        transform: translateY(-7px) scale(1.025);
    }

    .provider-card-link:hover .provider-card::before {
        opacity: 1;
    }

    .provider-card-link:hover {
        color: inherit;
        text-decoration: none;
        filter: saturate(1.04);
    }

    .provider-stat {
        min-height: 124px;
    }

    .provider-stat .card-body {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }

    .provider-stat-label {
        color: #94a3b8;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .provider-stat-value {
        color: #ffffff;
        font-size: clamp(1.55rem, 2.6vw, 2.15rem);
        font-weight: 800;
        line-height: 1.05;
    }

    .provider-stat-note {
        color: #cbd5e1;
        font-size: .88rem;
    }

    .provider-section {
        margin-top: 1.25rem;
    }

    .provider-section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1rem 0;
    }

    .provider-section-title {
        margin: 0;
        color: #ffffff;
        font-size: 1.08rem;
        font-weight: 800;
    }

    .provider-section-copy {
        margin: .25rem 0 0;
        color: #94a3b8;
        font-size: .9rem;
    }

    .provider-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .4rem .65rem;
        border-radius: 999px;
        background: rgba(14, 165, 233, .14);
        color: #bae6fd;
        font-size: .78rem;
        font-weight: 800;
    }

    .provider-table {
        min-width: 1040px;
    }

    .provider-table-wrap {
        overflow-x: auto;
    }

    .provider-card .bootstrap-table .fixed-table-toolbar,
    .provider-card .bootstrap-table .fixed-table-pagination {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .provider-card .bootstrap-table .fixed-table-toolbar {
        margin-bottom: 1rem;
    }

    .provider-card .bootstrap-table .fixed-table-toolbar .search {
        width: min(100%, 360px);
    }

    .provider-card .bootstrap-table .fixed-table-toolbar .search input {
        min-height: 42px;
        border-radius: 10px;
        border: 1px solid rgba(148, 163, 184, .34);
        background: rgba(15, 23, 42, .92);
        color: #f8fafc;
    }

    .provider-card .fixed-table-pagination {
        color: #cbd5e1;
        padding-top: 1rem;
    }

    .provider-card .fixed-table-pagination .btn,
    .provider-card .fixed-table-pagination .dropdown-menu,
    .provider-card .fixed-table-pagination .page-link {
        background: #111827;
        border-color: rgba(148, 163, 184, .28);
        color: #f8fafc;
    }

    .provider-card .fixed-table-pagination .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
    }

    .provider-history-table {
        min-width: 880px;
    }

    .provider-history-empty {
        padding: 18px;
        text-align: center;
        color: #cbd5e1;
        background: rgba(30, 41, 59, .55);
        border-radius: 14px;
        border: 1px dashed rgba(148, 163, 184, .2);
    }

    .provider-status-strip {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        padding: 0 1rem 1rem;
    }

    .provider-status-item {
        padding: .85rem;
        border-radius: 10px;
        background: rgba(15, 23, 42, .7);
        border: 1px solid rgba(148, 163, 184, .14);
    }

    .provider-status-label {
        color: #94a3b8;
        font-size: .75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .provider-status-value {
        margin-top: .15rem;
        font-size: 1.45rem;
        font-weight: 800;
    }

    .provider-table td,
    .provider-history-table td {
        vertical-align: middle;
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

    @media (max-width: 991px) {
        .provider-hero {
            grid-template-columns: 1fr;
        }

        .provider-action {
            width: 100%;
        }
    }

    @media (max-width: 575px) {
        .provider-page {
            padding: 18px;
        }

        .provider-status-strip {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid provider-page" id="proveedorPage" data-provider-mode="1" data-establecimientos-url="<?= esc(base_url('index.php/Inicio/getEstablecimientosProveedor'), 'attr') ?>" data-solicitud-url="<?= esc(base_url('index.php/Inicio/guardarSolicitudUsuarioProveedor'), 'attr') ?>" data-pago-sin-qr-url="<?= esc(base_url('index.php/Inicio/guardarPagoSinQrProveedor'), 'attr') ?>">
    <div class="provider-shell">
        <?php if (!empty($esVistaEstablecimientoEspecifico)): ?>
            <div class="mb-3">
             <a href="<?= base_url('index.php/Inicio') ?>" class="btn btn-outline-secondary">
             <i class="mdi mdi-arrow-left me-1"></i> Atrás
             </a>
            </div>
        <?php endif; ?>
        <section class="provider-hero">
            <div>
                <div class="provider-eyebrow">
                    <i class="mdi mdi-storefront-outline"></i>
                    Portal proveedor
                </div>
                <h3 class="provider-title"><?= esc($proveedorNombre) ?></h3>
                <p class="provider-subtitle">Consulta pagos, cortes y solicitudes operativas desde un solo tablero.</p>
                <div class="provider-meta-grid">
                    <span class="provider-meta-chip"><i class="mdi mdi-card-account-details-outline"></i> RFC: <?= esc((string) ($rfc ?? 'Sin RFC')) ?></span>
                    <span class="provider-meta-chip"><i class="mdi mdi-pound"></i> No. proveedor: <?= esc($proveedorNumero !== '' ? $proveedorNumero : 'Sin asignar') ?></span>
                    <span class="provider-meta-chip"><i class="mdi mdi-calendar-range"></i> Corte desde <?= esc((string) ($ventasCorteContexto['fecha_corte_desde'] ?? 'sin fecha')) ?></span>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-light provider-action" type="button" data-bs-toggle="modal" data-bs-target="#modalSolicitudPersonal">
                    <i class="mdi mdi-account-plus me-1"></i> Solicitar personal
                </button>
                <a class="btn btn-primary provider-action" href="<?= esc(base_url('index.php/Inicio/ProveedorFormatos'), 'attr') ?>">
                    <i class="mdi mdi-file-document-outline me-1"></i> Subir Factura
                </a>
                <button class="btn btn-success provider-action" type="button" data-bs-toggle="modal" data-bs-target="#modalPagoSinQr">
                    <i class="mdi mdi-cash-register me-1"></i> Pagos sin QR
                </button>
            </div>
        </section>

        <section class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card provider-card provider-stat h-100">
                    <div class="card-body">
                        <div class="provider-stat-label">Monto total</div>
                        <div class="provider-stat-value"><?= $formatMoney($montoTotalProveedor) ?></div>
                        <div class="provider-stat-note"><?= esc((string) ($ventasCorteContexto['estado_corte'] ?? 'Sin movimientos')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card provider-card provider-stat h-100">
                    <div class="card-body">
                        <div class="provider-stat-label">Pagos registrados</div>
                        <div class="provider-stat-value"><?= (int) $pagosTotales ?></div>
                        <div class="provider-stat-note">Movimientos capturados</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card provider-card provider-stat h-100">
                    <div class="card-body">
                        <div class="provider-stat-label">Establecimientos</div>
                        <div class="provider-stat-value"><?= (int) $establecimientosCount ?></div>
                        <div class="provider-stat-note">Vinculados al proveedor</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card provider-card provider-stat h-100">
                    <div class="card-body">
                        <div class="provider-stat-label">Estatus</div>
                        <div class="provider-stat-value"><?= (int) $aprobadosCount ?> / <?= (int) $pendientesCount ?></div>
                        <div class="provider-stat-note">Aprobados / pendientes</div>
                    </div>
                </div>
            </div>
        </section>

    
        <?php if (!$ocultarMisEstablecimientos): ?>
        <section class="provider-section">
            <div class="card provider-card">
                <div class="provider-section-header">
                    <div>
                        <span class="provider-summary-pill"><i class="mdi mdi-office-building"></i> Padrón ligado</span>
                        <h5 class="provider-section-title mt-2">Mis establecimientos</h5>
                        <p class="provider-section-copy">Todos los establecimientos vinculados a este proveedor se muestran aquí para consulta rápida.</p>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($proveedorEstablecimientos)): ?>
                        <div class="row g-3">
                            <?php foreach ($proveedorEstablecimientos as $establecimiento): ?>
                                <div class="col-12 col-md-6 col-xl-4">
                                    <?php
                                        $idEstablecimientoCard = (int) ($establecimiento['id_establecimiento'] ?? 0);
                                        $tipoEstablecimientoCard = strtolower(trim((string) ($establecimiento['dsc_tipo'] ?? '')));
                                        $esHotelCard = (int) ($establecimiento['id_tipo'] ?? 0) === 2 || str_contains($tipoEstablecimientoCard, 'hotel');
                                        $perfilEstablecimientoUrl = $esHotelCard
                                            ? base_url('index.php/Inicio/Establecimiento/' . $idEstablecimientoCard)
                                            : base_url('index.php/Inicio/ProveedorEstablecimiento/' . $idEstablecimientoCard);
                                    ?>
                                    <a href="<?= esc($perfilEstablecimientoUrl, 'attr') ?>" class="text-decoration-none d-block h-100">
                                        <div class="card provider-card h-100 provider-card-link">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <div class="provider-stat-label">ID establecimiento</div>
                                                        <div class="provider-stat-value"><?= esc((string) ($establecimiento['id_establecimiento'] ?? '')) ?></div>
                                                    </div>
                                                    <span class="provider-summary-pill">
                                                        <?= $esHotelCard ? 'Recepción' : 'Cobros' ?>
                                                    </span>
                                                </div>
                                                <div class="fw-semibold mt-2"><?= esc((string) ($establecimiento['dsc_establecimiento'] ?? 'Sin nombre')) ?></div>
                                                <div class="provider-stat-note mt-1"><?= esc((string) ($establecimiento['dsc_tipo'] ?? 'Sin tipo')) ?></div>
                                                <div class="provider-stat-note mt-1">No. proveedor: <?= esc((string) ($establecimiento['no_proveedor'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="provider-history-empty">Este proveedor aún no tiene establecimientos visibles ligados.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
    

        <section class="provider-section">
            <div class="card provider-card">
                <div class="provider-section-header d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
                    <div>
                        <span class="provider-summary-pill"><i class="mdi mdi-history"></i> Historial de pagos</span>
                        <h5 class="provider-section-title mt-2">Cortes y autorizaciones</h5>
                        <p class="provider-section-copy">Revisa folios, método, estatus y fecha de cada solicitud de pago.</p>
                    </div>
                </div>
                <div class="provider-status-strip">
                    <div class="provider-status-item">
                        <div class="provider-status-label">Pendientes</div>
                        <div class="provider-status-value text-warning"><?= (int) $pendientesCount ?></div>
                    </div>
                    <div class="provider-status-item">
                        <div class="provider-status-label">Aprobados</div>
                        <div class="provider-status-value text-success"><?= (int) $aprobadosCount ?></div>
                    </div>
                    <div class="provider-status-item">
                        <div class="provider-status-label">Rechazados</div>
                        <div class="provider-status-value text-danger"><?= (int) $rechazadosCount ?></div>
                    </div>
                </div>
                <div class="card-body provider-table-wrap pt-0">
                    <?php if (!empty($solicitudPago)): ?>
                        <table
                            id="tabla-historial-pagos-proveedor"
                            class="table table-dark table-hover align-middle provider-history-table mb-0"
                            data-toggle="table"
                            data-search="true"
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
                                    <th data-sortable="true">Método</th>
                                    <th data-sortable="true">Monto</th>
                                    <th data-sortable="true">Estatus</th>
                                    <th data-sortable="true">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudPago as $pago): ?>
                                    <tr>
                                        <td><?= esc((string) ($pago['folio_solicitud'] ?? 'Sin folio')) ?></td>
                                        <td><?= esc((string) ($pago['metodo_autorizacion'] ?? 'Sin método')) ?></td>
                                        <td><?= $formatMoney($pago['monto_solicitado'] ?? 0) ?></td>
                                        <td><?= $estatusBadge((string) ($pago['estatus'] ?? '')) ?></td>
                                        <td><?= esc((string) ( date('Y-m-d H:i:s', strtotime($pago['fec_reg'] ?? '')) ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="provider-history-empty">Aún no hay pagos o solicitudes registradas.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="modal fade provider-modal" id="modalPagoSinQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <form id="formPagoSinQr" method="post">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title">Pago sin QR</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Captura el folio del cliente, monto, propina y NIP para aplicar el cargo al saldo disponible.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="pago_sin_qr_folio">Folio</label>
                            <input type="text" id="pago_sin_qr_folio" name="folio" class="form-control crud-ui-upper" placeholder="FIC-745-QR" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pago_sin_qr_monto">Monto</label>
                            <input type="number" step="0.01" min="0.01" id="pago_sin_qr_monto" name="monto" class="form-control" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pago_sin_qr_propina">Propina</label>
                            <select id="pago_sin_qr_propina" name="propina_porcentaje" class="form-select" required>
                                <option value="0">0%</option>
                                <option value="5">5%</option>
                                <option value="10">10%</option>
                                <option value="15">15%</option>
                                <option value="20">20%</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pago_sin_qr_total">Total</label>
                            <input type="number" step="0.01" id="pago_sin_qr_total" name="total" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pago_sin_qr_nip">NIP del cliente</label>
                            <input type="password" inputmode="numeric" maxlength="8" id="pago_sin_qr_nip" name="nip" class="form-control" required autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success" id="btnEnviarPagoSinQr">Aplicar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade provider-modal" id="modalSolicitudPersonal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-dark text-white">
            <form id="formSolicitudProveedor" method="post">
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
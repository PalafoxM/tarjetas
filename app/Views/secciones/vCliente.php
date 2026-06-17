<?php
$clienteValor = static function ($origen, string $campo, $default = '') {
    if (is_array($origen) && array_key_exists($campo, $origen)) {
        return $origen[$campo];
    }

    if (is_object($origen) && isset($origen->{$campo})) {
        return $origen->{$campo};
    }

    return $default;
};

$idUsuario = (int) $clienteValor($datosCliente ?? null, 'id_usuario', 0);
$codigoQr = trim((string) $clienteValor($datosCliente ?? null, 'codigo_qr', ''));
$codigoQrRegistrado = $codigoQr !== '';

if ($codigoQr === '') {
    $codigoQr = 'FIC-' . $idUsuario;
}

$saldoDisponible = (float) $clienteValor($saldo ?? null, 'saldo_solicitudo', 0);
$nombreCompleto = (string) $clienteValor($datosCliente ?? null, 'nombre_completo', 'Sin nombre registrado');
$correo = (string) $clienteValor($datosCliente ?? null, 'correo', 'Sin correo registrado');
$folio = (string) $clienteValor($datosCliente ?? null, 'folio', 'Sin folio registrado');
$nip = (string) $clienteValor($datosCliente ?? null, 'nip', 'Sin NIP registrado');
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&margin=12&data=' . rawurlencode($codigoQr);
?>
<link rel="stylesheet" href="<?= base_url('css/fic-cliente.css') ?>?filever=<?= time() ?>">

<div class="container-fluid py-4 cliente-app">
    <section class="cliente-hero">
        <article class="cliente-shell cliente-copy">
            <span class="cliente-kicker">FIC Cliente</span>
            <h1 class="cliente-title">Bienvenido a FIC</h1>
            <p class="cliente-subtitle">En esta sección podrás consultar tu saldo disponible y tu último movimiento.</p>

            <div class="cliente-stats">
                <div class="cliente-stat">
                    <span class="cliente-stat-label">Saldo disponible</span>
                    <strong class="cliente-stat-value">$<?= number_format($saldoDisponible, 2) ?></strong>
                    <span class="cliente-stat-note">Saldo disponible sincronizado con el mismo valor que consume la app.</span>
                </div>
            </div>
        </article>

        <article class="cliente-shell cliente-qr-card">
            <div class="cliente-qr-frame">
                <img src="<?= esc($qrUrl, 'attr') ?>" alt="Código QR para cobrar">
            </div>
            <span class="cliente-token-pill"><?= esc($codigoQr) ?></span>
            <p class="cliente-subtitle">Este es tu QR para realizar pagos en los establecimientos afiliados a FIC.</p>
        </article>
    </section>

    <section class="cliente-grid">
        <article class="cliente-shell">
            <h2 class="cliente-info-title">Identidad del cliente</h2>
            <div class="cliente-list">
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Nombre</span>
                    <strong class="cliente-list-value"><?= esc($nombreCompleto) ?></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Usuario</span>
                    <strong class="cliente-list-value"><?= esc('FIC-' . $idUsuario) ?></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Correo</span>
                    <strong class="cliente-list-value"><?= esc($correo) ?></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Folio</span>
                    <strong class="cliente-list-value"><?= esc($folio) ?></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">NIP</span>
                    <strong class="cliente-list-value"><?= esc($nip) ?></strong>
                </div>
            </div>
        </article>

        <article class="cliente-shell">
            <h2 class="cliente-info-title">Estado del token</h2>
            <div class="cliente-list">
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Código QR</span>
                    <strong class="cliente-list-value"><?= esc($codigoQr) ?></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Último movimiento</span>
                    <strong class="cliente-list-value"></strong>
                </div>
                <div class="cliente-list-item">
                    <span class="cliente-list-label">Beneficio</span>
                    <strong class="cliente-list-value"></strong>
                </div>
                <div class="cliente-callout">
                    <?php if ($codigoQrRegistrado): ?>
                        Este QR usa el mismo código que se valida en la app móvil para identificar al cliente en los cobros.
                    <?php else: ?>
                        No se recibió un código QR registrado desde la consulta; se generó un identificador visual de respaldo.
                    <?php endif; ?>
                </div>
            </div>
        </article>
    </section>
</div>

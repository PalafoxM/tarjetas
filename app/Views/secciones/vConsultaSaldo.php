<style>
    .fic-login-body {
        background: url("<?= base_url('images/fondo_susi.png') ?>") center center / cover no-repeat fixed !important;
    }

    .balance-stage {
        min-height: 100vh;
        position: relative;
        overflow: hidden;
    }

    .balance-stage .background {
        background: url("<?= base_url('images/fondo_susi.png') ?>") center center / cover no-repeat !important;
        inset: -2% !important;
        width: 104% !important;
        opacity: 1;
    }

    .balance-brand-logos {
        position: fixed;
        top: 18px;
        z-index: 20;
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: min(30vw, 260px);
        pointer-events: none;
    }

    .balance-brand-logos--left {
        left: 22px;
    }

    .balance-brand-logos--right {
        right: 22px;
    }

    .balance-brand-logos img {
        display: block;
        max-width: 100%;
        max-height: 92px;
        object-fit: contain;
        filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.7));
    }

    .balance-panel {
        position: fixed;
        top: 50%;
        left: 50%;
        z-index: 25;
        width: min(92vw, 420px);
        padding: 28px;
        border: 2px solid #d4af37;
        border-radius: 16px;
        color: #d4af37;
        background: rgba(0, 0, 0, 0.78);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.65), 0 0 22px rgba(212, 175, 55, 0.2);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        transform: translate(-50%, -50%);
    }

    .balance-panel__header {
        display: flex;
        justify-content: center;
        align-items: center;
        border-bottom: 1px solid rgba(212, 175, 55, 0.45);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }

    .balance-panel__title {
        margin: 0;
        color: #d4af37;
        font-family: Arial, sans-serif;
        font-size: 22px;
        font-weight: 800;
        text-align: center;
    }

    .balance-field {
        margin-bottom: 18px;
    }

    .balance-field label {
        display: block;
        margin-bottom: 8px;
        color: #d4af37;
        font-family: Arial, sans-serif;
        font-size: 13px;
        font-weight: 700;
    }

    .balance-field input {
        box-sizing: border-box;
        width: 100%;
        border: 1px solid #d4af37;
        border-radius: 999px;
        padding: 12px 15px;
        color: #f4d675;
        background: rgba(0, 0, 0, 0.72);
        font-family: Arial, sans-serif;
        font-size: 14px;
        text-transform: uppercase;
        outline: none;
    }

    .balance-field input:focus {
        border-color: #f4d675;
        color: #fff2bd;
        background: rgba(0, 0, 0, 0.9);
        box-shadow: 0 0 0 .18rem rgba(212, 175, 55, 0.2);
    }

    .balance-field input::placeholder {
        color: rgba(244, 214, 117, 0.62);
        text-transform: none;
    }

    .balance-field__help {
        display: block;
        margin-top: 7px;
        color: rgba(247, 232, 166, 0.78);
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.35;
    }

    .balance-btn,
    .balance-back-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        border: 1px solid #d4af37;
        border-radius: 999px;
        padding: 13px 18px;
        color: #0a0a0a;
        background: linear-gradient(135deg, #b8860b, #f4d675);
        font-family: Arial, sans-serif;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.2;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
    }

    .balance-btn:hover,
    .balance-back-link:hover {
        color: #000;
        background: linear-gradient(135deg, #d4af37, #fff0a8);
        transform: translateY(-1px);
        text-decoration: none;
    }

    .balance-btn:disabled {
        cursor: wait;
        opacity: 0.82;
        transform: none;
    }

    .balance-back-link {
        margin-top: 10px;
        color: #f7e8a6;
        background: rgba(212, 175, 55, 0.12);
    }

    .balance-back-link:hover {
        color: #fff3b0;
        background: rgba(212, 175, 55, 0.22);
    }

    .balance-message {
        display: none;
        margin-top: 16px;
        border-radius: 14px;
        padding: 14px;
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.4;
        text-align: center;
    }

    .balance-message.is-visible {
        display: block;
    }

    .balance-message--success {
        border: 1px solid rgba(212, 175, 55, 0.5);
        color: #f7e8a6;
        background: rgba(212, 175, 55, 0.12);
    }

    .balance-message--error {
        border: 1px solid rgba(248, 113, 113, 0.55);
        color: #fecaca;
        background: rgba(127, 29, 29, 0.34);
    }

    .balance-result-label {
        display: block;
        margin-bottom: 6px;
        color: #d4af37;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .balance-result-amount {
        display: block;
        color: #fff3b0;
        font-size: 34px;
        font-weight: 900;
    }

    @media (max-width: 768px) {
        .balance-brand-logos {
            top: 14px;
            max-width: 42vw;
        }

        .balance-brand-logos--left {
            left: 12px;
        }

        .balance-brand-logos--right {
            right: 12px;
        }

        .balance-brand-logos img {
            max-height: 58px;
        }

        .balance-panel {
            top: 54%;
            padding: 22px;
        }

        .balance-result-amount {
            font-size: 28px;
        }
    }
</style>

<div id="container" class="balance-stage">
    <ul id="scene" class="scene unselectable" data-friction-x="0.08" data-friction-y="0.08" data-scalar-x="18" data-scalar-y="12">
        <li class="layer" data-depth="0.00"></li>
        <li class="layer" data-depth="0.10"><div class="background"></div></li>
        <li class="layer" data-depth="0.10"><div class="light orange b phase-4"></div></li>
        <li class="layer" data-depth="0.10"><div class="light purple c phase-5"></div></li>
        <li class="layer" data-depth="0.10"><div class="light orange d phase-3"></div></li>
        <li class="layer" data-depth="0.20"><h1 class="title"><em>EN UN LUGAR DE LA MANCHA</em></h1></li>
        <li class="layer" data-depth="0.30"><div class="wave paint depth-30"></div></li>
        <li class="layer" data-depth="0.40"><div class="wave plain depth-40"></div></li>
        <li class="layer" data-depth="0.50"><div class="wave paint depth-50"></div></li>
        <li class="layer" data-depth="0.60"><div class="lighthouse depth-60"></div></li>
        <li class="layer" data-depth="0.60"><div class="wave plain depth-60"></div></li>
        <li class="layer" data-depth="0.80"><div class="wave plain depth-80"></div></li>
        <li class="layer" data-depth="1.00"><div class="wave paint depth-100"></div></li>
    </ul>

    <div class="balance-brand-logos balance-brand-logos--left">
        <img src="<?= base_url() ?>assets/images/logo-guanajuato.png" alt="Marca Guanajuato">
    </div>
    <div class="balance-brand-logos balance-brand-logos--right">
        <img src="<?= base_url() ?>assets/images/ggt-2006.png" alt="Gobierno de Guanajuato">
    </div>

    <main class="balance-panel" aria-labelledby="consultaSaldoTitle">
        <div class="balance-panel__header">
            <h5 class="balance-panel__title" id="consultaSaldoTitle">Consulta de saldo</h5>
        </div>

        <form id="consultaSaldoForm" data-consulta-url="<?= esc(base_url('index.php/ConsultaSaldo/consultar'), 'attr') ?>" novalidate>
            <div class="balance-field">
                <label for="folioSaldo">Folio</label>
                <input type="text" id="folioSaldo" name="folio" placeholder="Escribe el folio de tu QR" autocomplete="off" inputmode="numeric" maxlength="3" pattern="[0-9]{3}">
                <small class="balance-field__help">Ingresa los 3 dígitos de tu folio, por ejemplo: 016.</small>
            </div>

            <button type="submit" id="btnConsultarSaldo" class="balance-btn">Consulta tu saldo</button>
            <!--<a class="balance-back-link" href="<?= esc(base_url(), 'attr') ?>">Volver al inicio</a>-->

            <div id="consultaSaldoMensaje" class="balance-message" role="status" aria-live="polite"></div>
        </form>
    </main>
</div>

<script>
(function () {
    const form = document.getElementById('consultaSaldoForm');
    const input = document.getElementById('folioSaldo');
    const button = document.getElementById('btnConsultarSaldo');
    const message = document.getElementById('consultaSaldoMensaje');
    let isSubmitting = false;

    function showMessage(type, html) {
        message.className = 'balance-message is-visible balance-message--' + type;
        message.innerHTML = html;
    }

    function clearMessage() {
        message.className = 'balance-message';
        message.textContent = '';
    }

    function getErrorMessage(request) {
        const fallback = 'No fue posible consultar el saldo en este momento. Inténtalo nuevamente más tarde.';

        if (!request || !request.responseText) {
            return fallback;
        }

        try {
            const response = JSON.parse(request.responseText);
            return response.message || response.respuesta || fallback;
        } catch (error) {
            return fallback;
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        if (isSubmitting) {
            return;
        }

        const folio = String(input.value || '').trim();
        const validFolioPattern = /^\d{3}$/;
        if (!validFolioPattern.test(folio)) {
            showMessage('error', 'Ingresa un folio válido de 3 dígitos, por ejemplo: 016.');
            input.focus();
            return;
        }

        isSubmitting = true;
        clearMessage();
        button.disabled = true;
        button.textContent = 'Consultando...';

        $.ajax({
            type: 'POST',
            url: form.dataset.consultaUrl,
            dataType: 'json',
            data: { folio: folio }
        }).done(function (response) {
            if (!response || response.error) {
                showMessage('error', (response && response.message) || 'No encontramos información para ese folio. Verifica el dato e inténtalo nuevamente.');
                return;
            }

            const saldo = response.saldo_formateado || new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(Number(response.saldo || 0));

            showMessage('success', '<span class="balance-result-label">Saldo disponible</span><span class="balance-result-amount">' + saldo + '</span>');
        }).fail(function (request) {
            if (request && request.status === 404) {
                showMessage('error', 'No encontramos información para ese folio.<br>Verifica el dato e inténtalo nuevamente.');
                return;
            }

            showMessage('error', getErrorMessage(request));
        }).always(function () {
            isSubmitting = false;
            button.disabled = false;
            button.textContent = 'Consulta tu saldo';
        });
    });
})();
</script>

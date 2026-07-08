var notificaciones = (function () {
    var state = {
        listUrl: '',
        readUrl: '',
        refreshTimer: null,
        lastItems: []
    };

    function esc(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatFecha(value) {
        if (!value) return '';
        if (window.saeg && saeg.principal && typeof saeg.principal.fecha === 'function') {
            return saeg.principal.fecha(value);
        }
        return value;
    }

    function renderEmpty(message) {
        $('#notificationTrayList').html(
            '<div class="px-3 py-4 text-muted small">' + esc(message || 'Sin notificaciones.') + '</div>'
        );
    }

    function updateBadge(unread) {
        var badge = $('#notificationBellBadge');
        unread = Number(unread || 0);
        if (badge.length === 0) return;
        if (unread > 0) {
            badge.text(unread > 99 ? '99+' : String(unread));
            badge.removeClass('d-none');
        } else {
            badge.addClass('d-none').text('0');
        }
    }

    function buildItem(item) {
        item = item || {};
        var unreadClass = Number(item.is_read || 0) === 1 ? '' : ' is-unread';
        var url = String(item.action_url || '').trim();
        var typeLabel = String(item.tipo || '').replace(/_/g, ' ');
        var title = String(item.titulo || 'Notificación');
        var message = String(item.mensaje || '');
        var created = formatFecha(item.created_at || '');
        var meta = typeLabel + (created !== '' ? ' · ' + created : '');
        var attrs = 'href="#" data-id="' + esc(item.id_notification || '') + '" data-url="' + esc(url) + '"';
        if (url !== '') {
            attrs = 'href="' + esc(url) + '" data-id="' + esc(item.id_notification || '') + '" data-url="' + esc(url) + '"';
        }

        return '<a class="notification-tray__item' + unreadClass + ' js-notification-item" ' + attrs + '>' +
            '<div class="notification-tray__title">' + esc(title) + '</div>' +
            '<div class="notification-tray__message">' + esc(message) + '</div>' +
            '<div class="notification-tray__meta">' + esc(meta) + '</div>' +
        '</a>';
    }

    function render(items, unread) {
        state.lastItems = Array.isArray(items) ? items : [];
        updateBadge(unread);

        if (!state.lastItems.length) {
            renderEmpty('No hay notificaciones para mostrar.');
            $('#notificationTraySubtitle').text('Sin actividad reciente.');
            return;
        }

        $('#notificationTraySubtitle').text(unread > 0 ? (unread + ' sin leer') : 'Todo al día');
        $('#notificationTrayList').html(state.lastItems.map(buildItem).join(''));
    }

    function load() {
        if (!state.listUrl) return;

        $.getJSON(state.listUrl)
            .done(function (response) {
                if (!response || response.ok !== true) {
                    renderEmpty((response && response.message) ? response.message : 'No fue posible consultar notificaciones.');
                    updateBadge(0);
                    return;
                }

                render(response.rows || [], response.unread || 0);
            })
            .fail(function () {
                renderEmpty('No fue posible consultar notificaciones.');
                updateBadge(0);
            });
    }

    function markRead(idNotification, callback) {
        idNotification = Number(idNotification || 0);
        if (!idNotification || !state.readUrl) {
            if (typeof callback === 'function') callback();
            return;
        }

        $.ajax({
            url: state.readUrl,
            type: 'GET',
            dataType: 'json',
            data: { id_notification: idNotification }
        }).always(function () {
            if (typeof callback === 'function') callback();
        });
    }

    function openNotification(item) {
        item = item || {};
        var url = String(item.action_url || '').trim();
        var idNotification = Number(item.id_notification || 0);

        markRead(idNotification, function () {
            load();
            if (url !== '') {
                window.location.href = url;
            }
        });
    }

    function bindEvents() {
        $('#notificationTrayRefresh').off('click.notificaciones').on('click.notificaciones', function (event) {
            event.preventDefault();
            load();
        });

        $(document)
            .off('click.notificaciones', '.js-notification-item')
            .on('click.notificaciones', '.js-notification-item', function (event) {
                event.preventDefault();
                var item = {
                    id_notification: $(this).data('id'),
                    action_url: $(this).data('url')
                };
                openNotification(item);
            });
    }

    function iniciar() {
        var root = $('body');
        if (!root.length || $('#notificationBellDropdown').length === 0) {
            return;
        }

        state.listUrl = (base_url || '') + 'index.php/Inicio/getNotificacionesUsuario';
        state.readUrl = (base_url || '') + 'index.php/Inicio/marcarNotificacionLeida';
        bindEvents();
        load();

        if (state.refreshTimer) {
            window.clearInterval(state.refreshTimer);
        }
        state.refreshTimer = window.setInterval(load, 60000);
    }

    return {
        iniciar: iniciar,
        cargar: load
    };
})();

$(function () {
    try {
        notificaciones.iniciar();
    } catch (error) {
        console.error('No fue posible inicializar notificaciones:', error);
    }
});

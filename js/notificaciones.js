var notificaciones = (function () {
    var state = {
        listUrl: '',
        readUrl: '',
        refreshTimer: null,
        lastItems: [],
        lastUnread: 0,
        filter: 'all'
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

    function getData(item) {
        item = item || {};
        return item.data_json && typeof item.data_json === 'object' ? item.data_json : {};
    }

    function normalizeType(item) {
        var data = getData(item);
        var tipo = String(item.tipo || data.type || '').trim().toUpperCase();
        var estatus = String(data.estatus || '').trim().toLowerCase();

        if (tipo.indexOf('RECHAZ') !== -1 || estatus === 'rechazada') {
            return 'rejected';
        }
        if (tipo.indexOf('PEND') !== -1 || estatus === 'pendiente') {
            return 'pending';
        }
        if (tipo.indexOf('APROB') !== -1 || estatus === 'aprobada') {
            return 'approved';
        }
        return 'general';
    }

    function getTypeLabel(item) {
        var type = normalizeType(item);
        if (type === 'rejected') return 'Rechazada';
        if (type === 'pending') return 'Pendiente';
        if (type === 'approved') return 'Aprobada';
        return String(item && item.tipo ? item.tipo : 'Notificacion').replace(/_/g, ' ');
    }

    function getGroupLabel(item) {
        var data = getData(item);
        var group = String(data.grupo || '').trim();
        if (!group) return '';
        return group.toUpperCase();
    }

    function getFilterLabel(filter) {
        if (filter === 'unread') return 'sin leer';
        if (filter === 'rejected') return 'rechazadas';
        return 'recientes';
    }

    function getFilteredItems(items) {
        items = Array.isArray(items) ? items : [];
        if (state.filter === 'unread') {
            return items.filter(function (item) {
                return Number(item.is_read || 0) !== 1;
            });
        }
        if (state.filter === 'rejected') {
            return items.filter(function (item) {
                return normalizeType(item) === 'rejected';
            });
        }
        return items;
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

    function updateFilterButtons() {
        $('.notification-tray__filter').each(function () {
            var filter = String($(this).data('filter') || 'all');
            $(this).toggleClass('is-active', filter === state.filter);
        });
    }

    function countItems(items) {
        items = Array.isArray(items) ? items : [];
        return {
            all: items.length,
            unread: items.filter(function (item) {
                return Number(item.is_read || 0) !== 1;
            }).length,
            rejected: items.filter(function (item) {
                return normalizeType(item) === 'rejected';
            }).length
        };
    }

    function updateCounters(counts) {
        counts = counts || { all: 0, unread: 0, rejected: 0 };
        $('[data-count-for="all"]').text(String(counts.all || 0));
        $('[data-count-for="unread"]').text(String(counts.unread || 0));
        $('[data-count-for="rejected"]').text(String(counts.rejected || 0));
    }

    function buildItem(item) {
        item = item || {};
        var isUnread = Number(item.is_read || 0) !== 1;
        var isRejected = normalizeType(item) === 'rejected';
        var unreadClass = isUnread ? ' is-unread' : '';
        var rejectedClass = isRejected ? ' is-rejected' : '';
        var url = String(item.action_url || '').trim();
        var typeLabel = getTypeLabel(item);
        var groupLabel = getGroupLabel(item);
        var title = String(item.titulo || 'Notificacion');
        var message = String(item.mensaje || '');
        var created = formatFecha(item.created_at || '');
        var meta = typeLabel + (created !== '' ? ' · ' + created : '');
        var attrs = 'href="#" data-id="' + esc(item.id_notification || '') + '" data-url="' + esc(url) + '"';
        if (url !== '') {
            attrs = 'href="' + esc(url) + '" data-id="' + esc(item.id_notification || '') + '" data-url="' + esc(url) + '"';
        }

        return '<a class="notification-tray__item' + unreadClass + rejectedClass + ' js-notification-item" ' + attrs + '>' +
            (isRejected ? '<div class="notification-tray__pill notification-tray__pill--rejected">Rechazada</div>' : (isUnread ? '<div class="notification-tray__pill notification-tray__pill--unread">Sin leer</div>' : '')) +
            (groupLabel !== '' ? '<div class="notification-tray__pill notification-tray__pill--group">' + esc(groupLabel) + '</div>' : '') +
            '<div class="notification-tray__title">' + esc(title) + '</div>' +
            '<div class="notification-tray__message">' + esc(message) + '</div>' +
            '<div class="notification-tray__meta">' + esc(meta) + '</div>' +
        '</a>';
    }

    function render(items, unread) {
        state.lastItems = Array.isArray(items) ? items : [];
        state.lastUnread = Number(unread || 0);

        updateBadge(state.lastUnread);
        updateFilterButtons();
        updateCounters(countItems(state.lastItems));

        var visibleItems = getFilteredItems(state.lastItems);
        if (!visibleItems.length) {
            renderEmpty(state.lastItems.length ? 'No hay notificaciones para este filtro.' : 'No hay notificaciones para mostrar.');
            $('#notificationTraySubtitle').text(state.lastItems.length ? ('Sin resultados para ' + getFilterLabel(state.filter)) : 'Sin actividad reciente.');
            return;
        }

        var rejectedCount = state.lastItems.filter(function (item) {
            return normalizeType(item) === 'rejected';
        }).length;
        var subtitle = visibleItems.length + ' ' + getFilterLabel(state.filter);
        if (state.filter === 'all') {
            subtitle = (state.lastUnread > 0 ? (state.lastUnread + ' sin leer') : 'Todo al dia') + ' · ' + rejectedCount + ' rechazadas';
        }

        $('#notificationTraySubtitle').text(subtitle);
        $('#notificationTrayList').html(visibleItems.map(buildItem).join(''));
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
            .off('click.notificaciones', '.notification-tray__filter')
            .on('click.notificaciones', '.notification-tray__filter', function (event) {
                event.preventDefault();
                event.stopPropagation();
                state.filter = String($(this).data('filter') || 'all');
                updateFilterButtons();
                render(state.lastItems, state.lastUnread);
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

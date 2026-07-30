(function (window, document, $) {
    'use strict';

    var app = window.ficRealtime || {};
    var broadcastChannel = null;
    var storageEventKey = 'ficRealtime:event';

    try {
        if (window.BroadcastChannel) {
            broadcastChannel = new window.BroadcastChannel('fic-realtime');
        }
    } catch (error) {
        broadcastChannel = null;
    }

    function getBaseUrl() {
        var value = String(window.base_url || '').trim();
        return value.replace(/\/?$/, '/');
    }

    function getMetaContent(name) {
        var meta = document.querySelector('meta[name="' + name + '"]');
        return meta ? String(meta.getAttribute('content') || '').trim() : '';
    }

    function getCookie(name) {
        var target = encodeURIComponent(name) + '=';
        var parts = String(document.cookie || '').split(';');
        for (var i = 0; i < parts.length; i += 1) {
            var item = parts[i].trim();
            if (item.indexOf(target) === 0) {
                return decodeURIComponent(item.substring(target.length));
            }
        }
        return '';
    }

    function resolveUrl(url) {
        var value = String(url || '').trim();
        if (!value) return '';
        if (/^https?:\/\//i.test(value) || value.charAt(0) === '/') return value;
        return getBaseUrl() + value.replace(/^\/+/, '');
    }

    function resolveCsrf() {
        var tokenName = getMetaContent('csrf-token-name') || 'csrf_test_name';
        var headerName = getMetaContent('csrf-header') || 'X-CSRF-TOKEN';
        var token = getMetaContent('csrf-token') || getCookie('csrf_cookie_name');

        return {
            tokenName: tokenName,
            headerName: headerName,
            token: token
        };
    }

    function appendCsrf(data) {
        var csrf = resolveCsrf();
        if (!csrf.token || !csrf.tokenName) {
            return data || {};
        }

        if (window.FormData && data instanceof window.FormData) {
            if (!data.has(csrf.tokenName)) {
                data.append(csrf.tokenName, csrf.token);
            }
            return data;
        }

        var payload = $.extend({}, data || {});
        if (typeof payload[csrf.tokenName] === 'undefined') {
            payload[csrf.tokenName] = csrf.token;
        }
        return payload;
    }

    function updateCsrfFromResponse(jqXHR) {
        if (!jqXHR || typeof jqXHR.getResponseHeader !== 'function') return;

        var csrf = resolveCsrf();
        var nextToken = jqXHR.getResponseHeader(csrf.headerName);
        var metaToken = document.querySelector('meta[name="csrf-token"]');

        if (nextToken && metaToken) {
            metaToken.setAttribute('content', nextToken);
        }
    }

    function extractMessage(source, fallback) {
        var defaultMessage = fallback || 'No fue posible completar la operacion.';
        var payload = source || {};

        if (payload.responseJSON) {
            payload = payload.responseJSON;
        } else if (typeof payload.responseText === 'string' && payload.responseText.trim() !== '') {
            try {
                payload = JSON.parse(payload.responseText);
            } catch (error) {
                return payload.responseText;
            }
        }

        return String(
            payload.respuesta ||
            payload.message ||
            payload.mensaje ||
            payload.error ||
            defaultMessage
        );
    }

    function setBusy(target, busy, label) {
        var element = target && target.jquery ? target : $(target);
        if (!element || !element.length) return;

        element.each(function () {
            var $el = $(this);
            if (busy) {
                if (typeof $el.data('fic-original-html') === 'undefined') {
                    $el.data('fic-original-html', $el.html());
                }
                $el.prop('disabled', true).addClass('is-loading');
                if (label) {
                    $el.html(label);
                }
            } else {
                var originalHtml = $el.data('fic-original-html');
                if (typeof originalHtml !== 'undefined') {
                    $el.html(originalHtml);
                    $el.removeData('fic-original-html');
                }
                $el.prop('disabled', false).removeClass('is-loading');
            }
        });
    }

    function notify(type, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire(title || '', text || '', type || 'info');
        }

        if (text || title) {
            window.alert((title ? title + '\n' : '') + (text || ''));
        }

        return null;
    }

    function request(options) {
        var config = $.extend({
            method: 'GET',
            dataType: 'json',
            timeout: 30000,
            showError: true,
            busyTarget: null,
            busyLabel: null
        }, options || {});

        var ajaxOptions = {
            url: resolveUrl(config.url),
            method: config.method || config.type || 'GET',
            dataType: config.dataType,
            timeout: config.timeout,
            headers: $.extend({}, config.headers || {})
        };

        if (String(ajaxOptions.method || '').toUpperCase() !== 'GET') {
            var csrf = resolveCsrf();
            if (csrf.token && csrf.headerName) {
                ajaxOptions.headers[csrf.headerName] = csrf.token;
            }
        }

        if (config.data) {
            ajaxOptions.data = String(ajaxOptions.method || '').toUpperCase() === 'GET'
                ? config.data
                : appendCsrf(config.data);
        }

        if (window.FormData && ajaxOptions.data instanceof window.FormData) {
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        setBusy(config.busyTarget, true, config.busyLabel);

        return $.ajax(ajaxOptions)
            .done(function (response, textStatus, jqXHR) {
                updateCsrfFromResponse(jqXHR);
                if (typeof config.onSuccess === 'function') {
                    config.onSuccess(response, textStatus, jqXHR);
                }
            })
            .fail(function (jqXHR, textStatus, errorThrown) {
                if (config.showError) {
                    notify('error', 'Error', extractMessage(jqXHR, errorThrown || textStatus || undefined));
                }
                if (typeof config.onError === 'function') {
                    config.onError(jqXHR, textStatus, errorThrown);
                }
            })
            .always(function () {
                setBusy(config.busyTarget, false);
                if (typeof config.onComplete === 'function') {
                    config.onComplete.apply(null, arguments);
                }
            });
    }

    function refreshTable(selector, options) {
        var $table = selector && selector.jquery ? selector : $(selector);
        if (!$table.length || typeof $table.bootstrapTable !== 'function') {
            return false;
        }

        $table.bootstrapTable('refresh', $.extend({ silent: true }, options || {}));
        return true;
    }

    function updateTableRow(selector, matcher, changes) {
        var $table = selector && selector.jquery ? selector : $(selector);
        if (!$table.length || typeof $table.bootstrapTable !== 'function' || typeof matcher !== 'function') {
            return false;
        }

        var rows = $table.bootstrapTable('getData', {
            useCurrentPage: false,
            includeHiddenRows: true
        }) || [];

        for (var i = 0; i < rows.length; i += 1) {
            if (matcher(rows[i], i)) {
                $table.bootstrapTable('updateRow', {
                    index: i,
                    row: $.extend({}, rows[i], changes || {})
                });
                return true;
            }
        }

        return false;
    }

    function emit(name, detail) {
        if (!name) return;
        var payload = {
            name: name,
            detail: detail || {},
            timestamp: Date.now()
        };

        document.dispatchEvent(new window.CustomEvent(name, {
            detail: payload.detail
        }));

        if (broadcastChannel) {
            broadcastChannel.postMessage(payload);
            return;
        }

        try {
            window.localStorage.setItem(storageEventKey, JSON.stringify(payload));
            window.localStorage.removeItem(storageEventKey);
        } catch (error) {
            // Cross-tab notifications are best-effort; local events already fired above.
        }
    }

    function on(name, handler) {
        if (!name || typeof handler !== 'function') return function () {};
        var documentHandler = handler;
        var channelHandler = function (event) {
            var payload = event && event.data ? event.data : {};
            if (payload.name !== name) return;
            handler(new window.CustomEvent(name, { detail: payload.detail || {} }));
        };
        var storageHandler = function (event) {
            if (event.key !== storageEventKey || !event.newValue) return;
            try {
                var payload = JSON.parse(event.newValue);
                if (!payload || payload.name !== name) return;
                handler(new window.CustomEvent(name, { detail: payload.detail || {} }));
            } catch (error) {
                return;
            }
        };

        document.addEventListener(name, documentHandler);
        if (broadcastChannel) {
            broadcastChannel.addEventListener('message', channelHandler);
        } else {
            window.addEventListener('storage', storageHandler);
        }

        return function () {
            document.removeEventListener(name, documentHandler);
            if (broadcastChannel) {
                broadcastChannel.removeEventListener('message', channelHandler);
            } else {
                window.removeEventListener('storage', storageHandler);
            }
        };
    }

    app.baseUrl = getBaseUrl;
    app.csrf = resolveCsrf;
    app.appendCsrf = appendCsrf;
    app.request = request;
    app.refreshTable = refreshTable;
    app.updateTableRow = updateTableRow;
    app.setBusy = setBusy;
    app.notify = notify;
    app.extractMessage = extractMessage;
    app.emit = emit;
    app.on = on;

    window.ficRealtime = app;
})(window, document, window.jQuery);

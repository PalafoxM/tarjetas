(function (window, document) {
    'use strict';

    var unsafeMethods = /^(POST|PUT|PATCH|DELETE)$/;

    function getMeta(name) {
        return document.querySelector('meta[name="' + name + '"]');
    }

    function getConfig() {
        var tokenMeta = getMeta('csrf-token');
        var headerMeta = getMeta('csrf-header');
        var nameMeta = getMeta('csrf-token-name');

        return {
            token: tokenMeta ? tokenMeta.getAttribute('content') || '' : '',
            header: headerMeta ? headerMeta.getAttribute('content') || '' : '',
            name: nameMeta ? nameMeta.getAttribute('content') || '' : ''
        };
    }

    function isSameOrigin(url) {
        try {
            return new URL(url || window.location.href, window.location.href).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    }

    function updateToken(token) {
        if (!token) {
            return;
        }

        var config = getConfig();
        var tokenMeta = getMeta('csrf-token');
        if (tokenMeta) {
            tokenMeta.setAttribute('content', token);
        }

        if (config.name) {
            document.querySelectorAll('input[name="' + config.name + '"]').forEach(function (input) {
                input.value = token;
            });
        }

        window.FicCsrf = {
            token: token,
            header: config.header,
            name: config.name
        };
    }

    function ensureFormToken(form) {
        var method = String(form.getAttribute('method') || 'GET').toUpperCase();
        if (method !== 'POST' || !isSameOrigin(form.getAttribute('action'))) {
            return;
        }

        var config = getConfig();
        if (!config.name || !config.token) {
            return;
        }

        var input = form.querySelector('input[name="' + config.name + '"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = config.name;
            form.appendChild(input);
        }
        input.value = config.token;
    }

    function initializeForms() {
        document.querySelectorAll('form').forEach(ensureFormToken);
    }

    document.addEventListener('submit', function (event) {
        if (event.target && event.target.tagName === 'FORM') {
            ensureFormToken(event.target);
        }
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeForms);
    } else {
        initializeForms();
    }

    if (window.jQuery) {
        window.jQuery.ajaxPrefilter(function (options, originalOptions, xhr) {
            var method = String(options.type || options.method || 'GET').toUpperCase();
            if (!unsafeMethods.test(method) || !isSameOrigin(options.url)) {
                return;
            }

            var config = getConfig();
            if (!config.header || !config.token) {
                return;
            }

            xhr.setRequestHeader(config.header, config.token);
            xhr.always(function () {
                updateToken(xhr.getResponseHeader(config.header));
            });
        });
    }

    if (window.fetch) {
        var originalFetch = window.fetch.bind(window);

        window.fetch = function (input, init) {
            var options = Object.assign({}, init || {});
            var url = typeof input === 'string'
                ? input
                : (input && input.url ? input.url : String(input || ''));
            var method = String(options.method || (input && input.method) || 'GET').toUpperCase();
            var config = getConfig();

            if (unsafeMethods.test(method) && isSameOrigin(url) && config.header && config.token) {
                options.headers = new window.Headers(options.headers || (input && input.headers) || {});
                if (!options.headers.has(config.header)) {
                    options.headers.set(config.header, config.token);
                }
            }

            return originalFetch(input, options).then(function (response) {
                if (config.header) {
                    updateToken(response.headers.get(config.header));
                }
                return response;
            });
        };
    }

    var initialConfig = getConfig();
    window.FicCsrf = {
        token: initialConfig.token,
        header: initialConfig.header,
        name: initialConfig.name
    };
})(window, document);

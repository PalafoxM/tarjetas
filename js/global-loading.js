(function (window, document, $) {
    'use strict';

    var overlayId = 'globalLoadingOverlay';
    var hideDelay = 120;
    var activeRequests = 0;
    var hideTimer = null;
    var suppressNextNavigationLoader = false;

    function getOverlay() {
        return document.getElementById(overlayId);
    }

    function setBodyState(isLoading) {
        if (!document.body) {
            return;
        }

        document.body.classList.toggle('is-loading', !!isLoading);
    }

    function showOverlay() {
        var overlay = getOverlay();
        if (!overlay) {
            return;
        }

        if (hideTimer) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }

        overlay.classList.remove('is-hidden');
        overlay.setAttribute('aria-hidden', 'false');
        setBodyState(true);
    }

    function hideOverlay() {
        var overlay = getOverlay();
        if (!overlay) {
            return;
        }

        hideTimer = window.setTimeout(function () {
            overlay.classList.add('is-hidden');
            overlay.setAttribute('aria-hidden', 'true');
            setBodyState(false);
        }, hideDelay);
    }

    function beginRequest() {
        activeRequests++;
        showOverlay();
    }

    function endRequest() {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0) {
            hideOverlay();
        }
    }

    function bindJqueryAjax() {
        if (!$ || !$.ajaxSetup) {
            return;
        }

        $(document).ajaxStart(function () {
            beginRequest();
        });

        $(document).ajaxStop(function () {
            activeRequests = 0;
            hideOverlay();
        });

        $(document).ajaxError(function () {
            endRequest();
        });
    }

    function bindFetch() {
        if (typeof window.fetch !== 'function') {
            return;
        }

        var nativeFetch = window.fetch.bind(window);
        window.fetch = function () {
            beginRequest();

            return nativeFetch.apply(window, arguments).then(function (response) {
                endRequest();
                return response;
            }).catch(function (error) {
                endRequest();
                throw error;
            });
        };
    }

    function bindNavigation() {
        document.addEventListener('click', function (event) {
            var target = event.target;
            while (target && target !== document) {
                if (target.tagName === 'A') {
                    break;
                }
                target = target.parentNode;
            }

            if (!target || target === document) {
                return;
            }

            var href = String(target.getAttribute('href') || '');
            var isDownloadLink = target.hasAttribute('download')
                || target.getAttribute('data-no-loading') === '1'
                || target.classList.contains('js-download-no-loader')
                || href.indexOf('exportarReporteInstitucionalSaldosPdf') !== -1;

            if (!isDownloadLink) {
                return;
            }

            suppressNextNavigationLoader = true;
            window.setTimeout(function () {
                suppressNextNavigationLoader = false;
                hideOverlay();
            }, 800);
        }, true);

        window.addEventListener('beforeunload', function () {
            if (suppressNextNavigationLoader) {
                suppressNextNavigationLoader = false;
                hideOverlay();
                return;
            }
            showOverlay();
        });
    }

    function init() {
        showOverlay();
        bindJqueryAjax();
        bindFetch();
        bindNavigation();

        window.addEventListener('load', function () {
            hideOverlay();
        });
    }

    if (document.readyState === 'complete') {
        init();
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }

    window.FicLoading = window.FicLoading || {};
    window.FicLoading.show = showOverlay;
    window.FicLoading.hide = hideOverlay;
    window.FicLoading.begin = beginRequest;
    window.FicLoading.end = endRequest;
})(window, document, window.jQuery);

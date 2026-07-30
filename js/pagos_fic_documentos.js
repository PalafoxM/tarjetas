(function (window, document, $) {
    'use strict';

    var realtime = window.ficRealtime || null;
    var endpoint = (window.ficPagosDocumentosConfig && window.ficPagosDocumentosConfig.estadoDocumentalUrl) || '';
    var pendingRefreshes = {};

    if (window.ficPagosDocumentosInitialized) {
        return;
    }
    window.ficPagosDocumentosInitialized = true;

    function escapeText(value) {
        return String(value || '');
    }

    function createIcon(className) {
        var icon = document.createElement('i');
        icon.className = className + ' me-1';
        return icon;
    }

    function createButton(config) {
        var disponible = !!config.disponible;
        var classes = 'btn btn-sm pagos-fic-action-btn ' + (config.className || '');
        var element = document.createElement(disponible ? 'a' : 'button');

        element.className = classes.trim();
        element.appendChild(createIcon(config.icon || 'mdi mdi-file-outline'));
        element.appendChild(document.createTextNode(' ' + escapeText(config.label)));

        if (disponible) {
            element.setAttribute('href', config.url || '#');
            element.setAttribute('target', '_blank');
            element.setAttribute('rel', 'noopener');
        } else {
            element.setAttribute('type', 'button');
            element.disabled = true;
        }

        return element;
    }

    function normalizeDocumento(documento, fallbackLabel) {
        return $.extend({
            disponible: false,
            label: fallbackLabel,
            url: ''
        }, documento || {});
    }

    function buildActions(estado) {
        var documentos = estado && estado.documentos ? estado.documentos : {};
        var formatos = documentos.formatos || {};
        var fragment = document.createDocumentFragment();
        var actions = [
            {
                data: normalizeDocumento(documentos.reporte, 'Visualizar reporte'),
                icon: 'mdi mdi-file-chart-outline',
                className: 'btn-outline-info'
            },
            {
                data: normalizeDocumento(documentos.pdf, 'Visualizar PDF'),
                icon: 'mdi mdi-file-pdf-box',
                className: 'pagos-fic-action-btn--pdf'
            },
            {
                data: normalizeDocumento(documentos.xml, 'Visualizar XML'),
                icon: 'mdi mdi-file-xml-box',
                className: 'btn-outline-warning'
            },
            {
                data: normalizeDocumento(formatos.formato_pt, 'Formato PT'),
                icon: 'mdi mdi-file-document-outline',
                className: 'pagos-fic-action-btn--format'
            },
            {
                data: normalizeDocumento(formatos.encabezado_factura, 'Encabezado factura'),
                icon: 'mdi mdi-file-sign',
                className: 'pagos-fic-action-btn--format'
            },
            {
                data: normalizeDocumento(formatos.liberacion_pago, 'Liberacion pago'),
                icon: 'mdi mdi-receipt-text-outline',
                className: 'pagos-fic-action-btn--format'
            },
            {
                data: normalizeDocumento(formatos.liberacion_pago_proveedor, 'Liberacion pago proveedor'),
                icon: 'mdi mdi-receipt-outline',
                className: 'pagos-fic-action-btn--format'
            }
        ];

        actions.forEach(function (action) {
            fragment.appendChild(createButton($.extend({}, action.data, {
                icon: action.icon,
                className: action.className
            })));
        });

        return fragment;
    }

    function applyEstadoDocumental(estado) {
        var idEstablecimiento = String(estado && estado.id_establecimiento ? estado.id_establecimiento : '').trim();
        if (!idEstablecimiento) return false;

        document.querySelectorAll('[data-fic-document-actions="' + idEstablecimiento + '"]').forEach(function (slot) {
            slot.innerHTML = '';
            slot.appendChild(buildActions(estado));
            slot.classList.add('is-updated');
            window.setTimeout(function () {
                slot.classList.remove('is-updated');
            }, 1200);
        });

        return true;
    }

    function applyEstadoDocumentalCollection(data) {
        if (!Array.isArray(data)) {
            return applyEstadoDocumental(data);
        }

        data.forEach(function (estado) {
            applyEstadoDocumental(estado);
        });
        return true;
    }

    function requestEstadosIniciales() {
        if (!endpoint) return;

        var options = {
            url: endpoint,
            method: 'GET',
            dataType: 'json',
            showError: false
        };
        var request = realtime && typeof realtime.request === 'function'
            ? realtime.request(options)
            : $.ajax(options);

        request.done(function (response) {
            if (response && response.ok !== false && response.data) {
                applyEstadoDocumentalCollection(response.data);
            }
        });
    }

    function requestEstadoDocumental(idEstablecimiento) {
        idEstablecimiento = String(idEstablecimiento || '').trim();
        if (!endpoint || !idEstablecimiento) return;

        if (pendingRefreshes[idEstablecimiento]) {
            window.clearTimeout(pendingRefreshes[idEstablecimiento]);
        }

        pendingRefreshes[idEstablecimiento] = window.setTimeout(function () {
            delete pendingRefreshes[idEstablecimiento];
            var options = {
                url: endpoint,
                method: 'GET',
                dataType: 'json',
                data: { id_establecimiento: idEstablecimiento },
                showError: false
            };
            var request = realtime && typeof realtime.request === 'function'
                ? realtime.request(options)
                : $.ajax(options);

            request.done(function (response) {
                if (response && response.ok !== false && response.data) {
                    applyEstadoDocumental(response.data);
                }
            });
        }, 250);
    }

    function refreshFromEvent(event) {
        var detail = event && event.detail ? event.detail : {};
        var estado = detail.estado || null;
        var idEstablecimiento = detail.id_establecimiento || (estado && estado.id_establecimiento) || '';

        if (estado && applyEstadoDocumental(estado)) {
            return;
        }

        if (!idEstablecimiento && detail.scope === 'documental') {
            requestEstadosIniciales();
            return;
        }

        requestEstadoDocumental(idEstablecimiento);
    }

    function init() {
        if (!document.querySelector('[data-fic-document-actions]')) return;

        requestEstadosIniciales();

        if (realtime && typeof realtime.on === 'function') {
            realtime.on('fic:estado-documental-actualizado', refreshFromEvent);
            realtime.on('fic:factura-subida', refreshFromEvent);
            realtime.on('fic:reporte-subido', refreshFromEvent);
            return;
        }

        document.addEventListener('fic:estado-documental-actualizado', refreshFromEvent);
        document.addEventListener('fic:factura-subida', refreshFromEvent);
        document.addEventListener('fic:reporte-subido', refreshFromEvent);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document, window.jQuery);

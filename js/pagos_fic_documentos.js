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
        var abreModalArchivos = disponible && !!config.abreModalArchivos;
        var element = document.createElement(disponible && !abreModalArchivos ? 'a' : 'button');

        element.className = classes.trim();
        element.appendChild(createIcon(config.icon || 'mdi mdi-file-outline'));
        element.appendChild(document.createTextNode(' ' + escapeText(config.label)));

        if (abreModalArchivos) {
            element.setAttribute('type', 'button');
            element.setAttribute('data-url', config.url || '');
        } else if (disponible) {
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
                data: $.extend(normalizeDocumento(formatos.liberacion_pago_proveedor, 'Archivos Adjuntos'), {
                    abreModalArchivos: true
                }),
                icon: 'mdi mdi-receipt-outline',
                className: 'btn-outline-success pagos-fic-action-btn--format js-archivos-juntos'
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

        requestEstadoDocumental(idEstablecimiento);
    }

    function init() {
        if (!document.querySelector('[data-fic-document-actions]')) return;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-archivos-juntos');
            if (!button || button.disabled) return;

            event.preventDefault();
            abrirModalArchivosJuntos(button.getAttribute('data-url') || '');
        });

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

    function abrirModalArchivosJuntos(baseUrl) {
        if (!baseUrl || typeof window.Swal === 'undefined') return;

        window.Swal.fire({
            title: 'Generar archivos adjuntos',
            html: '' +
                '<div class="text-start">' +
                    '<label for="fic-consecutivo" class="form-label">No. consecutivo</label>' +
                    '<input id="fic-consecutivo" type="text" inputmode="numeric" maxlength="10" class="swal2-input m-0 w-100" placeholder="Ej. 001" autocomplete="off">' +
                    '<div class="form-check mt-3">' +
                        '<input id="fic-irs" class="form-check-input" type="checkbox">' +
                        '<label class="form-check-label" for="fic-irs">IRS</label>' +
                    '</div>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            focusConfirm: false,
            preConfirm: function () {
                var input = document.getElementById('fic-consecutivo');
                var consecutivo = input ? input.value.trim() : '';

                if (!/^\d{1,10}$/.test(consecutivo)) {
                    window.Swal.showValidationMessage('Ingresa un número consecutivo válido.');
                    return false;
                }

                return {
                    consecutivo: consecutivo,
                    irs: document.getElementById('fic-irs').checked
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) return;

            var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
            var url = baseUrl + separator + 'consecutivo=' + encodeURIComponent(result.value.consecutivo);
            if (result.value.irs) {
                url += '&irs=1';
            }
            window.open(url, '_blank', 'noopener');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(window, document, window.jQuery);

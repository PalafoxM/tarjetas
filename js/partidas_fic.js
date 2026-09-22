(function (window, document) {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }

        fn();
    }

    function toNumber(value) {
        if (value === null || value === undefined) {
            return 0;
        }

        var normalized = String(value).replace(/[^0-9.-]/g, '');
        var parsed = parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(Number(value) || 0);
    }

    function renderNoData(mount, message) {
        mount.innerHTML = '<div class="partidas-chart-empty">' + message + '</div>';
    }

    function buildDonutCardMarkup(item) {
        var formattedAvailable = formatCurrency(item.available);

        return '' +
            '<button class="partidas-donut-card partidas-available-balance" type="button" data-partida-id="' + item.id + '" title="Saldo disponible: ' + formattedAvailable + '" aria-label="Saldo disponible: ' + formattedAvailable + '">' +
                '<span class="partidas-chart-summary-label">' + item.label + '</span>' +
                '<strong class="partidas-donut-card__title">' + item.note + '</strong>' +
                '<span class="partidas-available-label">Saldo disponible</span>' +
                '<span class="partidas-available-amount">' + formattedAvailable + '</span>' +
            '</button>';
    }

    function destroyChartInstances(root) {
        var instances = Array.isArray(root.__partidasCharts) ? root.__partidasCharts : [];
        instances.forEach(function (chart) {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        root.__partidasCharts = [];
    }

    function renderChart(root, dashboardOverride) {
        var mount = document.getElementById('partidasMultiPieChart');
        if (!mount) {
            return;
        }

        var dashboard = dashboardOverride && typeof dashboardOverride === 'object' ? dashboardOverride : null;
        if (!dashboard) {
            var raw = root.dataset.partidasDashboard || '{}';
            try {
                dashboard = JSON.parse(raw);
            } catch (e) {
                dashboard = {};
            }
        }

        var partidas = Array.isArray(dashboard.partidas) ? dashboard.partidas : [];
        var palette = ['#60a5fa', '#34d399', '#fbbf24', '#f97316', '#a78bfa', '#fb7185', '#22d3ee', '#c084fc'];
        var priorityOrder = { 2: 0, 3: 1, 1: 2 };

        var chartData = partidas
            .map(function (partida, index) {
                var available = toNumber(partida.monto_disponible);
                var id = Number(partida.id_partida || 0);

                return {
                    id: id,
                    label: String(partida.partida || ('Partida ' + (index + 1))),
                    note: String(partida.des_partida || 'Sin descripcion'),
                    available: available,
                    color: String(partida.color_dashboard || palette[index % palette.length])
                };
            })
            .filter(function (item) {
                return item.available > 0 && (item.id === 2 || item.id === 3 || item.id === 1);
            })
            .sort(function (a, b) {
                var aOrder = Object.prototype.hasOwnProperty.call(priorityOrder, a.id) ? priorityOrder[a.id] : 99;
                var bOrder = Object.prototype.hasOwnProperty.call(priorityOrder, b.id) ? priorityOrder[b.id] : 99;
                return aOrder - bOrder;
            });

        if (!chartData.length) {
            destroyChartInstances(root);
            renderNoData(mount, 'No hay saldo disponible para mostrar todavia.');
            return;
        }

        destroyChartInstances(root);
        mount.innerHTML = '<div class="partidas-donut-grid">' + chartData.map(buildDonutCardMarkup).join('') + '</div>';
    }

    onReady(function () {
        var root = document.getElementById('partidas-fic-root');
        if (!root) {
            return;
        }

        renderChart(root);
    });
})(window, document);

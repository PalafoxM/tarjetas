(function (window, document) {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }

        fn();
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var existing = document.querySelector('script[data-src="' + src + '"]');
            if (existing) {
                if (window.ApexCharts) {
                    resolve();
                    return;
                }

                existing.addEventListener('load', function () {
                    resolve();
                }, { once: true });
                existing.addEventListener('error', function () {
                    reject(new Error('No fue posible cargar ' + src));
                }, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.dataset.src = src;
            script.onload = function () {
                resolve();
            };
            script.onerror = function () {
                reject(new Error('No fue posible cargar ' + src));
            };
            document.head.appendChild(script);
        });
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

    function buildLegendItem(item) {
        return '' +
            '<div class="partidas-chart-legend-item">' +
                '<span class="partidas-chart-legend-swatch" style="background:' + item.color + '"></span>' +
                '<div>' +
                    '<div class="partidas-chart-legend-label">' + item.label + '</div>' +
                    '<div class="partidas-muted" style="font-size:.82rem">' + item.note + '</div>' +
                '</div>' +
                '<div class="partidas-chart-legend-meta">' +
                    formatCurrency(item.value) + '<br>' + item.percent.toFixed(1) + '% ejercido' +
                '</div>' +
            '</div>';
    }

    function renderChart(root) {
        var mount = document.getElementById('partidasMultiPieChart');
        if (!mount) {
            return;
        }

        var raw = root.dataset.partidasDashboard || '{}';
        var dashboard;
        try {
            dashboard = JSON.parse(raw);
        } catch (e) {
            dashboard = {};
        }

        var partidas = Array.isArray(dashboard.partidas) ? dashboard.partidas : [];
        var palette = ['#60a5fa', '#34d399', '#fbbf24', '#f97316', '#a78bfa', '#fb7185', '#22d3ee', '#c084fc'];

        var chartData = partidas
            .map(function (partida, index) {
                var budget = toNumber(partida.monto_presupuesto);
                var exercised = toNumber(partida.monto_ejercido);
                var available = toNumber(partida.monto_disponible);
                return {
                    label: String(partida.partida || ('Partida ' + (index + 1))),
                    note: String(partida.des_partida || 'Sin descripción'),
                    value: budget,
                    exercised: exercised,
                    available: available,
                    percent: budget > 0 ? (exercised / budget) * 100 : 0,
                    color: String(partida.color_dashboard || palette[index % palette.length])
                };
            })
            .filter(function (item) {
                return item.value > 0;
            })
            .sort(function (a, b) {
                return b.value - a.value;
            });

        if (!chartData.length) {
            renderNoData(mount, 'No hay presupuesto disponible para graficar todavía.');
            return;
        }

        var totalBudget = chartData.reduce(function (carry, item) {
            return carry + item.value;
        }, 0);
        var totalExercised = chartData.reduce(function (carry, item) {
            return carry + item.exercised;
        }, 0);
        var totalAvailable = chartData.reduce(function (carry, item) {
            return carry + item.available;
        }, 0);

        var legendItems = chartData.slice(0, 6).map(buildLegendItem).join('');

        mount.innerHTML = [
            '<div class="partidas-chart-layout">',
                '<div class="partidas-chart-canvas">',
                    '<div id="partidasMultiPieChartCanvas"></div>',
                '</div>',
                '<aside class="partidas-chart-side">',
                    '<div class="partidas-chart-summary">',
                        '<div class="partidas-chart-summary-card">',
                            '<span class="partidas-chart-summary-label">Presupuesto total</span>',
                            '<div class="partidas-chart-summary-value">' + formatCurrency(totalBudget) + '</div>',
                        '</div>',
                        '<div class="partidas-chart-summary-card">',
                            '<span class="partidas-chart-summary-label">Ejercido</span>',
                            '<div class="partidas-chart-summary-value">' + formatCurrency(totalExercised) + '</div>',
                        '</div>',
                        '<div class="partidas-chart-summary-card">',
                            '<span class="partidas-chart-summary-label">Disponible</span>',
                            '<div class="partidas-chart-summary-value">' + formatCurrency(totalAvailable) + '</div>',
                        '</div>',
                    '</div>',
                    '<div class="partidas-chart-legend">' + legendItems + '</div>',
                '</aside>',
            '</div>'
        ].join('');

        var chartEl = document.getElementById('partidasMultiPieChartCanvas');
        if (!chartEl) {
            return;
        }

        var chartOptions = {
            chart: {
                type: 'donut',
                height: 320,
                toolbar: { show: false },
                foreColor: '#cbd5e1',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 650
                }
            },
            series: chartData.map(function (item) { return item.value; }),
            labels: chartData.map(function (item) { return item.label; }),
            colors: chartData.map(function (item) { return item.color; }),
            legend: { show: false },
            stroke: {
                width: 2,
                colors: ['#0f172a']
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '12px', fontWeight: 700 },
                formatter: function (val, opts) {
                    var item = chartData[opts.seriesIndex];
                    return item ? item.label : val.toFixed(1) + '%';
                },
                dropShadow: { enabled: false }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                color: '#f8fafc',
                                offsetY: -8
                            },
                            value: {
                                show: true,
                                color: '#e2e8f0',
                                formatter: function () {
                                    return formatCurrency(totalExercised);
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Presupuesto',
                                color: '#93c5fd',
                                formatter: function () {
                                    return formatCurrency(totalBudget);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value, opts) {
                        var item = chartData[opts.seriesIndex];
                        return formatCurrency(value) + ' · ' + (item ? item.percent.toFixed(1) : '0.0') + '% ejercido';
                    }
                }
            },
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: { height: 280 },
                    plotOptions: { pie: { donut: { size: '64%' } } }
                }
            }]
        };

        function mountChart() {
            if (typeof window.ApexCharts === 'undefined') {
                renderNoData(mount, 'ApexCharts no está disponible en esta vista.');
                return;
            }

            var chart = new window.ApexCharts(chartEl, chartOptions);
            chart.render();
        }

        if (window.ApexCharts) {
            mountChart();
            return;
        }

        if (!window.base_url) {
            renderNoData(mount, 'No fue posible resolver la ruta base de la librería de gráficas.');
            return;
        }

        loadScript(window.base_url.replace(/\/$/, '') + '/plugins/apexcharts/apexcharts.min.js')
            .then(mountChart)
            .catch(function () {
                renderNoData(mount, 'No fue posible cargar ApexCharts.');
            });
    }

    onReady(function () {
        var root = document.getElementById('partidas-fic-root');
        if (!root) {
            return;
        }

        renderChart(root);
    });
})(window, document);

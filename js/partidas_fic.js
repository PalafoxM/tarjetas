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

    function sanitizeKey(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'partida';
    }

    function buildDonutCardMarkup(item) {
        return '' +
            '<article class="partidas-donut-card" data-partida-id="' + item.id + '">' +
                '<div class="partidas-donut-card__head">' +
                    '<div>' +
                        '<span class="partidas-chart-summary-label">' + item.label + '</span>' +
                        '<h3 class="partidas-donut-card__title">' + item.note + '</h3>' +
                    '</div>' +
                    '<div class="partidas-donut-card__meta">' +
                        '<span>' + item.percent.toFixed(1) + '% ejercido</span>' +
                        '<strong>' + formatCurrency(item.exercised) + '</strong>' +
                    '</div>' +
                '</div>' +
                '<div class="partidas-donut-card__chart" id="partidasDonut-' + sanitizeKey(item.label) + '"></div>' +
                '<div class="partidas-donut-card__footer">' +
                    '<span>Presupuesto: ' + formatCurrency(item.budget) + '</span>' +
                    '<span>Disponible: ' + formatCurrency(item.available) + '</span>' +
                '</div>' +
            '</article>';
    }

    function renderSingleDonut(chartEl, item) {
        if (typeof window.ApexCharts === 'undefined') {
            chartEl.innerHTML = '<div class="partidas-chart-empty">ApexCharts no esta disponible.</div>';
            return null;
        }

        var chartOptions = {
            chart: {
                type: 'donut',
                height: 270,
                toolbar: { show: false },
                foreColor: '#cbd5e1',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 650
                },
                events: {
                    dataPointSelection: function () {
                        chartEl.classList.add('is-active');
                    }
                }
            },
            series: [item.exercised, item.available],
            labels: ['Ejercido', 'Disponible'],
            colors: [item.color, 'rgba(148, 163, 184, .18)'],
            legend: { show: false },
            stroke: {
                width: 2,
                colors: ['#0f172a']
            },
            states: {
                hover: {
                    filter: { type: 'lighten', value: 0.08 }
                },
                active: {
                    allowMultipleDataPointsSelection: false,
                    filter: { type: 'darken', value: 0.65 }
                }
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '11px', fontWeight: 700 },
                formatter: function (val, opts) {
                    return opts.seriesIndex === 0 ? 'Ejercido' : 'Disponible';
                },
                dropShadow: { enabled: false }
            },
            plotOptions: {
                pie: {
                    expandOnClick: true,
                    donut: {
                        size: '70%',
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
                                    return formatCurrency(item.exercised);
                                }
                            },
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Presupuesto',
                                color: '#93c5fd',
                                formatter: function () {
                                    return formatCurrency(item.budget);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value, opts) {
                        var label = opts.seriesIndex === 0 ? 'Ejercido' : 'Disponible';
                        return label + ': ' + formatCurrency(value) + ' · ' + item.percent.toFixed(1) + '% del presupuesto';
                    }
                }
            }
        };

        var chart = new window.ApexCharts(chartEl, chartOptions);
        chart.render();
        return chart;
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
                var budget = toNumber(partida.monto_presupuesto);
                var exercised = toNumber(partida.monto_ejercido);
                var available = toNumber(partida.monto_disponible);
                var id = Number(partida.id_partida || 0);

                return {
                    id: id,
                    label: String(partida.partida || ('Partida ' + (index + 1))),
                    note: String(partida.des_partida || 'Sin descripcion'),
                    exercised: exercised,
                    budget: budget,
                    available: available,
                    percent: budget > 0 ? (exercised / budget) * 100 : 0,
                    color: String(partida.color_dashboard || palette[index % palette.length])
                };
            })
            .filter(function (item) {
                return item.budget > 0 && (item.id === 2 || item.id === 3 || item.id === 1);
            })
            .sort(function (a, b) {
                var aOrder = Object.prototype.hasOwnProperty.call(priorityOrder, a.id) ? priorityOrder[a.id] : 99;
                var bOrder = Object.prototype.hasOwnProperty.call(priorityOrder, b.id) ? priorityOrder[b.id] : 99;
                return aOrder - bOrder;
            });

        if (!chartData.length) {
            destroyChartInstances(root);
            renderNoData(mount, 'No hay presupuesto disponible para graficar todavia.');
            return;
        }

        destroyChartInstances(root);
        mount.innerHTML = '<div class="partidas-donut-grid">' + chartData.map(buildDonutCardMarkup).join('') + '</div>';

        function mountChart() {
            var chartInstances = [];
            chartData.forEach(function (item) {
                var chartEl = document.getElementById('partidasDonut-' + sanitizeKey(item.label));
                if (!chartEl) {
                    return;
                }

                var chart = renderSingleDonut(chartEl, item);
                if (chart) {
                    chartInstances.push(chart);
                }
            });
            root.__partidasCharts = chartInstances;
        }

        if (window.ApexCharts) {
            mountChart();
            return;
        }

        if (!window.base_url) {
            renderNoData(mount, 'No fue posible resolver la ruta base de la libreria de graficas.');
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

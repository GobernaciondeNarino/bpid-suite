/**
 * BPID Suite — Frontend Chart Manager v2.0.0
 * Gobernación de Nariño
 *
 * Dual rendering engine: Chart.js (primary) + D3plus (legacy fallback).
 * Features:
 * - ChartManager class for lifecycle management
 * - IntersectionObserver lazy loading
 * - Colombian number formatting (COP)
 * - Configurable toolbar (Detalle, Compartir, Datos, Imagen, CSV)
 * - Responsive resize handling
 * - Multiple Y-axis with individual colors
 */
(function () {
    'use strict';

    /* ========================================
       Number Formatter (Colombian locale)
       ======================================== */
    var NumberFormatter = {
        formatCOP: function (value) {
            if (value == null || isNaN(value)) return '0';
            var num = Number(value);
            if (Math.abs(num) >= 1e12) return (num / 1e12).toFixed(2) + 'B';
            if (Math.abs(num) >= 1e9)  return (num / 1e9).toFixed(2)  + 'MMII';
            if (Math.abs(num) >= 1e6)  return (num / 1e6).toFixed(2)  + 'MM';
            if (Math.abs(num) >= 1e3)  return (num / 1e3).toFixed(1)  + 'K';
            return num.toLocaleString('es-CO');
        },
        currency: function (value) {
            if (value == null || isNaN(value)) return '$0';
            var num = Number(value);
            if (num >= 1e9) return '$' + (num / 1e9).toFixed(1).replace('.', ',') + 'B';
            if (num >= 1e6) return '$' + (num / 1e6).toFixed(1).replace('.', ',') + 'M';
            if (num >= 1e3) return '$' + (num / 1e3).toFixed(1).replace('.', ',') + 'K';
            return '$' + num.toLocaleString('es-CO');
        },
        percent: function (value) {
            if (value == null || isNaN(value)) return '0%';
            return Number(value).toFixed(1).replace('.', ',') + '%';
        },
        number: function (value) {
            if (value == null || isNaN(value)) return '0';
            return Number(value).toLocaleString('es-CO');
        },
        byFormat: function (format) {
            var self = this;
            switch (format) {
                case 'es-CO':    return function (v) { return self.number(v); };
                case 'en-US':    return function (v) { return Number(v).toLocaleString('en-US'); };
                case 'de-DE':    return function (v) { return Number(v).toLocaleString('de-DE'); };
                case 'compact':  return function (v) { return self.formatCOP(v); };
                case 'raw':      return function (v) { return String(v); };
                default:         return function (v) { return self.number(v); };
            }
        }
    };

    /* ========================================
       Chart.js Type Mapping
       ======================================== */
    function mapChartType(internal) {
        var map = {
            bar: 'bar',
            bar_horizontal: 'bar',
            bar_stacked: 'bar',
            bar_grouped: 'bar',
            line: 'line',
            area: 'line',
            area_stacked: 'line',
            pie: 'pie',
            donut: 'doughnut',
            radar: 'radar',
            heatmap: 'matrix',
            plot: 'scatter'
        };
        return map[internal] || 'bar';
    }

    function isStacked(type) {
        return type === 'bar_stacked' || type === 'area_stacked';
    }

    function isHorizontal(type) {
        return type === 'bar_horizontal';
    }

    function isAreaType(type) {
        return type === 'area' || type === 'area_stacked';
    }

    /* ========================================
       Build Chart.js Config
       ======================================== */
    function buildChartConfig(config, data) {
        var chartJsType = mapChartType(config.type);
        var formatter = NumberFormatter.byFormat(config.number_format);
        var yColumns = config.y_columns || [];
        var yColors = config.y_colors || [];
        var palette = config.color_palette || [];
        // Heatmap rendering
        if (config.type === 'heatmap') {
            return buildHeatmapConfig(config, data, formatter, palette);
        }

        // Scatter/Plot fallback (Chart.js)
        if (config.type === 'plot' || config._chartJsFallbackType === 'scatter') {
            chartJsType = 'scatter';
            var scatterDatasets = yColumns.map(function (col, i) {
                var color = yColors[i] || palette[i % palette.length] || '#3eba6a';
                return {
                    label: col,
                    data: data.map(function (row) {
                        return { x: row[config.axis_x], y: parseFloat(row[col]) || 0 };
                    }),
                    backgroundColor: color,
                    borderColor: color,
                    pointRadius: 5,
                    pointHoverRadius: 7
                };
            });
            return {
                type: 'scatter',
                data: { datasets: scatterDatasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: !!config.show_legend },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': (' + ctx.parsed.x + ', ' + formatter(ctx.parsed.y) + ')';
                                }
                            }
                        }
                    },
                    scales: {
                        x: { title: { display: !!config.title_x, text: config.title_x || '' } },
                        y: {
                            title: { display: !!config.title_y, text: config.title_y || '' },
                            ticks: { callback: function (v) { return formatter(v); } }
                        }
                    }
                }
            };
        }

        var labels = data.map(function (row) { return row[config.axis_x] || ''; });

        var datasets = yColumns.map(function (col, i) {
            var color = yColors[i] || palette[i % palette.length] || '#3eba6a';
            return {
                label: col,
                data: data.map(function (row) { return parseFloat(row[col]) || 0; }),
                backgroundColor: isAreaType(config.type) ? hexToRgba(color, 0.3) : color,
                borderColor: color,
                borderWidth: (chartJsType === 'line') ? 2 : 0,
                fill: isAreaType(config.type),
                tension: 0.3
            };
        });

        // Single series with per-bar colors
        if (datasets.length === 1 && (chartJsType === 'bar' || chartJsType === 'pie' || chartJsType === 'doughnut')) {
            var singleDs = datasets[0];
            if (chartJsType === 'bar') {
                singleDs.backgroundColor = labels.map(function (_, i) {
                    return palette[i % palette.length] || yColors[0] || '#3eba6a';
                });
                singleDs.borderColor = singleDs.backgroundColor;
            }
        }

        var options = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: isHorizontal(config.type) ? 'y' : 'x',
            plugins: {
                legend: { display: !!config.show_legend },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            return ctx.dataset.label + ': ' + formatter(ctx.parsed.y || ctx.parsed.x || ctx.raw);
                        }
                    }
                }
            },
            scales: {}
        };

        // Scales for non-pie/donut/radar types
        if (chartJsType !== 'pie' && chartJsType !== 'doughnut' && chartJsType !== 'radar') {
            options.scales = {
                x: {
                    title: { display: !!config.title_x, text: config.title_x || '', color: '#555' },
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#666' },
                    stacked: isStacked(config.type)
                },
                y: {
                    title: { display: !!config.title_y, text: config.title_y || '', color: '#555' },
                    grid: { color: 'rgba(0,0,0,0.06)' },
                    ticks: {
                        font: { size: 11 },
                        color: '#666',
                        callback: function (value) { return formatter(value); }
                    },
                    stacked: isStacked(config.type)
                }
            };
        }

        return {
            type: chartJsType,
            data: { labels: labels, datasets: datasets },
            options: options
        };
    }

    /* ========================================
       Build Heatmap Config (chartjs-chart-matrix)
       ======================================== */
    function buildHeatmapConfig(config, data, formatter, palette) {
        // data rows: { axis_x: ..., group_col: ..., value: ... }
        if (!data || !data.length) return { type: 'matrix', data: { datasets: [] }, options: {} };

        var axisX = config.axis_x;
        // Detect the group column (second key that is not axis_x and not 'value')
        var keys = Object.keys(data[0]);
        var groupCol = '';
        for (var k = 0; k < keys.length; k++) {
            if (keys[k] !== axisX && keys[k] !== 'value') {
                groupCol = keys[k];
                break;
            }
        }

        // Collect unique X and Y labels
        var xLabelsSet = {};
        var yLabelsSet = {};
        data.forEach(function (row) {
            xLabelsSet[row[axisX]] = true;
            yLabelsSet[row[groupCol]] = true;
        });
        var xLabels = Object.keys(xLabelsSet);
        var yLabels = Object.keys(yLabelsSet);

        // Find min/max values for color scaling
        var values = data.map(function (row) { return parseFloat(row.value) || 0; });
        var minVal = Math.min.apply(null, values);
        var maxVal = Math.max.apply(null, values);
        var range = maxVal - minVal || 1;

        // Build matrix data points
        var matrixData = data.map(function (row) {
            var val = parseFloat(row.value) || 0;
            return {
                x: row[axisX],
                y: row[groupCol],
                v: val
            };
        });

        // Color interpolation: low (light) to high (intense)
        var baseColor = palette[0] || '#1a5276';
        var r0 = parseInt(baseColor.slice(1, 3), 16);
        var g0 = parseInt(baseColor.slice(3, 5), 16);
        var b0 = parseInt(baseColor.slice(5, 7), 16);

        return {
            type: 'matrix',
            data: {
                datasets: [{
                    label: (config.y_columns && config.y_columns[0]) || 'Valor',
                    data: matrixData,
                    backgroundColor: function (ctx) {
                        if (!ctx.raw) return 'rgba(200,200,200,0.3)';
                        var alpha = 0.15 + 0.85 * ((ctx.raw.v - minVal) / range);
                        return 'rgba(' + r0 + ',' + g0 + ',' + b0 + ',' + alpha.toFixed(2) + ')';
                    },
                    borderColor: 'rgba(255,255,255,0.6)',
                    borderWidth: 1,
                    width: function (ctx) {
                        var chart = ctx.chart;
                        var area = chart.chartArea;
                        if (!area) return 20;
                        return Math.max(8, (area.right - area.left) / xLabels.length - 2);
                    },
                    height: function (ctx) {
                        var chart = ctx.chart;
                        var area = chart.chartArea;
                        if (!area) return 20;
                        return Math.max(8, (area.bottom - area.top) / yLabels.length - 2);
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                if (!items.length) return '';
                                var raw = items[0].raw;
                                return raw.x + ' / ' + raw.y;
                            },
                            label: function (ctx) {
                                return 'Valor: ' + formatter(ctx.raw.v);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: xLabels,
                        title: { display: !!config.title_x, text: config.title_x || '', color: '#555' },
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#666' }
                    },
                    y: {
                        type: 'category',
                        labels: yLabels,
                        title: { display: !!config.title_y, text: config.title_y || '', color: '#555' },
                        grid: { display: false },
                        ticks: { font: { size: 10 }, color: '#666' },
                        offset: true
                    }
                }
            }
        };
    }

    function hexToRgba(hex, alpha) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    /* ========================================
       Toolbar Builder
       ======================================== */
    function createToolbar(wrapper, chartInstance, chartData, config) {
        var toolbar = config.toolbar || {};
        if (!toolbar.show) return;

        var container = document.createElement('div');
        container.className = 'bpid-chart-toolbar';
        container.setAttribute('role', 'toolbar');
        container.setAttribute('aria-label', 'Herramientas del gráfico');

        var uid = wrapper.id || 'bpid-chart-' + Date.now();

        // Detalle
        if (toolbar.info) {
            container.appendChild(makeToolbarBtn('info', 'Detalle',
                '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
                function () { showInfoModal(uid, config); }
            ));
        }

        // Compartir
        if (toolbar.share) {
            container.appendChild(makeToolbarBtn('share', 'Compartir',
                '<svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
                function () {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(window.location.href);
                    }
                    showToast('Enlace copiado al portapapeles');
                }
            ));
        }

        // Datos
        if (toolbar.data) {
            var dataTableId = uid + '-data-table';
            container.appendChild(makeToolbarBtn('data', 'Datos',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>',
                function () { toggleDataTable(wrapper, dataTableId, chartData, config); }
            ));
        }

        // Imagen
        if (toolbar.save_img) {
            container.appendChild(makeToolbarBtn('image', 'Imagen',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
                function () { downloadChartImage(chartInstance, config); }
            ));
        }

        // CSV
        if (toolbar.csv) {
            container.appendChild(makeToolbarBtn('csv', 'Descarga',
                '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
                function () { downloadCSV(chartData, config); }
            ));
        }

        wrapper.insertBefore(container, wrapper.firstChild);
    }

    function makeToolbarBtn(action, label, iconSvg, onClick) {
        var btn = document.createElement('button');
        btn.className = 'sct-btn';
        btn.setAttribute('data-action', action);
        btn.title = label;
        btn.innerHTML = iconSvg + ' ' + label;
        btn.addEventListener('click', onClick);
        return btn;
    }

    /* ========================================
       Toolbar Actions
       ======================================== */
    function showInfoModal(uid, config) {
        var existing = document.getElementById(uid + '-info-modal');
        if (existing) {
            existing.remove();
            return;
        }
        var modal = document.createElement('div');
        modal.id = uid + '-info-modal';
        modal.className = 'bpid-chart-data-modal';
        modal.innerHTML = '<div class="bpid-chart-data-modal-content">' +
            '<div class="bpid-chart-data-modal-header">' +
            '<h3>Detalle del Gráfico</h3>' +
            '<button class="bpid-chart-data-modal-close">&times;</button>' +
            '</div>' +
            '<div class="bpid-chart-data-modal-body" style="padding:20px;">' +
            '<p><strong>Título:</strong> ' + escapeHtml(config.title || 'Sin título') + '</p>' +
            '<p><strong>Tipo:</strong> ' + escapeHtml(config.type || '') + '</p>' +
            '<p><strong>Fuente:</strong> ' + escapeHtml(config.table || '') + '</p>' +
            '<p><strong>Eje X:</strong> ' + escapeHtml(config.axis_x || '') + '</p>' +
            '<p><strong>Columnas Y:</strong> ' + escapeHtml((config.y_columns || []).join(', ')) + '</p>' +
            '</div></div>';
        document.body.appendChild(modal);
        requestAnimationFrame(function () { modal.classList.add('is-open'); });
        modal.querySelector('.bpid-chart-data-modal-close').addEventListener('click', function () {
            modal.classList.remove('is-open');
            setTimeout(function () { modal.remove(); }, 200);
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.classList.remove('is-open');
                setTimeout(function () { modal.remove(); }, 200);
            }
        });
    }

    function toggleDataTable(wrapper, tableId, chartData, config) {
        var existing = document.getElementById(tableId);
        if (existing) {
            existing.hidden = !existing.hidden;
            return;
        }
        var table = document.createElement('table');
        table.id = tableId;
        table.className = 'bpid-chart-inline-data-table';
        var formatter = NumberFormatter.byFormat(config.number_format);
        var headers = [config.axis_x].concat(config.y_columns || []);
        var thead = '<thead><tr>';
        headers.forEach(function (h) { thead += '<th>' + escapeHtml(h) + '</th>'; });
        thead += '</tr></thead>';
        var tbody = '<tbody>';
        chartData.forEach(function (row) {
            tbody += '<tr>';
            headers.forEach(function (h, i) {
                var val = row[h];
                if (i > 0 && typeof val === 'number') val = formatter(val);
                tbody += '<td>' + escapeHtml(String(val != null ? val : '')) + '</td>';
            });
            tbody += '</tr>';
        });
        tbody += '</tbody>';
        table.innerHTML = thead + tbody;
        wrapper.appendChild(table);
    }

    function downloadChartImage(chartInstance, config) {
        if (!chartInstance) return;
        var link = document.createElement('a');
        link.download = slugify(config.title || 'grafico') + '.png';
        link.href = chartInstance.toBase64Image('image/png', 1.0);
        link.click();
    }

    function downloadCSV(chartData, config) {
        var headers = [config.axis_x].concat(config.y_columns || []);
        var rows = chartData.map(function (row) {
            return headers.map(function (h) {
                return JSON.stringify(row[h] != null ? row[h] : '');
            }).join(',');
        });
        var bom = '\uFEFF';
        var csv = bom + [headers.join(',')].concat(rows).join('\r\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = slugify(config.title || 'datos') + '-datos.csv';
        link.click();
        URL.revokeObjectURL(url);
    }

    /* ========================================
       Toast
       ======================================== */
    function showToast(message, duration) {
        duration = duration || 2500;
        var toast = document.createElement('div');
        toast.className = 'bpid-chart-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('visible'); });
        setTimeout(function () {
            toast.classList.remove('visible');
            setTimeout(function () { toast.remove(); }, 300);
        }, duration);
    }

    /* ========================================
       Utilities
       ======================================== */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    /* ========================================
       D3plus Chart Rendering
       ======================================== */

    /**
     * Build and render a d3plus chart with the correct data mapping.
     * D3plus uses: groupBy (category), x, y, size, column, row.
     */
    function renderD3plusChart(renderDivId, chartType, chartData, config) {
        if (typeof d3plus === 'undefined') return null;

        var axisX = config.axis_x || '';
        var yCol = (config.y_columns && config.y_columns[0]) || '';
        var palette = config.color_palette || ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c'];

        // Transform data: ensure numeric Y values
        var data = chartData.map(function(row) {
            var d = {};
            for (var k in row) {
                if (row.hasOwnProperty(k)) d[k] = row[k];
            }
            if (yCol && d[yCol] !== undefined) {
                d[yCol] = parseFloat(d[yCol]) || 0;
            }
            return d;
        });

        var selector = '#' + renderDivId;

        try {
            if (chartType === 'treemap') {
                new d3plus.Treemap()
                    .select(selector)
                    .data(data)
                    .groupBy(axisX)
                    .sum(yCol)
                    .shapeConfig({ label: function(d) { return d[axisX] || ''; } })
                    .tooltipConfig({
                        body: function(d) {
                            var val = d[yCol];
                            return yCol + ': ' + NumberFormatter.formatCOP(val);
                        }
                    })
                    .render();
                return true;

            } else if (chartType === 'heatmap') {
                // D3plus Matrix: rows vs columns with color intensity
                var groupCol = '';
                var keys = Object.keys(data[0] || {});
                for (var k = 0; k < keys.length; k++) {
                    if (keys[k] !== axisX && keys[k] !== yCol && keys[k] !== 'value') {
                        groupCol = keys[k];
                        break;
                    }
                }
                var valueKey = yCol || 'value';

                new d3plus.Matrix()
                    .select(selector)
                    .data(data)
                    .column(axisX)
                    .row(groupCol || axisX)
                    .colorScale(valueKey)
                    .colorScaleConfig({ color: ['#f7fbff', '#08306b'] })
                    .render();
                return true;

            } else if (chartType === 'plot') {
                new d3plus.Plot()
                    .select(selector)
                    .data(data)
                    .groupBy(axisX)
                    .x(axisX)
                    .y(yCol)
                    .shape('Circle')
                    .shapeConfig({
                        Circle: { r: 6 }
                    })
                    .render();
                return true;

            } else {
                // Generic fallback for other d3plus types
                var factory = {
                    'tree': function() { return new d3plus.Tree(); },
                    'pack': function() { return new d3plus.Pack(); },
                    'network': function() { return new d3plus.Network(); },
                    'bump': function() { return new d3plus.BumpChart(); }
                };
                var create = factory[chartType];
                if (!create) return null;

                var chart = create();
                chart.select(selector).data(data);
                if (axisX) chart.groupBy(axisX);
                if (yCol) chart.size(yCol);
                chart.render();
                return true;
            }
        } catch (e) {
            console.error('[BPID Suite] D3plus render error for ' + chartType + ':', e);
            return null;
        }
    }

    /* ========================================
       ChartManager Class
       ======================================== */
    function ChartManager() {
        this.charts = [];
        this.observer = null;
        this.resizeTimer = null;
        this.init();
    }

    ChartManager.prototype.init = function () {
        var self = this;
        var containers = document.querySelectorAll('.bpid-chart-container');
        if (!containers.length) return;

        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        self.loadChart(entry.target);
                        self.observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '100px' });

            containers.forEach(function (c) {
                if (!c.querySelector('canvas') && !c.querySelector('.bpid-chart-wrapper')) {
                    c.innerHTML = '<div class="bpid-chart-loading"></div>';
                }
                self.observer.observe(c);
            });
        } else {
            containers.forEach(function (c) { self.loadChart(c); });
        }

        // Responsive resize
        window.addEventListener('resize', function () {
            clearTimeout(self.resizeTimer);
            self.resizeTimer = setTimeout(function () {
                self.charts.forEach(function (entry) {
                    if (entry.instance && typeof entry.instance.resize === 'function') {
                        entry.instance.resize();
                    }
                });
            }, 300);
        });

        // ESC fullscreen
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var fs = document.querySelector('.bpid-chart-fullscreen');
                if (fs) {
                    fs.classList.remove('bpid-chart-fullscreen');
                    document.body.style.overflow = '';
                }
            }
        });
    };

    ChartManager.prototype.loadChart = function (container) {
        var chartId = container.dataset.chartId;

        // Get config from JSON script tag
        var configEl = document.getElementById('bpid-chart-config-' + chartId);
        var dataEl = document.getElementById('bpid-chart-data-' + chartId);

        // Fallback to legacy data format
        if (!configEl && !dataEl) {
            dataEl = document.getElementById('bpid-chart-' + chartId + '-data');
        }

        if (!dataEl) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles</div>';
            return;
        }

        var chartData, config;
        try {
            chartData = JSON.parse(dataEl.textContent);
            config = configEl ? JSON.parse(configEl.textContent) : this.buildLegacyConfig(container);
        } catch (e) {
            console.error('[BPID Suite] Error parsing chart data:', e);
            container.innerHTML = '<div class="bpid-chart-no-data">Error al cargar datos</div>';
            return;
        }

        if (!chartData || !chartData.length) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles para esta gráfica</div>';
            return;
        }

        // D3plus-only types: treemap, heatmap (matrix), plot, and legacy types
        var d3plusTypes = ['treemap', 'plot', 'tree', 'pack', 'network', 'bump'];
        var isD3plusType = d3plusTypes.indexOf(config.type) !== -1;

        // Heatmap: prefer d3plus Matrix if available, fallback to chartjs-chart-matrix
        if (config.type === 'heatmap') {
            if (typeof d3plus !== 'undefined') {
                this.renderD3plus(container, chartData, config, chartId);
            } else if (typeof Chart !== 'undefined') {
                this.renderChartJs(container, chartData, config, chartId);
            } else {
                container.innerHTML = '<div class="bpid-chart-no-data">Librería de gráficos no disponible</div>';
            }
        } else if (isD3plusType && typeof d3plus !== 'undefined') {
            this.renderD3plus(container, chartData, config, chartId);
        } else if (typeof Chart !== 'undefined') {
            // Scatter fallback for 'plot' when d3plus not available
            if (config.type === 'plot') config._chartJsFallbackType = 'scatter';
            this.renderChartJs(container, chartData, config, chartId);
        } else {
            container.innerHTML = '<div class="bpid-chart-no-data">Librería de gráficos no disponible</div>';
        }
    };

    ChartManager.prototype.renderChartJs = function (container, chartData, config, chartId) {
        container.innerHTML = '';

        // Create wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'bpid-chart-wrapper';
        wrapper.id = 'bpid-chart-wrapper-' + chartId;

        var canvasContainer = document.createElement('div');
        canvasContainer.className = 'bpid-chart-canvas-container';
        canvasContainer.style.height = (config.height || 400) + 'px';

        var canvas = document.createElement('canvas');
        canvas.id = 'bpid-canvas-' + chartId;
        canvasContainer.appendChild(canvas);
        wrapper.appendChild(canvasContainer);
        container.appendChild(wrapper);

        // Build config and render
        try {
            var chartJsConfig = buildChartConfig(config, chartData);
            var instance = new Chart(canvas.getContext('2d'), chartJsConfig);

            // Add toolbar
            createToolbar(wrapper, instance, chartData, config);

            this.charts.push({
                id: chartId,
                container: container,
                instance: instance,
                data: chartData,
                config: config
            });
        } catch (e) {
            console.error('[BPID Suite] Error rendering Chart.js:', e);
            container.innerHTML = '<div class="bpid-chart-no-data" style="color:var(--bpid-color-danger,#c00);">Error al renderizar la gráfica</div>';
        }
    };

    ChartManager.prototype.renderD3plus = function (container, chartData, config, chartId) {
        container.innerHTML = '';
        var renderDiv = document.createElement('div');
        renderDiv.id = 'bpid-chart-render-' + chartId;
        renderDiv.style.width = '100%';
        renderDiv.style.height = (config.height || 400) + 'px';
        container.appendChild(renderDiv);

        var result = renderD3plusChart(renderDiv.id, config.type, chartData, config);

        if (!result) {
            container.innerHTML = '<div class="bpid-chart-no-data">Tipo de gráfico no soportado o error al renderizar</div>';
            return;
        }

        // Create wrapper for toolbar
        var wrapper = document.createElement('div');
        wrapper.className = 'bpid-chart-wrapper';
        wrapper.id = 'bpid-chart-wrapper-' + chartId;
        container.insertBefore(wrapper, container.firstChild);
        wrapper.appendChild(renderDiv);
        createToolbar(wrapper, null, chartData, config);

        this.charts.push({
            id: chartId,
            container: container,
            instance: null,
            data: chartData,
            config: config
        });
    };

    ChartManager.prototype.buildLegacyConfig = function (container) {
        return {
            type: container.dataset.chartType || 'bar',
            axis_x: container.dataset.columnX || 'x',
            y_columns: container.dataset.columnY ? [container.dataset.columnY] : ['y'],
            y_colors: container.dataset.color ? [container.dataset.color] : ['#3eba6a'],
            color_palette: ['#3eba6a', '#e84c4c', '#4a90d9', '#f5a623', '#9b59b6', '#1abc9c'],
            height: parseInt(container.style.height) || 400,
            number_format: 'es-CO',
            show_legend: false,
            toolbar: { show: true, info: false, share: false, data: true, save_img: true, csv: false },
            title: '',
            title_y: '',
            title_x: ''
        };
    };

    /* ========================================
       Initialize on DOM ready
       ======================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { new ChartManager(); });
    } else {
        new ChartManager();
    }

})();

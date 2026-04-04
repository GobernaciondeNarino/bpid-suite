/**
 * BPID Suite — Frontend Chart Manager v3.0.0
 * Gobernación de Nariño
 *
 * Single rendering engine: Chart.js + plugins.
 * Plugins: chartjs-chart-treemap (treemap), chartjs-chart-matrix (heatmap).
 *
 * Supported types (13):
 *   bar, bar_horizontal, bar_stacked, bar_grouped, line, area, area_stacked,
 *   pie, donut, treemap, radar, heatmap, plot
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
       Helpers
       ======================================== */
    function hexToRgba(hex, alpha) {
        if (!hex || hex.length < 7) return 'rgba(100,100,100,' + alpha + ')';
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-').replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-').replace(/^-+|-+$/g, '');
    }

    function isStacked(type) { return type === 'bar_stacked' || type === 'area_stacked'; }
    function isHorizontal(type) { return type === 'bar_horizontal'; }
    function isAreaType(type) { return type === 'area' || type === 'area_stacked'; }

    /* ========================================
       Chart.js Type Mapping
       ======================================== */
    function mapChartType(internal) {
        var map = {
            bar: 'bar', bar_horizontal: 'bar', bar_stacked: 'bar', bar_grouped: 'bar',
            line: 'line', area: 'line', area_stacked: 'line',
            pie: 'pie', donut: 'doughnut', radar: 'radar',
            heatmap: 'matrix', treemap: 'treemap', plot: 'scatter'
        };
        return map[internal] || 'bar';
    }

    /* ========================================
       Build standard Chart.js Config
       ======================================== */
    function buildChartConfig(config, data) {
        var formatter = NumberFormatter.byFormat(config.number_format);
        var yColumns = config.y_columns || [];
        var yColors = config.y_colors || [];
        var palette = config.color_palette || ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c','#844c00','#ff7300'];

        // --- Treemap (chartjs-chart-treemap plugin) ---
        if (config.type === 'treemap') {
            return buildTreemapConfig(config, data, formatter, palette);
        }

        // --- Heatmap (chartjs-chart-matrix plugin) ---
        if (config.type === 'heatmap') {
            return buildHeatmapConfig(config, data, formatter, palette);
        }

        // --- Scatter / Plot ---
        if (config.type === 'plot') {
            return buildScatterConfig(config, data, formatter, yColumns, yColors, palette);
        }

        // --- Standard types ---
        var chartJsType = mapChartType(config.type);
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

        // Single series per-bar/slice colors
        if (datasets.length === 1 && (chartJsType === 'bar' || chartJsType === 'pie' || chartJsType === 'doughnut')) {
            if (chartJsType === 'bar') {
                datasets[0].backgroundColor = labels.map(function (_, i) {
                    return palette[i % palette.length] || yColors[0] || '#3eba6a';
                });
                datasets[0].borderColor = datasets[0].backgroundColor;
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
                            var val = ctx.parsed.y != null ? ctx.parsed.y : (ctx.parsed.x != null ? ctx.parsed.x : ctx.raw);
                            return ctx.dataset.label + ': ' + formatter(val);
                        }
                    }
                }
            },
            scales: {}
        };

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
                    ticks: { font: { size: 11 }, color: '#666', callback: function (v) { return formatter(v); } },
                    stacked: isStacked(config.type)
                }
            };
        }

        return { type: chartJsType, data: { labels: labels, datasets: datasets }, options: options };
    }

    /* ========================================
       Treemap Config (chartjs-chart-treemap)
       ======================================== */
    function buildTreemapConfig(config, data, formatter, palette) {
        var axisX = config.axis_x || '';
        var yCol = (config.y_columns && config.y_columns[0]) || '';

        // Build tree data: array of objects with group + value
        var tree = data.map(function (row) {
            return {
                category: String(row[axisX] || ''),
                value: parseFloat(row[yCol]) || 0
            };
        }).filter(function (d) { return d.value > 0; });

        return {
            type: 'treemap',
            data: {
                datasets: [{
                    tree: tree,
                    key: 'value',
                    groups: ['category'],
                    borderColor: 'rgba(255,255,255,0.8)',
                    borderWidth: 2,
                    spacing: 1,
                    backgroundColor: function (ctx) {
                        if (!ctx.raw || !ctx.raw._data) return palette[0] || '#3eba6a';
                        var i = ctx.dataIndex || 0;
                        return palette[i % palette.length] || '#3eba6a';
                    },
                    labels: {
                        display: true,
                        align: 'center',
                        position: 'middle',
                        color: '#fff',
                        font: { size: 12, weight: 'bold' },
                        formatter: function (ctx) {
                            if (ctx && ctx.raw && ctx.raw._data) {
                                return ctx.raw._data.category + '\n' + formatter(ctx.raw._data.value);
                            }
                            return '';
                        }
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
                                if (!items.length || !items[0].raw || !items[0].raw._data) return '';
                                return items[0].raw._data.category;
                            },
                            label: function (ctx) {
                                if (!ctx.raw || !ctx.raw._data) return '';
                                return yCol + ': ' + formatter(ctx.raw._data.value);
                            }
                        }
                    }
                }
            }
        };
    }

    /* ========================================
       Heatmap Config (chartjs-chart-matrix)
       ======================================== */
    function buildHeatmapConfig(config, data, formatter, palette) {
        if (!data || !data.length) return { type: 'matrix', data: { datasets: [] }, options: {} };

        var axisX = config.axis_x;
        // Detect the group column (key that is not axis_x and not 'value')
        var keys = Object.keys(data[0]);
        var groupCol = '';
        for (var k = 0; k < keys.length; k++) {
            if (keys[k] !== axisX && keys[k] !== 'value') {
                groupCol = keys[k];
                break;
            }
        }

        // Collect unique labels
        var xLabelsSet = {}, yLabelsSet = {};
        data.forEach(function (row) {
            xLabelsSet[row[axisX]] = true;
            yLabelsSet[row[groupCol]] = true;
        });
        var xLabels = Object.keys(xLabelsSet);
        var yLabels = Object.keys(yLabelsSet);

        // Value range for color scaling
        var values = data.map(function (r) { return parseFloat(r.value) || 0; });
        var minVal = Math.min.apply(null, values);
        var maxVal = Math.max.apply(null, values);
        var range = maxVal - minVal || 1;

        var matrixData = data.map(function (row) {
            return { x: row[axisX], y: row[groupCol], v: parseFloat(row.value) || 0 };
        });

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
                        var a = ctx.chart.chartArea;
                        return a ? Math.max(8, (a.right - a.left) / xLabels.length - 2) : 20;
                    },
                    height: function (ctx) {
                        var a = ctx.chart.chartArea;
                        return a ? Math.max(8, (a.bottom - a.top) / yLabels.length - 2) : 20;
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
                                return items[0].raw.x + ' / ' + items[0].raw.y;
                            },
                            label: function (ctx) { return 'Valor: ' + formatter(ctx.raw.v); }
                        }
                    }
                },
                scales: {
                    x: { type: 'category', labels: xLabels, grid: { display: false }, ticks: { font: { size: 10 } },
                         title: { display: !!config.title_x, text: config.title_x || '' } },
                    y: { type: 'category', labels: yLabels, grid: { display: false }, ticks: { font: { size: 10 } },
                         offset: true, title: { display: !!config.title_y, text: config.title_y || '' } }
                }
            }
        };
    }

    /* ========================================
       Scatter / Plot Config
       ======================================== */
    function buildScatterConfig(config, data, formatter, yColumns, yColors, palette) {
        var datasets = yColumns.map(function (col, i) {
            var color = yColors[i] || palette[i % palette.length] || '#3eba6a';
            return {
                label: col,
                data: data.map(function (row) {
                    return { x: row[config.axis_x], y: parseFloat(row[col]) || 0 };
                }),
                backgroundColor: hexToRgba(color, 0.6),
                borderColor: color,
                pointRadius: 6,
                pointHoverRadius: 9,
                pointBorderWidth: 2
            };
        });

        return {
            type: 'scatter',
            data: { datasets: datasets },
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
                    x: { title: { display: !!config.title_x, text: config.title_x || '' }, grid: { color: 'rgba(0,0,0,0.06)' } },
                    y: { title: { display: !!config.title_y, text: config.title_y || '' }, grid: { color: 'rgba(0,0,0,0.06)' },
                         ticks: { callback: function (v) { return formatter(v); } } }
                }
            }
        };
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

        var uid = wrapper.id || 'bpid-chart-' + Date.now();

        if (toolbar.info) {
            container.appendChild(makeToolbarBtn('info', 'Detalle',
                '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
                function () { showInfoModal(uid, config); }
            ));
        }
        if (toolbar.share) {
            container.appendChild(makeToolbarBtn('share', 'Compartir',
                '<svg viewBox="0 0 24 24"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
                function () { if (navigator.clipboard) navigator.clipboard.writeText(window.location.href); showToast('Enlace copiado'); }
            ));
        }
        if (toolbar.data) {
            container.appendChild(makeToolbarBtn('data', 'Datos',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>',
                function () { toggleDataTable(wrapper, uid + '-dt', chartData, config); }
            ));
        }
        if (toolbar.save_img && chartInstance) {
            container.appendChild(makeToolbarBtn('image', 'Imagen',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
                function () {
                    var link = document.createElement('a');
                    link.download = slugify(config.title || 'grafico') + '.png';
                    link.href = chartInstance.toBase64Image('image/png', 1.0);
                    link.click();
                }
            ));
        }
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
        var existing = document.getElementById(uid + '-info');
        if (existing) { existing.remove(); return; }
        var modal = document.createElement('div');
        modal.id = uid + '-info';
        modal.className = 'bpid-chart-data-modal';
        modal.innerHTML = '<div class="bpid-chart-data-modal-content">' +
            '<div class="bpid-chart-data-modal-header"><h3>Detalle</h3>' +
            '<button class="bpid-chart-data-modal-close">&times;</button></div>' +
            '<div class="bpid-chart-data-modal-body" style="padding:20px;">' +
            '<p><strong>Título:</strong> ' + escapeHtml(config.title || '') + '</p>' +
            '<p><strong>Tipo:</strong> ' + escapeHtml(config.type || '') + '</p>' +
            '<p><strong>Fuente:</strong> ' + escapeHtml(config.table || '') + '</p>' +
            '<p><strong>Eje X:</strong> ' + escapeHtml(config.axis_x || '') + '</p>' +
            '<p><strong>Columnas Y:</strong> ' + escapeHtml((config.y_columns || []).join(', ')) + '</p>' +
            '</div></div>';
        document.body.appendChild(modal);
        requestAnimationFrame(function () { modal.classList.add('is-open'); });
        var close = function () { modal.classList.remove('is-open'); setTimeout(function () { modal.remove(); }, 200); };
        modal.querySelector('.bpid-chart-data-modal-close').addEventListener('click', close);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    }

    function toggleDataTable(wrapper, tableId, chartData, config) {
        var existing = document.getElementById(tableId);
        if (existing) { existing.hidden = !existing.hidden; return; }
        var table = document.createElement('table');
        table.id = tableId;
        table.className = 'bpid-chart-inline-data-table';
        var fmt = NumberFormatter.byFormat(config.number_format);
        var headers = [config.axis_x].concat(config.y_columns || []);
        var html = '<thead><tr>';
        headers.forEach(function (h) { html += '<th>' + escapeHtml(h) + '</th>'; });
        html += '</tr></thead><tbody>';
        chartData.forEach(function (row) {
            html += '<tr>';
            headers.forEach(function (h, i) {
                var val = row[h];
                if (i > 0 && !isNaN(val)) val = fmt(val);
                html += '<td>' + escapeHtml(String(val != null ? val : '')) + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody>';
        table.innerHTML = html;
        wrapper.appendChild(table);
    }

    function downloadCSV(chartData, config) {
        var headers = [config.axis_x].concat(config.y_columns || []);
        var rows = chartData.map(function (row) {
            return headers.map(function (h) { return JSON.stringify(row[h] != null ? row[h] : ''); }).join(',');
        });
        var csv = '\uFEFF' + [headers.join(',')].concat(rows).join('\r\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = slugify(config.title || 'datos') + '.csv';
        link.click();
        URL.revokeObjectURL(url);
    }

    function showToast(message) {
        var toast = document.createElement('div');
        toast.className = 'bpid-chart-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('visible'); });
        setTimeout(function () { toast.classList.remove('visible'); setTimeout(function () { toast.remove(); }, 300); }, 2500);
    }

    /* ========================================
       ChartManager
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

        window.addEventListener('resize', function () {
            clearTimeout(self.resizeTimer);
            self.resizeTimer = setTimeout(function () {
                self.charts.forEach(function (entry) {
                    if (entry.instance && typeof entry.instance.resize === 'function') entry.instance.resize();
                });
            }, 300);
        });
    };

    ChartManager.prototype.loadChart = function (container) {
        var chartId = container.dataset.chartId;
        var configEl = document.getElementById('bpid-chart-config-' + chartId);
        var dataEl = document.getElementById('bpid-chart-data-' + chartId);
        if (!configEl && !dataEl) dataEl = document.getElementById('bpid-chart-' + chartId + '-data');

        if (!dataEl) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles</div>';
            return;
        }

        var chartData, config;
        try {
            chartData = JSON.parse(dataEl.textContent);
            config = configEl ? JSON.parse(configEl.textContent) : {
                type: container.dataset.chartType || 'bar',
                axis_x: container.dataset.columnX || 'x',
                y_columns: container.dataset.columnY ? [container.dataset.columnY] : ['y'],
                y_colors: ['#3eba6a'], color_palette: ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c'],
                height: 400, number_format: 'es-CO', show_legend: false,
                toolbar: { show: true, info: false, share: false, data: true, save_img: true, csv: false },
                title: '', title_y: '', title_x: ''
            };
        } catch (e) {
            console.error('[BPID Suite] Parse error:', e);
            container.innerHTML = '<div class="bpid-chart-no-data">Error al cargar datos</div>';
            return;
        }

        if (!chartData || !chartData.length) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles para esta gráfica</div>';
            return;
        }

        if (typeof Chart === 'undefined') {
            container.innerHTML = '<div class="bpid-chart-no-data">Chart.js no disponible</div>';
            return;
        }

        this.renderChart(container, chartData, config, chartId);
    };

    ChartManager.prototype.renderChart = function (container, chartData, config, chartId) {
        container.innerHTML = '';

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

        try {
            var chartJsConfig = buildChartConfig(config, chartData);
            var instance = new Chart(canvas.getContext('2d'), chartJsConfig);

            createToolbar(wrapper, instance, chartData, config);

            this.charts.push({
                id: chartId, container: container, instance: instance,
                data: chartData, config: config
            });
        } catch (e) {
            console.error('[BPID Suite] Render error (' + config.type + '):', e);
            container.innerHTML = '<div class="bpid-chart-no-data" style="color:#c00;">Error al renderizar: ' + escapeHtml(e.message) + '</div>';
        }
    };

    /* ========================================
       Initialize
       ======================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { new ChartManager(); });
    } else {
        new ChartManager();
    }

})();

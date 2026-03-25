/**
 * BPID Suite — Frontend Chart Manager v1.3.0
 * Gobernación de Nariño
 *
 * Handles D3plus chart rendering with:
 * - ChartManager class for lifecycle management
 * - IntersectionObserver lazy loading
 * - Colombian number formatting
 * - Chart toolbar (fullscreen, download image, view data)
 * - Responsive resize handling
 */
(function () {
    'use strict';

    /* ========================================
       Number Formatter (Colombian locale)
       ======================================== */
    var NumberFormatter = {
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
        }
    };

    /* ========================================
       D3plus Chart Factory
       ======================================== */
    function createChart(chartType) {
        if (typeof d3plus === 'undefined') {
            console.error('[BPID Suite] D3plus no está disponible');
            return null;
        }

        var map = {
            'bar':         function () { return new d3plus.BarChart(); },
            'line':        function () { return new d3plus.LinePlot(); },
            'area':        function () { return new d3plus.AreaPlot(); },
            'pie':         function () { return new d3plus.Pie(); },
            'donut':       function () { return new d3plus.Donut(); },
            'treemap':     function () { return new d3plus.Treemap(); },
            'stacked_bar': function () { return new d3plus.BarChart().stacked(true); },
            'grouped_bar': function () { return new d3plus.BarChart().stacked(false); },
            'tree':        function () { return new d3plus.Tree(); },
            'pack':        function () { return new d3plus.Pack(); },
            'network':     function () { return new d3plus.Network(); },
            'scatter':     function () { return new d3plus.Plot(); },
            'box_whisker': function () { return new d3plus.BoxWhisker(); },
            'matrix':      function () { return new d3plus.Matrix(); },
            'bump':        function () { return new d3plus.BumpChart(); }
        };

        var factory = map[chartType];
        if (!factory) {
            console.warn('[BPID Suite] Tipo de gráfico no reconocido:', chartType);
            return new d3plus.BarChart();
        }

        try {
            return factory();
        } catch (e) {
            console.error('[BPID Suite] Error instanciando gráfico:', chartType, e);
            return new d3plus.BarChart();
        }
    }

    /* ========================================
       Chart Toolbar
       ======================================== */
    function createToolbar(container, chartInstance, chartData) {
        var toolbar = document.createElement('div');
        toolbar.className = 'bpid-chart-toolbar';

        // Fullscreen button
        var fullscreenBtn = document.createElement('button');
        fullscreenBtn.className = 'bpid-chart-toolbar-btn';
        fullscreenBtn.title = 'Pantalla completa';
        fullscreenBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>';
        fullscreenBtn.addEventListener('click', function () {
            toggleFullscreen(container);
        });
        toolbar.appendChild(fullscreenBtn);

        // Download image button
        var downloadBtn = document.createElement('button');
        downloadBtn.className = 'bpid-chart-toolbar-btn';
        downloadBtn.title = 'Descargar imagen';
        downloadBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
        downloadBtn.addEventListener('click', function () {
            downloadChartImage(container);
        });
        toolbar.appendChild(downloadBtn);

        // View data button
        var dataBtn = document.createElement('button');
        dataBtn.className = 'bpid-chart-toolbar-btn';
        dataBtn.title = 'Ver datos';
        dataBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>';
        dataBtn.addEventListener('click', function () {
            showDataModal(container, chartData);
        });
        toolbar.appendChild(dataBtn);

        container.insertBefore(toolbar, container.firstChild);
    }

    function toggleFullscreen(container) {
        if (container.classList.contains('bpid-chart-fullscreen')) {
            container.classList.remove('bpid-chart-fullscreen');
            document.body.style.overflow = '';
        } else {
            container.classList.add('bpid-chart-fullscreen');
            document.body.style.overflow = 'hidden';
        }
    }

    function downloadChartImage(container) {
        var svg = container.querySelector('svg.d3plus-viz');
        if (!svg) {
            svg = container.querySelector('svg');
        }
        if (!svg) return;

        var clone = svg.cloneNode(true);
        clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        var svgData = new XMLSerializer().serializeToString(clone);
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var img = new Image();
        var svgBlob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
        var url = URL.createObjectURL(svgBlob);

        img.onload = function () {
            canvas.width = img.width * 2;
            canvas.height = img.height * 2;
            ctx.scale(2, 2);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, img.width, img.height);
            ctx.drawImage(img, 0, 0);
            URL.revokeObjectURL(url);

            var a = document.createElement('a');
            a.download = 'bpid-grafico.png';
            a.href = canvas.toDataURL('image/png');
            a.click();
        };
        img.src = url;
    }

    function showDataModal(container, chartData) {
        // Remove existing modal
        var existing = document.getElementById('bpid-data-modal');
        if (existing) existing.remove();

        var modal = document.createElement('div');
        modal.id = 'bpid-data-modal';
        modal.className = 'bpid-chart-data-modal';

        var content = '<div class="bpid-chart-data-modal-content">';
        content += '<div class="bpid-chart-data-modal-header">';
        content += '<h3>Datos del gráfico</h3>';
        content += '<button class="bpid-chart-data-modal-close" id="bpid-data-modal-close">&times;</button>';
        content += '</div>';
        content += '<div class="bpid-chart-data-modal-body">';
        content += '<table class="bpid-chart-data-table">';

        if (chartData && chartData.length > 0) {
            var keys = Object.keys(chartData[0]);
            content += '<thead><tr>';
            keys.forEach(function (k) {
                content += '<th>' + escapeHtml(k) + '</th>';
            });
            content += '</tr></thead><tbody>';

            chartData.forEach(function (row) {
                content += '<tr>';
                keys.forEach(function (k) {
                    var val = row[k];
                    if (typeof val === 'number') {
                        val = NumberFormatter.number(val);
                    }
                    content += '<td>' + escapeHtml(String(val != null ? val : '')) + '</td>';
                });
                content += '</tr>';
            });
            content += '</tbody>';
        }

        content += '</table></div></div>';
        modal.innerHTML = content;
        document.body.appendChild(modal);

        // Show with animation
        requestAnimationFrame(function () {
            modal.classList.add('is-open');
        });

        // Close handlers
        document.getElementById('bpid-data-modal-close').addEventListener('click', function () {
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

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
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

        // Set up IntersectionObserver for lazy loading
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
                // Add loading indicator
                c.innerHTML = '<div class="bpid-chart-loading"></div>';
                self.observer.observe(c);
            });
        } else {
            // Fallback: load all immediately
            containers.forEach(function (c) {
                self.loadChart(c);
            });
        }

        // Responsive resize
        window.addEventListener('resize', function () {
            clearTimeout(self.resizeTimer);
            self.resizeTimer = setTimeout(function () {
                self.charts.forEach(function (entry) {
                    if (entry.instance && typeof entry.instance.render === 'function') {
                        try {
                            entry.instance.render();
                        } catch (e) {
                            // Ignore resize render errors
                        }
                    }
                });
            }, 300);
        });

        // ESC to exit fullscreen
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
        var chartType = container.dataset.chartType;
        var columnX = container.dataset.columnX;
        var columnY = container.dataset.columnY;
        var group = container.dataset.group;
        var color = container.dataset.color;
        var aggregation = container.dataset.aggregation;

        // Get data from JSON script tag
        var dataEl = document.getElementById('bpid-chart-' + chartId + '-data');
        if (!dataEl) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles</div>';
            return;
        }

        var chartData;
        try {
            chartData = JSON.parse(dataEl.textContent);
        } catch (e) {
            console.error('[BPID Suite] Error parsing chart data:', e);
            container.innerHTML = '<div class="bpid-chart-no-data">Error al cargar datos</div>';
            return;
        }

        if (!chartData || !chartData.length) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles para esta gráfica</div>';
            return;
        }

        // Clear loading indicator
        container.innerHTML = '';

        // Create chart render target
        var renderDiv = document.createElement('div');
        renderDiv.id = 'bpid-chart-render-' + chartId;
        renderDiv.style.width = '100%';
        renderDiv.style.height = '100%';
        container.appendChild(renderDiv);

        // Render chart
        try {
            var chart = createChart(chartType);
            if (!chart) return;

            chart
                .select('#' + renderDiv.id)
                .data(chartData);

            // Configure axes
            if (columnX) chart.x('x');
            if (columnY) chart.y('y');
            if (group) chart.groupBy('group');

            // Color configuration
            if (color) {
                chart.shapeConfig({ fill: color });
            }

            // Tooltip with Colombian formatting
            chart.tooltipConfig({
                title: function (d) {
                    return d.x || d.group || '';
                },
                body: function (d) {
                    var parts = [];
                    if (d.y != null) {
                        if (aggregation === 'sum' || columnY === 'valor') {
                            parts.push('Valor: ' + NumberFormatter.currency(d.y));
                        } else if (columnY === 'avance_fisico') {
                            parts.push('Avance: ' + NumberFormatter.percent(d.y));
                        } else {
                            parts.push('Valor: ' + NumberFormatter.number(d.y));
                        }
                    }
                    if (d.group) {
                        parts.push('Grupo: ' + d.group);
                    }
                    return parts.join('<br>');
                }
            });

            chart.render();

            // Add toolbar after render
            createToolbar(container, chart, chartData);

            // Store reference
            this.charts.push({
                id: chartId,
                container: container,
                instance: chart,
                data: chartData
            });

        } catch (e) {
            console.error('[BPID Suite] Error rendering chart:', chartType, e);
            container.innerHTML = '<div class="bpid-chart-no-data" style="color:var(--bpid-color-danger,#c00);">Error al renderizar la gráfica</div>';
        }
    };

    /* ========================================
       Initialize on DOM ready
       ======================================== */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            new ChartManager();
        });
    } else {
        new ChartManager();
    }

})();

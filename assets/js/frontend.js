/**
 * BPID Suite — Frontend Chart Manager v1.8.0
 * Gobernacion de Narino
 *
 * Rendering engine: d3plus v3 (@d3plus/core)
 * CDN: https://cdn.jsdelivr.net/npm/@d3plus/core
 *
 * Supported types (13):
 *   bar, bar_horizontal, bar_stacked, bar_grouped, line, area, area_stacked,
 *   pie, donut, treemap, radar, heatmap, plot
 */
(function () {
    'use strict';

    /* ========================================
       Default Palette
       ======================================== */
    var DEFAULT_PALETTE = [
        '#3eba6a', '#e84c4c', '#4a90d9', '#f5a623', '#9b59b6',
        '#1abc9c', '#844c00', '#ff7300', '#2ecc71', '#e74c3c',
        '#3498db', '#f39c12', '#8e44ad', '#16a085', '#d35400'
    ];

    /* ========================================
       Number Formatter
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
        fullFormat: function (value) {
            if (value == null || isNaN(value)) return '0';
            return new Intl.NumberFormat('es-CO', {
                style: 'decimal', minimumFractionDigits: 0, maximumFractionDigits: 0
            }).format(value);
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

    function uniqueValues(arr) {
        var seen = {};
        var result = [];
        for (var i = 0; i < arr.length; i++) {
            if (!seen[arr[i]]) {
                seen[arr[i]] = true;
                result.push(arr[i]);
            }
        }
        return result;
    }

    /**
     * Safely get a d3plus class by name.
     */
    function getD3PlusClass(name) {
        if (typeof d3plus !== 'undefined' && d3plus[name]) return d3plus[name];
        return null;
    }

    /* ========================================
       Data Transformation
       ======================================== */
    function meltData(data, axisX, yColumns) {
        var melted = [];
        data.forEach(function (row) {
            yColumns.forEach(function (col) {
                var obj = {};
                obj[axisX] = row[axisX] != null ? String(row[axisX]) : '';
                obj._measure = col;
                obj._value = parseFloat(row[col]) || 0;
                melted.push(obj);
            });
        });
        return melted;
    }

    function prepareData(data, axisX, yColumns) {
        return data.map(function (row) {
            var obj = {};
            obj[axisX] = row[axisX] != null ? String(row[axisX]) : '';
            yColumns.forEach(function (col) {
                obj[col] = parseFloat(row[col]) || 0;
            });
            return obj;
        });
    }

    /* ========================================
       Shared Color Functions
       ======================================== */
    function makeMultiColorFn(yColumns, config, palette) {
        return function (d) {
            var idx = yColumns.indexOf(d._measure);
            return (config.y_colors && config.y_colors[idx]) || palette[idx % palette.length];
        };
    }

    function makeSingleColorFn(axisX, chartData, palette) {
        var categories = uniqueValues(chartData.map(function (r) { return r[axisX]; }));
        return function (d) {
            var ci = categories.indexOf(d[axisX]);
            return palette[ci % palette.length];
        };
    }

    /* ========================================
       Shared Tooltip Builder
       ======================================== */
    function buildTooltipConfig(config, opts) {
        var fmt = NumberFormatter.byFormat(config.number_format || 'es-CO');
        var customText = config.tooltip_text || '';

        return {
            background: '#ffffff',
            title: function (d) {
                if (opts.titleFn) return opts.titleFn(d);
                if (opts.isMulti) return d._measure || '';
                var axisX = opts.axisX || '';
                return d[axisX] != null ? String(d[axisX]) : '';
            },
            body: function (d) {
                var lines = [];

                if (opts.bodyFn) {
                    lines.push(opts.bodyFn(d, fmt));
                } else if (opts.isMulti) {
                    lines.push('<strong>' + escapeHtml(opts.axisX) + ':</strong> ' + escapeHtml(String(d[opts.axisX] || '')));
                    lines.push('<strong>' + escapeHtml(String(d._measure || '')) + ':</strong> ' + fmt(d._value));
                } else {
                    var yColumns = opts.yColumns || [];
                    for (var i = 0; i < yColumns.length; i++) {
                        var val = d[yColumns[i]];
                        if (val != null) {
                            lines.push('<strong>' + escapeHtml(yColumns[i]) + ':</strong> ' + fmt(val));
                        }
                    }
                }

                if (customText) {
                    lines.push('<em class="bpid-tooltip-custom">' + escapeHtml(customText) + '</em>');
                }

                return lines.join('<br>');
            },
            tbody: []
        };
    }

    /* ========================================
       Shared Axis Config (removes colored bar)
       ======================================== */
    function buildAxisConfig(config, isHoriz) {
        var fmt = NumberFormatter.byFormat(config.number_format || 'es-CO');

        var xCfg = {
            barConfig: { stroke: 'transparent', fill: 'transparent', 'stroke-width': 0 },
            tickFormat: function (d) { return String(d); }
        };
        var yCfg = {
            barConfig: { stroke: 'transparent', fill: 'transparent', 'stroke-width': 0 },
            tickFormat: function (d) { return fmt(d); }
        };

        if (config.title_x) xCfg.title = config.title_x;
        if (config.title_y) yCfg.title = config.title_y;

        if (isHoriz) {
            var xTickFmt = yCfg.tickFormat;
            xCfg.tickFormat = xTickFmt;
            yCfg.tickFormat = function (d) { return String(d); };
        }

        return { xConfig: xCfg, yConfig: yCfg };
    }

    /* ========================================
       Base config shared by all charts
       ======================================== */
    function baseConfig(container, config) {
        return {
            select: container,
            detectResize: true,
            detectResizeDelay: 400,
            detectVisible: true,
            detectVisibleInterval: 500,
            locale: 'es-ES'
        };
    }

    /* ========================================
       d3plus Chart Builders
       ======================================== */

    function buildBarChart(container, config, data, palette) {
        var Cls = getD3PlusClass('BarChart');
        if (!Cls) throw new Error('d3plus.BarChart no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var isMulti = yColumns.length > 1;
        var isHoriz = config.type === 'bar_horizontal';
        var isStacked = config.type === 'bar_stacked';

        var chartData, groupBy, xKey, yKey;

        if (isMulti) {
            chartData = meltData(data, axisX, yColumns);
            groupBy = '_measure';
            xKey = axisX;
            yKey = '_value';
        } else {
            chartData = prepareData(data, axisX, yColumns);
            groupBy = axisX;
            xKey = axisX;
            yKey = yColumns[0];
        }

        var axes = buildAxisConfig(config, isHoriz);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: groupBy,
            x: isHoriz ? yKey : xKey,
            y: isHoriz ? xKey : yKey,
            discrete: isHoriz ? 'y' : 'x',
            stacked: isStacked,
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: isMulti, axisX: axisX, yColumns: yColumns
            }),
            shapeConfig: {
                fill: isMulti
                    ? makeMultiColorFn(yColumns, config, palette)
                    : makeSingleColorFn(axisX, chartData, palette)
            },
            xConfig: axes.xConfig,
            yConfig: axes.yConfig,
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildLineChart(container, config, data, palette) {
        var Cls = getD3PlusClass('LinePlot');
        if (!Cls) throw new Error('d3plus.LinePlot no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var isMulti = yColumns.length > 1;

        var chartData, xKey, yKey;

        if (isMulti) {
            chartData = meltData(data, axisX, yColumns);
            xKey = axisX;
            yKey = '_value';
        } else {
            chartData = prepareData(data, axisX, yColumns);
            xKey = axisX;
            yKey = yColumns[0];
        }

        var axes = buildAxisConfig(config, false);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: isMulti ? '_measure' : function () { return yColumns[0]; },
            x: xKey,
            y: yKey,
            discrete: 'x',
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: isMulti, axisX: axisX, yColumns: yColumns
            }),
            shapeConfig: {
                Line: {
                    stroke: function (d) {
                        if (isMulti) {
                            var idx = yColumns.indexOf(d._measure);
                            return (config.y_colors && config.y_colors[idx]) || palette[idx % palette.length];
                        }
                        return (config.y_colors && config.y_colors[0]) || palette[0];
                    },
                    strokeWidth: 2.5
                }
            },
            xConfig: axes.xConfig,
            yConfig: axes.yConfig,
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildAreaChart(container, config, data, palette) {
        var isStacked = config.type === 'area_stacked';
        var ClsName = isStacked ? 'StackedArea' : 'AreaPlot';
        var Cls = getD3PlusClass(ClsName);
        if (!Cls) throw new Error('d3plus.' + ClsName + ' no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var isMulti = yColumns.length > 1;

        var chartData, xKey, yKey;

        if (isMulti) {
            chartData = meltData(data, axisX, yColumns);
            xKey = axisX;
            yKey = '_value';
        } else {
            chartData = prepareData(data, axisX, yColumns);
            xKey = axisX;
            yKey = yColumns[0];
        }

        var axes = buildAxisConfig(config, false);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: isMulti ? '_measure' : function () { return yColumns[0]; },
            x: xKey,
            y: yKey,
            discrete: 'x',
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: isMulti, axisX: axisX, yColumns: yColumns
            }),
            shapeConfig: {
                Area: {
                    fill: function (d) {
                        if (isMulti) {
                            var idx = yColumns.indexOf(d._measure);
                            return (config.y_colors && config.y_colors[idx]) || palette[idx % palette.length];
                        }
                        return (config.y_colors && config.y_colors[0]) || palette[0];
                    }
                }
            },
            xConfig: axes.xConfig,
            yConfig: axes.yConfig,
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildPieChart(container, config, data, palette) {
        var Cls = getD3PlusClass('Pie');
        if (!Cls) throw new Error('d3plus.Pie no disponible');

        var axisX = config.axis_x;
        var yCol = (config.y_columns && config.y_columns[0]) || '';
        var chartData = prepareData(data, axisX, [yCol]);
        var colorFn = makeSingleColorFn(axisX, chartData, palette);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: axisX,
            value: yCol,
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: false, axisX: axisX, yColumns: [yCol],
                titleFn: function (d) { return d[axisX] || ''; }
            }),
            shapeConfig: { fill: colorFn },
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildDonutChart(container, config, data, palette) {
        var Cls = getD3PlusClass('Donut');
        if (!Cls) throw new Error('d3plus.Donut no disponible');

        var axisX = config.axis_x;
        var yCol = (config.y_columns && config.y_columns[0]) || '';
        var chartData = prepareData(data, axisX, [yCol]);
        var colorFn = makeSingleColorFn(axisX, chartData, palette);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: axisX,
            value: yCol,
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: false, axisX: axisX, yColumns: [yCol],
                titleFn: function (d) { return d[axisX] || ''; }
            }),
            shapeConfig: { fill: colorFn },
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildTreemap(container, config, data, palette) {
        var Cls = getD3PlusClass('Treemap');
        if (!Cls) throw new Error('d3plus.Treemap no disponible');

        var axisX = config.axis_x;
        var yCol = (config.y_columns && config.y_columns[0]) || '';

        var chartData = prepareData(data, axisX, [yCol]).filter(function (d) {
            return d[yCol] > 0;
        });
        var colorFn = makeSingleColorFn(axisX, chartData, palette);
        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: axisX,
            sum: yCol,
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: false, axisX: axisX, yColumns: [yCol],
                titleFn: function (d) { return d[axisX] || ''; }
            }),
            shapeConfig: { fill: colorFn },
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    function buildRadarChart(container, config, data, palette) {
        var Cls = getD3PlusClass('Radar');
        if (!Cls) throw new Error('d3plus.Radar no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var isMulti = yColumns.length > 1;

        var chartData;

        if (isMulti) {
            chartData = meltData(data, axisX, yColumns);
        } else {
            // Single series: melt to create metric/value format
            chartData = data.map(function (row) {
                return {
                    _group: yColumns[0],
                    _metric: row[axisX] != null ? String(row[axisX]) : '',
                    _value: parseFloat(row[yColumns[0]]) || 0
                };
            });
        }

        var base = baseConfig(container, config);

        if (isMulti) {
            // Multi-series: _measure = group, axisX = metric, _value = value
            var cfg = Object.assign(base, {
                data: chartData,
                groupBy: '_measure',
                metric: axisX,
                value: '_value',
                tooltipConfig: buildTooltipConfig(config, {
                    isMulti: true, axisX: axisX, yColumns: yColumns
                }),
                shapeConfig: {
                    fill: function (d) {
                        var idx = yColumns.indexOf(d._measure);
                        return (config.y_colors && config.y_colors[idx]) || palette[idx % palette.length];
                    }
                },
                legend: !!config.show_legend
            });
            return new Cls().config(cfg);
        } else {
            var cfg2 = Object.assign(base, {
                data: chartData,
                groupBy: '_group',
                metric: '_metric',
                value: '_value',
                tooltipConfig: buildTooltipConfig(config, {
                    isMulti: false, axisX: '_metric', yColumns: ['_value'],
                    titleFn: function (d) { return d._metric || ''; }
                }),
                shapeConfig: {
                    fill: function () {
                        return (config.y_colors && config.y_colors[0]) || palette[0];
                    }
                },
                legend: !!config.show_legend
            });
            return new Cls().config(cfg2);
        }
    }

    function buildHeatmap(container, config, data, palette) {
        var Cls = getD3PlusClass('Matrix');
        if (!Cls) throw new Error('d3plus.Matrix no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var yCol = yColumns[0] || '';
        var fmt = NumberFormatter.byFormat(config.number_format || 'es-CO');
        var customText = config.tooltip_text || '';

        var keys = Object.keys(data[0] || {});
        var groupCol = '';
        for (var k = 0; k < keys.length; k++) {
            if (keys[k] !== axisX && keys[k] !== 'value' && keys[k] !== yCol) {
                groupCol = keys[k];
                break;
            }
        }
        if (!groupCol) groupCol = yCol;

        var chartData = data.map(function (row) {
            var obj = {};
            obj[axisX] = String(row[axisX] || '');
            obj[groupCol] = String(row[groupCol] || '');
            obj._value = parseFloat(row.value || row[yCol]) || 0;
            return obj;
        });

        var base = baseConfig(container, config);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: [axisX, groupCol],
            column: axisX,
            row: groupCol,
            colorScale: '_value',
            colorScaleConfig: {
                color: [palette[0] || '#1a5276']
            },
            tooltipConfig: {
                background: '#ffffff',
                title: function (d) { return d[axisX] + ' / ' + d[groupCol]; },
                body: function (d) {
                    var lines = ['<strong>Valor:</strong> ' + fmt(d._value)];
                    if (customText) {
                        lines.push('<em class="bpid-tooltip-custom">' + escapeHtml(customText) + '</em>');
                    }
                    return lines.join('<br>');
                },
                tbody: []
            },
            legend: false
        });

        if (config.title_x) {
            cfg.columnConfig = {
                title: config.title_x,
                barConfig: { stroke: 'transparent', fill: 'transparent', 'stroke-width': 0 }
            };
        }
        if (config.title_y) {
            cfg.rowConfig = {
                title: config.title_y,
                barConfig: { stroke: 'transparent', fill: 'transparent', 'stroke-width': 0 }
            };
        }

        return new Cls().config(cfg);
    }

    function buildPlotChart(container, config, data, palette) {
        var Cls = getD3PlusClass('Plot');
        if (!Cls) throw new Error('d3plus.Plot no disponible');

        var axisX = config.axis_x;
        var yColumns = config.y_columns || [];
        var isMulti = yColumns.length > 1;

        var chartData, groupBy, xKey, yKey;

        if (isMulti) {
            chartData = meltData(data, axisX, yColumns);
            groupBy = '_measure';
            xKey = axisX;
            yKey = '_value';
        } else {
            chartData = prepareData(data, axisX, yColumns);
            groupBy = axisX;
            xKey = axisX;
            yKey = yColumns[0];
        }

        var axes = buildAxisConfig(config, false);
        var base = baseConfig(container, config);

        var colorFn = isMulti
            ? makeMultiColorFn(yColumns, config, palette)
            : makeSingleColorFn(axisX, chartData, palette);

        var cfg = Object.assign(base, {
            data: chartData,
            groupBy: isMulti ? groupBy : axisX,
            x: xKey,
            y: yKey,
            shape: 'Circle',
            tooltipConfig: buildTooltipConfig(config, {
                isMulti: isMulti, axisX: axisX, yColumns: yColumns
            }),
            shapeConfig: {
                fill: colorFn,
                Circle: {
                    r: 5
                }
            },
            xConfig: axes.xConfig,
            yConfig: axes.yConfig,
            legend: !!config.show_legend
        });

        return new Cls().config(cfg);
    }

    /* ========================================
       Chart Builder Dispatcher
       ======================================== */
    function buildD3PlusChart(container, config, data) {
        var palette = config.color_palette || DEFAULT_PALETTE;
        var chart;

        switch (config.type) {
            case 'bar':
            case 'bar_horizontal':
            case 'bar_stacked':
            case 'bar_grouped':
                chart = buildBarChart(container, config, data, palette);
                break;
            case 'line':
                chart = buildLineChart(container, config, data, palette);
                break;
            case 'area':
            case 'area_stacked':
                chart = buildAreaChart(container, config, data, palette);
                break;
            case 'pie':
                chart = buildPieChart(container, config, data, palette);
                break;
            case 'donut':
                chart = buildDonutChart(container, config, data, palette);
                break;
            case 'treemap':
                chart = buildTreemap(container, config, data, palette);
                break;
            case 'radar':
                chart = buildRadarChart(container, config, data, palette);
                break;
            case 'heatmap':
                chart = buildHeatmap(container, config, data, palette);
                break;
            case 'plot':
                chart = buildPlotChart(container, config, data, palette);
                break;
            default:
                chart = buildBarChart(container, config, data, palette);
                break;
        }

        // Apply common settings to all charts
        chart.config({
            legendPosition: 'bottom',
            locale: 'es_ES'
        });

        return chart;
    }

    /* ========================================
       Toolbar Builder
       ======================================== */
    function createToolbar(wrapper, chartData, config) {
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
        if (toolbar.save_img) {
            container.appendChild(makeToolbarBtn('image', 'Imagen',
                '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
                function () { exportChartImage(wrapper, config); }
            ));
        }
        if (toolbar.csv) {
            container.appendChild(makeToolbarBtn('csv', 'Descarga',
                '<svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
                function () { downloadCSV(chartData, config); }
            ));
        }

        wrapper.appendChild(container);
    }

    function makeToolbarBtn(action, label, iconSvg, onClick) {
        var btn = document.createElement('button');
        btn.className = 'sct-btn';
        btn.setAttribute('data-action', action);
        btn.title = label;

        // Create icon element with explicit dimensions to prevent theme overrides
        var iconSpan = document.createElement('span');
        iconSpan.className = 'sct-btn-icon';
        iconSpan.innerHTML = iconSvg;

        var labelSpan = document.createElement('span');
        labelSpan.className = 'sct-btn-label';
        labelSpan.textContent = label;

        btn.appendChild(iconSpan);
        btn.appendChild(labelSpan);
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
            '<button class="bpid-chart-data-modal-close" title="Cerrar">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button></div>' +
            '<div class="bpid-chart-data-modal-body" style="padding:20px;">' +
            '<p><strong>Titulo:</strong> ' + escapeHtml(config.title || '') + '</p>' +
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
        if (existing) { existing.remove(); return; }

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

        var modal = document.createElement('div');
        modal.id = tableId;
        modal.className = 'bpid-chart-data-modal';
        modal.innerHTML = '<div class="bpid-chart-data-modal-content">' +
            '<div class="bpid-chart-data-modal-header"><h3>Datos</h3>' +
            '<button class="bpid-chart-data-modal-close" title="Cerrar">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button></div>' +
            '<div class="bpid-chart-data-modal-body">' +
            '<table class="bpid-chart-data-table">' + html + '</table>' +
            '</div></div>';

        document.body.appendChild(modal);
        requestAnimationFrame(function () { modal.classList.add('is-open'); });

        var close = function () {
            modal.classList.remove('is-open');
            setTimeout(function () { modal.remove(); }, 200);
        };
        modal.querySelector('.bpid-chart-data-modal-close').addEventListener('click', close);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
    }

    function exportChartImage(wrapper, config) {
        var svg = wrapper.querySelector('svg');
        if (!svg) { showToast('No se pudo exportar'); return; }
        var svgData = new XMLSerializer().serializeToString(svg);
        var canvas = document.createElement('canvas');
        var svgRect = svg.getBoundingClientRect();
        canvas.width = svgRect.width * 2;
        canvas.height = svgRect.height * 2;
        var ctx = canvas.getContext('2d');
        ctx.scale(2, 2);
        var img = new Image();
        img.onload = function () {
            ctx.drawImage(img, 0, 0);
            var link = document.createElement('a');
            link.download = slugify(config.title || 'grafico') + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
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
                if (!c.querySelector('svg') && !c.querySelector('.bpid-chart-wrapper')) {
                    c.innerHTML = '<div class="bpid-chart-loading"></div>';
                }
                self.observer.observe(c);
            });
        } else {
            containers.forEach(function (c) { self.loadChart(c); });
        }
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
                y_colors: ['#3eba6a'], color_palette: DEFAULT_PALETTE,
                height: 400, number_format: 'es-CO', show_legend: false,
                toolbar: { show: true, info: false, share: false, data: true, save_img: true, csv: false },
                title: '', title_y: '', title_x: '', tooltip_text: ''
            };
        } catch (e) {
            console.error('[BPID Suite] Parse error:', e);
            container.innerHTML = '<div class="bpid-chart-no-data">Error al cargar datos</div>';
            return;
        }

        if (!chartData || !chartData.length) {
            container.innerHTML = '<div class="bpid-chart-no-data">Sin datos disponibles para esta grafica</div>';
            return;
        }

        if (typeof d3plus === 'undefined') {
            container.innerHTML = '<div class="bpid-chart-no-data">d3plus no disponible</div>';
            return;
        }

        this.renderChart(container, chartData, config, chartId);
    };

    ChartManager.prototype.renderChart = function (container, chartData, config, chartId) {
        container.innerHTML = '';

        var wrapper = document.createElement('div');
        wrapper.className = 'bpid-chart-wrapper';
        wrapper.id = 'bpid-chart-wrapper-' + chartId;

        // Toolbar goes ABOVE the chart
        createToolbar(wrapper, chartData, config);

        var chartContainer = document.createElement('div');
        chartContainer.className = 'bpid-chart-d3plus-container';
        chartContainer.id = 'bpid-d3plus-' + chartId;
        chartContainer.style.height = (config.height || 400) + 'px';
        chartContainer.style.width = '100%';

        wrapper.appendChild(chartContainer);
        container.appendChild(wrapper);

        var self = this;

        // Delay render to next frame so the DOM has computed layout dimensions.
        // d3plus reads container width at render time; if the element hasn't been
        // laid out yet, it interprets "100%" as 100px.
        requestAnimationFrame(function () {
            try {
                var actualWidth = chartContainer.offsetWidth || container.offsetWidth || 600;
                var actualHeight = config.height || 400;

                var instance = buildD3PlusChart('#' + chartContainer.id, config, chartData);

                // Pass explicit pixel dimensions so d3plus never guesses
                instance.config({
                    width: actualWidth,
                    height: actualHeight
                });

                instance.render();

                self.charts.push({
                    id: chartId, container: container, instance: instance,
                    data: chartData, config: config
                });

                // Re-render on window resize for responsive behavior
                var resizeTimer;
                window.addEventListener('resize', function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function () {
                        var newWidth = chartContainer.offsetWidth || container.offsetWidth || 600;
                        if (newWidth > 0 && newWidth !== actualWidth) {
                            actualWidth = newWidth;
                            instance.config({ width: newWidth }).render();
                        }
                    }, 300);
                });
            } catch (e) {
                console.error('[BPID Suite] Render error (' + config.type + '):', e);
                container.innerHTML = '<div class="bpid-chart-no-data" style="color:#c00;">Error al renderizar: ' + escapeHtml(e.message) + '</div>';
            }
        });
    };

    /* ========================================
       Global preview builder (for admin AJAX)
       ======================================== */
    window.bpidBuildPreviewChart = function (containerEl, config, data) {
        if (typeof d3plus === 'undefined' || !data || !data.length) return;

        containerEl.innerHTML = '';
        var chartDiv = document.createElement('div');
        chartDiv.id = 'bpid-preview-d3plus-' + Date.now();
        chartDiv.style.height = (config.height || 350) + 'px';
        chartDiv.style.width = '100%';
        containerEl.appendChild(chartDiv);

        requestAnimationFrame(function () {
            try {
                var actualWidth = chartDiv.offsetWidth || containerEl.offsetWidth || 600;
                var instance = buildD3PlusChart('#' + chartDiv.id, config, data);
                instance.config({
                    width: actualWidth,
                    height: config.height || 350
                });
                instance.render();
            } catch (e) {
                console.error('[BPID Suite] Preview error:', e);
                containerEl.innerHTML = '<p style="color:#c00;text-align:center;padding:20px;">Error: ' + escapeHtml(e.message) + '</p>';
            }
        });
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

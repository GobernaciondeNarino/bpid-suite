(function() {
    'use strict';

    var DEFAULT_COLORS = ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c','#844c00','#ff7300'];

    var yRowCounter = 0;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    function qs(selector, root) {
        return (root || document).querySelector(selector);
    }

    function qsa(selector, root) {
        return (root || document).querySelectorAll(selector);
    }

    function ajaxPost(action, data, callback) {
        var fd = new FormData();
        fd.append('action', action);
        fd.append('_ajax_nonce', bpidCharts.nonce);
        if (data) {
            Object.keys(data).forEach(function(k) {
                fd.append(k, data[k]);
            });
        }
        var xhr = new XMLHttpRequest();
        xhr.open('POST', bpidCharts.ajaxUrl, true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        callback(null, json);
                    } catch (e) {
                        callback(e, null);
                    }
                } else {
                    callback(new Error('HTTP ' + xhr.status), null);
                }
            }
        };
        xhr.send(fd);
    }

    // -------------------------------------------------------------------------
    // 1. Chart Type Grid Selection
    // -------------------------------------------------------------------------

    function initChartTypeGrid() {
        var cards = qsa('.bpid-chart-type-card');

        cards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                e.preventDefault();

                // Remove active from all cards
                cards.forEach(function(c) { c.classList.remove('active'); });
                // Activate this card
                card.classList.add('active');

                // Check the radio input
                var radio = qs('input[type="radio"]', card);
                if (radio) {
                    radio.checked = true;
                }

                var type = radio ? radio.value : '';
                showChartTypeNotice(type);
                validateYColumns(type);
            });
        });

        // Initialize from already-checked radio
        var checkedRadio = qs('.bpid-chart-type-card input[type="radio"]:checked');
        if (checkedRadio) {
            var parentCard = checkedRadio.closest('.bpid-chart-type-card');
            if (parentCard) {
                parentCard.classList.add('active');
                showChartTypeNotice(checkedRadio.value);
            }
        }
    }

    function showChartTypeNotice(type) {
        var container = qs('#chart-type-notice');
        if (!container) return;

        var notices = {
            bar_stacked: 'Barras apiladas: agregue 2 o m\u00e1s valores Y para apilar las series en cada categor\u00eda del eje X.',
            pie: 'Pie/Donut: use exactamente 1 valor Y y 1 columna de agrupaci\u00f3n.',
            donut: 'Pie/Donut: use exactamente 1 valor Y y 1 columna de agrupaci\u00f3n.',
            line: 'L\u00edneas/\u00c1rea: cada columna Y genera una serie independiente.',
            area: 'L\u00edneas/\u00c1rea: cada columna Y genera una serie independiente.',
            area_stacked: '\u00c1rea Apilada: cada columna Y genera una capa apilada.'
        };

        if (notices[type]) {
            container.textContent = notices[type];
            container.style.display = 'block';
        } else {
            container.textContent = '';
            container.style.display = 'none';
        }
    }

    // -------------------------------------------------------------------------
    // 2. AJAX Data Source Loading
    // -------------------------------------------------------------------------

    function loadTables() {
        var tableSelect = qs('#chart_data_table');
        if (!tableSelect) return;

        ajaxPost('bpid_get_tables', null, function(err, res) {
            if (err || !res || !res.success) return;

            tableSelect.innerHTML = '<option value="">— Seleccionar tabla —</option>';
            var tables = res.data || [];
            tables.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                tableSelect.appendChild(opt);
            });

            // Restore saved table
            var saved = (typeof bpidChartSavedTable !== 'undefined' && bpidChartSavedTable) ? bpidChartSavedTable : '';
            if (saved) {
                tableSelect.value = saved;
                loadColumns(saved);
            }
        });
    }

    function loadColumns(table) {
        if (!table) return;

        ajaxPost('bpid_get_columns', { table: table }, function(err, res) {
            if (err || !res || !res.success) return;

            var columns = res.data || [];

            // Populate X axis select
            var xSelect = qs('#chart_axis_x');
            if (xSelect) {
                xSelect.innerHTML = '<option value="">— Seleccionar columna —</option>';
                columns.forEach(function(col) {
                    var opt = document.createElement('option');
                    opt.value = col;
                    opt.textContent = col;
                    xSelect.appendChild(opt);
                });

                // Restore saved X axis
                var savedX = (typeof bpidChartSavedAxisX !== 'undefined' && bpidChartSavedAxisX) ? bpidChartSavedAxisX : '';
                if (savedX) {
                    xSelect.value = savedX;
                }
            }

            // Populate all existing Y column selects
            populateYColumnSelects(columns);

            // Restore saved Y columns/colors if not already initialized
            if (yRowCounter === 0) {
                restoreSavedYRows(columns);
            }
        });
    }

    function populateYColumnSelects(columns) {
        var selects = qsa('.y-column-select');
        selects.forEach(function(sel) {
            var currentVal = sel.value;
            sel.innerHTML = '<option value="">— Columna Y —</option>';
            columns.forEach(function(col) {
                var opt = document.createElement('option');
                opt.value = col;
                opt.textContent = col;
                sel.appendChild(opt);
            });
            if (currentVal) {
                sel.value = currentVal;
            }
        });
    }

    function getCurrentColumns() {
        var xSelect = qs('#chart_axis_x');
        if (!xSelect) return [];
        var cols = [];
        var options = xSelect.querySelectorAll('option');
        options.forEach(function(opt) {
            if (opt.value) cols.push(opt.value);
        });
        return cols;
    }

    function restoreSavedYRows(columns) {
        var savedCols = (typeof bpidChartSavedYColumns !== 'undefined') ? bpidChartSavedYColumns : [];
        var savedColors = (typeof bpidChartSavedYColors !== 'undefined') ? bpidChartSavedYColors : [];

        if (savedCols && savedCols.length > 0) {
            savedCols.forEach(function(col, i) {
                var color = savedColors[i] || DEFAULT_COLORS[i % DEFAULT_COLORS.length];
                addYAxisRow(col, color, columns);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 3. Dynamic Y-Axis Rows
    // -------------------------------------------------------------------------

    function addYAxisRow(columnValue, colorValue, columns) {
        var container = qs('#y-axis-rows');
        if (!container) return;

        yRowCounter++;
        var index = yRowCounter;
        var color = colorValue || DEFAULT_COLORS[(index - 1) % DEFAULT_COLORS.length];

        // Use provided columns or get current ones from X select
        var cols = columns || getCurrentColumns();

        var row = document.createElement('div');
        row.className = 'y-axis-row';
        row.setAttribute('data-index', index);

        // Badge
        var badge = document.createElement('span');
        badge.className = 'y-axis-badge';
        var badgeNum = container.querySelectorAll('.y-axis-row').length + 1;
        badge.textContent = 'Y' + badgeNum;

        // Column select
        var select = document.createElement('select');
        select.className = 'y-column-select';
        select.name = 'chart_y_columns[]';
        var defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '— Columna Y —';
        select.appendChild(defaultOpt);
        cols.forEach(function(col) {
            var opt = document.createElement('option');
            opt.value = col;
            opt.textContent = col;
            select.appendChild(opt);
        });
        if (columnValue) {
            select.value = columnValue;
        }

        // Color input
        var colorInput = document.createElement('input');
        colorInput.type = 'color';
        colorInput.className = 'y-color-input';
        colorInput.name = 'chart_y_colors[]';
        colorInput.value = color;

        // Remove button
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'y-axis-remove button button-small';
        removeBtn.innerHTML = '<span class="dashicons dashicons-no-alt" style="font-size:16px;width:16px;height:16px;margin-top:2px;"></span>';
        removeBtn.title = 'Eliminar';
        removeBtn.addEventListener('click', function() {
            row.remove();
            reindexYRows();
            validateYColumnsFromCurrent();
        });

        row.appendChild(badge);
        row.appendChild(select);
        row.appendChild(colorInput);
        row.appendChild(removeBtn);
        container.appendChild(row);

        reindexYRows();
        validateYColumnsFromCurrent();
    }

    function reindexYRows() {
        var container = qs('#y-axis-rows');
        if (!container) return;
        var rows = container.querySelectorAll('.y-axis-row');
        rows.forEach(function(row, i) {
            var badge = qs('.y-axis-badge', row);
            if (badge) {
                badge.textContent = 'Y' + (i + 1);
            }
        });
    }

    function initYAxisControls() {
        var addBtn = qs('#add-y-axis');
        if (addBtn) {
            addBtn.addEventListener('click', function(e) {
                e.preventDefault();
                addYAxisRow(null, null);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 4. Color Palette Swatches
    // -------------------------------------------------------------------------

    function initColorPalette() {
        var paletteInput = qs('#chart_color_palette');
        if (!paletteInput) return;

        paletteInput.addEventListener('input', function() {
            renderSwatches(paletteInput.value);
        });

        // Initialize from saved value
        if (paletteInput.value) {
            renderSwatches(paletteInput.value);
        }
    }

    function renderSwatches(value) {
        var container = qs('#color-swatches');
        if (!container) return;

        container.innerHTML = '';
        var colors = value.split(',').map(function(c) { return c.trim(); }).filter(function(c) {
            return /^#[0-9a-fA-F]{3,8}$/.test(c);
        });

        colors.forEach(function(color) {
            var swatch = document.createElement('span');
            swatch.className = 'color-swatch';
            swatch.style.backgroundColor = color;
            swatch.title = color;

            var picker = document.createElement('input');
            picker.type = 'color';
            picker.value = color;
            picker.style.cssText = 'position:absolute;opacity:0;width:0;height:0;pointer-events:none;';

            picker.addEventListener('input', function() {
                swatch.style.backgroundColor = picker.value;
                swatch.title = picker.value;
                updatePaletteFromSwatches();
            });

            swatch.addEventListener('click', function() {
                picker.click();
            });

            var wrapper = document.createElement('span');
            wrapper.style.cssText = 'position:relative;display:inline-block;';
            wrapper.appendChild(swatch);
            wrapper.appendChild(picker);
            container.appendChild(wrapper);
        });
    }

    function updatePaletteFromSwatches() {
        var container = qs('#color-swatches');
        var paletteInput = qs('#chart_color_palette');
        if (!container || !paletteInput) return;

        var pickers = container.querySelectorAll('input[type="color"]');
        var colors = [];
        pickers.forEach(function(p) {
            colors.push(p.value);
        });
        paletteInput.value = colors.join(', ');
    }

    // -------------------------------------------------------------------------
    // 5. Chart Preview
    // -------------------------------------------------------------------------

    function initChartPreview() {
        var btn = qs('#btn-update-preview');
        if (!btn) return;

        btn.addEventListener('click', function() {
            var form = qs('#post');
            if (!form) return;

            var previewContainer = qs('#chart-preview-container');
            if (!previewContainer) return;

            previewContainer.innerHTML = '<p class="loading">Cargando vista previa…</p>';
            btn.disabled = true;

            var formData = new FormData(form);
            formData.append('action', 'bpid_chart_preview');
            formData.append('_ajax_nonce', bpidCharts.nonce);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', bpidCharts.ajaxUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.disabled = false;
                    if (xhr.status === 200) {
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (json.success && json.data) {
                                previewContainer.innerHTML = json.data;
                            } else {
                                previewContainer.innerHTML = '<p class="error">Error al generar la vista previa.</p>';
                            }
                        } catch (e) {
                            previewContainer.innerHTML = '<p class="error">Respuesta inv\u00e1lida del servidor.</p>';
                        }
                    } else {
                        previewContainer.innerHTML = '<p class="error">Error de conexi\u00f3n.</p>';
                    }
                }
            };
            xhr.send(formData);
        });
    }

    // -------------------------------------------------------------------------
    // 6. Custom Query Toggle & Generator
    // -------------------------------------------------------------------------

    function initCustomQueryToggle() {
        var textarea = qs('#chart_custom_query');
        if (!textarea) return;

        var dataSourceSection = qs('#data-source-section');
        if (!dataSourceSection) return;

        function toggleOverlay() {
            if (textarea.value.trim().length > 0) {
                dataSourceSection.classList.add('has-overlay');
            } else {
                dataSourceSection.classList.remove('has-overlay');
            }
        }

        textarea.addEventListener('input', toggleOverlay);
        toggleOverlay();
    }

    function initQueryGenerator() {
        var generateBtn = qs('#bpid-query-generate');
        var clearBtn = qs('#bpid-query-clear');
        var textarea = qs('#chart_custom_query');

        if (generateBtn && textarea) {
            generateBtn.addEventListener('click', function(e) {
                e.preventDefault();

                var table = qs('#chart_data_table');
                var axisX = qs('#chart_axis_x');
                var aggFunc = qs('#chart_agg_function');

                if (!table || !table.value) {
                    textarea.value = '-- Seleccione una tabla primero';
                    textarea.dispatchEvent(new Event('input'));
                    return;
                }

                var tableName = table.value;
                var xCol = axisX ? axisX.value : '';
                var agg = aggFunc ? aggFunc.value : 'SUM';

                // Gather Y columns
                var ySelects = qsa('.y-column-select');
                var yCols = [];
                ySelects.forEach(function(sel) {
                    if (sel.value) yCols.push(sel.value);
                });

                if (!xCol || yCols.length === 0) {
                    textarea.value = '-- Configure el eje X y al menos una variable Y';
                    textarea.dispatchEvent(new Event('input'));
                    return;
                }

                var selectParts = ['`' + xCol + '`'];
                yCols.forEach(function(yCol) {
                    selectParts.push(agg + '(`' + yCol + '`) AS `' + yCol + '`');
                });

                var query = 'SELECT ' + selectParts.join(', ') +
                    '\nFROM `' + tableName + '`' +
                    '\nGROUP BY `' + xCol + '`' +
                    '\nORDER BY `' + xCol + '` ASC' +
                    '\nLIMIT 1000';

                textarea.value = query;
                textarea.dispatchEvent(new Event('input'));
            });
        }

        if (clearBtn && textarea) {
            clearBtn.addEventListener('click', function(e) {
                e.preventDefault();
                textarea.value = '';
                textarea.dispatchEvent(new Event('input'));
            });
        }
    }

    // -------------------------------------------------------------------------
    // 7. Copy Shortcode
    // -------------------------------------------------------------------------

    function initCopyShortcode() {
        var btns = qsa('.bpid-copy-shortcode');
        btns.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var targetId = btn.getAttribute('data-target');
                var target = qs('#' + targetId);
                if (target) {
                    var text = target.textContent || target.innerText;
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(text);
                    }
                    btn.textContent = '\u00a1Copiado!';
                    setTimeout(function() { btn.textContent = 'Copiar'; }, 1500);
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // 8. Validation Warnings
    // -------------------------------------------------------------------------

    function getSelectedChartType() {
        var checkedRadio = qs('.bpid-chart-type-card input[type="radio"]:checked');
        return checkedRadio ? checkedRadio.value : '';
    }

    function getYRowCount() {
        var container = qs('#y-axis-rows');
        if (!container) return 0;
        return container.querySelectorAll('.y-axis-row').length;
    }

    function validateYColumns(type) {
        var warningEl = qs('#y-axis-warning');
        if (!warningEl) return;

        var count = getYRowCount();

        if (type === 'bar_stacked' && count < 2) {
            warningEl.textContent = 'Barras apiladas requiere al menos 2 columnas Y.';
            warningEl.style.display = 'block';
        } else if ((type === 'pie' || type === 'donut') && count > 1) {
            warningEl.textContent = 'Pie/Donut debe usar exactamente 1 columna Y.';
            warningEl.style.display = 'block';
        } else {
            warningEl.textContent = '';
            warningEl.style.display = 'none';
        }
    }

    function validateYColumnsFromCurrent() {
        validateYColumns(getSelectedChartType());
    }

    // -------------------------------------------------------------------------
    // Initialization
    // -------------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function() {
        // Only initialize on chart edit screens
        if (!qs('.bpid-chart-config')) return;

        // 1. Chart type grid
        initChartTypeGrid();

        // 2. AJAX data source
        loadTables();

        var tableSelect = qs('#chart_data_table');
        if (tableSelect) {
            tableSelect.addEventListener('change', function() {
                loadColumns(tableSelect.value);
            });
        }

        // 3. Y-Axis controls
        initYAxisControls();

        // 4. Color palette
        initColorPalette();

        // 5. Chart preview
        initChartPreview();

        // 6. Custom query toggle & generator
        initCustomQueryToggle();
        initQueryGenerator();

        // 7. Copy shortcode
        initCopyShortcode();
    });

})();

(function() {
    'use strict';

    var DEFAULT_COLORS = ['#3eba6a','#e84c4c','#4a90d9','#f5a623','#9b59b6','#1abc9c','#844c00','#ff7300'];

    var yRowCounter = 0;

    // Column type colors for visual guidance
    var TYPE_COLORS = {
        number: { bg: '#e8f5e9', border: '#4caf50', label: '#2e7d32', icon: '#' },
        text:   { bg: '#e3f2fd', border: '#2196f3', label: '#1565c0', icon: 'Abc' },
        date:   { bg: '#fff3e0', border: '#ff9800', label: '#e65100', icon: '📅' }
    };

    // Store loaded columns with metadata
    var currentColumnsData = [];

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

    /**
     * Create a styled <option> element with type color coding
     */
    function createTypedOption(col) {
        var opt = document.createElement('option');
        opt.value = col.name;

        var typeInfo = TYPE_COLORS[col.type] || TYPE_COLORS.text;
        var prefix = col.type === 'number' ? '#  ' : (col.type === 'date' ? '📅 ' : 'Abc ');
        var tableHint = col.table ? ' [' + col.table + ']' : '';
        opt.textContent = prefix + col.name + tableHint;
        opt.style.backgroundColor = typeInfo.bg;
        opt.style.borderLeft = '3px solid ' + typeInfo.border;
        opt.setAttribute('data-type', col.type);
        opt.setAttribute('data-table', col.table || '');

        return opt;
    }

    /**
     * Apply background color to a select based on its selected option type
     */
    function applySelectColor(select) {
        var selected = select.options[select.selectedIndex];
        if (selected && selected.getAttribute('data-type')) {
            var type = selected.getAttribute('data-type');
            var typeInfo = TYPE_COLORS[type] || {};
            select.style.backgroundColor = typeInfo.bg || '';
            select.style.borderColor = typeInfo.border || '';
        } else {
            select.style.backgroundColor = '';
            select.style.borderColor = '';
        }
    }

    // -------------------------------------------------------------------------
    // 1. Chart Type Grid Selection
    // -------------------------------------------------------------------------

    function initChartTypeGrid() {
        var cards = qsa('.bpid-chart-type-card');

        cards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                e.preventDefault();

                cards.forEach(function(c) { c.classList.remove('active'); });
                card.classList.add('active');

                var radio = qs('input[type="radio"]', card);
                if (radio) {
                    radio.checked = true;
                }

                var type = radio ? radio.value : '';
                showChartTypeNotice(type);
                validateYColumns(type);
            });
        });

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
            area_stacked: '\u00c1rea Apilada: cada columna Y genera una capa apilada.',
            heatmap: 'Mapa de Calor: use 1 variable Y (valor) y configure la agrupaci\u00f3n (Group By) para definir las filas de la matriz.'
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

            tableSelect.innerHTML = '<option value="">\u2014 Seleccionar tabla \u2014</option>';
            var tables = res.data || [];
            tables.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                tableSelect.appendChild(opt);
            });

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

            var rawColumns = res.data || [];

            // Normalize: API may return objects {name, type, table} or plain strings
            currentColumnsData = rawColumns.map(function(col) {
                if (typeof col === 'string') {
                    return { name: col, type: 'text', table: '' };
                }
                return col;
            });

            // Populate X axis select
            var xSelect = qs('#chart_axis_x');
            if (xSelect) {
                xSelect.innerHTML = '<option value="">\u2014 Seleccionar columna \u2014</option>';
                currentColumnsData.forEach(function(col) {
                    xSelect.appendChild(createTypedOption(col));
                });

                var savedX = (typeof bpidChartSavedAxisX !== 'undefined' && bpidChartSavedAxisX) ? bpidChartSavedAxisX : '';
                if (savedX) {
                    xSelect.value = savedX;
                }
                applySelectColor(xSelect);
                xSelect.addEventListener('change', function() { applySelectColor(xSelect); });
            }

            // Populate Group By select — show table origin for each column
            var groupBySelect = qs('#chart_group_by');
            if (groupBySelect) {
                var savedGroupBy = groupBySelect.value || '';
                groupBySelect.innerHTML = '<option value="">\u2014 Sin agrupaci\u00f3n adicional \u2014</option>';

                // Only show text/categorical columns for grouping (not numeric)
                currentColumnsData.forEach(function(col) {
                    var opt = createTypedOption(col);
                    groupBySelect.appendChild(opt);
                });
                if (savedGroupBy) {
                    groupBySelect.value = savedGroupBy;
                }
                applySelectColor(groupBySelect);
                groupBySelect.addEventListener('change', function() { applySelectColor(groupBySelect); });
            }

            // Populate Y column selects
            populateYColumnSelects();

            // Populate advanced filter column selects and orderby
            populateAdvFilterSelects();

            // Restore saved Y rows
            if (yRowCounter === 0) {
                restoreSavedYRows();
            }
        });
    }

    function populateYColumnSelects() {
        var selects = qsa('.y-column-select');
        selects.forEach(function(sel) {
            var currentVal = sel.value;
            sel.innerHTML = '<option value="">\u2014 Columna Y \u2014</option>';
            currentColumnsData.forEach(function(col) {
                sel.appendChild(createTypedOption(col));
            });
            if (currentVal) {
                sel.value = currentVal;
            }
            applySelectColor(sel);
        });
    }

    function getCurrentColumnNames() {
        return currentColumnsData.map(function(c) { return c.name; });
    }

    function restoreSavedYRows() {
        var savedCols = (typeof bpidChartSavedYColumns !== 'undefined') ? bpidChartSavedYColumns : [];
        var savedColors = (typeof bpidChartSavedYColors !== 'undefined') ? bpidChartSavedYColors : [];

        if (savedCols && savedCols.length > 0) {
            savedCols.forEach(function(col, i) {
                var color = savedColors[i] || DEFAULT_COLORS[i % DEFAULT_COLORS.length];
                addYAxisRow(col, color);
            });
        }
    }

    // -------------------------------------------------------------------------
    // 3. Dynamic Y-Axis Rows
    // -------------------------------------------------------------------------

    function addYAxisRow(columnValue, colorValue) {
        var container = qs('#y-axis-rows');
        if (!container) return;

        yRowCounter++;
        var index = yRowCounter;
        var color = colorValue || DEFAULT_COLORS[(index - 1) % DEFAULT_COLORS.length];

        var row = document.createElement('div');
        row.className = 'y-axis-row';
        row.setAttribute('data-index', index);

        // Badge
        var badge = document.createElement('span');
        badge.className = 'y-axis-badge';
        var badgeNum = container.querySelectorAll('.y-axis-row').length + 1;
        badge.textContent = 'Y' + badgeNum;

        // Column select with type colors
        var select = document.createElement('select');
        select.className = 'y-column-select';
        select.name = 'chart_y_columns[]';
        var defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '\u2014 Columna Y \u2014';
        select.appendChild(defaultOpt);
        currentColumnsData.forEach(function(col) {
            select.appendChild(createTypedOption(col));
        });
        if (columnValue) {
            select.value = columnValue;
        }
        applySelectColor(select);
        select.addEventListener('change', function() { applySelectColor(select); });

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

            previewContainer.innerHTML = '<p class="loading" style="text-align:center;color:#666;padding:20px;">Cargando vista previa\u2026</p>';
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
                                // Execute any inline scripts from the preview HTML
                                var scripts = previewContainer.querySelectorAll('script:not([type])');
                                scripts.forEach(function(s) {
                                    var newScript = document.createElement('script');
                                    newScript.textContent = s.textContent;
                                    s.parentNode.replaceChild(newScript, s);
                                });
                            } else {
                                previewContainer.innerHTML = '<p class="error" style="color:#c00;text-align:center;padding:20px;">Error al generar la vista previa.</p>';
                            }
                        } catch (e) {
                            previewContainer.innerHTML = '<p class="error" style="color:#c00;text-align:center;padding:20px;">Respuesta inv\u00e1lida del servidor.</p>';
                        }
                    } else {
                        previewContainer.innerHTML = '<p class="error" style="color:#c00;text-align:center;padding:20px;">Error de conexi\u00f3n.</p>';
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

                var vigenciaCheck = qs('#chart_group_by_vigencia');
                var groupBySelect = qs('#chart_group_by');
                var groupByCol = '';
                if (vigenciaCheck && vigenciaCheck.checked) {
                    selectParts.splice(1, 0, 'YEAR(fecha_importacion) AS vigencia');
                    groupByCol = 'vigencia';
                } else if (groupBySelect && groupBySelect.value) {
                    selectParts.splice(1, 0, '`' + groupBySelect.value + '`');
                    groupByCol = '`' + groupBySelect.value + '`';
                }

                var groupClause = '`' + xCol + '`';
                if (groupByCol) {
                    groupClause += ', ' + groupByCol;
                }

                var query = 'SELECT ' + selectParts.join(', ') +
                    '\nFROM `' + tableName + '`' +
                    '\nGROUP BY ' + groupClause +
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
    // 7.5. Advanced Filters (dynamic rows)
    // -------------------------------------------------------------------------

    var advFilterCounter = 0;

    function initAdvFilters() {
        var addBtn = qs('#add-adv-filter');
        if (!addBtn) return;

        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addAdvFilterRow('', '=', '');
        });

        // Bind remove buttons for existing rows (restored from saved)
        var existing = qsa('.adv-filter-remove');
        existing.forEach(function(btn) {
            btn.addEventListener('click', function() {
                btn.closest('.adv-filter-row').remove();
                reindexAdvFilters();
            });
        });

        // Count existing rows
        advFilterCounter = qsa('.adv-filter-row').length;
    }

    function addAdvFilterRow(column, operator, value) {
        var container = qs('#adv-filter-rows');
        if (!container) return;

        var index = advFilterCounter++;
        var row = document.createElement('div');
        row.className = 'adv-filter-row';
        row.setAttribute('data-index', index);

        // Column select
        var colSelect = document.createElement('select');
        colSelect.name = 'chart_adv_filters[' + index + '][column]';
        colSelect.className = 'adv-filter-column bpid-chart-select';
        var defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = '\u2014 Campo \u2014';
        colSelect.appendChild(defaultOpt);
        currentColumnsData.forEach(function(col) {
            colSelect.appendChild(createTypedOption(col));
        });
        if (column) colSelect.value = column;
        applySelectColor(colSelect);
        colSelect.addEventListener('change', function() { applySelectColor(colSelect); });

        // Operator select
        var opSelect = document.createElement('select');
        opSelect.name = 'chart_adv_filters[' + index + '][operator]';
        opSelect.className = 'adv-filter-operator bpid-chart-select';
        var ops = [
            { v: '=', l: '=' }, { v: '!=', l: '!=' },
            { v: '>', l: '>' }, { v: '<', l: '<' },
            { v: '>=', l: '>=' }, { v: '<=', l: '<=' },
            { v: 'LIKE', l: 'LIKE' }
        ];
        ops.forEach(function(op) {
            var o = document.createElement('option');
            o.value = op.v;
            o.textContent = op.l;
            if (op.v === operator) o.selected = true;
            opSelect.appendChild(o);
        });

        // Value input
        var valInput = document.createElement('input');
        valInput.type = 'text';
        valInput.name = 'chart_adv_filters[' + index + '][value]';
        valInput.className = 'adv-filter-value bpid-chart-input';
        valInput.placeholder = 'Valor';
        if (value) valInput.value = value;

        // Remove button
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'adv-filter-remove button button-small';
        removeBtn.title = 'Eliminar';
        removeBtn.innerHTML = '<span class="dashicons dashicons-no-alt" style="font-size:16px;width:16px;height:16px;margin-top:2px;"></span>';
        removeBtn.addEventListener('click', function() {
            row.remove();
            reindexAdvFilters();
        });

        row.appendChild(colSelect);
        row.appendChild(opSelect);
        row.appendChild(valInput);
        row.appendChild(removeBtn);
        container.appendChild(row);
    }

    function reindexAdvFilters() {
        var rows = qsa('.adv-filter-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-index', i);
            var colSel = qs('.adv-filter-column', row);
            var opSel = qs('.adv-filter-operator', row);
            var valInp = qs('.adv-filter-value', row);
            if (colSel) colSel.name = 'chart_adv_filters[' + i + '][column]';
            if (opSel)  opSel.name = 'chart_adv_filters[' + i + '][operator]';
            if (valInp) valInp.name = 'chart_adv_filters[' + i + '][value]';
        });
    }

    function populateAdvFilterSelects() {
        // Populate existing filter column selects and orderby select
        var filterColSelects = qsa('.adv-filter-column');
        filterColSelects.forEach(function(sel) {
            var currentVal = sel.value;
            sel.innerHTML = '<option value="">\u2014 Campo \u2014</option>';
            currentColumnsData.forEach(function(col) {
                sel.appendChild(createTypedOption(col));
            });
            if (currentVal) sel.value = currentVal;
            applySelectColor(sel);
            if (!sel._hasChangeHandler) {
                sel.addEventListener('change', function() { applySelectColor(sel); });
                sel._hasChangeHandler = true;
            }
        });

        // Populate orderby select
        var orderBySelect = qs('#chart_query_orderby');
        if (orderBySelect) {
            var savedOrderBy = orderBySelect.value || '';
            orderBySelect.innerHTML = '<option value="">\u2014 Eje X (defecto) \u2014</option>';
            currentColumnsData.forEach(function(col) {
                orderBySelect.appendChild(createTypedOption(col));
            });
            if (savedOrderBy) orderBySelect.value = savedOrderBy;
            applySelectColor(orderBySelect);
            if (!orderBySelect._hasChangeHandler) {
                orderBySelect.addEventListener('change', function() { applySelectColor(orderBySelect); });
                orderBySelect._hasChangeHandler = true;
            }
        }
    }

    // -------------------------------------------------------------------------
    // 7.6. Vigencia Toggle
    // -------------------------------------------------------------------------

    function initVigenciaToggle() {
        var vigenciaCheck = qs('#chart_group_by_vigencia');
        var groupBySelect = qs('#chart_group_by');
        if (!vigenciaCheck || !groupBySelect) return;

        function syncVigencia() {
            if (vigenciaCheck.checked) {
                groupBySelect.disabled = true;
                groupBySelect.style.opacity = '0.5';
            } else {
                groupBySelect.disabled = false;
                groupBySelect.style.opacity = '1';
            }
        }

        vigenciaCheck.addEventListener('change', syncVigencia);
        syncVigencia();
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
    // 9. Preview Chart Builder (exposed globally for AJAX preview)
    // -------------------------------------------------------------------------

    // bpidBuildPreviewChart is provided by frontend.js (d3plus rendering engine)

    // -------------------------------------------------------------------------
    // 10. Data Mode Tabs (Manual / View)
    // -------------------------------------------------------------------------

    var viewsCache = null;

    function initDataModeTabs() {
        var tabs = qsa('.bpid-data-mode-tab');
        var modeInput = qs('#chart_data_mode');
        if (!tabs.length || !modeInput) return;

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var mode = tab.getAttribute('data-mode');
                modeInput.value = mode;

                tabs.forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');

                var manualPanel = qs('#bpid-mode-manual');
                var viewPanel = qs('#bpid-mode-view');
                if (manualPanel) manualPanel.style.display = mode === 'manual' ? '' : 'none';
                if (viewPanel) viewPanel.style.display = mode === 'view' ? '' : 'none';

                // Disable hidden inputs to prevent form submission conflicts
                if (manualPanel) qsa('input, select', manualPanel).forEach(function(el) { el.disabled = mode !== 'manual'; });
                if (viewPanel) qsa('input, select', viewPanel).forEach(function(el) { el.disabled = mode !== 'view'; });

                // Also show/hide group-by and advanced filters sections for view mode
                var groupSection = qs('#group-by-section');
                var filterSection = qs('#filters-section');
                if (groupSection) groupSection.style.display = mode === 'manual' ? '' : 'none';
                if (filterSection) filterSection.style.display = mode === 'manual' ? '' : 'none';
            });
        });

        // Apply initial state
        var currentMode = modeInput.value || 'manual';
        var manualPanel = qs('#bpid-mode-manual');
        var viewPanel = qs('#bpid-mode-view');

        // Disable hidden panel inputs
        if (manualPanel && currentMode === 'view') {
            qsa('input, select', manualPanel).forEach(function(el) { el.disabled = true; });
        }
        if (viewPanel && currentMode === 'manual') {
            qsa('input, select', viewPanel).forEach(function(el) { el.disabled = true; });
        }

        var groupSection = qs('#group-by-section');
        var filterSection = qs('#filters-section');
        if (currentMode === 'view') {
            if (groupSection) groupSection.style.display = 'none';
            if (filterSection) filterSection.style.display = 'none';
        }
    }

    function loadViews() {
        var viewSelect = qs('#chart_view');
        if (!viewSelect) return;

        ajaxPost('bpid_get_views', null, function(err, res) {
            if (err || !res || !res.success) return;

            viewsCache = res.data || {};
            viewSelect.innerHTML = '<option value="">\u2014 Seleccionar vista \u2014</option>';

            // Group views by category
            var groups = { simple: [], series: [] };
            Object.keys(viewsCache).forEach(function(key) {
                var v = viewsCache[key];
                var group = v.group || 'simple';
                if (!groups[group]) groups[group] = [];
                groups[group].push({ key: key, label: v.label });
            });

            // Simple views
            if (groups.simple.length) {
                var optgroup1 = document.createElement('optgroup');
                optgroup1.label = 'Vistas simples';
                groups.simple.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.key;
                    opt.textContent = item.label;
                    optgroup1.appendChild(opt);
                });
                viewSelect.appendChild(optgroup1);
            }

            // Series views
            if (groups.series.length) {
                var optgroup2 = document.createElement('optgroup');
                optgroup2.label = 'Vistas con series (Apiladas/Agrupadas)';
                groups.series.forEach(function(item) {
                    var opt = document.createElement('option');
                    opt.value = item.key;
                    opt.textContent = item.label;
                    optgroup2.appendChild(opt);
                });
                viewSelect.appendChild(optgroup2);
            }

            // Restore saved view
            var savedView = (typeof bpidChartSavedView !== 'undefined' && bpidChartSavedView) ? bpidChartSavedView : '';
            if (savedView) {
                viewSelect.value = savedView;
                updateViewDescription(savedView);
            }
        });

        viewSelect.addEventListener('change', function() {
            updateViewDescription(viewSelect.value);
        });
    }

    function updateViewDescription(viewKey) {
        var descEl = qs('#bpid-view-desc');
        if (!descEl || !viewsCache) return;

        if (viewKey && viewsCache[viewKey]) {
            var v = viewsCache[viewKey];
            var text = v.desc || '';
            if (v.series) {
                text += ' Las vistas con series son ideales para gráficos de Barras Apiladas o Agrupadas.';
            }
            descEl.textContent = text;
        } else {
            descEl.textContent = '';
        }
    }

    // -------------------------------------------------------------------------
    // 11. Data Preview Widget
    // -------------------------------------------------------------------------

    function initDataPreview() {
        var btn = qs('#btn-data-preview');
        if (!btn) return;

        btn.addEventListener('click', function() {
            var contentEl = qs('#bpid-data-preview-content');
            if (!contentEl) return;

            contentEl.innerHTML = '<p style="text-align:center;padding:12px;color:#999;">Cargando...</p>';

            // Collect form data
            var form = qs('#post');
            if (!form) return;

            var formData = new FormData(form);
            formData.append('action', 'bpid_view_preview');
            formData.append('_ajax_nonce', bpidCharts.nonce);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', bpidCharts.ajaxUrl, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        if (json.success && json.data) {
                            renderDataPreview(contentEl, json.data);
                        } else {
                            contentEl.innerHTML = '<p style="text-align:center;padding:12px;color:#c00;">Sin datos para la configuración actual.</p>';
                        }
                    } catch (e) {
                        contentEl.innerHTML = '<p style="text-align:center;padding:12px;color:#c00;">Error al cargar datos.</p>';
                    }
                }
            };
            xhr.send(formData);
        });
    }

    function renderDataPreview(container, data) {
        var total = data.total || 0;
        var rows = data.rows || [];
        var columns = data.columns || [];

        if (total === 0 || !columns.length) {
            container.innerHTML = '<p style="text-align:center;padding:12px;color:#999;">Sin datos disponibles.</p>';
            return;
        }

        var html = '<div style="text-align:center;padding:8px 0;">';
        html += '<div style="font-size:28px;font-weight:700;color:#348afb;">' + total + '</div>';
        html += '<div style="font-size:11px;text-transform:uppercase;letter-spacing:0.05em;color:#666;">REGISTROS ENCONTRADOS</div>';
        html += '</div>';

        html += '<table class="bpid-data-preview-table">';
        html += '<thead><tr>';
        columns.forEach(function(col) {
            html += '<th>' + col + '</th>';
        });
        html += '</tr></thead><tbody>';

        rows.forEach(function(row) {
            html += '<tr>';
            columns.forEach(function(col) {
                var val = row[col];
                if (val != null && !isNaN(val) && val !== '' && typeof val !== 'boolean') {
                    val = Number(val).toLocaleString('es-CO', { maximumFractionDigits: 2 });
                }
                html += '<td>' + (val != null ? val : '') + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table>';

        if (total > rows.length) {
            html += '<p style="text-align:center;padding:6px;font-size:11px;color:#999;">Mostrando ' + rows.length + ' de ' + total + ' registros</p>';
        }

        container.innerHTML = html;
    }

    // -------------------------------------------------------------------------
    // Initialization
    // -------------------------------------------------------------------------

    document.addEventListener('DOMContentLoaded', function() {
        if (!qs('.bpid-chart-config')) return;

        // 1. Chart type grid
        initChartTypeGrid();

        // 2. Data mode tabs
        initDataModeTabs();

        // 3. AJAX data source (manual mode)
        loadTables();

        var tableSelect = qs('#chart_data_table');
        if (tableSelect) {
            tableSelect.addEventListener('change', function() {
                loadColumns(tableSelect.value);
            });
        }

        // 4. Load views (view mode)
        loadViews();

        // 5. Y-Axis controls
        initYAxisControls();

        // 6. Color palette
        initColorPalette();

        // 7. Chart preview
        initChartPreview();

        // 8. Custom query toggle & generator
        initCustomQueryToggle();
        initQueryGenerator();

        // 9. Advanced filters
        initAdvFilters();

        // 9.5 Group By vigencia toggle
        initVigenciaToggle();

        // 10. Copy shortcode
        initCopyShortcode();

        // 11. Data preview widget
        initDataPreview();
    });

})();

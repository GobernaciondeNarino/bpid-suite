(function () {
    'use strict';

    // ── Initialization ──
    var proyectosData = [];
    try {
        var el = document.getElementById('bpid-grid-data');
        if (el) proyectosData = JSON.parse(el.textContent);
    } catch (e) {
        console.error('[BPID Post] Error al parsear datos:', e);
    }

    var gridConfig = {};
    try {
        var cfgEl = document.getElementById('bpid-grid-config');
        if (cfgEl) gridConfig = JSON.parse(cfgEl.textContent);
    } catch(e) {
        console.error('[BPID Post] Error al parsear config:', e);
    }

    // ── Filters ──
    var filterIds = [
        'bpid-grid-search-general',
        'bpid-grid-filter-dependencia',
        'bpid-grid-filter-municipio',
        'bpid-grid-filter-ods'
    ];

    filterIds.forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var event = id === 'bpid-grid-search-general' ? 'input' : 'change';
        el.addEventListener(event, filtrar);
    });

    var clearBtn = document.getElementById('bpid-grid-clear-filters');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            filterIds.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
            filtrar();
        });
    }

    function filtrar() {
        var searchEl = document.getElementById('bpid-grid-search-general');
        var depEl    = document.getElementById('bpid-grid-filter-dependencia');
        var munEl    = document.getElementById('bpid-grid-filter-municipio');
        var odsEl    = document.getElementById('bpid-grid-filter-ods');

        var search = searchEl ? searchEl.value.toLowerCase() : '';
        var dep    = depEl    ? depEl.value.toLowerCase()    : '';
        var mun    = munEl    ? munEl.value.toLowerCase()    : '';
        var ods    = odsEl    ? odsEl.value.toLowerCase()    : '';

        var visible = 0;
        document.querySelectorAll('.bpid-grid-card').forEach(function(card) {
            var d = card.dataset;
            var match =
                (!search || d.search.includes(search)) &&
                (!dep    || d.dependencia.toLowerCase() === dep) &&
                (!mun    || d.municipios.toLowerCase().includes(mun)) &&
                (!ods    || d.odss.toLowerCase().includes(ods));

            card.style.display = match ? 'flex' : 'none';
            if (match) visible++;
        });

        var noRes = document.getElementById('bpid-grid-no-results-message');
        var grid  = document.getElementById('bpid-grid-proyectos');
        if (noRes && grid) {
            grid.style.display  = visible ? 'grid' : 'none';
            noRes.style.display = visible ? 'none' : 'block';
        }
    }

    // ── Modal ──
    var modal = document.getElementById('bpid-grid-modal');
    var modalBody = document.getElementById('bpid-grid-modal-body');
    var ocultarOps = modal ? modal.dataset.ocultarOps === '1' : false;

    // Belt-and-braces: strip any inline display:none so the .show class can flex it.
    if (modal) modal.style.removeProperty('display');

    // Delegated clicks: card openers + modal accordions.
    document.addEventListener('click', function(e) {
        if (!e.target.closest) return;

        var opener = e.target.closest('.bpid-grid-card-open');
        if (opener) {
            e.preventDefault();
            var idx = parseInt(opener.getAttribute('data-index'), 10);
            if (isNaN(idx)) {
                var card = opener.closest('.bpid-grid-card');
                if (card) idx = parseInt(card.getAttribute('data-index'), 10);
            }
            if (!isNaN(idx)) {
                try { window.bpidGridOpenModal(idx); }
                catch (err) { console.error('[BPID Post] open modal failed:', err); cerrarModal(); }
            }
            return;
        }

        var toggle = e.target.closest('.bpid-modal-accordion-toggle');
        if (toggle) {
            e.preventDefault();
            var item = toggle.closest('.bpid-modal-accordion-item');
            if (item) {
                item.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', item.classList.contains('is-open') ? 'true' : 'false');
            }
            return;
        }
    });

    // Accordion config from grid config.
    var showMetas     = gridConfig.accordionShowMetas !== false && gridConfig.accordionShowMetas !== 0;
    var showOds       = gridConfig.accordionShowOds !== false && gridConfig.accordionShowOds !== 0;
    var showContratos = gridConfig.accordionShowContratos !== false && gridConfig.accordionShowContratos !== 0;
    var contratoFields = Array.isArray(gridConfig.accordionContratoFields) ? gridConfig.accordionContratoFields : [];
    var fieldMap = gridConfig.fieldMap || {};
    var currencyFields = Array.isArray(gridConfig.currencyFields) ? gridConfig.currencyFields : [];
    var percentFields  = Array.isArray(gridConfig.percentFields)  ? gridConfig.percentFields  : [];

    function resolveField(source, fallback, fieldKey) {
        var apiKey = fieldMap[fieldKey] || fieldKey;
        var v = source ? source[apiKey] : undefined;
        if ((v === undefined || v === null || v === '') && fallback) {
            v = fallback[apiKey];
        }
        // Also try the raw key (in case caller used the API key directly).
        if ((v === undefined || v === null || v === '') && source && source[fieldKey] !== undefined) {
            v = source[fieldKey];
        }
        if ((v === undefined || v === null || v === '') && fallback && fallback[fieldKey] !== undefined) {
            v = fallback[fieldKey];
        }
        return v;
    }

    function formatFieldValue(fieldKey, value) {
        if (value === undefined || value === null || value === '') return '—';
        var apiKey = fieldMap[fieldKey] || fieldKey;
        // Arrays → bullet list (metas, odss, municipios).
        if (Array.isArray(value)) {
            if (!value.length) return '—';
            var items = value.map(function(v) {
                if (v && typeof v === 'object') {
                    if (v.nombre) return escHtml(v.nombre);
                    return escHtml(JSON.stringify(v));
                }
                return escHtml(String(v));
            });
            return '<ul class="bpid-modal-inline-list"><li>' + items.join('</li><li>') + '</li></ul>';
        }
        if (typeof value === 'object') {
            return escHtml(JSON.stringify(value));
        }
        // Currency.
        if (currencyFields.indexOf(fieldKey) >= 0 || currencyFields.indexOf(apiKey) >= 0) {
            var num = parseFloat(value);
            if (!isNaN(num)) return escHtml(formatCOP(num));
        }
        // Percent.
        if (percentFields.indexOf(fieldKey) >= 0 || percentFields.indexOf(apiKey) >= 0) {
            var pct = parseFloat(value);
            if (!isNaN(pct)) return escHtml(pct.toFixed(1) + '%');
        }
        return escHtml(String(value));
    }

    window.bpidGridOpenModal = function (idx) {
        var p = proyectosData[idx];
        if (!p || !modal || !modalBody) return;

        var totalVal = 0, sumAvance = 0, contratosHtml = '';
        try {
            (p.contratosProyecto || []).forEach(function(c) {
                var val = parseFloat(c.valorContrato)    || 0;
                var av  = parseFloat(c.procentajeAvanceFisico) || 0;
                totalVal  += val;
                sumAvance += val * av;

                var esOps = (c.esOpsEjecContractual || '').toLowerCase().trim();
                if (!ocultarOps || (esOps !== 'si' && esOps !== 'si\u0301')) {
                    contratosHtml += renderContrato(c, val, av, p);
                }
            });

            var avFisico     = totalVal > 0 ? (sumAvance / totalVal).toFixed(1) : '0.0';
            var pVal         = parseFloat(p.valorProyecto) || 0;
            var avFinanciero = pVal > 0 ? Math.min((totalVal / pVal) * 100, 100).toFixed(1) : '0.0';

            modalBody.innerHTML = buildModalHtml(p, pVal, totalVal, avFisico, avFinanciero, contratosHtml);
        } catch (err) {
            console.error('[BPID Post] build modal failed:', err);
            modalBody.innerHTML = '<p>Ocurrio un error al cargar los detalles del proyecto.</p>';
        }
        modal.style.removeProperty('display');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    // Close modal
    var closeBtn = modal ? modal.querySelector('.bpid-modal-close, .bpid-grid-modal-close') : null;
    if (closeBtn) {
        closeBtn.addEventListener('click', cerrarModal);
    }
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) cerrarModal();
        });
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });

    function cerrarModal() {
        if (modal) {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
    }

    // ── Render helpers ──
    function formatCOP(val) {
        return '$' + Number(val).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    function barra(porcentaje) {
        var p = Math.min(Math.max(parseFloat(porcentaje) || 0, 0), 100);
        var bucket = p < 30 ? 'progress-low' : (p < 70 ? 'progress-mid' : 'progress-high');
        return '<div class="bpid-modal-progress-group">' +
            '<div class="bpid-modal-progress-label">' +
                '<span class="label-value">' + p.toFixed(1) + '%</span>' +
            '</div>' +
            '<div class="bpid-modal-progress-bar">' +
                '<div class="bpid-modal-progress-bar-fill ' + bucket + '" style="width:' + p + '%"></div>' +
            '</div>' +
        '</div>';
    }

    function accordion(title, content) {
        return '<div class="bpid-modal-accordion-item">' +
            '<button type="button" class="bpid-modal-accordion-header bpid-modal-accordion-toggle">' +
                '<span class="bpid-modal-accordion-title">' + title + '</span>' +
                '<svg class="bpid-modal-accordion-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>' +
            '</button>' +
            '<div class="bpid-modal-accordion-body">' + content + '</div>' +
        '</div>';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function humanize(key) {
        if (!key) return '';
        return key.replace(/_/g, ' ').replace(/\b\w/g, function(c) { return c.toUpperCase(); });
    }

    function renderContrato(c, val, av, parentProyecto) {
        var html = '<div class="bpid-modal-contrato">';

        html += '<div class="bpid-modal-contrato-header">' +
            '<strong>' + escHtml(c.numeroContrato || '—') + '</strong>' +
            '<span class="bpid-modal-contrato-valor">' + formatCOP(val) + '</span></div>';

        // If custom contrato fields are configured, render them via fieldMap with
        // fallback to the parent project (so picking "nombre_proyecto" works).
        if (contratoFields.length > 0) {
            contratoFields.forEach(function(cf) {
                var fieldKey = cf.field || '';
                if (!fieldKey) return;
                var label = cf.label || humanize(fieldKey);
                var rawValue = resolveField(c, parentProyecto, fieldKey);
                html += '<p class="bpid-modal-contrato-field">' +
                    '<strong>' + escHtml(label) + ':</strong> ' +
                    '<span>' + formatFieldValue(fieldKey, rawValue) + '</span>' +
                '</p>';
            });
        } else {
            // Default rendering: object + description if present.
            if (c.objetoContrato) {
                html += '<p>' + escHtml(c.objetoContrato) + '</p>';
            }
        }

        html += '<div class="bpid-modal-contrato-avance"><span class="label-text">Avance fisico:</span>' + barra(av) + '</div>';

        // Municipios
        var munsHtml = '';
        if (Array.isArray(c.municipiosEjecContractual) && c.municipiosEjecContractual.length) {
            munsHtml = '<div class="bpid-modal-municipios"><strong>Municipios:</strong><ul>';
            c.municipiosEjecContractual.forEach(function(m) {
                var nombre = typeof m === 'object' ? (m.nombre || '') : String(m);
                var pob = typeof m === 'object' && m.poblacion_beneficiada ? ' — ' + Number(m.poblacion_beneficiada).toLocaleString('es-CO') + ' beneficiarios' : '';
                munsHtml += '<li>' + escHtml(nombre) + pob + '</li>';
            });
            munsHtml += '</ul></div>';
        }

        // Images
        var imgsHtml = '';
        if (Array.isArray(c.imagenesEjecContractual) && c.imagenesEjecContractual.length) {
            imgsHtml = '<div class="bpid-modal-images">';
            c.imagenesEjecContractual.forEach(function(url) {
                if (typeof url === 'string' && url.startsWith('http')) {
                    imgsHtml += '<img src="' + escHtml(url) + '" loading="lazy" alt="Imagen del contrato" class="bpid-modal-img">';
                }
            });
            imgsHtml += '</div>';
        }

        html += munsHtml + imgsHtml + '</div>';
        return html;
    }

    function buildModalHtml(p, pVal, totalVal, avFisico, avFinanciero, contratosHtml) {
        var contratos = Array.isArray(p.contratosProyecto) ? p.contratosProyecto : [];
        var numContratos = contratos.length;

        // Unique municipios + total beneficiarios
        var munSet = {};
        var beneficiarios = 0;
        contratos.forEach(function(c) {
            var muns = c.municipiosEjecContractual || [];
            if (Array.isArray(muns)) {
                muns.forEach(function(m) {
                    var nombre = typeof m === 'object' ? (m.nombre || '') : String(m);
                    if (nombre) munSet[nombre] = true;
                    if (typeof m === 'object' && m.poblacion_beneficiada) {
                        beneficiarios += Number(m.poblacion_beneficiada) || 0;
                    }
                });
            }
        });
        var munNames = Object.keys(munSet);

        // Inner header (kept inside body; outer .bpid-modal-close lives in the template).
        var html = '<div class="bpid-modal-inner-header">' +
            '<span class="bpid-modal-bpin">BPIN ' + escHtml(p.numeroProyecto || '—') + '</span>' +
            '<h2 class="bpid-modal-title">' + escHtml(p.nombreProyecto || 'Sin nombre') + '</h2>' +
        '</div>';

        // Info grid (6 items for richer summary)
        html += '<div class="bpid-modal-info-grid">' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Valor del proyecto</span><span class="bpid-modal-info-value">' + formatCOP(pVal) + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Dependencia</span><span class="bpid-modal-info-value">' + escHtml(p.dependenciaProyecto || '—') + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Contratos</span><span class="bpid-modal-info-value">' + Number(numContratos).toLocaleString('es-CO') + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Municipios</span><span class="bpid-modal-info-value">' + (munNames.length ? Number(munNames.length).toLocaleString('es-CO') : '0') + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Avance fisico</span>' + barra(avFisico) + '</div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Ejecucion financiera</span>' + barra(avFinanciero) + '</div>' +
        '</div>';

        // Beneficiarios line if any
        if (beneficiarios > 0) {
            html += '<p class="bpid-modal-description"><strong>Beneficiarios:</strong> ' +
                Number(beneficiarios).toLocaleString('es-CO') + '</p>';
        }

        // Accordions — configurable
        if (showMetas) {
            var metasContent = '<p>Sin metas registradas</p>';
            if (Array.isArray(p.metasProyecto) && p.metasProyecto.length) {
                metasContent = '<ul>' + p.metasProyecto.map(function(m) {
                    return '<li>' + escHtml(typeof m === 'string' ? m : JSON.stringify(m)) + '</li>';
                }).join('') + '</ul>';
            }
            html += accordion('Metas del proyecto (' + (Array.isArray(p.metasProyecto) ? p.metasProyecto.length : 0) + ')', metasContent);
        }

        if (showOds) {
            var odsContent = '<p>Sin ODS asociados</p>';
            if (Array.isArray(p.odssProyecto) && p.odssProyecto.length) {
                odsContent = '<div class="bpid-modal-ods">' + p.odssProyecto.map(function(o) { return '<span class="bpid-modal-ods-badge">' + escHtml(o) + '</span>'; }).join('') + '</div>';
            }
            html += accordion('ODS relacionados', odsContent);
        }

        if (munNames.length) {
            var munsListHtml = '<ul class="bpid-modal-mun-list">' + munNames.map(function(n) {
                return '<li>' + escHtml(n) + '</li>';
            }).join('') + '</ul>';
            html += accordion('Municipios (' + munNames.length + ')', munsListHtml);
        }

        if (showContratos) {
            var contContent = contratosHtml || '<p>Sin contratos registrados</p>';
            html += accordion('Contratos (' + numContratos + ')', contContent);
        }

        return html;
    }

    // ── Export ──
    function setStatus(message, cls) {
        var status = document.getElementById('bpid-grid-export-status');
        if (!status) return;
        status.style.display = 'block';
        status.textContent = message;
        status.className = 'bpid-grid-export-status ' + (cls || '');
        if (cls === 'success' || cls === 'error') {
            setTimeout(function() { status.style.display = 'none'; }, 5000);
        }
    }

    function bpidExport(format) {
        var depSelect = document.getElementById('bpid-grid-filter-dependencia');
        var dep = depSelect ? depSelect.value : '';

        setStatus('Generando documento...', 'loading');

        var formData = new FormData();
        formData.append('action', format === 'word' ? 'bpid_suite_export_word' : 'bpid_suite_export_excel');
        formData.append('nonce', gridConfig.nonce || '');
        formData.append('dependencia', dep);

        fetch(gridConfig.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.blob();
        })
        .then(function(blob) {
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            var ext = format === 'word' ? 'doc' : 'xls';
            var slug = (dep || 'general').replace(/[^a-zA-Z0-9_-]+/g, '_');
            a.download = 'Informe_' + slug + '_' + new Date().toISOString().split('T')[0] + '.' + ext;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            setStatus('Documento generado exitosamente', 'success');
        })
        .catch(function(error) {
            setStatus('Error: ' + error.message, 'error');
        });
    }

    function loadHtml2Canvas() {
        return new Promise(function(resolve, reject) {
            if (typeof window.html2canvas === 'function') {
                resolve(window.html2canvas);
                return;
            }
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
            s.async = true;
            s.onload = function() {
                if (typeof window.html2canvas === 'function') {
                    resolve(window.html2canvas);
                } else {
                    reject(new Error('html2canvas no disponible'));
                }
            };
            s.onerror = function() { reject(new Error('No se pudo cargar la libreria de captura')); };
            document.head.appendChild(s);
        });
    }

    function bpidExportImage() {
        var grid = document.querySelector('.bpid-grid-container');
        if (!grid) {
            setStatus('No se encontro el contenedor para capturar', 'error');
            return;
        }
        setStatus('Generando imagen...', 'loading');
        loadHtml2Canvas().then(function(h2c) {
            return h2c(grid, {
                backgroundColor: window.getComputedStyle(grid).backgroundColor || '#ffffff',
                scale: window.devicePixelRatio > 1 ? 2 : 1,
                useCORS: true,
                logging: false,
                ignoreElements: function(el) {
                    return el.classList && el.classList.contains('bpid-grid-export-btns');
                }
            });
        }).then(function(canvas) {
            canvas.toBlob(function(blob) {
                if (!blob) {
                    setStatus('No se pudo generar la imagen', 'error');
                    return;
                }
                var url = window.URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'Informe_' + new Date().toISOString().split('T')[0] + '.png';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                setStatus('Imagen generada exitosamente', 'success');
            }, 'image/png');
        }).catch(function(err) {
            setStatus('Error: ' + (err && err.message ? err.message : err), 'error');
        });
    }

    var wordBtn  = document.getElementById('bpid-grid-export-word');
    var excelBtn = document.getElementById('bpid-grid-export-excel');
    var imgBtn   = document.getElementById('bpid-grid-export-image');
    if (wordBtn)  wordBtn.addEventListener('click',  function() { bpidExport('word'); });
    if (excelBtn) excelBtn.addEventListener('click', function() { bpidExport('excel'); });
    if (imgBtn)   imgBtn.addEventListener('click',   bpidExportImage);

})();

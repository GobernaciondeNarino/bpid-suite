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

    // Accordion config from grid config.
    var showMetas     = gridConfig.accordionShowMetas !== false && gridConfig.accordionShowMetas !== 0;
    var showOds       = gridConfig.accordionShowOds !== false && gridConfig.accordionShowOds !== 0;
    var showContratos = gridConfig.accordionShowContratos !== false && gridConfig.accordionShowContratos !== 0;
    var contratoFields = Array.isArray(gridConfig.accordionContratoFields) ? gridConfig.accordionContratoFields : [];

    window.bpidGridOpenModal = function (idx) {
        var p = proyectosData[idx];
        if (!p || !modal || !modalBody) return;

        var totalVal = 0, sumAvance = 0, contratosHtml = '';

        (p.contratosProyecto || []).forEach(function(c) {
            var val = parseFloat(c.valorContrato)    || 0;
            var av  = parseFloat(c.procentajeAvanceFisico) || 0;
            totalVal  += val;
            sumAvance += val * av;

            var esOps = (c.esOpsEjecContractual || '').toLowerCase().trim();
            if (!ocultarOps || (esOps !== 'si' && esOps !== 'si\u0301')) {
                contratosHtml += renderContrato(c, val, av);
            }
        });

        var avFisico     = totalVal > 0 ? (sumAvance / totalVal).toFixed(1) : '0.0';
        var pVal         = parseFloat(p.valorProyecto) || 0;
        var avFinanciero = pVal > 0 ? Math.min((totalVal / pVal) * 100, 100).toFixed(1) : '0.0';

        modalBody.innerHTML = buildModalHtml(p, pVal, totalVal, avFisico, avFinanciero, contratosHtml);
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    // Close modal
    var closeBtn = document.querySelector('.bpid-grid-modal-close');
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
            document.body.style.overflow = 'auto';
        }
    }

    // ── Render helpers ──
    function formatCOP(val) {
        return '$' + Number(val).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
    }

    function barra(porcentaje) {
        var p = Math.min(Math.max(parseFloat(porcentaje) || 0, 0), 100);
        return '<div class="bpid-modal-progress-bar"><div class="bpid-modal-progress-fill" style="width:' + p + '%">' + p + '%</div></div>';
    }

    function accordion(title, content) {
        return '<div class="bpid-modal-accordion-item">' +
            '<div class="bpid-modal-accordion-header" onclick="this.nextElementSibling.classList.toggle(\'active\')">' +
            '<span>' + title + '</span><span>&#9660;</span></div>' +
            '<div class="bpid-modal-accordion-content"><div class="bpid-modal-accordion-body">' + content + '</div></div></div>';
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function renderContrato(c, val, av) {
        var html = '<div class="bpid-modal-contrato">';

        // If custom contrato fields are configured, use them.
        if (contratoFields.length > 0) {
            html += '<div class="bpid-modal-contrato-header">';
            html += '<strong>' + escHtml(c.numeroContrato) + '</strong>';
            html += '<span class="bpid-modal-contrato-valor">' + formatCOP(val) + '</span></div>';
            contratoFields.forEach(function(cf) {
                var fieldKey = cf.field || '';
                var label = cf.label || fieldKey;
                var value = c[fieldKey] || '';
                if (typeof value === 'object') value = JSON.stringify(value);
                html += '<p><strong>' + escHtml(label) + ':</strong> ' + escHtml(String(value)) + '</p>';
            });
            html += '<div class="bpid-modal-contrato-avance"><span>Avance fisico:</span>' + barra(av) + '</div>';
        } else {
            // Default contrato rendering.
            html += '<div class="bpid-modal-contrato-header">' +
                '<strong>' + escHtml(c.numeroContrato) + '</strong>' +
                '<span class="bpid-modal-contrato-valor">' + formatCOP(val) + '</span></div>' +
                '<p>' + escHtml(c.objetoContrato) + '</p>' +
                '<div class="bpid-modal-contrato-avance"><span>Avance fisico:</span>' + barra(av) + '</div>';
        }

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
        // Header
        var html = '<div class="bpid-modal-header">' +
            '<h2>' + escHtml(p.nombreProyecto) + '</h2>' +
            '<span class="bpid-modal-bpin">' + escHtml(p.numeroProyecto) + '</span></div>';

        // Info grid
        html += '<div class="bpid-modal-info-grid">' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Valor del proyecto</span><span class="bpid-modal-info-value">' + formatCOP(pVal) + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Dependencia</span><span class="bpid-modal-info-value">' + escHtml(p.dependenciaProyecto) + '</span></div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Avance fisico</span>' + barra(avFisico) + '</div>' +
            '<div class="bpid-modal-info-item"><span class="bpid-modal-info-label">Ejecucion financiera</span>' + barra(avFinanciero) + '</div></div>';

        // Accordions — configurable
        if (showMetas) {
            var metasContent = '<p>Sin metas registradas</p>';
            if (Array.isArray(p.metasProyecto) && p.metasProyecto.length) {
                metasContent = '<ul>' + p.metasProyecto.map(function(m) { return '<li>' + escHtml(m) + '</li>'; }).join('') + '</ul>';
            }
            html += accordion('Metas del proyecto', metasContent);
        }

        if (showOds) {
            var odsContent = '<p>Sin ODS asociados</p>';
            if (Array.isArray(p.odssProyecto) && p.odssProyecto.length) {
                odsContent = '<div class="bpid-modal-ods">' + p.odssProyecto.map(function(o) { return '<span class="bpid-modal-ods-badge">' + escHtml(o) + '</span>'; }).join('') + '</div>';
            }
            html += accordion('ODS relacionados', odsContent);
        }

        if (showContratos) {
            var contContent = contratosHtml || '<p>Sin contratos registrados</p>';
            var numContratos = (p.contratosProyecto || []).length;
            html += accordion('Contratos (' + numContratos + ')', contContent);
        }

        return html;
    }

    // ── Export ──
    function bpidExport(format) {
        var depSelect = document.getElementById('bpid-grid-filter-dependencia');
        var dep = depSelect ? depSelect.value : '';

        if (!dep) {
            alert('Seleccione una dependencia primero');
            return;
        }

        var status = document.getElementById('bpid-grid-export-status');
        if (status) {
            status.style.display = 'block';
            status.textContent = 'Generando documento...';
            status.className = 'bpid-grid-export-status loading';
        }

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
            if (!response.ok) throw new Error('Error HTTP ' + response.status);
            return response.blob();
        })
        .then(function(blob) {
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            var ext = format === 'word' ? 'doc' : 'xls';
            a.download = 'Informe_' + dep.replace(/ /g, '_') + '_' + new Date().toISOString().split('T')[0] + '.' + ext;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            if (status) {
                status.textContent = 'Documento generado exitosamente';
                status.className = 'bpid-grid-export-status success';
                setTimeout(function() { status.style.display = 'none'; }, 5000);
            }
        })
        .catch(function(error) {
            if (status) {
                status.textContent = 'Error: ' + error.message;
                status.className = 'bpid-grid-export-status error';
            }
        });
    }

    var wordBtn = document.getElementById('bpid-grid-export-word');
    var excelBtn = document.getElementById('bpid-grid-export-excel');
    if (wordBtn) wordBtn.addEventListener('click', function() { bpidExport('word'); });
    if (excelBtn) excelBtn.addEventListener('click', function() { bpidExport('excel'); });

})();

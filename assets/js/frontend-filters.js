(function() {
    'use strict';

    // ── Configuration ──
    var configEl = document.getElementById('bpid-filter-config');
    if (!configEl) return;

    var config;
    try {
        config = JSON.parse(configEl.textContent);
    } catch (e) {
        console.error('[BPID Filters] Error parsing config:', e);
        return;
    }

    var ajaxUrl   = config.ajaxUrl || '';
    var nonce     = config.nonce || '';
    var filterId  = config.filterId || 0;
    var perPage   = config.perPage || 20;
    var columns   = config.columns || [];

    var $form       = document.getElementById('bpid-filter-form');
    var $results    = document.getElementById('bpid-filter-results');
    var $pagination = document.getElementById('bpid-filter-pagination');
    var $loader     = document.getElementById('bpid-filter-loader');
    var $count      = document.getElementById('bpid-filter-count');
    var $exportBtn  = document.getElementById('bpid-filter-export-csv');

    var currentPage = 1;
    var debounceTimer = null;
    var lastQuery = '';

    // ── Form submission ──
    if ($form) {
        $form.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            doSearch();
        });

        // Reset button
        var $resetBtn = $form.querySelector('.bpid-filter-reset');
        if ($resetBtn) {
            $resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                $form.reset();
                currentPage = 1;
                doSearch();
            });
        }
    }

    // ── Debounced text search ──
    var $searchInput = document.getElementById('bpid-filter-search');
    if ($searchInput) {
        $searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                currentPage = 1;
                doSearch();
            }, 400);
        });
    }

    // ── Select change handlers ──
    if ($form) {
        var selects = $form.querySelectorAll('select');
        selects.forEach(function(sel) {
            sel.addEventListener('change', function() {
                currentPage = 1;
                doSearch();
            });
        });
    }

    // ── Build query from form fields ──
    function buildQuery() {
        var params = new URLSearchParams();
        params.set('action', 'bpid_suite_filter_query');
        params.set('nonce', nonce);
        params.set('filter_id', filterId);
        params.set('page', currentPage);
        params.set('per_page', perPage);

        if (!$form) return params.toString();

        var formData = new FormData($form);
        formData.forEach(function(value, key) {
            if (value && key !== 'action') {
                params.set(key, value);
            }
        });

        return params.toString();
    }

    // ── AJAX search ──
    function doSearch() {
        var query = buildQuery();

        // Skip duplicate requests
        if (query === lastQuery) return;
        lastQuery = query;

        showLoader(true);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', ajaxUrl + '?' + query, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            showLoader(false);

            if (xhr.status !== 200) {
                showError('Error de conexion (' + xhr.status + ')');
                return;
            }

            var response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (e) {
                showError('Error al procesar respuesta');
                return;
            }

            if (response.success && response.data) {
                renderResults(response.data.rows || []);
                renderPagination(response.data.total || 0, response.data.pages || 1);
                if ($count) {
                    $count.textContent = (response.data.total || 0) + ' resultado(s)';
                }
            } else {
                showError(response.data?.message || 'Error en la consulta');
            }
        };

        xhr.onerror = function() {
            showLoader(false);
            showError('Error de conexion');
        };

        xhr.send();
    }

    // ── Render results table ──
    function renderResults(rows) {
        if (!$results) return;

        if (!rows.length) {
            $results.innerHTML = '<p class="bpid-filter-no-results">No se encontraron resultados</p>';
            return;
        }

        var html = '<table class="bpid-filter-table"><thead><tr>';

        // Table headers from configured columns
        columns.forEach(function(col) {
            html += '<th>' + escHtml(col.label || col.key) + '</th>';
        });
        html += '</tr></thead><tbody>';

        // Table rows
        rows.forEach(function(row) {
            html += '<tr>';
            columns.forEach(function(col) {
                var value = row[col.key] !== undefined ? row[col.key] : '';
                html += '<td>' + formatCell(value, col.type) + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        $results.innerHTML = html;
    }

    // ── Format cell based on column type ──
    function formatCell(value, type) {
        if (value === null || value === undefined || value === '') return '-';

        switch (type) {
            case 'currency':
                return '$' + Number(value).toLocaleString('es-CO', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            case 'percentage':
                return parseFloat(value).toFixed(1) + '%';
            case 'number':
                return Number(value).toLocaleString('es-CO');
            case 'date':
                return escHtml(String(value));
            default:
                return escHtml(String(value));
        }
    }

    // ── Pagination ──
    function renderPagination(total, totalPages) {
        if (!$pagination) return;

        if (totalPages <= 1) {
            $pagination.innerHTML = '';
            return;
        }

        var html = '<div class="bpid-filter-pagination">';

        // Previous
        if (currentPage > 1) {
            html += '<button class="bpid-filter-page-btn" data-page="' + (currentPage - 1) + '">&laquo; Anterior</button>';
        }

        // Page numbers
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            html += '<button class="bpid-filter-page-btn" data-page="1">1</button>';
            if (startPage > 2) html += '<span class="bpid-filter-page-ellipsis">...</span>';
        }

        for (var i = startPage; i <= endPage; i++) {
            var activeClass = i === currentPage ? ' bpid-filter-page-active' : '';
            html += '<button class="bpid-filter-page-btn' + activeClass + '" data-page="' + i + '">' + i + '</button>';
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += '<span class="bpid-filter-page-ellipsis">...</span>';
            html += '<button class="bpid-filter-page-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
        }

        // Next
        if (currentPage < totalPages) {
            html += '<button class="bpid-filter-page-btn" data-page="' + (currentPage + 1) + '">Siguiente &raquo;</button>';
        }

        html += '</div>';
        $pagination.innerHTML = html;

        // Bind page buttons
        $pagination.querySelectorAll('.bpid-filter-page-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page, 10);
                lastQuery = ''; // Force new request
                doSearch();
                // Scroll to top of results
                if ($results) $results.scrollIntoView({behavior: 'smooth', block: 'start'});
            });
        });
    }

    // ── CSV Export ──
    if ($exportBtn) {
        $exportBtn.addEventListener('click', function(e) {
            e.preventDefault();

            var params = new URLSearchParams();
            params.set('action', 'bpid_suite_filter_export_csv');
            params.set('nonce', nonce);
            params.set('filter_id', filterId);

            if ($form) {
                var formData = new FormData($form);
                formData.forEach(function(value, key) {
                    if (value && key !== 'action') {
                        params.set(key, value);
                    }
                });
            }

            // Trigger download via hidden iframe/link
            var link = document.createElement('a');
            link.href = ajaxUrl + '?' + params.toString();
            link.download = 'bpid-export.csv';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    // ── Helpers ──
    function showLoader(show) {
        if ($loader) $loader.style.display = show ? 'block' : 'none';
    }

    function showError(msg) {
        if ($results) {
            $results.innerHTML = '<div class="bpid-filter-error">' + escHtml(msg) + '</div>';
        }
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Initial load ──
    if (config.autoLoad !== false) {
        doSearch();
    }

})();

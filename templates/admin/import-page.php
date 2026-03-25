<?php
/**
 * Admin template: Import dashboard.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$db           = BPID_Suite_Database::get_instance();
$record_count = $db->table_exists() ? $db->get_record_count() : 0;
$last_import  = get_option('bpid_suite_last_import_date', '');
?>

<div class="bpid-admin-wrap">

    <div class="bpid-page-header">
        <div class="bpid-page-header-content">
            <div>
                <h1 class="bpid-page-title"><?php echo esc_html__('Importación', 'bpid-suite'); ?></h1>
                <p class="bpid-page-subtitle"><?php echo esc_html__('Importa datos desde la API de BPID.', 'bpid-suite'); ?></p>
            </div>
        </div>
    </div>

    <!-- Import Steps Indicator -->
    <div class="bpid-import-steps" id="bpid-import-steps">
        <div class="bpid-step" id="bpid-step-1">
            <span class="bpid-step-indicator">1</span>
            <span class="bpid-step-label"><?php echo esc_html__('Conexión', 'bpid-suite'); ?></span>
        </div>
        <div class="bpid-step-connector"></div>
        <div class="bpid-step" id="bpid-step-2">
            <span class="bpid-step-indicator">2</span>
            <span class="bpid-step-label"><?php echo esc_html__('Descarga', 'bpid-suite'); ?></span>
        </div>
        <div class="bpid-step-connector"></div>
        <div class="bpid-step" id="bpid-step-3">
            <span class="bpid-step-indicator">3</span>
            <span class="bpid-step-label"><?php echo esc_html__('Procesamiento', 'bpid-suite'); ?></span>
        </div>
        <div class="bpid-step-connector"></div>
        <div class="bpid-step" id="bpid-step-4">
            <span class="bpid-step-indicator">4</span>
            <span class="bpid-step-label"><?php echo esc_html__('Completado', 'bpid-suite'); ?></span>
        </div>
    </div>

    <!-- Current Stats -->
    <div class="bpid-card" style="margin-bottom: 20px;">
        <div class="bpid-card-header">
            <h2><span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html__('Estado actual', 'bpid-suite'); ?></h2>
        </div>
        <div class="bpid-card-body">
            <div class="bpid-sysinfo-grid">
                <div class="bpid-sysinfo-item">
                    <span class="bpid-sysinfo-label"><?php echo esc_html__('Total de registros', 'bpid-suite'); ?></span>
                    <span class="bpid-sysinfo-value"><?php echo esc_html(number_format_i18n($record_count)); ?></span>
                </div>
                <div class="bpid-sysinfo-item">
                    <span class="bpid-sysinfo-label"><?php echo esc_html__('Última importación', 'bpid-suite'); ?></span>
                    <span class="bpid-sysinfo-value">
                        <?php
                        if (!empty($last_import)) {
                            echo esc_html($last_import);
                        } else {
                            echo esc_html__('Nunca', 'bpid-suite');
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Controls -->
    <div class="bpid-card" style="margin-bottom: 20px;">
        <div class="bpid-card-header">
            <h2><span class="dashicons dashicons-download"></span> <?php echo esc_html__('Importar datos', 'bpid-suite'); ?></h2>
        </div>
        <div class="bpid-card-body">
            <div class="bpid-form-actions">
                <button type="button" id="bpid-start-import" class="button button-primary button-hero">
                    <span class="dashicons dashicons-update"></span>
                    <?php echo esc_html__('Iniciar Importación', 'bpid-suite'); ?>
                </button>
            </div>

            <!-- Progress Bar -->
            <div id="bpid-import-progress-wrap" class="bpid-progress-container" style="display:none;">
                <div class="bpid-progress-header">
                    <span id="bpid-import-progress-text" class="bpid-progress-text">
                        <?php echo esc_html__('Preparando importación...', 'bpid-suite'); ?>
                    </span>
                    <span id="bpid-import-progress-percent" class="bpid-progress-percent">0%</span>
                </div>
                <div class="bpid-progress-bar">
                    <div id="bpid-import-progress-bar" class="bpid-progress-fill" style="width:0%;"></div>
                </div>
            </div>

            <!-- Cancel Button -->
            <div id="bpid-cancel-import-wrap" class="bpid-form-actions" style="display:none;">
                <button type="button" id="bpid-cancel-import" class="button button-secondary">
                    <?php echo esc_html__('Cancelar Importación', 'bpid-suite'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="bpid-import-results" class="bpid-results-container" style="display:none;">
        <div id="bpid-result-header" class="bpid-result-header">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php echo esc_html__('Resultados', 'bpid-suite'); ?>
        </div>
        <div class="bpid-import-stats">
            <div class="bpid-import-stat">
                <div id="bpid-result-inserted" class="number">0</div>
                <div class="label"><?php echo esc_html__('Insertados', 'bpid-suite'); ?></div>
            </div>
            <div class="bpid-import-stat">
                <div id="bpid-result-updated" class="number">0</div>
                <div class="label"><?php echo esc_html__('Actualizados', 'bpid-suite'); ?></div>
            </div>
            <div class="bpid-import-stat">
                <div id="bpid-result-errors" class="number">0</div>
                <div class="label"><?php echo esc_html__('Errores', 'bpid-suite'); ?></div>
            </div>
        </div>
        <div id="bpid-result-message" style="margin-top:12px;"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var config = typeof bpidSuiteImport !== 'undefined' ? bpidSuiteImport : {
        ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
        nonce: <?php echo wp_json_encode(wp_create_nonce('bpid_suite_import_nonce')); ?>,
        i18n: {
            importing: <?php echo wp_json_encode(__('Importando...', 'bpid-suite')); ?>,
            complete: <?php echo wp_json_encode(__('Importación completada', 'bpid-suite')); ?>,
            error: <?php echo wp_json_encode(__('Error en la importación', 'bpid-suite')); ?>,
            cancelled: <?php echo wp_json_encode(__('Importación cancelada', 'bpid-suite')); ?>,
            confirm: <?php echo wp_json_encode(__('¿Iniciar importación?', 'bpid-suite')); ?>
        }
    };

    var startBtn      = document.getElementById('bpid-start-import');
    var cancelBtn     = document.getElementById('bpid-cancel-import');
    var progressWrap  = document.getElementById('bpid-import-progress-wrap');
    var progressBar   = document.getElementById('bpid-import-progress-bar');
    var progressPct   = document.getElementById('bpid-import-progress-percent');
    var progressText  = document.getElementById('bpid-import-progress-text');
    var cancelWrap    = document.getElementById('bpid-cancel-import-wrap');
    var resultsWrap   = document.getElementById('bpid-import-results');
    var resultMsg     = document.getElementById('bpid-result-message');
    var pollingTimer  = null;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function setStep(stepNum) {
        for (var i = 1; i <= 4; i++) {
            var el = document.getElementById('bpid-step-' + i);
            if (!el) continue;
            el.classList.remove('active', 'completed');
            if (i < stepNum) {
                el.classList.add('completed');
            } else if (i === stepNum) {
                el.classList.add('active');
            }
        }
    }

    function ajaxPost(action, callback) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', config.nonce);

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(callback)
        .catch(function () {
            callback({ success: false, data: { message: config.i18n.error } });
        });
    }

    function pollStatus() {
        ajaxPost('bpid_suite_import_status', function (res) {
            if (!res.success) return;

            var d = res.data;
            var total     = parseInt(d.total, 10) || 0;
            var processed = parseInt(d.processed, 10) || 0;
            var pct       = total > 0 ? Math.round((processed / total) * 100) : 0;

            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
            progressText.textContent = <?php echo wp_json_encode(__('Procesando', 'bpid-suite')); ?> + ' ' + processed + ' ' + <?php echo wp_json_encode(__('de', 'bpid-suite')); ?> + ' ' + total + ' ' + <?php echo wp_json_encode(__('contratos...', 'bpid-suite')); ?>;

            if (pct > 0) {
                setStep(3);
            }

            if (d.status === 'complete' || d.status === 'cancelled') {
                clearInterval(pollingTimer);
                pollingTimer = null;
                showResults(d);
            }
        });
    }

    function showResults(d) {
        startBtn.disabled = false;
        cancelWrap.style.display = 'none';

        document.getElementById('bpid-result-inserted').textContent = d.inserted || 0;
        document.getElementById('bpid-result-updated').textContent  = d.updated || 0;
        document.getElementById('bpid-result-errors').textContent   = d.errors || 0;

        var resultHeader = document.getElementById('bpid-result-header');

        if (d.status === 'cancelled') {
            resultsWrap.className = 'bpid-results-container error';
            resultHeader.className = 'bpid-result-header error';
            resultMsg.innerHTML = '<div class="bpid-alert bpid-alert-warning"><p>' + escapeHtml(config.i18n.cancelled) + '</p></div>';
        } else {
            resultsWrap.className = 'bpid-results-container success';
            resultHeader.className = 'bpid-result-header success';
            resultMsg.innerHTML = '<div class="bpid-alert bpid-alert-success"><p>' + escapeHtml(config.i18n.complete) + '</p></div>';
            setStep(4);
        }

        resultsWrap.style.display = '';
    }

    if (startBtn) {
        startBtn.addEventListener('click', function () {
            if (!confirm(config.i18n.confirm)) return;

            startBtn.disabled = true;
            progressWrap.style.display = '';
            cancelWrap.style.display = '';
            resultsWrap.style.display = 'none';
            progressBar.style.width = '0%';
            progressPct.textContent = '0%';
            progressText.textContent = config.i18n.importing;
            setStep(1);

            ajaxPost('bpid_suite_start_import', function (res) {
                clearInterval(pollingTimer);
                pollingTimer = null;

                if (res.success) {
                    showResults(res.data);
                } else {
                    startBtn.disabled = false;
                    cancelWrap.style.display = 'none';
                    resultsWrap.className = 'bpid-results-container error';
                    resultMsg.innerHTML = '<div class="bpid-alert bpid-alert-error"><p>' + escapeHtml(res.data.message || config.i18n.error) + '</p></div>';
                    resultsWrap.style.display = '';
                }
            });

            // Move to step 2 after starting.
            setStep(2);

            // Start polling for progress updates.
            pollingTimer = setInterval(pollStatus, 2000);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            cancelBtn.disabled = true;
            ajaxPost('bpid_suite_cancel_import', function () {
                cancelBtn.disabled = false;
            });
        });
    }
})();
</script>

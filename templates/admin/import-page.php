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

<div class="wrap">
    <h1><?php echo esc_html__('BPID Suite — Importación', 'bpid-suite'); ?></h1>

    <!-- Current Stats -->
    <div class="card" style="max-width:600px;margin-bottom:20px;">
        <h2 style="margin-top:0;"><?php echo esc_html__('Estado actual', 'bpid-suite'); ?></h2>
        <table class="widefat striped" style="max-width:100%;">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('Total de registros', 'bpid-suite'); ?></strong></td>
                    <td><?php echo esc_html(number_format_i18n($record_count)); ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Última importación', 'bpid-suite'); ?></strong></td>
                    <td>
                        <?php
                        if (!empty($last_import)) {
                            echo esc_html($last_import);
                        } else {
                            echo esc_html__('Nunca', 'bpid-suite');
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Import Controls -->
    <div class="card" style="max-width:600px;margin-bottom:20px;">
        <h2 style="margin-top:0;"><?php echo esc_html__('Importar datos', 'bpid-suite'); ?></h2>

        <p>
            <button type="button" id="bpid-start-import" class="button button-primary button-hero">
                <?php echo esc_html__('Iniciar Importación', 'bpid-suite'); ?>
            </button>
        </p>

        <!-- Progress Bar -->
        <div id="bpid-import-progress-wrap" style="display:none;margin-top:16px;">
            <div style="background:#e0e0e0;border-radius:4px;overflow:hidden;height:24px;position:relative;">
                <div id="bpid-import-progress-bar" style="background:#0073aa;height:100%;width:0%;transition:width 0.3s ease;border-radius:4px;"></div>
                <span id="bpid-import-progress-percent" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:12px;font-weight:600;color:#333;">0%</span>
            </div>
            <p id="bpid-import-progress-text" style="margin-top:8px;font-style:italic;">
                <?php echo esc_html__('Preparando importación...', 'bpid-suite'); ?>
            </p>
        </div>

        <!-- Cancel Button -->
        <p id="bpid-cancel-import-wrap" style="display:none;margin-top:12px;">
            <button type="button" id="bpid-cancel-import" class="button button-secondary">
                <?php echo esc_html__('Cancelar Importación', 'bpid-suite'); ?>
            </button>
        </p>
    </div>

    <!-- Results -->
    <div id="bpid-import-results" class="card" style="max-width:600px;display:none;">
        <h2 style="margin-top:0;"><?php echo esc_html__('Resultados', 'bpid-suite'); ?></h2>
        <table class="widefat striped" style="max-width:100%;">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('Insertados', 'bpid-suite'); ?></strong></td>
                    <td id="bpid-result-inserted">0</td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Actualizados', 'bpid-suite'); ?></strong></td>
                    <td id="bpid-result-updated">0</td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Errores', 'bpid-suite'); ?></strong></td>
                    <td id="bpid-result-errors">0</td>
                </tr>
            </tbody>
        </table>
        <div id="bpid-result-message" style="margin-top:10px;"></div>
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
        resultsWrap.style.display = '';

        if (d.status === 'cancelled') {
            resultMsg.innerHTML = '<div class="notice notice-warning inline"><p>' + escapeHtml(config.i18n.cancelled) + '</p></div>';
        } else {
            resultMsg.innerHTML = '<div class="notice notice-success inline"><p>' + escapeHtml(config.i18n.complete) + '</p></div>';
        }
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

            ajaxPost('bpid_suite_start_import', function (res) {
                clearInterval(pollingTimer);
                pollingTimer = null;

                if (res.success) {
                    showResults(res.data);
                } else {
                    startBtn.disabled = false;
                    cancelWrap.style.display = 'none';
                    resultMsg.innerHTML = '<div class="notice notice-error inline"><p>' + escapeHtml(res.data.message || config.i18n.error) + '</p></div>';
                    resultsWrap.style.display = '';
                }
            });

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

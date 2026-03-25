<?php
/**
 * Admin template: Logs viewer.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$logger = BPID_Suite_Logger::get_instance();
$logs   = $logger->get_logs(200);
?>

<div class="bpid-admin-wrap">

    <div class="bpid-page-header">
        <div class="bpid-page-header-content">
            <div>
                <h1 class="bpid-page-title"><?php echo esc_html__('Logs', 'bpid-suite'); ?></h1>
                <p class="bpid-page-subtitle"><?php echo esc_html__('Registro de actividad del plugin.', 'bpid-suite'); ?></p>
            </div>
        </div>
        <div class="bpid-header-actions">
            <button type="button" id="bpid-refresh-logs" class="button button-secondary">
                <span class="dashicons dashicons-update" style="vertical-align:middle;"></span>
                <?php echo esc_html__('Actualizar', 'bpid-suite'); ?>
            </button>
            <button type="button" id="bpid-clear-logs" class="button button-secondary">
                <span class="dashicons dashicons-trash" style="vertical-align:middle;"></span>
                <?php echo esc_html__('Limpiar Logs', 'bpid-suite'); ?>
            </button>
        </div>
    </div>

    <div class="bpid-card" style="margin-bottom: 20px;">
        <div class="bpid-card-header">
            <h2><span class="dashicons dashicons-media-text"></span> <?php echo esc_html__('Visor de logs', 'bpid-suite'); ?></h2>
            <span id="bpid-log-count" class="bpid-badge bpid-badge--info">
                <?php echo esc_html(sprintf(
                    /* translators: %d: number of log entries */
                    __('%d entradas', 'bpid-suite'),
                    count($logs)
                )); ?>
            </span>
        </div>
        <div class="bpid-card-body" style="padding:0;">
            <div class="bpid-log-viewer" id="bpid-logs-terminal">
                <pre class="bpid-log-content" id="bpid-log-content"><?php
                    if (!empty($logs)) {
                        foreach ($logs as $line) {
                            $escaped = esc_html($line);
                            // Color code log levels.
                            if (strpos($line, '[ERROR]') !== false) {
                                echo '<span class="bpid-log-error">' . $escaped . '</span>' . "\n";
                            } elseif (strpos($line, '[WARNING]') !== false) {
                                echo '<span class="bpid-log-warn">' . $escaped . '</span>' . "\n";
                            } elseif (strpos($line, '[INFO]') !== false) {
                                echo '<span class="bpid-log-info">' . $escaped . '</span>' . "\n";
                            } elseif (strpos($line, '[DEBUG]') !== false) {
                                echo '<span class="bpid-log-success">' . $escaped . '</span>' . "\n";
                            } elseif (strpos($line, '---') !== false) {
                                echo '<span class="bpid-log-separator">' . $escaped . '</span>' . "\n";
                            } else {
                                echo $escaped . "\n";
                            }
                        }
                    } else {
                        echo esc_html__('No hay entradas de log.', 'bpid-suite');
                    }
                ?></pre>
            </div>
        </div>
    </div>

    <div id="bpid-clear-logs-result"></div>
</div>

<script>
(function () {
    'use strict';

    var terminal = document.getElementById('bpid-logs-terminal');
    if (terminal) {
        terminal.scrollTop = terminal.scrollHeight;
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function ajaxPost(action, callback) {
        var formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', <?php echo wp_json_encode(wp_create_nonce('bpid_suite_import_nonce')); ?>);

        fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
        .then(function (r) { return r.json(); })
        .then(callback)
        .catch(function () {
            callback({ success: false, data: { message: <?php echo wp_json_encode(__('Error de conexión.', 'bpid-suite')); ?> } });
        });
    }

    // Clear logs.
    var clearBtn = document.getElementById('bpid-clear-logs');
    var resultDiv = document.getElementById('bpid-clear-logs-result');

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            var msg = <?php echo wp_json_encode(__('¿Está seguro de que desea eliminar todos los logs?', 'bpid-suite')); ?>;
            if (!confirm(msg)) {
                return;
            }

            clearBtn.disabled = true;

            ajaxPost('bpid_suite_clear_logs', function (data) {
                clearBtn.disabled = false;
                if (data.success) {
                    document.getElementById('bpid-log-content').textContent = <?php echo wp_json_encode(__('No hay entradas de log.', 'bpid-suite')); ?>;
                    resultDiv.innerHTML = '<div class="bpid-alert bpid-alert-success" style="margin-top:12px;"><p>' + escapeHtml(<?php echo wp_json_encode(__('Logs limpiados correctamente.', 'bpid-suite')); ?>) + '</p></div>';
                    document.getElementById('bpid-log-count').textContent = '0 ' + <?php echo wp_json_encode(__('entradas', 'bpid-suite')); ?>;
                } else {
                    resultDiv.innerHTML = '<div class="bpid-alert bpid-alert-error" style="margin-top:12px;"><p>' + escapeHtml(<?php echo wp_json_encode(__('Error al limpiar logs.', 'bpid-suite')); ?>) + '</p></div>';
                }
            });
        });
    }

    // Refresh logs.
    var refreshBtn = document.getElementById('bpid-refresh-logs');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }
})();
</script>

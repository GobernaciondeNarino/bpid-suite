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

<div class="wrap">
    <h1><?php echo esc_html__('BPID Suite — Logs', 'bpid-suite'); ?></h1>

    <p>
        <button type="button" id="bpid-clear-logs" class="button button-secondary">
            <?php echo esc_html__('Limpiar Logs', 'bpid-suite'); ?>
        </button>
        <span id="bpid-clear-logs-spinner" class="spinner" style="float:none;"></span>
        <span id="bpid-clear-logs-result"></span>
    </p>

    <div id="bpid-logs-terminal" style="
        background: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Courier New', Courier, monospace;
        font-size: 13px;
        line-height: 1.6;
        padding: 16px;
        border-radius: 4px;
        max-height: 600px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        border: 1px solid #333;
    ">
<?php
if (!empty($logs)) {
    foreach ($logs as $line) {
        echo esc_html($line) . "\n";
    }
} else {
    echo esc_html__('No hay entradas de log.', 'bpid-suite');
}
?>
    </div>
</div>

<script>
(function () {
    'use strict';

    // Auto-scroll terminal to bottom.
    var terminal = document.getElementById('bpid-logs-terminal');
    if (terminal) {
        terminal.scrollTop = terminal.scrollHeight;
    }

    // Clear logs.
    var clearBtn     = document.getElementById('bpid-clear-logs');
    var clearSpinner = document.getElementById('bpid-clear-logs-spinner');
    var clearResult  = document.getElementById('bpid-clear-logs-result');

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            var msg = <?php echo wp_json_encode(__('¿Está seguro de que desea eliminar todos los logs?', 'bpid-suite')); ?>;
            if (!confirm(msg)) {
                return;
            }

            clearBtn.disabled = true;
            clearSpinner.classList.add('is-active');
            clearResult.textContent = '';

            var formData = new FormData();
            formData.append('action', 'bpid_suite_clear_logs');
            formData.append('nonce', <?php echo wp_json_encode(wp_create_nonce('bpid_suite_import_nonce')); ?>);

            fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                clearBtn.disabled = false;
                clearSpinner.classList.remove('is-active');

                if (data.success) {
                    terminal.textContent = <?php echo wp_json_encode(__('No hay entradas de log.', 'bpid-suite')); ?>;
                    clearResult.textContent = <?php echo wp_json_encode(__('Logs limpiados.', 'bpid-suite')); ?>;
                    clearResult.style.color = '#46b450';
                } else {
                    clearResult.textContent = <?php echo wp_json_encode(__('Error al limpiar logs.', 'bpid-suite')); ?>;
                    clearResult.style.color = '#dc3232';
                }
            })
            .catch(function () {
                clearBtn.disabled = false;
                clearSpinner.classList.remove('is-active');
                clearResult.textContent = <?php echo wp_json_encode(__('Error de conexión.', 'bpid-suite')); ?>;
                clearResult.style.color = '#dc3232';
            });
        });
    }
})();
</script>

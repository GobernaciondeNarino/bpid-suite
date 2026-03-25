<?php
/**
 * Admin template: Configuration page.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$db             = BPID_Suite_Database::get_instance();
$api_key_exists = !empty(get_option('bpid_suite_api_key', ''));
$cron_frequency = get_option('bpid_suite_cron_frequency', 'disabled');
$table_exists   = $db->table_exists();
$record_count   = $table_exists ? $db->get_record_count() : 0;
$next_scheduled = wp_next_scheduled('bpid_suite_cron_import');

// Restore settings errors from transient (set during handle_config_save redirect).
$transient_errors = get_transient('bpid_suite_settings_errors');
if ($transient_errors) {
    foreach ($transient_errors as $error) {
        add_settings_error($error['setting'], $error['code'], $error['message'], $error['type']);
    }
    delete_transient('bpid_suite_settings_errors');
}
?>

<div class="wrap">
    <h1><?php echo esc_html__('BPID Suite — Configuración', 'bpid-suite'); ?></h1>

    <?php settings_errors('bpid_suite'); ?>

    <form method="post" action="">
        <?php wp_nonce_field('bpid_suite_config_save', 'bpid_suite_config_nonce'); ?>

        <!-- Section: API Key -->
        <h2><?php echo esc_html__('API Key BPID', 'bpid-suite'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bpid_suite_api_key"><?php echo esc_html__('Clave de API', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <input
                            type="password"
                            id="bpid_suite_api_key"
                            name="bpid_suite_api_key"
                            value=""
                            class="regular-text"
                            placeholder="<?php echo $api_key_exists ? esc_attr(str_repeat('*', 20)) : esc_attr__('Ingrese la clave de API', 'bpid-suite'); ?>"
                            autocomplete="off"
                        />
                        <button type="button" id="bpid-toggle-api-key" class="button button-secondary">
                            <span class="dashicons dashicons-visibility" style="vertical-align:middle;"></span>
                        </button>
                    </div>
                    <?php if ($api_key_exists) : ?>
                        <p class="description">
                            <?php echo esc_html__('Ya existe una clave guardada. Deje vacío para mantener la actual.', 'bpid-suite'); ?>
                        </p>
                    <?php endif; ?>
                    <p style="margin-top:10px;">
                        <button type="button" id="bpid-test-connection" class="button button-secondary">
                            <?php echo esc_html__('Probar conexión', 'bpid-suite'); ?>
                        </button>
                        <span id="bpid-connection-spinner" class="spinner" style="float:none;"></span>
                    </p>
                    <div id="bpid-connection-result" style="margin-top:8px;"></div>
                </td>
            </tr>
        </table>

        <!-- Section: System Information -->
        <h2><?php echo esc_html__('Información del Sistema', 'bpid-suite'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Versión del plugin', 'bpid-suite'); ?></th>
                <td><?php echo esc_html(BPID_SUITE_VERSION); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('WordPress requerido', 'bpid-suite'); ?></th>
                <td>
                    <?php echo esc_html('6.0'); ?>
                    <?php if (version_compare(get_bloginfo('version'), '6.0', '>=')) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-warning" style="color:#dc3232;"></span>
                        <em><?php echo esc_html(sprintf(__('Actual: %s', 'bpid-suite'), get_bloginfo('version'))); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('PHP requerido', 'bpid-suite'); ?></th>
                <td>
                    <?php echo esc_html('8.1'); ?>
                    <?php if (version_compare(PHP_VERSION, '8.1', '>=')) : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>
                    <?php else : ?>
                        <span class="dashicons dashicons-warning" style="color:#dc3232;"></span>
                        <em><?php echo esc_html(sprintf(__('Actual: %s', 'bpid-suite'), PHP_VERSION)); ?></em>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Estado de la tabla', 'bpid-suite'); ?></th>
                <td>
                    <?php if ($table_exists) : ?>
                        <span style="color:#46b450;font-weight:600;"><?php echo esc_html__('Existe', 'bpid-suite'); ?></span>
                        —
                        <?php
                        echo esc_html(sprintf(
                            /* translators: %s: number of records */
                            __('%s registros', 'bpid-suite'),
                            number_format_i18n($record_count)
                        ));
                        ?>
                    <?php else : ?>
                        <span style="color:#dc3232;font-weight:600;"><?php echo esc_html__('No existe', 'bpid-suite'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Section: Cron Scheduling -->
        <h2><?php echo esc_html__('Programación Cron', 'bpid-suite'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="bpid_suite_cron_frequency"><?php echo esc_html__('Frecuencia', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <select id="bpid_suite_cron_frequency" name="bpid_suite_cron_frequency">
                        <option value="disabled" <?php selected($cron_frequency, 'disabled'); ?>>
                            <?php echo esc_html__('Desactivado', 'bpid-suite'); ?>
                        </option>
                        <option value="daily" <?php selected($cron_frequency, 'daily'); ?>>
                            <?php echo esc_html__('Diario', 'bpid-suite'); ?>
                        </option>
                        <option value="weekly" <?php selected($cron_frequency, 'weekly'); ?>>
                            <?php echo esc_html__('Semanal', 'bpid-suite'); ?>
                        </option>
                        <option value="monthly" <?php selected($cron_frequency, 'monthly'); ?>>
                            <?php echo esc_html__('Mensual', 'bpid-suite'); ?>
                        </option>
                    </select>
                    <?php if ($next_scheduled) : ?>
                        <p class="description">
                            <?php
                            echo esc_html(sprintf(
                                /* translators: %s: date and time */
                                __('Próxima ejecución programada: %s', 'bpid-suite'),
                                wp_date('Y-m-d H:i:s', $next_scheduled)
                            ));
                            ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <!-- Section: Maintenance -->
        <h2><?php echo esc_html__('Mantenimiento', 'bpid-suite'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><?php echo esc_html__('Regenerar tabla', 'bpid-suite'); ?></th>
                <td>
                    <button type="button" id="bpid-regenerate-table" class="button button-secondary">
                        <?php echo esc_html__('Regenerar tabla', 'bpid-suite'); ?>
                    </button>
                    <p class="description">
                        <?php
                        echo esc_html(sprintf(
                            /* translators: %s: number of records */
                            __('Actualmente hay %s registros en la tabla. Esta acción eliminará todos los datos y recreará la tabla.', 'bpid-suite'),
                            number_format_i18n($record_count)
                        ));
                        ?>
                    </p>
                    <div id="bpid-regenerate-result" style="margin-top:8px;"></div>
                </td>
            </tr>
        </table>

        <?php submit_button(esc_html__('Guardar Configuración', 'bpid-suite')); ?>
    </form>
</div>

<script>
(function () {
    'use strict';

    // Toggle password visibility.
    var toggleBtn = document.getElementById('bpid-toggle-api-key');
    var apiKeyInput = document.getElementById('bpid_suite_api_key');

    if (toggleBtn && apiKeyInput) {
        toggleBtn.addEventListener('click', function () {
            var isPassword = apiKeyInput.type === 'password';
            apiKeyInput.type = isPassword ? 'text' : 'password';
            var icon = toggleBtn.querySelector('.dashicons');
            if (icon) {
                icon.classList.toggle('dashicons-visibility', !isPassword);
                icon.classList.toggle('dashicons-hidden', isPassword);
            }
        });
    }

    // Test connection via AJAX.
    var testBtn = document.getElementById('bpid-test-connection');
    var spinner = document.getElementById('bpid-connection-spinner');
    var resultDiv = document.getElementById('bpid-connection-result');

    if (testBtn) {
        testBtn.addEventListener('click', function () {
            spinner.classList.add('is-active');
            resultDiv.innerHTML = '';
            testBtn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'bpid_suite_test_connection');
            formData.append('nonce', <?php echo wp_json_encode(wp_create_nonce('bpid_suite_import_nonce')); ?>);

            fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                spinner.classList.remove('is-active');
                testBtn.disabled = false;
                if (data.success) {
                    resultDiv.innerHTML = '<div class="notice notice-success inline"><p>' + escapeHtml(data.data.message) + '</p></div>';
                } else {
                    resultDiv.innerHTML = '<div class="notice notice-error inline"><p>' + escapeHtml(data.data.message) + '</p></div>';
                }
            })
            .catch(function () {
                spinner.classList.remove('is-active');
                testBtn.disabled = false;
                resultDiv.innerHTML = '<div class="notice notice-error inline"><p><?php echo esc_js(__('Error de conexión.', 'bpid-suite')); ?></p></div>';
            });
        });
    }

    // Regenerate table with double-step confirmation.
    var regenBtn = document.getElementById('bpid-regenerate-table');
    var regenResult = document.getElementById('bpid-regenerate-result');

    if (regenBtn) {
        regenBtn.addEventListener('click', function () {
            var recordCount = <?php echo wp_json_encode(number_format_i18n($record_count)); ?>;
            var msg1 = <?php echo wp_json_encode(sprintf(
                /* translators: %s: number of records */
                __('Se eliminarán %s registros. ¿Desea continuar?', 'bpid-suite'),
                number_format_i18n($record_count)
            )); ?>;

            if (!confirm(msg1)) {
                return;
            }

            var msg2 = <?php echo wp_json_encode(__('¿Está completamente seguro? Esta acción no se puede deshacer.', 'bpid-suite')); ?>;
            if (!confirm(msg2)) {
                return;
            }

            regenBtn.disabled = true;
            regenResult.innerHTML = '<span class="spinner is-active" style="float:none;"></span>';

            var formData = new FormData();
            formData.append('action', 'bpid_suite_regenerate_table');
            formData.append('nonce', <?php echo wp_json_encode(wp_create_nonce('bpid_suite_import_nonce')); ?>);

            fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                regenBtn.disabled = false;
                if (data.success) {
                    regenResult.innerHTML = '<div class="notice notice-success inline"><p>' + escapeHtml(data.data.message) + '</p></div>';
                } else {
                    regenResult.innerHTML = '<div class="notice notice-error inline"><p>' + escapeHtml(data.data.message) + '</p></div>';
                }
            })
            .catch(function () {
                regenBtn.disabled = false;
                regenResult.innerHTML = '<div class="notice notice-error inline"><p><?php echo esc_js(__('Error de conexión.', 'bpid-suite')); ?></p></div>';
            });
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
})();
</script>

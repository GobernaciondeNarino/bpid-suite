<?php
/**
 * Metabox template: Post module visualizer configuration.
 *
 * Included by BPID_Suite_Post::render_meta_box().
 * Receives $post variable from the calling context.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var WP_Post $post */

wp_nonce_field('bpid_suite_post_admin', 'bpid_suite_post_nonce');

// Retrieve saved meta values with defaults.
$mostrar_stats    = get_post_meta($post->ID, '_bpid_post_mostrar_stats', true);
$mostrar_buscador = get_post_meta($post->ID, '_bpid_post_mostrar_buscador', true);
$mostrar_filtros  = get_post_meta($post->ID, '_bpid_post_mostrar_filtros', true);
$filtro_dep       = get_post_meta($post->ID, '_bpid_post_filtro_dependencia', true);
$color_primario   = get_post_meta($post->ID, '_bpid_post_color_primario', true);
$color_fondo      = get_post_meta($post->ID, '_bpid_post_color_fondo', true);
$ocultar_ops      = get_post_meta($post->ID, '_bpid_post_ocultar_ops', true);
$cols_grid        = get_post_meta($post->ID, '_bpid_post_cols_grid', true);
$texto_intro      = get_post_meta($post->ID, '_bpid_post_texto_intro', true);
$cache_horas      = get_post_meta($post->ID, '_bpid_post_cache_horas', true);

// Apply defaults.
if (empty($color_primario)) {
    $color_primario = '#348afb';
}
if (empty($color_fondo)) {
    $color_fondo = '#fffcf3';
}
if (empty($cols_grid) || (int) $cols_grid < 1 || (int) $cols_grid > 4) {
    $cols_grid = 3;
}
if (empty($cache_horas) || (int) $cache_horas < 1 || (int) $cache_horas > 24) {
    $cache_horas = 12;
}

// Load dependencias for the select dropdown.
$db = BPID_Suite_Database::get_instance();
$dependencias = $db->table_exists() ? $db->get_distinct_values('dependencia') : [];
?>

<table class="form-table">
    <!-- Mostrar Stats -->
    <tr>
        <th scope="row">
            <label for="bpid_post_mostrar_stats"><?php echo esc_html__('Mostrar estadísticas', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="checkbox"
                id="bpid_post_mostrar_stats"
                name="bpid_post_mostrar_stats"
                value="1"
                <?php checked($mostrar_stats, '1'); ?>
            />
            <span class="description">
                <?php echo esc_html__('Muestra tarjetas de resumen (total proyectos, valor total, avance promedio).', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>

    <!-- Mostrar Buscador -->
    <tr>
        <th scope="row">
            <label for="bpid_post_mostrar_buscador"><?php echo esc_html__('Mostrar buscador', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="checkbox"
                id="bpid_post_mostrar_buscador"
                name="bpid_post_mostrar_buscador"
                value="1"
                <?php checked($mostrar_buscador, '1'); ?>
            />
            <span class="description">
                <?php echo esc_html__('Campo de búsqueda por texto libre.', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>

    <!-- Mostrar Filtros -->
    <tr>
        <th scope="row">
            <label for="bpid_post_mostrar_filtros"><?php echo esc_html__('Mostrar filtros', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="checkbox"
                id="bpid_post_mostrar_filtros"
                name="bpid_post_mostrar_filtros"
                value="1"
                <?php checked($mostrar_filtros, '1'); ?>
            />
            <span class="description">
                <?php echo esc_html__('Muestra filtros desplegables (dependencia, rango de valor, avance, OPS).', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>

    <!-- Filtro Dependencia -->
    <tr>
        <th scope="row">
            <label for="bpid_post_filtro_dependencia"><?php echo esc_html__('Filtrar por dependencia', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select id="bpid_post_filtro_dependencia" name="bpid_post_filtro_dependencia">
                <option value=""><?php echo esc_html__('— Todas —', 'bpid-suite'); ?></option>
                <?php foreach ($dependencias as $dep) : ?>
                    <option value="<?php echo esc_attr($dep); ?>" <?php selected($filtro_dep, $dep); ?>>
                        <?php echo esc_html($dep); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">
                <?php echo esc_html__('Prefiltra los resultados por esta dependencia. Vacío para mostrar todas.', 'bpid-suite'); ?>
            </p>
        </td>
    </tr>

    <!-- Color Primario -->
    <tr>
        <th scope="row">
            <label for="bpid_post_color_primario"><?php echo esc_html__('Color primario', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="text"
                id="bpid_post_color_primario"
                name="bpid_post_color_primario"
                value="<?php echo esc_attr($color_primario); ?>"
                class="bpid-color-picker"
                data-default-color="#348afb"
            />
        </td>
    </tr>

    <!-- Color Fondo -->
    <tr>
        <th scope="row">
            <label for="bpid_post_color_fondo"><?php echo esc_html__('Color de fondo', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="text"
                id="bpid_post_color_fondo"
                name="bpid_post_color_fondo"
                value="<?php echo esc_attr($color_fondo); ?>"
                class="bpid-color-picker"
                data-default-color="#fffcf3"
            />
        </td>
    </tr>

    <!-- Ocultar OPS -->
    <tr>
        <th scope="row">
            <label for="bpid_post_ocultar_ops"><?php echo esc_html__('Ocultar OPS', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="checkbox"
                id="bpid_post_ocultar_ops"
                name="bpid_post_ocultar_ops"
                value="1"
                <?php checked($ocultar_ops, '1'); ?>
            />
            <span class="description">
                <?php echo esc_html__('Excluye del visualizador los contratos marcados como OPS.', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>

    <!-- Cols Grid -->
    <tr>
        <th scope="row">
            <label for="bpid_post_cols_grid"><?php echo esc_html__('Columnas del grid', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select id="bpid_post_cols_grid" name="bpid_post_cols_grid">
                <?php for ($i = 1; $i <= 4; $i++) : ?>
                    <option value="<?php echo esc_attr((string) $i); ?>" <?php selected((int) $cols_grid, $i); ?>>
                        <?php echo esc_html((string) $i); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </td>
    </tr>

    <!-- Texto Intro -->
    <tr>
        <th scope="row">
            <label for="bpid_post_texto_intro"><?php echo esc_html__('Texto introductorio', 'bpid-suite'); ?></label>
        </th>
        <td>
            <textarea
                id="bpid_post_texto_intro"
                name="bpid_post_texto_intro"
                rows="4"
                class="large-text"
                placeholder="<?php echo esc_attr__('Texto opcional que aparecerá antes del visualizador.', 'bpid-suite'); ?>"
            ><?php echo esc_textarea($texto_intro); ?></textarea>
        </td>
    </tr>

    <!-- Cache Horas -->
    <tr>
        <th scope="row">
            <label for="bpid_post_cache_horas"><?php echo esc_html__('Caché (horas)', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="number"
                id="bpid_post_cache_horas"
                name="bpid_post_cache_horas"
                value="<?php echo esc_attr((string) $cache_horas); ?>"
                min="1"
                max="24"
                step="1"
                class="small-text"
            />
            <span class="description">
                <?php echo esc_html__('Tiempo en horas para mantener los resultados en caché (1-24).', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>

    <!-- Clear Cache -->
    <tr>
        <th scope="row"><?php echo esc_html__('Mantenimiento', 'bpid-suite'); ?></th>
        <td>
            <button type="button" id="bpid-post-clear-cache" class="button button-secondary">
                <?php echo esc_html__('Limpiar caché del visualizador', 'bpid-suite'); ?>
            </button>
            <span id="bpid-post-clear-cache-spinner" class="spinner" style="float:none;"></span>
            <span id="bpid-post-clear-cache-result"></span>
        </td>
    </tr>
</table>

<!-- Shortcode Preview -->
<div style="margin-top:16px;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;border-radius:2px;">
    <strong><?php echo esc_html__('Shortcode:', 'bpid-suite'); ?></strong>
    <code>[bpid_grid_visualizador id="<?php echo esc_attr((string) $post->ID); ?>"]</code>
</div>

<script>
(function () {
    'use strict';

    // Initialize color pickers if wp-color-picker is available.
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.wpColorPicker !== 'undefined') {
        jQuery('.bpid-color-picker').wpColorPicker();
    }

    // Clear cache via AJAX.
    var clearBtn     = document.getElementById('bpid-post-clear-cache');
    var clearSpinner = document.getElementById('bpid-post-clear-cache-spinner');
    var clearResult  = document.getElementById('bpid-post-clear-cache-result');

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            clearBtn.disabled = true;
            clearSpinner.classList.add('is-active');
            clearResult.textContent = '';

            var config = typeof bpidSuitePost !== 'undefined' ? bpidSuitePost : {
                ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
                nonce: <?php echo wp_json_encode(wp_create_nonce('bpid_post_clear_cache')); ?>
            };

            var formData = new FormData();
            formData.append('action', 'bpid_suite_post_clear_cache');
            formData.append('nonce', config.nonce);
            formData.append('post_id', <?php echo wp_json_encode($post->ID); ?>);

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                clearBtn.disabled = false;
                clearSpinner.classList.remove('is-active');

                if (data.success) {
                    clearResult.textContent = <?php echo wp_json_encode(__('Caché limpiado correctamente.', 'bpid-suite')); ?>;
                    clearResult.style.color = '#46b450';
                } else {
                    clearResult.textContent = data.data && data.data.message
                        ? data.data.message
                        : <?php echo wp_json_encode(__('Error al limpiar caché.', 'bpid-suite')); ?>;
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

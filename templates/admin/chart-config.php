<?php
/**
 * Metabox template: Chart configuration.
 *
 * Included by BPID_Suite_Visualizer::render_meta_box().
 * Receives $post variable from the calling context.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var WP_Post $post */

wp_nonce_field('bpid_suite_chart_admin', 'bpid_suite_chart_nonce');

$visualizer = BPID_Suite_Visualizer::get_instance();
$chart_types = $visualizer->get_chart_types();

$chart_type  = get_post_meta($post->ID, '_bpid_chart_type', true);
$column_x    = get_post_meta($post->ID, '_bpid_chart_column_x', true);
$column_y    = get_post_meta($post->ID, '_bpid_chart_column_y', true);
$group       = get_post_meta($post->ID, '_bpid_chart_group', true);
$color       = get_post_meta($post->ID, '_bpid_chart_color', true);
$height      = get_post_meta($post->ID, '_bpid_chart_height', true);
$aggregation = get_post_meta($post->ID, '_bpid_chart_aggregation', true);
$limit       = get_post_meta($post->ID, '_bpid_chart_limit', true);

$columns = [
    'dependencia',
    'numero_proyecto',
    'nombre_proyecto',
    'entidad_ejecutora',
    'numero',
    'objeto',
    'descripcion',
    'valor',
    'avance_fisico',
    'es_ops',
    'fecha_importacion',
    'fecha_actualizacion',
];
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label for="bpid_chart_type"><?php echo esc_html__('Tipo de gráfico', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select name="bpid_chart_type" id="bpid_chart_type">
                <option value=""><?php echo esc_html__('— Seleccionar —', 'bpid-suite'); ?></option>
                <?php foreach ($chart_types as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($chart_type, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_column_x"><?php echo esc_html__('Columna X', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select name="bpid_chart_column_x" id="bpid_chart_column_x">
                <option value=""><?php echo esc_html__('— Seleccionar —', 'bpid-suite'); ?></option>
                <?php foreach ($columns as $col) : ?>
                    <option value="<?php echo esc_attr($col); ?>" <?php selected($column_x, $col); ?>>
                        <?php echo esc_html($col); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_column_y"><?php echo esc_html__('Columna Y', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select name="bpid_chart_column_y" id="bpid_chart_column_y">
                <option value=""><?php echo esc_html__('— Ninguna —', 'bpid-suite'); ?></option>
                <?php foreach ($columns as $col) : ?>
                    <option value="<?php echo esc_attr($col); ?>" <?php selected($column_y, $col); ?>>
                        <?php echo esc_html($col); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_group"><?php echo esc_html__('Agrupar por', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select name="bpid_chart_group" id="bpid_chart_group">
                <option value=""><?php echo esc_html__('— Ninguno —', 'bpid-suite'); ?></option>
                <?php foreach ($columns as $col) : ?>
                    <option value="<?php echo esc_attr($col); ?>" <?php selected($group, $col); ?>>
                        <?php echo esc_html($col); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_color"><?php echo esc_html__('Color por', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="text"
                name="bpid_chart_color"
                id="bpid_chart_color"
                value="<?php echo esc_attr($color); ?>"
                class="regular-text"
                placeholder="#3498db"
            />
            <p class="description">
                <?php echo esc_html__('Color hexadecimal o nombre de columna para colorear.', 'bpid-suite'); ?>
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_height"><?php echo esc_html__('Altura (px)', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="number"
                name="bpid_chart_height"
                id="bpid_chart_height"
                value="<?php echo esc_attr($height); ?>"
                class="small-text"
                min="100"
                max="2000"
                placeholder="400"
            />
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_aggregation"><?php echo esc_html__('Agregación', 'bpid-suite'); ?></label>
        </th>
        <td>
            <select name="bpid_chart_aggregation" id="bpid_chart_aggregation">
                <option value="count" <?php selected($aggregation, 'count'); ?>>
                    <?php echo esc_html__('Conteo', 'bpid-suite'); ?>
                </option>
                <option value="sum" <?php selected($aggregation, 'sum'); ?>>
                    <?php echo esc_html__('Suma', 'bpid-suite'); ?>
                </option>
                <option value="avg" <?php selected($aggregation, 'avg'); ?>>
                    <?php echo esc_html__('Promedio', 'bpid-suite'); ?>
                </option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">
            <label for="bpid_chart_limit"><?php echo esc_html__('Límite de registros', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="number"
                name="bpid_chart_limit"
                id="bpid_chart_limit"
                value="<?php echo esc_attr($limit); ?>"
                class="small-text"
                min="0"
                placeholder="0"
            />
            <p class="description">
                <?php echo esc_html__('0 o vacío para todos los registros.', 'bpid-suite'); ?>
            </p>
        </td>
    </tr>
</table>

<!-- Shortcode Preview -->
<div style="margin-top:16px;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;border-radius:2px;">
    <strong><?php echo esc_html__('Shortcode:', 'bpid-suite'); ?></strong>
    <code>[bpid_chart id="<?php echo esc_attr((string) $post->ID); ?>"]</code>
</div>

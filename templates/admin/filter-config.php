<?php
/**
 * Metabox template: Filter configuration.
 *
 * Included by BPID_Suite_Filter::render_meta_box().
 * Receives $post variable from the calling context.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var WP_Post $post */

wp_nonce_field('bpid_suite_filter_admin', 'bpid_suite_filter_nonce');

$columns     = (array) get_post_meta($post->ID, '_bpid_filter_columns', true);
$types       = (array) get_post_meta($post->ID, '_bpid_filter_types', true);
$per_page    = (int) get_post_meta($post->ID, '_bpid_filter_per_page', true);
$show_export = (string) get_post_meta($post->ID, '_bpid_filter_show_export', true);

if ($per_page < 1) {
    $per_page = 20;
}

$allowed_columns = [
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
    'municipios',
    'fecha_importacion',
    'fecha_actualizacion',
];

$field_types_map = [
    'text'         => [
        'dependencia', 'numero_proyecto', 'nombre_proyecto', 'entidad_ejecutora',
        'numero', 'objeto', 'descripcion', 'municipios',
    ],
    'select'       => ['dependencia', 'entidad_ejecutora', 'es_ops', 'municipios'],
    'range_number' => ['valor', 'avance_fisico'],
    'range_date'   => ['fecha_importacion', 'fecha_actualizacion'],
    'checkbox'     => ['es_ops'],
];

$field_type_labels = [
    'text'         => __('Texto', 'bpid-suite'),
    'select'       => __('Selección', 'bpid-suite'),
    'range_number' => __('Rango numérico', 'bpid-suite'),
    'range_date'   => __('Rango fecha', 'bpid-suite'),
    'checkbox'     => __('Checkbox', 'bpid-suite'),
];
?>

<table class="form-table">
    <!-- Columns to Include -->
    <tr>
        <th scope="row">
            <label><?php echo esc_html__('Columnas a incluir', 'bpid-suite'); ?></label>
        </th>
        <td>
            <fieldset>
                <?php foreach ($allowed_columns as $col) : ?>
                    <label style="display:block;margin-bottom:4px;">
                        <input
                            type="checkbox"
                            name="_bpid_filter_columns[]"
                            value="<?php echo esc_attr($col); ?>"
                            <?php checked(in_array($col, $columns, true)); ?>
                        />
                        <?php echo esc_html($col); ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        </td>
    </tr>

    <!-- Field Type per Column -->
    <tr>
        <th scope="row">
            <label><?php echo esc_html__('Tipo de campo por columna', 'bpid-suite'); ?></label>
        </th>
        <td>
            <?php foreach ($allowed_columns as $col) :
                // Determine which field types are applicable for this column.
                $applicable = [];
                foreach ($field_types_map as $type => $type_columns) {
                    if (in_array($col, $type_columns, true)) {
                        $applicable[] = $type;
                    }
                }
                if (empty($applicable)) {
                    continue;
                }
                $current_type = $types[$col] ?? $applicable[0];
                ?>
                <div style="display:inline-block;margin-right:16px;margin-bottom:8px;">
                    <strong><?php echo esc_html($col); ?>:</strong>
                    <select name="_bpid_filter_types[<?php echo esc_attr($col); ?>]">
                        <?php foreach ($applicable as $type_option) : ?>
                            <option
                                value="<?php echo esc_attr($type_option); ?>"
                                <?php selected($current_type, $type_option); ?>
                            >
                                <?php echo esc_html($field_type_labels[$type_option] ?? $type_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </td>
    </tr>

    <!-- Per Page -->
    <tr>
        <th scope="row">
            <label for="bpid_filter_per_page"><?php echo esc_html__('Resultados por página', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="number"
                id="bpid_filter_per_page"
                name="_bpid_filter_per_page"
                value="<?php echo esc_attr((string) $per_page); ?>"
                min="1"
                max="100"
                step="1"
                class="small-text"
            />
        </td>
    </tr>

    <!-- Show Export -->
    <tr>
        <th scope="row">
            <label for="bpid_filter_show_export"><?php echo esc_html__('Mostrar botón exportar', 'bpid-suite'); ?></label>
        </th>
        <td>
            <input
                type="checkbox"
                id="bpid_filter_show_export"
                name="_bpid_filter_show_export"
                value="1"
                <?php checked($show_export, '1'); ?>
            />
            <span class="description">
                <?php echo esc_html__('Permite a los usuarios exportar los resultados filtrados.', 'bpid-suite'); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Shortcode Preview -->
<div style="margin-top:16px;padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;border-radius:2px;">
    <strong><?php echo esc_html__('Shortcode:', 'bpid-suite'); ?></strong>
    <code>[bpid_filter id="<?php echo esc_attr((string) $post->ID); ?>"]</code>
</div>

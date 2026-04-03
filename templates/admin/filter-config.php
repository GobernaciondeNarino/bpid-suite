<?php
/**
 * Metabox template: Filter configuration.
 *
 * Included by BPID_Suite_Filter::render_meta_box().
 * Receives $post variable from the calling context.
 *
 * @package BPID_Suite
 * @since   1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @var WP_Post $post */

wp_nonce_field('bpid_suite_filter_admin', 'bpid_suite_filter_nonce');

$columns     = (array) get_post_meta($post->ID, '_bpid_filter_columns', true);
$types       = (array) get_post_meta($post->ID, '_bpid_filter_types', true);
$operators   = (array) get_post_meta($post->ID, '_bpid_filter_operators', true);
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
    'valor_proyecto',
    'numero_contrato',
    'objeto_contrato',
    'descripcion_contrato',
    'valor_contrato',
    'avance_fisico',
    'es_ops',
    'municipios',
    'beneficiarios',
    'metas',
    'odss',
    'fecha_importacion',
    'fecha_actualizacion',
];

$field_types_map = [
    'text'         => [
        'dependencia', 'numero_proyecto', 'nombre_proyecto', 'entidad_ejecutora',
        'numero_contrato', 'objeto_contrato', 'descripcion_contrato', 'municipios', 'metas', 'odss',
    ],
    'select'       => ['dependencia', 'entidad_ejecutora', 'es_ops'],
    'range_number' => ['valor_contrato', 'valor_proyecto', 'avance_fisico', 'beneficiarios'],
    'range_date'   => ['fecha_importacion', 'fecha_actualizacion'],
    'checkbox'     => ['es_ops'],
];

$field_type_labels = [
    'text'         => __('Texto', 'bpid-suite'),
    'select'       => __('Selecci&oacute;n', 'bpid-suite'),
    'range_number' => __('Rango num&eacute;rico', 'bpid-suite'),
    'range_date'   => __('Rango fecha', 'bpid-suite'),
    'checkbox'     => __('Checkbox', 'bpid-suite'),
];

$operator_options = [
    'LIKE'  => __('Contiene (LIKE)', 'bpid-suite'),
    '='     => __('Igual (=)', 'bpid-suite'),
    '!='    => __('Diferente (!=)', 'bpid-suite'),
    '>'     => __('Mayor que (>)', 'bpid-suite'),
    '<'     => __('Menor que (<)', 'bpid-suite'),
    '>='    => __('Mayor o igual (>=)', 'bpid-suite'),
    '<='    => __('Menor o igual (<=)', 'bpid-suite'),
];

$column_labels = [
    'dependencia'           => __('Dependencia', 'bpid-suite'),
    'numero_proyecto'       => __('N&uacute;mero Proyecto', 'bpid-suite'),
    'nombre_proyecto'       => __('Nombre Proyecto', 'bpid-suite'),
    'entidad_ejecutora'     => __('Entidad Ejecutora', 'bpid-suite'),
    'valor_proyecto'        => __('Valor Proyecto', 'bpid-suite'),
    'numero_contrato'       => __('N&uacute;mero Contrato', 'bpid-suite'),
    'objeto_contrato'       => __('Objeto Contrato', 'bpid-suite'),
    'descripcion_contrato'  => __('Descripci&oacute;n Contrato', 'bpid-suite'),
    'valor_contrato'        => __('Valor Contrato', 'bpid-suite'),
    'avance_fisico'         => __('Avance F&iacute;sico', 'bpid-suite'),
    'es_ops'                => __('Es OPS', 'bpid-suite'),
    'municipios'            => __('Municipios', 'bpid-suite'),
    'beneficiarios'         => __('Beneficiarios', 'bpid-suite'),
    'metas'                 => __('Metas', 'bpid-suite'),
    'odss'                  => __('ODS', 'bpid-suite'),
    'fecha_importacion'     => __('Fecha Importaci&oacute;n', 'bpid-suite'),
    'fecha_actualizacion'   => __('Fecha Actualizaci&oacute;n', 'bpid-suite'),
];
?>

<div class="bpid-filter-config">

    <!-- ================================================================= -->
    <!-- Section A — Columns Selection (Card)                               -->
    <!-- ================================================================= -->
    <div class="bpid-filter-card">
        <div class="bpid-filter-card-header">
            <span class="dashicons dashicons-columns"></span>
            <?php esc_html_e('Columnas del Filtro', 'bpid-suite'); ?>
            <div class="bpid-filter-card-actions">
                <button type="button" class="button button-small" id="bpid-filter-select-all"><?php esc_html_e('Seleccionar Todo', 'bpid-suite'); ?></button>
                <button type="button" class="button button-small" id="bpid-filter-deselect-all"><?php esc_html_e('Deseleccionar', 'bpid-suite'); ?></button>
            </div>
        </div>
        <div class="bpid-filter-card-body">
            <p class="bpid-filter-help-text"><?php esc_html_e('Seleccione las columnas que desea incluir como filtros. Al activar una columna se habilitar&aacute; el selector de tipo de campo y operador.', 'bpid-suite'); ?></p>
            <div id="bpid-filter-columns" class="bpid-filter-columns-list">
                <?php foreach ($allowed_columns as $col) :
                    $is_checked = in_array($col, $columns, true);
                    // Determine applicable field types
                    $applicable = [];
                    foreach ($field_types_map as $type => $type_columns) {
                        if (in_array($col, $type_columns, true)) {
                            $applicable[] = $type;
                        }
                    }
                    $current_type = $types[$col] ?? ($applicable[0] ?? 'text');
                    $current_operator = $operators[$col] ?? 'LIKE';
                    $label = $column_labels[$col] ?? ucwords(str_replace('_', ' ', $col));
                    ?>
                    <div class="bpid-column-row<?php echo $is_checked ? ' bpid-column-row--active' : ''; ?>" data-column="<?php echo esc_attr($col); ?>">
                        <div class="bpid-column-row-main">
                            <label class="bpid-column-check-label">
                                <input
                                    type="checkbox"
                                    class="bpid-column-checkbox"
                                    name="_bpid_filter_columns[]"
                                    value="<?php echo esc_attr($col); ?>"
                                    <?php checked($is_checked); ?>
                                />
                                <span class="bpid-column-name"><?php echo esc_html($label); ?></span>
                                <code class="bpid-column-code"><?php echo esc_html($col); ?></code>
                            </label>

                            <div class="bpid-column-type-selector" style="<?php echo $is_checked ? '' : 'display:none;'; ?>">
                                <?php if (!empty($applicable)) : ?>
                                    <select name="_bpid_filter_types[<?php echo esc_attr($col); ?>]" class="bpid-column-type-select">
                                        <?php foreach ($applicable as $type_option) : ?>
                                            <option value="<?php echo esc_attr($type_option); ?>" <?php selected($current_type, $type_option); ?>>
                                                <?php echo esc_html($field_type_labels[$type_option] ?? $type_option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>

                                <select name="_bpid_filter_operators[<?php echo esc_attr($col); ?>]" class="bpid-column-operator-select">
                                    <?php foreach ($operator_options as $op_key => $op_label) : ?>
                                        <option value="<?php echo esc_attr($op_key); ?>" <?php selected($current_operator, $op_key); ?>>
                                            <?php echo esc_html($op_label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Individual shortcode per column -->
                        <div class="bpid-column-shortcode" style="<?php echo $is_checked ? '' : 'display:none;'; ?>">
                            <code class="bpid-column-shortcode-code">[bpid_filter id="<?php echo esc_attr((string) $post->ID); ?>" column="<?php echo esc_attr($col); ?>"]</code>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- Section B — Settings (Card)                                        -->
    <!-- ================================================================= -->
    <div class="bpid-filter-card">
        <div class="bpid-filter-card-header">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e('Configuraci&oacute;n', 'bpid-suite'); ?>
        </div>
        <div class="bpid-filter-card-body">
            <div class="bpid-filter-settings-grid">
                <div class="bpid-filter-setting">
                    <label for="bpid_filter_per_page"><?php esc_html_e('Resultados por p&aacute;gina', 'bpid-suite'); ?></label>
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
                </div>
                <div class="bpid-filter-setting">
                    <label for="bpid_filter_show_export" class="bpid-filter-toggle-label">
                        <input
                            type="checkbox"
                            id="bpid_filter_show_export"
                            name="_bpid_filter_show_export"
                            value="1"
                            <?php checked($show_export, '1'); ?>
                        />
                        <?php esc_html_e('Mostrar bot&oacute;n exportar CSV', 'bpid-suite'); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- Section C — Shortcode Preview (Card)                               -->
    <!-- ================================================================= -->
    <div class="bpid-filter-card">
        <div class="bpid-filter-card-header">
            <span class="dashicons dashicons-shortcode"></span>
            <?php esc_html_e('Shortcodes', 'bpid-suite'); ?>
        </div>
        <div class="bpid-filter-card-body">
            <div class="bpid-filter-shortcode-main">
                <strong><?php esc_html_e('Shortcode completo:', 'bpid-suite'); ?></strong>
                <code id="bpid-filter-shortcode-preview">[bpid_filter id="<?php echo esc_attr((string) $post->ID); ?>"]</code>
                <button type="button" class="button button-small bpid-copy-shortcode" data-target="bpid-filter-shortcode-preview">
                    <?php esc_html_e('Copiar', 'bpid-suite'); ?>
                </button>
            </div>
            <p class="bpid-filter-shortcode-note">
                <?php esc_html_e('Puede usar el shortcode completo o los shortcodes individuales por columna que aparecen junto a cada campo activado.', 'bpid-suite'); ?>
            </p>
        </div>
    </div>

</div><!-- .bpid-filter-config -->

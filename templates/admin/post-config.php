<?php
/**
 * Metabox template: Post GRID configuration.
 *
 * @package BPID_Suite
 * @since   1.5.0
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
$color_primario   = get_post_meta($post->ID, '_bpid_post_color_primario', true) ?: '#348afb';
$color_fondo      = get_post_meta($post->ID, '_bpid_post_color_fondo', true) ?: '#fffcf3';
$ocultar_ops      = get_post_meta($post->ID, '_bpid_post_ocultar_ops', true);
$cols_grid        = get_post_meta($post->ID, '_bpid_post_cols_grid', true) ?: 3;
$texto_intro      = get_post_meta($post->ID, '_bpid_post_texto_intro', true) ?: '';
$cache_horas      = get_post_meta($post->ID, '_bpid_post_cache_horas', true) ?: 12;

// Card config
$card_title_field = get_post_meta($post->ID, '_bpid_post_card_title_field', true) ?: 'nombre_proyecto';
$card_desc_field  = get_post_meta($post->ID, '_bpid_post_card_desc_field', true) ?: 'dependencia';
$card_extra_fields = get_post_meta($post->ID, '_bpid_post_card_extra_fields', true);
if (!is_array($card_extra_fields)) $card_extra_fields = [];

// Global style config
$default_image      = get_post_meta($post->ID, '_bpid_post_default_image', true) ?: '';
$title_font_size    = get_post_meta($post->ID, '_bpid_post_title_font_size', true) ?: '15';
$title_color        = get_post_meta($post->ID, '_bpid_post_title_color', true) ?: '#1d2327';
$desc_font_size     = get_post_meta($post->ID, '_bpid_post_desc_font_size', true) ?: '13';
$desc_color         = get_post_meta($post->ID, '_bpid_post_desc_color', true) ?: '#646970';
$secondary_color    = get_post_meta($post->ID, '_bpid_post_secondary_color', true) ?: '#348afb';

// Search bar CSS config
$search_border_color = get_post_meta($post->ID, '_bpid_post_search_border_color', true) ?: '#dcdcde';
$search_border_radius = get_post_meta($post->ID, '_bpid_post_search_border_radius', true) ?: '8';
$search_font_size    = get_post_meta($post->ID, '_bpid_post_search_font_size', true) ?: '14';
$search_bg_color     = get_post_meta($post->ID, '_bpid_post_search_bg_color', true) ?: '#ffffff';

// Stats summary fields
$stats_fields = get_post_meta($post->ID, '_bpid_post_stats_fields', true);
if (!is_array($stats_fields)) $stats_fields = [];

// Accordion config
$accordion_show_metas     = get_post_meta($post->ID, '_bpid_post_accordion_show_metas', true) ?: '1';
$accordion_show_ods       = get_post_meta($post->ID, '_bpid_post_accordion_show_ods', true) ?: '1';
$accordion_show_contratos = get_post_meta($post->ID, '_bpid_post_accordion_show_contratos', true) ?: '1';
$accordion_contrato_fields = get_post_meta($post->ID, '_bpid_post_accordion_contrato_fields', true);
if (!is_array($accordion_contrato_fields)) $accordion_contrato_fields = [];

// Load dependencias for dropdowns
$db = BPID_Suite_Database::get_instance();
$dependencias = $db->table_exists() ? $db->get_distinct_values('dependencia') : [];

// Available DB fields for selectors
$available_fields = [
    'nombre_proyecto'     => __('Nombre Proyecto', 'bpid-suite'),
    'numero_proyecto'     => __('N&uacute;mero Proyecto (BPIN)', 'bpid-suite'),
    'dependencia'         => __('Dependencia', 'bpid-suite'),
    'entidad_ejecutora'   => __('Entidad Ejecutora', 'bpid-suite'),
    'valor'               => __('Valor', 'bpid-suite'),
    'avance_fisico'       => __('Avance F&iacute;sico', 'bpid-suite'),
    'es_ops'              => __('Es OPS', 'bpid-suite'),
    'municipios'          => __('Municipios', 'bpid-suite'),
    'numero'              => __('N&uacute;mero Contrato', 'bpid-suite'),
    'objeto'              => __('Objeto Contrato', 'bpid-suite'),
    'descripcion'         => __('Descripci&oacute;n', 'bpid-suite'),
];

$aggregation_options = [
    'none'  => __('Sin agregaci&oacute;n', 'bpid-suite'),
    'SUM'   => __('Suma (SUM)', 'bpid-suite'),
    'AVG'   => __('Promedio (AVG)', 'bpid-suite'),
    'COUNT' => __('Conteo (COUNT)', 'bpid-suite'),
    'MAX'   => __('M&aacute;ximo (MAX)', 'bpid-suite'),
    'MIN'   => __('M&iacute;nimo (MIN)', 'bpid-suite'),
];
?>

<div class="bpid-postgrid-config">

    <!-- Card 1: Card Layout -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-grid-view"></span>
            <?php esc_html_e('Configuraci&oacute;n del Card (Proyecto)', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <p class="bpid-chart-help"><?php esc_html_e('Configure c&oacute;mo se ver&aacute; cada tarjeta de proyecto: imagen, t&iacute;tulo y descripci&oacute;n. Los campos recomendados se seleccionan autom&aacute;ticamente.', 'bpid-suite'); ?></p>

            <div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
                <div class="bpid-chart-form-group">
                    <label for="bpid_post_card_title_field"><?php esc_html_e('Campo para T&iacute;tulo', 'bpid-suite'); ?></label>
                    <select name="bpid_post_card_title_field" id="bpid_post_card_title_field" class="bpid-chart-select">
                        <?php foreach ($available_fields as $fk => $fl) : ?>
                            <option value="<?php echo esc_attr($fk); ?>" <?php selected($card_title_field, $fk); ?>><?php echo esc_html($fl); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="bpid-chart-help"><?php esc_html_e('Recomendado: Nombre Proyecto', 'bpid-suite'); ?></span>
                </div>
                <div class="bpid-chart-form-group">
                    <label for="bpid_post_card_desc_field"><?php esc_html_e('Campo para Descripci&oacute;n', 'bpid-suite'); ?></label>
                    <select name="bpid_post_card_desc_field" id="bpid_post_card_desc_field" class="bpid-chart-select">
                        <?php foreach ($available_fields as $fk => $fl) : ?>
                            <option value="<?php echo esc_attr($fk); ?>" <?php selected($card_desc_field, $fk); ?>><?php echo esc_html($fl); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="bpid-chart-help"><?php esc_html_e('Recomendado: Dependencia', 'bpid-suite'); ?></span>
                </div>
            </div>

            <!-- Extra card fields via AJAX -->
            <div class="bpid-postgrid-extra-section">
                <label class="bpid-chart-y-label"><?php esc_html_e('Campos adicionales en el Card', 'bpid-suite'); ?></label>
                <div id="bpid-post-card-extra-rows" class="bpid-chart-y-rows">
                    <!-- Populated by JS -->
                </div>
                <button type="button" class="button button-secondary bpid-add-y-btn" id="bpid-post-add-card-field">
                    <span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span>
                    <?php esc_html_e('Agregar campo al Card', 'bpid-suite'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Card 2: Stats Summary -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Resumen / Estad&iacute;sticas', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <label class="bpid-chart-toggle">
                <input type="hidden" name="bpid_post_mostrar_stats" value="0">
                <input type="checkbox" name="bpid_post_mostrar_stats" value="1" <?php checked($mostrar_stats, '1'); ?> />
                <span><?php esc_html_e('Mostrar secci&oacute;n de estad&iacute;sticas', 'bpid-suite'); ?></span>
            </label>

            <div class="bpid-postgrid-extra-section" style="margin-top:16px;">
                <label class="bpid-chart-y-label"><?php esc_html_e('Datos del resumen (configurables)', 'bpid-suite'); ?></label>
                <p class="bpid-chart-help"><?php esc_html_e('Agregue campos con su agregaci&oacute;n (suma, promedio, conteo, etc.) y un texto personalizado.', 'bpid-suite'); ?></p>
                <div id="bpid-post-stats-rows" class="bpid-chart-y-rows">
                    <!-- Populated by JS -->
                </div>
                <button type="button" class="button button-secondary bpid-add-y-btn" id="bpid-post-add-stat">
                    <span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span>
                    <?php esc_html_e('Agregar dato al resumen', 'bpid-suite'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Card 3: Search Bar Style -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e('Campo de B&uacute;squeda', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <label class="bpid-chart-toggle" style="margin-bottom:16px;">
                <input type="hidden" name="bpid_post_mostrar_buscador" value="0">
                <input type="checkbox" name="bpid_post_mostrar_buscador" value="1" <?php checked($mostrar_buscador, '1'); ?> />
                <span><?php esc_html_e('Mostrar campo de b&uacute;squeda', 'bpid-suite'); ?></span>
            </label>

            <div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color de borde', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_search_border_color" value="<?php echo esc_attr($search_border_color); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Border radius (px)', 'bpid-suite'); ?></label>
                    <input type="number" name="bpid_post_search_border_radius" value="<?php echo esc_attr($search_border_radius); ?>" min="0" max="50" class="bpid-chart-input-sm" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Tama&ntilde;o de fuente (px)', 'bpid-suite'); ?></label>
                    <input type="number" name="bpid_post_search_font_size" value="<?php echo esc_attr($search_font_size); ?>" min="10" max="24" class="bpid-chart-input-sm" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color de fondo', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_search_bg_color" value="<?php echo esc_attr($search_bg_color); ?>" class="y-color-input" />
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Global Styles -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-art"></span>
            <?php esc_html_e('Estilos Globales', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color primario', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_color_primario" value="<?php echo esc_attr($color_primario); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color de fondo', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_color_fondo" value="<?php echo esc_attr($color_fondo); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color del t&iacute;tulo', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_title_color" value="<?php echo esc_attr($title_color); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Tama&ntilde;o del t&iacute;tulo (px)', 'bpid-suite'); ?></label>
                    <input type="number" name="bpid_post_title_font_size" value="<?php echo esc_attr($title_font_size); ?>" min="10" max="30" class="bpid-chart-input-sm" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color de descripci&oacute;n', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_desc_color" value="<?php echo esc_attr($desc_color); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Tama&ntilde;o descripci&oacute;n (px)', 'bpid-suite'); ?></label>
                    <input type="number" name="bpid_post_desc_font_size" value="<?php echo esc_attr($desc_font_size); ?>" min="10" max="24" class="bpid-chart-input-sm" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Color valores secundarios', 'bpid-suite'); ?></label>
                    <input type="color" name="bpid_post_secondary_color" value="<?php echo esc_attr($secondary_color); ?>" class="y-color-input" />
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Columnas del grid', 'bpid-suite'); ?></label>
                    <select name="bpid_post_cols_grid" class="bpid-chart-select">
                        <?php for ($i = 1; $i <= 4; $i++) : ?>
                            <option value="<?php echo esc_attr((string) $i); ?>" <?php selected((int) $cols_grid, $i); ?>><?php echo esc_html((string) $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="bpid-chart-form-group" style="margin-top:16px;">
                <label><?php esc_html_e('Imagen por defecto (URL) para proyectos sin imagen', 'bpid-suite'); ?></label>
                <input type="url" name="bpid_post_default_image" value="<?php echo esc_attr($default_image); ?>" class="bpid-chart-input" placeholder="https://ejemplo.com/imagen.jpg" />
                <span class="bpid-chart-help"><?php esc_html_e('Se mostrar&aacute; cuando un proyecto no tenga im&aacute;genes en sus contratos.', 'bpid-suite'); ?></span>
            </div>
        </div>
    </div>

    <!-- Card 5: Accordion Modal Config -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-editor-kitchensink"></span>
            <?php esc_html_e('Ventana Flotante (Modal / Acorde&oacute;n)', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <p class="bpid-chart-help"><?php esc_html_e('Configure qu&eacute; secciones mostrar en el acorde&oacute;n al abrir los detalles de un proyecto.', 'bpid-suite'); ?></p>

            <div class="bpid-chart-toggles">
                <label class="bpid-chart-toggle">
                    <input type="hidden" name="bpid_post_accordion_show_metas" value="0">
                    <input type="checkbox" name="bpid_post_accordion_show_metas" value="1" <?php checked($accordion_show_metas, '1'); ?> />
                    <span><?php esc_html_e('Mostrar Metas del proyecto', 'bpid-suite'); ?></span>
                </label>
                <label class="bpid-chart-toggle">
                    <input type="hidden" name="bpid_post_accordion_show_ods" value="0">
                    <input type="checkbox" name="bpid_post_accordion_show_ods" value="1" <?php checked($accordion_show_ods, '1'); ?> />
                    <span><?php esc_html_e('Mostrar ODS relacionados', 'bpid-suite'); ?></span>
                </label>
                <label class="bpid-chart-toggle">
                    <input type="hidden" name="bpid_post_accordion_show_contratos" value="0">
                    <input type="checkbox" name="bpid_post_accordion_show_contratos" value="1" <?php checked($accordion_show_contratos, '1'); ?> />
                    <span><?php esc_html_e('Mostrar Contratos', 'bpid-suite'); ?></span>
                </label>
            </div>

            <div class="bpid-postgrid-extra-section" style="margin-top:16px;">
                <label class="bpid-chart-y-label"><?php esc_html_e('Campos visibles en cada contrato del acorde&oacute;n', 'bpid-suite'); ?></label>
                <div id="bpid-post-accordion-fields" class="bpid-chart-y-rows">
                    <!-- Populated by JS -->
                </div>
                <button type="button" class="button button-secondary bpid-add-y-btn" id="bpid-post-add-accordion-field">
                    <span class="dashicons dashicons-plus-alt2" style="margin-top:4px;"></span>
                    <?php esc_html_e('Agregar campo al acorde&oacute;n', 'bpid-suite'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Card 6: Filters & Advanced -->
    <div class="bpid-chart-card">
        <div class="bpid-chart-card-header">
            <span class="dashicons dashicons-filter"></span>
            <?php esc_html_e('Filtros y Opciones Avanzadas', 'bpid-suite'); ?>
        </div>
        <div class="bpid-chart-card-body">
            <div class="bpid-chart-toggles" style="margin-bottom:16px;">
                <label class="bpid-chart-toggle">
                    <input type="hidden" name="bpid_post_mostrar_filtros" value="0">
                    <input type="checkbox" name="bpid_post_mostrar_filtros" value="1" <?php checked($mostrar_filtros, '1'); ?> />
                    <span><?php esc_html_e('Mostrar filtros (dependencia, municipio, ODS)', 'bpid-suite'); ?></span>
                </label>
                <label class="bpid-chart-toggle">
                    <input type="hidden" name="bpid_post_ocultar_ops" value="0">
                    <input type="checkbox" name="bpid_post_ocultar_ops" value="1" <?php checked($ocultar_ops, '1'); ?> />
                    <span><?php esc_html_e('Ocultar OPS en contratos', 'bpid-suite'); ?></span>
                </label>
            </div>

            <div class="bpid-chart-form-grid bpid-chart-form-grid--2col">
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Filtrar por dependencia', 'bpid-suite'); ?></label>
                    <select name="bpid_post_filtro_dependencia" class="bpid-chart-select">
                        <option value=""><?php esc_html_e('— Todas —', 'bpid-suite'); ?></option>
                        <?php foreach ($dependencias as $dep) : ?>
                            <option value="<?php echo esc_attr($dep); ?>" <?php selected($filtro_dep, $dep); ?>><?php echo esc_html($dep); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bpid-chart-form-group">
                    <label><?php esc_html_e('Cach&eacute; (horas)', 'bpid-suite'); ?></label>
                    <input type="number" name="bpid_post_cache_horas" value="<?php echo esc_attr((string) $cache_horas); ?>" min="1" max="24" class="bpid-chart-input-sm" />
                </div>
            </div>

            <div class="bpid-chart-form-group" style="margin-top:12px;">
                <label><?php esc_html_e('Texto introductorio', 'bpid-suite'); ?></label>
                <textarea name="bpid_post_texto_intro" rows="3" class="bpid-chart-input" style="max-width:100%;" placeholder="<?php esc_attr_e('Texto opcional antes del grid...', 'bpid-suite'); ?>"><?php echo esc_textarea($texto_intro); ?></textarea>
            </div>

            <div style="margin-top:16px;">
                <button type="button" id="bpid-post-clear-cache" class="button button-secondary">
                    <span class="dashicons dashicons-update" style="margin-top:4px;"></span>
                    <?php esc_html_e('Limpiar cach&eacute;', 'bpid-suite'); ?>
                </button>
                <span id="bpid-post-clear-cache-spinner" class="spinner" style="float:none;"></span>
                <span id="bpid-post-clear-cache-result"></span>
            </div>
        </div>
    </div>

    <!-- Shortcode -->
    <div class="bpid-chart-shortcode-bar">
        <span class="dashicons dashicons-shortcode"></span>
        <strong><?php esc_html_e('Shortcode:', 'bpid-suite'); ?></strong>
        <code id="bpid-postgrid-shortcode">[bpid_grid_visualizador id="<?php echo esc_attr((string) $post->ID); ?>"]</code>
        <button type="button" class="button button-small bpid-copy-shortcode" data-target="bpid-postgrid-shortcode">
            <?php esc_html_e('Copiar', 'bpid-suite'); ?>
        </button>
    </div>

</div>

<script type="application/json" id="bpid-postgrid-saved-extra-fields"><?php echo wp_json_encode($card_extra_fields); ?></script>
<script type="application/json" id="bpid-postgrid-saved-stats-fields"><?php echo wp_json_encode($stats_fields); ?></script>
<script type="application/json" id="bpid-postgrid-saved-accordion-fields"><?php echo wp_json_encode($accordion_contrato_fields); ?></script>
<script type="application/json" id="bpid-postgrid-available-fields"><?php echo wp_json_encode($available_fields); ?></script>
<script type="application/json" id="bpid-postgrid-aggregation-options"><?php echo wp_json_encode($aggregation_options); ?></script>
<script type="application/json" id="bpid-postgrid-admin-config"><?php echo wp_json_encode([
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('bpid_post_clear_cache'),
    'postId'  => $post->ID,
]); ?></script>

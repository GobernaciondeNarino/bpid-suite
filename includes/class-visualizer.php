<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Visualizer v2.0
 *
 * Manages the 'bpid_chart' Custom Post Type for chart configurations
 * and renders d3plus-based visualizations via shortcode.
 *
 * @package BPID_Suite
 * @since   2.0.0
 */
final class BPID_Suite_Visualizer {

    private static ?self $instance = null;

    /** @var string[] Supported chart types */
    private const CHART_TYPES = [
        'bar', 'bar_horizontal', 'bar_stacked', 'bar_grouped',
        'line', 'area', 'area_stacked',
        'pie', 'donut', 'treemap', 'radar', 'heatmap', 'plot',
    ];

    /** @var string[] Allowed aggregation functions */
    private const ALLOWED_AGG = ['SUM', 'AVG', 'COUNT', 'MAX', 'MIN'];

    /** @var string[] Default color palette */
    private const DEFAULT_PALETTE = [
        '#3eba6a', '#e84c4c', '#4a90d9', '#f5a623',
        '#9b59b6', '#1abc9c', '#844c00', '#ff7300',
    ];

    /** @var string[] Allowed number formats */
    private const NUMBER_FORMATS = ['es-CO', 'en-US', 'de-DE', 'compact', 'raw'];

    /** @var string[] Allowed value scales */
    private const VALUE_SCALES = ['full', 'thousands', 'millions', 'billions'];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
        add_shortcode('bpid_chart', [$this, 'shortcode_render']);

        // AJAX endpoints
        add_action('wp_ajax_bpid_get_tables', [$this, 'ajax_get_tables']);
        add_action('wp_ajax_bpid_get_columns', [$this, 'ajax_get_columns']);
        add_action('wp_ajax_bpid_get_filter_values', [$this, 'ajax_get_filter_values']);
        add_action('wp_ajax_bpid_chart_preview', [$this, 'ajax_chart_preview']);
        add_action('wp_ajax_bpid_chart_data', [$this, 'ajax_chart_data']);
    }

    private function __clone() {}
    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /* =========================================================================
       CPT Registration
       ========================================================================= */

    public function register_post_type(): void {
        $labels = [
            'name'               => __('BPID Gráficos', 'bpid-suite'),
            'singular_name'      => __('Gráfico', 'bpid-suite'),
            'add_new'            => __('Añadir nuevo', 'bpid-suite'),
            'add_new_item'       => __('Añadir nuevo gráfico', 'bpid-suite'),
            'edit_item'          => __('Editar gráfico', 'bpid-suite'),
            'new_item'           => __('Nuevo gráfico', 'bpid-suite'),
            'view_item'          => __('Ver gráfico', 'bpid-suite'),
            'search_items'       => __('Buscar gráficos', 'bpid-suite'),
            'not_found'          => __('No se encontraron gráficos', 'bpid-suite'),
            'not_found_in_trash' => __('No se encontraron gráficos en la papelera', 'bpid-suite'),
            'all_items'          => __('Gráficos', 'bpid-suite'),
            'menu_name'          => __('Gráficos', 'bpid-suite'),
        ];

        register_post_type('bpid_chart', [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'bpid-suite',
            'supports'        => ['title'],
            'capability_type' => 'post',
        ]);
    }

    public function get_chart_types(): array {
        return [
            'bar'            => __('Barras', 'bpid-suite'),
            'bar_horizontal' => __('Barras Horizontales', 'bpid-suite'),
            'bar_stacked'    => __('Barras Apiladas', 'bpid-suite'),
            'bar_grouped'    => __('Barras Agrupadas', 'bpid-suite'),
            'line'           => __('Líneas', 'bpid-suite'),
            'area'           => __('Área', 'bpid-suite'),
            'area_stacked'   => __('Área Apilada', 'bpid-suite'),
            'pie'            => __('Torta', 'bpid-suite'),
            'donut'          => __('Dona', 'bpid-suite'),
            'treemap'        => __('Treemap', 'bpid-suite'),
            'radar'          => __('Radar', 'bpid-suite'),
            'heatmap'        => __('Mapa de Calor', 'bpid-suite'),
            'plot'           => __('Dispersión', 'bpid-suite'),
        ];
    }

    /* =========================================================================
       Meta Boxes
       ========================================================================= */

    public function add_meta_boxes(): void {
        // Main configuration
        add_meta_box(
            'bpid_chart_config',
            __('Configuración del gráfico', 'bpid-suite'),
            [$this, 'render_meta_box'],
            'bpid_chart',
            'normal',
            'high'
        );

        // Shortcode sidebar
        add_meta_box(
            'bpid_chart_shortcode',
            __('Shortcode', 'bpid-suite'),
            [$this, 'render_shortcode_box'],
            'bpid_chart',
            'side',
            'high'
        );

        // Preview
        add_meta_box(
            'bpid_chart_preview',
            __('Vista Previa', 'bpid-suite'),
            [$this, 'render_preview_box'],
            'bpid_chart',
            'normal',
            'low'
        );
    }

    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('bpid_suite_chart_admin', 'bpid_suite_chart_nonce');
        include BPID_SUITE_PATH . 'templates/admin/chart-config.php';
    }

    public function render_shortcode_box(\WP_Post $post): void {
        $post_id = $post->ID;
        ?>
        <div class="bpid-shortcode-box">
            <code id="bpid-chart-shortcode">[bpid_chart id="<?php echo esc_attr((string) $post_id); ?>"]</code>
            <button type="button" class="button" onclick="navigator.clipboard.writeText(document.getElementById('bpid-chart-shortcode').textContent);this.textContent='¡Copiado!';setTimeout(()=>{this.textContent='Copiar Shortcode'},1500);">
                <?php esc_html_e('Copiar Shortcode', 'bpid-suite'); ?>
            </button>
            <p class="bpid-shortcode-help">
                <?php esc_html_e('Copia y pega este shortcode en cualquier página o entrada.', 'bpid-suite'); ?>
            </p>
        </div>
        <?php
    }

    public function render_preview_box(\WP_Post $post): void {
        ?>
        <div style="margin-bottom:12px;">
            <button type="button" id="btn-update-preview" class="button button-primary">
                <span class="dashicons dashicons-update" style="margin-top:4px"></span>
                <?php esc_html_e('Actualizar Vista Previa', 'bpid-suite'); ?>
            </button>
        </div>
        <div id="chart-preview-container">
            <p class="bpid-preview-placeholder">
                <?php esc_html_e('Configure la gráfica y haga clic en "Actualizar Vista Previa" para ver el resultado.', 'bpid-suite'); ?>
            </p>
        </div>
        <?php
    }

    /* =========================================================================
       Save Meta Box
       ========================================================================= */

    public function save_meta_box(int $post_id, \WP_Post $post): void {
        if (
            !isset($_POST['bpid_suite_chart_nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['bpid_suite_chart_nonce'])),
                'bpid_suite_chart_admin'
            )
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if ('bpid_chart' !== $post->post_type) {
            return;
        }

        // Chart type
        $chart_type = sanitize_text_field(wp_unslash($_POST['chart_type'] ?? ''));
        if (in_array($chart_type, self::CHART_TYPES, true)) {
            update_post_meta($post_id, '_chart_type', $chart_type);
        }

        // Data table
        $table = sanitize_text_field(wp_unslash($_POST['chart_data_table'] ?? ''));
        if (!empty($table) && $this->validate_table_name($table)) {
            update_post_meta($post_id, '_chart_data_table', $table);
        } else {
            delete_post_meta($post_id, '_chart_data_table');
        }

        // Axis X
        $axis_x = sanitize_text_field(wp_unslash($_POST['chart_axis_x'] ?? ''));
        if (!empty($axis_x)) {
            update_post_meta($post_id, '_chart_axis_x', $axis_x);
        } else {
            delete_post_meta($post_id, '_chart_axis_x');
        }

        // Y columns & colors (arrays)
        $y_columns = isset($_POST['chart_y_columns']) && is_array($_POST['chart_y_columns'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['chart_y_columns']))
            : [];
        $y_colors = isset($_POST['chart_y_colors']) && is_array($_POST['chart_y_colors'])
            ? array_map(function ($c) {
                $c = sanitize_text_field($c);
                return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '';
            }, wp_unslash($_POST['chart_y_colors']))
            : [];

        // Fill missing colors from palette
        foreach ($y_columns as $i => $col) {
            if (empty($y_colors[$i])) {
                $y_colors[$i] = self::DEFAULT_PALETTE[$i % count(self::DEFAULT_PALETTE)];
            }
        }

        // Remove empty columns
        $filtered_columns = [];
        $filtered_colors = [];
        foreach ($y_columns as $i => $col) {
            if (!empty($col)) {
                $filtered_columns[] = $col;
                $filtered_colors[] = $y_colors[$i] ?? self::DEFAULT_PALETTE[$i % count(self::DEFAULT_PALETTE)];
            }
        }

        update_post_meta($post_id, '_chart_y_columns', $filtered_columns);
        update_post_meta($post_id, '_chart_y_colors', $filtered_colors);

        // Aggregation function
        $agg = strtoupper(sanitize_text_field(wp_unslash($_POST['chart_agg_function'] ?? 'SUM')));
        if (in_array($agg, self::ALLOWED_AGG, true)) {
            update_post_meta($post_id, '_chart_agg_function', $agg);
        }

        // Filters
        $filter_year = absint($_POST['chart_filter_year'] ?? 0);
        update_post_meta($post_id, '_chart_filter_year', (string) $filter_year);

        $filter_month = absint($_POST['chart_filter_month'] ?? 0);
        if ($filter_month > 12) $filter_month = 0;
        update_post_meta($post_id, '_chart_filter_month', (string) $filter_month);

        // Appearance
        $height = absint($_POST['chart_height'] ?? 400);
        if ($height < 200) $height = 200;
        if ($height > 900) $height = 900;
        update_post_meta($post_id, '_chart_height', (string) $height);

        $title_y = sanitize_text_field(wp_unslash($_POST['chart_title_y'] ?? ''));
        update_post_meta($post_id, '_chart_title_y', $title_y);

        $title_x = sanitize_text_field(wp_unslash($_POST['chart_title_x'] ?? ''));
        update_post_meta($post_id, '_chart_title_x', $title_x);

        $number_format = sanitize_text_field(wp_unslash($_POST['chart_number_format'] ?? 'es-CO'));
        if (!in_array($number_format, self::NUMBER_FORMATS, true)) {
            $number_format = 'es-CO';
        }
        update_post_meta($post_id, '_chart_number_format', $number_format);

        $value_scale = sanitize_text_field(wp_unslash($_POST['chart_value_scale'] ?? 'full'));
        if (!in_array($value_scale, self::VALUE_SCALES, true)) {
            $value_scale = 'full';
        }
        update_post_meta($post_id, '_chart_value_scale', $value_scale);

        $color_palette = sanitize_text_field(wp_unslash($_POST['chart_color_palette'] ?? ''));
        update_post_meta($post_id, '_chart_color_palette', $color_palette);

        $tooltip_text = sanitize_text_field(wp_unslash($_POST['chart_tooltip_text'] ?? ''));
        update_post_meta($post_id, '_chart_tooltip_text', $tooltip_text);

        // Booleans
        $bool_fields = [
            'chart_show_legend', 'chart_show_timeline',
            'chart_toolbar_show', 'chart_toolbar_info', 'chart_toolbar_share',
            'chart_toolbar_data', 'chart_toolbar_save_img', 'chart_toolbar_csv',
        ];
        foreach ($bool_fields as $field) {
            $value = isset($_POST[$field]) ? '1' : '0';
            update_post_meta($post_id, '_' . $field, $value);
        }

        // Group By
        $group_by = sanitize_text_field(wp_unslash($_POST['chart_group_by'] ?? ''));
        update_post_meta($post_id, '_chart_group_by', $group_by);

        $group_vigencia = isset($_POST['chart_group_by_vigencia']) ? '1' : '0';
        update_post_meta($post_id, '_chart_group_by_vigencia', $group_vigencia);

        // Advanced filters (dynamic rows)
        $raw_filters = $_POST['chart_adv_filters'] ?? [];
        $adv_filters = [];
        if (is_array($raw_filters)) {
            foreach ($raw_filters as $f) {
                $col = sanitize_text_field(wp_unslash($f['column'] ?? ''));
                $op  = sanitize_text_field(wp_unslash($f['operator'] ?? '='));
                $val = sanitize_text_field(wp_unslash($f['value'] ?? ''));
                if (!empty($col) && !empty($val)) {
                    $allowed_ops = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
                    if (!in_array($op, $allowed_ops, true)) {
                        $op = '=';
                    }
                    $adv_filters[] = ['column' => $col, 'operator' => $op, 'value' => $val];
                }
            }
        }
        update_post_meta($post_id, '_chart_adv_filters', $adv_filters);

        // Query limit
        $query_limit = absint($_POST['chart_query_limit'] ?? 1000);
        if ($query_limit < 1) $query_limit = 1000;
        if ($query_limit > 50000) $query_limit = 50000;
        update_post_meta($post_id, '_chart_query_limit', (string) $query_limit);

        // Query order by
        $query_orderby = sanitize_text_field(wp_unslash($_POST['chart_query_orderby'] ?? ''));
        update_post_meta($post_id, '_chart_query_orderby', $query_orderby);

        $query_order = strtoupper(sanitize_text_field(wp_unslash($_POST['chart_query_order'] ?? 'ASC')));
        if (!in_array($query_order, ['ASC', 'DESC'], true)) {
            $query_order = 'ASC';
        }
        update_post_meta($post_id, '_chart_query_order', $query_order);

        // Custom query (SELECT only)
        $custom_query = wp_unslash($_POST['chart_custom_query'] ?? '');
        $custom_query = sanitize_textarea_field($custom_query);
        if (!empty($custom_query) && !$this->is_safe_query($custom_query)) {
            $custom_query = '';
        }
        update_post_meta($post_id, '_chart_custom_query', $custom_query);
    }

    /* =========================================================================
       Shortcode Render
       ========================================================================= */

    public function shortcode_render($atts): string {
        $atts = shortcode_atts(
            ['id' => 0, 'width' => '', 'height' => '', 'class' => ''],
            $atts,
            'bpid_chart'
        );

        $post_id = absint($atts['id']);
        if (0 === $post_id || 'bpid_chart' !== get_post_type($post_id)) {
            return '';
        }

        $config = $this->build_config($post_id);
        if (empty($config['type']) || empty($config['axis_x'])) {
            return '';
        }

        // Override height/width from shortcode attributes
        if (!empty($atts['height'])) {
            $config['height'] = absint($atts['height']);
        }

        $data = $this->get_chart_data($post_id);
        if (empty($data)) {
            return '';
        }

        // Enqueue d3plus (includes d3.js)
        wp_enqueue_script(
            'bpid-d3plus',
            'https://cdn.jsdelivr.net/npm/@d3plus/core',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'bpid-suite-frontend-charts',
            BPID_SUITE_URL . 'assets/js/frontend.js',
            ['bpid-d3plus'],
            BPID_SUITE_VERSION,
            true
        );

        wp_enqueue_style(
            'bpid-suite-frontend',
            BPID_SUITE_URL . 'assets/css/frontend.css',
            [],
            BPID_SUITE_VERSION
        );

        $chart_id     = (string) $post_id;
        $chart_type   = $config['type'];
        $chart_data   = $data;
        $chart_config = $config;
        $height       = $config['height'];
        $width        = !empty($atts['width']) ? $atts['width'] : '100%';
        $extra_class  = !empty($atts['class']) ? sanitize_html_class($atts['class']) : '';

        ob_start();
        include BPID_SUITE_PATH . 'templates/frontend/chart.php';
        return (string) ob_get_clean();
    }

    /* =========================================================================
       Config Builder
       ========================================================================= */

    private function build_config(int $post_id): array {
        $y_columns = get_post_meta($post_id, '_chart_y_columns', true);
        $y_colors  = get_post_meta($post_id, '_chart_y_colors', true);

        $palette_str = get_post_meta($post_id, '_chart_color_palette', true);
        $palette = !empty($palette_str)
            ? array_filter(array_map('trim', explode(',', $palette_str)), function ($c) {
                return preg_match('/^#[0-9a-fA-F]{6}$/', $c);
            })
            : self::DEFAULT_PALETTE;

        // Resolve virtual column names to clean aliases for frontend display
        $axis_x_raw = get_post_meta($post_id, '_chart_axis_x', true);
        $axis_x_display = $this->resolve_virtual_column_alias($axis_x_raw);

        $y_cols_raw = is_array($y_columns) ? $y_columns : [];
        $y_cols_display = array_map([$this, 'resolve_virtual_column_alias'], $y_cols_raw);

        return [
            'type'          => get_post_meta($post_id, '_chart_type', true) ?: 'bar',
            'table'         => get_post_meta($post_id, '_chart_data_table', true),
            'axis_x'        => $axis_x_display,
            'axis_x_raw'    => $axis_x_raw,
            'y_columns'     => $y_cols_display,
            'y_columns_raw' => $y_cols_raw,
            'y_colors'      => is_array($y_colors) ? $y_colors : [],
            'agg_function'  => get_post_meta($post_id, '_chart_agg_function', true) ?: 'SUM',
            'height'        => absint(get_post_meta($post_id, '_chart_height', true) ?: 400),
            'title'         => get_the_title($post_id),
            'title_y'       => get_post_meta($post_id, '_chart_title_y', true) ?: '',
            'title_x'       => get_post_meta($post_id, '_chart_title_x', true) ?: '',
            'number_format'  => get_post_meta($post_id, '_chart_number_format', true) ?: 'es-CO',
            'value_scale'    => get_post_meta($post_id, '_chart_value_scale', true) ?: 'full',
            'color_palette'  => array_values($palette),
            'show_legend'    => (bool) get_post_meta($post_id, '_chart_show_legend', true),
            'show_timeline'  => (bool) get_post_meta($post_id, '_chart_show_timeline', true),
            'toolbar'        => [
                'show'     => (bool) get_post_meta($post_id, '_chart_toolbar_show', true),
                'info'     => (bool) get_post_meta($post_id, '_chart_toolbar_info', true),
                'share'    => (bool) get_post_meta($post_id, '_chart_toolbar_share', true),
                'data'     => (bool) get_post_meta($post_id, '_chart_toolbar_data', true),
                'save_img' => (bool) get_post_meta($post_id, '_chart_toolbar_save_img', true),
                'csv'      => (bool) get_post_meta($post_id, '_chart_toolbar_csv', true),
            ],
            'filters' => [
                'year'  => absint(get_post_meta($post_id, '_chart_filter_year', true)),
                'month' => absint(get_post_meta($post_id, '_chart_filter_month', true)),
            ],
            'group_by'         => get_post_meta($post_id, '_chart_group_by', true) ?: '',
            'group_by_vigencia' => (bool) get_post_meta($post_id, '_chart_group_by_vigencia', true),
            'adv_filters'      => get_post_meta($post_id, '_chart_adv_filters', true) ?: [],
            'query_limit'      => absint(get_post_meta($post_id, '_chart_query_limit', true) ?: 1000),
            'query_orderby'    => get_post_meta($post_id, '_chart_query_orderby', true) ?: '',
            'query_order'      => get_post_meta($post_id, '_chart_query_order', true) ?: 'ASC',
            'tooltip_text'     => get_post_meta($post_id, '_chart_tooltip_text', true) ?: '',
        ];
    }

    /* =========================================================================
       Data Query
       ========================================================================= */

    public function get_chart_data(int $post_id): array {
        $config = $this->build_config($post_id);

        // Custom query takes precedence
        $custom_query = get_post_meta($post_id, '_chart_custom_query', true);
        if (!empty($custom_query) && $this->is_safe_query($custom_query)) {
            return $this->execute_custom_query($custom_query);
        }

        $table = $config['table'];
        $raw_axis_x = $config['axis_x_raw'] ?? $config['axis_x'];
        $y_columns = $config['y_columns_raw'] ?? $config['y_columns'];
        $agg = $config['agg_function'];
        $group_by = $config['group_by'] ?? '';
        $group_vigencia = $config['group_by_vigencia'] ?? false;
        $chart_type = $config['type'] ?? 'bar';

        if (empty($table) || empty($raw_axis_x) || empty($y_columns)) {
            return [];
        }

        // Validate table
        if (!$this->validate_table_name($table)) {
            return [];
        }

        $valid_columns = $this->get_table_columns($table);
        $db = BPID_Suite_Database::get_instance();
        $is_main_table = ($table === $db->get_table_name());

        global $wpdb;
        $agg_func = in_array($agg, self::ALLOWED_AGG, true) ? $agg : 'SUM';

        // ── Detect relational (virtual) columns ──
        // Virtual columns from relational tables: "⟶ municipio (individual)", etc.
        $relational_map = [
            '⟶ municipio (individual)'   => ['table' => $db->get_table_municipios(), 'col' => 'municipio',  'alias' => 'municipio'],
            '⟶ ods (individual)'         => ['table' => $db->get_table_odss(),       'col' => 'ods',        'alias' => 'ods'],
            '⟶ meta_texto (individual)'  => ['table' => $db->get_table_metas(),      'col' => 'meta_texto', 'alias' => 'meta_texto'],
            '⟶ municipio_beneficiarios'  => ['table' => $db->get_table_municipios(), 'col' => 'beneficiarios', 'alias' => 'beneficiarios_municipio'],
        ];

        $axis_x = $raw_axis_x;
        $join_sql = '';
        $x_expr = '';
        $x_alias = '';
        $join_table_alias = 'r';

        if ($is_main_table && isset($relational_map[$raw_axis_x])) {
            $rel = $relational_map[$raw_axis_x];
            $rel_table = $rel['table'];
            $rel_col   = $rel['col'];
            $x_alias   = $rel['alias'];
            $join_sql  = " JOIN `$rel_table` AS $join_table_alias ON c.id = $join_table_alias.contrato_id";
            $x_expr    = "$join_table_alias.`$rel_col`";
            $axis_x    = $x_alias; // for output
        } elseif (in_array($raw_axis_x, $valid_columns, true)) {
            $x_expr  = "c.`$raw_axis_x`";
            $x_alias = $raw_axis_x;
        } else {
            return [];
        }

        // Also check if group_by is a relational column
        $group_join_sql = '';
        $group_table_alias = 'g';
        $raw_group_by = $group_by;

        if ($is_main_table && isset($relational_map[$group_by])) {
            $grel = $relational_map[$group_by];
            $grel_table = $grel['table'];
            $grel_col   = $grel['col'];
            // Avoid duplicate join if same table
            if ($grel_table !== ($relational_map[$raw_axis_x]['table'] ?? '')) {
                $group_join_sql = " JOIN `$grel_table` AS $group_table_alias ON c.id = $group_table_alias.contrato_id";
            } else {
                $group_table_alias = $join_table_alias; // reuse same alias
            }
            $group_by = $grel['alias'];
        }

        // Also check if Y columns are relational
        $y_join_sql = '';
        $y_table_alias = 'yrel';
        $y_col_exprs = [];

        foreach ($y_columns as $ycol) {
            if ($is_main_table && isset($relational_map[$ycol])) {
                $yrel = $relational_map[$ycol];
                // Beneficiarios from municipio table
                if ($yrel['table'] === ($relational_map[$raw_axis_x]['table'] ?? '') && !empty($join_sql)) {
                    $y_col_exprs[$ycol] = "$join_table_alias.`{$yrel['col']}`";
                } else {
                    $y_join_sql = " JOIN `{$yrel['table']}` AS $y_table_alias ON c.id = $y_table_alias.contrato_id";
                    $y_col_exprs[$ycol] = "$y_table_alias.`{$yrel['col']}`";
                }
            } elseif (in_array($ycol, $valid_columns, true)) {
                $y_col_exprs[$ycol] = "c.`$ycol`";
            }
        }

        if (empty($y_col_exprs)) {
            return [];
        }

        // Table reference (alias 'c' for main table when using joins)
        $from_table = !empty($join_sql) || !empty($group_join_sql) || !empty($y_join_sql)
            ? "`$table` AS c"
            : "`$table` AS c";

        // Determine effective group-by
        $effective_group = '';
        if ($group_vigencia) {
            $effective_group = '__vigencia__';
        } elseif (!empty($group_by)) {
            if ($is_main_table && isset($relational_map[$raw_group_by])) {
                $effective_group = $group_by; // already resolved
            } elseif (in_array($group_by, $valid_columns, true)) {
                $effective_group = $group_by;
            }
        }

        // Build WHERE
        $where = '1=1';
        $year = $config['filters']['year'] ?? 0;
        $month = $config['filters']['month'] ?? 0;

        if ($year > 0) {
            $where .= $wpdb->prepare(' AND YEAR(c.fecha_importacion) = %d', $year);
        }
        if ($month > 0 && $month <= 12) {
            $where .= $wpdb->prepare(' AND MONTH(c.fecha_importacion) = %d', $month);
        }

        // Advanced filters (dynamic WHERE clauses)
        $adv_filters = $config['adv_filters'] ?? [];
        if (is_array($adv_filters)) {
            $allowed_ops = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
            foreach ($adv_filters as $f) {
                $f_col = $f['column'] ?? '';
                $f_op  = $f['operator'] ?? '=';
                $f_val = $f['value'] ?? '';

                if (empty($f_col) || empty($f_val) || !in_array($f_op, $allowed_ops, true)) {
                    continue;
                }

                // Resolve relational virtual columns for filters
                if ($is_main_table && isset($relational_map[$f_col])) {
                    $frel = $relational_map[$f_col];
                    // Use existing join alias if it matches, otherwise use main table alias
                    $f_expr = "c.`" . $frel['alias'] . "`";
                    // For relational filters, we actually need to filter on the joined table
                    // This is a simplified approach — filter on the main table's flattened column
                    if (in_array($frel['alias'], $valid_columns, true)) {
                        $f_expr = "c.`{$frel['alias']}`";
                    } else {
                        // Skip this filter if we can't resolve it safely
                        continue;
                    }
                } elseif (in_array($f_col, $valid_columns, true)) {
                    $f_expr = "c.`$f_col`";
                } else {
                    continue; // skip invalid column
                }

                if ($f_op === 'LIKE') {
                    $where .= $wpdb->prepare(" AND $f_expr LIKE %s", $f_val);
                } else {
                    $where .= $wpdb->prepare(" AND $f_expr {$f_op} %s", $f_val);
                }
            }
        }

        // Query config: limit, order
        $query_limit = max(1, min(50000, $config['query_limit'] ?? 1000));
        $query_orderby_col = $config['query_orderby'] ?? '';
        $query_order = in_array($config['query_order'] ?? 'ASC', ['ASC', 'DESC'], true) ? ($config['query_order'] ?? 'ASC') : 'ASC';

        $full_join = $join_sql . $group_join_sql . $y_join_sql;

        // ── Heatmap mode ──
        if ($chart_type === 'heatmap' && count($y_col_exprs) >= 1) {
            $first_y_key = array_key_first($y_col_exprs);
            $first_y_expr = $y_col_exprs[$first_y_key];

            $heatmap_group = $effective_group;
            if (empty($heatmap_group) || $heatmap_group === '__vigencia__') {
                $group_expr = 'YEAR(c.fecha_importacion)';
                $group_alias_hm = 'vigencia';
            } elseif ($is_main_table && isset($relational_map[$raw_group_by])) {
                $grel = $relational_map[$raw_group_by];
                $group_expr = "{$group_table_alias}.`{$grel['col']}`";
                $group_alias_hm = $grel['alias'];
            } else {
                $group_expr = "c.`$heatmap_group`";
                $group_alias_hm = $heatmap_group;
            }

            $select = "$x_expr AS `$x_alias`, $group_expr AS `$group_alias_hm`, $agg_func($first_y_expr) AS `value`";
            $group = "`$x_alias`, `$group_alias_hm`";

            // Build ORDER BY clause
            $hm_order = !empty($query_orderby_col) && in_array($query_orderby_col, $valid_columns, true)
                ? "c.`$query_orderby_col` $query_order"
                : "`$group_alias_hm` ASC, `$x_alias` ASC";

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT $select FROM $from_table $full_join WHERE $where GROUP BY $group ORDER BY $hm_order LIMIT $query_limit";

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $results = $wpdb->get_results($sql, ARRAY_A);

            return is_array($results) ? $results : [];
        }

        // ── Group By mode ──
        // Group By only adds an extra dimension to GROUP BY without pivoting.
        // The group column value is concatenated with the X-axis label so the
        // chart renders as a normal (non-pivoted) chart.
        if (!empty($effective_group)) {
            if ($effective_group === '__vigencia__') {
                $group_expr = 'YEAR(c.fecha_importacion)';
                $group_alias_gb = 'vigencia';
            } elseif ($is_main_table && isset($relational_map[$raw_group_by])) {
                $grel = $relational_map[$raw_group_by];
                $group_expr = "{$group_table_alias}.`{$grel['col']}`";
                $group_alias_gb = $grel['alias'];
            } else {
                $group_expr = "c.`$effective_group`";
                $group_alias_gb = $effective_group;
            }

            // Build SELECT: X axis, group column, then all Y columns aggregated
            $select_parts = [
                "CONCAT($x_expr, ' — ', $group_expr) AS `$x_alias`",
                "$group_expr AS `$group_alias_gb`",
            ];
            foreach ($y_col_exprs as $col_key => $col_expr) {
                $col_alias = preg_replace('/[^a-zA-Z0-9_]/', '_', $col_key);
                $select_parts[] = "$agg_func($col_expr) AS `$col_alias`";
            }

            $select = implode(', ', $select_parts);
            $group = "$x_expr, $group_expr";

            $gb_order = !empty($query_orderby_col) && in_array($query_orderby_col, $valid_columns, true)
                ? "c.`$query_orderby_col` $query_order"
                : "$group_expr ASC, $x_expr ASC";

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = "SELECT $select FROM $from_table $full_join WHERE $where GROUP BY $group ORDER BY $gb_order LIMIT $query_limit";

            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $results = $wpdb->get_results($sql, ARRAY_A);

            if (!is_array($results)) {
                return [];
            }

            // Remove the extra group column from the output — it was only used
            // for ordering.  The X-axis label already contains the group info.
            foreach ($results as &$row) {
                unset($row[$group_alias_gb]);
            }
            unset($row);

            return $results;
        }

        // ── Standard mode (no group by) ──
        $select_parts = ["$x_expr AS `$x_alias`"];
        foreach ($y_col_exprs as $col_key => $col_expr) {
            $col_alias = preg_replace('/[^a-zA-Z0-9_]/', '_', $col_key);
            $select_parts[] = "$agg_func($col_expr) AS `$col_alias`";
        }

        $select = implode(', ', $select_parts);

        // Custom order or default to X axis
        $std_order = !empty($query_orderby_col) && in_array($query_orderby_col, $valid_columns, true)
            ? "c.`$query_orderby_col` $query_order"
            : "`$x_alias` $query_order";

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT $select FROM $from_table $full_join WHERE $where GROUP BY `$x_alias` ORDER BY $std_order LIMIT $query_limit";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results($sql, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    private function execute_custom_query(string $query): array {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query validated via is_safe_query
        $results = $wpdb->get_results($query, ARRAY_A);
        return is_array($results) ? $results : [];
    }

    /* =========================================================================
       AJAX Handlers
       ========================================================================= */

    public function ajax_get_tables(): void {
        check_ajax_referer('bpid_charts_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        global $wpdb;
        $prefix = $wpdb->prefix;

        // Get tables related to bpid
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $all_tables = $wpdb->get_col("SHOW TABLES");
        $tables = [];
        foreach ($all_tables as $t) {
            if (
                str_starts_with($t, $prefix . 'bpid_')
                || $t === $prefix . 'bpid_suite_contratos'
            ) {
                $tables[] = $t;
            }
        }

        wp_send_json_success($tables);
    }

    public function ajax_get_columns(): void {
        check_ajax_referer('bpid_charts_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $table = sanitize_text_field(wp_unslash($_POST['table'] ?? ''));
        if (empty($table) || !$this->validate_table_name($table)) {
            wp_send_json_error('Invalid table');
        }

        // Get columns with their types for color-coding
        $columns_with_types = $this->get_table_columns_typed($table);

        // If this is the main contratos table, add virtual relational columns
        $db = BPID_Suite_Database::get_instance();
        if ($table === $db->get_table_name()) {
            $columns_with_types[] = [
                'name'  => '⟶ municipio (individual)',
                'type'  => 'text',
                'table' => 'bpid_contrato_municipios',
            ];
            $columns_with_types[] = [
                'name'  => '⟶ ods (individual)',
                'type'  => 'text',
                'table' => 'bpid_contrato_odss',
            ];
            $columns_with_types[] = [
                'name'  => '⟶ meta_texto (individual)',
                'type'  => 'text',
                'table' => 'bpid_contrato_metas',
            ];
            $columns_with_types[] = [
                'name'  => '⟶ municipio_beneficiarios',
                'type'  => 'number',
                'table' => 'bpid_contrato_municipios',
            ];
        }

        wp_send_json_success($columns_with_types);
    }

    public function ajax_get_filter_values(): void {
        check_ajax_referer('bpid_charts_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $table = sanitize_text_field(wp_unslash($_POST['table'] ?? ''));
        $column = sanitize_text_field(wp_unslash($_POST['column'] ?? ''));

        if (empty($table) || !$this->validate_table_name($table)) {
            wp_send_json_error('Invalid table');
        }

        $valid_columns = $this->get_table_columns($table);
        if (!in_array($column, $valid_columns, true)) {
            wp_send_json_error('Invalid column');
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $values = $wpdb->get_col("SELECT DISTINCT `$column` FROM `$table` WHERE `$column` IS NOT NULL ORDER BY `$column` ASC LIMIT 500");

        wp_send_json_success($values ?: []);
    }

    public function ajax_chart_preview(): void {
        check_ajax_referer('bpid_charts_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $post_id = absint($_POST['post_ID'] ?? 0);
        if ($post_id < 1 || 'bpid_chart' !== get_post_type($post_id)) {
            wp_send_json_error('Invalid post ID');
        }

        // Save current form data to post meta so build_config/get_chart_data work
        $post = get_post($post_id);
        if ($post) {
            $this->save_meta_box($post_id, $post);
        }

        // Build config and get data using the saved meta
        $config = $this->build_config($post_id);
        if (empty($config['type']) || empty($config['axis_x'])) {
            wp_send_json_success('<p style="text-align:center;color:#999;padding:30px;">Configure el tipo de gráfico, eje X y al menos una variable Y.</p>');
            return;
        }

        $data = $this->get_chart_data($post_id);
        if (empty($data)) {
            wp_send_json_success('<p style="text-align:center;color:#999;padding:30px;">Sin datos para la configuración actual. Verifique la tabla, columnas y filtros.</p>');
            return;
        }

        $chart_id     = 'preview-' . $post_id;
        $chart_type   = $config['type'];
        $chart_data   = $data;
        $chart_config = $config;
        $height       = $config['height'];
        $width        = '100%';
        $extra_class  = 'bpid-chart-preview-render';
        $json_flags   = JSON_HEX_TAG | JSON_HEX_AMP;

        // Render the chart container + JSON data inline
        $html = '<div class="bpid-chart-container ' . esc_attr($extra_class) . '"'
            . ' id="bpid-chart-' . esc_attr($chart_id) . '"'
            . ' style="min-height:' . esc_attr((string) $height) . 'px;width:' . $width . '"'
            . ' data-chart-type="' . esc_attr($chart_type) . '"'
            . ' data-chart-id="' . esc_attr($chart_id) . '">'
            . '</div>';

        $html .= '<script type="application/json" id="bpid-chart-config-' . esc_attr($chart_id) . '">'
            . wp_json_encode($chart_config, $json_flags)
            . '</script>';

        $html .= '<script type="application/json" id="bpid-chart-data-' . esc_attr($chart_id) . '">'
            . wp_json_encode($chart_data, $json_flags)
            . '</script>';

        // Inline JS to initialize the preview chart immediately
        $html .= '<script>'
            . '(function(){'
            . 'if(typeof d3plus==="undefined"){document.getElementById("bpid-chart-' . esc_js($chart_id) . '").innerHTML="<p style=\\"color:#c00;text-align:center;padding:20px\\">d3plus no disponible. Guarde y use el shortcode.</p>";return;}'
            . 'var c=document.getElementById("bpid-chart-' . esc_js($chart_id) . '");'
            . 'var configEl=document.getElementById("bpid-chart-config-' . esc_js($chart_id) . '");'
            . 'var dataEl=document.getElementById("bpid-chart-data-' . esc_js($chart_id) . '");'
            . 'if(!c||!dataEl)return;'
            . 'try{'
            . 'var cfg=JSON.parse(configEl.textContent);'
            . 'var dat=JSON.parse(dataEl.textContent);'
            . 'c.innerHTML="";'
            . 'c.style.height="' . esc_js((string) $height) . 'px";'
            . 'if(typeof bpidBuildPreviewChart==="function"){bpidBuildPreviewChart(c,cfg,dat);}'
            . 'else{c.innerHTML="<p style=\\"color:#666;text-align:center;padding:20px\\">Datos: "+dat.length+" registros. Guarde y visualice con el shortcode.</p>";}'
            . '}catch(e){c.innerHTML="<p style=\\"color:#c00\\">Error: "+e.message+"</p>";}'
            . '})();'
            . '</script>';

        wp_send_json_success($html);
    }

    public function ajax_chart_data(): void {
        check_ajax_referer('bpid_charts_nonce');

        $post_id = absint($_POST['chart_id'] ?? $_GET['chart_id'] ?? 0);
        if ($post_id < 1 || 'bpid_chart' !== get_post_type($post_id)) {
            wp_send_json_error('Invalid chart');
        }

        $data = $this->get_chart_data($post_id);
        wp_send_json_success($data);
    }

    /* =========================================================================
       Validation Helpers
       ========================================================================= */

    private function validate_table_name(string $table): bool {
        // Only allow alphanumeric, underscores and must exist in DB
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        global $wpdb;
        $all_tables = $wpdb->get_col("SHOW TABLES");
        return in_array($table, $all_tables, true);
    }

    private function get_table_columns(string $table): array {
        if (!$this->validate_table_name($table)) {
            return [];
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");
        return is_array($columns) ? $columns : [];
    }

    /**
     * Get columns with their MySQL types for the admin UI color coding.
     *
     * @return array<int, array{name: string, type: string, table: string}>
     */
    private function get_table_columns_typed(string $table): array {
        if (!$this->validate_table_name($table)) {
            return [];
        }

        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results("SHOW COLUMNS FROM `$table`", ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        // Extract short table name for display
        $short_table = preg_replace('/^' . preg_quote($wpdb->prefix, '/') . '/', '', $table);

        $result = [];
        foreach ($rows as $row) {
            $raw_type = strtolower($row['Type'] ?? '');
            // Classify: numeric vs text
            $type = 'text'; // default
            if (preg_match('/^(int|bigint|smallint|tinyint|decimal|float|double|numeric)/', $raw_type)) {
                $type = 'number';
            } elseif (preg_match('/^(date|datetime|timestamp|time|year)/', $raw_type)) {
                $type = 'date';
            }

            $result[] = [
                'name'  => $row['Field'],
                'type'  => $type,
                'table' => $short_table,
            ];
        }

        return $result;
    }

    /**
     * Resolve virtual column names (with arrow prefix) to clean aliases.
     */
    private function resolve_virtual_column_alias(string $col): string {
        $map = [
            '⟶ municipio (individual)'   => 'municipio',
            '⟶ ods (individual)'         => 'ods',
            '⟶ meta_texto (individual)'  => 'meta_texto',
            '⟶ municipio_beneficiarios'  => 'beneficiarios_municipio',
        ];
        return $map[$col] ?? $col;
    }

    private function is_safe_query(string $query): bool {
        $query = trim($query);
        if (empty($query)) {
            return false;
        }

        // Must start with SELECT
        if (!preg_match('/^\s*SELECT\b/i', $query)) {
            return false;
        }

        // Reject dangerous keywords
        $forbidden = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER',
            'TRUNCATE', 'CREATE', 'REPLACE', 'GRANT', 'REVOKE',
            'EXEC', 'EXECUTE', 'INTO\s+OUTFILE', 'INTO\s+DUMPFILE',
            'LOAD_FILE', 'BENCHMARK', 'SLEEP',
        ];
        $pattern = '/\b(' . implode('|', $forbidden) . ')\b/i';
        if (preg_match($pattern, $query)) {
            return false;
        }

        // No semicolons (prevent multi-statement)
        if (str_contains($query, ';')) {
            return false;
        }

        return true;
    }
}

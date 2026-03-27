<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Visualizer v2.0
 *
 * Manages the 'bpid_chart' Custom Post Type for chart configurations
 * and renders Chart.js-based visualizations via shortcode.
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
        'pie', 'donut', 'treemap', 'radar',
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

        $color_palette = sanitize_text_field(wp_unslash($_POST['chart_color_palette'] ?? ''));
        update_post_meta($post_id, '_chart_color_palette', $color_palette);

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

        // Enqueue Chart.js
        wp_enqueue_script(
            'bpid-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            null,
            true
        );

        // D3plus fallback for legacy types
        $d3plus_types = ['treemap', 'tree', 'pack', 'network', 'scatter', 'box_whisker', 'matrix', 'bump'];
        if (in_array($config['type'], $d3plus_types, true)) {
            wp_enqueue_script(
                'bpid-d3plus',
                'https://cdn.jsdelivr.net/npm/d3plus@2/build/d3plus.full.min.js',
                [],
                null,
                true
            );
        }

        wp_enqueue_script(
            'bpid-suite-frontend-charts',
            BPID_SUITE_URL . 'assets/js/frontend.js',
            ['bpid-chartjs'],
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

        return [
            'type'          => get_post_meta($post_id, '_chart_type', true) ?: 'bar',
            'table'         => get_post_meta($post_id, '_chart_data_table', true),
            'axis_x'        => get_post_meta($post_id, '_chart_axis_x', true),
            'y_columns'     => is_array($y_columns) ? $y_columns : [],
            'y_colors'      => is_array($y_colors) ? $y_colors : [],
            'agg_function'  => get_post_meta($post_id, '_chart_agg_function', true) ?: 'SUM',
            'height'        => absint(get_post_meta($post_id, '_chart_height', true) ?: 400),
            'title'         => get_the_title($post_id),
            'title_y'       => get_post_meta($post_id, '_chart_title_y', true) ?: '',
            'title_x'       => get_post_meta($post_id, '_chart_title_x', true) ?: '',
            'number_format'  => get_post_meta($post_id, '_chart_number_format', true) ?: 'es-CO',
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
        $axis_x = $config['axis_x'];
        $y_columns = $config['y_columns'];
        $agg = $config['agg_function'];

        if (empty($table) || empty($axis_x) || empty($y_columns)) {
            return [];
        }

        // Validate table and columns
        if (!$this->validate_table_name($table)) {
            return [];
        }

        $valid_columns = $this->get_table_columns($table);
        if (!in_array($axis_x, $valid_columns, true)) {
            return [];
        }

        global $wpdb;

        // Build SELECT
        $select_parts = ["`$axis_x`"];
        foreach ($y_columns as $col) {
            if (!in_array($col, $valid_columns, true)) {
                continue;
            }
            $agg_func = in_array($agg, self::ALLOWED_AGG, true) ? $agg : 'SUM';
            $select_parts[] = "$agg_func(`$col`) AS `$col`";
        }

        if (count($select_parts) < 2) {
            return [];
        }

        $select = implode(', ', $select_parts);

        // Build WHERE
        $where = '1=1';
        $year = $config['filters']['year'] ?? 0;
        $month = $config['filters']['month'] ?? 0;

        if ($year > 0) {
            $where .= $wpdb->prepare(' AND YEAR(fecha_importacion) = %d', $year);
        }
        if ($month > 0 && $month <= 12) {
            $where .= $wpdb->prepare(' AND MONTH(fecha_importacion) = %d', $month);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table/column names validated above
        $sql = "SELECT $select FROM `$table` WHERE $where GROUP BY `$axis_x` ORDER BY `$axis_x` ASC LIMIT 1000";

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

        $columns = $this->get_table_columns($table);
        wp_send_json_success($columns);
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

        // Build temporary config from POST data
        $post_id = absint($_POST['post_ID'] ?? 0);
        if ($post_id < 1) {
            wp_send_json_error('Invalid post ID');
        }

        // Temporarily save meta to generate preview, then render
        wp_send_json_success([
            'html' => '<p style="text-align:center;color:#666;padding:40px;">Vista previa disponible después de guardar. Guarde el gráfico primero.</p>',
        ]);
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

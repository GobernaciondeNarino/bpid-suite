<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Filter
 *
 * Manages the 'bpid_filter' Custom Post Type for configurable
 * frontend filter forms that query the BPID contracts table.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Filter {

    private static ?self $instance = null;

    /** @var string[] Columns allowed for filtering. */
    private array $allowed_columns = [
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

    /**
     * Get the singleton instance.
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box']);
        add_shortcode('bpid_filter', [$this, 'shortcode_render']);
        add_action('wp_ajax_bpid_suite_filter_query', [$this, 'ajax_filter_query']);
        add_action('wp_ajax_nopriv_bpid_suite_filter_query', [$this, 'ajax_filter_query']);
        add_action('wp_ajax_bpid_filter_column_values', [$this, 'ajax_column_values']);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Register the bpid_filter custom post type.
     */
    public function register_post_type(): void {
        register_post_type('bpid_filter', [
            'labels'       => [
                'name'               => __('BPID Filtros', 'bpid-suite'),
                'singular_name'      => __('Filtro', 'bpid-suite'),
                'add_new'            => __('Agregar nuevo', 'bpid-suite'),
                'add_new_item'       => __('Agregar nuevo filtro', 'bpid-suite'),
                'edit_item'          => __('Editar filtro', 'bpid-suite'),
                'new_item'           => __('Nuevo filtro', 'bpid-suite'),
                'view_item'          => __('Ver filtro', 'bpid-suite'),
                'search_items'       => __('Buscar filtros', 'bpid-suite'),
                'not_found'          => __('No se encontraron filtros', 'bpid-suite'),
                'not_found_in_trash' => __('No se encontraron filtros en la papelera', 'bpid-suite'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bpid-suite',
            'supports'     => ['title'],
        ]);
    }

    /**
     * Get the mapping of field types to applicable columns.
     *
     * @return array<string, string[]>
     */
    public function get_field_types(): array {
        return [
            'text'         => [
                'dependencia',
                'numero_proyecto',
                'nombre_proyecto',
                'entidad_ejecutora',
                'numero_contrato',
                'objeto_contrato',
                'descripcion_contrato',
                'municipios',
                'metas',
                'odss',
            ],
            'select'       => [
                'dependencia',
                'entidad_ejecutora',
                'es_ops',
            ],
            'range_number' => [
                'valor_contrato',
                'valor_proyecto',
                'avance_fisico',
                'beneficiarios',
            ],
            'range_date'   => [
                'fecha_importacion',
                'fecha_actualizacion',
            ],
            'checkbox'     => [
                'es_ops',
            ],
        ];
    }

    /**
     * Register the meta box for filter configuration.
     */
    public function add_meta_box(): void {
        add_meta_box(
            'bpid_filter_config',
            __('Configuraci&oacute;n del filtro', 'bpid-suite'),
            [$this, 'render_meta_box'],
            'bpid_filter',
            'normal',
            'high'
        );
    }

    /**
     * Render the filter configuration meta box.
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_meta_box(\WP_Post $post): void {
        include BPID_SUITE_PATH . 'templates/admin/filter-config.php';
    }

    /**
     * Save meta box data on post save.
     *
     * @param int $post_id Post ID being saved.
     */
    public function save_meta_box(int $post_id): void {
        // Verify nonce.
        if (
            !isset($_POST['bpid_suite_filter_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['bpid_suite_filter_nonce'])),
                'bpid_suite_filter_admin'
            )
        ) {
            return;
        }

        // Check autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check capability.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type.
        if (get_post_type($post_id) !== 'bpid_filter') {
            return;
        }

        // Sanitize and save columns.
        $raw_columns = isset($_POST['_bpid_filter_columns']) && is_array($_POST['_bpid_filter_columns'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['_bpid_filter_columns']))
            : [];
        $columns = array_values(array_intersect($raw_columns, $this->allowed_columns));
        update_post_meta($post_id, '_bpid_filter_columns', $columns);

        // Sanitize and save types.
        $raw_types = isset($_POST['_bpid_filter_types']) && is_array($_POST['_bpid_filter_types'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['_bpid_filter_types']))
            : [];
        $valid_type_keys = array_keys($this->get_field_types());
        $types = [];
        foreach ($raw_types as $col => $type) {
            $col  = sanitize_text_field($col);
            $type = sanitize_text_field($type);
            if (in_array($col, $this->allowed_columns, true) && in_array($type, $valid_type_keys, true)) {
                $types[$col] = $type;
            }
        }
        update_post_meta($post_id, '_bpid_filter_types', $types);

        // Save per page.
        $per_page = isset($_POST['_bpid_filter_per_page'])
            ? min(max((int) $_POST['_bpid_filter_per_page'], 1), 100)
            : 20;
        update_post_meta($post_id, '_bpid_filter_per_page', $per_page);

        // Save show export.
        $show_export = !empty($_POST['_bpid_filter_show_export']) ? '1' : '0';
        update_post_meta($post_id, '_bpid_filter_show_export', $show_export);

        // Save operators per column.
        $allowed_operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];
        $raw_operators = isset($_POST['_bpid_filter_operators']) && is_array($_POST['_bpid_filter_operators'])
            ? array_map('sanitize_text_field', wp_unslash($_POST['_bpid_filter_operators']))
            : [];
        $operators = [];
        foreach ($raw_operators as $col => $op) {
            $col = sanitize_text_field($col);
            $op  = strtoupper(trim(sanitize_text_field($op)));
            if (in_array($col, $this->allowed_columns, true) && in_array($op, $allowed_operators, true)) {
                $operators[$col] = $op;
            }
        }
        update_post_meta($post_id, '_bpid_filter_operators', $operators);
    }

    /**
     * Render the [bpid_filter] shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function shortcode_render($atts): string {
        $atts = shortcode_atts([
            'id'    => 0,
            'class' => '',
        ], $atts, 'bpid_filter');

        $post_id = (int) $atts['id'];
        if ($post_id < 1 || get_post_type($post_id) !== 'bpid_filter') {
            return '';
        }

        $columns     = (array) get_post_meta($post_id, '_bpid_filter_columns', true);
        $types       = (array) get_post_meta($post_id, '_bpid_filter_types', true);
        $per_page    = (int) get_post_meta($post_id, '_bpid_filter_per_page', true);
        $show_export = (string) get_post_meta($post_id, '_bpid_filter_show_export', true);

        if ($per_page < 1) {
            $per_page = 20;
        }

        // Filter columns to only those in the whitelist.
        $columns = array_values(array_intersect($columns, $this->allowed_columns));
        if (empty($columns)) {
            return '';
        }

        $field_types = $this->get_field_types();
        $css_class   = sanitize_html_class($atts['class']);
        $wrapper_cls = 'bpid-filter-wrap' . ($css_class ? ' ' . $css_class : '');

        ob_start();
        ?>
        <div class="<?php echo esc_attr($wrapper_cls); ?>" data-filter-id="<?php echo esc_attr((string) $post_id); ?>">
            <form class="bpid-filter-form" data-per-page="<?php echo esc_attr((string) $per_page); ?>">
                <?php wp_nonce_field('bpid_suite_filter_query', 'bpid_filter_nonce', false); ?>
                <?php foreach ($columns as $col) :
                    $type = $types[$col] ?? 'text';
                    if (!in_array($type, array_keys($field_types), true)) {
                        $type = 'text';
                    }
                    $label = ucwords(str_replace('_', ' ', $col));
                    ?>
                    <div class="bpid-filter-field" data-column="<?php echo esc_attr($col); ?>" data-type="<?php echo esc_attr($type); ?>">
                        <label><?php echo esc_html($label); ?></label>
                        <?php if ('text' === $type) : ?>
                            <input type="text" name="<?php echo esc_attr($col); ?>" />
                        <?php elseif ('select' === $type) : ?>
                            <select name="<?php echo esc_attr($col); ?>">
                                <option value=""><?php esc_html_e('-- Todos --', 'bpid-suite'); ?></option>
                            </select>
                        <?php elseif ('range_number' === $type) : ?>
                            <input type="number" name="<?php echo esc_attr($col); ?>_min" placeholder="<?php esc_attr_e('M&iacute;n', 'bpid-suite'); ?>" />
                            <input type="number" name="<?php echo esc_attr($col); ?>_max" placeholder="<?php esc_attr_e('M&aacute;x', 'bpid-suite'); ?>" />
                        <?php elseif ('range_date' === $type) : ?>
                            <input type="date" name="<?php echo esc_attr($col); ?>_min" />
                            <input type="date" name="<?php echo esc_attr($col); ?>_max" />
                        <?php elseif ('checkbox' === $type) : ?>
                            <input type="checkbox" name="<?php echo esc_attr($col); ?>" value="1" />
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <div class="bpid-filter-actions">
                    <button type="submit" class="bpid-filter-submit"><?php esc_html_e('Filtrar', 'bpid-suite'); ?></button>
                    <button type="reset" class="bpid-filter-reset"><?php esc_html_e('Limpiar', 'bpid-suite'); ?></button>
                    <?php if ('1' === $show_export) : ?>
                        <button type="button" class="bpid-filter-export"><?php esc_html_e('Exportar', 'bpid-suite'); ?></button>
                    <?php endif; ?>
                </div>
            </form>
            <div class="bpid-filter-results"></div>
        </div>
        <?php

        $config = [
            'id'          => $post_id,
            'columns'     => $columns,
            'types'       => $types,
            'perPage'     => $per_page,
            'showExport'  => '1' === $show_export,
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('bpid_suite_filter_query'),
        ];
        ?>
        <script type="application/json" class="bpid-filter-config"><?php
            echo wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP);
        ?></script>
        <?php

        wp_enqueue_script(
            'bpid-suite-frontend-filters',
            BPID_SUITE_URL . 'assets/js/frontend-filters.js',
            [],
            BPID_SUITE_VERSION,
            true
        );

        return ob_get_clean();
    }

    /**
     * AJAX handler for frontend filter queries.
     *
     * Validates nonce, enforces rate limiting, checks column and operator
     * whitelists, builds a prepared query, and returns paginated JSON results.
     */
    public function ajax_filter_query(): void {
        // Rate limiting: max 60 requests per minute per IP.
        $ip_hash       = 'bpid_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $request_count = (int) get_transient($ip_hash);
        if ($request_count >= 60) {
            wp_send_json_error(
                ['message' => __('Demasiadas solicitudes. Intente de nuevo en un minuto.', 'bpid-suite')],
                429
            );
        }
        set_transient($ip_hash, $request_count + 1, MINUTE_IN_SECONDS);

        // Verify nonce.
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'bpid_suite_filter_query')
        ) {
            wp_send_json_error(
                ['message' => __('Nonce de seguridad inv&aacute;lido.', 'bpid-suite')],
                403
            );
        }

        $allowed_operators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE'];

        // Parse filters.
        $raw_filters = isset($_POST['filters']) && is_array($_POST['filters'])
            ? wp_unslash($_POST['filters'])
            : [];

        $page     = max((int) ($_POST['page'] ?? 1), 1);
        $per_page = min(max((int) ($_POST['per_page'] ?? 20), 1), 100);
        $offset   = ($page - 1) * $per_page;

        global $wpdb;
        $db    = BPID_Suite_Database::get_instance();
        $table = $db->get_table_name();

        $where  = [];
        $values = [];

        foreach ($raw_filters as $filter) {
            if (!is_array($filter)) {
                continue;
            }

            $column   = sanitize_text_field((string) ($filter['column'] ?? ''));
            $operator = strtoupper(trim(sanitize_text_field((string) ($filter['operator'] ?? '='))));
            $value    = sanitize_text_field((string) ($filter['value'] ?? ''));

            // Validate column against whitelist.
            if (!in_array($column, $this->allowed_columns, true)) {
                continue;
            }

            // Validate operator against whitelist.
            if (!in_array($operator, $allowed_operators, true)) {
                continue;
            }

            if ('LIKE' === $operator) {
                $where[]  = "`{$column}` LIKE %s";
                $values[] = '%' . $wpdb->esc_like($value) . '%';
            } else {
                $where[]  = "`{$column}` {$operator} %s";
                $values[] = $value;
            }
        }

        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        // Count total matching rows.
        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $count_sql = $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$table}` {$where_sql}",
                ...$values
            );
        } else {
            $count_sql = "SELECT COUNT(*) FROM `{$table}`";
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total = (int) $wpdb->get_var($count_sql);

        // Build data query.
        $data_values = array_merge($values, [$per_page, $offset]);

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $data_sql = $wpdb->prepare(
                "SELECT * FROM `{$table}` {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d",
                ...$data_values
            );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $data_sql = $wpdb->prepare(
                "SELECT * FROM `{$table}` ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $data = $wpdb->get_results($data_sql, ARRAY_A);

        wp_send_json_success([
            'data'     => $data ?: [],
            'total'    => $total,
            'pages'    => (int) ceil($total / $per_page),
            'page'     => $page,
            'per_page' => $per_page,
        ]);
    }

    /**
     * AJAX handler to return distinct column values for select-type fields.
     */
    public function ajax_column_values(): void {
        check_ajax_referer('bpid_filter_admin_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $column = sanitize_text_field(wp_unslash($_POST['column'] ?? ''));
        if (!in_array($column, $this->allowed_columns, true)) {
            wp_send_json_error('Invalid column');
        }

        global $wpdb;
        $db    = BPID_Suite_Database::get_instance();
        $table = $db->get_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $values = $wpdb->get_col(
            "SELECT DISTINCT `{$column}` FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` != '' ORDER BY `{$column}` ASC LIMIT 500"
        );

        wp_send_json_success($values ?: []);
    }
}

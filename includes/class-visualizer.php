<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Visualizer
 *
 * Manages the 'bpid_chart' Custom Post Type for chart configurations
 * and renders D3plus-based visualizations via shortcode.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Visualizer {

    private static ?self $instance = null;

    /** @var string[] Allowed database columns for chart axes */
    private const ALLOWED_COLUMNS = [
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

    /** @var string[] Allowed aggregation methods */
    private const ALLOWED_AGGREGATIONS = ['count', 'sum', 'avg'];

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
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
        add_shortcode('bpid_chart', [$this, 'shortcode_render']);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException(
            esc_html__('Cannot unserialize singleton', 'bpid-suite')
        );
    }

    /**
     * Register the bpid_chart Custom Post Type.
     */
    public function register_post_type(): void {
        $labels = [
            'name'               => __('BPID Gr&aacute;ficos', 'bpid-suite'),
            'singular_name'      => __('Gr&aacute;fico', 'bpid-suite'),
            'add_new'            => __('A&ntilde;adir nuevo', 'bpid-suite'),
            'add_new_item'       => __('A&ntilde;adir nuevo gr&aacute;fico', 'bpid-suite'),
            'edit_item'          => __('Editar gr&aacute;fico', 'bpid-suite'),
            'new_item'           => __('Nuevo gr&aacute;fico', 'bpid-suite'),
            'view_item'          => __('Ver gr&aacute;fico', 'bpid-suite'),
            'search_items'       => __('Buscar gr&aacute;ficos', 'bpid-suite'),
            'not_found'          => __('No se encontraron gr&aacute;ficos', 'bpid-suite'),
            'not_found_in_trash' => __('No se encontraron gr&aacute;ficos en la papelera', 'bpid-suite'),
            'all_items'          => __('Gr&aacute;ficos', 'bpid-suite'),
            'menu_name'          => __('Gr&aacute;ficos', 'bpid-suite'),
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'bpid-suite',
            'supports'        => ['title'],
            'capability_type' => 'post',
        ];

        register_post_type('bpid_chart', $args);
    }

    /**
     * Return the supported chart types as key => display name.
     *
     * @return array<string, string>
     */
    public function get_chart_types(): array {
        return [
            'bar'         => __('Barras', 'bpid-suite'),
            'line'        => __('L&iacute;neas', 'bpid-suite'),
            'area'        => __('&Aacute;rea', 'bpid-suite'),
            'pie'         => __('Torta', 'bpid-suite'),
            'donut'       => __('Dona', 'bpid-suite'),
            'treemap'     => __('Treemap', 'bpid-suite'),
            'stacked_bar' => __('Barras apiladas', 'bpid-suite'),
            'grouped_bar' => __('Barras agrupadas', 'bpid-suite'),
            'tree'        => __('&Aacute;rbol', 'bpid-suite'),
            'pack'        => __('Pack', 'bpid-suite'),
            'network'     => __('Red', 'bpid-suite'),
            'scatter'     => __('Dispersi&oacute;n', 'bpid-suite'),
            'box_whisker' => __('Caja y bigotes', 'bpid-suite'),
            'matrix'      => __('Matriz', 'bpid-suite'),
            'bump'        => __('Bump', 'bpid-suite'),
        ];
    }

    /**
     * Register the chart configuration meta box.
     */
    public function add_meta_box(): void {
        add_meta_box(
            'bpid_chart_config',
            __('Configuraci&oacute;n del gr&aacute;fico', 'bpid-suite'),
            [$this, 'render_meta_box'],
            'bpid_chart',
            'normal',
            'high'
        );
    }

    /**
     * Render the chart configuration meta box.
     *
     * @param \WP_Post $post The current post object.
     */
    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('bpid_suite_chart_admin', 'bpid_suite_chart_nonce');

        $chart_type   = get_post_meta($post->ID, '_bpid_chart_type', true);
        $column_x     = get_post_meta($post->ID, '_bpid_chart_column_x', true);
        $column_y     = get_post_meta($post->ID, '_bpid_chart_column_y', true);
        $group        = get_post_meta($post->ID, '_bpid_chart_group', true);
        $color        = get_post_meta($post->ID, '_bpid_chart_color', true);
        $height       = get_post_meta($post->ID, '_bpid_chart_height', true);
        $aggregation  = get_post_meta($post->ID, '_bpid_chart_aggregation', true);
        $limit        = get_post_meta($post->ID, '_bpid_chart_limit', true);

        $chart_types = $this->get_chart_types();
        $columns     = self::ALLOWED_COLUMNS;
        ?>
        <table class="form-table">
            <tr>
                <th>
                    <label for="bpid_chart_type"><?php echo esc_html__('Tipo de gr&aacute;fico', 'bpid-suite'); ?></label>
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
                <th>
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
                <th>
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
                <th>
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
                <th>
                    <label for="bpid_chart_color"><?php echo esc_html__('Color', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="text"
                           name="bpid_chart_color"
                           id="bpid_chart_color"
                           value="<?php echo esc_attr($color); ?>"
                           class="regular-text"
                           placeholder="#3498db" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="bpid_chart_height"><?php echo esc_html__('Altura (px)', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="number"
                           name="bpid_chart_height"
                           id="bpid_chart_height"
                           value="<?php echo esc_attr($height); ?>"
                           class="small-text"
                           min="100"
                           max="2000"
                           placeholder="400" />
                </td>
            </tr>
            <tr>
                <th>
                    <label for="bpid_chart_aggregation"><?php echo esc_html__('Agregaci&oacute;n', 'bpid-suite'); ?></label>
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
                <th>
                    <label for="bpid_chart_limit"><?php echo esc_html__('L&iacute;mite de registros', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="number"
                           name="bpid_chart_limit"
                           id="bpid_chart_limit"
                           value="<?php echo esc_attr($limit); ?>"
                           class="small-text"
                           min="0"
                           placeholder="0" />
                    <p class="description">
                        <?php echo esc_html__('0 o vac&iacute;o para todos los registros.', 'bpid-suite'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save chart configuration meta box data.
     *
     * @param int      $post_id The post ID.
     * @param \WP_Post $post    The post object.
     */
    public function save_meta_box(int $post_id, \WP_Post $post): void {
        // Verify nonce.
        if (
            !isset($_POST['bpid_suite_chart_nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['bpid_suite_chart_nonce'])),
                'bpid_suite_chart_admin'
            )
        ) {
            return;
        }

        // Check for autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check capabilities.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type.
        if ('bpid_chart' !== $post->post_type) {
            return;
        }

        // Chart type.
        $chart_type = sanitize_text_field(wp_unslash($_POST['bpid_chart_type'] ?? ''));
        if (array_key_exists($chart_type, $this->get_chart_types())) {
            update_post_meta($post_id, '_bpid_chart_type', $chart_type);
        } else {
            delete_post_meta($post_id, '_bpid_chart_type');
        }

        // Column X.
        $column_x = sanitize_text_field(wp_unslash($_POST['bpid_chart_column_x'] ?? ''));
        if (in_array($column_x, self::ALLOWED_COLUMNS, true)) {
            update_post_meta($post_id, '_bpid_chart_column_x', $column_x);
        } else {
            delete_post_meta($post_id, '_bpid_chart_column_x');
        }

        // Column Y.
        $column_y = sanitize_text_field(wp_unslash($_POST['bpid_chart_column_y'] ?? ''));
        if (in_array($column_y, self::ALLOWED_COLUMNS, true)) {
            update_post_meta($post_id, '_bpid_chart_column_y', $column_y);
        } else {
            delete_post_meta($post_id, '_bpid_chart_column_y');
        }

        // Group.
        $group = sanitize_text_field(wp_unslash($_POST['bpid_chart_group'] ?? ''));
        if (in_array($group, self::ALLOWED_COLUMNS, true)) {
            update_post_meta($post_id, '_bpid_chart_group', $group);
        } else {
            delete_post_meta($post_id, '_bpid_chart_group');
        }

        // Color.
        $color = sanitize_hex_color(wp_unslash($_POST['bpid_chart_color'] ?? ''));
        if ($color) {
            update_post_meta($post_id, '_bpid_chart_color', $color);
        } else {
            delete_post_meta($post_id, '_bpid_chart_color');
        }

        // Height.
        $height = absint($_POST['bpid_chart_height'] ?? 0);
        if ($height >= 100 && $height <= 2000) {
            update_post_meta($post_id, '_bpid_chart_height', (string) $height);
        } else {
            delete_post_meta($post_id, '_bpid_chart_height');
        }

        // Aggregation.
        $aggregation = sanitize_text_field(wp_unslash($_POST['bpid_chart_aggregation'] ?? 'count'));
        if (in_array($aggregation, self::ALLOWED_AGGREGATIONS, true)) {
            update_post_meta($post_id, '_bpid_chart_aggregation', $aggregation);
        } else {
            update_post_meta($post_id, '_bpid_chart_aggregation', 'count');
        }

        // Limit.
        $limit = absint($_POST['bpid_chart_limit'] ?? 0);
        update_post_meta($post_id, '_bpid_chart_limit', (string) $limit);
    }

    /**
     * Render the [bpid_chart] shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function shortcode_render($atts): string {
        $atts = shortcode_atts(
            [
                'id'     => 0,
                'height' => '',
                'class'  => '',
            ],
            $atts,
            'bpid_chart'
        );

        $post_id = absint($atts['id']);

        if (0 === $post_id || 'bpid_chart' !== get_post_type($post_id)) {
            return '';
        }

        // Enqueue D3plus from CDN.
        wp_enqueue_script(
            'bpid-d3plus',
            'https://cdn.jsdelivr.net/npm/d3plus@2/build/d3plus.full.min.js',
            [],
            null,
            true
        );

        // Enqueue frontend chart script.
        wp_enqueue_script(
            'bpid-suite-frontend-charts',
            BPID_SUITE_URL . 'assets/js/frontend.js',
            ['bpid-d3plus'],
            BPID_SUITE_VERSION,
            true
        );

        // Retrieve chart configuration.
        $chart_type  = get_post_meta($post_id, '_bpid_chart_type', true);
        $column_x    = get_post_meta($post_id, '_bpid_chart_column_x', true);
        $column_y    = get_post_meta($post_id, '_bpid_chart_column_y', true);
        $group_col   = get_post_meta($post_id, '_bpid_chart_group', true);
        $color       = get_post_meta($post_id, '_bpid_chart_color', true);
        $height      = get_post_meta($post_id, '_bpid_chart_height', true);
        $aggregation = get_post_meta($post_id, '_bpid_chart_aggregation', true);

        // Allow shortcode attribute to override stored height.
        if (!empty($atts['height'])) {
            $height = $atts['height'];
        }

        $height = absint($height);
        if ($height < 100 || $height > 2000) {
            $height = 400;
        }

        if (empty($chart_type) || empty($column_x)) {
            return '';
        }

        // Fetch aggregated chart data.
        $data = $this->get_chart_data($post_id);

        if (empty($data)) {
            return '';
        }

        $css_class = 'bpid-chart-container';
        if (!empty($atts['class'])) {
            $css_class .= ' ' . sanitize_html_class($atts['class']);
        }

        $container_id = 'bpid-chart-' . $post_id;

        $json_flags = JSON_HEX_TAG | JSON_HEX_AMP;

        ob_start();
        ?>
        <div id="<?php echo esc_attr($container_id); ?>"
             class="<?php echo esc_attr($css_class); ?>"
             data-chart-type="<?php echo esc_attr($chart_type); ?>"
             data-column-x="<?php echo esc_attr($column_x); ?>"
             data-column-y="<?php echo esc_attr($column_y); ?>"
             data-group="<?php echo esc_attr($group_col); ?>"
             data-color="<?php echo esc_attr($color); ?>"
             data-height="<?php echo esc_attr((string) $height); ?>"
             data-aggregation="<?php echo esc_attr($aggregation); ?>"
             style="height:<?php echo esc_attr((string) $height); ?>px;">
        </div>
        <script type="application/json" id="<?php echo esc_attr($container_id . '-data'); ?>">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with safe flags
            echo wp_json_encode($data, $json_flags);
            ?>
        </script>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Fetch and aggregate data for a given chart.
     *
     * @param int $post_id The chart post ID.
     * @return array<int, array<string, mixed>> Aggregated chart data.
     */
    public function get_chart_data(int $post_id): array {
        $column_x    = get_post_meta($post_id, '_bpid_chart_column_x', true);
        $column_y    = get_post_meta($post_id, '_bpid_chart_column_y', true);
        $group_col   = get_post_meta($post_id, '_bpid_chart_group', true);
        $aggregation = get_post_meta($post_id, '_bpid_chart_aggregation', true);
        $limit       = absint(get_post_meta($post_id, '_bpid_chart_limit', true));

        if (empty($column_x) || !in_array($column_x, self::ALLOWED_COLUMNS, true)) {
            return [];
        }

        if (!in_array($aggregation, self::ALLOWED_AGGREGATIONS, true)) {
            $aggregation = 'count';
        }

        /** @var BPID_Suite_Database $database */
        $database = BPID_Suite_Database::get_instance();
        $records  = $database->get_all_records($limit > 0 ? $limit : 0);

        if (empty($records)) {
            return [];
        }

        $use_group = !empty($group_col) && in_array($group_col, self::ALLOWED_COLUMNS, true);
        $use_y     = !empty($column_y) && in_array($column_y, self::ALLOWED_COLUMNS, true);

        // Build aggregated data.
        $aggregated = [];

        foreach ($records as $record) {
            $x_value = $record[$column_x] ?? '';
            $key     = (string) $x_value;

            if ($use_group) {
                $group_value = $record[$group_col] ?? '';
                $key        .= '||' . (string) $group_value;
            }

            if (!isset($aggregated[$key])) {
                $entry = ['x' => $x_value];
                if ($use_group) {
                    $entry['group'] = $record[$group_col] ?? '';
                }
                $entry['_values'] = [];
                $entry['_count']  = 0;
                $aggregated[$key] = $entry;
            }

            $aggregated[$key]['_count']++;

            if ($use_y) {
                $aggregated[$key]['_values'][] = (float) ($record[$column_y] ?? 0);
            }
        }

        // Compute final values.
        $result = [];

        foreach ($aggregated as $entry) {
            $row = [
                'x' => $entry['x'],
            ];

            if (isset($entry['group'])) {
                $row['group'] = $entry['group'];
            }

            switch ($aggregation) {
                case 'sum':
                    $row['y'] = array_sum($entry['_values']);
                    break;

                case 'avg':
                    $count    = count($entry['_values']);
                    $row['y'] = $count > 0 ? array_sum($entry['_values']) / $count : 0;
                    break;

                case 'count':
                default:
                    $row['y'] = $entry['_count'];
                    break;
            }

            $result[] = $row;
        }

        return $result;
    }
}

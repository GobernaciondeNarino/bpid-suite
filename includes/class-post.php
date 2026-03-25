<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Module Post — Visualizador de Proyectos.
 *
 * Registers a custom post type for project visualizers, provides a shortcode
 * to render project grids on the frontend, and handles API data with caching.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Post {

    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

    /**
     * Database table name (set in constructor).
     */
    private string $table_name;

    /**
     * Transient key prefix for API data cache.
     */
    private string $transient_key = 'bpid_post_api_data_v1';

    /**
     * Default cache duration in seconds.
     */
    private int $cache_seconds = 3600;

    /**
     * Get the singleton instance.
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor — register hooks.
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bpid_suite_contratos';

        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta_box'], 10, 2);
        add_shortcode('bpid_grid_visualizador', [$this, 'shortcode_render']);
        add_action('wp_ajax_bpid_post_clear_cache', [$this, 'ajax_clear_cache']);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    // ------------------------------------------------------------------
    // Custom Post Type
    // ------------------------------------------------------------------

    /**
     * Register the bpid_post custom post type.
     */
    public function register_post_type(): void {
        $labels = [
            'name'               => __('BPID Visualizadores', 'bpid-suite'),
            'singular_name'      => __('Visualizador', 'bpid-suite'),
            'add_new'            => __('Agregar nuevo', 'bpid-suite'),
            'add_new_item'       => __('Agregar nuevo Visualizador', 'bpid-suite'),
            'edit_item'          => __('Editar Visualizador', 'bpid-suite'),
            'new_item'           => __('Nuevo Visualizador', 'bpid-suite'),
            'view_item'          => __('Ver Visualizador', 'bpid-suite'),
            'search_items'       => __('Buscar Visualizadores', 'bpid-suite'),
            'not_found'          => __('No se encontraron visualizadores.', 'bpid-suite'),
            'not_found_in_trash' => __('No se encontraron visualizadores en la papelera.', 'bpid-suite'),
            'all_items'          => __('Visualizadores', 'bpid-suite'),
            'menu_name'          => __('Visualizadores', 'bpid-suite'),
        ];

        $args = [
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bpid-suite',
            'supports'     => ['title'],
            'has_archive'  => false,
            'rewrite'      => false,
        ];

        register_post_type('bpid_post', $args);
    }

    // ------------------------------------------------------------------
    // Meta Box
    // ------------------------------------------------------------------

    /**
     * Register the configuration meta box.
     */
    public function add_meta_box(): void {
        add_meta_box(
            'bpid_post_config',
            __('Configuración del Visualizador', 'bpid-suite'),
            [$this, 'render_meta_box'],
            'bpid_post',
            'normal',
            'high'
        );
    }

    /**
     * Render the meta box fields.
     */
    public function render_meta_box(\WP_Post $post): void {
        wp_nonce_field('bpid_suite_post_admin', 'bpid_suite_post_nonce');

        $mostrar_stats    = (int) get_post_meta($post->ID, '_bpid_post_mostrar_stats', true) ?: 1;
        $mostrar_buscador = (int) get_post_meta($post->ID, '_bpid_post_mostrar_buscador', true) ?: 1;
        $mostrar_filtros  = (int) get_post_meta($post->ID, '_bpid_post_mostrar_filtros', true) ?: 1;
        $filtro_dep       = get_post_meta($post->ID, '_bpid_post_filtro_dependencia', true) ?: '';
        $color_primario   = get_post_meta($post->ID, '_bpid_post_color_primario', true) ?: '#348afb';
        $color_fondo      = get_post_meta($post->ID, '_bpid_post_color_fondo', true) ?: '#fffcf3';
        $ocultar_ops      = (int) get_post_meta($post->ID, '_bpid_post_ocultar_ops', true) ?: 1;
        $cols_grid        = (int) get_post_meta($post->ID, '_bpid_post_cols_grid', true) ?: 3;
        $texto_intro      = get_post_meta($post->ID, '_bpid_post_texto_intro', true) ?: '';
        $cache_horas      = (int) get_post_meta($post->ID, '_bpid_post_cache_horas', true) ?: 1;

        // Handle unchecked checkboxes on existing posts.
        if (get_post_meta($post->ID, '_bpid_post_mostrar_stats', true) === '0') {
            $mostrar_stats = 0;
        }
        if (get_post_meta($post->ID, '_bpid_post_mostrar_buscador', true) === '0') {
            $mostrar_buscador = 0;
        }
        if (get_post_meta($post->ID, '_bpid_post_mostrar_filtros', true) === '0') {
            $mostrar_filtros = 0;
        }
        if (get_post_meta($post->ID, '_bpid_post_ocultar_ops', true) === '0') {
            $ocultar_ops = 0;
        }

        ?>
        <table class="form-table bpid-post-metabox">
            <tr>
                <th scope="row">
                    <label for="bpid_post_mostrar_stats"><?php esc_html_e('Mostrar estadísticas', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="_bpid_post_mostrar_stats" value="0">
                    <input type="checkbox" id="bpid_post_mostrar_stats" name="_bpid_post_mostrar_stats" value="1" <?php checked($mostrar_stats, 1); ?>>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_mostrar_buscador"><?php esc_html_e('Mostrar buscador', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="_bpid_post_mostrar_buscador" value="0">
                    <input type="checkbox" id="bpid_post_mostrar_buscador" name="_bpid_post_mostrar_buscador" value="1" <?php checked($mostrar_buscador, 1); ?>>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_mostrar_filtros"><?php esc_html_e('Mostrar filtros', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="_bpid_post_mostrar_filtros" value="0">
                    <input type="checkbox" id="bpid_post_mostrar_filtros" name="_bpid_post_mostrar_filtros" value="1" <?php checked($mostrar_filtros, 1); ?>>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_filtro_dependencia"><?php esc_html_e('Filtro por dependencia', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <select id="bpid_post_filtro_dependencia" name="_bpid_post_filtro_dependencia">
                        <option value=""><?php esc_html_e('— Todas las dependencias —', 'bpid-suite'); ?></option>
                        <?php
                        /**
                         * Allow other modules to populate the dependencias dropdown.
                         *
                         * @param string $current Current selected value.
                         */
                        do_action('bpid_post_dependencia_options', $filtro_dep);
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_color_primario"><?php esc_html_e('Color primario', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="text" id="bpid_post_color_primario" name="_bpid_post_color_primario" value="<?php echo esc_attr($color_primario); ?>" class="bpid-color-picker" data-default-color="#348afb">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_color_fondo"><?php esc_html_e('Color de fondo', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="text" id="bpid_post_color_fondo" name="_bpid_post_color_fondo" value="<?php echo esc_attr($color_fondo); ?>" class="bpid-color-picker" data-default-color="#fffcf3">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_ocultar_ops"><?php esc_html_e('Ocultar OPS', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="_bpid_post_ocultar_ops" value="0">
                    <input type="checkbox" id="bpid_post_ocultar_ops" name="_bpid_post_ocultar_ops" value="1" <?php checked($ocultar_ops, 1); ?>>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_cols_grid"><?php esc_html_e('Columnas del grid', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="number" id="bpid_post_cols_grid" name="_bpid_post_cols_grid" value="<?php echo esc_attr((string) $cols_grid); ?>" min="1" max="4" step="1">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_texto_intro"><?php esc_html_e('Texto introductorio', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <textarea id="bpid_post_texto_intro" name="_bpid_post_texto_intro" rows="4" class="large-text"><?php echo esc_textarea($texto_intro); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="bpid_post_cache_horas"><?php esc_html_e('Horas de caché', 'bpid-suite'); ?></label>
                </th>
                <td>
                    <input type="number" id="bpid_post_cache_horas" name="_bpid_post_cache_horas" value="<?php echo esc_attr((string) $cache_horas); ?>" min="1" max="24" step="1">
                </td>
            </tr>
        </table>
        <?php
    }

    // ------------------------------------------------------------------
    // Save Meta Box
    // ------------------------------------------------------------------

    /**
     * Save meta box fields on post save.
     */
    public function save_meta_box(int $post_id, \WP_Post $post): void {
        // Verify nonce.
        if (
            !isset($_POST['bpid_suite_post_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['bpid_suite_post_nonce'])),
                'bpid_suite_post_admin'
            )
        ) {
            return;
        }

        // Check post type.
        if ('bpid_post' !== $post->post_type) {
            return;
        }

        // Check capability.
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Skip autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Checkbox fields (saved as 0 or 1).
        $checkboxes = [
            '_bpid_post_mostrar_stats',
            '_bpid_post_mostrar_buscador',
            '_bpid_post_mostrar_filtros',
            '_bpid_post_ocultar_ops',
        ];

        foreach ($checkboxes as $key) {
            $value = isset($_POST[$key]) ? absint($_POST[$key]) : 0;
            update_post_meta($post_id, $key, $value ? 1 : 0);
        }

        // Text / select fields.
        if (isset($_POST['_bpid_post_filtro_dependencia'])) {
            update_post_meta(
                $post_id,
                '_bpid_post_filtro_dependencia',
                sanitize_text_field(wp_unslash($_POST['_bpid_post_filtro_dependencia']))
            );
        }

        if (isset($_POST['_bpid_post_color_primario'])) {
            update_post_meta(
                $post_id,
                '_bpid_post_color_primario',
                sanitize_hex_color(wp_unslash($_POST['_bpid_post_color_primario'])) ?: '#348afb'
            );
        }

        if (isset($_POST['_bpid_post_color_fondo'])) {
            update_post_meta(
                $post_id,
                '_bpid_post_color_fondo',
                sanitize_hex_color(wp_unslash($_POST['_bpid_post_color_fondo'])) ?: '#fffcf3'
            );
        }

        // Numeric fields.
        if (isset($_POST['_bpid_post_cols_grid'])) {
            $cols = absint($_POST['_bpid_post_cols_grid']);
            $cols = max(1, min(4, $cols));
            update_post_meta($post_id, '_bpid_post_cols_grid', $cols);
        }

        if (isset($_POST['_bpid_post_cache_horas'])) {
            $horas = absint($_POST['_bpid_post_cache_horas']);
            $horas = max(1, min(24, $horas));
            update_post_meta($post_id, '_bpid_post_cache_horas', $horas);
        }

        // Textarea.
        if (isset($_POST['_bpid_post_texto_intro'])) {
            update_post_meta(
                $post_id,
                '_bpid_post_texto_intro',
                sanitize_textarea_field(wp_unslash($_POST['_bpid_post_texto_intro']))
            );
        }
    }

    // ------------------------------------------------------------------
    // Shortcode
    // ------------------------------------------------------------------

    /**
     * Render the [bpid_grid_visualizador] shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function shortcode_render(array $atts): string {
        $atts = shortcode_atts([
            'id'               => 0,
            'dependencia'      => '',
            'mostrar_stats'    => 1,
            'mostrar_filtros'  => 1,
            'mostrar_buscador' => 1,
            'ocultar_ops'      => 1,
            'cols'             => 3,
            'color_primario'   => '#348afb',
            'color_fondo'      => '#fffcf3',
            'texto_intro'      => '',
            'cache_horas'      => 1,
        ], $atts, 'bpid_grid_visualizador');

        // If a CPT post id is specified, load its meta and merge.
        $post_id = absint($atts['id']);
        if ($post_id > 0 && get_post_type($post_id) === 'bpid_post') {
            $meta_map = [
                '_bpid_post_mostrar_stats'    => 'mostrar_stats',
                '_bpid_post_mostrar_buscador' => 'mostrar_buscador',
                '_bpid_post_mostrar_filtros'  => 'mostrar_filtros',
                '_bpid_post_filtro_dependencia' => 'dependencia',
                '_bpid_post_color_primario'   => 'color_primario',
                '_bpid_post_color_fondo'      => 'color_fondo',
                '_bpid_post_ocultar_ops'      => 'ocultar_ops',
                '_bpid_post_cols_grid'        => 'cols',
                '_bpid_post_texto_intro'      => 'texto_intro',
                '_bpid_post_cache_horas'      => 'cache_horas',
            ];

            foreach ($meta_map as $meta_key => $att_key) {
                $meta_value = get_post_meta($post_id, $meta_key, true);
                if ($meta_value !== '' && $meta_value !== false) {
                    $atts[$att_key] = $meta_value;
                }
            }
        }

        // Sanitize attributes.
        $mostrar_stats    = (int) $atts['mostrar_stats'];
        $mostrar_buscador = (int) $atts['mostrar_buscador'];
        $mostrar_filtros  = (int) $atts['mostrar_filtros'];
        $ocultar_ops      = (int) $atts['ocultar_ops'];
        $cols             = max(1, min(4, (int) $atts['cols']));
        $color_primario   = sanitize_hex_color($atts['color_primario']) ?: '#348afb';
        $color_fondo      = sanitize_hex_color($atts['color_fondo']) ?: '#fffcf3';
        $texto_intro      = sanitize_textarea_field($atts['texto_intro']);
        $dependencia      = sanitize_text_field($atts['dependencia']);
        $cache_horas      = max(1, min(24, (int) $atts['cache_horas']));

        // Fetch API data.
        $api_result = $this->consultar_api($cache_horas);

        // If API returned contratos but not proyectos, group them.
        if (
            $api_result['success'] &&
            isset($api_result['data']['contratos']) &&
            !isset($api_result['data']['proyectos'])
        ) {
            $api_result['data']['proyectos'] = $this->agrupar_por_proyecto($api_result['data']['contratos']);
        }

        // Enqueue frontend assets.
        wp_enqueue_script(
            'bpid-suite-frontend-post',
            BPID_SUITE_URL . 'assets/js/frontend-post.js',
            [],
            BPID_SUITE_VERSION,
            true
        );

        wp_enqueue_style(
            'bpid-suite-frontend',
            BPID_SUITE_URL . 'assets/css/frontend.css',
            [],
            BPID_SUITE_VERSION
        );

        // Prepare template variables expected by templates/frontend/post.php.
        $resultado = $api_result;
        $proyectos = $api_result['data']['proyectos'] ?? [];

        // Filter by dependencia if specified.
        if ( $dependencia && is_array( $proyectos ) ) {
            $proyectos = array_values( array_filter( $proyectos, function ( array $p ) use ( $dependencia ): bool {
                return ( $p['dependenciaProyecto'] ?? '' ) === $dependencia;
            } ) );
        }

        // Re-assign atts with sanitized values for the template.
        $atts = [
            'mostrar_stats'    => $mostrar_stats,
            'mostrar_buscador' => $mostrar_buscador,
            'mostrar_filtros'  => $mostrar_filtros,
            'ocultar_ops'      => $ocultar_ops,
            'cols'             => $cols,
            'color_primario'   => $color_primario,
            'color_fondo'      => $color_fondo,
            'texto_intro'      => $texto_intro,
        ];

        ob_start();
        include BPID_SUITE_PATH . 'templates/frontend/post.php';
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // API Consultation
    // ------------------------------------------------------------------

    /**
     * Fetch data from the BPID API with transient caching.
     *
     * @param int $cache_horas Cache duration in hours (1-24).
     * @return array{success: bool, data?: array, error?: string}
     */
    public function consultar_api(int $cache_horas = 1): array {
        $api_key       = get_option('bpid_suite_api_key', '');
        $transient_key = 'bpid_post_api_' . md5($api_key);

        // Check transient cache first.
        $cached = get_transient($transient_key);
        if (false !== $cached && is_array($cached)) {
            return $cached;
        }

        if (empty($api_key)) {
            return [
                'success' => false,
                'error'   => __('La clave de API no está configurada.', 'bpid-suite'),
            ];
        }

        $response = wp_remote_get(BPID_SUITE_API_URL, [
            'timeout'   => 30,
            'sslverify' => true,
            'headers'   => [
                'apikey'       => $api_key,
                'Content-Type' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if (200 !== $status_code) {
            return [
                'success' => false,
                'error'   => sprintf(
                    /* translators: %d: HTTP status code */
                    __('La API respondió con el código HTTP %d.', 'bpid-suite'),
                    $status_code
                ),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return [
                'success' => false,
                'error'   => __('La respuesta de la API no es un JSON válido.', 'bpid-suite'),
            ];
        }

        // If contratos exist but not proyectos, group them.
        if (isset($data['contratos']) && !isset($data['proyectos'])) {
            $data['proyectos'] = $this->agrupar_por_proyecto($data['contratos']);
        }

        $result = [
            'success' => true,
            'data'    => $data,
        ];

        // Cache the result.
        $cache_horas = max(1, min(24, $cache_horas));
        set_transient($transient_key, $result, $cache_horas * HOUR_IN_SECONDS);

        return $result;
    }

    // ------------------------------------------------------------------
    // Data Grouping
    // ------------------------------------------------------------------

    /**
     * Group an array of contracts into projects by `numeroProyecto`.
     *
     * @param array $contratos Raw contracts from the API.
     * @return array Grouped project data.
     */
    public function agrupar_por_proyecto(array $contratos): array {
        $proyectos_map = [];

        foreach ($contratos as $contrato) {
            $numero_proyecto = $contrato['numeroProyecto'] ?? '';

            if (!isset($proyectos_map[$numero_proyecto])) {
                $proyectos_map[$numero_proyecto] = [
                    'numeroProyecto'      => $numero_proyecto,
                    'nombreProyecto'      => $contrato['nombreProyecto'] ?? '',
                    'dependenciaProyecto' => $contrato['dependenciaProyecto'] ?? '',
                    'valorProyecto'       => 0.0,
                    'odssProyecto'        => $contrato['odssProyecto'] ?? '',
                    'metasProyecto'       => $contrato['metasProyecto'] ?? '',
                    'contratosProyecto'   => [],
                ];
            }

            $valor_contrato = (float) ($contrato['valorContrato'] ?? 0);
            $proyectos_map[$numero_proyecto]['valorProyecto'] += $valor_contrato;

            // Decode municipios if provided as a JSON string.
            $municipios = $contrato['municipiosEjecContractual'] ?? '';
            if (is_string($municipios) && '' !== $municipios) {
                $decoded = json_decode($municipios, true);
                if (is_array($decoded)) {
                    $municipios = $decoded;
                }
            }

            // Decode imagenes if provided as a JSON string.
            $imagenes = $contrato['imagenesEjecContractual'] ?? '';
            if (is_string($imagenes) && '' !== $imagenes) {
                $decoded = json_decode($imagenes, true);
                if (is_array($decoded)) {
                    $imagenes = $decoded;
                }
            }

            $proyectos_map[$numero_proyecto]['contratosProyecto'][] = [
                'numeroContrato'             => $contrato['numeroContrato'] ?? '',
                'objetoContrato'             => $contrato['objetoContrato'] ?? '',
                'valorContrato'              => $valor_contrato,
                'procentajeAvanceFisico'     => $contrato['procentajeAvanceFisico'] ?? 0,
                'esOpsEjecContractual'       => $contrato['esOpsEjecContractual'] ?? '',
                'municipiosEjecContractual'  => $municipios,
                'imagenesEjecContractual'    => $imagenes,
            ];
        }

        return array_values($proyectos_map);
    }

    // ------------------------------------------------------------------
    // AJAX: Clear Cache
    // ------------------------------------------------------------------

    /**
     * AJAX handler to clear all post-related API transients.
     */
    public function ajax_clear_cache(): void {
        check_ajax_referer('bpid_post_clear_cache', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('No tienes permisos para realizar esta acción.', 'bpid-suite'),
            ], 403);
        }

        global $wpdb;

        // Delete all transients matching bpid_post_api_%.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_bpid_post_api_') . '%',
                $wpdb->esc_like('_transient_timeout_bpid_post_api_') . '%'
            )
        );

        wp_send_json_success([
            'message' => __('Caché limpiada correctamente.', 'bpid-suite'),
        ]);
    }
}

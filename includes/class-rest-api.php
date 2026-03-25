<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API controller for BPID Suite.
 *
 * Registers public and authenticated endpoints for contracts,
 * statistics, charts, projects, import management, and cache control.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Rest_API {

    private static ?self $instance = null;

    /**
     * REST namespace for all plugin endpoints.
     */
    private const NAMESPACE = 'bpid-suite/v1';

    /**
     * Maximum requests per minute for public endpoints.
     */
    private const RATE_LIMIT_MAX = 60;

    /**
     * Rate-limit window in seconds.
     */
    private const RATE_LIMIT_WINDOW = 60;

    /**
     * Columns allowed for ordering in the contracts endpoint.
     */
    private const ORDERABLE_COLUMNS = [
        'id',
        'dependencia',
        'numero_proyecto',
        'nombre_proyecto',
        'entidad_ejecutora',
        'numero',
        'valor',
        'avance_fisico',
        'es_ops',
        'fecha_importacion',
        'fecha_actualizacion',
    ];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    // ------------------------------------------------------------------
    // Route registration
    // ------------------------------------------------------------------

    /**
     * Register all REST routes for the plugin.
     */
    public function register_routes(): void {
        // --- Public endpoints (rate-limited) ---

        register_rest_route(self::NAMESPACE, '/contracts', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_contracts'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => $this->get_contracts_args(),
        ]);

        register_rest_route(self::NAMESPACE, '/contracts/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_contract'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => static function ($value): bool {
                        return is_numeric($value) && (int) $value > 0;
                    },
                    'sanitize_callback' => static function ($value): int {
                        return absint($value);
                    },
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/stats', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_stats'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/chart/(?P<id>\d+)/data', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_chart_data'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => static function ($value): bool {
                        return is_numeric($value) && (int) $value > 0;
                    },
                    'sanitize_callback' => static function ($value): int {
                        return absint($value);
                    },
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/chart/(?P<id>\d+)/csv', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_chart_csv'],
            'permission_callback' => [$this, 'public_permission_check'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => static function ($value): bool {
                        return is_numeric($value) && (int) $value > 0;
                    },
                    'sanitize_callback' => static function ($value): int {
                        return absint($value);
                    },
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/projects', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'get_projects'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        // --- Authenticated endpoints (manage_options) ---

        register_rest_route(self::NAMESPACE, '/import/start', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'import_start'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/import/status', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'import_status'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/import/cancel', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'import_cancel'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/cache/clear', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'cache_clear'],
            'permission_callback' => [$this, 'admin_permission_check'],
        ]);
    }

    // ------------------------------------------------------------------
    // Permission callbacks
    // ------------------------------------------------------------------

    /**
     * Permission callback for public endpoints.
     *
     * Enforces IP-based rate limiting before allowing access.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return true|\WP_Error True if allowed, WP_Error if rate-limited.
     */
    public function public_permission_check(\WP_REST_Request $request): true|\WP_Error {
        return $this->rate_limit_check($request);
    }

    /**
     * Permission callback for authenticated endpoints.
     *
     * @return true|\WP_Error True if the user has manage_options, WP_Error otherwise.
     */
    public function admin_permission_check(): true|\WP_Error {
        if (current_user_can('manage_options')) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __('No tienes permisos para realizar esta acción.', 'bpid-suite'),
            ['status' => 403]
        );
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    /**
     * IP-based rate limiter using transients.
     *
     * Allows a maximum of RATE_LIMIT_MAX requests per RATE_LIMIT_WINDOW
     * seconds per unique IP address (hashed with SHA-256).
     *
     * @param \WP_REST_Request $request Current request object.
     * @return true|\WP_Error True if within limit, WP_Error if exceeded.
     */
    private function rate_limit_check(\WP_REST_Request $request): true|\WP_Error {
        $ip   = $this->get_client_ip($request);
        $hash = hash('sha256', $ip);
        $key  = 'bpid_rl_' . substr($hash, 0, 32);

        $current = (int) get_transient($key);

        if ($current >= self::RATE_LIMIT_MAX) {
            return new \WP_Error(
                'rest_rate_limit',
                __('Has excedido el límite de solicitudes. Intenta de nuevo en un minuto.', 'bpid-suite'),
                ['status' => 429]
            );
        }

        if (0 === $current) {
            set_transient($key, 1, self::RATE_LIMIT_WINDOW);
        } else {
            set_transient($key, $current + 1, self::RATE_LIMIT_WINDOW);
        }

        return true;
    }

    /**
     * Retrieve the client IP address from the request.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return string Client IP address.
     */
    private function get_client_ip(\WP_REST_Request $request): string {
        $server_params = $request->get_header('X-Forwarded-For');

        if (!empty($server_params)) {
            $ips = array_map('trim', explode(',', $server_params));
            return sanitize_text_field($ips[0]);
        }

        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }

    // ------------------------------------------------------------------
    // Public endpoint callbacks
    // ------------------------------------------------------------------

    /**
     * GET /contracts — List contracts with pagination and filters.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return \WP_REST_Response
     */
    public function get_contracts(\WP_REST_Request $request): \WP_REST_Response {
        $database = BPID_Suite_Database::get_instance();

        $args = [
            'page'       => $request->get_param('page') ?? 1,
            'per_page'   => $request->get_param('per_page') ?? 20,
            'orderby'    => $request->get_param('orderby') ?? 'id',
            'order'      => $request->get_param('order') ?? 'DESC',
        ];

        // Optional filters.
        $optional = ['dependencia', 'search', 'valor_min', 'valor_max', 'avance_min', 'es_ops'];

        foreach ($optional as $param) {
            $value = $request->get_param($param);
            if (null !== $value && '' !== $value) {
                $args[$param] = $value;
            }
        }

        $result = $database->get_contratos($args);

        return new \WP_REST_Response($result, 200);
    }

    /**
     * GET /contracts/{id} — Retrieve a single contract by ID.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_contract(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $database = BPID_Suite_Database::get_instance();
        $id       = (int) $request->get_param('id');
        $contract = $database->get_contrato_by_id($id);

        if (null === $contract) {
            return new \WP_Error(
                'rest_not_found',
                __('Contrato no encontrado.', 'bpid-suite'),
                ['status' => 404]
            );
        }

        return new \WP_REST_Response($contract, 200);
    }

    /**
     * GET /stats — Return general statistics about stored contracts.
     *
     * @return \WP_REST_Response
     */
    public function get_stats(): \WP_REST_Response {
        $database = BPID_Suite_Database::get_instance();
        $stats    = $database->get_stats();

        return new \WP_REST_Response($stats, 200);
    }

    /**
     * GET /chart/{id}/data — Return chart data for a specific chart CPT.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_chart_data(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $id   = (int) $request->get_param('id');
        $post = get_post($id);

        if (!$post || 'bpid_chart' !== $post->post_type || 'publish' !== $post->post_status) {
            return new \WP_Error(
                'rest_not_found',
                __('Gráfica no encontrada.', 'bpid-suite'),
                ['status' => 404]
            );
        }

        $chart_type  = get_post_meta($id, '_bpid_chart_type', true);
        $chart_field = get_post_meta($id, '_bpid_chart_field', true);
        $chart_limit = (int) get_post_meta($id, '_bpid_chart_limit', true);

        if (empty($chart_type) || empty($chart_field)) {
            return new \WP_Error(
                'rest_invalid_chart',
                __('La gráfica no tiene configuración válida.', 'bpid-suite'),
                ['status' => 422]
            );
        }

        $database = BPID_Suite_Database::get_instance();
        $values   = $database->get_distinct_values(sanitize_key($chart_field));

        $data = [
            'id'         => $id,
            'title'      => $post->post_title,
            'chart_type' => $chart_type,
            'field'      => $chart_field,
            'limit'      => $chart_limit ?: 10,
            'labels'     => [],
            'values'     => [],
        ];

        // Build aggregated data based on the chart field.
        $contratos = $database->get_contratos(['per_page' => 100]);
        $counts    = [];

        foreach ($contratos['data'] as $row) {
            $label = $row[$chart_field] ?? __('Sin dato', 'bpid-suite');
            if (!isset($counts[$label])) {
                $counts[$label] = 0;
            }
            $counts[$label]++;
        }

        arsort($counts);

        $limit = $data['limit'];
        $i     = 0;
        foreach ($counts as $label => $count) {
            if ($i >= $limit) {
                break;
            }
            $data['labels'][] = (string) $label;
            $data['values'][] = $count;
            $i++;
        }

        return new \WP_REST_Response($data, 200);
    }

    /**
     * GET /chart/{id}/csv — Download chart data as CSV.
     *
     * @param \WP_REST_Request $request Current request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_chart_csv(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
        $chart_response = $this->get_chart_data($request);

        if (is_wp_error($chart_response)) {
            return $chart_response;
        }

        /** @var array $chart_data */
        $chart_data = $chart_response->get_data();

        $csv_lines   = [];
        $csv_lines[] = implode(',', [
            $this->csv_escape(__('Etiqueta', 'bpid-suite')),
            $this->csv_escape(__('Valor', 'bpid-suite')),
        ]);

        $labels = $chart_data['labels'] ?? [];
        $values = $chart_data['values'] ?? [];

        foreach ($labels as $index => $label) {
            $csv_lines[] = implode(',', [
                $this->csv_escape((string) $label),
                $this->csv_escape((string) ($values[$index] ?? 0)),
            ]);
        }

        $csv_content = implode("\n", $csv_lines);

        $response = new \WP_REST_Response($csv_content, 200);
        $response->header('Content-Type', 'text/csv; charset=utf-8');
        $response->header(
            'Content-Disposition',
            'attachment; filename="chart-' . (int) $request->get_param('id') . '.csv"'
        );

        return $response;
    }

    /**
     * GET /projects — Return grouped projects via BPID_Suite_Post.
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_projects(): \WP_REST_Response|\WP_Error {
        if (!class_exists('BPID_Suite_Post')) {
            return new \WP_Error(
                'rest_unavailable',
                __('El módulo de proyectos no está disponible.', 'bpid-suite'),
                ['status' => 503]
            );
        }

        $post_module = BPID_Suite_Post::get_instance();
        $result      = $post_module->consultar_api();

        if (empty($result['success'])) {
            return new \WP_Error(
                'rest_project_error',
                $result['error'] ?? __('Error al consultar la API.', 'bpid-suite'),
                ['status' => 500]
            );
        }

        return new \WP_REST_Response($result, 200);
    }

    // ------------------------------------------------------------------
    // Authenticated endpoint callbacks
    // ------------------------------------------------------------------

    /**
     * POST /import/start — Trigger a data import.
     *
     * @return \WP_REST_Response
     */
    public function import_start(): \WP_REST_Response {
        $importer = BPID_Suite_Importer::get_instance();
        $result   = $importer->run_import();

        if ($result['success']) {
            return new \WP_REST_Response([
                'message'  => __('Importación completada.', 'bpid-suite'),
                'inserted' => $result['inserted'],
                'updated'  => $result['updated'],
                'errors'   => $result['errors'],
            ], 200);
        }

        return new \WP_REST_Response([
            'message'  => $result['message'] ?? __('Error durante la importación.', 'bpid-suite'),
            'inserted' => $result['inserted'],
            'updated'  => $result['updated'],
            'errors'   => $result['errors'],
        ], 500);
    }

    /**
     * GET /import/status — Return current import progress.
     *
     * @return \WP_REST_Response
     */
    public function import_status(): \WP_REST_Response {
        $progress = get_transient('bpid_suite_import_running');

        if (false === $progress) {
            return new \WP_REST_Response([
                'status'    => 'idle',
                'total'     => 0,
                'processed' => 0,
                'inserted'  => 0,
                'updated'   => 0,
                'errors'    => 0,
            ], 200);
        }

        return new \WP_REST_Response($progress, 200);
    }

    /**
     * POST /import/cancel — Signal cancellation of a running import.
     *
     * @return \WP_REST_Response
     */
    public function import_cancel(): \WP_REST_Response {
        set_transient('bpid_suite_import_cancel', true, HOUR_IN_SECONDS);

        return new \WP_REST_Response([
            'message' => __('Cancelación solicitada.', 'bpid-suite'),
        ], 200);
    }

    /**
     * POST /cache/clear — Clear all plugin transients.
     *
     * @return \WP_REST_Response
     */
    public function cache_clear(): \WP_REST_Response {
        global $wpdb;

        // Delete all transients with the bpid_suite prefix.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $wpdb->esc_like('_transient_bpid_') . '%',
                $wpdb->esc_like('_transient_timeout_bpid_') . '%'
            )
        );

        return new \WP_REST_Response([
            'message' => sprintf(
                /* translators: %d: number of deleted transient rows */
                __('Caché limpiada. Se eliminaron %d registros.', 'bpid-suite'),
                (int) $deleted
            ),
            'deleted' => (int) $deleted,
        ], 200);
    }

    // ------------------------------------------------------------------
    // Argument definitions
    // ------------------------------------------------------------------

    /**
     * Return argument schema for the GET /contracts endpoint.
     *
     * @return array<string, array>
     */
    private function get_contracts_args(): array {
        return [
            'page'         => [
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => static function ($value): int {
                    return max(1, absint($value));
                },
                'validate_callback' => static function ($value): bool {
                    return is_numeric($value) && (int) $value >= 1;
                },
            ],
            'per_page'     => [
                'type'              => 'integer',
                'default'           => 20,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => static function ($value): int {
                    return min(100, max(1, absint($value)));
                },
                'validate_callback' => static function ($value): bool {
                    return is_numeric($value) && (int) $value >= 1 && (int) $value <= 100;
                },
            ],
            'dependencia'  => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
            ],
            'search'       => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
            ],
            'valor_min'    => [
                'type'              => 'number',
                'default'           => null,
                'sanitize_callback' => static function ($value): ?float {
                    return null !== $value && '' !== $value ? (float) $value : null;
                },
                'validate_callback' => static function ($value): bool {
                    return '' === $value || null === $value || is_numeric($value);
                },
            ],
            'valor_max'    => [
                'type'              => 'number',
                'default'           => null,
                'sanitize_callback' => static function ($value): ?float {
                    return null !== $value && '' !== $value ? (float) $value : null;
                },
                'validate_callback' => static function ($value): bool {
                    return '' === $value || null === $value || is_numeric($value);
                },
            ],
            'avance_min'   => [
                'type'              => 'integer',
                'default'           => null,
                'sanitize_callback' => static function ($value): ?int {
                    return null !== $value && '' !== $value ? absint($value) : null;
                },
                'validate_callback' => static function ($value): bool {
                    return '' === $value || null === $value || (is_numeric($value) && (int) $value >= 0);
                },
            ],
            'es_ops'       => [
                'type'              => 'integer',
                'default'           => null,
                'sanitize_callback' => static function ($value): ?int {
                    return null !== $value && '' !== $value ? absint($value) : null;
                },
                'validate_callback' => static function ($value): bool {
                    return '' === $value || null === $value || in_array((int) $value, [0, 1], true);
                },
            ],
            'orderby'      => [
                'type'              => 'string',
                'default'           => 'id',
                'sanitize_callback' => static function ($value): string {
                    $column = sanitize_key((string) $value);
                    return in_array($column, self::ORDERABLE_COLUMNS, true) ? $column : 'id';
                },
                'validate_callback' => static function ($value): bool {
                    return in_array(sanitize_key((string) $value), self::ORDERABLE_COLUMNS, true);
                },
            ],
            'order'        => [
                'type'              => 'string',
                'default'           => 'DESC',
                'enum'              => ['ASC', 'DESC'],
                'sanitize_callback' => static function ($value): string {
                    $upper = strtoupper(sanitize_text_field((string) $value));
                    return in_array($upper, ['ASC', 'DESC'], true) ? $upper : 'DESC';
                },
                'validate_callback' => static function ($value): bool {
                    return in_array(strtoupper((string) $value), ['ASC', 'DESC'], true);
                },
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Escape a value for safe inclusion in a CSV cell.
     *
     * @param string $value Raw cell value.
     * @return string Escaped and quoted value.
     */
    private function csv_escape(string $value): string {
        // Prevent CSV injection by stripping leading formula characters.
        $value = preg_replace('/^[=+\-@\t\r]/', '', $value) ?? $value;

        return '"' . str_replace('"', '""', $value) . '"';
    }
}

<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles data import from the BPID API.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Importer {

    /**
     * Number of contratos to process per batch.
     */
    private const BATCH_SIZE = 100;

    /**
     * Transient key for tracking import progress.
     */
    private const TRANSIENT_PROGRESS = 'bpid_suite_import_running';

    /**
     * Transient key for signalling cancellation.
     */
    private const TRANSIENT_CANCEL = 'bpid_suite_import_cancel';

    /**
     * Singleton instance.
     */
    private static ?self $instance = null;

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
        // AJAX actions (logged-in administrators only).
        add_action('wp_ajax_bpid_suite_start_import', [$this, 'ajax_start_import']);
        add_action('wp_ajax_bpid_suite_import_status', [$this, 'ajax_import_status']);
        add_action('wp_ajax_bpid_suite_cancel_import', [$this, 'ajax_cancel_import']);
        add_action('wp_ajax_bpid_suite_test_connection', [$this, 'ajax_test_connection']);

        // Cron hook for scheduled imports.
        add_action('bpid_suite_cron_import', [$this, 'cron_import']);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    // ------------------------------------------------------------------
    // AJAX handlers
    // ------------------------------------------------------------------

    /**
     * AJAX: Start a full import.
     */
    public function ajax_start_import(): void {
        check_ajax_referer('bpid_suite_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('No tienes permisos para realizar esta acción.', 'bpid-suite')],
                403
            );
        }

        $result = $this->run_import();

        if ($result['success']) {
            wp_send_json_success([
                'message'  => __('Importación completada.', 'bpid-suite'),
                'inserted' => $result['inserted'],
                'updated'  => $result['updated'],
                'errors'   => $result['errors'],
            ]);
        } else {
            wp_send_json_error([
                'message' => $result['message'] ?? __('Error durante la importación.', 'bpid-suite'),
            ]);
        }
    }

    /**
     * AJAX: Return current import progress.
     */
    public function ajax_import_status(): void {
        check_ajax_referer('bpid_suite_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('No tienes permisos para realizar esta acción.', 'bpid-suite')],
                403
            );
        }

        $progress = get_transient(self::TRANSIENT_PROGRESS);

        if (false === $progress) {
            wp_send_json_success([
                'status'    => 'idle',
                'total'     => 0,
                'processed' => 0,
                'inserted'  => 0,
                'updated'   => 0,
                'errors'    => 0,
            ]);
        } else {
            wp_send_json_success($progress);
        }
    }

    /**
     * AJAX: Signal cancellation of a running import.
     */
    public function ajax_cancel_import(): void {
        check_ajax_referer('bpid_suite_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('No tienes permisos para realizar esta acción.', 'bpid-suite')],
                403
            );
        }

        set_transient(self::TRANSIENT_CANCEL, true, HOUR_IN_SECONDS);

        wp_send_json_success([
            'message' => __('Cancelación solicitada.', 'bpid-suite'),
        ]);
    }

    /**
     * AJAX: Test the API connection and return the total contratos count.
     */
    public function ajax_test_connection(): void {
        check_ajax_referer('bpid_suite_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(
                ['message' => __('No tienes permisos para realizar esta acción.', 'bpid-suite')],
                403
            );
        }

        $data = $this->fetch_api_data();

        if (is_wp_error($data)) {
            wp_send_json_error([
                'message' => $data->get_error_message(),
            ]);
        }

        // Count total items — API may return 'contratos' or 'proyectos'.
        $total_contratos = 0;
        $total_proyectos = 0;

        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            $total_contratos = count($data['contratos']);
        }
        if (!empty($data['proyectos']) && is_array($data['proyectos'])) {
            $total_proyectos = count($data['proyectos']);
            if (0 === $total_contratos) {
                foreach ($data['proyectos'] as $p) {
                    $total_contratos += count($p['contratosProyecto'] ?? []);
                }
            }
        }

        $total_display = $data['total'] ?? $data['totalProyectos'] ?? $total_contratos;

        wp_send_json_success([
            'message' => sprintf(
                /* translators: 1: total items, 2: total projects */
                __('Conexión exitosa. Se encontraron %1$d contratos en %2$d proyectos.', 'bpid-suite'),
                $total_contratos,
                $total_proyectos
            ),
            'total'     => (int) $total_display,
            'contratos' => $total_contratos,
            'proyectos' => $total_proyectos,
        ]);
    }

    // ------------------------------------------------------------------
    // Import logic
    // ------------------------------------------------------------------

    /**
     * Run the full import process.
     *
     * @return array{success: bool, inserted: int, updated: int, errors: int, message?: string}
     */
    public function run_import(): array {
        $logger   = BPID_Suite_Logger::get_instance();
        $database = BPID_Suite_Database::get_instance();

        // Clean up any previous cancellation signal.
        delete_transient(self::TRANSIENT_CANCEL);

        $logger->info(__('Iniciando importación desde la API de BPID.', 'bpid-suite'));

        // Fetch data from API.
        $data = $this->fetch_api_data();

        if (is_wp_error($data)) {
            $error_message = $data->get_error_message();
            $logger->error(
                sprintf(
                    /* translators: %s: error message */
                    __('Error al conectar con la API: %s', 'bpid-suite'),
                    $error_message
                )
            );
            delete_transient(self::TRANSIENT_PROGRESS);

            return [
                'success'  => false,
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => 0,
                'message'  => $error_message,
            ];
        }

        // The API may return data under 'contratos' key (flat list)
        // or under 'proyectos' key (grouped by project). Handle both.
        $contratos = [];

        if (!empty($data['contratos']) && is_array($data['contratos'])) {
            $contratos = $data['contratos'];
        } elseif (!empty($data['proyectos']) && is_array($data['proyectos'])) {
            // Extract individual contracts from the grouped project structure.
            foreach ($data['proyectos'] as $proyecto) {
                $contratos_proyecto = $proyecto['contratosProyecto'] ?? [];
                if (!is_array($contratos_proyecto)) {
                    continue;
                }
                foreach ($contratos_proyecto as $contrato) {
                    // Map project-level fields into each contract for flat storage.
                    $contratos[] = [
                        'dependencia'    => $proyecto['dependenciaProyecto'] ?? '',
                        'numeroProyecto' => $proyecto['numeroProyecto'] ?? '',
                        'nombreProyecto' => $proyecto['nombreProyecto'] ?? '',
                        'entidadEjecutora' => $proyecto['entidadEjecutora'] ?? '',
                        'odss'           => $proyecto['odssProyecto'] ?? [],
                        'numero'         => $contrato['numeroContrato'] ?? '',
                        'objeto'         => $contrato['objetoContrato'] ?? '',
                        'descripcion'    => $contrato['descripcionContrato'] ?? ($contrato['objetoContrato'] ?? ''),
                        'valor'          => $contrato['valorContrato'] ?? 0,
                        'avanceFisico'   => $contrato['procentajeAvanceFisico'] ?? 0,
                        'esOps'          => $contrato['esOpsEjecContractual'] ?? 'No',
                        'municipios'     => $contrato['municipiosEjecContractual'] ?? [],
                        'imagenes'       => $contrato['imagenesEjecContractual'] ?? [],
                    ];
                }
            }
        }

        $total = count($contratos);

        if (0 === $total) {
            $logger->warning(__('La API no devolvió contratos.', 'bpid-suite'));
            delete_transient(self::TRANSIENT_PROGRESS);

            return [
                'success'  => true,
                'inserted' => 0,
                'updated'  => 0,
                'errors'   => 0,
                'message'  => __('No se encontraron contratos para importar.', 'bpid-suite'),
            ];
        }

        // Initialise progress tracking.
        $progress = [
            'total'     => $total,
            'processed' => 0,
            'inserted'  => 0,
            'updated'   => 0,
            'errors'    => 0,
            'status'    => 'running',
        ];
        set_transient(self::TRANSIENT_PROGRESS, $progress, HOUR_IN_SECONDS);

        // Process in batches.
        $batches = array_chunk($contratos, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            // Check for cancellation between batches.
            if (get_transient(self::TRANSIENT_CANCEL)) {
                $progress['status'] = 'cancelled';
                set_transient(self::TRANSIENT_PROGRESS, $progress, HOUR_IN_SECONDS);
                delete_transient(self::TRANSIENT_CANCEL);

                $logger->warning(
                    sprintf(
                        /* translators: %d: number of processed items */
                        __('Importación cancelada por el usuario tras procesar %d registros.', 'bpid-suite'),
                        $progress['processed']
                    )
                );

                return [
                    'success'  => false,
                    'inserted' => $progress['inserted'],
                    'updated'  => $progress['updated'],
                    'errors'   => $progress['errors'],
                    'message'  => __('Importación cancelada por el usuario.', 'bpid-suite'),
                ];
            }

            foreach ($batch as $contrato) {
                $result = $database->upsert_contrato($contrato);

                if ('inserted' === $result) {
                    $progress['inserted']++;
                } elseif ('updated' === $result) {
                    $progress['updated']++;
                } else {
                    $progress['errors']++;
                }

                $progress['processed']++;
            }

            // Persist progress after each batch.
            set_transient(self::TRANSIENT_PROGRESS, $progress, HOUR_IN_SECONDS);
        }

        // Mark as complete.
        $progress['status'] = 'complete';
        set_transient(self::TRANSIENT_PROGRESS, $progress, HOUR_IN_SECONDS);

        $logger->info(
            sprintf(
                /* translators: 1: inserted count, 2: updated count, 3: error count */
                __('Importación finalizada. Insertados: %1$d, Actualizados: %2$d, Errores: %3$d.', 'bpid-suite'),
                $progress['inserted'],
                $progress['updated'],
                $progress['errors']
            )
        );

        return [
            'success'  => true,
            'inserted' => $progress['inserted'],
            'updated'  => $progress['updated'],
            'errors'   => $progress['errors'],
        ];
    }

    // ------------------------------------------------------------------
    // API communication
    // ------------------------------------------------------------------

    /**
     * Fetch data from the BPID API.
     *
     * @return array|\WP_Error Decoded JSON array on success, WP_Error on failure.
     */
    public function fetch_api_data(): array|\WP_Error {
        $api_key = get_option('bpid_suite_api_key', '');

        if (empty($api_key)) {
            return new \WP_Error(
                'bpid_suite_no_api_key',
                __('La clave de API no está configurada.', 'bpid-suite')
            );
        }

        $response = wp_remote_get(BPID_SUITE_API_URL, [
            'timeout'   => 30,
            'sslverify' => false,
            'headers'   => [
                'apikey' => $api_key,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if (200 !== $status_code) {
            return new \WP_Error(
                'bpid_suite_api_error',
                sprintf(
                    /* translators: %d: HTTP status code */
                    __('La API respondió con el código HTTP %d.', 'bpid-suite'),
                    $status_code
                )
            );
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            return new \WP_Error(
                'bpid_suite_invalid_json',
                __('La respuesta de la API no es un JSON válido.', 'bpid-suite')
            );
        }

        return $data;
    }

    // ------------------------------------------------------------------
    // Cron
    // ------------------------------------------------------------------

    /**
     * Run import via WP-Cron (no AJAX context).
     */
    public function cron_import(): void {
        $this->run_import();
    }
}

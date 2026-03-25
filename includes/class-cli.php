<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP-CLI commands for BPID Suite.
 *
 * Provides CLI access to import, stats, truncate, logs, test-connection,
 * and clear-cache operations.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
class BPID_Suite_CLI {

    /**
     * Register the `wp bpid` command group.
     */
    public static function register(): void {
        WP_CLI::add_command('bpid', self::class);
    }

    /**
     * Run a full import from the BPID API.
     *
     * ## EXAMPLES
     *
     *     wp bpid import
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function import(array $args, array $assoc_args): void {
        $importer = BPID_Suite_Importer::get_instance();

        WP_CLI::log(__('Conectando con la API de BPID...', 'bpid-suite'));

        $data = $importer->fetch_api_data();

        if (is_wp_error($data)) {
            WP_CLI::error($data->get_error_message());
        }

        $contratos = $data['contratos'] ?? [];
        $total     = count($contratos);

        if (0 === $total) {
            WP_CLI::warning(__('La API no devolvio contratos.', 'bpid-suite'));
            return;
        }

        WP_CLI::log(
            sprintf(
                /* translators: %d: number of contracts found */
                __('Se encontraron %d contratos. Iniciando importacion...', 'bpid-suite'),
                $total
            )
        );

        $progress = \WP_CLI\Utils\make_progress_bar(
            __('Importando contratos', 'bpid-suite'),
            $total
        );

        $database = BPID_Suite_Database::get_instance();
        $logger   = BPID_Suite_Logger::get_instance();
        $inserted = 0;
        $updated  = 0;
        $errors   = 0;

        foreach ($contratos as $contrato) {
            $result = $database->upsert_contrato($contrato);

            if ('inserted' === $result) {
                $inserted++;
            } elseif ('updated' === $result) {
                $updated++;
            } else {
                $errors++;
            }

            $progress->tick();
        }

        $progress->finish();

        $logger->log(
            sprintf(
                /* translators: 1: inserted count, 2: updated count, 3: error count */
                __('Importacion CLI finalizada. Insertados: %1$d, Actualizados: %2$d, Errores: %3$d.', 'bpid-suite'),
                $inserted,
                $updated,
                $errors
            )
        );

        WP_CLI::success(
            sprintf(
                /* translators: 1: inserted count, 2: updated count, 3: error count */
                __('Importacion completada. Insertados: %1$d | Actualizados: %2$d | Errores: %3$d', 'bpid-suite'),
                $inserted,
                $updated,
                $errors
            )
        );
    }

    /**
     * Show database statistics for BPID contracts.
     *
     * ## EXAMPLES
     *
     *     wp bpid stats
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function stats(array $args, array $assoc_args): void {
        $database = BPID_Suite_Database::get_instance();
        $stats    = $database->get_stats();

        WP_CLI::log(__('=== Estadisticas generales ===', 'bpid-suite'));
        WP_CLI::log(
            sprintf(
                /* translators: %d: total records */
                __('Total registros: %d', 'bpid-suite'),
                $stats['total']
            )
        );
        WP_CLI::log(
            sprintf(
                /* translators: %s: total valor formatted */
                __('Valor total: $%s', 'bpid-suite'),
                number_format($stats['total_valor'], 2)
            )
        );
        WP_CLI::log(
            sprintf(
                /* translators: %s: average avance */
                __('Avance promedio: %s%%', 'bpid-suite'),
                number_format($stats['avg_avance'], 2)
            )
        );

        if (!empty($stats['by_dependencia'])) {
            WP_CLI::log('');
            WP_CLI::log(__('=== Registros por dependencia ===', 'bpid-suite'));

            \WP_CLI\Utils\format_items(
                'table',
                $stats['by_dependencia'],
                ['dependencia', 'total']
            );
        }
    }

    /**
     * Delete all records from the BPID contracts table.
     *
     * ## OPTIONS
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * ## EXAMPLES
     *
     *     wp bpid truncate --yes
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function truncate(array $args, array $assoc_args): void {
        $database    = BPID_Suite_Database::get_instance();
        $record_count = $database->get_record_count();

        if (0 === $record_count) {
            WP_CLI::warning(__('La tabla ya esta vacia.', 'bpid-suite'));
            return;
        }

        if (empty($assoc_args['yes'])) {
            WP_CLI::confirm(
                sprintf(
                    /* translators: %d: number of records to delete */
                    __('Se eliminaran %d registros. ¿Continuar?', 'bpid-suite'),
                    $record_count
                )
            );
        }

        $result = $database->truncate_table();

        if ($result) {
            $logger = BPID_Suite_Logger::get_instance();
            $logger->log(
                sprintf(
                    /* translators: %d: number of records deleted */
                    __('Tabla truncada via CLI. %d registros eliminados.', 'bpid-suite'),
                    $record_count
                ),
                'warning'
            );

            WP_CLI::success(
                sprintf(
                    /* translators: %d: number of records deleted */
                    __('Se eliminaron %d registros.', 'bpid-suite'),
                    $record_count
                )
            );
        } else {
            WP_CLI::error(__('Error al truncar la tabla.', 'bpid-suite'));
        }
    }

    /**
     * Show the last lines from the import log.
     *
     * ## OPTIONS
     *
     * [--lines=<number>]
     * : Number of lines to display.
     * ---
     * default: 50
     * ---
     *
     * ## EXAMPLES
     *
     *     wp bpid logs
     *     wp bpid logs --lines=100
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function logs(array $args, array $assoc_args): void {
        $lines  = (int) ($assoc_args['lines'] ?? 50);
        $logger = BPID_Suite_Logger::get_instance();
        $entries = $logger->get_logs($lines);

        if (empty($entries)) {
            WP_CLI::log(__('No hay entradas en el log.', 'bpid-suite'));
            return;
        }

        WP_CLI::log(
            sprintf(
                /* translators: %d: number of lines shown */
                __('Mostrando las ultimas %d lineas del log:', 'bpid-suite'),
                count($entries)
            )
        );
        WP_CLI::log('');

        foreach ($entries as $entry) {
            WP_CLI::log($entry);
        }
    }

    /**
     * Test API connection with the stored key.
     *
     * ## EXAMPLES
     *
     *     wp bpid test-connection
     *
     * @subcommand test-connection
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function test_connection(array $args, array $assoc_args): void {
        WP_CLI::log(__('Probando conexion con la API de BPID...', 'bpid-suite'));

        $importer = BPID_Suite_Importer::get_instance();
        $data     = $importer->fetch_api_data();

        if (is_wp_error($data)) {
            WP_CLI::error($data->get_error_message());
        }

        $contratos = $data['contratos'] ?? [];

        WP_CLI::success(
            sprintf(
                /* translators: %d: number of contracts available */
                __('Conexion exitosa. Contratos disponibles: %d', 'bpid-suite'),
                count($contratos)
            )
        );
    }

    /**
     * Clear all BPID Suite transients (import and post module).
     *
     * ## EXAMPLES
     *
     *     wp bpid clear-cache
     *
     * @subcommand clear-cache
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function clear_cache(array $args, array $assoc_args): void {
        global $wpdb;

        $cleared = 0;

        // Import-related transients.
        $import_transients = [
            'bpid_suite_import_running',
            'bpid_suite_import_cancel',
            'bpid_suite_api_data_v1',
        ];

        foreach ($import_transients as $transient) {
            if (false !== get_transient($transient)) {
                delete_transient($transient);
                $cleared++;
            }
        }

        // Post module transients (dynamic keys based on API key hash).
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $post_transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_bpid_post_api_%'
                OR option_name LIKE '_transient_timeout_bpid_post_api_%'"
        );

        foreach ($post_transients as $option_name) {
            // Strip the `_transient_` or `_transient_timeout_` prefix.
            if (str_starts_with($option_name, '_transient_timeout_')) {
                $key = substr($option_name, strlen('_transient_timeout_'));
            } else {
                $key = substr($option_name, strlen('_transient_'));
            }
            delete_transient($key);
            $cleared++;
        }

        WP_CLI::success(
            sprintf(
                /* translators: %d: number of transients cleared */
                __('Cache limpiado. Transients eliminados: %d', 'bpid-suite'),
                $cleared
            )
        );
    }
}

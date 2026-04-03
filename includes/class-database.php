<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database manager for BPID Suite project/contract data.
 *
 * Unified table with project-first layout, flattened arrays,
 * and proper categorical/quantitative typing.
 *
 * @package BPID_Suite
 * @since   1.5.0
 */
final class BPID_Suite_Database {

    private static ?self $instance = null;

    private string $table_name;

    /** @var string[] Columns allowed in queries for filtering/ordering/distinct. */
    private const ALLOWED_COLUMNS = [
        'id',
        'numero_proyecto',
        'nombre_proyecto',
        'dependencia',
        'entidad_ejecutora',
        'valor_proyecto',
        'metas',
        'odss',
        'numero_contrato',
        'objeto_contrato',
        'descripcion_contrato',
        'valor_contrato',
        'avance_fisico',
        'es_ops',
        'municipios',
        'beneficiarios',
        'imagenes',
        'fecha_importacion',
        'fecha_actualizacion',
    ];

    /** @var string[] Columns valid for ordering results. */
    private const ORDERABLE_COLUMNS = [
        'id',
        'numero_proyecto',
        'nombre_proyecto',
        'dependencia',
        'entidad_ejecutora',
        'valor_proyecto',
        'numero_contrato',
        'valor_contrato',
        'avance_fisico',
        'es_ops',
        'beneficiarios',
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bpid_suite_contratos';
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Return the full table name including prefix.
     */
    public function get_table_name(): string {
        return $this->table_name;
    }

    /**
     * Create or update the database table using dbDelta.
     *
     * Schema v2.0: project-first layout with flattened arrays.
     */
    public function create_table(): void {
        global $wpdb;

        $table   = $this->table_name;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            numero_proyecto VARCHAR(100) NOT NULL DEFAULT '',
            nombre_proyecto TEXT,
            dependencia VARCHAR(500) DEFAULT '',
            entidad_ejecutora VARCHAR(500) DEFAULT '',
            valor_proyecto DECIMAL(20,2) DEFAULT 0.00,
            metas TEXT,
            odss TEXT,
            numero_contrato VARCHAR(200) NOT NULL DEFAULT '',
            objeto_contrato LONGTEXT,
            descripcion_contrato LONGTEXT,
            valor_contrato DECIMAL(20,2) DEFAULT 0.00,
            avance_fisico DECIMAL(5,2) DEFAULT 0.00,
            es_ops TINYINT(1) DEFAULT 0,
            municipios TEXT,
            beneficiarios INT UNSIGNED DEFAULT 0,
            imagenes LONGTEXT,
            fecha_importacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_contrato_proyecto (numero_contrato(100), numero_proyecto(100)),
            KEY idx_numero_proyecto (numero_proyecto),
            KEY idx_dependencia (dependencia(100)),
            KEY idx_avance (avance_fisico),
            KEY idx_valor_contrato (valor_contrato),
            KEY idx_valor_proyecto (valor_proyecto),
            KEY idx_es_ops (es_ops)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('BPID_SUITE_DB_VERSION', BPID_SUITE_DB_VERSION);
    }

    /**
     * Migrate from old schema (v1) to new schema (v2).
     *
     * Drops the old table and creates the new one since data
     * can be re-imported from the API.
     */
    public function maybe_migrate(): void {
        $current_version = get_option('BPID_SUITE_DB_VERSION', '0');

        if (version_compare($current_version, BPID_SUITE_DB_VERSION, '<')) {
            // Drop old table and recreate with new schema.
            $this->drop_table();
            $this->create_table();
        }
    }

    /**
     * Insert or update a contract record.
     *
     * Uses the composite unique key (numero_contrato, numero_proyecto) for upsert logic.
     * Data is cleaned and normalized before storage.
     *
     * @param array<string, mixed> $contrato Normalized contract data.
     * @return string 'inserted', 'updated', or 'error'.
     */
    public function upsert_contrato(array $contrato): string {
        global $wpdb;

        // ── Project-level fields ──
        $numero_proyecto   = $this->clean_text((string) ($contrato['numeroProyecto'] ?? ''));
        $nombre_proyecto   = $this->clean_text((string) ($contrato['nombreProyecto'] ?? ''));
        $dependencia       = $this->clean_categorical((string) ($contrato['dependencia'] ?? ''));
        $entidad_ejecutora = $this->clean_categorical((string) ($contrato['entidadEjecutora'] ?? ''));
        $valor_proyecto    = $this->clean_decimal($contrato['valorProyecto'] ?? 0);
        $metas             = $this->flatten_array($contrato['metas'] ?? []);
        $odss              = $this->flatten_array($contrato['odss'] ?? []);

        // ── Contract-level fields ──
        $numero_contrato     = $this->clean_text((string) ($contrato['numeroContrato'] ?? ''));
        $objeto_contrato     = sanitize_textarea_field((string) ($contrato['objetoContrato'] ?? ''));
        $descripcion_contrato = sanitize_textarea_field((string) ($contrato['descripcionContrato'] ?? ''));
        $valor_contrato      = $this->clean_decimal($contrato['valorContrato'] ?? 0);
        $avance_fisico       = $this->clean_percentage($contrato['avanceFisico'] ?? 0);
        $es_ops_raw          = (string) ($contrato['esOps'] ?? 'No');
        $es_ops              = $this->clean_boolean_ops($es_ops_raw);

        // ── Flattened array fields ──
        $municipios_data   = $contrato['municipios'] ?? [];
        $municipios_result = $this->flatten_municipios($municipios_data);
        $municipios        = $municipios_result['nombres'];
        $beneficiarios     = $municipios_result['beneficiarios'];
        $imagenes          = $this->flatten_urls($contrato['imagenes'] ?? []);

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe (prefix-based).
        $sql = $wpdb->prepare(
            "INSERT INTO `{$table}` (
                numero_proyecto,
                nombre_proyecto,
                dependencia,
                entidad_ejecutora,
                valor_proyecto,
                metas,
                odss,
                numero_contrato,
                objeto_contrato,
                descripcion_contrato,
                valor_contrato,
                avance_fisico,
                es_ops,
                municipios,
                beneficiarios,
                imagenes,
                fecha_importacion
            ) VALUES (
                %s, %s, %s, %s, %f, %s, %s, %s, %s, %s, %f, %f, %d, %s, %d, %s, NOW()
            ) ON DUPLICATE KEY UPDATE
                nombre_proyecto       = VALUES(nombre_proyecto),
                dependencia           = VALUES(dependencia),
                entidad_ejecutora     = VALUES(entidad_ejecutora),
                valor_proyecto        = VALUES(valor_proyecto),
                metas                 = VALUES(metas),
                odss                  = VALUES(odss),
                objeto_contrato       = VALUES(objeto_contrato),
                descripcion_contrato  = VALUES(descripcion_contrato),
                valor_contrato        = VALUES(valor_contrato),
                avance_fisico         = VALUES(avance_fisico),
                es_ops                = VALUES(es_ops),
                municipios            = VALUES(municipios),
                beneficiarios         = VALUES(beneficiarios),
                imagenes              = VALUES(imagenes),
                fecha_actualizacion   = NOW()",
            $numero_proyecto,
            $nombre_proyecto,
            $dependencia,
            $entidad_ejecutora,
            $valor_proyecto,
            $metas,
            $odss,
            $numero_contrato,
            $objeto_contrato,
            $descripcion_contrato,
            $valor_contrato,
            $avance_fisico,
            $es_ops,
            $municipios,
            $beneficiarios,
            $imagenes
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- already prepared above.
        $result = $wpdb->query($sql);

        if (false === $result) {
            return 'error';
        }

        // $wpdb->query returns number of affected rows.
        // INSERT = 1 row affected. ON DUPLICATE KEY UPDATE = 2 rows affected.
        if ($result >= 2) {
            return 'updated';
        }

        return 'inserted';
    }

    // ------------------------------------------------------------------
    // Data Cleaning & Normalization Helpers
    // ------------------------------------------------------------------

    /**
     * Clean and trim a text field.
     */
    private function clean_text(string $value): string {
        $value = sanitize_text_field($value);
        $value = trim($value);
        // Remove excessive whitespace.
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return $value;
    }

    /**
     * Clean a categorical variable: trim, normalize case for consistency.
     */
    private function clean_categorical(string $value): string {
        $value = $this->clean_text($value);
        // Normalize common variations.
        $value = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        return $value;
    }

    /**
     * Clean a decimal/monetary value.
     */
    private function clean_decimal(mixed $value): float {
        if (is_string($value)) {
            // Remove currency symbols, commas, spaces.
            $value = preg_replace('/[^0-9.\-]/', '', $value);
        }
        $result = (float) $value;
        return max(0, $result);
    }

    /**
     * Clean a percentage value (0-100).
     */
    private function clean_percentage(mixed $value): float {
        $result = (float) $value;
        return max(0, min(100, round($result, 2)));
    }

    /**
     * Clean boolean OPS field: 'Si'/'Sí' → 1, else → 0.
     */
    private function clean_boolean_ops(string $value): int {
        $normalized = mb_strtolower(trim($value), 'UTF-8');
        // Remove accents for comparison.
        $normalized = remove_accents($normalized);
        return ('si' === $normalized) ? 1 : 0;
    }

    /**
     * Flatten an array to comma-separated text.
     * Handles strings, arrays of strings, and nested structures.
     */
    private function flatten_array(mixed $data): string {
        if (empty($data)) {
            return '';
        }

        // If it's a JSON string, decode it.
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (is_array($decoded)) {
                $data = $decoded;
            } else {
                return $this->clean_text($data);
            }
        }

        if (!is_array($data)) {
            return $this->clean_text((string) $data);
        }

        $items = [];
        foreach ($data as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $items[] = trim($item);
            } elseif (is_array($item)) {
                // Extract first meaningful string value from object.
                $name = $item['nombre'] ?? $item['name'] ?? $item['descripcion'] ?? '';
                if (is_string($name) && '' !== trim($name)) {
                    $items[] = trim($name);
                }
            }
        }

        // Remove duplicates and empty.
        $items = array_unique(array_filter($items));
        return implode(', ', $items);
    }

    /**
     * Flatten municipios array: extract names and sum beneficiaries.
     *
     * @return array{nombres: string, beneficiarios: int}
     */
    private function flatten_municipios(mixed $data): array {
        $nombres = [];
        $beneficiarios = 0;

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($data)) {
            return ['nombres' => '', 'beneficiarios' => 0];
        }

        foreach ($data as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $nombres[] = trim($item);
            } elseif (is_array($item)) {
                $nombre = $item['nombre'] ?? $item['name'] ?? '';
                if (is_string($nombre) && '' !== trim($nombre)) {
                    $nombres[] = trim($nombre);
                }
                $beneficiarios += absint($item['poblacion_beneficiada'] ?? $item['beneficiarios'] ?? 0);
            }
        }

        $nombres = array_unique(array_filter($nombres));
        sort($nombres);

        return [
            'nombres'       => implode(', ', $nombres),
            'beneficiarios' => $beneficiarios,
        ];
    }

    /**
     * Flatten URLs array to comma-separated string.
     */
    private function flatten_urls(mixed $data): string {
        if (empty($data)) {
            return '';
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : [$data];
        }

        if (!is_array($data)) {
            return '';
        }

        $urls = [];
        foreach ($data as $item) {
            $url = is_string($item) ? $item : ($item['url'] ?? '');
            if (is_string($url) && str_starts_with($url, 'http')) {
                $urls[] = esc_url_raw($url);
            }
        }

        return implode(', ', array_unique($urls));
    }

    // ------------------------------------------------------------------
    // Query Methods
    // ------------------------------------------------------------------

    /**
     * Retrieve contracts with pagination, filtering, and ordering.
     *
     * @param array<string, mixed> $args Query arguments.
     * @return array{ data: array, total: int, pages: int, page: int, per_page: int }
     */
    public function get_contratos(array $args = []): array {
        global $wpdb;

        $per_page = min(max((int) ($args['per_page'] ?? 20), 1), 100);
        $page     = max((int) ($args['page'] ?? 1), 1);
        $orderby  = (string) ($args['orderby'] ?? 'id');
        $order    = strtoupper((string) ($args['order'] ?? 'DESC'));

        if (!in_array($orderby, self::ORDERABLE_COLUMNS, true)) {
            $orderby = 'id';
        }

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $where   = [];
        $values  = [];
        $table   = $this->table_name;

        // Free-text search.
        if (!empty($args['search'])) {
            $like     = '%' . $wpdb->esc_like(sanitize_text_field((string) $args['search'])) . '%';
            $where[]  = '(dependencia LIKE %s OR numero_proyecto LIKE %s OR nombre_proyecto LIKE %s OR numero_contrato LIKE %s OR objeto_contrato LIKE %s OR entidad_ejecutora LIKE %s OR municipios LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        // Exact dependencia filter.
        if (!empty($args['dependencia'])) {
            $where[]  = 'dependencia = %s';
            $values[] = sanitize_text_field((string) $args['dependencia']);
        }

        // es_ops filter.
        if (isset($args['es_ops']) && '' !== $args['es_ops']) {
            $where[]  = 'es_ops = %d';
            $values[] = (int) $args['es_ops'];
        }

        // Avance range.
        if (isset($args['avance_min']) && '' !== $args['avance_min']) {
            $where[]  = 'avance_fisico >= %f';
            $values[] = (float) $args['avance_min'];
        }
        if (isset($args['avance_max']) && '' !== $args['avance_max']) {
            $where[]  = 'avance_fisico <= %f';
            $values[] = (float) $args['avance_max'];
        }

        // Valor contrato range.
        if (isset($args['valor_min']) && '' !== $args['valor_min']) {
            $where[]  = 'valor_contrato >= %f';
            $values[] = (float) $args['valor_min'];
        }
        if (isset($args['valor_max']) && '' !== $args['valor_max']) {
            $where[]  = 'valor_contrato <= %f';
            $values[] = (float) $args['valor_max'];
        }

        // Exact numero_proyecto filter.
        if (!empty($args['numero_proyecto'])) {
            $where[]  = 'numero_proyecto = %s';
            $values[] = sanitize_text_field((string) $args['numero_proyecto']);
        }

        $where_sql = '';
        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        $offset = ($page - 1) * $per_page;

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

        $data_sql_template = "SELECT * FROM `{$table}` {$where_sql} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";
        $data_values       = array_merge($values, [$per_page, $offset]);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $data_sql = $wpdb->prepare($data_sql_template, ...$data_values);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $data = $wpdb->get_results($data_sql, ARRAY_A);

        return [
            'data'     => $data ?: [],
            'total'    => $total,
            'pages'    => (int) ceil($total / $per_page),
            'page'     => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Get a single contract by its primary key.
     */
    public function get_contrato_by_id(int $id): ?array {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE id = %d", $id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    /**
     * Return aggregate statistics.
     */
    public function get_stats(): array {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $totals = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total, COALESCE(SUM(valor_contrato), 0) AS total_valor, COALESCE(AVG(avance_fisico), 0) AS avg_avance FROM `{$table}` WHERE %d = %d",
                1,
                1
            ),
            ARRAY_A
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $by_dependencia = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT dependencia, COUNT(*) AS total FROM `{$table}` WHERE %d = %d GROUP BY dependencia ORDER BY total DESC",
                1,
                1
            ),
            ARRAY_A
        );

        return [
            'total'           => (int) ($totals['total'] ?? 0),
            'total_valor'     => (float) ($totals['total_valor'] ?? 0),
            'avg_avance'      => round((float) ($totals['avg_avance'] ?? 0), 2),
            'by_dependencia'  => $by_dependencia ?: [],
        ];
    }

    /**
     * Get distinct non-null values for a given column.
     */
    public function get_distinct_values(string $column): array {
        global $wpdb;

        if (!in_array($column, self::ALLOWED_COLUMNS, true)) {
            return [];
        }

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT `{$column}` FROM `{$table}` WHERE `{$column}` IS NOT NULL AND `{$column}` != %s ORDER BY `{$column}` ASC",
                ''
            )
        );

        return $results ?: [];
    }

    /**
     * Retrieve all records for chart aggregation.
     */
    public function get_all_records(int $limit = 0): array {
        global $wpdb;

        $table = $this->table_name;

        if ($limit > 0) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = $wpdb->prepare("SELECT * FROM `{$table}` ORDER BY id ASC LIMIT %d", $limit);
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $sql = $wpdb->prepare("SELECT * FROM `{$table}` WHERE %d = %d ORDER BY id ASC", 1, 1);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $results = $wpdb->get_results($sql, ARRAY_A);

        return $results ?: [];
    }

    /**
     * Get total number of records in the table.
     */
    public function get_record_count(): int {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE %d = %d", 1, 1)
        );

        return (int) $count;
    }

    /**
     * Remove all rows from the table without dropping it.
     */
    public function truncate_table(): bool {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->query(
            $wpdb->prepare("TRUNCATE TABLE `{$table}` /* %s */", $table)
        );

        return false !== $result;
    }

    /**
     * Check whether the plugin table exists in the database.
     */
    public function table_exists(): bool {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $table)
        );

        return null !== $result;
    }

    /**
     * Drop the plugin table entirely.
     */
    public function drop_table(): void {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query(
            $wpdb->prepare("DROP TABLE IF EXISTS `{$table}` /* %s */", $table)
        );

        delete_option('BPID_SUITE_DB_VERSION');
    }
}

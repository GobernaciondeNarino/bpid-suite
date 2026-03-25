<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database manager for BPID Suite contract data.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Database {

    private static ?self $instance = null;

    private string $table_name;

    /** @var string[] Columns allowed in queries for filtering/ordering/distinct. */
    private const ALLOWED_COLUMNS = [
        'id',
        'dependencia',
        'numero_proyecto',
        'nombre_proyecto',
        'entidad_ejecutora',
        'odss',
        'numero',
        'objeto',
        'descripcion',
        'valor',
        'avance_fisico',
        'es_ops',
        'municipios',
        'imagenes',
        'fecha_importacion',
        'fecha_actualizacion',
    ];

    /** @var string[] Columns valid for ordering results. */
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
     */
    public function create_table(): void {
        global $wpdb;

        $table   = $this->table_name;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            dependencia VARCHAR(500),
            numero_proyecto VARCHAR(100),
            nombre_proyecto TEXT,
            entidad_ejecutora VARCHAR(500),
            odss LONGTEXT,
            numero VARCHAR(200),
            objeto LONGTEXT,
            descripcion LONGTEXT,
            valor DECIMAL(20,2),
            avance_fisico INT(3),
            es_ops TINYINT(1) DEFAULT 0,
            municipios LONGTEXT,
            imagenes LONGTEXT,
            fecha_importacion DATETIME DEFAULT CURRENT_TIMESTAMP,
            fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_numero_proyecto (numero(100), numero_proyecto(100)),
            KEY idx_dependencia (dependencia(100)),
            KEY idx_avance (avance_fisico),
            KEY idx_valor (valor),
            KEY idx_es_ops (es_ops)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('BPID_SUITE_DB_VERSION', BPID_SUITE_DB_VERSION);
    }

    /**
     * Insert or update a contract record.
     *
     * Uses the composite unique key (numero, numero_proyecto) for upsert logic.
     * Field names are mapped from JSON camelCase to database snake_case.
     *
     * @param array<string, mixed> $contrato Raw contract data from the API.
     * @return bool True on success, false on failure.
     */
    public function upsert_contrato(array $contrato): bool {
        global $wpdb;

        $dependencia      = sanitize_text_field((string) ($contrato['dependencia'] ?? ''));
        $numero_proyecto   = sanitize_text_field((string) ($contrato['numeroProyecto'] ?? ''));
        $nombre_proyecto   = sanitize_text_field((string) ($contrato['nombreProyecto'] ?? ''));
        $entidad_ejecutora = sanitize_text_field((string) ($contrato['entidadEjecutora'] ?? ''));
        $odss             = wp_json_encode($contrato['odss'] ?? []);
        $numero           = sanitize_text_field((string) ($contrato['numero'] ?? ''));
        $objeto           = sanitize_textarea_field((string) ($contrato['objeto'] ?? ''));
        $descripcion      = sanitize_textarea_field((string) ($contrato['descripcion'] ?? ''));
        $valor            = (float) ($contrato['valor'] ?? 0);
        $avance_fisico    = (int) ($contrato['avanceFisico'] ?? 0);
        $es_ops_raw       = (string) ($contrato['esOps'] ?? 'No');
        $es_ops           = ('Si' === $es_ops_raw || 'Sí' === $es_ops_raw) ? 1 : 0;
        $municipios       = wp_json_encode($contrato['municipios'] ?? []);
        $imagenes         = wp_json_encode($contrato['imagenes'] ?? []);

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe (prefix-based).
        $sql = $wpdb->prepare(
            "INSERT INTO `{$table}` (
                dependencia,
                numero_proyecto,
                nombre_proyecto,
                entidad_ejecutora,
                odss,
                numero,
                objeto,
                descripcion,
                valor,
                avance_fisico,
                es_ops,
                municipios,
                imagenes,
                fecha_importacion
            ) VALUES (
                %s, %s, %s, %s, %s, %s, %s, %s, %f, %d, %d, %s, %s, NOW()
            ) ON DUPLICATE KEY UPDATE
                dependencia      = VALUES(dependencia),
                nombre_proyecto  = VALUES(nombre_proyecto),
                entidad_ejecutora = VALUES(entidad_ejecutora),
                odss             = VALUES(odss),
                objeto           = VALUES(objeto),
                descripcion      = VALUES(descripcion),
                valor            = VALUES(valor),
                avance_fisico    = VALUES(avance_fisico),
                es_ops           = VALUES(es_ops),
                municipios       = VALUES(municipios),
                imagenes         = VALUES(imagenes),
                fecha_actualizacion = NOW()",
            $dependencia,
            $numero_proyecto,
            $nombre_proyecto,
            $entidad_ejecutora,
            $odss,
            $numero,
            $objeto,
            $descripcion,
            $valor,
            $avance_fisico,
            $es_ops,
            $municipios,
            $imagenes
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- already prepared above.
        $result = $wpdb->query($sql);

        return false !== $result;
    }

    /**
     * Retrieve contracts with pagination, filtering, and ordering.
     *
     * Accepted $args keys:
     *  - per_page        int    Number of results per page (default 20, max 100).
     *  - page            int    Current page number (default 1).
     *  - orderby         string Column to order by (default 'id').
     *  - order           string ASC or DESC (default 'DESC').
     *  - search          string Free-text search across key text columns.
     *  - dependencia     string Filter by exact dependencia.
     *  - es_ops          int    Filter by es_ops flag (0 or 1).
     *  - avance_min      int    Minimum avance_fisico.
     *  - avance_max      int    Maximum avance_fisico.
     *  - valor_min       float  Minimum valor.
     *  - valor_max       float  Maximum valor.
     *  - numero_proyecto string Filter by exact numero_proyecto.
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

        // Validate orderby against whitelist.
        if (!in_array($orderby, self::ORDERABLE_COLUMNS, true)) {
            $orderby = 'id';
        }

        // Validate order direction.
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        $where   = [];
        $values  = [];
        $table   = $this->table_name;

        // Free-text search.
        if (!empty($args['search'])) {
            $like     = '%' . $wpdb->esc_like(sanitize_text_field((string) $args['search'])) . '%';
            $where[]  = '(dependencia LIKE %s OR numero_proyecto LIKE %s OR nombre_proyecto LIKE %s OR numero LIKE %s OR objeto LIKE %s OR entidad_ejecutora LIKE %s)';
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
            $where[]  = 'avance_fisico >= %d';
            $values[] = (int) $args['avance_min'];
        }
        if (isset($args['avance_max']) && '' !== $args['avance_max']) {
            $where[]  = 'avance_fisico <= %d';
            $values[] = (int) $args['avance_max'];
        }

        // Valor range.
        if (isset($args['valor_min']) && '' !== $args['valor_min']) {
            $where[]  = 'valor >= %f';
            $values[] = (float) $args['valor_min'];
        }
        if (isset($args['valor_max']) && '' !== $args['valor_max']) {
            $where[]  = 'valor <= %f';
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

        // Build data query — orderby is validated against whitelist, order is validated above.
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
     *
     * @param int $id Row ID.
     * @return array<string, mixed>|null The row as an associative array or null.
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
     * Return aggregate statistics about the stored contracts.
     *
     * @return array{ total: int, total_valor: float, avg_avance: float, by_dependencia: array }
     */
    public function get_stats(): array {
        global $wpdb;

        $table = $this->table_name;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $totals = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total, COALESCE(SUM(valor), 0) AS total_valor, COALESCE(AVG(avance_fisico), 0) AS avg_avance FROM `{$table}` WHERE %d = %d",
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
     *
     * @param string $column Column name (validated against whitelist).
     * @return string[] Distinct values.
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
     *
     * @return bool True on success, false on failure.
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

<?php
declare(strict_types=1);
/**
 * Plugin Name:       BPID Suite
 * Plugin URI:        https://github.com/GobernaciondeNarino/bpid-suite
 * Description:       Plugin para importar, filtrar, graficar y visualizar datos del Banco de Proyectos de Inversión y Desarrollo (BPID) de la Gobernación de Nariño.
 * Version:           1.4.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Gobernación de Nariño — Secretaría de TIC, Innovación y Gobierno Abierto
 * Author URI:        https://narino.gov.co
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bpid-suite
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct file access
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin constants
 */
define('BPID_SUITE_VERSION', '1.4.0');
define('BPID_SUITE_PATH', plugin_dir_path(__FILE__));
define('BPID_SUITE_URL', plugin_dir_url(__FILE__));
define('BPID_SUITE_BASENAME', plugin_basename(__FILE__));
define('BPID_SUITE_API_URL', 'https://bpid.narino.gov.co/bpid/publico/consulta_contratos_con_ejecucion_contractual.php');
define('BPID_SUITE_DB_VERSION', '1.0.0');

/**
 * Main plugin class — Singleton pattern
 */
final class BPID_Suite {

    private static ?self $instance = null;

    /** @var array<string, object> Loaded module instances */
    private array $modules = [];

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function __clone() {}
    public function __wakeup(): void {
        throw new \RuntimeException('Cannot unserialize singleton');
    }

    /**
     * Load all required class files
     */
    private function load_dependencies(): void {
        $includes = BPID_SUITE_PATH . 'includes/';

        require_once $includes . 'class-logger.php';
        require_once $includes . 'class-database.php';
        require_once $includes . 'class-importer.php';
        require_once $includes . 'class-visualizer.php';
        require_once $includes . 'class-filter.php';
        require_once $includes . 'class-post.php';
        require_once $includes . 'class-rest-api.php';
        require_once $includes . 'class-updater.php';

        if (defined('WP_CLI') && WP_CLI) {
            require_once $includes . 'class-cli.php';
        }
    }

    /**
     * Register WordPress hooks
     */
    private function init_hooks(): void {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('plugins_loaded', [$this, 'init_modules'], 10);
        add_action('admin_menu', [$this, 'register_admin_menu'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 10);
        add_action('admin_init', [$this, 'handle_config_save'], 10);
    }

    /**
     * Initialize all plugin modules
     */
    public function init_modules(): void {
        $this->modules['logger']     = BPID_Suite_Logger::get_instance();
        $this->modules['database']   = BPID_Suite_Database::get_instance();
        $this->modules['importer']   = BPID_Suite_Importer::get_instance();
        $this->modules['visualizer'] = BPID_Suite_Visualizer::get_instance();
        $this->modules['filter']     = BPID_Suite_Filter::get_instance();
        $this->modules['post']       = BPID_Suite_Post::get_instance();
        $this->modules['rest_api']   = BPID_Suite_Rest_API::get_instance();
        $this->modules['updater']    = BPID_Suite_Updater::get_instance();

        if (defined('WP_CLI') && WP_CLI) {
            BPID_Suite_CLI::register();
        }
    }

    /**
     * Get a specific module instance
     */
    public function get_module(string $name): ?object {
        return $this->modules[$name] ?? null;
    }

    /**
     * Plugin activation
     */
    public function activate(): void {
        BPID_Suite_Database::get_instance()->create_table();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        wp_clear_scheduled_hook('bpid_suite_cron_import');
        flush_rewrite_rules();
    }

    /**
     * Register admin menu pages
     */
    public function register_admin_menu(): void {
        add_menu_page(
            __('BPID Suite', 'bpid-suite'),
            __('BPID Suite', 'bpid-suite'),
            'manage_options',
            'bpid-suite',
            [$this, 'render_dashboard_page'],
            'dashicons-chart-area',
            30
        );

        add_submenu_page(
            'bpid-suite',
            __('Dashboard', 'bpid-suite'),
            __('Dashboard', 'bpid-suite'),
            'manage_options',
            'bpid-suite',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'bpid-suite',
            __('Importación', 'bpid-suite'),
            __('Importación', 'bpid-suite'),
            'manage_options',
            'bpid-suite-import',
            [$this, 'render_import_page']
        );

        add_submenu_page(
            'bpid-suite',
            __('Registros', 'bpid-suite'),
            __('Registros', 'bpid-suite'),
            'manage_options',
            'bpid-suite-records',
            [$this, 'render_records_page']
        );

        add_submenu_page(
            'bpid-suite',
            __('Logs', 'bpid-suite'),
            __('Logs', 'bpid-suite'),
            'manage_options',
            'bpid-suite-logs',
            [$this, 'render_logs_page']
        );

        add_submenu_page(
            'bpid-suite',
            __('Configuración', 'bpid-suite'),
            __('Configuración', 'bpid-suite'),
            'manage_options',
            'bpid-suite-config',
            [$this, 'render_config_page']
        );
    }

    /**
     * Render admin pages via templates
     */
    public function render_dashboard_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }
        include BPID_SUITE_PATH . 'templates/admin/dashboard-page.php';
    }

    public function render_config_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }
        include BPID_SUITE_PATH . 'templates/admin/config-page.php';
    }

    public function render_import_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }
        include BPID_SUITE_PATH . 'templates/admin/import-page.php';
    }

    public function render_records_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }
        include BPID_SUITE_PATH . 'templates/admin/records-page.php';
    }

    public function render_logs_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }
        include BPID_SUITE_PATH . 'templates/admin/logs-page.php';
    }

    /**
     * Handle configuration form save
     */
    public function handle_config_save(): void {
        if (!isset($_POST['bpid_suite_config_nonce'])) {
            return;
        }

        check_admin_referer('bpid_suite_config_save', 'bpid_suite_config_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No autorizado', 'bpid-suite'));
        }

        $api_key = sanitize_text_field(wp_unslash($_POST['bpid_suite_api_key'] ?? ''));
        if (!empty($api_key)) {
            update_option('bpid_suite_api_key', $api_key);
        }

        $cron_frequency = sanitize_text_field(wp_unslash($_POST['bpid_suite_cron_frequency'] ?? 'disabled'));
        $allowed_frequencies = ['disabled', 'daily', 'weekly', 'monthly'];
        if (!in_array($cron_frequency, $allowed_frequencies, true)) {
            $cron_frequency = 'disabled';
        }

        update_option('bpid_suite_cron_frequency', $cron_frequency);
        $this->schedule_cron($cron_frequency);

        add_settings_error('bpid_suite', 'settings_updated', __('Configuración guardada correctamente.', 'bpid-suite'), 'updated');
        set_transient('bpid_suite_settings_errors', get_settings_errors('bpid_suite'), 30);
    }

    /**
     * Schedule or unschedule cron based on frequency
     */
    private function schedule_cron(string $frequency): void {
        wp_clear_scheduled_hook('bpid_suite_cron_import');

        if ($frequency === 'disabled') {
            return;
        }

        $recurrence_map = [
            'daily'   => 'daily',
            'weekly'  => 'weekly',
            'monthly' => 'monthly',
        ];

        $recurrence = $recurrence_map[$frequency] ?? null;
        if ($recurrence) {
            if (!wp_next_scheduled('bpid_suite_cron_import')) {
                wp_schedule_event(time() + HOUR_IN_SECONDS, $recurrence, 'bpid_suite_cron_import');
            }
        }
    }

    /**
     * Enqueue admin CSS and JS
     */
    public function enqueue_admin_assets(string $hook): void {
        $plugin_pages = [
            'toplevel_page_bpid-suite',
            'bpid-suite_page_bpid-suite-import',
            'bpid-suite_page_bpid-suite-records',
            'bpid-suite_page_bpid-suite-logs',
            'bpid-suite_page_bpid-suite-config',
        ];

        $screen = get_current_screen();
        $is_plugin_page = in_array($hook, $plugin_pages, true);
        $is_cpt_page = $screen && in_array($screen->post_type, ['bpid_chart', 'bpid_filter', 'bpid_post'], true);

        if (!$is_plugin_page && !$is_cpt_page) {
            return;
        }

        wp_enqueue_style(
            'bpid-suite-admin',
            BPID_SUITE_URL . 'assets/css/admin.css',
            [],
            BPID_SUITE_VERSION
        );

        // Import page uses an inline script in import-page.php template.
        // No external JS file is enqueued to avoid duplicate AJAX handlers.

        if ($screen && $screen->post_type === 'bpid_chart') {
            wp_enqueue_script(
                'bpid-suite-admin-charts',
                BPID_SUITE_URL . 'assets/js/admin-charts.js',
                [],
                BPID_SUITE_VERSION,
                true
            );

            $post_id = get_the_ID();
            $saved_y_columns = get_post_meta((int) $post_id, '_chart_y_columns', true);
            $saved_y_colors  = get_post_meta((int) $post_id, '_chart_y_colors', true);

            wp_localize_script('bpid-suite-admin-charts', 'bpidCharts', [
                'ajaxUrl'       => admin_url('admin-ajax.php'),
                'nonce'         => wp_create_nonce('bpid_charts_nonce'),
                'savedTable'    => get_post_meta((int) $post_id, '_chart_data_table', true) ?: '',
                'savedAxisX'    => get_post_meta((int) $post_id, '_chart_axis_x', true) ?: '',
                'savedYColumns' => is_array($saved_y_columns) ? $saved_y_columns : [],
                'savedYColors'  => is_array($saved_y_colors) ? $saved_y_colors : [],
            ]);
        }

        if ($screen && $screen->post_type === 'bpid_filter') {
            wp_enqueue_script(
                'bpid-suite-admin-filters',
                BPID_SUITE_URL . 'assets/js/admin-filters.js',
                ['jquery'],
                BPID_SUITE_VERSION,
                true
            );
            wp_localize_script('bpid-suite-admin-filters', 'bpidFiltersAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('bpid_filter_admin_nonce'),
            ]);
        }

        if ($screen && $screen->post_type === 'bpid_post') {
            wp_enqueue_script(
                'bpid-suite-admin-post',
                BPID_SUITE_URL . 'assets/js/admin-post.js',
                ['jquery', 'wp-color-picker'],
                BPID_SUITE_VERSION,
                true
            );
            wp_enqueue_style('wp-color-picker');
            wp_localize_script('bpid-suite-admin-post', 'bpidSuitePost', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('bpid_post_clear_cache'),
            ]);
        }
    }

    /**
     * Enqueue frontend CSS and JS
     */
    public function enqueue_frontend_assets(): void {
        wp_enqueue_style(
            'bpid-suite-frontend',
            BPID_SUITE_URL . 'assets/css/frontend.css',
            [],
            BPID_SUITE_VERSION
        );
    }

    /**
     * Add monthly cron schedule if not exists
     */
    public static function add_cron_schedules(array $schedules): array {
        if (!isset($schedules['monthly'])) {
            $schedules['monthly'] = [
                'interval' => 30 * DAY_IN_SECONDS,
                'display'  => __('Una vez al mes', 'bpid-suite'),
            ];
        }
        return $schedules;
    }
}

// Add monthly schedule
add_filter('cron_schedules', ['BPID_Suite', 'add_cron_schedules'], 10, 1);

// Initialize plugin
BPID_Suite::get_instance();

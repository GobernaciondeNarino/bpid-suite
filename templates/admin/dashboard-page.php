<?php
/**
 * Admin template: Main dashboard.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$db            = BPID_Suite_Database::get_instance();
$table_exists  = $db->table_exists();
$record_count  = $table_exists ? $db->get_record_count() : 0;
$stats         = $table_exists ? $db->get_stats() : [];
$last_import   = get_option('bpid_suite_last_import_date', '');
$cron_freq     = get_option('bpid_suite_cron_frequency', 'disabled');
$next_cron     = wp_next_scheduled('bpid_suite_cron_import');

// Distinct project count.
$distinct_projects = 0;
if ($table_exists) {
    $distinct_values   = $db->get_distinct_values('numero_proyecto');
    $distinct_projects = count($distinct_values);
}

$avg_avance = isset($stats['avg_avance']) ? $stats['avg_avance'] : 0;

global $wpdb;
?>

<div class="wrap bpid-wrap">

    <!-- Page Header -->
    <div class="bpid-page-header">
        <div class="bpid-page-header-left">
            <svg class="bpid-page-header-logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="4" width="40" height="40" rx="4" />
                <line x1="12" y1="36" x2="12" y2="24" />
                <line x1="20" y1="36" x2="20" y2="16" />
                <line x1="28" y1="36" x2="28" y2="20" />
                <line x1="36" y1="36" x2="36" y2="12" />
            </svg>
            <div>
                <h1 class="bpid-page-header-title"><?php echo esc_html__('BPID Suite', 'bpid-suite'); ?></h1>
                <p class="bpid-page-header-subtitle"><?php echo esc_html__('Banco de Proyectos de Inversión y Desarrollo — Gobernación de Nariño', 'bpid-suite'); ?></p>
            </div>
        </div>
        <div class="bpid-page-header-right">
            <span class="bpid-version-badge">
                <?php
                /* translators: %s: plugin version number */
                printf(esc_html__('v%s', 'bpid-suite'), esc_html(BPID_SUITE_VERSION));
                ?>
            </span>
            <a href="<?php echo esc_url(admin_url('admin.php?page=bpid-suite-import')); ?>" class="bpid-btn bpid-btn-primary">
                <span class="dashicons dashicons-upload"></span>
                <?php echo esc_html__('Importar Datos', 'bpid-suite'); ?>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="bpid-dashboard-stats">

        <div class="bpid-stat-card bpid-stat-card--primary">
            <div class="bpid-stat-card-icon">
                <span class="dashicons dashicons-database"></span>
            </div>
            <div class="bpid-stat-card-content">
                <span class="bpid-stat-card-value"><?php echo esc_html(number_format_i18n($record_count)); ?></span>
                <span class="bpid-stat-card-label"><?php echo esc_html__('Total Contratos', 'bpid-suite'); ?></span>
            </div>
        </div>

        <div class="bpid-stat-card bpid-stat-card--info">
            <div class="bpid-stat-card-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="bpid-stat-card-content">
                <span class="bpid-stat-card-value"><?php echo esc_html(number_format_i18n($distinct_projects)); ?></span>
                <span class="bpid-stat-card-label"><?php echo esc_html__('Total Proyectos', 'bpid-suite'); ?></span>
            </div>
        </div>

        <div class="bpid-stat-card bpid-stat-card--success">
            <div class="bpid-stat-card-icon">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="bpid-stat-card-content">
                <span class="bpid-stat-card-value"><?php echo esc_html(number_format_i18n($avg_avance, 2)); ?>%</span>
                <span class="bpid-stat-card-label"><?php echo esc_html__('Promedio Avance', 'bpid-suite'); ?></span>
            </div>
        </div>

        <div class="bpid-stat-card bpid-stat-card--warning">
            <div class="bpid-stat-card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="bpid-stat-card-content">
                <span class="bpid-stat-card-value">
                    <?php
                    if (!empty($last_import)) {
                        echo esc_html($last_import);
                    } else {
                        echo esc_html__('Nunca', 'bpid-suite');
                    }
                    ?>
                </span>
                <span class="bpid-stat-card-label"><?php echo esc_html__('Última Importación', 'bpid-suite'); ?></span>
            </div>
        </div>

    </div>

    <!-- Dashboard Grid -->
    <div class="bpid-dashboard-grid">

        <!-- Left Column: Table Status -->
        <div class="bpid-card">
            <div class="bpid-card-header">
                <h2 class="bpid-card-title"><?php echo esc_html__('Estado de la tabla', 'bpid-suite'); ?></h2>
            </div>
            <div class="bpid-card-body">
                <ul class="bpid-table-status-list">
                    <li>
                        <span class="bpid-table-name"><?php echo esc_html($db->get_table_name()); ?></span>
                        <?php if ($table_exists) : ?>
                            <span class="bpid-status-dot bpid-status-dot--ok"></span>
                            <span class="bpid-status-label"><?php echo esc_html__('OK', 'bpid-suite'); ?></span>
                        <?php else : ?>
                            <span class="bpid-status-dot bpid-status-dot--error"></span>
                            <span class="bpid-status-label"><?php echo esc_html__('Error', 'bpid-suite'); ?></span>
                        <?php endif; ?>
                        <span class="bpid-record-count">
                            <?php
                            /* translators: %s: number of records */
                            printf(esc_html__('%s registros', 'bpid-suite'), esc_html(number_format_i18n($record_count)));
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Right Column: Quick Actions -->
        <div class="bpid-card">
            <div class="bpid-card-header">
                <h2 class="bpid-card-title"><?php echo esc_html__('Acciones rápidas', 'bpid-suite'); ?></h2>
            </div>
            <div class="bpid-card-body">
                <div class="bpid-quick-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bpid-suite-import')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-download"></span>
                        <?php echo esc_html__('Importar Datos', 'bpid-suite'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bpid-suite-records')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-list-view"></span>
                        <?php echo esc_html__('Ver Registros', 'bpid-suite'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=bpid_chart')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php echo esc_html__('Crear Gráfico', 'bpid-suite'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=bpid_post')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php echo esc_html__('Crear Visualización', 'bpid-suite'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bpid-suite-logs')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-media-text"></span>
                        <?php echo esc_html__('Ver Logs', 'bpid-suite'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bpid-suite-config')); ?>" class="bpid-quick-action">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php echo esc_html__('Configuración', 'bpid-suite'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Full Width: System Info -->
        <div class="bpid-card bpid-card--full">
            <div class="bpid-card-header">
                <h2 class="bpid-card-title"><?php echo esc_html__('Información del sistema', 'bpid-suite'); ?></h2>
            </div>
            <div class="bpid-card-body">
                <div class="bpid-sysinfo-grid">
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('Versión del plugin', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html(BPID_SUITE_VERSION); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('WordPress', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('PHP', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html(phpversion()); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('MySQL', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html($wpdb->db_version()); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('Estado tabla', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value">
                            <?php echo $table_exists ? esc_html__('Existe', 'bpid-suite') : esc_html__('No existe', 'bpid-suite'); ?>
                        </span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('Total registros', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html(number_format_i18n($record_count)); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('Frecuencia Cron', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value"><?php echo esc_html($cron_freq); ?></span>
                    </div>
                    <div class="bpid-sysinfo-item">
                        <span class="bpid-sysinfo-label"><?php echo esc_html__('Próxima ejecución', 'bpid-suite'); ?></span>
                        <span class="bpid-sysinfo-value">
                            <?php
                            if ($next_cron) {
                                echo esc_html(
                                    date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'),
                                        $next_cron + (int) (get_option('gmt_offset', 0) * HOUR_IN_SECONDS)
                                    )
                                );
                            } else {
                                echo esc_html__('No programada', 'bpid-suite');
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

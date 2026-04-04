<?php
declare(strict_types=1);
/**
 * BPID Suite — Uninstall
 *
 * Removes all plugin data when uninstalled via WordPress admin.
 *
 * @package BPID_Suite
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Check if user opted to delete all data on uninstall
$delete_data = get_option('bpid_suite_delete_data_on_uninstall', '0');

if ('1' === $delete_data) {
    // 1. Drop all custom tables (relational + main)
    $tables_to_drop = [
        $wpdb->prefix . 'bpid_contrato_municipios',
        $wpdb->prefix . 'bpid_contrato_odss',
        $wpdb->prefix . 'bpid_contrato_metas',
        $wpdb->prefix . 'bpid_suite_contratos',
    ];

    foreach ($tables_to_drop as $t) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS `{$t}`");
    }
}

// 2. Delete all plugin options (always clean up options)
$options_to_delete = [
    'bpid_suite_api_key',
    'bpid_suite_db_version',
    'BPID_SUITE_DB_VERSION',
    'bpid_suite_cron_frequency',
    'bpid_suite_last_import',
    'bpid_suite_last_import_date',
    'bpid_suite_import_stats',
    'bpid_suite_delete_data_on_uninstall',
];

foreach ($options_to_delete as $option) {
    delete_option($option);
}

// 3. Delete all transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_bpid_suite_%'
        OR option_name LIKE '_transient_timeout_bpid_suite_%'
        OR option_name LIKE '_transient_bpid_post_api_%'
        OR option_name LIKE '_transient_timeout_bpid_post_api_%'
        OR option_name LIKE '_transient_bpid_rest_rate_%'
        OR option_name LIKE '_transient_timeout_bpid_rest_rate_%'"
);

// 4. Delete all CPT posts: bpid_chart, bpid_filter, bpid_post
$cpt_types = ['bpid_chart', 'bpid_filter', 'bpid_post'];

foreach ($cpt_types as $post_type) {
    $posts = get_posts([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ]);

    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true);
    }
}

// 5. Clear scheduled cron events
wp_clear_scheduled_hook('bpid_suite_cron_import');

// 6. Delete log files
$log_dir = plugin_dir_path(__FILE__) . 'logs/';
if (is_dir($log_dir)) {
    $files = glob($log_dir . '*');
    if (is_array($files)) {
        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }
}

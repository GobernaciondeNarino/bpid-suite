<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Updater
 *
 * GitHub-based auto-updater for the BPID Suite plugin.
 * Checks the public GitHub releases API for newer versions
 * and integrates with the WordPress plugin update system.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Updater {

    private static ?self $instance = null;

    /** @var string GitHub repository in owner/repo format */
    private string $github_repo = 'GobernaciondeNarino/bpid-suite';

    /** @var string Plugin slug as registered in WordPress */
    private string $plugin_slug = 'bpid-suite/bpid-suite.php';

    /** @var string GitHub releases API base URL */
    private string $github_api_url = 'https://api.github.com/repos/';

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
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_updates']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_action('upgrader_process_complete', [$this, 'after_update'], 10, 2);
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException(
            esc_html__('Cannot unserialize singleton', 'bpid-suite')
        );
    }

    /**
     * Check GitHub for a newer plugin release.
     *
     * Caches the result in a transient for 12 hours to avoid
     * excessive API requests.
     *
     * @param object $transient WordPress update transient data.
     * @return object Modified transient data.
     */
    public function check_for_updates(object $transient): object {
        if (empty($transient->checked)) {
            return $transient;
        }

        $cached = get_transient('bpid_suite_update_check');

        if (false === $cached) {
            $release = $this->get_latest_release();

            if (null === $release) {
                set_transient('bpid_suite_update_check', 'none', 12 * HOUR_IN_SECONDS);
                return $transient;
            }

            $cached = $release;
            set_transient('bpid_suite_update_check', $cached, 12 * HOUR_IN_SECONDS);
        }

        if ('none' === $cached) {
            return $transient;
        }

        $remote_version = ltrim($cached['tag_name'], 'v');

        if (version_compare($remote_version, BPID_SUITE_VERSION, '>')) {
            $transient->response[$this->plugin_slug] = (object) [
                'slug'        => 'bpid-suite',
                'plugin'      => $this->plugin_slug,
                'new_version' => $remote_version,
                'url'         => 'https://github.com/' . $this->github_repo,
                'package'     => $cached['zipball_url'],
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin information for the WordPress update/install screen.
     *
     * @param false|object $result The result object or false.
     * @param string       $action The API action being performed.
     * @param object       $args   Plugin API arguments.
     * @return false|object Plugin info object or the original result.
     */
    public function plugin_info(false|object $result, string $action, object $args): false|object {
        if ('plugin_information' !== $action) {
            return $result;
        }

        if (!isset($args->slug) || 'bpid-suite' !== $args->slug) {
            return $result;
        }

        $release = $this->get_latest_release();

        if (null === $release) {
            return $result;
        }

        $remote_version = ltrim($release['tag_name'], 'v');

        return (object) [
            'name'          => __('BPID Suite', 'bpid-suite'),
            'slug'          => 'bpid-suite',
            'version'       => $remote_version,
            'author'        => __('Gobernación de Nariño', 'bpid-suite'),
            'homepage'      => 'https://github.com/' . $this->github_repo,
            'download_link' => $release['zipball_url'],
            'sections'      => [
                'description' => __('BPID Suite — Banco de Proyectos de Inversión Departamental.', 'bpid-suite'),
                'changelog'   => nl2br(esc_html($release['body'])),
            ],
        ];
    }

    /**
     * Clean up cached data after the plugin has been updated.
     *
     * @param \WP_Upgrader $upgrader WP_Upgrader instance.
     * @param array        $options  Array of update data.
     */
    public function after_update(\WP_Upgrader $upgrader, array $options): void {
        if (
            isset($options['action'], $options['type'], $options['plugins']) &&
            'update' === $options['action'] &&
            'plugin' === $options['type'] &&
            is_array($options['plugins'])
        ) {
            if (in_array($this->plugin_slug, $options['plugins'], true)) {
                delete_transient('bpid_suite_update_check');
            }
        }
    }

    /**
     * Fetch the latest release data from the GitHub API.
     *
     * @return array{tag_name: string, body: string, zipball_url: string, published_at: string}|null
     */
    public function get_latest_release(): ?array {
        $url = $this->github_api_url . $this->github_repo . '/releases/latest';

        $response = wp_remote_get($url, [
            'timeout'   => 10,
            'sslverify' => false,
            'headers'   => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);

        if (200 !== $code) {
            return null;
        }

        /** @var array|null $data */
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        return [
            'tag_name'     => sanitize_text_field($data['tag_name']),
            'body'         => $data['body'] ?? '',
            'zipball_url'  => esc_url_raw($data['zipball_url'] ?? ''),
            'published_at' => sanitize_text_field($data['published_at'] ?? ''),
        ];
    }
}

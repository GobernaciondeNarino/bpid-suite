<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * BPID Suite Logger
 *
 * Handles logging for the BPID Suite plugin with file rotation,
 * directory protection, and timestamped entries.
 *
 * @package BPID_Suite
 * @since   1.0.0
 */
final class BPID_Suite_Logger {

    private static ?self $instance = null;

    /** @var string Full path to the log file */
    private string $log_file;

    /** @var string Full path to the logs directory */
    private string $log_dir;

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
        $this->log_dir  = BPID_SUITE_PATH . 'logs/';
        $this->log_file = $this->log_dir . 'import.log';

        $this->ensure_log_directory();
    }

    private function __clone() {}

    public function __wakeup(): void {
        throw new \RuntimeException(
            esc_html__('Cannot unserialize singleton', 'bpid-suite')
        );
    }

    /**
     * Write a timestamped log entry.
     *
     * @param string $message The message to log.
     * @param string $level   Log level: info, warning, error, debug.
     */
    public function log(string $message, string $level = 'info'): void {
        $this->rotate_logs();

        $timestamp = current_time('Y-m-d H:i:s');
        $level_tag = strtoupper($level);
        $entry     = sprintf("[%s] [%s] %s\n", $timestamp, $level_tag, $message);

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        @file_put_contents($this->log_file, $entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log an error-level message.
     *
     * @param string $message The error message.
     */
    public function error(string $message): void {
        $this->log($message, 'error');
    }

    /**
     * Log a warning-level message.
     *
     * @param string $message The warning message.
     */
    public function warning(string $message): void {
        $this->log($message, 'warning');
    }

    /**
     * Retrieve the last N lines from the log file.
     *
     * @param int $lines Number of lines to return.
     * @return array<int, string> Array of log lines (newest last).
     */
    public function get_logs(int $lines = 100): array {
        if (!is_readable($this->log_file)) {
            return [];
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
        $contents = @file_get_contents($this->log_file);

        if (false === $contents || '' === $contents) {
            return [];
        }

        $all_lines = explode("\n", trim($contents));

        return array_slice($all_lines, -$lines);
    }

    /**
     * Clear the log file.
     *
     * @return bool True on success, false on failure.
     */
    public function clear_logs(): bool {
        if (!file_exists($this->log_file)) {
            return true;
        }

        if (!is_writable($this->log_file)) {
            return false;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        $result = @file_put_contents($this->log_file, '');

        return false !== $result;
    }

    /**
     * Rotate the log file when it exceeds the maximum size.
     *
     * The current file is renamed with a `.1` suffix (overwriting any
     * previous rotation) and a fresh file is started.
     *
     * @param int $max_size_mb Maximum log file size in megabytes.
     */
    public function rotate_logs(int $max_size_mb = 5): void {
        if (!file_exists($this->log_file)) {
            return;
        }

        $max_bytes = $max_size_mb * 1024 * 1024;
        $file_size = @filesize($this->log_file);

        if (false === $file_size || $file_size < $max_bytes) {
            return;
        }

        $rotated = $this->log_file . '.1';

        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
        @rename($this->log_file, $rotated);
    }

    /**
     * Create the logs directory with protective files if it does not exist.
     */
    private function ensure_log_directory(): void {
        if (!is_dir($this->log_dir)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
            if (!@mkdir($this->log_dir, 0755, true) && !is_dir($this->log_dir)) {
                return;
            }
        }

        $htaccess = $this->log_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            @file_put_contents($htaccess, "deny from all\n");
        }

        $index = $this->log_dir . 'index.php';
        if (!file_exists($index)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            @file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }
}

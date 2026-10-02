<?php
/**
 * Bootstrap for unit tests (no WordPress core, Brain Monkey stubs).
 *
 * @package wp-theme
 */

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_readable($autoload)) {
	fwrite(STDERR, "Composer autoload not found. Run: composer install\n");
	exit(1);
}

require_once $autoload;

if (! defined('ABSPATH')) {
	define('ABSPATH', dirname(__DIR__) . '/');
}

if (! defined('WP_THEME_DIR')) {
	define('WP_THEME_DIR', dirname(__DIR__) . '/wp-content/themes/wp-theme');
}

/**
 * Minimal WP_Error for unit tests that load theme modules without core.
 */
if (! class_exists('WP_Error', false)) {
	class WP_Error {
		/** @var string */
		public $code;

		/** @var string */
		public $message;

		/** @var mixed */
		public $data;

		/**
		 * @param string|int $code    Error code.
		 * @param string     $message Message.
		 * @param mixed      $data    Optional data.
		 */
		public function __construct($code = '', $message = '', $data = '') {
			$this->code    = (string) $code;
			$this->message = (string) $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

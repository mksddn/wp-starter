<?php
/**
 * Base test case for unit tests with Brain Monkey.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Shared setup/teardown for Brain Monkey.
 */
abstract class UnitTestCase extends TestCase {
	use MockeryPHPUnitIntegration;

	/**
	 * Theme absolute path.
	 */
	protected string $theme_dir;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->theme_dir = WP_THEME_DIR;

		Functions\when('__')->returnArg();
		Functions\when('esc_html__')->returnArg();
		Functions\when('esc_html')->returnArg();
		Functions\when('esc_attr')->returnArg();
		Functions\when('esc_url')->returnArg();
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('sanitize_title')->alias(
			static function ($title) {
				return strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $title) ?? '');
			}
		);
		Functions\when('wp_unslash')->returnArg();
		Functions\when('wp_json_encode')->alias('json_encode');
		Functions\when('add_action')->justReturn(true);
		Functions\when('add_filter')->justReturn(true);
		Functions\when('remove_action')->justReturn(true);
		Functions\when('remove_filter')->justReturn(true);
		Functions\when('apply_filters')->alias(
			static function ($hook, $value) {
				return $value;
			}
		);
		Functions\when('do_action')->justReturn(null);
		Functions\when('is_wp_error')->alias(
			static function ($thing): bool {
				return $thing instanceof \WP_Error;
			}
		);
		Functions\when('current_user_can')->justReturn(true);
		Functions\when('get_template_directory')->justReturn($this->theme_dir);
		Functions\when('get_stylesheet_directory')->justReturn($this->theme_dir);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Require a theme PHP file once (path relative to theme root).
	 *
	 * @param string $relative Relative path under wp-theme/.
	 */
	protected function requireThemeFile(string $relative): void {
		require_once $this->theme_dir . '/' . ltrim($relative, '/');
	}
}

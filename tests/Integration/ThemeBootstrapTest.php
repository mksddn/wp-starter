<?php
/**
 * Integration tests for theme bootstrap and security hooks.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_UnitTestCase;

/**
 * Theme load and default security wiring.
 */
final class ThemeBootstrapTest extends WP_UnitTestCase {

	public function test_theme_is_active(): void {
		$this->assertSame('wp-theme', get_template());
		$this->assertTrue(function_exists('theme_setup'));
	}

	public function test_theme_supports_core_features(): void {
		$this->assertTrue(current_theme_supports('title-tag'));
		$this->assertTrue(current_theme_supports('post-thumbnails'));
		$this->assertNotFalse(get_theme_support('custom-logo'));
	}

	public function test_settings_option_exists(): void {
		$settings = get_option('wp_theme_settings');
		$this->assertIsArray($settings);
		$this->assertTrue(! empty($settings['headless']));
		$this->assertTrue(! empty($settings['svg_support']));
		$this->assertTrue(! empty($settings['category_thumbnails']));
	}

	public function test_security_hooks_registered(): void {
		$this->assertNotFalse(has_filter('xmlrpc_enabled', '__return_false'));
		$this->assertNotFalse(has_filter('wp_headers', 'wp_theme_remove_x_pingback'));
		$this->assertNotFalse(has_filter('the_generator', '__return_empty_string'));
		$this->assertNotFalse(has_filter('rest_endpoints', 'wp_theme_filter_public_user_endpoints_security'));
	}

	public function test_cyrillic_title_is_transliterated(): void {
		$this->assertSame('privet-mir', sanitize_title('Привет мир'));
	}
}

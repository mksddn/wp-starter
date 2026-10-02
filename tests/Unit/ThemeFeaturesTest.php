<?php
/**
 * Unit tests for theme settings helpers.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_get_default_settings
 * @covers ::wp_theme_settings_sanitize
 * @covers ::wp_theme_disable_generated_excerpt
 * @covers ::wp_theme_is_auto_excerpt_disabled
 */
final class ThemeFeaturesTest extends UnitTestCase {

	/** @var array<string, mixed> */
	private array $stored_settings;

	protected function setUp(): void {
		parent::setUp();

		$this->stored_settings = array(
			'disable_comments'             => false,
			'cyr2lat'                      => false,
			'woocommerce_support'          => false,
			'plugins_logger'               => false,
			'duplicate_post'               => false,
			'thumbnail_column'             => false,
			'polylang_rest_api'            => false,
			'disable_gutenberg'            => false,
			'disable_gutenberg_post_types' => array(),
			'page_excerpt'                 => false,
			'disable_auto_excerpt'          => true,
			'clean_archive_title'          => false,
			'clean_pagination'             => false,
			'category_thumbnails'          => false,
			'headless'                     => false,
			'schema_markup'                => false,
			'security_xmlrpc'              => false,
			'security_x_pingback'          => false,
			'security_hide_version'        => false,
			'security_disable_editing'     => false,
			'security_disable_enumeration' => false,
			'security_headers'             => false,
			'security_directory_browsing'  => false,
			'security_login_limits'        => false,
			'search_empty_handling'        => false,
			'search_post_types'            => false,
			'search_post_types_list'       => array(),
			'search_exclude_ids_list'      => array(),
			'search_exclude_slugs_list'    => array(),
			'acf_local_json'               => false,
			'performance_remove_emojis'    => false,
			'performance_disable_embeds'   => false,
			'performance_optimize_queries' => false,
			'performance_cleanup_head'     => false,
			'performance_disable_wp_cron'  => false,
			'performance_limit_revisions'  => false,
			'performance_revisions_limit'  => 3,
			'image_opt_upload_limits'      => false,
			'image_opt_quality'            => false,
			'image_opt_priority_loading'   => false,
			'image_opt_max_file_size'      => 5,
			'image_opt_max_dimension'      => 2560,
			'image_opt_jpeg_quality'       => 85,
			'image_opt_quality_value'      => 85,
			'image_opt_remove_sizes_list'  => array(),
			'file_size_column'             => false,
			'svg_support'                  => false,
		);

		$settings = &$this->stored_settings;

		Functions\when('get_option')->alias(
			static function ($key, $default = false) use (&$settings) {
				if ('wp_theme_settings' === $key) {
					return $settings;
				}

				return $default;
			}
		);
		Functions\when('update_option')->justReturn(true);
		Functions\when('get_post_types')->justReturn(array( 'post' => 'post', 'page' => 'page' ));
		Functions\when('is_admin')->justReturn(false);
		Functions\when('add_options_page')->justReturn('settings_page_wp-theme-settings');
		Functions\when('register_setting')->justReturn(true);
		Functions\when('add_settings_section')->justReturn(true);
		Functions\when('add_settings_field')->justReturn(true);
		Functions\when('check_admin_referer')->justReturn(true);
		Functions\when('wp_die')->alias(
			static function ($message = '') {
				throw new \RuntimeException((string) $message);
			}
		);
		Functions\when('has_excerpt')->justReturn(false);
		Functions\when('__')->returnArg();
		Functions\when('esc_html_e')->justReturn(null);
		Functions\when('esc_attr_e')->justReturn(null);
		Functions\when('esc_html__')->returnArg();
		Functions\when('admin_url')->justReturn('http://example.test/wp-admin/');
		Functions\when('wp_nonce_field')->justReturn(null);
		Functions\when('wp_create_nonce')->justReturn('nonce');
		Functions\when('sanitize_text_field')->returnArg();
		Functions\when('absint')->alias('intval');

		$this->requireThemeFile('inc/theme-features.php');
		wp_theme_clear_settings_cache();
	}

	public function test_default_settings_contain_expected_keys(): void {
		$defaults = wp_theme_get_default_settings();

		$this->assertTrue($defaults['disable_comments']);
		$this->assertTrue($defaults['svg_support']);
		$this->assertFalse($defaults['headless']);
		$this->assertSame(5, $defaults['image_opt_max_file_size']);
	}

	public function test_settings_sanitize_booleans_and_sync_image_opt(): void {
		$input = array(
			'headless'                     => '1',
			'svg_support'                  => '1',
			'image_opt_upload_limits'      => '1',
			'search_exclude_ids_list'      => '1, 2, abc, -5',
			'disable_gutenberg_post_types' => array( 'page', 'fake' ),
			'search_post_types_list'       => array( 'post' ),
		);

		$result = wp_theme_settings_sanitize($input);

		$this->assertTrue($result['headless']);
		$this->assertTrue($result['svg_support']);
		$this->assertTrue($result['image_opt_upload_limits']);
		$this->assertTrue($result['image_opt_quality']);
		$this->assertSame(array( 1, 2 ), array_values($result['search_exclude_ids_list']));
		$this->assertSame(array( 'page' ), $result['disable_gutenberg_post_types']);
		$this->assertSame(array( 'post' ), $result['search_post_types_list']);
	}

	public function test_disable_generated_excerpt_clears_auto_excerpt(): void {
		if (! class_exists('WP_Post', false)) {
			eval('class WP_Post { public $ID = 1; }');
		}

		wp_theme_clear_settings_cache();
		$post = new \WP_Post();

		$this->assertTrue(wp_theme_is_auto_excerpt_disabled());
		$this->assertSame('', wp_theme_disable_generated_excerpt('generated text', $post));
	}

	public function test_disable_generated_excerpt_keeps_manual_excerpt(): void {
		if (! class_exists('WP_Post', false)) {
			eval('class WP_Post { public $ID = 1; }');
		}

		Functions\when('has_excerpt')->justReturn(true);
		wp_theme_clear_settings_cache();

		$post = new \WP_Post();
		$this->assertSame('manual', wp_theme_disable_generated_excerpt('manual', $post));
	}
}

<?php
/**
 * Unit tests for search settings REST query helpers.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_apply_search_settings_to_rest_query
 */
final class SearchSettingsTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		$settings = array(
			'search_empty_handling'     => true,
			'search_post_types'         => true,
			'search_post_types_list'    => array( 'post', 'page' ),
			'search_exclude_ids_list'   => array( 10, 20 ),
			'search_exclude_slugs_list' => array(),
		);

		Functions\when('wp_theme_get_settings')->justReturn($settings);
		Functions\when('wp_theme_settings')->justReturn($settings);
		Functions\when('get_posts')->justReturn(array());
		Functions\when('get_post_types')->justReturn(array( 'post', 'page' ));
		Functions\when('is_admin')->justReturn(false);

		$this->requireThemeFile('inc/search-settings.php');
	}

	public function test_empty_search_returns_no_results(): void {
		$request = new class() {
			public function has_param(string $key): bool {
				return 'search' === $key;
			}

			public function get_param(string $key) {
				return '';
			}
		};

		$result = wp_theme_apply_search_settings_to_rest_query(array( 's' => '' ), $request);

		$this->assertSame(array( 0 ), $result['post__in']);
	}

	public function test_search_applies_post_types_and_excludes(): void {
		$request = new class() {
			public function has_param(string $key): bool {
				return 'search' === $key;
			}

			public function get_param(string $key) {
				return 'hello';
			}
		};

		$result = wp_theme_apply_search_settings_to_rest_query(array(), $request);

		$this->assertSame(array( 'post', 'page' ), $result['post_type']);
		$this->assertSame(array( 10, 20 ), $result['post__not_in']);
	}

	public function test_without_search_param_leaves_args(): void {
		$request = new class() {
			public function has_param(string $key): bool {
				return false;
			}

			public function get_param(string $key) {
				return null;
			}
		};

		$args = array( 'post_type' => 'post' );
		$this->assertSame($args, wp_theme_apply_search_settings_to_rest_query($args, $request));
	}
}

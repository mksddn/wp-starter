<?php
/**
 * Unit tests for REST API helper functions.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_decode_html_entities_recursive
 * @covers ::wp_theme_filter_empty_acf_fields
 */
final class ApiHelpersTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when('is_admin')->justReturn(false);
		Functions\when('admin_url')->justReturn('http://example.test/wp-admin/');
		Functions\when('wp_redirect')->justReturn(true);
		Functions\when('register_rest_route')->justReturn(true);
		Functions\when('register_rest_field')->justReturn(true);

		$this->requireThemeFile('inc/api/api.php');
	}

	public function test_decode_html_entities_in_string(): void {
		$this->assertSame('Tom & Jerry', wp_theme_decode_html_entities_recursive('Tom &amp; Jerry'));
	}

	public function test_decode_html_entities_recursive_array(): void {
		$input = array(
			'title' => 'A &amp; B',
			'nested' => array(
				'body' => '&quot;quoted&quot;',
			),
		);

		$expected = array(
			'title' => 'A & B',
			'nested' => array(
				'body' => '"quoted"',
			),
		);

		$this->assertSame($expected, wp_theme_decode_html_entities_recursive($input));
	}

	public function test_filter_empty_acf_fields_removes_false_and_empty_string(): void {
		$input = array(
			'title'   => 'Hello',
			'empty'   => '',
			'missing' => false,
			'zero'    => 0,
			'nested'  => array(
				'ok'  => 'x',
				'bad' => false,
			),
			'all_bad' => array(
				'a' => false,
				'b' => '',
			),
		);

		$result = wp_theme_filter_empty_acf_fields($input);

		$this->assertSame(
			array(
				'title'  => 'Hello',
				'zero'   => 0,
				'nested' => array(
					'ok' => 'x',
				),
			),
			$result
		);
	}

	public function test_filter_empty_acf_fields_passthrough_non_array(): void {
		$this->assertNull(wp_theme_filter_empty_acf_fields(null));
		$this->assertSame('x', wp_theme_filter_empty_acf_fields('x'));
	}
}

<?php
/**
 * Unit tests for schema markup builders.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_get_schema_data
 * @covers ::wp_theme_get_website_schema
 */
final class SchemaMarkupTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when('is_admin')->justReturn(false);
		Functions\when('is_home')->justReturn(true);
		Functions\when('is_front_page')->justReturn(true);
		Functions\when('is_single')->justReturn(false);
		Functions\when('is_page')->justReturn(false);
		Functions\when('is_archive')->justReturn(false);
		Functions\when('get_bloginfo')->alias(
			static function ($show = '') {
				$map = array(
					'name'        => 'Test Site',
					'description' => 'Test description',
					'url'         => 'http://example.test',
					'language'    => 'en-US',
				);

				return $map[ $show ] ?? '';
			}
		);
		Functions\when('home_url')->alias(
			static function ($path = '/') {
				return 'http://example.test' . $path;
			}
		);
		Functions\when('get_locale')->justReturn('en_US');

		$this->requireThemeFile('inc/schema-markup.php');
	}

	public function test_website_schema_on_front_page(): void {
		$schema = wp_theme_get_schema_data();

		$this->assertSame('WebSite', $schema['@type']);
		$this->assertSame('Test Site', $schema['name']);
		$this->assertSame('http://example.test/', $schema['url']);
		$this->assertArrayHasKey('potentialAction', $schema);
	}
}

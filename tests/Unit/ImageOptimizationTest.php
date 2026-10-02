<?php
/**
 * Unit tests for image optimization helpers.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_image_opt_supported_mimes
 * @covers ::wp_theme_get_image_opt_quality_for_mime
 * @covers ::wp_theme_get_image_max_upload_bytes
 * @covers ::wp_theme_filter_intermediate_sizes
 */
final class ImageOptimizationTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when('wp_theme_get_settings')->justReturn(
			array(
				'image_opt_upload_limits'     => true,
				'image_opt_quality'           => true,
				'image_opt_jpeg_quality'      => 85,
				'image_opt_quality_value'     => 80,
				'image_opt_max_file_size'     => 5,
				'image_opt_max_dimension'     => 2560,
				'image_opt_remove_sizes_list' => array( 'thumbnail', 'medium' ),
				'image_opt_priority_loading'  => false,
			)
		);
		Functions\when('is_admin')->justReturn(false);
		Functions\when('__')->returnArg();

		$this->requireThemeFile('inc/image-optimization.php');
	}

	public function test_supported_mimes(): void {
		$mimes = wp_theme_image_opt_supported_mimes();
		$this->assertContains('image/jpeg', $mimes);
		$this->assertContains('image/png', $mimes);
		$this->assertContains('image/webp', $mimes);
		$this->assertNotContains('image/gif', $mimes);
		$this->assertNotContains('image/svg+xml', $mimes);
	}

	public function test_quality_for_jpeg_uses_jpeg_setting(): void {
		$this->assertSame(85, wp_theme_get_image_opt_quality_for_mime('image/jpeg'));
	}

	public function test_quality_for_png_uses_generic_setting(): void {
		$this->assertSame(80, wp_theme_get_image_opt_quality_for_mime('image/png'));
	}

	public function test_max_upload_bytes(): void {
		$this->assertSame(5 * 1024 * 1024, wp_theme_get_image_max_upload_bytes());
	}

	public function test_filter_intermediate_sizes_removes_configured(): void {
		$sizes = array(
			'thumbnail'    => array(),
			'medium'       => array(),
			'large'        => array(),
			'custom-size'  => array(),
		);

		$result = wp_theme_filter_intermediate_sizes($sizes);

		$this->assertArrayNotHasKey('thumbnail', $result);
		$this->assertArrayNotHasKey('medium', $result);
		$this->assertArrayHasKey('large', $result);
		$this->assertArrayHasKey('custom-size', $result);
	}
}

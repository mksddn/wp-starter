<?php
/**
 * Integration tests for SVG sanitizer with real WordPress WP_Error.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_UnitTestCase;

/**
 * SVG upload sanitization against live WP helpers.
 */
final class SvgSupportTest extends WP_UnitTestCase {

	public function test_safe_svg_passes_sanitizer(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="5" height="5"/></svg>';
		$result = wp_theme_sanitize_svg_markup($svg);

		$this->assertFalse(is_wp_error($result));
		$this->assertStringContainsString('<svg', (string) $result);
		$this->assertStringContainsString('<rect', (string) $result);
	}

	public function test_script_svg_is_rejected(): void {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>';
		$result = wp_theme_sanitize_svg_markup($svg);

		$this->assertTrue(is_wp_error($result));
		$this->assertSame('wp_theme_svg_active', $result->get_error_code());
	}

	public function test_upload_prefilter_rejects_dangerous_svg(): void {
		$user_id = self::factory()->user->create(array( 'role' => 'administrator' ));
		wp_set_current_user($user_id);

		$tmp = wp_tempnam('evil.svg');
		file_put_contents(
			$tmp,
			'<svg xmlns="http://www.w3.org/2000/svg"><script>1</script><rect width="1" height="1"/></svg>'
		);

		$file = array(
			'name'     => 'evil.svg',
			'type'     => 'image/svg+xml',
			'tmp_name' => $tmp,
			'error'    => 0,
			'size'     => filesize($tmp),
		);

		$result = wp_theme_sanitize_svg_upload($file);

		$this->assertNotEmpty($result['error']);
		@unlink($tmp);
	}
}

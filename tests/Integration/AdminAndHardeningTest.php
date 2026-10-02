<?php
/**
 * Integration tests for media file size column and security hardening.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_UnitTestCase;

/**
 * Admin column wiring and option-based hardening checks.
 */
final class AdminAndHardeningTest extends WP_UnitTestCase {

	public function test_filesize_column_is_registered(): void {
		$columns = apply_filters('manage_upload_columns', array( 'title' => 'File' ));
		$this->assertArrayHasKey('filesize', $columns);
	}

	public function test_user_registration_check_when_closed(): void {
		update_option('users_can_register', 0);

		$result = wp_theme_security_hardening_check_user_registration();

		$this->assertSame('user_registration', $result['id']);
		$this->assertSame('good', $result['status']);
	}

	public function test_user_registration_check_when_open(): void {
		update_option('users_can_register', 1);
		update_option('default_role', 'subscriber');

		$result = wp_theme_security_hardening_run_check('user_registration');

		$this->assertNotNull($result);
		$this->assertSame('user_registration', $result['id']);
		// Elevated check: critical on production, softened outside it.
		$this->assertContains($result['status'], array( 'critical', 'not_counted' ));
		$this->assertSame('critical', $result['base_status']);
	}
}

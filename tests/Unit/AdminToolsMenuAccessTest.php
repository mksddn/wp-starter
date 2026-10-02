<?php
/**
 * Unit tests for Tools menu access helper.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_user_has_administrator_role
 */
final class AdminToolsMenuAccessTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireThemeFile('inc/admin-tools-menu-access.php');
	}

	public function test_guest_is_not_administrator(): void {
		Functions\when('is_user_logged_in')->justReturn(false);
		$this->assertFalse(wp_theme_user_has_administrator_role());
	}

	public function test_administrator_role(): void {
		Functions\when('is_user_logged_in')->justReturn(true);
		Functions\when('wp_get_current_user')->justReturn(
			(object) array(
				'roles' => array( 'administrator' ),
			)
		);

		$this->assertTrue(wp_theme_user_has_administrator_role());
	}

	public function test_editor_role_is_rejected(): void {
		Functions\when('is_user_logged_in')->justReturn(true);
		Functions\when('wp_get_current_user')->justReturn(
			(object) array(
				'roles' => array( 'editor' ),
			)
		);

		$this->assertFalse(wp_theme_user_has_administrator_role());
	}
}

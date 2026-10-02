<?php
/**
 * Unit tests for Security Hardening check helpers.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_security_hardening_catalog
 * @covers ::wp_theme_security_hardening_check_label
 * @covers ::wp_theme_security_hardening_tab_for_check
 * @covers ::wp_theme_security_hardening_apply_environment
 * @covers ::wp_theme_security_hardening_wp2fa_administrator_required
 * @covers ::wp_theme_security_hardening_wp2fa_role_list
 */
final class SecurityHardeningChecksTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when('wp_get_environment_type')->justReturn('production');
		Functions\when('sanitize_key')->alias(
			static function ($key) {
				return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string) $key) ?? '');
			}
		);

		$this->requireThemeFile('inc/security-hardening/checks.php');
	}

	public function test_catalog_contains_core_checks(): void {
		$catalog = wp_theme_security_hardening_catalog();

		$this->assertArrayHasKey('user_registration', $catalog);
		$this->assertArrayHasKey('debug_mode', $catalog);
		$this->assertSame('wp_theme_security_hardening_check_user_registration', $catalog['user_registration']['callback']);
	}

	public function test_check_label_known_and_unknown(): void {
		$this->assertSame('Open user registration', wp_theme_security_hardening_check_label('user_registration'));
		$this->assertSame('Security Hardening', wp_theme_security_hardening_check_label('missing-check'));
	}

	public function test_tab_for_check(): void {
		$this->assertSame('web-server', wp_theme_security_hardening_tab_for_check('uploads_php'));
		$this->assertSame('wp-config', wp_theme_security_hardening_tab_for_check('debug_mode'));
		$this->assertSame('', wp_theme_security_hardening_tab_for_check('user_registration'));
	}

	public function test_apply_environment_softens_elevated_outside_production(): void {
		Functions\when('wp_get_environment_type')->justReturn('development');

		$item = wp_theme_security_hardening_make_item(
			'user_registration',
			'critical',
			'Open user registration',
			'Registration is open.',
			true
		);

		$result = wp_theme_security_hardening_apply_environment($item);

		$this->assertSame('not_counted', $result['status']);
		$this->assertSame('critical', $result['base_status']);
	}

	public function test_apply_environment_keeps_status_on_production(): void {
		Functions\when('wp_get_environment_type')->justReturn('production');

		$item = wp_theme_security_hardening_make_item(
			'user_registration',
			'critical',
			'Open user registration',
			'Registration is open.',
			true
		);

		$result = wp_theme_security_hardening_apply_environment($item);

		$this->assertSame('critical', $result['status']);
	}

	public function test_wp2fa_administrator_required_policies(): void {
		$this->assertFalse(
			wp_theme_security_hardening_wp2fa_administrator_required(
				array( 'enforcement-policy' => 'do-not-enforce' )
			)
		);

		$this->assertTrue(
			wp_theme_security_hardening_wp2fa_administrator_required(
				array( 'enforcement-policy' => 'all-users' )
			)
		);

		$this->assertFalse(
			wp_theme_security_hardening_wp2fa_administrator_required(
				array(
					'enforcement-policy' => 'all-users',
					'excluded_roles'     => array( 'administrator' ),
				)
			)
		);

		$this->assertTrue(
			wp_theme_security_hardening_wp2fa_administrator_required(
				array(
					'enforcement-policy' => 'certain-roles-only',
					'enforced_roles'     => 'administrator,editor',
				)
			)
		);

		$this->assertNull(
			wp_theme_security_hardening_wp2fa_administrator_required(
				array( 'enforcement-policy' => 'specific-users' )
			)
		);
	}

	public function test_wp2fa_role_list_normalizes_string_and_array(): void {
		$this->assertSame(
			array( 'administrator', 'editor' ),
			wp_theme_security_hardening_wp2fa_role_list('administrator, editor')
		);
		$this->assertSame(
			array( 'administrator' ),
			wp_theme_security_hardening_wp2fa_role_list(array( 'administrator', '' ))
		);
	}
}

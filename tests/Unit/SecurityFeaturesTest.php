<?php
/**
 * Unit tests for security feature helpers.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

use Brain\Monkey\Functions;

/**
 * @covers ::wp_theme_remove_x_pingback
 * @covers ::wp_theme_filter_public_user_endpoints_security
 * @covers ::wp_theme_get_client_ip
 */
final class SecurityFeaturesTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when('wp_theme_get_settings')->justReturn(
			array(
				'security_xmlrpc'              => true,
				'security_x_pingback'          => true,
				'security_hide_version'        => true,
				'security_disable_editing'     => true,
				'security_disable_enumeration' => true,
				'security_headers'             => true,
				'security_directory_browsing'  => true,
				'security_login_limits'        => true,
			)
		);
		Functions\when('is_admin')->justReturn(false);
		Functions\when('is_user_logged_in')->justReturn(false);
		Functions\when('home_url')->justReturn('http://example.test/');

		$this->requireThemeFile('inc/security-features.php');
	}

	public function test_remove_x_pingback(): void {
		$headers = array(
			'Content-Type' => 'text/html',
			'X-Pingback'   => 'http://example.test/xmlrpc.php',
		);

		$result = wp_theme_remove_x_pingback($headers);

		$this->assertArrayNotHasKey('X-Pingback', $result);
		$this->assertSame('text/html', $result['Content-Type']);
	}

	public function test_filter_user_endpoints_for_anonymous(): void {
		$endpoints = array(
			'/wp/v2/posts'                         => array(),
			'/wp/v2/users'                         => array(),
			'/wp/v2/users/(?P<id>[\\d]+)'           => array(),
		);

		$result = wp_theme_filter_public_user_endpoints_security($endpoints);

		$this->assertArrayHasKey('/wp/v2/posts', $result);
		$this->assertArrayNotHasKey('/wp/v2/users', $result);
		$this->assertArrayNotHasKey('/wp/v2/users/(?P<id>[\\d]+)', $result);
	}

	public function test_filter_user_endpoints_keeps_for_logged_in(): void {
		Functions\when('is_user_logged_in')->justReturn(true);

		$endpoints = array(
			'/wp/v2/users' => array(),
		);

		$this->assertSame($endpoints, wp_theme_filter_public_user_endpoints_security($endpoints));
	}

	public function test_get_client_ip_from_remote_addr(): void {
		$_SERVER = array(
			'REMOTE_ADDR' => '203.0.113.10',
		);

		$this->assertSame('203.0.113.10', wp_theme_get_client_ip());
	}

	public function test_get_client_ip_prefers_forwarded_first_hop(): void {
		$_SERVER = array(
			'HTTP_X_FORWARDED_FOR' => '198.51.100.1, 203.0.113.10',
			'REMOTE_ADDR'          => '10.0.0.1',
		);

		$this->assertSame('198.51.100.1', wp_theme_get_client_ip());
	}
}

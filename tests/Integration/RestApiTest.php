<?php
/**
 * Integration tests for headless REST routes and user enumeration.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * REST API behaviour with headless features enabled.
 */
final class RestApiTest extends WP_UnitTestCase {

	public function test_custom_options_routes_registered(): void {
		$routes = rest_get_server()->get_routes();

		$this->assertArrayHasKey('/custom/v1/options', $routes);
		$this->assertArrayHasKey('/custom/v1/options/(?P<slug>[a-zA-Z0-9_-]+)', $routes);
	}

	public function test_enhanced_search_route_registered(): void {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey('/wp/v2/search', $routes);
	}

	public function test_anonymous_users_endpoint_is_removed(): void {
		wp_set_current_user(0);

		$request  = new WP_REST_Request('GET', '/wp/v2/users');
		$response = rest_get_server()->dispatch($request);

		$this->assertSame(404, $response->get_status());
	}

	public function test_authenticated_admin_can_list_users(): void {
		$user_id = self::factory()->user->create(array( 'role' => 'administrator' ));
		wp_set_current_user($user_id);

		$request  = new WP_REST_Request('GET', '/wp/v2/users');
		$response = rest_get_server()->dispatch($request);

		$this->assertSame(200, $response->get_status());
	}
}

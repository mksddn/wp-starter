---
description: Create a WordPress REST API endpoint using register_rest_route
---

Create a REST API endpoint following WordPress REST API standards:

1. **Use register_rest_route()**: Never create custom endpoints outside WordPress REST API
2. **Namespace**: Use theme/plugin prefix (e.g., `wp-theme/v1`)
3. **Security**: Implement permission callbacks
4. **Sanitization**: Sanitize and validate all inputs
5. **Schema**: Define response schema when possible
6. **Named callbacks**: Use named functions for hooks and permission callbacks

**Basic endpoint structure:**
```php
<?php
/**
 * Register REST API endpoint.
 *
 * @package wp-theme
 */
function prefix_register_rest_routes() {
    register_rest_route(
        'wp-theme/v1',
        '/endpoint-name',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'prefix_endpoint_callback',
            'permission_callback' => 'prefix_endpoint_permissions',
            'args'                => array(
                'param_name' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => 'prefix_validate_param_name',
                ),
            ),
        )
    );
}

add_action( 'rest_api_init', 'prefix_register_rest_routes' );

/**
 * Check permissions for endpoint access.
 *
 * @package wp-theme
 * @param WP_REST_Request $request Request object.
 * @return bool
 */
function prefix_endpoint_permissions( $request ) {
    return current_user_can( 'read' );
}

/**
 * Validate endpoint parameter.
 *
 * @package wp-theme
 * @param mixed           $param   Parameter value.
 * @param WP_REST_Request $request Request object.
 * @param string          $key     Parameter key.
 * @return bool
 */
function prefix_validate_param_name( $param, $request, $key ) {
    return is_string( $param );
}

/**
 * REST API endpoint callback.
 *
 * @package wp-theme
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Response object.
 */
function prefix_endpoint_callback( $request ) {
    // Get and sanitize parameters
    $param = $request->get_param( 'param_name' );
    $param = sanitize_text_field( $param );
    
    // Process request
    
    // Return response
    return rest_ensure_response( array(
        'success' => true,
        'data'    => $result,
    ) );
}
```

**Security best practices:**
- Always implement `permission_callback`
- Use `current_user_can()` for user permissions
- Sanitize all inputs with appropriate callbacks
- Validate data types and formats
- Use `rest_ensure_response()`/`WP_Error` for consistent responses

**Common permission callbacks:**
- `__return_true` - Public endpoint
- `is_user_logged_in` - Logged in users only
- `prefix_endpoint_permissions` - Recommended named callback pattern

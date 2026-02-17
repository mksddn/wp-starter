---
description: Create a WordPress REST API endpoint using register_rest_route
---

Create a REST API endpoint following WordPress REST API standards:

1. **Use register_rest_route()**: Never create custom endpoints outside WordPress REST API
2. **Namespace**: Use theme/plugin prefix (e.g., `wp-theme/v1`)
3. **Security**: Implement permission callbacks
4. **Sanitization**: Sanitize and validate all inputs
5. **Schema**: Define response schema when possible

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
            'permission_callback' => '__return_true', // Or custom permission check
            'args'                => array(
                'param_name' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function( $param ) {
                        return is_string( $param );
                    },
                ),
            ),
        )
    );
}

add_action( 'rest_api_init', 'prefix_register_rest_routes' );

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
- Escape outputs in responses

**Common permission callbacks:**
- `__return_true` - Public endpoint
- `is_user_logged_in` - Logged in users only
- `function() { return current_user_can( 'manage_options' ); }` - Admin only

---
name: wordpress-rest-api
description: WordPress REST API development guide covering endpoints, permissions, sanitization, and security. Use when creating REST API endpoints.
---

# WordPress REST API Development

## REST API Basics

WordPress REST API provides JSON endpoints for interacting with WordPress data.

### Base URL Structure

```
/wp-json/{namespace}/v{version}/{route}
```

Example: `/wp-json/wp-theme/v1/posts`

### Registering Endpoints

Use `register_rest_route()` in `rest_api_init` hook:

```php
add_action( 'rest_api_init', 'prefix_register_rest_routes' );

function prefix_register_rest_routes() {
    register_rest_route(
        'namespace/v1',      // Namespace
        '/route',            // Route
        array(               // Options
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'callback_function',
            'permission_callback' => 'prefix_rest_permissions',
        )
    );
}

function prefix_rest_permissions() {
    return current_user_can( 'read' );
}
```

### HTTP Methods

- `WP_REST_Server::READABLE` - GET
- `WP_REST_Server::CREATABLE` - POST
- `WP_REST_Server::EDITABLE` - PUT/PATCH
- `WP_REST_Server::DELETABLE` - DELETE
- `WP_REST_Server::ALLMETHODS` - All methods

### Permission Callbacks

**Public endpoint:**
```php
'permission_callback' => '__return_true',
```

**Logged in users:**
```php
'permission_callback' => 'prefix_can_access_endpoint',
```

**Admin only:**
```php
'permission_callback' => 'prefix_can_manage_options',
```

**Custom capability:**
```php
'permission_callback' => 'prefix_can_edit_posts',
```

```php
function prefix_can_access_endpoint() {
    return is_user_logged_in();
}

function prefix_can_manage_options() {
    return current_user_can( 'manage_options' );
}

function prefix_can_edit_posts() {
    return current_user_can( 'edit_posts' );
}
```

### Request Parameters

Define and validate parameters:

```php
'args' => array(
    'id' => array(
        'required'          => true,
        'sanitize_callback' => 'absint',
        'validate_callback' => 'prefix_validate_positive_numeric',
    ),
    'title' => array(
        'required'          => false,
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => 'prefix_validate_short_string',
    ),
),
```

```php
function prefix_validate_positive_numeric( $param ) {
    return is_numeric( $param ) && $param > 0;
}

function prefix_validate_short_string( $param ) {
    return is_string( $param ) && strlen( $param ) <= 100;
}
```

### Callback Function

```php
function prefix_endpoint_callback( $request ) {
    // Get parameters
    $id = $request->get_param( 'id' );
    
    // Sanitize (even if sanitize_callback is set)
    $id = absint( $id );
    
    // Process request
    $post = get_post( $id );
    
    if ( ! $post ) {
        return new WP_Error(
            'not_found',
            'Post not found',
            array( 'status' => 404 )
        );
    }
    
    // Return response
    return rest_ensure_response( array(
        'success' => true,
        'data'    => array(
            'id'    => $post->ID,
            'title' => get_the_title( $post ),
        ),
    ) );
}
```

### Response Helpers

**Success response:**
```php
return rest_ensure_response( array(
    'success' => true,
    'data'    => $data,
) );
```

**Error response:**
```php
return new WP_Error(
    'error_code',
    'Error message',
    array( 'status' => 400 )
);
```

### Common Sanitize Callbacks

- `sanitize_text_field()` - Text
- `sanitize_email()` - Email
- `sanitize_url()` - URL
- `absint()` - Positive integer
- `intval()` - Integer
- `wp_kses_post()` - HTML content

### Schema Definition

Define response schema for better documentation:

```php
'schema' => array(
    'description' => 'Get post data',
    'type'        => 'object',
    'properties'  => array(
        'id' => array(
            'type'        => 'integer',
            'description' => 'Post ID',
        ),
        'title' => array(
            'type'        => 'string',
            'description' => 'Post title',
        ),
    ),
),
```

### Best Practices

1. **Always implement permission_callback** - Never leave it empty
2. **Public access intentionally** - Use `__return_true` only for truly public routes
3. **Sanitize all inputs** - Use sanitize_callback and validate_callback
4. **Validate data** - Check types, ranges, formats
5. **Return proper errors** - Use WP_Error with appropriate status codes
6. **Use route namespace** - Prefix routes with theme/plugin slug
7. **Version endpoints** - Use version numbers (v1, v2)
8. **Document endpoints** - Add schema and descriptions

### Testing Endpoints

**cURL example:**
```bash
curl http://localhost:8000/wp-json/wp-theme/v1/endpoint
```

**JavaScript example:**
```javascript
fetch('/wp-json/wp-theme/v1/endpoint')
    .then(response => response.json())
    .then(data => console.log(data));
```

### Common Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

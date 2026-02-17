---
description: Create WordPress AJAX handler with proper security and structure
---

Create a WordPress AJAX handler following security best practices:

## AJAX Handler Structure

### 1. Enqueue Script with Localization

```php
<?php
/**
 * Enqueue AJAX script with localized data.
 *
 * @package wp-theme
 */
function prefix_enqueue_ajax_script() {
    wp_enqueue_script(
        'ajax-handler',
        get_template_directory_uri() . '/js/ajax-handler.js',
        array( 'jquery' ),
        wp_get_theme()->get( 'Version' ),
        true
    );
    
    wp_localize_script(
        'ajax-handler',
        'ajaxData',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ajax-nonce' ),
            'action'  => 'prefix_ajax_action',
        )
    );
}

add_action( 'wp_enqueue_scripts', 'prefix_enqueue_ajax_script' );
```

### 2. AJAX Handler (Logged-in Users)

```php
<?php
/**
 * AJAX handler for logged-in users.
 *
 * @package wp-theme
 */
function prefix_ajax_handler_logged_in() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
        return;
    }
    
    // Check user is logged in
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'Authentication required' ) );
        return;
    }
    
    // Get and sanitize data
    $data = isset( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '';
    
    // Process request
    $result = array(
        'success' => true,
        'data'    => $data,
    );
    
    // Send response
    wp_send_json_success( $result );
}

add_action( 'wp_ajax_prefix_ajax_action', 'prefix_ajax_handler_logged_in' );
```

### 3. AJAX Handler (Public)

```php
<?php
/**
 * AJAX handler for public (non-logged-in) users.
 *
 * @package wp-theme
 */
function prefix_ajax_handler_public() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
        wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
        return;
    }
    
    // Get and sanitize data
    $data = isset( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '';
    
    // Process request
    $result = array(
        'success' => true,
        'data'    => $data,
    );
    
    // Send response
    wp_send_json_success( $result );
}

add_action( 'wp_ajax_nopriv_prefix_ajax_action', 'prefix_ajax_handler_public' );
```

### 4. JavaScript Example

```javascript
jQuery(document).ready(function($) {
    $('#button').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxData.ajaxUrl,
            type: 'POST',
            data: {
                action: ajaxData.action,
                nonce: ajaxData.nonce,
                data: 'some data'
            },
            success: function(response) {
                if (response.success) {
                    console.log(response.data);
                } else {
                    console.error(response.data);
                }
            },
            error: function() {
                console.error('AJAX request failed');
            }
        });
    });
});
```

## Security Checklist

- [ ] Nonce created and passed to JavaScript
- [ ] Nonce verified in handler
- [ ] User permissions checked (if needed)
- [ ] All inputs sanitized
- [ ] Proper error handling
- [ ] Use `wp_send_json_success()` or `wp_send_json_error()`

## Important Notes

1. **Two hooks needed**: `wp_ajax_{action}` for logged-in, `wp_ajax_nopriv_{action}` for public
2. **Always verify nonce** - Never skip nonce verification
3. **Sanitize inputs** - All POST/GET data must be sanitized
4. **Use wp_send_json_*** - Don't use `echo json_encode()`
5. **Check capabilities** - If action requires specific permissions

---
name: wordpress-security
description: WordPress security best practices including input sanitization, output escaping, SQL injection prevention, XSS protection, and REST API security. Use when writing secure WordPress code.
---

# WordPress Security Best Practices

## Core Security Principles

### 1. Input Sanitization
Always sanitize user inputs before processing:
- `sanitize_text_field()` - Text inputs
- `sanitize_email()` - Email addresses
- `sanitize_url()` - URLs
- `sanitize_textarea_field()` - Textarea content
- `wp_kses_post()` - HTML content (allows safe HTML)
- `wp_kses()` - Custom HTML whitelist
- `intval()` / `absint()` - Integers
- `floatval()` - Floats
- `sanitize_file_name()` - File names

### 2. Output Escaping
Always escape output before displaying:
- `esc_html()` - HTML content
- `esc_attr()` - HTML attributes
- `esc_url()` - URLs
- `esc_js()` - JavaScript strings
- `esc_sql()` - SQL queries (use $wpdb->prepare() instead)
- `esc_textarea()` - Textarea content
- `wp_kses_post()` - Safe HTML output

### 3. Database Queries
- **Never use direct SQL** - Use WordPress functions or $wpdb->prepare()
- Use `$wpdb->prepare()` for all queries with variables
- Use WordPress functions: `get_post()`, `get_user()`, `get_option()`, etc.

**Example:**
```php
// ❌ WRONG
$wpdb->query( "SELECT * FROM {$wpdb->posts} WHERE ID = $post_id" );

// ✅ CORRECT
$wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE ID = %d",
    $post_id
) );
```

### 4. Nonces
Use nonces for all forms and AJAX requests:
- `wp_create_nonce( 'action-name' )` - Create nonce
- `wp_verify_nonce( $_POST['nonce'], 'action-name' )` - Verify nonce
- Always check nonces in AJAX callbacks

### 5. Capability Checks
Check user capabilities before allowing actions:
- `current_user_can( 'capability' )` - Check capability
- `is_user_logged_in()` - Check if user is logged in
- `current_user_can( 'manage_options' )` - Admin check

### 6. File Uploads
- Use `wp_handle_upload()` for file uploads
- Validate file types with `wp_check_filetype()`
- Check file size limits
- Store uploads in wp-content/uploads/

### 7. Direct File Access Prevention
Always include ABSPATH check:
```php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

### 8. REST API Security
- Always implement `permission_callback` in REST endpoints
- Sanitize and validate all request parameters
- Use appropriate permission checks

### 9. XSS Prevention
- Never echo user input without escaping
- Use `esc_html()`, `esc_attr()`, etc.
- For HTML content, use `wp_kses_post()` or `wp_kses()`

### 10. SQL Injection Prevention
- Never concatenate user input into SQL queries
- Always use `$wpdb->prepare()`
- Prefer WordPress functions over raw SQL

## Security Checklist

When writing code, ensure:
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] Database queries use $wpdb->prepare()
- [ ] Forms include nonces
- [ ] AJAX requests verify nonces
- [ ] Capability checks are in place
- [ ] File access includes ABSPATH check
- [ ] REST endpoints have permission callbacks

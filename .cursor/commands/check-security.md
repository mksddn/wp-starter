---
description: Security audit checklist for WordPress code
---

Run a security audit on WordPress code following this checklist:

## Security Audit Checklist

### 1. Input Sanitization
- [ ] All `$_GET`, `$_POST`, `$_REQUEST` values are sanitized
- [ ] File uploads use `wp_handle_upload()` and validation
- [ ] REST API parameters have `sanitize_callback`
- [ ] AJAX data is sanitized before use

### 2. Output Escaping
- [ ] All `echo`/`print` statements escape output
- [ ] HTML content uses `esc_html()` or `wp_kses_post()`
- [ ] Attributes use `esc_attr()`
- [ ] URLs use `esc_url()`
- [ ] JavaScript strings use `esc_js()`

### 3. Database Security
- [ ] No direct SQL queries (use WordPress functions)
- [ ] All `$wpdb` queries use `$wpdb->prepare()`
- [ ] No string concatenation in SQL
- [ ] Table names use `$wpdb->prefix`

### 4. Authentication & Authorization
- [ ] Forms include nonces (`wp_create_nonce()`)
- [ ] Nonces verified (`wp_verify_nonce()`)
- [ ] AJAX requests verify nonces
- [ ] Capability checks before sensitive operations
- [ ] REST endpoints have `permission_callback`

### 5. File Security
- [ ] ABSPATH check present (`if ( ! defined( 'ABSPATH' ) ) exit;`)
- [ ] File paths sanitized (`sanitize_file_name()`)
- [ ] File types validated
- [ ] Uploads stored in `wp-content/uploads/`

### 6. XSS Prevention
- [ ] No unescaped user input in HTML
- [ ] No unescaped user input in JavaScript
- [ ] No unescaped user input in attributes
- [ ] HTML content filtered with `wp_kses_post()` or `wp_kses()`

### 7. CSRF Protection
- [ ] All forms have nonces
- [ ] All AJAX requests include nonces
- [ ] Nonces verified before processing

### 8. REST API Security
- [ ] All endpoints have `permission_callback`
- [ ] Request parameters sanitized
- [ ] Response data escaped
- [ ] Proper error handling

## Common Vulnerabilities

Check for these common issues:
1. **SQL Injection** - Direct SQL with user input
2. **XSS** - Unescaped output
3. **CSRF** - Missing nonces
4. **Privilege Escalation** - Missing capability checks
5. **Path Traversal** - Unsanitized file paths
6. **Unsafe Deserialization** - `unserialize()` on user input

## Review Process

1. Scan code for user input points
2. Verify sanitization at input
3. Verify escaping at output
4. Check database queries
5. Verify authentication/authorization
6. Check file operations
7. Review REST API endpoints

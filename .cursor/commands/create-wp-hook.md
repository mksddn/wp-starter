---
description: Create a WordPress hook (add_action or add_filter) with proper naming and structure
---

Create a WordPress hook following these rules:

1. **Always use named functions** - Never use anonymous functions for hooks
2. **Function naming**: Hook callback function should have descriptive name with prefix
3. **Hook priority**: Default priority is 10, adjust if needed
4. **Hook arguments**: Check WordPress documentation for number of arguments

**Action hook structure:**
```php
<?php
/**
 * Callback function description.
 *
 * @package wp-theme
 */
function prefix_action_callback() {
    // Action logic
}

add_action( 'hook_name', 'prefix_action_callback', 10 );
```

**Filter hook structure:**
```php
<?php
/**
 * Filter callback description.
 *
 * @package wp-theme
 * @param mixed $value Value to filter.
 * @param mixed $arg1 Additional argument.
 * @return mixed Filtered value.
 */
function prefix_filter_callback( $value, $arg1 = '' ) {
    // Sanitize/validate input
    $value = sanitize_text_field( $value );
    
    // Modify value
    
    return $value;
}

add_filter( 'hook_name', 'prefix_filter_callback', 10, 2 );
```

**Important:**
- Check existing hooks in the file to determine prefix
- For filters, always return the modified value
- Use appropriate sanitization functions (sanitize_text_field, sanitize_email, wp_kses_post, etc.)
- Document all parameters and return values in PHPDoc

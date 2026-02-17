---
description: Create a new WordPress function with proper prefix and WordPress coding standards
---

Create a new WordPress function following these rules:

1. **Function naming**: Use unique prefix (e.g., `wp_theme_`, `child_theme_`) based on theme/plugin context
2. **Code style**: Follow WordPress Coding Standards (https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
3. **Procedural code only**: No classes, namespaces, or OOP patterns
4. **Security**: Always sanitize inputs and escape outputs
5. **Documentation**: Add PHPDoc comments with @package tag

**Function structure:**
```php
<?php
/**
 * Brief description of what the function does.
 *
 * @package wp-theme
 * @param type $param_name Description.
 * @return type Description.
 */
function prefix_function_name( $param_name ) {
    // Sanitize input
    $param_name = sanitize_text_field( $param_name );
    
    // Function logic
    
    // Escape output if returning HTML
    return esc_html( $result );
}
```

**When creating the function:**
- Check existing functions in the file to determine the correct prefix
- Place in appropriate file (functions.php or inc/*.php)
- Add proper type hints if applicable
- Use WordPress functions instead of PHP native when available
- Never use anonymous functions for hooks - always named functions

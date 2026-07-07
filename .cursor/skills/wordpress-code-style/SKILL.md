---
name: wordpress-code-style
description: WordPress Coding Standards reference covering PHP code style, naming conventions, formatting, and documentation. Use when writing WordPress code.
---

# WordPress Code Style Standards

## PHP Coding Standards

Follow WordPress PHP Coding Standards: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

### Key Rules

1. **Project architecture** - This project is procedural-first (functions/hooks), unless explicitly requested otherwise
2. **WordPress compatibility** - Follow WordPress APIs and coding standards
3. **Indentation** - Use tabs (not spaces) in PHP
4. **Line length** - Keep under 80 characters when possible
5. **Naming** - Use lowercase with underscores (snake_case) for functions/variables
6. **Arrays** - Use `array()` syntax to match WPCS expectations

### Function Naming

```php
// ✅ CORRECT
function wp_theme_function_name() {
}

// ❌ WRONG
function wpThemeFunctionName() {
}
function WP_Theme_Function() {
}
```

### Variable Naming

```php
// ✅ CORRECT
$variable_name = 'value';
$post_id = 123;

// ❌ WRONG
$variableName = 'value';
$PostId = 123;
```

### Constants

```php
// ✅ CORRECT
define( 'WP_THEME_VERSION', '1.0.0' );

// ❌ WRONG
define( 'wp_theme_version', '1.0.0' );
```

### Indentation

- Use tabs for indentation
- Align array keys when appropriate
- Indent continuation lines

```php
// ✅ CORRECT
$array = array(
    'key1' => 'value1',
    'key2' => 'value2',
);

// ❌ WRONG
$array = array(
  'key1' => 'value1',
  'key2' => 'value2',
);
```

### Spacing

- One space after control structures
- No space after function names
- One space around operators

```php
// ✅ CORRECT
if ( $condition ) {
    do_something();
}

// ❌ WRONG
if($condition){
    do_something();
}
```

### Arrays

- Use `array()` syntax (not `[]`)
- Trailing comma in multi-line arrays
- Align keys when appropriate

```php
// ✅ CORRECT
$array = array(
    'key1' => 'value1',
    'key2' => 'value2',
);

// ❌ WRONG
$array = [
    'key1' => 'value1',
    'key2' => 'value2'
];
```

### PHPDoc Comments

Always document functions:

```php
/**
 * Brief description of function.
 *
 * Longer description if needed.
 *
 * @package wp-theme
 * @since 1.0.0
 * @param string $param_name Description.
 * @param int    $id         Post ID.
 * @return string|false Description.
 */
function prefix_function_name( $param_name, $id = 0 ) {
    // Function body
}
```

### File Headers

Theme files should include header:

```php
<?php
/**
 * Template Name: Page Template
 * Description: Template description.
 *
 * @package wp-theme
 */
```

### Security Checks

Always include ABSPATH check:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

### Best Practices

1. **Use WordPress functions** - Prefer WP functions over PHP native
2. **Check function existence** - Use `function_exists()` for optional functions
3. **Type declarations** - Allowed when project compatibility and style permit
4. **Closures** - Allowed for local callbacks, but avoid for hooks that may need removal
5. **Early returns** - Use early returns to reduce nesting
6. **No closing PHP tag** - Omit `?>` at end of PHP-only files

### Code Organization

- Group related functions together
- Use descriptive function names
- Keep functions focused (single responsibility)
- Use includes for large files (`require_once`)

### Linting

Project uses PHPCS with WordPress standards:
- Run: `composer lint`
- Auto-fix: `composer fix`

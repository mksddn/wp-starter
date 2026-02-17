---
name: wordpress-hooks
description: WordPress hooks and filters guide covering actions, filters, priorities, and best practices. Use when working with WordPress hooks system.
---

# WordPress Hooks & Filters

## Understanding Hooks

WordPress uses two types of hooks:
- **Actions** (`add_action`) - Execute code at specific points
- **Filters** (`add_filter`) - Modify data before it's used

## Action Hooks

Actions allow you to execute code at specific points in WordPress execution.

**Structure:**
```php
add_action( 'hook_name', 'callback_function', priority, accepted_args );
```

**Common Action Hooks:**
- `init` - After WordPress loads
- `wp_enqueue_scripts` - Enqueue scripts/styles
- `admin_enqueue_scripts` - Enqueue admin assets
- `after_setup_theme` - After theme setup
- `rest_api_init` - REST API initialization
- `save_post` - When post is saved
- `wp_footer` - In footer
- `wp_head` - In head

**Example:**
```php
function prefix_do_something() {
    // Code to execute
}

add_action( 'init', 'prefix_do_something', 10 );
```

## Filter Hooks

Filters allow you to modify data before it's returned or used.

**Structure:**
```php
add_filter( 'hook_name', 'callback_function', priority, accepted_args );
```

**Important:** Filters MUST return the modified value.

**Common Filter Hooks:**
- `the_content` - Post content
- `the_title` - Post title
- `excerpt_length` - Excerpt length
- `body_class` - Body classes
- `wp_nav_menu_items` - Menu items
- `rest_prepare_post` - REST API post data

**Example:**
```php
function prefix_modify_content( $content ) {
    // Modify $content
    $content .= '<p>Additional content</p>';
    
    // Always return the value
    return $content;
}

add_filter( 'the_content', 'prefix_modify_content', 10 );
```

## Priority

Priority determines execution order (lower = earlier):
- Default: 10
- Higher priority (20, 30) = executes later
- Lower priority (5, 1) = executes earlier

## Accepted Arguments

Specify how many arguments your callback accepts:
```php
add_filter( 'hook_name', 'callback_function', 10, 2 );

function callback_function( $arg1, $arg2 ) {
    // Use both arguments
    return $arg1;
}
```

## Best Practices

1. **Always use named functions** - Never anonymous functions for hooks
2. **Use unique prefixes** - Avoid conflicts with other code
3. **Document hooks** - Add PHPDoc comments
4. **Check hook documentation** - Know what arguments are passed
5. **Return values in filters** - Filters must return modified data
6. **Use appropriate priority** - Consider when your code should run

## Removing Hooks

```php
remove_action( 'hook_name', 'callback_function', priority );
remove_filter( 'hook_name', 'callback_function', priority );
```

## Finding Hooks

- WordPress Codex: https://codex.wordpress.org/Plugin_API/Hooks
- WordPress Developer Reference: https://developer.wordpress.org/reference/hooks/
- Use `grep` to search WordPress core files

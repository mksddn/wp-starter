---
description: Enqueue JavaScript and CSS files using wp_enqueue_scripts
---

Enqueue scripts and styles following WordPress standards:

1. **Use wp_enqueue_scripts hook**: Never hardcode script/style tags
2. **Dependencies**: Declare WordPress dependencies (jquery, etc.)
3. **Versioning**: Use theme version or file modification time
4. **Conditional loading**: Load only where needed (admin/frontend)

**Enqueue styles:**
```php
<?php
/**
 * Enqueue theme styles.
 *
 * @package wp-theme
 */
function prefix_enqueue_styles() {
    wp_enqueue_style(
        'theme-style',
        get_template_directory_uri() . '/css/main.css',
        array(), // Dependencies
        wp_get_theme()->get( 'Version' ) // Version
    );
}

add_action( 'wp_enqueue_scripts', 'prefix_enqueue_styles' );
```

**Enqueue scripts:**
```php
<?php
/**
 * Enqueue theme scripts.
 *
 * @package wp-theme
 */
function prefix_enqueue_scripts() {
    wp_enqueue_script(
        'theme-script',
        get_template_directory_uri() . '/js/main.js',
        array( 'jquery' ), // Dependencies
        wp_get_theme()->get( 'Version' ), // Version
        true // In footer
    );
    
    // Localize script for passing PHP data to JS
    wp_localize_script(
        'theme-script',
        'themeData',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'theme-nonce' ),
        )
    );
}

add_action( 'wp_enqueue_scripts', 'prefix_enqueue_scripts' );
```

**Admin assets:**
```php
add_action( 'admin_enqueue_scripts', 'prefix_admin_assets' );
```

**Important:**
- Always use `get_template_directory_uri()` or `get_stylesheet_directory_uri()`
- For child theme: use `get_stylesheet_directory_uri()`
- Never hardcode URLs
- Use `wp_localize_script()` to pass PHP data to JavaScript securely

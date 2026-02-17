<?php

/**
 * Child theme functions and hooks.
 *
 * @package child-theme
 */


/**
 * Enqueue child theme styles and scripts.
 */
function child_theme_scripts(): void {
    // Get theme version for cache busting
    $theme_version = wp_get_theme( get_stylesheet() )->get('Version');

    // Enqueue child theme stylesheet
    $css_uri = get_stylesheet_directory_uri() . '/css/main.css';
    wp_enqueue_style( 'child-theme-styles', $css_uri, [], $theme_version );

    // Enqueue child theme script
    $js_uri = get_stylesheet_directory_uri() . '/js/main.js';
    wp_enqueue_script( 'child-theme-scripts', $js_uri, [], $theme_version, true );
}


add_action( 'wp_enqueue_scripts', 'child_theme_scripts' );


/**
 * Get hot reload port from environment or use default.
 */
function child_theme_get_hot_reload_port(): string {
    $port = getenv( 'HOT_RELOAD_PORT' );
    return $port ?: '35729';
}


/**
 * Enqueue hot reload client script in development mode.
 */
function child_theme_hot_reload_client(): void {
    if (! defined( 'WP_DEBUG' ) || ! WP_DEBUG) {
        return;
    }

    $client_path = get_stylesheet_directory() . '/js/hot-reload-client.js';
    if (! file_exists( $client_path )) {
        return;
    }

    wp_enqueue_script(
        'child-theme-hot-reload',
        get_stylesheet_directory_uri() . '/js/hot-reload-client.js',
        [],
        filemtime( $client_path ),
        true
    );
}


/**
 * Add data attribute to hot reload script tag.
 */
function child_theme_hot_reload_script_tag( $tag, $handle ): string {
    if ('child-theme-hot-reload' !== $handle) {
        return $tag;
    }

    $port = child_theme_get_hot_reload_port();
    return str_replace( '<script ', '<script data-hot-reload-port="' . esc_attr( $port ) . '" ', $tag );
}


add_action( 'wp_enqueue_scripts', 'child_theme_hot_reload_client' );
add_filter( 'script_loader_tag', 'child_theme_hot_reload_script_tag', 10, 2 );


/**
 * Register new image sizes.
 */
// if (function_exists('add_image_size')) {
//     // 300 width and unlimited height.
//     add_image_size('category-thumb', 300, 9999);
//     // Image cropping.
//     add_image_size('homepage-thumb', 220, 180, true);
// }


/**
 * Register menus.
 */
// register_nav_menus(array(
//     'main_menu' => esc_html__('Main Menu'),
//     'footer_menu' => esc_html__('Footer Menu'),
// ));

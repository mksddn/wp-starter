<?php
/**
 * Основные функции темы и хуки.
 *
 * @package wp-theme
 */


/**
 * Настройки темы.
 */
function theme_setup(): void {
    // add_theme_support('post-formats').
    // add_theme_support('automatic-feed-links').
    add_theme_support( 'post-thumbnails' );
    // add_theme_support('html5', array(
    // 'search-form',
    // 'comment-form',
    // 'comment-list',
    // 'gallery',
    // 'caption',
    // 'style',
    // 'script',
    // )).
    add_theme_support( 'title-tag' );
    add_theme_support( 'custom-logo', [ 'unlink-homepage-logo' => true ] );
}


add_action( 'after_setup_theme', 'theme_setup' );

/**
 * Отключить XML-RPC.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Отключить X-Pingback в хэдере.
 */
add_filter( 'wp_headers', 'disable_x_pingback' );


/**
 * Удаляет X-Pingback из заголовков.
 *
 * @param array $headers Заголовки.
 */
function disable_x_pingback( array $headers ): array {
    unset( $headers['X-Pingback'] );
    return $headers;
}


/**
 * Программно устанавливаем структуру постоянных ссылок (Permalink structure) на Post name.
 */
add_action(
    'after_switch_theme',
    function (): void {
        global $wp_rewrite;
        $desired_structure = '/%postname%/';
        if (get_option( 'permalink_structure' ) !== $desired_structure) {
            update_option( 'permalink_structure', $desired_structure );
            $wp_rewrite->set_permalink_structure( $desired_structure );
            flush_rewrite_rules();
        }
    }
);

/**
 * Визуально разделяем поля повторителя в ACF.
 */
if (class_exists( 'ACF' )) {


    /**
     * Стилизация строк ACF repeater.
     */
    function stylize_acf_repeater_fields(): void {
        echo '<style>
		.acf-repeater tbody .acf-row:nth-child(even)>.acf-row-handle {
		   filter: brightness(0.9);
		}
	</style>';
    }


    add_action( 'admin_head', 'stylize_acf_repeater_fields' );
}

/**
 * Search settings.
 */
require_once get_template_directory() . '/inc/search-settings.php';

/**
 * Theme Settings page and helpers.
 */
require_once get_template_directory() . '/inc/theme-settings.php';

/**
 * ACF Local JSON settings.
 */
require_once get_template_directory() . '/inc/acf-json-settings.php';

/**
 * Регистрируем новые размеры изображений.
 */
// if (function_exists('add_image_size')) {
// 300 в ширину и без ограничения в высоту.
// add_image_size('category-thumb', 300, 9999);
// Кадрирование изображения.
// add_image_size('homepage-thumb', 220, 180, true);
// }

/**
 * Регистрируем меню.
 */
// register_nav_menus(array(
// 'main_menu' => esc_html__('Основное меню'),
// 'footer_menu' => esc_html__('Дополнительное меню'),
// ));

/**
 * Добавляем свои классы в body (иногда нужно, тк верстальщики прописывают стили к кастомным классам).
 */
// function my_plugin_body_class($classes)
// {
// $classes[] = 'body-header-fixed';
// return $classes;
// }
// add_filter('body_class', 'my_plugin_body_class');

/**
 * Добавляем описание (отрывок) к странице.
 */
// function add_excerpt_page()
// {
// add_post_type_support('page', 'excerpt');
// }
// add_action('init', 'add_excerpt_page');

/**
 * Удаляет "Рубрика: ", "Метка: " и т.д. из заголовка архива.
 */
// add_filter('get_the_archive_title', function ($title) {
// return preg_replace('~^[^:]+: ~', '', $title);
// });

/**
 * Удаляет H2 из шаблона пагинации.
 */
// add_filter('navigation_markup_template', 'my_navigation_template', 10, 2);
// function my_navigation_template($template, $class)
// {
// return '
// <nav class="navigation %1$s" role="navigation">
// <div class="nav-links">%3$s</div>
// </nav>
// ';
// }

/**
 * Настройки WooCommerce.
 */
// require_once get_template_directory() . '/inc/woocommerce-settings.php';

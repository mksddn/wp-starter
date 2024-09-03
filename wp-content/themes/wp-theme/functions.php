<?php

/**
 * Настройки темы
 */
function theme_setup()
{
  // add_theme_support('post-formats');
  // add_theme_support('automatic-feed-links');
  add_theme_support('post-thumbnails');
  // add_theme_support('html5', array(
  //   'search-form',
  //   'comment-form',
  //   'comment-list',
  //   'gallery',
  //   'caption',
  //   'style',
  //   'script',
  // ));
  add_theme_support('title-tag');
  add_theme_support('custom-logo', ['unlink-homepage-logo' => true]);
}
add_action('after_setup_theme', 'theme_setup');


/**
 * Регистрируем меню
 */
// register_nav_menus(array(
//   'main_menu' => esc_html__('Основное меню'),
//   // 'footer_menu' => esc_html__('Дополнительное меню'),
// ));


/**
 * Подключаем стили и скрипты
 */
require get_template_directory() . '/inc/styles-n-scripts.php';


/**
 * Настройки WooCommerce
 */
// require get_template_directory() . '/inc/woocommerce-settings.php';


/**
 * Регистрируем новые размеры изображений
 */
// if (function_exists('add_image_size')) {
//   // 300 в ширину и без ограничения в высоту
//   add_image_size('category-thumb', 300, 9999);
//   // Кадрирование изображения
//   add_image_size('homepage-thumb', 220, 180, true);
// }


/**
 * Отключаем создание миниатюр изображений для указанных размеров
 */
add_filter('intermediate_image_sizes', 'delete_intermediate_image_sizes');
function delete_intermediate_image_sizes($sizes)
{
  // размеры которые нужно удалить
  return array_diff($sizes, [
    'thumbnail',
    'medium',
    'medium_large',
    'large',
    '1536x1536',
    '2048x2048',
  ]);
}


/**
 * REST API роуты
 */
require get_template_directory() . '/inc/api/api.php';



/**
 * Миниатюры в списке записей в админке
 */
require get_template_directory() . '/inc/thumbnail-columns.php';


/**
 * Настройки поиска
 */
require get_template_directory() . '/inc/search-settings.php';


/**
 * Добавляем свои классы в body (иногда нужно, тк верстальщики прописывают стили к кастомным классам)
 */
// function my_plugin_body_class($classes)
// {
//   $classes[] = 'body-header-fixed';
//   return $classes;
// }
// add_filter('body_class', 'my_plugin_body_class');


/**
 * Поддержка SVG
 */
require get_template_directory() . '/inc/svg-support.php';


/**
 * Отключаем комментарии
 */
require get_template_directory() . '/inc/disable-comments.php';


/**
 * Mail обработчик
 */
require get_template_directory() . '/inc/mail.php';


/**
 * Список плагинов
 */
require get_template_directory() . '/inc/plugins.php';


/**
 * Добавляем страницу настроек ACF (и в меню тоже)
 */
// if (function_exists('acf_add_options_page')) {
//   $args = array(
//     'page_title' => 'Общие настройки', //Заголовок страницы
//     'menu_title' => 'Общие настройки', //Заголовок в меню
//     'menu_slug' => 'site-options', //Адрес страницы
//     'capability' => 'edit_posts', //Кто может редактировать 
//   );

//   acf_add_options_page($args);
// }


/**
 * Добавляем описание (отрывок) к странице
 */
// function add_excerpt_page()
// {
//   add_post_type_support('page', 'excerpt');
// }
// add_action('init', 'add_excerpt_page');


/**
 * Отключаем редактор Гутенберг
 */
// add_filter('use_block_editor_for_post', '__return_false', 10);


/**
 * Удаляет "Рубрика: ", "Метка: " и т.д. из заголовка архива
 */
// add_filter('get_the_archive_title', function ($title) {
//   return preg_replace('~^[^:]+: ~', '', $title);
// });


/**
 * Удаляет H2 из шаблона пагинации
 */
// add_filter('navigation_markup_template', 'my_navigation_template', 10, 2);
// function my_navigation_template($template, $class)
// {
//   return '
//   <nav class="navigation %1$s" role="navigation">
//   <div class="nav-links">%3$s</div>
//   </nav>    
//   ';
// }


/**
 * Убираем лишние теги в формах CF7
 */ 
// require get_template_directory() . '/inc/wpcf7-form-cleaner.php';

<?php
function wp_starter_scripts()
{
  wp_enqueue_style('wp-styles', get_template_directory_uri() . '/wp-styles.css', array()); // Оставляем, это стандартные стили WP
  wp_enqueue_style('my-styles', get_template_directory_uri() . '/css/index.css', array()); // Подключаем свои стили
  wp_enqueue_script('my-scripts', get_template_directory_uri() . '/js/index.js', array()); // Подключаем свои скрипты
}
add_action('wp_enqueue_scripts', 'wp_starter_scripts');

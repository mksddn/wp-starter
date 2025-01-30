<?php

// // Enable CORS on JSON API Wordpress
// function add_cors_http_header()
// {
//   header("Access-Control-Allow-Origin: *");
// }
// add_action('init', 'add_cors_http_header');


/**
 * Отключение отображения всех страниц на фронтенде
 */
add_action('template_redirect', function () {
  // Разрешаем доступ только к REST API и wp-admin
  if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
    return;
  }
  wp_redirect(home_url());
  exit;
});


/**
 * Отключение эндпоинта /wp/v2/users
 */
add_filter('rest_endpoints', function ($endpoints) {
  // Удаляем эндпоинт для получения пользователей
  if (isset($endpoints['/wp/v2/users'])) {
    unset($endpoints['/wp/v2/users']);
  }
  // Удаляем эндпоинт для получения одного пользователя по ID
  if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
    unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
  }
  return $endpoints;
});


/**
 * Кастомный эндпойнт для получения данных страницы по slug
 */
// require get_template_directory() . '/api/custom-route-page.php';


/**
 * Полное отключение REST API для неавторизованных пользователей
 */
// add_filter('rest_authentication_errors', function ($result) {
//   if (!is_user_logged_in()) {
//     return new WP_Error(
//       'rest_forbidden',
//       __('Вы должны быть авторизованы для доступа к API.', 'text-domain'),
//       ['status' => 401]
//     );
//   }
//   return $result;
// });
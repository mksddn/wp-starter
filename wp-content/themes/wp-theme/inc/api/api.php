<?php
/**
 * Настройки и кастомизация REST API для темы.
 *
 * @package wp-theme
 */

// // Enable CORS on JSON API WordPress
// function add_cors_http_header()
// {
// header("Access-Control-Allow-Origin: *");
// }
// add_action('init', 'add_cors_http_header');


/**
 * Разрешаем только REST API и wp-admin, остальное редиректим на /wp-admin
 */
add_action('template_redirect', function (): void {
    // Разрешаем доступ к админке и REST API
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    wp_redirect(admin_url());
    exit;
});


/**
 * Отключение эндпоинта /wp/v2/users.
 */
add_filter(
    'rest_endpoints',
    function ( $endpoints ) {
        // Удаляем эндпоинт для получения пользователей.
        if (isset( $endpoints['/wp/v2/users'] )) {
            unset( $endpoints['/wp/v2/users'] );
        }

        // Удаляем эндпоинт для получения одного пользователя по ID.
        if (isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] )) {
            unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
        }

        return $endpoints;
    }
);


/**
 * Кастомный эндпойнт для получения данных страницы по slug.
 */
require get_template_directory() . '/inc/api/custom-route-page.php';


/**
 * Кастомный эндпойнт для получения данных Options Page по slug.
 */
require get_template_directory() . '/inc/api/custom-route-options.php';


/**
 * Полное отключение REST API для неавторизованных пользователей.
 */
// add_filter('rest_authentication_errors', function ($result) {
// if (!is_user_logged_in()) {
// return new WP_Error(
// 'rest_forbidden',
// __('Вы должны быть авторизованы для доступа к API.', 'text-domain'),
// ['status' => 401]
// );
// }
// return $result;
// });


/**
 * Этот фильтр автоматически добавляет параметр show_in_rest для новых групп полей.
 */
add_filter( 'acf/register_field_group', 'acf_default_show_in_rest', 10, 1 );


/**
 * Добавляет show_in_rest для новых групп полей ACF.
 *
 * @param array $field_group Группа полей.
 * @return array
 */
function acf_default_show_in_rest( $field_group ) {
    // Если группа полей новая (ещё не сохранена), устанавливаем show_in_rest = 1.
    if (! isset( $field_group['ID'] )) {
        $field_group['show_in_rest'] = 1;
    }

    return $field_group;
}


add_filter( 'acf/prepare_field_group_for_import', 'acf_default_show_in_rest_import' );


/**
 * Добавляет show_in_rest при импорте группы полей ACF.
 *
 * @param array $field_group Группа полей.
 */
function acf_default_show_in_rest_import( array $field_group ): array {
    $field_group['show_in_rest'] = 1;
    return $field_group;
}
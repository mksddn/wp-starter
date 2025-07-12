<?php
/**
 * Кастомные REST API endpoints для Options Pages через ACF.
 *
 * @package wp-theme
 */


/**
 * Функция для получения всех Options Pages через ACF.
 */
function get_all_options_pages() {
    $options_pages = array();

    // Через ACF Options Page API.
    if (function_exists( 'acf_options_page' )) {
        try {
            $acf_pages = acf_options_page()->get_pages();
            if (is_array( $acf_pages ) && ! empty( $acf_pages )) {
                $options_pages = $acf_pages;
            }
        } catch (Exception $e) {
            error_log( 'ACF Options Page API error: ' . $e->getMessage() );
        }
    }

    // Через acf_get_options_pages (альтернативный способ).
    if (empty( $options_pages ) && function_exists( 'acf_get_options_pages' )) {
        $acf_pages = acf_get_options_pages();
        if (is_array( $acf_pages ) && ! empty( $acf_pages )) {
            $options_pages = $acf_pages;
        }
    }

    return $options_pages;
}


/**
 * Функция для форматирования данных Options Page.
 *
 * @param array $page Данные страницы Options Page.
 * @return array
 */
function format_options_page_data( $page ) {
    if (! is_array( $page )) {
        return array();
    }

    return array(
        'menu_slug'  => isset( $page['menu_slug'] ) ? $page['menu_slug'] : '',
        'page_title' => isset( $page['page_title'] ) ? $page['page_title'] : '',
        'menu_title' => isset( $page['menu_title'] ) ? $page['menu_title'] : '',
        'post_id'    => isset( $page['post_id'] ) ? $page['post_id'] : '',
        'data'       => get_fields( isset( $page['post_id'] ) ? $page['post_id'] : '' ) ? get_fields( isset( $page['post_id'] ) ? $page['post_id'] : '' ) : array(),
    );
}


/**
 * Кастомный эндпойнт для получения данных Options Page по слагу.
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'custom/v1',
            '/options/(?P<slug>[a-zA-Z0-9_-]+)',
            array(
                'methods'             => 'GET',
                'callback'            => function ( $data ) {
                    $slug = sanitize_text_field( $data['slug'] );

                    // Получаем все Options Pages.
                    $options_pages = get_all_options_pages();

                    // Ищем Options Page по слагу.
                    $target_page = null;
                    foreach ($options_pages as $page) {
                        if ($page['menu_slug'] === $slug) {
                            $target_page = $page;
                            break;
                        }
                    }

                    // Если страница не найдена.
                    if (! $target_page) {
                        return new WP_Error(
                            'options_page_not_found',
                            'Options Page с указанным слагом не найдена',
                            array( 'status' => 404 )
                        );
                    }

                    // Получаем данные ACF для этой Options Page.
                    $options_data = get_fields( $target_page['post_id'] );

                    // Возвращаем данные без success и data.
                    return $options_data ? $options_data : (object) array();
                },
                'args'                => array(
                    'slug' => array(
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'permission_callback' => function () {
                    return true;
                },
            )
        );
    }
);

/**
 * Эндпойнт для получения всех Options Pages.
 */
add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'custom/v1',
            '/options',
            array(
                'methods'             => 'GET',
                'callback'            => function () {
                    // Получаем все Options Pages.
                    $options_pages = get_all_options_pages();

                    $pages_data = array();
                    foreach ($options_pages as $page) {
                        $pages_data[] = format_options_page_data( $page );
                    }

                    return array(
                        'success' => true,
                        'data'    => $pages_data,
                    );
                },
                'permission_callback' => function () {
                    return true;
                },
            )
        );
    }
);

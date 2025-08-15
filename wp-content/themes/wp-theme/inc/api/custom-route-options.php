<?php
/**
 * Кастомные REST API endpoints для Options Pages через ACF.
 *
 * @package wp-theme
 */


/**
 * Функция для получения всех Options Pages через ACF.
 * @return mixed[]
 */
function get_all_options_pages(): array {
    $options_pages = [];

    // Через ACF Options Page API.
    if (function_exists( 'acf_options_page' )) {
        try {
            $acf_pages = acf_options_page()->get_pages();
            if (is_array( $acf_pages ) && $acf_pages !== []) {
                $options_pages = $acf_pages;
            }
        } catch (Exception $e) {
            error_log( 'ACF Options Page API error: ' . $e->getMessage() );
        }
    }

    // Через acf_get_options_pages (альтернативный способ).
    if ($options_pages === [] && function_exists( 'acf_get_options_pages' )) {
        $acf_pages = acf_get_options_pages();
        if (is_array( $acf_pages ) && $acf_pages !== []) {
            $options_pages = $acf_pages;
        }
    }

    return $options_pages;
}


/**
 * Функция для форматирования данных Options Page.
 *
 * @param array $page Данные страницы Options Page.
 */
function format_options_page_data( $page ): array {
    if (! is_array( $page )) {
        return [];
    }

    return [
        'menu_slug'  => $page['menu_slug'] ?? '',
        'page_title' => $page['page_title'] ?? '',
        'menu_title' => $page['menu_title'] ?? '',
        'post_id'    => $page['post_id'] ?? '',
        'data'       => get_fields( $page['post_id'] ?? '' ) ?: [],
    ];
}


/**
 * Кастомный эндпойнт для получения данных Options Page по слагу.
 */
add_action(
    'rest_api_init',
    function (): void {
        register_rest_route(
            'custom/v1',
            '/options/(?P<slug>[a-zA-Z0-9_-]+)',
            [
                'methods'             => 'GET',
                'callback'            => function ( array $data ) {
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
                            [ 'status' => 404 ]
                        );
                    }

                    // Получаем данные ACF для этой Options Page.
                    $options_data = get_fields( $target_page['post_id'] );

                    // Возвращаем данные без success и data.
                    return $options_data ?: (object) [];
                },
                'args'                => [
                    'slug' => [
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
                'permission_callback' => fn(): true => true,
            ]
        );
    }
);

/**
 * Эндпойнт для получения всех Options Pages.
 */
add_action(
    'rest_api_init',
    function (): void {
        register_rest_route(
            'custom/v1',
            '/options',
            [
                'methods'             => 'GET',
                'callback'            => function (): array {
                    // Получаем все Options Pages.
                    $options_pages = get_all_options_pages();

                    $pages_data = [];
                    foreach ($options_pages as $page) {
                        $pages_data[] = format_options_page_data( $page );
                    }

                    return $pages_data;
                },
                'permission_callback' => fn(): true => true,
            ]
        );
    }
);

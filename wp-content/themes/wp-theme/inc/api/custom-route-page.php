<?php
/**
 * Кастомный REST API endpoint для страниц.
 *
 * @package wp-theme
 */

add_action(
    'rest_api_init',
    function () {
        register_rest_route(
            'custom/v1',
            '/page/(?P<slug>[a-zA-Z0-9_-]+)',
            array(
                'methods'  => 'GET',
                'callback' => function ( $data ) {
                    $pages = get_posts(
                        array(
                            'name'           => $data['slug'],
                            'post_type'      => 'page',
                            'posts_per_page' => 1,
                        )
                    );

                    if (empty( $pages )) {
                        return new WP_Error( 'no_page', 'Page not found', array( 'status' => 404 ) );
                    }

                    $page = $pages[0];

                    return array(
                        'title' => get_the_title( $page ),
                        'acf'   => get_fields( $page->ID ),
                    );
                },
                'args'     => array(
                    'slug' => array(
                        'required' => true,
                    ),
                ),
            )
        );
    }
);

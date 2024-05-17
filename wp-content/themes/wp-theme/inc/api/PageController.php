<?php

/**
 * PageController не работает без ACF
 */
class PageController {
    function __construct() {
        add_action('rest_api_init', [$this, 'init']);
    }

    function init() {
        register_rest_route('my/v1', '/pages', [
            'methods' => 'GET',
            'callback' => [$this, 'index'],
        ]);

        register_rest_route('my/v1', '/pages/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'show'],
        ]);
    }

    function index() {
        $pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]);

        if (empty($pages)) {
            return new WP_REST_Response([
                'message' => 'No pages found',
            ], 404);
        }

        $formatted_pages = [];
        foreach ($pages as $page) {
            $formatted_pages[] = $this->format_page_data($page);
        }

        return new WP_REST_Response($formatted_pages, 200);
    }

    function show($request) {
        $page = get_post($request['id']);

        if (!$page || $page->post_type !== 'page') {
            return new WP_REST_Response(['message' => 'Page not found'], 404);
        }

        $formatted_page = $this->format_page_data($page);

        return new WP_REST_Response($formatted_page, 200);
    }

    private function format_page_data($page) {
        return [
            'id' => $page->ID,
            'title' => get_the_title($page),
            'content' => apply_filters('the_content', $page->post_content),
            'acf' => get_fields($page->ID),
        ];
    }
}

new PageController();

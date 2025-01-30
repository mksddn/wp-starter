<?php

add_action('rest_api_init', function () {
  register_rest_route('custom/v1', '/page/(?P<slug>[a-zA-Z0-9_-]+)', [
    'methods' => 'GET',
    'callback' => function ($data) {
      $pages = get_posts([
        'name' => $data['slug'],
        'post_type' => 'page',
        'posts_per_page' => 1,
      ]);

      if (empty($pages)) {
        return new WP_Error('no_page', 'Page not found', ['status' => 404]);
      }

      $page = $pages[0];

      return [
        'title' => get_the_title($page),
        'acf' => get_fields($page->ID),
      ];
    },
    'args' => [
      'slug' => [
        'required' => true,
      ],
    ],
  ]);
});

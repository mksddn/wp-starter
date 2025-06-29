<?php
/*
Plugin Name: Postman Collection Admin
Description: Generate Postman collection for REST API via button in admin panel.
Version: 2.0
Author: Your Name
*/

add_action('admin_menu', function() {
    add_menu_page(
        'Postman Collection',
        'Postman Collection',
        'manage_options',
        'postman-collection-admin',
        'postman_collection_admin_page',
        'dashicons-share-alt2',
        80
    );
});

function postman_collection_admin_page() {
    // Get all post types
    $post_types = get_post_types(['public' => true], 'objects');
    
    // Get all pages
    $pages = get_posts([
        'post_type' => 'page',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    ]);
    
    // Get all posts
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
    ]);
    
    // Get custom post types (excluding page, post, and attachment)
    $custom_post_types = [];
    foreach ($post_types as $post_type) {
        if (!in_array($post_type->name, ['page', 'post', 'attachment'])) {
            $custom_post_types[$post_type->name] = $post_type;
        }
    }
    
    // Get posts for each custom post type
    $custom_posts = [];
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $custom_posts[$post_type_name] = get_posts([
            'post_type' => $post_type_name,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);
    }
    
    // Get available REST routes to find Options Pages
    if (!class_exists('WP_REST_Server')) {
        require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php';
    }
    $server = rest_get_server();
    $routes = $server->get_routes();
    
    // Get Options Pages from API
    $options_pages = [];
    $options_pages_data = []; // Для хранения полных данных
    
    // Получаем Options Pages через внутренний REST API вызов
    $rest_server = rest_get_server();
    $request = new WP_REST_Request('GET', '/custom/v1/options');
    $response = $rest_server->dispatch($request);
    
    if ($response->get_status() === 200) {
        $options_data = $response->get_data();
        
        if (is_array($options_data) && isset($options_data['success']) && $options_data['success'] && isset($options_data['data'])) {
            foreach ($options_data['data'] as $option) {
                if (isset($option['menu_slug'])) {
                    $options_pages[] = $option['menu_slug'];
                    $options_pages_data[$option['menu_slug']] = [
                        'title' => isset($option['page_title']) ? $option['page_title'] : ucfirst(str_replace('-', ' ', $option['menu_slug'])),
                        'slug' => $option['menu_slug']
                    ];
                }
            }
        }
    }
    
    // Если API не работает, пытаемся получить из роутов (fallback)
    if (empty($options_pages)) {
        foreach ($routes as $route => $handlers) {
            if (strpos($route, '/custom/v1/options') !== false) {
                if (strpos($route, '/custom/v1/options/') === 0) {
                    // Извлекаем параметр из регулярного выражения
                    if (preg_match('/\/custom\/v1\/options\/\(\?P<([^>]+)>[^)]+\)/', $route, $matches)) {
                        $param_name = $matches[1];
                        // Добавляем пример значения для параметра
                        $options_pages[] = 'example-' . $param_name;
                    } else {
                        $page_name = str_replace('/custom/v1/options/', '', $route);
                        if (!empty($page_name)) {
                            // Очищаем от регулярных выражений и параметров
                            $page_name = preg_replace('/\(\?P<[^>]+>[^)]+\)/', '', $page_name);
                            $page_name = preg_replace('/\([^)]+\)/', '', $page_name);
                            $page_name = trim($page_name, '/');
                            
                            // Если это не пустая строка и не содержит специальных символов
                            if (!empty($page_name) && !preg_match('/[{}()\[\]]/', $page_name)) {
                                // Убираем дубликаты
                                if (!in_array($page_name, $options_pages)) {
                                    $options_pages[] = $page_name;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Get previously selected (if any)
    $selected_page_slugs = isset($_POST['custom_page_slugs']) ? (array)$_POST['custom_page_slugs'] : [];
    $selected_post_slugs = isset($_POST['custom_post_slugs']) ? (array)$_POST['custom_post_slugs'] : [];
    $selected_custom_slugs = isset($_POST['custom_post_type_slugs']) ? (array)$_POST['custom_post_type_slugs'] : [];
    $selected_options_pages = isset($_POST['options_pages']) ? (array)$_POST['options_pages'] : [];
    
    echo '<div class="wrap">';
    echo '<h1>Generate Postman Collection</h1>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('generate_postman_collection');
    echo '<input type="hidden" name="action" value="generate_postman_collection">';
    
    // Pages selection
    echo '<h3>Add individual requests for pages:</h3>';
    echo '<div style="margin-bottom: 10px;"><button type="button" class="button" onclick="selectAll(\'custom_page_slugs\')">Select All</button> <button type="button" class="button" onclick="deselectAll(\'custom_page_slugs\')">Deselect All</button></div>';
    echo '<ul style="max-height:200px;overflow:auto;border:1px solid #eee;padding:10px;margin-bottom:20px;">';
    foreach ($pages as $page) {
        $slug = $page->post_name;
        $checked = in_array($slug, $selected_page_slugs) ? 'checked' : '';
        echo '<li><label><input type="checkbox" name="custom_page_slugs[]" value="' . esc_attr($slug) . '" ' . $checked . '> ' . esc_html($page->post_title) . ' <span style="color:#888">(' . esc_html($slug) . ')</span></label></li>';
    }
    echo '</ul>';
    
    echo '<br><button class="button button-primary" name="generate_postman">Generate and download collection</button>';
    echo '</form>';
    
    // JavaScript for select all functionality
    echo '<script>
    function selectAll(name) {
        var checkboxes = document.querySelectorAll("input[name=\'" + name + "[]\']");
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true;
        });
    }
    
    function deselectAll(name) {
        var checkboxes = document.querySelectorAll("input[name=\'" + name + "[]\']");
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
    }
    
    function selectAllCustom(name) {
        var checkboxes = document.querySelectorAll("input[name=\'custom_post_type_slugs[" + name + "][]\']");
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = true;
        });
    }
    
    function deselectAllCustom(name) {
        var checkboxes = document.querySelectorAll("input[name=\'custom_post_type_slugs[" + name + "][]\']");
        checkboxes.forEach(function(checkbox) {
            checkbox.checked = false;
        });
    }
    </script>';
    
    echo '</div>';
}

add_action('admin_post_generate_postman_collection', 'postman_collection_generate_and_download');
function postman_collection_generate_and_download() {
    if (!current_user_can('manage_options') || !check_admin_referer('generate_postman_collection')) {
        wp_die('Недостаточно прав или неверный nonce.');
    }

    $selected_page_slugs = isset($_POST['custom_page_slugs']) ? (array)$_POST['custom_page_slugs'] : [];
    $selected_post_slugs = isset($_POST['custom_post_slugs']) ? (array)$_POST['custom_post_slugs'] : [];
    $selected_custom_slugs = isset($_POST['custom_post_type_slugs']) ? (array)$_POST['custom_post_type_slugs'] : [];
    $selected_options_pages = isset($_POST['options_pages']) ? (array)$_POST['options_pages'] : [];

    // Get all post types
    $post_types = get_post_types(['public' => true], 'objects');
    $custom_post_types = [];
    foreach ($post_types as $post_type) {
        if (!in_array($post_type->name, ['page', 'post', 'attachment'])) {
            $custom_post_types[$post_type->name] = $post_type;
        }
    }

    // Get available REST routes to find Options Pages
    if (!class_exists('WP_REST_Server')) {
        require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php';
    }
    $server = rest_get_server();
    $routes = $server->get_routes();
    
    // Get Options Pages from API
    $options_pages = [];
    $options_pages_data = []; // Для хранения полных данных
    
    // Получаем Options Pages через внутренний REST API вызов
    $rest_server = rest_get_server();
    $request = new WP_REST_Request('GET', '/custom/v1/options');
    $response = $rest_server->dispatch($request);
    
    if ($response->get_status() === 200) {
        $options_data = $response->get_data();
        
        if (is_array($options_data) && isset($options_data['success']) && $options_data['success'] && isset($options_data['data'])) {
            foreach ($options_data['data'] as $option) {
                if (isset($option['menu_slug'])) {
                    $options_pages[] = $option['menu_slug'];
                    $options_pages_data[$option['menu_slug']] = [
                        'title' => isset($option['page_title']) ? $option['page_title'] : ucfirst(str_replace('-', ' ', $option['menu_slug'])),
                        'slug' => $option['menu_slug']
                    ];
                }
            }
        }
    }
    
    // Если API не работает, пытаемся получить из роутов (fallback)
    if (empty($options_pages)) {
        foreach ($routes as $route => $handlers) {
            if (strpos($route, '/custom/v1/options') !== false) {
                if (strpos($route, '/custom/v1/options/') === 0) {
                    // Извлекаем параметр из регулярного выражения
                    if (preg_match('/\/custom\/v1\/options\/\(\?P<([^>]+)>[^)]+\)/', $route, $matches)) {
                        $param_name = $matches[1];
                        // Добавляем пример значения для параметра
                        $options_pages[] = 'example-' . $param_name;
                    } else {
                        $page_name = str_replace('/custom/v1/options/', '', $route);
                        if (!empty($page_name)) {
                            // Очищаем от регулярных выражений и параметров
                            $page_name = preg_replace('/\(\?P<[^>]+>[^)]+\)/', '', $page_name);
                            $page_name = preg_replace('/\([^)]+\)/', '', $page_name);
                            $page_name = trim($page_name, '/');
                            
                            // Если это не пустая строка и не содержит специальных символов
                            if (!empty($page_name) && !preg_match('/[{}()\[\]]/', $page_name)) {
                                // Убираем дубликаты
                                if (!in_array($page_name, $options_pages)) {
                                    $options_pages[] = $page_name;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $items = [];

    // Basic Routes folder
    $basic_routes = [];
    
    // Standard WordPress entities
    $standard_entities = [
        'pages' => 'Page',
        'posts' => 'Post', 
        'categories' => 'Category',
        'tags' => 'Tag',
        'taxonomies' => 'Taxonomy',
        'comments' => 'Comment',
        'users' => 'User',
        'settings' => 'Setting'
    ];

    foreach ($standard_entities as $entity => $singular) {
        $plural = $entity;
        $folder_items = [];
        
        // List
        $folder_items[] = [
            'name' => "List of " . ucfirst($plural),
            'request' => [
                'method' => 'GET',
                'header' => [],
                'url' => [
                    'raw' => (
                        in_array($entity, ['pages', 'posts'])
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity?_fields=id,slug,title"
                        : "{{baseUrl}}/wp-json/wp/v2/$entity"
                    ),
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity],
                    'query' => (
                        in_array($entity, ['pages', 'posts'])
                        ? [ [ 'key' => '_fields', 'value' => 'id,slug,title' ] ]
                        : []
                    )
                ],
                'description' => "Get list of all $plural"
            ]
        ];
        
        // Get by Slug
        $folder_items[] = [
            'name' => "$singular by Slug",
            'request' => [
                'method' => 'GET',
                'header' => [],
                'url' => [
                    'raw' => (
                        in_array($entity, ['pages', 'posts'])
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity?slug=" . ($entity === 'pages' ? 'sample-page' : 'hello-world') . "&acf_format=standard&_fields=title,acf,content"
                        : "{{baseUrl}}/wp-json/wp/v2/$entity?slug=" . ($entity === 'categories' ? 'uncategorized' : 'example')
                    ),
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity],
                    'query' => (
                        in_array($entity, ['pages', 'posts'])
                        ? [
                            [ 'key' => 'slug', 'value' => ($entity === 'pages' ? 'sample-page' : 'hello-world') ],
                            [ 'key' => 'acf_format', 'value' => 'standard' ],
                            [ 'key' => '_fields', 'value' => 'title,acf,content' ],
                        ]
                        : [
                            [ 'key' => 'slug', 'value' => ($entity === 'categories' ? 'uncategorized' : 'example') ]
                        ]
                    )
                ],
                'description' => "Get specific $singular by slug" . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : '')
            ]
        ];
        
        // Get by ID
        $folder_items[] = [
            'name' => "$singular by ID",
            'request' => [
                'method' => 'GET',
                'header' => [],
                'url' => [
                    'raw' => (
                        in_array($entity, ['pages', 'posts'])
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . "ID}}?acf_format=standard&_fields=title,acf,content"
                        : "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . "ID}}"
                    ),
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity, "{{" . $singular . "ID}}"],
                    'query' => (
                        in_array($entity, ['pages', 'posts'])
                        ? [
                            [ 'key' => 'acf_format', 'value' => 'standard' ],
                            [ 'key' => '_fields', 'value' => 'title,acf,content' ],
                        ]
                        : []
                    )
                ],
                'description' => "Get specific $singular by ID" . (in_array($entity, ['pages', 'posts']) ? ' with ACF fields' : '')
            ]
        ];
        
        // Create
        $folder_items[] = [
            'name' => "Create $singular",
            'request' => [
                'method' => 'POST',
                'header' => [ [ 'key' => 'Content-Type', 'value' => 'application/json' ] ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'title' => 'Sample ' . $singular . ' Title',
                        'content' => 'Sample ' . $singular . ' content here.',
                        'excerpt' => 'Sample ' . $singular . ' excerpt.',
                        'status' => 'draft'
                    ], JSON_PRETTY_PRINT)
                ],
                'url' => [
                    'raw' => "{{baseUrl}}/wp-json/wp/v2/$entity",
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity],
                ],
                'description' => "Create new $singular"
            ]
        ];
        
        // Update
        $folder_items[] = [
            'name' => "Update $singular",
            'request' => [
                'method' => 'POST',
                'header' => [ [ 'key' => 'Content-Type', 'value' => 'application/json' ] ],
                'body' => [
                    'mode' => 'raw',
                    'raw' => json_encode([
                        'title' => 'Updated ' . $singular . ' Title',
                        'content' => 'Updated ' . $singular . ' content here.',
                        'excerpt' => 'Updated ' . $singular . ' excerpt.'
                    ], JSON_PRETTY_PRINT)
                ],
                'url' => [
                    'raw' => "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . "ID}}",
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity, "{{" . $singular . "ID}}"],
                ],
                'description' => "Update existing $singular by ID"
            ]
        ];
        
        // Delete
        $folder_items[] = [
            'name' => "Delete $singular",
            'request' => [
                'method' => 'DELETE',
                'header' => [],
                'url' => [
                    'raw' => "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . "ID}}",
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'wp', 'v2', $entity, "{{" . $singular . "ID}}"],
                ],
                'description' => "Delete $singular by ID"
            ]
        ];
        
        $basic_routes[] = [
            'name' => ucfirst($plural),
            'item' => $folder_items
        ];
    }
    
    $items[] = [
        'name' => 'Basic Routes',
        'item' => $basic_routes
    ];

    // Options Pages
    if (!empty($options_pages)) {
        $options_items = [];
        
        // Add List of Options Pages
        $options_items[] = [
            'name' => "List of Options Pages",
            'request' => [
                'method' => 'GET',
                'header' => [],
                'url' => [
                    'raw' => "{{baseUrl}}/wp-json/custom/v1/options",
                    'host' => ['{{baseUrl}}'],
                    'path' => ['wp-json', 'custom', 'v1', 'options'],
                ],
                'description' => "Get list of all available options pages"
            ]
        ];
        
        // Add ALL Options Pages (not just selected ones)
        foreach ($options_pages as $page_slug) {
            $display_name = isset($options_pages_data[$page_slug]['title']) ? $options_pages_data[$page_slug]['title'] : ucfirst(str_replace('-', ' ', $page_slug));
            $is_example = strpos($page_slug, 'example-') === 0;
            
            // Пропускаем example роуты
            if ($is_example) {
                continue;
            }
            
            $options_items[] = [
                'name' => $display_name,
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/custom/v1/options/$page_slug",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'custom', 'v1', 'options', $page_slug],
                    ],
                    'description' => "Get options for $display_name"
                ]
            ];
        }
        
        $items[] = [
            'name' => 'Options Pages',
            'item' => $options_items
        ];
    }

    // Custom post types
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $type_label = isset($post_type_obj->labels->name) ? $post_type_obj->labels->name : ucfirst($post_type_name);
        $singular_label = isset($post_type_obj->labels->singular_name) ? $post_type_obj->labels->singular_name : ucfirst($post_type_name);
        $folder_items = [];
        
        // Special handling for Forms
        if ($post_type_name === 'forms') {
            // List for Forms
            $folder_items[] = [
                'name' => "List of $type_label",
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/$post_type_name?_fields=id,slug,title",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name],
                        'query' => [
                            [ 'key' => '_fields', 'value' => 'id,slug,title' ],
                        ]
                    ],
                    'description' => "Get list of all $type_label"
                ]
            ];
            
            // Add ALL Forms (not just selected ones)
            $forms = get_posts([
                'post_type' => 'forms',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC',
                'post_status' => 'publish',
            ]);
            
            foreach ($forms as $form) {
                $slug = $form->post_name;
                $form_title = $form->post_title;
                
                $folder_items[] = [
                    'name' => $form_title,
                    'item' => [[
                        'name' => "Submit Form - $form_title",
                        'request' => [
                            'method' => 'POST',
                            'header' => [ [ 'key' => 'Content-Type', 'value' => 'application/json' ] ],
                            'body' => [
                                'mode' => 'raw',
                                'raw' => json_encode([
                                    'name' => 'John Doe',
                                    'email' => 'john.doe@example.com',
                                    'message' => 'This is a test message from the form.',
                                    'phone' => '+1234567890',
                                    'subject' => 'Test Subject'
                                ], JSON_PRETTY_PRINT)
                            ],
                            'url' => [
                                'raw' => "{{baseUrl}}/wp-json/wp/v2/forms/$slug/submit",
                                'host' => ['{{baseUrl}}'],
                                'path' => ['wp-json', 'wp', 'v2', 'forms', $slug, 'submit']
                            ],
                            'description' => "Submit form data for '$form_title'"
                        ]
                    ]]
                ];
            }
        } else {
            // Standard routes for other custom post types
            $folder_items[] = [
                'name' => "List of $type_label",
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/$post_type_name?_fields=id,slug,title",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name],
                        'query' => [
                            [ 'key' => '_fields', 'value' => 'id,slug,title' ],
                        ]
                    ],
                    'description' => "Get list of all $type_label"
                ]
            ];
            
            $folder_items[] = [
                'name' => "$singular_label by Slug",
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => (
                            in_array($post_type_name, ['pages', 'posts'])
                            ? "{{baseUrl}}/wp-json/wp/v2/$post_type_name?slug=" . ($post_type_name === 'pages' ? 'sample-page' : 'hello-world') . "&acf_format=standard&_fields=title,acf,content"
                            : "{{baseUrl}}/wp-json/wp/v2/$post_type_name?slug=" . ($post_type_name === 'categories' ? 'uncategorized' : 'example')
                        ),
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name],
                        'query' => (
                            in_array($post_type_name, ['pages', 'posts'])
                            ? [
                                [ 'key' => 'slug', 'value' => ($post_type_name === 'pages' ? 'sample-page' : 'hello-world') ],
                                [ 'key' => 'acf_format', 'value' => 'standard' ],
                                [ 'key' => '_fields', 'value' => 'title,acf,content' ],
                            ]
                            : [
                                [ 'key' => 'slug', 'value' => ($post_type_name === 'categories' ? 'uncategorized' : 'example') ]
                            ]
                        )
                    ],
                    'description' => "Get specific $singular_label by slug" . (in_array($post_type_name, ['pages', 'posts']) ? ' with ACF fields' : '')
                ]
            ];
            
            $folder_items[] = [
                'name' => "$singular_label by ID",
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => (
                            in_array($post_type_name, ['pages', 'posts'])
                            ? "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . "ID}}?acf_format=standard&_fields=title,acf,content"
                            : "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . "ID}}"
                        ),
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name, "{{" . $singular_label . "ID}}"],
                        'query' => (
                            in_array($post_type_name, ['pages', 'posts'])
                            ? [
                                [ 'key' => 'acf_format', 'value' => 'standard' ],
                                [ 'key' => '_fields', 'value' => 'title,acf,content' ],
                            ]
                            : []
                        )
                    ],
                    'description' => "Get specific $singular_label by ID" . (in_array($post_type_name, ['pages', 'posts']) ? ' with ACF fields' : '')
                ]
            ];
            
            $folder_items[] = [
                'name' => "Create $singular_label",
                'request' => [
                    'method' => 'POST',
                    'header' => [ [ 'key' => 'Content-Type', 'value' => 'application/json' ] ],
                    'body' => [
                        'mode' => 'raw',
                        'raw' => json_encode([
                            'title' => 'Sample ' . $singular_label . ' Title',
                            'content' => 'Sample ' . $singular_label . ' content here.',
                            'excerpt' => 'Sample ' . $singular_label . ' excerpt.',
                            'status' => 'draft'
                        ], JSON_PRETTY_PRINT)
                    ],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/$post_type_name",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name],
                    ],
                    'description' => "Create new $singular_label"
                ]
            ];
            
            $folder_items[] = [
                'name' => "Update $singular_label",
                'request' => [
                    'method' => 'POST',
                    'header' => [ [ 'key' => 'Content-Type', 'value' => 'application/json' ] ],
                    'body' => [
                        'mode' => 'raw',
                        'raw' => json_encode([
                            'title' => 'Updated ' . $singular_label . ' Title',
                            'content' => 'Updated ' . $singular_label . ' content here.',
                            'excerpt' => 'Updated ' . $singular_label . ' excerpt.'
                        ], JSON_PRETTY_PRINT)
                    ],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . "ID}}",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name, "{{" . $singular_label . "ID}}"],
                    ],
                    'description' => "Update existing $singular_label by ID"
                ]
            ];
            
            $folder_items[] = [
                'name' => "Delete $singular_label",
                'request' => [
                    'method' => 'DELETE',
                    'header' => [],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . "ID}}",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', $post_type_name, "{{" . $singular_label . "ID}}"],
                    ],
                    'description' => "Delete $singular_label by ID"
                ]
            ];
        }
        
        $items[] = [
            'name' => $type_label,
            'item' => $folder_items
        ];
    }

    // Individual selected pages
    foreach ($selected_page_slugs as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        $page_title = $page ? $page->post_title : $slug;
        
        $items[] = [
            'name' => $page_title,
            'item' => [[
                'name' => $page_title,
                'request' => [
                    'method' => 'GET',
                    'header' => [],
                    'url' => [
                        'raw' => "{{baseUrl}}/wp-json/wp/v2/pages?slug=$slug&acf_format=standard&_fields=title,acf,content",
                        'host' => ['{{baseUrl}}'],
                        'path' => ['wp-json', 'wp', 'v2', 'pages'],
                        'query' => [
                            [ 'key' => 'slug', 'value' => $slug ],
                            [ 'key' => 'acf_format', 'value' => 'standard' ],
                            [ 'key' => '_fields', 'value' => 'title,acf,content' ],
                        ]
                    ],
                    'description' => "Get $page_title by slug with ACF fields"
                ]
            ]]
        ];
    }

    // Variables - baseUrl и ID для динамических запросов
    $variables = [
        [ 'key' => 'baseUrl', 'value' => 'http://localhost:8000' ],
        [ 'key' => 'PostID', 'value' => '1' ],
        [ 'key' => 'PageID', 'value' => '2' ],
        [ 'key' => 'CommentID', 'value' => '1' ],
        [ 'key' => 'UserID', 'value' => '1' ],
        [ 'key' => 'CategoryID', 'value' => '1' ],
        [ 'key' => 'TagID', 'value' => '1' ],
        [ 'key' => 'TaxID', 'value' => '1' ]
    ];
    
    // Добавляем переменные для кастомных типов записей
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $singular_label = isset($post_type_obj->labels->singular_name) ? $post_type_obj->labels->singular_name : ucfirst($post_type_name);
        $variables[] = [ 'key' => $singular_label . 'ID', 'value' => '1' ];
    }

    $collection = [
        'info' => [
            'name' => get_bloginfo('name') . ' API',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json'
        ],
        'item' => $items,
        'variable' => $variables
    ];
    
    $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="postman_collection.json"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
} 
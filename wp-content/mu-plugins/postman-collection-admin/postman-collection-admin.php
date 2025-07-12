<?php
/*
Plugin Name: Postman Collection Admin
Description: Generate Postman collection for REST API via button in admin panel.
Version: 1.0
Author: mksddn
*/

add_action(
    'admin_menu',
    function () {
        add_menu_page(
            'Postman Collection',
            'Postman Collection',
            'manage_options',
            'postman-collection-admin',
            'postman_collection_admin_page',
            'dashicons-share-alt2',
            80
        );
    }
);


function postman_collection_admin_page() {
    // Get all post types
    $post_types = get_post_types( array( 'public' => true ), 'objects' );

    // Get all pages
    $pages = get_posts(
        array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        )
    );

    // Get all posts
    $posts = get_posts(
        array(
            'post_type'      => 'post',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        )
    );

    // Get custom post types (excluding page, post, and attachment)
    $custom_post_types = array();
    foreach ($post_types as $post_type) {
        if (! in_array( $post_type->name, array( 'page', 'post', 'attachment' ) )) {
            $custom_post_types[ $post_type->name ] = $post_type;
        }
    }

    // Get posts for each custom post type
    $custom_posts = array();
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $custom_posts[ $post_type_name ] = get_posts(
            array(
                'post_type'      => $post_type_name,
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_status'    => 'publish',
            )
        );
    }

    // Get available REST routes to find Options Pages
    if (! class_exists( 'WP_REST_Server' )) {
        require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php';
    }

    $server = rest_get_server();
    $routes = $server->get_routes();

    // Get Options Pages from API
    $options_pages      = array();
    $options_pages_data = array(); // For storing full data

    // Get Options Pages via internal REST API call
    $rest_server = rest_get_server();
    $request     = new WP_REST_Request( 'GET', '/custom/v1/options' );
    $response    = $rest_server->dispatch( $request );

    if ($response->get_status() === 200) {
        $options_data = $response->get_data();

        if (is_array( $options_data ) && isset( $options_data['success'] ) && $options_data['success'] && isset( $options_data['data'] )) {
            foreach ($options_data['data'] as $option) {
                if (isset( $option['menu_slug'] )) {
                    $options_pages[]                            = $option['menu_slug'];
                    $options_pages_data[ $option['menu_slug'] ] = array(
                        'title' => isset( $option['page_title'] ) ? $option['page_title'] : ucfirst( str_replace( '-', ' ', $option['menu_slug'] ) ),
                        'slug'  => $option['menu_slug'],
                    );
                }
            }
        }
    }

    // If API does not work, try to get from routes (fallback)
    if (empty( $options_pages )) {
        foreach ($routes as $route => $handlers) {
            if (strpos( $route, '/custom/v1/options' ) !== false) {
                if (strpos( $route, '/custom/v1/options/' ) === 0) {
                    // Extract parameter from regex
                    if (preg_match( '/\/custom\/v1\/options\/\(\?P<([^>]+)>[^)]+\)/', $route, $matches )) {
                        $param_name = $matches[1];
                        // Add example value for parameter
                        $options_pages[] = 'example-' . $param_name;
                    } else {
                        $page_name = str_replace( '/custom/v1/options/', '', $route );
                        if (! empty( $page_name )) {
                            // Clean from regex and parameters
                            $page_name = preg_replace( '/\(\?P<[^>]+>[^)]+\)/', '', $page_name );
                            $page_name = preg_replace( '/\([^)]+\)/', '', $page_name );
                            $page_name = trim( $page_name, '/' );

                            // If not empty and does not contain special symbols
                            if (! empty( $page_name ) && ! preg_match( '/[{}()\[\]]/', $page_name )) {
                                // Remove duplicates
                                if (! in_array( $page_name, $options_pages )) {
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
    $selected_page_slugs    = isset( $_POST['custom_page_slugs'] ) ? (array) $_POST['custom_page_slugs'] : array();
    $selected_post_slugs    = isset( $_POST['custom_post_slugs'] ) ? (array) $_POST['custom_post_slugs'] : array();
    $selected_custom_slugs  = isset( $_POST['custom_post_type_slugs'] ) ? (array) $_POST['custom_post_type_slugs'] : array();
    $selected_options_pages = isset( $_POST['options_pages'] ) ? (array) $_POST['options_pages'] : array();

    echo '<div class="wrap">';
    echo '<h1>Generate Postman Collection</h1>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'generate_postman_collection' );
    echo '<input type="hidden" name="action" value="generate_postman_collection">';

    // Pages selection
    echo '<h3>Add individual requests for pages:</h3>';
    echo '<div style="margin-bottom: 10px;"><button type="button" class="button" onclick="selectAll(\'custom_page_slugs\')">Select All</button> <button type="button" class="button" onclick="deselectAll(\'custom_page_slugs\')">Deselect All</button></div>';
    echo '<ul style="max-height:200px;overflow:auto;border:1px solid #eee;padding:10px;margin-bottom:20px;">';
    foreach ($pages as $page) {
        $slug    = $page->post_name;
        $checked = in_array( $slug, $selected_page_slugs ) ? 'checked' : '';
        echo '<li><label><input type="checkbox" name="custom_page_slugs[]" value="' . esc_attr( $slug ) . '" ' . $checked . '> ' . esc_html( $page->post_title ) . ' <span style="color:#888">(' . esc_html( $slug ) . ')</span></label></li>';
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


add_action( 'admin_post_generate_postman_collection', 'postman_collection_generate_and_download' );


function postman_collection_generate_and_download() {
    if (! current_user_can( 'manage_options' ) || ! check_admin_referer( 'generate_postman_collection' )) {
        wp_die( 'Недостаточно прав или неверный nonce.' );
    }

    $selected_page_slugs    = isset( $_POST['custom_page_slugs'] ) ? (array) $_POST['custom_page_slugs'] : array();
    $selected_post_slugs    = isset( $_POST['custom_post_slugs'] ) ? (array) $_POST['custom_post_slugs'] : array();
    $selected_custom_slugs  = isset( $_POST['custom_post_type_slugs'] ) ? (array) $_POST['custom_post_type_slugs'] : array();
    $selected_options_pages = isset( $_POST['options_pages'] ) ? (array) $_POST['options_pages'] : array();

    // Get all post types
    $post_types        = get_post_types( array( 'public' => true ), 'objects' );
    $custom_post_types = array();
    foreach ($post_types as $post_type) {
        if (! in_array( $post_type->name, array( 'page', 'post', 'attachment' ) )) {
            $custom_post_types[ $post_type->name ] = $post_type;
        }
    }

    // Get available REST routes to find Options Pages
    if (! class_exists( 'WP_REST_Server' )) {
        require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-server.php';
    }

    $server = rest_get_server();
    $routes = $server->get_routes();

    // Get Options Pages from API
    $options_pages      = array();
    $options_pages_data = array(); // For storing full data

    // Get Options Pages via internal REST API call
    $rest_server = rest_get_server();
    $request     = new WP_REST_Request( 'GET', '/custom/v1/options' );
    $response    = $rest_server->dispatch( $request );

    if ($response->get_status() === 200) {
        $options_data = $response->get_data();

        if (is_array( $options_data ) && isset( $options_data['success'] ) && $options_data['success'] && isset( $options_data['data'] )) {
            foreach ($options_data['data'] as $option) {
                if (isset( $option['menu_slug'] )) {
                    $options_pages[]                            = $option['menu_slug'];
                    $options_pages_data[ $option['menu_slug'] ] = array(
                        'title' => isset( $option['page_title'] ) ? $option['page_title'] : ucfirst( str_replace( '-', ' ', $option['menu_slug'] ) ),
                        'slug'  => $option['menu_slug'],
                    );
                }
            }
        }
    }

    // If API does not work, try to get from routes (fallback)
    if (empty( $options_pages )) {
        foreach ($routes as $route => $handlers) {
            if (strpos( $route, '/custom/v1/options' ) !== false) {
                if (strpos( $route, '/custom/v1/options/' ) === 0) {
                    // Extract parameter from regex
                    if (preg_match( '/\/custom\/v1\/options\/\(\?P<([^>]+)>[^)]+\)/', $route, $matches )) {
                        $param_name = $matches[1];
                        // Add example value for parameter
                        $options_pages[] = 'example-' . $param_name;
                    } else {
                        $page_name = str_replace( '/custom/v1/options/', '', $route );
                        if (! empty( $page_name )) {
                            // Clean from regex and parameters
                            $page_name = preg_replace( '/\(\?P<[^>]+>[^)]+\)/', '', $page_name );
                            $page_name = preg_replace( '/\([^)]+\)/', '', $page_name );
                            $page_name = trim( $page_name, '/' );

                            // If not empty and does not contain special symbols
                            if (! empty( $page_name ) && ! preg_match( '/[{}()\[\]]/', $page_name )) {
                                // Remove duplicates
                                if (! in_array( $page_name, $options_pages )) {
                                    $options_pages[] = $page_name;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $items = array();

    // Basic Routes folder
    $basic_routes = array();

    // Standard WordPress entities
    $standard_entities = array(
        'pages'      => 'Page',
        'posts'      => 'Post',
        'categories' => 'Category',
        'tags'       => 'Tag',
        'taxonomies' => 'Taxonomy',
        'comments'   => 'Comment',
        'users'      => 'User',
        'settings'   => 'Setting',
    );

    foreach ($standard_entities as $entity => $singular) {
        $plural       = $entity;
        $folder_items = array();

        // List
        $folder_items[] = array(
            'name'    => 'List of ' . ucfirst( $plural ),
            'request' => array(
                'method'      => 'GET',
                'header'      => array(),
                'url'         => array(
                    'raw'   => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity?_fields=id,slug,title"
                        : "{{baseUrl}}/wp-json/wp/v2/$entity"
                    ),
                    'host'  => array( '{{baseUrl}}' ),
                    'path'  => array( 'wp-json', 'wp', 'v2', $entity ),
                    'query' => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? array(
                            array(
                                'key'   => '_fields',
                                'value' => 'id,slug,title',
                            ),
                        )
                        : array()
                    ),
                ),
                'description' => "Get list of all $plural",
            ),
        );

        // Get by Slug
        $folder_items[] = array(
            'name'    => "$singular by Slug",
            'request' => array(
                'method'      => 'GET',
                'header'      => array(),
                'url'         => array(
                    'raw'   => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity?slug=" . ( $entity === 'pages' ? 'sample-page' : 'hello-world' ) . '&acf_format=standard&_fields=title,acf,content'
                        : "{{baseUrl}}/wp-json/wp/v2/$entity?slug=" . ( $entity === 'categories' ? 'uncategorized' : 'example' )
                    ),
                    'host'  => array( '{{baseUrl}}' ),
                    'path'  => array( 'wp-json', 'wp', 'v2', $entity ),
                    'query' => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? array(
                            array(
                                'key'   => 'slug',
                                'value' => ( $entity === 'pages' ? 'sample-page' : 'hello-world' ),
                            ),
                            array(
                                'key'   => 'acf_format',
                                'value' => 'standard',
                            ),
                            array(
                                'key'   => '_fields',
                                'value' => 'title,acf,content',
                            ),
                        )
                        : array(
                            array(
                                'key'   => 'slug',
                                'value' => ( $entity === 'categories' ? 'uncategorized' : 'example' ),
                            ),
                        )
                    ),
                ),
                'description' => "Get specific $singular by slug" . ( in_array( $entity, array( 'pages', 'posts' ) ) ? ' with ACF fields' : '' ),
            ),
        );

        // Get by ID
        $folder_items[] = array(
            'name'    => "$singular by ID",
            'request' => array(
                'method'      => 'GET',
                'header'      => array(),
                'url'         => array(
                    'raw'   => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . 'ID}}?acf_format=standard&_fields=title,acf,content'
                        : "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . 'ID}}'
                    ),
                    'host'  => array( '{{baseUrl}}' ),
                    'path'  => array( 'wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}' ),
                    'query' => (
                        in_array( $entity, array( 'pages', 'posts' ) )
                        ? array(
                            array(
                                'key'   => 'acf_format',
                                'value' => 'standard',
                            ),
                            array(
                                'key'   => '_fields',
                                'value' => 'title,acf,content',
                            ),
                        )
                        : array()
                    ),
                ),
                'description' => "Get specific $singular by ID" . ( in_array( $entity, array( 'pages', 'posts' ) ) ? ' with ACF fields' : '' ),
            ),
        );

        // Create
        $folder_items[] = array(
            'name'    => "Create $singular",
            'request' => array(
                'method'      => 'POST',
                'header'      => array(
                    array(
                        'key'   => 'Content-Type',
                        'value' => 'application/json',
                    ),
                ),
                'body'        => array(
                    'mode' => 'raw',
                    'raw'  => json_encode(
                        array(
                            'title'   => 'Sample ' . $singular . ' Title',
                            'content' => 'Sample ' . $singular . ' content here.',
                            'excerpt' => 'Sample ' . $singular . ' excerpt.',
                            'status'  => 'draft',
                        ),
                        JSON_PRETTY_PRINT
                    ),
                ),
                'url'         => array(
                    'raw'  => "{{baseUrl}}/wp-json/wp/v2/$entity",
                    'host' => array( '{{baseUrl}}' ),
                    'path' => array( 'wp-json', 'wp', 'v2', $entity ),
                ),
                'description' => "Create new $singular",
            ),
        );

        // Update
        $folder_items[] = array(
            'name'    => "Update $singular",
            'request' => array(
                'method'      => 'POST',
                'header'      => array(
                    array(
                        'key'   => 'Content-Type',
                        'value' => 'application/json',
                    ),
                ),
                'body'        => array(
                    'mode' => 'raw',
                    'raw'  => json_encode(
                        array(
                            'title'   => 'Updated ' . $singular . ' Title',
                            'content' => 'Updated ' . $singular . ' content here.',
                            'excerpt' => 'Updated ' . $singular . ' excerpt.',
                        ),
                        JSON_PRETTY_PRINT
                    ),
                ),
                'url'         => array(
                    'raw'  => "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . 'ID}}',
                    'host' => array( '{{baseUrl}}' ),
                    'path' => array( 'wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}' ),
                ),
                'description' => "Update existing $singular by ID",
            ),
        );

        // Delete
        $folder_items[] = array(
            'name'    => "Delete $singular",
            'request' => array(
                'method'      => 'DELETE',
                'header'      => array(),
                'url'         => array(
                    'raw'  => "{{baseUrl}}/wp-json/wp/v2/$entity/{{" . $singular . 'ID}}',
                    'host' => array( '{{baseUrl}}' ),
                    'path' => array( 'wp-json', 'wp', 'v2', $entity, '{{' . $singular . 'ID}}' ),
                ),
                'description' => "Delete $singular by ID",
            ),
        );

        $basic_routes[] = array(
            'name' => ucfirst( $plural ),
            'item' => $folder_items,
        );
    }

    $items[] = array(
        'name' => 'Basic Routes',
        'item' => $basic_routes,
    );

    // Options Pages
    if (! empty( $options_pages )) {
        $options_items = array();

        // Add List of Options Pages
        $options_items[] = array(
            'name'    => 'List of Options Pages',
            'request' => array(
                'method'      => 'GET',
                'header'      => array(),
                'url'         => array(
                    'raw'  => '{{baseUrl}}/wp-json/custom/v1/options',
                    'host' => array( '{{baseUrl}}' ),
                    'path' => array( 'wp-json', 'custom', 'v1', 'options' ),
                ),
                'description' => 'Get list of all available options pages',
            ),
        );

        // Add ALL Options Pages (not just selected ones)
        foreach ($options_pages as $page_slug) {
            $display_name = isset( $options_pages_data[ $page_slug ]['title'] ) ? $options_pages_data[ $page_slug ]['title'] : ucfirst( str_replace( '-', ' ', $page_slug ) );
            $is_example   = strpos( $page_slug, 'example-' ) === 0;

            // Skip example routes
            if ($is_example) {
                continue;
            }

            $options_items[] = array(
                'name'    => $display_name,
                'request' => array(
                    'method'      => 'GET',
                    'header'      => array(),
                    'url'         => array(
                        'raw'  => "{{baseUrl}}/wp-json/custom/v1/options/$page_slug",
                        'host' => array( '{{baseUrl}}' ),
                        'path' => array( 'wp-json', 'custom', 'v1', 'options', $page_slug ),
                    ),
                    'description' => "Get options for $display_name",
                ),
            );
        }

        $items[] = array(
            'name' => 'Options Pages',
            'item' => $options_items,
        );
    }

    // Custom post types
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $type_label     = isset( $post_type_obj->labels->name ) ? $post_type_obj->labels->name : ucfirst( $post_type_name );
        $singular_label = isset( $post_type_obj->labels->singular_name ) ? $post_type_obj->labels->singular_name : ucfirst( $post_type_name );
        $folder_items   = array();

        // Get rest_base for post type (if exists)
        $rest_base = ! empty( $post_type_obj->rest_base ) ? $post_type_obj->rest_base : $post_type_name;

        // Special handling for Forms
        if ($post_type_name === 'forms') {
            // List for Forms
            $folder_items[] = array(
                'name'    => "List of $type_label",
                'request' => array(
                    'method'      => 'GET',
                    'header'      => array(),
                    'url'         => array(
                        'raw'   => "{{baseUrl}}/wp-json/wp/v2/$rest_base?_fields=id,slug,title",
                        'host'  => array( '{{baseUrl}}' ),
                        'path'  => array( 'wp-json', 'wp', 'v2', $rest_base ),
                        'query' => array(
                            array(
                                'key'   => '_fields',
                                'value' => 'id,slug,title',
                            ),
                        ),
                    ),
                    'description' => "Get list of all $type_label",
                ),
            );

            // Add ALL Forms (not just selected ones)
            $forms = get_posts(
                array(
                    'post_type'      => 'forms',
                    'posts_per_page' => -1,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                    'post_status'    => 'publish',
                )
            );

            foreach ($forms as $form) {
                $slug       = $form->post_name;
                $form_title = $form->post_title;

                // Get form fields config
                $fields_config = get_post_meta( $form->ID, '_fields_config', true );
                $fields        = json_decode( $fields_config, true );
                $body_fields   = array();
                if (is_array( $fields )) {
                    foreach ($fields as $field) {
                        $name = $field['name'];
                        $type = isset( $field['type'] ) ? $field['type'] : 'text';
                        // Example values by type
                        switch ($type) {
                            case 'email':
                                $body_fields[ $name ] = 'test@example.com';
                                break;
                            case 'tel':
                                $body_fields[ $name ] = '+1234567890';
                                break;
                            case 'textarea':
                                $body_fields[ $name ] = 'Sample message text.';
                                break;
                            case 'boolean':
                                $body_fields[ $name ] = '1';
                                break;
                            case 'number':
                                $body_fields[ $name ] = '42';
                                break;
                            default:
                                $body_fields[ $name ] = 'Sample text';
                        }
                    }
                }

                $folder_items[] = array(
                    'name' => $form_title,
                    'item' => array(
                        array(
                            'name'    => "Submit Form - $form_title",
                            'request' => array(
                                'method'      => 'POST',
                                'header'      => array(
                                    array(
                                        'key'   => 'Content-Type',
                                        'value' => 'application/json',
                                    ),
                                ),
                                'body'        => array(
                                    'mode' => 'raw',
                                    'raw'  => json_encode( $body_fields, JSON_PRETTY_PRINT ),
                                ),
                                'url'         => array(
                                    'raw'  => "{{baseUrl}}/wp-json/wp/v2/forms/$slug/submit",
                                    'host' => array( '{{baseUrl}}' ),
                                    'path' => array( 'wp-json', 'wp', 'v2', 'forms', $slug, 'submit' ),
                                ),
                                'description' => "Submit form data for '$form_title'",
                            ),
                        ),
                    ),
                );
            }
        } else {
            // Standard routes for other custom post types
            $folder_items[] = array(
                'name'    => "List of $type_label",
                'request' => array(
                    'method'      => 'GET',
                    'header'      => array(),
                    'url'         => array(
                        'raw'   => "{{baseUrl}}/wp-json/wp/v2/$rest_base?_fields=id,slug,title",
                        'host'  => array( '{{baseUrl}}' ),
                        'path'  => array( 'wp-json', 'wp', 'v2', $rest_base ),
                        'query' => array(
                            array(
                                'key'   => '_fields',
                                'value' => 'id,slug,title',
                            ),
                        ),
                    ),
                    'description' => "Get list of all $type_label",
                ),
            );

            $folder_items[] = array(
                'name'    => "$singular_label by Slug",
                'request' => array(
                    'method'      => 'GET',
                    'header'      => array(),
                    'url'         => array(
                        'raw'   => (
                            in_array( $post_type_name, array( 'pages', 'posts' ) )
                            ? "{{baseUrl}}/wp-json/wp/v2/$post_type_name?slug=" . ( $post_type_name === 'pages' ? 'sample-page' : 'hello-world' ) . '&acf_format=standard&_fields=title,acf,content'
                            : "{{baseUrl}}/wp-json/wp/v2/$post_type_name?slug=" . ( $post_type_name === 'categories' ? 'uncategorized' : 'example' )
                        ),
                        'host'  => array( '{{baseUrl}}' ),
                        'path'  => array( 'wp-json', 'wp', 'v2', $post_type_name ),
                        'query' => (
                            in_array( $post_type_name, array( 'pages', 'posts' ) )
                            ? array(
                                array(
                                    'key'   => 'slug',
                                    'value' => ( $post_type_name === 'pages' ? 'sample-page' : 'hello-world' ),
                                ),
                                array(
                                    'key'   => 'acf_format',
                                    'value' => 'standard',
                                ),
                                array(
                                    'key'   => '_fields',
                                    'value' => 'title,acf,content',
                                ),
                            )
                            : array(
                                array(
                                    'key'   => 'slug',
                                    'value' => ( $post_type_name === 'categories' ? 'uncategorized' : 'example' ),
                                ),
                            )
                        ),
                    ),
                    'description' => "Get specific $singular_label by slug" . ( in_array( $post_type_name, array( 'pages', 'posts' ) ) ? ' with ACF fields' : '' ),
                ),
            );

            $folder_items[] = array(
                'name'    => "$singular_label by ID",
                'request' => array(
                    'method'      => 'GET',
                    'header'      => array(),
                    'url'         => array(
                        'raw'   => (
                            in_array( $post_type_name, array( 'pages', 'posts' ) )
                            ? "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . 'ID}}?acf_format=standard&_fields=title,acf,content'
                            : "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . 'ID}}'
                        ),
                        'host'  => array( '{{baseUrl}}' ),
                        'path'  => array( 'wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}' ),
                        'query' => (
                            in_array( $post_type_name, array( 'pages', 'posts' ) )
                            ? array(
                                array(
                                    'key'   => 'acf_format',
                                    'value' => 'standard',
                                ),
                                array(
                                    'key'   => '_fields',
                                    'value' => 'title,acf,content',
                                ),
                            )
                            : array()
                        ),
                    ),
                    'description' => "Get specific $singular_label by ID" . ( in_array( $post_type_name, array( 'pages', 'posts' ) ) ? ' with ACF fields' : '' ),
                ),
            );

            $folder_items[] = array(
                'name'    => "Create $singular_label",
                'request' => array(
                    'method'      => 'POST',
                    'header'      => array(
                        array(
                            'key'   => 'Content-Type',
                            'value' => 'application/json',
                        ),
                    ),
                    'body'        => array(
                        'mode' => 'raw',
                        'raw'  => json_encode(
                            array(
                                'title'   => 'Sample ' . $singular_label . ' Title',
                                'content' => 'Sample ' . $singular_label . ' content here.',
                                'excerpt' => 'Sample ' . $singular_label . ' excerpt.',
                                'status'  => 'draft',
                            ),
                            JSON_PRETTY_PRINT
                        ),
                    ),
                    'url'         => array(
                        'raw'  => "{{baseUrl}}/wp-json/wp/v2/$post_type_name",
                        'host' => array( '{{baseUrl}}' ),
                        'path' => array( 'wp-json', 'wp', 'v2', $post_type_name ),
                    ),
                    'description' => "Create new $singular_label",
                ),
            );

            $folder_items[] = array(
                'name'    => "Update $singular_label",
                'request' => array(
                    'method'      => 'POST',
                    'header'      => array(
                        array(
                            'key'   => 'Content-Type',
                            'value' => 'application/json',
                        ),
                    ),
                    'body'        => array(
                        'mode' => 'raw',
                        'raw'  => json_encode(
                            array(
                                'title'   => 'Updated ' . $singular_label . ' Title',
                                'content' => 'Updated ' . $singular_label . ' content here.',
                                'excerpt' => 'Updated ' . $singular_label . ' excerpt.',
                            ),
                            JSON_PRETTY_PRINT
                        ),
                    ),
                    'url'         => array(
                        'raw'  => "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . 'ID}}',
                        'host' => array( '{{baseUrl}}' ),
                        'path' => array( 'wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}' ),
                    ),
                    'description' => "Update existing $singular_label by ID",
                ),
            );

            $folder_items[] = array(
                'name'    => "Delete $singular_label",
                'request' => array(
                    'method'      => 'DELETE',
                    'header'      => array(),
                    'url'         => array(
                        'raw'  => "{{baseUrl}}/wp-json/wp/v2/$post_type_name/{{" . $singular_label . 'ID}}',
                        'host' => array( '{{baseUrl}}' ),
                        'path' => array( 'wp-json', 'wp', 'v2', $post_type_name, '{{' . $singular_label . 'ID}}' ),
                    ),
                    'description' => "Delete $singular_label by ID",
                ),
            );
        }

        $items[] = array(
            'name' => $type_label,
            'item' => $folder_items,
        );
    }

    // Individual selected pages
    foreach ($selected_page_slugs as $slug) {
        $page       = get_page_by_path( $slug, OBJECT, 'page' );
        $page_title = $page ? $page->post_title : $slug;

        $items[] = array(
            'name' => 'Page: ' . $page_title,
            'item' => array(
                array(
                    'name'    => 'Page: ' . $page_title,
                    'request' => array(
                        'method'      => 'GET',
                        'header'      => array(),
                        'url'         => array(
                            'raw'   => "{{baseUrl}}/wp-json/wp/v2/pages?slug=$slug&acf_format=standard&_fields=title,acf,content",
                            'host'  => array( '{{baseUrl}}' ),
                            'path'  => array( 'wp-json', 'wp', 'v2', 'pages' ),
                            'query' => array(
                                array(
                                    'key'   => 'slug',
                                    'value' => $slug,
                                ),
                                array(
                                    'key'   => 'acf_format',
                                    'value' => 'standard',
                                ),
                                array(
                                    'key'   => '_fields',
                                    'value' => 'title,acf,content',
                                ),
                            ),
                        ),
                        'description' => "Get $page_title by slug with ACF fields",
                    ),
                ),
            ),
        );
    }

    // Variables - baseUrl and IDs for dynamic requests
    $variables = array(
        array(
            'key'   => 'baseUrl',
            'value' => 'http://localhost:8000',
        ),
        array(
            'key'   => 'PostID',
            'value' => '1',
        ),
        array(
            'key'   => 'PageID',
            'value' => '2',
        ),
        array(
            'key'   => 'CommentID',
            'value' => '1',
        ),
        array(
            'key'   => 'UserID',
            'value' => '1',
        ),
        array(
            'key'   => 'CategoryID',
            'value' => '1',
        ),
        array(
            'key'   => 'TagID',
            'value' => '1',
        ),
        array(
            'key'   => 'TaxID',
            'value' => '1',
        ),
    );

    // Add variables for custom post types
    foreach ($custom_post_types as $post_type_name => $post_type_obj) {
        $singular_label = isset( $post_type_obj->labels->singular_name ) ? $post_type_obj->labels->singular_name : ucfirst( $post_type_name );
        $variables[]    = array(
            'key'   => $singular_label . 'ID',
            'value' => '1',
        );
    }

    $collection = array(
        'info'     => array(
            'name'   => get_bloginfo( 'name' ) . ' API',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ),
        'item'     => $items,
        'variable' => $variables,
    );

    $json = json_encode( $collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

    header( 'Content-Type: application/json' );
    header( 'Content-Disposition: attachment; filename="postman_collection.json"' );
    header( 'Content-Length: ' . strlen( $json ) );
    echo $json;
    exit;
}

<?php
/**
 * Plugin Name: Duplicate Post
 * Description: Functionality for duplicating posts of any type with content
 * Version: 1.0.0
 * Author: mksddn
 *
 * This plugin adds the ability to duplicate posts of any type
 * with all content, including ACF fields, taxonomies and attachments.
 */

// Protection against direct access
if (! defined( 'ABSPATH' )) {
    exit;
}

/**
 * Post duplication functionality
 */

// Add "Duplicate" button in admin
function add_duplicate_post_button(): void {
    global $post;

    // Check user permissions
    if (! current_user_can( 'edit_posts' )) {
        return;
    }

    // Get all post types that support editing
    $post_types = get_post_types(
        [
            'public'  => true,
            'show_ui' => true,
        ],
        'objects'
    );

    foreach ($post_types as $post_type) {
        if (post_type_supports( $post_type->name, 'editor' )) {
            add_action( 'post_row_actions', 'duplicate_post_link', 10, 2 );
            add_action( 'page_row_actions', 'duplicate_post_link', 10, 2 );
        }
    }
}


add_action( 'admin_init', 'add_duplicate_post_button' );

// Add "Duplicate" link in posts list
function duplicate_post_link( array $actions, $post ): array {
    if (current_user_can( 'edit_posts' )) {
        $actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'admin.php?action=duplicate_post&post=' . $post->ID ), 'duplicate_post_' . $post->ID ) . '" title="Duplicate this post" rel="permalink">Duplicate</a>';
    }

    return $actions;
}


// Handle duplication
function duplicate_post_action(): void {
    if (! isset( $_GET['action'] ) || $_GET['action'] !== 'duplicate_post') {
        return;
    }

    if (! isset( $_GET['post'] ) || ! isset( $_GET['_wpnonce'] )) {
        wp_die( 'Invalid parameters' );
    }

    $post_id = intval( $_GET['post'] );
    $nonce   = $_GET['_wpnonce'];

    // Verify nonce
    if (! wp_verify_nonce( $nonce, 'duplicate_post_' . $post_id )) {
        wp_die( 'Security error' );
    }

    // Check user permissions
    if (! current_user_can( 'edit_posts' )) {
        wp_die( 'Insufficient permissions' );
    }

    // Get original post
    $original_post = get_post( $post_id );
    if (! $original_post) {
        wp_die( 'Post not found' );
    }

    // Create duplicate
    $duplicate_id = duplicate_post( $original_post );

    if ($duplicate_id) {
        // Redirect to edit new post
        wp_redirect( admin_url( 'post.php?post=' . $duplicate_id . '&action=edit' ) );
        exit;
    }

    wp_die( 'Error creating duplicate' );
}


add_action( 'admin_init', 'duplicate_post_action' );

// Main duplication function
function duplicate_post( $original_post ) {
    // Prepare data for new post
    $post_data = [
        'post_title'            => $original_post->post_title . ' (copy)',
        'post_content'          => $original_post->post_content,
        'post_excerpt'          => $original_post->post_excerpt,
        'post_status'           => 'draft', // Create as draft
        'post_type'             => $original_post->post_type,
        'post_author'           => get_current_user_id(),
        'post_parent'           => $original_post->post_parent,
        'menu_order'            => $original_post->menu_order,
        'comment_status'        => $original_post->comment_status,
        'ping_status'           => $original_post->ping_status,
        'post_password'         => $original_post->post_password,
        'to_ping'               => $original_post->to_ping,
        'pinged'                => $original_post->pinged,
        'post_content_filtered' => $original_post->post_content_filtered,
        'post_mime_type'        => $original_post->post_mime_type,
        'guid'                  => '',
    ];

    // Insert new post
    $duplicate_id = wp_insert_post( $post_data );

    if (is_wp_error( $duplicate_id )) {
        return false;
    }

    // Copy taxonomies
    $taxonomies = get_object_taxonomies( $original_post->post_type );
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms( $original_post->ID, $taxonomy, [ 'fields' => 'slugs' ] );
        wp_set_object_terms( $duplicate_id, $terms, $taxonomy, false );
    }

    // Copy meta fields
    $meta_keys = get_post_custom_keys( $original_post->ID );
    if ($meta_keys) {
        foreach ($meta_keys as $meta_key) {
            $meta_values = get_post_meta( $original_post->ID, $meta_key, false );
            foreach ($meta_values as $meta_value) {
                add_post_meta( $duplicate_id, $meta_key, $meta_value );
            }
        }
    }

    // Copy ACF fields (if ACF is active)
    if (function_exists( 'get_fields' ) && function_exists( 'update_field' )) {
        $acf_fields = get_fields( $original_post->ID );
        if ($acf_fields) {
            foreach ($acf_fields as $field_key => $field_value) {
                update_field( $field_key, $field_value, $duplicate_id );
            }
        }
    }

    // Copy featured image
    if (has_post_thumbnail( $original_post->ID )) {
        $thumbnail_id = get_post_thumbnail_id( $original_post->ID );
        set_post_thumbnail( $duplicate_id, $thumbnail_id );
    }

    // Copy attachments (if it's a page or post)
    if (in_array( $original_post->post_type, [ 'post', 'page' ] )) {
        $attachments = get_posts(
            [
                'post_type'   => 'attachment',
                'post_parent' => $original_post->ID,
                'numberposts' => -1,
                'post_status' => 'any',
            ]
        );

        foreach ($attachments as $attachment) {
            $attachment_data = [
                'post_title'     => $attachment->post_title,
                'post_content'   => $attachment->post_content,
                'post_excerpt'   => $attachment->post_excerpt,
                'post_status'    => $attachment->post_status,
                'post_type'      => 'attachment',
                'post_parent'    => $duplicate_id,
                'menu_order'     => $attachment->menu_order,
                'post_mime_type' => $attachment->post_mime_type,
                'guid'           => '',
            ];

            $new_attachment_id = wp_insert_post( $attachment_data );

            if (! is_wp_error( $new_attachment_id )) {
                // Copy attachment meta data
                $attachment_meta_keys = get_post_custom_keys( $attachment->ID );
                if ($attachment_meta_keys) {
                    foreach ($attachment_meta_keys as $meta_key) {
                        $meta_values = get_post_meta( $attachment->ID, $meta_key, false );
                        foreach ($meta_values as $meta_value) {
                            add_post_meta( $new_attachment_id, $meta_key, $meta_value );
                        }
                    }
                }
            }
        }
    }

    return $duplicate_id;
}


// Add "Duplicate" button in post editor
function add_duplicate_button_to_editor(): void {
    global $post;

    if (! $post || ! current_user_can( 'edit_posts' )) {
        return;
    }

    $duplicate_url = wp_nonce_url( admin_url( 'admin.php?action=duplicate_post&post=' . $post->ID ), 'duplicate_post_' . $post->ID );

    echo '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $(".edit-post-header__settings").append(\'<a href="' . $duplicate_url . '" class="components-button is-secondary" style="margin-left: 10px;">Duplicate</a>\');
        });
    </script>';
}


add_action( 'admin_footer-post.php', 'add_duplicate_button_to_editor' );
add_action( 'admin_footer-post-new.php', 'add_duplicate_button_to_editor' );

// Add notification about successful duplication
function duplicate_post_admin_notice(): void {
    if (isset( $_GET['duplicated'] ) && $_GET['duplicated'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>Post successfully duplicated!</p></div>';
    }
}


add_action( 'admin_notices', 'duplicate_post_admin_notice' );

<?php
/*
Plugin Name: Thumbnail Column for Posts and Pages
Description: Adds a "Thumbnail" column to the admin post and page list views.
Version: 1.0
Author: mksddn
*/

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

// Check if function already exists to avoid conflicts
if (!function_exists('add_thumb_column')) {
  // Enable support for post thumbnails
  add_theme_support('post-thumbnails', ['post', 'page']);

  /**
   * Adds a "Thumbnail" column to the posts and pages admin tables.
   *
   * @param array $columns Existing columns in the admin table.
   * @return array Updated columns with the new "Thumbnail" column.
   */
  function add_thumb_column($columns)
  {
    $columns['thumbnail'] = __('Thumbnail', 'textdomain');
    return $columns;
  }

  /**
   * Outputs the thumbnail for a given post in the "Thumbnail" column.
   *
   * @param string $column_name The name of the column being rendered.
   * @param int $post_id The ID of the current post.
   */
  function add_thumb_value($column_name, $post_id)
  {
    if ($column_name === 'thumbnail') {
      $width = 60; // Thumbnail width
      $height = 60; // Thumbnail height

      // Get the thumbnail ID
      $thumbnail_id = get_post_meta($post_id, '_thumbnail_id', true);

      if ($thumbnail_id) {
        // Display the featured image
        echo wp_get_attachment_image($thumbnail_id, [$width, $height], true);
      } else {
        // Fetch the first image attachment as a fallback
        $attachments = get_children([
          'post_parent' => $post_id,
          'post_type' => 'attachment',
          'post_mime_type' => 'image',
          'numberposts' => 1, // Limit to one image
        ]);

        if ($attachments) {
          // Get the first attachment
          $attachment = reset($attachments);
          echo wp_get_attachment_image($attachment->ID, [$width, $height], true);
        } else {
          // Display "None" if no image is found
          echo __('None', 'textdomain');
        }
      }
    }
  }

  /**
   * Hooks the thumbnail column functions to both posts and pages.
   */
  function register_thumb_column_hooks()
  {
    foreach (['posts', 'pages'] as $screen) {
      add_filter("manage_{$screen}_columns", 'add_thumb_column');
      add_action("manage_{$screen}_custom_column", 'add_thumb_value', 10, 2);
    }
  }
  add_action('admin_init', 'register_thumb_column_hooks');
}

<?php

// /**
//  * Plugin Name: WP Change Logger
//  * Description: Logs changes to posts, pages, custom fields (including ACF fields) in a log file.
//  * Version: 1.0
//  * Author: mksddn
//  */

// if (!defined('ABSPATH')) {
//   exit; // Exit if accessed directly
// }

// /**
//  * Log changes to WordPress content and custom fields.
//  *
//  * @param int $post_ID The ID of the post being saved.
//  * @param WP_Post|null $post_after The post object after the update (optional for save_post hook).
//  * @param WP_Post|null $post_before The post object before the update (optional for post_updated hook).
//  */
// function log_wp_changes_with_meta($post_ID, $post_after = null, $post_before = null)
// {
//   // Path to the log file
//   $log_file = WP_CONTENT_DIR . '/wp_changes.log';

//   // Skip if it's an autosave or revision
//   if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
//   if (wp_is_post_revision($post_ID)) return;

//   $changes = [];

//   // Check for changes in standard fields like title and content
//   if ($post_after && $post_before) {
//     if ($post_after->post_title !== $post_before->post_title) {
//       $changes[] = "Title changed from '{$post_before->post_title}' to '{$post_after->post_title}'";
//     }
//     if ($post_after->post_content !== $post_before->post_content) {
//       $changes[] = "Content changed";
//     }
//   }

//   // Check for changes in ACF fields
//   if (isset($_POST['acf'])) {
//     $acf_fields = get_field_objects($post_ID);
//     if ($acf_fields) {
//       foreach ($acf_fields as $field_key => $field) {
//         $old_value = get_field($field_key, $post_ID, true); // Old value
//         $new_value = isset($_POST['acf'][$field_key]) ? $_POST['acf'][$field_key] : null; // New value

//         if ($old_value !== $new_value) {
//           $changes[] = "ACF field '{$field['name']}' changed from '{$old_value}' to '{$new_value}'";
//         }
//       }
//     }
//   }

//   // Check for changes in other custom fields (non-ACF)
//   $meta_keys = get_post_custom_keys($post_ID);
//   if ($meta_keys) {
//     foreach ($meta_keys as $meta_key) {
//       $old_value = get_post_meta($post_ID, $meta_key, true); // Old value
//       $new_value = isset($_POST[$meta_key]) ? $_POST[$meta_key] : $old_value; // New value

//       if ($old_value !== $new_value) {
//         $changes[] = "Meta field '{$meta_key}' changed from '{$old_value}' to '{$new_value}'";
//       }
//     }
//   }

//   // Log changes if there are any
//   if (!empty($changes)) {
//     $log_message = sprintf(
//       "[%s] Post/Page updated: ID %d, Type: %s, Author: %s\nChanges:\n%s\n",
//       current_time('mysql'),
//       $post_ID,
//       get_post_type($post_ID),
//       wp_get_current_user()->display_name,
//       implode("\n", $changes)
//     );
//     file_put_contents($log_file, $log_message, FILE_APPEND);
//   }
// }

// // Hook into WordPress to track changes
// add_action('post_updated', 'log_wp_changes_with_meta', 10, 3); // Track changes in standard fields
// add_action('save_post', 'log_wp_changes_with_meta', 10, 1);   // Track changes in custom fields

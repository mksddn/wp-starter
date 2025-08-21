<?php
/**
 * Plugin Name: Hard Pages
 * Description: Manage hard-coded pages list via theme JSON file (hard-pages.json).
 * Version: 2.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// Path helpers
function hard_pages_get_theme_dir(): string {
    return get_template_directory();
}


function hard_pages_get_json_path(): string {
    return hard_pages_get_theme_dir() . '/hard-pages.json';
}


// Runtime globals
$GLOBALS['hard_pages'] ??= [];
$GLOBALS['recently_deleted_hard_pages'] ??= [];

// ---- JSON I/O ----
function hard_pages_read_json(): array {
    $path = hard_pages_get_json_path();
    if (! file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if (false === $content || '' === trim($content)) {
        return [];
    }

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}


function hard_pages_write_json(array $pages): bool {
    $path = hard_pages_get_json_path();
    $json = json_encode(array_values($pages), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (false === $json) {
        return false;
    }

    return (bool) file_put_contents($path, $json . "\n");
}


function hard_pages_sync_runtime_from_json(): void {
    $pages = hard_pages_read_json();
    $GLOBALS['hard_pages'] = [];
    foreach ($pages as $page) {
        if (! isset($page['title'], $page['slug'])) {
            continue;
        }

        $title = (string) $page['title'];
        $slug  = (string) $page['slug'];
        $GLOBALS['hard_pages'][$title] = [
            'slug'      => $slug,
            'page_type' => $slug,
        ];
    }
}


// Initialize runtime from JSON
hard_pages_sync_runtime_from_json();

// ---- Admin hooks to keep JSON in sync ----
function hard_pages_add_to_json($page_id, string $title, string $slug): void {
    static $added = [];
    $key = $title . '|' . $slug;
    if (in_array($key, $added, true)) {
        return;
    }

    if ('yes' === get_post_meta($page_id, '_hard_page', true)) {
        return;
    }

    if (str_contains($slug, '__trashed')) {
        return;
    }

    $pages = hard_pages_read_json();
    foreach ($pages as $p) {
        if (isset($p['slug']) && $p['slug'] === $slug) {
            return;
        }

        if (isset($p['title']) && $p['title'] === $title) {
            return;
        }
    }

    $pages[] = [ 'title' => $title, 'slug' => $slug ];
    if (hard_pages_write_json($pages)) {
        $added[] = $key;
        hard_pages_sync_runtime_from_json();
        update_post_meta($page_id, '_hard_page', 'yes');
        update_post_meta($page_id, 'page_type', $slug);
    }
}


function hard_pages_remove_from_json(string $title, string $slug): void {
    static $removed = [];
    $key = $title . '|' . $slug;
    if (in_array($key, $removed, true)) {
        return;
    }

    $clean_slug = preg_replace('/__trashed-\d+$/', '', $slug);
    $pages = hard_pages_read_json();
    $changed = false;
    $result = [];
    foreach ($pages as $p) {
        if ((isset($p['title']) && $p['title'] === $title) || (isset($p['slug']) && $p['slug'] === $clean_slug)) {
            $changed = true;
            continue;
        }

        $result[] = $p;
    }

    if ($changed && hard_pages_write_json($result)) {
        $removed[] = $key;
        $GLOBALS['recently_deleted_hard_pages'][] = $key;
        hard_pages_sync_runtime_from_json();
    }
}


function hard_pages_update_in_json($post_id, string $old_title, string $old_slug, string $new_title, string $new_slug): void {
    if (get_post_meta($post_id, '_hard_page', true) !== 'yes') {
        return;
    }

    $pages = hard_pages_read_json();
    $changed = false;
    foreach ($pages as &$p) {
        if ((isset($p['title']) && $p['title'] === $old_title) && (isset($p['slug']) && $p['slug'] === $old_slug)) {
            $p['title'] = $new_title;
            $p['slug']  = $new_slug;
            $changed = true;
            break;
        }
    }

    if ($changed && hard_pages_write_json($pages)) {
        hard_pages_sync_runtime_from_json();
        update_post_meta($post_id, 'page_type', $new_slug);
    }
}


// Hooks: create/publish page
function hard_pages_on_page_created($post_id, $post, $update): void {
    if ($update) {
        return;
    }

    if ('page' !== $post->post_type) {
        return;
    }

    if ('publish' !== $post->post_status) {
        return;
    }

    hard_pages_add_to_json($post_id, $post->post_title, $post->post_name);
}


add_action('wp_insert_post', 'hard_pages_on_page_created', 5, 3);


function hard_pages_on_status_changed($new_status, $old_status, $post): void {
    if ('publish' === $new_status && 'publish' !== $old_status && 'page' === $post->post_type) {
        hard_pages_add_to_json($post->ID, $post->post_title, $post->post_name);
    }

    if ('trash' === $new_status && 'trash' !== $old_status && 'page' === $post->post_type && 'yes' === get_post_meta($post->ID, '_hard_page', true)) {
        hard_pages_remove_from_json($post->post_title, $post->post_name);
        wp_schedule_single_event(time() + 5, 'hard_pages_force_delete_event', [ $post->ID ]);
    }
}


add_action('transition_post_status', 'hard_pages_on_status_changed', 5, 3);

// Hooks: delete/trash
function hard_pages_on_page_deleted($post_id): void {
    $post = get_post($post_id);
    if (! $post) { return;
    }

    if ('page' !== $post->post_type) { return;
    }

    if ('yes' === get_post_meta($post_id, '_hard_page', true)) {
        hard_pages_remove_from_json($post->post_title, $post->post_name);
    }
}


add_action('before_delete_post', 'hard_pages_on_page_deleted');
add_action('wp_trash_post', 'hard_pages_on_page_deleted');
add_action('delete_post', 'hard_pages_on_page_deleted');
add_action('rest_delete_post', 'hard_pages_on_page_deleted');


function hard_pages_on_page_trashed($post_id): void {
    $post = get_post($post_id);
    if (! $post) { return;
    }

    if ('page' !== $post->post_type) { return;
    }

    if ('yes' === get_post_meta($post_id, '_hard_page', true)) {
        $original_slug = preg_replace('/__trashed-\d+$/', '', (string) $post->post_name);
        hard_pages_remove_from_json($post->post_title, $original_slug);
        wp_schedule_single_event(time() + 5, 'hard_pages_force_delete_event', [ $post_id ]);
    }
}


add_action('wp_trash_post', 'hard_pages_on_page_trashed');


function hard_pages_force_delete($post_id): void {
    $post = get_post($post_id);
    if (! $post) { return;
    }

    if ('page' !== $post->post_type) { return;
    }

    if ('trash' === $post->post_status) {
        wp_delete_post($post_id, true);
    }
}


add_action('hard_pages_force_delete_event', 'hard_pages_force_delete');

// Hook: update
function hard_pages_on_page_updated($post_id, $post_after, $post_before): void {
    if ('page' !== $post_after->post_type) { return;
    }

    if ($post_after->post_title !== $post_before->post_title || $post_after->post_name !== $post_before->post_name) {
        hard_pages_update_in_json(
            $post_id,
            (string) $post_before->post_title,
            (string) $post_before->post_name,
            (string) $post_after->post_title,
            (string) $post_after->post_name
        );
    }
}


add_action('post_updated', 'hard_pages_on_page_updated', 10, 3);

// Create pages on fresh DB based on JSON
function hard_pages_bootstrap_pages(): void {
    hard_pages_sync_runtime_from_json();
    if (! isset($GLOBALS['hard_pages']) || ! is_array($GLOBALS['hard_pages'])) {
        return;
    }

    foreach ($GLOBALS['hard_pages'] as $title => $data) {
        $slug = $data['slug'];
        $page_key = $title . '|' . $slug;
        if (isset($GLOBALS['recently_deleted_hard_pages']) && in_array($page_key, $GLOBALS['recently_deleted_hard_pages'], true)) {
            continue;
        }

        $existing_page = get_page_by_path($slug, OBJECT, 'page');
        if (! $existing_page) {
            $page_id = wp_insert_post([
                'post_title'  => $title,
                'post_name'   => $slug,
                'post_status' => 'publish',
                'post_type'   => 'page',
                'post_author' => 1,
            ]);
            if ($page_id) {
                update_post_meta($page_id, '_hard_page', 'yes');
                update_post_meta($page_id, 'page_type', $data['page_type']);
            }
        } else {
            update_post_meta($existing_page->ID, '_hard_page', 'yes');
            update_post_meta($existing_page->ID, 'page_type', $data['page_type']);
        }
    }
}


add_action('wp_loaded', 'hard_pages_bootstrap_pages', 999);

// ACF integration (unchanged behavior, values come from runtime $GLOBALS['hard_pages'])
if (function_exists('acf_add_local_field_group')) {
    add_filter('acf/location/rule_types', function (array $choices) {
        $choices['Hard Pages']['hard_page'] = 'Hard Page Type';
        return $choices;
    });

    add_filter('acf/location/rule_values/hard_page', function (array $choices): array {
        if (isset($GLOBALS['hard_pages']) && is_array($GLOBALS['hard_pages'])) {
            foreach ($GLOBALS['hard_pages'] as $page) {
                if (! empty($page['page_type'])) {
                    $value             = $page['page_type'];
                    $choices[ $value ] = $value;
                }
            }
        }

        return $choices;
    });

    add_filter('acf/location/rule_match/hard_page', function ($match, array $rule, array $options) {
        $post_id = $options['post_id'];
        if (! $post_id || 'page' !== get_post_type($post_id)) {
            return false;
        }

        $field_value = get_post_meta($post_id, 'page_type', true);
        if ('==' === $rule['operator']) {
            $match = ($field_value === $rule['value']);
        } elseif ('!=' === $rule['operator']) {
            $match = ($field_value !== $rule['value']);
        }

        return $match;
    }, 10, 3);
}

// Admin GET handling to sync JSON on bulk actions
function hard_pages_get_ids_from_get($key = 'ids'): array {
    if (! isset($_GET[$key])) { return [];
    }

    $raw = wp_unslash($_GET[$key]); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $ids = array_map('sanitize_text_field', explode(',', $raw));
    $ids = array_map('intval', $ids);
    return array_filter($ids, fn($id): bool => $id > 0);
}


// phpcs:ignore WordPress.Security.NonceVerification.Missing
function hard_pages_on_admin_all_actions_early(): void {
    // Minimal nonce check
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    if (isset($_GET['_wpnonce']) && ! wp_verify_nonce(wp_unslash($_GET['_wpnonce']))) {
        return;
    }

    if (isset($_GET['trashed']) && isset($_GET['ids'])) {
        foreach (hard_pages_get_ids_from_get('ids') as $post_id) {
            $post = get_post($post_id);
            if ($post && 'page' === $post->post_type && 'yes' === get_post_meta($post_id, '_hard_page', true)) {
                $original_slug = preg_replace('/__trashed-\d+$/', '', (string) $post->post_name);
                hard_pages_remove_from_json($post->post_title, $original_slug);
                wp_schedule_single_event(time() + 5, 'hard_pages_force_delete_event', [ $post_id ]);
            }
        }
    }

    if (isset($_GET['deleted']) && isset($_GET['ids'])) {
        foreach (hard_pages_get_ids_from_get('ids') as $post_id) {
            global $wpdb;
            $post = $wpdb->get_row($wpdb->prepare('SELECT * FROM %s WHERE ID = ' . $wpdb->posts, $post_id));
            if ($post && 'page' === $post->post_type) {
                $is_hard_page = $wpdb->get_var($wpdb->prepare('SELECT meta_value FROM %s WHERE post_id = %d AND meta_key = ' . $wpdb->postmeta, $post_id, '_hard_page'));
                if ('yes' === $is_hard_page) {
                    hard_pages_remove_from_json($post->post_title, $post->post_name);
                }
            }
        }
    }
}


add_action('admin_init', 'hard_pages_on_admin_all_actions_early', 1);



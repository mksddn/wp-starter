<?php
/*
Plugin Name: Export and Import Single Page with ACF
Description: Export and import a single page along with its ACF fields in JSON format.
Version: 1.0
Author: mksddn
*/

if (!defined('ABSPATH')) exit; // Prevent direct access

// Add menu item to the admin panel
add_action('admin_menu', function () {
    add_menu_page(
        'Export and Import Page',
        'Export & Import Page',
        'manage_options',
        'export-import-single-page',
        'render_export_import_page',
        'dashicons-download',
        20
    );
});

// Render the export and import page
function render_export_import_page()
{
    if (!current_user_can('manage_options')) return;

    echo '<div class="wrap">';
    echo '<h1>Export & Import Page</h1>';

    // Export form
    echo '<h2>Export Page</h2>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('export_single_page_nonce');

    echo '<input type="hidden" name="action" value="export_single_page">';
    echo '<label for="export_page_id">Select a page to export:</label><br>';
    $pages = get_pages();
    echo '<select id="export_page_id" name="page_id" required>';
    foreach ($pages as $page) {
        echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
    }
    echo '</select><br><br>';

    echo '<button type="submit" class="button button-primary">Export</button>';
    echo '</form>';

    // Import form
    echo '<h2>Import Page</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('import_single_page_nonce');

    echo '<label for="import_file">Upload JSON file:</label><br>';
    echo '<input type="file" id="import_file" name="import_file" accept=".json" required><br><br>';

    echo '<button type="submit" class="button button-primary">Import</button>';
    echo '</form>';

    // Handle the import
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('import_single_page_nonce')) {
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['import_file']['tmp_name'];
            $json = file_get_contents($file);

            $data = json_decode($json, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<div class="error"><p>Invalid JSON file.</p></div>';
                return;
            }

            $result = import_single_page($data);

            if ($result) {
                echo '<div class="updated"><p>Page imported successfully!</p></div>';
            } else {
                echo '<div class="error"><p>Failed to import page.</p></div>';
            }
        } else {
            echo '<div class="error"><p>Failed to upload file.</p></div>';
        }
    }

    echo '</div>';
}

// Handle the export request
add_action('admin_post_export_single_page', 'export_single_page');
function export_single_page()
{
    if (!isset($_POST['page_id']) || !check_admin_referer('export_single_page_nonce')) {
        wp_die('Invalid request');
    }

    $page_id = intval($_POST['page_id']);
    $page = get_post($page_id);

    if (!$page || $page->post_type !== 'page') {
        wp_die('Invalid page ID.');
    }

    // Collect page data
    $data = [
        'ID' => $page->ID,
        'title' => $page->post_title,
        'content' => $page->post_content,
        'excerpt' => $page->post_excerpt,
        'slug' => $page->post_name,
        'acf_fields' => function_exists('get_fields') ? get_fields($page->ID) : [],
        'meta' => get_post_meta($page->ID),
    ];

    // Generate JSON
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Clear all output buffering levels
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for file download
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="page-' . $page_id . '.json"');
    header('Content-Length: ' . strlen($json));

    // Output JSON
    echo $json;
    exit;
}

// Function to import a single page
function import_single_page($data)
{
    // Validate input data
    if (!isset($data['title'], $data['content'], $data['slug'])) {
        return false;
    }

    // Prepare data for insertion
    $page_data = [
        'post_title'   => sanitize_text_field($data['title']),
        'post_content' => wp_kses_post($data['content']),
        'post_excerpt' => sanitize_text_field($data['excerpt'] ?? ''),
        'post_name'    => sanitize_title($data['slug']),
        'post_type'    => 'page',
        'post_status'  => 'draft', // Save as draft for review
    ];

    // Create a new page, always with a new ID
    $page_id = wp_insert_post($page_data);

    if (is_wp_error($page_id)) {
        return false;
    }

    // Import ACF fields
    if (function_exists('update_field') && isset($data['acf_fields'])) {
        foreach ($data['acf_fields'] as $field_name => $field_value) {
            update_field($field_name, $field_value, $page_id);
        }
    }

    // Import custom meta
    if (isset($data['meta'])) {
        foreach ($data['meta'] as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($page_id, $key, maybe_unserialize($value));
            }
        }
    }

    return true;
}

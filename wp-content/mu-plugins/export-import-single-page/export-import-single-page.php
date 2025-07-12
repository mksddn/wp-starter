<?php
/*
Plugin Name: Export and Import Single Page with ACF
Description: Export and import a single page or options page along with its ACF fields in JSON format.
Version: 1.1
Author: mksddn
*/

if (! defined( 'ABSPATH' )) {
    exit; // Prevent direct access
}

// Add menu item to the admin panel
add_action(
    'admin_menu',
    function () {
        add_menu_page(
            'Export and Import Page',
            'Export & Import Page',
            'manage_options',
            'export-import-single-page',
            'render_export_import_page',
            'dashicons-download',
            20
        );
    }
);

// Render the export and import page
function render_export_import_page() {
    if (! current_user_can( 'manage_options' )) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>Export & Import Page</h1>';

    // Export form
    echo '<h2>Export Page</h2>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'export_single_page_nonce' );

    echo '<input type="hidden" name="action" value="export_single_page">';
    echo '<label for="export_type">Select type to export:</label><br>';
    echo '<select id="export_type" name="export_type" onchange="toggleExportOptions()" required>';
    echo '<option value="">Select type...</option>';
    echo '<option value="page">Page</option>';
    echo '<option value="options_page">Options Page</option>';
    echo '</select><br><br>';

    // Page selection (initially hidden)
    echo '<div id="page_selection" style="display:none;">';
    echo '<label for="export_page_id">Select a page to export:</label><br>';
    $pages = get_pages();
    echo '<select id="export_page_id" name="page_id">';
    echo '<option value="">Select page...</option>';
    foreach ($pages as $page) {
        echo '<option value="' . esc_attr( $page->ID ) . '">' . esc_html( $page->post_title ) . '</option>';
    }

    echo '</select><br><br>';
    echo '</div>';

    // Options Page selection (initially hidden)
    echo '<div id="options_page_selection" style="display:none;">';
    echo '<label for="export_options_page_slug">Select an options page to export:</label><br>';
    $options_pages = get_all_options_pages();
    echo '<select id="export_options_page_slug" name="options_page_slug">';
    echo '<option value="">Select options page...</option>';
    foreach ($options_pages as $page) {
        $title = $page['page_title'] ?? $page['menu_title'] ?? ucfirst( str_replace( '-', ' ', $page['menu_slug'] ) );
        echo '<option value="' . esc_attr( $page['menu_slug'] ) . '">' . esc_html( $title ) . '</option>';
    }

    echo '</select><br><br>';
    echo '</div>';

    echo '<button type="submit" class="button button-primary">Export</button>';
    echo '</form>';

    // Import form
    echo '<h2>Import Page</h2>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field( 'import_single_page_nonce' );

    echo '<label for="import_file">Upload JSON file:</label><br>';
    echo '<input type="file" id="import_file" name="import_file" accept=".json" required><br><br>';

    echo '<button type="submit" class="button button-primary">Import</button>';
    echo '</form>';

    // Handle the import
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'import_single_page_nonce' )) {
        if (isset( $_FILES['import_file'] ) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['import_file']['tmp_name'];
            $mime = mime_content_type( $file );
            if ($mime !== 'application/json') {
                echo '<div class="error"><p>' . esc_html__( 'Invalid file type.', 'export-import-single-page' ) . '</p></div>';
                return;
            }

            $json = file_get_contents( $file );

            $data = json_decode( $json, true );

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<div class="error"><p>' . esc_html__( 'Invalid JSON file.', 'export-import-single-page' ) . '</p></div>';
                return;
            }

            $result = false;
            if (isset( $data['type'] )) {
                if ($data['type'] === 'options_page') {
                    $result = import_options_page( $data );
                } else {
                    $result = import_single_page( $data );
                }
            } else {
                // Backward compatibility - assume it's a page
                $result = import_single_page( $data );
            }

            if ($result) {
                echo '<div class="updated"><p>' . esc_html__( 'Content imported successfully!', 'export-import-single-page' ) . '</p></div>';
            } else {
                echo '<div class="error"><p>' . esc_html__( 'Failed to import content.', 'export-import-single-page' ) . '</p></div>';
            }
        } else {
            echo '<div class="error"><p>' . esc_html__( 'Failed to upload file.', 'export-import-single-page' ) . '</p></div>';
        }
    }

    echo '</div>';

    // JavaScript for dynamic form
    echo '<script>
    function toggleExportOptions() {
        var exportType = document.getElementById("export_type").value;
        var pageSelection = document.getElementById("page_selection");
        var optionsPageSelection = document.getElementById("options_page_selection");
        
        pageSelection.style.display = "none";
        optionsPageSelection.style.display = "none";
        
        if (exportType === "page") {
            pageSelection.style.display = "block";
        } else if (exportType === "options_page") {
            optionsPageSelection.style.display = "block";
        }
    }
    </script>';
}


// Handle the export request
add_action( 'admin_post_export_single_page', 'export_single_page' );


function export_single_page() {
    if (! check_admin_referer( 'export_single_page_nonce' )) {
        wp_die( 'Invalid request' );
    }

    $export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( $_POST['export_type'] ) : '';

    if ($export_type === 'options_page') {
        $options_page_slug = isset( $_POST['options_page_slug'] ) ? sanitize_text_field( $_POST['options_page_slug'] ) : '';
        if (empty( $options_page_slug )) {
            wp_die( esc_html__( 'Invalid options page slug.', 'export-import-single-page' ) );
        }

        // Get options page data
        $options_pages = get_all_options_pages();
        $target_page   = null;

        foreach ($options_pages as $page) {
            if ($page['menu_slug'] === $options_page_slug) {
                $target_page = $page;
                break;
            }
        }

        if (! $target_page) {
            wp_die( esc_html__( 'Invalid options page slug.', 'export-import-single-page' ) );
        }

        // Collect options page data
        $data = array(
            'type'       => 'options_page',
            'menu_slug'  => $target_page['menu_slug'],
            'page_title' => $target_page['page_title'] ?? '',
            'menu_title' => $target_page['menu_title'] ?? '',
            'post_id'    => $target_page['post_id'] ?? '',
            'acf_fields' => function_exists( 'get_fields' ) ? get_fields( $target_page['post_id'] ) : array(),
        );

        $filename = 'options-page-' . $options_page_slug . '.json';
    } else {
        // Default to page export
        if (! isset( $_POST['page_id'] )) {
            wp_die( esc_html__( 'Invalid request', 'export-import-single-page' ) );
        }

        $page_id = intval( $_POST['page_id'] );
        $page    = get_post( $page_id );

        if (! $page || $page->post_type !== 'page') {
            wp_die( esc_html__( 'Invalid page ID.', 'export-import-single-page' ) );
        }

        // Collect page data
        $data = array(
            'type'       => 'page',
            'ID'         => $page->ID,
            'title'      => $page->post_title,
            'content'    => $page->post_content,
            'excerpt'    => $page->post_excerpt,
            'slug'       => $page->post_name,
            'acf_fields' => function_exists( 'get_fields' ) ? get_fields( $page->ID ) : array(),
            'meta'       => get_post_meta( $page->ID ),
        );

        $filename = 'page-' . $page_id . '.json';
    }

    // Generate JSON
    $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

    // Clear all output buffering levels
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for file download
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
    header( 'Content-Length: ' . strlen( $json ) );

    // Output JSON
    echo $json;
    exit;
}


// Function to import a single page
function import_single_page( $data ) {
    // Validate input data
    if (! isset( $data['title'], $data['content'], $data['slug'] )) {
        return false;
    }

    // Check if page with this slug already exists
    $existing_page = get_page_by_path( $data['slug'], OBJECT, 'page' );

    // Prepare data for insertion/update
    $page_data = array(
        'post_title'   => sanitize_text_field( $data['title'] ),
        'post_content' => wp_kses_post( $data['content'] ),
        'post_excerpt' => sanitize_text_field( $data['excerpt'] ?? '' ),
        'post_name'    => sanitize_title( $data['slug'] ),
        'post_type'    => 'page',
        'post_status'  => 'publish',
    );

    // If page exists, update it
    if ($existing_page) {
        $page_data['ID'] = $existing_page->ID;
        $page_id         = wp_update_post( $page_data );
    } else {
        // Create new page if it doesn't exist
        $page_id = wp_insert_post( $page_data );
    }

    if (is_wp_error( $page_id )) {
        return false;
    }

    // Import ACF fields
    if (function_exists( 'update_field' ) && isset( $data['acf_fields'] )) {
        foreach ($data['acf_fields'] as $field_name => $field_value) {
            update_field( sanitize_text_field( $field_name ), $field_value, $page_id );
        }
    }

    // Import custom meta
    if (isset( $data['meta'] )) {
        foreach ($data['meta'] as $key => $values) {
            $meta_key = sanitize_text_field( $key );
            foreach ($values as $value) {
                add_post_meta( $page_id, $meta_key, maybe_unserialize( $value ) );
            }
        }
    }

    return true;
}


/**
 * Imports ACF fields for an options page.
 *
 * @param array $data Data array containing 'menu_slug', 'acf_fields', and optionally 'post_id'.
 * @return bool True on success, false on failure.
 */
function import_options_page( $data ) {
    // Validate input data
    if (! isset( $data['menu_slug'], $data['acf_fields'] )) {
        return false;
    }

    $menu_slug = sanitize_text_field( $data['menu_slug'] );
    $post_id   = isset( $data['post_id'] ) ? sanitize_text_field( $data['post_id'] ) : 'option';

    // Import ACF fields
    if (function_exists( 'update_field' ) && isset( $data['acf_fields'] )) {
        foreach ($data['acf_fields'] as $field_name => $field_value) {
            update_field( sanitize_text_field( $field_name ), $field_value, $post_id );
        }
    }

    return true;
}

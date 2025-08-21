<?php
/**
 * Theme Settings page (Appearance → Theme Settings).
 */

if (! defined('ABSPATH')) {
    exit;
}


function wp_theme_get_default_settings(): array {
    return [
        'disable_comments'   => true,
        'cyr2lat'            => true,
        'disable_gutenberg'  => true,
        'file_size_column'   => true,
        'plugins_logger'     => true,
        'svg_support'        => true,
        'duplicate_post'     => true,
        'thumbnail_column'   => false,
        'headless'           => true,
        'enable_delete_sizes'=> true,
        'delete_sizes'       => [ 'thumbnail', 'medium', 'medium_large', 'large', '1536x1536', '2048x2048' ],
    ];
}


function wp_theme_get_settings(): array {
    static $cached;
    if (isset($cached)) {
        return $cached;
    }

    $defaults = wp_theme_get_default_settings();
    $opts = get_option('wp_theme_settings', []);
    if (! is_array($opts)) {
        $opts = [];
    }

    $cached = array_merge($defaults, $opts);
    return $cached;
}


function wp_theme_settings(): array {
    return wp_theme_get_settings();
}


function wp_theme_settings_admin_menu(): void {
    add_theme_page(
        'Theme Settings',
        'Theme Settings',
        'manage_options',
        'wp-theme-settings',
        'wp_theme_render_settings_page'
    );
}


add_action('admin_menu', 'wp_theme_settings_admin_menu');


function wp_theme_settings_register(): void {
    register_setting(
        'wp_theme_settings_group',
        'wp_theme_settings',
        [
            'type'              => 'array',
            'sanitize_callback' => 'wp_theme_settings_sanitize',
            'default'           => wp_theme_get_default_settings(),
        ]
    );

    add_settings_section('wp_theme_section_features', 'Features', '__return_empty_string', 'wp-theme-settings');
    add_settings_field('wp_theme_features_checkboxes', 'Enable features', 'wp_theme_render_features', 'wp-theme-settings', 'wp_theme_section_features');

    add_settings_section('wp_theme_section_headless', 'Headless CMS', '__return_empty_string', 'wp-theme-settings');
    add_settings_field('wp_theme_headless', 'Use as headless CMS', 'wp_theme_render_headless', 'wp-theme-settings', 'wp_theme_section_headless');

    add_settings_section('wp_theme_section_media', 'Media sizes', '__return_empty_string', 'wp-theme-settings');
    add_settings_field('wp_theme_delete_sizes', 'Remove image sizes', 'wp_theme_render_media', 'wp-theme-settings', 'wp_theme_section_media');
}


add_action('admin_init', 'wp_theme_settings_register');


function wp_theme_settings_sanitize($input): array {
    $defaults = wp_theme_get_default_settings();
    $output = is_array($input) ? $input : [];
    $boolKeys = [ 'disable_comments','cyr2lat','disable_gutenberg','file_size_column','plugins_logger','svg_support','duplicate_post','thumbnail_column','headless','enable_delete_sizes' ];
    foreach ($boolKeys as $key) {
        $output[$key] = isset($output[$key]) && (int) $output[$key] === 1;
    }

    $allowed_sizes = [ 'thumbnail','medium','medium_large','large','1536x1536','2048x2048' ];
    $selected = isset($output['delete_sizes']) && is_array($output['delete_sizes']) ? array_values(array_intersect($allowed_sizes, array_map('sanitize_text_field', $output['delete_sizes']))) : [];
    $output['delete_sizes'] = $selected ?: $defaults['delete_sizes'];
    return array_merge($defaults, $output);
}


function wp_theme_render_settings_page(): void {
    if (! current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Theme Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp_theme_settings_group'); ?>
            <?php do_settings_sections('wp-theme-settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function wp_theme_render_features(): void {
    $opts = wp_theme_get_settings();
    $features = [
        'disable_comments'  => 'Disable comments',
        'cyr2lat'           => 'Cyr to Lat (transliteration)',
        'disable_gutenberg' => 'Disable Gutenberg',
        'file_size_column'  => 'Media Library: file size column',
        'plugins_logger'    => 'Plugins logger',
        'svg_support'       => 'SVG support',
        'duplicate_post'    => 'Duplicate Post feature',
        'thumbnail_column'  => 'Posts list: thumbnail column',
    ];
    foreach ($features as $key => $label) {
        $checked = $opts[$key] ? 'checked' : '';
        echo wp_kses(
            '<p><label><input type="checkbox" name="wp_theme_settings[' . esc_attr($key) . ']" value="1" ' . esc_attr($checked) . '> ' . esc_html($label) . '</label></p>',
            [
                'p'     => [],
                'label' => [],
                'input' => [ 'type' => true, 'name' => true, 'value' => true, 'checked' => true ],
            ]
        );
    }
}


function wp_theme_render_headless(): void {
    $opts = wp_theme_get_settings();
    $checked = $opts['headless'] ? 'checked' : '';
    echo wp_kses(
        '<p><label><input type="checkbox" name="wp_theme_settings[headless]" value="1" ' . esc_attr($checked) . '> Enable headless CMS mode (API only, no theme assets)</label></p>',
        [ 'p' => [], 'label' => [], 'input' => [ 'type' => true, 'name' => true, 'value' => true, 'checked' => true ] ]
    );
}


function wp_theme_render_media(): void {
    $opts = wp_theme_get_settings();
    $enable_checked = $opts['enable_delete_sizes'] ? 'checked' : '';
    echo wp_kses(
        '<p><label><input type="checkbox" name="wp_theme_settings[enable_delete_sizes]" value="1" ' . esc_attr($enable_checked) . '> Enable removal of selected intermediate sizes</label></p>',
        [ 'p' => [], 'label' => [], 'input' => [ 'type' => true, 'name' => true, 'value' => true, 'checked' => true ] ]
    );
    $all = [ 'thumbnail','medium','medium_large','large','1536x1536','2048x2048' ];
    foreach ($all as $size) {
        $checked = in_array($size, $opts['delete_sizes'], true) ? 'checked' : '';
        echo wp_kses(
            '<p><label><input type="checkbox" name="wp_theme_settings[delete_sizes][]" value="' . esc_attr($size) . '" ' . esc_attr($checked) . '> ' . esc_html($size) . '</label></p>',
            [ 'p' => [], 'label' => [], 'input' => [ 'type' => true, 'name' => true, 'value' => true, 'checked' => true ] ]
        );
    }
}


/**
 * Apply removal of selected intermediate sizes based on settings.
 */
function wp_theme_apply_media_sizes_filter(): void {
    $opts = wp_theme_get_settings();
    if (empty($opts['enable_delete_sizes'])) {
        return;
    }

    add_filter('intermediate_image_sizes', function ($sizes) use ($opts): array {
        $to_remove = isset($opts['delete_sizes']) && is_array($opts['delete_sizes']) ? $opts['delete_sizes'] : [];
        return array_diff($sizes, $to_remove);
    });
}


// Conditionally enable features from Theme Settings
if (wp_theme_settings()['disable_comments']) {
    // Disable all comments across the site
    require_once get_template_directory() . '/inc/disable-comments.php';
}

if (wp_theme_settings()['cyr2lat']) {
    // Convert Cyrillic to Latin
    require_once get_template_directory() . '/inc/cyr2lat.php';
}

if (wp_theme_settings()['disable_gutenberg']) {
    // Turn off Gutenberg editor
    require_once get_template_directory() . '/inc/disable-gutenberg.php';
}

if (wp_theme_settings()['file_size_column']) {
    // Add file size column in Media Library
    require_once get_template_directory() . '/inc/file-size-column.php';
}

if (wp_theme_settings()['plugins_logger']) {
    // Log plugins changes
    require_once get_template_directory() . '/inc/plugins-logger.php';
}

if (wp_theme_settings()['duplicate_post']) {
    // Duplicate post feature
    require_once get_template_directory() . '/inc/duplicate-post.php';
}

if (wp_theme_settings()['svg_support']) {
    // Allow SVG uploads safely
    require_once get_template_directory() . '/inc/svg-support.php';
}

if (wp_theme_settings()['thumbnail_column']) {
    // Add thumbnail column in admin lists
    require_once get_template_directory() . '/inc/thumbnail-column.php';
}


/**
 * Frontend vs Headless (controlled via Theme Settings)
 */
if (wp_theme_settings()['headless']) {
    require_once get_template_directory() . '/inc/api/api.php';
} else {
    require_once get_template_directory() . '/inc/styles-n-scripts.php';
}

/**
 * Optional: delete intermediate image sizes based on Theme Settings
 */
wp_theme_apply_media_sizes_filter();
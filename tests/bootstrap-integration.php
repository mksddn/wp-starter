<?php
/**
 * Bootstrap for WordPress integration tests (wp-phpunit).
 *
 * @package wp-theme
 */

declare(strict_types=1);

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_readable($autoload)) {
	fwrite(STDERR, "Composer autoload not found. Run: composer install\n");
	exit(1);
}

require_once $autoload;

$_tests_dir = getenv('WP_TESTS_DIR');

if (! $_tests_dir) {
	$_tests_dir = dirname(__DIR__) . '/.srv/wp-tests/wordpress-tests-lib';
}

if (! is_readable($_tests_dir . '/includes/functions.php')) {
	fwrite(
		STDERR,
		"WordPress test library not found at {$_tests_dir}.\n" .
		"Run: composer test:install-wp\n"
	);
	exit(1);
}

// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
putenv('WP_PHPUNIT__DIR=' . $_tests_dir);

require_once $_tests_dir . '/includes/functions.php';

/**
 * Register the theme under test and seed settings before the theme loads.
 */
function wp_theme_tests_bootstrap_theme(): void {
	$themes_root = dirname(__DIR__) . '/wp-content/themes';
	register_theme_directory($themes_root);

	/*
	 * Theme modules load once from settings at include time.
	 * Enable features that do not need third-party plugins.
	 */
	$settings = array(
		'disable_comments'             => true,
		'cyr2lat'                      => true,
		'woocommerce_support'          => false,
		'plugins_logger'               => true,
		'duplicate_post'               => true,
		'thumbnail_column'             => false,
		'polylang_rest_api'            => false,
		'disable_gutenberg'            => false,
		'disable_gutenberg_post_types' => array( 'page' ),
		'page_excerpt'                 => true,
		'disable_auto_excerpt'          => false,
		'clean_archive_title'          => true,
		'clean_pagination'             => true,
		'category_thumbnails'          => true,
		'headless'                     => true,
		'schema_markup'                => true,
		'security_xmlrpc'              => true,
		'security_x_pingback'          => true,
		'security_hide_version'        => true,
		'security_disable_editing'     => true,
		'security_disable_enumeration' => true,
		'security_headers'             => true,
		'security_directory_browsing'  => true,
		'security_login_limits'        => true,
		'search_empty_handling'        => true,
		'search_post_types'            => false,
		'search_post_types_list'       => array( 'post', 'page' ),
		'search_exclude_ids_list'      => array(),
		'search_exclude_slugs_list'    => array(),
		'acf_local_json'               => false,
		'performance_remove_emojis'    => true,
		'performance_disable_embeds'   => true,
		'performance_optimize_queries' => true,
		'performance_cleanup_head'     => true,
		'performance_disable_wp_cron'  => false,
		'performance_limit_revisions'  => true,
		'performance_revisions_limit'  => 3,
		'image_opt_upload_limits'      => true,
		'image_opt_quality'            => true,
		'image_opt_priority_loading'   => true,
		'image_opt_max_file_size'      => 5,
		'image_opt_max_dimension'      => 2560,
		'image_opt_jpeg_quality'       => 85,
		'image_opt_quality_value'      => 85,
		'image_opt_remove_sizes_list'  => array(),
		'file_size_column'             => true,
		'svg_support'                  => true,
	);

	update_option('wp_theme_settings', $settings);

	switch_theme('wp-theme');
}

tests_add_filter('muplugins_loaded', 'wp_theme_tests_bootstrap_theme');

require $_tests_dir . '/includes/bootstrap.php';

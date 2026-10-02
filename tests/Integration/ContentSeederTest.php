<?php
/**
 * Integration tests for content seeder.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_UnitTestCase;

/**
 * Content seeder creates sample pages on an empty test site.
 */
final class ContentSeederTest extends WP_UnitTestCase {

	public function test_seed_creates_sample_page(): void {
		$before = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$stats = wp_theme_content_seed_run(
			array(
				'post_type'       => 'page',
				'limit'           => 1,
				'create_entities' => true,
				'overwrite'       => false,
				'dry_run'         => false,
			)
		);

		$after = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_theme_content_seed_entity',
				'meta_value'     => '1',
			)
		);

		$this->assertGreaterThanOrEqual(1, (int) $stats['created'] + count($after));
		$this->assertNotEmpty($after);
		$this->assertGreaterThan(count($before), count(get_posts(array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		))));
	}
}

<?php
/**
 * Integration tests for category thumbnails.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Integration;

use WP_UnitTestCase;

/**
 * Category term meta thumbnail helpers.
 */
final class CategoryThumbnailsTest extends WP_UnitTestCase {

	public function test_category_thumbnail_term_meta_roundtrip(): void {
		$term = self::factory()->category->create_and_get();
		$attachment_id = self::factory()->attachment->create_upload_object(
			DIR_TESTDATA . '/images/canola.jpg'
		);

		$this->assertNotEmpty($attachment_id);

		update_term_meta($term->term_id, 'category_thumbnail', $attachment_id);

		$this->assertTrue(category_has_thumbnail($term->term_id));
		$url = get_category_thumbnail($term->term_id, 'full');
		$this->assertIsString($url);
		$this->assertNotSame('', $url);
	}
}

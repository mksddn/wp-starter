<?php
/**
 * Unit tests for media library file size column.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

/**
 * @covers ::add_filesize_column
 * @covers ::make_filesize_column_sortable
 */
final class FileSizeColumnTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireThemeFile('inc/file-size-column.php');
	}

	public function test_add_filesize_column(): void {
		$columns = array(
			'cb'    => '<input type="checkbox" />',
			'title' => 'File',
		);

		$result = add_filesize_column($columns);

		$this->assertArrayHasKey('filesize', $result);
		$this->assertSame('File Size', $result['filesize']);
	}

	public function test_make_filesize_column_sortable(): void {
		$result = make_filesize_column_sortable(array());
		$this->assertArrayHasKey('filesize', $result);
		$this->assertSame('filesize', $result['filesize']);
	}
}

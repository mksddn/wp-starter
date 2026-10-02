<?php
/**
 * Unit tests for Cyrillic to Latin transliteration.
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

/**
 * @covers ::my_cyr_to_lat
 */
final class Cyr2LatTest extends UnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->requireThemeFile('inc/cyr2lat.php');
	}

	public function test_transliterates_cyrillic(): void {
		$this->assertSame('Privet mir', my_cyr_to_lat('Привет мир'));
	}

	public function test_leaves_latin_unchanged(): void {
		$this->assertSame('Hello World', my_cyr_to_lat('Hello World'));
	}

	public function test_empty_string(): void {
		$this->assertSame('', my_cyr_to_lat(''));
	}

	public function test_mixed_string(): void {
		$this->assertSame('Test-ABV', my_cyr_to_lat('Test-АБВ'));
	}
}

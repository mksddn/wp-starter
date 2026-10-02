<?php
/**
 * Unit tests for SVG sanitizer (migrated from standalone script).
 *
 * @package wp-theme
 */

declare(strict_types=1);

namespace WPTheme\Tests\Unit;

/**
 * @covers ::wp_theme_sanitize_svg_markup
 * @covers ::wp_theme_sanitize_svg_file
 */
final class SvgSanitizerTest extends UnitTestCase {

	private string $open  = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10">';
	private string $close = '</svg>';

	protected function setUp(): void {
		parent::setUp();
		$this->requireThemeFile('inc/svg-support.php');
	}

	/**
	 * @param string               $svg      Markup.
	 * @param string               $expected 'ok' or WP_Error code.
	 * @param callable|null        $check    Extra assertion on success.
	 */
	private function assertSvgCase(string $svg, string $expected, ?callable $check = null): void {
		$result = wp_theme_sanitize_svg_markup($svg);
		$actual = is_wp_error($result) ? $result->code : 'ok';
		$this->assertSame($expected, $actual);

		if ('ok' === $expected && null !== $check) {
			$this->assertTrue((bool) $check((string) $result));
		}
	}

	public function test_plain_shape_is_ok(): void {
		$this->assertSvgCase($this->open . '<path d="M0 0h10v10z" fill="#000"/>' . $this->close, 'ok');
	}

	public function test_bare_svg_gets_xmlns(): void {
		$this->assertSvgCase(
			'<svg viewBox="0 0 10 10"><rect width="5" height="5"/></svg>',
			'ok',
			static fn(string $out): bool => str_contains($out, 'xmlns="http://www.w3.org/2000/svg"')
		);
	}

	public function test_gradient_with_local_url(): void {
		$this->assertSvgCase(
			$this->open . '<defs><linearGradient id="g"><stop offset="0" stop-color="#fff"/></linearGradient></defs><rect fill="url(#g)" width="5" height="5"/>' . $this->close,
			'ok'
		);
	}

	public function test_use_with_local_href(): void {
		$this->assertSvgCase(
			$this->open . '<defs><path id="p" d="M0 0h1"/></defs><use xlink:href="#p"/><use href="#p"/>' . $this->close,
			'ok'
		);
	}

	public function test_text_with_url_like_aria_label(): void {
		$this->assertSvgCase(
			$this->open . '<text aria-label="Note: see http://example.com" x="1" y="1">Hi</text>' . $this->close,
			'ok'
		);
	}

	public function test_id_containing_data_colon(): void {
		$this->assertSvgCase($this->open . '<rect id="metadata:1" width="1" height="1"/>' . $this->close, 'ok');
	}

	public function test_comment_is_dropped(): void {
		$this->assertSvgCase(
			$this->open . '<!-- hi --><rect width="1" height="1"/>' . $this->close,
			'ok',
			static fn(string $out): bool => ! str_contains($out, 'hi')
		);
	}

	public function test_editor_namespace_is_dropped(): void {
		$this->assertSvgCase(
			'<svg xmlns="http://www.w3.org/2000/svg" xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"><sodipodi:namedview id="n"/><rect width="1" height="1"/></svg>',
			'ok',
			static fn(string $out): bool => ! str_contains($out, 'sodipodi')
		);
	}

	public function test_anchor_is_unwrapped(): void {
		$this->assertSvgCase(
			$this->open . '<a href="https://example.com"><rect width="1" height="1"/></a>' . $this->close,
			'ok',
			static fn(string $out): bool => ! str_contains($out, '<a') && ! str_contains($out, 'example.com') && str_contains($out, '<rect')
		);
	}

	public function test_filter_primitives_allowed(): void {
		$this->assertSvgCase(
			$this->open . '<filter id="f"><feGaussianBlur stdDeviation="2"/></filter><rect filter="url(#f)" width="1" height="1"/>' . $this->close,
			'ok'
		);
	}

	/**
	 * @return array<string, array{0:string,1:string}>
	 */
	public function activeContentProvider(): array {
		$o = $this->open;
		$c = $this->close;

		return array(
			'script element'             => array( $o . '<script>alert(1)</script><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'style element'              => array( $o . '<style>rect{fill:red}</style><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'style attribute'            => array( $o . '<rect style="fill:red" width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'onload handler'             => array( '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="1" height="1"/></svg>', 'wp_theme_svg_active' ),
			'onclick handler'            => array( $o . '<rect onclick="alert(1)" width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'foreignObject'              => array( $o . '<foreignObject><div xmlns="http://www.w3.org/1999/xhtml">x</div></foreignObject><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'animate'                    => array( $o . '<rect width="1" height="1"><animate attributeName="x" to="5"/></rect>' . $c, 'wp_theme_svg_active' ),
			'set'                        => array( $o . '<a><set attributeName="href" to="javascript:alert(1)"/><rect width="1" height="1"/></a>' . $c, 'wp_theme_svg_active' ),
			'javascript href'            => array( $o . '<a href="javascript:alert(1)"><rect width="1" height="1"/></a>' . $c, 'wp_theme_svg_active' ),
			'javascript xlink'           => array( $o . '<a xlink:href="jav&#x09;ascript:alert(1)"><rect width="1" height="1"/></a>' . $c, 'wp_theme_svg_active' ),
			'namespaced script'          => array( $o . '<x:script xmlns:x="http://www.w3.org/2000/svg">alert(1)</x:script><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'php tag'                    => array( $o . '<?php echo 1; ?><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
			'short php tag'              => array( $o . '<?= 1 ?><rect width="1" height="1"/>' . $c, 'wp_theme_svg_active' ),
		);
	}

	/**
	 * @dataProvider activeContentProvider
	 */
	public function test_active_content_rejected(string $svg, string $expected): void {
		$this->assertSvgCase($svg, $expected);
	}

	/**
	 * @return array<string, array{0:string,1:string}>
	 */
	public function remoteContentProvider(): array {
		$o = $this->open;
		$c = $this->close;

		return array(
			'xml-stylesheet'           => array( '<?xml-stylesheet href="x.css"?>' . $o . '<rect width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
			'image element'            => array( $o . '<image href="https://example.com/a.png"/><rect width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
			'feImage'                  => array( $o . '<filter id="f"><feImage href="https://example.com/a.png"/></filter><rect width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
			'use external href'        => array( $o . '<use href="https://example.com/a.svg#x"/>' . $c, 'wp_theme_svg_remote' ),
			'use relative file href'   => array( $o . '<use xlink:href="other.svg#x"/>' . $c, 'wp_theme_svg_remote' ),
			'use data href'            => array( $o . '<use href="data:image/svg+xml;base64,PHN2Zy8+"/>' . $c, 'wp_theme_svg_remote' ),
			'remote url in fill'       => array( $o . '<rect fill="url(https://example.com/p)" width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
			'quoted remote url'        => array( $o . '<rect fill="url(\'//example.com/p\')" width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
			'xml:base blocked'         => array( $o . '<use xml:base="https://example.com/" href="#a"/><rect id="a" width="1" height="1"/>' . $c, 'wp_theme_svg_remote' ),
		);
	}

	/**
	 * @dataProvider remoteContentProvider
	 */
	public function test_remote_content_rejected(string $svg, string $expected): void {
		$this->assertSvgCase($svg, $expected);
	}

	public function test_xhtml_element_with_svg_like_name_stripped(): void {
		$this->assertSvgCase(
			$this->open . '<h:title xmlns:h="http://www.w3.org/1999/xhtml">x</h:title><rect width="1" height="1"/>' . $this->close,
			'ok',
			static fn(string $out): bool => ! str_contains($out, 'h:title')
		);
	}

	/**
	 * @return array<string, array{0:string,1:string}>
	 */
	public function invalidSvgProvider(): array {
		$o = $this->open;
		$c = $this->close;

		return array(
			'DOCTYPE'           => array( '<!DOCTYPE svg [<!ENTITY x "y">]>' . $o . '<rect width="1" height="1"/>' . $c, 'wp_theme_svg_invalid' ),
			'external entity'   => array( '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>' . $o . '<text>&xxe;</text>' . $c, 'wp_theme_svg_invalid' ),
			'null byte'         => array( $o . "\0" . '<rect width="1" height="1"/>' . $c, 'wp_theme_svg_invalid' ),
			'malformed xml'     => array( '<svg xmlns="http://www.w3.org/2000/svg"><rect></svg>', 'wp_theme_svg_invalid' ),
			'non-svg root'      => array( '<html xmlns="http://www.w3.org/1999/xhtml"><body/></html>', 'wp_theme_svg_invalid' ),
			'no drawable'       => array( $o . '<g/>' . $c, 'wp_theme_svg_empty' ),
		);
	}

	/**
	 * @dataProvider invalidSvgProvider
	 */
	public function test_invalid_and_empty_svg(string $svg, string $expected): void {
		$this->assertSvgCase($svg, $expected);
	}

	public function test_file_sanitize_is_idempotent(): void {
		$tmp = tempnam(sys_get_temp_dir(), 'svg');
		$this->assertNotFalse($tmp);
		file_put_contents($tmp, $this->open . '<!-- c --><rect width="1" height="1"/>' . $this->close);

		$first  = wp_theme_sanitize_svg_file($tmp);
		$body1  = (string) file_get_contents($tmp);
		$second = wp_theme_sanitize_svg_file($tmp);
		$body2  = (string) file_get_contents($tmp);

		$this->assertTrue($first);
		$this->assertTrue($second);
		$this->assertSame($body1, $body2);
		$this->assertStringNotContainsString('c --', $body1);

		unlink($tmp);
	}

	public function test_file_with_script_rejected(): void {
		$tmp = tempnam(sys_get_temp_dir(), 'svg');
		$this->assertNotFalse($tmp);
		file_put_contents($tmp, $this->open . '<script>1</script><rect width="1" height="1"/>' . $this->close);

		$result = wp_theme_sanitize_svg_file($tmp);
		$this->assertTrue(is_wp_error($result));

		unlink($tmp);
	}
}

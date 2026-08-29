<?php

use WP_CLI\Tests\TestCase;

/**
 * Tests for adding a config when the placement anchor appears more than once.
 *
 * See: https://github.com/wp-cli/config-command/issues/231
 */
class DuplicateAnchorTest extends TestCase {

	/**
	 * The default placement anchor.
	 */
	const ANCHOR = "/* That's all, stop editing!";

	/**
	 * The full anchor comment as shipped by WordPress.
	 */
	const ANCHOR_LINE = "/* That's all, stop editing! Happy publishing. */";

	protected static $test_config_path;

	public static function set_up_before_class() {
		self::$test_config_path = __DIR__ . '/wp-config-test-duplicate-anchor.php';
	}

	public static function tear_down_after_class() {
		if ( file_exists( self::$test_config_path ) ) {
			unlink( self::$test_config_path );
		}
	}

	/**
	 * Writes a fresh wp-config.php in which the anchor appears twice.
	 *
	 * @return WPConfigTransformer
	 */
	private function get_transformer() {
		$contents  = "<?php\n";
		$contents .= "define( 'DB_NAME', 'test_db' );\n";
		$contents .= "\n";
		$contents .= self::ANCHOR_LINE . "\n";
		$contents .= "\n";
		$contents .= "define( 'WP_DEBUG', false );\n";
		$contents .= "\n";
		$contents .= self::ANCHOR_LINE . "\n";

		file_put_contents( self::$test_config_path, $contents );

		return new WPConfigTransformer( self::$test_config_path );
	}

	public function testAddConstantOnlyOnce() {
		$transformer = $this->get_transformer();

		$this->assertTrue( $transformer->add( 'constant', 'TEST_CONST_DUPLICATE_ANCHOR', 'foo' ) );

		$contents = (string) file_get_contents( self::$test_config_path );

		$this->assertSame( 1, substr_count( $contents, "define( 'TEST_CONST_DUPLICATE_ANCHOR', 'foo' );" ) );
		$this->assertSame( 2, substr_count( $contents, self::ANCHOR_LINE ) );
	}

	public function testAddVariableOnlyOnce() {
		$transformer = $this->get_transformer();

		$this->assertTrue( $transformer->add( 'variable', 'test_var_duplicate_anchor', 'foo' ) );

		$contents = (string) file_get_contents( self::$test_config_path );

		$this->assertSame( 1, substr_count( $contents, "\$test_var_duplicate_anchor = 'foo';" ) );
		$this->assertSame( 2, substr_count( $contents, self::ANCHOR_LINE ) );
	}

	public function testAddBeforeFirstAnchor() {
		$transformer = $this->get_transformer();

		$this->assertTrue( $transformer->add( 'constant', 'TEST_CONST_BEFORE_ANCHOR', 'foo' ) );

		$contents = (string) file_get_contents( self::$test_config_path );

		$this->assertStringContainsString(
			"define( 'TEST_CONST_BEFORE_ANCHOR', 'foo' );" . PHP_EOL . self::ANCHOR_LINE . "\n\ndefine( 'WP_DEBUG', false );",
			$contents
		);
	}

	public function testAddAfterFirstAnchor() {
		$transformer = $this->get_transformer();

		$this->assertTrue(
			$transformer->add(
				'constant',
				'TEST_CONST_AFTER_ANCHOR',
				'foo',
				array(
					'anchor'    => self::ANCHOR_LINE,
					'placement' => 'after',
				)
			)
		);

		$contents = (string) file_get_contents( self::$test_config_path );

		$this->assertSame( 1, substr_count( $contents, "define( 'TEST_CONST_AFTER_ANCHOR', 'foo' );" ) );
		$this->assertStringContainsString(
			self::ANCHOR_LINE . PHP_EOL . "define( 'TEST_CONST_AFTER_ANCHOR', 'foo' );\n\ndefine( 'WP_DEBUG', false );",
			$contents
		);
	}

	public function testMissingAnchorStillThrows() {
		$transformer = $this->get_transformer();

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Unable to locate placement anchor.' );

		$transformer->add( 'constant', 'TEST_CONST_MISSING_ANCHOR', 'foo', array( 'anchor' => '/* Nope. */' ) );
	}
}

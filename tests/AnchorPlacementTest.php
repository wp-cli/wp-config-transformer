<?php

use WP_CLI\Tests\TestCase;

/**
 * Tests that a config added with the 'after' placement lands on a line of its own.
 *
 * An anchor may match only part of the line it sits on — the default anchor matches
 * the start of the "stop editing" comment but not the whole of it — in which case the
 * config used to be inserted into the middle of that line.
 */
class AnchorPlacementTest extends TestCase {

	/**
	 * The full anchor comment as shipped by WordPress.
	 */
	const ANCHOR_LINE = "/* That's all, stop editing! Happy publishing. */";

	protected static $test_config_path;

	public static function set_up_before_class() {
		self::$test_config_path = __DIR__ . '/wp-config-test-anchor-placement.php';
	}

	public static function tear_down_after_class() {
		if ( file_exists( self::$test_config_path ) ) {
			unlink( self::$test_config_path );
		}
	}

	/**
	 * Writes a fresh wp-config.php and returns a transformer for it.
	 *
	 * @param string $contents Config file source.
	 *
	 * @return WPConfigTransformer
	 */
	private function get_transformer( $contents ) {
		file_put_contents( self::$test_config_path, $contents );

		return new WPConfigTransformer( self::$test_config_path );
	}

	/**
	 * A wp-config.php whose anchor comment is the last line.
	 *
	 * @return string
	 */
	private function get_config_src() {
		return "<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n\n" . self::ANCHOR_LINE . "\n";
	}

	public function testAfterPlacementWithDefaultAnchor() {
		$transformer = $this->get_transformer( $this->get_config_src() );

		$this->assertTrue( $transformer->add( 'constant', 'TEST_CONST_AFTER', 'foo', array( 'placement' => 'after' ) ) );

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n\n" . self::ANCHOR_LINE . PHP_EOL . "define( 'TEST_CONST_AFTER', 'foo' );\n",
			(string) file_get_contents( self::$test_config_path )
		);
	}

	public function testAfterPlacementWithFullLineAnchor() {
		$transformer = $this->get_transformer( $this->get_config_src() );

		$this->assertTrue(
			$transformer->add(
				'constant',
				'TEST_CONST_AFTER_FULL_LINE',
				'foo',
				array(
					'anchor'    => self::ANCHOR_LINE,
					'placement' => 'after',
				)
			)
		);

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n\n" . self::ANCHOR_LINE . PHP_EOL . "define( 'TEST_CONST_AFTER_FULL_LINE', 'foo' );\n",
			(string) file_get_contents( self::$test_config_path )
		);
	}

	public function testAfterPlacementWithSeparator() {
		$transformer = $this->get_transformer( $this->get_config_src() );

		$this->assertTrue(
			$transformer->add(
				'constant',
				'TEST_CONST_AFTER_SEPARATOR',
				'foo',
				array(
					'placement' => 'after',
					'separator' => PHP_EOL . PHP_EOL,
				)
			)
		);

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n\n" . self::ANCHOR_LINE . PHP_EOL . PHP_EOL . "define( 'TEST_CONST_AFTER_SEPARATOR', 'foo' );\n",
			(string) file_get_contents( self::$test_config_path )
		);
	}

	public function testAfterPlacementWithAnchorEndingInNewline() {
		$transformer = $this->get_transformer(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n" . self::ANCHOR_LINE . "\ndefine( 'TEST_ANCHOR_NEXT', 'next' );\n"
		);

		$this->assertTrue(
			$transformer->add(
				'constant',
				'TEST_CONST_AFTER_ANCHOR_NEWLINE',
				'foo',
				array(
					'anchor'    => self::ANCHOR_LINE . "\n",
					'placement' => 'after',
				)
			)
		);

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n" . self::ANCHOR_LINE . PHP_EOL . "define( 'TEST_CONST_AFTER_ANCHOR_NEWLINE', 'foo' );\ndefine( 'TEST_ANCHOR_NEXT', 'next' );\n",
			(string) file_get_contents( self::$test_config_path )
		);
	}

	public function testAfterPlacementWithoutTrailingNewline() {
		$transformer = $this->get_transformer( "<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n" . self::ANCHOR_LINE );

		$this->assertTrue( $transformer->add( 'constant', 'TEST_CONST_AFTER_EOF', 'foo', array( 'placement' => 'after' ) ) );

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n" . self::ANCHOR_LINE . PHP_EOL . "define( 'TEST_CONST_AFTER_EOF', 'foo' );",
			(string) file_get_contents( self::$test_config_path )
		);
	}

	public function testAfterPlacementDoesNotCommentOutTheConfig() {
		$transformer = $this->get_transformer( $this->get_config_src() );

		$transformer->add( 'constant', 'TEST_CONST_AFTER_LIVE', 'foo', array( 'placement' => 'after' ) );

		$contents = (string) file_get_contents( self::$test_config_path );

		foreach ( token_get_all( $contents ) as $token ) {
			if ( is_array( $token )
				&& in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true )
				&& false !== strpos( $token[1], 'TEST_CONST_AFTER_LIVE' ) ) {
				$this->fail( 'The config was added inside a comment.' );
			}
		}

		$this->assertTrue( $transformer->exists( 'constant', 'TEST_CONST_AFTER_LIVE' ) );
	}

	public function testBeforePlacementIsUnaffected() {
		$transformer = $this->get_transformer( $this->get_config_src() );

		$this->assertTrue( $transformer->add( 'constant', 'TEST_CONST_BEFORE', 'foo' ) );

		$this->assertSame(
			"<?php\ndefine( 'TEST_ANCHOR_DB', 'test_db' );\n\ndefine( 'TEST_CONST_BEFORE', 'foo' );" . PHP_EOL . self::ANCHOR_LINE . "\n",
			(string) file_get_contents( self::$test_config_path )
		);
	}
}

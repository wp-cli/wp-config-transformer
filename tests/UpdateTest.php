<?php

use WP_CLI\Tests\TestCase;

class UpdateTest extends TestCase {

	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data    = array();
	protected static $string_data = array();

	public static function set_up_before_class() {
		self::$raw_data    = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data.txt' ) );
		self::$string_data = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/string-data.txt' ) );

		if ( version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			self::$raw_data = array_merge( self::$raw_data, explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data-extra.txt' ) ) );
		}

		self::$test_config_path = __DIR__ . '/wp-config-test-update.php';
		file_put_contents( self::$test_config_path, '<?php' . PHP_EOL . PHP_EOL );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tear_down_after_class() {
		unlink( self::$test_config_path );
	}

	public function testRawConstants() {
		foreach ( self::$raw_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_RAW_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue(
				self::$config_transformer->add(
					'constant',
					$name,
					'oldvalue',
					array(
						'anchor'    => '<?php',
						'placement' => 'after',
					)
				),
				$name
			);
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data, array( 'raw' => true ) ), $name );
		}
	}

	public function testStringConstants() {
		foreach ( self::$string_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_STRING_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue(
				self::$config_transformer->add(
					'constant',
					$name,
					'oldvalue',
					array(
						'anchor'    => '<?php',
						'placement' => 'after',
					)
				),
				$name
			);
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data ), $name );
		}
	}

	public function testRawVariables() {
		foreach ( self::$raw_data as $d => $data ) {
			$name = "test_var_update_raw_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue(
				self::$config_transformer->add(
					'variable',
					$name,
					'oldvalue',
					array(
						'anchor'    => '<?php',
						'placement' => 'after',
					)
				),
				"\${$name}"
			);
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data, array( 'raw' => true ) ), "\${$name}" );
		}
	}

	public function testStringVariables() {
		foreach ( self::$string_data as $d => $data ) {
			$name = "test_var_update_string_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue(
				self::$config_transformer->add(
					'variable',
					$name,
					'oldvalue',
					array(
						'anchor'    => '<?php',
						'placement' => 'after',
					)
				),
				"\${$name}"
			);
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data ), "\${$name}" );
		}
	}

	public function testConstantAddIfMissing() {
		$name = 'TEST_CONST_UPDATE_ADD_MISSING';
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue(
			self::$config_transformer->update(
				'constant',
				$name,
				'foo',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			$name
		);
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
	}

	public function testVariableAddIfMissing() {
		$name = 'test_var_update_add_missing';
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertTrue(
			self::$config_transformer->update(
				'variable',
				$name,
				'bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			"\${$name}"
		);
		$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
	}

	public function testConstantNoAddIfMissing() {
		$name = 'TEST_CONST_UPDATE_NO_ADD_MISSING';
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertFalse(
			self::$config_transformer->update(
				'constant',
				$name,
				'foo',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
					'add'       => false,
				)
			),
			$name
		);
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
	}

	/**
	 * @dataProvider constantValueEscapedCorrectlyProvider
	 */
	public function testConstantValueEscapedCorrectly( $value ) {
		$name = 'TEST_CONST_VALUE_ESCAPED';
		self::$config_transformer->update(
			'constant',
			$name,
			'foo',
			array(
				'anchor'    => '<?php',
				'placement' => 'after',
				'add'       => true,
			)
		);
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue( self::$config_transformer->update( 'constant', $name, $value ), $name );
		$this->assertEquals( "'" . $value . "'", self::$config_transformer->get_value( 'constant', $name ) );
	}

	public function constantValueEscapedCorrectlyProvider() {
		return array(
			array( '$12345abcde' ),
			array( 'abc$12345de' ),
			array( '$abcde12345' ),
			array( '123$abcde45' ),
			array( '\\\\12345abcde' ),
		);
	}

	public function testConstantUpdateStringContainingClosingStatementChars() {
		$name = 'TEST_CONST_UPDATE_STRING_CONTAINING_CLOSING_STATEMENT_CHARS';
		$this->assertTrue(
			self::$config_transformer->update(
				'constant',
				$name,
				'foo);bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
					'add'       => true,
				)
			),
			$name
		);
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue( self::$config_transformer->update( 'constant', $name, 'baz' ), $name );
		$this->assertEquals( "'baz'", self::$config_transformer->get_value( 'constant', $name ), $name );
	}

	public function testVariableUpdateStringContainingClosingStatementChars() {
		$name = 'test_var_update_string_containing_closing_statement_chars';
		$this->assertTrue(
			self::$config_transformer->update(
				'variable',
				$name,
				'foo);bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
					'add'       => true,
				)
			),
			"\${$name}"
		);
		$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->update( 'variable', $name, 'baz' ), "\${$name}" );
		$this->assertEquals( "'baz'", self::$config_transformer->get_value( 'variable', $name ), "\${$name}" );
	}

	public function testVariableNoAddIfMissing() {
		$name = 'test_var_update_no_add_missing';
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertFalse(
			self::$config_transformer->update(
				'variable',
				$name,
				'bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
					'add'       => false,
				)
			),
			"\${$name}"
		);
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
	}

	public function testConstantNonString() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Config value must be a string.' );
		$name = 'TEST_CONST_UPDATE_NON_STRING';
		$this->assertTrue(
			self::$config_transformer->add(
				'constant',
				$name,
				'foo',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			$name
		);
		self::$config_transformer->update( 'constant', $name, true );
	}

	public function testVariableNonString() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Config value must be a string.' );
		$name = 'test_var_update_non_string';
		$this->assertTrue(
			self::$config_transformer->add(
				'variable',
				$name,
				'bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			"\${$name}"
		);
		self::$config_transformer->update( 'variable', $name, true );
	}

	public function testConstantEmptyStringRaw() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Raw value for empty string not supported.' );
		$name = 'TEST_CONST_UPDATE_EMPTY_STRING_RAW';
		$this->assertTrue(
			self::$config_transformer->add(
				'constant',
				$name,
				'foo',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			$name
		);
		self::$config_transformer->update( 'constant', $name, '', array( 'raw' => true ) );
	}

	public function testVariableEmptyStringRaw() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Raw value for empty string not supported.' );
		$name = 'test_var_update_empty_string_raw';
		$this->assertTrue(
			self::$config_transformer->add(
				'variable',
				$name,
				'bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			"\${$name}"
		);
		self::$config_transformer->update( 'variable', $name, '', array( 'raw' => true ) );
	}

	public function testConstantWhitespaceStringRaw() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Raw value for empty string not supported.' );
		$name = 'TEST_CONST_UPDATE_WHITESPACE_STRING_RAW';
		$this->assertTrue(
			self::$config_transformer->add(
				'constant',
				$name,
				'foo',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			$name
		);
		self::$config_transformer->update( 'constant', $name, '   ', array( 'raw' => true ) );
	}

	public function testVariableWhitespaceStringRaw() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Raw value for empty string not supported.' );
		$name = 'test_var_update_whitespace_string_raw';
		$this->assertTrue(
			self::$config_transformer->add(
				'variable',
				$name,
				'bar',
				array(
					'anchor'    => '<?php',
					'placement' => 'after',
				)
			),
			"\${$name}"
		);
		self::$config_transformer->update( 'variable', $name, '   ', array( 'raw' => true ) );
	}

	public function testConfigValues() {
		require_once self::$test_config_path;

		foreach ( self::$raw_data as $d => $data ) {
			// Convert string to a real value.
			eval( "\$data = $data;" ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
			// Raw Constants
			$name = "TEST_CONST_UPDATE_RAW_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertNotSame( 'oldvalue', constant( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// Raw Variables
			$name = "test_var_update_raw_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertNotSame( 'oldvalue', ${$name}, "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		foreach ( self::$string_data as $d => $data ) {
			// String Constants
			$name = "TEST_CONST_UPDATE_STRING_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertNotSame( 'oldvalue', constant( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// String Variables
			$name = "test_var_update_string_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertNotSame( 'oldvalue', ${$name}, "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		$this->assertTrue( defined( 'TEST_CONST_UPDATE_ADD_MISSING' ), 'TEST_CONST_UPDATE_ADD_MISSING' );
		$this->assertEquals( 'foo', constant( 'TEST_CONST_UPDATE_ADD_MISSING' ), 'TEST_CONST_UPDATE_ADD_MISSING' );

		$this->assertTrue( ( isset( $test_var_update_add_missing ) || is_null( $test_var_update_add_missing ) ), '$test_var_update_add_missing' );
		$this->assertEquals( 'bar', $test_var_update_add_missing, '$test_var_update_add_missing' );

		$this->assertFalse( defined( 'TEST_CONST_UPDATE_NO_ADD_MISSING' ), 'TEST_CONST_UPDATE_NO_ADD_MISSING' );
		$this->assertFalse( isset( $test_var_update_no_add_missing ), '$test_var_update_no_add_missing' );
	}

	public function testAddConstantWithoutAnchor() {
		$name = 'TEST_CONST_ADD_EXISTS_NO_ANCHOR';
		$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'foo', array( 'anchor' => WPConfigTransformer::ANCHOR_EOF ), $name ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertFalse( self::$config_transformer->add( 'constant', $name, 'bar' ), $name );

		$config_data = file( self::$test_config_path );
		$this->assertStringContainsString( $name, end( $config_data ) );
	}
}

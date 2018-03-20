<?php

use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data = array();
	protected static $string_data = array();

	public static function setUpBeforeClass()
	{
		self::$raw_data    = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data.txt' ) );
		self::$string_data = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/string-data.txt' ) );

		if ( version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			self::$raw_data = array_merge( self::$raw_data, explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data-extra.txt' ) ) );
		}

		self::$test_config_path = __DIR__ . '/wp-config-test-update.php';
		file_put_contents( self::$test_config_path, '<?php' . PHP_EOL . PHP_EOL );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tearDownAfterClass()
	{
		unlink( self::$test_config_path );
	}

	public function testRawConstants()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_RAW_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'oldvalue', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data, array( 'raw' => true ) ), $name );
		}
	}

	public function testStringConstants()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_STRING_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'oldvalue', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data ), $name );
		}
	}

	public function testRawVariables()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "test_var_update_raw_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'oldvalue', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data, array( 'raw' => true ) ), "\${$name}" );
		}
	}

	public function testStringVariables()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "test_var_update_string_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'oldvalue', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data ), "\${$name}" );
		}
	}

	public function testConstantAddIfMissing()
	{
		$name = 'TEST_CONST_UPDATE_ADD_MISSING';
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue( self::$config_transformer->update( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
	}

	public function testVariableAddIfMissing()
	{
		$name = 'test_var_update_add_missing';
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->update( 'variable', $name, 'bar', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
	}

	public function testConstantNoAddIfMissing()
	{
		$name = 'TEST_CONST_UPDATE_NO_ADD_MISSING';
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertFalse( self::$config_transformer->update( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after', 'add' => false ) ), $name );
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
	}

	/**
	 * @dataProvider constantValueProvider
	 */
	public function testConstantValueEscapedCorrectly( $value )
	{
		$name = 'TEST_CONST_VALUE_ESCAPED';
		self::$config_transformer->update( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after', 'add' => true ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue( self::$config_transformer->update( 'constant', $name, $value ), $name );
		$this->assertEquals( "'" . $value . "'", self::$config_transformer->get_value( 'constant', $name ) );
	}

	public function constantValueProvider() {
		return array(
			array( '$12345abcde' ),
			array( 'abc$12345de' ),
			array( '$abcde12345' ),
			array( '123$abcde45' ),
			array( '\\\\12345abcde' ),
		);
	}

	public function testVariableNoAddIfMissing()
	{
		$name = 'test_var_update_no_add_missing';
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertFalse( self::$config_transformer->update( 'variable', $name, 'bar', array( 'anchor' => '<?php', 'placement' => 'after', 'add' => false ) ), "\${$name}" );
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Config value must be a string.
	 */
	public function testConstantNonString()
	{
		$name = 'TEST_CONST_UPDATE_NON_STRING';
		$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
		self::$config_transformer->update( 'constant', $name, true );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Config value must be a string.
	 */
	public function testVariableNonString()
	{
		$name = 'test_var_update_non_string';
		$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'bar', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
		self::$config_transformer->update( 'variable', $name, true );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testConstantEmptyStringRaw()
	{
		$name = 'TEST_CONST_UPDATE_EMPTY_STRING_RAW';
		$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
		self::$config_transformer->update( 'constant', $name, '', array( 'raw' => true ) );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testVariableEmptyStringRaw()
	{
		$name = 'test_var_update_empty_string_raw';
		$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'bar', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
		self::$config_transformer->update( 'variable', $name, '', array( 'raw' => true ) );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testConstantWhitespaceStringRaw()
	{
		$name = 'TEST_CONST_UPDATE_WHITESPACE_STRING_RAW';
		$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'foo', array( 'anchor' => '<?php', 'placement' => 'after' ) ), $name );
		self::$config_transformer->update( 'constant', $name, '   ', array( 'raw' => true ) );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testVariableWhitespaceStringRaw()
	{
		$name = 'test_var_update_whitespace_string_raw';
		$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'bar', array( 'anchor' => '<?php', 'placement' => 'after' ) ), "\${$name}" );
		self::$config_transformer->update( 'variable', $name, '   ', array( 'raw' => true ) );
	}

	public function testConfigValues()
	{
		require_once self::$test_config_path;

		foreach ( self::$raw_data as $d => $data ) {
			eval( "\$data = $data;" ); // Convert string to a real value.
			// Raw Constants
			$name = "TEST_CONST_UPDATE_RAW_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertNotEquals( 'oldvalue', constant( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// Raw Variables
			$name = "test_var_update_raw_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertNotEquals( 'oldvalue', ${$name}, "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		foreach ( self::$string_data as $d => $data ) {
			// String Constants
			$name = "TEST_CONST_UPDATE_STRING_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertNotEquals( 'oldvalue', constant( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// String Variables
			$name = "test_var_update_string_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertNotEquals( 'oldvalue', ${$name}, "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		$this->assertTrue( defined( 'TEST_CONST_UPDATE_ADD_MISSING' ), 'TEST_CONST_UPDATE_ADD_MISSING' );
		$this->assertEquals( 'foo', constant( 'TEST_CONST_UPDATE_ADD_MISSING' ), 'TEST_CONST_UPDATE_ADD_MISSING' );

		$this->assertTrue( ( isset( $test_var_update_add_missing ) || is_null( $test_var_update_add_missing ) ), '$test_var_update_add_missing' );
		$this->assertEquals( 'bar', $test_var_update_add_missing, '$test_var_update_add_missing' );

		$this->assertFalse( defined( 'TEST_CONST_UPDATE_NO_ADD_MISSING' ), 'TEST_CONST_UPDATE_NO_ADD_MISSING' );
		$this->assertFalse( isset( $test_var_update_no_add_missing ), '$test_var_update_no_add_missing' );
	}
}

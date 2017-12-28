<?php

use PHPUnit\Framework\TestCase;

class AddTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data = [];
	protected static $string_data = [];

	public static function setUpBeforeClass()
	{
		self::$raw_data    = explode( PHP_EOL, file_get_contents( __DIR__ . '/bin/raw-data.txt' ) );
		self::$string_data = explode( PHP_EOL, file_get_contents( __DIR__ . '/bin/string-data.txt' ) );

		if ( version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			self::$raw_data = array_merge( self::$raw_data, explode( PHP_EOL, file_get_contents( __DIR__ . '/bin/raw-data-extra.txt' ) ) );
		}

		self::$test_config_path = __DIR__ . '/wp-config-test-add.php';
		copy( __DIR__ . '/bin/wp-config-sample.php', self::$test_config_path );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tearDownAfterClass()
	{
		unlink( self::$test_config_path );
	}

	public function testRawConstants()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "TEST_CONST_ADD_RAW_{$d}";
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, $data, [ 'raw' => true ] ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		}
	}

	public function testStringConstants()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "TEST_CONST_ADD_STRING_{$d}";
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, $data ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		}
	}

	public function testRawVariables()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "test_var_add_raw_{$d}";
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, $data, [ 'raw' => true ] ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		}
	}

	public function testStringVariables()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "test_var_add_string_{$d}";
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, $data ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		}
	}

	public function testConstantNoAddIfExists()
	{
		$name = 'TEST_CONST_ADD_EXISTS';
		$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'foo' ), $name );
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertFalse( self::$config_transformer->add( 'constant', $name, 'bar' ), $name );
	}

	public function testVariableNoAddIfExists()
	{
		$name = 'test_var_add_exists';
		$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'foo' ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertFalse( self::$config_transformer->add( 'variable', $name, 'bar' ), "\${$name}" );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Unable to locate placement target.
	 */
	public function testConstantNoPlacementTarget()
	{
		self::$config_transformer->add( 'constant', 'TEST_CONST_ADD_NO_TARGET', 'foo', [ 'target' => 'nothingtoseehere' ] );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Unable to locate placement target.
	 */
	public function testVariableNoPlacementTarget()
	{
		self::$config_transformer->add( 'variable', 'test_var_add_no_target', 'foo', [ 'target' => 'nothingtoseehere' ] );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Config value must be a string.
	 */
	public function testConstantNonString()
	{
		self::$config_transformer->add( 'constant', 'TEST_CONST_ADD_NON_STRING', true );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Config value must be a string.
	 */
	public function testVariableNonString()
	{
		self::$config_transformer->add( 'variable', 'test_var_add_non_string', true );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testConstantEmptyStringRaw()
	{
		self::$config_transformer->add( 'constant', 'TEST_CONST_ADD_EMPTY_STRING_RAW', '', [ 'raw' => true ] );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testVariableEmptyStringRaw()
	{
		self::$config_transformer->add( 'variable', 'test_var_add_empty_string_raw', '', [ 'raw' => true ] );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testConstantWhitespaceStringRaw()
	{
		self::$config_transformer->add( 'constant', 'TEST_CONST_ADD_WHITESPACE_STRING_RAW', '   ', [ 'raw' => true ] );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Raw value for empty string not supported.
	 */
	public function testVariableWhitespaceStringRaw()
	{
		self::$config_transformer->add( 'variable', 'test_var_add_whitespace_string_raw', '   ', [ 'raw' => true ] );
	}

	public function testConfigValues()
	{
		require_once self::$test_config_path;

		foreach ( self::$raw_data as $d => $data ) {
			eval( "\$data = $data;" ); // Convert string to a real value.
			// Raw Constants
			$name = "TEST_CONST_ADD_RAW_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// Raw Variables
			$name = "test_var_add_raw_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		foreach ( self::$string_data as $d => $data ) {
			// String Constants
			$name = "TEST_CONST_ADD_STRING_{$d}";
			$this->assertTrue( defined( $name ), $name );
			$this->assertEquals( $data, constant( $name ), $name );
			// String Variables
			$name = "test_var_add_string_{$d}";
			$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
			$this->assertEquals( $data, ${$name}, "\${$name}" );
		}

		$this->assertTrue( defined( 'TEST_CONST_ADD_EXISTS' ), 'TEST_CONST_ADD_EXISTS' );
		$this->assertEquals( 'foo', constant( 'TEST_CONST_ADD_EXISTS' ), 'TEST_CONST_ADD_EXISTS' );

		$this->assertTrue( ( isset( $test_var_add_exists ) || is_null( $test_var_add_exists ) ), '$test_var_update_add_missing' );
		$this->assertEquals( 'foo', $test_var_add_exists, '$test_var_add_exists' );
	}
}

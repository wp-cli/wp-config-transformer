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
		self::$test_config_path = __DIR__ . '/wp-config-test-add.php';
		copy( __DIR__ . '/bin/wp-config-sample.php', self::$test_config_path );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );

		self::$raw_data    = explode( "\n", file_get_contents( __DIR__ . '/bin/raw-data.txt' ) );
		self::$string_data = explode( "\n", file_get_contents( __DIR__ . '/bin/string-data.txt' ) );
	}

	public static function tearDownAfterClass()
	{
//		unlink( self::$test_config_path );
	}

	public function testAddRawConstants()
	{
		foreach ( self::$raw_data as $i => $data ) {
			$this->assertTrue( self::$config_transformer->add( 'constant', "TEST_ADD_RAW_{$i}", $data, [ 'raw' => true ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'constant', "TEST_ADD_RAW_{$i}" ) );
		}
	}

	public function testAddStringConstants()
	{
		foreach ( self::$string_data as $i => $data ) {
			$this->assertTrue( self::$config_transformer->add( 'constant', "TEST_ADD_STRING_{$i}", $data ) );
			$this->assertTrue( self::$config_transformer->exists( 'constant', "TEST_ADD_STRING_{$i}" ) );
		}
	}

	public function testAddRawVariables()
	{
		foreach ( self::$raw_data as $i => $data ) {
			$this->assertTrue( self::$config_transformer->add( 'variable', "test_add_raw_{$i}", $data, [ 'raw' => true ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'variable', "test_add_raw_{$i}" ) );
		}
	}

	public function testAddStringVariables()
	{
		foreach ( self::$string_data as $i => $data ) {
			$this->assertTrue( self::$config_transformer->add( 'variable', "test_add_string_{$i}", $data ) );
			$this->assertTrue( self::$config_transformer->exists( 'variable', "test_add_string_{$i}" ) );
		}
	}

	public function testConfigValues()
	{
		require_once self::$test_config_path;

		foreach ( self::$raw_data as $i => $data ) {
			// Convert string to a real value.
			eval( "\$data = $data;" );
			// Raw Constants
			$this->assertTrue( defined( "TEST_ADD_RAW_{$i}" ), "TEST_ADD_RAW_{$i}" );
			$this->assertEquals( $data, constant( "TEST_ADD_RAW_{$i}" ), "TEST_ADD_RAW_{$i}" );
			// Raw Variables
			$this->assertTrue( ( isset( ${"test_add_raw_" . $i} ) || is_null( ${"test_add_raw_" . $i} ) ), "\$test_add_raw_{$i}" );
			$this->assertEquals( $data, ${"test_add_raw_" . $i}, "test_add_raw_{$i}" );
		}

		foreach ( self::$string_data as $i => $data ) {
			// String Constants
			$this->assertTrue( defined( "TEST_ADD_STRING_{$i}" ), "TEST_ADD_STRING_{$i}" );
			$this->assertEquals( $data, constant( "TEST_ADD_STRING_{$i}" ), "TEST_ADD_STRING_{$i}" );
			// String Variables
			$this->assertTrue( ( isset( ${"test_add_string_" . $i} ) || is_null( ${"test_add_string_" . $i} ) ), "\$test_add_string_{$i}" );
			$this->assertEquals( $data, ${"test_add_string_" . $i}, "test_add_string_{$i}" );
		}
	}

	public function testConstantNoAddIfExists()
	{
		$this->assertTrue( self::$config_transformer->add( 'constant', 'TEST_ADD_EXISTS', 'foo' ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'TEST_ADD_EXISTS' ) );
		$this->assertFalse( self::$config_transformer->add( 'constant', 'TEST_ADD_EXISTS', 'bar' ) );
	}

	public function testVariableNoAddIfExists()
	{
		$this->assertTrue( self::$config_transformer->add( 'variable', 'test_add_exists', 'foo' ) );
		$this->assertTrue( self::$config_transformer->exists( 'variable', 'test_add_exists' ) );
		$this->assertFalse( self::$config_transformer->add( 'variable', 'test_add_exists', 'bar' ) );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Unable to locate placement target.
	 */
	public function testNoPlacementTarget()
	{
		self::$config_transformer->add( 'constant', 'TEST_ADD_NO_TARGET', 'foo', [ 'target' => 'nothingtoseehere' ] );
	}
}

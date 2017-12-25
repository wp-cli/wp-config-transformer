<?php

use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data = [];
	protected static $string_data = [];
	protected static $constants = [];
	protected static $variables = [];

	public static function setUpBeforeClass()
	{
		self::$test_config_path = __DIR__ . '/wp-config-test-update.php';
		file_put_contents( self::$test_config_path, "<?php\n\n" );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );

		self::$raw_data    = explode( "\n", file_get_contents( __DIR__ . '/bin/raw-data.txt' ) );
		self::$string_data = explode( "\n", file_get_contents( __DIR__ . '/bin/string-data.txt' ) );
	}

	public static function tearDownAfterClass()
	{
//		unlink( self::$test_config_path );
	}

	public function testUpdateRawConstants()
	{
		foreach ( self::$raw_data as $i => $data ) {
			$this->assertFalse( self::$config_transformer->exists( 'constant', "TEST_UPDATE_RAW_{$i}" ) );
			$this->assertTrue( self::$config_transformer->add( 'constant', "TEST_UPDATE_RAW_{$i}", 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'constant', "TEST_UPDATE_RAW_{$i}" ) );
			$this->assertTrue( self::$config_transformer->update( 'constant', "TEST_UPDATE_RAW_{$i}", $data, [ 'raw' => true ] ) );
		}
	}

	public function testUpdateStringConstants()
	{
		foreach ( self::$string_data as $i => $data ) {
			$this->assertFalse( self::$config_transformer->exists( 'constant', "TEST_UPDATE_STRING_{$i}" ) );
			$this->assertTrue( self::$config_transformer->add( 'constant', "TEST_UPDATE_STRING_{$i}", 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'constant', "TEST_UPDATE_STRING_{$i}" ) );
			$this->assertTrue( self::$config_transformer->update( 'constant', "TEST_UPDATE_STRING_{$i}", $data ) );
		}
	}

	public function testUpdateRawVariables()
	{
		foreach ( self::$raw_data as $i => $data ) {
			$this->assertFalse( self::$config_transformer->exists( 'variable', "test_update_raw_{$i}" ) );
			$this->assertTrue( self::$config_transformer->add( 'variable', "test_update_raw_{$i}", 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'variable', "test_update_raw_{$i}" ) );
			$this->assertTrue( self::$config_transformer->update( 'variable', "test_update_raw_{$i}", $data, [ 'raw' => true ] ) );
		}
	}

	public function testUpdateStringVariables()
	{
		foreach ( self::$string_data as $i => $data ) {
			$this->assertFalse( self::$config_transformer->exists( 'variable', "test_update_string_{$i}" ) );
			$this->assertTrue( self::$config_transformer->add( 'variable', "test_update_string_{$i}", 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ) );
			$this->assertTrue( self::$config_transformer->exists( 'variable', "test_update_string_{$i}" ) );
			$this->assertTrue( self::$config_transformer->update( 'variable', "test_update_string_{$i}", $data ) );
		}
	}

	public function testConstantAddIfMissing()
	{
		$this->assertFalse( self::$config_transformer->exists( 'constant', 'TEST_UPDATE_ADD_MISSING' ) );
		$this->assertTrue( self::$config_transformer->update( 'constant', 'TEST_UPDATE_ADD_MISSING', 'foo', [ 'target' => '<?php', 'placement' => 'after' ] ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'TEST_UPDATE_ADD_MISSING' ) );
	}

	public function testVariableAddIfMissing()
	{
		$this->assertFalse( self::$config_transformer->exists( 'variable', 'test_update_add_missing' ) );
		$this->assertTrue( self::$config_transformer->update( 'variable', 'test_update_add_missing', 'bar', [ 'target' => '<?php', 'placement' => 'after' ] ) );
		$this->assertTrue( self::$config_transformer->exists( 'variable', 'test_update_add_missing' ) );
	}

	public function testConfigValues()
	{
		require_once self::$test_config_path;

		foreach ( self::$raw_data as $i => $data ) {
			// Convert string to a real value.
			eval( "\$data = $data;" );
			// Raw Constants
			$this->assertTrue( defined( "TEST_UPDATE_RAW_{$i}" ), "TEST_UPDATE_RAW_{$i}" );
			$this->assertNotEquals( 'oldvalue', constant( "TEST_UPDATE_RAW_{$i}" ), "TEST_UPDATE_RAW_{$i}" );
			$this->assertEquals( $data, constant( "TEST_UPDATE_RAW_{$i}" ), "TEST_UPDATE_RAW_{$i}" );
			// Raw Variables
			$this->assertTrue( ( isset( ${"test_update_raw_" . $i} ) || is_null( ${"test_update_raw_" . $i} ) ), "\$test_update_raw_{$i}" );
			$this->assertNotEquals( 'oldvalue', ${"test_update_raw_" . $i}, "test_update_raw_{$i}" );
			$this->assertEquals( $data, ${"test_update_raw_" . $i}, "test_update_raw_{$i}" );
		}

		foreach ( self::$string_data as $i => $data ) {
			// String Constants
			$this->assertTrue( defined( "TEST_UPDATE_STRING_{$i}" ), "TEST_UPDATE_STRING_{$i}" );
			$this->assertNotEquals( 'oldvalue', constant( "TEST_UPDATE_STRING_{$i}" ), "TEST_UPDATE_STRING_{$i}" );
			$this->assertEquals( $data, constant( "TEST_UPDATE_STRING_{$i}" ), "TEST_UPDATE_STRING_{$i}" );
			// String Variables
			$this->assertTrue( ( isset( ${"test_update_string_" . $i} ) || is_null( ${"test_update_string_" . $i} ) ), "\$test_update_string_{$i}" );
			$this->assertNotEquals( 'oldvalue', ${"test_update_string_" . $i}, "test_update_string_{$i}" );
			$this->assertEquals( $data, ${"test_update_string_" . $i}, "test_update_string_{$i}" );
		}

		$this->assertTrue( defined( 'TEST_UPDATE_ADD_MISSING' ), 'TEST_UPDATE_ADD_MISSING' );
		$this->assertEquals( 'foo', constant( 'TEST_UPDATE_ADD_MISSING' ), 'TEST_UPDATE_ADD_MISSING' );

		$this->assertTrue( ( isset( $test_update_add_missing ) || is_null( $test_update_add_missing ) ), '$test_update_add_missing' );
		$this->assertEquals( 'bar', $test_update_add_missing, '$test_update_add_missing' );
	}
}

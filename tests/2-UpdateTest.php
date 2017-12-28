<?php

use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
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

		self::$test_config_path = __DIR__ . '/wp-config-test-update.php';
		file_put_contents( self::$test_config_path, "<?php\n\n" );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tearDownAfterClass()
	{
		unlink( self::$test_config_path );
	}

	public function testUpdateRawConstants()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_RAW_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data, [ 'raw' => true ] ), $name );
		}
	}

	public function testUpdateStringConstants()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "TEST_CONST_UPDATE_STRING_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->add( 'constant', $name, 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ), $name );
			$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
			$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data ), $name );
		}
	}

	public function testUpdateRawVariables()
	{
		foreach ( self::$raw_data as $d => $data ) {
			$name = "test_var_update_raw_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data, [ 'raw' => true ] ), "\${$name}" );
		}
	}

	public function testUpdateStringVariables()
	{
		foreach ( self::$string_data as $d => $data ) {
			$name = "test_var_update_string_{$d}";
			$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->add( 'variable', $name, 'oldvalue', [ 'target' => '<?php', 'placement' => 'after' ] ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
			$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data ), "\${$name}" );
		}
	}

	public function testConstantAddIfMissing()
	{
		$name = 'TEST_CONST_UPDATE_ADD_MISSING';
		$this->assertFalse( self::$config_transformer->exists( 'constant', $name ), $name );
		$this->assertTrue( self::$config_transformer->update( 'constant', $name, 'foo', [ 'target' => '<?php', 'placement' => 'after' ] ), $name );
		$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
	}

	public function testVariableAddIfMissing()
	{
		$name = 'test_var_update_add_missing';
		$this->assertFalse( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->update( 'variable', $name, 'bar', [ 'target' => '<?php', 'placement' => 'after' ] ), "\${$name}" );
		$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
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
	}
}

<?php

use PHPUnit\Framework\TestCase;

class BlockTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data = array();
	protected static $string_data = array();
	protected static $block_data = array();

	public static function setUpBeforeClass()
	{
		self::$raw_data    = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data.txt' ) );
		self::$string_data = explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/string-data.txt' ) );

		if ( version_compare( PHP_VERSION, '7.0', '>=' ) ) {
			self::$raw_data = array_merge( self::$raw_data, explode( PHP_EOL, file_get_contents( __DIR__ . '/fixtures/raw-data-extra.txt' ) ) );
		}

		$block_data = explode( PHP_EOL . '---' . PHP_EOL, file_get_contents( __DIR__ . '/fixtures/block-data.txt' ) );

		self::$block_data['constant'] = array_values( array_filter( $block_data, function ( $v ) {
			return ( false !== strpos( $v, 'TEST_CONST_#' ) );
		} ) );

		self::$block_data['variable'] = array_values( array_filter( $block_data, function ( $v ) {
			return ( false !== strpos( $v, 'test_var_#' ) );
		} ) );

		$contents = '<?php' . PHP_EOL . PHP_EOL;

		foreach ( self::$block_data['constant'] as $b => $block ) {
			foreach ( self::$raw_data as $d => $data ) {
				$contents .= str_replace( 'TEST_CONST_#', "TEST_CONST_BLOCK_{$b}_RAW_{$d}", $block ) . PHP_EOL;
			}
			foreach ( self::$string_data as $d => $data ) {
				$contents .= str_replace( 'TEST_CONST_#', "TEST_CONST_BLOCK_{$b}_STRING_{$d}", $block ) . PHP_EOL;
			}
		}

		foreach ( self::$block_data['variable'] as $b => $block ) {
			foreach ( self::$raw_data as $d => $data ) {
				$contents .= str_replace( 'test_var_#', "test_var_block_{$b}_raw_{$d}", $block ) . PHP_EOL;
			}
			foreach ( self::$string_data as $d => $data ) {
				$contents .= str_replace( 'test_var_#', "test_var_block_{$b}_string_{$d}", $block ) . PHP_EOL;
			}
		}

		self::$test_config_path = __DIR__ . '/wp-config-test-block.php';
		file_put_contents( self::$test_config_path, $contents );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tearDownAfterClass()
	{
		unlink( self::$test_config_path );
	}

	public function testBlockRawConstants()
	{
		foreach ( self::$block_data['constant'] as $b => $block ) {
			foreach ( self::$raw_data as $d => $data ) {
				$name = "TEST_CONST_BLOCK_{$b}_RAW_{$d}";
				$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
				$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data, array( 'raw' => true ) ), $name );
			}
		}
	}

	public function testBlockStringConstants()
	{
		foreach ( self::$block_data['constant'] as $b => $block ) {
			foreach ( self::$string_data as $d => $data ) {
				$name = "TEST_CONST_BLOCK_{$b}_STRING_{$d}";
				$this->assertTrue( self::$config_transformer->exists( 'constant', $name ), $name );
				$this->assertTrue( self::$config_transformer->update( 'constant', $name, $data ), $name );
			}
		}
	}

	public function testBlockRawVariables()
	{
		foreach ( self::$block_data['variable'] as $b => $block ) {
			foreach ( self::$raw_data as $d => $data ) {
				$name = "test_var_block_{$b}_raw_{$d}";
				$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
				$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data, array( 'raw' => true ) ), "\${$name}" );
			}
		}
	}

	public function testBlockStringVariables()
	{
		foreach ( self::$block_data['variable'] as $b => $block ) {
			foreach ( self::$string_data as $d => $data ) {
				$name = "test_var_block_{$b}_string_{$d}";
				$this->assertTrue( self::$config_transformer->exists( 'variable', $name ), "\${$name}" );
				$this->assertTrue( self::$config_transformer->update( 'variable', $name, $data ), "\${$name}" );
			}
		}
	}

	public function testConfigValues()
	{
		require_once self::$test_config_path;

		// Constants
		foreach ( self::$block_data['constant'] as $b => $block ) {
			// Raw
			foreach ( self::$raw_data as $d => $data ) {
				eval( "\$data = $data;" ); // Convert string to a real value.
				$name = "TEST_CONST_BLOCK_{$b}_RAW_{$d}";
				$this->assertTrue( defined( $name ), $name );
				$this->assertNotEquals( 'oldvalue', constant( $name ), $name );
				$this->assertEquals( $data, constant( $name ), $name );
			}
			// Strings
			foreach ( self::$string_data as $d => $data ) {
				$name = "TEST_CONST_BLOCK_{$b}_STRING_{$d}";
				$this->assertTrue( defined( $name ), $name );
				$this->assertNotEquals( 'oldvalue', constant( $name ), $name );
				$this->assertEquals( $data, constant( $name ), $name );
			}
		}

		// Variables
		foreach ( self::$block_data['variable'] as $b => $block ) {
			// Raw
			foreach ( self::$raw_data as $d => $data ) {
				eval( "\$data = $data;" ); // Convert string to a real value.
				$name = "test_var_block_{$b}_raw_{$d}";
				$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
				$this->assertNotEquals( 'oldvalue', ${$name}, "\${$name}" );
				$this->assertEquals( $data, ${$name}, "\${$name}" );
			}
			// Strings
			foreach ( self::$string_data as $d => $data ) {
				$name = "test_var_block_{$b}_string_{$d}";
				$this->assertTrue( ( isset( ${$name} ) || is_null( ${$name} ) ), "\${$name}" );
				$this->assertNotEquals( 'oldvalue', ${$name}, "\${$name}" );
				$this->assertEquals( $data, ${$name}, "\${$name}" );
			}
		}
	}
}

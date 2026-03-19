<?php

use WP_CLI\Tests\TestCase;

/**
 * Tests for the `defined( 'CONST' ) or define( 'CONST', value )` pattern.
 */
class DefinedOrTest extends TestCase {

	protected static $test_config_path;
	protected static $config_transformer;

	public static function set_up_before_class() {
		self::$test_config_path = __DIR__ . '/wp-config-test-defined-or.php';

		$contents  = '<?php' . PHP_EOL;
		$contents .= "defined( 'DB_NAME' )     or define( 'DB_NAME', 'test_db' );" . PHP_EOL;
		$contents .= "defined( 'DB_USER' )     or define( 'DB_USER', 'wp' );" . PHP_EOL;
		$contents .= "defined( 'DB_PASSWORD' ) or define( 'DB_PASSWORD', 'secret' );" . PHP_EOL;
		$contents .= "defined( 'DB_HOST' )     || define( 'DB_HOST', 'localhost' );" . PHP_EOL;
		$contents .= "defined('DB_CHARSET')    || define('DB_CHARSET', 'utf8');" . PHP_EOL;

		file_put_contents( self::$test_config_path, $contents );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}

	public static function tear_down_after_class() {
		unlink( self::$test_config_path );
	}

	public function testExistsWithOrPattern() {
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_NAME' ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_USER' ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_PASSWORD' ) );
	}

	public function testExistsWithDoubleBarPattern() {
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_HOST' ) );
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_CHARSET' ) );
	}

	public function testGetValueWithOrPattern() {
		$this->assertSame( "'test_db'", self::$config_transformer->get_value( 'constant', 'DB_NAME' ) );
		$this->assertSame( "'wp'", self::$config_transformer->get_value( 'constant', 'DB_USER' ) );
		$this->assertSame( "'secret'", self::$config_transformer->get_value( 'constant', 'DB_PASSWORD' ) );
	}

	public function testGetValueWithDoubleBarPattern() {
		$this->assertSame( "'localhost'", self::$config_transformer->get_value( 'constant', 'DB_HOST' ) );
		$this->assertSame( "'utf8'", self::$config_transformer->get_value( 'constant', 'DB_CHARSET' ) );
	}

	public function testUpdateWithOrPattern() {
		$this->assertTrue( self::$config_transformer->update( 'constant', 'DB_USER', 'newuser' ) );
		$this->assertSame( "'newuser'", self::$config_transformer->get_value( 'constant', 'DB_USER' ) );

		$contents = file_get_contents( self::$test_config_path );
		$this->assertStringContainsString( "defined( 'DB_USER' )     or define( 'DB_USER', 'newuser' );", $contents );
	}

	public function testUpdateWithDoubleBarPattern() {
		$this->assertTrue( self::$config_transformer->update( 'constant', 'DB_HOST', '127.0.0.1' ) );
		$this->assertSame( "'127.0.0.1'", self::$config_transformer->get_value( 'constant', 'DB_HOST' ) );

		$contents = file_get_contents( self::$test_config_path );
		$this->assertStringContainsString( "defined( 'DB_HOST' )     || define( 'DB_HOST', '127.0.0.1' );", $contents );
	}

	public function testNormalizeUpdateWithOrPattern() {
		$this->assertTrue(
			self::$config_transformer->update(
				'constant',
				'DB_NAME',
				'normalized_db',
				array( 'normalize' => true )
			)
		);
		$this->assertSame( "'normalized_db'", self::$config_transformer->get_value( 'constant', 'DB_NAME' ) );
	}

	public function testRemoveWithOrPattern() {
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_PASSWORD' ) );
		$this->assertTrue( self::$config_transformer->remove( 'constant', 'DB_PASSWORD' ) );
		$this->assertFalse( self::$config_transformer->exists( 'constant', 'DB_PASSWORD' ) );

		$contents = file_get_contents( self::$test_config_path );
		$this->assertStringNotContainsString( "define( 'DB_PASSWORD'", $contents );
		$this->assertStringNotContainsString( "defined( 'DB_PASSWORD'", $contents );
	}

	public function testRemoveWithDoubleBarPattern() {
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_CHARSET' ) );
		$this->assertTrue( self::$config_transformer->remove( 'constant', 'DB_CHARSET' ) );
		$this->assertFalse( self::$config_transformer->exists( 'constant', 'DB_CHARSET' ) );

		$contents = file_get_contents( self::$test_config_path );
		$this->assertStringNotContainsString( "define( 'DB_CHARSET'", $contents );
		$this->assertStringNotContainsString( "defined( 'DB_CHARSET'", $contents );
	}
}

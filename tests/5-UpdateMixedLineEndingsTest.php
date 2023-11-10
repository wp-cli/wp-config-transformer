<?php

use WP_CLI\Tests\TestCase;

class UpdateMixedLineEndingsTest extends TestCase {
	private static $test_config_path;
	private static $test_config_lines;
	private static $config_transformer;
	public static function set_up_before_class() {
		self::$test_config_lines = array(
			"<?php\n",
			"// this is a demo\r\n",
			"\r\n",
			"\r\n",
			"define( 'DB_NAME', '' );\n",
			"define( 'DB_HOST', '' );\r\n",
			"define( 'DB_USER', '' );\n\r",
			"\r\n",
			"\n\r",
			"\r",
			"\r",
			"\r\n",
			"define( 'DB_COLLATE', '');\n",
			"\n\r",
			"\n\r",
			"\r",
			"\r",
		);
		self::$test_config_path  = tempnam( sys_get_temp_dir(), 'wp-config' );
		file_put_contents( self::$test_config_path, implode( '', self::$test_config_lines ) );
		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}
	public static function tear_down_after_class() {
		unlink( self::$test_config_path );
	}
	public function testMixedLineEndingsAreNormalized() {
		$this->assertTrue( self::$config_transformer->update( 'constant', 'DB_HOST', 'demo' ) );
		$this->assertTrue( self::$config_transformer->update( 'constant', 'DB_HOST', '' ) );

		$modified_config_lines = file( self::$test_config_path );
		$this->assertSame( array_map( 'trim', self::$test_config_lines ), array_map( 'trim', $modified_config_lines ) );
	}
}

<?php

use WP_CLI\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConcatenationTest extends TestCase {

	protected static $config_path;
	protected static $config_transformer;

	public static function set_up_before_class() {
		self::$config_path = __DIR__ . '/wp-config-test-concat.php';
		copy( __DIR__ . '/fixtures/wp-config-concat.php', self::$config_path );
		self::$config_transformer = new WPConfigTransformer( self::$config_path );
	}

	public static function tear_down_after_class() {
		unlink( self::$config_path );
	}

	public static function existsProvider() {
		return array(
			'concatenation variable itself'           => array( 'variable', 'do_redirect' ),
			'variable after concatenation'            => array( 'variable', 'table_prefix' ),
			'constant after concatenation variable'   => array( 'constant', 'DB_NAME' ),
			'constant with multiline string value'    => array( 'constant', 'CUSTOM_CSS' ),
			'variable after multiline string value'   => array( 'variable', 'after_multiline' ),
			'multiline concatenation variable'        => array( 'variable', 'long_url' ),
			'constant with multiline raw value'       => array( 'constant', 'ALLOWED_HOSTS' ),
			'variable after multiline raw define'     => array( 'variable', 'after_array_define' ),
			'backslash-newline in quoted string'      => array( 'variable', 'backslash_newline' ),
			'variable after backslash-newline string' => array( 'variable', 'after_backslash_newline' ),
		);
	}

	/**
	 * @dataProvider existsProvider
	 */
	#[DataProvider( 'existsProvider' )] // phpcs:ignore PHPCompatibility.Attributes.NewAttributes.PHPUnitAttributeFound
	public function testExists( $type, $name ) {
		$label = ( 'variable' === $type ) ? "\${$name}" : $name;
		$this->assertTrue( self::$config_transformer->exists( $type, $name ), "{$label} should be found" );
	}

	public function testVariableAfterConcatenationHasCorrectValue() {
		$this->assertSame( "'wp_'", self::$config_transformer->get_value( 'variable', 'table_prefix' ) );
	}
}

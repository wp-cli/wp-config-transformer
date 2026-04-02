<?php

use WP_CLI\Tests\TestCase;

class ConcatenationTest extends TestCase {

	protected static $config_path;
	protected static $config_transformer;

	public static function set_up_before_class() {
		self::$config_path = __DIR__ . '/fixtures/wp-config-test-concat.php';
		copy( __DIR__ . '/fixtures/wp-config-concat.php', self::$config_path );
		self::$config_transformer = new WPConfigTransformer( self::$config_path );
	}

	public static function tear_down_after_class() {
		unlink( self::$config_path );
	}

	public function testVariableAfterConcatenationAssignmentExists() {
		$this->assertTrue( self::$config_transformer->exists( 'variable', 'table_prefix' ), '$table_prefix should be found after a concatenation assignment' );
	}

	public function testConcatenationVariableItselfExists() {
		$this->assertTrue( self::$config_transformer->exists( 'variable', 'do_redirect' ), '$do_redirect should be found' );
	}

	public function testConstantAfterConcatenationAssignmentExists() {
		$this->assertTrue( self::$config_transformer->exists( 'constant', 'DB_NAME' ), 'DB_NAME should be found after a concatenation variable' );
	}

	public function testVariableAfterConcatenationHasCorrectValue() {
		$this->assertSame( "'wp_'", self::$config_transformer->get_value( 'variable', 'table_prefix' ), '$table_prefix value should be wp_' );
	}
}

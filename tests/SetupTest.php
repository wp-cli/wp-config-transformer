<?php

use WP_CLI\Tests\TestCase;

class SetupTest extends TestCase {

	public function testFileMissing() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'wp-config-missing.php does not exist.' );
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-missing.php' );
	}

	public function testFileNotWritable() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'wp-config-not-writable.php is not writable.' );
		chmod( __DIR__ . '/fixtures/wp-config-not-writable.php', 0444 );
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-not-writable.php' );
	}

	public function testFileEmpty() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Config file is empty.' );
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-empty.php' );
		$config_transformer->exists( 'foo', 'bar' );
	}

	public function testFileNoConfigType() {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Config type \'foo\' does not exist.' );
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-example.php' );
		$config_transformer->exists( 'foo', 'bar' );
	}
}

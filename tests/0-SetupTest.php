<?php

use PHPUnit\Framework\TestCase;

class SetupTest extends TestCase
{
	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage wp-config.php file does not exist.
	 */
	public function testFileMissing()
	{
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-missing.php' );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage wp-config.php file is not writable.
	 */
	public function testFileNotWritable()
	{
		chmod( __DIR__ . '/fixtures/wp-config-not-writable.php', 0444 );
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-not-writable.php' );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage wp-config.php file is empty.
	 */
	public function testFileEmpty()
	{
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-empty.php' );
		$config_transformer->exists( 'foo', 'bar' );
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage Config type 'foo' does not exist.
	 */
	public function testFileNoConfigType()
	{
		$config_transformer = new WPConfigTransformer( __DIR__ . '/fixtures/wp-config-example.php' );
		$config_transformer->exists( 'foo', 'bar' );
	}
}

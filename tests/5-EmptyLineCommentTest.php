<?php

use WP_CLI\Tests\TestCase;

/**
 * In PHP 8.0, 8.1, 8.2, `parse_wp_config` failed to parse string constant values that contain double-slashes
 * when there are empty line comments in wp-config.
 *
 * See: https://github.com/wp-cli/wp-config-transformer/issues/47
 */
class EmptyLineCommentTest extends TestCase {

	protected static $test_config_path;
	protected static $config_transformer;

	public static function set_up_before_class() {
		self::$test_config_path = __DIR__ . '/wp-config-test-empty-line-comment.php';

		file_put_contents(
			self::$test_config_path,
			<<<EOF
<?php
// Empty Line Comment
// See: https://github.com/wp-cli/wp-config-transformer/issues/47
//
define( 'WP_HOME', 'https://wordpress.org' );
EOF
		);

		self::$config_transformer = new WPConfigTransformer( self::$test_config_path );
	}


	public static function tear_down_after_class() {
		unlink( self::$test_config_path );
	}

	public function testConfigValues() {
		self::$config_transformer->update( 'constant', 'WP_HOME', 'https://wordpress.com' );

		require_once self::$test_config_path;

		$this->assertNotSame( 'https://wordpress.org', constant( 'WP_HOME' ), 'WP_HOME still contains the original value (https://wordpress.org).' );
		$this->assertEquals( 'https://wordpress.com', constant( 'WP_HOME' ), 'WP_HOME was not updated to the new value (https://wordpress.com).' );
	}
}

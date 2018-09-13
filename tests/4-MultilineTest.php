<?php

use PHPUnit\Framework\TestCase;

class MultilineTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;
	protected static $raw_data = array();
	protected static $string_data = array();

    public function testConfigValues()
    {
        self::$test_config_path = __DIR__ . '/wp-config-test-update.php';

        /* The // at the end of the first line causes parse_wp_config to
           have a space prefixing the second line, which causes the preg_replace
           in update to fail.
           Fixed in ad435a7
        */
        file_put_contents( self::$test_config_path, <<<EOF
<?php
define('WP_CACHE', true); //
define('DB_NAME', 'oldvalue');
EOF
);

        $config_transformer = new WPConfigTransformer( self::$test_config_path );

        $config_transformer->update('constant', 'DB_NAME', 'newvalue');

        require_once self::$test_config_path;

        $this->assertTrue( defined( 'DB_NAME' ), 'DB_NAME not defined');
        $this->assertNotEquals( 'oldvalue', constant( 'DB_NAME' ), 'DB_NAME is still "oldvalue"' );
        $this->assertEquals( 'newvalue', constant( 'DB_NAME' ), 'DB_NAME is not "newvalue"');

        unlink( self::$test_config_path );
    }
}

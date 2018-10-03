<?php

use PHPUnit\Framework\TestCase;

class MultilineTest extends TestCase
{
	protected static $test_config_path;
	protected static $config_transformer;

    public static function setUpBeforeClass() {
        self::$test_config_path = __DIR__ . '/wp-config-test-multiline.php';
        /* The // at the end of the first line causes parse_wp_config to
           have a space prefixing the second line, which causes the preg_replace
           in update to fail.
           Fixed in ad435a7
        */
        file_put_contents( self::$test_config_path, <<<EOF
<?php
define('FIRST_CONSTANT', true); //
define('SECOND_CONSTANT', 'oldvalue');
EOF
);
        self::$config_transformer = new WPConfigTransformer( self::$test_config_path );

    }


    public static function tearDownAfterClass() {
        unlink( self::$test_config_path );
    }

    public function testConfigValues()
    {
        self::$config_transformer->update('constant', 'SECOND_CONSTANT', 'newvalue');

        require_once self::$test_config_path;

        $this->assertTrue( defined( 'SECOND_CONSTANT' ), 'SECOND_CONSTANT not defined');
        $this->assertNotEquals( 'oldvalue', constant( 'SECOND_CONSTANT' ), 'SECOND_CONSTANT is still "oldvalue"' );
        $this->assertEquals( 'newvalue', constant( 'SECOND_CONSTANT' ), 'SECOND_CONSTANT is not "newvalue"');

    }
}

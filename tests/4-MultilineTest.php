<?php

use PHPUnit\Framework\TestCase;

class MultilineTest extends TestCase
{
    public function testConfigValues()
    {
        $test_config_path = __DIR__ . '/wp-config-test-update.php';

        /* The // at the end of the first line causes parse_wp_config to
           have a space prefixing the second line, which causes the preg_replace
           in update to fail.
           Fixed in ad435a7
        */
        file_put_contents( $test_config_path, <<<EOF
<?php
define('WP_CACHE', true); //
define('DB_NAME', 'oldvalue');
EOF
);

        $config_transformer = new WPConfigTransformer( $test_config_path );

        $config_transformer->update('constant', 'DB_NAME', 'newvalue');

        require_once $test_config_path;

        $this->assertTrue( defined( 'DB_NAME' ), 'DB_NAME not defined');
        $this->assertNotEquals( 'oldvalue', constant( 'DB_NAME' ), 'DB_NAME is still "oldvalue"' );
        $this->assertEquals( 'newvalue', constant( 'DB_NAME' ), 'DB_NAME is not "newvalue"');

        unlink( $test_config_path );
    }
}

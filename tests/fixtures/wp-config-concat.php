<?php
$do_redirect  = 'https://example.com' . $_SERVER['REQUEST_URI'];
$table_prefix = 'wp_';
define( 'DB_NAME', 'test_db' );
define( 'CUSTOM_CSS', 'body {
  color: red;
}' );
$after_multiline = 'found';
$long_url = 'https://example.com'
  . '/path';
define( 'ALLOWED_HOSTS', array(
  'example.com',
) );
$after_array_define = 'found';

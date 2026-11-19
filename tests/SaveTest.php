<?php

use WP_CLI\Tests\TestCase;

class SaveTest extends TestCase {

	protected $test_dir;
	protected $test_file;

	public function set_up() {
		parent::set_up();
		$this->test_dir = sys_get_temp_dir() . '/wp_config_test_' . uniqid();
		mkdir( $this->test_dir );
		$this->test_file = $this->test_dir . '/wp-config.php';
		file_put_contents( $this->test_file, "<?php\ndefine( 'DB_NAME', 'test_db' );\n" );
	}

	public function tear_down() {
		parent::tear_down();
		if ( file_exists( $this->test_file ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@chmod( $this->test_dir, 0777 );
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@chmod( $this->test_file, 0666 );
			@unlink( $this->test_file );
		}
		if ( is_dir( $this->test_dir ) ) {
			@rmdir( $this->test_dir );
		}
	}

	public function testAtomicSavePreservesPermissions() {
		chmod( $this->test_file, 0640 );
		$transformer = new WPConfigTransformer( $this->test_file );
		$this->assertTrue( $transformer->update( 'constant', 'DB_NAME', 'new_db' ) );

		$this->assertStringContainsString( 'new_db', file_get_contents( $this->test_file ) );
		$this->assertSame( 0640, fileperms( $this->test_file ) & 0777 );
	}

	public function testSaveSymlinkPreservesSymlink() {
		$link_file = $this->test_dir . '/wp-config-link.php';
		symlink( $this->test_file, $link_file );

		$transformer = new WPConfigTransformer( $link_file );
		$this->assertTrue( $transformer->update( 'constant', 'DB_NAME', 'symlink_db' ) );

		$this->assertTrue( is_link( $link_file ) );
		$this->assertStringContainsString( 'symlink_db', file_get_contents( $this->test_file ) );
		$this->assertStringContainsString( 'symlink_db', file_get_contents( $link_file ) );

		unlink( $link_file );
	}

	public function testSaveFallbackWhenDirectoryNotWritable() {
		// Make directory non-writable
		chmod( $this->test_dir, 0555 );

		$transformer = new WPConfigTransformer( $this->test_file );
		$this->assertTrue( $transformer->update( 'constant', 'DB_NAME', 'fallback_db' ) );

		$this->assertStringContainsString( 'fallback_db', file_get_contents( $this->test_file ) );

		chmod( $this->test_dir, 0777 );
	}
}

<?php

/**
 * Transforms a wp-config.php file.
 */
class WPConfigTransformer {

	/**
	 * @var string
	 */
	protected $wp_config_path;

	/**
	 * Instantiate the class with a valid wp-config.php
	 *
	 * @param string $wp_config_path Path to a wp-config.php file.
	 */
	public function __construct( $wp_config_path ) {
		if ( ! file_exists( $wp_config_path ) ) {
			throw new Exception( 'wp-config.php file does not exist.' );
		}
		if ( ! is_writable( $wp_config_path ) ) {
			throw new Exception( 'wp-config.php file is not writable.' );
		}
		$this->wp_config_path = $wp_config_path;
	}

	/**
	 * Add a constant to the wp-config.php file.
	 *
	 * @param string $name  Constant name.
	 * @param mixed  $value Constant value.
	 */
	public function add_constant( $name, $value ) {

	}

	/**
	 * Update an existing constant in the wp-config.php file
	 *
	 * @param string $name  Constant name.
	 * @param mixed  $value Constant value.
	 */
	public function update_constant( $name, $value ) {

	}

	/**
	 * Remove a constant from the wp-config.php file.
	 *
	 * @param string $name Constant name.
	 */
	public function remove_constant( $name, $value ) {

	}

}

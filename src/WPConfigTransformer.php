<?php

/**
 * Transform a wp-config.php file.
 */
class WPConfigTransformer {

	/**
	 * Path to the wp-config.php file.
	 *
	 * @var string
	 */
	protected $wp_config_path;

	/**
	 * Original contents of the wp-config.php file.
	 *
	 * @var string
	 */
	protected $wp_config_src;

	/**
	 * Array of parsed configs.
	 *
	 * @var array
	 */
	protected $wp_configs = [];

	/**
	 * Instantiate the class with a valid wp-config.php.
	 *
	 * @throws Exception If the wp-config.php file is missing.
	 * @throws Exception If the wp-config.php file is not writable.
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
	 * Check whether a config exists in the wp-config.php file.
	 *
	 * @throws Exception If the wp-config.php file is empty.
	 * @throws Exception If the requested config type is invalid.
	 *
	 * @param string $type Config type (constant or variable).
	 * @param string $name Config name.
	 *
	 * @return bool
	 */
	public function exists( $type, $name ) {
		$wp_config_src = file_get_contents( $this->wp_config_path );

		if ( ! trim( $wp_config_src ) ) {
			throw new Exception( 'wp-config.php file is empty.' );
		}

		$this->wp_config_src = $wp_config_src;
		$this->wp_configs    = $this->parse_wp_config( $this->wp_config_src );

		if ( ! isset( $this->wp_configs[ $type ] ) ) {
			throw new Exception( "Config type '{$type}' does not exist." );
		}

		return isset( $this->wp_configs[ $type ][ $name ] );
	}

	/**
	 * Add a config to the wp-config.php file.
	 *
	 * @throws Exception If the config value provided is not a string.
	 * @throws Exception If the config placement target could not be located.
	 *
	 * @param string $type    Config type (constant or variable).
	 * @param string $name    Config name.
	 * @param string $value   Config value.
	 * @param array  $options (optional) Array of special behavior options.
	 *
	 * @return bool
	 */
	public function add( $type, $name, $value, array $options = array() ) {
		if ( ! is_string( $value ) ) {
			throw new Exception( 'Config value must be a string.' );
		}

		if ( $this->exists( $type, $name ) ) {
			return false;
		}

		$defaults = array(
			'raw'       => false, // Display value in raw format without quotes.
			'target'    => "/* That's all, stop editing!", // Config placement target string.
			'buffer'    => PHP_EOL . PHP_EOL, // Buffer between config definition and target string.
			'placement' => 'before', // Config placement direction (insert before or after).
		);

		list( $raw, $target, $buffer, $placement ) = array_values( array_merge( $defaults, $options ) );

		$raw       = (bool) $raw;
		$target    = (string) $target;
		$buffer    = (string) $buffer;
		$placement = (string) $placement;

		if ( false === strpos( $this->wp_config_src, $target ) ) {
			throw new Exception( 'Unable to locate placement target.' );
		}

		$new_src  = $this->normalize( $type, $name, $this->format_value( $value, $raw ) );
		$new_src  = ( 'after' === $placement ) ? $target . $buffer . $new_src : $new_src . $buffer . $target;
		$contents = str_replace( $target, $new_src, $this->wp_config_src );

		return $this->save( $contents );
	}

	/**
	 * Update an existing config in the wp-config.php file.
	 *
	 * @throws Exception If the config value provided is not a string.
	 *
	 * @param string $type    Config type (constant or variable).
	 * @param string $name    Config name.
	 * @param string $value   Config value.
	 * @param array  $options (optional) Array of special behavior options.
	 *
	 * @return bool
	 */
	public function update( $type, $name, $value, array $options = array() ) {
		if ( ! is_string( $value ) ) {
			throw new Exception( 'Config value must be a string.' );
		}

		$defaults = array(
			'add'       => true, // Add the config if missing.
			'raw'       => false, // Display value in raw format without quotes.
			'normalize' => false, // Normalize config output using WP Coding Standards.
		);

		list( $add, $raw, $normalize ) = array_values( array_merge( $defaults, $options ) );

		$add       = (bool) $add;
		$raw       = (bool) $raw;
		$normalize = (bool) $normalize;

		if ( ! $this->exists( $type, $name ) ) {
			return ( $add ) ? $this->add( $type, $name, $value, $options ) : false;
		}

		$old_src   = $this->wp_configs[ $type ][ $name ]['src'];
		$old_value = $this->wp_configs[ $type ][ $name ]['value'];
		$new_value = $this->format_value( $value, $raw );

		if ( $normalize ) {
			$new_src = $this->normalize( $type, $name, $new_value );
		} else {
			$new_parts    = $this->wp_configs[ $type ][ $name ]['parts'];
			$new_parts[1] = str_replace( $old_value, $new_value, $new_parts[1] ); // Only edit the value part.
			$new_src      = implode( '', $new_parts );
		}

		$contents = preg_replace( sprintf( '/^%s/m', preg_quote( $old_src, '/' ) ), $new_src, $this->wp_config_src );

		return $this->save( $contents );
	}

	/**
	 * Remove a config from the wp-config.php file.
	 *
	 * @param string $type Config type (constant or variable).
	 * @param string $name Config name.
	 *
	 * @return bool
	 */
	public function remove( $type, $name ) {
		if ( ! $this->exists( $type, $name ) ) {
			return false;
		}

		$pattern  = sprintf( '/^%s\s*(\S|$)/m', preg_quote( $this->wp_configs[ $type ][ $name ]['src'], '/' ) );
		$contents = preg_replace( $pattern, '$1', $this->wp_config_src );

		return $this->save( $contents );
	}

	/**
	 * Format a string for config value output.
	 *
	 * @throws Exception When a raw value is requested for an empty string.
	 *
	 * @param string $value Config value.
	 * @param bool   $raw   Display value in raw format without quotes.
	 *
	 * @return mixed
	 */
	protected function format_value( $value, $raw ) {
		if ( $raw && '' === trim( $value ) ) {
			throw new Exception( 'Raw value for empty string not supported.' );
		}

		return ( $raw ) ? $value : var_export( $value, true );
	}

	/**
	 * Return normalized source for a name/value pair.
	 *
	 * @throws Exception If the requested config type does not support normalization.
	 *
	 * @param string $type  Config type (constant or variable).
	 * @param string $name  Config name.
	 * @param mixed  $value Config value.
	 *
	 * @return string
	 */
	protected function normalize( $type, $name, $value ) {
		if ( 'constant' === $type ) {
			$placeholder = "define( '%s', %s );";
		} elseif ( 'variable' === $type ) {
			$placeholder = '$%s = %s;';
		} else {
			throw new Exception( "Unable to normalize config type '{$type}'." );
		}

		return sprintf( $placeholder, $name, $value );
	}

	/**
	 * Parse config source and return an array.
	 *
	 * @param string $src Config file source.
	 *
	 * @return array
	 */
	protected function parse_wp_config( $src ) {
		$configs             = [];
		$configs['constant'] = [];
		$configs['variable'] = [];

		preg_match_all( '/^(\h*define\s*\(\s*[\'"](\w*?)[\'"]\s*)(,\s*(.*?)\s*)((?:,\s*(?:true|false)\s*)?\)\s*;)/ims', $src, $constants );
		preg_match_all( '/^(\h*\$(\w+)\s*=)(\s*(.*?)\s*;)/ims', $src, $variables );

		if ( ! empty( $constants[0] ) && ! empty( $constants[1] ) && ! empty( $constants[2] ) && ! empty( $constants[3] ) && ! empty( $constants[4] ) && ! empty( $constants[5] ) ) {
			foreach ( $constants[2] as $index => $name ) {
				$configs['constant'][ $name ] = array(
					'src'   => $constants[0][ $index ],
					'value' => $constants[4][ $index ],
					'parts' => array(
						$constants[1][ $index ],
						$constants[3][ $index ],
						$constants[5][ $index ],
					),
				);
			}
		}

		if ( ! empty( $variables[0] ) && ! empty( $variables[1] ) && ! empty( $variables[2] ) && ! empty( $variables[3] ) && ! empty( $variables[4] ) ) {
			// Remove duplicate(s), last definition wins.
			$variables[2] = array_reverse( array_unique( array_reverse( $variables[2], true ) ), true );
			foreach ( $variables[2] as $index => $name ) {
				$configs['variable'][ $name ] = array(
					'src'   => $variables[0][ $index ],
					'value' => $variables[4][ $index ],
					'parts' => array(
						$variables[1][ $index ],
						$variables[3][ $index ],
					),
				);
			}
		}

		return $configs;
	}

	/**
	 * Save the wp-config.php file with new contents.
	 *
	 * @throws Exception If the config file content provided is empty.
	 * @throws Exception If there is a failure when saving the wp-config.php file.
	 *
	 * @param string $contents New config contents.
	 *
	 * @return bool
	 */
	protected function save( $contents ) {
		if ( ! trim( $contents ) ) {
			throw new Exception( 'Cannot save the wp-config.php file with empty contents.' );
		}

		if ( $contents === $this->wp_config_src ) {
			return false;
		}

		$result = file_put_contents( $this->wp_config_path, $contents, LOCK_EX );

		if ( false === $result ) {
			throw new Exception( 'Failed to update the wp-config.php file.' );
		}

		return true;
	}

}

# WP Config Transformer

Programmatically edit a `wp-config.php` file.

## Basic usage

### Instantiate

```php
$config_transformer = new WPConfigTransformer( '/path/to/wp-config.php' );
```

### Transform constants

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', [ 'raw' => true ] );
$config_transformer->add( 'constant', 'MY_SPECIAL_CONFIG', 'foo' );
$config_transformer->remove( 'constant', 'MY_SPECIAL_CONFIG' );
```

### Transform variables

```php
$config_transformer->update( 'variable', 'table_prefix', 'wp_custom_' );
$config_transformer->add( 'variable', 'my_special_global', 'foo' );
$config_transformer->remove( 'variable', 'my_special_global' );
```

### Check for existence

```php
if ( $config_transformer->exists( 'constant', 'MY_SPECIAL_CONFIG' ) ) {
	// do stuff
}

if ( $config_transformer->exists( 'variable', 'my_special_global' ) ) {
	// do stuff
}
```

## How it works

### Parsing configs

Constants: https://regex101.com/r/6AeNGP/1
Variables: https://regex101.com/r/cSLZZz/1

### Editing in place

Due to the unsemantic nature of the `wp-config.php` file, and PHP's loose syntax in general, the WP Config Transformer takes an "edit in place" strategy in order to preserve the original formatting and whatever other obscurities may be taking place in the block. After all, we only care about transforming values, not constant or variable names.

To achieve this, the following steps are performed:

1. A PHP block containing a config is split into distinct parts.
2. Only the part containing the config value is targeted for replacement.
3. The parts are reassembled with the new value in place.
4. The old PHP block is replaced with the new PHP block.

Consider the following horrifically-valid PHP block, that also happens to be using the optional (and rare) 3rd argument for constant case-sensitivity:

```php
                 define   (    'WP_DEBUG'   ,
    false, false     )
;
```

The "edit in place" strategy means that running:

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', [ 'raw' => true ] );
```

Will give us a result that safely changes _only_ the value, leaving the formatting and additional argument(s) unscathed:

```php
                 define   (    'WP_DEBUG'   ,
    true, false     )
;
```

## Options

Special behaviors when adding or updating configs are available using the options array.

### Normalization

In contrast to the "edit in place" strategy above, there is the option to normalize the output during a config update and effectively replace the existing syntax with output that adheres to WP Coding Standards.

Let's reconsider a poorly-formatted example:

```php
                 define   (    'WP_DEBUG'   ,
    false, false     )
;
```

This time running:

```php
$config_transformer->update( 'constant', 'WP_DEBUG', 'true', [ 'raw' => true, 'normalize' => true ] );
```

Now we will get an output of:

```php
define( 'WP_DEBUG', true );
```

Noice!

### Raw format

Suppose you want to change your `ABSPATH` config _(gasp!)_. To do that, we can run:

```php
$config_transformer->update( 'constant', 'ABSPATH', "dirname( __FILE__ ) . '/somewhere/else/'", [ 'raw' => true ] );
```

The `raw` option means that instead of placing the value inside the config as a string `"dirname( __FILE__ ) . '/somewhere/else/'"` it will become unquoted (and executable) syntax `dirname( __FILE__ ) . '/somewhere/else/'`.

### Placement target

TODO

```php
$config_transformer->update( 'constant', 'FOO', 'bar', [ 'target' => '/** Absolute path to the WordPress directory', 'placement' => 'before', 'buffer' => "\n\n" ] );
```

## Running tests

TODO

## Known issues

TODO

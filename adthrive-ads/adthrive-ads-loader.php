<?php
/**
 * Loader Class
 *
 * @package AdThrive Ads
 */

if ( ! function_exists( 'adthrive_ads_autoload' ) ) {
	/**
	 * After registering this autoload function with SPL, the following line
	 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
	 * from /path/to/project/src/Baz/Qux.php:
	 *
	 * @param String $class The fully-qualified class name.
	 * @return void
	 */
	function adthrive_ads_autoload( $class ) {
		// project-specific namespace prefix
		$prefix = 'AdThrive_Ads\\';

		// base directory for the namespace prefix
		$base_dir = ADTHRIVE_ADS_PATH;

		$len = strlen( $prefix );

		// does the class use the namespace prefix?
		if ( 0 !== strncmp( $prefix, $class, $len ) ) {
			return;
		}

		// Default to the root folder
		$path = '';

		// remove the namespace prefix and convert to file naming
		$class = str_replace( '_', '-', strtolower( substr( $class, $len ) ) );

		// split the class into the namespace path and file name
		$file_pos = strrpos( $class, '\\' );

		if ( $file_pos ) {
			$path = substr( $class, 0, $file_pos + 1 );
			$class = substr( $class, $file_pos + 1 );
		}

		$file = 'class-' . $class;

		$file_path = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $path . $file ) . '.php';

		if ( file_exists( $file_path ) ) {
			require $file_path;
		}
	}
}

spl_autoload_register( 'adthrive_ads_autoload' );

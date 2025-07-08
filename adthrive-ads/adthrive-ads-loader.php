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
	 * @param String $class_name The fully-qualified class name.
	 *
	 * @return void
	 */
	function adthrive_ads_autoload( $class_name ) {
		// project-specific namespace prefix
		$prefix = 'AdThrive_Ads\\';

		// base directory for the namespace prefix
		$base_dir = ADTHRIVE_ADS_PATH;

		$len = strlen( $prefix );

		// does the class use the namespace prefix?
		if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
			return;
		}

		// Default to the root folder
		$path = '';

		// remove the namespace prefix and convert to file naming
		$class_name = str_replace( '_', '-', strtolower( substr( $class_name, $len ) ) );

		// split the class into the namespace path and file name
		$file_pos = strrpos( $class_name, '\\' );

		if ( $file_pos ) {
			$path = substr( $class_name, 0, $file_pos + 1 );
			$class_name = substr( $class_name, $file_pos + 1 );
		}

		$file = 'class-' . $class_name;

		$file_path = $base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $path . $file ) . '.php';

		if ( file_exists( $file_path ) ) {
			require $file_path;
		}
	}
}

spl_autoload_register( 'adthrive_ads_autoload' );

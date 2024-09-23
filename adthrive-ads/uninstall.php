<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AdThrive Ads
 */

if ( ! defined( 'ADTHRIVE_ADS_FILE' ) ) {
	define( 'ADTHRIVE_ADS_FILE', __FILE__ );
}
if ( ! defined( 'ADTHRIVE_ADS_PATH' ) ) {
	define( 'ADTHRIVE_ADS_PATH', plugin_dir_path( ADTHRIVE_ADS_FILE ) );
}

require_once ADTHRIVE_ADS_PATH . 'class-options.php';

// Cleaning up site.js file if it was downloaded
require_once ABSPATH . 'wp-admin/includes/file.php';
if ( \WP_Filesystem() ) {
	global $wp_filesystem;
	$filename = ADTHRIVE_ADS_PATH . 'site.js';

	if ( $wp_filesystem->is_file( $filename ) ) {
		$wp_filesystem->delete( $filename );
	}

	\AdThrive_Ads\Options::remove_option( 'site_js' );
}

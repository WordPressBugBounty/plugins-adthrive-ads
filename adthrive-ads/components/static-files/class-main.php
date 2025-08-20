<?php
/**
 * Static Files Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Static_Files;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
	}

	/**
	 * Add the iframe busters
	 */
	public function template_redirect() {
		global $wp_query;

		if ( is_404() ) {
			$paths = array(
				'/ads.txt',
				'/doubleclick/DARTIframe.html',
				'/rubicon/rp-smartfile.html',
			);

			$uri = ! empty( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

			$current_path = wp_parse_url( $uri, PHP_URL_PATH );

			$file = plugin_dir_path( __FILE__ ) . 'partials' . $current_path;

			if ( in_array( $current_path, $paths, true ) && file_exists( $file ) ) {
				status_header( 200 );

				$content_type = wp_check_filetype( basename( $file ) );

				if ( $content_type['type'] ) {
					header( 'Content-type: ' . $content_type['type'] );
				}

				readfile( $file );

				exit;
			}
		}
	}
}

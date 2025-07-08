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
				'/undertone/UT_IFRAME_buster.html',
			);

			$uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );

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

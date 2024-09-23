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
				'/adcentric/ifr_b.html',
				'/adcom/aceFIF.html',
				'/adinterax/adx-iframe-v2.html',
				'/atlas/atlas_rm.htm',
				'/doubleclick/DARTIframe.html',
				'/eyereturn/eyereturn.html',
				'/ifrm/cwfl.htm',
				'/klipmart/km_ss.html',
				'/linkstorm/linkstorm_certified.html',
				'/pointroll/PointRollAds.htm',
				'/rubicon/rp-smartfile.html',
				'/saymedia/iframebuster.html',
				'/smartadserver/iframeout.html',
				'/undertone/UT_IFRAME_buster.html',
				'/viewpoint/vwpt.html',
			);

			$uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );

			$current_path = parse_url( $uri, PHP_URL_PATH );

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

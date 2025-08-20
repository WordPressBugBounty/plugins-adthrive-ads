<?php
/**
 * Ad Block Detection Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Adblock_Detection;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'wp_footer', array( $this, 'adblock_detection' ), PHP_INT_MAX - 1 );
	}

	/**
	 * Add the Ad Block Detection script
	 */
	public function adblock_detection() {
		echo '<script>';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo file_get_contents( ADTHRIVE_ADS_PATH . 'js/adblock-detection.min.js' );
		// phpcs:enable
		echo '</script>';
	}
}

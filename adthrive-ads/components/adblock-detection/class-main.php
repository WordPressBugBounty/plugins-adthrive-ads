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
		// phpcs:disable
		echo "<script>";
		echo file_get_contents(ADTHRIVE_ADS_PATH . 'js/min/adblock-detection.min.js');
		echo "</script>";
		// phpcs:enable
	}
}

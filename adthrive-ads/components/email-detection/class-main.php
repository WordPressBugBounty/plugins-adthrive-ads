<?php
/**
 * Email Detection Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Email_Detection;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'wp_head', array( $this, 'email_detection' ), 0 );
	}


	/**
	 * Checks url params for emails to capture and removes them
	 */
	public function email_detection() {
		echo '<script data-no-optimize="1" data-cfasync="false">';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo file_get_contents( ADTHRIVE_ADS_PATH . 'js/email-detection.min.js' );
		// phpcs:enable
		echo '</script>';
	}
}

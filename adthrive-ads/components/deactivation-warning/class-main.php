<?php
/**
 * Deactivation Warning Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Deactivation_Warning;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'load-plugins.php', array( $this, 'add_deactivation_warning' ) );
	}

	/**
	 * Pulls in the javascript file that listens
	 * for deactivation click and creates a confirmation
	 * popup for the user
	 */
	public function add_deactivation_warning() {
		wp_enqueue_script( 'adthrive-deactivation-warning', plugins_url( 'js/adthrive-deactivation-warning.js', __FILE__ ), array( 'jquery' ), ADTHRIVE_ADS_VERSION, true );
	}
}

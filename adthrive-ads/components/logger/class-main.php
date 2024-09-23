<?php
/**
 * Logger Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Logger;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'adthrive_daily_event', array( $this, 'log_versions' ) );

	}

	/**
	 * Setup query param to fire log for testing
	 */
	public function init() {
		if ( isset( $_GET['sendLog'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['sendLog'] ) ) ) {
			$this->log_versions();
		}
	}

	/**
	 * Log PHP and WordPress versions
	 */
	public function log_versions() {
		$site_id = \AdThrive_Ads\Options::get( 'site_id' );

		if ( $site_id ) {
			$data = array(
				'siteId' => $site_id,
				'siteName' => '',
				'bucket' => '',
				'branch' => '',
				'deployment' => '',
				'message' => 'versions',
				'pageUrl' => '',
				'body' => wp_json_encode( array(
					'plugin' => ADTHRIVE_ADS_VERSION,
					'wp' => get_bloginfo( 'version' ),
					'php' => phpversion(),
					'autoUpdate' => $this->is_auto_update(),
					'noai' => $this->get_noai_value(),
				) ),
			);

			$request = wp_remote_get( 'https://logger.adthrive.com/wordpress?' . http_build_query( $data ) );
		}
	}

	/**
	 * Determine if auto update is enabled
	 */
	private function is_auto_update() {
		$plugin_name = explode( '/', plugin_basename( __FILE__ ) )[0];
		$auto_updates = array_filter(get_site_option( 'auto_update_plugins', array() ),
			function( $plugin ) use ( $plugin_name ) {
				return strpos( $plugin, $plugin_name ) !== false;
			}
		);
		return count( $auto_updates ) >= 1;
	}

	/**
	 * Retrieves the value for the noai option
	 */
	private function get_noai_value() {
		return \AdThrive_Ads\Options::get( 'no_ai' );
	}
}

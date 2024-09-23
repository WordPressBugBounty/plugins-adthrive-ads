<?php
/**
 * Ads.txt Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Ads_Txt;

/**
 * Main class
 */
class Main {
	private $remote = 'https://ads.adthrive.com/ads.txt';

	/**
	 * Add hooks
	 */
	public function setup() {
		$site_id = \AdThrive_Ads\Options::get( 'site_id' );

		if ( isset( $site_id ) ) {
			$this->remote = 'https://ads.adthrive.com/sites/' . $site_id . '/ads.txt';
		}

		if ( ! is_admin() ) {
			add_action( 'init', array( $this, 'init' ) );
		}

		add_action( 'adthrive_daily_event', array( $this, 'adthrive_daily_event' ) );

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 15, 1 );

		add_filter( 'adthrive_ads_updated', array( $this, 'options_updated' ), 10, 3 );
	}

	/**
	 * Init hook - check if requesting ads.txt and redirect
	 */
	public function init() {
		$url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '/ads.txt' === $url && wp_redirect( $this->remote, 301 ) ) {
			header( 'X-Redirect-Agent: adthrive' );

			exit();
		}
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field( array(
			'name' => 'Override ads.txt',
			'desc' => 'Copies ads.txt to the site root daily. Only use if instructed to do so by Raptive support.',
			'id' => 'copy_ads_txt',
			'type' => 'checkbox',
		) );

		return $cmb;
	}

	/**
	 * Called when the adthrive_ads option is updated
	 */
	public function options_updated( $old_value, $value, $option ) {
		if ( isset( $value['copy_ads_txt'] ) && 'on' === $value['copy_ads_txt'] ) {
			$this->save();
		} else {
			$this->delete();
		}
	}

	/**
	 * Update the ads.txt file daily
	 */
	public function adthrive_daily_event() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$copy_ads_txt = \AdThrive_Ads\Options::get( 'copy_ads_txt' );

		if ( isset( $copy_ads_txt ) && 'on' === $copy_ads_txt ) {
			$this->save();
		}
	}

	/**
	 * Get and save the latest ads.txt file
	 */
	private function save() {
		$response = wp_remote_get( $this->remote );

		if ( is_array( $response ) ) {
			if ( \WP_Filesystem() ) {
				global $wp_filesystem;

				$filename = $wp_filesystem->abspath() . 'ads.txt';

				$wp_filesystem->put_contents( $filename, $response['body'], FS_CHMOD_FILE );
			}
		}
	}

	/**
	 * Delete the ads.txt file
	 */
	private function delete() {
		if ( \WP_Filesystem() ) {
			global $wp_filesystem;

			$filename = $wp_filesystem->abspath() . 'ads.txt';

			if ( $wp_filesystem->is_file( $filename ) ) {
				$contents = $wp_filesystem->get_contents( $filename );
				$wp_filesystem->delete( $filename );
			}
		}
	}
}

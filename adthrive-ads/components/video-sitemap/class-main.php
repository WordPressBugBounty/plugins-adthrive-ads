<?php
/**
 * Video Sitemap Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Video_Sitemap;

/**
 * Main class
 */
class Main {
	private $remote = '';

	/**
	 * Add hooks
	 */
	public function setup() {
		$site_id = \AdThrive_Ads\Options::get( 'site_id' );
		$override_video_sitemap = \AdThrive_Ads\Options::get( 'override_video_sitemap' );

		if ( isset( $site_id ) ) {
			$this->remote = 'https://sitemaps.adthrive.com/' . $site_id . '.xml';
		}

		if ( isset( $override_video_sitemap ) && 'on' === $override_video_sitemap ) {
			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		}

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 15, 1 );
	}

	/**
	 * Init hook - check if requesting video-sitemap and redirect
	 */
	public function template_redirect() {
		$url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( '/adthrive-video-sitemap' === $url && wp_redirect( $this->remote, 301 ) ) {
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
			'name' => 'Video Sitemap Redirect',
			'desc' => 'This will create a redirect from ' . get_site_url() . '/adthrive-video-sitemap to your Raptive-hosted video sitemap',
			'id' => 'override_video_sitemap',
			'type' => 'checkbox',
		) );

		return $cmb;
	}

}

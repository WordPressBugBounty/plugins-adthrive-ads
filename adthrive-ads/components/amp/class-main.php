<?php
/**
 * AMP Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\AMP;

/**
 * Main class
 */
class Main {
	private $adthrive_ads;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->adthrive_ads = get_option( 'adthrive_ads' );
	}

	/**
	 * Add hooks
	 */
	public function setup() {
		if ( isset( $this->adthrive_ads['site_id'] ) && isset( $this->adthrive_ads['amp'] ) && 'on' === $this->adthrive_ads['amp'] ) {
			add_filter( 'amp_content_sanitizers', array( $this, 'add_ad_sanitizer' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
		}
	}

	/**
	 * Add the AMP ad sanitizer to inject ads
	 */
	public function add_ad_sanitizer( $sanitizer_classes, $post ) {
		require_once 'class-ad-injection-sanitizer.php';

		$sanitizer_classes['AdThrive_Ads\Components\AMP\Ad_Injection_Sanitizer'] = array(
			'site_id' => $this->adthrive_ads['site_id'],
		);

		return $sanitizer_classes;
	}

	/**
	 * Add the AMP specific ad style
	 */
	public function enqueue_style() {
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			wp_enqueue_style( 'adthrive-amp-ads', plugins_url( 'css/adthrive-amp-ads.css', ADTHRIVE_ADS_FILE ), false, ADTHRIVE_ADS_VERSION );
		}
	}
}

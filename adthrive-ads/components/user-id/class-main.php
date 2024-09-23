<?php
/**
 * User ID Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\User_ID;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Register API routes
	 */
	public function register_route() {
		register_rest_route( 'adthrive-pubcid/v1', 'extend',
			array(
				'method'   => 'GET',
				'callback' => array( &$this, 'extend_pubcid' ),
				'permission_callback' => '__return_true',
			) );
	}

	/**
	 * Return header with cookie for pubcid with new expiry or generate new one if not found
	 */
	public function extend_pubcid() {
		$urlparts = parse_url( home_url() );
		$cookie_domain = preg_replace( '/^www\./i', '', $urlparts['host'] );
		$cookie_name = '_pubcid';
		$max_age = 365;
		$expires = gmdate( 'D, d M Y H:i:s T', time() + $max_age * DAY_IN_SECONDS );

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
			header( 'Cache-Control: private, max-age=' . $max_age * DAY_IN_SECONDS );
			header( 'Expires: ' . $expires );
		} else {
			$value = wp_generate_uuid4();
			header( 'Cache-Control: no-cache' );
			header( 'Pragma: no-cache' );
		}

		if ( isset( $value ) && strlen( $value ) > 0 ) {
			header( 'Set-Cookie: ' . $cookie_name . '=' . $value . '; expires=' . $expires . '; path=/; domain=' . $cookie_domain . '; SameSite=Lax' );
		}

		header( 'HTTP/1.1 200 OK' );
	}
}

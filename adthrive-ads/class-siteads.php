<?php
/**
 * SiteAds Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads;

/**
 * SiteAds Util class
 */
class SiteAds {

	/**
	 * Returns the site js value.
	 * If a location is provided via query params, it will fetch the value from the specified endpoint.
	 * Otherwise, it will fetch the value from the site.js file on the file system.
	 */
	public function get_site_js() {
		$site_ads_environment = $this->get_site_ads_environment();

		// This is for debugging purposes, we should be fetching the site.js file from wp_options.
		if ( isset( $site_ads_environment ) ) {
			$response = $this->get_remote_site_ads( $site_ads_environment );
			if ( is_array( $response ) ) {
				return wp_remote_retrieve_body( $response );
			} else {
				esc_html_e( 'Bad response when retrieving site ads from environment ' . $site_ads_environment );
				return null;
			}
		}

		// If this value in wp_options is not set for whatever reason, use the `atFileReset=true` query param.
		return $this->get_option_value( 'site_js' );
	}

	/**
	 * Return the environment that site ads should be retrived from.
	 * Expected values are "production", "staging", "dev", or null.
	 */
	private function get_site_ads_environment() {
		return isset( $_GET['plugin_site_ads_environment'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['plugin_site_ads_environment'] ) ), 0, 10 ) : null;
	}

	/**
	 * Returns the site id
	 */
	private function get_site_id() {
		return \AdThrive_Ads\Options::get( 'site_id' );
	}

	/**
	 * Returns query param needed to fetch the site ads value from a specific table.
	 */
	private function get_site_ads_config_query_param( $environment ) {
		if ( ! isset( $environment ) ) {
			return '';
		}
		$environment_config = array(
			'production' => 'Prod',
			'staging' => 'QA',
			'dev' => 'Dev',
		);
		if ( ! isset( $environment_config[ $environment ] ) ) {
			esc_html_e( 'Query param is not set for environment "' . $environment . '". Update $enviroment_config to include a value for this environment.' );
			return '';
		}
		return "&config=$environment_config[$environment]:all";
	}

	/**
	 * Fetches site ads value from endpoint
	 */
	private function get_remote_site_ads( $environment ) {
		$site_id = $this->get_site_id();
		$config_query_param = $this->get_site_ads_config_query_param( $environment );
		$remote = "https://ads.adthrive.com/api/v1/siteAds/$site_id?ts=" . strval( time() ) . $config_query_param;
		$response = wp_remote_get( $remote );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			esc_html_e( 'Unexpected response code when fetching remote site ads from ' . $remote . '. Response code: ' . $response_code );
		}
		if ( wp_remote_retrieve_body( $response ) === 'false' ) {
			esc_html_e( 'Error when fetch remote site ads from ' . $remote . '. Make sure you have siteAds defined for this environment.' );
		}
		return $response;
	}

	/**
	 * Get the Adthrive option value from WP transient or option storage
	 */
	private function get_option_value( $option_name ) {
		$adthrive_options = get_option( 'adthrive_options' );

		if ( false === $adthrive_options ) {
			return false;
		}

		if ( isset( $adthrive_options[ $option_name ]['content'] ) ) {
			return $adthrive_options[ $option_name ]['content'];
		}

		return false;
	}
}

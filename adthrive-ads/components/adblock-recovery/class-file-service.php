<?php
/**
 * Ad Shield file storage and rendering.
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Adblock_Recovery;

/**
 * Keeps Ad Shield artifacts independent of CLS deployment state.
 */
class File_Service {
	const OPTION_KEY = 'ad-shield-state';
	const ORIGIN = 'https://ads.adthrive.com';
	const CONFIG_URL = self::ORIGIN . '/api/v1/core/cms/experiments';
	const QA_URL = self::ORIGIN . '/abr/test.js';
	const BETA_URL = self::ORIGIN . '/abr/candidate.js';
	const PRODUCTION_URL = self::ORIGIN . '/abr/prod.js';
	const PRODUCTION_SCRIPT_ID = 'rtv-6f0a';
	const BETA_SCRIPT_ID = 'rtv-6f1b';
	const QA_SCRIPT_ID = 'rtv-6f2c';

	/**
	 * Fetch beta site IDs and persist the selected recovery script.
	 *
	 * @param string|false $configuration Previously retrieved experiments response.
	 * @return bool Whether the selected artifact was saved.
	 */
	public function sync( $configuration = false ) {
		$previous = $this->get_state();
		if ( false === $configuration ) {
			$configuration = $this->get_remote_body( self::CONFIG_URL . '?ts=' . strval( time() ) );
		}
		if ( false === $configuration ) {
			if ( ! $this->has_usable_state( $previous, \AdThrive_Ads\Options::get( 'site_id' ) ) ) {
				$this->invalidate_state( $previous );
			}
			return false;
		}

		$config = json_decode( $configuration );
		if ( ! is_object( $config ) || ! isset( $config->version, $config->adRecoveryBetaSites ) || '1.0.0' !== $config->version || ! $this->valid_beta_sites( $config->adRecoveryBetaSites ) ) {
			if ( ! $this->has_usable_production_state( $previous, \AdThrive_Ads\Options::get( 'site_id' ) ) ) {
				$this->invalidate_state( $previous );
			}
			return false;
		}

		$site_id = \AdThrive_Ads\Options::get( 'site_id' );
		if ( ! $this->valid_site_id( $site_id ) ) {
			$this->invalidate_state( $previous );
			return false;
		}
		$stage = in_array( $site_id, $config->adRecoveryBetaSites, true ) ? 'beta' : 'production';

		$state = array(
			'site_id' => $site_id,
			'stage'   => $stage,
		);
		if ( is_array( $previous ) && isset( $previous['site_id'] ) && $site_id === $previous['site_id'] ) {
			if ( isset( $previous['lastGood'] ) ) {
				$state['lastGood'] = $previous['lastGood'];
			}
			if ( 'beta' === $stage && isset( $previous['stage'], $previous['artifact'] ) && 'beta' === $previous['stage'] && $this->artifact_matches( $previous['artifact'], self::BETA_URL ) ) {
				$state['artifact'] = $previous['artifact'];
			}
			if ( isset( $previous['stage'], $previous['artifact'] ) && 'production' === $previous['stage'] && $this->artifact_matches( $previous['artifact'], self::PRODUCTION_URL ) ) {
				$state['lastGood'] = $previous['artifact'];
			}
		}

		// A local Off disables retrieval as well as rendering.
		if ( 'off' === \AdThrive_Ads\Options::get( 'adblock_recovery', 'light' ) ) {
			\AdThrive_Ads\Options::save_to_option( self::OPTION_KEY, $state );
			return false;
		}

		$selected = $this->download_artifact( $this->stage_url( $stage ) );
		if ( false !== $selected ) {
			if ( 'production' === $stage ) {
				$state['lastGood'] = $selected;
				unset( $state['artifact'] );
			} else {
				$state['artifact'] = $selected;
			}
		}

		\AdThrive_Ads\Options::save_to_option( self::OPTION_KEY, $state );
		\AdThrive_Ads\Options::remove_options_by_prefix( 'adblock-recovery.' );
		return 'production' === $stage ? isset( $state['lastGood'] ) : isset( $state['artifact'] );
	}

	/**
	 * Return the selected Ad Shield script tag.
	 *
	 * @param string      $recovery_mode Publisher-selected Light or Essential mode.
	 * @param string|null $test_recovery_content Request-local QA body, or null for saved recovery.
	 * @return string
	 */
	public function get_script_markup( $recovery_mode, $test_recovery_content = null ) {
		if ( ! in_array( $recovery_mode, array( 'light', 'essential' ), true ) ) {
			return '';
		}

		if ( null !== $test_recovery_content ) {
			return '' === $test_recovery_content ? '' : $this->script_markup( $test_recovery_content, $recovery_mode, self::QA_SCRIPT_ID, hash( 'sha256', $test_recovery_content ) );
		}

		$state = $this->get_state();
		if ( ! is_array( $state ) ) {
			return '';
		}

		$site_id = \AdThrive_Ads\Options::get( 'site_id' );
		if ( ! $this->valid_site_id( $site_id ) || ! isset( $state['site_id'], $state['stage'] ) || $site_id !== $state['site_id'] || ! in_array( $state['stage'], array( 'beta', 'production' ), true ) ) {
			return '';
		}
		$stage = $state['stage'];

		$url = $this->stage_url( $stage );
		$artifact = isset( $state['artifact'] ) ? $state['artifact'] : false;
		if ( ! $this->artifact_matches( $artifact, $url ) ) {
			if ( false !== $artifact || 'production' !== $stage || ! isset( $state['lastGood'] ) || ! $this->artifact_matches( $state['lastGood'], self::PRODUCTION_URL ) ) {
				return '';
			}
			$artifact = $state['lastGood'];
		}

		$script_id = 'beta' === $stage ? self::BETA_SCRIPT_ID : self::PRODUCTION_SCRIPT_ID;
		return $this->script_markup( $artifact['content'], $recovery_mode, $script_id );
	}

	/**
	 * Format trusted Raptive script content as one inline tag.
	 *
	 * @param string $content Recovery script content.
	 * @param string $recovery_mode Publisher-selected Light or Essential mode.
	 * @param string $script_id Script tag ID.
	 * @param string $sha256 SHA-256 of the raw QA payload, when present.
	 * @return string
	 */
	private function script_markup( $content, $recovery_mode, $script_id, $sha256 = '' ) {
		$script = preg_replace( '~</script~i', '<\\/script', $content );
		$hash_attribute = '' === $sha256 ? '' : " data-raptive-abr-sha256='" . esc_attr( $sha256 ) . "'";
		return "<script data-no-optimize='1' data-cfasync='false' data-abr-mode='" . esc_attr( $recovery_mode ) . "' id='" . esc_attr( $script_id ) . "'" . $hash_attribute . '>' . $script . '</script>';
	}

	/**
	 * Fetch the fixed QA endpoint without using or updating saved recovery.
	 *
	 * @return string|false Valid JavaScript body, or false on failure.
	 */
	public function fetch_qa_script() {
		$response = wp_remote_get(
			self::QA_URL,
			array(
				'timeout'            => 5,
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
			)
		);
		return $this->valid_javascript_body( $response );
	}

	/**
	 * Get persisted Ad Shield state.
	 *
	 * @return array|false
	 */
	private function get_state() {
		$state = \AdThrive_Ads\Options::get_saved_option( self::OPTION_KEY );
		return is_array( $state ) ? $state : false;
	}

	/**
	 * Keep a selected script when configuration retrieval fails.
	 *
	 * @param array|false $state Saved Ad Shield state.
	 * @param mixed       $site_id Current publisher site ID.
	 * @return bool
	 */
	private function has_usable_state( $state, $site_id ) {
		if ( ! is_array( $state ) || ! $this->valid_site_id( $site_id ) || ! isset( $state['site_id'], $state['stage'] ) || $site_id !== $state['site_id'] ) {
			return false;
		}
		if ( 'beta' === $state['stage'] ) {
			return isset( $state['artifact'] ) && $this->artifact_matches( $state['artifact'], self::BETA_URL );
		}
		return $this->has_usable_production_state( $state, $site_id );
	}

	/**
	 * Keep a selected production script when configuration validation fails.
	 *
	 * @param array|false $state Saved Ad Shield state.
	 * @param mixed       $site_id Current publisher site ID.
	 * @return bool
	 */
	private function has_usable_production_state( $state, $site_id ) {
		if ( ! is_array( $state ) || ! $this->valid_site_id( $site_id ) || ! isset( $state['site_id'], $state['stage'] ) || $site_id !== $state['site_id'] || 'production' !== $state['stage'] ) {
			return false;
		}
		$artifact = isset( $state['artifact'] ) ? $state['artifact'] : false;
		return $this->artifact_matches( $artifact, self::PRODUCTION_URL ) || ( false === $artifact && isset( $state['lastGood'] ) && $this->artifact_matches( $state['lastGood'], self::PRODUCTION_URL ) );
	}

	/**
	 * Prevent stale rollout targeting from continuing to render after retrieval fails.
	 *
	 * @param array|false $previous Previous state.
	 * @return void
	 */
	private function invalidate_state( $previous ) {
		$state = array();
		if ( is_array( $previous ) && isset( $previous['lastGood'] ) ) {
			$state['lastGood'] = $previous['lastGood'];
		}
		\AdThrive_Ads\Options::save_to_option( self::OPTION_KEY, $state );
	}

	/**
	 * Get the delivery URL for a rollout stage.
	 *
	 * @param string $stage Beta or production.
	 * @return string
	 */
	private function stage_url( $stage ) {
		$urls = array(
			'beta'       => self::BETA_URL,
			'production' => self::PRODUCTION_URL,
		);
		return $urls[ $stage ];
	}

	/**
	 * Download one artifact without following redirects.
	 *
	 * @param string $url Stage URL.
	 * @return array|false
	 */
	private function download_artifact( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'timeout'            => 10,
			)
		);
		$body = $this->valid_javascript_body( $response );
		if ( false === $body ) {
			return false;
		}
		return array(
			'url'     => $url,
			'content' => $body,
		);
	}

	/**
	 * Fetch a response body from the Raptive origin.
	 *
	 * @param string $url URL to retrieve.
	 * @return string|false
	 */
	private function get_remote_body( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
				'timeout'            => 10,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Return a nonblank JavaScript body from a successful HTTP response.
	 *
	 * @param mixed $response HTTP response.
	 * @return string|false
	 */
	private function valid_javascript_body( $response ) {
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! is_string( $body ) || '' === trim( $body ) || ! is_string( $content_type ) ) {
			return false;
		}
		$type = strtolower( trim( strtok( $content_type, ';' ) ) );
		return in_array( $type, array( 'application/javascript', 'text/javascript' ), true ) ? $body : false;
	}

	/**
	 * Validate beta site IDs before selecting a stage.
	 *
	 * @param mixed $beta_sites Beta site IDs.
	 * @return bool
	 */
	private function valid_beta_sites( $beta_sites ) {
		if ( ! is_array( $beta_sites ) || array_values( $beta_sites ) !== $beta_sites ) {
			return false;
		}
		foreach ( $beta_sites as $site_id ) {
			if ( ! $this->valid_site_id( $site_id ) ) {
				return false;
			}
		}
		return count( $beta_sites ) === count( array_unique( $beta_sites ) );
	}

	/**
	 * Confirm the established publisher site ID format.
	 *
	 * @param mixed $site_id Publisher site ID.
	 * @return bool
	 */
	private function valid_site_id( $site_id ) {
		return is_string( $site_id ) && 1 === preg_match( '/^[a-f0-9]{24}$/', $site_id );
	}

	/**
	 * Confirm the stored script belongs to the selected stage URL.
	 *
	 * @param mixed $artifact Stored artifact.
	 * @param string $url Stage URL.
	 * @return bool
	 */
	private function artifact_matches( $artifact, $url ) {
		return is_array( $artifact ) && isset( $artifact['url'], $artifact['content'] ) && is_string( $artifact['content'] ) && '' !== $artifact['content'] && $url === $artifact['url'];
	}
}

<?php
/**
 * Ad Recovery Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Adblock_Recovery;

/**
 * Main class
 */
class Main {
	public $feature_overidden = false;
	public $overridden_recovery_mode = false;
	private $script_rendered = false;
	private $test_recovery_content = null;

	/**
	 * Add hooks
	 */
	public function setup() {
		$adblock_recovery = $this->get_overridden_recovery_mode( \AdThrive_Ads\Options::get_plugin_settings() );
		if ( 'off' === $adblock_recovery ) {
			$this->feature_overidden = true;
			$this->overridden_recovery_mode = $adblock_recovery;
		}

		add_action( 'template_redirect', array( $this, 'prepare_test_recovery' ), 0 );
		add_action( 'wp_footer', array( $this, 'adblock_recovery' ), PHP_INT_MAX );
		add_action( 'adthrive_ad_shield_daily_event', array( $this, 'sync_ad_shield_data' ) );
		add_action( 'adthrive_plugin_experiments_retrieved', array( $this, 'sync_ad_shield_data' ) );

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 15, 1 );
		add_filter( 'adthrive_ads_updated', array( $this, 'options_updated' ), 10, 2 );
	}

	/**
	 * Refresh Ad Shield from an experiments response when available.
	 *
	 * @param string|false $configuration Previously retrieved experiments response.
	 * @return bool
	 */
	public function sync_ad_shield_data( $configuration = false ) {
		$file_service = new File_Service();
		return $file_service->sync( $configuration );
	}

	/**
	 * Refresh when the publisher changes Ad Block Recovery mode.
	 *
	 * @param array $old_value Previous plugin settings.
	 * @param array $value Updated plugin settings.
	 * @return void
	 */
	public function options_updated( $old_value, $value ) {
		if ( ( isset( $old_value['adblock_recovery'] ) ? $old_value['adblock_recovery'] : 'light' ) !== ( isset( $value['adblock_recovery'] ) ? $value['adblock_recovery'] : 'light' ) ) {
			if ( ! wp_next_scheduled( 'adthrive_ad_shield_daily_event' ) ) {
				wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'adthrive_ad_shield_daily_event' );
			}
		}
	}

	/**
	 * Fetch QA recovery before page output so the response can disable caching.
	 */
	public function prepare_test_recovery() {
		if ( ! array_key_exists( 'raptive_abr_qa_cb', $_GET ) ) {
			return;
		}

		$recovery_mode = $this->feature_overidden
			? $this->overridden_recovery_mode
			: $this->get_recovery_mode();
		if ( 'off' === $recovery_mode ) {
			return;
		}

		// A failed QA fetch must never fall through to the saved recovery script.
		$this->test_recovery_content = '';
		nocache_headers();
		$file_service = new File_Service();
		$script = $file_service->fetch_qa_script();
		if ( false !== $script ) {
			$this->test_recovery_content = $script;
		}
	}

	/**
	 * Add the Ad Block Recovery script
	 */
	public function adblock_recovery() {
		if ( $this->script_rendered ) {
			return;
		}

		$recovery_mode = $this->feature_overidden
			? $this->overridden_recovery_mode
			: $this->get_recovery_mode();

		if ( 'off' === $recovery_mode ) {
			return;
		}

		require 'partials/adblock-recovery.php';
		if ( '' === $script_markup ) {
			return;
		}

		$this->script_rendered = true;
		echo $script_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The service renders a script downloaded from the Raptive origin and escapes attributes.
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field(
			array(
				'name' => 'Ad Block Recovery',
				'desc' => 'Show ads to users with ad blockers enabled. Learn more <a href="https://help.raptive.com/hc/en-us/articles/360039737371/" target="_blank">here.</a>',
				'id' => 'adblock_recovery',
				'type' => 'radio',
				'options' => $this->get_recovery_options(),
				'default' => 'light',
				'escape_cb' => array( $this, 'recovery_mode_field_value' ),
				'sanitization_cb' => array( $this, 'recovery_mode_field_value' ),
				'save_field' => ! $this->feature_overidden,
				'attributes' => array(
					'readonly' => $this->feature_overidden,
					'disabled' => $this->feature_overidden,
				),
			)
		);

		return $cmb;
	}

	/**
	 * Map the stored field value to a supported radio option.
	 *
	 * Sites that saved the legacy On/Off field still store 'on', which matches
	 * no current radio option.
	 *
	 * @param mixed $value The stored or submitted field value.
	 *
	 * @return string
	 */
	public function recovery_mode_field_value( $value ) {
		return $this->normalize_recovery_mode( $value );
	}

	/**
	 * Get the effective recovery mode from plugin settings.
	 */
	private function get_recovery_mode() {
		return $this->normalize_recovery_mode( \AdThrive_Ads\Options::get( 'adblock_recovery', 'light' ) );
	}

	/**
	 * Get any remote override for ad block recovery.
	 *
	 * @param array|object|false $plugin_settings Remote plugin settings response.
	 *
	 * @return string|false
	 */
	private function get_overridden_recovery_mode( $plugin_settings ) {
		if ( is_array( $plugin_settings ) && isset( $plugin_settings['option_overrides']['adblock_recovery'] ) ) {
			return $this->normalize_recovery_mode( $plugin_settings['option_overrides']['adblock_recovery'] );
		}

		if ( is_object( $plugin_settings ) && isset( $plugin_settings->option_overrides, $plugin_settings->option_overrides->adblock_recovery ) ) {
			return $this->normalize_recovery_mode( $plugin_settings->option_overrides->adblock_recovery );
		}

		return false;
	}

	/**
	 * Normalize legacy and current option values to supported modes.
	 *
	 * @param string|bool $recovery_mode Saved or overridden recovery mode.
	 *
	 * @return string
	 */
	private function normalize_recovery_mode( $recovery_mode ) {
		if ( 'essential' === $recovery_mode ) {
			return 'essential';
		}

		if ( 'off' === $recovery_mode ) {
			return 'off';
		}

		return 'light';
	}

	/**
	 * Get radio option labels for the ad block recovery field.
	 */
	private function get_recovery_options() {
		return array(
			'light' => $this->get_recovery_option_label(
				'Light',
				'Recovers most revenue for ad block users, shows a popup wall for users with an invalid filter on their ad blocker software, ~3% of ad block users.'
			),
			'essential' => $this->get_recovery_option_label(
				'Essential',
				'Recovers less revenue for ad block users, any users with an invalid filter on their ad block software will still be able to view the content of your site.'
			),
			'off'       => $this->get_recovery_option_label( 'Off' ),
		);
	}

	/**
	 * Build a radio option label with an inline info icon.
	 *
	 * @param string $label Option label.
	 * @param string $description Tooltip and screen-reader copy.
	 *
	 * @return string
	 */
	private function get_recovery_option_label( $label, $description = '' ) {
		if ( empty( $description ) ) {
			return esc_html( $label );
		}

		$tooltip_id = sprintf( 'adthrive-option-tooltip-%s', sanitize_key( $label ) );

		$info_icon = sprintf(
			'<span class="adthrive-option-info-wrapper" tabindex="0" aria-describedby="%1$s"><span class="dashicons dashicons-info-outline adthrive-option-info" aria-hidden="true"></span><span id="%1$s" class="adthrive-option-tooltip">%2$s</span><span class="screen-reader-text">%3$s</span></span>',
			esc_attr( $tooltip_id ),
			esc_html( $description ),
			esc_html( $description )
		);

		return sprintf( '%1$s %2$s', esc_html( $label ), $info_icon );
	}
}

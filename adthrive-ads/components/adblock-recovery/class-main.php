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

	/**
	 * Add hooks
	 */
	public function setup() {
		$adblock_recovery = $this->get_overridden_recovery_mode( \AdThrive_Ads\Options::get_plugin_settings() );
		if ( 'off' === $adblock_recovery ) {
			$this->feature_overidden = true;
			$this->overridden_recovery_mode = $adblock_recovery;
		}

		add_action( 'wp_footer', array( $this, 'adblock_recovery' ), PHP_INT_MAX );

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 15, 1 );
	}

	/**
	 * Add the Ad Block Recovery script
	 */
	public function adblock_recovery() {
		$recovery_mode = $this->feature_overidden
			? $this->overridden_recovery_mode
			: $this->get_recovery_mode();

		if ( 'off' === $recovery_mode ) {
			return;
		}

		require 'partials/adblock-recovery.php';
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

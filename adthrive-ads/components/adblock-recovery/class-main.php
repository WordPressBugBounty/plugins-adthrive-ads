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

	/**
	 * Add hooks
	 */
	public function setup() {
		$plugin_settings = \AdThrive_Ads\Options::get_plugin_settings();
		if ( isset( $plugin_settings, $plugin_settings->option_overrides, $plugin_settings->option_overrides->adblock_recovery ) ) {
			$adblock_recovery = $plugin_settings['option_overrides']['adblock_recovery'];
			$this->feature_overidden = 'off' === $adblock_recovery;
		}

		add_action( 'wp_footer', array( $this, 'adblock_recovery' ), PHP_INT_MAX );

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 15, 1 );
	}

	/**
	 * Add the Ad Block Recovery script
	 */
	public function adblock_recovery() {
		$recovery_enabled = \AdThrive_Ads\Options::get( 'adblock_recovery' );

		if ( 'off' !== $recovery_enabled && ! $this->feature_overidden ) {
			require 'partials/adblock-recovery.php';
		}
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field( array(
			'name' => 'Ad Block Recovery',
			'desc' => 'Show ads to users with ad blockers enabled. Learn more <a href="https://help.raptive.com/hc/en-us/articles/360039737371/" target="_blank">here.</a>',
			'id' => 'adblock_recovery',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
			'save_field' => ! $this->feature_overidden,
			'attributes' => array(
				'readonly' => $this->feature_overidden,
				'disabled' => $this->feature_overidden,
			),
		) );

		return $cmb;
	}
}

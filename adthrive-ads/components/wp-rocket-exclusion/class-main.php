<?php
/**
 * WP Rocket Exclusion Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Wp_Rocket_Exclusion;

/**
 * Main class
 */
class Main {


	/**
	 * Add hooks
	 */
	public function setup() {
		add_filter(
			'rocket_defer_inline_exclusions',
			function ( $inline_exclusions_list ) {
				if ( ! is_array( $inline_exclusions_list ) ) {
					$inline_exclusions_list = array();
				}

				$inline_exclusions_list[] = 'adthrive';

				return $inline_exclusions_list;
			}
		);
	}
}

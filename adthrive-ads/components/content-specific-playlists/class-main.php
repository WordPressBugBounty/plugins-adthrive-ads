<?php
/**
 * Content Specific Playlist Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Content_Specific_Playlists;

/**
 * Main class
 */
class Main {
	/**
	 * Add hooks
	 */
	public function setup() {
		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 11, 1 );
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field( array(
			'name' => __( 'Playlist Management', 'adthrive_ads' ),
			'desc' => __( 'Enable Category Specific Playlists
				<br/>Adds categories as classes on posts for Category Specific Playlist functionality', 'adthrive_ads' ),
			'id' => 'content_specific_playlists',
			'type' => 'checkbox',
		) );

		return $cmb;
	}
}

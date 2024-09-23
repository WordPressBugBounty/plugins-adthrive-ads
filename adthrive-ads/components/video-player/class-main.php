<?php
/**
 * Ads Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Video_Player;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_shortcode( 'adthrive-in-post-video-player', array( $this, 'in_post_video_player_shortcode' ) );
	}

	/**
	 * Add an in-post video player
	 *
	 * @param array $atts The shortcode attributes.
	 */
	public function in_post_video_player_shortcode( $atts ) {
		$disable_meta_on_site = \AdThrive_Ads\Options::get( 'disable_video_metadata' );
		$disable_meta_in_post = get_post_meta( get_the_ID(), 'adthrive_ads_disable_metadata' );
		$enable_meta_in_post = get_post_meta( get_the_ID(), 'adthrive_ads_enable_metadata' );

		$add_meta = 'on';
		if ( isset( $disable_meta_on_site ) && 'on' === $disable_meta_on_site ) {
			if ( ! isset( $enable_meta_in_post[0] ) ) {
				$add_meta = '';
			}
		} else {
			if ( isset( $disable_meta_in_post[0] ) && 'on' === $disable_meta_in_post[0] ) {
				$add_meta = '';
			}
		}

		$atts = shortcode_atts(
			array(
				'video-id' => '',
				'name' => '',
				'description' => '',
				'upload-date' => '',
				'add-meta' => $add_meta,
				'player-type' => '',
				'override-embed' => '',
			),
			$atts,
			'adthrive-in-post-video-player'
		);

		ob_start();

		require 'partials/in-post.php';

		return ob_get_clean();
	}
}

<?php
/**
 * Ad Block Detection Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Adblock_Detection;

/**
 * Main class
 */
class Main {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'wp_footer', array( $this, 'adblock_detection' ), PHP_INT_MAX - 1 );
	}

	/**
	 * Add the Ad Block Detection script
	 */
	public function adblock_detection() {
		$cls_file_service = new \AdThrive_Ads\Components\Ads\Cls_File_Service();
		$cls_data = $cls_file_service->parse_cls_deployment();
		$cls_file_service->insert_cls_file( 'adblock-detection', $cls_data );
	}
}

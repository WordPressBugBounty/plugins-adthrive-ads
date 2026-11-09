<?php
/**
 * Ads partial view
 *
 * @package AdThrive Ads
 */

if ( ! defined( 'ADTHRIVE_ADS_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

$recovery_mode = ! empty( $recovery_mode ) ? $recovery_mode : 'light';
$file_service = new \AdThrive_Ads\Components\Adblock_Recovery\File_Service();
$script_markup = $file_service->get_script_markup( $recovery_mode, $this->test_recovery_content );

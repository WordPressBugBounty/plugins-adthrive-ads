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
$cls_file_service = new \AdThrive_Ads\Components\Ads\Cls_File_Service();
$cls_data = $cls_file_service->parse_cls_deployment();
// A single recovery script handles both Light and Essential modes; the mode is
// passed through via data-abr-mode and read at runtime from
// document.currentScript.dataset.abrMode (PE-739).
$cls_file_service->insert_cls_file( 'adblock-recovery', $cls_data, array( 'data-abr-mode' => $recovery_mode ) );

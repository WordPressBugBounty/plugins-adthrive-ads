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
// phpcs:disable
echo "<script>";
echo file_get_contents(ADTHRIVE_ADS_PATH . 'js/min/adblock-recovery.min.js');
echo "</script>";
// phpcs:enable

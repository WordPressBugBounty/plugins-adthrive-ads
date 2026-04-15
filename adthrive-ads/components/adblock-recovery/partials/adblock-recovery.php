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
echo '<script data-cfasync="false" data-abr-mode="' . esc_attr( $recovery_mode ) . '">';
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
echo file_get_contents( ADTHRIVE_ADS_PATH . 'js/adblock-recovery.min.js' );
// phpcs:enable
echo '</script>';

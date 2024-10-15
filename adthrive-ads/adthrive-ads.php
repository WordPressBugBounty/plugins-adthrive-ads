<?php
/**
 * Raptive Ads
 *
 * @package AdThrive Ads
 *
 * Plugin Name: Raptive Ads
 * Plugin URI: http://www.raptive.com
 * Description: Raptive Ads
 * Version: 3.6.3
 * Author: Raptive
 * Author URI: http://www.raptive.com
 * License: GPL2
 *
 * Copyright (c) Raptive
 *
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with This plugin. If not, see {License URI}.
 */

defined( 'ABSPATH' ) || die;

define( 'ADTHRIVE_ADS_VERSION', '3.6.3' );
define( 'ADTHRIVE_ADS_FILE', __FILE__ );
define( 'ADTHRIVE_ADS_PATH', plugin_dir_path( ADTHRIVE_ADS_FILE ) );
define( 'ADTHRIVE_ADS_URL', trailingslashit( plugin_dir_url( ADTHRIVE_ADS_FILE ) ) );
define( 'ADTHRIVE_PHP_INT_MIN', ~PHP_INT_MAX );

/**
 * Output the minimum version error message
 */
function adthrive_ads_php_notice() {
	// @codingStandardsIgnoreStart
	echo '<div class="error"><p>' . __( 'Raptive Ads requires PHP 5.6+ and WordPress 4.6+', 'adthrive-ads' ) . '</p></div>';

	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Deactivate this plugin
 */
function adthrive_ads_deactivate_self() {
	deactivate_plugins( plugin_basename( ADTHRIVE_ADS_FILE ) );
}

global $wp_version;

if ( version_compare( phpversion(), '5.6', '<' ) || version_compare( $wp_version, '4.6', '<' ) ) {
	add_action( 'admin_notices', 'adthrive_ads_php_notice' );

	add_action( 'admin_init', 'adthrive_ads_deactivate_self' );

	return;
} else {
	require_once 'adthrive-ads-loader.php';

	require_once 'class-main.php';

	new AdThrive_Ads\Main();
}

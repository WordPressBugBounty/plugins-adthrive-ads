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
?>

<script data-no-optimize="1" data-cfasync="false">
(function(w, d) {
	w.adthrive = w.adthrive || {};
	w.adthrive.cmd = w.adthrive.cmd || [];
	w.adthrive.plugin = 'adthrive-ads-<?php echo esc_js( ADTHRIVE_ADS_VERSION ); ?>';
	w.adthrive.host = 'ads.adthrive.com';
	w.adthrive.integration = 'plugin';

	var commitParam = (w.adthriveCLS && w.adthriveCLS.bucket !== 'prod' && w.adthriveCLS.branch) ? '&commit=' + w.adthriveCLS.branch : '';

	var s = d.createElement('script');
	s.async = true;
	s.referrerpolicy='no-referrer-when-downgrade';
	s.src = 'https://' + w.adthrive.host + '/sites/<?php esc_attr_e( $data['site_id'] ); ?>/ads.min.js?referrer=' + w.encodeURIComponent(w.location.href) + commitParam + '&cb=' + (Math.floor(Math.random() * 100) + 1) + '<?php echo 'true' === $data['plugin_debug'] ? '&debug=true' : ''; ?>';
	var n = d.getElementsByTagName('script')[0];
	n.parentNode.insertBefore(s, n);
})(window, document);
</script>

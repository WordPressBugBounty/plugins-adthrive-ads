<?php
/**
 * Adthrive-ad CSS and site ads for div insertion
 *
 * @package AdThrive Ads
 */

?>
<style data-no-optimize="1" data-cfasync="false">
	.adthrive-ad {
		margin-top: 10px;
		margin-bottom: 10px;
		text-align: center;
		overflow-x: visible;
		clear: both;
		line-height: 0;
	}
	<?php
    // phpcs:disable
		echo $data['site_css'];
    // phpcs:enable
	?>
</style>
<script data-no-optimize="1" data-cfasync="false">
	window.adthriveCLS = {
		enabledLocations: ['Content', 'Recipe'],
		injectedSlots: [],
		injectedFromPlugin: true,
		<?php
			// phpcs:disable
			echo isset( $data['cls_branch'] ) ? 'branch: \'' . $data['cls_branch'] . '\',' : '';
			echo isset( $data['cls_bucket'] ) ? 'bucket: \'' . $data['cls_bucket'] . '\',' : '';
			// phpcs:enable
		?>
		<?php if ( in_array( 'adthrive-disable-video', $body_classes, true ) ) : ?>
			videoDisabledFromPlugin: true,
		<?php endif; ?>
	};
	<?php
        // phpcs:disable
		echo 'window.adthriveCLS.siteAds = ' . $data['site_js'] . ';';
        // phpcs:enable
	?>
</script>

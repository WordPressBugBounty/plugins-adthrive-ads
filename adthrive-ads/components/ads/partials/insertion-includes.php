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
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
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
			echo isset( $data['cls_branch'] ) ? "branch: '" . esc_js( $data['cls_branch'] ) . "'," : '';
			echo isset( $data['cls_bucket'] ) ? "bucket: '" . esc_js( $data['cls_bucket'] ) . "'," : '';
		?>
		<?php if ( in_array( 'adthrive-disable-video', $body_classes, true ) ) : ?>
			videoDisabledFromPlugin: true,
		<?php endif; ?>
	};
	<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo 'window.adthriveCLS.siteAds = ' . $data['site_js'] . ';';
		// phpcs:enable
	?>
</script>

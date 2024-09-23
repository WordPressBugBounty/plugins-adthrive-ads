<?php
/**
 * Video Player Embed view
 *
 * @package AdThrive Ads
 */

if ( ! defined( 'ADTHRIVE_ADS_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}
?>

<div class="adthrive-video-player in-post" <?php echo ( sanitize_key( $atts['add-meta'] ) === 'on' ) ? 'itemscope itemtype="https://schema.org/VideoObject"' : ''; ?> data-video-id="<?php esc_attr_e( $atts['video-id'] ); ?>" data-player-type="<?php esc_attr_e( $atts['player-type'] ); ?>" override-embed="<?php esc_attr_e( $atts['override-embed'] ); ?>">
	<?php if ( sanitize_key( $atts['add-meta'] ) === 'on' ) : ?>
		<meta itemprop="uploadDate" content="<?php esc_attr_e( $atts['upload-date'] ); ?>" />
		<meta itemprop="name" content="<?php esc_attr_e( $atts['name'] ); ?>" />
		<meta itemprop="description" content="<?php esc_attr_e( $atts['description'] ); ?>" />
		<meta itemprop="thumbnailUrl" content="https://content.jwplatform.com/thumbs/<?php esc_attr_e( $atts['video-id'] ); ?>-720.jpg" />
		<meta itemprop="contentUrl" content="https://content.jwplatform.com/videos/<?php esc_attr_e( $atts['video-id'] ); ?>.mp4" />
	<?php endif; ?>
</div>

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

<div class="adthrive-video-player in-post" <?php echo ( sanitize_key( $atts['add-meta'] ) === 'on' ) ? 'itemscope itemtype="https://schema.org/VideoObject"' : ''; ?> data-video-id="<?php echo esc_attr( $atts['video-id'] ); ?>" data-player-type="<?php echo esc_attr( $atts['player-type'] ); ?>" override-embed="<?php echo esc_attr( $atts['override-embed'] ); ?>">
	<?php if ( sanitize_key( $atts['add-meta'] ) === 'on' ) : ?>
		<meta itemprop="uploadDate" content="<?php echo esc_attr( $atts['upload-date'] ); ?>" />
		<meta itemprop="name" content="<?php echo esc_attr( $atts['name'] ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( $atts['description'] ); ?>" />
		<meta itemprop="thumbnailUrl" content="https://content.jwplatform.com/thumbs/<?php echo esc_attr( $atts['video-id'] ); ?>-720.jpg" />
		<meta itemprop="contentUrl" content="https://content.jwplatform.com/videos/<?php echo esc_attr( $atts['video-id'] ); ?>.mp4" />
	<?php endif; ?>
</div>

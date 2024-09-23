<?php
/**
 * AMP Ad Injection Sanitizer Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\AMP;

use AMP_DOM_Utils;
use AMP_Base_Sanitizer;
use DOMElement;
use DOMText;

/**
 * AMP Ad Injection Sanitizer Class
 */
class Ad_Injection_Sanitizer extends AMP_Base_Sanitizer {

	private $body;
	private $site_id;

	private $dfp_account = '18190176';
	private $ad_unit_prefix = 'AdThrive';
	private $page_width = 360;
	private $page_height = 640;
	private $pixels_per_char = .5;

	/**
	 * Class constructor
	 */
	public function __construct( $dom, $args = array() ) {
		parent::__construct( $dom, $args );

		$this->site_id = $args['site_id'];
		$this->body = $this->get_body_node();
	}

	/**
	 * Sanitize
	 *
	 * 25 lines / page
	 * 50 chars / line
	 * 50 x 25 = 1250 chars/page
	 * 600 / 1250 = .5 pixels/char
	 *
	 * Page height = 640px
	 * Page width = 360px
	 */
	public function sanitize() {
		$disabled = false;

		if ( is_singular() ) {
			$disabled = $this->are_ads_disabled();
		}

		if ( ! $disabled ) {
			if ( $this->body->getElementsByTagName( 'p' )->length > 1 ) {
				$this->inject_header();

				$this->inject_sticky_footer();

				$this->inject_content();
			}
		}
	}

	/**
	 * Inject a sticky ad component at the start of the content
	 */
	private function inject_header() {
		$header_ad = $this->create_wrapped_ad( 'Header', 1, 'adthrive-header', 320, 100, '320x50' );
		$this->body->insertBefore( $header_ad, $this->body->firstChild );
	}

	/**
	 * Inject a sticky ad component at the end of the content
	 */
	private function inject_sticky_footer() {
		$sticky_footer_ad = $this->create_sticky_ad( 'Footer', 1, 320, 100, '320x50' );
		$this->body->appendChild( $sticky_footer_ad );
	}

	/**
	 * Inject fluid ad components in the content
	 */
	private function inject_content() {
		$ad_spacing = $this->page_height;

		$spacing = 0;
		$content_ads = 0;

		foreach ( $this->body->getElementsByTagName( '*' ) as $child ) {
			if ( $spacing > $ad_spacing ) {
				$content_ads++;

				$content_ad = $this->create_wrapped_fluid_ad( 'Content', $content_ads, 'adthrive-content' );

				$child->parentNode->insertBefore( $content_ad, $child );

				$spacing = 0;
			}

			if ( $content_ads >= 9 ) {
				break;
			}

			if ( $child instanceof DOMElement || $child instanceof DOMText ) {
				$spacing += strlen( $child->nodeValue ) * $this->pixels_per_char;

				if ( 'amp-img' === $child->nodeName ) {
					$spacing += $this->get_image_height( $child );
				} elseif ( $child->hasChildNodes() ) {
					$images = $child->getElementsByTagName( 'amp-img' );

					foreach ( $images as $image ) {
						$spacing += $this->get_image_height( $image );
					}
				}
			}
		}
	}

	/**
	 * Creates a fix sized ad component wrapped in a div
	 */
	private function create_wrapped_ad( $location, $sequence, $class, $width, $height, $sizes ) {
		$wrapper = AMP_DOM_Utils::create_node( $this->dom, 'div', [
			'class' => 'adthrive-ad ' . $class,
		] );

		$attributes = [
			'width' => $width,
			'height' => $height,
			'data-multi-size' => $sizes,
			'data-multi-size-validation' => 'false',
		];

		$ad = $this->create_ad( $location, $sequence, $attributes );

		$wrapper->appendChild( $ad );

		return $wrapper;
	}

	/**
	 * Creates an ad component wrapped in an amp-sticky-ad component
	 */
	private function create_sticky_ad( $location, $sequence, $width, $height, $sizes ) {
		$sticky_ad = AMP_DOM_Utils::create_node( $this->dom, 'amp-sticky-ad', [
			'layout' => 'nodisplay',
			'class' => 'adthrive-footer',
		] );

		$attributes = [
			'width' => $width,
			'height' => $height,
			'data-multi-size' => $sizes,
			'data-multi-size-validation' => 'false',
			'data-enable-refresh' => 30,
		];

		$ad = $this->create_ad( $location, $sequence, $attributes );

		$sticky_ad->appendChild( $ad );

		return $sticky_ad;
	}

	/**
	 * Creates a fuid sized ad component wrapped in a div
	 */
	private function create_wrapped_fluid_ad( $location, $sequence, $class ) {
		$wrapper = AMP_DOM_Utils::create_node( $this->dom, 'div', [
			'class' => 'adthrive-ad ' . $class,
		] );

		$attributes = [
			'layout' => 'fluid',
			'height' => 'fluid',
			'width' => '320',
		];

		$ad = $this->create_ad( $location, $sequence, $attributes );

		$wrapper->appendChild( $ad );

		return $wrapper;
	}

	/**
	 * Creates a ad component
	 */
	private function create_ad( $location, $sequence, $attributes ) {
		$ad_unit = $this->ad_unit_prefix . '_' . $location . '_' . $sequence;
		$slot = '/' . $this->dfp_account . '/' . $ad_unit . '/' . $this->site_id;

		$default = [
			'type' => 'doubleclick',
			'data-slot' => $slot,
			'json' => wp_json_encode([
				'targeting' => [
					'siteId' => $this->site_id,
					'location' => $location,
					'sequence' => $sequence,
					'refresh' => '00',
					'amp' => 'true',
				],
			]),
		];

		return AMP_DOM_Utils::create_node( $this->dom, 'amp-ad', array_merge( $default, $attributes ) );
	}

	/**
	 * Get the image height scaled based on the page width
	 *
	 * @return int The scaled image height
	 */
	private function get_image_height( $image ) {
		if ( $image->getAttribute( 'layout' ) === 'fixed' && $image->getAttribute( 'width' ) > 0 ) {
			$height = $image->getAttribute( 'height' );
			$width = $image->getAttribute( 'width' );

			$scale = $this->page_width / $width;

			return $height * $scale;
		}

		return $this->page_height / 2;
	}

	/**
	 * Checks if ads are disabled for the current post
	 *
	 * @return boolean True if ads should be disabled
	 */
	private function are_ads_disabled() {
		global $post;

		$disable_all = get_post_meta( get_the_ID(), 'adthrive_ads_disable', true );
		$disable_content_ads = get_post_meta( get_the_ID(), 'adthrive_ads_disable_content_ads', true );

		$disabled_categories = [];
		$disabled_tags = [];

		if ( isset( $adthrive_ads['disabled_categories'] ) ) {
			$disabled_categories = $adthrive_ads['disabled_categories'];
		}

		if ( isset( $adthrive_ads['disabled_tags'] ) ) {
			$disabled_tags = $adthrive_ads['disabled_tags'];
		}

		$categories = get_the_category( $post->ID );
		$tags = get_the_tags( $post->ID );

		$category_names = is_array( $categories ) ? array_map( array( $this, 'pluck_name' ), $categories ) : array();
		$tag_names = is_array( $tags ) ? array_map( array( $this, 'pluck_name' ), $tags ) : array();

		$disable_category = is_array( $disabled_categories ) && array_intersect( $disabled_categories, $category_names );
		$disable_tag = is_array( $disabled_tags ) && array_intersect( $disabled_tags, $tag_names );

		$disable_email = preg_match( '/([A-Z0-9._%+-]+(@|%(25)*40)[A-Z0-9.-]+\.[A-Z]{2,})/i', filter_input( INPUT_SERVER, 'REQUEST_URI' ) );

		return $disable_all || in_array( 'noads', $tag_names, true ) || $disable_category || $disable_tag || $disable_email;
	}

	/**
	 * Gets the object name
	 *
	 * @param object $obj An object with a name property
	 *
	 * @return string The object name
	 */
	private function pluck_name( $obj ) {
		return $obj->name;
	}
}

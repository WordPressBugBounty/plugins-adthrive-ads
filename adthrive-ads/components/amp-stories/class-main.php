<?php
/**
 * AMP Stories Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\AMP_Stories;

/**
 * Main class
 */
class Main {
	private $dfp_account = '18190176';

	private $adthrive_ads;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->adthrive_ads = get_option( 'adthrive_ads' );
	}

	/**
	 * Add hooks
	 */
	public function setup() {
		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 11, 1 );

		if ( isset( $this->adthrive_ads['site_id'] ) && isset( $this->adthrive_ads['amp_stories'] ) && 'on' === $this->adthrive_ads['amp_stories'] ) {
			add_action( 'web_stories_print_analytics', array( $this, 'web_stories_print_analytics' ) );
		}

		$site_ads = new \AdThrive_Ads\SiteAds();
		$site_js_json = $site_ads->get_site_js();
		$site_js = json_decode( $site_js_json );
		if ( null !== $site_js && isset( $site_js->adOptions ) ) {
			$ad_options = $site_js->adOptions;
			if ( isset( $ad_options->gamMCMEnabled ) && $ad_options->gamMCMEnabled && isset( $ad_options->gamMCMChildNetworkCode ) ) {
				$this->dfp_account = $this->dfp_account . ',' . $ad_options->gamMCMChildNetworkCode;
			}
		}
	}

	/**
	 * Get the local file content
	 */
	private function get_file_content( $filename ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( \WP_Filesystem() ) {
			global $wp_filesystem;

			return $wp_filesystem->get_contents( ADTHRIVE_ADS_PATH . $filename );
		}

		return false;
	}

	/**
	 * Add the AMP Story Auto Ads
	 */
	public function web_stories_print_analytics() {
		$site_id = $this->adthrive_ads['site_id'];
		$slot = '/' . $this->dfp_account . '/AdThrive_WebStories_1/' . $site_id;
		?>
		<amp-story-auto-ads>
			<script type="application/json">
				{
					"ad-attributes": {
						"type": "doubleclick",
						"data-slot": "<?php echo esc_js( $slot ); ?>",
						"json": {
							"targeting": {
								"siteId": "<?php echo esc_js( $site_id ); ?>",
								"location": "WebStories",
								"sequence": "1",
								"refresh": "00",
								"amp": "true",
								"nref": "0"
							}
						}

					}
				}
			</script>
		</amp-story-auto-ads>
		<?php
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field( array(
			'name' => __( 'Web Stories', 'adthrive_ads' ),
			'desc' => __( 'Enable Web Stories ads
				</br>Learn more about monetizing Web Stories <a target="_blank" href="https://help.raptive.com/hc/en-us/articles/360056144432">here</a>.
			', 'adthrive_ads' ),
			'id' => 'amp_stories',
			'type' => 'checkbox',
		) );

		return $cmb;
	}
}

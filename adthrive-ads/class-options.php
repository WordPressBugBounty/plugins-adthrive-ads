<?php
/**
 * Options Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads;

/**
 * Options Plugin class
 */
class Options {

	/**
	 * Option key, and option page slug
	 *
	 * @var string
	 */
	public static $key;

	/**
	 * Options page metabox id
	 *
	 * @var string
	 */
	protected $metabox_id;

	/**
	 * Options title
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Options Page title
	 *
	 * @var string
	 */
	protected $page_title;

	/**
	 * Options Menu title
	 *
	 * @var string
	 */
	protected $menu_title;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::$key = 'adthrive_ads';
		$this->metabox_id = self::$key . '_metabox';
		$this->page_title = __( 'Raptive Ads', 'adthrive_ads' );
		$this->menu_title = __( 'Ads', 'adthrive_ads' );
	}

	/**
	 * Setup hooks
	 *
	 * @since 1.0.0
	 */
	public function setup() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ), 20 );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'update_option_' . self::$key, array( $this, 'update_option' ), 10, 3 );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  1.0.0
	 */
	public function init() {
		register_setting( self::$key, self::$key );
	}

	/**
	 * Add menu options page
	 *
	 * @since 1.0.0
	 */
	public function add_options_page() {
		$options_page = $this->add_submenu( $this->page_title, $this->menu_title, self::$key, array( $this, 'admin_page_display' ) );

		// Include CMB CSS in the head to avoid flash of unstyled content.
		add_action( "admin_print_styles-{$options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );

		add_action( 'load-' . $options_page, array( $this, 'page_loaded' ) );
	}

	/**
	 * Add an AdThrive submenu page.
	 *
	 * @global array $submenu
	 *
	 * @param String $page_title The text to be displayed in the title.
	 * @param String $menu_title The text to be displayed in menu.
	 * @param String $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @param callable $function The function to be called to output the content for this page.
	 * @return false|string The resulting page's hook_suffix, or false if the user does not have the capability required.
	 */
	public function add_submenu( $page_title, $menu_title, $menu_slug, $function ) {
		global $submenu;

		if ( ! isset( $submenu['adthrive'] ) ) {
			$menu_slug = 'adthrive';

			add_menu_page( 'Raptive Ads', 'Raptive Ads', 'manage_options', 'adthrive', '', 'dashicons-welcome-widgets-menus', '82.02132013' );
		}

		return add_submenu_page( 'adthrive', $page_title, $menu_title, 'manage_options', $menu_slug, $function );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  1.0.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2_options_page <?php esc_attr_e( self::$key ); ?>">
			<h2><?php esc_html_e( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, self::$key, array( 'cmb_styles' => false ) ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 *
	 * @since  1.0.0
	 */
	public function add_options_page_metabox() {
		$cmb = new_cmb2_box( array(
			'id' => $this->metabox_id,
			'hookup' => false,
			'show_on' => array(
				'key' => 'options-page',
				'value' => array( self::$key ),
			),
		) );

		apply_filters( 'adthrive_ads_options', $cmb );
	}

	/**
	 * Called when on the AdThrive Ads menu page
	 *
	 * @since  1.0.0
	 */
	public function page_loaded() {
		do_action( 'adthrive_admin_loaded' );
	}

	/**
	 * Called when the adthrive_ads option is updated and dispatches a new action
	 */
	public function update_option( $old_value, $value, $option ) {
		do_action( 'adthrive_ads_updated', $old_value, $value, $option );
	}

	/**
	 * Get the field value or default if it's not set or doesn't exist.
	 *
	 * This is mostly barrowed from CMB2 to allow gettings options before
	 * CMB2 is loaded (cmb2/includes/CMB2_Options.php).
	 *
	 * @since 1.0.0
	 * @param String $field     Options array field name
	 * @param String $default   Default value
	 * @return mixed            Option value
	 */
	public static function get( $field = 'all', $default = false ) {
		$opts = self::all();

		if ( 'all' === $field ) {
			return $opts;
		} elseif ( is_array( $opts ) && array_key_exists( $field, $opts ) ) {
			return false !== $opts[ $field ] ? $opts[ $field ] : $default;
		}

		return $default;
	}

	/**
	 * Get all option values.
	 *
	 * @since 1.0.0
	 * @param String $default   Default value
	 * @return mixed            Option value
	 */
	public static function all( $default = null ) {
		return get_option( self::$key, $default );
	}

	/**
	 * Returns remote settings for the plugin or false if they cannot be retrieved
	 */
	public static function get_plugin_settings() {
		$data = get_transient( 'adthrive_plugin_settings' );

		if ( false === $data ) {
			$request = wp_remote_get( 'https://ads.adthrive.com/api/v1/core/cms/features' );

			if ( is_wp_error( $request ) ) {
				return false;
			}
			$body = wp_remote_retrieve_body( $request );
			$data = json_decode( $body, true );

			if ( null !== $data ) {
				set_transient( 'adthrive_plugin_settings', $data, 3600 );
			}
		}

		return $data;
	}

	/**
	 * Save value as Adthrive WP Option
	 */
	public static function save_to_option( $option_name, $content ) {
		$adthrive_options = get_option( 'adthrive_options' );
		if ( false === $adthrive_options ) {
			$adthrive_options = array();
		}

		$adthrive_options[ $option_name ] = array(
			'content' => $content,
			'timestamp' => time(),
		);

		return update_option( 'adthrive_options', $adthrive_options );
	}

	/**
	 * Remove Adthrive WP Option
	 */
	public static function remove_option( $option_name ) {
		$adthrive_options = get_option( 'adthrive_options' );
		if ( false === $adthrive_options ) {
			$adthrive_options = array();
		}

		unset( $adthrive_options[ $option_name ] );

		return update_option( 'adthrive_options', $adthrive_options );
	}
}

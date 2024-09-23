<?php
/**
 * Ads Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Ads;

/**
 * Main class
 */
class Main {

	/**
	 * Content types to support
	 *
	 * @var array OBJECT_TYPE_POST
	 */
	const OBJECT_TYPES = array( 'page', 'post' );

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ), 10, 1 );

		add_action( 'cmb2_admin_init', array( $this, 'all_objects' ) );

		add_action( 'wp_head', array( $this, 'head_scripts' ), 1 );

		add_filter( 'body_class', array( $this, 'body_class' ) );

		// 21 is higher than priority 20 set by many of our pubs themes, meaning classes will be added after the theme class-stripping occurs
		add_filter( 'body_class', array( $this, 'adthrive_cat_class' ), 21 );

		add_action( 'wp_ajax_adthrive_terms', array( $this, 'ajax_terms' ) );

	}

	/**
	 * Init hook - check for parameter to update downloaded file
	 */
	public function init() {
		if ( isset( $_GET['atSyncStatus'] ) ) {
			header( 'Content-Type: application/json; charset=utf-8' );

			$adthrive_options = get_option( 'adthrive_options' );
			$return = '';

			if ( $adthrive_options ) {
				$status_type = sanitize_text_field( wp_unslash( $_GET['atSyncStatus'] ) );
				switch ( $status_type ) {
					case 'site_js':
						// Fall through to next case
					case 'site_css':
						if ( isset( $adthrive_options[ $_GET['atSyncStatus'] ] ) ) {
							$return = array(
								'timestamp' => $adthrive_options[ $status_type ]['timestamp'],
								$status_type => $adthrive_options[ $status_type ]['content'],
							);
						} else {
							$error = "{$status_type} not set in adthrive_options";
						}
						break;
					case 'cls':
						if ( isset( $adthrive_options['cls-deployments'] ) ) {
							$return = $adthrive_options['cls-deployments']['content'];
						} else {
							$error = 'cls-deployments not set in adthrive_options';
						}
						break;
				}
			} else {
				$error = 'adthrive_options not set';
			}
			if ( isset( $error ) ) {
				print wp_json_encode( array( 'error' => $error ) );
			} else {
				print wp_json_encode( $return );
			}
			exit();
		}
	}

	/**
	 * AJAX method to get terms with the matched search query for the specified taxonomy
	 */
	public function ajax_terms() {
		if ( isset( $_GET['query'] ) ) {
			$query = sanitize_text_field( wp_unslash( $_GET['query'] ) );
		}

		if ( isset( $_GET['taxonomy'] ) ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) );
		}

		wp_send_json( $this->get_term_selectize( $taxonomy, array( 'search' => $query ) ) );
	}

	/**
	 * Adds classes to disable ads based on the plugin settings
	 *
	 * @param string|array $classes One or more classes to add to the class list.
	 */
	public function body_class( $classes ) {
		if ( is_singular() ) {
			global $post;

			$disable = get_post_meta( get_the_ID(), 'adthrive_ads_disable', true );
			$disable_content_ads = get_post_meta( get_the_ID(), 'adthrive_ads_disable_content_ads', true );
			$disable_auto_insert_videos = get_post_meta( get_the_ID(), 'adthrive_ads_disable_auto_insert_videos', true );
			$re_enable_ads_on = get_post_meta( get_the_ID(), 'adthrive_ads_re_enable_ads_on', true );

			$disabled_categories = \AdThrive_Ads\Options::get( 'disabled_categories' );
			$disabled_tags = \AdThrive_Ads\Options::get( 'disabled_tags' );

			$categories = get_the_category( $post->ID );
			$tags = get_the_tags( $post->ID );

			$category_names = is_array( $categories ) ? array_map( array( $this, 'pluck_name' ), $categories ) : array();
			$tag_names = is_array( $tags ) ? array_map( array( $this, 'pluck_name' ), $tags ) : array();

			if ( ! $re_enable_ads_on || false === trim( $re_enable_ads_on ) || $re_enable_ads_on > time() ) {
				if ( $disable ) {
					$classes[] = 'adthrive-disable-all';
				}

				if ( $disable_content_ads || in_array( 'noads', $tag_names, true ) ) {
					$classes[] = 'adthrive-disable-content';
				}

				if ( $disable_auto_insert_videos || in_array( 'noads', $tag_names, true ) ) {
					$classes[] = 'adthrive-disable-video';
				}
			}

			if ( is_array( $disabled_categories ) && array_intersect( $disabled_categories, $category_names ) ) {
				$classes[] = 'adthrive-disable-all';
			} elseif ( is_array( $disabled_tags ) && array_intersect( $disabled_tags, $tag_names ) ) {
				$classes[] = 'adthrive-disable-all';
			}
		} elseif ( is_404() ) {
			$classes[] = 'adthrive-disable-all';
		}

		return $classes;
	}

	/**
	 * Adds Content Specific Video Playlist category classes to the body based on the plugin settings
	 *
	 * @param string|array $classes One or more classes to add to the class list.
	 */
	public function adthrive_cat_class( $classes ) {
		if ( is_singular() ) {
			global $post;
			$categories = get_the_category( $post->ID );
			$category_names = is_array( $categories ) ? array_map( array( $this, 'pluck_name' ), $categories ) : array();

			if ( 'on' === \AdThrive_Ads\Options::get( 'content_specific_playlists' ) ) {
				$category_body_classes = is_array( $categories ) ? array_map( array( $this, 'pluck_body_class' ), $categories ) : array();
				$classes = array_merge( $classes, $category_body_classes );
			}
		}
		return $classes;
	}

	/**
	 * Gets the object name
	 *
	 * @param object $obj    An object with a name property
	 *
	 * @return string The object name
	 */
	private function pluck_name( $obj ) {
		return $obj->name;
	}

	/**
	 * Gets the object nicename
	 *
	 * @param object $obj    An object with a nicename property
	 *
	 * @return string Adthrive class name formatted for use in the body tag
	 */
	private function pluck_body_class( $obj ) {
		return 'adthrive-cat-' . $obj->category_nicename;
	}

	/**
	 * Gets just the object name property
	 *
	 * @param object $obj    An object with a name property
	 *
	 * @return string An object with just a name property
	 */
	private function get_selectize( $obj ) {
		return array(
			'text' => $obj->name,
			'value' => $obj->name,
		);
	}

	/**
	 * Add fields to the options metabox for page and posts
	 */
	public function all_objects() {
		$post_meta = new_cmb2_box( array(
			'id' => 'adthrive_ads_object_metabox',
			'title' => __( 'Raptive Ads', 'adthrive_ads' ),
			'object_types' => self::OBJECT_TYPES,
		) );

		$post_meta->add_field( array(
			'name' => __( 'Disable all ads', 'adthrive_ads' ),
			'id' => 'adthrive_ads_disable',
			'type' => 'checkbox',
		) );

		$post_meta->add_field( array(
			'name' => __( 'Disable content ads', 'adthrive_ads' ),
			'id' => 'adthrive_ads_disable_content_ads',
			'type' => 'checkbox',
		) );

		$post_meta->add_field( array(
			'name' => __( 'Disable auto-insert video players', 'adthrive_ads' ),
			'id' => 'adthrive_ads_disable_auto_insert_videos',
			'type' => 'checkbox',
		) );

		$post_meta->add_field( array(
			'name' => __( 'Re-enable ads on', 'adthrive_ads' ),
			'desc' => __( 'All ads on this post will be enabled on the specified date', 'adthrive_ads' ),
			'id'   => 'adthrive_ads_re_enable_ads_on',
			'type' => 'text_date_timestamp',
		) );

		if ( \AdThrive_Ads\Options::get( 'disable_video_metadata' ) === 'on' ) {
			$post_meta->add_field( array(
				'name' => __( 'Enable Video Metadata', 'adthrive_ads' ),
				'desc' => __( 'Enable adding metadata to video player on this post', 'adthrive_ads' ),
				'id'   => 'adthrive_ads_enable_metadata',
				'type' => 'checkbox',
			) );
		} else {
			$post_meta->add_field( array(
				'name' => __( 'Disable Video Metadata', 'adthrive_ads' ),
				'desc' => __( 'Disable adding metadata to video player on this post', 'adthrive_ads' ),
				'id'   => 'adthrive_ads_disable_metadata',
				'type' => 'checkbox',
			) );
		}

		if ( \AdThrive_Ads\Options::get( 'disable_admin_ads' ) === 'on' ) {
			$post_meta->add_field(
				array(
					'name' => __( 'Enable ads when previewing post', 'adthrive_ads' ),
					'desc' => __( 'Enable all ads when previewing a post or customizing a theme in WordPress Admin', 'adthrive_ads' ),
					'id'   => 'adthrive_ads_enable_admin_ads',
					'type' => 'checkbox',
				)
			);
		} else {
			$post_meta->add_field(
				array(
					'name' => __( 'Disable ads when previewing post', 'adthrive_ads' ),
					'desc' => __( 'Disable all ads when previewing a post or customizing a theme in WordPress Admin', 'adthrive_ads' ),
					'id'   => 'adthrive_ads_disable_admin_ads',
					'type' => 'checkbox',
				)
			);
		}

	}

	/**
	 * Add fields to the options metabox for the plugin settings.
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		$cmb->add_field( array(
			'name' => __( 'Site Id', 'adthrive_ads' ),
			'desc' => __( 'Add your Raptive Site ID', 'adthrive_ads' ),
			'id' => 'site_id',
			'type' => 'text',
			'attributes' => array(
				'required' => 'required',
				'pattern' => '[0-9a-f]{24}',
				'title' => 'The site id needs to match the one provided by Raptive exactly',
			),
		) );

		$cmb->add_field( array(
			'name' => 'Disabled for Categories',
			'desc' => 'Disable ads for the selected categories.',
			'id' => 'disabled_categories',
			'type' => 'text',
			'escape_cb' => array( $this, 'selectize_escape' ),
			'sanitization_cb' => array( $this, 'selectize_sanitize' ),
		) );

		$cmb->add_field( array(
			'name' => 'Disabled for Tags',
			'desc' => 'Disable ads for the selected tags.',
			'id' => 'disabled_tags',
			'type' => 'text',
			'escape_cb' => array( $this, 'selectize_escape' ),
			'sanitization_cb' => array( $this, 'selectize_sanitize' ),
		) );

		$cmb->add_field( array(
			'name' => 'Disable Video Metadata',
			'desc' => 'Disable adding metadata to video players. Caution: This is a site-wide change. Only choose if metadata is being loaded another way.',
			'id' => 'disable_video_metadata',
			'type' => 'checkbox',
		) );

		$cmb->add_field( array(
			'name' => 'CLS Optimization',
			'desc' => "Enable solution to reduce ad-related CLS
			</br>Clear your site's cache after saving this setting to apply the update across your site. Get more details on CLS optimization <a href='https://help.raptive.com/hc/en-us/articles/360048229151' target='_blank'>here.</a>",
			'id' => 'cls_optimization',
			'type' => 'checkbox',
			'default_cb' => array( $this, 'cls_checkbox_default' ),
		) );

		$cmb->add_field(
			array(
				'name' => 'Disable ads within WordPress Admin',
				'desc' => 'Disable all ads when previewing a post or customizing a theme in WordPress Admin. Recommended when using post or page builders.',
				'id'   => 'disable_admin_ads',
				'type' => 'checkbox',
			)
		);

		return $cmb;
	}

	/**
	 * Returns true unless the checkbox was explicitly unchecked.
	 * Note: Working with the CMB2 checkbox is odd. This function attempts to:
	 * 1. Default the checkbox to 'on'
	 * 2. If the `adthrive_ads` options are stored in the database, look for the `cls_optimization`
	 * option in they array.
	 * 3. If the `cls_optimization` option is not stored, that means a user explicitly disabled the
	 * option so don't force it to true.
	 */
	public function cls_checkbox_default( $field_args, $field ) {
		$cls_optimization = 'on';
		$adthrive_options = get_option( $field->object_id, false );
		if ( $adthrive_options ) {
			if ( isset( $adthrive_options['cls_optimization'] ) ) {
				$cls_optimization = $adthrive_options['cls_optimization'];
			} else {
				$cls_optimization = null;
			}
		}
		return isset( $cls_optimization ) ? true : false;
	}

	/**
	 * Convert a selectize field array value to string
	 *
	 * @param  mixed $value The actual field value.
	 * @return String Field value converted to a string
	 */
	public function selectize_escape( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		}

		return ! empty( $value ) ? implode( ',', $value ) : null;
	}

	/**
	 * Convert a selectize field value to array
	 *
	 * @param  mixed $value The actual field value.
	 * @return array Field value converted to an array
	 */
	public function selectize_sanitize( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}

		return ! empty( $value ) ? explode( ',', $value ) : null;
	}

	/**
	 * Check if the user is within admin
	 *
	 * True if
	 * - user is editing a post/page
	 * - user is customizing a theme
	 * - user is previewing an unpublished post/page
	 * - user is viewing a published post/page while logged into WP and has higher roles than subscriber & customer
	 *
	 * @return boolean
	 */
	public function is_admin() {
		$is_admin = false;

		if ( is_admin() ) {
			// User is in the admin.
			$is_admin = true;
		} elseif ( is_customize_preview() ) {
			// User is customizing a theme.
			$is_admin = true;
		} elseif ( is_preview() ) {
			// User is previewing an unpublished post/page.
			$is_admin = true;
		} elseif ( is_user_logged_in() ) {
			// User is logged into WP. Check if user has an edit capability ( all roles beyond Subscriber ).
			if ( current_user_can( 'edit_posts' ) ) {
				$is_admin = true;
			}
		}

		return $is_admin;
	}

	/**
	 * Check if admin ads are disabled
	 *
	 * @return boolean
	 */
	public function admin_ads_disabled() {
		// Check site level settings.
		$disable_site_admin_ads = \AdThrive_Ads\Options::get( 'disable_admin_ads' ) === 'on';

		// Check page/post level settings if it's overriding site level settings.
		if ( is_singular( self::OBJECT_TYPES ) ) {
			if ( $disable_site_admin_ads ) {
				$enable_post_admin_ads = get_post_meta( get_the_ID(), 'adthrive_ads_enable_admin_ads', true ) === 'on';
				return ! $enable_post_admin_ads;
			} else {
				$disable_post_admin_ads = get_post_meta( get_the_ID(), 'adthrive_ads_disable_admin_ads', true ) === 'on';
				return $disable_post_admin_ads;
			}
		}

		return $disable_site_admin_ads;
	}

	/**
	 * Add header scripts for ads.
	 */
	public function head_scripts() {
		if ( $this->is_admin() && $this->admin_ads_disabled() ) {
			// Don't load ads if the user is in the admin and admin ads are disabled.
			return;
		}

		$this->ad_head();
		$this->adthrive_preload();
	}

	/**
	 * Add the AdThrive ads script
	 */
	private function ad_head() {
		$data['site_id'] = \AdThrive_Ads\Options::get( 'site_id' );
		$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );
		// if cls_optimization is false in the plugin, cls could still be on in ads min, so we set this here
		$data['plugin_debug'] = isset( $_GET['plugin_debug'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['plugin_debug'] ) ) ? 'true' : 'false';
		$thrive_architect_enabled = isset( $_GET['tve'] ) && sanitize_key( $_GET['tve'] ) === 'true';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$widget_preview_active = strpos( $request_uri, 'wp-admin/widgets.php' ) !== false;
		} else {
			$widget_preview_active = false;
		}

		if ( isset( $data['site_id'] ) && preg_match( '/[0-9a-f]{24}/i', $data['site_id'] ) && ! $thrive_architect_enabled && ! $widget_preview_active ) {
			$body_classes = $this->body_class( [] );
			if ( 'on' === $cls_optimization ) {
				$cls_data = $this->parse_cls_deployment();
				$data = array_merge( $data, $cls_data );

				$site_ads = new \AdThrive_Ads\SiteAds();
				$data['site_js'] = $site_ads->get_site_js();
				$data['site_css'] = $this->get_option_value( 'site_css' );
				$disable_all = in_array( 'adthrive-disable-all', $body_classes, true );
				if ( ! empty( $data['site_js'] ) ) {
					$decoded_data = json_decode( $data['site_js'] );
					if ( $this->has_essential_site_ads_keys( $decoded_data ) ) {
						require 'partials/insertion-includes.php';
						add_action('wp_head', function() use ( $data ) {
							$this->insert_cls_file( 'cls-disable-ads', $data );
						}, 100 );

						if ( ! $disable_all && null !== $decoded_data && isset( $decoded_data->adUnits ) ) {
							$adunits = $decoded_data->adUnits;
							foreach ( $adunits as $adunit ) {
								if ( 'Header' === $adunit->location && isset( $adunit->dynamic ) ) {
									if ( ! isset( $adunit->dynamic->spacing ) ) {
										$adunit->dynamic->spacing = 0;
									}
									if ( ! isset( $adunit->dynamic->max ) ) {
										$adunit->dynamic->max = 0;
									} else {
										$adunit->dynamic->max = (int) floor( $adunit->dynamic->max );
									}
									if ( ! isset( $adunit->sequence ) ) {
										$adunit->sequence = 1;
									}
									if ( true === $adunit->dynamic->enabled && 1 === $adunit->dynamic->max && 0 === $adunit->dynamic->spacing && 1 === $adunit->sequence ) {
										add_action('wp_head', function() use ( $data ) {
											$this->insert_cls_file( 'cls-header-insertion', $data );
										}, 101 );
									}
								}
							}
						}

						add_action('wp_footer', function() use ( $data ) {
							$this->insert_cls_file( 'cls-insertion', $data );
							$this->check_cls_insertion();
						}, 1 );
					} else {
						require 'partials/sync-error.php';
					}
				}
			}

			require 'partials/ads.php';
		}
	}

	/**
	 * Adthrive Preload hook - adds dns prefetch and preconnect elements to adthrive resources
	 */
	private function adthrive_preload() {
		echo '<link rel="dns-prefetch" href="https://ads.adthrive.com/">';
		echo '<link rel="preconnect" href="https://ads.adthrive.com/">';
		echo '<link rel="preconnect" href="https://ads.adthrive.com/" crossorigin>';
	}

	/**
	 * Returns true if all expected siteAds keys exist: siteId, siteName, adOptions, breakpoints, adUnits
	 * If these keys are not present, there was an issue syncing the siteAds data, and we shouldnt load any CLS
	 */
	private function has_essential_site_ads_keys( $site_ads_object ) {
		return ( null !== $site_ads_object && isset( $site_ads_object->siteId ) && isset( $site_ads_object->siteName ) && isset( $site_ads_object->adOptions ) && isset( $site_ads_object->breakpoints ) && isset( $site_ads_object->adUnits ) );
	}

	/**
	 * Check if cls insertion script tag has been inserted to page, if not set injectedFromPlugin to false
	 */
	private function check_cls_insertion() {
		$cls = "'cls-'";
		// phpcs:disable
		echo '<script data-no-optimize="1" data-cfasync="false">';
		echo '(function () {';
		echo 'var clsElements = document.querySelectorAll("script[id^=' . $cls . ']"); window.adthriveCLS && clsElements && clsElements.length === 0 ? window.adthriveCLS.injectedFromPlugin = false : ""; ';
		echo '})();';
		echo '</script>';
		// phpcs:enable
	}

	/**
	 * Returns hash value specified from the url params
	 */
	public function get_remote_cls_hash() {
		return isset( $_GET['plugin_remote_cls'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin_remote_cls'] ) ) : '';
	}

	/**
	 * Get cls file endpoint url for the hash. If no hash specified, then return empty string
	 */
	public function get_remote_cls_file_url( $filename, $data ) {
		$remote_cls_hash = $this->get_remote_cls_hash();
		if ( '' !== $remote_cls_hash ) {
			return 'https://ads.adthrive.com/builds/core/' . $remote_cls_hash . '/js/cls/' . $filename . '.min.js?ts=' . strval( time() );
		}
		return '';
	}

	private $cls_files_inserted = [];
	/**
	 * Inserts cls file content to script tag
	 * If debug options are enabled, makes request to remote url to fetch cls files.
	 */
	public function insert_cls_file( $filename, $data ) {
		if ( in_array( $filename, $this->cls_files_inserted, true ) ) {
			// Skip insertion when filename already inserted
			return;
		}
		array_push( $this->cls_files_inserted, $filename );

		$remote_cls_file_url = $this->get_remote_cls_file_url( $filename, $data );
		// phpcs:disable
		if ( '' !== $remote_cls_file_url ) {
			echo "<script data-no-optimize='1' data-cfasync='false' id='" . $filename . "-remote' src='" . $remote_cls_file_url . "'></script>";
		} else {
			$cls_content = $this->get_cls_file( $filename, $data );
			if ( '' !== $cls_content['branch'] ) {
				echo "<script data-no-optimize='1' data-cfasync='false' id='" . $filename . "-" . $cls_content['branch'] . "'>";
				echo $cls_content['content'];
				echo "</script>";
			}
		}
		// phpcs:enable
	}

	/**
	 * Get cls insertion file for the hash, if file for the hash is not found, return stable version
	 */
	public function get_cls_file( $filename, $data ) {
		if ( isset( $data['cls_branch'] ) ) {
			if ( isset( $data['cls_bucket'] ) && 'prod' !== $data['cls_bucket'] ) {
				$option_content = $this->get_option_value( $filename . '.' . $data['cls_branch'] );
				if ( $option_content ) {
					return array(
						'branch' => $data['cls_branch'],
						'bucket' => $data['cls_bucket'],
						'content' => $option_content,
					);
				}
			}

			$stable_option_content = $this->get_option_value( $filename . '.stable' );
			if ( $stable_option_content ) {
				return array(
					'branch' => $data['cls_branch'],
					'bucket' => $data['cls_bucket'],
					'content' => $stable_option_content,
				);
			}
		}

		return array(
			'branch' => '',
			'bucket' => '',
			'content' => '',
		);
	}

	/**
	 * Parser CLS deployment file from file system and return deployment info
	 */
	private function parse_cls_deployment() {
		$output = array();

		$cls_deployment = $this->get_option_value( 'cls-deployments' );
		if ( $cls_deployment ) {
			$output['cls_branch'] = $cls_deployment['stable'];
			$output['cls_bucket'] = 'prod';

			if ( isset( $cls_deployment['test'] ) ) {
				$output['cls_branch'] = $cls_deployment['test'];
				$output['cls_bucket'] = 'feature';
			}

			$cls_hash = $this->get_remote_cls_hash();
			if ( strlen( $cls_hash ) > 0 ) {
				$output['cls_branch'] = $cls_hash;
				$output['cls_bucket'] = 'debug';
			}
		}
		return $output;
	}

	/**
	 * Gets terms and displays them as options
	 *
	 * @param  String $taxonomy Taxonomy terms to retrieve. Default is category.
	 * @param  String|array $args Optional. get_terms optional arguments
	 * @return array An array of options that matches the CMB2 options array
	 */
	public function get_term_selectize( $taxonomy = 'category', $args = array() ) {
		$args['taxonomy'] = $taxonomy;
		$args = wp_parse_args( $args, array(
			'taxonomy' => 'category',
			'number' => 100,
		) );

		$taxonomy = $args['taxonomy'];

		$terms = (array) get_terms( $taxonomy, $args );

		return is_array( $terms ) ? array_map( array( $this, 'get_selectize' ), $terms ) : array();
	}

	/**
	 * Get the Adthrive option value from WP transient or option storage
	 */
	private function get_option_value( $option_name ) {
		$adthrive_options = get_option( 'adthrive_options' );

		if ( false === $adthrive_options ) {
			return false;
		}

		if ( isset( $adthrive_options[ $option_name ]['content'] ) ) {
			return $adthrive_options[ $option_name ]['content'];
		}

		return false;
	}
}

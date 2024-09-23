<?php
/**
 * Ads Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Ads;

/**
 * Class for scheduled events
 */
class Scheduled {

	/**
	 * Add hooks
	 */
	public function setup() {
		add_action( 'init', array( $this, 'init' ) );

		add_action( 'adthrive_cls_daily_event', array( $this, 'sync_cls_data' ) );

		add_action( 'adthrive_site_daily_event', array( $this, 'sync_site_ads_data' ) );

		add_action( 'upgrader_process_complete', array( $this, 'plugin_upgraded' ), 10, 2 );

		add_action( 'rest_api_init', array( $this, 'register_route' ) );

		add_filter( 'adthrive_ads_updated', array( $this, 'options_updated' ), 10, 3 );
	}

	/**
	 * Init hook - check for parameter to update cls insertion file
	 */
	public function init() {
		$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );

		if ( 'on' === $cls_optimization ) {
			$query_param_value = isset( $_GET['clsFileReset'] ) ? sanitize_text_field( wp_unslash( $_GET['clsFileReset'] ) ) : '';

			if ( 'true' === $query_param_value ) {
				$this->cls_file_reset();
			}
		}

		if ( isset( $_GET['atFileReset'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['atFileReset'] ) ) && $this->should_save_site_ads() ) {
			$this->site_ads_files_reset();
		}
	}

	/**
	 * Register API routes
	 */
	public function register_route() {
		$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );

		if ( 'on' === $cls_optimization ) {
			register_rest_route( 'adthrive-ads/v1', '/reset/cls',
				array(
					'method'   => 'GET',
					'callback' => array( &$this, 'cls_file_reset' ),
					'permission_callback' => '__return_true',
				) );
		}
		if ( $this->should_save_site_ads() ) {
			register_rest_route( 'adthrive-ads/v1', '/reset/siteads',
				array(
					'method'   => 'GET',
					'callback' => array( &$this, 'site_ads_files_reset' ),
					'permission_callback' => '__return_true',
				) );
		}
	}

	/**
	 * Resets cls files and echoes the results
	 */
	public function cls_file_reset() {
		header( 'Content-Type: application/json' );
		try {
			$output = array();
			$error = array();
			$output['status'] = 'success';
			$output['message'] = 'Saved all files';
			$output['version'] = ADTHRIVE_ADS_VERSION;
			$output['result'] = $this->sync_cls_data();
			echo wp_json_encode( $output );
			exit();
		} catch ( \Exception $e ) {
			http_response_code( 500 );
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage(),
				'errors' => array( $e ),
			);
			echo wp_json_encode( $output );
			exit();
		}
	}

	/**
	 * Resets site ads files and echoes the results
	 */
	public function site_ads_files_reset() {
		header( 'Content-Type: application/json' );
		try {
			$save_result = $this->save_site_ads_files();

			$output = array();

			$output['status'] = 'success';
			$output['message'] = 'Saved all files';
			$output['version'] = ADTHRIVE_ADS_VERSION;
			$output['errors'] = array();

			foreach ( $save_result as $k => $k_value ) {
				if ( is_wp_error( $k_value ) ) {
					$output['status'] = 'error';
					$output['message'] = 'Failed to save one or more files';
					$output[ $k ] = $k_value->get_error_message();
					$output['errors'][] = $k_value;
				} elseif ( false === $k_value ) {
					$output['status'] = 'error';
					$output[ $k ] = 'Failed to save file';
					$output['message'] = 'Failed to save one or more files';
					$output['errors'][] = array( 'message' => 'Failed to save ' . $k );
				} else {
					$output[ $k ] = 'Saved file';
				}
			}

			if ( 'error' === $output['status'] ) {
				http_response_code( 500 );
			}
			echo wp_json_encode( $output );

			exit();
		} catch ( \Exception $e ) {
			http_response_code( 500 );
			$output = array(
				'status' => 'error',
				'message' => $e->getMessage(),
				'errors' => array( $e ),
			);
			echo wp_json_encode( $output );
			exit();
		}
	}

	/**
	 * Returns if publisher needs site js saved
	 */
	public function should_save_site_ads() {
		$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );
		$amp_stories = \AdThrive_Ads\Options::get( 'amp_stories' );
		return 'on' === $cls_optimization || 'on' === $amp_stories;
	}

	/**
	 * Get deployment json from remote location
	 */
	public function get_deployment_json() {
		$remote = 'https://ads.adthrive.com/api/v1/core/cms/experiments?ts=' . strval( time() );
		return $this->get_remote_file( $remote );
	}

	/**
	 * Update Deployment json and cls files
	 */
	public function sync_cls_data() {
		$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );

		if ( 'on' === $cls_optimization ) {
			$status = array();
			$deployment_str = $this->get_deployment_json();
			if ( $deployment_str && strlen( $deployment_str ) > 1 ) {
				try {
					$deployment = $this->parse_deployment_json( $deployment_str );
					if ( isset( $deployment['stable'] ) ) {
						$this->delete_cls_files( false );
						if ( isset( $deployment['test'] ) ) {
							$status[ $deployment['test'] ] = $this->get_cls_files( $deployment['test'], false );
						}
						$status[ $deployment['stable'] ] = $this->get_cls_files( $deployment['stable'], true );
						$deployment['updated_at'] = time();

						$status['deployment'] = $deployment;
						\AdThrive_Ads\Options::save_to_option( 'cls-deployments', $deployment );
					}
				} catch ( \Exception $e ) {
					$status['error'] = $e->getMessage();
				}
			} else {
				$status['error'] = 'Failed to retrieve data from deployment json endpoint.';
			}
			if ( isset( $status['error'] ) ) {
				$status['error_at'] = time();
				\AdThrive_Ads\Options::save_to_option( 'cls-error', $status );
			}
			return $status;
		}
	}

	/**
	 * Parse deployment json and return object
	 */
	public function parse_deployment_json( $deployment_str ) {
		$site_id = \AdThrive_Ads\Options::get( 'site_id' );
		$deployment = array();
		$feature_hashes = array();
		$deploy_json = json_decode( $deployment_str );
		if ( isset( $deploy_json->values ) ) {
			foreach ( $deploy_json->values as $feature_branch ) {
				$feature_hashes[] = $feature_branch->test;
				foreach ( $feature_branch->sites as $site ) {
					if ( $site === $site_id ) {
						$deployment['test'] = $feature_branch->test;
						break;
					}
				}
			}
			$deployment['stable'] = $deploy_json->stable;
		}
		$deployment['feature_hashes'] = $feature_hashes;
		return $deployment;
	}

	/**
	 * Get CLS insertion files for given hash
	 */
	public function get_cls_files( $hash, $is_stable ) {
		$base_url = 'https://ads.adthrive.com/builds/core/' . $hash . '/js/cls/';
		$status = array();
		$status['insertion'] = \AdThrive_Ads\Options::save_to_option( 'cls-insertion.' . ( $is_stable ? 'stable' : $hash ), $this->get_remote_file( $base_url . 'cls-insertion.min.js' ) );
		$status['header-insertion'] = \AdThrive_Ads\Options::save_to_option( 'cls-header-insertion.' . ( $is_stable ? 'stable' : $hash ), $this->get_remote_file( $base_url . 'cls-header-insertion.min.js' ) );
		$status['disable-ads'] = \AdThrive_Ads\Options::save_to_option( 'cls-disable-ads.' . ( $is_stable ? 'stable' : $hash ), $this->get_remote_file( $base_url . 'cls-disable-ads.min.js' ) );
		return $status;
	}

	/**
	 * Make request to remote location and return content
	 */
	public function get_remote_file( $remote ) {
		$response = wp_remote_get( $remote );
		if ( is_wp_error( $response ) ) {
			return '';
		}
		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Delete existing cls files, if delete_all is true, it will delete all fines under insertion/min, otherwise keep stable files
	 */
	public function delete_cls_files( $delete_all ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( \WP_Filesystem() ) {
			global $wp_filesystem;

			if ( $wp_filesystem->is_file( 'cls-deployments.js' ) ) {
				$wp_filesystem->delete( 'cls-deployments.js' );
			}
			if ( $wp_filesystem->is_file( 'cls-error.js' ) ) {
				$wp_filesystem->delete( 'cls-error.js' );
			}

			$cls_files = list_files( ADTHRIVE_ADS_PATH . 'js/insertion/min' );
			foreach ( $cls_files as $cls_file ) {
				if ( $wp_filesystem->is_file( $cls_file ) && ( ! preg_match( '/stable.js$/', $cls_file ) || $delete_all ) ) {
					$wp_filesystem->delete( $cls_file );
				}
			}
		}

		\AdThrive_Ads\Options::remove_option( 'cls-deployments' );
		\AdThrive_Ads\Options::remove_option( 'cls-error' );
		$options = get_option( 'adthrive_options' );
		foreach ( $options as $k => $option ) {
			if ( false !== strpos( $k, 'cls' ) ) {
				if ( ! preg_match( '/stable$/', $k ) || $delete_all ) {
					\AdThrive_Ads\Options::remove_option( $k );
				}
			}
		}
	}

	/**
	 * Save site js if publisher should
	 */
	public function sync_site_ads_data() {
		if ( $this->should_save_site_ads() ) {
			$this->save_site_ads_files();
		}
	}

	/**
	 * Get and save the latest site js and css file
	 */
	private function save_site_ads_files() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp;
		$site_id = \AdThrive_Ads\Options::get( 'site_id' );
		$remote = "https://ads.adthrive.com/api/v1/siteAds/$site_id?ts=" . strval( time() );

		$successful_saves = array();

		$response = wp_remote_get( $remote );
		if ( is_wp_error( $response ) ) {
			$successful_saves['site_ads_json'] = $response;
		} else {
			$response_text = wp_remote_retrieve_body( $response );

			if ( is_array( $response ) ) {
				$successful_saves['site_ads_json'] = \AdThrive_Ads\Options::save_to_option( 'site_js', $response_text );
			}
		}

		$css_response = wp_remote_get( 'https://ads.adthrive.com/sites/' . $site_id . '/ads.min.css?ts=' . strval( time() ) );
		if ( is_wp_error( $css_response ) ) {
			$successful_saves['css_content'] = $response;
		} else {
			$css_response_text = wp_remote_retrieve_body( $css_response );
			$successful_saves['css_content'] = \AdThrive_Ads\Options::save_to_option( 'site_css', $css_response_text );
		}

		return $successful_saves;
	}

	/**
	 * Delete the site js and css file
	 */
	private function delete_site_ads_files() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		if ( \WP_Filesystem() ) {
			global $wp_filesystem;
			$filename = ADTHRIVE_ADS_PATH . 'site.js';
			$css_filename = ADTHRIVE_ADS_PATH . 'site.css';

			if ( $wp_filesystem->is_file( $filename ) ) {
				$wp_filesystem->delete( $filename );
			}
			if ( $wp_filesystem->is_file( $css_filename ) ) {
				$wp_filesystem->delete( $css_filename );
			}
		}

		\AdThrive_Ads\Options::remove_option( 'site_js' );
		\AdThrive_Ads\Options::remove_option( 'site_css' );
	}

	/**
	 * Called when the adthrive_ads option is updated
	 */
	public function options_updated( $old_value, $value, $option ) {
		if ( isset( $value['cls_optimization'] ) && 'on' === $value['cls_optimization'] ) {
			$this->sync_cls_data();
			$this->sync_site_ads_data();
		} else {
			$this->delete_cls_files( true );
			$this->delete_site_ads_files();
		}
	}

	/**
	 * Deployment json and cls files need to be updated when plugin is upgraded
	 */
	public function plugin_upgraded( $upgrader_object, $options ) {
		$current_plugin_path_name = plugin_basename( ADTHRIVE_ADS_FILE );

		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $each_plugin ) {
				if ( $each_plugin === $current_plugin_path_name ) {
					$cls_optimization = \AdThrive_Ads\Options::get( 'cls_optimization' );

					if ( 'on' === $cls_optimization ) {
						$this->sync_cls_data();
					}
					$this->sync_site_ads_data();
				}
			}
		}
	}
}

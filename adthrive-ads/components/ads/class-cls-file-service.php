<?php
/**
 * CLS File Service
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\Ads;

/**
 * Service for fetching and inserting CLS-adjacent scripts.
 */
class Cls_File_Service {
	private $cls_files_inserted = array();

	/**
	 * Returns hash value specified from the url params
	 */
	private function get_remote_cls_hash() {
		return isset( $_GET['plugin_remote_cls'] ) ? sanitize_text_field( wp_unslash( $_GET['plugin_remote_cls'] ) ) : '';
	}

	/**
	 * Get cls file endpoint url for the hash. If no hash specified, then return empty string
	 */
	private function get_remote_cls_file_url( $filename ) {
		$remote_cls_hash = $this->get_remote_cls_hash();

		if ( '' !== $remote_cls_hash ) {
			return esc_url( 'https://ads.adthrive.com/builds/core/' . $remote_cls_hash . '/js/cls/' . $filename . '.min.js?ts=' . strval( time() ) );
		}
		return '';
	}

	/**
	 * Inserts cls file content to script tag
	 * If debug options are enabled, makes request to remote url to fetch cls files.
	 *
	 * @param string $filename The script filename to insert.
	 * @param array  $data Optional. Deployment data with cls_branch/cls_bucket. If not provided, will be fetched automatically.
	 * @param array  $attrs Optional. Extra attributes to add to the script tag.
	 */
	public function insert_cls_file( $filename, $data = array(), $attrs = array() ) {
		if ( in_array( $filename, $this->cls_files_inserted, true ) ) {
			// Skip insertion when filename already inserted
			return;
		}
		array_push( $this->cls_files_inserted, $filename );

		// If no deployment data provided, fetch it with fallback to stable.
		if ( empty( $data ) || ! isset( $data['cls_branch'] ) ) {
			$data = $this->parse_cls_deployment();
			if ( empty( $data ) ) {
				$data = array(
					'cls_branch' => 'stable',
					'cls_bucket' => 'prod',
				);
			}
		}

		$extra_attrs = '';
		foreach ( $attrs as $attr_name => $attr_value ) {
			$extra_attrs .= ' ' . esc_attr( $attr_name ) . "='" . esc_attr( $attr_value ) . "'";
		}

		$remote_cls_file_url = $this->get_remote_cls_file_url( $filename );
		// phpcs:disable
		if ( '' !== $remote_cls_file_url ) {
			echo "<script data-no-optimize='1' data-cfasync='false'" . $extra_attrs . " id='" . $filename . "-remote' src='" . $remote_cls_file_url . "'></script>";
		} else {
			$cls_content = $this->get_cls_file( $filename, $data );
			if ( '' !== $cls_content['branch'] ) {
				echo "<script data-no-optimize='1' data-cfasync='false'" . $extra_attrs . " id='" . $filename . "-" . $cls_content['branch'] . "'>";
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
	 * Parse CLS deployment file from options and return deployment info.
	 * Returns branch/bucket for script insertion, respecting test branches and debug overrides.
	 *
	 * @return array Deployment data with cls_branch and cls_bucket, or empty array if not available.
	 */
	public function parse_cls_deployment() {
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
	 * Get the Adthrive option value from WP transient or option storage
	 */
	public function get_option_value( $option_name ) {
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

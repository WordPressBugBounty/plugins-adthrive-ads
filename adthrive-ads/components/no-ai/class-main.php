<?php
/**
 * No AI Main Class
 *
 * @package AdThrive Ads
 */

namespace AdThrive_Ads\Components\No_AI;

/**
 * Main class
 */
class Main {
	/**
	 * Add hooks
	 */
	public function setup() {
		add_filter( 'adthrive_ads_options', array( $this, 'add_options' ) );
		add_action( 'wp_head', array( $this, 'append_ai_metadata' ) );
		add_filter( 'robots_txt', array( $this, 'update_robot_file' ), 10, 2 );
	}

	/**
	 * Add fields to the options metabox
	 *
	 * @param CMB $cmb A CMB metabox instance
	 */
	public function add_options( $cmb ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$cmb->add_field( array(
			'name' => __( 'NoAI Meta Tags', 'adthrive_ads' ),
			'desc' => __( 'Enable “noai” and “noimageai” meta tags to tell AI systems not to use your content without your consent to train their models.<br/>
				<a href="https://help.raptive.com/hc/en-us/articles/13764527993755" target="_blank" rel="noopener noreferrer">Read more</a> about this setting and language to add to your Terms of Service.', 'adthrive_ads' ),
			'id' => 'no_ai',
			'type' => 'checkbox',
		) );

		if ( \WP_Filesystem() ) {
			global $wp_filesystem;

			$filename = $wp_filesystem->abspath() . 'robots.txt';

			if ( $wp_filesystem->is_file( $filename ) ) {
				$cmb->add_field( array(
					'name' => 'Block AI crawlers',
					'desc' => 'We\'ve detected you have a physical robots.txt file, so we can\'t add entries automatically. To block common AI crawlers from your site, <a href="https://help.raptive.com/hc/en-us/articles/25756415800987-How-to-manually-block-common-AI-crawlers" target="_blank" rel="noopener noreferrer">follow these instructions</a> to add entries to your robots.txt file.',
					'type' => 'title',
					'id'   => 'need_del_robots_file',
					'before_row'    => '<hr>',
				) );
			} else {
				$this->append_ai_crawler_checkboxes( $cmb );
			}
		} else {
			// we can't do a file system check, so just show the buttons that appended to a virtual file
			$this->append_ai_crawler_checkboxes( $cmb );
		}

		return $cmb;
	}

	/**
	 * Adds AI crawler checkboxes to the page
	 */
	public function append_ai_crawler_checkboxes( $cmb ) {
		$cmb->add_field( array(
			'name' => 'Block AI crawlers',
			'desc' => 'Entries are added to your robots.txt file beginning June 3, 2024.',
			'type' => 'title',
			'id'   => 'block_ai_crawlers_title',
			'before_row'    => '<hr>',
		) );

		$cmb->add_field( array(
			'name' => 'anthropic-ai',
			'desc' => 'Disallow the anthropic-ai user agent in your robots.txt file. Recommended.',
			'id' => 'block_anthropic_ai',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'CCbot',
			'desc' => 'Disallow the CCbot user agent in your robots.txt file. Recommended.',
			'id' => 'block_ccbot',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'ChatGPT-User',
			'desc' => 'Disallow the ChatGPT-User user agent in your robots.txt file. Recommended.',
			'id' => 'block_chatgpt_user',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'Claude-Web',
			'desc' => 'Disallow the Claude-Web user agent in your robots.txt file. Recommended.',
			'id' => 'block_claude_web',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'FacebookBot',
			'desc' => 'Disallow the FacebookBot user agent in your robots.txt file. Recommended.',
			'id' => 'block_facebook_bot',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'Google-Extended',
			'desc' => 'Disallow the Google-Extended user agent in your robots.txt file. Recommended.',
			'id' => 'block_google_extended',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'off',
		) );

		$cmb->add_field( array(
			'name' => 'GPTBot',
			'desc' => 'Disallow the GPTBot user agent in your robots.txt file. Recommended.',
			'id' => 'block_gptbot',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
		) );

		$cmb->add_field( array(
			'name' => 'PiplBot',
			'desc' => 'Disallow the PiplBot user agent in your robots.txt file. Recommended.',
			'id' => 'block_pipl_bot',
			'type' => 'radio_inline',
			'options' => array(
				'on' => 'On',
				'off' => 'Off',
			),
			'default' => 'on',
			'after_row'    => '<hr>',
		) );

		return $cmb;
	}

	/**
	 * Adds AI metadata to page if option is selected
	 */
	public function append_ai_metadata() {
		if ( 'on' === \AdThrive_Ads\Options::get( 'no_ai' ) ) {
			echo '<meta name="robots" content="noai, noimageai">';
		}
	}

	/**
	 * Adds to robot.txt
	 */
	public function update_robot_file( $output, $public ) {
		// If the 'today' query parameter is provided, use it
		// Otherwise, get the current date in 'Y-m-d' format
		$today = isset( $_GET['today'] ) ? sanitize_text_field( wp_unslash( $_GET['today'] ) ) : gmdate( 'Y-m-d' );

		$release_date = '2024-06-03';

		$output .= '
# ======Raptive Begin======
		';

		if ( $today < $release_date ) {
			$output .= '
# ======Raptive End======
			';
			return $output;
		}

		// Defaults are super weird in CMB, so i'm checking the double negative here in case CMB didn't save the default specificed above.
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_chatgpt_user' ) ) {
			$output .= '
User-agent: ChatGPT-User
Disallow: /
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_gptbot' ) ) {
			$output .= '
User-agent: GPTBot
Disallow: /
			';
		}
		if ( 'on' === \AdThrive_Ads\Options::get( 'block_google_extended' ) ) {
			$output .= '
User-agent: Google-Extended
Disallow: /
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_anthropic_ai' ) ) {
			$output .= '
User-agent: anthropic-ai
Disallow: /
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_claude_web' ) ) {
			$output .= '
User-agent: Claude-Web
Disallow: /		
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_pipl_bot' ) ) {
			$output .= '
User-agent: PiplBot
Disallow: /		
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_ccbot' ) ) {
			$output .= '
User-agent: CCbot
Disallow: /		
			';
		}
		if ( 'off' !== \AdThrive_Ads\Options::get( 'block_facebook_bot' ) ) {
			$output .= '
User-agent: FacebookBot
Disallow: /		
			';
		}
		$output .= '
# ======Raptive End======
		';
		return $output;
	}

}

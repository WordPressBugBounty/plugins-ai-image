<?php

/**
 * Plugin Name: Instant Image Generator
 * Plugin URI: https://bdthemes.com
 * Description: One-Click Image Uploads from Pixabay, Pexels and OpenAI. Easily upload images directly to your website without leaving WordPress.
 * Version: 1.5.9
 * Author: BdThemes
 * Author URI: https://bdthemes.com
 * License: GPLv3
 * Text Domain: ai-image
 * Domain Path: /languages/
 */

define( 'BDT_AI_IMAGE_VERSION', '1.5.9' );

define( 'BDT_AI_IMAGE__FILE__', __FILE__ );
define( 'BDT_AI_IMAGE_PATH', plugin_dir_path( BDT_AI_IMAGE__FILE__ ) );
define( 'BDT_AI_IMAGE_URL', plugins_url( '/', BDT_AI_IMAGE__FILE__ ) );
define( 'BDT_AI_IMAGE_ASSETS', BDT_AI_IMAGE_URL . 'assets/' );
define( 'BDT_AI_IMAGE_PATH_NAME', basename( dirname( BDT_AI_IMAGE__FILE__ ) ) );
define( 'BDT_AI_IMAGE_INC_PATH', BDT_AI_IMAGE_PATH . 'includes/' );


/**
 * Blocks Final Class
 */

final class BDTHEMES_AI_IMAGE {
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_files' ] );
	}

	/**
	 * Initialize the plugin
	 */

	public static function init() {
		static $instance = false;
		if ( ! $instance ) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Load Plugin Files
	 */
	public function load_files() {
		require_once __DIR__ . '/admin/api.php';
		require_once BDT_AI_IMAGE_PATH . 'plugin.php';

		if ( is_admin() ) {
			require_once __DIR__ . '/admin/settings.php';
		}
	}
}

/**
 * Kickoff
 */

BDTHEMES_AI_IMAGE::init();

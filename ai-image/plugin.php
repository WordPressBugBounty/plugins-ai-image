<?php
/**
 * Main Plugin File
 */

namespace BDT_AI_IMG;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * The main plugin class
 */
final class Plugin {

	/**
	 * Instance
	 *
	 * @var object
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Instance
	 *
	 * @return object
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();

			do_action( 'ai_image/init' );
		}
		return self::$instance;
	}

	/**
	 * Admin Styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		// if ( 'toplevel_page_ai-image' !== $hook_suffix && 'ai-image_page_ai-image-get-pro' !== $hook_suffix ) {
		// 	return;
		// }
		$direction_suffix = is_rtl() ? '.rtl' : '';
		wp_register_style( 'ai-image', BDT_AI_IMAGE_URL . 'build/admin/index.css', array(), BDT_AI_IMAGE_VERSION );
		wp_enqueue_style( 'ai-image' );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// if ( 'toplevel_page_ai-image' !== $hook_suffix ) {
		// 	return;
		// }
		$asset_file = plugin_dir_path( __FILE__ ) . 'build/admin/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_register_script( 'ai-image', BDT_AI_IMAGE_URL . 'build/admin/index.js', $asset['dependencies'], $asset['version'], true );

		wp_enqueue_script( 'ai-image' );

		$script_config = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'wp_rest' ),
			'assets_url' => BDT_AI_IMAGE_ASSETS,
			'rest_url'   => rest_url( 'bdthemes/v1/' ),
		);

		wp_localize_script(
			'ai-image',
			'AI_IMAGE_AdminConfig',
			$script_config
		);
	}

	/**
	 * Summary of upload_image_to_wp
	 */
	public function upload_image_to_wp() {

		// Verify nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_rest' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
		}

		// Check if image URL is provided
		if ( ! isset( $_POST['image_url'] ) || empty( $_POST['image_url'] ) ) {
			wp_send_json_error( array( 'message' => 'No image URL provided.' ) );
		}

		$image_url  = esc_url_raw( wp_unslash( $_POST['image_url'] ) );
		$upload_dir = wp_upload_dir();

		// Fetch the image from the URL
		$response = wp_remote_get( $image_url, array( 'timeout' => 60 ) );

		// Check if the request failed
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			wp_send_json_error( array( 'message' => 'Failed to fetch image.', 'error' => $error_message ) );
		}

		// Check if the status code is 200 (success)
		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			wp_send_json_error( array( 'message' => 'Failed to fetch image. Status code: ' . $status_code ) );
		}

		// Get the image data
		$image_data = wp_remote_retrieve_body( $response );
		if ( empty( $image_data ) ) {
			wp_send_json_error( array( 'message' => 'Failed to retrieve image data.' ) );
		}

		// Determine filename and ensure there are no query parameters
		$filename = basename( $image_url );
		$filename = preg_replace( '/\?.*/', '', $filename );

		// Optional transformation for special image URLs (e.g., DALL-E)
		if ( strpos( $image_url, 'oaidalleapiprodscus.blob.core.windows.net/' ) !== false ) {
			$wp_domain_name = get_site_url();
			$wp_domain_name = str_replace( array( 'http://', 'https://' ), '', strtolower( $wp_domain_name ) );
			$wp_domain_name = preg_replace( '/[^a-z0-9]/', '-', $wp_domain_name );
			$filename       = $wp_domain_name . '-' . time() . '.jpg';
		}

		// Check if the filename is more than 50 characters
		if ( strlen( $filename ) > 50 ) {
			$filename = substr( $filename, 0, 50 );
		}

		// If the image URL contains a real domain, rename the file using your own domain name
		$parsed_url = wp_parse_url( $image_url );
		if ( isset( $parsed_url['host'] ) ) {
			$domain         = preg_replace( '/[^a-z0-9]/', '-', strtolower( $parsed_url['host'] ) );
			$wp_domain_name = get_site_url();
			$wp_domain_name = str_replace( array( 'http://', 'https://' ), '', strtolower( $wp_domain_name ) );
			$wp_domain_name = preg_replace( '/[^a-z0-9]/', '-', $wp_domain_name );
			$filename       = $wp_domain_name . '-' . time() . '.jpg';
		}

		// Check if the upload directory exists
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file_path = $upload_dir['path'] . '/' . $filename;
		} else {
			$file_path = $upload_dir['basedir'] . '/' . $filename;
		}

		// Write the image data to the file
		$write_result = file_put_contents( $file_path, $image_data );
		if ( ! $write_result ) {
			wp_send_json_error( array( 'message' => 'Failed to save image to disk.' ) );
		}

		// Check the file type
		$wp_filetype = wp_check_filetype( $filename, null );
		if ( ! in_array( $wp_filetype['type'], array( 'image/jpeg', 'image/png', 'image/gif' ) ) ) {
			wp_send_json_error( array( 'message' => 'Invalid image type.' ) );
		}

		// Create the attachment array for the image
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert the attachment into the media library
		$attach_id = wp_insert_attachment( $attachment, $file_path );
		if ( ! $attach_id ) {
			wp_send_json_error( array( 'message' => 'Failed to upload image.' ) );
		}

		// Generate metadata for the attachment
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Return success response
		wp_send_json_success( array(
			'attach_id'  => $attach_id,
			'attach_url' => wp_get_attachment_url( $attach_id ),
		) );
	}

	/**
	 * Media Sub Menu
	 */
	public function media_sub_menu() {
		add_media_page( 'Image Generator', 'Image Generator', 'read', 'bdt-ai-media-tab', function () {
			?>
			<div class="wrap ai-image-wrap">
				<h2 class="ai-image-title">Image Generator</h2>
				<div id="ai-image-generator"></div>
			</div>
			<?php
		} );
	}

	/**
	 * Media upload tabs
	 */
	public function add_media_tab( $tabs ) {
		$tabs['ai_image'] = __( 'Image Generator 🪄', 'ai-image' );
		return $tabs;
	}

	public function media_tab_content() {
		wp_iframe( array( $this, 'media_tab_content_callback' ) );
	}

	public function media_tab_content_callback() {
		?>
		<div class="ai-image-wrap ai-image-media-modal">
			<div id="ai-image-generator"></div>
		</div>
		<?php
	}

	/**
	 * Setup hooks.
	 *
	 * @since 1.0.0
	 */
	private function setup_hooks() {
		add_action( 'admin_menu', array( $this, 'media_sub_menu' ) );
		add_filter( 'media_upload_tabs', array( $this, 'add_media_tab' ) );
		add_action( 'media_upload_ai_image', array( $this, 'media_tab_content' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 999 );
		add_action( 'wp_ajax_upload_image_to_wp', array( $this, 'upload_image_to_wp' ) );

	}

	/**
	 * Init
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$this->setup_hooks();
	}

}

if ( class_exists( 'BDT_AI_IMG\Plugin' ) ) {
	\BDT_AI_IMG\Plugin::instance();
}

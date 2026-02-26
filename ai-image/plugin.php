<?php
/**
 * Main Plugin File
 */

namespace BDT_AI_IMG;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

// Load plugin constants
require_once plugin_dir_path( __FILE__ ) . 'admin/constants.php';

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

			do_action( 'bdthemes_ai_image_init' );
		}
		return self::$instance;
	}

	/**
	 * Admin Styles
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_styles( $hook_suffix ) {
		$is_plugin_page = ( 'settings_page_ai-image-settings' === $hook_suffix || 'media_page_bdt-ai-media-tab' === $hook_suffix );
		$media_modal_enabled = get_option( 'bdthemes_ai_image_hide_media_modal_tab', '0' ) !== '1';

		if ( ! $is_plugin_page && ! $media_modal_enabled ) {
			return;
		}

		// On plugin pages always load; on other pages only if media modal is enabled
		if ( $is_plugin_page || $media_modal_enabled ) {
			wp_register_style( 'ai-image', BDT_AI_IMAGE_URL . 'build/admin/index.css', array(), BDT_AI_IMAGE_VERSION );
			wp_enqueue_style( 'ai-image' );
		}
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		$is_plugin_page = ( 'settings_page_ai-image-settings' === $hook_suffix || 'media_page_bdt-ai-media-tab' === $hook_suffix );
		$media_modal_enabled = get_option( 'bdthemes_ai_image_hide_media_modal_tab', '0' ) !== '1';

		if ( ! $is_plugin_page && ! $media_modal_enabled ) {
			return;
		}

		$asset_file = plugin_dir_path( __FILE__ ) . 'build/admin/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_register_script( 'ai-image', BDT_AI_IMAGE_URL . 'build/admin/index.js', $asset['dependencies'], $asset['version'], true );

		wp_enqueue_script( 'ai-image' );

		$provider_ids = array( 'global', 'pexels', 'pixabay', 'openverse', 'unsplash',  'giphy', 'openai', 'gemini' );
		$provider_enabled = array();
		$enabled_by_default = array( 'global', 'pexels', 'pixabay', 'openverse', 'unsplash', 'giphy' );
		foreach ( $provider_ids as $id ) {
			$default_value = in_array( $id, $enabled_by_default, true ) ? '1' : '0';
			$val = get_option( 'bdthemes_ai_image_provider_' . $id, $default_value );
			$provider_enabled[ $id ] = ( $val === '1' || $val === true );
		}

		$default_order = array( 'pexels', 'pixabay','openverse', 'unsplash', 'giphy', 'openai', 'gemini' );
		$saved_order   = get_option( 'bdthemes_ai_image_provider_order', $default_order );
		if ( ! is_array( $saved_order ) || empty( $saved_order ) ) {
			$saved_order = $default_order;
		}
		$provider_order = array_values( array_unique( array_merge( $saved_order, $default_order ) ) );

		$max_w = get_option( 'bdthemes_ai_image_max_upload_width', 1600 );
		$max_h = get_option( 'bdthemes_ai_image_max_upload_height', 1200 );
		$items_per_page = get_option( 'bdthemes_ai_image_items_per_page', 20 );
		$general = array(
			'max_upload_width'       => is_numeric( $max_w ) ? (int) $max_w : 1600,
			'max_upload_height'      => is_numeric( $max_h ) ? (int) $max_h : 1200,
			'default_provider'       => get_option( 'bdthemes_ai_image_default_provider', 'pexels' ),
			'image_attribution'     => get_option( 'bdthemes_ai_image_attribution', '0' ) === '1',
			'auto_alt_text'         => get_option( 'bdthemes_ai_image_auto_alt_text', '0' ) === '1',
			'auto_title'            => get_option( 'bdthemes_ai_image_auto_title', '0' ) === '1',
			'hide_media_modal_tab'   => get_option( 'bdthemes_ai_image_hide_media_modal_tab', '0' ) === '1',
			'default_view_mode'      => get_option( 'bdthemes_ai_image_default_view_mode', 'grid' ),
			'items_per_page'         => is_numeric( $items_per_page ) ? (int) $items_per_page : 20,
			'thumbnail_size'         => get_option( 'bdthemes_ai_image_thumbnail_size', 'small' ),
			'load_more_mode'         => get_option( 'bdthemes_ai_image_load_more_mode', 'manual' ),
		);

		$script_config = array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'wp_rest' ),
			'assets_url'       => BDT_AI_IMAGE_ASSETS,
			'rest_url'         => rest_url( 'bdthemes/v1/' ),
			'version'          => BDT_AI_IMAGE_VERSION,
			'settings_url'     => admin_url( 'options-general.php?page=ai-image-settings' ),
			'generator_url'    => admin_url( 'upload.php?page=bdt-ai-media-tab' ),
			'support_url'      => 'https://bdthemes.com/support/?utm_source=WordPress_Repository&utm_medium=Plugin_Page&utm_campaign=WordPress_to_Instant_Image_Generator',
			'docs_url'         => 'https://bdthemes.com/all-knowledge-base-of-instant-image-generator/?utm_source=WordPress_Repository&utm_medium=Plugin_Page&utm_campaign=WordPress_to_Instant_Image_Generator',
			'provider_enabled' => $provider_enabled,
			'provider_order'   => $provider_order,
			'general_settings' => $general,
			'image_sizes'      => $this->get_all_image_sizes(),
		);

		wp_localize_script(
			'ai-image',
			'AI_IMAGE_AdminConfig',
			$script_config
		);

		// Add media modal integration script on non-plugin pages
		if ( ! $is_plugin_page && $media_modal_enabled ) {
			wp_enqueue_media();
			$this->enqueue_media_modal_script();
		}
	}

	/**
	 * Enqueue block editor assets for the Image Generator button
	 *
	 * @since 2.0.0
	 */
	public function enqueue_block_editor_assets() {
		// Load the main admin bundle which includes block-toolbar functionality
		$asset_file = BDT_AI_IMAGE_PATH . 'build/admin/index.asset.php';
		
		if ( ! file_exists( $asset_file ) ) {
			return;
		}
		
		$asset = include $asset_file;
		
		// Enqueue the admin script (includes block toolbar)
		wp_enqueue_script(
			'ai-image',
			BDT_AI_IMAGE_URL . 'build/admin/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		
		// Enqueue the admin styles
		wp_enqueue_style(
			'ai-image',
			BDT_AI_IMAGE_URL . 'build/admin/index.css',
			array(),
			BDT_AI_IMAGE_VERSION
		);
		
		// Check if any provider is enabled and build enabled providers list
		$provider_ids = array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
		$has_enabled_provider = false;
		$enabled_providers = array();
		$enabled_by_default = array( 'pexels', 'pixabay', 'openverse', 'unsplash', 'giphy' );
		
		foreach ( $provider_ids as $id ) {
			$default_value = in_array( $id, $enabled_by_default, true ) ? '1' : '0';
			$enabled = get_option( 'bdthemes_ai_image_provider_' . $id, $default_value ) === '1';
			$enabled_providers[ $id ] = $enabled;
			if ( $enabled ) {
				$has_enabled_provider = true;
			}
		}
		
		// Get provider order from settings
		$default_order = array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
		$saved_order   = get_option( 'bdthemes_ai_image_provider_order', $default_order );
		if ( ! is_array( $saved_order ) || empty( $saved_order ) ) {
			$saved_order = $default_order;
		}
		$provider_order = array_values( array_unique( array_merge( $saved_order, $default_order ) ) );

		// Localize script with config for block toolbar
		wp_localize_script(
			'ai-image',
			'AI_IMAGE_BlockToolbar',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'wp_rest' ),
				'hasEnabledProviders' => $has_enabled_provider,
				'enabledProviders'    => $enabled_providers,
				'providerOrder'       => $provider_order,
			)
		);
	}

	/**
	 * Enqueue the inline script that adds the "Image Generator" tab to the new wp.media modal.
	 */
	private function enqueue_media_modal_script() {
		$inline_js = <<<'MEDIAJS'
(function( $, wp ){
	if ( typeof wp === 'undefined' || ! wp.media || ! wp.media.view ) return;

	var TAB_ID   = 'ai-image-tab';
	var TAB_TEXT = 'Image Generator';

	/**
	 * Custom Backbone view that renders our React app.
	 */
	var AiImageContent = wp.media.View.extend({
		className: 'ai-image-wrap ai-image-media-modal',
		initialize: function() {
			this.$el.attr('style', 'height:100%;overflow-y:auto;padding:16px 20px;background:#f0f0f1;');
		},
		render: function() {
			this.$el.html( '<div id="ai-image-generator-modal"></div>' );
			var self = this;
			// Small delay to ensure DOM is ready before React renders
			setTimeout(function(){
				var root = self.$el.find('#ai-image-generator-modal')[0];
				if ( root && window.aiImageRenderApp ) {
					window.aiImageRenderApp( root );
				}
			}, 50);
			return this;
		}
	});

	/**
	 * Add the tab to router for any frame type.
	 */
	function addTab( routerView ) {
		routerView.set( TAB_ID, {
			text:     TAB_TEXT,
			priority: 200
		});
	}

	/**
	 * Bind content creation handler to a frame.
	 */
	function bindContentHandler( frame ) {
		if ( frame._aiImageBound ) return;
		frame._aiImageBound = true;

		frame.on( 'content:create:' + TAB_ID, function() {
			var view = new AiImageContent();
			frame.content.set( view );
		});
	}

	// Override Select frame router (used in Gutenberg, featured image, etc.)
	var origSelectRouter = wp.media.view.MediaFrame.Select.prototype.browseRouter;
	wp.media.view.MediaFrame.Select.prototype.browseRouter = function( routerView ) {
		origSelectRouter.apply( this, arguments );
		addTab( routerView );
		bindContentHandler( this );
	};

	// Override Post frame router (used in classic editor "Add Media")
	if ( wp.media.view.MediaFrame.Post ) {
		var origPostRouter = wp.media.view.MediaFrame.Post.prototype.browseRouter;
		wp.media.view.MediaFrame.Post.prototype.browseRouter = function( routerView ) {
			origPostRouter.apply( this, arguments );
			addTab( routerView );
			bindContentHandler( this );
		};
	}

})( jQuery, wp );
MEDIAJS;
		wp_add_inline_script( 'ai-image', $inline_js, 'after' );
	}

	/**
	 * Add AI Image settings page under Settings menu.
	 */
	public function add_settings_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Image Generator', 'ai-image' ),
			__( 'Image Generator', 'ai-image' ),
			'manage_options',
			'ai-image-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Settings page callback: output root for React.
	 */
	public function render_settings_page() {
		echo '<div id="ai-image-dashboard" class="ai-image-dashboard-wrap ai-image-wrap"></div>';
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized based on type below
		$raw_image_url = wp_unslash( $_POST['image_url'] );
		$image_title   = isset( $_POST['image_title'] ) ? sanitize_text_field( wp_unslash( $_POST['image_title'] ) ) : '';
		$image_author  = isset( $_POST['image_author'] ) ? sanitize_text_field( wp_unslash( $_POST['image_author'] ) ) : '';
		$upload_dir    = wp_upload_dir();
		
		// Check if this is a base64 data URI (e.g., from Gemini)
		$is_base64 = strpos( $raw_image_url, 'data:image/' ) === 0;
		
		if ( $is_base64 ) {
			// Handle base64 data URI
			// Extract the image data from the data URI
			// Format: data:image/png;base64,iVBORw0KGgoAAAANS...
			if ( ! preg_match( '/^data:image\/(\w+);base64,(.+)$/i', $raw_image_url, $matches ) ) {
				wp_send_json_error( array( 'message' => 'Invalid base64 image data.' ) );
			}
			
			$extension  = strtolower( $matches[1] );
			$base64_str = $matches[2];
			
			// Validate extension
			if ( ! in_array( $extension, array( 'png', 'jpg', 'jpeg', 'gif', 'webp' ) ) ) {
				wp_send_json_error( array( 'message' => 'Unsupported image format: ' . $extension ) );
			}
			
			// Decode base64 data
			$image_data = base64_decode( $base64_str );
			
			if ( $image_data === false ) {
				wp_send_json_error( array( 'message' => 'Failed to decode base64 image data.' ) );
			}
			
			if ( empty( $image_data ) ) {
				wp_send_json_error( array( 'message' => 'Decoded base64 image data is empty.' ) );
			}
			
		} else {
			// Handle regular URL
			$image_url = esc_url_raw( $raw_image_url );
			
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

			// Detect file extension from content-type or URL
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			$extension    = 'jpg';
			if ( ! empty( $content_type ) ) {
				if ( strpos( $content_type, 'png' ) !== false ) {
					$extension = 'png';
				} elseif ( strpos( $content_type, 'gif' ) !== false ) {
					$extension = 'gif';
				} elseif ( strpos( $content_type, 'webp' ) !== false ) {
					$extension = 'webp';
				}
			}
		}

		// Build filename from image title if available, otherwise use a domain-based name
		if ( ! empty( $image_title ) ) {
			// Sanitize the title into a clean slug for the filename
			$slug     = sanitize_title( $image_title );
			$slug     = preg_replace( '/[^a-z0-9\-]/', '', $slug );
			// Limit to 80 chars to avoid extremely long filenames
			if ( strlen( $slug ) > 80 ) {
				$slug = substr( $slug, 0, 80 );
			}
			$slug     = rtrim( $slug, '-' );
			$filename = $slug . '.' . $extension;
		} else {
			// Fallback: domain-based random filename
			$wp_domain_name = get_site_url();
			$wp_domain_name = str_replace( array( 'http://', 'https://' ), '', strtolower( $wp_domain_name ) );
			$wp_domain_name = preg_replace( '/[^a-z0-9]/', '-', $wp_domain_name );
			$filename       = $wp_domain_name . '-' . time() . '.' . $extension;
		}

		// Ensure unique filename in uploads directory
		$filename = wp_unique_filename( $upload_dir['path'], $filename );

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

		// Resize image if it exceeds max dimensions
		$max_width  = get_option( 'bdthemes_ai_image_max_upload_width', 1600 );
		$max_height = get_option( 'bdthemes_ai_image_max_upload_height', 1200 );
		$max_width  = is_numeric( $max_width ) ? (int) $max_width : 1600;
		$max_height = is_numeric( $max_height ) ? (int) $max_height : 1200;

		if ( $max_width > 0 && $max_height > 0 ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			$image_editor = wp_get_image_editor( $file_path );
			
			if ( ! is_wp_error( $image_editor ) ) {
				$size = $image_editor->get_size();
				
				// Only resize if image exceeds max dimensions
				if ( $size['width'] > $max_width || $size['height'] > $max_height ) {
					$image_editor->resize( $max_width, $max_height, false );
					$saved = $image_editor->save( $file_path );
				}
			}
		}

		// Read user settings for auto-populating metadata
		$auto_alt_text       = get_option( 'bdthemes_ai_image_auto_alt_text', '1' ) === '1';
		$auto_title          = get_option( 'bdthemes_ai_image_auto_title', '1' ) === '1';
		$image_attribution   = get_option( 'bdthemes_ai_image_attribution', '0' ) === '1';

		// Determine the WP attachment title
		if ( $auto_title && ! empty( $image_title ) ) {
			$attachment_title = $image_title;
		} else {
			// When auto title is off, leave empty so WordPress doesn't show a generated name
			$attachment_title = $auto_title ? sanitize_file_name( $filename ) : '';
		}

		// Build caption from author name if attribution is enabled
		$caption = '';
		if ( $image_attribution && ! empty( $image_author ) ) {
			$caption = 'Photo by ' . $image_author;
		}

		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => $attachment_title,
			'post_content'   => '',
			'post_excerpt'   => $caption,
			'post_status'    => 'inherit',
		);

		// Insert the attachment into the media library
		$attach_id = wp_insert_attachment( $attachment, $file_path );
		if ( ! $attach_id ) {
			wp_send_json_error( array( 'message' => 'Failed to upload image.' ) );
		}

		// Set alternative text from the image title if enabled
		if ( $auto_alt_text && ! empty( $image_title ) ) {
			update_post_meta( $attach_id, '_wp_attachment_image_alt', $image_title );
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
				<div id="ai-image-generator"></div>
			</div>
			<?php
		} );
	}

	/**
	 * Media upload tabs
	 */
	public function add_media_tab( $tabs ) {
		if ( get_option( 'bdthemes_ai_image_hide_media_modal_tab', '0' ) === '1' ) {
			return $tabs;
		}
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
		add_action( 'after_setup_theme', array( $this, 'register_custom_image_sizes' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'media_sub_menu' ), 20 );
		add_filter( 'media_upload_tabs', array( $this, 'add_media_tab' ) );
		add_action( 'media_upload_ai_image', array( $this, 'media_tab_content' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 999 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'wp_ajax_upload_image_to_wp', array( $this, 'upload_image_to_wp' ) );
		add_action( 'wp_ajax_ai_image_get_openai_key', array( $this, 'ajax_get_openai_key' ) );
		add_action( 'wp_ajax_ai_image_get_gemini_key', array( $this, 'ajax_get_gemini_key' ) );
		add_action( 'wp_ajax_ai_image_get_pexels_key', array( $this, 'ajax_get_pexels_key' ) );
		add_action( 'wp_ajax_ai_image_get_unsplash_key', array( $this, 'ajax_get_unsplash_key' ) );
		add_action( 'wp_ajax_ai_image_get_pixabay_key', array( $this, 'ajax_get_pixabay_key' ) );
		add_action( 'wp_ajax_ai_image_get_giphy_key', array( $this, 'ajax_get_giphy_key' ) );
		add_action( 'wp_ajax_ai_image_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_ai_image_test_api_key', array( $this, 'ajax_test_api_key' ) );
		add_action( 'wp_ajax_ai_image_generate_gemini', array( $this, 'ajax_generate_gemini_image' ) );
		add_action( 'wp_ajax_ai_image_add_custom_size', array( $this, 'ajax_add_custom_size' ) );
		add_action( 'wp_ajax_ai_image_delete_custom_size', array( $this, 'ajax_delete_custom_size' ) );
	}

	/**
	 * Register custom image sizes on init.
	 */
	public function register_custom_image_sizes() {
		$custom = get_option( 'bdthemes_ai_image_custom_sizes', array() );
		if ( ! is_array( $custom ) ) return;
		foreach ( $custom as $name => $size ) {
			if ( ! empty( $size['width'] ) && ! empty( $size['height'] ) ) {
				add_image_size( $name, (int) $size['width'], (int) $size['height'], ! empty( $size['crop'] ) );
			}
		}
	}

	/**
	 * Get all image sizes (WP defaults + custom).
	 */
	private function get_all_image_sizes() {
		global $_wp_additional_image_sizes;
		$sizes = array();
		$default_names = array( 'thumbnail', 'medium', 'medium_large', 'large' );
		foreach ( $default_names as $name ) {
			$w = get_option( $name . '_size_w' );
			$h = get_option( $name . '_size_h' );
			$crop = get_option( $name . '_crop' );
			if ( $w || $h ) {
				$label = ucfirst( str_replace( '_', ' ', $name ) );
				$sizes[] = array(
					'label'  => $label,
					'name'   => $name,
					'width'  => (int) $w,
					'height' => (int) $h,
					'crop'   => ! empty( $crop ),
					'source' => 'wordpress',
				);
			}
		}
		
		// Get custom sizes from database to verify they still exist
		$custom_sizes = get_option( 'bdthemes_ai_image_custom_sizes', array() );
		if ( ! is_array( $custom_sizes ) ) $custom_sizes = array();
		
		if ( is_array( $_wp_additional_image_sizes ) ) {
			foreach ( $_wp_additional_image_sizes as $name => $data ) {
				// Only include custom sizes that exist in our database
				if ( isset( $custom_sizes[ $name ] ) ) {
					$label = ucfirst( str_replace( array( '_', '-' ), ' ', $name ) );
					$sizes[] = array(
						'label'  => $label,
						'name'   => $name,
						'width'  => isset( $data['width'] ) ? (int) $data['width'] : 0,
						'height' => isset( $data['height'] ) ? (int) $data['height'] : 0,
						'crop'   => ! empty( $data['crop'] ),
						'source' => 'custom',
					);
				}
			}
		}
		return $sizes;
	}

	/**
	 * AJAX: add a custom image size.
	 */
	public function ajax_add_custom_size() {
		$this->api_key_ajax_permission_check();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$name   = isset( $_POST['name'] ) ? sanitize_title( wp_unslash( $_POST['name'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$width  = isset( $_POST['width'] ) ? absint( $_POST['width'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$height = isset( $_POST['height'] ) ? absint( $_POST['height'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$crop   = ! empty( $_POST['crop'] );
		
		// Validate name and dimensions
		if ( empty( $name ) || $width < 1 || $height < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid name or dimensions.', 'ai-image' ) ) );
		}
		
		// Check maximum dimensions
		if ( $width > 1920 ) {
			wp_send_json_error( array( 'message' => __( 'Width must not exceed 1920 pixels.', 'ai-image' ) ) );
		}
		
		if ( $height > 3000 ) {
			wp_send_json_error( array( 'message' => __( 'Height must not exceed 3000 pixels.', 'ai-image' ) ) );
		}
		
		// Check for reserved WordPress image size names
		$reserved = array( 'thumbnail', 'medium', 'medium_large', 'large' );
		if ( in_array( $name, $reserved, true ) ) {
			wp_send_json_error( array( 'message' => __( 'This name is reserved by WordPress. Please use a different name.', 'ai-image' ) ) );
		}
		
		// Check for duplicate custom size names
		$custom = get_option( 'bdthemes_ai_image_custom_sizes', array() );
		if ( ! is_array( $custom ) ) $custom = array();
		if ( isset( $custom[ $name ] ) ) {
			wp_send_json_error( array( 'message' => __( 'A size with this name already exists. Please use a different name.', 'ai-image' ) ) );
		}
		
		$custom[ $name ] = array( 'width' => $width, 'height' => $height, 'crop' => $crop );
		update_option( 'bdthemes_ai_image_custom_sizes', $custom );
		add_image_size( $name, $width, $height, $crop );
		wp_send_json_success( array(
			'message' => __( 'Image size added.', 'ai-image' ),
			'sizes'   => $this->get_all_image_sizes(),
		) );
	}

	/**
	 * AJAX: delete a custom image size.
	 */
	public function ajax_delete_custom_size() {
		$this->api_key_ajax_permission_check();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$name = isset( $_POST['name'] ) ? sanitize_title( wp_unslash( $_POST['name'] ) ) : '';
		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid name.', 'ai-image' ) ) );
		}
		$custom = get_option( 'bdthemes_ai_image_custom_sizes', array() );
		if ( ! is_array( $custom ) ) $custom = array();
		if ( ! isset( $custom[ $name ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Size not found.', 'ai-image' ) ) );
		}
		unset( $custom[ $name ] );
		update_option( 'bdthemes_ai_image_custom_sizes', $custom );
		wp_send_json_success( array(
			'message' => __( 'Image size deleted.', 'ai-image' ),
			'sizes'   => $this->get_all_image_sizes(),
		) );
	}

	/**
	 * Verify nonce and manage_options for API key AJAX handlers.
	 */
	private function api_key_ajax_permission_check() {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}
	}

	/**
	 * AJAX: return OpenAI API key (JSON).
	 */
	public function ajax_get_openai_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_openai_api_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: return Gemini API key (JSON).
	 */
	public function ajax_get_gemini_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_gemini_api_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: return Pexels API key (JSON).
	 */
	public function ajax_get_pexels_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_pexels_api_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: return Unsplash API key (JSON).
	 */
	public function ajax_get_unsplash_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_unsplash_access_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: return Pixabay API key (JSON).
	 */
	public function ajax_get_pixabay_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_pixabay_api_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: return Giphy API key (JSON).
	 */
	public function ajax_get_giphy_key() {
		$this->api_key_ajax_permission_check();
		$key = get_option( 'bdthemes_giphy_api_key' );
		$key = is_string( $key ) ? trim( $key ) : '';
		wp_send_json_success( array( 'api_key' => $key ? $key : null ) );
	}

	/**
	 * AJAX: save dashboard settings (API keys).
	 */
	public function ajax_save_settings() {
		$this->api_key_ajax_permission_check();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput -- Nonce verified in api_key_ajax_permission_check(), data is JSON decoded and sanitized per field
		$input = isset( $_POST['data'] ) && is_string( $_POST['data'] ) ? json_decode( stripslashes( $_POST['data'] ), true ) : null;
		if ( ! is_array( $input ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid request.', 'ai-image' ) ) );
		}
		if ( isset( $input['openai_api_key'] ) ) {
			update_option( 'bdthemes_openai_api_key', sanitize_text_field( is_string( $input['openai_api_key'] ) ? trim( $input['openai_api_key'] ) : '' ) );
		}
		if ( isset( $input['gemini_api_key'] ) ) {
			update_option( 'bdthemes_gemini_api_key', sanitize_text_field( is_string( $input['gemini_api_key'] ) ? trim( $input['gemini_api_key'] ) : '' ) );
		}
		if ( isset( $input['unsplash_access_key'] ) ) {
			update_option( 'bdthemes_unsplash_access_key', sanitize_text_field( is_string( $input['unsplash_access_key'] ) ? trim( $input['unsplash_access_key'] ) : '' ) );
		}
		if ( isset( $input['giphy_api_key'] ) ) {
			update_option( 'bdthemes_giphy_api_key', sanitize_text_field( is_string( $input['giphy_api_key'] ) ? trim( $input['giphy_api_key'] ) : '' ) );
		}
		if ( isset( $input['pexels_api_key'] ) ) {
			update_option( 'bdthemes_pexels_api_key', sanitize_text_field( is_string( $input['pexels_api_key'] ) ? trim( $input['pexels_api_key'] ) : '' ) );
		}
		if ( isset( $input['pixabay_api_key'] ) ) {
			update_option( 'bdthemes_pixabay_api_key', sanitize_text_field( is_string( $input['pixabay_api_key'] ) ? trim( $input['pixabay_api_key'] ) : '' ) );
		}
		$provider_ids = array( 'global', 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
		foreach ( $provider_ids as $id ) {
			$key = 'provider_' . $id;
			if ( array_key_exists( $key, $input ) ) {
				$val = $input[ $key ];
				$enabled = ( $val === true || $val === '1' || $val === 1 );
				update_option( 'bdthemes_ai_image_provider_' . $id, $enabled ? '1' : '0' );
			}
		}
		if ( array_key_exists( 'max_upload_width', $input ) ) {
			update_option( 'bdthemes_ai_image_max_upload_width', absint( $input['max_upload_width'] ) ?: 1600 );
		}
		if ( array_key_exists( 'max_upload_height', $input ) ) {
			update_option( 'bdthemes_ai_image_max_upload_height', absint( $input['max_upload_height'] ) ?: 1200 );
		}
		if ( array_key_exists( 'default_provider', $input ) ) {
			update_option( 'bdthemes_ai_image_default_provider', sanitize_text_field( $input['default_provider'] ) );
		}
		if ( array_key_exists( 'image_attribution', $input ) ) {
			$v = $input['image_attribution'];
			update_option( 'bdthemes_ai_image_attribution', ( $v === true || $v === '1' || $v === 1 ) ? '1' : '0' );
		}
		if ( array_key_exists( 'auto_alt_text', $input ) ) {
			$v = $input['auto_alt_text'];
			update_option( 'bdthemes_ai_image_auto_alt_text', ( $v === true || $v === '1' || $v === 1 ) ? '1' : '0' );
		}
		if ( array_key_exists( 'auto_title', $input ) ) {
			$v = $input['auto_title'];
			update_option( 'bdthemes_ai_image_auto_title', ( $v === true || $v === '1' || $v === 1 ) ? '1' : '0' );
		}
		if ( array_key_exists( 'hide_media_modal_tab', $input ) ) {
			$v = $input['hide_media_modal_tab'];
			update_option( 'bdthemes_ai_image_hide_media_modal_tab', ( $v === true || $v === '1' || $v === 1 ) ? '1' : '0' );
		}
		if ( array_key_exists( 'default_view_mode', $input ) ) {
			$mode = sanitize_text_field( $input['default_view_mode'] );
			update_option( 'bdthemes_ai_image_default_view_mode', $mode === 'list' ? 'list' : 'grid' );
		}
		if ( array_key_exists( 'items_per_page', $input ) ) {
			$items = absint( $input['items_per_page'] );
			update_option( 'bdthemes_ai_image_items_per_page', ( $items >= 20 && $items <= 100 ) ? $items : 30 );
		}
		if ( array_key_exists( 'thumbnail_size', $input ) ) {
			$size = sanitize_text_field( $input['thumbnail_size'] );
			$allowed_sizes = array( 'small', 'medium', 'large' );
			update_option( 'bdthemes_ai_image_thumbnail_size', in_array( $size, $allowed_sizes, true ) ? $size : 'medium' );
		}
		if ( array_key_exists( 'load_more_mode', $input ) ) {
			$mode = sanitize_text_field( $input['load_more_mode'] );
			$allowed_modes = array( 'auto', 'manual' );
			update_option( 'bdthemes_ai_image_load_more_mode', in_array( $mode, $allowed_modes, true ) ? $mode : 'auto' );
		}
		if ( array_key_exists( 'provider_order', $input ) && is_array( $input['provider_order'] ) ) {
			$valid_ids = array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
			$order     = array_values( array_intersect( array_map( 'sanitize_text_field', $input['provider_order'] ), $valid_ids ) );
			if ( ! empty( $order ) ) {
				update_option( 'bdthemes_ai_image_provider_order', $order );
			}
		}
		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'ai-image' ) ) );
	}

	/**
	 * AJAX: test API key for a provider.
	 */
	public function ajax_test_api_key() {
		$this->api_key_ajax_permission_check();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
		if ( empty( $provider ) ) {
			wp_send_json_error( array( 'message' => __( 'Provider required.', 'ai-image' ) ) );
		}
		if ( empty( $api_key ) ) {
			if ( 'openai' === $provider ) {
				$api_key = get_option( 'bdthemes_openai_api_key' );
			} elseif ( 'unsplash' === $provider ) {
				$api_key = get_option( 'bdthemes_unsplash_access_key' );
				// For Unsplash, if no custom key is saved, use default key for testing
				if ( empty( trim( $api_key ) ) ) {
					$api_key = \BDT_AI_IMG\decrypt_key( AI_IMAGE_UNSPLASH_DEFAULT_KEY );
				}
			} elseif ( 'pexels' === $provider ) {
				$api_key = get_option( 'bdthemes_pexels_api_key' );
				// For Pexels, if no custom key is saved, use default key for testing
				if ( empty( trim( $api_key ) ) ) {
					$api_key = \BDT_AI_IMG\decrypt_key( AI_IMAGE_PEXELS_DEFAULT_KEY );
				}
			} elseif ( 'pixabay' === $provider ) {
				$api_key = get_option( 'bdthemes_pixabay_api_key' );
				// For Pixabay, if no custom key is saved, use default key for testing
				if ( empty( trim( $api_key ) ) ) {
					$api_key = \BDT_AI_IMG\decrypt_key( AI_IMAGE_PIXABAY_DEFAULT_KEY );
				}
			} elseif ( 'giphy' === $provider ) {
				$api_key = get_option( 'bdthemes_giphy_api_key' );
				// For Giphy, if no custom key is saved, use default key for testing
				if ( empty( trim( $api_key ) ) ) {
					$api_key = \BDT_AI_IMG\decrypt_key( AI_IMAGE_GIPHY_DEFAULT_KEY );
				}
			} elseif ( 'gemini' === $provider ) {
				$api_key = get_option( 'bdthemes_gemini_api_key' );
			}
			$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		}
		// If user provides a key via POST, test that exact key (don't fallback)
		// The fallback only applies when api_key POST param is completely empty
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'No API key provided.', 'ai-image' ) ) );
		}
		$result = $this->test_provider_key( $provider, $api_key );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		wp_send_json_success( array( 'message' => __( 'Key valid.', 'ai-image' ) ) );
	}

	/**
	 * Minimal check for provider API key.
	 *
	 * @param string $provider openai|unsplash|giphy
	 * @param string $api_key
	 * @return true|WP_Error
	 */
	private function test_provider_key( $provider, $api_key ) {
		if ( 'openai' === $provider ) {
			$response = wp_remote_get(
				'https://api.openai.com/v1/models',
				array(
					'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
					'timeout' => 15,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				return true;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'OpenAI key invalid.', 'ai-image' );
			return new \WP_Error( 'openai_test_failed', $msg );
		}
		if ( 'unsplash' === $provider ) {
			$response = wp_remote_get(
				'https://api.unsplash.com/photos?per_page=1',
				array(
					'headers' => array( 'Authorization' => 'Client-ID ' . $api_key ),
					'timeout' => 15,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				return true;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['errors'][0] ) ? $body['errors'][0] : __( 'Unsplash Access key not valid. Please pass a valid Access key.', 'ai-image' );
			return new \WP_Error( 'unsplash_test_failed', $msg );
		}
		if ( 'giphy' === $provider ) {
			$response = wp_remote_get(
				'https://api.giphy.com/v1/gifs/trending?api_key=' . urlencode( $api_key ) . '&limit=1',
				array( 'timeout' => 15 )
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				return true;
			}
			return new \WP_Error( 'giphy_test_failed', __( 'Giphy API key not valid. Please pass a valid API key.', 'ai-image' ) );
		}
		if ( 'gemini' === $provider ) {
			$response = wp_remote_get(
				'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode( $api_key ),
				array( 'timeout' => 15 )
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				return true;
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Gemini key invalid.', 'ai-image' );
			return new \WP_Error( 'gemini_test_failed', $msg );
		}
		if ( 'pexels' === $provider ) {
			function generateRandomWords($count = 5) {
				$letters = 'abcdefghijklmnopqrstuvwxyz';

				function createWord($letters) {
					$length = wp_rand(4, 8); // word length between 4–8 characters
					$word = '';

					for ($i = 0; $i < $length; $i++) {
						$word .= $letters[wp_rand(0, strlen($letters) - 1)];
					}

					return $word;
				}

				$words = [];

				for ($i = 0; $i < $count; $i++) {
					$words[] = createWord($letters);
				}

				return $words;
			}
			$response = wp_remote_get(
				'https://api.pexels.com/v1/search?query=' . urlencode(implode(' ', generateRandomWords())) . '&per_page=1',
				array(
					'headers' => array( 'Authorization' => $api_key ),
					'timeout' => 15,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			
			// Check for error responses
			if ( $code === 401 || $code === 403 ) {
				return new \WP_Error( 'pexels_test_failed', __( 'Pexels API key not valid. Invalid or unauthorized key.', 'ai-image' ) );
			}
			
			if ( $code !== 200 ) {
				return new \WP_Error( 'pexels_test_failed', __( 'Pexels API key not valid. Please pass a valid API key.', 'ai-image' ) );
			}
			
			// Parse and validate response structure
			$data = json_decode( $body, true );
			
			// Check JSON decode errors
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new \WP_Error( 'pexels_test_failed', __( 'Pexels API returned invalid JSON.', 'ai-image' ) );
			}
			
			// Check for error field in response
			if ( isset( $data['error'] ) ) {
				/* translators: %s: error message from Pexels API */
				return new \WP_Error( 'pexels_test_failed', sprintf( __( 'Pexels API key not valid: %s', 'ai-image' ), $data['error'] ) );
			}
			
			// Verify response has the expected photos array structure
			// A valid API key should return a response with a photos array (even if empty)
			if ( ! isset( $data['photos'] ) || ! is_array( $data['photos'] ) ) {
				return new \WP_Error( 'pexels_test_failed', __( 'Pexels API key not valid. Unexpected response format.', 'ai-image' ) );
			}
			
			return true;
		}
		if ( 'pixabay' === $provider ) {
			$response = wp_remote_get(
				'https://pixabay.com/api/?key=' . urlencode( $api_key ) . '&q=nature&per_page=3',
				array( 'timeout' => 15 )
			);
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				return true;
			}
			return new \WP_Error( 'pixabay_test_failed', __( 'Pixabay API key not valid. Please pass a valid API key.', 'ai-image' ) );
		}
		return new \WP_Error( 'unknown_provider', __( 'Unknown provider.', 'ai-image' ) );
	}

	/**
	 * AJAX: Generate Gemini image (proxy to avoid CORS).
	 */
	public function ajax_generate_gemini_image() {
		$this->api_key_ajax_permission_check();
		
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$prompt = isset( $_POST['prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$num_images = isset( $_POST['number_of_images'] ) ? absint( $_POST['number_of_images'] ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in api_key_ajax_permission_check()
		$aspect_ratio = isset( $_POST['aspect_ratio'] ) ? sanitize_text_field( wp_unslash( $_POST['aspect_ratio'] ) ) : '1:1';
		
		if ( empty( $prompt ) ) {
			wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'ai-image' ) ) );
		}
		
		$api_key = get_option( 'bdthemes_gemini_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		
		if ( empty( $api_key ) ) {
			wp_send_json_error( array( 'message' => __( 'No Gemini API key configured.', 'ai-image' ) ) );
		}
		
		// Use Imagen 4.0 model which is available in the API
		// Available models: imagen-4.0-generate-001 (stable), imagen-4.0-fast-generate-001, imagen-4.0-ultra-generate-001
		// These models use :predict method
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key=' . urlencode( $api_key );
		
		// Correct request format for predict API
		$body = wp_json_encode( array(
			'instances' => array(
				array(
					'prompt' => $prompt
				)
			),
			'parameters' => array(
				'sampleCount' => min( max( 1, $num_images ), 4 ),
				'aspectRatio' => $aspect_ratio,
				'safetyFilterLevel' => 'block_some',
				'personGeneration' => 'allow_adult'
			)
		) );
		
		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => $body,
			'timeout' => 60,
		) );
		
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );
		
		if ( $code !== 200 ) {
			$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Gemini API request failed.', 'ai-image' );
			$error_details = isset( $data['error'] ) ? $data['error'] : null;
			
			wp_send_json_error( array( 
				'message' => $error_msg, 
				'code' => $code, 
				'raw_response' => $data,
				'error_details' => $error_details 
			) );
		}
		
		wp_send_json_success( array( 'data' => $data ) );
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

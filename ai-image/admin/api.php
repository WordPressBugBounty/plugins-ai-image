<?php
/*
 * OpenAI Rest Controller
 */
class BDTHEMES_OPENAI_REST_CONTROLLER extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'bdthemes/v1';
		$this->rest_base = 'openai';
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		register_rest_route( $this->namespace, $this->rest_base . '/api-key', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'get_openai_api_key' ],
			'permission_callback' => [ $this, 'api_key_permission_check' ],
		] );
	}

	public function image_generation_permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	public function api_key_permission_check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Nonce verification failed', 'bdthemes-element-pack' ), array( 'status' => 403 ) );
		}

		// Check if the user has the required capability
		return current_user_can( 'manage_options' );
	}

	public function get_openai_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_openai_api_key' );
		if ( ! empty( $api_key ) ) {
			return new WP_REST_Response( [ 'api_key' => sanitize_text_field( $api_key ) ], 200 );
		}
		return new WP_REST_Response( [ 'message' => 'API key not found' ], 404 );
	}
}

new BDTHEMES_OPENAI_REST_CONTROLLER();

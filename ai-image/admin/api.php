<?php
/*
 * OpenAI Rest Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class BDTHEMES_OPENAI_REST_CONTROLLER extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = 'bdthemes/v1';
		$this->rest_base = 'openai';
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		$permission = [ $this, 'api_key_permission_check' ];
		register_rest_route( $this->namespace, $this->rest_base . '/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_openai_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_openai_api_key' ],
				'permission_callback' => $permission,
			],
		] );
	}

	public function image_generation_permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	public function api_key_permission_check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Nonce verification failed', 'ai-image' ), array( 'status' => 403 ) );
		}

		// Check if the user has the required capability
		return current_user_can( 'manage_options' );
	}

	public function get_openai_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_openai_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}
}

new BDTHEMES_OPENAI_REST_CONTROLLER();

/**
 * Unsplash & Giphy API keys REST controller
 */
class BDTHEMES_AI_IMAGE_KEYS_REST_CONTROLLER extends WP_REST_Controller {

	public function __construct() {
		$this->namespace = 'bdthemes/v1';
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_rest_routes() {
		$permission = [ $this, 'api_key_permission_check' ];
		register_rest_route( $this->namespace, 'pexels/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_pexels_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_pexels_api_key' ],
				'permission_callback' => $permission,
			],
		] );
		register_rest_route( $this->namespace, 'unsplash/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_unsplash_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_unsplash_api_key' ],
				'permission_callback' => $permission,
			],
		] );
		register_rest_route( $this->namespace, 'pixabay/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_pixabay_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_pixabay_api_key' ],
				'permission_callback' => $permission,
			],
		] );
		register_rest_route( $this->namespace, 'giphy/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_giphy_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_giphy_api_key' ],
				'permission_callback' => $permission,
			],
		] );
		register_rest_route( $this->namespace, 'gemini/api-key', [
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_gemini_api_key' ],
				'permission_callback' => $permission,
			],
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'get_gemini_api_key' ],
				'permission_callback' => $permission,
			],
		] );
	}

	public function api_key_permission_check( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'Nonce verification failed', 'ai-image' ), [ 'status' => 403 ] );
		}
		return current_user_can( 'manage_options' );
	}

	public function get_pexels_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_pexels_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}

	public function get_unsplash_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_unsplash_access_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}

	public function get_pixabay_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_pixabay_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}

	public function get_giphy_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_giphy_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}

	public function get_gemini_api_key( WP_REST_Request $request ) {
		$api_key = get_option( 'bdthemes_gemini_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		return new WP_REST_Response( [
			'api_key' => $api_key ? sanitize_text_field( $api_key ) : null,
		], 200 );
	}
}

new BDTHEMES_AI_IMAGE_KEYS_REST_CONTROLLER();

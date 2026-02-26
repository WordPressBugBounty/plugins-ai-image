<?php
/**
 * API Proxy Endpoints
 * 
 * Proxies all external API calls through WordPress backend to hide API keys from frontend
 * 
 * @package BdThemes\AiImage
 */

namespace BDT_AI_IMG;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API Proxy REST Controller
 * Handles proxying of external API requests to hide API keys from frontend
 */
class API_Proxy_REST_Controller extends \WP_REST_Controller {
	
	public function __construct() {
		$this->namespace = 'bdthemes/v1';
		$this->rest_base = 'proxy';
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$permission = [ $this, 'permission_check' ];

		// Pexels proxy
		register_rest_route( $this->namespace, $this->rest_base . '/pexels/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'proxy_pexels_search' ],
			'permission_callback' => $permission,
			'args'                => [
				'query'    => [ 'required' => true, 'type' => 'string' ],
				'page'     => [ 'default' => 1, 'type' => 'integer' ],
				'per_page' => [ 'default' => 30, 'type' => 'integer' ],
			],
		] );

		// Pixabay proxy
		register_rest_route( $this->namespace, $this->rest_base . '/pixabay/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'proxy_pixabay_search' ],
			'permission_callback' => $permission,
			'args'                => [
				'query'    => [ 'required' => true, 'type' => 'string' ],
				'page'     => [ 'default' => 1, 'type' => 'integer' ],
				'per_page' => [ 'default' => 30, 'type' => 'integer' ],
			],
		] );

		// Unsplash proxy
		register_rest_route( $this->namespace, $this->rest_base . '/unsplash/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'proxy_unsplash_search' ],
			'permission_callback' => $permission,
			'args'                => [
				'query'    => [ 'required' => true, 'type' => 'string' ],
				'page'     => [ 'default' => 1, 'type' => 'integer' ],
				'per_page' => [ 'default' => 30, 'type' => 'integer' ],
			],
		] );

		// Giphy proxy
		register_rest_route( $this->namespace, $this->rest_base . '/giphy/search', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'proxy_giphy_search' ],
			'permission_callback' => $permission,
			'args'                => [
				'query'    => [ 'required' => true, 'type' => 'string' ],
				'offset'   => [ 'default' => 0, 'type' => 'integer' ],
				'limit'    => [ 'default' => 30, 'type' => 'integer' ],
			],
		] );
	}

	/**
	 * Permission check for API proxy
	 */
	public function permission_check( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Get API key for a provider
	 */
	private function get_provider_api_key( $provider ) {
		$key_option_map = [
			'pexels'   => 'bdthemes_pexels_api_key',
			'pixabay'  => 'bdthemes_pixabay_api_key',
			'unsplash' => 'bdthemes_unsplash_access_key',
			'giphy'    => 'bdthemes_giphy_api_key',
		];

		$default_key_map = [
			'pexels'   => AI_IMAGE_PEXELS_DEFAULT_KEY,
			'pixabay'  => AI_IMAGE_PIXABAY_DEFAULT_KEY,
			'unsplash' => AI_IMAGE_UNSPLASH_DEFAULT_KEY,
			'giphy'    => AI_IMAGE_GIPHY_DEFAULT_KEY,
		];

		if ( ! isset( $key_option_map[ $provider ] ) ) {
			return '';
		}

		$custom_key = get_option( $key_option_map[ $provider ], '' );
		$custom_key = is_string( $custom_key ) ? trim( $custom_key ) : '';

		if ( ! empty( $custom_key ) ) {
			return $custom_key;
		}

		// Use default encrypted key
		if ( isset( $default_key_map[ $provider ] ) ) {
			return decrypt_key( $default_key_map[ $provider ] );
		}

		return '';
	}

	/**
	 * Proxy Pexels search API
	 */
	public function proxy_pexels_search( \WP_REST_Request $request ) {
		$api_key = $this->get_provider_api_key( 'pexels' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', 'Pexels API key not configured', [ 'status' => 400 ] );
		}

		$query    = $request->get_param( 'query' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		// Use search endpoint if query provided, otherwise use curated endpoint
		$has_query = ! empty( trim( $query ) );
		
		if ( $has_query ) {
			$api_url = add_query_arg( [
				'query'    => urlencode( $query ),
				'page'     => $page,
				'per_page' => $per_page,
			], 'https://api.pexels.com/v1/search' );
		} else {
			$api_url = add_query_arg( [
				'page'     => $page,
				'per_page' => $per_page,
			], 'https://api.pexels.com/v1/curated' );
		}

		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Authorization' => $api_key,
			],
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return new \WP_REST_Response( $data, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Proxy Pixabay search API
	 */
	public function proxy_pixabay_search( \WP_REST_Request $request ) {
		$api_key = $this->get_provider_api_key( 'pixabay' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', 'Pixabay API key not configured', [ 'status' => 400 ] );
		}

		$query    = $request->get_param( 'query' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$api_url = add_query_arg( [
			'key'      => $api_key,
			'q'        => urlencode( $query ),
			'page'     => $page,
			'per_page' => $per_page,
			'image_type' => 'photo',
		], 'https://pixabay.com/api/' );

		$response = wp_remote_get( $api_url, [
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return new \WP_REST_Response( $data, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Proxy Unsplash search API
	 */
	public function proxy_unsplash_search( \WP_REST_Request $request ) {
		$api_key = $this->get_provider_api_key( 'unsplash' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', 'Unsplash API key not configured', [ 'status' => 400 ] );
		}

		$query    = $request->get_param( 'query' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		// Use search endpoint if query provided, otherwise use curated photos endpoint
		$has_query = ! empty( trim( $query ) );
		
		if ( $has_query ) {
			$api_url = add_query_arg( [
				'query'    => urlencode( $query ),
				'page'     => $page,
				'per_page' => $per_page,
			], 'https://api.unsplash.com/search/photos' );
		} else {
			$api_url = add_query_arg( [
				'page'     => $page,
				'per_page' => $per_page,
			], 'https://api.unsplash.com/photos' );
		}

		$response = wp_remote_get( $api_url, [
			'headers' => [
				'Authorization' => 'Client-ID ' . $api_key,
			],
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Normalize response format - curated endpoint returns array directly, search returns {results: []}
		if ( ! $has_query && is_array( $data ) ) {
			$data = [ 'results' => $data ];
		}

		return new \WP_REST_Response( $data, wp_remote_retrieve_response_code( $response ) );
	}

	/**
	 * Proxy Giphy search API
	 */
	public function proxy_giphy_search( \WP_REST_Request $request ) {
		$api_key = $this->get_provider_api_key( 'giphy' );

		if ( empty( $api_key ) ) {
			return new \WP_Error( 'no_api_key', 'Giphy API key not configured', [ 'status' => 400 ] );
		}

		$query  = $request->get_param( 'query' );
		$offset = $request->get_param( 'offset' );
		$limit  = $request->get_param( 'limit' );

		// Use search endpoint if query provided, otherwise use trending endpoint
		$has_query = ! empty( trim( $query ) );
		
		if ( $has_query ) {
			$api_url = add_query_arg( [
				'api_key' => $api_key,
				'q'       => urlencode( $query ),
				'offset'  => $offset,
				'limit'   => $limit,
			], 'https://api.giphy.com/v1/gifs/search' );
		} else {
			$api_url = add_query_arg( [
				'api_key' => $api_key,
				'offset'  => $offset,
				'limit'   => $limit,
			], 'https://api.giphy.com/v1/gifs/trending' );
		}

		$response = wp_remote_get( $api_url, [
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'api_error', $response->get_error_message(), [ 'status' => 500 ] );
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return new \WP_REST_Response( $data, wp_remote_retrieve_response_code( $response ) );
	}
}

// Initialize the API Proxy controller
new API_Proxy_REST_Controller();

<?php
/**
 * Block Toolbar AI Image Generation API
 * 
 * Handles image generation with smart fallback logic:
 * Gemini → OpenAI → Other providers (Pexels, Pixabay, Unsplash, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Smart AI Image Generation with Fallback or Specific Provider
 * 
 * Tries providers in order of preference:
 * 1. Gemini (if API key exists and enabled)
 * 2. OpenAI (if API key exists and enabled)
 * 3. Other free providers based on settings
 * 
 * Or uses a specific provider if requested via the 'provider' parameter.
 */
function ai_image_generate_smart_handler() {
	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'wp_rest' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
	}
	
	// Check user permissions
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
	}
	
	// Get the prompt
	$prompt = isset( $_POST['prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt'] ) ) : '';
	
	if ( empty( $prompt ) ) {
		wp_send_json_error( array( 'message' => __( 'No content provided for image generation.', 'ai-image' ) ) );
	}
	
	// Check if a specific provider was requested
	$requested_provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
	
	if ( ! empty( $requested_provider ) ) {
		// Try the specific provider requested
		$result = ai_image_try_specific_provider( $requested_provider, $prompt );
		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( array(
				'message' => sprintf( 
					__( 'Failed to generate image with %s: %s', 'ai-image' ),
					ucfirst( $requested_provider ),
					$result->get_error_message()
				)
			) );
		}
		return;
	}
	
	$attempted_providers = array();
	$skipped_providers = array();
	
	// Try Gemini first
	$gemini_key = get_option( 'bdthemes_gemini_api_key' );
	$gemini_key = is_string( $gemini_key ) ? trim( $gemini_key ) : '';
	$gemini_enabled = get_option( 'bdthemes_ai_image_provider_gemini', '1' ) === '1';
	
	if ( ! $gemini_enabled ) {
		$skipped_providers[] = 'Gemini (disabled in settings)';
	} elseif ( empty( $gemini_key ) ) {
		$skipped_providers[] = 'Gemini (no API key)';
	} else {
		$attempted_providers[] = 'Gemini';
		$result = ai_image_try_gemini( $prompt, $gemini_key );
		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( array(
				'image_url' => $result,
				'provider'  => 'Gemini',
				'message'   => __( 'Image generated with Gemini', 'ai-image' )
			) );
		}
	}
	
	// Try OpenAI second
	$openai_key = get_option( 'bdthemes_openai_api_key' );
	$openai_key = is_string( $openai_key ) ? trim( $openai_key ) : '';
	$openai_enabled = get_option( 'bdthemes_ai_image_provider_openai', '1' ) === '1';
	
	if ( ! $openai_enabled ) {
		$skipped_providers[] = 'OpenAI (disabled in settings)';
	} elseif ( empty( $openai_key ) ) {
		$skipped_providers[] = 'OpenAI (no API key)';
	} else {
		$attempted_providers[] = 'OpenAI';
		$result = ai_image_try_openai( $prompt, $openai_key );
		if ( ! is_wp_error( $result ) ) {
			wp_send_json_success( array(
				'image_url' => $result,
				'provider'  => 'OpenAI',
				'message'   => __( 'Image generated with OpenAI', 'ai-image' )
			) );
		}
	}
	
	// Try other providers as fallback (Pexels, Pixabay, Unsplash, Openverse)
	$result = ai_image_try_free_providers( $prompt, $attempted_providers, $skipped_providers );
	if ( ! is_wp_error( $result ) ) {
		wp_send_json_success( array(
			'image_url' => $result['url'],
			'provider'  => $result['provider'],
			'message'   => sprintf( __( 'Image found on %s', 'ai-image' ), $result['provider'] )
		) );
	}
	
	// If all fail, return detailed error
	$error_message = __( 'Failed to generate image with all available providers.', 'ai-image' );
	if ( ! empty( $skipped_providers ) ) {
		$error_message .= ' ' . sprintf( 
			__( 'Skipped: %s.', 'ai-image' ), 
			implode( ', ', $skipped_providers ) 
		);
	}
	if ( ! empty( $attempted_providers ) ) {
		$error_message .= ' ' . sprintf( 
			__( 'Tried but failed: %s.', 'ai-image' ), 
			implode( ', ', $attempted_providers ) 
		);
	}
	$error_message .= ' ' . __( 'Please check your API keys and provider settings.', 'ai-image' );
	
	wp_send_json_error( array(
		'message' => $error_message,
		'attempted' => $attempted_providers,
		'skipped' => $skipped_providers
	) );
}

/**
 * Try a specific provider
 */
function ai_image_try_specific_provider( $provider, $prompt ) {
	$provider = strtolower( $provider );
	
	// Check if provider is enabled
	$enabled = get_option( 'bdthemes_ai_image_provider_' . $provider, '1' ) === '1';
	if ( ! $enabled ) {
		return new \WP_Error( 
			'provider_disabled', 
			sprintf( __( '%s is disabled in settings', 'ai-image' ), ucfirst( $provider ) )
		);
	}
	
	// Handle AI providers (Gemini, OpenAI)
	if ( $provider === 'gemini' ) {
		$gemini_key = get_option( 'bdthemes_gemini_api_key' );
		$gemini_key = is_string( $gemini_key ) ? trim( $gemini_key ) : '';
		
		if ( empty( $gemini_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'Gemini API key not configured', 'ai-image' ) );
		}
		
		$result = ai_image_try_gemini( $prompt, $gemini_key );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return array(
			'image_url' => $result,
			'provider'  => 'Gemini',
			'message'   => __( 'Image generated with Gemini', 'ai-image' )
		);
	}
	
	if ( $provider === 'openai' ) {
		$openai_key = get_option( 'bdthemes_openai_api_key' );
		$openai_key = is_string( $openai_key ) ? trim( $openai_key ) : '';
		
		if ( empty( $openai_key ) ) {
			return new \WP_Error( 'no_api_key', __( 'OpenAI API key not configured', 'ai-image' ) );
		}
		
		$result = ai_image_try_openai( $prompt, $openai_key );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return array(
			'image_url' => $result,
			'provider'  => 'OpenAI',
			'message'   => __( 'Image generated with OpenAI', 'ai-image' )
		);
	}
	
	// Handle free stock photo providers
	$free_providers = array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy' );
	if ( in_array( $provider, $free_providers, true ) ) {
		$result = ai_image_fetch_from_provider( $provider, $prompt );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return array(
			'image_url' => $result,
			'provider'  => ucfirst( $provider ),
			'message'   => sprintf( __( 'Image found on %s', 'ai-image' ), ucfirst( $provider ) )
		);
	}
	
	return new \WP_Error( 'unknown_provider', __( 'Unknown provider', 'ai-image' ) );
}

/**
 * Try to generate image with Gemini
 */
function ai_image_try_gemini( $prompt, $api_key ) {
	$url = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-4.0-generate-001:predict?key=' . urlencode( $api_key );
	
	$body = wp_json_encode( array(
		'instances' => array(
			array(
				'prompt' => $prompt
			)
		),
		'parameters' => array(
			'sampleCount'        => 1,
			'aspectRatio'        => '1:1',
			'safetyFilterLevel'  => 'block_some',
			'personGeneration'   => 'allow_adult'
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
		return $response;
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$data = json_decode( $response_body, true );
	
	if ( $code !== 200 ) {
		$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Gemini API request failed.', 'ai-image' );
		return new \WP_Error( 'gemini_failed', $error_msg );
	}
	
	// Extract base64 image
	if ( isset( $data['predictions'][0]['bytesBase64Encoded'] ) ) {
		$base64_image = $data['predictions'][0]['bytesBase64Encoded'];
		return 'data:image/png;base64,' . $base64_image;
	}
	
	return new \WP_Error( 'gemini_no_image', __( 'No image returned from Gemini', 'ai-image' ) );
}

/**
 * Try to generate image with OpenAI
 */
function ai_image_try_openai( $prompt, $api_key ) {
	$url = 'https://api.openai.com/v1/images/generations';
	
	$body = wp_json_encode( array(
		'prompt' => $prompt,
		'n'      => 1,
		'size'   => '1024x1024',
		'model'  => 'dall-e-3'
	) );
	
	$response = wp_remote_post( $url, array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		'body'    => $body,
		'timeout' => 60,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	$data = json_decode( $response_body, true );
	
	if ( $code !== 200 ) {
		$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'OpenAI API request failed.', 'ai-image' );
		return new \WP_Error( 'openai_failed', $error_msg );
	}
	
	// Extract image URL
	if ( isset( $data['data'][0]['url'] ) ) {
		return $data['data'][0]['url'];
	}
	
	return new \WP_Error( 'openai_no_image', __( 'No image returned from OpenAI', 'ai-image' ) );
}

/**
 * Try free providers as fallback (Pexels, Pixabay, Unsplash, Openverse)
 */
function ai_image_try_free_providers( $prompt, &$attempted_providers, &$skipped_providers ) {
	// Get provider order from settings
	$default_order = array( 'pexels', 'pixabay', 'unsplash', 'openverse' );
	$saved_order   = get_option( 'bdthemes_ai_image_provider_order', $default_order );
	
	if ( ! is_array( $saved_order ) || empty( $saved_order ) ) {
		$saved_order = $default_order;
	}
	
	// Filter to only free providers
	$free_providers = array_intersect( $saved_order, array( 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy' ) );
	
	foreach ( $free_providers as $provider ) {
		// Check if provider is enabled
		$enabled = get_option( 'bdthemes_ai_image_provider_' . $provider, '1' ) === '1';
		if ( ! $enabled ) {
			$skipped_providers[] = ucfirst( $provider ) . ' (disabled in settings)';
			continue;
		}
		
		// Add to attempted list
		$attempted_providers[] = ucfirst( $provider );
		
		// Try to fetch from provider
		$result = ai_image_fetch_from_provider( $provider, $prompt );
		if ( ! is_wp_error( $result ) ) {
			return array(
				'url'      => $result,
				'provider' => ucfirst( $provider )
			);
		} 
	}
	
	return new \WP_Error( 'all_providers_failed', __( 'All image providers failed.', 'ai-image' ) );
}

/**
 * Fetch image from a specific free provider
 */
function ai_image_fetch_from_provider( $provider, $query ) {
	switch ( $provider ) {
		case 'pexels':
			return ai_image_fetch_from_pexels( $query );
		case 'pixabay':
			return ai_image_fetch_from_pixabay( $query );
		case 'unsplash':
			return ai_image_fetch_from_unsplash( $query );
		case 'openverse':
			return ai_image_fetch_from_openverse( $query );
		case 'giphy':
			return ai_image_fetch_from_giphy( $query );
		default:
			return new \WP_Error( 'unknown_provider', __( 'Unknown provider', 'ai-image' ) );
	}
}

/**
 * Fetch from Pexels
 */
function ai_image_fetch_from_pexels( $query ) {
	// Pexels is free and doesn't require API key for basic usage
	// If you have an API key, it should be configured
	$api_key = 'l7Pk56fQ7sjfslcgFBUXVuggY5sZ2EIRLtSvM1pBwLyzpIWjdQ93gVpH';
	
	if ( empty( $api_key ) ) {
		return new \WP_Error( 'pexels_no_key', __( 'Pexels API key not configured', 'ai-image' ) );
	}
	
	$url = 'https://api.pexels.com/v1/search?query=' . urlencode( $query ) . '&per_page=1';
	
	$response = wp_remote_get( $url, array(
		'headers' => array(
			'Authorization' => $api_key,
		),
		'timeout' => 30,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( isset( $data['photos'][0]['src']['large'] ) ) {
		return $data['photos'][0]['src']['large'];
	}
	
	return new \WP_Error( 'pexels_no_results', __( 'No results from Pexels', 'ai-image' ) );
}

/**
 * Fetch from Pixabay
 */
function ai_image_fetch_from_pixabay( $query ) {
	// Pixabay has a fixed API key
	$api_key = '27427772-5e3b7770787f4e0e591d5d2eb';
	
	if ( empty( $api_key ) ) {
		return new \WP_Error( 'pixabay_no_key', __( 'Pixabay API key not configured', 'ai-image' ) );
	}
	
	// Ensure query is not empty
	if ( empty( $query ) || strlen( trim( $query ) ) === 0 ) {
		return new \WP_Error( 'pixabay_empty_query', __( 'Empty search query for Pixabay', 'ai-image' ) );
	}
	
	// Pixabay has a 100 character limit for search queries
	$search_query = $query;
	if ( strlen( $search_query ) > 100 ) {
		$search_query = substr( $search_query, 0, 100 );
		// Remove partial word at the end
		$search_query = trim( preg_replace( '/\s+\S*$/', '', $search_query ) );
	}
	
	$url = 'https://pixabay.com/api/?key=' . urlencode( $api_key ) . '&q=' . urlencode( $search_query ) . '&per_page=3&image_type=all&pretty=true&order=popular';
	
	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	
	// Check for HTTP errors first (before JSON decode)
	if ( $code !== 200 ) {
		// Pixabay returns plain text errors for 400 codes
		$error_msg = $response_body ? $response_body : __( 'Pixabay API request failed with code: ' . $code, 'ai-image' );
		return new \WP_Error( 'pixabay_failed', $error_msg );
	}
	
	$data = json_decode( $response_body, true );
	
	// Check for JSON decode errors
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new \WP_Error( 'pixabay_json_error', __( 'Invalid JSON response from Pixabay', 'ai-image' ) );
	}
	
	// Check if we have results
	if ( isset( $data['hits'] ) && is_array( $data['hits'] ) && count( $data['hits'] ) > 0 ) {
		// Return large image URL
		if ( isset( $data['hits'][0]['largeImageURL'] ) ) {
			return $data['hits'][0]['largeImageURL'];
		}
	}
	
	// Log the total hits count if available
	$total_hits = isset( $data['totalHits'] ) ? $data['totalHits'] : 'unknown';
	
	return new \WP_Error( 'pixabay_no_results', __( 'No results from Pixabay for query: ' . $search_query, 'ai-image' ) );
}

/**
 * Fetch from Unsplash
 */
function ai_image_fetch_from_unsplash( $query ) {
	$api_key = get_option( 'bdthemes_unsplash_access_key', '' );
	$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
	
	if ( empty( $api_key ) ) {
		return new \WP_Error( 'unsplash_no_key', __( 'Unsplash API key not configured', 'ai-image' ) );
	}
	
	$url = 'https://api.unsplash.com/search/photos?query=' . urlencode( $query ) . '&per_page=1';
	
	$response = wp_remote_get( $url, array(
		'headers' => array(
			'Authorization' => 'Client-ID ' . $api_key,
		),
		'timeout' => 30,
	) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	
	if ( isset( $data['results'][0]['urls']['regular'] ) ) {
		return $data['results'][0]['urls']['regular'];
	}
	
	return new \WP_Error( 'unsplash_no_results', __( 'No results from Unsplash', 'ai-image' ) );
}

/**
 * Fetch from Openverse
 */
function ai_image_fetch_from_openverse( $query ) {
	// Ensure query is not empty
	if ( empty( $query ) || strlen( trim( $query ) ) === 0 ) {
		return new \WP_Error( 'openverse_empty_query', __( 'Empty search query for Openverse', 'ai-image' ) );
	}
	
	// Simplify query for better results - remove AI descriptors and keep core subject
	$search_query = ai_image_simplify_query_for_openverse( $query );
	
	$url = 'https://api.openverse.engineering/v1/images/?q=' . urlencode( $search_query ) . '&page_size=1';

	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	
	// Check for HTTP errors
	if ( $code !== 200 ) {
		$error_msg = __( 'Openverse API request failed with code: ' . $code, 'ai-image' );
		return new \WP_Error( 'openverse_failed', $error_msg );
	}
	
	$data = json_decode( $response_body, true );
	
	// Check for JSON decode errors
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new \WP_Error( 'openverse_json_error', __( 'Invalid JSON response from Openverse', 'ai-image' ) );
	}
	
	// Check if we have results
	if ( isset( $data['results'] ) && is_array( $data['results'] ) && count( $data['results'] ) > 0 ) {
		// Return image URL
		if ( isset( $data['results'][0]['url'] ) ) {
			return $data['results'][0]['url'];
		} 
	}
	
	$result_count = isset( $data['result_count'] ) ? $data['result_count'] : 'unknown';
	
	return new \WP_Error( 'openverse_no_results', __( 'No results from Openverse for query: ' . $search_query, 'ai-image' ) );
}

/**
 * Simplify query for Openverse by taking first 30 characters
 */
function ai_image_simplify_query_for_openverse( $query ) {
	// Openverse works better with shorter, simpler queries
	// Just take the first 30 characters (main subject only)
	$simplified = substr( $query, 0, 30 );
	
	// Remove partial word at the end
	$simplified = trim( preg_replace( '/\s+\S*$/', '', $simplified ) );
	
	// If still too short or empty, use up to 40 chars
	if ( empty( $simplified ) || strlen( $simplified ) < 5 ) {
		$simplified = substr( $query, 0, 40 );
		$simplified = trim( preg_replace( '/\s+\S*$/', '', $simplified ) );
	}
	
	return trim( $simplified );
}

/**
 * Fetch from Giphy
 */
function ai_image_fetch_from_giphy( $query ) {
	$api_key = get_option( 'bdthemes_giphy_api_key', '' );
	$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
	
	if ( empty( $api_key ) ) {
		return new \WP_Error( 'giphy_no_key', __( 'Giphy API key not configured', 'ai-image' ) );
	}
	
	// Ensure query is not empty
	if ( empty( $query ) || strlen( trim( $query ) ) === 0 ) {
		return new \WP_Error( 'giphy_empty_query', __( 'Empty search query for Giphy', 'ai-image' ) );
	}
	
	// Simplify query for Giphy - GIFs need simple, short search terms
	$search_query = ai_image_simplify_query_for_giphy( $query );
	
	$url = 'https://api.giphy.com/v1/gifs/search?api_key=' . urlencode( $api_key ) . '&q=' . urlencode( $search_query ) . '&limit=1&rating=g';
	
	$response = wp_remote_get( $url, array( 'timeout' => 30 ) );
	
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	
	$code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );
	
	// Check for HTTP errors
	if ( $code !== 200 ) {
		$error_msg = __( 'Giphy API request failed with code: ' . $code, 'ai-image' );
		return new \WP_Error( 'giphy_failed', $error_msg );
	}
	
	$data = json_decode( $response_body, true );
	
	// Check for JSON decode errors
	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new \WP_Error( 'giphy_json_error', __( 'Invalid JSON response from Giphy', 'ai-image' ) );
	}
	
	// Check if we have results
	if ( isset( $data['data'] ) && is_array( $data['data'] ) && count( $data['data'] ) > 0 ) {
		// Return GIF URL
		if ( isset( $data['data'][0]['images']['original']['url'] ) ) {
			return $data['data'][0]['images']['original']['url'];
		} 
	}
	
	$result_count = isset( $data['pagination']['total_count'] ) ? $data['pagination']['total_count'] : 'unknown';
	
	return new \WP_Error( 'giphy_no_results', __( 'No results from Giphy for query: ' . $search_query, 'ai-image' ) );
}

/**
 * Simplify query for Giphy by taking first 20 characters
 */
function ai_image_simplify_query_for_giphy( $query ) {
	// Giphy works best with very short, simple search terms
	// Just take the first 20 characters (main action/subject only)
	$simplified = substr( $query, 0, 20 );
	
	// Remove partial word at the end
	$simplified = trim( preg_replace( '/\s+\S*$/', '', $simplified ) );
	
	// If still too short or empty, use up to 30 chars
	if ( empty( $simplified ) || strlen( $simplified ) < 3 ) {
		$simplified = substr( $query, 0, 30 );
		$simplified = trim( preg_replace( '/\s+\S*$/', '', $simplified ) );
	}
	
	return trim( $simplified );
}

// Register AJAX handler
add_action( 'wp_ajax_ai_image_generate_smart', 'ai_image_generate_smart_handler' );

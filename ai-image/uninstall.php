<?php
/**
 * Uninstall AI Image Plugin
 * 
 * Deletes all plugin options and custom data when the plugin is deleted
 *
 * @package AI_Image
 * @since 2.0.0
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin options
 */
function bdthemes_ai_image_uninstall() {
	// API Keys
	delete_option( 'bdthemes_openai_api_key' );
	delete_option( 'bdthemes_gemini_api_key' );
	delete_option( 'bdthemes_unsplash_access_key' );
	delete_option( 'bdthemes_giphy_api_key' );

	// Provider Settings
	$provider_ids = array( 'global', 'pexels', 'pixabay', 'unsplash', 'openverse', 'giphy', 'openai', 'gemini' );
	foreach ( $provider_ids as $id ) {
		delete_option( 'bdthemes_ai_image_provider_' . $id );
	}
	delete_option( 'bdthemes_ai_image_provider_order' );

	// General Settings
	delete_option( 'bdthemes_ai_image_max_upload_width' );
	delete_option( 'bdthemes_ai_image_max_upload_height' );
	delete_option( 'bdthemes_ai_image_default_provider' );
	delete_option( 'bdthemes_ai_image_attribution' );
	delete_option( 'bdthemes_ai_image_auto_alt_text' );
	delete_option( 'bdthemes_ai_image_auto_title' );
	delete_option( 'bdthemes_ai_image_hide_media_modal_tab' );

	// Display Settings
	delete_option( 'bdthemes_ai_image_default_view_mode' );
	delete_option( 'bdthemes_ai_image_items_per_page' );
	delete_option( 'bdthemes_ai_image_thumbnail_size' );
	delete_option( 'bdthemes_ai_image_load_more_mode' );

	// Custom Image Sizes
	delete_option( 'bdthemes_ai_image_custom_sizes' );

	// Clear any transients/cache that might exist
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for cleanup during uninstall
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bdthemes_ai_image_%'" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for cleanup during uninstall
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bdthemes_ai_image_%'" );
}

// Run the uninstall function
bdthemes_ai_image_uninstall();

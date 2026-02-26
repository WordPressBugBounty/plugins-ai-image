<?php
/**
 * Plugin Constants
 * 
 * Centralized location for all plugin constants including default API keys
 * 
 * @package BdThemes\AiImage
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Encryption Key
 * 
 * This key is used to encrypt/decrypt the default API keys
 * DO NOT change this value after deploying the plugin
 */
define('AI_IMAGE_ENCRYPTION_KEY', 'bdthemes-ai-image-secure-key-2026');

// Load security functions for encryption/decryption
require_once __DIR__ . '/security.php';

/**
 * Default API Keys (Encrypted)
 * 
 * These keys are used as fallback when users don't provide their own API keys
 * Keys are encrypted for security - use BDT_AI_IMG\decrypt_key() to get plain text
 */
define('AI_IMAGE_PEXELS_DEFAULT_KEY', 'NPPVOr+xpukWAuCAShwKvBFGIsA7XUecjL2oHtPiIoBpAsY/DOZu6yRMgjDbX6vJO1SjtLowWfsx/YAMJXWSbg699vBxqLblNzUdksXVpIc=');
define('AI_IMAGE_PIXABAY_DEFAULT_KEY', 'c/qMAD417ZxgEhAltS4YwPWcvh48RBQEhiVJVrvIknwJPxCNZupnqLoon+uA7YKd8vZBPVoqk4W+H6fO/eVZTA==');
define('AI_IMAGE_UNSPLASH_DEFAULT_KEY', 'LdC0mkRWxhH91XOfClPz06LI2eEmxCQ+C1qdqIXGmMVoRBpQRJh5zAGBjkbpSJI8CiLCjp5CzJqvA/KW1Ckigg==');
define('AI_IMAGE_GIPHY_DEFAULT_KEY', 'Oq4xYrqf8/th0H9ROkPb98oma+UaCzfdRqlPL/zsCaN1Ve1r2urUQFzY2SgdiEaQQ9grMXon9VLE+0szBpdqsw==');


<?php
/**
 * Security Functions for API Key Encryption/Decryption
 * 
 * @package BdThemes\AiImage
 */

namespace BDT_AI_IMG;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Encrypt a string using AES-256-CBC encryption
 * 
 * @param string $plain_text The text to encrypt
 * @return string The encrypted string (base64 encoded)
 */
function encrypt_key($plain_text) {
	if (empty($plain_text)) {
		return '';
	}

	// Get the encryption key from constant
	if (!defined('AI_IMAGE_ENCRYPTION_KEY')) {
		return $plain_text; // Return plain text if no encryption key is defined
	}

	$encryption_key = AI_IMAGE_ENCRYPTION_KEY;
	
	// Generate a key from the constant using MD5 (as requested)
	$key = md5($encryption_key, true);
	
	// Generate a random initialization vector
	$iv_length = openssl_cipher_iv_length('aes-256-cbc');
	$iv = openssl_random_pseudo_bytes($iv_length);
	
	// Encrypt the data
	$encrypted = openssl_encrypt(
		$plain_text,
		'aes-256-cbc',
		$key,
		OPENSSL_RAW_DATA,
		$iv
	);
	
	// Combine IV and encrypted data, then base64 encode
	return base64_encode($iv . $encrypted);
}

/**
 * Decrypt a string using AES-256-CBC decryption
 * 
 * @param string $encrypted_text The encrypted text (base64 encoded)
 * @return string The decrypted plain text
 */
function decrypt_key($encrypted_text) {
	if (empty($encrypted_text)) {
		return '';
	}

	// Get the encryption key from constant
	if (!defined('AI_IMAGE_ENCRYPTION_KEY')) {
		return $encrypted_text; // Return as-is if no encryption key is defined
	}

	$encryption_key = AI_IMAGE_ENCRYPTION_KEY;
	
	// Generate a key from the constant using MD5 (as requested)
	$key = md5($encryption_key, true);
	
	// Decode the base64 encoded data
	$data = base64_decode($encrypted_text);
	
	if ($data === false) {
		return ''; // Invalid base64
	}
	
	// Extract IV and encrypted data
	$iv_length = openssl_cipher_iv_length('aes-256-cbc');
	$iv = substr($data, 0, $iv_length);
	$encrypted = substr($data, $iv_length);
	
	// Decrypt the data
	$decrypted = openssl_decrypt(
		$encrypted,
		'aes-256-cbc',
		$key,
		OPENSSL_RAW_DATA,
		$iv
	);
	
	return $decrypted !== false ? $decrypted : '';
}

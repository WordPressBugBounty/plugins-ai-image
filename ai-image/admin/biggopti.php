<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AdminApiBiggopties class
 */
class AI_IMAGE_AdminApiBiggopties {

	private static $biggopties = [];

	private static $instance;

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_bdt_admin_api_biggopti_dismiss', [ $this, 'bdt_admin_api_ai_image_dismiss' ] );
		add_action( 'wp_ajax_bdt_admin_api_ai_image_dismiss', [ $this, 'bdt_admin_api_ai_image_dismiss' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_assets' ] );
	}

	/**
	 * Dismiss Admin API Biggopti.
	 */
	public function bdt_admin_api_ai_image_dismiss() {
		$nonce      = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$display_id = isset( $_POST['display_id'] ) ? sanitize_text_field( wp_unslash( $_POST['display_id'] ) ) : '';
		$id         = isset( $_POST['id'] ) ? esc_attr( wp_unslash( $_POST['id'] ) ) : '';
		$meta       = isset( $_POST['meta'] ) ? esc_attr( wp_unslash( $_POST['meta'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ai-image' ) ) {
			wp_send_json_error();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( empty( $display_id ) && ! empty( $id ) ) {
			$prefix = 'bdt-admin-biggopti-api-biggopti-';
			if ( strpos( $id, $prefix ) === 0 ) {
				$display_id = substr( $id, strlen( $prefix ) );
			} else {
				$display_id = $id;
			}
		}

		if ( ! empty( $display_id ) ) {
			if ( 'user' === $meta ) {
				$user_key = 'bdt-admin-biggopti-api-biggopti-' . $display_id;
				update_user_meta( get_current_user_id(), $user_key, true );
			} else {
				$dismissals_option                 = get_option( 'bdt_biggopti_dismissals', [] );
				$dismissals_option[ $display_id ] = [ 'dismissed_at' => time() ];
				update_option( 'bdt_biggopti_dismissals', $dismissals_option, false );
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	public function load_assets() {
		wp_enqueue_style( 'ai-image-admin-api-biggopti', BDT_AI_IMAGE_ASSETS . 'admin/css/biggopti.css', [], BDT_AI_IMAGE_VERSION );
		wp_enqueue_script( 'ai-image-admin-api-biggopti', BDT_AI_IMAGE_ASSETS . 'admin/js/biggopti.js', [ 'jquery' ], BDT_AI_IMAGE_VERSION, true );

		$dismissals           = get_option( 'bdt_biggopti_dismissals', [] );
		$dismissed_display_ids = [];
		$prefix               = 'bdt-admin-biggopti-api-biggopti-';
		foreach ( array_keys( $dismissals ) as $key ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$dismissed_display_ids[] = substr( $key, strlen( $prefix ) );
			} else {
				$dismissed_display_ids[] = $key;
			}
		}

		$current_sector = '';
		if ( isset( $_GET['page'] ) ) {
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			if ( 'ai-image-settings' === $page || 'bdt-ai-media-tab' === $page ) {
				$current_sector = 'plugin_dashboard';
			}
		}

		wp_localize_script(
			'ai-image-admin-api-biggopti',
			'AIImageAdminApiBiggoptiConfig',
			[
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'ai-image' ),
				'isPro'               => false,
				'assetsUrl'           => BDT_AI_IMAGE_ASSETS,
				'dismissedDisplayIds' => $dismissed_display_ids,
				'currentSector'       => $current_sector,
			]
		);
	}
}

AI_IMAGE_AdminApiBiggopties::get_instance();

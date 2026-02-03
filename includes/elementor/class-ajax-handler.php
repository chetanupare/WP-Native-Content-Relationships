<?php
/**
 * AJAX Handler for Elementor Integration
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX Handler for Elementor Integration
 *
 * Handles AJAX requests for Elementor dynamic tags and controls.
 * Provides secure AJAX endpoints for relationship management in Elementor.
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */
class NATICORE_Elementor_Ajax_Handler {

	/**
	 * Instance
	 * @var NATICORE_Elementor_Ajax_Handler|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_naticore_get_relationship_types', array( $this, 'get_relationship_types' ) );
		add_action( 'wp_ajax_nopriv_naticore_get_relationship_types', array( $this, 'get_relationship_types' ) );
	}

	/**
	 * Get relationship types for Elementor
	 */
	public function get_relationship_types() {
		// Verify nonce
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'naticore_elementor_nonce' ) ) {
			wp_send_json_error( array(
				'success' => false,
				'message' => __( 'Security check failed', 'native-content-relationships' )
			) );
		}

		$target_type = isset( $_POST['target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['target_type'] ) ) : 'post';

		// Get relationship types
		$types = NATICORE_Elementor_Integration::get_relationship_types_for_elementor( $target_type );

		wp_send_json_success( array(
			'success' => true,
			'data' => $types
		) );
	}
}

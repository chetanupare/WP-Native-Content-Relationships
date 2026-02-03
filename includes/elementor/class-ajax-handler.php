<?php
/**
 * AJAX Handler for Elementor Integration
 *
 * @package Native Content Relationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NCR_Elementor_Ajax_Handler {

	/**
	 * Instance
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
		add_action( 'wp_ajax_ncr_get_relationship_types', array( $this, 'get_relationship_types' ) );
		add_action( 'wp_ajax_nopriv_ncr_get_relationship_types', array( $this, 'get_relationship_types' ) );
	}

	/**
	 * Get relationship types for Elementor
	 */
	public function get_relationship_types() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'ncr_elementor_nonce' ) ) {
			wp_send_json_error( array(
				'success' => false,
				'message' => __( 'Security check failed', 'native-content-relationships' )
			) );
		}

		$target_type = isset( $_POST['target_type'] ) ? sanitize_text_field( wp_unslash( $_POST['target_type'] ) ) : 'post';

		// Get relationship types
		$types = NCR_Elementor_Integration::get_relationship_types_for_elementor( $target_type );

		wp_send_json_success( array(
			'success' => true,
			'data' => $types
		) );
	}
}

<?php
/**
 * User Relationships AJAX Handlers
 * Handles AJAX requests for user relationship management
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Relationships AJAX Handlers
 *
 * Provides AJAX functionality for managing user relationships in the WordPress
 * admin area, including search, creation, and deletion operations.
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */
class NATICORE_User_Relations_Ajax {

	/**
	 * Instance
	 *
	 * @var NATICORE_User_Relations_Ajax|null
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
		// Add user relation
		add_action( 'wp_ajax_naticore_add_user_relation', array( $this, 'add_user_relation' ) );

		// Remove user relation
		add_action( 'wp_ajax_naticore_remove_user_relation', array( $this, 'remove_user_relation' ) );
	}

	/**
	 * AJAX: Add user relationship
	 */
	public function add_user_relation() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ?? '' ), 'naticore_search_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		// Get and validate parameters
		$from_id = absint( $_POST['from_id'] ?? 0 );
		$to_id   = absint( $_POST['to_id'] ?? 0 );
		$type    = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		$to_type = sanitize_text_field( wp_unslash( $_POST['to_type'] ?? 'post' ) );

		if ( ! $from_id || ! $to_id || ! $type ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		// Check capabilities
		if ( 'user' === $to_type ) {
			// Post to user relationship
			if ( ! current_user_can( 'edit_post', $from_id ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}
		} elseif ( 'user' !== $to_type ) {
			// User to post relationship
			if ( ! current_user_can( 'edit_user', $from_id ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}
		}

		// Add the relationship
		$result = wp_add_relation( $from_id, $to_id, $type, null, $to_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'relation_id' => $result,
				'message'     => __( 'Relationship added successfully', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * AJAX: Remove user relationship
	 */
	public function remove_user_relation() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ?? '' ), 'naticore_search_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		// Get and validate parameters
		$from_id = absint( $_POST['from_id'] ?? 0 );
		$to_id   = absint( $_POST['to_id'] ?? 0 );
		$type    = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
		$to_type = sanitize_text_field( wp_unslash( $_POST['to_type'] ?? 'post' ) );

		if ( ! $from_id || ! $to_id || ! $type ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		// Check capabilities
		if ( 'user' === $to_type ) {
			// Post to user relationship
			if ( ! current_user_can( 'edit_post', $from_id ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}
		} elseif ( 'user' !== $to_type ) {
			// User to post relationship
			if ( ! current_user_can( 'edit_user', $from_id ) ) {
				wp_send_json_error( 'Insufficient permissions' );
			}
		}

		// Remove the relationship
		$result = wp_remove_relation( $from_id, $to_id, $type, $to_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Relationship removed successfully', 'native-content-relationships' ),
			)
		);
	}
}

// Initialize
NATICORE_User_Relations_Ajax::get_instance();

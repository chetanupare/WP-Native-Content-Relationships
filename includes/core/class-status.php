<?php
/**
 * Relationship Status Workflow
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Status
 *
 * Manages relationship status workflows.
 */
class NATICORE_Status {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'naticore_relation_is_allowed', array( $this, 'validate_status_transition' ), 8, 2 );
		add_action( 'naticore_relation_added', array( $this, 'set_initial_status' ), 10, 4 );
	}

	/**
	 * Get default workflows
	 *
	 * @return array
	 */
	private static function get_default_workflows() {
		return array(
			'hiring' => array(
				'label'      => __( 'Hiring Workflow', 'native-content-relationships' ),
				'statuses'   => array(
					'applied'     => array( 'label' => __( 'Applied', 'native-content-relationships' ), 'color' => '#7c3aed' ),
					'shortlisted' => array( 'label' => __( 'Shortlisted', 'native-content-relationships' ), 'color' => '#2563eb' ),
					'interviewed' => array( 'label' => __( 'Interviewed', 'native-content-relationships' ), 'color' => '#d97706' ),
					'rejected'    => array( 'label' => __( 'Rejected', 'native-content-relationships' ), 'color' => '#dc2626' ),
					'hired'       => array( 'label' => __( 'Hired', 'native-content-relationships' ), 'color' => '#16a34a' ),
				),
				'allowed'    => array(
					'applied'     => array( 'shortlisted', 'rejected' ),
					'shortlisted' => array( 'interviewed', 'rejected' ),
					'interviewed' => array( 'hired', 'rejected' ),
					'rejected'    => array(),
					'hired'       => array(),
				),
			),
			'editorial' => array(
				'label'      => __( 'Editorial Workflow', 'native-content-relationships' ),
				'statuses'   => array(
					'pending'   => array( 'label' => __( 'Pending', 'native-content-relationships' ), 'color' => '#d97706' ),
					'approved'  => array( 'label' => __( 'Approved', 'native-content-relationships' ), 'color' => '#16a34a' ),
					'published' => array( 'label' => __( 'Published', 'native-content-relationships' ), 'color' => '#2563eb' ),
					'rejected'  => array( 'label' => __( 'Rejected', 'native-content-relationships' ), 'color' => '#dc2626' ),
				),
				'allowed'    => array(
					'pending'   => array( 'approved', 'rejected' ),
					'approved'  => array( 'published', 'rejected' ),
					'published' => array(),
					'rejected'  => array( 'pending' ),
				),
			),
			'sponsorship' => array(
				'label'      => __( 'Sponsorship Workflow', 'native-content-relationships' ),
				'statuses'   => array(
					'proposed'  => array( 'label' => __( 'Proposed', 'native-content-relationships' ), 'color' => '#7c3aed' ),
					'negotiating' => array( 'label' => __( 'Negotiating', 'native-content-relationships' ), 'color' => '#d97706' ),
					'accepted'  => array( 'label' => __( 'Accepted', 'native-content-relationships' ), 'color' => '#16a34a' ),
					'declined'  => array( 'label' => __( 'Declined', 'native-content-relationships' ), 'color' => '#dc2626' ),
					'active'    => array( 'label' => __( 'Active', 'native-content-relationships' ), 'color' => '#2563eb' ),
					'completed' => array( 'label' => __( 'Completed', 'native-content-relationships' ), 'color' => '#6b7280' ),
				),
				'allowed'    => array(
					'proposed'    => array( 'negotiating', 'declined' ),
					'negotiating' => array( 'accepted', 'declined' ),
					'accepted'    => array( 'active' ),
					'declined'    => array( 'proposed' ),
					'active'      => array( 'completed' ),
					'completed'   => array(),
				),
			),
		);
	}

	/**
	 * Get all workflows
	 *
	 * @return array
	 */
	public static function get_workflows() {
		$custom = get_option( 'ncr_workflows', array() );
		return wp_parse_args( $custom, self::get_default_workflows() );
	}

	/**
	 * Get status for a relationship
	 *
	 * @param int $relation_id Relation ID.
	 * @return string|null Status key or null.
	 */
	public static function get_status( $relation_id ) {
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			return NATICORE_Meta_API::get_meta( $relation_id, '_status' );
		}
		return null;
	}

	/**
	 * Set status for a relationship
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $status      Status key.
	 * @return bool True on success.
	 */
	public static function set_status( $relation_id, $status ) {
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			return NATICORE_Meta_API::update_meta( $relation_id, '_status', $status );
		}
		return false;
	}

	/**
	 * Validate status transition
	 *
	 * @param bool  $is_allowed Current allowed status.
	 * @param array $context    Relationship context.
	 * @return bool
	 */
	public function validate_status_transition( $is_allowed, $context ) {
		if ( ! $is_allowed ) {
			return false;
		}

		$relation_id = $context['relation_id'] ?? 0;
		$new_status  = $context['new_status'] ?? '';

		if ( ! $relation_id || ! $new_status ) {
			return true;
		}

		$current_status = self::get_status( $relation_id );
		if ( ! $current_status ) {
			return true;
		}

		$workflows = self::get_workflows();
		$type      = $context['type'];

		// Find which workflow this type belongs to
		foreach ( $workflows as $workflow_key => $workflow ) {
			if ( isset( $workflow['statuses'][ $new_status ] ) ) {
				$allowed_transitions = $workflow['allowed'][ $current_status ] ?? array();
				return in_array( $new_status, $allowed_transitions, true );
			}
		}

		return true;
	}

	/**
	 * Set initial status when relationship is created
	 *
	 * @param int    $relation_id Relation ID.
	 * @param int    $from_id     Source post ID.
	 * @param int    $to_id       Target post ID.
	 * @param string $type        Relationship type.
	 */
	public function set_initial_status( $relation_id, $from_id, $to_id, $type ) {
		$workflows = self::get_workflows();

		foreach ( $workflows as $workflow_key => $workflow ) {
			$first_status = array_key_first( $workflow['statuses'] );
			if ( $first_status ) {
				self::set_status( $relation_id, $first_status );
				return;
			}
		}
	}

	/**
	 * Get available transitions for a status
	 *
	 * @param string $status Current status.
	 * @param string $type   Relationship type.
	 * @return array Available status transitions.
	 */
	public static function get_transitions( $status, $type = '' ) {
		$workflows = self::get_workflows();

		foreach ( $workflows as $workflow_key => $workflow ) {
			if ( isset( $workflow['statuses'][ $status ] ) ) {
				$allowed = $workflow['allowed'][ $status ] ?? array();
				$result  = array();
				foreach ( $allowed as $transition ) {
					if ( isset( $workflow['statuses'][ $transition ] ) ) {
						$result[ $transition ] = $workflow['statuses'][ $transition ];
					}
				}
				return $result;
			}
		}

		return array();
	}

	/**
	 * AJAX: Change relationship status
	 */
	public static function ajax_change_status() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$relation_id = absint( $_POST['relation_id'] ?? 0 );
		$new_status  = sanitize_text_field( wp_unslash( $_POST['new_status'] ?? '' ) );

		if ( ! $relation_id || ! $new_status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'native-content-relationships' ) ) );
		}

		// Verify the user can edit the source post of this relationship.
		global $wpdb;
		$table   = $wpdb->prefix . 'content_relations';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$from_id = $wpdb->get_var( $wpdb->prepare( "SELECT from_id FROM `{$table}` WHERE id = %d", $relation_id ) );
		if ( ! $from_id || ! current_user_can( 'edit_post', (int) $from_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this content.', 'native-content-relationships' ) ) );
		}

		$current_status = self::get_status( $relation_id );
		$context        = array(
			'relation_id' => $relation_id,
			'new_status'  => $new_status,
			'type'        => '', // Will be resolved in validation
		);

		$is_allowed = apply_filters( 'naticore_relation_is_allowed', true, $context );
		if ( ! $is_allowed ) {
			wp_send_json_error( array( 'message' => __( 'Invalid status transition.', 'native-content-relationships' ) ) );
		}

		$result = self::set_status( $relation_id, $new_status );
		if ( $result ) {
			wp_send_json_success( array( 'status' => $new_status ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update status.', 'native-content-relationships' ) ) );
		}
	}
}

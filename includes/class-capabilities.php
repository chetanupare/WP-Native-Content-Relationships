<?php
/**
 * Capability Control
 * Manages permissions for relationship operations
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Capabilities {

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Flag to prevent infinite recursion
	 */
	private static $mapping_in_progress = false;

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
		add_filter( 'map_meta_cap', array( $this, 'map_meta_caps' ), 10, 4 );
	}

	/**
	 * Map meta capabilities
	 *
	 * @param array  $caps    Required capabilities
	 * @param string $cap    Capability being checked
	 * @param int    $user_id User ID
	 * @param array  $args   Additional arguments
	 * @return array
	 */
	public function map_meta_caps( $caps, $cap, $user_id, $args ) {
		// Prevent infinite recursion
		if ( self::$mapping_in_progress ) {
			return $caps;
		}

		// Handle relationship capabilities
		if ( 'naticore_create_relation' === $cap || 'naticore_delete_relation' === $cap ) {

			$from_id = isset( $args[0] ) ? absint( $args[0] ) : 0;
			$to_id   = isset( $args[1] ) ? absint( $args[1] ) : 0;
			$type    = isset( $args[2] ) ? sanitize_key( $args[2] ) : 'related_to';

			if ( $from_id ) {
				$type_info = NATICORE_Relation_Types::get_type( $type );
				$from_type = $type_info ? $type_info['from_type'] : 'post';

				// Use flag to prevent infinite recursion
				self::$mapping_in_progress = true;

				if ( 'post' === $from_type ) {
					$caps = map_meta_cap( 'edit_post', $user_id, $from_id );
				} elseif ( 'user' === $from_type ) {
					$caps = map_meta_cap( 'edit_user', $user_id, $from_id );
				} elseif ( 'term' === $from_type ) {
					// Terms generally use manage_categories or similar, but edit_term is meta cap
					$caps = map_meta_cap( 'edit_term', $user_id, $from_id );
				}

				self::$mapping_in_progress = false;
			} else {
				$caps[] = 'do_not_allow';
			}
		}

		if ( 'naticore_manage_relation_types' === $cap ) {
			// Only administrators can manage relation types
			$caps = array( 'manage_options' );
		}

		return $caps;
	}
}

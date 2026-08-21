<?php
/**
 * Bidirectional Auto-Sync
 *
 * @package Native_Content_Relationships
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Bidirectional_Sync
 *
 * Automatically syncs relationship metadata between bidirectional relationships.
 * When metadata changes on one side, the reverse is automatically updated.
 */
class NATICORE_Bidirectional_Sync {

	/**
	 * Constructor - hook into metadata changes
	 */
	public function __construct() {
		add_action( 'naticore_relation_meta_updated', array( $this, 'sync_meta_forward' ), 10, 4 );
		add_action( 'naticore_relation_added', array( $this, 'sync_initial_meta' ), 20, 4 );
	}

	/**
	 * Sync metadata to reverse relationship when updated
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  Meta value.
	 * @param bool   $is_reverse  Whether this is already a reverse sync.
	 */
	public function sync_meta_forward( $relation_id, $meta_key, $meta_value, $is_reverse = false ) {
		// Prevent infinite loops
		if ( $is_reverse ) {
			return;
		}

		$settings = NATICORE_Settings::get_instance();
		if ( ! $settings->get_setting( 'bidirectional_sync', 1 ) ) {
			return;
		}

		// Get the relation details
		$relation = $this->get_relation_by_id( $relation_id );
		if ( ! $relation ) {
			return;
		}

		// Check if this type supports bidirectional
		$type_info = NATICORE_Relation_Types::get_type( $relation->type );
		if ( ! $type_info || ! $type_info['bidirectional'] ) {
			return;
		}

		// Find the reverse relation
		$reverse_id = $this->find_reverse_relation_id( $relation );
		if ( ! $reverse_id ) {
			return;
		}

		// Sync the meta to reverse relation
		$this->update_relation_meta( $reverse_id, $meta_key, $meta_value, true );
	}

	/**
	 * Sync initial metadata when a bidirectional relation is created
	 *
	 * @param int    $relation_id Relation ID.
	 * @param int    $from_id     Source post ID.
	 * @param int    $to_id       Target post ID.
	 * @param string $type        Relationship type.
	 */
	public function sync_initial_meta( $relation_id, $from_id, $to_id, $type ) {
		$settings = NATICORE_Settings::get_instance();
		if ( ! $settings->get_setting( 'bidirectional_sync', 1 ) ) {
			return;
		}

		$type_info = NATICORE_Relation_Types::get_type( $type );
		if ( ! $type_info || ! $type_info['bidirectional'] ) {
			return;
		}

		// Find the reverse relation
		$relation = $this->get_relation_by_id( $relation_id );
		if ( ! $relation ) {
			return;
		}

		$reverse_id = $this->find_reverse_relation_id( $relation );
		if ( ! $reverse_id ) {
			return;
		}

		// Copy all meta from forward to reverse
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			$all_meta = NATICORE_Meta_API::get_all_meta( $relation_id );
			if ( ! empty( $all_meta ) ) {
				foreach ( $all_meta as $key => $value ) {
					$this->update_relation_meta( $reverse_id, $key, $value, true );
				}
			}
		}
	}

	/**
	 * Get relation by ID
	 *
	 * @param int $relation_id Relation ID.
	 * @return object|null
	 */
	private function get_relation_by_id( $relation_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}content_relations` WHERE id = %d",
				$relation_id
			)
		);
	}

	/**
	 * Find the reverse relation ID
	 *
	 * @param object $relation Relation object.
	 * @return int|null Reverse relation ID.
	 */
	private function find_reverse_relation_id( $relation ) {
		global $wpdb;

		if ( 'bidirectional' !== $relation->direction ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
		$reverse = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND type = %s AND to_type = %s AND direction = 'bidirectional'",
				$relation->to_id,
				$relation->from_id,
				$relation->type,
				$relation->to_type
			)
		);

		return $reverse ? absint( $reverse ) : null;
	}

	/**
	 * Update relation meta directly (bypassing hooks to prevent loops)
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  Meta value.
	 * @param bool   $is_reverse  Whether this is a reverse sync.
	 */
	private function update_relation_meta( $relation_id, $meta_key, $meta_value, $is_reverse = false ) {
		if ( ! class_exists( 'NATICORE_Meta_API' ) ) {
			return;
		}

		// Use a flag to prevent recursion
		static $syncing = array();
		$sync_key = $relation_id . '_' . $meta_key;

		if ( isset( $syncing[ $sync_key ] ) ) {
			return;
		}

		$syncing[ $sync_key ] = true;

		NATICORE_Meta_API::update_meta( $relation_id, $meta_key, $meta_value );

		unset( $syncing[ $sync_key ] );
	}

	/**
	 * Get sync settings fields
	 *
	 * @return array Settings fields.
	 */
	public static function get_settings_fields() {
		return array(
			array(
				'name'        => 'bidirectional_sync',
				'label'       => __( 'Enable Bidirectional Auto-Sync', 'native-content-relationships' ),
				'description' => __( 'Automatically sync metadata between bidirectional relationships.', 'native-content-relationships' ),
				'type'        => 'checkbox',
				'default'     => 1,
			),
		);
	}
}

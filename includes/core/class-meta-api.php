<?php
/**
 * Metadata API for content relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Meta_API {

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
		// Register the meta table to $wpdb so WP knows about it
		global $wpdb;
		$wpdb->content_relationmeta = $wpdb->prefix . 'content_relationmeta';
	}

	/**
	 * Add metadata to a relationship
	 */
	public static function add_meta( $relation_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'content_relation', $relation_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update relationship metadata
	 */
	public static function update_meta( $relation_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'content_relation', $relation_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete relationship metadata
	 */
	public static function delete_meta( $relation_id, $meta_key, $meta_value = '' ) {
		return delete_metadata( 'content_relation', $relation_id, $meta_key, $meta_value );
	}

	/**
	 * Get relationship metadata
	 */
	public static function get_meta( $relation_id, $meta_key = '', $single = false ) {
		return get_metadata( 'content_relation', $relation_id, $meta_key, $single );
	}

	/**
	 * Get all metadata for a relationship
	 *
	 * @param int $relation_id Relation ID.
	 * @return array Associative array of meta_key => meta_value pairs.
	 */
	public static function get_all_meta( $relation_id ) {
		return get_metadata( 'content_relation', $relation_id );
	}
}

// Global helper functions
function wp_add_relation_meta( $relation_id, $meta_key, $meta_value, $unique = false ) {
	return NATICORE_Meta_API::add_meta( $relation_id, $meta_key, $meta_value, $unique );
}

function wp_update_relation_meta( $relation_id, $meta_key, $meta_value, $prev_value = '' ) {
	return NATICORE_Meta_API::update_meta( $relation_id, $meta_key, $meta_value, $prev_value );
}

function wp_delete_relation_meta( $relation_id, $meta_key, $meta_value = '' ) {
	return NATICORE_Meta_API::delete_meta( $relation_id, $meta_key, $meta_value );
}

function wp_get_relation_meta( $relation_id, $meta_key = '', $single = false ) {
	return NATICORE_Meta_API::get_meta( $relation_id, $meta_key, $single );
}

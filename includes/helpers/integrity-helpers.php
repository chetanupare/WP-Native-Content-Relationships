<?php
/**
 * Relationship Integrity Helpers
 * Modular functions for validating relationship data.
 *
 * @package NativeContentRelationships
 * @since 1.0.18
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check for relationships where objects no longer exist.
 *
 * @param object $rel Relationship object.
 * @return bool True if orphan detected.
 */
function ncr_is_orphaned_relation( $rel ) {
	$type_info = NATICORE_Relation_Types::get_type( $rel->type );
	$from_type = $type_info ? $type_info['from_type'] : 'post';
	$to_type   = $rel->to_type;

	$from_exists = false;
	if ( 'post' === $from_type ) {
		$from_exists = (bool) get_post( $rel->from_id );
	} elseif ( 'user' === $from_type ) {
		$from_exists = (bool) get_userdata( $rel->from_id );
	} elseif ( 'term' === $from_type ) {
		$from_exists = (bool) get_term( $rel->from_id );
	}

	$to_exists = false;
	if ( 'post' === $to_type ) {
		$to_exists = (bool) get_post( $rel->to_id );
	} elseif ( 'user' === $to_type ) {
		$to_exists = (bool) get_userdata( $rel->to_id );
	} elseif ( 'term' === $to_type ) {
		$term      = get_term( $rel->to_id );
		$to_exists = (bool) ( $term && ! is_wp_error( $term ) );
	}

	return ! $from_exists || ! $to_exists;
}

/**
 * Check for relationships with unregistered types.
 *
 * @param object $rel Relationship object.
 * @return bool True if type is unregistered.
 */
function ncr_has_unregistered_type( $rel ) {
	return ! NATICORE_Relation_Types::exists( $rel->type );
}

/**
 * Check for relationships violating max_connections.
 * Returns offending IDs if limit exceeded.
 *
 * @param int    $from_id Source ID.
 * @param string $type    Relation type.
 * @param string $to_type Target object type.
 * @param int    $limit   Maximum connections allowed.
 * @return array List of relationship IDs that exceed the limit.
 */
function ncr_get_exceeded_connections( $from_id, $type, $to_type, $limit ) {
	global $wpdb;
	
	if ( $limit <= 0 ) {
		return array();
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT id FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND type = %s AND to_type = %s ORDER BY created_at ASC",
		$from_id,
		$type,
		$to_type
	) );

	if ( count( $ids ) > $limit ) {
		// Return the most recent IDs that exceed the limit
		return array_slice( $ids, $limit );
	}

	return array();
}

/**
 * Check for directional inconsistencies.
 * Detects one-way types stored as bidirectional.
 *
 * @param object $rel Relationship object.
 * @return bool True if direction is inconsistent.
 */
function ncr_has_directional_inconsistency( $rel ) {
	$type_info = NATICORE_Relation_Types::get_type( $rel->type );
	if ( ! $type_info ) {
		return false;
	}

	// If type is unidirectional but record is bidirectional
	if ( ! $type_info['bidirectional'] && 'bidirectional' === $rel->direction ) {
		return true;
	}

	return false;
}

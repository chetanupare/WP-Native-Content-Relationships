<?php
/**
 * Relationship Caching Layer
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Cache
 *
 * Intelligent caching for relationship queries.
 */
class NATICORE_Cache {

	/**
	 * Cache group — must match the group used in class-api.php
	 */
	const GROUP = 'naticore_relationships';

	/**
	 * Cache TTL (1 hour)
	 */
	const TTL = 3600;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'naticore_relation_added', array( $this, 'invalidate_on_change' ), 100, 4 );
		add_action( 'naticore_relation_removed', array( $this, 'invalidate_on_remove' ), 100, 3 );
		add_action( 'naticore_relation_meta_updated', array( $this, 'invalidate_meta' ), 100, 3 );
	}

	/**
	 * Get cached result or compute
	 *
	 * @param string $cache_key Cache key.
	 * @param callable $callback Function to compute if not cached.
	 * @param int $ttl Optional TTL override.
	 * @return mixed
	 */
	public static function remember( $cache_key, $callback, $ttl = 0 ) {
		if ( ! $ttl ) {
			$ttl = self::TTL;
		}

		$cached = wp_cache_get( $cache_key, self::GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$result = $callback();
		wp_cache_set( $cache_key, $result, self::GROUP, $ttl );

		return $result;
	}

	/**
	 * Invalidate cache for a post
	 *
	 * Performs targeted deletion of known cache key patterns instead of
	 * flushing the entire group. This prevents cache stampedes on busy sites.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function invalidate_post( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return;
		}

		// Delete known cache key patterns used by NATICORE_API::get_related().
		// Keys follow: naticore_get_related_{id}_{type}_{to_type}_{limit}_{orderby}_{manual}
		wp_cache_delete( "naticore_get_related_{$post_id}_all_all_0_default", self::GROUP );
		wp_cache_delete( "naticore_get_related_{$post_id}_all_all_0_default_0", self::GROUP );
		wp_cache_delete( "naticore_get_related_{$post_id}_all_all_0_default_1", self::GROUP );

		// Delete existence check caches.
		// Keys follow: naticore_exists_{from}_{to}_{type}_{to_type}
		// We cannot enumerate all possible {to}_{type} combinations,
		// but the primary use case is clearing the "all" wildcard entry.
		wp_cache_delete( "naticore_exists_{$post_id}_all_all", self::GROUP );

		// Delete count caches used by NATICORE_Cache::count_relations().
		wp_cache_delete( "count_{$post_id}", self::GROUP );
		wp_cache_delete( "count_{$post_id}_all", self::GROUP );

		// Delete relation list caches used by NATICORE_Cache::get_all_relations().
		wp_cache_delete( "relations_{$post_id}", self::GROUP );
		wp_cache_delete( "related_to_{$post_id}", self::GROUP );
	}

	/**
	 * Invalidate on relationship change
	 *
	 * @param int    $relation_id Relation ID.
	 * @param int    $from_id     Source post ID.
	 * @param int    $to_id       Target post ID.
	 * @param string $type        Relationship type.
	 */
	public function invalidate_on_change( $relation_id, $from_id, $to_id, $type ) {
		self::invalidate_post( $from_id );
		self::invalidate_post( $to_id );
	}

	/**
	 * Invalidate on relationship removal
	 *
	 * @param int    $from_id Source post ID.
	 * @param int    $to_id   Target post ID.
	 * @param string $type    Relationship type.
	 */
	public function invalidate_on_remove( $from_id, $to_id, $type ) {
		self::invalidate_post( $from_id );
		self::invalidate_post( $to_id );
	}

	/**
	 * Invalidate on meta update
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  Meta value.
	 */
	public function invalidate_meta( $relation_id, $meta_key, $meta_value ) {
		// Get the relation to find post IDs
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cache invalidation
		$relation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT from_id, to_id FROM `{$wpdb->prefix}content_relations` WHERE id = %d",
				$relation_id
			)
		);

		if ( $relation ) {
			self::invalidate_post( $relation->from_id );
			self::invalidate_post( $relation->to_id );
		}
	}

	/**
	 * Cached version of get_all_relations
	 *
	 * @param int $post_id Post ID.
	 * @return array Relations.
	 */
	public static function get_all_relations( $post_id ) {
		return self::remember(
			'relations_' . $post_id,
			function () use ( $post_id ) {
				return NATICORE_API::get_all_relations( $post_id );
			}
		);
	}

	/**
	 * Cached version of get_related_to
	 *
	 * @param int $post_id Post ID.
	 * @return array Relations.
	 */
	public static function get_related_to( $post_id ) {
		return self::remember(
			'related_to_' . $post_id,
			function () use ( $post_id ) {
				return NATICORE_API::get_related_to( $post_id );
			}
		);
	}

	/**
	 * Cached count of relations
	 *
	 * @param int    $post_id Post ID.
	 * @param string $type    Optional type filter.
	 * @return int Count.
	 */
	public static function count_relations( $post_id, $type = '' ) {
		$key = 'count_' . $post_id . ( $type ? '_' . $type : '' );

		return self::remember(
			$key,
			function () use ( $post_id, $type ) {
				return NATICORE_API::count_relations( $post_id, $type );
			}
		);
	}

	/**
	 * Get cache stats
	 *
	 * @return array Cache statistics.
	 */
	public static function get_stats() {
		return array(
			'group' => self::GROUP,
			'ttl'   => self::TTL,
		);
	}

	/**
	 * Clear all NCR cache
	 */
	public static function flush_all() {
		wp_cache_flush_group( self::GROUP );
	}
}

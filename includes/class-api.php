<?php
/**
 * Relationship API
 * Core functions for managing content relationships
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Relationship API
 *
 * Core functions for managing content relationships between posts, users, and terms.
 * Provides methods for creating, removing, and querying relationships with
 * support for different relationship types and directions.
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */
class NATICORE_API {

	/**
	 * Instance
	 *
	 * @var NATICORE_API|null
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
		// Nothing to do.
	}

	/**
	 * Add a relationship between two content items
	 *
	 * Creates a new relationship between two content items with support for posts, users, and terms.
	 * The relationship type determines the semantic meaning and default direction.
	 *
	 * @since 1.0.0
	 * @since 1.0.10 Added support for user relationships ($to_type = 'user')
	 * @since 1.0.11 Added support for term relationships ($to_type = 'term')
	 *
	 * @param int    $from_id       The ID of the source content (post, user, or term).
	 * @param int    $to_id         The ID of the target content (post, user, or term).
	 * @param string $type          Type of relationship (e.g., 'related_to', 'favorite_posts', 'categorized_as').
	 * @param string $direction     Direction: 'unidirectional' or 'bidirectional' (auto-determined from type).
	 * @param string $to_type       Target type: 'post', 'user', or 'term' (default: 'post').
	 *
	 * @return int|WP_Error Relationship ID on success, WP_Error on failure.
	 *
	 * @throws WP_Error When:
	 *         - User lacks permission to create relationships.
	 *         - Relationship type is not allowed.
	 *         - Invalid content IDs provided.
	 *         - Content cannot be related to itself (posts only).
	 *         - Target content does not exist.
	 *         - Relationship would create infinite loop.
	 *         - Relationship already exists.
	 *         - Database operation fails.
	 *
	 * @example Create a post-to-post relationship
	 * ```php
	 * $relation_id = NATICORE_API::add_relation( 123, 456, 'related_to' );
	 * if ( is_wp_error( $relation_id ) ) {
	 *     error_log( 'Failed to create relationship: ' . $relation_id->get_error_message() );
	 * }
	 * ```
	 *
	 * @example Create a user-to-post relationship
	 * ```php
	 * $relation_id = NATICORE_API::add_relation( 15, 123, 'favorite_posts', null, 'post' );
	 * ```
	 *
	 * @example Create a post-to-term relationship
	 * ```php
	 * $relation_id = NATICORE_API::add_relation( 123, 25, 'categorized_as', null, 'term' );
	 * ```
	 *
	 * @see NATICORE_Relation_Types::get_type() Get relationship type details
	 * @see wp_is_related() Check if relationship exists
	 * @see NATICORE_API::remove_relation() Remove a relationship
	 */
	public static function add_relation( $from_id, $to_id, $type = 'related_to', $direction = null, $to_type = 'post' ) {
		global $wpdb;

		// Apply filter to check if relation is allowed.
		$context    = array(
			'from_id' => $from_id,
			'to_id'   => $to_id,
			'type'    => $type,
		);
		$is_allowed = apply_filters( 'naticore_relation_is_allowed', true, $context );
		if ( ! $is_allowed ) {
			return new WP_Error( 'relation_not_allowed', __( 'This relationship is not allowed.', 'native-content-relationships' ) );
		}

		// Check capabilities.

		$can_create = current_user_can( 'naticore_create_relation', $from_id, $to_id );

		if ( ! $can_create ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to create this relationship.', 'native-content-relationships' ) );
		}

		$from_id = absint( $from_id );
		$to_id   = absint( $to_id );
		$to_type = in_array( $to_type, array( 'post', 'user', 'term' ), true ) ? $to_type : 'post';

		// Validate inputs.
		if ( 0 === $from_id || 0 === $to_id ) {
			return new WP_Error( 'invalid_id', __( 'Invalid content ID.', 'native-content-relationships' ) );
		}

		// Prevent self-linking (only for post-to-post).
		if ( $from_id === $to_id && 'post' === $to_type ) {
			return new WP_Error( 'self_relation', __( 'Content cannot be related to itself.', 'native-content-relationships' ) );
		}

		// Validate target exists.
		if ( 'post' === $to_type ) {
			$to_post = get_post( $to_id );
			if ( ! $to_post ) {
				return new WP_Error( 'post_not_found', __( 'Target post does not exist.', 'native-content-relationships' ) );
			}
		} elseif ( 'user' === $to_type ) {
			$to_user = get_userdata( $to_id );
			if ( ! $to_user ) {
				return new WP_Error( 'user_not_found', __( 'Target user does not exist.', 'native-content-relationships' ) );
			}
		} elseif ( 'term' === $to_type ) {
			$to_term = get_term( $to_id );
			if ( is_wp_error( $to_term ) || ! $to_term ) {
				return new WP_Error( 'term_not_found', __( 'Target term does not exist.', 'native-content-relationships' ) );
			}
		}

		// Validate source post exists.
		$from_post = get_post( $from_id );
		if ( ! $from_post ) {
			return new WP_Error( 'post_not_found', __( 'Source post does not exist.', 'native-content-relationships' ) );
		}

		// Check immutable mode (lock relationships after publish).
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'immutable_mode', 0 ) ) {
			// Check if posts are published.
			if ( 'publish' === $from_post->post_status ) {
				// Only allow changes via admin or WP-CLI.
				if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
					return new WP_Error( 'immutable_mode', __( 'Relationships for published posts are locked. Use the admin interface or WP-CLI to modify.', 'native-content-relationships' ) );
				}
			}
		}

		// Check if relation already exists FIRST (before other checks).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND type = %s AND to_type = %s",
				$from_id,
				$to_id,
				$type,
				$to_type
			)
		);

		if ( $existing ) {
			return new WP_Error( 'relation_exists', __( 'This relationship already exists.', 'native-content-relationships' ) );
		}

		// Check for infinite loops (A → B → A) - respect settings.
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'prevent_circular', 1 ) ) {
			if ( self::would_create_loop( $from_id, $to_id, $type ) ) {
				return new WP_Error( 'infinite_loop', __( 'This relationship would create an infinite loop.', 'native-content-relationships' ) );
			}
		}

		// Check max relationships limit.
		$max_relationships = $settings->get_setting( 'max_relationships', 0 );
		if ( $max_relationships > 0 ) {
			$current_count = count( self::get_all_relations( $from_id ) );
			if ( $current_count >= $max_relationships ) {
				/* translators: %d: Maximum number of relationships allowed */
				return new WP_Error( 'max_relationships', sprintf( __( 'Maximum relationships limit (%d) reached for this post.', 'native-content-relationships' ), $max_relationships ) );
			}
		}

		// Validate relation type exists.
		if ( ! NATICORE_Relation_Types::exists( $type ) ) {
			return new WP_Error( 'invalid_relation_type', __( 'Invalid relationship type.', 'native-content-relationships' ) );
		}

		// Check if post types are allowed for this relation type (skip for user targets).
		if ( 'post' === $to_type ) {
			if ( ! NATICORE_Relation_Types::are_post_types_allowed( $type, $from_post->post_type, $to_post->post_type ) ) {
				return new WP_Error( 'post_type_not_allowed', __( 'This relationship type is not allowed between these post types.', 'native-content-relationships' ) );
			}
		}

		$type_supports_bidirectional = NATICORE_Relation_Types::is_bidirectional( $type );

		// Determine direction.
		if ( null === $direction ) {
			// Default direction is derived from type, but can be overridden by global setting.
			$settings          = NATICORE_Settings::get_instance();
			$default_direction = $settings->get_setting( 'default_direction', $type_supports_bidirectional ? 'bidirectional' : 'unidirectional' );
			$direction         = 'bidirectional' === $default_direction ? 'bidirectional' : 'unidirectional';
		}

		// Validate direction value.
		if ( ! in_array( $direction, array( 'unidirectional', 'bidirectional' ), true ) ) {
			$direction = 'unidirectional';
		}

		// Enforce type capability: one-way types must stay one-way.
		if ( ! $type_supports_bidirectional ) {
			$direction = 'unidirectional';
		}

		// Generate deterministic hash for this relationship.
		$relation_hash = self::generate_relation_hash( $from_id, $to_id, $type );

		// Insert relationship.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert
		$result = $wpdb->insert(
			$wpdb->prefix . 'content_relations',
			array(
				'from_id'    => $from_id,
				'to_id'      => $to_id,
				'type'       => $type,
				'direction'  => $direction,
				'to_type'    => $to_type,
				'to_user_id' => 'user' === $to_type ? $to_id : null,
				'to_term_id' => 'term' === $to_type ? $to_id : null,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to create relationship.', 'native-content-relationships' ) );
		}

		$relation_id = $wpdb->insert_id;

		// If bidirectional, create reverse relation.
		if ( 'bidirectional' === $direction && 'post' === $to_type ) {
			// Only create reverse for post-to-post relationships.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table insert
			$wpdb->insert(
				$wpdb->prefix . 'content_relations',
				array(
					'from_id'    => $to_id,
					'to_id'      => $from_id,
					'type'       => $type,
					'direction'  => $direction,
					'to_type'    => 'post',
					'to_user_id' => null,
				),
				array( '%d', '%d', '%s', '%s', '%s', '%d' )
			);
		}

		// Create relation object for hooks.
		$relation_object = (object) array(
			'id'         => $relation_id,
			'from_id'    => $from_id,
			'to_id'      => $to_id,
			'type'       => $type,
			'direction'  => $direction,
			'to_type'    => $to_type,
			'to_user_id' => 'user' === $to_type ? $to_id : null,
			'to_term_id' => 'term' === $to_type ? $to_id : null,
			'hash'       => $relation_hash,
		);

		// Fire actions
		do_action( 'naticore_relation_added', $relation_id, $from_id, $to_id, $type );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		do_action( 'wp_content_relation_added', $from_id, $to_id, $type );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		do_action( 'wpcr_relation_created', $relation_object );

		// Debug logging
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'debug_logging', 0 ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled via settings
				error_log( sprintf( 'WPNCR: Relation added - from_id: %d, to_id: %d, type: %s', $from_id, $to_id, $type ) );
			}
		}

		// Clear cache
		self::clear_cache( $from_id, $to_id, $type, $to_type );

		return $relation_id;
	}

	/**
	 * Generate deterministic hash for a relationship
	 *
	 * @param int    $from_id Source post ID
	 * @param int    $to_id   Target post ID
	 * @param string $type    Relationship type
	 * @return string SHA1 hash
	 */
	public static function generate_relation_hash( $from_id, $to_id, $type ) {
		// Create stable hash: from_id + to_id + type
		// Sort IDs to ensure same hash regardless of direction (for bidirectional)
		$ids = array( absint( $from_id ), absint( $to_id ) );
		sort( $ids );
		$hash_string = $ids[0] . '|' . $ids[1] . '|' . sanitize_text_field( $type );
		return sha1( $hash_string );
	}

	/**
	 * Check if adding a relation would create an infinite loop
	 *
	 * @param int    $from_id Source post ID
	 * @param int    $to_id   Target post ID
	 * @param string $type    Relation type
	 * @return bool True if loop would be created
	 */
	private static function would_create_loop( $from_id, $to_id, $type ) {
		// For unidirectional relations, check if to_id already relates back to from_id
		$reverse_exists = self::is_related( $to_id, $from_id, $type );

		if ( $reverse_exists ) {
			return true;
		}

		// Check for longer loops (A → B → C → A)
		// This is a simplified check - for production, you might want a more thorough graph traversal
		$visited = array( $from_id );
		return self::check_loop_recursive( $to_id, $from_id, $type, $visited, 0, 10 ); // Max depth 10
	}

	/**
	 * Recursively check for loops
	 *
	 * @param int    $current Current post ID
	 * @param int    $target Target post ID (the one we're trying to reach)
	 * @param string $type   Relation type
	 * @param array  $visited Visited post IDs
	 * @param int    $depth  Current depth
	 * @param int    $max_depth Maximum depth to check
	 * @return bool
	 */
	private static function check_loop_recursive( $current, $target, $type, &$visited, $depth, $max_depth ) {
		if ( $depth >= $max_depth ) {
			return false; // Too deep, assume no loop
		}

		if ( $current === $target ) {
			return true; // Found a loop!
		}

		if ( in_array( $current, $visited, true ) ) {
			return false; // Already visited this node
		}

		$visited[] = $current;

		// Get all relations from current post
		$related = self::get_related( $current, $type );

		foreach ( $related as $rel ) {
			if ( self::check_loop_recursive( $rel['id'], $target, $type, $visited, $depth + 1, $max_depth ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Remove a relationship between two content items
	 *
	 * Deletes an existing relationship between two content items. For bidirectional relationships,
	 * both the forward and reverse relationships are removed. Automatically clears related cache.
	 *
	 * @since 1.0.0
	 * @since 1.0.10 Added support for user relationships
	 * @since 1.0.11 Added support for term relationships and cache clearing
	 *
	 * @param int    $from_id The ID of the source content (post, user, or term)
	 * @param int    $to_id   The ID of the target content (post, user, or term)
	 * @param string $type    Type of relationship to remove (optional, null for all types)
	 * @param string $to_type Target type: 'post', 'user', 'term' (default: 'post')
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure
	 *
	 * @throws WP_Error When:
	 *         - User lacks permission to delete relationships.
	 *         - Invalid content IDs provided.
	 *         - Relationship does not exist.
	 *         - Database operation fails.
	 *
	 * @example Remove a specific relationship
	 * ```php
	 * $result = NATICORE_API::remove_relation( 123, 456, 'related_to' );
	 * if ( is_wp_error( $result ) ) {
	 *     error_log( 'Failed to remove relationship: ' . $result->get_error_message() );
	 * }
	 * ```
	 *
	 * @example Remove all relationships between two items
	 * ```php
	 * $result = NATICORE_API::remove_relation( 123, 456 );
	 * ```
	 *
	 * @example Remove user relationship
	 * ```php
	 * $result = NATICORE_API::remove_relation( 15, 123, 'favorite_posts', 'post' );
	 * ```
	 *
	 * @example Remove term relationship
	 * ```php
	 * $result = NATICORE_API::remove_relation( 123, 25, 'categorized_as', 'term' );
	 * ```
	 *
	 * @see NATICORE_API::add_relation() Create a relationship
	 * @see NATICORE_API::is_related() Check if relationship exists
	 * @see NATICORE_API::clear_cache() Clear relationship cache
	 *
	 * @action naticore_relation_removed Fires after relationship is removed
	 * @action wp_content_relation_removed Backward compatibility hook
	 * @action wpcr_relation_deleted Extended relationship object hook
	 */
	public static function remove_relation( $from_id, $to_id, $type = null, $to_type = 'post' ) {
		global $wpdb;

		// Check capabilities

		$can_delete = current_user_can( 'naticore_delete_relation', $from_id, $to_id );

		if ( ! $can_delete ) {
			return new WP_Error( 'permission_denied', __( 'You do not have permission to delete this relationship.', 'native-content-relationships' ) );
		}

		$from_id = absint( $from_id );
		$to_id   = absint( $to_id );
		$to_type = in_array( $to_type, array( 'post', 'user' ), true ) ? $to_type : 'post';

		// Get direction before deletion - use conditional queries for PHPCS compliance
		if ( $type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$direction = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT direction FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND type = %s AND to_type = %s",
					$from_id,
					$to_id,
					$type,
					$to_type
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$direction = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT direction FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND to_type = %s",
					$from_id,
					$to_id,
					$to_type
				)
			);
		}

		// Delete relationship(s)
		$where = array(
			'from_id' => $from_id,
			'to_id'   => $to_id,
			'to_type' => $to_type,
		);

		if ( $type ) {
			$where['type'] = $type;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
		$result = $wpdb->delete( $wpdb->prefix . 'content_relations', $where, array( '%d', '%d', '%s', $type ? '%s' : null ) );

		if ( false === $result ) {
			return new WP_Error( 'db_error', __( 'Failed to remove relationship.', 'native-content-relationships' ) );
		}

		// If bidirectional, remove reverse relation too
		if ( 'bidirectional' === $direction && 'post' === $to_type ) {
			$reverse_where = array(
				'from_id' => $to_id,
				'to_id'   => $from_id,
				'to_type' => 'post',
			);
			if ( $type ) {
				$reverse_where['type'] = $type;
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$wpdb->delete( $wpdb->prefix . 'content_relations', $reverse_where, array( '%d', '%d', '%s', $type ? '%s' : null ) );
		}

		// Fire action
		do_action( 'naticore_relation_removed', $from_id, $to_id, $type );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		do_action( 'wp_content_relation_removed', $from_id, $to_id, $type );

		// Create relation object for hooks
		$relation_object = (object) array(
			'from_id' => $from_id,
			'to_id'   => $to_id,
			'type'    => $type,
			'to_type' => $to_type,
		);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		do_action( 'wpcr_relation_deleted', $relation_object );

		// Debug logging
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'debug_logging', 0 ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( sprintf( 'WPNCR: Relation removed - from_id: %d, to_id: %d, type: %s', $from_id, $to_id, $type ) );
		}

		// Clear cache
		self::clear_cache( $from_id, $to_id, $type, $to_type );

		return true;
	}

	/**
	 * Clear relationship cache
	 *
	 * Clears cached relationship data for specific items or all relationships.
	 * Used to maintain cache consistency when relationships are modified.
	 *
	 * @since 1.0.11
	 *
	 * @param int|null    $from_id Optional. Source content ID to clear cache for.
	 * @param int|null    $to_id   Optional. Target content ID to clear cache for.
	 * @param string|null $type Optional. Relationship type to clear cache for.
	 * @return void
	 *
	 * @example Clear cache for specific post
	 * ```php
	 * NATICORE_API::clear_cache( 123 );
	 * ```
	 *
	 * @example Clear cache for all relationships
	 * ```php
	 * NATICORE_API::clear_cache();
	 * ```
	 */
	public static function clear_cache( $from_id = null, $to_id = null, $type = null ) {
		if ( null !== $from_id ) {
			// Clear specific post cache
			$from_id = absint( $from_id );
			wp_cache_delete( "naticore_get_related_{$from_id}_all_all_0_default", 'naticore_relationships' );
			wp_cache_delete( "naticore_exists_{$from_id}_all_all", 'naticore_relationships' );
		}

		if ( null !== $from_id && null !== $to_id && null !== $type ) {
			// Clear specific relationship cache
			$from_id = absint( $from_id );
			$to_id   = absint( $to_id );
			$type    = sanitize_key( (string) $type );
			wp_cache_delete( "naticore_exists_{$from_id}_{$to_id}_{$type}_post", 'naticore_relationships' );
		}

		// Clear admin cache
		wp_cache_delete( 'naticore_admin_total_count', 'naticore_relationships' );

		// Clear all admin items cache (pattern-based)
		wp_cache_delete_group( 'naticore_relationships' );
	}

	/**
	 * Build WHERE clause for prepared statements
	 *
	 * @param array $where Array of where conditions
	 * @return string WHERE clause string
	 */
	private static function build_where_clause( $where ) {
		$clauses = array();
		foreach ( array_keys( $where ) as $key ) {
			$clauses[] = "`{$key}` = %s";
		}
		return implode( ' AND ', $clauses );
	}

	/**
	 * Get related content with advanced filtering and pagination support
	 *
	 * Retrieves related content items for a given source with support for filtering by type,
	 * target type, pagination, ordering, and search. Supports posts, users, and terms.
	 *
	 * @since 1.0.0
	 * @since 1.0.10 Added support for user relationships
	 * @since 1.0.11 Added support for term relationships and advanced filtering
	 *
	 * @param int    $post_id The ID of the source content (post, user, or term)
	 * @param string $type    Type of relationship to filter by (optional, null for all types)
	 * @param array  $args    {
	 *      Optional. Array of query arguments.
	 *
	 *     @type int    $limit    Maximum number of results to return (default: no limit)
	 *     @type int    $offset   Number of results to skip (for pagination)
	 *     @type string $orderby  Field to order by: 'created_at', 'title', 'type' (default: 'created_at')
	 *     @type string $order    Sort order: 'ASC' or 'DESC' (default: 'DESC')
	 *     @type string $search   Search term to filter results by
	 *     @type bool   $count_only Return only count instead of results (default: false)
	 * }
	 * @param string $to_type Target type filter: 'post', 'user', 'term', or 'all' (default: 'post')
	 *
	 * @return array Array of related items with enhanced data, or empty array if no results.
	 *     Each item contains:
	 *     - @type int    $id          The target content ID
	 *     - @type string $type        Relationship type
	 *     - @type string $to_type     Target content type ('post', 'user', 'term')
	 *     - @type string $created_at  Creation timestamp (ISO 8601 format)
	 *     - @type string $title       Post title (for posts)
	 *     - @type string $post_type   Post type (for posts)
	 *     - @type string $permalink   Post URL (for posts)
	 *     - @type string $display_name User display name (for users)
	 *     - @type string $user_email   User email (for users)
	 *     - @type string $term_name    Term name (for terms)
	 *     - @type string $taxonomy     Term taxonomy (for terms)
	 *
	 * @example Get all related posts
	 * ```php
	 * $related = NATICORE_API::get_related( 123 );
	 * foreach ( $related as $item ) {
	 *     echo $item['title'] . '<br>';
	 * }
	 * ```
	 *
	 * @example Get related users with pagination
	 * ```php
	 * $args = array(
	 *     'limit' => 10,
	 *     'offset' => 0,
	 *     'orderby' => 'display_name'
	 * );
	 * $users = NATICORE_API::get_related( 123, 'favorite_posts', $args, 'user' );
	 * ```
	 *
	 * @example Get related terms with search
	 * ```php
	 * $args = array(
	 *     'search' => 'featured',
	 *     'limit' => 5
	 * );
	 * $terms = NATICORE_API::get_related( 123, 'categorized_as', $args, 'term' );
	 * ```
	 *
	 * @example Get count only
	 * ```php
	 * $args = array( 'count_only' => true );
	 * $count = NATICORE_API::get_related( 123, 'related_to', $args );
	 * ```
	 *
	 * @see wp_get_related() Global wrapper function
	 * @see wp_get_related_users() Get user relationships helper
	 * @see wp_get_related_terms() Get term relationships helper
	 * @see NATICORE_API::is_related() Check if specific relationship exists
	 *
	 * @filter naticore_content_relations_allowed Allow/disallow relationship queries
	 * @filter naticore_get_related_args Filter query arguments
	 */
	public static function get_related( $post_id, $type = null, $args = array(), $to_type = 'post' ) {
		// Apply filters for extensibility
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		$allowed = apply_filters( 'naticore_content_relations_allowed', true, $post_id, $type );
		if ( ! $allowed ) {
			return array();
		}

		// Allow filtering of query arguments
		$args = apply_filters( 'naticore_get_related_args', $args, $post_id, $type );

		global $wpdb;

		$post_id = absint( $post_id );
		$to_type = in_array( $to_type, array( 'post', 'user', 'term', 'all' ), true ) ? $to_type : 'post';

		// Use conditional queries for PHPCS compliance - ORDER BY and LIMIT use %d
		$has_type  = ! empty( $type );
		$has_limit = isset( $args['limit'] );
		$limit     = $has_limit ? absint( $args['limit'] ) : 0;

		// Create cache key
		$cache_key = sprintf(
			'naticore_get_related_%d_%s_%s_%d_%s',
			$post_id,
			(string) $type,
			$to_type,
			$limit,
			isset( $args['orderby'] ) ? sanitize_key( (string) $args['orderby'] ) : 'default'
		);

		// Check cache first
		$cached_result = wp_cache_get( $cache_key, 'naticore_relationships' );
		if ( false !== $cached_result ) {
			return $cached_result;
		}

		// Build SQL using array-based WHERE clause (scanner-friendly)
		$where  = array( 'from_id = %d' );
		$params = array( $post_id );

		if ( $has_to_type_filter ) {
			$where[]  = 'to_type = %s';
			$params[] = $to_type;
		}

		if ( $has_type ) {
			$where[]  = 'type = %s';
			$params[] = $type;
		}

		$where_clause = implode( ' AND ', $where );

		if ( $has_limit ) {
			/**
			 * Custom table query with manual caching.
			 * WordPress.DB.DirectDatabaseQuery.DirectQuery is acceptable here
			 * because WordPress core APIs do not support custom relationship tables.
			 */
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type, to_type FROM {$wpdb->prefix}content_relations WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d",
					$params
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- Custom table with prepared statement
		} else {
			/**
			 * Custom table query with manual caching.
			 * WordPress.DB.DirectDatabaseQuery.DirectQuery is acceptable here
			 * because WordPress core APIs do not support custom relationship tables.
			 */
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type, to_type FROM {$wpdb->prefix}content_relations WHERE {$where_clause} ORDER BY created_at DESC",
					$params
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared -- Custom table with prepared statement
		}

		if ( ! $results ) {
			$related_items = array();
		} else {
			$related_items = array();
			foreach ( $results as $row ) {
				$item = array(
					'id'      => absint( $row->to_id ),
					'type'    => $row->type,
					'to_type' => $row->to_type,
				);

				// Add additional data based on target type
				if ( 'user' === $row->to_type ) {
					$user = get_userdata( $row->to_id );
					if ( $user ) {
						$item['display_name'] = $user->display_name;
						$item['user_email']   = $user->user_email;
					}
				} elseif ( 'term' === $row->to_type ) {
					$term = get_term( $row->to_id );
					if ( $term && ! is_wp_error( $term ) ) {
						$item['term_name']     = $term->name;
						$item['term_taxonomy'] = $term->taxonomy;
						$item['term_slug']     = $term->slug;
					}
				} else {
					$post = get_post( $row->to_id );
					if ( $post ) {
						$item['post_title'] = $post->post_title;
						$item['post_type']  = $post->post_type;
					}
				}

				$related_items[] = $item;
			}
		}

		// Cache the result for 1 hour
		wp_cache_set( $cache_key, $related_items, 'naticore_relationships', HOUR_IN_SECONDS );

		return $related_items;
	}

	/**
	 * Get all relationships for a content item
	 *
	 * @param int $post_id The ID of the content
	 * @return array Array of relationships
	 */
	public static function get_all_relations( $post_id ) {
		global $wpdb;

		$post_id = absint( $post_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT to_id, type, direction, created_at FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d ORDER BY type, created_at DESC",
				$post_id
			)
		);

		return $results;
	}

	/**
	 * Check if two items are related with caching support
	 *
	 * Determines whether a specific relationship exists between two content items.
	 * Uses WordPress object caching for improved performance on repeated checks.
	 * Results are cached for 1 hour by default.
	 *
	 * @since 1.0.0
	 * @since 1.0.10 Added support for user relationships
	 * @since 1.0.11 Added support for term relationships and caching
	 *
	 * @param int    $from_id  The ID of the source content (post, user, or term)
	 * @param int    $to_id    The ID of the target content (post, user, or term)
	 * @param string $type     Type of relationship to check (optional, null for any type)
	 * @param string $direction Direction of relationship (optional, for future use)
	 * @param string $to_type  Target type: 'post', 'user', 'term' (default: 'post')
	 *
	 * @return bool True if relationship exists, false otherwise
	 *
	 * @example Check if posts are related
	 * ```php
	 * if ( NATICORE_API::is_related( 123, 456, 'related_to' ) ) {
	 *     echo 'Posts are related!';
	 * }
	 * ```
	 *
	 * @example Check if user favorited a post
	 * ```php
	 * if ( NATICORE_API::is_related( 15, 123, 'favorite_posts', null, 'post' ) ) {
	 *     echo 'User favorited this post';
	 * }
	 * ```
	 *
	 * @example Check if post is in category
	 * ```php
	 * if ( NATICORE_API::is_related( 123, 25, 'categorized_as', null, 'term' ) ) {
	 *     echo 'Post is in this category';
	 * }
	 * ```
	 *
	 * @example Check any relationship type
	 * ```php
	 * if ( NATICORE_API::is_related( 123, 456 ) ) {
	 *     echo 'Some relationship exists';
	 * }
	 * ```
	 *
	 * @see wp_is_related() Global wrapper function
	 * @see NATICORE_API::get_related() Get all related items
	 * @see NATICORE_API::clear_cache() Clear relationship cache
	 *
	 * @filter naticore_relation_is_allowed Allow/disallow relationship checks
	 */
	public static function is_related( $from_id, $to_id, $type = null, $direction = null, $to_type = 'post' ) {
		global $wpdb;

		$from_id = absint( $from_id );
		$to_id   = absint( $to_id );
		$to_type = in_array( $to_type, array( 'post', 'user', 'term' ), true ) ? $to_type : 'post';

		// Create cache key
		$cache_key = "naticore_exists_{$from_id}_{$to_id}_{$type}_{$to_type}";

		// Check cache first
		$cached_result = wp_cache_get( $cache_key, 'naticore_relationships' );
		if ( false !== $cached_result ) {
			return (bool) $cached_result;
		}

		if ( $type ) {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND to_type = %s AND type = %s",
					$from_id,
					$to_id,
					$to_type,
					$type
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with manual caching
		} else {
			$result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_id = %d AND to_type = %s",
					$from_id,
					$to_id,
					$to_type
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table with manual caching
		}

		$exists = (int) $result > 0;

		// Cache the result for 1 hour
		wp_cache_set( $cache_key, $exists, 'naticore_relationships', HOUR_IN_SECONDS );

		return $exists;
	}
}

// Make functions available globally (backward compatibility)
if ( ! function_exists( 'wp_add_relation' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_add_relation( $from_id, $to_id, $type = 'related_to', $direction = null, $to_type = 'post' ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::add_relation( $from_id, $to_id, $type, $direction, $to_type );
	}
}

if ( ! function_exists( 'wp_remove_relation' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_remove_relation( $from_id, $to_id, $type = null, $to_type = 'post' ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::remove_relation( $from_id, $to_id, $type, $to_type );
	}
}

if ( ! function_exists( 'wp_get_related' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_related( $post_id, $type = null, $args = array(), $to_type = 'post' ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::get_related( $post_id, $type, $args, $to_type );
	}
}

if ( ! function_exists( 'wp_is_related' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_is_related( $from_id, $to_id, $type = null ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::is_related( $from_id, $to_id, $type );
	}
}

// User relationship helper functions
if ( ! function_exists( 'wp_get_related_users' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_related_users( $post_id, $type = null, $args = array() ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::get_related( $post_id, $type, $args, 'user' );
	}
}

if ( ! function_exists( 'wp_get_user_related_posts' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_user_related_posts( $user_id, $type = null, $args = array() ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		// For user-to-post relationships, we need to query where from_id is the user
		// This is a simplified version - in a full implementation, you'd add a dedicated method
		global $wpdb;
		$user_id   = absint( $user_id );
		$has_type  = ! empty( $type );
		$has_limit = isset( $args['limit'] );
		$limit     = $has_limit ? absint( $args['limit'] ) : 0;

		if ( $has_type && $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND type = %s AND to_type = %s ORDER BY created_at DESC LIMIT %d",
					$user_id,
					$type,
					'post',
					$limit
				)
			);
		} elseif ( $has_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND type = %s AND to_type = %s ORDER BY created_at DESC",
					$user_id,
					$type,
					'post'
				)
			);
		} elseif ( $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_type = %s ORDER BY created_at DESC LIMIT %d",
					$user_id,
					'post',
					$limit
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_type = %s ORDER BY created_at DESC",
					$user_id,
					'post'
				)
			);
		}

		if ( ! $results ) {
			return array();
		}

		$related_posts = array();
		foreach ( $results as $row ) {
			$post = get_post( $row->to_id );
			if ( $post ) {
				$related_posts[] = array(
					'id'         => absint( $row->to_id ),
					'type'       => $row->type,
					'post_title' => $post->post_title,
					'post_type'  => $post->post_type,
				);
			}
		}

		return $related_posts;
	}
}

// Term relationship helper functions
if ( ! function_exists( 'wp_get_related_terms' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_related_terms( $post_id, $type = null, $args = array() ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		return NATICORE_API::get_related( $post_id, $type, $args, 'term' );
	}
}

if ( ! function_exists( 'wp_get_term_related_posts' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_term_related_posts( $term_id, $type = null, $args = array() ) {
		if ( ! class_exists( 'NATICORE_API' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_API class is not loaded yet.' );
		}
		// For term-to-post relationships, we need to query where from_id is the term
		global $wpdb;
		$term_id   = absint( $term_id );
		$has_type  = ! empty( $type );
		$has_limit = isset( $args['limit'] );
		$limit     = $has_limit ? absint( $args['limit'] ) : 0;

		if ( $has_type && $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND type = %s AND to_type = %s ORDER BY created_at DESC LIMIT %d",
					$term_id,
					$type,
					'post',
					$limit
				)
			);
		} elseif ( $has_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND type = %s AND to_type = %s ORDER BY created_at DESC",
					$term_id,
					$type,
					'post'
				)
			);
		} elseif ( $has_limit ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_type = %s ORDER BY created_at DESC LIMIT %d",
					$term_id,
					'post',
					$limit
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT to_id, type FROM `{$wpdb->prefix}content_relations` WHERE from_id = %d AND to_type = %s ORDER BY created_at DESC",
					$term_id,
					'post'
				)
			);
		}

		if ( ! $results ) {
			return array();
		}

		$related_posts = array();
		foreach ( $results as $row ) {
			$post = get_post( $row->to_id );
			if ( $post ) {
				$related_posts[] = array(
					'id'         => absint( $row->to_id ),
					'type'       => $row->type,
					'post_title' => $post->post_title,
					'post_type'  => $post->post_type,
				);
			}
		}

		return $related_posts;
	}
}

<?php
/**
 * Extend WP_Query to support relationship queries
 *
 * WP_Query arguments (plugin-specific; registered via query_vars so WP does not strip them):
 *
 * - content_relation (array, recommended): {
 *     post_id or from_id: int|int[],
 *     type: string (relation type slug),
 *     direction: 'incoming'|'outgoing'
 *   }
 * - wpcr (array, legacy): { from|post_id, type, direction }
 * - related_to (int|int[], legacy): source post ID(s) for outgoing relations
 * - relation_type (string, legacy): type when used with related_to
 *
 * If WordPress core ever adds a native relationship API, use the filter
 * `ncr_skip_relationship_query` to return true and this plugin will not add
 * its JOIN/WHERE, allowing clean migration to core.
 *
 * @see apply_filters( 'ncr_skip_relationship_query', $skip, $query )
 * @see apply_filters( 'ncr_wp_query_relation_args', $args, $query )
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Query {

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Query debug data
	 */
	private $debug_data = array();

	/**
	 * Constructor
	 */
	private function __construct() {
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		add_filter( 'posts_distinct', array( $this, 'posts_distinct' ), 10, 2 );

		// Debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			add_filter( 'posts_request', array( $this, 'log_query_debug' ), 10, 2 );
			add_action( 'wp_footer', array( $this, 'output_debug_info' ) );
			add_action( 'admin_footer', array( $this, 'output_debug_info' ) );
		}
	}

	/**
	 * Register custom query vars so WordPress does not strip them.
	 *
	 * Uses plugin-prefixed / specific names to avoid conflicting with future core query vars.
	 *
	 * @param array $vars Existing query vars.
	 * @return array
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'content_relation';
		$vars[] = 'wpcr';
		$vars[] = 'related_to';
		$vars[] = 'relation_type';
		return $vars;
	}

	/**
	 * Add JOIN clause for relationship queries
	 */
	public function posts_join( $join, $query ) {
		global $wpdb;

		// Allow another implementation (e.g. future core API) to handle relationship queries.
		if ( apply_filters( 'ncr_skip_relationship_query', false, $query ) ) {
			return $join;
		}

		// Support all query formats
		$has_relation = isset( $query->query_vars['related_to'] )
			|| isset( $query->query_vars['content_relation'] )
			|| isset( $query->query_vars['wpcr'] )
			|| isset( $query->query_vars['relation_type'] );

		if ( ! $has_relation ) {
			return $join;
		}

		$join .= " INNER JOIN `{$wpdb->prefix}content_relations` AS naticore_rel ON `{$wpdb->posts}`.ID = naticore_rel.to_id";

		return $join;
	}

	/**
	 * Add WHERE clause for relationship queries
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;

		// Allow another implementation (e.g. future core API) to handle relationship queries.
		if ( apply_filters( 'ncr_skip_relationship_query', false, $query ) ) {
			return $where;
		}

		// Support new cleaner syntax: 'wpcr' => array(...)
		if ( isset( $query->query_vars['wpcr'] ) && is_array( $query->query_vars['wpcr'] ) ) {
			$wpcr    = $query->query_vars['wpcr'];
			$post_id = isset( $wpcr['from'] ) ? $wpcr['from'] : ( isset( $wpcr['post_id'] ) ? $wpcr['post_id'] : null );

			if ( $post_id ) {
				// Support single ID or array of IDs
				if ( is_array( $post_id ) ) {
					$ids          = array_map( 'absint', $post_id );
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					// Build WHERE clause part with placeholders
					$where_part = " AND naticore_rel.from_id IN ($placeholders)";
					$where     .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
				} else {
					$post_id = absint( $post_id );
					$where  .= $wpdb->prepare( ' AND naticore_rel.from_id = %d', $post_id );
				}

				// Type filter
				if ( isset( $wpcr['type'] ) ) {
					$type   = sanitize_text_field( $wpcr['type'] );
					$where .= $wpdb->prepare( ' AND naticore_rel.type = %s', $type );
				}

				// Direction filter
				if ( isset( $wpcr['direction'] ) && 'incoming' === $wpcr['direction'] ) {
					// For incoming, swap the join
					// $wpdb->posts is safe - it's a WordPress core table name
					$where = str_replace( "`{$wpdb->posts}`.ID = naticore_rel.to_id", "`{$wpdb->posts}`.ID = naticore_rel.from_id", $where );
					$where = str_replace( 'naticore_rel.from_id', 'naticore_rel.to_id', $where );
				}
			}
		}
		// New format: content_relation array
		elseif ( isset( $query->query_vars['content_relation'] ) && is_array( $query->query_vars['content_relation'] ) ) {
			$relation = $query->query_vars['content_relation'];
			$post_id  = isset( $relation['post_id'] ) ? $relation['post_id'] : ( isset( $relation['from_id'] ) ? $relation['from_id'] : null );

			if ( $post_id ) {
				// Support single ID or array of IDs
				if ( is_array( $post_id ) ) {
					$ids          = array_map( 'absint', $post_id );
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					// Build WHERE clause part with placeholders
					$where_part = " AND naticore_rel.from_id IN ($placeholders)";
					$where     .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
				} else {
					$post_id = absint( $post_id );
					$where  .= $wpdb->prepare( ' AND naticore_rel.from_id = %d', $post_id );
				}

				// Type filter
				if ( isset( $relation['type'] ) ) {
					$type   = sanitize_text_field( $relation['type'] );
					$where .= $wpdb->prepare( ' AND naticore_rel.type = %s', $type );
				}

				// Direction filter
				if ( isset( $relation['direction'] ) && 'incoming' === $relation['direction'] ) {
					// For incoming, swap the join
					$where = str_replace( "`{$wpdb->posts}`.ID = naticore_rel.to_id", "`{$wpdb->posts}`.ID = naticore_rel.from_id", $where );
					$where = str_replace( 'naticore_rel.from_id', 'naticore_rel.to_id', $where );
				}
			}
		}
		// Old format: related_to (backward compatibility)
		elseif ( isset( $query->query_vars['related_to'] ) ) {
			$related_to = $query->query_vars['related_to'];

			// Support single ID or array of IDs
			if ( is_array( $related_to ) ) {
				$ids          = array_map( 'absint', $related_to );
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// Build WHERE clause part with placeholders
				$where_part = " AND naticore_rel.from_id IN ($placeholders)";
				$where     .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
			} else {
				$related_to = absint( $related_to );
				$where     .= $wpdb->prepare( ' AND naticore_rel.from_id = %d', $related_to );
			}

			if ( isset( $query->query_vars['relation_type'] ) ) {
				$relation_type = sanitize_text_field( $query->query_vars['relation_type'] );
				$where        .= $wpdb->prepare( ' AND naticore_rel.type = %s', $relation_type );
			}
		}

		return $where;
	}

	/**
	 * Add DISTINCT to prevent duplicates
	 */
	public function posts_distinct( $distinct, $query ) {
		if ( apply_filters( 'ncr_skip_relationship_query', false, $query ) ) {
			return $distinct;
		}
		if ( isset( $query->query_vars['related_to'] )
			|| isset( $query->query_vars['content_relation'] )
			|| isset( $query->query_vars['wpcr'] ) ) {
			return 'DISTINCT';
		}
		return $distinct;
	}

	/**
	 * Log query debug information
	 */
	public function log_query_debug( $request, $query ) {
		$settings = NATICORE_Settings::get_instance();
		if ( ! $settings->get_setting( 'query_debug', 0 ) ) {
			return $request;
		}

		// Check if this is a relationship query
		$has_relation = isset( $query->query_vars['related_to'] )
			|| isset( $query->query_vars['content_relation'] )
			|| isset( $query->query_vars['wpcr'] )
			|| isset( $query->query_vars['relation_type'] );

		if ( ! $has_relation ) {
			return $request;
		}

		// Extract relationship info
		$relation_type = null;
		if ( isset( $query->query_vars['wpcr'] ) && is_array( $query->query_vars['wpcr'] ) ) {
			$relation_type = isset( $query->query_vars['wpcr']['type'] ) ? $query->query_vars['wpcr']['type'] : null;
		} elseif ( isset( $query->query_vars['content_relation'] ) && is_array( $query->query_vars['content_relation'] ) ) {
			$relation_type = isset( $query->query_vars['content_relation']['type'] ) ? $query->query_vars['content_relation']['type'] : null;
		} elseif ( isset( $query->query_vars['relation_type'] ) ) {
			$relation_type = $query->query_vars['relation_type'];
		}

		// Determine index used
		$index_used = 'from_id';
		if ( isset( $query->query_vars['wpcr']['direction'] ) && 'incoming' === $query->query_vars['wpcr']['direction'] ) {
			$index_used = 'to_id';
		} elseif ( isset( $query->query_vars['content_relation']['direction'] ) && 'incoming' === $query->query_vars['content_relation']['direction'] ) {
			$index_used = 'to_id';
		}
		if ( $relation_type ) {
			$index_used .= '_type';
		}

		// Store debug info
		$debug_info = array(
			'type'      => $relation_type ?: 'all',
			'sql'       => $request,
			'index'     => $index_used,
			'timestamp' => microtime( true ),
		);

		$this->debug_data[] = $debug_info;

		// Fire action for query execution
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Backward compatibility
		do_action( 'wpcr_query_executed', $debug_info, $query );

		return $request;
	}

	/**
	 * Output debug information
	 */
	public function output_debug_info() {
		$settings = NATICORE_Settings::get_instance();
		if ( ! $settings->get_setting( 'query_debug', 0 ) || empty( $this->debug_data ) ) {
			return;
		}

		// Build debug script content
		$script_lines   = array();
		$script_lines[] = "console.group('NCR Query Debug');";

		$start_time = ! empty( $this->debug_data ) ? $this->debug_data[0]['timestamp'] : 0;
		foreach ( $this->debug_data as $index => $debug ) {
			$time           = $index > 0 ? ( $debug['timestamp'] - $start_time ) * 1000 : 0;
			$script_lines[] = "console.log('Type: " . esc_js( $debug['type'] ) . "');";
			$script_lines[] = "console.log('Index: " . esc_js( $debug['index'] ) . "');";
			$script_lines[] = "console.log('Time: " . esc_js( number_format( $time, 2 ) ) . "ms');";
			$script_lines[] = "console.log('SQL: " . esc_js( substr( $debug['sql'], 0, 200 ) ) . "...');";
		}

		$script_lines[] = 'console.groupEnd();';

		$script = implode( "\n", $script_lines );
		wp_add_inline_script( 'naticore-admin', $script );

		// Also log to error log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			foreach ( $this->debug_data as $debug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
				error_log(
					sprintf(
						'NCR Query Debug - Type: %s, Index: %s, SQL: %s',
						$debug['type'],
						$debug['index'],
						substr( $debug['sql'], 0, 200 )
					)
				);
			}
		}
	}
}

<?php
/**
 * Extend WP_Query to support relationship queries
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_Query {
	
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
	 * Query debug data
	 */
	private $debug_data = array();
	
	/**
	 * Constructor
	 */
	private function __construct() {
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
	 * Add JOIN clause for relationship queries
	 */
	public function posts_join( $join, $query ) {
		global $wpdb;
		
		// Support all query formats
		$has_relation = isset( $query->query_vars['related_to'] ) 
			|| isset( $query->query_vars['content_relation'] )
			|| isset( $query->query_vars['wpcr'] )
			|| isset( $query->query_vars['relation_type'] );
		
		if ( ! $has_relation ) {
			return $join;
		}
		
		$join .= " INNER JOIN `{$wpdb->prefix}content_relations` AS wpncr_rel ON `{$wpdb->posts}`.ID = wpncr_rel.to_id";
		
		return $join;
	}
	
	/**
	 * Add WHERE clause for relationship queries
	 */
	public function posts_where( $where, $query ) {
		global $wpdb;
		
		// Support new cleaner syntax: 'wpcr' => array(...)
		if ( isset( $query->query_vars['wpcr'] ) && is_array( $query->query_vars['wpcr'] ) ) {
			$wpcr = $query->query_vars['wpcr'];
			$post_id = isset( $wpcr['from'] ) ? $wpcr['from'] : ( isset( $wpcr['post_id'] ) ? $wpcr['post_id'] : null );
			
			if ( $post_id ) {
				// Support single ID or array of IDs
				if ( is_array( $post_id ) ) {
					$ids = array_map( 'absint', $post_id );
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					// Build WHERE clause part with placeholders
					$where_part = " AND wpncr_rel.from_id IN ($placeholders)";
					$where .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
				} else {
					$post_id = absint( $post_id );
					$where .= $wpdb->prepare( " AND wpncr_rel.from_id = %d", $post_id );
				}
				
				// Type filter
				if ( isset( $wpcr['type'] ) ) {
					$type = sanitize_text_field( $wpcr['type'] );
					$where .= $wpdb->prepare( " AND wpncr_rel.type = %s", $type );
				}
				
				// Direction filter
				if ( isset( $wpcr['direction'] ) && $wpcr['direction'] === 'incoming' ) {
					// For incoming, swap the join
					// $wpdb->posts is safe - it's a WordPress core table name
					$where = str_replace( "`{$wpdb->posts}`.ID = wpncr_rel.to_id", "`{$wpdb->posts}`.ID = wpncr_rel.from_id", $where );
					$where = str_replace( "wpncr_rel.from_id", "wpncr_rel.to_id", $where );
				}
			}
		}
		// New format: content_relation array
		elseif ( isset( $query->query_vars['content_relation'] ) && is_array( $query->query_vars['content_relation'] ) ) {
			$relation = $query->query_vars['content_relation'];
			$post_id = isset( $relation['post_id'] ) ? $relation['post_id'] : ( isset( $relation['from_id'] ) ? $relation['from_id'] : null );
			
			if ( $post_id ) {
				// Support single ID or array of IDs
				if ( is_array( $post_id ) ) {
					$ids = array_map( 'absint', $post_id );
					$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
					// Build WHERE clause part with placeholders
					$where_part = " AND wpncr_rel.from_id IN ($placeholders)";
					$where .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
				} else {
					$post_id = absint( $post_id );
					$where .= $wpdb->prepare( " AND wpncr_rel.from_id = %d", $post_id );
				}
				
				// Type filter
				if ( isset( $relation['type'] ) ) {
					$type = sanitize_text_field( $relation['type'] );
					$where .= $wpdb->prepare( " AND wpncr_rel.type = %s", $type );
				}
				
				// Direction filter
				if ( isset( $relation['direction'] ) && $relation['direction'] === 'incoming' ) {
					// For incoming, swap the join
					$where = str_replace( "`{$wpdb->posts}`.ID = wpncr_rel.to_id", "`{$wpdb->posts}`.ID = wpncr_rel.from_id", $where );
					$where = str_replace( "wpncr_rel.from_id", "wpncr_rel.to_id", $where );
				}
			}
		}
		// Old format: related_to (backward compatibility)
		elseif ( isset( $query->query_vars['related_to'] ) ) {
			$related_to = $query->query_vars['related_to'];
			
			// Support single ID or array of IDs
			if ( is_array( $related_to ) ) {
				$ids = array_map( 'absint', $related_to );
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				// Build WHERE clause part with placeholders
				$where_part = " AND wpncr_rel.from_id IN ($placeholders)";
				$where .= call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $where_part ), $ids ) );
			} else {
				$related_to = absint( $related_to );
				$where .= $wpdb->prepare( " AND wpncr_rel.from_id = %d", $related_to );
			}
			
			if ( isset( $query->query_vars['relation_type'] ) ) {
				$relation_type = sanitize_text_field( $query->query_vars['relation_type'] );
				$where .= $wpdb->prepare( " AND wpncr_rel.type = %s", $relation_type );
			}
		}
		
		return $where;
	}
	
	/**
	 * Add DISTINCT to prevent duplicates
	 */
	public function posts_distinct( $distinct, $query ) {
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
		$settings = WPNCR_Settings::get_instance();
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
		if ( isset( $query->query_vars['wpcr']['direction'] ) && $query->query_vars['wpcr']['direction'] === 'incoming' ) {
			$index_used = 'to_id';
		} elseif ( isset( $query->query_vars['content_relation']['direction'] ) && $query->query_vars['content_relation']['direction'] === 'incoming' ) {
			$index_used = 'to_id';
		}
		if ( $relation_type ) {
			$index_used .= '_type';
		}
		
		// Store debug info
		$debug_info = array(
			'type' => $relation_type ?: 'all',
			'sql' => $request,
			'index' => $index_used,
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
		$settings = WPNCR_Settings::get_instance();
		if ( ! $settings->get_setting( 'query_debug', 0 ) || empty( $this->debug_data ) ) {
			return;
		}
		
		?>
		<!-- WPNCR Query Debug -->
		<script>
		console.group('WPCR Query Debug');
		<?php 
		$start_time = ! empty( $this->debug_data ) ? $this->debug_data[0]['timestamp'] : 0;
		foreach ( $this->debug_data as $index => $debug ) : 
			$time = $index > 0 ? ( $debug['timestamp'] - $start_time ) * 1000 : 0;
		?>
		console.log('Type: <?php echo esc_js( $debug['type'] ); ?>');
		console.log('Index: <?php echo esc_js( $debug['index'] ); ?>');
		console.log('Time: <?php echo esc_js( number_format( $time, 2 ) ); ?>ms');
		console.log('SQL: <?php echo esc_js( substr( $debug['sql'], 0, 200 ) ); ?>...');
		<?php endforeach; ?>
		console.groupEnd();
		</script>
		<?php
		
		// Also log to error log
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			foreach ( $this->debug_data as $debug ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
				error_log( sprintf( 
				'WPNCR Query Debug - Type: %s, Index: %s, SQL: %s',
				$debug['type'],
				$debug['index'],
				substr( $debug['sql'], 0, 200 )
			) );
			}
		}
	}
}

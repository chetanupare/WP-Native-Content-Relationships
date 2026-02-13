<?php
/**
 * Integrity Checker
 * Silent cleanup of invalid relationships
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrity Checker
 *
 * Provides functionality to check and clean up invalid relationships
 * that may occur due to deleted content, orphaned relationships, or
 * data corruption. Runs silently in the background to maintain data integrity.
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */
class NATICORE_Integrity {

	/**
	 * Instance
	 *
	 * @var NATICORE_Integrity|null
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
	 * Load helpers
	 */
	public function load_helpers() {
		$helper_path = plugin_dir_path( __FILE__ ) . 'helpers/integrity-helpers.php';
		if ( file_exists( $helper_path ) ) {
			require_once $helper_path;
		}
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_helpers();

		// Only load admin functionality in admin context
		if ( ! is_admin() ) {
			return;
		}

		// Run integrity check on admin init (once per day)
		add_action( 'admin_init', array( $this, 'maybe_run_integrity_check' ) );
	}

	/**
	 * Maybe run integrity check
	 */
	public function maybe_run_integrity_check() {
		// Only run once per day
		$last_check     = get_option( 'naticore_last_integrity_check', 0 );
		$day_in_seconds = DAY_IN_SECONDS;

		if ( time() - $last_check < $day_in_seconds ) {
			return;
		}

		$results = $this->run_integrity_check();

		if ( $results['cleaned'] > 0 ) {
			// Store notice to show
			set_transient( 'naticore_integrity_notice', $results, HOUR_IN_SECONDS );
		}

		update_option( 'naticore_last_integrity_check', time() );
	}

	/**
	 * Run integrity check with performance optimizations.
	 *
	 * @param bool     $fix        Whether to actually delete invalid records.
	 * @param int      $batch_size Number of records to process per chunk.
	 * @param callable $callback   Optional callback for real-time issue reporting.
	 * @return array Results with issue counts and fixing status.
	 */
	public function run_integrity_check( $fix = true, $batch_size = 1000, $callback = null ) {
		global $wpdb;
		$table = "{$wpdb->prefix}content_relations";

		$stats = array(
			'cleaned'      => 0,
			'duplicates'   => 0,
			'orphaned'     => 0,
			'unregistered' => 0,
			'constraints'  => 0,
			'direction'    => 0,
			'invalid'      => 0,
		);

		// 1. SQL-Native Duplicate Detection (Avoids massive $seen array in PHP)
		// Identifying groups with count > 1
		$duplicate_sets = $wpdb->get_results( "SELECT from_id, to_id, type, COUNT(*) as cnt FROM `$table` GROUP BY from_id, to_id, type HAVING cnt > 1" );
		
		foreach ( $duplicate_sets as $set ) {
			// Select all but the oldest ID for this set
			$ids_to_remove = $wpdb->get_col( $wpdb->prepare(
				"SELECT id FROM `$table` WHERE from_id = %d AND to_id = %d AND type = %s ORDER BY id ASC LIMIT %d, 999999",
				$set->from_id,
				$set->to_id,
				$set->type,
				1 // Skip the first one
			) );

			if ( ! empty( $ids_to_remove ) ) {
				$stats['duplicates'] += count( $ids_to_remove );
				if ( $callback ) {
					call_user_func( $callback, 'duplicates', $ids_to_remove );
				}
				if ( $fix ) {
					$ids_string = implode( ',', array_map( 'absint', $ids_to_remove ) );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->query( "DELETE FROM `$table` WHERE id IN ($ids_string)" );
				}
			}
		}

		// 2. Chunked Iterative Checks (Orphans, Constraints, Direction)
		$last_id = 0;
		$connection_counts = array(); // Semi-stateless: we only track constraints per batch/session

		while ( true ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$relations = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM `$table` WHERE id > %d ORDER BY id ASC LIMIT %d",
				$last_id,
				$batch_size
			) );

			if ( empty( $relations ) ) {
				break;
			}

			$batch_to_delete = array();
			$batch_issues    = array(
				'orphaned'     => array(),
				'unregistered' => array(),
				'constraints'  => array(),
				'direction'    => array(),
				'invalid'      => array(),
			);

			foreach ( $relations as $rel ) {
				$last_id = $rel->id;

				// A. Check for unregistered types
				if ( function_exists( 'ncr_has_unregistered_type' ) && ncr_has_unregistered_type( $rel ) ) {
					$batch_to_delete[] = $rel->id;
					$batch_issues['unregistered'][] = $rel->id;
					continue;
				}

				// B. Check for orphaned relationships
				if ( function_exists( 'ncr_is_orphaned_relation' ) && ncr_is_orphaned_relation( $rel ) ) {
					$batch_to_delete[] = $rel->id;
					$batch_issues['orphaned'][] = $rel->id;
					continue;
				}

				// C. Check for directional inconsistencies
				if ( function_exists( 'ncr_has_directional_inconsistency' ) && ncr_has_directional_inconsistency( $rel ) ) {
					$batch_to_delete[] = $rel->id;
					$batch_issues['direction'][] = $rel->id;
					continue;
				}

				// D. Check for max_connections violations (Historical)
				$type_info = NATICORE_Relation_Types::get_type( $rel->type );
				if ( $type_info && $type_info['max_connections'] > 0 ) {
					$constraint_key = $rel->from_id . '|' . $rel->type . '|' . $rel->to_type;
					if ( ! isset( $connection_counts[ $constraint_key ] ) ) {
						// Only count valid relations that aren't already flagged for deletion
						// We initialize count by querying preceding IDs if this is the start of a deep scan
						// But for simplicity in chunked runs, we just accumulate.
						$connection_counts[ $constraint_key ] = 0;
					}
					$connection_counts[ $constraint_key ]++;

					if ( $connection_counts[ $constraint_key ] > $type_info['max_connections'] ) {
						$batch_to_delete[] = $rel->id;
						$batch_issues['constraints'][] = $rel->id;
						continue;
					}
				}

				// E. Legacy post type restriction check
				if ( 'post' === $rel->to_type ) {
					$from_type = $type_info ? $type_info['from_type'] : 'post';
					if ( 'post' === $from_type ) {
						$from_post = get_post( $rel->from_id );
						$to_post   = get_post( $rel->to_id );
						if ( $from_post && $to_post && $type_info && ! empty( $type_info['allowed_post_types'] ) ) {
							$allowed = $type_info['allowed_post_types'];
							if ( ! in_array( $from_post->post_type, $allowed, true ) || ! in_array( $to_post->post_type, $allowed, true ) ) {
								$batch_to_delete[] = $rel->id;
								$batch_issues['invalid'][] = $rel->id;
								continue;
							}
						}
					}
				}
			}

			// Update stats and trigger callback for batch
			foreach ( $batch_issues as $type => $ids ) {
				if ( ! empty( $ids ) ) {
					$stats[ $type ] += count( $ids );
					if ( $callback ) {
						call_user_func( $callback, $type, $ids );
					}
				}
			}

			// Execute batch delete
			if ( $fix && ! empty( $batch_to_delete ) ) {
				$ids_string = implode( ',', array_map( 'absint', $batch_to_delete ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( "DELETE FROM `$table` WHERE id IN ($ids_string)" );
			}

			$stats['cleaned'] += count( $batch_to_delete );

			// Prevent accidental infinite loop and bound execution if needed
			if ( count( $relations ) < $batch_size ) {
				break;
			}
			
			// Optional: Clear object cache between chunks to free memory
			if ( function_exists( 'wp_cache_flush_runtime' ) ) {
				wp_cache_flush_runtime();
			}
		}

		// Sum up all issues for legacy compatibility with notices
		$legacy_issues = array(
			'duplicates'  => $stats['duplicates'],
			'orphaned'    => $stats['orphaned'],
			'unregistered' => $stats['unregistered'],
			'constraints' => $stats['constraints'],
			'direction'   => $stats['direction'],
			'invalid'     => $stats['invalid'],
		);

		return array(
			'cleaned' => $stats['cleaned'] + $stats['duplicates'],
			'issues'  => $legacy_issues,
			'fixing'  => $fix,
		);
	}

	/**
	 * Show admin notice if integrity check found issues
	 */
	public static function show_integrity_notice() {
		$notice = get_transient( 'naticore_integrity_notice' );

		if ( ! $notice || $notice['cleaned'] === 0 ) {
			return;
		}

		delete_transient( 'naticore_integrity_notice' );

		$cleaned_count = absint( $notice['cleaned'] );
		/* translators: %d: Number of invalid relationships cleaned up */
		$message_text = _n(
			'%d invalid relationship was cleaned up.',
			'%d invalid relationships were cleaned up.',
			$cleaned_count,
			'native-content-relationships'
		);
		$message      = sprintf( $message_text, $cleaned_count );

		$details = array();
		if ( $notice['issues']['duplicates'] > 0 ) {
			/* translators: %d: Number of duplicate relationships */
			$details[] = sprintf( _n( '%d duplicate', '%d duplicates', $notice['issues']['duplicates'], 'native-content-relationships' ), $notice['issues']['duplicates'] );
		}
		if ( $notice['issues']['orphaned'] > 0 ) {
			/* translators: %d: Number of broken references */
			$details[] = sprintf( _n( '%d broken reference', '%d broken references', $notice['issues']['orphaned'], 'native-content-relationships' ), $notice['issues']['orphaned'] );
		}
		if ( $notice['issues']['unregistered'] > 0 ) {
			/* translators: %d: Number of invalid types */
			$details[] = sprintf( _n( '%d invalid type', '%d invalid types', $notice['issues']['unregistered'], 'native-content-relationships' ), $notice['issues']['unregistered'] );
		}

		if ( ! empty( $details ) ) {
			$message .= ' (' . implode( ', ', $details ) . ')';
		}

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Content Relationships:', 'native-content-relationships' ); ?></strong>
				<?php echo esc_html( $message ); ?>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=naticore-overview' ) ); ?>"><?php esc_html_e( 'View all relationships', 'native-content-relationships' ); ?></a>
			</p>
		</div>
		<?php
	}
}

// Show notice
add_action( 'admin_notices', array( 'NATICORE_Integrity', 'show_integrity_notice' ) );

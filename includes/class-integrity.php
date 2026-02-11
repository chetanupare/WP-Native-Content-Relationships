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
	 * Run integrity check
	 *
	 * @param bool $fix Whether to actually delete invalid records.
	 * @return array Results with cleaned count and issues
	 */
	public function run_integrity_check( $fix = true ) {
		global $wpdb;

		$cleaned = 0;
		$issues  = array(
			'duplicates'  => array(),
			'orphaned'    => array(),
			'unregistered' => array(),
			'constraints' => array(),
			'direction'   => array(),
			'invalid'     => array(), // Legacy bucket
		);

		// Get all relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Integrity check
		$all_relations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}content_relations`" );

		$seen      = array();
		$to_delete = array();
		
		// Track connections for constraint checks
		$connection_counts = array();

		foreach ( $all_relations as $rel ) {
			$key = $rel->from_id . '|' . $rel->to_id . '|' . $rel->type;

			// 1. Check for duplicates
			if ( isset( $seen[ $key ] ) ) {
				$to_delete[] = $rel->id;
				$issues['duplicates'][] = $rel->id;
				++$cleaned;
				continue;
			}
			$seen[ $key ] = true;

			// 2. Check for unregistered types
			if ( function_exists( 'ncr_has_unregistered_type' ) && ncr_has_unregistered_type( $rel ) ) {
				$to_delete[] = $rel->id;
				$issues['unregistered'][] = $rel->id;
				++$cleaned;
				continue;
			}

			// 3. Check for orphaned relationships
			if ( function_exists( 'ncr_is_orphaned_relation' ) && ncr_is_orphaned_relation( $rel ) ) {
				$to_delete[] = $rel->id;
				$issues['orphaned'][] = $rel->id;
				++$cleaned;
				continue;
			}

			// 4. Check for directional inconsistencies (One-way type used as bidirectional)
			if ( function_exists( 'ncr_has_directional_inconsistency' ) && ncr_has_directional_inconsistency( $rel ) ) {
				$to_delete[] = $rel->id;
				$issues['direction'][] = $rel->id;
				++$cleaned;
				continue;
			}

			// 5. Check for max_connections violations (Historical)
			$type_info = NATICORE_Relation_Types::get_type( $rel->type );
			if ( $type_info && $type_info['max_connections'] > 0 ) {
				$constraint_key = $rel->from_id . '|' . $rel->type . '|' . $rel->to_type;
				if ( ! isset( $connection_counts[ $constraint_key ] ) ) {
					$connection_counts[ $constraint_key ] = 0;
				}
				$connection_counts[ $constraint_key ]++;

				if ( $connection_counts[ $constraint_key ] > $type_info['max_connections'] ) {
					$to_delete[] = $rel->id;
					$issues['constraints'][] = $rel->id;
					++$cleaned;
					continue;
				}
			}

			// 6. Legacy post type restriction check
			if ( 'post' === $rel->to_type ) {
				$from_type = $type_info ? $type_info['from_type'] : 'post';
				if ( 'post' === $from_type ) {
					$from_post = get_post( $rel->from_id );
					$to_post   = get_post( $rel->to_id );
					if ( $from_post && $to_post && $type_info && ! empty( $type_info['allowed_post_types'] ) ) {
						$allowed = $type_info['allowed_post_types'];
						if ( ! in_array( $from_post->post_type, $allowed, true ) || ! in_array( $to_post->post_type, $allowed, true ) ) {
							$to_delete[] = $rel->id;
							$issues['invalid'][] = $rel->id;
							++$cleaned;
							continue;
						}
					}
				}
			}
		}

		// Delete invalid relationships if not dry-run
		if ( $fix && ! empty( $to_delete ) ) {
			$ids = array_map( 'absint', $to_delete );
			if ( ! empty( $ids ) ) {
				$ids_string = implode( ',', $ids );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table delete
				$wpdb->query( "DELETE FROM `{$wpdb->prefix}content_relations` WHERE id IN ($ids_string)" );
			}
		}

		return array(
			'cleaned' => $cleaned,
			'issues'  => $issues,
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
		if ( count( $notice['issues']['duplicates'] ) > 0 ) {
			/* translators: %d: Number of duplicate relationships */
			$details[] = sprintf( _n( '%d duplicate', '%d duplicates', count( $notice['issues']['duplicates'] ), 'native-content-relationships' ), count( $notice['issues']['duplicates'] ) );
		}
		if ( count( $notice['issues']['orphaned'] ) > 0 ) {
			/* translators: %d: Number of broken references */
			$details[] = sprintf( _n( '%d broken reference', '%d broken references', count( $notice['issues']['orphaned'] ), 'native-content-relationships' ), count( $notice['issues']['orphaned'] ) );
		}
		if ( count( $notice['issues']['unregistered'] ) > 0 ) {
			/* translators: %d: Number of invalid types */
			$details[] = sprintf( _n( '%d invalid type', '%d invalid types', count( $notice['issues']['unregistered'] ), 'native-content-relationships' ), count( $notice['issues']['unregistered'] ) );
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

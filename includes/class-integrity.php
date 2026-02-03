<?php
/**
 * Integrity Checker
 * Silent cleanup of invalid relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Integrity {

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
	 * @return array Results with cleaned count and issues
	 */
	public function run_integrity_check() {
		global $wpdb;

		$cleaned = 0;
		$issues  = array(
			'duplicates' => 0,
			'broken'     => 0,
			'invalid'    => 0,
		);

		// Get all relationships
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Integrity check
		$all_relations = $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}content_relations`" );

		$seen      = array();
		$to_delete = array();

		foreach ( $all_relations as $rel ) {
			$key = $rel->from_id . '|' . $rel->to_id . '|' . $rel->type;

			// Check for duplicates
			if ( isset( $seen[ $key ] ) ) {
				$to_delete[] = $rel->id;
				++$issues['duplicates'];
				++$cleaned;
				continue;
			}
			$seen[ $key ] = true;

			// Check for broken references
			$from_post = get_post( $rel->from_id );
			$to_post   = get_post( $rel->to_id );

			if ( ! $from_post || ! $to_post ) {
				$to_delete[] = $rel->id;
				++$issues['broken'];
				++$cleaned;
				continue;
			}

			// Check for invalid types
			if ( ! NATICORE_Relation_Types::exists( $rel->type ) ) {
				$to_delete[] = $rel->id;
				++$issues['invalid'];
				++$cleaned;
				continue;
			}

			// Check post type restrictions
			$type_info = NATICORE_Relation_Types::get_type( $rel->type );
			if ( $type_info && ! empty( $type_info['allowed_post_types'] ) ) {
				$allowed = $type_info['allowed_post_types'];
				if ( ! in_array( $from_post->post_type, $allowed, true ) || ! in_array( $to_post->post_type, $allowed, true ) ) {
					$to_delete[] = $rel->id;
					++$issues['invalid'];
					++$cleaned;
					continue;
				}
			}
		}

		// Delete invalid relationships
		if ( ! empty( $to_delete ) ) {
			$ids = array_map( 'absint', $to_delete );
			if ( ! empty( $ids ) ) {
				$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Safe delete with prepared placeholders
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders are safely constructed with validated values
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders are in $ids array
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM `{$wpdb->prefix}content_relations` WHERE id IN ( $placeholders )",
						$ids
					)
				);
			}
		}

		return array(
			'cleaned' => $cleaned,
			'issues'  => $issues,
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
		if ( $notice['issues']['broken'] > 0 ) {
			/* translators: %d: Number of broken references */
			$details[] = sprintf( _n( '%d broken reference', '%d broken references', $notice['issues']['broken'], 'native-content-relationships' ), $notice['issues']['broken'] );
		}
		if ( $notice['issues']['invalid'] > 0 ) {
			/* translators: %d: Number of invalid types */
			$details[] = sprintf( _n( '%d invalid type', '%d invalid types', $notice['issues']['invalid'], 'native-content-relationships' ), $notice['issues']['invalid'] );
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

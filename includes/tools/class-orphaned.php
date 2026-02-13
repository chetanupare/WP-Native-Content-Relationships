<?php
/**
 * Orphaned Relationships Checker
 * Weekly admin notice
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Orphaned {

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

		add_action( 'admin_init', array( $this, 'maybe_check_orphaned' ) );
		add_action( 'admin_notices', array( $this, 'show_orphaned_notice' ) );
	}

	/**
	 * Maybe check for orphaned relationships
	 */
	public function maybe_check_orphaned() {
		// Only check once per week
		$last_check      = get_option( 'naticore_last_orphaned_check', 0 );
		$week_in_seconds = WEEK_IN_SECONDS;

		if ( time() - $last_check < $week_in_seconds ) {
			return;
		}

		$count = $this->count_orphaned();

		if ( $count > 0 ) {
			update_option( 'naticore_orphaned_count', $count );
		} else {
			delete_option( 'naticore_orphaned_count' );
		}

		update_option( 'naticore_last_orphaned_check', time() );
	}

	/**
	 * Count orphaned relationships
	 */
	private function count_orphaned() {
		global $wpdb;

		// Count relationships where from_id or to_id points to non-existent posts
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Orphan check
		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$wpdb->prefix}content_relations` AS rel 
			LEFT JOIN `{$wpdb->posts}` AS from_post ON rel.from_id = from_post.ID 
			LEFT JOIN `{$wpdb->posts}` AS to_post ON rel.to_id = to_post.ID 
			WHERE from_post.ID IS NULL OR to_post.ID IS NULL"
		);

		return absint( $count );
	}

	/**
	 * Show orphaned notice
	 */
	public function show_orphaned_notice() {
		$count = get_option( 'naticore_orphaned_count', 0 );

		if ( 0 === $count ) {
			return;
		}

		// Only show once per session
		if ( get_transient( 'naticore_orphaned_notice_shown' ) ) {
			return;
		}

		set_transient( 'naticore_orphaned_notice_shown', true, HOUR_IN_SECONDS );

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Content Relationships:', 'native-content-relationships' ); ?></strong>
				<?php
				/* translators: %d: Number of orphaned relationships */
				$orphaned_message = _n(
					'You have %d orphaned relationship.',
					'You have %d orphaned relationships.',
					$count,
					'native-content-relationships'
				);
				printf(
					esc_html( $orphaned_message ),
					esc_html( $count )
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'tools.php?page=naticore-overview' ) ); ?>"><?php esc_html_e( 'View all relationships', 'native-content-relationships' ); ?></a>
			</p>
		</div>
		<?php
	}
}

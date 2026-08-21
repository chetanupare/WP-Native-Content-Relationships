<?php
/**
 * Relationship Expiration
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Expiration
 *
 * Manages relationship expiration dates and auto-deactivation.
 */
class NATICORE_Expiration {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Run expiration check daily
		if ( ! wp_next_scheduled( 'naticore_check_expirations' ) ) {
			wp_schedule_event( time(), 'daily', 'naticore_check_expirations' );
		}
		add_action( 'naticore_check_expirations', array( $this, 'process_expirations' ) );

		// Also check on admin init for immediate effect
		add_action( 'admin_init', array( $this, 'check_now' ) );
	}

	/**
	 * Get expiration date for a relationship
	 *
	 * @param int $relation_id Relation ID.
	 * @return string|null Expiration date (Y-m-d) or null.
	 */
	public static function get_expiration( $relation_id ) {
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			return NATICORE_Meta_API::get_meta( $relation_id, '_expiration_date' );
		}
		return null;
	}

	/**
	 * Set expiration date for a relationship
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $date        Expiration date (Y-m-d).
	 * @return bool True on success.
	 */
	public static function set_expiration( $relation_id, $date ) {
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			// Store the date
			NATICORE_Meta_API::update_meta( $relation_id, '_expiration_date', $date );

			// Set active status
			NATICORE_Meta_API::update_meta( $relation_id, '_is_expired', '0' );

			return true;
		}
		return false;
	}

	/**
	 * Check if a relationship is expired
	 *
	 * @param int $relation_id Relation ID.
	 * @return bool True if expired.
	 */
	public static function is_expired( $relation_id ) {
		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			return NATICORE_Meta_API::get_meta( $relation_id, '_is_expired' ) === '1';
		}
		return false;
	}

	/**
	 * Process all expirations
	 */
	public function process_expirations() {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		// Find all relationships with expiration dates that are past due and not yet marked expired
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Batch operation
		$expired = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.id FROM {$wpdb->prefix}content_relations r
				INNER JOIN {$wpdb->prefix}content_relationmeta rm ON r.id = rm.content_relation_id
				WHERE rm.meta_key = '_expiration_date' AND rm.meta_value < %s
				AND r.id NOT IN (
					SELECT content_relation_id FROM {$wpdb->prefix}content_relationmeta
					WHERE meta_key = '_is_expired' AND meta_value = '1'
				)",
				$today
			)
		);

		$count = 0;
		foreach ( $expired as $row ) {
			if ( class_exists( 'NATICORE_Meta_API' ) ) {
				NATICORE_Meta_API::update_meta( $row->id, '_is_expired', '1' );
				$count++;
			}
		}

		if ( $count > 0 ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging
			error_log( "NATICORE: Expired {$count} relationships on " . $today );
		}
	}

	/**
	 * Check expirations immediately (admin only)
	 */
	public function check_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['ncr_check_expirations'] ) && check_admin_referer( 'ncr_check_expirations' ) ) {
			$this->process_expirations();
			wp_safe_redirect( admin_url( 'admin.php?page=naticore-expiration&checked=1' ) );
			exit;
		}
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$today    = current_time( 'Y-m-d' );
		$checked  = isset( $_GET['checked'] );
		$relations = self::get_all_with_expiration();
		
		$stitch_admin = NATICORE_Stitch_Admin::get_instance();
		$stitch_admin->render_wrapper_start( 'naticore-tools' );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Expiration', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Manage auto-expiration dates for temporary relationships.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<?php if ( $checked ) : ?>
			<div class="nc-notice nc-notice-success nc-mb-lg">
				<?php esc_html_e( 'Expiration check completed.', 'native-content-relationships' ); ?>
			</div>
		<?php endif; ?>

		<div class="nc-grid-12">
			<div class="nc-col-12 nc-card">
				<div class="nc-card-header nc-flex nc-justify-between nc-items-center">
					<h3><?php esc_html_e( 'Expiring Connections', 'native-content-relationships' ); ?></h3>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-expiration&ncr_check_expirations=1' ), 'ncr_check_expirations' ) ); ?>" class="nc-btn nc-btn-secondary">
						<?php esc_html_e( 'Run Expiration Check Now', 'native-content-relationships' ); ?>
					</a>
				</div>
				<div style="overflow-x:auto;">
					<div class="nc-table-responsive">
						<table class="nc-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Relationship', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Expiration Date', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Status', 'native-content-relationships' ); ?></th>
									<th style="text-align:right;"><?php esc_html_e( 'Action', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $relations ) ) : ?>
									<tr>
										<td colspan="5" class="nc-text-muted"><?php esc_html_e( 'No relationships with expiration dates found.', 'native-content-relationships' ); ?></td>
									</tr>
								<?php else : ?>
									<?php foreach ( $relations as $rel ) :
										$is_expired = self::is_expired( $rel->id );
										$days_left  = ( strtotime( $rel->expiration ) - strtotime( $today ) ) / DAY_IN_SECONDS;
										?>
										<tr>
											<td>
												<a href="<?php echo esc_url( get_edit_post_link( $rel->from_id ) ); ?>" class="nc-text-primary" style="text-decoration:none;"><?php echo esc_html( get_the_title( $rel->from_id ) ); ?></a>
												<span style="color:var(--nc-outline-variant);margin:0 4px;">&rarr;</span>
												<a href="<?php echo esc_url( get_edit_post_link( $rel->to_id ) ); ?>" class="nc-text-primary" style="text-decoration:none;"><?php echo esc_html( get_the_title( $rel->to_id ) ); ?></a>
											</td>
											<td><span class="nc-badge nc-badge-type" style="font-size:10px;"><?php echo esc_html( $rel->type ); ?></span></td>
											<td><?php echo esc_html( $rel->expiration ); ?></td>
											<td>
												<?php if ( $is_expired ) : ?>
													<span class="nc-badge nc-badge-error"><?php esc_html_e( 'Expired', 'native-content-relationships' ); ?></span>
												<?php elseif ( $days_left <= 7 ) : ?>
													<span class="nc-badge nc-badge-warning">
														<?php
														printf(
															esc_html( _n( '%d day left', '%d days left', max( 0, (int) $days_left ), 'native-content-relationships' ) ),
															max( 0, (int) $days_left )
														);
														?>
													</span>
												<?php else : ?>
													<span class="nc-badge nc-badge-success"><?php esc_html_e( 'Active', 'native-content-relationships' ); ?></span>
												<?php endif; ?>
											</td>
											<td style="text-align:right;">
												<a href="<?php echo esc_url( get_edit_post_link( $rel->from_id ) ); ?>" class="nc-text-primary" style="text-decoration:none;font-weight:500;">
													<?php esc_html_e( 'Review', 'native-content-relationships' ); ?>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
		$stitch_admin->render_wrapper_end();
	}

	/**
	 * Get all relationships with expiration dates
	 *
	 * @return array
	 */
	private static function get_all_with_expiration() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin page query
		$results = $wpdb->get_results(
			"SELECT r.id, r.from_id, r.to_id, r.type, rm.meta_value as expiration
			FROM {$wpdb->prefix}content_relations r
			INNER JOIN {$wpdb->prefix}content_relationmeta rm ON r.id = rm.content_relation_id
			WHERE rm.meta_key = '_expiration_date'
			ORDER BY rm.meta_value ASC"
		);

		return $results ? $results : array();
	}

	/**
	 * Register admin menu
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'naticore-hidden',
			__( 'Relationship Expiration', 'native-content-relationships' ),
			__( 'Relationship Expiration', 'native-content-relationships' ),
			'manage_options',
			'naticore-expiration',
			array( __CLASS__, 'render_admin_page' )
		);
	}
}

<?php
/**
 * Relationship Analytics Dashboard
 *
 * Shows statistics about content relationships.
 *
 * @package Native Content Relationships
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Analytics
 *
 * Provides analytics and statistics for content relationships.
 */
class NATICORE_Analytics {

	/**
	 * Instance
	 *
	 * @var NATICORE_Analytics|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return NATICORE_Analytics
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=naticore_relation',
			__( 'Analytics', 'native-content-relationships' ),
			__( 'Analytics', 'native-content-relationships' ),
			'manage_options',
			'naticore-analytics',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Get analytics data
	 *
	 * @return array Analytics data.
	 */
	public function get_analytics() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'content_relations';

		// Total relationships
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

		// Relationships by type
		$by_type = $wpdb->get_results(
			"SELECT type, COUNT(*) as count FROM {$table_name} GROUP BY type ORDER BY count DESC"
		);

		// Most connected posts (by incoming relationships)
		$most_connected_incoming = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT to_id as post_id, COUNT(*) as count FROM {$table_name} GROUP BY to_id ORDER BY count DESC LIMIT %d",
				10
			)
		);

		// Most connected posts (by outgoing relationships)
		$most_connected_outgoing = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT from_id as post_id, COUNT(*) as count FROM {$table_name} GROUP BY from_id ORDER BY count DESC LIMIT %d",
				10
			)
		);

		// Orphaned posts (no relationships)
		$all_posts = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('revision', 'nav_menu_item', 'custom_css', 'customize_changeset') AND post_status = 'publish'"
		);

		$connected_posts = $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM (
				SELECT from_id as post_id FROM {$table_name}
				UNION
				SELECT to_id as post_id FROM {$table_name}
			) as connected"
		);

		$orphaned = max( 0, $all_posts - $connected_posts );

		// Relationships created over time (last 30 days)
		$over_time = $wpdb->get_results(
			"SELECT DATE(created_at) as date, COUNT(*) as count 
			FROM {$table_name} 
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY DATE(created_at) 
			ORDER BY date ASC"
		);

		// Average relationships per post
		$avg_per_post = $connected_posts > 0 ? round( $total / $connected_posts, 1 ) : 0;

		// Posts with most incoming relationships
		$top_incoming = array();
		foreach ( $most_connected_incoming as $item ) {
			$post = get_post( $item->post_id );
			if ( $post ) {
				$top_incoming[] = array(
					'id'    => $item->post_id,
					'title' => $post->post_title,
					'type'  => $post->post_type,
					'count' => (int) $item->count,
				);
			}
		}

		// Posts with most outgoing relationships
		$top_outgoing = array();
		foreach ( $most_connected_outgoing as $item ) {
			$post = get_post( $item->post_id );
			if ( $post ) {
				$top_outgoing[] = array(
					'id'    => $item->post_id,
					'title' => $post->post_title,
					'type'  => $post->post_type,
					'count' => (int) $item->count,
				);
			}
		}

		return array(
			'total'          => $total,
			'by_type'        => $by_type,
			'orphaned'       => $orphaned,
			'connected'      => (int) $connected_posts,
			'all_posts'      => (int) $all_posts,
			'avg_per_post'   => $avg_per_post,
			'top_incoming'   => $top_incoming,
			'top_outgoing'   => $top_outgoing,
			'over_time'      => $over_time,
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		$analytics = $this->get_analytics();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Relationship Analytics', 'native-content-relationships' ); ?></h1>
			<p><?php esc_html_e( 'Overview of how your content is connected.', 'native-content-relationships' ); ?></p>

			<!-- Summary Cards -->
			<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
				<div class="naticore-card" style="text-align: center;">
					<h3 style="margin: 0; font-size: 32px; color: #2271b1;"><?php echo esc_html( number_format_i18n( $analytics['total'] ) ); ?></h3>
					<p style="margin: 5px 0 0;"><?php esc_html_e( 'Total Relationships', 'native-content-relationships' ); ?></p>
				</div>
				<div class="naticore-card" style="text-align: center;">
					<h3 style="margin: 0; font-size: 32px; color: #00a32a;"><?php echo esc_html( number_format_i18n( $analytics['connected'] ) ); ?></h3>
					<p style="margin: 5px 0 0;"><?php esc_html_e( 'Connected Posts', 'native-content-relationships' ); ?></p>
				</div>
				<div class="naticore-card" style="text-align: center;">
					<h3 style="margin: 0; font-size: 32px; color: #dba617;"><?php echo esc_html( number_format_i18n( $analytics['orphaned'] ) ); ?></h3>
					<p style="margin: 5px 0 0;"><?php esc_html_e( 'Orphaned Posts', 'native-content-relationships' ); ?></p>
				</div>
				<div class="naticore-card" style="text-align: center;">
					<h3 style="margin: 0; font-size: 32px; color: #d63638;"><?php echo esc_html( $analytics['avg_per_post'] ); ?></h3>
					<p style="margin: 5px 0 0;"><?php esc_html_e( 'Avg. per Post', 'native-content-relationships' ); ?></p>
				</div>
			</div>

			<!-- Relationships by Type -->
			<div class="naticore-card" style="margin: 20px 0;">
				<h3><?php esc_html_e( 'Relationships by Type', 'native-content-relationships' ); ?></h3>
				<?php if ( empty( $analytics['by_type'] ) ) : ?>
					<p><?php esc_html_e( 'No relationships found.', 'native-content-relationships' ); ?></p>
				<?php else : ?>
					<table class="widefat">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Count', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Percentage', 'native-content-relationships' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $analytics['by_type'] as $type ) : ?>
								<?php
								$type_info = NATICORE_Relation_Types::get_type( $type->type );
								$type_label = $type_info ? $type_info['label'] : $type->type;
								$percentage = $analytics['total'] > 0 ? round( ( $type->count / $analytics['total'] ) * 100, 1 ) : 0;
								?>
								<tr>
									<td><?php echo esc_html( $type_label ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $type->count ) ); ?></td>
									<td>
										<div style="background: #f0f0f1; border-radius: 3px; height: 20px; width: 200px; display: inline-block;">
											<div style="background: #2271b1; height: 100%; width: <?php echo esc_attr( $percentage ); ?>%; border-radius: 3px;"></div>
										</div>
										<?php echo esc_html( $percentage ); ?>%
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Top Connected Posts -->
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
				<div class="naticore-card">
					<h3><?php esc_html_e( 'Most Referenced (Incoming)', 'native-content-relationships' ); ?></h3>
					<?php if ( empty( $analytics['top_incoming'] ) ) : ?>
						<p><?php esc_html_e( 'No data yet.', 'native-content-relationships' ); ?></p>
					<?php else : ?>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Post', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Incoming', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $analytics['top_incoming'] as $item ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>">
												<?php echo esc_html( $item['title'] ); ?>
											</a>
											<br><small><?php echo esc_html( $item['type'] ); ?></small>
										</td>
										<td><?php echo esc_html( $item['count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>

				<div class="naticore-card">
					<h3><?php esc_html_e( 'Most Connected (Outgoing)', 'native-content-relationships' ); ?></h3>
					<?php if ( empty( $analytics['top_outgoing'] ) ) : ?>
						<p><?php esc_html_e( 'No data yet.', 'native-content-relationships' ); ?></p>
					<?php else : ?>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Post', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Outgoing', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $analytics['top_outgoing'] as $item ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( get_edit_post_link( $item['id'] ) ); ?>">
												<?php echo esc_html( $item['title'] ); ?>
											</a>
											<br><small><?php echo esc_html( $item['type'] ); ?></small>
										</td>
										<td><?php echo esc_html( $item['count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<!-- Activity Over Time -->
			<div class="naticore-card" style="margin: 20px 0;">
				<h3><?php esc_html_e( 'Activity (Last 30 Days)', 'native-content-relationships' ); ?></h3>
				<?php if ( empty( $analytics['over_time'] ) ) : ?>
					<p><?php esc_html_e( 'No activity yet.', 'native-content-relationships' ); ?></p>
				<?php else : ?>
					<div style="display: flex; align-items: flex-end; height: 100px; gap: 2px;">
						<?php
						$max_count = max( wp_list_pluck( $analytics['over_time'], 'count' ) );
						foreach ( $analytics['over_time'] as $day ) :
							$height = $max_count > 0 ? ( $day->count / $max_count ) * 100 : 0;
							?>
							<div style="flex: 1; background: #2271b1; height: <?php echo esc_attr( $height ); ?>%; min-height: 2px; border-radius: 2px 2px 0 0;" title="<?php echo esc_attr( $day->date . ': ' . $day->count ); ?>"></div>
						<?php endforeach; ?>
					</div>
					<div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 12px; color: #646970;">
						<span><?php echo esc_html( $analytics['over_time'][0]->date ?? '' ); ?></span>
						<span><?php echo esc_html( end( $analytics['over_time'] )->date ?? '' ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

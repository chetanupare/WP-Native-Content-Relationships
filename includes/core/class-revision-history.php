<?php
/**
 * Relationship Revision History
 *
 * @package Native_Content_Relationships
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Revision_History
 *
 * Tracks and logs relationship changes for audit trail.
 */
class NATICORE_Revision_History {

	/**
	 * Constructor - hook into relationship actions
	 */
	public function __construct() {
		add_action( 'naticore_relation_added', array( $this, 'log_add' ), 10, 4 );
		add_action( 'naticore_relation_removed', array( $this, 'log_remove' ), 10, 3 );
	}

	/**
	 * Log when a relationship is added
	 */
	public function log_add( $relation_id, $from_id, $to_id, $type ) {
		self::log( 'added', $from_id, $to_id, $type );
	}

	/**
	 * Log when a relationship is removed
	 */
	public function log_remove( $from_id, $to_id, $type ) {
		self::log( 'removed', $from_id, $to_id, $type );
	}

	/**
	 * Log a relationship change
	 *
	 * @param string $action   Action performed: added, removed, meta_updated.
	 * @param int    $from_id  Source post ID.
	 * @param int    $to_id    Target post ID.
	 * @param string $type     Relationship type key.
	 * @param array  $meta     Optional metadata about the change.
	 * @return bool|int Log ID on success, false on failure.
	 */
	public static function log( $action, $from_id, $to_id, $type, $meta = array() ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		$now     = current_time( 'mysql', true );

		$log_entry = array(
			'action'    => $action,
			'from_id'   => $from_id,
			'to_id'     => $to_id,
			'type'      => $type,
			'user_id'   => $user_id,
			'user_name' => get_the_author_meta( 'display_name', $user_id ),
			'timestamp' => $now,
			'meta'      => $meta,
		);

		$log_id = wp_insert_post( array(
			'post_type'   => 'ncr_revision',
			'post_status' => 'private',
			'post_title'  => sprintf(
				'%s: %s → %s (%s)',
				$action,
				get_the_title( $from_id ),
				get_the_title( $to_id ),
				$type
			),
			'post_content' => wp_json_encode( $log_entry ),
		) );

		if ( $log_id && ! is_wp_error( $log_id ) ) {
			add_post_meta( $log_id, '_ncr_from_id', $from_id );
			add_post_meta( $log_id, '_ncr_to_id', $to_id );
			add_post_meta( $log_id, '_ncr_type', $type );
			add_post_meta( $log_id, '_ncr_user_id', $user_id );
			add_post_meta( $log_id, '_ncr_action', $action );
		}

		return $log_id;
	}

	/**
	 * Get revision history for a post
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit   Number of entries to return.
	 * @return array Array of log entries.
	 */
	public static function get_history( $post_id, $limit = 50 ) {
		$query = new WP_Query( array(
			'post_type'   => 'ncr_revision',
			'post_status' => 'private',
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'   => '_ncr_from_id',
					'value' => $post_id,
				),
				array(
					'key'   => '_ncr_to_id',
					'value' => $post_id,
				),
			),
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$logs = array();
		foreach ( $query->posts as $post ) {
			$entry = json_decode( $post->post_content, true );
			if ( $entry ) {
				$entry['log_id'] = $post->ID;
				$logs[]          = $entry;
			}
		}

		return $logs;
	}

	/**
	 * Get all revision history
	 *
	 * @param array $args Query arguments.
	 * @return array Array of log entries.
	 */
	public static function get_all_history( $args = array() ) {
		$defaults = array(
			'from_id'        => 0,
			'to_id'          => 0,
			'type'           => '',
			'user_id'        => 0,
			'action'         => '',
			'date_from'      => '',
			'date_to'        => '',
			'posts_per_page' => 50,
			'paged'          => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => 'ncr_revision',
			'post_status'    => 'private',
			'posts_per_page' => $args['posts_per_page'],
			'paged'          => $args['paged'],
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$meta_query = array();

		if ( $args['from_id'] ) {
			$meta_query[] = array(
				'key'   => '_ncr_from_id',
				'value' => $args['from_id'],
			);
		}

		if ( $args['to_id'] ) {
			$meta_query[] = array(
				'key'   => '_ncr_to_id',
				'value' => $args['to_id'],
			);
		}

		if ( $args['type'] ) {
			$meta_query[] = array(
				'key'   => '_ncr_type',
				'value' => $args['type'],
			);
		}

		if ( $args['user_id'] ) {
			$meta_query[] = array(
				'key'   => '_ncr_user_id',
				'value' => $args['user_id'],
			);
		}

		if ( $args['action'] ) {
			$meta_query[] = array(
				'key'   => '_ncr_action',
				'value' => $args['action'],
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query_args['meta_query'] = $meta_query;
		}

		if ( $args['date_from'] ) {
			$query_args['date_query'] = array(
				array(
					'after'  => $args['date_from'],
					'inclusive' => true,
				),
			);
		}

		if ( $args['date_to'] ) {
			if ( ! isset( $query_args['date_query'] ) ) {
				$query_args['date_query'] = array();
			}
			$query_args['date_query'][] = array(
				'before'   => $args['date_to'],
				'inclusive' => true,
			);
		}

		$query = new WP_Query( $query_args );

		$logs = array();
		foreach ( $query->posts as $post ) {
			$entry = json_decode( $post->post_content, true );
			if ( $entry ) {
				$entry['log_id'] = $post->ID;
				$logs[]          = $entry;
			}
		}

		return array(
			'logs'       => $logs,
			'total'      => $query->found_posts,
			'pages'      => $query->max_num_pages,
			'page'       => $args['paged'],
		);
	}

	/**
	 * Render revision history table for a post
	 *
	 * @param int $post_id Post ID.
	 */
	public static function render_post_history( $post_id ) {
		$history = self::get_history( $post_id, 20 );

		if ( empty( $history ) ) {
			echo '<p class="description">' . esc_html__( 'No relationship changes recorded yet.', 'native-content-relationships' ) . '</p>';
			return;
		}

		?>
		<table class="widefat striped" style="margin-top: 10px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Action', 'native-content-relationships' ); ?></th>
					<th><?php esc_html_e( 'Related To', 'native-content-relationships' ); ?></th>
					<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
					<th><?php esc_html_e( 'User', 'native-content-relationships' ); ?></th>
					<th><?php esc_html_e( 'Date', 'native-content-relationships' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $history as $entry ) : ?>
					<tr>
						<td>
							<span class="naticore-badge naticore-badge-<?php echo esc_attr( $entry['action'] ); ?>">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $entry['action'] ) ) ); ?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url( get_edit_post_link( $entry['to_id'] ) ); ?>" target="_blank">
								<?php echo esc_html( get_the_title( $entry['to_id'] ) ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $entry['type'] ); ?></td>
						<td><?php echo esc_html( $entry['user_name'] ); ?></td>
						<td><?php echo esc_html( human_time_diff( strtotime( $entry['timestamp'] ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get history stats
	 *
	 * @param int $days Number of days to look back.
	 * @return array Stats.
	 */
	public static function get_stats( $days = 30 ) {
		global $wpdb;

		$date_from = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_content FROM {$wpdb->posts} WHERE post_type = 'ncr_revision' AND post_date >= %s",
				$date_from
			)
		);

		$stats = array(
			'total'        => count( $results ),
			'by_action'    => array(),
			'by_user'      => array(),
			'by_type'      => array(),
			'by_day'       => array(),
		);

		foreach ( $results as $row ) {
			$entry = json_decode( $row->post_content, true );
			if ( ! $entry ) {
				continue;
			}

			$action = $entry['action'] ?? 'unknown';
			$user   = $entry['user_name'] ?? 'Unknown';
			$type   = $entry['type'] ?? 'unknown';
			$day    = substr( $entry['timestamp'] ?? '', 0, 10 );

			$stats['by_action'][ $action ] = ( $stats['by_action'][ $action ] ?? 0 ) + 1;
			$stats['by_user'][ $user ]     = ( $stats['by_user'][ $user ] ?? 0 ) + 1;
			$stats['by_type'][ $type ]     = ( $stats['by_type'][ $type ] ?? 0 ) + 1;
			$stats['by_day'][ $day ]       = ( $stats['by_day'][ $day ] ?? 0 ) + 1;
		}

		return $stats;
	}
}

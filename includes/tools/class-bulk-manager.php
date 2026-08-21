<?php
/**
 * Bulk Relationship Manager
 *
 * Admin page for managing all relationships at once.
 *
 * @package Native Content Relationships
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Bulk_Manager
 *
 * Provides a bulk relationship management interface.
 */
class NATICORE_Bulk_Manager {

	/**
	 * Instance
	 *
	 * @var NATICORE_Bulk_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return NATICORE_Bulk_Manager
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
		add_action( 'admin_init', array( $this, 'handle_bulk_actions' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=naticore_relation',
			__( 'Bulk Manager', 'native-content-relationships' ),
			__( 'Bulk Manager', 'native-content-relationships' ),
			'manage_options',
			'naticore-bulk-manager',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle bulk actions
	 */
	public function handle_bulk_actions() {
		if ( ! isset( $_POST['naticore_bulk_manager_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['naticore_bulk_manager_nonce'] ) ), 'naticore_bulk_manager' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$ids    = isset( $_POST['relation_ids'] ) ? array_map( 'absint', (array) $_POST['relation_ids'] ) : array();

		if ( empty( $action ) || empty( $ids ) ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'content_relations';

		switch ( $action ) {
			case 'delete':
				$placeholders = array_fill( 0, count( $ids ), '%d' );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$table_name} WHERE id IN (" . implode( ',', $placeholders ) . ')',
						...$ids
					)
				);
				$count = count( $ids );
				add_settings_error(
					'naticore_bulk_manager',
					'bulk_deleted',
					sprintf(
						/* translators: %d: number of relationships deleted */
						esc_html__( '%d relationships deleted.', 'native-content-relationships' ),
						$count
					),
					'updated'
				);
				break;

			case 'change_type':
				$new_type = isset( $_POST['new_type'] ) ? sanitize_text_field( wp_unslash( $_POST['new_type'] ) ) : '';
				if ( ! empty( $new_type ) ) {
					$placeholders = array_fill( 0, count( $ids ), '%d' );
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$table_name} SET type = %s WHERE id IN (" . implode( ',', $placeholders ) . ')',
							$new_type,
							...$ids
						)
					);
					$count = count( $ids );
					add_settings_error(
						'naticore_bulk_manager',
						'bulk_type_changed',
						sprintf(
							/* translators: 1: number of relationships, 2: new type */
							esc_html__( '%1$d relationships changed to "%2$s".', 'native-content-relationships' ),
							$count,
							$new_type
						),
						'updated'
					);
				}
				break;
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=naticore_relation&page=naticore-bulk-manager' ) );
		exit;
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'content_relations';

		// Get all relationships with post data
		$page     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page = 20;
		$offset   = ( $page - 1 ) * $per_page;

		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		$total_pages = ceil( $total / $per_page );

		// Get relationship types
		$types = NATICORE_Relation_Types::get_types();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bulk Relationship Manager', 'native-content-relationships' ); ?></h1>
			<p><?php esc_html_e( 'Manage all relationships at once. Select relationships and perform bulk actions.', 'native-content-relationships' ); ?></p>

			<?php settings_errors( 'naticore_bulk_manager' ); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'naticore_bulk_manager', 'naticore_bulk_manager_nonce' ); ?>

				<div class="tablenav top">
					<div class="alignleft actions">
						<select name="bulk_action" id="bulk-action-selector-top">
							<option value=""><?php esc_html_e( 'Bulk Actions', 'native-content-relationships' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete', 'native-content-relationships' ); ?></option>
							<option value="change_type"><?php esc_html_e( 'Change Type', 'native-content-relationships' ); ?></option>
						</select>

						<select name="new_type" id="new-type-selector" style="display: none;">
							<option value=""><?php esc_html_e( 'Select Type', 'native-content-relationships' ); ?></option>
							<?php foreach ( $types as $slug => $type_info ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $type_info['label'] ); ?></option>
							<?php endforeach; ?>
						</select>

						<input type="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'native-content-relationships' ); ?>">
					</div>

					<div class="tablenav-pages">
						<span class="displaying-num"><?php printf( esc_html__( '%s items', 'native-content-relationships' ), number_format_i18n( $total ) ); ?></span>
						<?php
						echo wp_kses(
							paginate_links(
								array(
									'base'    => add_query_arg( 'paged', '%#%' ),
									'format'  => '',
									'current' => $page,
									'total'   => $total_pages,
								)
							),
							array(
								'a' => array(
									'href' => array(),
									'class' => array(),
								),
								'span' => array(
									'class' => array(),
								),
							)
						);
						?>
					</div>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<td class="manage-column column-cb check-column">
								<label class="screen-reader-text"><?php esc_html_e( 'Select All', 'native-content-relationships' ); ?></label>
								<input type="checkbox" id="cb-select-all-1">
							</td>
							<th class="manage-column column-id"><?php esc_html_e( 'ID', 'native-content-relationships' ); ?></th>
							<th class="manage-column column-from"><?php esc_html_e( 'From', 'native-content-relationships' ); ?></th>
							<th class="manage-column column-to"><?php esc_html_e( 'To', 'native-content-relationships' ); ?></th>
							<th class="manage-column column-type"><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
							<th class="manage-column column-date"><?php esc_html_e( 'Date', 'native-content-relationships' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $results ) ) : ?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No relationships found.', 'native-content-relationships' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $results as $rel ) : ?>
								<?php
								$from_post = get_post( $rel->from_id );
								$to_post   = get_post( $rel->to_id );
								$type_info = NATICORE_Relation_Types::get_type( $rel->type );
								$type_label = $type_info ? $type_info['label'] : $rel->type;
								?>
								<tr>
									<th class="check-column">
										<input type="checkbox" name="relation_ids[]" value="<?php echo esc_attr( $rel->id ); ?>">
									</th>
									<td><?php echo esc_html( $rel->id ); ?></td>
									<td>
										<?php if ( $from_post ) : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $from_post->ID ) ); ?>">
												<?php echo esc_html( $from_post->post_title ); ?>
											</a>
											<br><small><?php echo esc_html( $from_post->post_type ); ?></small>
										<?php else : ?>
											<em><?php esc_html_e( 'Deleted', 'native-content-relationships' ); ?></em>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $to_post ) : ?>
											<a href="<?php echo esc_url( get_edit_post_link( $to_post->ID ) ); ?>">
												<?php echo esc_html( $to_post->post_title ); ?>
											</a>
											<br><small><?php echo esc_html( $to_post->post_type ); ?></small>
										<?php else : ?>
											<em><?php esc_html_e( 'Deleted', 'native-content-relationships' ); ?></em>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $type_label ); ?></td>
									<td><?php echo esc_html( human_time_diff( strtotime( $rel->created_at ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#bulk-action-selector-top').on('change', function() {
				if ($(this).val() === 'change_type') {
					$('#new-type-selector').show();
				} else {
					$('#new-type-selector').hide();
				}
			});

			$('#cb-select-all-1').on('change', function() {
				$('input[name="relation_ids[]"]').prop('checked', this.checked);
			});
		});
		</script>
		<?php
	}
}

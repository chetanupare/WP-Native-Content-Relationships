<?php
/**
 * Relationship Overview Screen
 * Read-only table of all relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class NATICORE_Overview_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'relationship',
				'plural'   => 'relationships',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns
	 */
	public function get_columns() {
		return array(
			'from'      => __( 'From', 'native-content-relationships' ),
			'type'      => __( 'Type', 'native-content-relationships' ),
			'to'        => __( 'To', 'native-content-relationships' ),
			'direction' => __( 'Direction', 'native-content-relationships' ),
			'date'      => __( 'Date', 'native-content-relationships' ),
		);
	}

	/**
	 * Get sortable columns
	 */
	protected function get_sortable_columns() {
		return array(
			'date' => array( 'created_at', false ),
			'type' => array( 'type', false ),
		);
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page     = $this->get_items_per_page( 'relationships_per_page', 20 );
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get total count
		$total_cache_key = 'naticore_admin_total_count';
		$total_items = wp_cache_get( $total_cache_key, 'naticore_relationships' );
		if ( false === $total_items ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
			$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}content_relations`" );
			wp_cache_set( $total_cache_key, $total_items, 'naticore_relationships', 5 * MINUTE_IN_SECONDS );
		}

		// Get items
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only sorting parameters
		$orderby = isset( $_GET['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Display-only sorting parameter, sanitized by strtoupper comparison
		$order = isset( $_GET['order'] ) && strtoupper( wp_unslash( $_GET['order'] ) ) === 'ASC' ? 'ASC' : 'DESC';

		// Sanitize orderby and order for SQL (whitelisting - never prepared)
		$allowed_orderby = array( 'from_id', 'to_id', 'type', 'direction', 'created_at' );
		$orderby         = in_array( $orderby, $allowed_orderby, true ) ? $orderby : 'created_at';
		$allowed_order   = array( 'ASC', 'DESC' );
		$order           = in_array( strtoupper( $order ), $allowed_order, true ) ? strtoupper( $order ) : 'DESC';

		// Create cache key for paginated results
		$items_cache_key = sprintf( 'naticore_admin_items_%d_%d_%s_%s', $per_page, $offset, $orderby, $order );
		$items = wp_cache_get( $items_cache_key, 'naticore_relationships' );
		
		if ( false === $items ) {
			// Build SQL without interpolated ORDER BY fragments (scanner-friendly)
			if ( 'ASC' === $order ) {
				switch ( $orderby ) {
					case 'from_id':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY from_id ASC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'to_id':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY to_id ASC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'type':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY type ASC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'direction':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY direction ASC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'created_at':
					default:
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY created_at ASC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
				}
			} else {
				switch ( $orderby ) {
					case 'from_id':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY from_id DESC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'to_id':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY to_id DESC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'type':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY type DESC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'direction':
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY direction DESC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
					case 'created_at':
					default:
						$items = $wpdb->get_results( $wpdb->prepare(
							"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY created_at DESC LIMIT %d OFFSET %d",
							$per_page,
							$offset
						) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query with manual caching
						break;
				}
			}
			
			// Cache the result for 5 minutes
			wp_cache_set( $items_cache_key, $items, 'naticore_relationships', 5 * MINUTE_IN_SECONDS );
		}

		$this->items = $items;
		$this->set_pagination_args(
			array(
				'total_items' => (int) $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Column: From
	 */
	protected function column_from( $item ) {
		$post = get_post( $item->from_id );
		if ( ! $post ) {
			return '<em>' . __( 'Deleted', 'native-content-relationships' ) . '</em>';
		}

		$edit_link = get_edit_post_link( $item->from_id );
		return sprintf(
			'<a href="%s">%s</a> <small>(%s)</small>',
			esc_url( $edit_link ),
			esc_html( get_the_title( $item->from_id ) ),
			esc_html( get_post_type_object( $post->post_type )->labels->singular_name )
		);
	}

	/**
	 * Column: Type
	 */
	protected function column_type( $item ) {
		$type_info = NATICORE_Relation_Types::get_type( $item->type );
		$label     = $type_info ? $type_info['label'] : $item->type;
		return '<code>' . esc_html( $item->type ) . '</code><br><small>' . esc_html( $label ) . '</small>';
	}

	/**
	 * Column: To
	 */
	protected function column_to( $item ) {
		$post = get_post( $item->to_id );
		if ( ! $post ) {
			return '<em>' . __( 'Deleted', 'native-content-relationships' ) . '</em>';
		}

		$edit_link = get_edit_post_link( $item->to_id );
		return sprintf(
			'<a href="%s">%s</a> <small>(%s)</small>',
			esc_url( $edit_link ),
			esc_html( get_the_title( $item->to_id ) ),
			esc_html( get_post_type_object( $post->post_type )->labels->singular_name )
		);
	}

	/**
	 * Column: Direction
	 */
	protected function column_direction( $item ) {
		if ( $item->direction === 'bidirectional' ) {
			return '↔ ' . __( 'Bidirectional', 'native-content-relationships' );
		}
		return '→ ' . __( 'One-way', 'native-content-relationships' );
	}

	/**
	 * Column: Date
	 */
	protected function column_date( $item ) {
		return mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->created_at );
	}

	/**
	 * Default column
	 */
	protected function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * No items message
	 */
	public function no_items() {
		esc_html_e( 'No relationships found.', 'native-content-relationships' );
	}
}

class NATICORE_Overview {

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

		add_action( 'admin_menu', array( $this, 'add_overview_page' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Add overview page
	 */
	public function add_overview_page() {
		$hook = add_management_page(
			__( 'Content Relationships', 'native-content-relationships' ),
			__( 'Content Relationships', 'native-content-relationships' ),
			'manage_options',
			'naticore-overview',
			array( $this, 'render_overview_page' )
		);

		add_action( "load-$hook", array( $this, 'add_screen_options' ) );
	}

	/**
	 * Add screen options
	 */
	public function add_screen_options() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Relationships per page', 'native-content-relationships' ),
				'default' => 20,
				'option'  => 'relationships_per_page',
			)
		);
	}

	/**
	 * Set screen option
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'relationships_per_page' === $option ) {
			return $value;
		}
		return $status;
	}

	/**
	 * Render overview page
	 */
	public function render_overview_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$table = new NATICORE_Overview_Table();
		$table->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="description" id="naticore-overview-desc">
				<?php esc_html_e( 'Overview of all content relationships. To edit relationships, use the "Related Content" meta box on individual posts.', 'native-content-relationships' ); ?>
			</p>
			
			<form method="get" aria-labelledby="naticore-overview-desc">
				<input type="hidden" name="page" value="naticore-overview" />
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}

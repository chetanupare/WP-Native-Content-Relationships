<?php
/**
 * Relationship Graph Visualization
 *
 * Displays a visual map of how content is connected.
 *
 * @package Native Content Relationships
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Graph
 *
 * Provides a visual graph of content relationships.
 */
class NATICORE_Graph {

	/**
	 * Instance
	 *
	 * @var NATICORE_Graph|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return NATICORE_Graph
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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_naticore_get_graph_data', array( $this, 'ajax_get_graph_data' ) );
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=naticore_relation',
			__( 'Relationship Graph', 'native-content-relationships' ),
			__( 'Graph', 'native-content-relationships' ),
			'manage_options',
			'naticore-graph',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'naticore_page_naticore-graph' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'naticore-graph',
			NATICORE_PLUGIN_URL . 'assets/js/graph.js',
			array( 'jquery' ),
			NATICORE_VERSION,
			true
		);

		wp_localize_script(
			'naticore-graph',
			'naticoreGraph',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'naticore_graph_nonce' ),
				'i18n'    => array(
					'loading'  => __( 'Loading graph...', 'native-content-relationships' ),
					'noData'   => __( 'No relationships found.', 'native-content-relationships' ),
					'error'    => __( 'Error loading graph.', 'native-content-relationships' ),
					'related'  => __( 'Related', 'native-content-relationships' ),
					'posts'    => __( 'Posts', 'native-content-relationships' ),
					'users'    => __( 'Users', 'native-content-relationships' ),
					'terms'    => __( 'Terms', 'native-content-relationships' ),
				),
			)
		);

		wp_enqueue_style(
			'naticore-graph',
			NATICORE_PLUGIN_URL . 'assets/css/graph.css',
			array(),
			NATICORE_VERSION
		);
	}

	/**
	 * Render admin page
	 */
	public function render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Relationship Graph', 'native-content-relationships' ); ?></h1>
			<p><?php esc_html_e( 'Visual map of how your content is connected.', 'native-content-relationships' ); ?></p>
			
			<div class="naticore-graph-controls" style="margin: 20px 0;">
				<label>
					<?php esc_html_e( 'Filter by type:', 'native-content-relationships' ); ?>
					<select id="naticore-graph-filter">
						<option value="all"><?php esc_html_e( 'All Types', 'native-content-relationships' ); ?></option>
						<option value="post"><?php esc_html_e( 'Posts', 'native-content-relationships' ); ?></option>
						<option value="page"><?php esc_html_e( 'Pages', 'native-content-relationships' ); ?></option>
						<option value="user"><?php esc_html_e( 'Users', 'native-content-relationships' ); ?></option>
					</select>
				</label>
				<label style="margin-left: 20px;">
					<?php esc_html_e( 'Max nodes:', 'native-content-relationships' ); ?>
					<select id="naticore-graph-limit">
						<option value="20">20</option>
						<option value="50" selected>50</option>
						<option value="100">100</option>
						<option value="200">200</option>
					</select>
				</label>
				<button type="button" id="naticore-graph-refresh" class="button" style="margin-left: 20px;">
					<?php esc_html_e( 'Refresh', 'native-content-relationships' ); ?>
				</button>
			</div>

			<div id="naticore-graph-container" style="background: #fff; border: 1px solid #dcdcde; border-radius: 4px; min-height: 500px; position: relative;">
				<div id="naticore-graph-loading" style="text-align: center; padding: 40px;">
					<span class="spinner is-active"></span>
					<p><?php esc_html_e( 'Loading graph...', 'native-content-relationships' ); ?></p>
				</div>
				<canvas id="naticore-graph-canvas" style="display: none;"></canvas>
			</div>

			<div id="naticore-graph-legend" style="margin-top: 20px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
				<strong><?php esc_html_e( 'Legend:', 'native-content-relationships' ); ?></strong>
				<span style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #2271b1; border-radius: 50%; margin-right: 5px;"></span> <?php esc_html_e( 'Posts', 'native-content-relationships' ); ?></span>
				<span style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #00a32a; border-radius: 50%; margin-right: 5px;"></span> <?php esc_html_e( 'Users', 'native-content-relationships' ); ?></span>
				<span style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #dba617; border-radius: 50%; margin-right: 5px;"></span> <?php esc_html_e( 'Terms', 'native-content-relationships' ); ?></span>
				<span style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #d63638; border-radius: 50%; margin-right: 5px;"></span> <?php esc_html_e( 'Pages', 'native-content-relationships' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Get graph data for visualization
	 *
	 * @param string $type  Filter by post type.
	 * @param int    $limit Max nodes.
	 * @return array Graph data with nodes and edges.
	 */
	public function get_graph_data( $type = 'all', $limit = 50 ) {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'content_relations';
		$meta_table_name = $wpdb->prefix . 'content_relationmeta';

		// Get relationships
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d",
				$limit * 2
			)
		);

		if ( empty( $results ) ) {
			return array( 'nodes' => array(), 'edges' => array() );
		}

		$node_ids = array();
		$nodes    = array();
		$edges    = array();

		foreach ( $results as $rel ) {
			$from_id = (int) $rel->from_id;
			$to_id   = (int) $rel->to_id;

			// Get post objects
			$from_post = get_post( $from_id );
			$to_post   = get_post( $to_id );

			if ( ! $from_post || ! $to_post ) {
				continue;
			}

			// Filter by type
			if ( 'all' !== $type ) {
				if ( $from_post->post_type !== $type && $to_post->post_type !== $type ) {
					continue;
				}
			}

			// Add nodes
			if ( ! isset( $node_ids[ $from_id ] ) ) {
				$node_ids[ $from_id ] = true;
				$nodes[]              = array(
					'id'    => $from_id,
					'label' => $this->get_node_label( $from_post ),
					'type'  => $from_post->post_type,
					'title' => $from_post->post_title,
					'url'   => get_edit_post_link( $from_id ),
				);
			}

			if ( ! isset( $node_ids[ $to_id ] ) ) {
				$node_ids[ $to_id ] = true;
				$nodes[]            = array(
					'id'    => $to_id,
					'label' => $this->get_node_label( $to_post ),
					'type'  => $to_post->post_type,
					'title' => $to_post->post_title,
					'url'   => get_edit_post_link( $to_id ),
				);
			}

			// Add edge
			$edges[] = array(
				'from'  => $from_id,
				'to'    => $to_id,
				'type'  => $rel->type,
				'label' => $rel->type,
			);
		}

		return array(
			'nodes' => array_slice( $nodes, 0, $limit ),
			'edges' => $edges,
		);
	}

	/**
	 * Get node label
	 *
	 * @param WP_Post $post Post object.
	 * @return string Node label.
	 */
	private function get_node_label( $post ) {
		$label = $post->post_title;
		if ( strlen( $label ) > 30 ) {
			$label = substr( $label, 0, 27 ) . '...';
		}
		return $label;
	}

	/**
	 * AJAX handler for getting graph data
	 */
	public function ajax_get_graph_data() {
		check_ajax_referer( 'naticore_graph_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'all';
		$limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50;

		$data = $this->get_graph_data( $type, $limit );

		wp_send_json_success( $data );
	}
}

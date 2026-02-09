<?php
/**
 * Admin UI for managing relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Admin {

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

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_relationships' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_naticore_search_content', array( $this, 'ajax_search_content' ) );
		add_action( 'wp_ajax_naticore_search_products', array( $this, 'ajax_search_products' ) );
		add_action( 'wp_ajax_naticore_add_relation', array( $this, 'ajax_add_relation' ) );
		add_action( 'wp_ajax_naticore_remove_relation', array( $this, 'ajax_remove_relation' ) );
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		$settings           = NATICORE_Settings::get_instance();
		$enabled_post_types = $settings->get_setting( 'enabled_post_types', array( 'post', 'page' ) );

		// If empty, show on all public post types (backward compatibility)
		if ( empty( $enabled_post_types ) ) {
			$enabled_post_types = array_keys( get_post_types( array( 'public' => true ) ) );
		}

		foreach ( $enabled_post_types as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				add_meta_box(
					'naticore_related_content',
					__( 'Related Content', 'native-content-relationships' ),
					array( $this, 'render_meta_box' ),
					$post_type,
					'normal',
					'default'
				);
			}
		}
	}

	/**
	 * Render meta box
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'naticore_save_relationships', 'naticore_nonce' );

		// Get existing relationships
		$relationships = NATICORE_API::get_all_relations( $post->ID );

		// Group by relation type
		$grouped = array();
		foreach ( $relationships as $rel ) {
			if ( ! isset( $grouped[ $rel->type ] ) ) {
				$grouped[ $rel->type ] = array();
			}
			$grouped[ $rel->type ][] = $rel;
		}

		// Get registered relation types
		$relation_types = NATICORE_Relation_Types::get_types();

		?>
		<div id="naticore-relationships">
			<div class="naticore-relation-types">
				<?php
				foreach ( $relation_types as $type => $type_info ) :
					$type_label = isset( $type_info['label'] ) ? $type_info['label'] : ucwords( str_replace( '_', ' ', $type ) );
					?>
					<div class="naticore-relation-type" data-type="<?php echo esc_attr( $type ); ?>">
						<h4><?php echo esc_html( $type_label ); ?></h4>
						<div class="naticore-relations-list" data-relation-type="<?php echo esc_attr( $type ); ?>">
							<?php if ( isset( $grouped[ $type ] ) ) : ?>
								<?php
								foreach ( $grouped[ $type ] as $rel ) :
									$related_post = get_post( $rel->to_id );
									if ( ! $related_post ) {
										continue;
									}

									$rel_type_info    = NATICORE_Relation_Types::get_type( $type );
									$is_bidirectional = $rel_type_info && $rel_type_info['bidirectional'];
									?>
									<div class="naticore-relation-item" data-related-id="<?php echo esc_attr( $rel->to_id ); ?>">
										<span class="naticore-relation-title">
											<span class="naticore-direction-indicator" title="<?php echo esc_attr( $is_bidirectional ? __( 'Bidirectional', 'native-content-relationships' ) : __( 'One-way', 'native-content-relationships' ) ); ?>">
												<?php echo esc_html( $is_bidirectional ? '↔' : '→' ); ?>
											</span>
											<a href="<?php echo esc_url( get_edit_post_link( $rel->to_id ) ); ?>" target="_blank">
												<?php echo esc_html( get_the_title( $rel->to_id ) ); ?>
											</a>
											<small>(<?php echo esc_html( get_post_type_object( $related_post->post_type )->labels->singular_name ); ?>)</small>
										</span>
										<button 
											type="button" 
											class="button naticore-remove-relation" 
											data-from-id="<?php echo esc_attr( $post->ID ); ?>" 
											data-to-id="<?php echo esc_attr( $rel->to_id ); ?>" 
											data-relation-type="<?php echo esc_attr( $type ); ?>"
											<?php
											/* translators: %s: Post title */
											$remove_label = sprintf( esc_attr__( 'Remove relationship to %s', 'native-content-relationships' ), esc_attr( get_the_title( $rel->to_id ) ) );
											?>
											aria-label="<?php echo esc_attr( $remove_label ); ?>"
										>
											<?php esc_html_e( 'Remove', 'native-content-relationships' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div class="naticore-add-relation">
							<label for="naticore-search-<?php echo esc_attr( $type ); ?>" class="screen-reader-text">
								<?php
								/* translators: %s: Relationship type label */
								printf( esc_html__( 'Search content to add as %s', 'native-content-relationships' ), esc_html( $type_label ) );
								?>
							</label>
							<input 
								type="text" 
								id="naticore-search-<?php echo esc_attr( $type ); ?>"
								class="naticore-search-input" 
								placeholder="<?php esc_attr_e( 'Search content...', 'native-content-relationships' ); ?>" 
								data-relation-type="<?php echo esc_attr( $type ); ?>"
								aria-describedby="naticore-search-desc-<?php echo esc_attr( $type ); ?>"
							/>
							<p id="naticore-search-desc-<?php echo esc_attr( $type ); ?>" class="screen-reader-text">
								<?php
								/* translators: %s: Relationship type label */
								printf( esc_html__( 'Type to search for content to relate with type %s', 'native-content-relationships' ), esc_html( $type_label ) );
								?>
							</p>
							<div class="naticore-search-results" style="display: none;" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'native-content-relationships' ); ?>"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_script(
			'naticore-admin',
			NATICORE_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-autocomplete' ),
			NATICORE_VERSION,
			true
		);

		wp_enqueue_style(
			'naticore-admin',
			NATICORE_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			NATICORE_VERSION
		);

		wp_localize_script(
			'naticore-admin',
			'naticoreData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'naticore_ajax' ),
				'strings' => array(
					'searching' => __( 'Searching...', 'native-content-relationships' ),
					'noResults' => __( 'No results found.', 'native-content-relationships' ),
				),
			)
		);
	}

	/**
	 * AJAX: Search products (WooCommerce)
	 */
	public function ajax_search_products() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$search          = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;

		if ( empty( $search ) ) {
			wp_send_json_error( array( 'message' => __( 'Search term required.', 'native-content-relationships' ) ) );
		}

		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce is not active.', 'native-content-relationships' ) ) );
		}

		// Build search query
		global $wpdb;
		$search_term = '%' . $wpdb->esc_like( $search ) . '%';

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Necessary for excluding current post
			'post__not_in'   => array( $current_post_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary for SKU search
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_sku',
					'value'   => $search,
					'compare' => 'LIKE',
				),
			),
		);

		// Add title search via posts_where
		$search_filter = function ( $where ) use ( $search_term ) {
			global $wpdb;
			$where .= $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_content LIKE %s)", $search_term, $search_term );
			return $where;
		};

		add_filter( 'posts_where', $search_filter );

		$query = new WP_Query( $args );

		// Remove filter after query safely
		remove_filter( 'posts_where', $search_filter );

		$results = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product   = wc_get_product( get_the_ID() );
				$results[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'sku'   => $product ? $product->get_sku() : '',
					'type'  => 'product',
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Search content
	 */
	public function ajax_search_content() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$search          = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;

		if ( empty( $search ) ) {
			wp_send_json_error( array( 'message' => __( 'Search term required.', 'native-content-relationships' ) ) );
		}

		$args = array(
			'post_type'      => get_post_types( array( 'public' => true ) ),
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			's'              => $search,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Necessary for excluding current post
			'post__not_in'   => array( $current_post_id ),
		);

		$query = new WP_Query( $args );

		$results = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$results[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
					'type'  => get_post_type(),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $results );
	}

	/**
	 * Save relationships
	 */
	public function save_relationships( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['naticore_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['naticore_nonce'] ) ), 'naticore_save_relationships' ) ) {
			return;
		}

		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Relationships are managed via AJAX, so we don't need to save here
		// This is kept for future use if needed
	}

	/**
	 * AJAX: Add relation
	 */
	public function ajax_add_relation() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$from_id       = isset( $_POST['from_id'] ) ? absint( $_POST['from_id'] ) : 0;
		$to_id         = isset( $_POST['to_id'] ) ? absint( $_POST['to_id'] ) : 0;
		$relation_type = isset( $_POST['relation_type'] ) ? sanitize_text_field( wp_unslash( $_POST['relation_type'] ) ) : 'related_to';

		if ( ! $from_id || ! $to_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'native-content-relationships' ) ) );
		}

		$result = NATICORE_API::add_relation( $from_id, $to_id, $relation_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'relation_id' => $result ) );
	}

	/**
	 * AJAX: Remove relation
	 */
	public function ajax_remove_relation() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$from_id       = isset( $_POST['from_id'] ) ? absint( $_POST['from_id'] ) : 0;
		$to_id         = isset( $_POST['to_id'] ) ? absint( $_POST['to_id'] ) : 0;
		$relation_type = isset( $_POST['relation_type'] ) ? sanitize_text_field( wp_unslash( $_POST['relation_type'] ) ) : null;

		if ( ! $from_id || ! $to_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'native-content-relationships' ) ) );
		}

		$result = NATICORE_API::remove_relation( $from_id, $to_id, $relation_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}
}

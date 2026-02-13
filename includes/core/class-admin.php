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
		add_action( 'wp_ajax_naticore_suggest_related', array( $this, 'ajax_suggest_related' ) );
		add_action( 'wp_ajax_naticore_add_relation', array( $this, 'ajax_add_relation' ) );
		add_action( 'wp_ajax_naticore_remove_relation', array( $this, 'ajax_remove_relation' ) );

		add_action( 'admin_notices', array( $this, 'render_activation_notice' ) );
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

		$settings             = NATICORE_Settings::get_instance();
		$manual_order_enabled = $settings->get_setting( 'enable_manual_order', 0 );

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
		<div id="naticore-relationships" data-manual-order="<?php echo $manual_order_enabled ? '1' : '0'; ?>">
			<div class="naticore-relation-types">
				<?php
				foreach ( $relation_types as $type => $type_info ) :
					$type_label = isset( $type_info['label'] ) ? $type_info['label'] : ucwords( str_replace( '_', ' ', $type ) );
					$list_class = 'naticore-relations-list';
					if ( $manual_order_enabled ) {
						$list_class .= ' naticore-sortable';
					}
					?>
					<div class="naticore-relation-type" data-type="<?php echo esc_attr( $type ); ?>">
						<h4><?php echo esc_html( $type_label ); ?></h4>
						<div class="<?php echo esc_attr( $list_class ); ?>" data-relation-type="<?php echo esc_attr( $type ); ?>">
							<?php if ( isset( $grouped[ $type ] ) ) : ?>
								<?php
								foreach ( $grouped[ $type ] as $rel ) :
									$related_post = get_post( $rel->to_id );
									if ( ! $related_post ) {
										continue;
									}

									$rel_type_info    = NATICORE_Relation_Types::get_type( $type );
									$is_bidirectional = $rel_type_info && $rel_type_info['bidirectional'];
									$item_attrs       = 'class="naticore-relation-item" data-related-id="' . esc_attr( $rel->to_id ) . '"';
									if ( $manual_order_enabled && ! empty( $rel->id ) ) {
										$item_attrs .= ' data-relation-id="' . esc_attr( $rel->id ) . '"';
									}
									?>
									<div <?php echo $item_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr() above. ?>>
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
						<?php if ( $manual_order_enabled ) : ?>
							<input type="hidden" name="naticore_relation_order[<?php echo esc_attr( $type ); ?>]" class="naticore-order-input" value="" />
						<?php endif; ?>
						<div class="naticore-add-relation">
							<p class="naticore-suggest-actions">
								<button type="button" class="button button-secondary naticore-suggest-btn" data-relation-type="<?php echo esc_attr( $type ); ?>">
									<?php esc_html_e( 'Suggest related', 'native-content-relationships' ); ?>
								</button>
							</p>
							<div class="naticore-suggest-results" style="display: none;" role="listbox" aria-label="<?php esc_attr_e( 'Suggested related content', 'native-content-relationships' ); ?>"></div>
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

		$settings             = NATICORE_Settings::get_instance();
		$manual_order_enabled = $settings->get_setting( 'enable_manual_order', 0 );

		$deps = array( 'jquery', 'jquery-ui-autocomplete' );
		if ( $manual_order_enabled ) {
			$deps[] = 'jquery-ui-sortable';
		}

		wp_enqueue_script(
			'naticore-admin',
			NATICORE_PLUGIN_URL . 'assets/js/admin.js',
			$deps,
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
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'naticore_ajax' ),
				'manualOrderEnabled' => $manual_order_enabled,
				'strings'            => array(
					'searching'     => __( 'Searching...', 'native-content-relationships' ),
					'noResults'     => __( 'No results found.', 'native-content-relationships' ),
					'suggesting'    => __( 'Suggesting...', 'native-content-relationships' ),
					'noSuggestions' => __( 'No suggestions (same category/tag or type).', 'native-content-relationships' ),
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
	 * AJAX: Suggest related posts by same category, tag, or post type.
	 * Kept cheap: limit 10, no heavy queries.
	 */
	public function ajax_suggest_related() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;
		if ( ! $current_post_id || ! get_post( $current_post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'native-content-relationships' ) ) );
		}

		$post    = get_post( $current_post_id );
		$exclude = array( $current_post_id );

		// Already related post IDs (exclude so we don't suggest them again)
		$relations = NATICORE_API::get_all_relations( $current_post_id );
		foreach ( $relations as $rel ) {
			if ( ! empty( $rel->to_id ) ) {
				$exclude[] = (int) $rel->to_id;
			}
		}
		$exclude = array_unique( array_filter( $exclude ) );

		$tax_query = array();
		$terms_cat = get_the_terms( $current_post_id, 'category' );
		$terms_tag = get_the_terms( $current_post_id, 'post_tag' );
		if ( $terms_cat && ! is_wp_error( $terms_cat ) ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $terms_cat, 'term_id' ),
			);
		}
		if ( $terms_tag && ! is_wp_error( $terms_tag ) ) {
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $terms_tag, 'term_id' ),
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'OR';
		}

		$args = array(
			'post_type'      => $post->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'post__not_in'   => $exclude,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$query   = new WP_Query( $args );
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

		$settings             = NATICORE_Settings::get_instance();
		$manual_order_enabled = $settings->get_setting( 'enable_manual_order', 0 );
		if ( ! $manual_order_enabled ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked above; values sanitized in loop (sanitize_key, absint).
		$order_data = isset( $_POST['naticore_relation_order'] ) && is_array( $_POST['naticore_relation_order'] ) ? wp_unslash( $_POST['naticore_relation_order'] ) : array();
		if ( empty( $order_data ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'content_relations';

		foreach ( $order_data as $type => $order_string ) {
			$type        = sanitize_key( $type );
			$ids         = array_map( 'absint', array_filter( explode( ',', $order_string ) ) );
			$post_id_int = absint( $post_id );

			foreach ( $ids as $position => $relation_id ) {
				if ( ! $relation_id ) {
					continue;
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from prefix; update order for this relation.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE `{$table}` SET relation_order = %d WHERE id = %d AND from_id = %d AND type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from prefix.
						$position,
						$relation_id,
						$post_id_int,
						$type
					)
				);
			}
		}
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

	/**
	 * Render activation notice
	 */
	public function render_activation_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if we should show the notice
		if ( ! get_transient( 'naticore_activation_notice' ) ) {
			return;
		}

		// Delete transient so it only shows once
		delete_transient( 'naticore_activation_notice' );

		$settings_url    = admin_url( 'options-general.php?page=naticore-settings' );
		$get_started_url = admin_url( 'options-general.php?page=naticore-settings&tab=get_started' );
		$docs_url        = 'https://chetanupare.github.io/WP-Native-Content-Relationships/';
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Native Content Relationships is active!', 'native-content-relationships' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Get started URL, 2: Settings URL, 3: Documentation URL */
					wp_kses_post( __( ' <a href="%1$s">Get started</a> with the quick setup checklist, <a href="%2$s">visit settings</a>, or <a href="%3$s" target="_blank">read the documentation</a>.', 'native-content-relationships' ) ),
					esc_url( $get_started_url ),
					esc_url( $settings_url ),
					esc_url( $docs_url )
				);
				?>
			</p>
		</div>
		<?php
	}
}

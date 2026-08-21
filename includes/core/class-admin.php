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
		add_action( 'wp_ajax_naticore_save_relation_meta', array( $this, 'ajax_save_relation_meta' ) );

		// Stitch Admin AJAX handlers.
		add_action( 'wp_ajax_naticore_save_type', array( $this, 'ajax_save_type' ) );
		add_action( 'wp_ajax_naticore_delete_relation', array( $this, 'ajax_delete_relation' ) );
		add_action( 'wp_ajax_naticore_get_relation', array( $this, 'ajax_get_relation' ) );

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
				// Suppress classic metabox if Gutenberg sidebar will be shown.
				$pt_obj = get_post_type_object( $post_type );
				if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $post_type ) && $pt_obj && $pt_obj->show_in_rest ) {
					continue;
				}

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

									// Get relationship metadata
									$meta_role  = '';
									$meta_note  = '';
									$meta_order = '';
									if ( ! empty( $rel->id ) && class_exists( 'NATICORE_Meta_API' ) ) {
										$meta_role  = NATICORE_Meta_API::get_meta( $rel->id, 'role' );
										$meta_note  = NATICORE_Meta_API::get_meta( $rel->id, 'note' );
										$meta_order = NATICORE_Meta_API::get_meta( $rel->id, 'order' );
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
										<span class="naticore-relation-meta">
											<input type="text" class="naticore-meta-role" placeholder="<?php esc_attr_e( 'Role (e.g. Speaker)', 'native-content-relationships' ); ?>" value="<?php echo esc_attr( $meta_role ); ?>" data-relation-id="<?php echo esc_attr( $rel->id ?? '' ); ?>" data-meta-key="role" style="width: 140px; font-size: 12px; margin-left: 8px;">
											<input type="text" class="naticore-meta-note" placeholder="<?php esc_attr_e( 'Note', 'native-content-relationships' ); ?>" value="<?php echo esc_attr( $meta_note ); ?>" data-relation-id="<?php echo esc_attr( $rel->id ?? '' ); ?>" data-meta-key="note" style="width: 120px; font-size: 12px; margin-left: 4px;">
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

		$deps = array( 'jquery' );
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
	/**
	 * AJAX: Search WooCommerce products.
	 *
	 * Delegates to NATICORE_Object_Search::search_products().
	 * Response format is unchanged for backward compatibility.
	 */
	public function ajax_search_products() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;
		$capability      = $current_post_id ? 'edit_post' : 'edit_posts';
		$cap_target      = $current_post_id ? $current_post_id : null;

		if ( ! current_user_can( $capability, $cap_target ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$search = isset( $_POST['search'] ) ? wp_unslash( $_POST['search'] ) : '';

		$service = new NATICORE_Object_Search();
		$items   = $service->search_products(
			$search,
			array(
				'exclude_ids' => $current_post_id ? array( $current_post_id ) : array(),
			)
		);

		if ( is_wp_error( $items ) ) {
			wp_send_json_error( array( 'message' => $items->get_error_message() ) );
		}

		// Map normalized results to the legacy response shape expected by admin.js.
		$results = array();
		foreach ( $items as $item ) {
			$sku       = '';
			if ( ! empty( $item['secondary_label'] ) && 0 === strpos( $item['secondary_label'], 'SKU: ' ) ) {
				$sku = substr( $item['secondary_label'], 5 );
			}
			$results[] = array(
				'id'    => $item['id'],
				'title' => $item['title'],
				'sku'   => $sku,
				'type'  => 'product',
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Search content (posts).
	 *
	 * Delegates to NATICORE_Object_Search::search_posts().
	 * Response format is unchanged for backward compatibility.
	 */
	public function ajax_search_content() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;
		$capability      = $current_post_id ? 'edit_post' : 'edit_posts';
		$cap_target      = $current_post_id ? $current_post_id : null;

		if ( ! current_user_can( $capability, $cap_target ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$search = isset( $_POST['search'] ) ? wp_unslash( $_POST['search'] ) : '';

		$service = new NATICORE_Object_Search();
		$items   = $service->search_posts(
			$search,
			array(
				'exclude_ids' => $current_post_id ? array( $current_post_id ) : array(),
			)
		);

		if ( is_wp_error( $items ) ) {
			wp_send_json_error( array( 'message' => $items->get_error_message() ) );
		}

		// Map normalized results to the legacy response shape expected by admin.js.
		$results = array();
		foreach ( $items as $item ) {
			$results[] = array(
				'id'        => $item['id'],
				'title'     => $item['title'],
				'type'      => $item['object_type'],
				'thumbnail' => $item['thumbnail_url'] ?? '',
				'url'       => $item['edit_url'] ?? '',
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Suggest related posts using AI or fallback to category/tag matching.
	 */
	public function ajax_suggest_related() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$current_post_id = isset( $_POST['current_post_id'] ) ? absint( $_POST['current_post_id'] ) : 0;
		$capability      = $current_post_id ? 'edit_post' : 'edit_posts';
		$cap_target      = $current_post_id ? $current_post_id : null;

		if ( ! current_user_can( $capability, $cap_target ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}
		if ( ! $current_post_id || ! get_post( $current_post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post.', 'native-content-relationships' ) ) );
		}

		// Use AI suggestions if available
		if ( class_exists( 'NATICORE_AI_Suggestions' ) ) {
			$ai       = NATICORE_AI_Suggestions::get_instance();
			$results  = $ai->get_suggestions( $current_post_id, 10 );
			$source   = $ai->is_enabled() ? 'ai' : 'fallback';
		} else {
			// Fallback to category/tag suggestions
			$results = $this->get_fallback_suggestions( $current_post_id, 10 );
			$source  = 'fallback';
		}

		wp_send_json_success( $results );
	}

	/**
	 * Get fallback suggestions based on categories and tags
	 *
	 * @param int $post_id Post ID.
	 * @param int $limit   Number of suggestions.
	 * @return array Suggestions array.
	 */
	private function get_fallback_suggestions( $post_id, $limit = 10 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$exclude = array( $post_id );

		// Already related post IDs
		$relations = NATICORE_API::get_all_relations( $post_id );
		foreach ( $relations as $rel ) {
			if ( ! empty( $rel->to_id ) ) {
				$exclude[] = (int) $rel->to_id;
			}
		}
		$exclude = array_unique( array_filter( $exclude ) );

		$tax_query = array();
		$terms_cat = get_the_terms( $post_id, 'category' );
		$terms_tag = get_the_terms( $post_id, 'post_tag' );
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
			'posts_per_page' => $limit,
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
				$thumbnail_url = '';
				$thumbnail_id  = get_post_thumbnail_id( get_the_ID() );
				if ( $thumbnail_id ) {
					$thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
					if ( $thumbnail ) {
						$thumbnail_url = $thumbnail[0];
					}
				}
				$results[] = array(
					'id'        => get_the_ID(),
					'title'     => get_the_title(),
					'type'      => get_post_type(),
					'thumbnail' => $thumbnail_url,
					'url'       => get_the_permalink(),
					'source'    => 'fallback',
				);
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Save relationships
	 */
	public function save_relationships( $post_id, $post ) {
		// Verify nonce
		if ( ! isset( $_POST['naticore_nonce'] ) || ! hash_equals( wp_create_nonce( 'naticore_save_relationships' ), sanitize_text_field( wp_unslash( $_POST['naticore_nonce'] ) ) ) ) {
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

		if ( ! current_user_can( 'edit_post', $from_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this content.', 'native-content-relationships' ) ) );
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

		if ( ! current_user_can( 'edit_post', $from_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this content.', 'native-content-relationships' ) ) );
		}

		$result = NATICORE_API::remove_relation( $from_id, $to_id, $relation_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Save relationship metadata
	 */
	public function ajax_save_relation_meta() {
		check_ajax_referer( 'naticore_ajax', 'nonce' );

		$relation_id = isset( $_POST['relation_id'] ) ? absint( $_POST['relation_id'] ) : 0;
		$meta_key    = isset( $_POST['meta_key'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_key'] ) ) : '';
		$meta_value  = isset( $_POST['meta_value'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_value'] ) ) : '';

		if ( ! $relation_id || empty( $meta_key ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'native-content-relationships' ) ) );
		}

		// Verify the user can edit the source post of this relationship.
		global $wpdb;
		$table  = $wpdb->prefix . 'content_relations';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$from_id = $wpdb->get_var( $wpdb->prepare( "SELECT from_id FROM `{$table}` WHERE id = %d", $relation_id ) );
		if ( ! $from_id || ! current_user_can( 'edit_post', (int) $from_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit this content.', 'native-content-relationships' ) ) );
		}

		// Sanitize meta key - only allow alphanumeric and underscores
		$meta_key = preg_replace( '/[^a-z0-9_]/', '', strtolower( $meta_key ) );

		if ( class_exists( 'NATICORE_Meta_API' ) ) {
			$result = NATICORE_Meta_API::update_meta( $relation_id, $meta_key, $meta_value );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Save (create or update) a relationship type.
	 *
	 * Built-in types are toggled via settings. Custom types are stored in settings.
	 */
	public function ajax_save_type() {
		check_ajax_referer( 'nc_stitch_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$bidir = isset( $_POST['bidirectional'] ) ? absint( $_POST['bidirectional'] ) : 0;
		$from  = isset( $_POST['from_type'] ) ? sanitize_text_field( wp_unslash( $_POST['from_type'] ) ) : 'post';
		$to    = isset( $_POST['to_type'] ) ? sanitize_text_field( wp_unslash( $_POST['to_type'] ) ) : 'post';

		if ( empty( $slug ) || empty( $label ) ) {
			wp_send_json_error( array( 'message' => __( 'Slug and label are required.', 'native-content-relationships' ) ) );
		}

		$settings    = get_option( 'naticore_settings', array() );
		$type_config = isset( $settings['relationship_types_config'] ) ? $settings['relationship_types_config'] : array();

		// Check if this is a built-in type being toggled.
		$reflection         = new ReflectionClass( 'NATICORE_Relation_Types' );
		$default_types_prop = $reflection->getProperty( 'default_types' );
		$default_types_prop->setAccessible( true );
		$built_in_defaults  = $default_types_prop->getValue();

		if ( isset( $built_in_defaults[ $slug ] ) ) {
			// Built-in type: toggle enabled/disabled.
			if ( ! isset( $type_config['built_in'] ) ) {
				$type_config['built_in'] = array();
			}
			$type_config['built_in'][ $slug ] = array(
				'enabled' => 1,
			);
		} else {
			// Custom type: create or update.
			if ( ! isset( $type_config['custom'] ) ) {
				$type_config['custom'] = array();
			}
			$type_config['custom'][ $slug ] = array(
				'label'         => $label,
				'bidirectional' => $bidir,
				'from_type'     => $from,
				'to_type'       => $to,
			);
		}

		$settings['relationship_types_config'] = $type_config;
		update_option( 'naticore_settings', $settings );

		wp_send_json_success( array( 'message' => __( 'Type saved.', 'native-content-relationships' ) ) );
	}

	/**
	 * AJAX: Delete a relationship by ID.
	 */
	public function ajax_delete_relation() {
		check_ajax_referer( 'nc_stitch_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$relation_id = isset( $_POST['relation_id'] ) ? absint( $_POST['relation_id'] ) : 0;

		if ( ! $relation_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid relationship ID.', 'native-content-relationships' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'content_relations';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$relation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $relation_id ) );

		if ( ! $relation ) {
			wp_send_json_error( array( 'message' => __( 'Relationship not found.', 'native-content-relationships' ) ) );
		}

		if ( ! current_user_can( 'edit_post', (int) $relation->from_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$result = NATICORE_API::remove_relation( (int) $relation->from_id, (int) $relation->to_id, $relation->type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Relationship deleted.', 'native-content-relationships' ) ) );
	}

	/**
	 * AJAX: Get a single relationship by ID (for editing).
	 */
	public function ajax_get_relation() {
		check_ajax_referer( 'nc_stitch_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$relation_id = isset( $_GET['relation_id'] ) ? absint( $_GET['relation_id'] ) : 0;

		if ( ! $relation_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid relationship ID.', 'native-content-relationships' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'content_relations';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$relation = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $relation_id ) );

		if ( ! $relation ) {
			wp_send_json_error( array( 'message' => __( 'Relationship not found.', 'native-content-relationships' ) ) );
		}

		// Get titles.
		$from_title = get_the_title( (int) $relation->from_id );
		$to_title   = '';
		if ( 'post' === $relation->to_type ) {
			$to_title = get_the_title( (int) $relation->to_id );
		} elseif ( 'user' === $relation->to_type ) {
			$user = get_userdata( (int) $relation->to_id );
			$to_title = $user ? $user->display_name : '';
		}

		wp_send_json_success( array(
			'id'         => (int) $relation->id,
			'from_id'    => (int) $relation->from_id,
			'to_id'      => (int) $relation->to_id,
			'type'       => $relation->type,
			'from_type'  => $relation->from_type ?? 'post',
			'to_type'    => $relation->to_type ?? 'post',
			'from_title' => $from_title ?: __( '(unknown)', 'native-content-relationships' ),
			'to_title'   => $to_title ?: __( '(unknown)', 'native-content-relationships' ),
		) );
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

		$settings_url    = admin_url( 'admin.php?page=naticore-settings' );
		$wizard_url      = admin_url( 'admin.php?page=naticore-wizard' );
		$docs_url        = 'https://chetanupare.github.io/WP-Native-Content-Relationships/';
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Native Content Relationships is active!', 'native-content-relationships' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Wizard URL, 2: Settings URL, 3: Documentation URL */
					wp_kses_post( __( ' <a href="%1$s">Run the Setup Wizard</a> to get started quickly, <a href="%2$s">visit settings</a>, or <a href="%3$s" target="_blank">read the documentation</a>.', 'native-content-relationships' ) ),
					esc_url( $wizard_url ),
					esc_url( $settings_url ),
					esc_url( $docs_url )
				);
				?>
			</p>
		</div>
		<?php
	}
}

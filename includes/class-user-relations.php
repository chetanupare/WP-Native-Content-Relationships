<?php
/**
 * User Relationships Admin UI
 * Handles user profile and post editor interfaces for user relationships
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Relationships Admin UI
 *
 * Provides functionality for managing user relationships in the WordPress admin
 * area, including user profile integration and post editor interfaces.
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */
class NATICORE_User_Relations {

	/**
	 * Instance
	 * @var NATICORE_User_Relations|null
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
		// Add user profile metabox.
		add_action( 'show_user_profile', array( $this, 'add_user_profile_metabox' ) );
		add_action( 'edit_user_profile', array( $this, 'add_user_profile_metabox' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_profile' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile' ) );

		// Add post editor metabox for user relationships.
		add_action( 'add_meta_boxes', array( $this, 'add_post_user_metabox' ) );
		add_action( 'save_post', array( $this, 'save_post_user_relations' ) );

		// AJAX for searching users and posts.
		add_action( 'wp_ajax_naticore_search_users', array( $this, 'ajax_search_users' ) );
		add_action( 'wp_ajax_naticore_search_posts_for_user', array( $this, 'ajax_search_posts_for_user' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add user profile metabox
	 */
	public function add_user_profile_metabox( $user ) {
		// Only show if user has permission.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		// Get user's related posts.
		$related_posts = wp_get_user_related_posts( $user->ID );

		// Get available relationship types that support user-to-post.
		$available_types = $this->get_user_to_post_types();

		if ( empty( $available_types ) ) {
			return;
		}

		?>
		<h2><?php esc_html_e( 'Content Relationships', 'native-content-relationships' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Related Posts', 'native-content-relationships' ); ?></th>
				<td>
					<div id="naticore-user-relations">
						<?php if ( ! empty( $related_posts ) ) : ?>
							<div class="naticore-related-items">
								<?php foreach ( $related_posts as $post ) : ?>
									<div class="naticore-related-item" data-id="<?php echo esc_attr( $post['id'] ); ?>" data-type="<?php echo esc_attr( $post['type'] ); ?>">
										<span class="naticore-item-title">
											<a href="<?php echo esc_url( get_edit_post_link( $post['id'] ) ); ?>" target="_blank">
												<?php echo esc_html( $post['post_title'] ); ?>
											</a>
										</span>
										<span class="naticore-item-type"><?php echo esc_html( $post['type'] ); ?></span>
										<button type="button" class="button naticore-remove-relation">
											<span class="dashicons dashicons-no-alt"></span>
											<?php esc_html_e( 'Remove', 'native-content-relationships' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'No related posts found.', 'native-content-relationships' ); ?></p>
						<?php endif; ?>

						<div class="naticore-add-relation">
							<select id="naticore-relation-type" class="regular-text">
								<?php foreach ( $available_types as $type => $label ) : ?>
									<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<input type="text" id="naticore-post-search" placeholder="<?php esc_attr_e( 'Search posts...', 'native-content-relationships' ); ?>" class="regular-text">
							<button type="button" id="naticore-add-post-relation" class="button">
								<?php esc_html_e( 'Add Relation', 'native-content-relationships' ); ?>
							</button>
						</div>
						<div id="naticore-post-search-results" class="naticore-search-results" style="display: none;"></div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save user profile relationships
	 */
	public function save_user_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Handle AJAX saves are handled separately.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Nonce verification would be handled by WordPress for user profile saves.
		// Additional validation can be added here if needed.
	}

	/**
	 * Add post editor metabox for user relationships
	 */
	public function add_post_user_metabox() {
		$post_types = $this->get_supported_post_types();
		
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'naticore-user-relations',
				__( 'User Relationships', 'native-content-relationships' ),
				array( $this, 'render_post_user_metabox' ),
				$post_type,
				'normal',
				'default'
			);
		}
	}

	/**
	 * Render post editor metabox for user relationships
	 */
	public function render_post_user_metabox( $post ) {
		// Get post's related users.
		$related_users = wp_get_related_users( $post->ID );

		// Get available relationship types that support post-to-user.
		$available_types = $this->get_post_to_user_types();

		if ( empty( $available_types ) ) {
			echo '<p>' . esc_html__( 'No user relationship types available.', 'native-content-relationships' ) . '</p>';
			return;
		}

		wp_nonce_field( 'naticore_save_user_relations', 'naticore_user_relations_nonce' );
		?>
		<div id="naticore-post-user-relations">
			<?php if ( ! empty( $related_users ) ) : ?>
				<div class="naticore-related-items">
					<?php foreach ( $related_users as $user ) : ?>
						<div class="naticore-related-item" data-id="<?php echo esc_attr( $user['id'] ); ?>" data-type="<?php echo esc_attr( $user['type'] ); ?>">
							<span class="naticore-item-title">
								<a href="<?php echo esc_url( get_edit_user_link( $user['id'] ) ); ?>" target="_blank">
									<?php echo esc_html( $user['display_name'] ); ?>
								</a>
								<?php if ( ! empty( $user['user_email'] ) ) : ?>
									(<?php echo esc_html( $user['user_email'] ); ?>)
								<?php endif; ?>
							</span>
							<span class="naticore-item-type"><?php echo esc_html( $user['type'] ); ?></span>
							<button type="button" class="button naticore-remove-relation">
								<span class="dashicons dashicons-no-alt"></span>
								<?php esc_html_e( 'Remove', 'native-content-relationships' ); ?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No related users found.', 'native-content-relationships' ); ?></p>
			<?php endif; ?>

			<div class="naticore-add-relation">
				<select id="naticore-user-relation-type" class="regular-text">
					<?php foreach ( $available_types as $type => $label ) : ?>
						<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="text" id="naticore-user-search" placeholder="<?php esc_attr_e( 'Search users...', 'native-content-relationships' ); ?>" class="regular-text">
				<button type="button" id="naticore-add-user-relation" class="button">
					<?php esc_html_e( 'Add Relation', 'native-content-relationships' ); ?>
				</button>
			</div>
			<div id="naticore-user-search-results" class="naticore-search-results" style="display: none;"></div>
		</div>

		<style>
		.naticore-related-items {
			margin-bottom: 15px;
		}
		.naticore-related-item {
			display: flex;
			align-items: center;
			padding: 8px;
			margin-bottom: 5px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.naticore-item-title {
			flex: 1;
			margin-right: 10px;
		}
		.naticore-item-title a {
			text-decoration: none;
			font-weight: 500;
		}
		.naticore-item-type {
			background: #e7e7e7;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 11px;
			margin-right: 10px;
		}
		.naticore-remove-relation {
			margin-left: auto;
		}
		.naticore-add-relation {
			display: flex;
			gap: 10px;
			align-items: center;
			margin-bottom: 10px;
		}
		.naticore-search-results {
			border: 1px solid #ddd;
			background: white;
			max-height: 200px;
			overflow-y: auto;
			margin-top: 5px;
		}
		.naticore-search-result-item {
			padding: 8px 12px;
			cursor: pointer;
			border-bottom: 1px solid #eee;
		}
		.naticore-search-result-item:hover {
			background: #f0f0f0;
		}
		.naticore-search-result-item:last-child {
			border-bottom: none;
		}
		</style>
		<?php
	}

	/**
	 * Save post user relationships
	 */
	public function save_post_user_relations( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['naticore_user_relations_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['naticore_user_relations_nonce'] ), 'naticore_save_user_relations' ) ) {
			return;
		}

		// Check user capabilities
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Autosave check
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// AJAX saves are handled separately
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}
	}

	/**
	 * AJAX: Search users
	 */
	public function ajax_search_users() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ?? '' ), 'naticore_search_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		if ( empty( $search ) ) {
			wp_die();
		}

		$users = get_users( array(
			'search' => '*' . $search . '*',
			'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
			'number' => 20,
		) );

		$results = array();
		foreach ( $users as $user ) {
			$results[] = array(
				'id' => $user->ID,
				'display_name' => $user->display_name,
				'user_email' => $user->user_email,
				'user_login' => $user->user_login,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Search posts for user
	 */
	public function ajax_search_posts_for_user() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ?? '' ), 'naticore_search_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		if ( empty( $search ) ) {
			wp_die();
		}

		$args = array(
			's' => $search,
			'post_type' => $this->get_supported_post_types(),
			'post_status' => 'publish',
			'posts_per_page' => 20,
		);

		$query = new WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$results[] = array(
					'id' => get_the_ID(),
					'post_title' => get_the_title(),
					'post_type' => get_post_type(),
					'edit_link' => get_edit_post_link(),
				);
			}
		}
		wp_reset_postdata();

		wp_send_json_success( $results );
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		global $pagenow;

		// Only load on relevant pages
		$is_user_profile = in_array( $pagenow, array( 'profile.php', 'user-edit.php' ), true );
		$is_post_editor = in_array( $hook, array( 'post.php', 'post-new.php' ), true );

		if ( ! $is_user_profile && ! $is_post_editor ) {
			return;
		}

		wp_enqueue_script( 'naticore-user-relations', plugins_url( '../assets/user-relations.js', __FILE__ ), array( 'jquery' ), NATICORE_VERSION, true );
		
		wp_localize_script( 'naticore-user-relations', 'naticoreUserRelations', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'naticore_search_nonce' ),
			'strings' => array(
				'addRelation' => __( 'Add Relation', 'native-content-relationships' ),
				'removeRelation' => __( 'Remove', 'native-content-relationships' ),
				'noResults' => __( 'No results found', 'native-content-relationships' ),
				'confirmRemove' => __( 'Are you sure you want to remove this relationship?', 'native-content-relationships' ),
			),
		) );
	}

	/**
	 * Get relationship types that support user-to-post connections
	 */
	private function get_user_to_post_types() {
		return NATICORE_Relation_Types::get_user_to_post_types();
	}

	/**
	 * Get relationship types that support post-to-user connections
	 */
	private function get_post_to_user_types() {
		return NATICORE_Relation_Types::get_post_to_user_types();
	}

	/**
	 * Get supported post types for user relationships
	 */
	private function get_supported_post_types() {
		// Get all public post types except attachments
		$post_types = get_post_types( array(
			'public' => true,
			'exclude_from_search' => false,
		), 'objects' );

		$supported = array();
		foreach ( $post_types as $post_type ) {
			if ( 'attachment' !== $post_type->name ) {
				$supported[] = $post_type->name;
			}
		}

		return $supported;
	}
}

// Initialize
NATICORE_User_Relations::get_instance();

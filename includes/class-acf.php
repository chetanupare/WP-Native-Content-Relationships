<?php
/**
 * Advanced Custom Fields (ACF) Integration
 * Optional migration and sync support
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_ACF {
	
	/**
	 * Instance
	 */
	private static $instance = null;
	
	/**
	 * Is ACF active
	 */
	private $is_acf_active = false;
	
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
		// Check if ACF is active
		$this->is_acf_active = function_exists( 'acf_get_field_groups' );
		
		if ( ! $this->is_acf_active ) {
			return; // Exit early if ACF is not active
		}
		
		// Initialize ACF features
		$this->init();
	}
	
	/**
	 * Initialize ACF features
	 */
	private function init() {
		// Add migration tools to settings
		add_action( 'wpncr_settings_tabs', array( $this, 'add_acf_settings_tab' ) );
		
		// Optional read-only sync (if enabled)
		$settings = WPNCR_Settings::get_instance();
		if ( $settings->get_setting( 'acf_sync_mode', 'off' ) === 'read_only' ) {
			add_action( 'acf/update_value/type=relationship', array( $this, 'sync_acf_to_native' ), 10, 3 );
		}
	}
	
	/**
	 * Check if ACF is active
	 */
	public function is_active() {
		return $this->is_acf_active;
	}
	
	/**
	 * Add ACF settings tab
	 */
	public function add_acf_settings_tab() {
		if ( $this->is_active() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only tab parameter
			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
			?>
			<a href="?page=wpncr-settings&tab=acf" class="nav-tab <?php echo esc_attr( $active_tab === 'acf' ? 'nav-tab-active' : '' ); ?>">
				<?php esc_html_e( 'ACF', 'native-content-relationships' ); ?>
			</a>
			<?php
		}
	}
	
	/**
	 * Render ACF settings
	 */
	public function render_acf_settings() {
		$settings = WPNCR_Settings::get_instance();
		$option_name = 'wpncr_settings';
		$sync_mode = $settings->get_setting( 'acf_sync_mode', 'off' );
		
		// Get ACF relationship fields
		$acf_fields = $this->get_acf_relationship_fields();
		
		?>
		<h2><?php esc_html_e( 'Advanced Custom Fields Integration', 'native-content-relationships' ); ?></h2>
		<p><?php esc_html_e( 'Migrate ACF relationship fields to native content relationships or enable read-only sync.', 'native-content-relationships' ); ?></p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'ACF Relationship Fields', 'native-content-relationships' ); ?></label>
				</th>
				<td>
					<?php if ( empty( $acf_fields ) ) : ?>
						<p><?php esc_html_e( 'No ACF relationship fields found.', 'native-content-relationships' ); ?></p>
					<?php else : ?>
						<ul>
							<?php foreach ( $acf_fields as $field ) : ?>
								<li>
									<strong><?php echo esc_html( $field['label'] ); ?></strong>
									<code><?php echo esc_html( $field['name'] ); ?></code>
									(<?php echo esc_html( $field['post_type'] ); ?>)
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="acf_sync_mode"><?php esc_html_e( 'Sync Mode', 'native-content-relationships' ); ?></label>
				</th>
				<td>
					<select name="<?php echo esc_attr( $option_name ); ?>[acf_sync_mode]" id="acf_sync_mode">
						<option value="off" <?php selected( $sync_mode, 'off' ); ?>><?php esc_html_e( 'Off', 'native-content-relationships' ); ?></option>
						<option value="read_only" <?php selected( $sync_mode, 'read_only' ); ?>><?php esc_html_e( 'Read-only (ACF → Native)', 'native-content-relationships' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Read-only mode syncs ACF relationship fields to native relationships without replacing ACF UI.', 'native-content-relationships' ); ?>
					</p>
				</td>
			</tr>
		</table>
		
		<?php if ( ! empty( $acf_fields ) ) : ?>
			<h3><?php esc_html_e( 'One-Time Migration', 'native-content-relationships' ); ?></h3>
			<p><?php esc_html_e( 'Migrate existing ACF relationship data to native content relationships. This is a one-time operation.', 'native-content-relationships' ); ?></p>
			<p>
				<button type="button" class="button" id="wpncr-migrate-acf">
					<?php esc_html_e( 'Migrate ACF Relationships', 'native-content-relationships' ); ?>
				</button>
				<span id="wpncr-migrate-status"></span>
			</p>
		<?php endif; ?>
		
		<script>
		jQuery(document).ready(function($) {
			$('#wpncr-migrate-acf').on('click', function() {
				if (!confirm('<?php esc_attr_e( 'This will migrate all ACF relationship fields to native relationships. Continue?', 'native-content-relationships' ); ?>')) {
					return;
				}
				
				$('#wpncr-migrate-status').html('<?php esc_attr_e( 'Migrating...', 'native-content-relationships' ); ?>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wpncr_migrate_acf',
						nonce: '<?php echo esc_js( wp_create_nonce( 'wpncr_migrate_acf' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$('#wpncr-migrate-status').html('<span style="color: green;">' + response.data.message + '</span>');
						} else {
							$('#wpncr-migrate-status').html('<span style="color: red;">' + response.data.message + '</span>');
						}
					}
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Get ACF relationship fields
	 */
	private function get_acf_relationship_fields() {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return array();
		}
		
		$field_groups = acf_get_field_groups();
		$relationship_fields = array();
		
		foreach ( $field_groups as $group ) {
			$fields = acf_get_fields( $group['ID'] );
			if ( $fields ) {
				foreach ( $fields as $field ) {
					if ( $field['type'] === 'relationship' ) {
						$relationship_fields[] = array(
							'label'     => $field['label'],
							'name'      => $field['name'],
							'post_type' => isset( $field['post_type'] ) ? implode( ', ', $field['post_type'] ) : 'all',
						);
					}
				}
			}
		}
		
		return $relationship_fields;
	}
	
	/**
	 * Sync ACF relationship to native (read-only)
	 */
	public function sync_acf_to_native( $value, $post_id, $field ) {
		if ( empty( $value ) || ! is_array( $value ) ) {
			return $value;
		}
		
		// Remove existing relations for this field
		$existing = WPNCR_API::get_related( $post_id, 'related_to' );
		// Note: In a real implementation, you'd want to track which relations came from ACF
		
		// Add new relations
		foreach ( $value as $related_id ) {
			WPNCR_API::add_relation( $post_id, $related_id, 'related_to' );
		}
		
		return $value; // Don't modify ACF value
	}
	
	/**
	 * Migrate ACF relationships (AJAX handler)
	 */
	public static function migrate_acf_relationships() {
		check_ajax_referer( 'wpncr_migrate_acf', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}
		
		$migrated = 0;
		$posts = get_posts( array(
			'post_type'      => 'any',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		) );
		
		foreach ( $posts as $post ) {
			$fields = get_fields( $post->ID );
			if ( ! $fields ) {
				continue;
			}
			
			foreach ( $fields as $field_name => $value ) {
				$field = get_field_object( $field_name, $post->ID );
				if ( ! $field || $field['type'] !== 'relationship' ) {
					continue;
				}
				
				if ( is_array( $value ) ) {
					foreach ( $value as $related_id ) {
						$result = WPNCR_API::add_relation( $post->ID, $related_id, 'related_to' );
						if ( ! is_wp_error( $result ) ) {
							$migrated++;
						}
					}
				}
			}
		}
		
		/* translators: %d: Number of relationships migrated */
		$message = sprintf( esc_html__( 'Migrated %d relationships.', 'native-content-relationships' ), esc_html( $migrated ) );
		wp_send_json_success( array(
			'message' => $message,
			'count'   => $migrated,
		) );
	}
}

// AJAX handler
add_action( 'wp_ajax_wpncr_migrate_acf', function() {
	WPNCR_ACF::migrate_acf_relationships();
} );

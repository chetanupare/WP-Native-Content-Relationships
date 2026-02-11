<?php
/**
 * Plugin Settings Page
 * Modern Tabbed Interface
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Settings {

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Option name
	 */
	private $option_name = 'naticore_settings';

	/**
	 * Current tab
	 */
	private $current_tab = 'general';

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

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . NATICORE_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		// Handle tab navigation
		add_action( 'admin_init', array( $this, 'handle_tab_action' ) );
	}

	/**
	 * Handle tab navigation
	 */
	public function handle_tab_action() {
		if ( isset( $_GET['tab'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'naticore_tab_nonce' ) && in_array( sanitize_key( $_GET['tab'] ), array_keys( $this->get_tabs() ), true ) ) {
			$this->current_tab = sanitize_key( $_GET['tab'] );
		}
	}

	/**
	 * Get available tabs
	 */
	private function get_tabs() {
		$tabs = array(
			'general'            => __( 'General', 'native-content-relationships' ),
			'relationship_types' => __( 'Relationship Types', 'native-content-relationships' ),
			'woocommerce'        => __( 'WooCommerce', 'native-content-relationships' ),
			'privacy'            => __( 'Privacy', 'native-content-relationships' ),
		);

		// Developer tab only in debug mode
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$tabs['developer'] = __( 'Developer', 'native-content-relationships' );
		}

		return $tabs;
	}

	/**
	 * Add settings page
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Content Relationships', 'native-content-relationships' ),
			__( 'Content Relationships', 'native-content-relationships' ),
			'manage_options',
			'naticore-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Add settings link to plugin actions
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=naticore-settings' ) . '">' . __( 'Settings', 'native-content-relationships' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'naticore_settings', $this->option_name, array( $this, 'sanitize_settings' ) );

		// Allow other components to add settings sections
		do_action( 'naticore_register_settings' );

		// Register sections based on current tab
		switch ( $this->current_tab ) {
			case 'general':
				$this->register_general_settings();
				break;
			case 'relationship_types':
				$this->register_relationship_types_settings();
				break;
			case 'woocommerce':
				$this->register_woocommerce_settings();
				break;
			case 'privacy':
				$this->register_privacy_settings();
				break;
			case 'developer':
				$this->register_developer_settings();
				break;
		}
	}

	/**
	 * Register General tab settings
	 */
	private function register_general_settings() {
		// Content Types Section
		add_settings_section(
			'naticore_content_types',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'enabled_post_types',
			'', // No title, will be rendered manually
			array( $this, 'render_enabled_post_types' ),
			'naticore-settings',
			'naticore_content_types'
		);

		// Relationship Behavior Section
		add_settings_section(
			'naticore_behavior',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'default_direction',
			'', // No title, will be rendered manually
			array( $this, 'render_default_direction' ),
			'naticore-settings',
			'naticore_behavior'
		);

		// Cleanup Section
		add_settings_section(
			'naticore_cleanup',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'cleanup_on_delete',
			'', // No title, will be rendered manually
			array( $this, 'render_cleanup_on_delete' ),
			'naticore-settings',
			'naticore_cleanup'
		);

		// Limits & Automation Section
		add_settings_section(
			'naticore_limits',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'ncr_max_relationships',
			'', // No title, will be rendered manually
			array( $this, 'render_ncr_max_relationships' ),
			'naticore-settings',
			'naticore_limits'
		);

		add_settings_field(
			'auto_relation_enabled',
			'', // No title, will be rendered manually
			array( $this, 'render_auto_relation' ),
			'naticore-settings',
			'naticore_limits'
		);

		// Permissions Section
		add_settings_section(
			'naticore_permissions',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'prevent_circular',
			'', // No title, will be rendered manually
			array( $this, 'render_prevent_circular' ),
			'naticore-settings',
			'naticore_permissions'
		);
	}

	/**
	 * Register Developer tab settings
	 */
	private function register_developer_settings() {
		add_settings_section(
			'naticore_developer',
			'', // No title, will be rendered manually
			'__return_false',
			'naticore-settings'
		);

		add_settings_field(
			'debug_logging',
			'', // No title, will be rendered manually
			array( $this, 'render_debug_logging' ),
			'naticore-settings',
			'naticore_developer'
		);

		add_settings_field(
			'query_debug',
			'', // No title, will be rendered manually
			array( $this, 'render_query_debug' ),
			'naticore-settings',
			'naticore_developer'
		);

		add_settings_field(
			'enable_rest_api',
			'', // No title, will be rendered manually
			array( $this, 'render_enable_rest_api' ),
			'naticore-settings',
			'naticore_developer'
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Enabled post types
		if ( isset( $input['enabled_post_types'] ) && is_array( $input['enabled_post_types'] ) ) {
			$sanitized['enabled_post_types'] = array_map( 'sanitize_text_field', $input['enabled_post_types'] );
		} else {
			$sanitized['enabled_post_types'] = array();
		}

		// Default direction
		$sanitized['default_direction'] = isset( $input['default_direction'] ) && $input['default_direction'] === 'bidirectional' ? 'bidirectional' : 'unidirectional';

		// Cleanup on delete
		$sanitized['cleanup_on_delete'] = isset( $input['cleanup_on_delete'] ) && $input['cleanup_on_delete'] === 'keep' ? 'keep' : 'remove';

		// Max relationships
		$sanitized['ncr_max_relationships'] = isset( $input['ncr_max_relationships'] ) ? absint( $input['ncr_max_relationships'] ) : 0;

		// Prevent circular
		$sanitized['prevent_circular'] = isset( $input['prevent_circular'] ) ? 1 : 0;

		// WooCommerce settings
		if ( isset( $input['wc_enabled_objects'] ) && is_array( $input['wc_enabled_objects'] ) ) {
			$sanitized['wc_enabled_objects'] = array_map( 'sanitize_text_field', $input['wc_enabled_objects'] );
		} else {
			$sanitized['wc_enabled_objects'] = array( 'product' ); // Default
		}

		$sanitized['wc_sync_upsells'] = isset( $input['wc_sync_upsells'] ) ? 1 : 0;

		// ACF settings
		$sanitized['acf_sync_mode'] = isset( $input['acf_sync_mode'] ) && in_array( $input['acf_sync_mode'], array( 'off', 'read_only' ), true ) ? $input['acf_sync_mode'] : 'off';

		// Multilingual settings
		$sanitized['multilingual_mirror'] = isset( $input['multilingual_mirror'] ) ? 1 : 0;

		// Auto-relation settings
		$sanitized['auto_relation_enabled'] = isset( $input['auto_relation_enabled'] ) ? 1 : 0;
		if ( isset( $input['auto_relation_post_types'] ) && is_array( $input['auto_relation_post_types'] ) ) {
			$sanitized['auto_relation_post_types'] = array_map( 'sanitize_text_field', $input['auto_relation_post_types'] );
		} else {
			$sanitized['auto_relation_post_types'] = array( 'post' );
		}

		// Immutable mode
		$sanitized['immutable_mode'] = isset( $input['immutable_mode'] ) ? 1 : 0;

		// Developer settings
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$sanitized['debug_logging']   = isset( $input['debug_logging'] ) ? 1 : 0;
			$sanitized['query_debug']     = isset( $input['query_debug'] ) ? 1 : 0;
			$sanitized['enable_rest_api'] = isset( $input['enable_rest_api'] ) ? 1 : 1; // Default enabled
		}

		return $sanitized;
	}

	/**
	 * Get settings
	 */
	public function get_settings() {
		$defaults = array(
			'enabled_post_types'       => array( 'post', 'page' ),
			'default_direction'        => 'unidirectional',
			'cleanup_on_delete'        => 'remove',
			'ncr_max_relationships'        => 0,
			'prevent_circular'         => 1,
			'debug_logging'            => 0,
			'query_debug'              => 0,
			'enable_rest_api'          => 1,
			'wc_enabled_objects'       => array( 'product' ),
			'wc_sync_upsells'          => 0,
			'acf_sync_mode'            => 'off',
			'multilingual_mirror'      => 0,
			'auto_relation_enabled'    => 0,
			'auto_relation_post_types' => array( 'post' ),
			'immutable_mode'           => 0,
		);

		$settings = get_option( $this->option_name, array() );
		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get a specific setting
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only tab parameter
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=naticore-settings&tab=general" class="nav-tab <?php echo esc_attr( $active_tab === 'general' ? 'nav-tab-active' : '' ); ?>">
					<?php esc_html_e( 'General', 'native-content-relationships' ); ?>
				</a>
				<a href="?page=naticore-settings&tab=types" class="nav-tab <?php echo esc_attr( $active_tab === 'types' ? 'nav-tab-active' : '' ); ?>">
					<?php esc_html_e( 'Relationship Types', 'native-content-relationships' ); ?>
				</a>
				<?php do_action( 'naticore_settings_tabs' ); ?>
				<a href="?page=naticore-settings&tab=privacy" class="nav-tab <?php echo esc_attr( $active_tab === 'privacy' ? 'nav-tab-active' : '' ); ?>">
					<?php esc_html_e( 'Privacy', 'native-content-relationships' ); ?>
				</a>
				<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					<a href="?page=naticore-settings&tab=developer" class="nav-tab <?php echo esc_attr( $active_tab === 'developer' ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'Developer', 'native-content-relationships' ); ?>
					</a>
				<?php endif; ?>
			</nav>
			
			<?php if ( $active_tab === 'general' ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'naticore_settings' );
					do_settings_sections( 'naticore-settings' );
					submit_button();
					?>
				</form>
			<?php elseif ( $active_tab === 'types' ) : ?>
				<?php $this->render_types_tab(); ?>
			<?php elseif ( $active_tab === 'woocommerce' ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'naticore_settings' );
					if ( class_exists( 'NATICORE_WooCommerce' ) ) {
						$wc = NATICORE_WooCommerce::get_instance();
						if ( $wc->is_active() ) {
							$wc->render_wc_settings();
						} else {
							echo '<div class="notice notice-info"><p>' . esc_html__( 'WooCommerce is not active. Install and activate WooCommerce to use these features.', 'native-content-relationships' ) . '</p></div>';
						}
					} else {
						echo '<div class="notice notice-info"><p>' . esc_html__( 'WooCommerce is not active. Install and activate WooCommerce to use these features.', 'native-content-relationships' ) . '</p></div>';
					}
					submit_button();
					?>
				</form>
			<?php elseif ( $active_tab === 'acf' ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'naticore_settings' );
					if ( class_exists( 'NATICORE_ACF' ) ) {
						$acf = NATICORE_ACF::get_instance();
						if ( $acf->is_active() ) {
							$acf->render_acf_settings();
						} else {
							echo '<div class="notice notice-info"><p>' . esc_html__( 'Advanced Custom Fields is not active.', 'native-content-relationships' ) . '</p></div>';
						}
					} else {
						echo '<div class="notice notice-info"><p>' . esc_html__( 'Advanced Custom Fields is not active.', 'native-content-relationships' ) . '</p></div>';
					}
					submit_button();
					?>
				</form>
			<?php elseif ( $active_tab === 'multilingual' ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'naticore_settings' );
					if ( class_exists( 'NATICORE_WPML' ) ) {
						$wpml = NATICORE_WPML::get_instance();
						if ( $wpml->is_active() ) {
							$wpml->render_multilingual_settings();
						} else {
							echo '<div class="notice notice-info"><p>' . esc_html__( 'WPML or Polylang is not active.', 'native-content-relationships' ) . '</p></div>';
						}
					} else {
						echo '<div class="notice notice-info"><p>' . esc_html__( 'WPML or Polylang is not active.', 'native-content-relationships' ) . '</p></div>';
					}
					submit_button();
					?>
				</form>
			<?php elseif ( $active_tab === 'privacy' ) : ?>
				<?php $this->render_privacy_tab(); ?>
			<?php elseif ( $active_tab === 'developer' && defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( 'naticore_settings' );
					// Only show developer section
					global $wp_settings_sections, $wp_settings_fields;
					if ( isset( $wp_settings_sections['naticore-settings']['naticore_developer'] ) ) {
						$section = $wp_settings_sections['naticore-settings']['naticore_developer'];
						echo '<h2>' . esc_html( $section['title'] ) . '</h2>';
						if ( $section['callback'] ) {
							call_user_func( $section['callback'], $section );
						}
						if ( isset( $wp_settings_fields['naticore-settings'][ $section['id'] ] ) ) {
							echo '<table class="form-table" role="presentation">';
							do_settings_fields( 'naticore-settings', $section['id'] );
							echo '</table>';
						}
					}
					submit_button();
					?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render privacy tab
	 */
	private function render_privacy_tab() {
		?>
		<div class="naticore-privacy-section">
			<h2><?php esc_html_e( 'Privacy Policy', 'native-content-relationships' ); ?></h2>
			
			<div class="card" style="max-width: 800px;">
				<h3><?php esc_html_e( 'Data Storage', 'native-content-relationships' ); ?></h3>
				<p>
					<?php esc_html_e( 'This plugin stores content relationship metadata in your WordPress database. All relationship data is stored locally in the custom table:', 'native-content-relationships' ); ?>
					<code>
					<?php
					global $wpdb;
					echo esc_html( $wpdb->prefix . 'content_relations' );
					?>
					</code>
				</p>
				
				<h3><?php esc_html_e( 'External Data Transmission', 'native-content-relationships' ); ?></h3>
				<p>
					<strong><?php esc_html_e( 'This plugin does not send any data to external servers.', 'native-content-relationships' ); ?></strong>
					<?php esc_html_e( 'All relationship data remains on your server and is never transmitted externally.', 'native-content-relationships' ); ?>
				</p>
				
				<h3><?php esc_html_e( 'Data Export & Deletion', 'native-content-relationships' ); ?></h3>
				<p>
					<?php esc_html_e( 'You can export all relationship data at any time using the Import/Export tool (Tools → Import/Export Relationships).', 'native-content-relationships' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'When uninstalling the plugin, you can choose to keep or remove all relationship data. This setting can be configured before uninstallation.', 'native-content-relationships' ); ?>
				</p>
				
				<h3><?php esc_html_e( 'What Data is Stored', 'native-content-relationships' ); ?></h3>
				<ul>
					<li><?php esc_html_e( 'Post IDs that are related to each other', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'Relationship type (e.g., "references", "depends_on")', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'Relationship direction (one-way or bidirectional)', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'Creation timestamp', 'native-content-relationships' ); ?></li>
				</ul>
				
				<p>
					<strong><?php esc_html_e( 'Note:', 'native-content-relationships' ); ?></strong>
					<?php esc_html_e( 'This plugin does not store any personal information, user data, or content from your posts. It only stores the relationships between content items.', 'native-content-relationships' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render types tab
	 */
	private function render_types_tab() {
		$types = NATICORE_Relation_Types::get_types();
		?>
		<div class="naticore-types-manager">
			<p><?php esc_html_e( 'Manage relationship types. These are controlled vocabulary — not free text.', 'native-content-relationships' ); ?></p>
			
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Slug', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Label', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Direction', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Allowed Post Types', 'native-content-relationships' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $types ) ) : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No relationship types registered.', 'native-content-relationships' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $types as $slug => $type_info ) : ?>
							<tr>
								<td><code><?php echo esc_html( $slug ); ?></code></td>
								<td><strong><?php echo esc_html( $type_info['label'] ); ?></strong></td>
								<td>
									<?php if ( $type_info['bidirectional'] ) : ?>
										<span class="dashicons dashicons-arrow-left-alt" title="<?php esc_attr_e( 'Bidirectional', 'native-content-relationships' ); ?>"></span> ↔
									<?php else : ?>
										<span class="dashicons dashicons-arrow-right-alt" title="<?php esc_attr_e( 'One-way', 'native-content-relationships' ); ?>"></span> →
									<?php endif; ?>
								</td>
								<td>
									<?php if ( empty( $type_info['allowed_post_types'] ) ) : ?>
										<em><?php esc_html_e( 'All post types', 'native-content-relationships' ); ?></em>
									<?php else : ?>
										<?php echo esc_html( implode( ', ', $type_info['allowed_post_types'] ) ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			
			<div class="naticore-types-info" style="margin-top: 20px; padding: 10px; background: #f6f7f7; border-left: 4px solid #2271b1;">
				<p><strong><?php esc_html_e( 'Registering Custom Types', 'native-content-relationships' ); ?></strong></p>
				<p><?php esc_html_e( 'Use the <code>register_content_relation_type()</code> function in your theme or plugin:', 'native-content-relationships' ); ?></p>
				<pre style="background: #fff; padding: 10px; border: 1px solid #dcdcde; border-radius: 2px;"><code>register_content_relation_type( 'custom_type', array(
	'label'            => 'Custom Type',
	'bidirectional'    => false,
	'allowed_post_types' => array( 'post', 'page' )
) );</code></pre>
			</div>
		</div>
		<?php
	}

	/**
	 * Render general section
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'Configure general behavior for content relationships.', 'native-content-relationships' ) . '</p>';
	}

	/**
	 * Render enabled post types field
	 */
	public function render_enabled_post_types() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array( 'post', 'page' );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Enable Relationships For', 'native-content-relationships' ); ?></legend>
			<?php foreach ( $post_types as $post_type ) : ?>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enabled_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $enabled, true ) ); ?>>
					<?php echo esc_html( $post_type->label ); ?>
				</label><br>
			<?php endforeach; ?>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Prevents unnecessary meta boxes on unused content types.', 'native-content-relationships' ); ?></p>
		<?php
	}

	/**
	 * Render default direction field
	 */
	public function render_default_direction() {
		$settings = $this->get_settings();
		$default  = isset( $settings['default_direction'] ) ? $settings['default_direction'] : 'unidirectional';
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Default Relationship Behavior', 'native-content-relationships' ); ?></legend>
			<label>
				<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[default_direction]" value="unidirectional" <?php checked( $default, 'unidirectional' ); ?>>
				<?php esc_html_e( 'One-way (default)', 'native-content-relationships' ); ?>
			</label><br>
			<label>
				<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[default_direction]" value="bidirectional" <?php checked( $default, 'bidirectional' ); ?>>
				<?php esc_html_e( 'Bidirectional', 'native-content-relationships' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render cleanup on delete field
	 */
	public function render_cleanup_on_delete() {
		$settings = $this->get_settings();
		$cleanup  = isset( $settings['cleanup_on_delete'] ) ? $settings['cleanup_on_delete'] : 'remove';
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'Cleanup on Delete', 'native-content-relationships' ); ?></legend>
			<label>
				<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[cleanup_on_delete]" value="remove" <?php checked( $cleanup, 'remove' ); ?>>
				<?php esc_html_e( 'Remove relations automatically', 'native-content-relationships' ); ?>
			</label><br>
			<label>
				<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[cleanup_on_delete]" value="keep" <?php checked( $cleanup, 'keep' ); ?>>
				<?php esc_html_e( 'Keep but mark as orphaned', 'native-content-relationships' ); ?>
			</label>
		</fieldset>
		<?php
	}

	/**
	 * Render max relationships field
	 */
	public function render_ncr_max_relationships() {
		$settings = $this->get_settings();
		$max      = isset( $settings['ncr_max_relationships'] ) ? $settings['ncr_max_relationships'] : 0;
		?>
		<label for="naticore-max-relationships">
			<input 
				type="number" 
				id="naticore-max-relationships"
				name="<?php echo esc_attr( $this->option_name ); ?>[ncr_max_relationships]" 
				value="<?php echo esc_attr( $max ); ?>" 
				min="0" 
				step="1" 
				class="small-text"
				aria-describedby="naticore-max-relationships-desc"
			>
		</label>
		<p id="naticore-max-relationships-desc" class="description"><?php esc_html_e( 'Maximum relationships per post. Set to 0 for unlimited. Prevents misuse.', 'native-content-relationships' ); ?></p>
		<?php
	}

	/**
	 * Render auto-relation field
	 */
	public function render_auto_relation() {
		$settings   = $this->get_settings();
		$enabled    = isset( $settings['auto_relation_enabled'] ) ? $settings['auto_relation_enabled'] : 0;
		$post_types = isset( $settings['auto_relation_post_types'] ) ? $settings['auto_relation_post_types'] : array( 'post' );

		?>
		<label for="naticore-auto-relation-enabled">
			<input 
				type="checkbox" 
				id="naticore-auto-relation-enabled"
				name="<?php echo esc_attr( $this->option_name ); ?>[auto_relation_enabled]" 
				value="1" 
				<?php checked( $enabled, 1 ); ?>
				aria-describedby="naticore-auto-relation-desc"
			>
			<?php esc_html_e( 'Auto-create relationship when post is published', 'native-content-relationships' ); ?>
		</label>
		<p id="naticore-auto-relation-desc" class="description">
			<?php esc_html_e( 'When enabled, posts will automatically link to their parent page with "part_of" relationship type.', 'native-content-relationships' ); ?>
		</p>
		<?php
	}

	/**
	 * Render immutable mode field
	 */
	public function render_immutable_mode() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['immutable_mode'] ) ? $settings['immutable_mode'] : 0;

		?>
		<label for="naticore-immutable-mode">
			<input 
				type="checkbox" 
				id="naticore-immutable-mode"
				name="<?php echo esc_attr( $this->option_name ); ?>[immutable_mode]" 
				value="1" 
				<?php checked( $enabled, 1 ); ?>
				aria-describedby="naticore-immutable-mode-desc"
			>
			<?php esc_html_e( 'Lock relationships after publish', 'native-content-relationships' ); ?>
		</label>
		<p id="naticore-immutable-mode-desc" class="description">
			<?php esc_html_e( 'When enabled, relationships for published posts become read-only. They can only be changed via the admin interface or WP-CLI. Great for documentation and courses where structure shouldn\'t change.', 'native-content-relationships' ); ?>
		</p>
		<?php
	}

	/**
	 * Render permissions section
	 */
	public function render_permissions_section() {
		echo '<p>' . esc_html__( 'Configure safety and permission settings.', 'native-content-relationships' ) . '</p>';
	}

	/**
	 * Render prevent circular field
	 */
	public function render_prevent_circular() {
		$settings = $this->get_settings();
		$prevent  = isset( $settings['prevent_circular'] ) ? $settings['prevent_circular'] : 1;
		?>
		<fieldset>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[prevent_circular]" value="1" <?php checked( $prevent, 1 ); ?>>
				<?php esc_html_e( 'Prevent circular relationships', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Prevents infinite loops (A → B → A). Self-linking is always prevented.', 'native-content-relationships' ); ?></p>
		</fieldset>
		<?php
	}

	/**
	 * Render developer section
	 */
	public function render_developer_section() {
		$env_type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
		?>
		<p>
			<?php esc_html_e( 'Advanced settings for developers. Only visible when WP_DEBUG is enabled.', 'native-content-relationships' ); ?>
		</p>
		<?php if ( function_exists( 'wp_get_environment_type' ) ) : ?>
			<p>
				<strong><?php esc_html_e( 'Detected Environment:', 'native-content-relationships' ); ?></strong>
				<?php echo esc_html( ucfirst( $env_type ) ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render debug logging field
	 */
	public function render_debug_logging() {
		$settings = $this->get_settings();
		$debug    = isset( $settings['debug_logging'] ) ? $settings['debug_logging'] : 0;
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[debug_logging]" value="1" <?php checked( $debug, 1 ); ?>>
			<?php esc_html_e( 'Enable debug logging', 'native-content-relationships' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Log relationship operations to WordPress debug log.', 'native-content-relationships' ); ?></p>
		<?php
	}

	/**
	 * Render query debug field
	 */
	public function render_query_debug() {
		$settings    = $this->get_settings();
		$query_debug = isset( $settings['query_debug'] ) ? $settings['query_debug'] : 0;
		$env_type    = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		// Auto-enable in local environment
		$auto_enabled = ( $env_type === 'local' && defined( 'WP_DEBUG' ) && WP_DEBUG );

		?>
		<label for="naticore-query-debug">
			<input 
				type="checkbox" 
				id="naticore-query-debug"
				name="<?php echo esc_attr( $this->option_name ); ?>[query_debug]" 
				value="1" 
				<?php checked( $query_debug, 1 ); ?>
				<?php disabled( $auto_enabled ); ?>
				aria-describedby="naticore-query-debug-desc"
			>
			<?php esc_html_e( 'Enable relation query debug', 'native-content-relationships' ); ?>
		</label>
		<p id="naticore-query-debug-desc" class="description">
			<?php esc_html_e( 'Outputs SQL used, index used, and query time. Logs to browser console and debug.log. Only active when WP_DEBUG is true.', 'native-content-relationships' ); ?>
			<?php if ( $auto_enabled ) : ?>
				<br><strong><?php esc_html_e( 'Auto-enabled in local environment.', 'native-content-relationships' ); ?></strong>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Render enable REST API field
	 */
	public function render_enable_rest_api() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['enable_rest_api'] ) ? $settings['enable_rest_api'] : 1;
		?>
		<label for="naticore-rest-api">
			<input 
				type="checkbox" 
				id="naticore-rest-api"
				name="<?php echo esc_attr( $this->option_name ); ?>[enable_rest_api]" 
				value="1" 
				<?php checked( $enabled, 1 ); ?>
				aria-describedby="naticore-rest-api-desc"
			>
			<?php esc_html_e( 'Enable REST API', 'native-content-relationships' ); ?>
		</label>
		<p id="naticore-rest-api-desc" class="description"><?php esc_html_e( 'Enable REST API endpoints for headless WordPress.', 'native-content-relationships' ); ?></p>
		<?php
	}
}

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
	 * @var NATICORE_Settings|null
	 */
	private static $instance = null;

	/**
	 * Option name
	 * @var string
	 */
	private $option_name = 'naticore_settings';

	/**
	 * Current tab
	 * @var string
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
		// Only load admin functionality in admin context.
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ), 20 );
		add_filter( 'plugin_action_links_' . NATICORE_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );

		// Handle tab navigation.
		add_action( 'admin_init', array( $this, 'handle_tab_action' ), 5 );

		// Enqueue admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Handle tab navigation
	 */
	public function handle_tab_action() {
		// Simple tab detection without nonce for WordPress standard approach.
		if ( isset( $_GET['tab'] ) && in_array( sanitize_key( $_GET['tab'] ), array_keys( $this->get_tabs() ), true ) ) {
			$this->current_tab = sanitize_key( $_GET['tab'] );
		}
	}

	private function get_page_slug() {
		switch ( $this->current_tab ) {
			case 'relationship_types':
				return 'naticore-settings-relationship-types';
			case 'woocommerce':
				return 'naticore-settings-woocommerce';
			case 'privacy':
				return 'naticore-settings-privacy';
			case 'developer':
				return 'naticore-settings-developer';
			case 'general':
			default:
				return 'naticore-settings';
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
	 * Enqueue admin styles and scripts
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'settings_page_naticore-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'naticore-settings',
			plugins_url( '../assets/settings.css', __FILE__ ),
			array(),
			NATICORE_VERSION
		);

		wp_enqueue_script(
			'naticore-settings',
			plugins_url( '../assets/settings.js', __FILE__ ),
			array( 'jquery' ),
			NATICORE_VERSION,
			true
		);
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
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					$this->register_developer_settings();
				}
				break;
		}
	}

	/**
	 * Register General tab settings
	 */
	private function register_general_settings() {
		$page = $this->get_page_slug();
		// Content Types Section
		add_settings_section(
			'naticore_content_types',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'enabled_post_types',
			'', // No title, will be rendered manually
			array( $this, 'render_enabled_post_types' ),
			$page,
			'naticore_content_types'
		);

		// Relationship Behavior Section
		add_settings_section(
			'naticore_behavior',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'default_direction',
			'', // No title, will be rendered manually
			array( $this, 'render_default_direction' ),
			$page,
			'naticore_behavior'
		);

		// Cleanup Section
		add_settings_section(
			'naticore_cleanup',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'cleanup_on_delete',
			'', // No title, will be rendered manually
			array( $this, 'render_cleanup_on_delete' ),
			$page,
			'naticore_cleanup'
		);

		// Limits & Automation Section
		add_settings_section(
			'naticore_limits',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'max_relationships',
			'', // No title, will be rendered manually
			array( $this, 'render_max_relationships' ),
			$page,
			'naticore_limits'
		);

		add_settings_field(
			'auto_relation_enabled',
			'', // No title, will be rendered manually
			array( $this, 'render_auto_relation' ),
			$page,
			'naticore_limits'
		);

		// Permissions Section
		add_settings_section(
			'naticore_permissions',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'prevent_circular',
			'', // No title, will be rendered manually
			array( $this, 'render_prevent_circular' ),
			$page,
			'naticore_permissions'
		);
	}

	/**
	 * Register Developer tab settings
	 */
	private function register_developer_settings() {
		$page = $this->get_page_slug();
		add_settings_section(
			'naticore_developer',
			'', // No title, will be rendered manually
			'__return_false',
			$page
		);

		add_settings_field(
			'debug_logging',
			'', // No title, will be rendered manually
			array( $this, 'render_debug_logging' ),
			$page,
			'naticore_developer'
		);

		add_settings_field(
			'query_debug',
			'', // No title, will be rendered manually
			array( $this, 'render_query_debug' ),
			$page,
			'naticore_developer'
		);

		add_settings_field(
			'enable_rest_api',
			'', // No title, will be rendered manually
			array( $this, 'render_enable_rest_api' ),
			$page,
			'naticore_developer'
		);

		add_settings_field(
			'readonly_mode',
			'', // No title, will be rendered manually
			array( $this, 'render_readonly_mode' ),
			$page,
			'naticore_developer'
		);
	}

	/**
	 * Register other tab settings (placeholders)
	 */
	private function register_relationship_types_settings() {
		$page = $this->get_page_slug();
		add_settings_section( 'naticore_relationship_types', '', '__return_false', $page );
		add_settings_field( 'relationship_types_manage_ui', '', array( $this, 'render_relationship_types_manage_ui' ), $page, 'naticore_relationship_types' );
	}

	private function register_woocommerce_settings() {
		$page = $this->get_page_slug();
		add_settings_section( 'naticore_woocommerce', '', '__return_false', $page );
		add_settings_field( 'wc_enabled_objects', '', array( $this, 'render_wc_enabled_objects' ), $page, 'naticore_woocommerce' );
		add_settings_field( 'wc_sync_upsells', '', array( $this, 'render_wc_sync_upsells' ), $page, 'naticore_woocommerce' );
		add_settings_field( 'wc_use_case_presets', '', array( $this, 'render_wc_use_case_presets' ), $page, 'naticore_woocommerce' );
	}

	private function register_privacy_settings() {
		$page = $this->get_page_slug();
		add_settings_section( 'naticore_privacy', '', '__return_false', $page );
		add_settings_field( 'remove_data_on_uninstall', '', array( $this, 'render_remove_data_on_uninstall' ), $page, 'naticore_privacy' );
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		$tabs        = $this->get_tabs();
		$current_tab = $this->current_tab;
		?>
		<div class="wrap naticore-settings-container">
			<h1><?php esc_html_e( 'Content Relationships Settings', 'native-content-relationships' ); ?></h1>
			
			<?php $this->render_tabs(); ?>
			
			<div class="naticore-tab-content">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'naticore_settings' );
					do_settings_sections( $this->get_page_slug() );
					if ( $current_tab !== 'developer' || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
						submit_button();
					}
					?>
				</form>
			</div>
		</div>
		<?php
	}

	public function render_wc_use_case_presets() {
		$settings    = $this->get_settings();
		$accessories = isset( $settings['wc_use_case_accessories'] ) ? (int) $settings['wc_use_case_accessories'] : 0;
		$related     = isset( $settings['wc_use_case_related_products'] ) ? (int) $settings['wc_use_case_related_products'] : 0;
		$bundles     = isset( $settings['wc_use_case_bundles'] ) ? (int) $settings['wc_use_case_bundles'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Common Use Cases', 'native-content-relationships' ); ?></h3>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[wc_use_case_accessories]" value="1" <?php checked( $accessories, 1 ); ?>>
				<?php esc_html_e( 'Accessories (One-way)', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Camera → Lens → Tripod', 'native-content-relationships' ); ?></p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[wc_use_case_related_products]" value="1" <?php checked( $related, 1 ); ?>>
				<?php esc_html_e( 'Related Products (Bidirectional)', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Similar or alternative items', 'native-content-relationships' ); ?></p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[wc_use_case_bundles]" value="1" <?php checked( $bundles, 1 ); ?>>
				<?php esc_html_e( 'Bundles / Kits', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'Multiple products grouped together', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	public function render_relationship_types_manage_ui() {
		$types = array();
		if ( class_exists( 'NATICORE_Relation_Types' ) ) {
			$types = NATICORE_Relation_Types::get_types();
		}
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Relationship Types', 'native-content-relationships' ); ?></h3>
			<p class="description"><?php esc_html_e( 'These are the registered relationship types available in the editor meta box.', 'native-content-relationships' ); ?></p>
			<?php if ( empty( $types ) ) : ?>
				<p class="description"><?php esc_html_e( 'No relationship types are currently registered.', 'native-content-relationships' ); ?></p>
			<?php else : ?>
				<div class="naticore-widefat-wrap">
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Slug', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Label', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Direction', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Allowed Post Types', 'native-content-relationships' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $types as $slug => $args ) : ?>
								<tr>
									<td><code><?php echo esc_html( $slug ); ?></code></td>
									<td><?php echo esc_html( isset( $args['label'] ) ? $args['label'] : $slug ); ?></td>
									<td><?php echo esc_html( ! empty( $args['bidirectional'] ) ? __( 'Bidirectional', 'native-content-relationships' ) : __( 'One-way', 'native-content-relationships' ) ); ?></td>
									<td>
										<?php
										$allowed = isset( $args['allowed_post_types'] ) && is_array( $args['allowed_post_types'] ) ? $args['allowed_post_types'] : array();
										echo esc_html( empty( $allowed ) ? __( 'All public types', 'native-content-relationships' ) : implode( ', ', $allowed ) );
										?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_wc_enabled_objects() {
		$settings     = $this->get_settings();
		$is_wc_active = class_exists( 'WooCommerce' );
		$enabled      = isset( $settings['wc_enabled_objects'] ) && is_array( $settings['wc_enabled_objects'] ) ? $settings['wc_enabled_objects'] : array( 'product' );

		$objects = array(
			'product'           => __( 'Products', 'native-content-relationships' ),
			'product_variation' => __( 'Variations', 'native-content-relationships' ),
			'shop_order'        => __( 'Orders', 'native-content-relationships' ),
		);
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Product Relationships', 'native-content-relationships' ); ?></h3>
			<p class="description">
				<?php
				if ( $is_wc_active ) {
					esc_html_e( 'Unlock accessories, bundles, and related product logic.', 'native-content-relationships' );
				} else {
					esc_html_e( 'WooCommerce is not active. These settings will take effect once WooCommerce is enabled.', 'native-content-relationships' );
				}
				?>
			</p>
			<p class="description"><?php esc_html_e( 'Enable for WooCommerce Products', 'native-content-relationships' ); ?></p>
			<div class="naticore-checkbox-grid">
				<?php foreach ( $objects as $key => $label ) : ?>
					<label class="naticore-checkbox-item">
						<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[wc_enabled_objects][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?>>
						<span class="naticore-checkbox-label"><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	public function render_wc_sync_upsells() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['wc_sync_upsells'] ) ? (int) $settings['wc_sync_upsells'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Sync Linked Products', 'native-content-relationships' ); ?></h3>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[wc_sync_upsells]" value="1" <?php checked( $enabled, 1 ); ?>>
				<?php esc_html_e( 'Sync to WooCommerce upsells and cross-sells', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'When enabled, relationship updates can be mirrored into WooCommerce linked products for compatibility.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	public function render_remove_data_on_uninstall() {
		$current = (bool) get_option( 'naticore_remove_data_on_uninstall', false );
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Privacy & Data', 'native-content-relationships' ); ?></h3>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[remove_data_on_uninstall]" value="1" <?php checked( $current, true ); ?>>
				<?php esc_html_e( 'Remove all plugin data on uninstall', 'native-content-relationships' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'If enabled, uninstalling the plugin will delete the relationships table and plugin options. If disabled, only options are removed.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render tabs
	 */
	private function render_tabs() {
		$tabs        = $this->get_tabs();
		$current_tab = $this->current_tab;

		echo '<nav class="nav-tab-wrapper" role="tablist">';

		foreach ( $tabs as $tab_id => $tab_label ) {
			$url    = add_query_arg( 'tab', $tab_id, admin_url( 'options-general.php?page=naticore-settings' ) );
			$active = $tab_id === $current_tab ? 'nav-tab-active' : '';

			printf(
				'<a href="%s" class="nav-tab %s" role="tab" aria-selected="%s">%s</a>',
				esc_url( $url ),
				esc_attr( $active ),
				$tab_id === $current_tab ? 'true' : 'false',
				esc_html( $tab_label )
			);
		}

		echo '</nav>';
	}

	/**
	 * Get settings
	 */
	private function get_settings() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Get a specific setting
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
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
		$sanitized['max_relationships'] = isset( $input['max_relationships'] ) ? absint( $input['max_relationships'] ) : 0;

		// Auto relation
		$sanitized['auto_relation_enabled'] = isset( $input['auto_relation_enabled'] ) ? 1 : 0;

		// Prevent circular
		$sanitized['prevent_circular'] = isset( $input['prevent_circular'] ) ? 1 : 0;

		// Developer settings
		$sanitized['debug_logging']   = isset( $input['debug_logging'] ) ? 1 : 0;
		$sanitized['query_debug']     = isset( $input['query_debug'] ) ? 1 : 0;
		$sanitized['enable_rest_api'] = isset( $input['enable_rest_api'] ) ? 1 : 0;

		// WooCommerce settings
		if ( isset( $input['wc_enabled_objects'] ) && is_array( $input['wc_enabled_objects'] ) ) {
			$sanitized['wc_enabled_objects'] = array_map( 'sanitize_text_field', $input['wc_enabled_objects'] );
		} else {
			$sanitized['wc_enabled_objects'] = array( 'product' );
		}
		$sanitized['wc_sync_upsells']              = isset( $input['wc_sync_upsells'] ) ? 1 : 0;
		$sanitized['wc_use_case_accessories']      = isset( $input['wc_use_case_accessories'] ) ? 1 : 0;
		$sanitized['wc_use_case_related_products'] = isset( $input['wc_use_case_related_products'] ) ? 1 : 0;
		$sanitized['wc_use_case_bundles']          = isset( $input['wc_use_case_bundles'] ) ? 1 : 0;

		// Privacy
		$remove_data_on_uninstall              = isset( $input['remove_data_on_uninstall'] ) ? 1 : 0;
		$sanitized['remove_data_on_uninstall'] = $remove_data_on_uninstall;
		update_option( 'naticore_remove_data_on_uninstall', (bool) $remove_data_on_uninstall );

		return $sanitized;
	}

	/**
	 * Render enabled post types field
	 */
	public function render_enabled_post_types() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array( 'post', 'page' );

		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Enable Relationships For', 'native-content-relationships' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Enable relationships only where they are needed to keep the editor clean and fast.', 'native-content-relationships' ); ?></p>
			
			<div class="naticore-checkbox-grid">
				<?php foreach ( $post_types as $post_type ) : ?>
					<label class="naticore-checkbox-item">
						<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enabled_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( in_array( $post_type->name, $enabled, true ) ); ?>>
						<span class="naticore-checkbox-label"><?php echo esc_html( $post_type->label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			
		</div>
		<?php
	}

	/**
	 * Render default direction field
	 */
	public function render_default_direction() {
		$settings = $this->get_settings();
		$default  = isset( $settings['default_direction'] ) ? $settings['default_direction'] : 'unidirectional';
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Default Relationship Direction', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-radio-cards">
				<label class="naticore-radio-card <?php echo $default === 'unidirectional' ? 'selected' : ''; ?>">
					<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[default_direction]" value="unidirectional" <?php checked( $default, 'unidirectional' ); ?>>
					<div class="naticore-radio-card-content">
						<h4><?php esc_html_e( 'One-way (Recommended)', 'native-content-relationships' ); ?></h4>
						<p><?php esc_html_e( 'Best for parent → child or product → accessory relationships.', 'native-content-relationships' ); ?></p>
					</div>
				</label>
				
				<label class="naticore-radio-card <?php echo $default === 'bidirectional' ? 'selected' : ''; ?>">
					<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[default_direction]" value="bidirectional" <?php checked( $default, 'bidirectional' ); ?>>
					<div class="naticore-radio-card-content">
						<h4><?php esc_html_e( 'Bidirectional', 'native-content-relationships' ); ?></h4>
						<p><?php esc_html_e( 'Ideal for related products, alternatives, or mutual links.', 'native-content-relationships' ); ?></p>
					</div>
				</label>
			</div>
			<p class="description"><?php esc_html_e( 'Most stores use one-way for accessories and bidirectional for related products.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render cleanup on delete field
	 */
	public function render_cleanup_on_delete() {
		$settings = $this->get_settings();
		$cleanup  = isset( $settings['cleanup_on_delete'] ) ? $settings['cleanup_on_delete'] : 'remove';
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'When Content Is Deleted', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-radio-buttons">
				<label>
					<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[cleanup_on_delete]" value="remove" <?php checked( $cleanup, 'remove' ); ?>>
					<?php esc_html_e( 'Remove relationships automatically', 'native-content-relationships' ); ?>
				</label><br>
				<label>
					<input type="radio" name="<?php echo esc_attr( $this->option_name ); ?>[cleanup_on_delete]" value="keep" <?php checked( $cleanup, 'keep' ); ?>>
					<?php esc_html_e( 'Keep but mark as orphaned', 'native-content-relationships' ); ?>
				</label>
			</div>
			
			<p class="description"><?php esc_html_e( 'Orphaned relationships are ignored but kept for audits and historical data.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render max relationships field
	 */
	public function render_max_relationships() {
		$settings = $this->get_settings();
		$max      = isset( $settings['max_relationships'] ) ? $settings['max_relationships'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Maximum Relationships per Item', 'native-content-relationships' ); ?></h3>
			
			<label for="naticore-max-relationships">
				<input 
					type="number" 
					id="naticore-max-relationships"
					name="<?php echo esc_attr( $this->option_name ); ?>[max_relationships]" 
					value="<?php echo esc_attr( $max ); ?>" 
					min="0" 
					step="1" 
					class="small-text"
				>
				<span class="naticore-field-suffix">(0 = <?php esc_html_e( 'Unlimited', 'native-content-relationships' ); ?>)</span>
			</label>
			
			<p class="description"><?php esc_html_e( 'Set a limit to prevent excessive cross-links. Use 0 for unlimited (recommended for WooCommerce).', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render auto-relation field
	 */
	public function render_auto_relation() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['auto_relation_enabled'] ) ? $settings['auto_relation_enabled'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Smart Auto-Linking', 'native-content-relationships' ); ?></h3>
			
			<label>
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( $this->option_name ); ?>[auto_relation_enabled]" 
					value="1" 
					<?php checked( $enabled, 1 ); ?>
				>
				<?php esc_html_e( 'Auto-create relationship on publish', 'native-content-relationships' ); ?>
			</label>
			
			<p class="description"><?php esc_html_e( 'Automatically links a post or product to its parent page using a part_of relationship.', 'native-content-relationships' ); ?></p>
			<p class="description"><?php esc_html_e( 'Helpful for category landing pages and grouped products.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render prevent circular field
	 */
	public function render_prevent_circular() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['prevent_circular'] ) ? $settings['prevent_circular'] : 1;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Circular Relationship Protection', 'native-content-relationships' ); ?></h3>
			
			<label>
				<input 
					type="checkbox" 
					name="<?php echo esc_attr( $this->option_name ); ?>[prevent_circular]" 
					value="1" 
					<?php checked( $enabled, 1 ); ?>
				>
				<?php esc_html_e( 'Prevent circular relationships', 'native-content-relationships' ); ?>
			</label>
			
			<p class="description"><?php esc_html_e( 'Stops infinite loops (Product A → B → A). Self-links are always blocked.', 'native-content-relationships' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render debug logging field
	 */
	public function render_debug_logging() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['debug_logging'] ) ? $settings['debug_logging'] : 0;

		// Environment detection
		$environment = 'Production';
		$env_class   = 'production';
		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			$environment = WP_ENVIRONMENT_TYPE;
			$env_class   = $environment;
		}
		?>
		<div class="naticore-card">
			<div class="naticore-env-badge">
				<span class="naticore-env-label"><?php esc_html_e( 'Environment:', 'native-content-relationships' ); ?></span>
				<span class="naticore-env-status <?php echo esc_attr( $env_class ); ?>"><?php echo esc_html( ucfirst( $environment ) ); ?></span>
			</div>
			
			<h3><?php esc_html_e( 'Developer Options', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-toggles">
				<label class="naticore-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[debug_logging]" value="1" <?php checked( $enabled, 1 ); ?>>
					<span class="naticore-toggle-slider"></span>
					<span class="naticore-toggle-label"><?php esc_html_e( 'Debug Logging', 'native-content-relationships' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Log relationship operations to debug.log', 'native-content-relationships' ); ?></p>
				
				<?php
				$query_debug = isset( $settings['query_debug'] ) ? $settings['query_debug'] : 0;
				?>
				<label class="naticore-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[query_debug]" value="1" <?php checked( $query_debug, 1 ); ?>>
					<span class="naticore-toggle-slider"></span>
					<span class="naticore-toggle-label"><?php esc_html_e( 'Query Debug Mode', 'native-content-relationships' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Show query performance in admin', 'native-content-relationships' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render read-only mode field
	 */
	public function render_readonly_mode() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['readonly_mode'] ) ? $settings['readonly_mode'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Read-Only Mode', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-toggles">
				<label class="naticore-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[readonly_mode]" value="1" <?php checked( $enabled, 1 ); ?>>
					<span class="naticore-toggle-slider"></span>
					<span class="naticore-toggle-label"><?php esc_html_e( 'Enable Read-Only Mode', 'native-content-relationships' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Prevent all modifications to relationships (create, update, delete). Only read operations will be allowed.', 'native-content-relationships' ); ?></p>
			</div>
			
			<div class="naticore-warning">
				<p><strong><?php esc_html_e( '⚠️ Warning:', 'native-content-relationships' ); ?></strong></p>
				<ul>
					<li><?php esc_html_e( 'Users will not be able to create, edit, or delete relationships', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'REST API write operations will be blocked', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'Admin interface will be view-only', 'native-content-relationships' ); ?></li>
					<li><?php esc_html_e( 'Use this for maintenance or to freeze relationship data', 'native-content-relationships' ); ?></li>
				</ul>
			</div>
		</div>
		<?php
	}

	/**
	 * Render REST API field
	 */
	public function render_enable_rest_api() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['enable_rest_api'] ) ? $settings['enable_rest_api'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'REST API', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-toggles">
				<label class="naticore-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[enable_rest_api]" value="1" <?php checked( $enabled, 1 ); ?>>
					<span class="naticore-toggle-slider"></span>
					<span class="naticore-toggle-label"><?php esc_html_e( 'Enable REST API', 'native-content-relationships' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Enable REST API endpoints for headless WordPress and external applications.', 'native-content-relationships' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render query debug field
	 */
	public function render_query_debug() {
		$settings = $this->get_settings();
		$enabled  = isset( $settings['query_debug'] ) ? $settings['query_debug'] : 0;
		?>
		<div class="naticore-card">
			<h3><?php esc_html_e( 'Query Debug', 'native-content-relationships' ); ?></h3>
			
			<div class="naticore-toggles">
				<label class="naticore-toggle">
					<input type="checkbox" name="<?php echo esc_attr( $this->option_name ); ?>[query_debug]" value="1" <?php checked( $enabled, 1 ); ?>>
					<span class="naticore-toggle-slider"></span>
					<span class="naticore-toggle-label"><?php esc_html_e( 'Query Debug Mode', 'native-content-relationships' ); ?></span>
				</label>
				<p class="description"><?php esc_html_e( 'Log all database queries for debugging relationship operations.', 'native-content-relationships' ); ?></p>
			</div>
			
			<?php if ( $enabled && defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
				<div class="naticore-notice">
					<p><?php esc_html_e( 'Query debug mode is active. Check your debug.log file for detailed query information.', 'native-content-relationships' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

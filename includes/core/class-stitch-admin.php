<?php
/**
 * Stitch Admin UI - Nexus Admin Design System
 * Rebuilds the admin interface to match Stitch design mockups.
 *
 * @package Native_Content_Relationships
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Stitch_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'register_menus' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
		add_action( 'admin_init', array( $this, 'redirect_setup_wizard' ) );
	}

	/**
	 * Add nc-stitch class to body on plugin pages so CSS can target WP core elements
	 */
	public function add_admin_body_class( $classes ) {
		$hook = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$plugin_pages = array(
			'naticore-types',
			'naticore-relationships',
			'naticore-explorer',
			'naticore-reports',
			'naticore-import-export',
			'naticore-settings',
			'naticore-tools',
			'naticore-developer',
			'naticore-get-started',
			'naticore-woocommerce',
			'naticore-performance',
			'naticore-privacy',
			'naticore-wizard',
		);
		if ( in_array( $hook, $plugin_pages, true ) ) {
			$classes .= ' nc-stitch';
		}
		return $classes;
	}

	/**
	 * Enqueue CSS and JS on plugin admin pages only
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'naticore' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'nc-stitch-admin',
			NATICORE_PLUGIN_URL . 'assets/css/stitch-admin.css',
			array(),
			NATICORE_VERSION
		);

		wp_enqueue_style(
			'nc-material-symbols',
			'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'nc-inter-font',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap',
			array(),
			null
		);

		wp_enqueue_script(
			'nc-stitch-admin',
			NATICORE_PLUGIN_URL . 'assets/js/stitch-admin.js',
			array( 'jquery' ),
			NATICORE_VERSION,
			true
		);

		wp_localize_script( 'nc-stitch-admin', 'ncStitchData', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nc_stitch_nonce' ),
		) );

		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'naticore-explorer' === $current_page ) {
			wp_enqueue_style(
				'naticore-graph',
				NATICORE_PLUGIN_URL . 'assets/css/graph.css',
				array(),
				NATICORE_VERSION
			);

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
		}
	}

	/**
	 * Register top-level Relationships menu and sub-pages
	 */
	public function register_menus() {
		// Remove old orphaned menu items registered by other classes
		remove_submenu_page( 'tools.php', 'naticore-constraints' );
		remove_submenu_page( 'tools.php', 'naticore-expiration' );
		remove_submenu_page( 'tools.php', 'naticore-permissions' );
		remove_submenu_page( 'tools.php', 'naticore-webhooks' );
		remove_submenu_page( 'tools.php', 'naticore-presets' );

		// Top-level menu: Relationships (Defaults to naticore-relationships)
		add_menu_page(
			__( 'Relationships', 'native-content-relationships' ),
			__( 'Relationships', 'native-content-relationships' ),
			'manage_options',
			'naticore-relationships',
			array( $this, 'render_relationships' ),
			'dashicons-networking',
			30
		);

		// Sub-pages visible in menu
		add_submenu_page(
			'naticore-relationships',
			__( 'Relationships', 'native-content-relationships' ),
			__( 'Relationships', 'native-content-relationships' ),
			'manage_options',
			'naticore-relationships',
			array( $this, 'render_relationships' )
		);

		add_submenu_page(
			'naticore-relationships',
			__( 'Relationship Types', 'native-content-relationships' ),
			__( 'Relationship Types', 'native-content-relationships' ),
			'manage_options',
			'naticore-types',
			array( $this, 'render_types' )
		);

		add_submenu_page(
			'naticore-relationships',
			__( 'Settings', 'native-content-relationships' ),
			__( 'Settings', 'native-content-relationships' ),
			'manage_options',
			'naticore-settings',
			array( $this, 'render_settings' )
		);
		
		add_submenu_page(
			'naticore-relationships',
			__( 'Tools', 'native-content-relationships' ),
			__( 'Tools', 'native-content-relationships' ),
			'manage_options',
			'naticore-tools',
			array( $this, 'render_tools' )
		);

		add_submenu_page(
			'naticore-relationships',
			__( 'Setup Wizard', 'native-content-relationships' ),
			__( 'Setup Wizard', 'native-content-relationships' ),
			'manage_options',
			'naticore-setup-wizard',
			array( $this, 'render_setup_wizard' )
		);

		// Hidden sub-pages (accessible via Tools or Settings)
		// We use a dummy parent slug ('naticore-hidden') to keep them off the menu 
		// while maintaining permissions and avoiding PHP 8.1 null title deprecation warnings.
		add_submenu_page(
			'naticore-hidden',
			__( 'Explorer', 'native-content-relationships' ),
			__( 'Explorer', 'native-content-relationships' ),
			'manage_options',
			'naticore-explorer',
			array( $this, 'render_explorer' )
		);

		add_submenu_page(
			'naticore-hidden',
			__( 'Reports', 'native-content-relationships' ),
			__( 'Reports', 'native-content-relationships' ),
			'manage_options',
			'naticore-reports',
			array( $this, 'render_reports' )
		);

		add_submenu_page(
			'naticore-hidden',
			__( 'Import & Export', 'native-content-relationships' ),
			__( 'Import & Export', 'native-content-relationships' ),
			'manage_options',
			'naticore-import-export',
			array( $this, 'render_import_export' )
		);

		add_submenu_page(
			'naticore-hidden',
			__( 'Developer', 'native-content-relationships' ),
			__( 'Developer', 'native-content-relationships' ),
			'manage_options',
			'naticore-developer',
			array( $this, 'render_developer' )
		);
		
		// Map old settings pages to hidden pages in case links exist
		add_submenu_page( 'naticore-hidden', __( 'Get Started', 'native-content-relationships' ), __( 'Get Started', 'native-content-relationships' ), 'manage_options', 'naticore-get-started', array( $this, 'render_settings' ) );
		add_submenu_page( 'naticore-hidden', __( 'WooCommerce', 'native-content-relationships' ), __( 'WooCommerce', 'native-content-relationships' ), 'manage_options', 'naticore-woocommerce', array( $this, 'render_settings' ) );
		add_submenu_page( 'naticore-hidden', __( 'Performance', 'native-content-relationships' ), __( 'Performance', 'native-content-relationships' ), 'manage_options', 'naticore-performance', array( $this, 'render_settings' ) );
		add_submenu_page( 'naticore-hidden', __( 'Privacy & Developer', 'native-content-relationships' ), __( 'Privacy & Developer', 'native-content-relationships' ), 'manage_options', 'naticore-privacy', array( $this, 'render_settings' ) );
	}

	/**
	 * Get current page slug from $_GET['page']
	 */
	private function get_current_page() {
		return isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'naticore-relationships';
	}

	/**
	 * Render the left sidebar (shared across all pages)
	 */
	/**
	 * Render the top navigation bar (shared across all pages)
	 */
	private function render_topbar( $current_page ) {
		$title = __( 'Relationships', 'native-content-relationships' );
		if ( $current_page === 'naticore-types' ) {
			$title = __( 'Relationship Types', 'native-content-relationships' );
		} elseif ( in_array( $current_page, array( 'naticore-settings', 'naticore-get-started', 'naticore-woocommerce', 'naticore-performance', 'naticore-privacy' ), true ) ) {
			$title = __( 'Settings', 'native-content-relationships' );
		} elseif ( in_array( $current_page, array( 'naticore-tools', 'naticore-explorer', 'naticore-reports', 'naticore-import-export', 'naticore-developer' ), true ) ) {
			$title = __( 'Tools', 'native-content-relationships' );
		}

		?>
		<header class="nc-topbar">
			<div class="nc-topbar-left">
				<span class="nc-topbar-title"><?php echo esc_html( $title ); ?></span>
			</div>
			<div class="nc-topbar-right">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-types' ) ); ?>"
				   class="nc-btn nc-btn-primary">
					<span class="material-symbols-outlined" style="font-size:16px;">add</span>
					<?php esc_html_e( 'Add New Type', 'native-content-relationships' ); ?>
				</a>
				<button class="nc-btn-icon" title="<?php esc_attr_e( 'Help', 'native-content-relationships' ); ?>">
					<span class="material-symbols-outlined">help</span>
				</button>
			</div>
		</header>
		<?php
	}

	/**
	 * Render the wrapper (sidebar + topbar + main content start)
	 */
	public function render_wrapper_start( $current_page ) {
		echo '<div class="nc-stitch" style="margin: 0; min-height: 100vh;">';
		echo '<div class="nc-layout">';
		echo '<div style="flex:1; display:flex; flex-direction:column; min-width:0;">';
		$this->render_topbar( $current_page );
		echo '<div class="nc-main">';
	}

	/**
	 * Renders the wrapper end HTML for the admin pages.
	 */
	public function render_wrapper_end() {
		echo '</div></div></div></div>';
	}

	// =====================================================================
	// PAGE: Tools
	// =====================================================================
	public function render_tools() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Tools', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Advanced tools for managing, analyzing, and troubleshooting relationships.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<div class="nc-grid-12">
			<!-- Graph -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">account_tree</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Graph Explorer', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Visually explore the connections between your content objects in an interactive graph.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-explorer' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Graph', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Analytics -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">bar_chart</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Analytics & Reports', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'View reports on relationship usage, orphaned content, and database health.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-reports' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Analytics', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Import & Export -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">import_export</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Import & Export', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Migrate relationship types and data between environments safely.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-import-export' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Import/Export', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Developer -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">code</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Developer Tools', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Access APIs, REST endpoints, webhooks, and system status information.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-developer' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Developer', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Expiration -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">timer</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Relationship Expiration', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Manage auto-expiration dates for temporary relationships.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-expiration' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Expiration', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Permissions -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">admin_panel_settings</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Role Permissions', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Configure which user roles can manage or view connections.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-permissions' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Permissions', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Webhooks -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">webhook</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Webhooks', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Trigger external HTTP callbacks on relationship events.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-webhooks' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Webhooks', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Constraints -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">rule</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Constraints', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Configure limits and rules (e.g., max connections) for relationships.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-constraints' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Constraints', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Presets -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">auto_awesome</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Presets', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Pre-defined connection rules for common use cases.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-presets' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Presets', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Bulk Manager -->
			<div class="nc-col-6">
				<div class="nc-card" style="height:100%;">
					<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
						<div class="nc-flex nc-items-center nc-gap-sm">
							<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);">select_all</span>
							<h3 style="margin:0;font-size:16px;font-weight:600;"><?php esc_html_e( 'Bulk Manager', 'native-content-relationships' ); ?></h3>
						</div>
						<p class="nc-text-sm nc-text-muted" style="margin:0;">
							<?php esc_html_e( 'Change relationship types or delete multiple connections in bulk.', 'native-content-relationships' ); ?>
						</p>
						<div style="margin-top:auto;padding-top:16px;">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-bulk-manager' ) ); ?>" class="nc-btn nc-btn-secondary">
								<?php esc_html_e( 'Open Bulk Manager', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}
	
	// =====================================================================
	// PAGE: Setup Wizard — redirect to the real wizard page
	// =====================================================================
	public function redirect_setup_wizard() {
		if ( ! is_admin() ) {
			return;
		}
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'naticore-setup-wizard' === $page ) {
			wp_safe_redirect( admin_url( 'admin.php?page=naticore-wizard' ) );
			exit;
		}
	}

	// =====================================================================
	// PAGE: Developer
	// =====================================================================
	public function render_developer() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );
		
		$settings = get_option( 'naticore_settings', array() );
		$rest_enabled = ! empty( $settings['rest_api_enabled'] );
		$plugin_version = defined( 'NATICORE_VERSION' ) ? NATICORE_VERSION : '1.4.0';
		$php_version = phpversion();
		$wp_version = get_bloginfo( 'version' );
		global $wpdb;
		$mysql_version = $wpdb->get_var( 'SELECT VERSION()' );
		?>
		<div class="nc-mb-lg nc-flex nc-items-center nc-gap-sm">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-tools' ) ); ?>" class="nc-btn-icon">
				<span class="material-symbols-outlined">arrow_back</span>
			</a>
			<div>
				<h1 class="nc-headline-lg" style="margin-bottom:4px;">
					<?php esc_html_e( 'Developer Experience', 'native-content-relationships' ); ?>
				</h1>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'System status, API documentation, and webhook references.', 'native-content-relationships' ); ?>
				</p>
			</div>
		</div>

		<div class="nc-grid-12">
			<!-- Main Content -->
			<div class="nc-col-8">
				
				<!-- PHP API -->
				<div class="nc-card nc-mb-lg">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">code</span> <?php esc_html_e( 'PHP Engine', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<p class="nc-text-sm nc-text-muted nc-mb-sm"><?php esc_html_e( 'Use the NATICORE_API class to programmatically manage relationships.', 'native-content-relationships' ); ?></p>
						<pre class="nc-surface-inset nc-p-md nc-rounded nc-text-xs" style="font-family:monospace;overflow-x:auto;">
// 1. Create a relationship
NATICORE_API::add_relation( 10, 25, 'post_to_user' );

// 2. Query relationships
$related = NATICORE_API::get_related( 10, 'post', 'any' );

// 3. Remove a relationship
NATICORE_API::remove_relation( 10, 25, 'post_to_user' );
</pre>
					</div>
				</div>

				<!-- REST API -->
				<div class="nc-card nc-mb-lg">
					<div class="nc-card-header">
						<div class="nc-flex nc-justify-between nc-items-center nc-w-full">
							<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">api</span> <?php esc_html_e( 'REST API', 'native-content-relationships' ); ?></h2>
							<span class="nc-badge <?php echo $rest_enabled ? 'nc-badge-active' : 'nc-badge-draft'; ?>"><?php echo $rest_enabled ? 'Active' : 'Disabled'; ?></span>
						</div>
					</div>
					<div class="nc-card-body">
						<p class="nc-text-sm nc-text-muted nc-mb-sm"><?php esc_html_e( 'Access relationships via the WordPress REST API.', 'native-content-relationships' ); ?></p>
						<div class="nc-table-responsive">
						<table class="nc-table" style="margin-bottom:0;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Method', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Endpoint', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Description', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><span class="nc-badge nc-badge-draft">GET</span></td>
									<td><code>/wp-json/naticore/v1/relations</code></td>
									<td class="nc-text-sm nc-text-muted"><?php esc_html_e( 'List relationships', 'native-content-relationships' ); ?></td>
								</tr>
								<tr>
									<td><span class="nc-badge" style="background:#e8f5e9;color:#2e7d32;">POST</span></td>
									<td><code>/wp-json/naticore/v1/relations</code></td>
									<td class="nc-text-sm nc-text-muted"><?php esc_html_e( 'Create relationship', 'native-content-relationships' ); ?></td>
								</tr>
								<tr>
									<td><span class="nc-badge" style="background:#ffebee;color:#c62828;">DELETE</span></td>
									<td><code>/wp-json/naticore/v1/relations/{id}</code></td>
									<td class="nc-text-sm nc-text-muted"><?php esc_html_e( 'Delete relationship', 'native-content-relationships' ); ?></td>
								</tr>
							</tbody>
						</table>
						</div>
					</div>
				</div>

				<!-- Action Hooks -->
				<div class="nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">webhook</span> <?php esc_html_e( 'Action Hooks', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<p class="nc-text-sm nc-text-muted nc-mb-sm"><?php esc_html_e( 'Listen for relationship changes in your themes or plugins.', 'native-content-relationships' ); ?></p>
						<ul style="list-style:disc;margin-left:20px;" class="nc-text-sm">
							<li><code>naticore_relation_added( $relation_id, $from_id, $to_id, $type )</code></li>
							<li><code>naticore_relation_removed( $from_id, $to_id, $type )</code></li>
						</ul>
					</div>
				</div>

			</div>

			<!-- Sidebar -->
			<div class="nc-col-4">
				<div class="nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><?php esc_html_e( 'System Environment', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<div class="nc-flex nc-justify-between nc-mb-sm"><span class="nc-text-muted nc-text-xs">Plugin Version</span><span class="nc-text-xs nc-font-semibold"><?php echo esc_html( $plugin_version ); ?></span></div>
						<div class="nc-flex nc-justify-between nc-mb-sm"><span class="nc-text-muted nc-text-xs">WordPress</span><span class="nc-text-xs nc-font-semibold"><?php echo esc_html( $wp_version ); ?></span></div>
						<div class="nc-flex nc-justify-between nc-mb-sm"><span class="nc-text-muted nc-text-xs">PHP</span><span class="nc-text-xs nc-font-semibold"><?php echo esc_html( $php_version ); ?></span></div>
						<div class="nc-flex nc-justify-between"><span class="nc-text-muted nc-text-xs">MySQL</span><span class="nc-text-xs nc-font-semibold"><?php echo esc_html( $mysql_version ); ?></span></div>
					</div>
				</div>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Relationship Types
	// =====================================================================
	public function render_types() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );

		$types = NATICORE_Relation_Types::get_types();
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Types', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Define and manage the architectural connections between content objects.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<!-- Table -->
		<div class="nc-table-wrapper">
			<div style="padding:16px;border-bottom:1px solid var(--nc-outline-variant);display:flex;justify-content:space-between;align-items:center;">
				<div class="nc-flex nc-items-center nc-gap-sm">
					<div class="nc-search-wrapper" style="width:256px;">
						<span class="material-symbols-outlined">search</span>
						<input type="text" class="nc-input" placeholder="<?php esc_attr_e( 'Search relationship types...', 'native-content-relationships' ); ?>">
					</div>
					<select class="nc-select">
						<option><?php esc_html_e( 'All Statuses', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Active', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Draft', 'native-content-relationships' ); ?></option>
					</select>
				</div>
				<button class="nc-btn nc-btn-primary nc-open-modal" data-modal="nc-type-modal">
					<span class="material-symbols-outlined" style="font-size:16px;">add</span>
					<?php esc_html_e( 'New Type', 'native-content-relationships' ); ?>
				</button>
			</div>

			<table class="nc-table">
				<thead>
					<tr>
						<th style="width:25%;"><?php esc_html_e( 'Name / Slug', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Source Object', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Target Object', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Cardinality', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Status', 'native-content-relationships' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Actions', 'native-content-relationships' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $types ) ) : ?>
						<tr>
							<td colspan="6" style="text-align:center;padding:32px;color:var(--nc-on-surface-variant);">
								<?php esc_html_e( 'No relationship types defined yet. Click "New Type" to create one.', 'native-content-relationships' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $types as $slug => $type ) :
							$label      = isset( $type['label'] ) ? $type['label'] : ucwords( str_replace( '_', ' ', $slug ) );
							$source     = isset( $type['from'] ) ? $type['from'] : 'post';
							$target     = isset( $type['to'] ) ? $type['to'] : 'post';
							$card       = isset( $type['cardinality'] ) ? $type['cardinality'] : 'one-to-many';
							$active     = isset( $type['active'] ) ? $type['active'] : true;
							$bidir      = ! empty( $type['bidirectional'] );
							$is_builtin = isset( $built_in_defaults[ $slug ] );
						?>
							<tr data-slug="<?php echo esc_attr( $slug ); ?>">
								<td>
									<div>
										<div style="font-weight:600;"><?php echo esc_html( $label ); ?></div>
										<div style="font-size:11px;color:var(--nc-on-surface-variant);font-family:monospace;"><?php echo esc_html( $slug ); ?></div>
									</div>
								</td>
								<td>
									<div class="nc-flex nc-items-center nc-gap-sm">
										<span class="material-symbols-outlined" style="font-size:16px;color:var(--nc-tertiary);">description</span>
										<span><?php echo esc_html( ucwords( $source ) ); ?></span>
									</div>
								</td>
								<td>
									<div class="nc-flex nc-items-center nc-gap-sm">
										<span class="material-symbols-outlined" style="font-size:16px;color:var(--nc-tertiary);">description</span>
										<span><?php echo esc_html( ucwords( $target ) ); ?></span>
									</div>
								</td>
								<td>
									<span class="nc-badge nc-badge-type"><?php echo esc_html( ucwords( str_replace( '-', ' ', $card ) ) ); ?></span>
								</td>
								<td>
									<span class="nc-badge <?php echo $active ? 'nc-badge-active' : 'nc-badge-draft'; ?>">
										<?php echo $active ? esc_html__( 'Active', 'native-content-relationships' ) : esc_html__( 'Draft', 'native-content-relationships' ); ?>
									</span>
								</td>
								<td style="text-align:right;">
									<button class="nc-btn-icon nc-edit-type-btn"
										title="<?php esc_attr_e( 'Edit', 'native-content-relationships' ); ?>"
										data-slug="<?php echo esc_attr( $slug ); ?>"
										data-label="<?php echo esc_attr( $label ); ?>"
										data-bidirectional="<?php echo $bidir ? '1' : '0'; ?>"
										data-from="<?php echo esc_attr( $source ); ?>"
										data-to="<?php echo esc_attr( $target ); ?>"
										data-builtin="<?php echo $is_builtin ? '1' : '0'; ?>">
										<span class="material-symbols-outlined">edit</span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>

		<!-- Create Type Modal -->
		<div class="nc-modal-overlay" id="nc-type-modal">
			<div class="nc-modal">
				<div class="nc-modal-header">
					<div>
						<h2 style="font-size:18px;font-weight:600;margin:0;"><?php esc_html_e( 'Create Relationship Type', 'native-content-relationships' ); ?></h2>
						<p class="nc-text-sm nc-text-muted" style="margin:4px 0 0 0;">
							<?php esc_html_e( 'Define a new architectural connection between two object types.', 'native-content-relationships' ); ?>
						</p>
					</div>
					<button class="nc-modal-close"><span class="material-symbols-outlined">close</span></button>
				</div>
				<div class="nc-modal-body">
					<!-- Section 1: Basic Info -->
					<div class="nc-section-number"><?php esc_html_e( '1. Basic Information', 'native-content-relationships' ); ?></div>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
						<div class="nc-form-group">
							<label class="nc-form-label"><?php esc_html_e( 'Relationship Label', 'native-content-relationships' ); ?> <span style="color:var(--nc-error);">*</span></label>
							<input type="text" class="nc-input" placeholder="<?php esc_attr_e( 'e.g., Post Authors', 'native-content-relationships' ); ?>" style="height:40px;">
							<p class="nc-form-help"><?php esc_html_e( 'Human-readable name for the UI.', 'native-content-relationships' ); ?></p>
						</div>
						<div class="nc-form-group">
							<label class="nc-form-label"><?php esc_html_e( 'Relationship Slug', 'native-content-relationships' ); ?> <span style="color:var(--nc-error);">*</span></label>
							<input type="text" class="nc-input nc-font-mono" placeholder="<?php esc_attr_e( 'e.g., post_authors', 'native-content-relationships' ); ?>" style="height:40px;">
							<p class="nc-form-help"><?php esc_html_e( 'Unique identifier. Auto-generated if left blank.', 'native-content-relationships' ); ?></p>
						</div>
					</div>

					<hr style="border:none;border-top:1px solid var(--nc-outline-variant);margin:24px 0;">

					<!-- Section 2: Endpoints -->
					<div class="nc-section-number"><?php esc_html_e( '2. Connection Endpoints', 'native-content-relationships' ); ?></div>
					<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
						<div style="border:1px solid var(--nc-outline-variant);border-radius:8px;padding:16px;">
							<label class="nc-form-label"><?php esc_html_e( 'Source Object Type', 'native-content-relationships' ); ?></label>
							<select class="nc-select" style="width:100%;height:40px;">
								<option><?php esc_html_e( 'Select Source...', 'native-content-relationships' ); ?></option>
								<optgroup label="<?php esc_attr_e( 'Core Objects', 'native-content-relationships' ); ?>">
									<option>Posts</option>
									<option>Pages</option>
									<option>Users</option>
									<option>Media</option>
								</optgroup>
							</select>
						</div>
						<div style="border:1px solid var(--nc-outline-variant);border-radius:8px;padding:16px;">
							<label class="nc-form-label"><?php esc_html_e( 'Target Object Type', 'native-content-relationships' ); ?></label>
							<select class="nc-select" style="width:100%;height:40px;">
								<option><?php esc_html_e( 'Select Target...', 'native-content-relationships' ); ?></option>
								<optgroup label="<?php esc_attr_e( 'Core Objects', 'native-content-relationships' ); ?>">
									<option>Posts</option>
									<option>Pages</option>
									<option>Users</option>
									<option>Media</option>
								</optgroup>
							</select>
						</div>
					</div>

					<hr style="border:none;border-top:1px solid var(--nc-outline-variant);margin:24px 0;">

					<!-- Section 3: Cardinality -->
					<div class="nc-section-number"><?php esc_html_e( '3. Cardinality', 'native-content-relationships' ); ?></div>
					<div class="nc-cardinality-grid nc-mb-lg">
						<div class="nc-cardinality-option">
							<input type="radio" name="nc_cardinality" id="nc_card_11" value="one-to-one">
							<label for="nc_card_11">
								<span class="material-symbols-outlined">commit</span>
								<span style="font-weight:600;font-size:12px;"><?php esc_html_e( 'One to One', 'native-content-relationships' ); ?></span>
								<span style="font-size:11px;color:var(--nc-on-surface-variant);margin-top:4px;"><?php esc_html_e( '1 Source : 1 Target', 'native-content-relationships' ); ?></span>
							</label>
						</div>
						<div class="nc-cardinality-option">
							<input type="radio" name="nc_cardinality" id="nc_card_1n" value="one-to-many" checked>
							<label for="nc_card_1n">
								<span class="material-symbols-outlined">account_tree</span>
								<span style="font-weight:600;font-size:12px;"><?php esc_html_e( 'One to Many', 'native-content-relationships' ); ?></span>
								<span style="font-size:11px;color:var(--nc-on-surface-variant);margin-top:4px;"><?php esc_html_e( '1 Source : Multiple Targets', 'native-content-relationships' ); ?></span>
							</label>
						</div>
						<div class="nc-cardinality-option">
							<input type="radio" name="nc_cardinality" id="nc_card_nn" value="many-to-many">
							<label for="nc_card_nn">
								<span class="material-symbols-outlined">hub</span>
								<span style="font-weight:600;font-size:12px;"><?php esc_html_e( 'Many to Many', 'native-content-relationships' ); ?></span>
								<span style="font-size:11px;color:var(--nc-on-surface-variant);margin-top:4px;"><?php esc_html_e( 'Multiple : Multiple', 'native-content-relationships' ); ?></span>
							</label>
						</div>
					</div>

					<hr style="border:none;border-top:1px solid var(--nc-outline-variant);margin:24px 0;">

					<!-- Section 4: Advanced Settings -->
					<div class="nc-section-number"><?php esc_html_e( '4. Advanced Settings', 'native-content-relationships' ); ?></div>
					<div style="border:1px solid var(--nc-outline-variant);border-radius:8px;overflow:hidden;">
						<?php
						$toggles = array(
							array( 'id' => 'nc_toggle_bidir',  'label' => __( 'Bidirectional Querying', 'native-content-relationships' ), 'desc' => __( 'Allow querying the relationship from both Source and Target objects.', 'native-content-relationships' ), 'checked' => true ),
							array( 'id' => 'nc_toggle_sort',   'label' => __( 'Sortable Connections', 'native-content-relationships' ),     'desc' => __( 'Enable drag-and-drop manual ordering of connected items.', 'native-content-relationships' ),        'checked' => true ),
							array( 'id' => 'nc_toggle_rest',   'label' => __( 'Show in REST API', 'native-content-relationships' ),          'desc' => __( 'Expose this relationship data in standard REST endpoints.', 'native-content-relationships' ),     'checked' => true ),
							array( 'id' => 'nc_toggle_meta',   'label' => __( 'Enable Relationship Metadata', 'native-content-relationships' ), 'desc' => __( 'Allow custom fields to be attached to the connection itself.', 'native-content-relationships' ), 'checked' => false ),
						);
						foreach ( $toggles as $i => $toggle ) : ?>
							<div style="padding:16px;display:flex;justify-content:space-between;align-items:center;<?php echo $i > 0 ? 'border-top:1px solid rgba(0,0,0,0.04);' : ''; ?>">
								<div>
									<div style="font-weight:600;font-size:12px;"><?php echo esc_html( $toggle['label'] ); ?></div>
									<p style="font-size:12px;color:var(--nc-on-surface-variant);margin:2px 0 0 0;"><?php echo esc_html( $toggle['desc'] ); ?></p>
								</div>
								<label class="nc-toggle">
									<input type="checkbox" <?php checked( $toggle['checked'] ); ?>>
									<span class="nc-toggle-slider"></span>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="nc-modal-footer">
					<button class="nc-btn nc-btn-secondary nc-modal-close"><?php esc_html_e( 'Cancel', 'native-content-relationships' ); ?></button>
					<button class="nc-btn nc-btn-primary" id="nc-type-modal-submit">
						<span class="material-symbols-outlined" style="font-size:16px;">add</span>
						<span id="nc-type-modal-submit-text"><?php esc_html_e( 'Create Type', 'native-content-relationships' ); ?></span>
					</button>
				</div>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Relationships Table
	// =====================================================================
	public function render_relationships() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );

		global $wpdb;
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}content_relations" );
		$per_page   = 20;
		$current_p  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset     = ( $current_p - 1 ) * $per_page;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, p.post_title as source_title, p.post_type as source_type
				 FROM {$wpdb->prefix}content_relations r
				 LEFT JOIN {$wpdb->prefix}posts p ON r.from_id = p.ID
				 ORDER BY r.id DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
		?>
		<!-- Header Actions -->
		<div class="nc-flex nc-justify-between nc-items-center nc-mb-md">
			<div class="nc-flex nc-items-center nc-gap-sm">
				<div class="nc-search-wrapper" style="width:240px;">
					<span class="material-symbols-outlined">search</span>
					<input type="text" class="nc-input" placeholder="<?php esc_attr_e( 'Search connections...', 'native-content-relationships' ); ?>">
				</div>
				<select class="nc-select">
					<option value=""><?php esc_html_e( 'All Types', 'native-content-relationships' ); ?></option>
					<?php
					$types = NATICORE_Relation_Types::get_types();
					foreach ( $types as $slug => $type ) :
						$label = isset( $type['label'] ) ? $type['label'] : ucwords( str_replace( '_', ' ', $slug ) );
					?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="nc-btn nc-btn-secondary"><?php esc_html_e( 'Filter', 'native-content-relationships' ); ?></button>
			</div>
			<div>
				<button class="nc-btn nc-btn-primary nc-open-modal" data-modal="nc-add-relation-modal">
					<span class="material-symbols-outlined" style="font-size:16px;">add</span>
					<?php esc_html_e( 'New Connection', 'native-content-relationships' ); ?>
				</button>
			</div>
		</div>

		<div class="nc-table-wrapper">
			<div style="padding:8px 16px;border-bottom:1px solid var(--nc-outline-variant);display:flex;justify-content:space-between;align-items:center;background:#f6f7f7;">
				<div class="nc-flex nc-items-center nc-gap-sm">
					<select class="nc-select">
						<option><?php esc_html_e( 'Bulk Actions', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Delete', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Export', 'native-content-relationships' ); ?></option>
					</select>
					<button class="nc-btn nc-btn-secondary"><?php esc_html_e( 'Apply', 'native-content-relationships' ); ?></button>
					<span class="nc-text-xs nc-text-muted" style="margin-left:12px;">
						<?php
						/* translators: %s: total number of items */
						echo esc_html( sprintf( __( '%s items', 'native-content-relationships' ), number_format( $total ) ) );
						?>
					</span>
				</div>
				<div class="nc-flex nc-items-center nc-gap-sm">
					<span class="nc-text-xs nc-text-muted"><?php esc_html_e( 'Sort by:', 'native-content-relationships' ); ?></span>
					<select class="nc-select">
						<option><?php esc_html_e( 'Date (Newest)', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Source ID', 'native-content-relationships' ); ?></option>
					</select>
				</div>
			</div>

			<div class="nc-table-responsive">
			<table class="nc-table">
				<thead>
					<tr>
						<th style="width:40px;"><input type="checkbox" class="nc-select-all" style="accent-color:var(--nc-primary);"></th>
						<th><?php esc_html_e( 'Source', 'native-content-relationships' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Target', 'native-content-relationships' ); ?></th>
						<th style="width:96px;"><?php esc_html_e( 'Status', 'native-content-relationships' ); ?></th>
						<th><?php esc_html_e( 'Date', 'native-content-relationships' ); ?></th>
						<th style="width:64px;text-align:right;"><?php esc_html_e( 'Actions', 'native-content-relationships' ); ?></th>
					</tr>
				</thead>
					<tbody>
						<?php if ( empty( $results ) ) : ?>
							<tr>
								<td colspan="7" style="text-align:center;padding:64px 32px;color:var(--nc-on-surface-variant);">
									<div class="nc-flex nc-flex-col nc-items-center nc-gap-sm">
										<span class="material-symbols-outlined" style="font-size:48px;color:var(--nc-outline);">link_off</span>
										<div style="font-weight:600;font-size:16px;color:var(--nc-on-surface);margin-top:8px;"><?php esc_html_e( 'No relationships yet', 'native-content-relationships' ); ?></div>
										<div class="nc-text-sm" style="max-width:400px;margin:4px auto 0;">
											<?php esc_html_e( 'Connect your content by creating relationships between posts, pages, and other content types.', 'native-content-relationships' ); ?>
										</div>
										<div style="display:flex;gap:10px;margin-top:16px;">
											<button class="nc-btn nc-btn-primary nc-open-modal" data-modal="nc-add-relation-modal">
												<span class="material-symbols-outlined" style="font-size:16px;">add</span>
												<?php esc_html_e( 'Create First Connection', 'native-content-relationships' ); ?>
											</button>
											<?php if ( ! get_option( 'naticore_wizard_completed', false ) ) : ?>
												<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-wizard' ) ); ?>" class="nc-btn nc-btn-secondary">
													<span class="material-symbols-outlined" style="font-size:16px;">wand_2</span>
													<?php esc_html_e( 'Run Setup Wizard', 'native-content-relationships' ); ?>
												</a>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $results as $row ) :
								$source_title = ! empty( $row['source_title'] ) ? $row['source_title'] : __( '(unknown)', 'native-content-relationships' );
								$target_title = __( 'Object', 'native-content-relationships' );
								$target_id    = isset( $row['to_id'] ) ? (int) $row['to_id'] : 0;
								if ( $target_id ) {
									$t = get_post( $target_id );
									$target_title = $t ? $t->post_title : __( '(deleted)', 'native-content-relationships' );
								}
								$relation_id = (int) $row['id'];
							?>
								<tr data-relation-id="<?php echo esc_attr( $relation_id ); ?>">
									<td><input type="checkbox" class="nc-row-select" style="accent-color:var(--nc-primary);"></td>
									<td>
										<div class="nc-flex nc-items-center nc-gap-sm">
											<span class="material-symbols-outlined" style="font-size:16px;color:var(--nc-outline);">description</span>
											<a href="<?php echo esc_url( get_edit_post_link( (int) $row['from_id'] ) ); ?>" class="nc-text-primary nc-font-semibold nc-truncate" style="max-width:200px;text-decoration:none;">
												<?php echo esc_html( $source_title ); ?>
											</a>
											<span class="nc-text-xs nc-text-muted">#<?php echo esc_html( $row['from_id'] ); ?></span>
										</div>
									</td>
									<td>
										<span class="nc-badge nc-badge-type">
											<span class="material-symbols-outlined" style="font-size:12px;">arrow_forward</span>
											<?php echo esc_html( $row['type'] ); ?>
										</span>
									</td>
									<td>
										<div class="nc-flex nc-items-center nc-gap-sm">
											<span class="material-symbols-outlined" style="font-size:16px;color:var(--nc-outline);">description</span>
											<a href="<?php echo $target_id ? esc_url( get_edit_post_link( $target_id ) ) : '#'; ?>" class="nc-text-primary nc-font-semibold nc-truncate" style="max-width:200px;text-decoration:none;">
												<?php echo esc_html( $target_title ); ?>
											</a>
											<span class="nc-text-xs nc-text-muted">#<?php echo esc_html( $target_id ); ?></span>
										</div>
									</td>
									<td>
										<label class="nc-toggle">
											<input type="checkbox" checked>
											<span class="nc-toggle-slider"></span>
										</label>
									</td>
									<td class="nc-text-muted"><?php echo esc_html( human_time_diff( strtotime( $row['created_at'] ?? 'now' ) ) . ' ago' ); ?></td>
									<td style="text-align:right;">
										<button class="nc-btn-icon nc-edit-relation-btn"
											title="<?php esc_attr_e( 'Edit', 'native-content-relationships' ); ?>"
											data-relation-id="<?php echo esc_attr( $relation_id ); ?>">
											<span class="material-symbols-outlined" style="font-size:18px;">edit</span>
										</button>
										<button class="nc-btn-icon nc-delete-relation-btn"
											title="<?php esc_attr_e( 'Delete', 'native-content-relationships' ); ?>"
											style="color:var(--nc-error);"
											data-relation-id="<?php echo esc_attr( $relation_id ); ?>"
											data-from-id="<?php echo esc_attr( $row['from_id'] ); ?>"
											data-to-id="<?php echo esc_attr( $row['to_id'] ); ?>"
											data-type="<?php echo esc_attr( $row['type'] ); ?>">
											<span class="material-symbols-outlined" style="font-size:18px;">delete</span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				</div>

				<!-- Pagination -->
				<div class="nc-pagination-bar" style="padding:8px 16px;border-top:1px solid var(--nc-outline-variant);display:flex;justify-content:space-between;align-items:center;background:#f6f7f7;">
					<span class="nc-text-xs nc-text-muted">
						<?php
						$per_page   = 20;
						$total_pages = max( 1, ceil( $total / $per_page ) );
						$current_p  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
						$from_num   = ( ( $current_p - 1 ) * $per_page ) + 1;
						$to_num     = min( $current_p * $per_page, $total );
						/* translators: 1: first item number, 2: last item number, 3: total items */
						echo esc_html( sprintf( __( 'Showing %1$d to %2$d of %3$s items', 'native-content-relationships' ), $from_num, $to_num, number_format( $total ) ) );
						?>
					</span>
					<div class="nc-flex nc-items-center" style="gap:4px;">
						<a href="<?php echo esc_url( add_query_arg( 'paged', 1 ) ); ?>"
						   class="nc-btn nc-btn-secondary <?php echo $current_p <= 1 ? 'nc-disabled' : ''; ?>"
						   <?php echo $current_p <= 1 ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
							<span class="material-symbols-outlined" style="font-size:16px;">keyboard_double_arrow_left</span>
						</a>
						<a href="<?php echo esc_url( add_query_arg( 'paged', max( 1, $current_p - 1 ) ) ); ?>"
						   class="nc-btn nc-btn-secondary <?php echo $current_p <= 1 ? 'nc-disabled' : ''; ?>"
						   <?php echo $current_p <= 1 ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
							<span class="material-symbols-outlined" style="font-size:16px;">chevron_left</span>
						</a>
						<span class="nc-text-sm" style="padding:0 8px;"><?php echo esc_html( $current_p ); ?> of <?php echo esc_html( $total_pages ); ?></span>
						<a href="<?php echo esc_url( add_query_arg( 'paged', min( $total_pages, $current_p + 1 ) ) ); ?>"
						   class="nc-btn nc-btn-secondary <?php echo $current_p >= $total_pages ? 'nc-disabled' : ''; ?>"
						   <?php echo $current_p >= $total_pages ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
							<span class="material-symbols-outlined" style="font-size:16px;">chevron_right</span>
						</a>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $total_pages ) ); ?>"
						   class="nc-btn nc-btn-secondary <?php echo $current_p >= $total_pages ? 'nc-disabled' : ''; ?>"
						   <?php echo $current_p >= $total_pages ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
							<span class="material-symbols-outlined" style="font-size:16px;">keyboard_double_arrow_right</span>
						</a>
					</div>
				</div>
			</div>

			<!-- Add Relationship Modal -->
			<div class="nc-modal-overlay" id="nc-add-relation-modal">
				<div class="nc-modal" style="max-width:600px;">
					<div class="nc-modal-header">
						<div>
							<h2 style="font-size:18px;font-weight:600;margin:0;"><?php esc_html_e( 'Add Connection', 'native-content-relationships' ); ?></h2>
							<p class="nc-text-sm nc-text-muted" style="margin:4px 0 0 0;">
								<?php esc_html_e( 'Create a new relationship between two content objects.', 'native-content-relationships' ); ?>
							</p>
						</div>
						<button class="nc-modal-close"><span class="material-symbols-outlined">close</span></button>
					</div>
					<div class="nc-modal-body">
						<div class="nc-flex nc-flex-col nc-gap-lg">
							<div class="nc-form-group">
								<label class="nc-form-label"><?php esc_html_e( 'Source Object', 'native-content-relationships' ); ?> <span style="color:var(--nc-error);">*</span></label>
								<div style="position:relative;">
									<span class="material-symbols-outlined" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:16px;color:var(--nc-outline);">search</span>
									<input type="text" class="nc-input" placeholder="<?php esc_attr_e( 'Search by title or ID...', 'native-content-relationships' ); ?>" style="padding-left:32px;">
								</div>
								<p class="nc-form-help"><?php esc_html_e( 'The object where the connection originates.', 'native-content-relationships' ); ?></p>
							</div>

							<div class="nc-form-group">
								<label class="nc-form-label"><?php esc_html_e( 'Relationship Type', 'native-content-relationships' ); ?> <span style="color:var(--nc-error);">*</span></label>
								<select class="nc-select">
									<option value=""><?php esc_html_e( '-- Select Type --', 'native-content-relationships' ); ?></option>
									<?php
									foreach ( $types as $slug => $type ) :
										$label = isset( $type['label'] ) ? $type['label'] : ucwords( str_replace( '_', ' ', $slug ) );
									?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="nc-form-group">
								<label class="nc-form-label"><?php esc_html_e( 'Target Object', 'native-content-relationships' ); ?> <span style="color:var(--nc-error);">*</span></label>
								<div style="position:relative;">
									<span class="material-symbols-outlined" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);font-size:16px;color:var(--nc-outline);">search</span>
									<input type="text" class="nc-input" placeholder="<?php esc_attr_e( 'Search by title or ID...', 'native-content-relationships' ); ?>" style="padding-left:32px;">
								</div>
								<p class="nc-form-help"><?php esc_html_e( 'The destination object being connected to.', 'native-content-relationships' ); ?></p>
							</div>
						</div>
					</div>
					<div class="nc-modal-footer">
						<button class="nc-btn nc-btn-secondary nc-modal-close"><?php esc_html_e( 'Cancel', 'native-content-relationships' ); ?></button>
						<button class="nc-btn nc-btn-primary" id="nc-btn-create-relation">
							<span class="material-symbols-outlined" style="font-size:16px;">link</span>
							<?php esc_html_e( 'Create Connection', 'native-content-relationships' ); ?>
						</button>
					</div>
				</div>
			</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Explorer
	// =====================================================================
	public function render_explorer() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );
		?>
		<div class="nc-card" style="margin-bottom: 24px;">
			<div class="nc-card-body" style="display: flex; gap: 16px; align-items: center;">
				<div style="display:flex; flex-direction:column; gap:4px;">
					<label class="nc-text-sm nc-font-semibold" style="color:var(--nc-on-surface-variant);"><?php esc_html_e( 'Filter by type:', 'native-content-relationships' ); ?></label>
					<select id="naticore-graph-filter" class="nc-select" style="width: auto;">
						<option value="all"><?php esc_html_e( 'All Types', 'native-content-relationships' ); ?></option>
						<option value="post"><?php esc_html_e( 'Posts', 'native-content-relationships' ); ?></option>
						<option value="page"><?php esc_html_e( 'Pages', 'native-content-relationships' ); ?></option>
						<option value="user"><?php esc_html_e( 'Users', 'native-content-relationships' ); ?></option>
					</select>
				</div>
				<div style="display:flex; flex-direction:column; gap:4px;">
					<label class="nc-text-sm nc-font-semibold" style="color:var(--nc-on-surface-variant);"><?php esc_html_e( 'Max nodes:', 'native-content-relationships' ); ?></label>
					<select id="naticore-graph-limit" class="nc-select" style="width: auto;">
						<option value="20">20</option>
						<option value="50" selected>50</option>
						<option value="100">100</option>
						<option value="200">200</option>
					</select>
				</div>
				<div style="margin-top:20px;">
					<button type="button" id="naticore-graph-refresh" class="nc-btn nc-btn-secondary">
						<span class="material-symbols-outlined" style="font-size:18px;margin-right:4px;">refresh</span>
						<?php esc_html_e( 'Refresh', 'native-content-relationships' ); ?>
					</button>
				</div>
			</div>
		</div>

		<div class="nc-card">
			<div id="naticore-graph-container" style="background:var(--nc-surface); min-height: 500px; position: relative; border-radius: 8px 8px 0 0;">
				<div id="naticore-graph-loading" style="text-align: center; padding: 40px;">
					<span class="spinner is-active" style="float:none;margin-bottom:8px;"></span>
					<p class="nc-text-muted" style="margin:0;"><?php esc_html_e( 'Loading graph...', 'native-content-relationships' ); ?></p>
				</div>
				<canvas id="naticore-graph-canvas" style="display: none;"></canvas>
			</div>
			
			<div class="nc-card-footer" id="naticore-graph-legend" style="border-top:1px solid var(--nc-border);">
				<strong class="nc-text-sm nc-font-semibold"><?php esc_html_e( 'Legend:', 'native-content-relationships' ); ?></strong>
				<span class="nc-text-sm nc-text-muted" style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #2271b1; border-radius: 50%; margin-right: 5px; vertical-align: -1px;"></span> <?php esc_html_e( 'Posts', 'native-content-relationships' ); ?></span>
				<span class="nc-text-sm nc-text-muted" style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #00a32a; border-radius: 50%; margin-right: 5px; vertical-align: -1px;"></span> <?php esc_html_e( 'Users', 'native-content-relationships' ); ?></span>
				<span class="nc-text-sm nc-text-muted" style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #dba617; border-radius: 50%; margin-right: 5px; vertical-align: -1px;"></span> <?php esc_html_e( 'Terms', 'native-content-relationships' ); ?></span>
				<span class="nc-text-sm nc-text-muted" style="margin-left: 20px;"><span style="display: inline-block; width: 12px; height: 12px; background: #d63638; border-radius: 50%; margin-right: 5px; vertical-align: -1px;"></span> <?php esc_html_e( 'Pages', 'native-content-relationships' ); ?></span>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Reports & Analytics
	// =====================================================================
	public function render_reports() {
		global $wpdb;
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );
		?>
		<div class="nc-flex nc-justify-between nc-items-center nc-mb-xl" style="flex-wrap:wrap;gap:16px;">
			<div>
				<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
					<?php esc_html_e( 'Reports & Analytics', 'native-content-relationships' ); ?>
				</h1>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'Analyze your content connections and relationship health.', 'native-content-relationships' ); ?>
				</p>
			</div>
			<div class="nc-flex nc-items-center nc-gap-md">
				<div class="nc-flex nc-items-center" style="background:#ffffff;border:1px solid var(--nc-outline-variant);border-radius:4px;padding:4px 8px;height:32px;">
					<span class="material-symbols-outlined" style="font-size:18px;color:var(--nc-on-surface-variant);margin-right:8px;">calendar_month</span>
					<select class="nc-select" style="border:none;box-shadow:none;height:24px;background:none;">
						<option><?php esc_html_e( 'Last 30 Days', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'Last 90 Days', 'native-content-relationships' ); ?></option>
						<option><?php esc_html_e( 'This Year', 'native-content-relationships' ); ?></option>
					</select>
				</div>
				<button class="nc-btn nc-btn-secondary">
					<span class="material-symbols-outlined" style="font-size:18px;">download</span>
					<?php esc_html_e( 'Download CSV', 'native-content-relationships' ); ?>
				</button>
			</div>
		</div>

		<!-- Bento Grid -->
		<?php
		$rpt_months = 12;
		$rpt_data = array();
		for ( $i = $rpt_months - 1; $i >= 0; $i-- ) {
			$date = date( 'Y-m', strtotime( "-{$i} months" ) );
			$label = date( 'M', strtotime( "-{$i} months" ) );
			$cnt = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}content_relations WHERE DATE_FORMAT(created_at, '%%Y-%%m') = %s",
				$date
			) );
			$rpt_data[] = array( 'label' => $label, 'count' => $cnt );
		}
		$rpt_max = max( array_column( $rpt_data, 'count' ) );
		if ( $rpt_max === 0 ) $rpt_max = 1;
		$rpt_w = 1000;
		$rpt_h = 250;
		$rpt_pad = 20;
		$rpt_inner_h = $rpt_h - $rpt_pad;
		$rn = count( $rpt_data );
		$rpt_step = $rn > 1 ? $rpt_w / ( $rn - 1 ) : $rpt_w;
		$rpt_points = array();
		$rpt_area = array();
		$rpt_circles = '';
		$rpt_labels = '';
		foreach ( $rpt_data as $i => $d ) {
			$x = round( $i * $rpt_step );
			$y = round( $rpt_inner_h - ( $d['count'] / $rpt_max ) * $rpt_inner_h ) + $rpt_pad / 2;
			$rpt_points[] = "{$x} {$y}";
			$rpt_area[] = "{$x} {$y}";
			if ( $d['count'] > 0 ) {
				$rpt_circles .= '<circle cx="' . $x . '" cy="' . $y . '" fill="#2271b1" r="4" stroke="white" stroke-width="2"><title>' . esc_attr( $d['label'] . ': ' . $d['count'] ) . '</title></circle>';
			}
			$rpt_labels .= '<span style="font-size:11px;color:var(--nc-on-surface-variant);">' . esc_html( $d['label'] ) . '</span>';
		}
		$rpt_path = 'M' . implode( ' L', $rpt_points );
		$rpt_area_path = $rpt_path . " L{$rpt_w} {$rpt_h} L0 {$rpt_h} Z";
		?>
		<div class="nc-grid-12">
			<!-- Growth Chart (full width) -->
			<div class="nc-col-12 nc-card">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Relationship Growth Over Time', 'native-content-relationships' ); ?></h3>
					<span class="nc-label-sm nc-text-muted"><?php echo esc_html( $rpt_months . '-month trend' ); ?></span>
				</div>
				<div class="nc-card-body">
					<div class="nc-chart-container">
						<svg class="w-full h-full" viewBox="0 0 <?php echo esc_attr( $rpt_w ); ?> <?php echo esc_attr( $rpt_h ); ?>" preserveAspectRatio="none">
							<line stroke="#f0f0f1" stroke-width="1" x1="0" x2="<?php echo esc_attr( $rpt_w ); ?>" y1="50" y2="50"></line>
							<line stroke="#f0f0f1" stroke-width="1" x1="0" x2="<?php echo esc_attr( $rpt_w ); ?>" y1="100" y2="100"></line>
							<line stroke="#f0f0f1" stroke-width="1" x1="0" x2="<?php echo esc_attr( $rpt_w ); ?>" y1="150" y2="150"></line>
							<line stroke="#f0f0f1" stroke-width="1" x1="0" x2="<?php echo esc_attr( $rpt_w ); ?>" y1="<?php echo esc_attr( $rpt_h ); ?>" y2="<?php echo esc_attr( $rpt_h ); ?>"></line>
							<path d="<?php echo esc_attr( $rpt_area_path ); ?>" fill="rgba(34,113,177,0.1)"></path>
							<path d="<?php echo esc_attr( $rpt_path ); ?>" fill="none" stroke="#2271b1" stroke-width="2"></path>
							<?php echo $rpt_circles; // phpcs:ignore ?>
						</svg>
						<div style="position:absolute;bottom:0;width:100%;display:flex;justify-content:space-between;padding:4px 0;">
							<?php echo $rpt_labels; // phpcs:ignore ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Top Types (pie) -->
			<?php
			$type_counts = $wpdb->get_results(
				"SELECT type, COUNT(*) as cnt FROM {$wpdb->prefix}content_relations GROUP BY type ORDER BY cnt DESC"
			);
			$total_rels = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}content_relations" );
			$pie_colors = array( 'var(--nc-primary)', 'var(--nc-primary-dark)', 'var(--nc-tertiary)', 'var(--nc-surface-high)', '#7c3aed', '#059669', '#d97706' );
			$pie_legend = '';
			$pie_borders = array();
			$accumulated = 0;
			foreach ( $type_counts as $idx => $tc ) {
				$pct = $total_rels > 0 ? round( ( $tc->cnt / $total_rels ) * 100 ) : 0;
				$color = $pie_colors[ $idx % count( $pie_colors ) ];
				$type_label = isset( $type_labels[ $tc->type ] ) ? $type_labels[ $tc->type ] : $tc->type;
				$deg = round( ( $tc->cnt / max( $total_rels, 1 ) ) * 360 );
				$end_deg = $accumulated + $deg;
				$pie_borders[] = "{$color} {$accumulated}deg {$end_deg}deg";
				$accumulated += $deg;
				$pie_legend .= '<div class="nc-flex nc-justify-between nc-items-center nc-mb-sm" style="font-size:13px;">
					<div class="nc-flex nc-items-center nc-gap-sm"><span style="width:12px;height:12px;border-radius:50%;background:' . esc_attr( $color ) . ';display:inline-block;"></span> ' . esc_html( $type_label ) . '</div>
					<span class="nc-font-semibold nc-text-muted">' . esc_html( $pct . '%' ) . '</span>
				</div>';
			}
			if ( empty( $pie_borders ) ) {
				$pie_borders[] = 'var(--nc-surface-low) 0deg 360deg';
			}
			$pie_border_str = implode( ', ', $pie_borders );
			?>
			<div class="nc-col-4 nc-card" style="display:flex;flex-direction:column;">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Top Relationship Types', 'native-content-relationships' ); ?></h3>
				</div>
				<div class="nc-card-body" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;">
					<div style="width:160px;height:160px;border-radius:50%;border:16px solid var(--nc-surface-low);border-top-color:var(--nc-primary);transform:rotate(0deg);margin-bottom:24px;background:conic-gradient(<?php echo esc_attr( $pie_border_str ); ?>);"></div>
					<div class="nc-w-full">
						<?php echo $pie_legend; // phpcs:ignore ?>
					</div>
				</div>
			</div>

			<!-- Most Connected Content -->
			<?php
			$most_connected = $wpdb->get_results(
				"SELECT obj_id, COUNT(*) as cnt FROM (
					SELECT from_id as obj_id FROM {$wpdb->prefix}content_relations
					UNION ALL
					SELECT to_id as obj_id FROM {$wpdb->prefix}content_relations
				) combined GROUP BY obj_id ORDER BY cnt DESC LIMIT 5"
			);
			?>
			<div class="nc-col-8 nc-card" style="display:flex;flex-direction:column;">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Most Connected Content', 'native-content-relationships' ); ?></h3>
				</div>
				<div style="flex:1;overflow:auto;">
					<div class="nc-table-responsive">
					<table class="nc-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Content Title', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Connections', 'native-content-relationships' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $most_connected ) ) : ?>
								<tr><td colspan="3" class="nc-text-muted"><?php esc_html_e( 'No connected content yet.', 'native-content-relationships' ); ?></td></tr>
							<?php else : foreach ( $most_connected as $mc ) :
								$post = get_post( $mc->obj_id );
								if ( ! $post ) continue;
								$badge_style = $post->post_type === 'user'
									? 'font-size:10px;background:var(--nc-tertiary-container);color:#fff;'
									: 'font-size:10px;';
							?>
								<tr>
									<td><a href="<?php echo esc_url( get_edit_post_link( $mc->obj_id ) ); ?>" class="nc-text-primary" style="text-decoration:none;"><?php echo esc_html( $post->post_title ); ?></a></td>
									<td><span class="nc-badge nc-badge-type" style="<?php echo esc_attr( $badge_style ); ?>"><?php echo esc_html( ucfirst( $post->post_type ) ); ?></span></td>
									<td style="text-align:right;font-weight:600;"><?php echo esc_html( $mc->cnt ); ?></td>
								</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
					</div>
				</div>
			</div>

			<!-- Orphaned Content Report -->
			<?php
			$orphaned_posts = $wpdb->get_results(
				"SELECT p.ID, p.post_title, p.post_type, p.post_date
				 FROM {$wpdb->prefix}posts p
				 LEFT JOIN {$wpdb->prefix}content_relations r1 ON p.ID = r1.from_id
				 LEFT JOIN {$wpdb->prefix}content_relations r2 ON p.ID = r2.to_id
				 WHERE r1.id IS NULL AND r2.id IS NULL
				   AND p.post_status = 'publish'
				   AND p.post_type IN ('post','page')
				 ORDER BY p.post_date DESC
				 LIMIT 10"
			);
			$orphaned_count = $this->get_orphaned_count();
			?>
			<div class="nc-col-12 nc-card nc-mt-md">
				<div class="nc-card-header" style="background:rgba(255,218,214,0.2);border-radius:8px 8px 0 0;">
					<div class="nc-flex nc-items-center nc-gap-sm">
						<span class="material-symbols-outlined" style="color:var(--nc-error);">warning</span>
						<h3><?php esc_html_e( 'Orphaned Content Report', 'native-content-relationships' ); ?></h3>
					</div>
					<span class="nc-badge nc-badge-error"><?php echo esc_html( $orphaned_count . ' Items' ); ?></span>
				</div>
				<div style="overflow-x:auto;">
					<div class="nc-table-responsive">
					<table class="nc-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'ID', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Title', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Post Type', 'native-content-relationships' ); ?></th>
								<th><?php esc_html_e( 'Date Created', 'native-content-relationships' ); ?></th>
								<th style="text-align:right;"><?php esc_html_e( 'Action', 'native-content-relationships' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $orphaned_posts ) ) : ?>
								<tr><td colspan="5" class="nc-text-muted"><?php esc_html_e( 'No orphaned content. All posts are connected!', 'native-content-relationships' ); ?></td></tr>
							<?php else : foreach ( $orphaned_posts as $op ) : ?>
								<tr>
									<td class="nc-text-muted">#<?php echo esc_html( $op->ID ); ?></td>
									<td class="nc-font-semibold"><?php echo esc_html( $op->post_title ); ?></td>
									<td><?php echo esc_html( ucfirst( $op->post_type ) ); ?></td>
									<td class="nc-text-muted"><?php echo esc_html( date( 'M j, Y', strtotime( $op->post_date ) ) ); ?></td>
									<td style="text-align:right;"><a href="<?php echo esc_url( get_edit_post_link( $op->ID ) ); ?>" class="nc-text-primary" style="text-decoration:none;font-weight:500;"><?php esc_html_e( 'Review', 'native-content-relationships' ); ?></a></td>
								</tr>
							<?php endforeach; endif; ?>
						</tbody>
					</table>
					</div>
				</div>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Import & Export
	// =====================================================================
	public function render_import_export() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );
		?>
		<div class="nc-mb-lg">
			<?php settings_errors( 'naticore_import' ); ?>
			<?php settings_errors( 'naticore_export' ); ?>
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Import & Export Tools', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Manage your data relationships by importing from or exporting to external systems.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<!-- Tabs -->
		<div class="nc-tabs">
			<button class="nc-tab nc-import-tab active" data-tab="import"><?php esc_html_e( 'Import', 'native-content-relationships' ); ?></button>
			<button class="nc-tab nc-import-tab" data-tab="export"><?php esc_html_e( 'Export', 'native-content-relationships' ); ?></button>
		</div>

		<!-- Import Section -->
		<div id="nc-import-import">
			<div class="nc-grid-12">
				<div class="nc-col-8 nc-card">
					<div class="nc-card-header">
						<h3><?php esc_html_e( 'Import Relationships', 'native-content-relationships' ); ?></h3>
					</div>
					<div class="nc-card-body">
						<p class="nc-text-sm nc-text-muted nc-mb-lg">
							<?php esc_html_e( 'Upload a previously exported JSON file to restore or migrate relationships. Existing identical relationships will be skipped to prevent duplicates.', 'native-content-relationships' ); ?>
						</p>
						<form method="post" enctype="multipart/form-data" action="">
							<?php wp_nonce_field( 'naticore_import', 'naticore_import_nonce' ); ?>
							<input type="hidden" name="action" value="naticore_import" />
							
							<div class="nc-dropzone" style="margin-bottom: 24px;">
								<span class="material-symbols-outlined">cloud_upload</span>
								<h3 style="font-size:18px;font-weight:600;margin:0 0 8px 0;"><?php esc_html_e( 'Select JSON File', 'native-content-relationships' ); ?></h3>
								<p class="nc-text-sm nc-text-muted"><?php esc_html_e( 'Select the JSON file generated by the Native Content Relationships export tool.', 'native-content-relationships' ); ?></p>
								<div class="nc-mt-md">
									<input type="file" name="import_file" accept=".json" required />
								</div>
							</div>
							
							<div class="nc-notice nc-notice-warning nc-mb-lg">
								<strong><?php esc_html_e( 'Important:', 'native-content-relationships' ); ?></strong>
								<?php esc_html_e( 'Importing will not delete existing relationships. It only adds new ones from your file. Ensure the destination site has the same content (IDs) for the import to work correctly.', 'native-content-relationships' ); ?>
							</div>
							
							<button type="submit" class="nc-btn nc-btn-primary">
								<?php esc_html_e( 'Start Import', 'native-content-relationships' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Export Section -->
		<div id="nc-import-export" class="nc-hidden">
			<div class="nc-grid-12">
				<div class="nc-col-8 nc-card">
					<div class="nc-card-header">
						<h3><?php esc_html_e( 'Export Relationships', 'native-content-relationships' ); ?></h3>
					</div>
					<div class="nc-card-body">
						<p class="nc-text-sm nc-text-muted nc-mb-lg">
							<?php esc_html_e( 'Download all existing content relationships as a JSON file. This is recommended before performing any bulk imports or migrations.', 'native-content-relationships' ); ?>
						</p>
						<form method="post" action="">
							<?php wp_nonce_field( 'naticore_export', 'naticore_export_nonce' ); ?>
							<input type="hidden" name="action" value="naticore_export" />
							<button type="submit" class="nc-btn nc-btn-primary">
								<?php esc_html_e( 'Download Export File', 'native-content-relationships' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// PAGE: Settings (renders Get Started)
	// =====================================================================
	public function render_settings() {
		$page = $this->get_current_page();
		$this->render_wrapper_start( $page );

		$settings = get_option( 'naticore_settings', array() );
		$lazy_meta = ! empty( $settings['lazy_load_metadata'] );
		$optimized = ! empty( $settings['optimized_query_engine'] );
		$graphql = ! empty( $settings['graphql_enabled'] );
		$rest = ! empty( $settings['rest_api_enabled'] );
		$debug = ! empty( $settings['debug_logging'] );
		$anonymize = ! empty( $settings['anonymize_logs'] );
		$remove_logs = ! empty( $settings['remove_logs_on_uninstall'] );
		$remove_data = (bool) get_option( 'naticore_remove_data_on_uninstall', false );

		// Behavioral settings.
		$enabled_post_types = isset( $settings['enabled_post_types'] ) && is_array( $settings['enabled_post_types'] ) ? $settings['enabled_post_types'] : array( 'post', 'page' );
		$default_direction  = isset( $settings['default_direction'] ) ? $settings['default_direction'] : 'unidirectional';
		$manual_order       = ! empty( $settings['enable_manual_order'] );
		$bidirectional_sync = isset( $settings['bidirectional_sync'] ) ? (int) $settings['bidirectional_sync'] : 1;
		$max_relationships  = isset( $settings['ncr_max_relationships'] ) ? absint( $settings['ncr_max_relationships'] ) : 0;
		$prevent_circular   = isset( $settings['prevent_circular'] ) ? (int) $settings['prevent_circular'] : 1;
		$cleanup_on_delete  = isset( $settings['cleanup_on_delete'] ) ? $settings['cleanup_on_delete'] : 'remove';
		$auto_relation      = ! empty( $settings['auto_relation_enabled'] );
		$auto_link          = ! empty( $settings['enable_auto_link'] );
		$ai_suggestions     = ! empty( $settings['enable_ai_suggestions'] );
		?>
		<div class="nc-mb-lg">
			<h1 class="nc-headline-lg"><?php esc_html_e( 'Settings', 'native-content-relationships' ); ?></h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Manage relationship behavior, automation, performance, and data privacy.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<form action="options.php" method="post" id="naticore-settings-form">
			<?php settings_fields( 'naticore_settings' ); ?>

			<div class="nc-grid-12">

				<!-- Relationship Behavior -->
				<div class="nc-col-6 nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">tune</span> <?php esc_html_e( 'Relationship Behavior', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">

						<!-- Enabled Post Types -->
						<div class="nc-setting-row" style="flex-direction:column;align-items:flex-start;gap:8px;">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Enabled Post Types', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Choose which post types show the Related Content panel in the editor.', 'native-content-relationships' ); ?></p>
							</div>
							<div class="nc-checkbox-grid" style="display:flex;flex-wrap:wrap;gap:8px 16px;">
								<?php
								$post_types = get_post_types( array( 'public' => true ), 'objects' );
								foreach ( $post_types as $pt ) :
								?>
									<label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
										<input type="checkbox" name="naticore_settings[enabled_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $enabled_post_types, true ) ); ?> style="accent-color:var(--nc-primary);">
										<?php echo esc_html( $pt->label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<!-- Default Direction -->
						<div class="nc-setting-row" style="flex-direction:column;align-items:flex-start;gap:8px;">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Default Direction', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Default direction for new relationships. Individual types can override this.', 'native-content-relationships' ); ?></p>
							</div>
							<div style="display:flex;gap:12px;">
								<label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:8px 16px;border:1px solid var(--nc-outline-variant);border-radius:6px;<?php echo 'unidirectional' === $default_direction ? 'border-color:var(--nc-primary);background:rgba(0,0,0,0.02);' : ''; ?>">
									<input type="radio" name="naticore_settings[default_direction]" value="unidirectional" <?php checked( $default_direction, 'unidirectional' ); ?> style="accent-color:var(--nc-primary);">
									<?php esc_html_e( 'One-way', 'native-content-relationships' ); ?>
								</label>
								<label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;padding:8px 16px;border:1px solid var(--nc-outline-variant);border-radius:6px;<?php echo 'bidirectional' === $default_direction ? 'border-color:var(--nc-primary);background:rgba(0,0,0,0.02);' : ''; ?>">
									<input type="radio" name="naticore_settings[default_direction]" value="bidirectional" <?php checked( $default_direction, 'bidirectional' ); ?> style="accent-color:var(--nc-primary);">
									<?php esc_html_e( 'Bidirectional', 'native-content-relationships' ); ?>
								</label>
							</div>
						</div>

						<!-- Manual Ordering -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Manual Ordering', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Allow drag-to-reorder related items in the post editor.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[enable_manual_order]" value="1" <?php checked( $manual_order ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

						<!-- Bidirectional Sync -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Bidirectional Sync', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Auto-sync metadata between bidirectional relationship pairs.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[bidirectional_sync]" value="1" <?php checked( $bidirectional_sync, 1 ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

						<!-- Max Relationships -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Max Relationships per Item', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Limit per item to prevent excessive cross-links. 0 = unlimited.', 'native-content-relationships' ); ?></p>
							</div>
							<input type="number" name="naticore_settings[ncr_max_relationships]" value="<?php echo esc_attr( $max_relationships ); ?>" min="0" step="1" class="small-text" style="width:80px;">
						</div>

						<!-- Prevent Circular -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Prevent Circular Relationships', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Block infinite loops like A → B → A. Self-links are always blocked.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[prevent_circular]" value="1" <?php checked( $prevent_circular, 1 ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

						<!-- Cleanup on Delete -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Cleanup on Delete', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'What happens to relationships when a connected item is deleted.', 'native-content-relationships' ); ?></p>
							</div>
							<select name="naticore_settings[cleanup_on_delete]" class="nc-select" style="width:auto;">
								<option value="remove" <?php selected( $cleanup_on_delete, 'remove' ); ?>><?php esc_html_e( 'Remove relationships', 'native-content-relationships' ); ?></option>
								<option value="keep" <?php selected( $cleanup_on_delete, 'keep' ); ?>><?php esc_html_e( 'Keep as orphaned', 'native-content-relationships' ); ?></option>
							</select>
						</div>

					</div>
				</div>

				<!-- Automation -->
				<div class="nc-col-6 nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">smart_toy</span> <?php esc_html_e( 'Automation', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">

						<!-- Auto Relations -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Auto-Link on Publish', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Automatically create a part_of relationship when a post is published under a parent.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[auto_relation_enabled]" value="1" <?php checked( $auto_relation ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

						<!-- Auto Link (AI) -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'AI Auto-Link', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'When AI is available, auto-create relationships on publish based on content analysis.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[enable_auto_link]" value="1" <?php checked( $auto_link ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

						<!-- AI Suggestions -->
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'AI Suggestions', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Show AI-powered relationship suggestions in the editor sidebar.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[enable_ai_suggestions]" value="1" <?php checked( $ai_suggestions ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>

					</div>
				</div>

				<!-- Performance & Engine -->
				<div class="nc-col-6 nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">speed</span> <?php esc_html_e( 'Performance & Engine', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Lazy Load Metadata', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Only load metadata when requested. Recommended for graphs with >10k edges.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[lazy_load_metadata]" value="1" <?php checked( $lazy_meta ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Optimized Query Engine', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Bypass WP_Query for deeply nested traversals for faster lookups.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[optimized_query_engine]" value="1" <?php checked( $optimized ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Integrations & Developer -->
				<div class="nc-col-6 nc-card">
					<div class="nc-card-header">
						<h2 class="nc-headline-sm"><span class="material-symbols-outlined nc-text-primary" style="vertical-align:bottom;margin-right:4px;">code</span> <?php esc_html_e( 'Developer & APIs', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Enable REST API', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Expose connections in the WP REST API under /wp-json/naticore/v1/.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[rest_api_enabled]" value="1" <?php checked( $rest ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Enable GraphQL', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Add relationship fields to WPGraphQL queries.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[graphql_enabled]" value="1" <?php checked( $graphql ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Debug Logging', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Write query metrics and errors to debug.log.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[debug_logging]" value="1" <?php checked( $debug ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
					</div>
				</div>

				<!-- Danger Zone -->
				<div class="nc-col-6 nc-card" style="border-color:var(--nc-error);">
					<div class="nc-card-header" style="background:var(--nc-error-container);color:var(--nc-on-error-container);">
						<h2 class="nc-headline-sm" style="color:var(--nc-error);"><span class="material-symbols-outlined" style="font-size:18px;vertical-align:-3px;margin-right:4px;">warning</span><?php esc_html_e( 'Danger Zone', 'native-content-relationships' ); ?></h2>
					</div>
					<div class="nc-card-body">
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Anonymize Tracking Logs', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Scrub user IDs and IPs from internal activity logs.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[anonymize_logs]" value="1" <?php checked( $anonymize ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Remove Logs on Uninstall', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Delete all activity logs when the plugin is deleted.', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_settings[remove_logs_on_uninstall]" value="1" <?php checked( $remove_logs ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Remove Data on Uninstall', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Erase all custom tables and relationships when the plugin is deleted. (Cannot be undone)', 'native-content-relationships' ); ?></p>
							</div>
							<label class="nc-toggle">
								<input type="checkbox" name="naticore_remove_data_on_uninstall" value="1" <?php checked( $remove_data ); ?>>
								<span class="nc-toggle-slider"></span>
							</label>
						</div>
						<div class="nc-setting-row">
							<div>
								<span class="nc-font-semibold"><?php esc_html_e( 'Restart Setup Wizard', 'native-content-relationships' ); ?></span>
								<p class="nc-text-muted nc-text-xs nc-mt-sm"><?php esc_html_e( 'Re-run the setup wizard to reconfigure relationship types, presets, and post types.', 'native-content-relationships' ); ?></p>
							</div>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-wizard&restart=1' ), 'nc_wizard_restart' ) ); ?>"
								class="nc-btn nc-btn-secondary"
								style="color:var(--nc-error);border-color:var(--nc-error);"
								onclick="return confirm('<?php esc_attr_e( 'This will reset your wizard progress. Continue?', 'native-content-relationships' ); ?>');">
								<span class="material-symbols-outlined" style="font-size:16px;">restart_alt</span>
								<?php esc_html_e( 'Restart Wizard', 'native-content-relationships' ); ?>
							</a>
						</div>
					</div>
				</div>

				<div class="nc-col-12" style="padding-top:16px;">
					<?php submit_button( __( 'Save Settings', 'native-content-relationships' ), 'nc-btn nc-btn-primary', 'submit', false ); ?>
				</div>

			</div>
		</form>
		<?php
		$this->render_wrapper_end();
	}

	// =====================================================================
	// HELPER: Activity Feed
	// =====================================================================
	private function render_activity_feed() {
		global $wpdb;
		$recent = $wpdb->get_results(
			"SELECT r.id, r.from_id, r.to_id, r.type, r.created_at,
			        sf.post_title as from_title, sf.post_type as from_type,
			        st.post_title as to_title, st.post_type as to_type,
			        mu.user_login as to_user_login
			 FROM {$wpdb->prefix}content_relations r
			 LEFT JOIN {$wpdb->prefix}posts sf ON r.from_id = sf.ID
			 LEFT JOIN {$wpdb->prefix}posts st ON r.to_id = st.ID AND r.to_type = 'post'
			 LEFT JOIN {$wpdb->prefix}users mu ON r.to_id = mu.ID AND r.to_type = 'user'
			 ORDER BY r.created_at DESC
			 LIMIT 10"
		);

		if ( empty( $recent ) ) {
			echo '<p class="nc-text-muted nc-text-sm">' . esc_html__( 'No recent activity.', 'native-content-relationships' ) . '</p>';
			return;
		}

		$type_labels = array();
		$types = NATICORE_Relation_Types::get_types();
		foreach ( $types as $slug => $type ) {
			$type_labels[ $slug ] = $type['label'];
		}
		?>
		<div>
			<?php foreach ( $recent as $r ) :
				$from_name = $r->from_title ? $r->from_title : '#' . $r->from_id;
				if ( $r->to_type === 'user' ) {
					$to_name = $r->to_user_login ? $r->to_user_login : '#' . $r->to_id;
				} else {
					$to_name = $r->to_title ? $r->to_title : '#' . $r->to_id;
				}
				$type_label = isset( $type_labels[ $r->type ] ) ? $type_labels[ $r->type ] : $r->type;
				$icon = 'link';
				$icon_class = 'link';
				if ( $r->type === 'parent_of' || $r->type === 'featured_in' ) {
					$icon = 'account_tree';
					$icon_class = 'type';
				} elseif ( $r->type === 'depends_on' ) {
					$icon = 'link';
					$icon_class = 'link';
				} elseif ( $r->type === 'references' ) {
					$icon = 'format_quote';
					$icon_class = 'link';
				}
				$time_ago = human_time_diff( strtotime( $r->created_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'native-content-relationships' );
			?>
				<div class="nc-activity-item">
					<div class="nc-activity-icon <?php echo esc_attr( $icon_class ); ?>">
						<span class="material-symbols-outlined"><?php echo esc_html( $icon ); ?></span>
					</div>
					<div>
						<p class="nc-activity-text">
							<strong><?php echo esc_html( $from_name ); ?></strong>
							&xrarr;
							<strong><?php echo esc_html( $to_name ); ?></strong>
						</p>
						<div class="nc-activity-meta">
							<span style="padding:2px 8px;background:var(--nc-surface-variant);border-radius:4px;font-size:11px;"><?php echo esc_html( $type_label ); ?></span>
							<span><?php echo esc_html( $time_ago ); ?></span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// =====================================================================
	// HELPER: Top Types Bar Chart
	// =====================================================================
	private function render_top_types_bar() {
		$types = array(
			array( 'label' => 'Author &rarr; Post',       'pct' => 45 ),
			array( 'label' => 'Product &rarr; Category',   'pct' => 30 ),
			array( 'label' => 'Company &rarr; Employee',   'pct' => 15 ),
			array( 'label' => 'Event &rarr; Location',     'pct' => 10 ),
		);
		?>
		<div class="nc-bar-chart">
			<?php foreach ( $types as $i => $t ) :
				$opacity = 1 - ( $i * 0.2 );
			?>
				<div>
					<div class="nc-bar-item-label">
						<span><?php echo wp_kses_post( $t['label'] ); ?></span>
						<span class="nc-font-semibold nc-text-muted"><?php echo esc_html( $t['pct'] ); ?>%</span>
					</div>
					<div class="nc-bar-track">
						<div class="nc-bar-fill" style="width:<?php echo esc_attr( $t['pct'] ); ?>%;opacity:<?php echo esc_attr( $opacity ); ?>;"></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	// =====================================================================
	// HELPER: Database counts
	// =====================================================================
	private function get_orphaned_count() {
		global $wpdb;
		$count = $wpdb->get_var(
			"SELECT COUNT(*)
			 FROM {$wpdb->prefix}posts p
			 LEFT JOIN {$wpdb->prefix}content_relations r1 ON p.ID = r1.from_id
			 LEFT JOIN {$wpdb->prefix}content_relations r2 ON p.ID = r2.to_id
			 WHERE r1.id IS NULL AND r2.id IS NULL
			   AND p.post_status = 'publish'
			   AND p.post_type IN ('post','page')"
		);
		return (int) $count;
	}

	private function get_type_count() {
		$types = NATICORE_Relation_Types::get_types();
		return count( $types );
	}

	private function get_connected_objects_count() {
		global $wpdb;
		$count = $wpdb->get_var(
			"SELECT COUNT(DISTINCT obj_id) FROM (
				SELECT from_id as obj_id FROM {$wpdb->prefix}content_relations
				UNION
				SELECT to_id as obj_id FROM {$wpdb->prefix}content_relations
			) as combined"
		);
		return (int) $count;
	}

	private function format_number( $number ) {
		if ( $number >= 1000000 ) {
			return round( $number / 1000000, 1 ) . 'M';
		} elseif ( $number >= 1000 ) {
			return round( $number / 1000, 1 ) . 'K';
		}
		return number_format( $number );
	}

}

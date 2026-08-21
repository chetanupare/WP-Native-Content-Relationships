<?php
/**
 * Plugin Name: Native Content Relationships
 * Plugin URI: https://chetanupare.github.io/WP-Native-Content-Relationships/
 * Description: A native content relationship system for WordPress. Relate posts, pages, custom post types, users, and terms with semantic relationship types.
 * Version: 1.4.1
 * Author: Chetan Upare
 * Author URI: https://chetanupare.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: native-content-relationships
 * Requires at least: 5.0
 * Tested up to: 7.1.0
 * Requires PHP: 7.4
 * GitHub Plugin URI: https://github.com/chetanupare/WP-Native-Content-Relationships
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define( 'NATICORE_VERSION', '1.4.2' );
define('NATICORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NATICORE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NATICORE_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('NCR_SCHEMA_VERSION', '1.5');

/**
 * Main plugin class
 *
 * @phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Main plugin class, prefix is NATICORE_
 */
class NATICORE_Plugin
{

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Database table name
	 */
	private $table_name;

	/**
	 * Get instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'content_relations';

		// Activation/Deactivation hooks
		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		// Initialize on init so textdomains are loaded
		add_action('init', array($this, 'init'));
	}

	/**
	 * Initialize plugin
	 */
	public function init()
	{

		// Load includes first
		$this->load_includes();

		// Initialize relation types first
		NATICORE_Relation_Types::init();

		// Initialize components
		NATICORE_Database::get_instance();
		NATICORE_Settings::get_instance();
		NATICORE_Meta_API::get_instance();
		NATICORE_Capabilities::get_instance();
		NATICORE_Cleanup::get_instance();
		NATICORE_API::get_instance();
		NATICORE_Admin::get_instance();
		NATICORE_Sidebar::get_instance();
		NATICORE_Query::get_instance();
		NATICORE_REST_API::get_instance();

		// Register presets admin menu
		add_action( 'admin_menu', array( 'NATICORE_Presets', 'add_admin_menu' ) );

		// Initialize revision history tracking
		new NATICORE_Revision_History();

		// Initialize bidirectional sync
		new NATICORE_Bidirectional_Sync();

		// Initialize GraphQL support (only if WPGraphQL is active)
		new NATICORE_GraphQL();

		// Initialize constraints & cardinality
		new NATICORE_Constraints();
		add_action( 'admin_menu', array( 'NATICORE_Constraints', 'add_admin_menu' ) );

		// Initialize status workflow
		new NATICORE_Status();
		add_action( 'wp_ajax_naticore_change_status', array( 'NATICORE_Status', 'ajax_change_status' ) );

		// Initialize expiration
		new NATICORE_Expiration();
		add_action( 'admin_menu', array( 'NATICORE_Expiration', 'add_admin_menu' ) );

		// Initialize permissions
		new NATICORE_Permissions();
		add_action( 'admin_menu', array( 'NATICORE_Permissions', 'add_admin_menu' ) );

		// Initialize cloning
		new NATICORE_Cloning();
		add_filter( 'post_row_actions', array( 'NATICORE_Cloning', 'add_clone_action' ), 10, 2 );

		// Initialize webhooks
		new NATICORE_Webhooks();
		add_action( 'admin_menu', array( 'NATICORE_Webhooks', 'add_admin_menu' ) );

		// Initialize caching layer
		new NATICORE_Cache();

		// Initialize shortcodes
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/frontend/class-shortcodes.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/frontend/class-shortcodes.php';
			NATICORE_Shortcodes::get_instance();
		}

		// Register classic widget
		add_action('widgets_init', array($this, 'register_widget'));

		// Initialize WooCommerce integration (optional, no fatal errors if WC not active)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-woocommerce.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-woocommerce.php';
			NATICORE_WooCommerce::get_instance();
		}

		// Initialize ACF integration (optional)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-acf.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-acf.php';
			NATICORE_ACF::get_instance();
		}

		// Initialize WPML/Polylang integration (optional)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-wpml.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-wpml.php';
			NATICORE_WPML::get_instance();
		}

		// Initialize SEO integration (optional)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-seo.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-seo.php';
			NATICORE_SEO::get_instance();
		}

		// Initialize AI Suggestions (WordPress 7.0+)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/core/class-ai-suggestions.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/core/class-ai-suggestions.php';
			NATICORE_AI_Suggestions::get_instance();
		}

		// DEPRECATED: Graph class replaced by Explorer (naticore-explorer page).
		// class-graph.php retained for backward compatibility but no longer loaded.

		// Initialize Bulk Manager
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-bulk-manager.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-bulk-manager.php';
			NATICORE_Bulk_Manager::get_instance();
		}

		// DEPRECATED: Analytics class replaced by Reports (naticore-reports page).
		// class-analytics.php retained for backward compatibility but no longer loaded.

		// Initialize Templates
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/frontend/class-templates.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/frontend/class-templates.php';
			NATICORE_Templates::get_instance();
		}

		// Duplicate Post integration (hooks into Yoast Duplicate Post when present)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-duplicate-post.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-duplicate-post.php';
			NATICORE_Duplicate_Post::init();
		}

		// Initialize Editor integration (optional)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/integrations/class-editors.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/integrations/class-editors.php';
			NATICORE_Editors::get_instance();
		}

		// Initialize Gutenberg Relationship Sidebar
		NATICORE_Sidebar::get_instance();

		// Initialize Stitch Admin UI (Nexus Admin Design System)
		NATICORE_Stitch_Admin::get_instance();

		// Initialize Setup Wizard
		new NC_Wizard();

		// DEPRECATED: Overview class replaced by Relationships page (naticore-relationships).
		// class-overview.php retained for backward compatibility but no longer loaded.

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-integrity.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-integrity.php';
			NATICORE_Integrity::get_instance();
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-site-health.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-site-health.php';
			NATICORE_Site_Health::get_instance();
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-import-export.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-import-export.php';
			NATICORE_Import_Export::get_instance();
		}

		// Initialize User Relationships
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/user/class-user-relations.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/user/class-user-relations.php';
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/user/class-user-relations-ajax.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/user/class-user-relations-ajax.php';
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/cli/class-wp-cli.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/cli/class-wp-cli.php';
		}

		// Initialize Elementor Integration (only if Elementor is active)
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/elementor/class-elementor-integration.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/elementor/class-elementor-integration.php';
			NATICORE_Elementor_Integration::get_instance();
		}

		// Initialize Elementor AJAX Handler
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/elementor/class-ajax-handler.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/elementor/class-ajax-handler.php';
			NATICORE_Elementor_Ajax_Handler::get_instance();
		}

		// Load Elementor templates
		if (file_exists(NATICORE_PLUGIN_DIR . 'assets/templates/elementor-controls.php')) {
			require_once NATICORE_PLUGIN_DIR . 'assets/templates/elementor-controls.php';
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-orphaned.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-orphaned.php';
			NATICORE_Orphaned::get_instance();
		}

		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/tools/class-auto-relations.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/tools/class-auto-relations.php';
			NATICORE_Auto_Relations::get_instance();
		}

		// Load fluent API
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/frontend/class-fluent-api.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/frontend/class-fluent-api.php';
		}
	}

	/**
	 * Load includes
	 */
	private function load_includes()
	{
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-database.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-relation-types.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-settings.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-meta-api.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-api.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-admin.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-sidebar.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-query.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-rest-api.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-capabilities.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-cleanup.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-object-search.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-presets.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-revision-history.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-bidirectional-sync.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-graphql.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-constraints.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-status.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-expiration.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-permissions.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-cloning.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-webhooks.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-cache.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-stitch-admin.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-wizard.php';
	}

	/**
	 * Load includes (public method for activation)
	 */
	public static function load_includes_static()
	{
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-database.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/core/class-relation-types.php';
	}

	/**
	 * Activation
	 */
	public function activate()
	{
		self::load_includes_static();
		NATICORE_Database::create_table();
		flush_rewrite_rules();

		// Set transient to show activation notice
		set_transient('naticore_activation_notice', true, 30);
	}

	/**
	 * Deactivation
	 */
	public function deactivate()
	{
		flush_rewrite_rules();
	}

	/**
	 * Register classic Related Content widget
	 */
	public function register_widget()
	{
		if (file_exists(NATICORE_PLUGIN_DIR . 'includes/frontend/class-widget.php')) {
			require_once NATICORE_PLUGIN_DIR . 'includes/frontend/class-widget.php';
			register_widget('NATICORE_Related_Content_Widget');
		}
	}

	/**
	 * Get table name
	 */
	public function get_table_name()
	{
		return $this->table_name;
	}
}

// Initialize plugin
NATICORE_Plugin::get_instance();

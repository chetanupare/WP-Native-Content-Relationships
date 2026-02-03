<?php
/**
 * Plugin Name: Native Content Relationships
 * Plugin URI: https://wordpress.org/plugins/native-content-relationships
 * Description: A native content relationship system for WordPress. Relate posts, pages, custom post types, users, and terms with semantic relationship types.
 * Version: 1.0.12
 * Author: Chetan Upare
 * Author URI: https://github.com/chetanupare
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: native-content-relationships
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * GitHub Plugin URI: https://github.com/chetanupare/WP-Native-Content-Relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'NATICORE_VERSION', '1.0.12' );
define( 'NATICORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NATICORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NATICORE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 *
 * @phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Main plugin class, prefix is NATICORE_
 */
class NATICORE_Plugin {

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
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'content_relations';

		// Activation/Deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Initialize
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {

		// Load includes first
		$this->load_includes();

		// Initialize relation types first
		NATICORE_Relation_Types::init();

		// Initialize components
		NATICORE_Database::get_instance();
		NATICORE_Settings::get_instance();
		NATICORE_Capabilities::get_instance();
		NATICORE_Cleanup::get_instance();
		NATICORE_API::get_instance();
		NATICORE_Admin::get_instance();
		NATICORE_Query::get_instance();
		NATICORE_REST_API::get_instance();

		// Initialize WooCommerce integration (optional, no fatal errors if WC not active)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-woocommerce.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-woocommerce.php';
			NATICORE_WooCommerce::get_instance();
		}

		// Initialize ACF integration (optional)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-acf.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-acf.php';
			NATICORE_ACF::get_instance();
		}

		// Initialize WPML/Polylang integration (optional)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-wpml.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-wpml.php';
			NATICORE_WPML::get_instance();
		}

		// Initialize SEO integration (optional)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-seo.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-seo.php';
			NATICORE_SEO::get_instance();
		}

		// Initialize Editor integration (optional)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-editors.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-editors.php';
			NATICORE_Editors::get_instance();
		}

		// Initialize MVP features
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-overview.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-overview.php';
			NATICORE_Overview::get_instance();
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-integrity.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-integrity.php';
			NATICORE_Integrity::get_instance();
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-import-export.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-import-export.php';
			NATICORE_Import_Export::get_instance();
		}

		// Initialize User Relationships
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-user-relations.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-user-relations.php';
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-user-relations-ajax.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-user-relations-ajax.php';
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-wp-cli.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-wp-cli.php';
		}

		// Initialize Elementor Integration (only if Elementor is active)
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/elementor/class-elementor-integration.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/elementor/class-elementor-integration.php';
			NATICORE_Elementor_Integration::get_instance();
		}

		// Initialize Elementor AJAX Handler
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/elementor/class-ajax-handler.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/elementor/class-ajax-handler.php';
			NATICORE_Elementor_Ajax_Handler::get_instance();
		}

		// Load Elementor templates
		if ( file_exists( NATICORE_PLUGIN_DIR . 'assets/templates/elementor-controls.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'assets/templates/elementor-controls.php';
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-orphaned.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-orphaned.php';
			NATICORE_Orphaned::get_instance();
		}

		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-auto-relations.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-auto-relations.php';
			NATICORE_Auto_Relations::get_instance();
		}

		// Load fluent API
		if ( file_exists( NATICORE_PLUGIN_DIR . 'includes/class-fluent-api.php' ) ) {
			require_once NATICORE_PLUGIN_DIR . 'includes/class-fluent-api.php';
		}
	}

	/**
	 * Load includes
	 */
	private function load_includes() {
		require_once NATICORE_PLUGIN_DIR . 'includes/class-database.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-relation-types.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-settings.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-api.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-admin.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-query.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-capabilities.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-cleanup.php';
	}

	/**
	 * Load includes (public method for activation)
	 */
	public static function load_includes_static() {
		require_once NATICORE_PLUGIN_DIR . 'includes/class-database.php';
		require_once NATICORE_PLUGIN_DIR . 'includes/class-relation-types.php';
	}

	/**
	 * Activation
	 */
	public function activate() {
		self::load_includes_static();
		NATICORE_Database::create_table();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Get table name
	 */
	public function get_table_name() {
		return $this->table_name;
	}
}

// Initialize plugin
NATICORE_Plugin::get_instance();

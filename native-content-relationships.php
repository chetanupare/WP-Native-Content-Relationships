<?php
/**
 * Plugin Name: Native Content Relationships
 * Plugin URI: https://wordpress.org/plugins/native-content-relationships
 * Description: A native content relationship system for WordPress. Relate posts, pages, custom post types, and media with semantic relationship types.
 * Version: 1.0.1
 * Author: Chetan Upare
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: native-content-relationships
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'WPNCR_VERSION', '1.0.1' );
define( 'WPNCR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNCR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPNCR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 * 
 * @phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Main plugin class, prefix is WPNCR_
 */
class WPNCR_Plugin {
	
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
		WPNCR_Relation_Types::init();
		
		// Initialize components
		WPNCR_Database::get_instance();
		WPNCR_Settings::get_instance();
		WPNCR_Capabilities::get_instance();
		WPNCR_Cleanup::get_instance();
		WPNCR_API::get_instance();
		WPNCR_Admin::get_instance();
		WPNCR_Query::get_instance();
		WPNCR_REST_API::get_instance();
		
		// Initialize WooCommerce integration (optional, no fatal errors if WC not active)
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-woocommerce.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-woocommerce.php';
			WPNCR_WooCommerce::get_instance();
		}
		
		// Initialize ACF integration (optional)
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-acf.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-acf.php';
			WPNCR_ACF::get_instance();
		}
		
		// Initialize WPML/Polylang integration (optional)
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-wpml.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-wpml.php';
			WPNCR_WPML::get_instance();
		}
		
		// Initialize SEO integration (optional)
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-seo.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-seo.php';
			WPNCR_SEO::get_instance();
		}
		
		// Initialize Editor integration (optional)
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-editors.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-editors.php';
			WPNCR_Editors::get_instance();
		}
		
		// Initialize MVP features
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-overview.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-overview.php';
			WPNCR_Overview::get_instance();
		}
		
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-integrity.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-integrity.php';
			WPNCR_Integrity::get_instance();
		}
		
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-import-export.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-import-export.php';
			WPNCR_Import_Export::get_instance();
		}
		
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-wp-cli.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-wp-cli.php';
		}
		
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-orphaned.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-orphaned.php';
			WPNCR_Orphaned::get_instance();
		}
		
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-auto-relations.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-auto-relations.php';
			WPNCR_Auto_Relations::get_instance();
		}
		
		// Load fluent API
		if ( file_exists( WPNCR_PLUGIN_DIR . 'includes/class-fluent-api.php' ) ) {
			require_once WPNCR_PLUGIN_DIR . 'includes/class-fluent-api.php';
		}
		
	}
	
	/**
	 * Load includes
	 */
	private function load_includes() {
		require_once WPNCR_PLUGIN_DIR . 'includes/class-database.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-relation-types.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-settings.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-api.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-admin.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-query.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-capabilities.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-cleanup.php';
	}
	
	/**
	 * Load includes (public method for activation)
	 */
	public static function load_includes_static() {
		require_once WPNCR_PLUGIN_DIR . 'includes/class-database.php';
		require_once WPNCR_PLUGIN_DIR . 'includes/class-relation-types.php';
	}
	
	/**
	 * Activation
	 */
	public function activate() {
		self::load_includes_static();
		WPNCR_Database::create_table();
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
WPNCR_Plugin::get_instance();

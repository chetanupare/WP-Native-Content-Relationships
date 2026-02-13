<?php
/**
 * Database handler for content relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Database {

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
		$this->maybe_upgrade();
	}

	/**
	 * Maybe upgrade database schema
	 */
	public function maybe_upgrade() {
		$current_schema = get_option( 'ncr_schema_version', '1.0' );

		if ( version_compare( $current_schema, NCR_SCHEMA_VERSION, '<' ) ) {
			self::create_table();
			
			// Fallback: dbDelta is notoriously finicky with indexes on existing tables.
			// Force add the index if it still doesn't exist.
			global $wpdb;
			$table = self::get_table_name();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Schema audit
			$index_exists = $wpdb->get_var( $wpdb->prepare( "SHOW INDEX FROM $table WHERE Key_name = %s", 'type_lookup' ) );
			
			if ( ! $index_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Performance optimization
				$wpdb->query( "ALTER TABLE $table ADD INDEX type_lookup (type, from_id, to_id)" );
			}

			update_option( 'ncr_schema_version', NCR_SCHEMA_VERSION );
		}

		// Optional: relation_order column (used only when "Manual order" is enabled in settings).
		self::maybe_add_relation_order_column();
	}

	/**
	 * Add relation_order column if missing (optional feature, gated by enable_manual_order setting).
	 */
	public static function maybe_add_relation_order_column() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'content_relations';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema check
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
		if ( ! $table_exists ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Schema check
		$columns = $wpdb->get_col( "DESCRIBE `{$table_name}`" );
		if ( in_array( 'relation_order', $columns, true ) ) {
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from prefix
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN relation_order int(11) unsigned NOT NULL DEFAULT 0 AFTER to_type" );
	}

	/**
	 * Create database table
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'content_relations';
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists and has old schema
		// Note: Table name is safe (from prefix + constant string)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema check
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

		if ( $table_exists ) {
			// Check for old column names
			// DESCRIBE doesn't accept prepared statements with quoted identifiers
			// Table name is safe - comes from $wpdb->prefix which is trusted
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Schema check, DESCRIBE doesn't support prepared statements
			$columns = $wpdb->get_col( "DESCRIBE `{$table_name}`" );
			if ( in_array( 'object_id', $columns, true ) ) {
				// Migrate old schema to new
				self::migrate_table_schema();
				return;
			}
			// Check if we need to add user relationship columns
			if ( ! in_array( 'to_type', $columns, true ) ) {
				self::add_user_relationship_columns();
				return;
			}
			// Check if we need to add term relationship columns
			if ( ! in_array( 'to_term_id', $columns, true ) ) {
				self::add_term_relationship_columns();
				return;
			}
		}

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			from_id bigint(20) unsigned NOT NULL,
			to_id bigint(20) unsigned NOT NULL,
			type varchar(50) NOT NULL DEFAULT 'related_to',
			direction varchar(20) NOT NULL DEFAULT 'bidirectional',
			to_type enum('post','user','term') NOT NULL DEFAULT 'post',
			relation_order int(11) unsigned NOT NULL DEFAULT 0,
			to_user_id bigint(20) unsigned NULL,
			to_term_id bigint(20) unsigned NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY  from_id (from_id),
			KEY  to_id (to_id),
			KEY  type (type),
			KEY  from_type (from_id, type),
			KEY  to_type (to_id, type),
			KEY  to_user_id (to_user_id),
			KEY  to_term_id (to_term_id),
			KEY  to_type_combined (to_type, to_id),
			KEY  type_lookup (type, from_id, to_id),
			UNIQUE KEY  unique_relation (from_id, to_id, type, to_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Update both version markers
		update_option( 'naticore_db_version', NATICORE_VERSION );
		update_option( 'ncr_schema_version', NCR_SCHEMA_VERSION );
	}

	/**
	 * Migrate table schema from old to new
	 */
	private static function migrate_table_schema() {
		global $wpdb;

		// Table name is safe - comes from $wpdb->prefix which is trusted
		$table_name = $wpdb->prefix . 'content_relations';

		// Rename columns
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` CHANGE object_id from_id bigint(20) unsigned NOT NULL" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` CHANGE related_id to_id bigint(20) unsigned NOT NULL" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` CHANGE relation_type type varchar(50) NOT NULL DEFAULT 'related_to'" );

		// Drop old indexes
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX object_id" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX related_id" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX relation_type" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX object_relation" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX related_relation" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX unique_relation" );

		// Add new indexes
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX from_id (from_id)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX to_id (to_id)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX type (type)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX from_type (from_id, type)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX to_type (to_id, type)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE KEY unique_relation (from_id, to_id, type)" );

		update_option( 'naticore_db_version', NATICORE_VERSION );
	}

	/**
	 * Add user relationship columns to existing table
	 */
	private static function add_user_relationship_columns() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'content_relations';

		// Add to_type column
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN to_type enum('post','user') NOT NULL DEFAULT 'post'" );

		// Add to_user_id column
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN to_user_id bigint(20) unsigned NULL" );

		// Add new indexes
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX to_user_id (to_user_id)" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX to_type_combined (to_type, to_id)" );

		// Drop and recreate unique constraint to include to_type
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` DROP INDEX unique_relation" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD UNIQUE KEY unique_relation (from_id, to_id, type, to_type)" );

		update_option( 'naticore_db_version', NATICORE_VERSION );
	}

	/**
	 * Add term relationship columns to existing table
	 */
	private static function add_term_relationship_columns() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'content_relations';

		// Update to_type enum to include 'term'
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` MODIFY COLUMN to_type enum('post','user','term') NOT NULL DEFAULT 'post'" );

		// Add to_term_id column
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD COLUMN to_term_id bigint(20) unsigned NULL" );

		// Add index for term_id
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Table name is safe (from $wpdb->prefix), schema migration requires direct queries
		$wpdb->query( "ALTER TABLE `{$table_name}` ADD INDEX to_term_id (to_term_id)" );

		update_option( 'naticore_db_version', NATICORE_VERSION );
	}

	/**
	 * Get table name
	 *
	 * @return string Table name (safe for use in SQL queries)
	 */
	public static function get_table_name() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'content_relations';
		// Validate table name format (alphanumeric, underscore, and $wpdb->prefix only)
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table_name ) ) {
			return '';
		}
		return $table_name;
	}

	/**
	 * Escape table name for use in SQL queries
	 *
	 * @param string $table_name Table name
	 * @return string Escaped table name
	 */
	public static function escape_table_name( $table_name ) {
		global $wpdb;
		// Use backticks to escape table name
		return '`' . str_replace( '`', '``', $table_name ) . '`';
	}
}

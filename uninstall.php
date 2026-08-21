<?php
/**
 * Uninstall handler for WP Native Content Relationships
 *
 * This file is executed when the plugin is uninstalled.
 * It only runs if WP_UNINSTALL_PLUGIN is defined.
 */

// Exit if accessed directly or if uninstall not called from WordPress
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Exit if uninstall not called for this plugin
if ( 'native-content-relationships/native-content-relationships.php' !== WP_UNINSTALL_PLUGIN ) {
	exit;
}

// Check user permissions
if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}

// Check if we should remove data (respect user choice)
$naticore_remove_data = get_option( 'naticore_remove_data_on_uninstall', false );

if ( ! $naticore_remove_data ) {
	// User chose to keep data, just remove options
	delete_option( 'naticore_settings' );
	delete_option( 'naticore_db_version' );
	delete_option( 'naticore_last_integrity_check' );
	delete_option( 'naticore_last_orphaned_check' );
	delete_option( 'naticore_orphaned_count' );
	delete_option( 'naticore_remove_data_on_uninstall' );
	return;
}

// Remove all plugin options
delete_option( 'naticore_settings' );
delete_option( 'naticore_db_version' );
delete_option( 'naticore_last_integrity_check' );
delete_option( 'naticore_last_orphaned_check' );
delete_option( 'naticore_orphaned_count' );
delete_option( 'naticore_remove_data_on_uninstall' );

// Remove transients
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_naticore_%'" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_naticore_%'" );

// Remove database table
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Uninstall cleanup
$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}content_relations`" );

// Remove user meta (if any)
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'naticore_%'" );

<?php
/**
 * Safe Deletion Handling
 * Cleans up relationships when content is trashed or deleted
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_Cleanup {
	
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
		// Clean up on post deletion
		add_action( 'before_delete_post', array( $this, 'cleanup_on_delete' ), 10, 1 );
		
		// Optionally clean up on trash (configurable)
		$cleanup_on_trash = apply_filters( 'wpncr_cleanup_on_trash', false );
		if ( $cleanup_on_trash ) {
			add_action( 'wp_trash_post', array( $this, 'cleanup_on_trash' ), 10, 1 );
		}
	}
	
	/**
	 * Clean up relationships when post is permanently deleted
	 *
	 * @param int $post_id Post ID
	 */
	public function cleanup_on_delete( $post_id ) {
		global $wpdb;
		
		$post_id = absint( $post_id );
		
		$settings = WPNCR_Settings::get_instance();
		$cleanup_mode = $settings->get_setting( 'cleanup_on_delete', 'remove' );
		
		if ( $cleanup_mode === 'remove' ) {
			// Delete all relationships where this post is the source
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table cleanup
			$wpdb->delete(
				$wpdb->prefix . 'content_relations',
				array( 'from_id' => $post_id ),
				array( '%d' )
			);
			
			// Delete all relationships where this post is the target
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table cleanup
			$wpdb->delete(
				$wpdb->prefix . 'content_relations',
				array( 'to_id' => $post_id ),
				array( '%d' )
			);
		} else {
			// Mark as orphaned (could add an 'orphaned' column in future)
			// For now, we'll just leave them but they'll be filtered out in queries
		}
		
		// Fire action
		do_action( 'wpncr_relationships_cleaned', $post_id, $cleanup_mode );
	}
	
	/**
	 * Clean up relationships when post is trashed (optional)
	 *
	 * @param int $post_id Post ID
	 */
	public function cleanup_on_trash( $post_id ) {
		// Same as delete, but for trashed posts
		$this->cleanup_on_delete( $post_id );
	}
}

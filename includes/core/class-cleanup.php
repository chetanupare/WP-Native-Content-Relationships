<?php
/**
 * Safe Deletion Handling
 * Cleans up relationships when content is trashed or deleted
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

class NATICORE_Cleanup
{

	/**
	 * Instance
	 */
	private static $instance = null;

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
		// Clean up on post deletion
		add_action('before_delete_post', array($this, 'cleanup_on_delete_post'), 10, 1);

		// Clean up on user deletion
		add_action('delete_user', array($this, 'cleanup_on_delete_user'), 10, 1);

		// Clean up on term deletion
		add_action('pre_delete_term', array($this, 'cleanup_on_delete_term'), 10, 2);

		// Optionally clean up on trash (configurable)
		$cleanup_on_trash = apply_filters('naticore_cleanup_on_trash', false);
		if ($cleanup_on_trash) {
			add_action('wp_trash_post', array($this, 'cleanup_on_delete_post'), 10, 1);
		}
	}

	public function cleanup_on_delete_post($post_id)
	{
		$this->cleanup_object_relationships($post_id, 'post');
	}

	public function cleanup_on_delete_user($user_id)
	{
		$this->cleanup_object_relationships($user_id, 'user');
	}

	public function cleanup_on_delete_term($term_id, $taxonomy)
	{
		$this->cleanup_object_relationships($term_id, 'term');
	}

	/**
	 * Centralized cleanup for any object type.
	 * 
	 * @param int $object_id The ID of the deleted object.
	 * @param string $object_type The type ('post', 'user', 'term').
	 */
	private function cleanup_object_relationships($object_id, $object_type)
	{
		global $wpdb;
		$object_id = absint($object_id);
		
		if (0 === $object_id) {
			return;
		}

		$settings = NATICORE_Settings::get_instance();
		$cleanup_mode = $settings->get_setting('cleanup_on_delete', 'remove');

		if ('remove' === $cleanup_mode) {
			$table = $wpdb->prefix . 'content_relations';
			$meta_table = $wpdb->prefix . 'content_relationmeta';

			// Find ALL relationships involving this object — both as source and target.
			// This handles bidirectional reverse rows that the old logic missed.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table cleanup
			$relations_to_delete = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE from_id = %d OR (to_id = %d AND to_type = %s)",
					$object_id,
					$object_id,
					$object_type
				)
			);

			if (!empty($relations_to_delete)) {
				$ids_list = implode(',', array_map('absint', $relations_to_delete));
				
				// Delete associated metadata
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table meta cleanup
				$wpdb->query($wpdb->prepare("DELETE FROM `{$meta_table}` WHERE content_relation_id IN ($ids_list)"));

				foreach ($relations_to_delete as $id_to_delete) {
					wp_cache_delete($id_to_delete, 'content_relation_meta');
				}

				// Delete the relations themselves
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table cleanup
				$wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE id IN ($ids_list)"));
			}

			// Debug logging
			if ($settings->get_setting('debug_logging', 0) && defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
				error_log(sprintf('WPNCR: Cleanup removed relations for deleted %s_id: %d', $object_type, $object_id));
			}
		}

		// Fire action
		do_action('naticore_relationships_cleaned', $object_id, $cleanup_mode, $object_type);
	}
}

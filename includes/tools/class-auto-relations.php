<?php
/**
 * Automatic Relations on Publish
 * Rule-based automation
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Auto_Relations {

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
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'auto_relation_enabled', 0 ) ) {
			$post_types = $settings->get_setting( 'auto_relation_post_types', array( 'post' ) );
			foreach ( $post_types as $post_type ) {
				add_action( "publish_{$post_type}", array( $this, 'auto_relation_to_parent' ), 10, 2 );
			}
		}
	}

	/**
	 * Auto-relation to parent page
	 */
	public function auto_relation_to_parent( $post_id, $post ) {
		// Skip revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if this post type is enabled for auto-relations
		$settings      = NATICORE_Settings::get_instance();
		$enabled_types = $settings->get_setting( 'auto_relation_post_types', array( 'post' ) );

		if ( ! in_array( $post->post_type, $enabled_types, true ) ) {
			return;
		}

		// Get parent page
		$parent_id = wp_get_post_parent_id( $post_id );

		if ( ! $parent_id ) {
			return;
		}

		// Check if already related
		if ( NATICORE_API::is_related( $post_id, $parent_id, 'part_of' ) ) {
			return;
		}

		// Register part_of type if not exists
		if ( ! NATICORE_Relation_Types::exists( 'part_of' ) ) {
			register_content_relation_type(
				'part_of',
				array(
					'label'              => __( 'Part Of', 'native-content-relationships' ),
					'bidirectional'      => false,
					'allowed_post_types' => array(),
				)
			);
		}

		// Create relationship
		$result = NATICORE_API::add_relation( $post_id, $parent_id, 'part_of' );

		// Debug logging
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'debug_logging', 0 ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( sprintf( 'WPNCR: Auto-relation attempted - post_id: %d, parent_id: %d, result: %s', $post_id, $parent_id, is_wp_error( $result ) ? 'error' : 'ok' ) );
		}
	}
}

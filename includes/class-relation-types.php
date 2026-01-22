<?php
/**
 * Relationship Types System
 * Allows registering and managing relationship types
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_Relation_Types {
	
	/**
	 * Registered relation types
	 */
	private static $types = array();
	
	/**
	 * Default relation types
	 */
	private static $default_types = array(
		'related_to' => array(
			'label'            => 'Related To',
			'bidirectional'    => true,
			'allowed_post_types' => array(), // Empty = all
		),
		'parent_of' => array(
			'label'            => 'Parent Of',
			'bidirectional'    => false,
			'allowed_post_types' => array(),
		),
		'depends_on' => array(
			'label'            => 'Depends On',
			'bidirectional'    => false,
			'allowed_post_types' => array(),
		),
		'references' => array(
			'label'            => 'References',
			'bidirectional'    => false,
			'allowed_post_types' => array(),
		),
	);
	
	/**
	 * Initialize
	 */
	public static function init() {
		// Register default types
		foreach ( self::$default_types as $slug => $args ) {
			self::register( $slug, $args );
		}
		
		// Allow filtering
		add_action( 'init', array( __CLASS__, 'register_defaults' ), 5 );
	}
	
	/**
	 * Register default types (hook)
	 */
	public static function register_defaults() {
		do_action( 'wpncr_register_relation_types' );
	}
	
	/**
	 * Register a relationship type
	 *
	 * @param string $slug Type slug
	 * @param array  $args Type arguments
	 * @return bool|WP_Error
	 */
	public static function register( $slug, $args = array() ) {
		$slug = sanitize_key( $slug );
		
		if ( empty( $slug ) ) {
			return new WP_Error( 'invalid_slug', __( 'Relation type slug cannot be empty.', 'native-content-relationships' ) );
		}
		
		$defaults = array(
			'label'            => ucwords( str_replace( '_', ' ', $slug ) ),
			'bidirectional'    => true,
			'allowed_post_types' => array(), // Empty array = all post types allowed
		);
		
		$args = wp_parse_args( $args, $defaults );
		
		// Validate
		if ( ! is_string( $args['label'] ) || empty( $args['label'] ) ) {
			return new WP_Error( 'invalid_label', __( 'Relation type label must be a non-empty string.', 'native-content-relationships' ) );
		}
		
		if ( ! is_bool( $args['bidirectional'] ) ) {
			$args['bidirectional'] = (bool) $args['bidirectional'];
		}
		
		if ( ! is_array( $args['allowed_post_types'] ) ) {
			$args['allowed_post_types'] = array();
		}
		
		self::$types[ $slug ] = $args;
		
		do_action( 'wpncr_relation_type_registered', $slug, $args );
		
		return true;
	}
	
	/**
	 * Get registered relation types
	 *
	 * @return array
	 */
	public static function get_types() {
		return apply_filters( 'wpncr_relation_types', self::$types );
	}
	
	/**
	 * Get a specific relation type
	 *
	 * @param string $slug Type slug
	 * @return array|false
	 */
	public static function get_type( $slug ) {
		$types = self::get_types();
		return isset( $types[ $slug ] ) ? $types[ $slug ] : false;
	}
	
	/**
	 * Check if a relation type exists
	 *
	 * @param string $slug Type slug
	 * @return bool
	 */
	public static function exists( $slug ) {
		return isset( self::$types[ $slug ] );
	}
	
	/**
	 * Check if post types are allowed for a relation type
	 *
	 * @param string $type_slug Relation type slug
	 * @param string $from_post_type From post type
	 * @param string $to_post_type To post type
	 * @return bool
	 */
	public static function are_post_types_allowed( $type_slug, $from_post_type, $to_post_type ) {
		$type = self::get_type( $type_slug );
		
		if ( ! $type ) {
			return false;
		}
		
		$allowed = $type['allowed_post_types'];
		
		// Empty array means all post types allowed
		if ( empty( $allowed ) ) {
			return true;
		}
		
		// Check if both post types are in allowed list
		return in_array( $from_post_type, $allowed, true ) && in_array( $to_post_type, $allowed, true );
	}
	
	/**
	 * Check if relation type is bidirectional
	 *
	 * @param string $slug Type slug
	 * @return bool
	 */
	public static function is_bidirectional( $slug ) {
		$type = self::get_type( $slug );
		return $type ? $type['bidirectional'] : false;
	}
}

// Make function available globally
if ( ! function_exists( 'register_content_relation_type' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function register_content_relation_type( $slug, $args = array() ) {
		if ( ! class_exists( 'WPNCR_Relation_Types' ) ) {
			return new WP_Error( 'class_not_loaded', 'WPNCR_Relation_Types class is not loaded yet.' );
		}
		return WPNCR_Relation_Types::register( $slug, $args );
	}
}

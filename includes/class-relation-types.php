<?php
/**
 * Relationship Types System
 * Allows registering and managing relationship types
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Relationship Types
 *
 * Provides functionality to register and manage relationship types
 * between posts, users, and terms with support for different
 * relationship directions and constraints.
 *
 * @package NativeContentRelationships
 * @since 1.0.0
 */
class NATICORE_Relation_Types {

	/**
	 * Whether the registry is locked
	 *
	 * @var bool
	 */
	private static $locked = false;

	/**
	 * Registered relation types
	 *
	 * @var array
	 */
	private static $types = array();

	/**
	 * Default relation types
	 *
	 * @var array
	 */
	private static $default_types = array(
		'related_to'     => array(
			'label'              => 'Related To',
			'bidirectional'      => true,
			'allowed_post_types' => array(), // Empty = all.
			'supports_users'     => false,
			'supports_terms'     => false,
		),
		'parent_of'      => array(
			'label'              => 'Parent Of',
			'bidirectional'      => false,
			'allowed_post_types' => array(),
			'supports_users'     => false,
			'supports_terms'     => false,
		),
		'depends_on'     => array(
			'label'              => 'Depends On',
			'bidirectional'      => false,
			'allowed_post_types' => array(),
			'supports_users'     => false,
			'supports_terms'     => false,
		),
		'references'     => array(
			'label'              => 'References',
			'bidirectional'      => false,
			'allowed_post_types' => array(),
			'supports_users'     => false,
			'supports_terms'     => false,
		),
		// User relationship types.
		'favorite_posts' => array(
			'label'              => 'Favorite Posts',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post', 'page' ),
			'supports_users'     => true,
			'supports_terms'     => false,
			'from_type'          => 'user',
			'to_type'            => 'post',
		),
		'bookmarked_by'  => array(
			'label'              => 'Bookmarked By',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post', 'page' ),
			'supports_users'     => true,
			'supports_terms'     => false,
			'from_type'          => 'user',
			'to_type'            => 'post',
		),
		'authored_by'    => array(
			'label'              => 'Authored By',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post', 'page' ),
			'supports_users'     => true,
			'supports_terms'     => false,
			'from_type'          => 'user',
			'to_type'            => 'post',
		),
		// Term relationship types.
		'categorized_as' => array(
			'label'              => 'Categorized As',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post', 'page' ),
			'supports_users'     => false,
			'supports_terms'     => true,
			'from_type'          => 'post',
			'to_type'            => 'term',
		),
		'tagged_with'    => array(
			'label'              => 'Tagged With',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post' ),
			'supports_users'     => false,
			'supports_terms'     => true,
			'from_type'          => 'post',
			'to_type'            => 'term',
		),
		'featured_in'    => array(
			'label'              => 'Featured In',
			'bidirectional'      => false,
			'allowed_post_types' => array( 'post', 'page' ),
			'supports_users'     => false,
			'supports_terms'     => true,
			'from_type'          => 'post',
			'to_type'            => 'term',
		),
	);

	/**
	 * Initialize
	 */
	public static function init() {
		// Load settings to check for disabled/custom types.
		$settings = get_option( 'naticore_settings', array() );
		$type_config = isset( $settings['relationship_types_config'] ) ? $settings['relationship_types_config'] : array();
		
		// Register default types.
		foreach ( self::$default_types as $slug => $args ) {
			// Check if type is disabled in settings.
			$is_disabled = isset( $type_config['built_in'][ $slug ]['enabled'] ) && ! $type_config['built_in'][ $slug ]['enabled'];
			
			if ( ! $is_disabled ) {
				self::register( $slug, $args );
			}
		}

		// Register custom types from settings.
		if ( isset( $type_config['custom'] ) && is_array( $type_config['custom'] ) ) {
			foreach ( $type_config['custom'] as $slug => $args ) {
				self::register( $slug, $args );
			}
		}

		// Allow filtering.
		add_action( 'init', array( __CLASS__, 'register_defaults' ), 5 );
	}

	/**
	 * Register default types (hook)
	 */
	public static function register_defaults() {
		do_action( 'naticore_register_relation_types' );
	}

	/**
	 * Register a relationship type
	 *
	 * @param string $slug Type slug.
	 * @param array  $args Type arguments.
	 * @return bool|WP_Error
	 */
	public static function register( $slug, $args = array() ) {
		if ( self::$locked ) {
			return new WP_Error( 'ncr_registry_locked', __( 'Relationship registry is locked. Registration must happen before init:20.', 'native-content-relationships' ) );
		}

		// Support both (slug, args) and (args with name) formats.
		if ( is_array( $slug ) && isset( $slug['name'] ) ) {
			$args = $slug;
			$slug = $args['name'];
		}

		$slug = sanitize_key( $slug );

		if ( empty( $slug ) ) {
			return new WP_Error( 'ncr_invalid_slug', __( 'Relation type slug cannot be empty.', 'native-content-relationships' ) );
		}

		$defaults = array(
			'label'              => ucwords( str_replace( '_', ' ', $slug ) ),
			'from'               => 'post',
			'to'                 => 'post',
			'bidirectional'      => true,
			'allowed_post_types' => array(), // Empty array = all post types allowed.
			'max_connections'    => 0,       // 0 = Unlimited.
		);

		$args = wp_parse_args( $args, $defaults );

		// Object type mapping and validation.
		$valid_objects = array( 'post', 'user', 'term' );
		if ( ! in_array( $args['from'], $valid_objects, true ) || ! in_array( $args['to'], $valid_objects, true ) ) {
			return new WP_Error( 'ncr_invalid_object_type', sprintf( __( 'Invalid object types. Allowed: %s', 'native-content-relationships' ), implode( ', ', $valid_objects ) ) );
		}

		// Derived flags for internal compatibility.
		$args['supports_users'] = ( 'user' === $args['from'] || 'user' === $args['to'] );
		$args['supports_terms'] = ( 'term' === $args['from'] || 'term' === $args['to'] );
		$args['from_type']      = $args['from'];
		$args['to_type']        = $args['to'];

		// Strict validation.
		if ( $args['supports_users'] && $args['supports_terms'] ) {
			return new WP_Error( 'ncr_invalid_combination', __( 'Direct user-to-term relationships are not currently supported.', 'native-content-relationships' ) );
		}

		if ( ! is_string( $args['label'] ) || empty( $args['label'] ) ) {
			return new WP_Error( 'ncr_invalid_label', __( 'Relation type label must be a non-empty string.', 'native-content-relationships' ) );
		}

		if ( ! is_bool( $args['bidirectional'] ) ) {
			$args['bidirectional'] = (bool) $args['bidirectional'];
		}

		if ( ! is_array( $args['allowed_post_types'] ) ) {
			$args['allowed_post_types'] = array();
		}

		self::$types[ $slug ] = $args;

		do_action( 'naticore_relation_type_registered', $slug, $args );

		return true;
	}

	/**
	 * Get registered relation types
	 *
	 * @return array
	 */
	public static function get_types() {
		return apply_filters( 'naticore_relation_types', self::$types );
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

	/**
	 * Check if relation type supports users
	 *
	 * @param string $slug Type slug
	 * @return bool
	 */
	public static function supports_users( $slug ) {
		$type = self::get_type( $slug );
		return $type ? $type['supports_users'] : false;
	}

	/**
	 * Check if relation type supports terms
	 *
	 * @param string $slug Type slug
	 * @return bool
	 */
	public static function supports_terms( $slug ) {
		$type = self::get_type( $slug );
		return $type ? $type['supports_terms'] : false;
	}

	/**
	 * Get user-to-post relationship types
	 *
	 * @return array
	 */
	public static function get_user_to_post_types() {
		$types      = self::get_types();
		$user_types = array();

		foreach ( $types as $slug => $config ) {
			if ( $config['supports_users'] &&
				$config['from_type'] === 'user' &&
				$config['to_type'] === 'post' ) {
				$user_types[ $slug ] = $config['label'];
			}
		}

		return $user_types;
	}

	/**
	 * Get post-to-user relationship types
	 *
	 * @return array
	 */
	public static function get_post_to_user_types() {
		$types      = self::get_types();
		$post_types = array();

		foreach ( $types as $slug => $config ) {
			if ( $config['supports_users'] &&
				$config['from_type'] === 'user' &&
				$config['to_type'] === 'post' ) {
				// For post-to-user, we use the same types but reverse the direction
				$post_types[ $slug ] = $config['label'];
			}
		}

		return $post_types;
	}

	/**
	 * Get post-to-term relationship types
	 *
	 * @return array
	 */
	public static function get_post_to_term_types() {
		$types      = self::get_types();
		$term_types = array();

		foreach ( $types as $slug => $config ) {
			if ( $config['supports_terms'] &&
				$config['from_type'] === 'post' &&
				$config['to_type'] === 'term' ) {
				$term_types[ $slug ] = $config['label'];
			}
		}

		return $term_types;
	}

	/**
	 * Get term-to-post relationship types
	 *
	 * @return array
	 */
	public static function get_term_to_post_types() {
		$types      = self::get_types();
		$term_types = array();

		foreach ( $types as $slug => $config ) {
			if ( $config['supports_terms'] &&
				$config['from_type'] === 'post' &&
				$config['to_type'] === 'term' ) {
				// For term-to-post, we use the same types but reverse the direction
				$term_types[ $slug ] = $config['label'];
			}
		}

		return $term_types;
	}

	/**
	 * Lock the registry.
	 */
	public static function lock() {
		self::$locked = true;
	}

	/**
	 * Check if the registry is locked.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		return self::$locked;
	}
}

// Lock registry on init.
add_action( 'init', array( 'NATICORE_Relation_Types', 'lock' ), 20 );

// Make function available globally
if ( ! function_exists( 'register_content_relation_type' ) ) {
	/**
	 * Legacy registration function
	 */
	function register_content_relation_type( $slug, $args = array() ) {
		if ( ! class_exists( 'NATICORE_Relation_Types' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_Relation_Types class is not loaded yet.' );
		}
		return NATICORE_Relation_Types::register( $slug, $args );
	}
}

if ( ! function_exists( 'ncr_register_relation_type' ) ) {
	/**
	 * Formally register a relationship type with schema validation.
	 *
	 * @since 1.0.15
	 * @param array $args {
	 *     @type string $name            Unique slug for the relationship type.
	 *     @type string $label           Display label.
	 *     @type string $from            Object type (post, user, term).
	 *     @type string $to              Object type (post, user, term).
	 *     @type bool   $bidirectional   Whether the relationship is two-way.
	 *     @type int    $max_connections Maximum number of relationships of this type allowed from source.
	 * }
	 * @return bool|WP_Error
	 */
	function ncr_register_relation_type( $args ) {
		if ( ! class_exists( 'NATICORE_Relation_Types' ) ) {
			return new WP_Error( 'class_not_loaded', 'NATICORE_Relation_Types class is not loaded yet.' );
		}
		return NATICORE_Relation_Types::register( $args );
	}
}

if ( ! function_exists( 'ncr_get_registered_relation_types' ) ) {
	/**
	 * Get all formally registered relationship types.
	 *
	 * @since 1.0.16
	 * @return array
	 */
	function ncr_get_registered_relation_types() {
		if ( ! class_exists( 'NATICORE_Relation_Types' ) ) {
			return array();
		}
		return NATICORE_Relation_Types::get_types();
	}
}

<?php
/**
 * Fluent API for Content Relationships.
 *
 * Provides a chainable, IDE-friendly API wrapper for managing relationships.
 *
 * @package NativeContentRelationships
 * @since   1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent API entry point
 *
 * @return NATICORE_Fluent_API
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional global API function
function naticore() {
	if ( ! class_exists( 'NATICORE_Fluent_API' ) ) {
		return NATICORE_Fluent_API::get_instance();
	}
	return NATICORE_Fluent_API::get_instance();
}

class NATICORE_Fluent_API {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @var NATICORE_Fluent_API|null
	 */
	private static $instance = null;

	/**
	 * Source post ID for the current chain.
	 *
	 * @since 1.0.0
	 * @var int|null
	 */
	private $from_id = null;

	/**
	 * Target post ID for the current chain.
	 *
	 * @since 1.0.0
	 * @var int|null
	 */
	private $to_id = null;

	/**
	 * Relationship type for the current chain.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $type = 'related_to';

	/**
	 * Relationship direction for the current chain.
	 *
	 * @since 1.0.0
	 * @var string|null
	 */
	private $direction = null;

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return NATICORE_Fluent_API
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Reset state
		$this->reset();
	}

	/**
	 * Reset the fluent chain state.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function reset() {
		$this->from_id   = null;
		$this->to_id     = null;
		$this->type      = 'related_to';
		$this->direction = null;
	}

	/**
	 * Set source post ID
	 *
	 * @param int $post_id
	 * @return $this
	 */
	public function from( $post_id ) {
		$this->from_id = absint( $post_id );
		return $this;
	}

	/**
	 * Set target post ID
	 *
	 * @param int $post_id
	 * @return $this
	 */
	public function to( $post_id ) {
		$this->to_id = absint( $post_id );
		return $this;
	}

	/**
	 * Set relationship type
	 *
	 * @param string $type
	 * @return $this
	 */
	public function type( $type ) {
		$this->type = sanitize_text_field( $type );
		return $this;
	}

	/**
	 * Set direction
	 *
	 * @param string $direction 'one-way' or 'bidirectional'
	 * @return $this
	 */
	public function direction( $direction ) {
		$this->direction = sanitize_text_field( $direction );
		return $this;
	}

	/**
	 * Create relationship
	 *
	 * @return int|WP_Error Relationship ID on success, WP_Error on failure
	 */
	public function create() {
		if ( ! $this->from_id || ! $this->to_id ) {
			$error = new WP_Error( 'missing_params', __( 'from() and to() must be called before create().', 'native-content-relationships' ) );
			$this->reset();
			return $error;
		}

		$result = NATICORE_API::add_relation( $this->from_id, $this->to_id, $this->type, $this->direction );
		$this->reset();
		return $result;
	}

	/**
	 * Get related posts
	 *
	 * @param array $args Optional arguments (limit, direction, etc.)
	 * @return array Array of related posts
	 */
	public function get( $args = array() ) {
		if ( ! $this->from_id ) {
			$this->reset();
			return array();
		}

		$defaults = array(
			'limit' => null,
		);
		$args     = wp_parse_args( $args, $defaults );

		$result = NATICORE_API::get_related( $this->from_id, $this->type, $args );
		$this->reset();
		return $result;
	}

	/**
	 * Check if related
	 *
	 * @return bool|WP_Error
	 */
	public function exists() {
		if ( ! $this->from_id || ! $this->to_id ) {
			$error = new WP_Error( 'missing_params', __( 'from() and to() must be called before exists().', 'native-content-relationships' ) );
			$this->reset();
			return $error;
		}

		$result = NATICORE_API::is_related( $this->from_id, $this->to_id, $this->type );
		$this->reset();
		return $result;
	}

	/**
	 * Remove relationship
	 *
	 * @return bool|WP_Error
	 */
	public function remove() {
		if ( ! $this->from_id || ! $this->to_id ) {
			$error = new WP_Error( 'missing_params', __( 'from() and to() must be called before remove().', 'native-content-relationships' ) );
			$this->reset();
			return $error;
		}

		$result = NATICORE_API::remove_relation( $this->from_id, $this->to_id, $this->type );
		$this->reset();
		return $result;
	}
}

<?php
/**
 * REST API endpoints for relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_REST_API {
	
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
		$settings = WPNCR_Settings::get_instance();
		if ( $settings->get_setting( 'enable_rest_api', 1 ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}
	
	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Get relationships for a post
		register_rest_route( 'wpncr/v1', '/post/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_relationships' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
				'relation_type' => array(
					'default'           => null,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
		
		// Add relationship
		register_rest_route( 'wpncr/v1', '/relationships', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'add_relationship' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
		
		// Remove relationship
		register_rest_route( 'wpncr/v1', '/relationships', array(
			'methods'             => 'DELETE',
			'callback'            => array( $this, 'remove_relationship' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
	}
	
	/**
	 * Get relationships
	 */
	public function get_relationships( $request ) {
		$post_id = absint( $request['id'] );
		$relation_type = $request->get_param( 'relation_type' );
		
		$relationships = WPNCR_API::get_related( $post_id, $relation_type );
		
		// Enhance with post data
		$enhanced = array();
		foreach ( $relationships as $rel ) {
			$post = get_post( $rel['id'] );
			if ( $post ) {
				$enhanced[] = array(
					'id'        => $rel['id'],
					'title'     => get_the_title( $rel['id'] ),
					'type'      => $post->post_type,
					'relation_type' => $rel['type'],
					'permalink' => get_permalink( $rel['id'] ),
					'edit_link' => get_edit_post_link( $rel['id'], 'raw' ),
				);
			}
		}
		
		return rest_ensure_response( $enhanced );
	}
	
	/**
	 * Add relationship
	 */
	public function add_relationship( $request ) {
		$params = $request->get_json_params();
		
		$from_id = isset( $params['from_id'] ) ? absint( $params['from_id'] ) : 0;
		$to_id = isset( $params['to_id'] ) ? absint( $params['to_id'] ) : 0;
		$type = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'related_to';
		
		if ( ! $from_id || ! $to_id ) {
			return new WP_Error( 'missing_params', __( 'from_id and to_id are required.', 'native-content-relationships' ), array( 'status' => 400 ) );
		}
		
		$result = WPNCR_API::add_relation( $from_id, $to_id, $type );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array(
			'success' => true,
			'relation_id' => $result,
		) );
	}
	
	/**
	 * Remove relationship
	 */
	public function remove_relationship( $request ) {
		$from_id = isset( $request['from_id'] ) ? absint( $request['from_id'] ) : 0;
		$to_id = isset( $request['to_id'] ) ? absint( $request['to_id'] ) : 0;
		$type = isset( $request['type'] ) ? sanitize_text_field( $request['type'] ) : null;
		
		if ( ! $from_id || ! $to_id ) {
			return new WP_Error( 'missing_params', __( 'from_id and to_id are required.', 'native-content-relationships' ), array( 'status' => 400 ) );
		}
		
		$result = WPNCR_API::remove_relation( $from_id, $to_id, $type );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		return rest_ensure_response( array(
			'success' => true,
		) );
	}
	
	/**
	 * Check permissions
	 */
	public function permissions_check( $request ) {
		// For now, allow if user can edit posts
		// You can customize this based on your needs
		return current_user_can( 'edit_posts' );
	}
}

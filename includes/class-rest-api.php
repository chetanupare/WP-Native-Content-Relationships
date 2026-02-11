<?php
/**
 * REST API endpoints for relationships
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_REST_API {

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
		if ( $settings->get_setting( 'enable_rest_api', 1 ) ) {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		// Get relationships for a post with pagination and filtering
		register_rest_route(
			'naticore/v1',
			'/post/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_relationships' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id'            => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
						'sanitize_callback' => 'absint',
					),
					'relation_type' => array(
						'default'           => null,
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
					),
					'to_type'       => array(
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'post', 'user', 'term', 'all' ),
					),
					'page'          => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
						'type'              => 'integer',
						'minimum'           => 1,
					),
					'per_page'      => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
						'type'              => 'integer',
						'minimum'           => 1,
						'maximum'           => 100,
					),
					'search'        => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
					),
					'orderby'       => array(
						'default'           => 'created_at',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'created_at', 'title', 'type' ),
					),
					'order'         => array(
						'default'           => 'DESC',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'ASC', 'DESC' ),
					),
				),
			)
		);

		// Get registered relationship types.
		register_rest_route(
			'naticore/v1',
			'/types',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_relationship_types' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		// Add relationship
		register_rest_route(
			'naticore/v1',
			'/relationships',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_relationship' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'from_id' => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'to_id'   => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'type'    => array(
						'required'          => false,
						'default'           => 'related_to',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
					),
					'to_type' => array(
						'required'          => false,
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'post', 'user', 'term' ),
					),
				),
			)
		);

		// Remove relationship
		register_rest_route(
			'naticore/v1',
			'/relationships',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'remove_relationship' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'from_id' => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'to_id'   => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'type'    => array(
						'required'          => false,
						'default'           => null,
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
					),
					'to_type' => array(
						'required'          => false,
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'post', 'user', 'term' ),
					),
				),
			)
		);

		// Bulk operations
		register_rest_route(
			'naticore/v1',
			'/relationships/bulk',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'bulk_relationships' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'operation'     => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'create', 'delete', 'import' ),
					),
					'relationships' => array(
						'required'          => true,
						'type'              => 'array',
						'validate_callback' => function ( $param ) {
							return is_array( $param );
						},
					),
				),
			)
		);

		// Check relationship exists
		register_rest_route(
			'naticore/v1',
			'/relationships/exists',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'relationship_exists' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'from_id' => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'to_id'   => array(
						'required'          => true,
						'validate_callback' => 'absint',
						'type'              => 'integer',
					),
					'type'    => array(
						'required'          => false,
						'default'           => 'related_to',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
					),
					'to_type' => array(
						'required'          => false,
						'default'           => 'post',
						'sanitize_callback' => 'sanitize_text_field',
						'type'              => 'string',
						'enum'              => array( 'post', 'user', 'term' ),
					),
				),
			)
		);
	}

	/**
	 * Get relationships with pagination and filtering
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error.
	 */
	public function get_relationships( $request ) {
		$post_id       = absint( $request['id'] );
		$relation_type = $request->get_param( 'relation_type' );
		$to_type       = $request->get_param( 'to_type' );
		$page          = $request->get_param( 'page' );
		$per_page      = $request->get_param( 'per_page' );
		$search        = $request->get_param( 'search' );
		$orderby       = $request->get_param( 'orderby' );
		$order         = $request->get_param( 'order' );

		$args = array(
			'limit'   => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( ! empty( $search ) ) {
			$args['search'] = $search;
		}

		$relationships = NATICORE_API::get_related( $post_id, $relation_type, $args, $to_type );

		// Get total count for pagination
		$total_args = array( 'count_only' => true );
		if ( ! empty( $search ) ) {
			$total_args['search'] = $search;
		}
		$total = NATICORE_API::get_related( $post_id, $relation_type, $total_args, $to_type );
		$total = is_array( $total ) ? count( $total ) : 0;

		// Enhance with post/user/term data
		$enhanced = array();
		foreach ( $relationships as $rel ) {
			$item = array(
				'id'            => $rel['id'],
				'type'          => $rel['type'],
				'to_type'       => $rel['to_type'],
				'relation_type' => $rel['type'],
				'created_at'    => $rel['created_at'] ?? null,
			);

			if ( 'user' === $rel['to_type'] ) {
				$user = get_userdata( $rel['id'] );
				if ( $user ) {
					$item['display_name'] = $user->display_name;
					$item['user_email']   = $user->user_email;
					$item['user_login']   = $user->user_login;
					$item['edit_link']    = get_edit_user_link( $rel['id'] );
				}
			} elseif ( 'term' === $rel['to_type'] ) {
				$term = get_term( $rel['id'] );
				if ( $term && ! is_wp_error( $term ) ) {
					$item['term_name'] = $term->name;
					$item['term_slug'] = $term->slug;
					$item['taxonomy']  = $term->taxonomy;
					$item['edit_link'] = get_edit_term_link( $rel['id'], $term->taxonomy );
				}
			} else {
				$post = get_post( $rel['id'] );
				if ( $post ) {
					$item['title']     = get_the_title( $rel['id'] );
					$item['post_type'] = $post->post_type;
					$item['permalink'] = get_permalink( $rel['id'] );
					$item['edit_link'] = get_edit_post_link( $rel['id'], 'raw' );
				}
			}

			$enhanced[] = $item;
		}

		$response = rest_ensure_response( $enhanced );

		// Add pagination headers
		$response->header( 'X-WP-Total', (int) $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * Add relationship with improved error handling
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error.
	 */
	public function add_relationship( $request ) {
		$params = $request->get_json_params();

		$from_id = isset( $params['from_id'] ) ? absint( $params['from_id'] ) : 0;
		$to_id   = isset( $params['to_id'] ) ? absint( $params['to_id'] ) : 0;
		$type    = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'related_to';
		$to_type = isset( $params['to_type'] ) ? sanitize_text_field( $params['to_type'] ) : 'post';

		// Validate required parameters.
		if ( ! $from_id || ! $to_id ) {
			return new WP_Error(
				'ncr_missing_params',
				__( 'from_id and to_id are required.', 'native-content-relationships' ),
				array( 'status' => 400 )
			);
		}

		// Validate Relationship Type exists.
		$type_info = NATICORE_Relation_Types::get_type( $type );
		if ( ! $type_info ) {
			return new WP_Error(
				'ncr_invalid_type',
				__( 'Invalid relationship type.', 'native-content-relationships' ),
				array( 'status' => 400 )
			);
		}

		// Validate Object Types match registry.
		if ( $type_info['from'] !== 'post' && $type_info['from'] !== 'user' && $type_info['from'] !== 'term' ) {
			// This shouldn't happen with the new registry but just in case.
		}

		// Validate to_type matches registry if applicable.
		if ( $to_type !== $type_info['to'] ) {
			return new WP_Error(
				'ncr_invalid_to_type',
				sprintf( __( 'Invalid target type for this relationship. Expected: %s', 'native-content-relationships' ), $type_info['to'] ),
				array( 'status' => 400 )
			);
		}

		// Check if relationship already exists.
		if ( wp_is_related( $from_id, $to_id, $type, null, $to_type ) ) {
			return new WP_Error(
				'ncr_relation_exists',
				__( 'Relationship already exists.', 'native-content-relationships' ),
				array( 'status' => 409 )
			);
		}

		$result = NATICORE_API::add_relation( $from_id, $to_id, $type, null, $to_type );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success'     => true,
				'relation_id' => $result,
				'message'     => __( 'Relationship created successfully.', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Remove relationship with improved error handling
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error.
	 */
	public function remove_relationship( $request ) {
		$from_id = isset( $request['from_id'] ) ? absint( $request['from_id'] ) : 0;
		$to_id   = isset( $request['to_id'] ) ? absint( $request['to_id'] ) : 0;
		$type    = isset( $request['type'] ) ? sanitize_text_field( $request['type'] ) : null;
		$to_type = isset( $request['to_type'] ) ? sanitize_text_field( $request['to_type'] ) : 'post';

		// Validate required parameters
		if ( ! $from_id || ! $to_id ) {
			return new WP_Error(
				'ncr_missing_params',
				__( 'from_id and to_id are required.', 'native-content-relationships' ),
				array( 'status' => 400 )
			);
		}

		// Check if relationship exists
		if ( ! wp_is_related( $from_id, $to_id, $type, null, $to_type ) ) {
			return new WP_Error(
				'ncr_relation_not_found',
				__( 'Relationship not found.', 'native-content-relationships' ),
				array( 'status' => 404 )
			);
		}

		$result = NATICORE_API::remove_relation( $from_id, $to_id, $type, null, $to_type );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Relationship removed successfully.', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Bulk relationship operations
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error.
	 */
	public function bulk_relationships( $request ) {
		$operation     = $request->get_param( 'operation' );
		$relationships = $request->get_param( 'relationships' );

		if ( ! is_array( $relationships ) || empty( $relationships ) ) {
			return new WP_Error(
				'naticore_invalid_relationships',
				__( 'Relationships must be a non-empty array.', 'native-content-relationships' ),
				array( 'status' => 400 )
			);
		}

		$results = array();
		$errors  = array();

		foreach ( $relationships as $index => $rel ) {
			if ( ! isset( $rel['from_id'] ) || ! isset( $rel['to_id'] ) ) {
				$errors[] = array(
					'index'   => $index,
					'error'   => 'missing_params',
					'message' => __( 'from_id and to_id are required.', 'native-content-relationships' ),
				);
				continue;
			}

			$from_id = absint( $rel['from_id'] );
			$to_id   = absint( $rel['to_id'] );
			$type    = isset( $rel['type'] ) ? sanitize_text_field( $rel['type'] ) : 'related_to';
			$to_type = isset( $rel['to_type'] ) ? sanitize_text_field( $rel['to_type'] ) : 'post';

			if ( 'create' === $operation ) {
				$result = NATICORE_API::add_relation( $from_id, $to_id, $type, null, $to_type );
			} elseif ( 'delete' === $operation ) {
				$result = NATICORE_API::remove_relation( $from_id, $to_id, $type, null, $to_type );
			} elseif ( 'import' === $operation ) {
				// For import, only create if doesn't exist
				if ( ! wp_is_related( $from_id, $to_id, $type, null, $to_type ) ) {
					$result = NATICORE_API::add_relation( $from_id, $to_id, $type, null, $to_type );
				} else {
					$result = true; // Skip existing
				}
			}

			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'index'   => $index,
					'error'   => $result->get_error_code(),
					'message' => $result->get_error_message(),
				);
			} else {
				$results[] = array(
					'index'   => $index,
					'success' => true,
					'data'    => $result,
				);
			}
		}

		return rest_ensure_response(
			array(
				'success'       => empty( $errors ),
				'processed'     => count( $relationships ),
				'created'       => count( $results ),
				'errors'        => count( $errors ),
				'results'       => $results,
				'error_details' => $errors,
			)
		);
	}

	/**
	 * Check if relationship exists with caching
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error The response object or WP_Error.
	 */
	public function relationship_exists( $request ) {
		$from_id = $request->get_param( 'from_id' );
		$to_id   = $request->get_param( 'to_id' );
		$type    = $request->get_param( 'type' );
		$to_type = $request->get_param( 'to_type' );

		$exists = wp_is_related( $from_id, $to_id, $type, null, $to_type );

		return rest_ensure_response(
			array(
				'exists' => $exists,
				'data'   => array(
					'from_id' => $from_id,
					'to_id'   => $to_id,
					'type'    => $type,
					'to_type' => $to_type,
				),
			)
		);
	}

	/**
	 * Check permissions with read-only mode support
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permission granted, WP_Error if not.
	 */
	public function permissions_check( $request ) {
		$method = $request->get_method();
		$route  = $request->get_route();
 
		// Check if read-only mode is enabled.
		$settings = NATICORE_Settings::get_instance();
		$readonly = $settings->get_setting( 'readonly_mode', false );
 
		// If read-only mode is enabled, only allow GET requests.
		if ( $readonly && 'GET' !== $method ) {
			return new WP_Error(
				'naticore_readonly_mode',
				__( 'Plugin is in read-only mode. Modifications are not allowed.', 'native-content-relationships' ),
				array( 'status' => 403 )
			);
		}
 
		// Check basic permissions.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'naticore_insufficient_permissions',
				__( 'You do not have sufficient permissions to manage relationships.', 'native-content-relationships' ),
				array( 'status' => 403 )
			);
		}
 
		// Additional checks for specific operations.
		if ( strpos( $route, '/bulk' ) !== false ) {
			// Bulk operations require higher permissions.
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error(
					'naticore_insufficient_permissions',
					__( 'You do not have sufficient permissions to perform bulk operations.', 'native-content-relationships' ),
					array( 'status' => 403 )
				);
			}
		}
 
		return true;
	}

	/**
	 * Get all registered relationship types.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_relationship_types( $request ) {
		$types = NATICORE_Relation_Types::get_types();
		return rest_ensure_response( $types );
	}
}

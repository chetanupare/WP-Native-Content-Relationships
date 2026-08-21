<?php
/**
 * Object Search Service
 *
 * Stateless, reusable service for searching WordPress objects (posts, users,
 * products). Independent of AJAX, Gutenberg, and admin UI.
 *
 * Central validation and security constraints:
 * - Minimum search term length (MIN_TERM_LENGTH)
 * - Hard result cap (MAX_RESULTS)
 * - Published-only results
 * - No private user data (email excluded)
 *
 * AJAX handlers, Gutenberg sidebar, and REST callers delegate to this service
 * and map the normalized result to their own response contract.
 *
 * SCALABILITY NOTE:
 * Uses LIKE '%term%' on post_title / display_name / user_login.
 * The leading wildcard prevents B-tree index use — acceptable to ~50K objects.
 * Larger installations can plug in a future adapter via:
 *   apply_filters( 'naticore_object_search_provider', null, $type, $term, $args )
 *
 * @package NativeContentRelationships
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NATICORE_Object_Search
 *
 * Stateless — instantiate directly. Do not use as a singleton.
 *
 *   $search  = new NATICORE_Object_Search();
 *   $results = $search->search_posts( 'Christopher Nolan' );
 */
class NATICORE_Object_Search {

	/** Hard maximum results per search. Callers may request fewer, never more. */
	const MAX_RESULTS = 20;

	/** Minimum term length before executing any query. */
	const MIN_TERM_LENGTH = 2;

	// -------------------------------------------------------------------------
	// Public search methods
	// -------------------------------------------------------------------------

	/**
	 * Search published posts by title.
	 *
	 * @param string $term Raw search term (sanitized internally).
	 * @param array  $args {
	 *     @type string|string[] $post_type   Post type(s). Default: all public.
	 *     @type int             $limit       Max results. Default MAX_RESULTS.
	 *     @type int[]           $exclude_ids Post IDs to exclude.
	 * }
	 * @return array|WP_Error Normalized results or WP_Error.
	 */
	public function search_posts( $term, array $args = array() ) {
		$term = $this->validate_term( $term );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$limit       = $this->clamp_limit( isset( $args['limit'] ) ? $args['limit'] : self::MAX_RESULTS );
		$exclude_ids = array_map( 'absint', (array) ( isset( $args['exclude_ids'] ) ? $args['exclude_ids'] : array() ) );
		$post_types  = $this->resolve_post_types( isset( $args['post_type'] ) ? $args['post_type'] : null );

		global $wpdb;
		$like          = '%' . $wpdb->esc_like( $term ) . '%';
		$search_filter = function ( $where ) use ( $like ) {
			global $wpdb;
			$where .= $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s)", $like );
			return $where;
		};

		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
		);
		if ( ! empty( $exclude_ids ) ) {
			$query_args['post__not_in'] = $exclude_ids;
		}

		add_filter( 'posts_where', $search_filter );
		$query = new WP_Query( $query_args );
		remove_filter( 'posts_where', $search_filter );

		$results = array();
		foreach ( $query->posts as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			$thumb_url = '';
			$thumb_id  = get_post_thumbnail_id( $post_id );
			if ( $thumb_id ) {
				$img = wp_get_attachment_image_src( $thumb_id, 'thumbnail' );
				if ( $img ) {
					$thumb_url = $img[0];
				}
			}
			$pto      = get_post_type_object( $post->post_type );
			$type_lbl = $pto ? $pto->labels->singular_name : $post->post_type;
			$edit_url = current_user_can( 'edit_post', $post_id ) ? get_edit_post_link( $post_id ) : null;

			$results[] = $this->make_result( $post_id, 'post', $post->post_title, $type_lbl, $edit_url, $thumb_url );
		}

		return $results;
	}

	/**
	 * Search users by display_name and user_login.
	 *
	 * user_email is intentionally excluded — never expose private contact info.
	 *
	 * @param string $term Raw search term (sanitized internally).
	 * @param array  $args {
	 *     @type int $limit Max results. Default MAX_RESULTS.
	 * }
	 * @return array|WP_Error Normalized results or WP_Error.
	 */
	public function search_users( $term, array $args = array() ) {
		$term = $this->validate_term( $term );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$limit = $this->clamp_limit( isset( $args['limit'] ) ? $args['limit'] : self::MAX_RESULTS );

		$users = get_users(
			array(
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'user_login', 'user_nicename', 'display_name' ),
				'number'         => $limit,
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$edit_url  = current_user_can( 'edit_user', $user->ID ) ? get_edit_user_link( $user->ID ) : null;
			$results[] = $this->make_result( $user->ID, 'user', $user->display_name, $user->user_login, $edit_url, null );
		}

		return $results;
	}

	/**
	 * Search WooCommerce products by title and SKU.
	 *
	 * Only available when WooCommerce is active.
	 *
	 * @param string $term Raw search term (sanitized internally).
	 * @param array  $args {
	 *     @type int   $limit       Max results. Default MAX_RESULTS.
	 *     @type int[] $exclude_ids Product IDs to exclude.
	 * }
	 * @return array|WP_Error Normalized results or WP_Error.
	 */
	public function search_products( $term, array $args = array() ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'woocommerce_inactive', __( 'WooCommerce is not active.', 'native-content-relationships' ) );
		}

		$term = $this->validate_term( $term );
		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$limit       = $this->clamp_limit( isset( $args['limit'] ) ? $args['limit'] : self::MAX_RESULTS );
		$exclude_ids = array_map( 'absint', (array) ( isset( $args['exclude_ids'] ) ? $args['exclude_ids'] : array() ) );

		global $wpdb;
		$like          = '%' . $wpdb->esc_like( $term ) . '%';
		$search_filter = function ( $where ) use ( $like ) {
			global $wpdb;
			$where .= $wpdb->prepare( " AND ({$wpdb->posts}.post_title LIKE %s)", $like );
			return $where;
		};

		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_sku',
					'value'   => $term,
					'compare' => 'LIKE',
				),
			),
			'meta_query_relation' => 'OR',
		);
		if ( ! empty( $exclude_ids ) ) {
			$query_args['post__not_in'] = $exclude_ids;
		}

		add_filter( 'posts_where', $search_filter );
		$query = new WP_Query( $query_args );
		remove_filter( 'posts_where', $search_filter );

		$results = array();
		foreach ( $query->posts as $post_id ) {
			$post    = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}
			$product  = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : null;
			$sku      = $product ? $product->get_sku() : '';
			$edit_url = current_user_can( 'edit_post', $post_id ) ? get_edit_post_link( $post_id ) : null;

			$results[] = $this->make_result( $post_id, 'product', $post->post_title, $sku ? 'SKU: ' . $sku : 'product', $edit_url, null );
		}

		return $results;
	}

	// -------------------------------------------------------------------------
	// Normalized result
	// -------------------------------------------------------------------------

	/**
	 * Build a normalized internal result.
	 *
	 * Callers map this to their own response contract. This shape must NEVER
	 * be returned directly to clients without an adapter.
	 *
	 * @param int         $id
	 * @param string      $object_type     'post'|'user'|'term'|'product'
	 * @param string      $title
	 * @param string|null $secondary_label Post type label, username, SKU, etc.
	 * @param string|null $edit_url        Admin edit URL (null if user cannot edit).
	 * @param string|null $thumbnail_url   Featured image URL or null.
	 * @return array
	 */
	public function make_result( $id, $object_type, $title, $secondary_label = null, $edit_url = null, $thumbnail_url = null ) {
		return array(
			'id'              => (int) $id,
			'object_type'     => (string) $object_type,
			'title'           => (string) $title,
			'secondary_label' => null !== $secondary_label ? (string) $secondary_label : null,
			'edit_url'        => null !== $edit_url ? (string) $edit_url : null,
			'thumbnail_url'   => ! empty( $thumbnail_url ) ? (string) $thumbnail_url : null,
		);
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Validate and sanitize a search term.
	 *
	 * @param string $term
	 * @return string|WP_Error
	 */
	protected function validate_term( $term ) {
		$term = sanitize_text_field( wp_unslash( (string) $term ) );
		if ( strlen( $term ) < self::MIN_TERM_LENGTH ) {
			return new WP_Error(
				'term_too_short',
				sprintf(
					/* translators: %d: minimum character count */
					__( 'Search term must be at least %d characters.', 'native-content-relationships' ),
					self::MIN_TERM_LENGTH
				)
			);
		}
		return $term;
	}

	/**
	 * Clamp a limit to the server-side maximum.
	 *
	 * @param int $requested
	 * @return int
	 */
	protected function clamp_limit( $requested ) {
		return min( max( 1, (int) $requested ), self::MAX_RESULTS );
	}

	/**
	 * Resolve post types, defaulting to all public types.
	 *
	 * @param string|string[]|null $post_type
	 * @return string[]
	 */
	protected function resolve_post_types( $post_type ) {
		if ( ! empty( $post_type ) ) {
			return (array) $post_type;
		}
		return array_keys( get_post_types( array( 'public' => true ) ) );
	}
}

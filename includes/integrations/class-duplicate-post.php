<?php
/**
 * Duplicate Post integration: copy relationships when a post is duplicated
 *
 * Supported plugins:
 * - Yoast Duplicate Post (dp_duplicate_post, dp_duplicate_page)
 * - Post Duplicator (mtphr_post_duplicator_created)
 * - Copy & Delete Posts (via _cdp_origin meta on new post)
 *
 * Other duplicate-post plugins can call naticore_copy_relations( $from_id, $to_id ) or
 * hook naticore_after_duplicate_post.
 *
 * @package NativeContentRelationships
 * @since 1.0.25
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicate Post integration
 */
class NATICORE_Duplicate_Post {

	/**
	 * Hook into duplicate-post plugin actions when present
	 */
	public static function init() {
		// Yoast Duplicate Post
		add_action( 'dp_duplicate_post', array( __CLASS__, 'copy_relations_yoast' ), 10, 3 );
		add_action( 'dp_duplicate_page', array( __CLASS__, 'copy_relations_yoast' ), 10, 3 );

		// Post Duplicator (metaphorcreations) – do_action( 'mtphr_post_duplicator_created', $original_id, $duplicate_id, $settings )
		add_action( 'mtphr_post_duplicator_created', array( __CLASS__, 'copy_relations_post_duplicator' ), 10, 3 );

		// Copy & Delete Posts (Inisev) – stores _cdp_origin on new post with original ID
		add_action( 'added_post_meta', array( __CLASS__, 'copy_relations_on_cdp_origin' ), 10, 4 );
	}

	/**
	 * Copy relations for Yoast Duplicate Post.
	 *
	 * @param int      $new_post_id New post ID.
	 * @param WP_Post  $post        Original post.
	 * @param string   $status      Destination status (e.g. 'draft').
	 */
	public static function copy_relations_yoast( $new_post_id, $post, $status ) {
		if ( ! $new_post_id || ! $post || ! isset( $post->ID ) ) {
			return;
		}
		self::copy_relations( $post->ID, (int) $new_post_id );
	}

	/**
	 * Copy relations for Post Duplicator plugin.
	 *
	 * @param int   $original_id  Original post ID.
	 * @param int   $duplicate_id New post ID.
	 * @param array $settings     Duplication settings (unused).
	 */
	public static function copy_relations_post_duplicator( $original_id, $duplicate_id, $settings ) {
		if ( ! $original_id || ! $duplicate_id ) {
			return;
		}
		self::copy_relations( (int) $original_id, (int) $duplicate_id );
	}

	/**
	 * When Copy & Delete Posts adds _cdp_origin to a new post, copy relations from original to duplicate.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $object_id  New (duplicate) post ID.
	 * @param string $meta_key   Meta key (we only act on _cdp_origin).
	 * @param mixed  $meta_value Original post ID.
	 */
	public static function copy_relations_on_cdp_origin( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( $meta_key !== '_cdp_origin' || ! $object_id || ! $meta_value ) {
			return;
		}
		$origin_id = is_array( $meta_value ) ? (int) reset( $meta_value ) : (int) $meta_value;
		if ( ! $origin_id ) {
			return;
		}
		self::copy_relations( $origin_id, (int) $object_id );
	}

	/**
	 * Copy relations from source post to target post.
	 *
	 * @param int $from_post_id Source post ID.
	 * @param int $to_post_id   Target (duplicate) post ID.
	 */
	protected static function copy_relations( $from_post_id, $to_post_id ) {
		if ( ! function_exists( 'naticore_copy_relations' ) ) {
			return;
		}
		naticore_copy_relations( $from_post_id, $to_post_id );
	}
}

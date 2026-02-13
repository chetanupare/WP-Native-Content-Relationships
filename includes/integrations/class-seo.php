<?php
/**
 * SEO Integration (Yoast SEO / Rank Math)
 * Schema and internal linking support
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_SEO {

	/**
	 * Instance
	 */
	private static $instance = null;

	/**
	 * Is SEO plugin active
	 */
	private $is_seo_active = false;

	/**
	 * SEO plugin type
	 */
	private $plugin_type = '';

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
		// Check if Yoast SEO is active
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			$this->is_seo_active = true;
			$this->plugin_type   = 'yoast';
		}
		// Check if Rank Math is active
		elseif ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			$this->is_seo_active = true;
			$this->plugin_type   = 'rankmath';
		}

		if ( ! $this->is_seo_active ) {
			return; // Exit early if no SEO plugin is active
		}

		// Initialize SEO features
		$this->init();
	}

	/**
	 * Initialize SEO features
	 */
	private function init() {
		// Expose relationships as internal links
		add_filter( 'the_content', array( $this, 'add_internal_links' ), 20 );

		// Add schema references
		if ( $this->plugin_type === 'yoast' ) {
			add_filter( 'wpseo_schema_graph_pieces', array( $this, 'add_yoast_schema' ), 10, 2 );
		} elseif ( $this->plugin_type === 'rankmath' ) {
			add_filter( 'rank_math/schema/validated', array( $this, 'add_rankmath_schema' ), 10, 2 );
		}

		// Expose hook for other plugins
		add_filter( 'naticore_seo_internal_links', array( $this, 'get_internal_links' ), 10, 2 );
	}

	/**
	 * Check if SEO plugin is active
	 */
	public function is_active() {
		return $this->is_seo_active;
	}

	/**
	 * Get internal links from relationships
	 */
	public function get_internal_links( $links, $post_id ) {
		$related = wp_get_related( $post_id, null, array( 'limit' => 10 ) );

		foreach ( $related as $rel ) {
			$post = get_post( $rel['id'] );
			if ( $post && $post->post_status === 'publish' ) {
				$links[] = array(
					'url'  => get_permalink( $rel['id'] ),
					'text' => get_the_title( $rel['id'] ),
					'type' => $rel['type'],
				);
			}
		}

		return $links;
	}

	/**
	 * Add internal links to content (lightweight)
	 */
	public function add_internal_links( $content ) {
		global $post;

		if ( ! $post || ! is_singular() ) {
			return $content;
		}

		$links = apply_filters( 'naticore_seo_internal_links', array(), $post->ID );

		if ( empty( $links ) ) {
			return $content;
		}

		// Add links section at the end (optional, can be filtered out)
		$links_html = apply_filters( 'naticore_seo_internal_links_html', $this->format_links( $links ), $links, $post->ID );

		if ( $links_html ) {
			$content .= $links_html;
		}

		return $content;
	}

	/**
	 * Format links HTML
	 */
	private function format_links( $links ) {
		if ( empty( $links ) ) {
			return '';
		}

		$html  = '<div class="naticore-related-links">';
		$html .= '<h3>' . __( 'Related Content', 'native-content-relationships' ) . '</h3>';
		$html .= '<ul>';

		foreach ( $links as $link ) {
			$html .= sprintf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $link['url'] ),
				esc_html( $link['text'] )
			);
		}

		$html .= '</ul></div>';

		return $html;
	}

	/**
	 * Add Yoast SEO schema
	 */
	public function add_yoast_schema( $pieces, $context ) {
		global $post;

		if ( ! $post ) {
			return $pieces;
		}

		$related = wp_get_related( $post->ID, null, array( 'limit' => 5 ) );

		if ( empty( $related ) ) {
			return $pieces;
		}

		// Add RelatedArticle schema
		$related_articles = array();
		foreach ( $related as $rel ) {
			$related_post = get_post( $rel['id'] );
			if ( $related_post && $related_post->post_status === 'publish' ) {
				$related_articles[] = array(
					'@type' => 'Article',
					'url'   => get_permalink( $rel['id'] ),
					'name'  => get_the_title( $rel['id'] ),
				);
			}
		}

		if ( ! empty( $related_articles ) ) {
			// This would need to be integrated with Yoast's schema system
			// For now, we expose it via filter
			do_action( 'naticore_seo_schema_related', $related_articles, $post->ID );
		}

		return $pieces;
	}

	/**
	 * Add Rank Math schema
	 */
	public function add_rankmath_schema( $data, $json_ld ) {
		global $post;

		if ( ! $post ) {
			return $data;
		}

		$related = wp_get_related( $post->ID, null, array( 'limit' => 5 ) );

		if ( empty( $related ) ) {
			return $data;
		}

		// Expose via filter for Rank Math integration
		do_action( 'naticore_seo_schema_related', $related, $post->ID );

		return $data;
	}
}

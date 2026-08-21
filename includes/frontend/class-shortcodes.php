<?php
/**
 * Built-in shortcodes for displaying related content
 *
 * @package NativeContentRelationships
 * @since 1.0.25
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes for Native Content Relationships
 *
 * Registers and renders [naticore_related_posts], [naticore_related_users],
 * and [naticore_related_terms] using the existing API.
 */
class NATICORE_Shortcodes {

	/**
	 * Instance
	 *
	 * @var NATICORE_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return NATICORE_Shortcodes
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
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Whether shortcode CSS has been enqueued this request
	 *
	 * @var bool
	 */
	private static $enqueued_css = false;

	/**
	 * Register shortcodes
	 */
	public function register_shortcodes() {
		add_shortcode( 'naticore_related_posts', array( $this, 'render_related_posts' ) );
		add_shortcode( 'naticore_related_users', array( $this, 'render_related_users' ) );
		add_shortcode( 'naticore_related_terms', array( $this, 'render_related_terms' ) );
	}

	/**
	 * Enqueue frontend shortcode CSS once per request when a shortcode is used
	 */
	private function maybe_enqueue_shortcode_css() {
		if ( self::$enqueued_css || is_admin() ) {
			return;
		}
		$url = NATICORE_PLUGIN_URL . 'assets/css/shortcodes.css';
		wp_enqueue_style( 'naticore-shortcodes', $url, array(), NATICORE_VERSION );
		self::$enqueued_css = true;
	}

	/**
	 * Parse common shortcode attributes
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $context 'posts'|'users'|'terms'.
	 * @return array Sanitized args for API.
	 */
	private function parse_common_atts( $atts, $context = 'posts' ) {
		$defaults = array(
			'type'           => 'related_to',
			'limit'          => 5,
			'order'          => 'date',
			'post_id'        => 0,
			'layout'         => 'list',
			'class'          => '',
			'title'          => '',
			'show_thumbnail' => 0,
			'excerpt_length' => 0,
		);

		if ( 'posts' === $context ) {
			$defaults['title'] = __( 'Related Content', 'native-content-relationships' );
		} elseif ( 'users' === $context ) {
			$defaults['type']  = 'authored_by';
			$defaults['title'] = __( 'Related Users', 'native-content-relationships' );
		} else {
			$defaults['type']  = 'categorized_as';
			$defaults['title'] = __( 'Related Terms', 'native-content-relationships' );
		}

		$atts = shortcode_atts( $defaults, $atts, 'naticore_related_' . $context );

		$post_id = absint( $atts['post_id'] );
		if ( 0 === $post_id && get_the_ID() ) {
			$post_id = get_the_ID();
		}

		$result = array(
			'type'           => sanitize_key( $atts['type'] ),
			'limit'          => min( 50, max( 1, absint( $atts['limit'] ) ) ),
			'order'          => in_array( $atts['order'], array( 'date', 'title', 'name' ), true ) ? $atts['order'] : 'date',
			'post_id'        => $post_id,
			'layout'         => in_array( $atts['layout'], array( 'list', 'grid' ), true ) ? $atts['layout'] : 'list',
			'class'          => implode( ' ', array_map( 'sanitize_html_class', array_filter( explode( ' ', $atts['class'] ) ) ) ),
			'title'          => $atts['title'],
			'show_thumbnail' => (int) $atts['show_thumbnail'] ? 1 : 0,
			'excerpt_length' => max( 0, (int) $atts['excerpt_length'] ),
		);

		return $result;
	}

	/**
	 * Render [naticore_related_posts]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML or empty string.
	 */
	public function render_related_posts( $atts ) {
		$args = $this->parse_common_atts( (array) $atts, 'posts' );

		if ( 0 === $args['post_id'] ) {
			return '';
		}

		$related = wp_get_related(
			$args['post_id'],
			$args['type'],
			array( 'limit' => $args['limit'] ),
			'post'
		);

		if ( is_wp_error( $related ) || empty( $related ) ) {
			return '';
		}

		$this->maybe_enqueue_shortcode_css();

		$posts = array();
		foreach ( $related as $item ) {
			$id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			if ( ! $id ) {
				continue;
			}
			$post = get_post( $id );
			if ( $post && 'publish' === $post->post_status ) {
				$posts[] = $post;
			}
		}

		if ( empty( $posts ) ) {
			return '';
		}

		if ( 'title' === $args['order'] ) {
			usort(
				$posts,
				function ( $a, $b ) {
					return strcmp( $a->post_title, $b->post_title );
				}
			);
		}

		return $this->render_posts_output( $posts, $args );
	}

	/**
	 * Build HTML output for related posts
	 *
	 * @param WP_Post[] $posts Post objects.
	 * @param array     $args  Parsed args (layout, class, title).
	 * @return string
	 */
	private function render_posts_output( array $posts, $args ) {
		$layout_class   = 'list' === $args['layout'] ? 'naticore-related-list' : 'naticore-related-grid';
		$wrapper_class  = 'naticore-related-posts ' . $layout_class;
		$show_thumbnail = ! empty( $args['show_thumbnail'] );
		$excerpt_length = isset( $args['excerpt_length'] ) ? max( 0, (int) $args['excerpt_length'] ) : 0;

		if ( ! empty( $args['class'] ) ) {
			$wrapper_class .= ' ' . $args['class'];
		}

		$html = '<div class="' . esc_attr( $wrapper_class ) . '">';
		if ( ! empty( $args['title'] ) ) {
			$html .= '<h3 class="naticore-related-title">' . esc_html( $args['title'] ) . '</h3>';
		}
		$html .= '<ul class="naticore-related-items">';

		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post->ID );
			$title     = get_the_title( $post->ID );
			$html     .= '<li class="naticore-related-item-inner">';
			if ( $show_thumbnail && has_post_thumbnail( $post->ID ) ) {
				$html .= '<span class="naticore-related-item-thumb"><a href="' . esc_url( $permalink ) . '">' . get_the_post_thumbnail( $post->ID, 'thumbnail' ) . '</a></span>';
			}
			$html .= '<span class="naticore-related-item-content">';
			$html .= '<a href="' . esc_url( $permalink ) . '" class="naticore-related-item-link">' . esc_html( $title ) . '</a>';
			if ( $excerpt_length > 0 ) {
				$excerpt = has_excerpt( $post->ID ) ? $post->post_excerpt : wp_trim_words( $post->post_content, $excerpt_length );
				$excerpt = wp_trim_words( $excerpt, $excerpt_length );
				if ( $excerpt ) {
					$html .= '<span class="naticore-related-item-excerpt">' . esc_html( $excerpt ) . '</span>';
				}
			}
			$html .= '</span></li>';
		}

		$html .= '</ul></div>';

		return $html;
	}

	/**
	 * Render [naticore_related_users]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML or empty string.
	 */
	public function render_related_users( $atts ) {
		$args = $this->parse_common_atts( (array) $atts, 'users' );

		if ( 0 === $args['post_id'] ) {
			return '';
		}

		$related = wp_get_related_users(
			$args['post_id'],
			$args['type'],
			array( 'limit' => $args['limit'] )
		);

		if ( is_wp_error( $related ) || empty( $related ) ) {
			return '';
		}

		$this->maybe_enqueue_shortcode_css();

		$users = array();
		foreach ( $related as $item ) {
			$id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			if ( ! $id ) {
				continue;
			}
			$user = get_userdata( $id );
			if ( $user ) {
				$users[] = $user;
			}
		}

		if ( empty( $users ) ) {
			return '';
		}

		if ( 'name' === $args['order'] ) {
			usort(
				$users,
				function ( $a, $b ) {
					return strcmp( $a->display_name, $b->display_name );
				}
			);
		}

		return $this->render_users_output( $users, $args );
	}

	/**
	 * Build HTML output for related users
	 *
	 * @param WP_User[] $users User objects.
	 * @param array     $args  Parsed args.
	 * @return string
	 */
	private function render_users_output( array $users, $args ) {
		$layout_class  = 'list' === $args['layout'] ? 'naticore-related-list' : 'naticore-related-grid';
		$wrapper_class = 'naticore-related-users ' . $layout_class;
		if ( ! empty( $args['class'] ) ) {
			$wrapper_class .= ' ' . $args['class'];
		}

		$html = '<div class="' . esc_attr( $wrapper_class ) . '">';
		if ( ! empty( $args['title'] ) ) {
			$html .= '<h3 class="naticore-related-title">' . esc_html( $args['title'] ) . '</h3>';
		}
		$html .= '<ul class="naticore-related-items">';

		foreach ( $users as $user ) {
			$author_url = get_author_posts_url( $user->ID );
			$html      .= sprintf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $author_url ),
				esc_html( $user->display_name )
			);
		}

		$html .= '</ul></div>';

		return $html;
	}

	/**
	 * Render [naticore_related_terms]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML or empty string.
	 */
	public function render_related_terms( $atts ) {
		$args = $this->parse_common_atts( (array) $atts, 'terms' );

		if ( 0 === $args['post_id'] ) {
			return '';
		}

		$related = wp_get_related_terms(
			$args['post_id'],
			$args['type'],
			array( 'limit' => $args['limit'] )
		);

		if ( is_wp_error( $related ) || empty( $related ) ) {
			return '';
		}

		$this->maybe_enqueue_shortcode_css();

		$terms = array();
		foreach ( $related as $item ) {
			$id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			if ( ! $id ) {
				continue;
			}
			$term = get_term( $id );
			if ( $term && ! is_wp_error( $term ) ) {
				$terms[] = $term;
			}
		}

		if ( empty( $terms ) ) {
			return '';
		}

		if ( 'name' === $args['order'] ) {
			usort(
				$terms,
				function ( $a, $b ) {
					return strcmp( $a->name, $b->name );
				}
			);
		}

		return $this->render_terms_output( $terms, $args );
	}

	/**
	 * Build HTML output for related terms
	 *
	 * @param WP_Term[] $terms Term objects.
	 * @param array     $args  Parsed args.
	 * @return string
	 */
	private function render_terms_output( array $terms, $args ) {
		$layout_class  = 'list' === $args['layout'] ? 'naticore-related-list' : 'naticore-related-grid';
		$wrapper_class = 'naticore-related-terms ' . $layout_class;
		if ( ! empty( $args['class'] ) ) {
			$wrapper_class .= ' ' . $args['class'];
		}

		$html = '<div class="' . esc_attr( $wrapper_class ) . '">';
		if ( ! empty( $args['title'] ) ) {
			$html .= '<h3 class="naticore-related-title">' . esc_html( $args['title'] ) . '</h3>';
		}
		$html .= '<ul class="naticore-related-items">';

		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				$link = '#';
			}
			$html .= sprintf(
				'<li><a href="%s">%s</a></li>',
				esc_url( $link ),
				esc_html( $term->name )
			);
		}

		$html .= '</ul></div>';

		return $html;
	}
}

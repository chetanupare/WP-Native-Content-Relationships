<?php
/**
 * Relationship Templates
 *
 * Provides template options for displaying related content.
 *
 * @package Native Content Relationships
 * @since 1.2.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Templates
 *
 * Provides template options for displaying related content.
 */
class NATICORE_Templates {

	/**
	 * Instance
	 *
	 * @var NATICORE_Templates|null
	 */
	private static $instance = null;

	/**
	 * Available templates
	 *
	 * @var array
	 */
	private $templates = array();

	/**
	 * Get instance
	 *
	 * @return NATICORE_Templates
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
		$this->templates = $this->get_default_templates();
		add_shortcode( 'naticore_related_carousel', array( $this, 'render_carousel_shortcode' ) );
	}

	/**
	 * Get default templates
	 *
	 * @return array Templates array.
	 */
	private function get_default_templates() {
		return array(
			'list'      => array(
				'label'   => __( 'List', 'native-content-relationships' ),
				'default' => true,
			),
			'grid'      => array(
				'label'   => __( 'Grid', 'native-content-relationships' ),
				'default' => false,
			),
			'cards'     => array(
				'label'   => __( 'Cards', 'native-content-relationships' ),
				'default' => false,
			),
			'carousel'  => array(
				'label'   => __( 'Carousel', 'native-content-relationships' ),
				'default' => false,
			),
			'minimal'   => array(
				'label'   => __( 'Minimal', 'native-content-relationships' ),
				'default' => false,
			),
		);
	}

	/**
	 * Get available templates
	 *
	 * @return array Templates array.
	 */
	public function get_templates() {
		return $this->templates;
	}

	/**
	 * Render carousel shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_carousel_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'           => 'related_to',
				'limit'          => 10,
				'order'          => 'date',
				'post_id'        => get_the_ID(),
				'title'          => __( 'Related Content', 'native-content-relationships' ),
				'show_thumbnail' => 1,
				'show_excerpt'   => 0,
				'autoplay'       => 1,
				'speed'          => 3000,
			),
			$atts,
			'naticore_related_carousel'
		);

		$post_id = absint( $atts['post_id'] );
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$related = NATICORE_API::get_related( $post_id, $atts['type'], array( 'limit' => absint( $atts['limit'] ) ) );

		if ( empty( $related ) ) {
			return '';
		}

		$carousel_id = 'naticore-carousel-' . wp_unique_id();

		// Enqueue carousel styles
		$this->enqueue_carousel_styles();

		$html = '<div class="naticore-carousel-wrapper" id="' . esc_attr( $carousel_id ) . '">';

		if ( ! empty( $atts['title'] ) ) {
			$html .= '<h3 class="naticore-related-title">' . esc_html( $atts['title'] ) . '</h3>';
		}

		$html .= '<div class="naticore-carousel-container">';
		$html .= '<button class="naticore-carousel-prev" aria-label="' . esc_attr__( 'Previous', 'native-content-relationships' ) . '">&lsaquo;</button>';
		$html .= '<div class="naticore-carousel-track">';

		foreach ( $related as $item ) {
			$to_post = get_post( $item['id'] );
			if ( ! $to_post ) {
				continue;
			}

			$permalink = get_the_permalink( $to_post->ID );
			$title     = get_the_title( $to_post->ID );
			$thumbnail = '';
			if ( ! empty( $atts['show_thumbnail'] ) ) {
				$thumb_id = get_post_thumbnail_id( $to_post->ID );
				if ( $thumb_id ) {
					$thumb = wp_get_attachment_image_src( $thumb_id, 'medium' );
					if ( $thumb ) {
						$thumbnail = $thumb[0];
					}
				}
			}

			$html .= '<div class="naticore-carousel-item">';
			$html .= '<a href="' . esc_url( $permalink ) . '">';

			if ( $thumbnail ) {
				$html .= '<img src="' . esc_url( $thumbnail ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">';
			}

			$html .= '<h4 class="naticore-carousel-item-title">' . esc_html( $title ) . '</h4>';

			if ( ! empty( $atts['show_excerpt'] ) ) {
				$excerpt = wp_trim_words( $to_post->post_content, 20, '...' );
				if ( $excerpt ) {
					$html .= '<p class="naticore-carousel-item-excerpt">' . esc_html( $excerpt ) . '</p>';
				}
			}

			$html .= '</a>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '<button class="naticore-carousel-next" aria-label="' . esc_attr__( 'Next', 'native-content-relationships' ) . '">&rsaquo;</button>';
		$html .= '</div>';
		$html .= '</div>';

		// Add carousel JavaScript
		$html .= '<script>
		(function() {
			var carousel = document.getElementById("' . esc_js( $carousel_id ) . '");
			if (!carousel) return;

			var track = carousel.querySelector(".naticore-carousel-track");
			var items = carousel.querySelectorAll(".naticore-carousel-item");
			var prevBtn = carousel.querySelector(".naticore-carousel-prev");
			var nextBtn = carousel.querySelector(".naticore-carousel-next");
			var currentIndex = 0;
			var autoplay = ' . ( ! empty( $atts['autoplay'] ) ? 'true' : 'false' ) . ';
			var speed = ' . absint( $atts['speed'] ) . ';

			function updateCarousel() {
				var itemWidth = items[0].offsetWidth + parseInt(getComputedStyle(track).gap || 20);
				track.style.transform = "translateX(-" + (currentIndex * itemWidth) + "px)";
			}

			function nextSlide() {
				currentIndex = (currentIndex + 1) % items.length;
				updateCarousel();
			}

			function prevSlide() {
				currentIndex = (currentIndex - 1 + items.length) % items.length;
				updateCarousel();
			}

			if (nextBtn) nextBtn.addEventListener("click", nextSlide);
			if (prevBtn) prevBtn.addEventListener("click", prevSlide);

			if (autoplay && items.length > 1) {
				setInterval(nextSlide, speed);
			}
		})();
		</script>';

		return $html;
	}

	/**
	 * Enqueue carousel styles
	 */
	private function enqueue_carousel_styles() {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;
		?>
		<style>
			.naticore-carousel-wrapper {
				margin: 1em 0;
			}
			.naticore-carousel-container {
				position: relative;
				display: flex;
				align-items: center;
				gap: 10px;
			}
			.naticore-carousel-track {
				display: flex;
				gap: 20px;
				overflow: hidden;
				flex: 1;
				scroll-behavior: smooth;
			}
			.naticore-carousel-item {
				flex: 0 0 200px;
				text-align: center;
			}
			.naticore-carousel-item img {
				width: 100%;
				height: 150px;
				object-fit: cover;
				border-radius: 4px;
				margin-bottom: 10px;
			}
			.naticore-carousel-item-title {
				margin: 0;
				font-size: 14px;
				font-weight: 500;
			}
			.naticore-carousel-item-excerpt {
				margin: 5px 0 0;
				font-size: 12px;
				color: #646970;
			}
			.naticore-carousel-prev,
			.naticore-carousel-next {
				width: 36px;
				height: 36px;
				border: 1px solid #dcdcde;
				background: #fff;
				border-radius: 50%;
				font-size: 20px;
				cursor: pointer;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.naticore-carousel-prev:hover,
			.naticore-carousel-next:hover {
				background: #f6f7f7;
			}
		</style>
		<?php
	}
}

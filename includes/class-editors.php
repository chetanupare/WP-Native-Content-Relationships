<?php
/**
 * Editor Integration (Elementor / Gutenberg)
 * Dynamic content and blocks
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_Editors {
	
	/**
	 * Instance
	 */
	private static $instance = null;
	
	/**
	 * Is Elementor active
	 */
	private $is_elementor_active = false;
	
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
		// Check if Elementor is active
		$this->is_elementor_active = did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
		
		// Initialize editor features
		$this->init();
	}
	
	/**
	 * Initialize editor features
	 */
	private function init() {
		// Gutenberg block (always available)
		add_action( 'init', array( $this, 'register_gutenberg_block' ) );
		
		// Elementor dynamic tags (if active)
		if ( $this->is_elementor_active ) {
			add_action( 'elementor/dynamic_tags/register_tags', array( $this, 'register_elementor_tags' ) );
		}
	}
	
	/**
	 * Register Gutenberg block
	 */
	public function register_gutenberg_block() {
		// Only register if Gutenberg is available
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		
		// Get relation types for block options
		$relation_types = WPNCR_Relation_Types::get_types();
		$type_options = array();
		foreach ( $relation_types as $slug => $type_info ) {
			$type_options[] = array(
				'label' => $type_info['label'],
				'value' => $slug,
			);
		}
		
		register_block_type( 'wpncr/related-posts', array(
			'editor_script' => 'wpncr-gutenberg',
			'render_callback' => array( $this, 'render_related_posts_block' ),
			'attributes' => array(
				'relationType' => array(
					'type' => 'string',
					'default' => 'related_to',
				),
				'limit' => array(
					'type' => 'number',
					'default' => 5,
				),
				'order' => array(
					'type' => 'string',
					'default' => 'date',
				),
			),
		) );
		
		// Enqueue block editor script
		wp_register_script(
			'wpncr-gutenberg',
			WPNCR_PLUGIN_URL . 'assets/js/gutenberg.js',
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
			WPNCR_VERSION,
			true
		);
		
		wp_localize_script( 'wpncr-gutenberg', 'wpncrBlockData', array(
			'relationTypes' => $type_options,
		) );
	}
	
	/**
	 * Render related posts block
	 */
	public function render_related_posts_block( $attributes ) {
		global $post;
		
		if ( ! $post ) {
			return '';
		}
		
		$relation_type = isset( $attributes['relationType'] ) ? $attributes['relationType'] : 'related_to';
		$limit = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 5;
		$order = isset( $attributes['order'] ) ? $attributes['order'] : 'date';
		
		$related = wp_get_related( $post->ID, $relation_type, array( 'limit' => $limit ) );
		
		if ( empty( $related ) ) {
			return '';
		}
		
		// Get post objects
		$posts = array();
		foreach ( $related as $rel ) {
			$related_post = get_post( $rel['id'] );
			if ( $related_post && $related_post->post_status === 'publish' ) {
				$posts[] = $related_post;
			}
		}
		
		// Sort by order
		if ( $order === 'title' ) {
			usort( $posts, function( $a, $b ) {
				return strcmp( $a->post_title, $b->post_title );
			} );
		}
		
		if ( empty( $posts ) ) {
			return '';
		}
		
		$html = '<div class="wpncr-related-posts-block">';
		$html .= '<h3>' . __( 'Related Content', 'native-content-relationships' ) . '</h3>';
		$html .= '<ul>';
		
		foreach ( $posts as $related_post ) {
			$html .= sprintf(
				'<li><a href="%s">%s</a></li>',
				esc_url( get_permalink( $related_post->ID ) ),
				esc_html( get_the_title( $related_post->ID ) )
			);
		}
		
		$html .= '</ul></div>';
		
		return $html;
	}
	
	/**
	 * Register Elementor dynamic tags
	 */
	public function register_elementor_tags( $dynamic_tags_manager ) {
		// Register "Related Content" dynamic tag
		require_once WPNCR_PLUGIN_DIR . 'includes/elementor/class-related-content-tag.php';
		$dynamic_tags_manager->register( new WPNCR_Elementor_Related_Content_Tag() );
	}
}

// Elementor dynamic tag class
if ( ! class_exists( 'WPNCR_Elementor_Related_Content_Tag' ) && class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
	class WPNCR_Elementor_Related_Content_Tag extends \Elementor\Core\DynamicTags\Tag {
		
		public function get_name() {
			return 'wpncr-related-content';
		}
		
		public function get_title() {
			return __( 'Related Content', 'native-content-relationships' );
		}
		
		public function get_categories() {
			return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
		}
		
		public function render() {
			global $post;
			
			if ( ! $post ) {
				return;
			}
			
			$relation_type = $this->get_settings( 'relation_type' );
			$related = wp_get_related( $post->ID, $relation_type, array( 'limit' => 1 ) );
			
			if ( ! empty( $related ) ) {
				$related_post = get_post( $related[0]['id'] );
				if ( $related_post ) {
					echo esc_html( get_the_title( $related_post->ID ) );
				}
			}
		}
		
		protected function _register_controls() {
			$this->add_control( 'relation_type', array(
				'label' => __( 'Relation Type', 'native-content-relationships' ),
				'type' => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_relation_types(),
			) );
		}
		
		private function get_relation_types() {
			$types = WPNCR_Relation_Types::get_types();
			$options = array();
			foreach ( $types as $slug => $type_info ) {
				$options[ $slug ] = $type_info['label'];
			}
			return $options;
		}
	}
}

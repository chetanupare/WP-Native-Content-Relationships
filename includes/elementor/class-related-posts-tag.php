<?php
/**
 * Elementor Dynamic Tag: Related Posts
 *
 * @package Native Content Relationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
	return;
}

class NCR_Related_Posts_Tag extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag name
	 *
	 * @return string Tag name
	 */
	public function get_name() {
		return 'ncr-related-posts';
	}

	/**
	 * Get tag title
	 *
	 * @return string Tag title
	 */
	public function get_title() {
		return __( 'Related Posts', 'native-content-relationships' );
	}

	/**
	 * Get tag categories
	 *
	 * @return array Tag categories
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::POSTS_CATEGORY );
	}

	/**
	 * Get tag group
	 *
	 * @return string Tag group
	 */
	public function get_group() {
		return 'ncr-relationships';
	}

	/**
	 * Register controls
	 */
	protected function register_controls() {
		// Relationship type control
		$this->add_control(
			'relationship_type',
			array(
				'label'   => __( 'Relationship Type', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_relationship_types(),
				'default' => 'related_to',
			)
		);

		// Direction control
		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'outgoing' => __( 'Outgoing (from this post)', 'native-content-relationships' ),
					'incoming' => __( 'Incoming (to this post)', 'native-content-relationships' ),
				),
				'default' => 'outgoing',
			)
		);

		// Output format control
		$this->add_control(
			'output_format',
			array(
				'label'   => __( 'Output Format', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'ids'     => __( 'Post IDs (comma-separated)', 'native-content-relationships' ),
					'titles'  => __( 'Post Titles (comma-separated)', 'native-content-relationships' ),
					'count'   => __( 'Count Only', 'native-content-relationships' ),
					'links'   => __( 'HTML Links', 'native-content-relationships' ),
				),
				'default' => 'ids',
			)
		);

		// Limit control
		$this->add_control(
			'limit',
			array(
				'label'   => __( 'Limit', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'default' => 10,
			)
		);

		// Order by control
		$this->add_control(
			'orderby',
			array(
				'label'   => __( 'Order By', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'date'    => __( 'Date', 'native-content-relationships' ),
					'title'   => __( 'Title', 'native-content-relationships' ),
					'random'  => __( 'Random', 'native-content-relationships' ),
				),
				'default' => 'date',
			)
		);

		// Order control
		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'desc' => __( 'Descending', 'native-content-relationships' ),
					'asc'  => __( 'Ascending', 'native-content-relationships' ),
				),
				'default' => 'desc',
				'condition' => array(
					'orderby!' => 'random',
				),
			)
		);

		// Separator control
		$this->add_control(
			'separator',
			array(
				'type' => \Elementor\Controls_Manager::DIVIDER,
			)
		);

		// Fallback text control
		$this->add_control(
			'fallback',
			array(
				'label'   => __( 'Fallback', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'No related posts found', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Get relationship types
	 *
	 * @return array Relationship types options
	 */
	private function get_relationship_types() {
		$types = NATICORE_Relation_Types::get_types();
		$options = array();

		foreach ( $types as $slug => $type_info ) {
			if ( isset( $type_info['supports_posts'] ) && $type_info['supports_posts'] ) {
				$options[ $slug ] = $type_info['label'];
			}
		}

		return $options;
	}

	/**
	 * Render tag value
	 *
	 * @param array $options Tag options
	 * @return mixed Tag value
	 */
	public function render( $options = array() ) {
		$settings = $this->get_settings();

		if ( ! $settings['relationship_type'] ) {
			return $settings['fallback'];
		}

		// Get current post
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return $settings['fallback'];
		}

		// Prepare query arguments
		$args = array(
			'limit'   => $settings['limit'],
			'orderby' => $settings['orderby'],
			'order'   => $settings['order'],
		);

		// Get related posts
		$related_posts = array();
		
		if ( 'incoming' === $settings['direction'] ) {
			// For incoming relationships, we need to query posts that relate to this post
			$related_posts = $this->get_incoming_related_posts( $post_id, $settings['relationship_type'], $args );
		} else {
			// For outgoing relationships, use the standard API
			$related_posts = NATICORE_API::get_related( $post_id, $settings['relationship_type'], $args, 'post' );
		}

		if ( empty( $related_posts ) ) {
			return $settings['fallback'];
		}

		// Format output based on settings
		return $this->format_output( $related_posts, $settings['output_format'] );
	}

	/**
	 * Get incoming related posts
	 *
	 * @param int    $post_id Current post ID
	 * @param string $type    Relationship type
	 * @param array  $args    Query arguments
	 * @return array Related posts
	 */
	private function get_incoming_related_posts( $post_id, $type, $args ) {
		global $wpdb;

		$limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 10;
		$orderby = isset( $args['orderby'] ) ? $args['orderby'] : 'date';
		$order = isset( $args['order'] ) ? $args['order'] : 'desc';

		// Build ORDER BY clause
		$order_clause = 'ORDER BY cr.created_at DESC';
		if ( 'title' === $orderby ) {
			$order_clause = 'ORDER BY p.post_title ' . strtoupper( $order );
		} elseif ( 'date' === $orderby ) {
			$order_clause = 'ORDER BY p.post_date ' . strtoupper( $order );
		}

		// Query for posts that have relationships to this post
		$sql = "SELECT DISTINCT p.ID, p.post_title, p.post_date
				FROM {$wpdb->prefix}content_relations cr
				INNER JOIN {$wpdb->posts} p ON cr.from_id = p.ID
				WHERE cr.to_id = %d AND cr.to_type = 'post' AND cr.type = %s
				AND p.post_status = 'publish'
				{$order_clause}
				LIMIT %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table query
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $post_id, $type, $limit ) );

		$posts = array();
		foreach ( $results as $row ) {
			$posts[] = array(
				'id'    => $row->ID,
				'title' => $row->post_title,
				'date'  => $row->post_date,
			);
		}

		return $posts;
	}

	/**
	 * Format output based on format type
	 *
	 * @param array  $related_posts Related posts
	 * @param string $format        Output format
	 * @return string Formatted output
	 */
	private function format_output( $related_posts, $format ) {
		switch ( $format ) {
			case 'ids':
				return implode( ',', wp_list_pluck( $related_posts, 'id' ) );

			case 'titles':
				return implode( ', ', wp_list_pluck( $related_posts, 'title' ) );

			case 'count':
				return count( $related_posts );

			case 'links':
				$links = array();
				foreach ( $related_posts as $post ) {
					$links[] = '<a href="' . esc_url( get_permalink( $post['id'] ) ) . '">' . esc_html( $post['title'] ) . '</a>';
				}
				return implode( ', ', $links );

			default:
				return implode( ',', wp_list_pluck( $related_posts, 'id' ) );
		}
	}
}

<?php
/**
 * Elementor Dynamic Tag: Related Terms
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
	return;
}

/**
 * Elementor Dynamic Tag: Related Terms
 *
 * Provides an Elementor dynamic tag to display related terms for a given post.
 * Integrates with the Native Content Relationships plugin to query and
 * display taxonomy term relationships in Elementor templates.
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */
class NATICORE_Related_Terms_Tag extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag name
	 *
	 * @return string Tag name
	 */
	public function get_name() {
		return 'ncr-related-terms';
	}

	/**
	 * Get tag title
	 *
	 * @return string Tag title
	 */
	public function get_title() {
		return __( 'Related Terms', 'native-content-relationships' );
	}

	/**
	 * Get tag categories
	 *
	 * @return array Tag categories
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TAXONOMY_CATEGORY );
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
				'options' => $this->get_term_relationship_types(),
				'default' => 'categorized_as',
			)
		);

		// Direction control
		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'outgoing' => __( 'Terms Related to This Post', 'native-content-relationships' ),
					'incoming' => __( 'Posts Related to This Term', 'native-content-relationships' ),
				),
				'default' => 'outgoing',
			)
		);

		// Taxonomy filter control
		$this->add_control(
			'taxonomy',
			array(
				'label'   => __( 'Taxonomy Filter', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $this->get_taxonomy_options(),
				'default' => '',
				'condition' => array(
					'direction' => 'incoming',
				),
			)
		);

		// Output format control
		$this->add_control(
			'output_format',
			array(
				'label'   => __( 'Output Format', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'ids'       => __( 'Term IDs (comma-separated)', 'native-content-relationships' ),
					'names'     => __( 'Term Names (comma-separated)', 'native-content-relationships' ),
					'slugs'     => __( 'Term Slugs (comma-separated)', 'native-content-relationships' ),
					'count'     => __( 'Count Only', 'native-content-relationships' ),
					'links'     => __( 'Term Archive Links', 'native-content-relationships' ),
					'term_links' => __( 'Term Links (with taxonomy)', 'native-content-relationships' ),
				),
				'default' => 'names',
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
				'default' => __( 'No related terms found', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Get term relationship types
	 *
	 * @return array Term relationship types options
	 */
	private function get_term_relationship_types() {
		$types = NATICORE_Relation_Types::get_post_to_term_types();
		$options = array();

		foreach ( $types as $slug => $type_info ) {
			$options[ $slug ] = $type_info['label'];
		}

		return $options;
	}

	/**
	 * Get taxonomy options
	 *
	 * @return array Taxonomy options
	 */
	private function get_taxonomy_options() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		$options = array( '' => __( 'All Taxonomies', 'native-content-relationships' ) );

		foreach ( $taxonomies as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->label;
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

		// Get current context
		$context = $this->get_context();
		$related_terms = array();

		if ( 'incoming' === $settings['direction'] ) {
			// For incoming relationships, we need to get terms related to this post
			$post_id = $context['post_id'] ?? get_the_ID();
			if ( $post_id ) {
				$related_terms = NATICORE_API::get_related( $post_id, $settings['relationship_type'], array( 'limit' => $settings['limit'] ), 'term' );
			}
		} else {
			// For outgoing relationships, we need to get posts related to this term
			$term_id = $context['term_id'] ?? get_queried_object_id();
			if ( $term_id ) {
				$related_terms = wp_get_term_related_posts( $term_id, $settings['relationship_type'], array( 'limit' => $settings['limit'] ) );
			}
		}

		if ( empty( $related_terms ) ) {
			return $settings['fallback'];
		}

		// Format output based on settings
		return $this->format_output( $related_terms, $settings['output_format'] );
	}

	/**
	 * Get current context
	 *
	 * @return array Current context information
	 */
	private function get_context() {
		$context = array();

		// Try to get post context
		if ( is_singular() ) {
			$context['post_id'] = get_the_ID();
		}

		// Try to get term context
		if ( is_tax() || is_category() || is_tag() ) {
			$context['term_id'] = get_queried_object_id();
		}

		return $context;
	}

	/**
	 * Format output based on format type
	 *
	 * @param array  $related_terms Related terms
	 * @param string $format        Output format
	 * @return string Formatted output
	 */
	private function format_output( $related_terms, $format ) {
		switch ( $format ) {
			case 'ids':
				return implode( ',', wp_list_pluck( $related_terms, 'id' ) );

			case 'names':
				return implode( ', ', wp_list_pluck( $related_terms, 'term_name' ) );

			case 'slugs':
				return implode( ', ', wp_list_pluck( $related_terms, 'term_slug' ) );

			case 'count':
				return count( $related_terms );

			case 'links':
				$links = array();
				foreach ( $related_terms as $term ) {
					$links[] = '<a href="' . esc_url( get_term_link( $term['id'] ) ) . '">' . esc_html( $term['term_name'] ) . '</a>';
				}
				return implode( ', ', $links );

			case 'term_links':
				$links = array();
				foreach ( $related_terms as $term ) {
					$taxonomy_label = get_taxonomy( $term['taxonomy'] )->labels->singular_name;
					$links[] = '<a href="' . esc_url( get_term_link( $term['id'] ) ) . '">' . esc_html( $taxonomy_label ) . ': ' . esc_html( $term['term_name'] ) . '</a>';
				}
				return implode( ', ', $links );

			default:
				return implode( ', ', wp_list_pluck( $related_terms, 'term_name' ) );
		}
	}
}

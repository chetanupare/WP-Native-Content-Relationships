<?php
/**
 * Elementor Dynamic Tag: Related Users
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
	return;
}

/**
 * Elementor Dynamic Tag: Related Users
 *
 * Provides an Elementor dynamic tag to display related users for a given post.
 * Integrates with the Native Content Relationships plugin to query and
 * display user relationships in Elementor templates.
 *
 * @package NativeContentRelationships
 * @since 1.0.11
 */
class NATICORE_Related_Users_Tag extends \Elementor\Core\DynamicTags\Tag {

	/**
	 * Get tag name
	 *
	 * @return string Tag name
	 */
	public function get_name() {
		return 'naticore-related-users';
	}

	/**
	 * Get tag title
	 *
	 * @return string Tag title
	 */
	public function get_title() {
		return __( 'Related Users', 'native-content-relationships' );
	}

	/**
	 * Get tag categories
	 *
	 * @return array Tag categories
	 */
	public function get_categories() {
		return array( \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY );
	}

	/**
	 * Get tag group
	 *
	 * @return string Tag group
	 */
	public function get_group() {
		return 'naticore-relationships';
	}

	/**
	 * Register controls
	 */
	protected function register_controls() {
		// Relationship type control.
		$this->add_control(
			'relationship_type',
			array(
				'label'   => __( 'Relationship Type', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_user_relationship_types(),
				'default' => 'favorite_posts',
			)
		);

		// Direction control.
		$this->add_control(
			'direction',
			array(
				'label'   => __( 'Direction', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'outgoing' => __( 'Users Related to This Post', 'native-content-relationships' ),
					'incoming' => __( 'Posts Related to This User', 'native-content-relationships' ),
				),
				'default' => 'outgoing',
			)
		);

		// Output format control.
		$this->add_control(
			'output_format',
			array(
				'label'   => __( 'Output Format', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => array(
					'ids'           => __( 'User IDs (comma-separated)', 'native-content-relationships' ),
					'names'         => __( 'Display Names (comma-separated)', 'native-content-relationships' ),
					'emails'        => __( 'Emails (comma-separated)', 'native-content-relationships' ),
					'count'         => __( 'Count Only', 'native-content-relationships' ),
					'user_links'    => __( 'User Profile Links', 'native-content-relationships' ),
					'avatar_images' => __( 'Avatar Images', 'native-content-relationships' ),
				),
				'default' => 'names',
			)
		);

		// Limit control.
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

		// Avatar size control.
		$this->add_control(
			'avatar_size',
			array(
				'label'     => __( 'Avatar Size', 'native-content-relationships' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 16,
				'max'       => 512,
				'default'   => 32,
				'condition' => array(
					'output_format' => 'avatar_images',
				),
			)
		);

		// Separator control.
		$this->add_control(
			'separator',
			array(
				'type' => \Elementor\Controls_Manager::DIVIDER,
			)
		);

		// Fallback text control.
		$this->add_control(
			'fallback',
			array(
				'label'   => __( 'Fallback', 'native-content-relationships' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'No related users found', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Get user relationship types
	 *
	 * @return array User relationship types options
	 */
	private function get_user_relationship_types() {
		$types   = NATICORE_Relation_Types::get_post_to_user_types();
		$options = array();

		foreach ( $types as $slug => $type_info ) {
			$options[ $slug ] = $type_info['label'];
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
		$context       = $this->get_context();
		$related_users = array();

		if ( 'incoming' === $settings['direction'] ) {
			// For incoming relationships, we need to get users related to this post
			$post_id = $context['post_id'] ?? get_the_ID();
			if ( $post_id ) {
				$related_users = NATICORE_API::get_related( $post_id, $settings['relationship_type'], array( 'limit' => $settings['limit'] ), 'user' );
			}
		} else {
			// For outgoing relationships, we need to get posts related to this user
			$user_id = $context['user_id'] ?? get_current_user_id();
			if ( $user_id ) {
				$related_users = wp_get_related_users( $user_id, $settings['relationship_type'], array( 'limit' => $settings['limit'] ) );
			}
		}

		if ( empty( $related_users ) ) {
			return $settings['fallback'];
		}

		// Format output based on settings
		return $this->format_output( $related_users, $settings['output_format'], $settings );
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

		// Try to get user context
		if ( is_author() ) {
			$context['user_id'] = get_queried_object_id();
		} elseif ( is_user_logged_in() ) {
			$context['user_id'] = get_current_user_id();
		}

		return $context;
	}

	/**
	 * Format output based on format type
	 *
	 * @param array  $related_users Related users
	 * @param string $format        Output format
	 * @param array  $settings      Tag settings
	 * @return string Formatted output
	 */
	private function format_output( $related_users, $format, $settings ) {
		switch ( $format ) {
			case 'ids':
				return implode( ',', wp_list_pluck( $related_users, 'id' ) );

			case 'names':
				return implode( ', ', wp_list_pluck( $related_users, 'display_name' ) );

			case 'emails':
				return implode( ', ', wp_list_pluck( $related_users, 'user_email' ) );

			case 'count':
				return count( $related_users );

			case 'user_links':
				$links = array();
				foreach ( $related_users as $user ) {
					$links[] = '<a href="' . esc_url( get_edit_user_link( $user['id'] ) ) . '">' . esc_html( $user['display_name'] ) . '</a>';
				}
				return implode( ', ', $links );

			case 'avatar_images':
				$avatars = array();
				$size    = $settings['avatar_size'] ?? 32;
				foreach ( $related_users as $user ) {
					$avatar    = get_avatar( $user['id'], $size, '', false, array( 'class' => 'ncr-user-avatar' ) );
					$avatars[] = $avatar;
				}
				return implode( ' ', $avatars );

			default:
				return implode( ', ', wp_list_pluck( $related_users, 'display_name' ) );
		}
	}
}

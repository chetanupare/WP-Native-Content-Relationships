<?php
/**
 * Gutenberg Sidebar for Native Content Relationships
 *
 * @package NativeContentRelationships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Sidebar {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		global $post;
		if ( ! $post ) {
			return;
		}

		$post_type = get_post_type();
		$pt_obj    = get_post_type_object( $post_type );

		if ( ! $pt_obj || ! $pt_obj->show_in_rest ) {
			return;
		}

		$settings           = NATICORE_Settings::get_instance();
		$enabled_post_types = $settings->get_setting( 'enabled_post_types', array( 'post', 'page' ) );

		if ( empty( $enabled_post_types ) ) {
			$enabled_post_types = array_keys( get_post_types( array( 'public' => true ) ) );
		}

		if ( ! in_array( $post_type, $enabled_post_types, true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		wp_enqueue_script(
			'naticore-relationship-panel',
			NATICORE_PLUGIN_URL . 'assets/js/relationship-panel.js',
			array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-i18n', 'wp-data', 'wp-api-fetch' ),
			NATICORE_VERSION,
			true
		);

		wp_enqueue_style(
			'naticore-relationship-panel',
			NATICORE_PLUGIN_URL . 'assets/css/relationship-panel.css',
			array(),
			NATICORE_VERSION
		);

		wp_localize_script(
			'naticore-relationship-panel',
			'naticorePanelData',
			array(
				'postId'        => $post->ID,
				'postType'      => $post_type,
				'restBase'      => rest_url( 'naticore/v1' ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'     => wp_create_nonce( 'naticore_ajax' ),
				'relationships' => $this->get_initial_relationships( $post->ID ),
				'types'         => $this->get_sidebar_types( $post_type ),
				'canEdit'       => current_user_can( 'edit_post', $post->ID ),
				'canListUsers'  => current_user_can( 'list_users' ),
				'i18n'          => array(
					'panelTitle'        => __( 'Relationships', 'native-content-relationships' ),
					'addRelationship'   => __( '+ Add Relationship', 'native-content-relationships' ),
					'searchPlaceholder' => __( 'Search...', 'native-content-relationships' ),
					'noRelationships'   => __( 'No relationships yet.', 'native-content-relationships' ),
					'bidirectionalHint' => __( 'Both items will show this connection.', 'native-content-relationships' ),
					'remove'            => __( 'Remove', 'native-content-relationships' ),
					'selectType'        => __( 'Select type', 'native-content-relationships' ),
					'cancel'            => __( 'Cancel', 'native-content-relationships' ),
				),
			)
		);
	}

	private function get_initial_relationships( $post_id ) {
		$related = NATICORE_API::get_related( $post_id, null, array( 'limit' => 100 ), 'all' );
		if ( is_wp_error( $related ) || ! is_array( $related ) ) {
			return array();
		}

		$out = array();
		foreach ( $related as $item ) {
			$type = isset( $item['type'] ) ? $item['type'] : '';
			if ( ! isset( $out[ $type ] ) ) {
				$out[ $type ] = array();
			}
			$out[ $type ][] = array(
				'id'           => isset( $item['id'] ) ? (int) $item['id'] : 0,
				'to_type'      => isset( $item['to_type'] ) ? $item['to_type'] : 'post',
				'title'        => isset( $item['post_title'] ) ? $item['post_title'] : ( isset( $item['display_name'] ) ? $item['display_name'] : '' ),
				'edit_url'     => isset( $item['to_type'] ) && 'post' === $item['to_type'] && ! empty( $item['id'] ) ? get_edit_post_link( $item['id'], 'raw' ) : '',
				'object_label' => isset( $item['post_type_label'] ) ? $item['post_type_label'] : ( isset( $item['to_type'] ) ? $item['to_type'] : 'post' ),
			);
		}

		return $out;
	}

	private function get_sidebar_types( $current_post_type ) {
		$types = NATICORE_Relation_Types::get_types();
		$out   = array();

		foreach ( $types as $slug => $config ) {
			// Only include types where from_type is post and current post type is allowed
			if ( 'post' !== ( isset( $config['from'] ) ? $config['from'] : 'post' ) ) {
				continue;
			}

			$allowed = isset( $config['allowed_post_types'] ) ? $config['allowed_post_types'] : array();
			if ( ! empty( $allowed ) && ! in_array( $current_post_type, $allowed, true ) ) {
				continue;
			}

			$out[ $slug ] = array(
				'label'           => isset( $config['label'] ) ? $config['label'] : $slug,
				'to_type'         => isset( $config['to'] ) ? $config['to'] : 'post',
				'bidirectional'   => ! empty( $config['bidirectional'] ),
				'max_connections' => isset( $config['max_connections'] ) ? (int) $config['max_connections'] : 0,
			);
		}

		return $out;
	}
}

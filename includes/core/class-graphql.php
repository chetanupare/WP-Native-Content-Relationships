<?php
/**
 * GraphQL Integration
 *
 * @package Native_Content_Relationships
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_GraphQL
 *
 * Registers GraphQL types and connections for WPGraphQL.
 */
class NATICORE_GraphQL {

	/**
	 * Constructor - check if WPGraphQL is active
	 */
	public function __construct() {
		if ( ! $this->is_wpgraphql_active() ) {
			return;
		}

		add_action( 'graphql_register_types', array( $this, 'register_types' ) );
		add_action( 'graphql_register_types', array( $this, 'register_connections' ) );
		add_filter( 'graphql_post_fields', array( $this, 'add_relationship_field' ), 10, 1 );
	}

	/**
	 * Check if WPGraphQL is active
	 *
	 * @return bool
	 */
	private function is_wpgraphql_active() {
		return defined( 'WPGRAPHQL_VERSION' ) || class_exists( 'WPGraphQL' );
	}

	/**
	 * Register GraphQL types
	 */
	public function register_types() {
		register_graphql_object_type( 'NaticoreRelationship', array(
			'description' => __( 'A content relationship', 'native-content-relationships' ),
			'fields'      => array(
				'id'          => array(
					'type'        => 'ID',
					'description' => __( 'The relationship ID', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return $relationship['id'];
					},
				),
				'relationId'  => array(
					'type'        => 'Int',
					'description' => __( 'The numeric relationship ID', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return absint( $relationship['id'] );
					},
				),
				'type'        => array(
					'type'        => 'String',
					'description' => __( 'The relationship type', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return $relationship['type'];
					},
				),
				'direction'   => array(
					'type'        => 'String',
					'description' => __( 'The relationship direction (bidirectional/unidirectional)', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return $relationship['direction'] ?? 'unidirectional';
					},
				),
				'meta'        => array(
					'type'        => 'NaticoreRelationshipMeta',
					'description' => __( 'The relationship metadata', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						if ( class_exists( 'NATICORE_Meta_API' ) ) {
							return NATICORE_Meta_API::get_all_meta( $relationship['relation_id'] );
						}
						return array();
					},
				),
				'fromPost'    => array(
					'type'        => 'Post',
					'description' => __( 'The source post', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return \WPGraphQL\Utils\Utils::get_post_node( $relationship['from_id'] );
					},
				),
				'toPost'      => array(
					'type'        => 'Post',
					'description' => __( 'The target post', 'native-content-relationships' ),
					'resolve'     => function ( $relationship ) {
						return \WPGraphQL\Utils\Utils::get_post_node( $relationship['to_id'] );
					},
				),
			),
		) );

		register_graphql_object_type( 'NaticoreRelationshipMeta', array(
			'description' => __( 'Relationship metadata', 'native-content-relationships' ),
			'fields'      => array(
				'role'  => array(
					'type'        => 'String',
					'description' => __( 'The role in this relationship', 'native-content-relationships' ),
				),
				'note'  => array(
					'type'        => 'String',
					'description' => __( 'Notes about this relationship', 'native-content-relationships' ),
				),
				'order' => array(
					'type'        => 'Int',
					'description' => __( 'Sort order', 'native-content-relationships' ),
				),
			),
		) );

		register_graphql_enum_type( 'NaticoreRelationTypeEnum', array(
			'description' => __( 'Relationship type', 'native-content-relationships' ),
			'values'      => $this->get_relation_type_enum_values(),
		) );
	}

	/**
	 * Register GraphQL connections
	 */
	public function register_connections() {
		register_graphql_connection( array(
			'fromType'         => 'Post',
			'toType'           => 'Post',
			'fromFieldName'    => 'ncrRelationships',
			'connectionTypeName' => 'NaticoreRelationshipConnection',
			'description'      => __( 'Relationships from this post', 'native-content-relationships' ),
			'resolve'          => function ( $source, $args, $context, $info ) {
				$relationships = NATICORE_API::get_all_relations( $source->databaseId );

				$connection = array(
					'nodes'    => $relationships,
					'total'    => count( $relationships ),
					'hasMore'  => false,
				);

				return $connection;
			},
		) );

		register_graphql_connection( array(
			'fromType'         => 'Post',
			'toType'           => 'Post',
			'fromFieldName'    => 'ncrRelatedTo',
			'connectionTypeName' => 'NaticoreRelationshipConnection',
			'description'      => __( 'Posts that relate to this post', 'native-content-relationships' ),
			'resolve'          => function ( $source, $args, $context, $info ) {
				$relationships = NATICORE_API::get_related_to( $source->databaseId );

				$connection = array(
					'nodes'    => $relationships,
					'total'    => count( $relationships ),
					'hasMore'  => false,
				);

				return $connection;
			},
		) );
	}

	/**
	 * Add relationship field to Post type
	 *
	 * @param array $fields Post fields.
	 * @return array Modified fields.
	 */
	public function add_relationship_field( $fields ) {
		$fields['ncr_relationships'] = array(
			'type'        => array( 'list_of' => 'NaticoreRelationship' ),
			'description' => __( 'Content relationships', 'native-content-relationships' ),
			'resolve'     => function ( $post ) {
				return NATICORE_API::get_all_relations( $post->databaseId );
			},
		);

		$fields['ncr_related_to'] = array(
			'type'        => array( 'list_of' => 'NaticoreRelationship' ),
			'description' => __( 'Posts that relate to this post', 'native-content-relationships' ),
			'resolve'     => function ( $post ) {
				return NATICORE_API::get_related_to( $post->databaseId );
			},
		);

		return $fields;
	}

	/**
	 * Get relation type enum values
	 *
	 * @return array Enum values.
	 */
	private function get_relation_type_enum_values() {
		$types = NATICORE_Relation_Types::get_types();
		$values = array();

		foreach ( $types as $key => $type ) {
			$values[ strtoupper( $key ) ] = array(
				'value' => $key,
				'label' => $type['label'],
			);
		}

		return $values;
	}
}

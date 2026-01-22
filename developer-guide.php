<?php
/**
 * Developer Guide for WP Native Content Relationships
 * 
 * This file contains comprehensive examples and documentation for developers.
 * 
 * @package WP_Native_Content_Relationships
 * @subpackage Developer_Guide
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DEVELOPER GUIDE
 * 
 * This file serves as a reference guide for developers working with
 * WP Native Content Relationships plugin.
 * 
 * DO NOT include this file in production - it's for reference only.
 */

// ============================================================================
// BASIC USAGE EXAMPLES
// ============================================================================

/**
 * Example 1: Create a simple relationship
 */
function example_create_relationship() {
	$product_id = 123;
	$accessory_id = 456;
	
	$result = wp_add_relation( $product_id, $accessory_id, 'accessory_of' );
	
	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( 'Failed to create relationship: ' . $result->get_error_message() );
		}
		return;
	}
	
	echo 'Relationship created with ID: ' . esc_html( $result );
}

/**
 * Example 2: Get all related posts
 */
function example_get_related_posts() {
	$post_id = get_the_ID();
	$related = wp_get_related( $post_id, 'related_to', array( 'limit' => 10 ) );
	
	if ( empty( $related ) ) {
		echo 'No related posts found.';
		return;
	}
	
	echo '<ul>';
	foreach ( $related as $post ) {
		echo '<li><a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Example 3: Check if two posts are related
 */
function example_check_relationship() {
	$post_1 = 123;
	$post_2 = 456;
	
	if ( wp_is_related( $post_1, $post_2, 'related_to' ) ) {
		echo 'These posts are related!';
	} else {
		echo 'These posts are not related.';
	}
}

/**
 * Example 4: Remove a relationship
 */
function example_remove_relationship() {
	$from_id = 123;
	$to_id = 456;
	
	$result = wp_remove_relation( $from_id, $to_id, 'related_to' );
	
	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( 'Failed to remove relationship: ' . $result->get_error_message() );
		}
		return;
	}
	
	if ( $result ) {
		echo 'Relationship removed successfully.';
	}
}

// ============================================================================
// FLUENT API EXAMPLES
// ============================================================================

/**
 * Example 5: Using the Fluent API to create relationships
 */
function example_fluent_api_create() {
	$result = wpncr()
		->from( 123 )
		->to( 456 )
		->type( 'references' )
		->create();
	
	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( 'Error: ' . $result->get_error_message() );
		}
	} else {
		echo 'Relationship created: ' . esc_html( $result );
	}
}

/**
 * Example 6: Using Fluent API to get related posts
 */
function example_fluent_api_get() {
	$related = wpncr()
		->from( get_the_ID() )
		->type( 'related_to' )
		->get( array( 'limit' => 5 ) );
	
	if ( is_wp_error( $related ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( 'Error: ' . $related->get_error_message() );
		}
		return;
	}
	
	foreach ( $related as $post ) {
		echo '<h3>' . esc_html( $post->post_title ) . '</h3>';
	}
}

/**
 * Example 7: Using Fluent API to check existence
 */
function example_fluent_api_exists() {
	$exists = wpncr()
		->from( 123 )
		->to( 456 )
		->type( 'references' )
		->exists();
	
	if ( is_wp_error( $exists ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( 'Error: ' . $exists->get_error_message() );
		}
		return;
	}
	
	echo $exists ? 'Relationship exists' : 'Relationship does not exist';
}

// ============================================================================
// WP_QUERY INTEGRATION EXAMPLES
// ============================================================================

/**
 * Example 8: Query posts by relationship
 */
function example_query_by_relationship() {
	$query = new WP_Query( array(
		'post_type' => 'post',
		'posts_per_page' => 10,
		'content_relation' => array(
			'post_id' => get_the_ID(),
			'type' => 'references',
			'direction' => 'outgoing',
		),
	) );
	
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<h2>' . esc_html( get_the_title() ) . '</h2>';
		}
		wp_reset_postdata();
	}
}

/**
 * Example 9: Query posts that reference the current post
 */
function example_query_incoming_relations() {
	$query = new WP_Query( array(
		'post_type' => 'post',
		'content_relation' => array(
			'post_id' => get_the_ID(),
			'type' => 'references',
			'direction' => 'incoming',
		),
	) );
	
	return $query;
}

/**
 * Example 10: Using cleaner WP_Query syntax
 */
function example_clean_query_syntax() {
	$query = new WP_Query( array(
		'post_type' => 'product',
		'wpcr' => array(
			'from' => 123,
			'type' => 'accessory_of',
		),
	) );
	
	return $query;
}

// ============================================================================
// CUSTOM RELATIONSHIP TYPES
// ============================================================================

/**
 * Example 11: Register a custom relationship type
 */
function example_register_custom_type() {
	add_action( 'wpncr_register_relation_types', function() {
		register_content_relation_type( 'part_of', array(
			'label'            => 'Part Of',
			'bidirectional'    => false,
			'allowed_post_types' => array( 'post', 'page' ),
		) );
	} );
}

/**
 * Example 12: Register multiple custom types
 */
function example_register_multiple_types() {
	add_action( 'wpncr_register_relation_types', function() {
		// Lesson is part of a course
		register_content_relation_type( 'lesson_of', array(
			'label'            => 'Lesson Of',
			'bidirectional'    => false,
			'allowed_post_types' => array( 'lesson', 'course' ),
		) );
		
		// Product has accessories
		register_content_relation_type( 'has_accessory', array(
			'label'            => 'Has Accessory',
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
	} );
}

// ============================================================================
// HOOKS AND FILTERS
// ============================================================================

/**
 * Example 13: Modify relationship validation
 */
function example_filter_relationship_allowed() {
	add_filter( 'wpncr_relation_is_allowed', function( $allowed, $context ) {
		// Prevent relationships between posts in different categories
		$from_category = wp_get_post_categories( $context['from_id'] );
		$to_category = wp_get_post_categories( $context['to_id'] );
		
		$common_categories = array_intersect( $from_category, $to_category );
		
		if ( empty( $common_categories ) ) {
			return new WP_Error( 'no_common_category', 'Posts must share at least one category.' );
		}
		
		return $allowed;
	}, 10, 2 );
}

/**
 * Example 14: Hook into relationship creation
 */
function example_hook_relation_added() {
	add_action( 'wpncr_relation_added', function( $from_id, $to_id, $type ) {
		// Log relationship creation
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
			error_log( sprintf( 'Relationship created: %d -> %d (%s)', $from_id, $to_id, $type ) );
		}
		
		// Send notification email
		$from_title = get_the_title( $from_id );
		$to_title = get_the_title( $to_id );
		wp_mail( 
			get_option( 'admin_email' ),
			'New Relationship Created',
			"A new relationship was created: {$from_title} -> {$to_title} ({$type})"
		);
	}, 10, 3 );
}

/**
 * Example 15: Modify get_related arguments
 */
function example_filter_get_related_args() {
	add_filter( 'wpncr_get_related_args', function( $args, $post_id, $type ) {
		// Always limit to 20 results
		$args['limit'] = 20;
		
		// Only get published posts
		$args['post_status'] = 'publish';
		
		return $args;
	}, 10, 3 );
}

// ============================================================================
// WOOCOMMERCE INTEGRATION
// ============================================================================

/**
 * Example 16: Get related WooCommerce products
 */
function example_get_related_products() {
	if ( ! function_exists( 'wp_get_related_products' ) ) {
		return;
	}
	
	$product_id = get_the_ID();
	$accessories = wp_get_related_products( $product_id, 'accessory_of' );
	
	if ( empty( $accessories ) ) {
		return;
	}
	
	echo '<h3>Accessories</h3>';
	echo '<ul>';
	foreach ( $accessories as $product ) {
		echo '<li>';
		echo '<a href="' . esc_url( get_permalink( $product->ID ) ) . '">';
		echo esc_html( $product->post_title );
		echo '</a>';
		echo '</li>';
	}
	echo '</ul>';
}

// ============================================================================
// REST API EXAMPLES
// ============================================================================

/**
 * Example 17: Fetch relationships via REST API (JavaScript)
 */
function example_rest_api_javascript() {
	?>
	<script>
	// Get relationships for a post
	fetch( '/wp-json/wpncr/v1/relations/123' )
		.then( response => response.json() )
		.then( data => {
			console.log( 'Relationships:', data );
		} );
	
	// Create a relationship
	fetch( '/wp-json/wpncr/v1/relations', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': wpApiSettings.nonce
		},
		body: JSON.stringify( {
			from_id: 123,
			to_id: 456,
			type: 'references'
		} )
	} )
		.then( response => response.json() )
		.then( data => {
			console.log( 'Relationship created:', data );
		} );
	</script>
	<?php
}

// ============================================================================
// ERROR HANDLING
// ============================================================================

/**
 * Example 18: Comprehensive error handling
 */
function example_error_handling() {
	$result = wp_add_relation( 123, 456, 'references' );
	
	if ( is_wp_error( $result ) ) {
		$error_code = $result->get_error_code();
		$error_message = $result->get_error_message();
		
		switch ( $error_code ) {
			case 'self_relation':
				echo 'Cannot relate a post to itself.';
				break;
			case 'infinite_loop':
				echo 'Circular relationship detected.';
				break;
			case 'relation_exists':
				echo 'This relationship already exists.';
				break;
			case 'invalid_post_type':
				echo 'This post type is not allowed for this relationship type.';
				break;
			case 'max_relationships':
				echo 'Maximum relationships limit reached.';
				break;
			default:
				echo 'Error: ' . esc_html( $error_message );
		}
		
		return;
	}
	
	echo 'Success! Relationship ID: ' . esc_html( $result );
}

// ============================================================================
// BULK OPERATIONS
// ============================================================================

/**
 * Example 19: Bulk create relationships
 */
function example_bulk_create() {
	$product_id = 123;
	$accessory_ids = array( 456, 789, 101, 202, 303 );
	
	$created = 0;
	$errors = 0;
	
	foreach ( $accessory_ids as $accessory_id ) {
		$result = wp_add_relation( $product_id, $accessory_id, 'accessory_of' );
		
		if ( is_wp_error( $result ) ) {
			$errors++;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when enabled
				error_log( 'Failed to create relationship: ' . $result->get_error_message() );
			}
		} else {
			$created++;
		}
	}
	
	echo sprintf( 'Created %d relationships, %d errors.', absint( $created ), absint( $errors ) );
}

/**
 * Example 20: Remove all relationships of a specific type
 */
function example_bulk_remove() {
	$post_id = 123;
	$type = 'related_to';
	
	$related = wp_get_related( $post_id, $type );
	
	if ( empty( $related ) ) {
		echo 'No relationships to remove.';
		return;
	}
	
	$removed = 0;
	foreach ( $related as $related_post ) {
		$result = wp_remove_relation( $post_id, $related_post->ID, $type );
		if ( ! is_wp_error( $result ) && $result ) {
			$removed++;
		}
	}
	
	echo sprintf( 'Removed %d relationships.', absint( $removed ) );
}

// ============================================================================
// TEMPLATE INTEGRATION
// ============================================================================

/**
 * Example 21: Display related posts in a template
 */
function example_template_display() {
	$related = wp_get_related( get_the_ID(), 'related_to', array( 'limit' => 6 ) );
	
	if ( empty( $related ) ) {
		return;
	}
	?>
	<section class="related-posts">
		<h2><?php esc_html_e( 'Related Posts', 'native-content-relationships' ); ?></h2>
		<div class="related-posts-grid">
			<?php foreach ( $related as $post ) : ?>
				<article class="related-post">
					<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
						<?php if ( has_post_thumbnail( $post->ID ) ) : ?>
							<?php echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
						<?php endif; ?>
						<h3><?php echo esc_html( $post->post_title ); ?></h3>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Example 22: Shortcode for related posts
 */
function example_related_posts_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'type' => 'related_to',
		'limit' => 5,
		'post_id' => get_the_ID(),
	), $atts );
	
	$related = wp_get_related( 
		absint( $atts['post_id'] ), 
		sanitize_text_field( $atts['type'] ),
		array( 'limit' => absint( $atts['limit'] ) )
	);
	
	if ( empty( $related ) ) {
		return '';
	}
	
	$output = '<ul class="related-posts-list">';
	foreach ( $related as $post ) {
		$output .= '<li><a href="' . esc_url( get_permalink( $post->ID ) ) . '">';
		$output .= esc_html( $post->post_title );
		$output .= '</a></li>';
	}
	$output .= '</ul>';
	
	return $output;
}
add_shortcode( 'related_posts', 'example_related_posts_shortcode' );

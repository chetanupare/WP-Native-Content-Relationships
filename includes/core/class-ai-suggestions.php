<?php
/**
 * AI-Powered Relationship Suggestions
 *
 * Uses WordPress 7.0 AI Client to suggest content relationships
 * based on semantic analysis of post content.
 *
 * @package Native Content Relationships
 * @since 1.1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_AI_Suggestions
 *
 * Provides AI-powered relationship suggestions using the WordPress 7.0 AI Client.
 * Falls back to category/tag-based suggestions when AI is not available.
 */
class NATICORE_AI_Suggestions {

	/**
	 * Instance
	 *
	 * @var NATICORE_AI_Suggestions|null
	 */
	private static $instance = null;

	/**
	 * Whether AI Client is available
	 *
	 * @var bool
	 */
	private $ai_available = false;

	/**
	 * Get instance
	 *
	 * @return NATICORE_AI_Suggestions
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
		$this->ai_available = $this->check_ai_availability();

		// Register auto-link on publish hook
		add_action( 'publish_post', array( $this, 'auto_link_on_publish' ), 100, 2 );
		add_action( 'publish_page', array( $this, 'auto_link_on_publish' ), 100, 2 );

		// Admin notice for auto-linked relationships
		add_action( 'admin_notices', array( $this, 'render_auto_link_notice' ) );
	}

	/**
	 * Check if WordPress AI Client is available
	 *
	 * @return bool True if AI Client is available
	 */
	private function check_ai_availability() {
		// WordPress 7.0+ has wp_ai_client_prompt()
		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			return true;
		}

		// Check for the wordpress/wp-ai-client package (pre-7.0 compatibility)
		if ( class_exists( ' WP_AI_Client_Prompt_Builder' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if AI suggestions are enabled in settings
	 *
	 * @return bool True if AI suggestions are enabled
	 */
	public function is_enabled() {
		if ( ! $this->ai_available ) {
			return false;
		}

		$settings = get_option( 'naticore_settings', array() );
		return isset( $settings['enable_ai_suggestions'] ) && 1 === (int) $settings['enable_ai_suggestions'];
	}

	/**
	 * Get AI-powered relationship suggestions for a post
	 *
	 * @param int $post_id The post ID to get suggestions for.
	 * @param int $limit   Maximum number of suggestions.
	 * @return array Array of suggested post data.
	 */
	public function get_suggestions( $post_id, $limit = 5 ) {
		if ( ! $this->is_enabled() ) {
			return $this->get_fallback_suggestions( $post_id, $limit );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		// Get post content for analysis
		$content = $this->prepare_content_for_ai( $post );

		// Get existing relationships to exclude
		$existing = $this->get_existing_relationship_ids( $post_id );

		// Get available post IDs (published, excluding current and already related)
		$available_ids = $this->get_available_post_ids( $post_id, $existing );

		if ( empty( $available_ids ) ) {
			return array();
		}

		// Build the prompt
		$prompt = $this->build_suggestion_prompt( $content, $available_ids, $limit );

		// Call AI Client
		$suggestions = $this->call_ai( $prompt );

		if ( is_wp_error( $suggestions ) ) {
			// Fallback to category/tag suggestions
			return $this->get_fallback_suggestions( $post_id, $limit );
		}

		// Parse AI response into post data
		return $this->parse_ai_response( $suggestions, $available_ids );
	}

	/**
	 * Prepare post content for AI analysis
	 *
	 * @param WP_Post $post The post object.
	 * @return string Prepared content string.
	 */
	private function prepare_content_for_ai( $post ) {
		$title   = $post->post_title;
		$excerpt = wp_trim_words( $post->post_content, 100, '...' );
		$terms   = wp_get_post_terms( $post->ID, array( 'category', 'post_tag' ), array( 'fields' => 'names' ) );
		$tax     = ! is_wp_error( $terms ) ? implode( ', ', $terms ) : '';

		$content = "Title: {$title}\n";
		if ( $tax ) {
			$content .= "Topics: {$tax}\n";
		}
		$content .= "Content: {$excerpt}";

		return $content;
	}

	/**
	 * Get existing relationship IDs for a post
	 *
	 * @param int $post_id The post ID.
	 * @return array Array of related post IDs.
	 */
	private function get_existing_relationship_ids( $post_id ) {
		$relations = NATICORE_API::get_all_relations( $post_id );
		$ids       = array();
		foreach ( $relations as $rel ) {
			if ( ! empty( $rel->to_id ) ) {
				$ids[] = (int) $rel->to_id;
			}
		}
		return array_unique( $ids );
	}

	/**
	 * Get available post IDs for suggestions
	 *
	 * @param int   $post_id   Current post ID.
	 * @param array $exclude   IDs to exclude.
	 * @return array Array of available post IDs.
	 */
	private function get_available_post_ids( $post_id, $exclude = array() ) {
		$exclude[] = $post_id;

		$args = array(
			'post_type'      => get_post_types( array( 'public' => true ) ),
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'post__not_in'   => $exclude,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		return get_posts( $args );
	}

	/**
	 * Build the AI prompt for relationship suggestions
	 *
	 * @param string $content       Post content to analyze.
	 * @param array  $available_ids Available post IDs.
	 * @param int    $limit         Maximum suggestions.
	 * @return string The prompt string.
	 */
	private function build_suggestion_prompt( $content, $available_ids, $limit ) {
		// Get titles of available posts for context
		$available_titles = array();
		foreach ( array_slice( $available_ids, 0, 20 ) as $id ) {
			$title = get_the_title( $id );
			if ( $title ) {
				$available_titles[] = "{$id}: {$title}";
			}
		}

		$titles_list = implode( "\n", $available_titles );

		return "You are a content relationship analyzer for a WordPress website.

Analyze the following content and suggest the {$limit} most relevant related posts from the available list.

CONTENT TO ANALYZE:
{$content}

AVAILABLE POSTS (ID: Title):
{$titles_list}

Respond with ONLY a JSON array of post IDs, ordered by relevance (most relevant first).
Example: [123, 456, 789]

Do not include any explanation. Only the JSON array.";
	}

	/**
	 * Call the WordPress AI Client
	 *
	 * @param string $prompt The prompt to send.
	 * @return mixed AI response or WP_Error on failure.
	 */
	private function call_ai( $prompt ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new WP_Error( 'ai_not_available', __( 'WordPress AI Client is not available.', 'native-content-relationships' ) );
		}

		try {
			$result = wp_ai_client_prompt()
				->using_model_preference( array( 'openai', 'anthropic', 'google' ) )
				->with_system_instruction(
					__( 'You are a content analysis assistant. Respond only with valid JSON.', 'native-content-relationships' )
				)
				->with_user_message( $prompt )
				->generate_text_result();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$response_text = $result->get_text();

			// Clean the response - extract JSON array
			$response_text = trim( $response_text );
			$response_text = preg_replace( '/^[^[]*/', '', $response_text );
			$response_text = preg_replace( '/[^]]*$/', '', $response_text );

			$ids = json_decode( $response_text, true );

			if ( ! is_array( $ids ) ) {
				return new WP_Error( 'invalid_response', __( 'Invalid AI response format.', 'native-content-relationships' ) );
			}

			return $ids;

		} catch ( \Exception $e ) {
			return new WP_Error( 'ai_error', $e->getMessage() );
		}
	}

	/**
	 * Parse AI response into post data
	 *
	 * @param array $post_ids     Array of post IDs from AI.
	 * @param array $available_ids Available post IDs.
	 * @return array Array of post data.
	 */
	private function parse_ai_response( $post_ids, $available_ids ) {
		$results = array();

		foreach ( $post_ids as $id ) {
			$id = absint( $id );

			// Validate the ID is in our available list
			if ( ! in_array( $id, $available_ids, true ) ) {
				continue;
			}

			$post = get_post( $id );
			if ( ! $post ) {
				continue;
			}

			$thumbnail_url = '';
			$thumbnail_id  = get_post_thumbnail_id( $id );
			if ( $thumbnail_id ) {
				$thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
				if ( $thumbnail ) {
					$thumbnail_url = $thumbnail[0];
				}
			}

			$results[] = array(
				'id'        => $id,
				'title'     => $post->post_title,
				'type'      => $post->post_type,
				'thumbnail' => $thumbnail_url,
				'url'       => get_the_permalink( $id ),
				'source'    => 'ai',
			);
		}

		return $results;
	}

	/**
	 * Get fallback suggestions (category/tag-based)
	 *
	 * Used when AI is not available or disabled.
	 *
	 * @param int $post_id The post ID.
	 * @param int $limit   Maximum suggestions.
	 * @return array Array of suggested post data.
	 */
	public function get_fallback_suggestions( $post_id, $limit = 5 ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array();
		}

		$exclude = array( $post_id );

		// Already related post IDs
		$relations = NATICORE_API::get_all_relations( $post_id );
		foreach ( $relations as $rel ) {
			if ( ! empty( $rel->to_id ) ) {
				$exclude[] = (int) $rel->to_id;
			}
		}
		$exclude = array_unique( array_filter( $exclude ) );

		$tax_query = array();
		$terms_cat = get_the_terms( $post_id, 'category' );
		$terms_tag = get_the_terms( $post_id, 'post_tag' );

		if ( $terms_cat && ! is_wp_error( $terms_cat ) ) {
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $terms_cat, 'term_id' ),
			);
		}
		if ( $terms_tag && ! is_wp_error( $terms_tag ) ) {
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'term_id',
				'terms'    => wp_list_pluck( $terms_tag, 'term_id' ),
			);
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'OR';
		}

		$args = array(
			'post_type'      => $post->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'post__not_in'   => $exclude,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$query   = new WP_Query( $args );
		$results = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$thumbnail_url = '';
				$thumbnail_id  = get_post_thumbnail_id( get_the_ID() );
				if ( $thumbnail_id ) {
					$thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
					if ( $thumbnail ) {
						$thumbnail_url = $thumbnail[0];
					}
				}
				$results[] = array(
					'id'        => get_the_ID(),
					'title'     => get_the_title(),
					'type'      => get_post_type(),
					'thumbnail' => $thumbnail_url,
					'url'       => get_the_permalink(),
					'source'    => 'fallback',
				);
			}
			wp_reset_postdata();
		}

		return $results;
	}

	/**
	 * Get AI status information
	 *
	 * @return array Status information.
	 */
	public function get_status() {
		return array(
			'ai_available' => $this->ai_available,
			'enabled'      => $this->is_enabled(),
			'wp_version'   => get_bloginfo( 'version' ),
			'ai_client'    => function_exists( 'wp_ai_client_prompt' ),
		);
	}

	/**
	 * Auto-link related posts when a post is published
	 *
	 * @param int   $post_id Post ID.
	 * @param object $post   Post object.
	 */
	public function auto_link_on_publish( $post_id, $post ) {
		// Skip auto-drafts, revisions, and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if auto-link is enabled
		$settings = get_option( 'naticore_settings', array() );
		if ( empty( $settings['enable_auto_link'] ) ) {
			return;
		}

		// Check if AI is available and enabled
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Check if post has any content
		if ( empty( $post->post_content ) ) {
			return;
		}

		// Get suggested relationships
		$suggestions = $this->get_suggestions( $post_id, 5 );

		if ( empty( $suggestions ) ) {
			return;
		}

		// Create relationships
		$created = 0;
		foreach ( $suggestions as $suggestion ) {
			$to_id = absint( $suggestion['id'] );
			if ( $to_id && $to_id !== $post_id ) {
				$result = NATICORE_API::add_relation( $post_id, $to_id, 'related_to' );
				if ( ! is_wp_error( $result ) ) {
					$created++;
				}
			}
		}

		// Store notice for display
		if ( $created > 0 ) {
			set_transient(
				'naticore_auto_link_notice',
				array(
					'post_id' => $post_id,
					'created' => $created,
					'title'   => $post->post_title,
				),
				60
			);
		}
	}

	/**
	 * Render admin notice for auto-linked relationships
	 */
	public function render_auto_link_notice() {
		$notice = get_transient( 'naticore_auto_link_notice' );
		if ( empty( $notice ) ) {
			return;
		}

		delete_transient( 'naticore_auto_link_notice' );

		$edit_link = get_edit_post_link( $notice['post_id'], 'raw' );
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				printf(
					/* translators: 1: number of relationships, 2: post title, 3: edit link */
					esc_html__( 'AI auto-linked %1$d relationships for "%2$s". %3$sView or edit%4$s.', 'native-content-relationships' ),
					intval( $notice['created'] ),
					esc_html( $notice['title'] ),
					'<a href="' . esc_url( $edit_link ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Check if auto-link is enabled
	 *
	 * @return bool True if auto-link is enabled.
	 */
	public function is_auto_link_enabled() {
		$settings = get_option( 'naticore_settings', array() );
		return ! empty( $settings['enable_auto_link'] ) && $this->is_enabled();
	}
}

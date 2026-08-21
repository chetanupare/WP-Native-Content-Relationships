<?php
/**
 * Classic widget: Related Content
 *
 * @package NativeContentRelationships
 * @since 1.0.25
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget class for displaying related content in sidebars
 */
class NATICORE_Related_Content_Widget extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			'naticore_related_content',
			__( 'Related Content (NCR)', 'native-content-relationships' ),
			array(
				'classname'   => 'widget_naticore_related_content',
				'description' => __( 'Display content related to the current post (or a specific post) in the sidebar.', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Output widget
	 *
	 * @param array $args     Display arguments.
	 * @param array $instance Widget instance settings.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args(
			$instance,
			array(
				'title'         => '',
				'relation_type' => 'related_to',
				'limit'         => 5,
				'order'         => 'date',
				'post_id'       => 0,
			)
		);

		$post_id = absint( $instance['post_id'] );
		if ( 0 === $post_id ) {
			$post_id = get_the_ID();
		}
		if ( 0 === $post_id ) {
			return;
		}

		$shortcodes = NATICORE_Shortcodes::get_instance();
		$atts       = array(
			'type'    => sanitize_key( $instance['relation_type'] ),
			'limit'   => min( 50, max( 1, absint( $instance['limit'] ) ) ),
			'order'   => in_array( $instance['order'], array( 'date', 'title' ), true ) ? $instance['order'] : 'date',
			'post_id' => $post_id,
			'title'   => $instance['title'] ? $instance['title'] : __( 'Related Content', 'native-content-relationships' ),
			'layout'  => 'list',
			'class'   => 'naticore-widget-related-posts',
		);

		$content = $shortcodes->render_related_posts( $atts );
		if ( empty( $content ) ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget title wrapper
			// Remove duplicate heading from shortcode output when widget has its own title.
			$content = preg_replace( '#<h3 class="naticore-related-title">.*?</h3>#s', '', $content );
		}

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is escaped internally

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Widget wrapper
	}

	/**
	 * Output widget form (admin)
	 *
	 * @param array $instance Current instance.
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			$instance,
			array(
				'title'         => __( 'Related Content', 'native-content-relationships' ),
				'relation_type' => 'related_to',
				'limit'         => 5,
				'order'         => 'date',
				'post_id'       => 0,
			)
		);

		$types      = NATICORE_Relation_Types::get_types();
		$post_types = array();
		foreach ( $types as $slug => $type_info ) {
			if ( empty( $type_info['supports_terms'] ) && empty( $type_info['supports_users'] ) ) {
				$post_types[ $slug ] = isset( $type_info['label'] ) ? $type_info['label'] : $slug;
			}
		}
		if ( empty( $post_types ) ) {
			$post_types['related_to'] = __( 'Related To', 'native-content-relationships' );
		}

		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'native-content-relationships' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'relation_type' ) ); ?>"><?php esc_html_e( 'Relationship type:', 'native-content-relationships' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'relation_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'relation_type' ) ); ?>">
				<?php foreach ( $post_types as $slug => $label ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $instance['relation_type'], $slug ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of items:', 'native-content-relationships' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="50" value="<?php echo esc_attr( $instance['limit'] ); ?>" class="tiny-text" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>"><?php esc_html_e( 'Order by:', 'native-content-relationships' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>">
				<option value="date" <?php selected( $instance['order'], 'date' ); ?>><?php esc_html_e( 'Date', 'native-content-relationships' ); ?></option>
				<option value="title" <?php selected( $instance['order'], 'title' ); ?>><?php esc_html_e( 'Title', 'native-content-relationships' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'post_id' ) ); ?>"><?php esc_html_e( 'Post ID (optional):', 'native-content-relationships' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'post_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_id' ) ); ?>" type="number" min="0" value="<?php echo esc_attr( $instance['post_id'] ); ?>" class="tiny-text" />
			<br><span class="description"><?php esc_html_e( 'Leave 0 to use the current post.', 'native-content-relationships' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Sanitize and save widget settings
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$order    = isset( $new_instance['order'] ) ? $new_instance['order'] : 'date';
		$instance = array(
			'title'         => sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' ),
			'relation_type' => sanitize_key( isset( $new_instance['relation_type'] ) ? $new_instance['relation_type'] : 'related_to' ),
			'limit'         => min( 50, max( 1, absint( isset( $new_instance['limit'] ) ? $new_instance['limit'] : 5 ) ) ),
			'order'         => in_array( $order, array( 'date', 'title' ), true ) ? $order : 'date',
			'post_id'       => absint( isset( $new_instance['post_id'] ) ? $new_instance['post_id'] : 0 ),
		);
		return $instance;
	}
}

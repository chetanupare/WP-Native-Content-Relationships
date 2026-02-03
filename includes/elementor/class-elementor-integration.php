<?php
/**
 * Elementor Integration for Native Content Relationships
 *
 * @package Native Content Relationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Elementor_Integration {

	/**
	 * Instance
	 * @var NATICORE_Elementor_Integration|null
	 */
	private static $instance = null;

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
		// Only load if Elementor is active
		if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		$this->init();
	}

	/**
	 * Initialize integration
	 */
	private function init() {
		// Register dynamic tags
		add_action( 'elementor/dynamic_tags/register_tags', array( $this, 'register_dynamic_tags' ) );

		// Register tag groups
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tag_groups' ) );

		// Add custom controls for relationship types
		add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );
	}

	/**
	 * Register dynamic tags
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Dynamic tags manager
	 */
	public function register_dynamic_tags( $dynamic_tags ) {
		// Only register if class exists
		if ( class_exists( 'NATICORE_Related_Posts_Tag' ) ) {
			$dynamic_tags->register( new NATICORE_Related_Posts_Tag() );
		}

		if ( class_exists( 'NATICORE_Related_Users_Tag' ) ) {
			$dynamic_tags->register( new NATICORE_Related_Users_Tag() );
		}

		if ( class_exists( 'NATICORE_Related_Terms_Tag' ) ) {
			$dynamic_tags->register( new NATICORE_Related_Terms_Tag() );
		}
	}

	/**
	 * Register tag groups
	 *
	 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags Dynamic tags manager
	 */
	public function register_tag_groups( $dynamic_tags ) {
		$dynamic_tags->register_group(
			'ncr-relationships',
			array(
				'title' => __( 'Content Relationships', 'native-content-relationships' ),
			)
		);
	}

	/**
	 * Register custom controls
	 *
	 * @param \Elementor\Controls_Manager $controls_manager Controls manager
	 */
	public function register_controls( $controls_manager ) {
		// Register Relationship Type control
		$controls_manager->register_control( 'ncr_relationship_type', new NATICORE_Relationship_Type_Control() );
	}

	/**
	 * Check if Elementor is active
	 *
	 * @return bool True if Elementor is active
	 */
	public static function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Get relationship types for Elementor
	 *
	 * @param string $target_type Target type (post, user, term)
	 * @return array Relationship types options
	 */
	public static function get_relationship_types_for_elementor( $target_type = 'post' ) {
		$types = NATICORE_Relation_Types::get_types();
		$options = array();

		foreach ( $types as $slug => $type_info ) {
			// Check if this type supports the target
			$supports_key = "supports_{$target_type}s";
			if ( isset( $type_info[ $supports_key ] ) && $type_info[ $supports_key ] ) {
				$options[ $slug ] = $type_info['label'];
			}
		}

		return $options;
	}
}

/**
 * Custom Relationship Type Control for Elementor
 */
if ( class_exists( '\Elementor\Base_Control' ) ) {
	class NATICORE_Relationship_Type_Control extends \Elementor\Base_Control {

		/**
		 * Get control type
		 *
		 * @return string Control type
		 */
		public function get_type() {
			return 'ncr_relationship_type';
		}

		/**
		 * Get control default settings
		 *
		 * @return array Control default settings
		 */
		protected function get_default_settings() {
			return array(
				'options' => array(),
				'target_type' => 'post',
			);
		}

		/**
		 * Enqueue control scripts
		 *
		 * @param array $settings Control settings
		 */
		public function enqueue( $settings ) {
			wp_enqueue_script(
				'ncr-elementor-controls',
				plugins_url( 'assets/js/elementor-controls.js', NATICORE_PLUGIN_BASENAME ),
				array( 'jquery' ),
				NATICORE_VERSION,
				true
			);
		}

		/**
		 * Content template
		 *
		 * @return string Content template
		 */
		public function content_template() {
			$control_uid = $this->get_control_uid();
			?>
			<div class="elementor-control-field">
				<label class="elementor-control-title"><?php echo esc_html( $this->get_label() ); ?></label>
				<div class="elementor-control-input-wrapper">
					<select class="elementor-control-tagged-input" data-setting="<?php echo esc_attr( $control_uid ); ?>">
						<?php foreach ( $this->get_settings( 'options' ) as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php
		}

		/**
		 * Get control label
		 *
		 * @return string Control label
		 */
		protected function get_label() {
			return __( 'Relationship Type', 'native-content-relationships' );
		}
	}
}

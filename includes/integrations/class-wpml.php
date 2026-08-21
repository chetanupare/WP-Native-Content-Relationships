<?php
/**
 * WPML / Polylang Integration
 * Multilingual relationship mirroring
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPML / Polylang Integration
 *
 * Provides functionality for managing relationships in multilingual environments,
 * including automatic mirroring of relationships between different language
 * versions of content.
 *
 * @package NativeContentRelationships
 * @since 1.0.10
 */
class NATICORE_WPML {

	/**
	 * Instance
	 *
	 * @var NATICORE_WPML|null
	 */
	private static $instance = null;

	/**
	 * Is WPML/Polylang active
	 *
	 * @var bool
	 */
	private $is_multilingual_active = false;

	/**
	 * Plugin type (wpml or polylang)
	 *
	 * @var string
	 */
	private $plugin_type = '';

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
		// Check if WPML is active
		if ( defined( 'ICL_SITEPRESS_VERSION' ) || class_exists( 'SitePress' ) ) {
			$this->is_multilingual_active = true;
			$this->plugin_type            = 'wpml';
		}
		// Check if Polylang is active
		elseif ( function_exists( 'pll_current_language' ) ) {
			$this->is_multilingual_active = true;
			$this->plugin_type            = 'polylang';
		}

		if ( ! $this->is_multilingual_active ) {
			return; // Exit early if no multilingual plugin is active
		}

		// Initialize multilingual features
		$this->init();
	}

	/**
	 * Initialize multilingual features
	 */
	private function init() {
		// Add settings
		$settings = NATICORE_Settings::get_instance();
		if ( $settings->get_setting( 'multilingual_mirror', 0 ) ) {
			// Mirror relationships across translations
			add_action( 'naticore_relation_added', array( $this, 'mirror_relationship' ), 10, 4 );
			add_action( 'naticore_relation_removed', array( $this, 'unmirror_relationship' ), 10, 3 );
		}

		// Add settings tab
		add_action( 'naticore_settings_tabs', array( $this, 'add_multilingual_settings_tab' ) );
	}

	/**
	 * Check if multilingual is active
	 */
	public function is_active() {
		return $this->is_multilingual_active;
	}

	/**
	 * Get plugin type
	 */
	public function get_plugin_type() {
		return $this->plugin_type;
	}

	/**
	 * Get translation ID (WPML)
	 */
	private function get_translation_id( $post_id, $lang_code ) {
		if ( 'wpml' === $this->plugin_type && function_exists( 'wpml_object_id_filter' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook
			return apply_filters( 'wpml_object_id', $post_id, get_post_type( $post_id ), false, $lang_code );
		}
		return false;
	}

	/**
	 * Get translation ID (Polylang)
	 */
	private function get_translation_id_polylang( $post_id, $lang_code ) {
		if ( 'polylang' === $this->plugin_type && function_exists( 'pll_get_post' ) ) {
			return pll_get_post( $post_id, $lang_code );
		}
		return false;
	}

	/**
	 * Get all translations of a post
	 */
	private function get_all_translations( $post_id ) {
		$translations = array( $post_id );

		if ( 'wpml' === $this->plugin_type ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook
			$trid = apply_filters( 'wpml_element_trid', null, $post_id );
			if ( $trid ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WPML hook
				$all_translations = apply_filters( 'wpml_get_element_translations', array(), $trid );
				foreach ( $all_translations as $lang => $translation ) {
					if ( (int) $translation->element_id !== (int) $post_id ) {
						$translations[] = $translation->element_id;
					}
				}
			}
		} elseif ( 'polylang' === $this->plugin_type ) {
			$all_langs = pll_languages_list();
			foreach ( $all_langs as $lang ) {
				$trans_id = pll_get_post( $post_id, $lang );
				if ( $trans_id && (int) $trans_id !== (int) $post_id ) {
					$translations[] = $trans_id;
				}
			}
		}

		return $translations;
	}

	/**
	 * Mirror relationship across translations
	 */
	public function mirror_relationship( $relation_id, $from_id, $to_id, $type ) {
		$from_translations = $this->get_all_translations( $from_id );
		$to_translations   = $this->get_all_translations( $to_id );

		// Create relationships between all translation pairs
		foreach ( $from_translations as $from_trans_id ) {
			foreach ( $to_translations as $to_trans_id ) {
				if ( (int) $from_trans_id === (int) $from_id && (int) $to_trans_id === (int) $to_id ) {
					continue; // Skip original
				}

				// Check if relation already exists
				if ( ! NATICORE_API::is_related( $from_trans_id, $to_trans_id, $type ) ) {
					NATICORE_API::add_relation( $from_trans_id, $to_trans_id, $type );
				}
			}
		}
	}

	/**
	 * Unmirror relationship across translations
	 */
	public function unmirror_relationship( $from_id, $to_id, $type ) {
		$from_translations = $this->get_all_translations( $from_id );
		$to_translations   = $this->get_all_translations( $to_id );

		// Remove relationships between all translation pairs
		foreach ( $from_translations as $from_trans_id ) {
			foreach ( $to_translations as $to_trans_id ) {
				if ( (int) $from_trans_id === (int) $from_id && (int) $to_trans_id === (int) $to_id ) {
					continue; // Skip original (already removed)
				}

				NATICORE_API::remove_relation( $from_trans_id, $to_trans_id, $type );
			}
		}
	}

	/**
	 * Add multilingual settings tab
	 */
	public function add_multilingual_settings_tab() {
		if ( $this->is_active() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only tab parameter
			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
			?>
			<a href="?page=naticore-settings&tab=multilingual" class="nav-tab <?php echo esc_attr( 'multilingual' === $active_tab ? 'nav-tab-active' : '' ); ?>">
				<?php esc_html_e( 'Multilingual', 'native-content-relationships' ); ?>
			</a>
			<?php
		}
	}

	/**
	 * Render multilingual settings
	 */
	public function render_multilingual_settings() {
		$settings    = NATICORE_Settings::get_instance();
		$option_name = 'naticore_settings';
		$mirror      = $settings->get_setting( 'multilingual_mirror', 0 );
		$plugin_name = 'wpml' === $this->plugin_type ? 'WPML' : 'Polylang';

		?>
		<h2><?php esc_html_e( 'Multilingual Settings', 'native-content-relationships' ); ?></h2>
		<?php
		/* translators: %s: Plugin name (WPML or Polylang) */
		$multilingual_message = sprintf( esc_html__( 'Configure relationship behavior for multilingual sites. Detected: %s', 'native-content-relationships' ), esc_html( $plugin_name ) );
		?>
		<p><?php echo esc_html( $multilingual_message ); ?></p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="multilingual_mirror"><?php esc_html_e( 'Mirror Relationships', 'native-content-relationships' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[multilingual_mirror]" id="multilingual_mirror" value="1" <?php checked( $mirror, 1 ); ?>>
						<?php esc_html_e( 'Mirror relationships across translations', 'native-content-relationships' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'When enabled, if Post A (English) ↔ Post B (English), then Post A (Spanish) ↔ Post B (Spanish) automatically.', 'native-content-relationships' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}
}

<?php
/**
 * WooCommerce Integration
 * Optional, auto-enables when WooCommerce is active
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_WooCommerce {
	
	/**
	 * Instance
	 */
	private static $instance = null;
	
	/**
	 * Is WooCommerce active
	 */
	private $is_wc_active = false;
	
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
		// Check if WooCommerce is active
		$this->is_wc_active = class_exists( 'WooCommerce' );
		
		if ( ! $this->is_wc_active ) {
			return; // Exit early if WooCommerce is not active
		}
		
		// Initialize WooCommerce features
		$this->init();
	}
	
	/**
	 * Initialize WooCommerce features
	 */
	private function init() {
		// Register WooCommerce relationship types
		add_action( 'init', array( $this, 'register_wc_relation_types' ), 20 );
		
		// Add product meta box
		add_action( 'add_meta_boxes', array( $this, 'add_product_meta_box' ), 20 );
		
		// Add WooCommerce settings tab (via action hook)
		add_action( 'wpncr_settings_tabs', array( $this, 'add_wc_settings_tab' ) );
		
		// Sync with WooCommerce upsells/cross-sells (optional)
		$settings = WPNCR_Settings::get_instance();
		if ( $settings->get_setting( 'wc_sync_upsells', 0 ) ) {
			add_action( 'woocommerce_update_product', array( $this, 'sync_upsells' ), 10, 1 );
			add_action( 'woocommerce_update_product', array( $this, 'sync_cross_sells' ), 10, 1 );
		}
		
		// Order relationships
		add_action( 'woocommerce_new_order', array( $this, 'create_order_relationships' ), 10, 1 );
		
		// Query helpers
		add_filter( 'wpncr_query_helpers', array( $this, 'add_query_helpers' ) );
	}
	
	/**
	 * Check if WooCommerce is active
	 */
	public function is_active() {
		return $this->is_wc_active;
	}
	
	/**
	 * Register WooCommerce-specific relationship types
	 */
	public function register_wc_relation_types() {
		// Related Product (bidirectional)
		register_content_relation_type( 'related_product', array(
			'label'            => __( 'Related Product', 'native-content-relationships' ),
			'bidirectional'    => true,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Upsell Of (one-way)
		register_content_relation_type( 'upsell_of', array(
			'label'            => __( 'Upsell Of', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Cross Sell Of (one-way)
		register_content_relation_type( 'cross_sell_of', array(
			'label'            => __( 'Cross Sell Of', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Bundle Contains (one-way)
		register_content_relation_type( 'bundle_contains', array(
			'label'            => __( 'Bundle Contains', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Accessory Of (one-way)
		register_content_relation_type( 'accessory_of', array(
			'label'            => __( 'Accessory Of', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Replacement For (one-way)
		register_content_relation_type( 'replacement_for', array(
			'label'            => __( 'Replacement For', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'product' ),
		) );
		
		// Order Contains Product (one-way)
		register_content_relation_type( 'order_contains_product', array(
			'label'            => __( 'Order Contains Product', 'native-content-relationships' ),
			'bidirectional'    => false,
			'allowed_post_types' => array( 'shop_order', 'product' ),
		) );
	}
	
	/**
	 * Add product meta box
	 */
	public function add_product_meta_box() {
		$settings = WPNCR_Settings::get_instance();
		$enabled = $settings->get_setting( 'wc_enabled_objects', array( 'product' ) );
		
		if ( in_array( 'product', $enabled, true ) ) {
			add_meta_box(
				'wpncr_product_relationships',
				__( 'Product Relationships', 'native-content-relationships' ),
				array( $this, 'render_product_meta_box' ),
				'product',
				'normal',
				'default'
			);
		}
		
		// Variations (optional)
		if ( in_array( 'product_variation', $enabled, true ) ) {
			add_meta_box(
				'wpncr_product_relationships',
				__( 'Product Relationships', 'native-content-relationships' ),
				array( $this, 'render_product_meta_box' ),
				'product_variation',
				'normal',
				'default'
			);
		}
	}
	
	/**
	 * Render product meta box
	 */
	public function render_product_meta_box( $post ) {
		// Use the same admin UI, but filter to show only WooCommerce relation types
		$wc_types = array(
			'related_product',
			'upsell_of',
			'cross_sell_of',
			'bundle_contains',
			'accessory_of',
			'replacement_for',
		);
		
		wp_nonce_field( 'wpncr_save_relationships', 'wpncr_nonce' );
		
		// Get existing relationships
		$relationships = WPNCR_API::get_all_relations( $post->ID );
		
		// Group by relation type (only WC types)
		$grouped = array();
		foreach ( $relationships as $rel ) {
			if ( in_array( $rel->type, $wc_types, true ) ) {
				if ( ! isset( $grouped[ $rel->type ] ) ) {
					$grouped[ $rel->type ] = array();
				}
				$grouped[ $rel->type ][] = $rel;
			}
		}
		
		// Get registered relation types
		$relation_types = WPNCR_Relation_Types::get_types();
		$wc_relation_types = array_intersect_key( $relation_types, array_flip( $wc_types ) );
		
		?>
		<div id="wpncr-relationships">
			<p class="description">
				<?php esc_html_e( 'Manage product relationships. These extend, not replace, WooCommerce linked products.', 'native-content-relationships' ); ?>
			</p>
			<div class="wpncr-relation-types">
				<?php foreach ( $wc_relation_types as $type => $type_info ) : 
					$type_label = isset( $type_info['label'] ) ? $type_info['label'] : ucwords( str_replace( '_', ' ', $type ) );
				?>
					<div class="wpncr-relation-type" data-type="<?php echo esc_attr( $type ); ?>">
						<h4><?php echo esc_html( $type_label ); ?></h4>
						<div class="wpncr-relations-list" data-relation-type="<?php echo esc_attr( $type ); ?>">
							<?php if ( isset( $grouped[ $type ] ) ) : ?>
								<?php foreach ( $grouped[ $type ] as $rel ) : 
									$related_post = get_post( $rel->to_id );
									if ( ! $related_post ) continue;
									
									$rel_type_info = WPNCR_Relation_Types::get_type( $type );
									$is_bidirectional = $rel_type_info && $rel_type_info['bidirectional'];
								?>
									<div class="wpncr-relation-item" data-related-id="<?php echo esc_attr( $rel->to_id ); ?>">
										<span class="wpncr-relation-title">
											<span class="wpncr-direction-indicator" title="<?php echo esc_attr( $is_bidirectional ? __( 'Bidirectional', 'native-content-relationships' ) : __( 'One-way', 'native-content-relationships' ) ); ?>">
												<?php echo esc_html( $is_bidirectional ? '↔' : '→' ); ?>
											</span>
											<a href="<?php echo esc_url( get_edit_post_link( $rel->to_id ) ); ?>" target="_blank">
												<?php echo esc_html( get_the_title( $rel->to_id ) ); ?>
											</a>
											<?php if ( $related_post->post_type === 'product' ) : 
												$product = wc_get_product( $rel->to_id );
												if ( $product ) :
											?>
												<small>(<?php echo esc_html( $product->get_sku() ? $product->get_sku() : esc_html__( 'No SKU', 'native-content-relationships' ) ); ?>)</small>
											<?php endif; endif; ?>
										</span>
										<button type="button" class="button wpncr-remove-relation" data-from-id="<?php echo esc_attr( $post->ID ); ?>" data-to-id="<?php echo esc_attr( $rel->to_id ); ?>" data-relation-type="<?php echo esc_attr( $type ); ?>">
											<?php esc_html_e( 'Remove', 'native-content-relationships' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div class="wpncr-add-relation">
							<input type="text" class="wpncr-search-input wpncr-product-search" placeholder="<?php esc_attr_e( 'Search products by name or SKU...', 'native-content-relationships' ); ?>" data-relation-type="<?php echo esc_attr( $type ); ?>" />
							<div class="wpncr-search-results" style="display: none;"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Add WooCommerce settings tab
	 */
	public function add_wc_settings_tab() {
		if ( $this->is_active() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only tab parameter
			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
			?>
			<a href="?page=wpncr-settings&tab=woocommerce" class="nav-tab <?php echo esc_attr( $active_tab === 'woocommerce' ? 'nav-tab-active' : '' ); ?>">
				<?php esc_html_e( 'WooCommerce', 'native-content-relationships' ); ?>
			</a>
			<?php
		}
	}
	
	/**
	 * Render WooCommerce settings
	 */
	public function render_wc_settings() {
		$settings = WPNCR_Settings::get_instance();
		$option_name = $this->option_name;
		$enabled_objects = $settings->get_setting( 'wc_enabled_objects', array( 'product' ) );
		$sync_upsells = $settings->get_setting( 'wc_sync_upsells', 0 );
		
		?>
		<h2><?php esc_html_e( 'WooCommerce Settings', 'native-content-relationships' ); ?></h2>
		<p><?php esc_html_e( 'Configure WooCommerce-specific relationship features.', 'native-content-relationships' ); ?></p>
		
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="wc_enabled_objects"><?php esc_html_e( 'Enable Relationships For', 'native-content-relationships' ); ?></label>
				</th>
				<td>
					<fieldset>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[wc_enabled_objects][]" value="product" <?php checked( in_array( 'product', $enabled_objects, true ) ); ?>>
							<?php esc_html_e( 'Products', 'native-content-relationships' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[wc_enabled_objects][]" value="product_variation" <?php checked( in_array( 'product_variation', $enabled_objects, true ) ); ?>>
							<?php esc_html_e( 'Product Variations', 'native-content-relationships' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[wc_enabled_objects][]" value="shop_order" <?php checked( in_array( 'shop_order', $enabled_objects, true ) ); ?>>
							<?php esc_html_e( 'Orders', 'native-content-relationships' ); ?>
						</label><br>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[wc_enabled_objects][]" value="shop_coupon" <?php checked( in_array( 'shop_coupon', $enabled_objects, true ) ); ?>>
							<?php esc_html_e( 'Coupons', 'native-content-relationships' ); ?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wc_sync_upsells"><?php esc_html_e( 'Sync with WooCommerce', 'native-content-relationships' ); ?></label>
				</th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[wc_sync_upsells]" value="1" <?php checked( $sync_upsells, 1 ); ?>>
						<?php esc_html_e( 'Sync with WooCommerce Upsells & Cross-Sells', 'native-content-relationships' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'If enabled, WooCommerce upsells ↔ upsell_of and cross-sells ↔ cross_sell_of. Changes reflect both ways with no data loss.', 'native-content-relationships' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}
	
	/**
	 * Sync WooCommerce upsells
	 */
	public function sync_upsells( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}
		
		$upsell_ids = $product->get_upsell_ids();
		
		// Remove existing upsell_of relations
		$existing = WPNCR_API::get_related( $product_id, 'upsell_of' );
		foreach ( $existing as $rel ) {
			WPNCR_API::remove_relation( $product_id, $rel['id'], 'upsell_of' );
		}
		
		// Add new relations
		foreach ( $upsell_ids as $upsell_id ) {
			WPNCR_API::add_relation( $product_id, $upsell_id, 'upsell_of' );
		}
	}
	
	/**
	 * Sync WooCommerce cross-sells
	 */
	public function sync_cross_sells( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}
		
		$cross_sell_ids = $product->get_cross_sell_ids();
		
		// Remove existing cross_sell_of relations
		$existing = WPNCR_API::get_related( $product_id, 'cross_sell_of' );
		foreach ( $existing as $rel ) {
			WPNCR_API::remove_relation( $product_id, $rel['id'], 'cross_sell_of' );
		}
		
		// Add new relations
		foreach ( $cross_sell_ids as $cross_sell_id ) {
			WPNCR_API::add_relation( $product_id, $cross_sell_id, 'cross_sell_of' );
		}
	}
	
	/**
	 * Create order relationships
	 */
	public function create_order_relationships( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			if ( $product_id ) {
				WPNCR_API::add_relation( $order_id, $product_id, 'order_contains_product' );
			}
		}
	}
	
	/**
	 * Add query helpers
	 */
	public function add_query_helpers( $helpers ) {
		$helpers['wp_get_related_products'] = array(
			'callback' => array( $this, 'get_related_products' ),
			'description' => __( 'Get related products for a product ID', 'native-content-relationships' ),
		);
		return $helpers;
	}
	
	/**
	 * Get related products helper
	 */
	public function get_related_products( $product_id, $type = 'related_product', $args = array() ) {
		$related = wp_get_related( $product_id, $type, $args );
		
		$products = array();
		foreach ( $related as $rel ) {
			$product = wc_get_product( $rel['id'] );
			if ( $product ) {
				$products[] = $product;
			}
		}
		
		return $products;
	}
}

// Make helper function available
if ( ! function_exists( 'wp_get_related_products' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Intentional API function name
	function wp_get_related_products( $product_id, $type = 'related_product', $args = array() ) {
		if ( ! class_exists( 'WPNCR_WooCommerce' ) ) {
			return new WP_Error( 'class_not_loaded', 'WPNCR_WooCommerce class is not loaded yet.' );
		}
		$wc = WPNCR_WooCommerce::get_instance();
		if ( ! $wc->is_active() ) {
			return array();
		}
		return $wc->get_related_products( $product_id, $type, $args );
	}
}

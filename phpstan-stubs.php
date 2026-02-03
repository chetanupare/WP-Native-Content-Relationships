<?php
// Minimal stubs for PHPStan in this plugin context.

namespace WP_CLI\Utils {
	if ( ! function_exists( __NAMESPACE__ . '\\format_items' ) ) {
		function format_items( $format, $items, $fields ) {}
	}
}

namespace {
	// Exit if accessed directly
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! defined( 'NATICORE_PLUGIN_BASENAME' ) ) {
		define( 'NATICORE_PLUGIN_BASENAME', 'native-content-relationships/native-content-relationships.php' );
	}

	if ( ! defined( 'NATICORE_PLUGIN_URL' ) ) {
		define( 'NATICORE_PLUGIN_URL', '' );
	}

	if ( ! defined( 'NATICORE_PLUGIN_DIR' ) ) {
		define( 'NATICORE_PLUGIN_DIR', '' );
	}

	if ( ! defined( 'NATICORE_VERSION' ) ) {
		define( 'NATICORE_VERSION', '0.0.0' );
	}

	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}

	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}

	if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
		define( 'WEEK_IN_SECONDS', 604800 );
	}

	if ( ! class_exists( 'WP_CLI' ) ) {
		class WP_CLI {
			public static function add_command( $name, $callable ) {}
			public static function line( $message = '' ) {}
			public static function success( $message = '' ) {}
			public static function warning( $message = '' ) {}
			public static function error( $message = '' ) {}
		}
	}

if ( ! function_exists( 'wc_get_product' ) ) {
	function wc_get_product( $product_id ) {
		return null;
	}
}

if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $order_id ) {
		return null;
	}
}

if ( ! function_exists( 'pll_languages_list' ) ) {
	function pll_languages_list() {
		return array();
	}
}

if ( ! function_exists( 'pll_get_post' ) ) {
	function pll_get_post( $post_id, $lang = null ) {
		return $post_id;
	}
}

}

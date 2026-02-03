<?php
/**
 * Elementor Control Templates
 *
 * @package Native Content Relationships
 * @since 1.0.11
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Localize script for Elementor controls
function ncr_localize_elementor_scripts() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}

	wp_localize_script( 'ncr-elementor-controls', 'ncr_vars', array(
		'nonce' => wp_create_nonce( 'ncr_elementor_nonce' ),
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	) );
}
add_action( 'elementor/editor/before_enqueue_scripts', 'ncr_localize_elementor_scripts' );

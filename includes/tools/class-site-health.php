<?php
/**
 * Site Health Integration
 *
 * @package NativeContentRelationships
 * @since 1.0.20
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Site_Health {

	/**
	 * Instance
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
		add_filter( 'site_status_tests', array( $this, 'add_integrity_test' ) );
	}

	/**
	 * Add integrity test to Site Health
	 */
	public function add_integrity_test( $tests ) {
		$tests['direct']['ncr_integrity'] = array(
			'label' => __( 'Relationship Integrity', 'native-content-relationships' ),
			'test'  => array( $this, 'run_integrity_check_test' ),
		);
		return $tests;
	}

	/**
	 * Run the integrity check test
	 */
	public function run_integrity_check_test() {
		$result = array(
			'label'       => __( 'Content Relationship Integrity', 'native-content-relationships' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'Relationships', 'native-content-relationships' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'The relationship graph is being monitored for integrity.', 'native-content-relationships' )
			),
			'actions'     => sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'tools.php?page=naticore_integrity' ) ), // Assuming this page exists or will exist
				__( 'Run Manual Integrity Check', 'native-content-relationships' )
			),
			'test'        => 'ncr_integrity',
		);

		$integrity = NATICORE_Integrity::get_instance();
		// Run a quick audit (dry run, small batch)
		$audit = $integrity->run_integrity_check( false, 100 );

		if ( $audit['cleaned'] > 0 ) {
			$result['status']      = 'recommended';
			$result['description'] = sprintf(
				'<p>%s</p>',
				sprintf(
					/* translators: %d: count of issues */
					__( 'Found %d potential integrity issues in your relationships. It is recommended to run a fix.', 'native-content-relationships' ),
					$audit['cleaned']
				)
			);
		}

		return $result;
	}
}

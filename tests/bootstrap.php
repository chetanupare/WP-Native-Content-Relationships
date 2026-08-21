<?php
/**
 * PHPUnit bootstrap for NCR P0 regression tests.
 *
 * Usage:
 *   wp phpunit --testsuite=NCR -- tests/test-p0-regressions.php
 *
 * Or run standalone with WP test suite:
 *   phpunit --bootstrap tests/bootstrap.php tests/test-p0-regressions.php
 */

// Load the plugin.
define( 'NCR_TEST_MODE', true );
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

// Check for WP test suite bootstrap.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

// When running under WP test suite, WP_UnitTestCase is already available.
// For standalone verification, provide a minimal stub.
if ( ! class_exists( 'WP_UnitTestCase' ) ) {
	echo "ERROR: WP test suite not found. Run with: wp phpunit --testsuite=NCR\n";
	exit( 1 );
}

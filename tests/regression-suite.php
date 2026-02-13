<?php
/**
 * Automated Regression Suite
 *
 * Validates core logic: constraints, directionality, integrity, and upgrades.
 * Run with: wp eval-file tests/regression-suite.php
 */

define( 'NCR_TEST_MODE', true );

echo '--- NATICORE Regression Test Suite ---' . PHP_EOL;

function test_expect( $label, $condition ) {
	if ( $condition ) {
		echo "PASS: $label" . PHP_EOL;
	} else {
		echo "FAIL: $label" . PHP_EOL;
		exit( 1 );
	}
}

global $wpdb;
$table = $wpdb->prefix . 'content_relations';
$wpdb->query( "TRUNCATE TABLE `{$table}`" );

// Initialize APIs
NATICORE_Relation_Types::init();
NATICORE_API::get_instance();

// Unlock registry for testing using Reflection
try {
	$reflection  = new ReflectionClass( 'NATICORE_Relation_Types' );
	$locked_prop = $reflection->getProperty( 'locked' );
	$locked_prop->setAccessible( true );
	$locked_prop->setValue( null, false );
} catch ( Exception $e ) {
	// Silent.
}
// 1. Constraint Testing: max_connections
echo 'Testing Constraints...' . PHP_EOL;
$reg_res = ncr_register_relation_type(
	array(
		'name'            => 'test_limit_5',
		'max_connections' => 5,
	)
);
if ( is_wp_error( $reg_res ) ) {
	echo 'ERROR: Failed to register test_limit_5: ' . $reg_res->get_error_message() . PHP_EOL;
}

// Verify it's in the registry
$types = ncr_get_registered_relation_types();
if ( ! isset( $types['test_limit_5'] ) ) {
	echo 'ERROR: test_limit_5 NOT found in registry.' . PHP_EOL;
	print_r( array_keys( $types ) );
}

for ( $i = 1; $i <= 5; $i++ ) {
	$res = ncr_add_relation( 1, 10 + $i, 'test_limit_5' );
	if ( is_wp_error( $res ) ) {
		echo 'ERROR in setup: ' . $res->get_error_message() . PHP_EOL;
	}
}
$err = ncr_add_relation( 1, 16, 'test_limit_5' );
if ( ! is_wp_error( $err ) ) {
	echo 'ERROR: 6th relation was allowed but should have been blocked.' . PHP_EOL;
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE from_id = 1 AND type = 'test_limit_5'" ) );
	echo "Actual count in DB: $count" . PHP_EOL;
}
test_expect( 'max_connections (5) enforcement', is_wp_error( $err ) && $err->get_error_code() === 'ncr_max_connections_exceeded' );

// 2. Directional Testing
echo 'Testing Directionality...' . PHP_EOL;
ncr_register_relation_type(
	array(
		'name'          => 'test_dir_uni',
		'bidirectional' => false,
	)
);
ncr_register_relation_type(
	array(
		'name'          => 'test_dir_bi',
		'bidirectional' => true,
	)
);

ncr_add_relation( 100, 200, 'test_dir_uni' );
$count_uni        = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE from_id = 100 AND to_id = 200 AND type = 'test_dir_uni'" );
$count_invert_uni = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE from_id = 200 AND to_id = 100 AND type = 'test_dir_uni'" );
test_expect( 'Unidirectional creates 1 record', (int) $count_uni === 1 && (int) $count_invert_uni === 0 );

ncr_add_relation( 300, 400, 'test_dir_bi' );
$count_bi        = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE from_id = 300 AND to_id = 400 AND type = 'test_dir_bi'" );
$count_invert_bi = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE from_id = 400 AND to_id = 300 AND type = 'test_dir_bi'" );
test_expect( 'Bidirectional creates 2 records', (int) $count_bi === 1 && (int) $count_invert_bi === 1 );

// 3. Integrity Repair
echo 'Testing Integrity Repair...' . PHP_EOL;
// Inject orphan
$wpdb->insert(
	$table,
	array(
		'from_id' => 999999,
		'to_id'   => 1,
		'type'    => 'related_to',
		'to_type' => 'post',
	)
);
$orphan_id = $wpdb->insert_id;

$integrity = NATICORE_Integrity::get_instance();
$results   = $integrity->run_integrity_check( true );
test_expect( 'Repair removes orphans', (int) $results['cleaned'] > 0 );

$still_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE id = %d", $orphan_id ) );
test_expect( 'Orphan record is gone', (int) $still_exists === 0 );

// 4. Schema Verification
echo 'Testing Schema Guard...' . PHP_EOL;
test_expect( 'Schema option is 1.1', get_option( 'ncr_schema_version' ) === '1.1' );
$indices      = $wpdb->get_results( "SHOW INDEX FROM `{$table}`" );
$found_lookup = false;
foreach ( $indices as $index ) {
	if ( 'type_lookup' === $index->Key_name ) {
		$found_lookup = true;
	}
}
test_expect( 'type_lookup index is present', $found_lookup );

echo '--- ALL REGRESSION TESTS PASSED ---' . PHP_EOL;

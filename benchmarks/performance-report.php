<?php
/**
 * Performance Benchmarking Suite
 *
 * This script measures query latency and memory usage at scale.
 * Run with: wp eval-file benchmarks/performance-report.php
 */

global $wpdb;
$table = $wpdb->prefix . 'content_relations';

function run_benchmark( $count ) {
	global $wpdb;
	$table = $wpdb->prefix . 'content_relations';
	
	echo "--- Benchmarking at " . number_format($count) . " rows ---" . PHP_EOL;
	
	// 1. Query Latency: Point Lookup
	$start = microtime(true);
	$wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE from_id = %d AND type = %s", rand(1, $count / 10), 'related_to' ) );
	$latency_point = microtime(true) - $start;
	echo "Point Lookup (Indexed): " . number_format($latency_point * 1000, 2) . "ms" . PHP_EOL;

	// 2. Query Latency: Covering Index Lookup (Optimized in 1.0.20)
	$start = microtime(true);
	$wpdb->get_results( $wpdb->prepare( "SELECT from_id, to_id FROM `{$table}` WHERE type = %s AND from_id > %d LIMIT 100", 'related_to', rand(1, $count / 2) ) );
	$latency_covering = microtime(true) - $start;
	echo "Covering Index Lookup: " . number_format($latency_covering * 1000, 2) . "ms" . PHP_EOL;

	// 3. Integrity Scan: Memory & Speed (Batch of 1000)
	$integrity = NATICORE_Integrity::get_instance();
	$mem_start = memory_get_usage();
	$start = microtime(true);
	
	// Just run 5 batches to estimate speed/memory per batch
	$audit = $integrity->run_integrity_check( false, 1000 );
	
	$scan_time = microtime(true) - $start;
	$mem_peak = memory_get_peak_usage() - $mem_start;
	
	echo "Integrity Scan (Full): " . number_format($scan_time, 2) . "s" . PHP_EOL;
	echo "Peak Memory Delta: " . number_format($mem_peak / 1024 / 1024, 2) . "MB" . PHP_EOL;
	echo PHP_EOL;
	
	return array(
		'point' => $latency_point,
		'covering' => $latency_covering,
		'scan' => $scan_time,
		'mem' => $mem_peak
	);
}

function inject_data( $target_count, $current_count = 0 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'content_relations';
	$to_add = $target_count - $current_count;
	
	if ( $to_add <= 0 ) return;
	
	echo "Injecting " . number_format($to_add) . " rows... (this may take a minute)" . PHP_EOL;
	
	$batch_size = 5000;
	for ( $i = 0; $i < $to_add; $i += $batch_size ) {
		$values = array();
		for ( $j = 0; $j < min($batch_size, $to_add - $i); $j++ ) {
			$from = rand(1, 50000);
			$to = rand(1, 50000);
			$values[] = "($from, $to, 'related_to', 'bidirectional', 'post')";
		}
		$wpdb->query( "INSERT INTO `{$table}` (from_id, to_id, type, direction, to_type) VALUES " . implode(',', $values) );
	}
}

// Main Execution
echo "Starting Performance Audit..." . PHP_EOL;

// Clean up
$wpdb->query( "TRUNCATE TABLE `{$table}`" );

// 100k rows
inject_data( 100000 );
$bench_100k = run_benchmark( 100000 );

// 1M rows
inject_data( 1000000, 100000 );
$bench_1m = run_benchmark( 1000000 );

echo "Audit Complete." . PHP_EOL;

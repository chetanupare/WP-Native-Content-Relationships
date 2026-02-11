<?php
/**
 * Advanced Performance Benchmarking Suite
 *
 * This script measures query latency (Mean/P95) and memory usage at scale.
 * Methodology: Warm-up phase, multi-iteration trials, and deterministic datasets.
 *
 * Run with: wp eval-file benchmarks/performance-report.php
 */

global $wpdb;
$table = $wpdb->prefix . 'content_relations';

function calculate_percentile($samples, $percentile) {
	sort($samples);
	$index = ($percentile / 100) * count($samples);
	if (floor($index) == $index) {
		$result = ($samples[$index - 1] + $samples[$index]) / 2;
	} else {
		$result = $samples[floor($index)];
	}
	return $result;
}

function run_benchmark( $count, $iterations = 50 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'content_relations';
	
	echo "--- Benchmarking at " . number_format($count) . " rows ($iterations iterations) ---" . PHP_EOL;
	
	// WARM UP PHASE
	// Run 10 priming queries to warm the MySQL buffer pool
	for ($i = 0; $i < 10; $i++) {
		$wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE from_id = %d AND type = %s", rand(1, $count / 10), 'related_to' ) );
	}

	// 1. Point Lookup (Indexed)
	$samples_point = [];
	for ($i = 0; $i < $iterations; $i++) {
		$start = microtime(true);
		$wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE from_id = %d AND type = %s", rand(1, $count / 10), 'related_to' ) );
		$samples_point[] = (microtime(true) - $start) * 1000;
	}

	// 2. Covering Index Lookup
	$samples_covering = [];
	for ($i = 0; $i < $iterations; $i++) {
		$start = microtime(true);
		$wpdb->get_results( $wpdb->prepare( "SELECT from_id, to_id FROM `{$table}` WHERE type = %s AND from_id > %d LIMIT 100", 'related_to', rand(1, $count / 2) ) );
		$samples_covering[] = (microtime(true) - $start) * 1000;
	}

	// 3. Integrity Scan (Single pass for memory delta)
	$integrity = NATICORE_Integrity::get_instance();
	$mem_start = memory_get_usage();
	$start_scan = microtime(true);
	
	// Just 5 batches to get a representative scan slice
	$integrity->run_integrity_check( false, 1000 );
	
	$scan_time = microtime(true) - $start_scan;
	$mem_peak = memory_get_peak_usage() - $mem_start;

	echo "Point Lookup:     Mean: " . number_format(array_sum($samples_point) / $iterations, 2) . "ms | P95: " . number_format(calculate_percentile($samples_point, 95), 2) . "ms" . PHP_EOL;
	echo "Covering Lookup:  Mean: " . number_format(array_sum($samples_covering) / $iterations, 2) . "ms | P95: " . number_format(calculate_percentile($samples_covering, 95), 2) . "ms" . PHP_EOL;
	echo "Scan Rate (1k):   " . number_format($scan_time, 3) . "s" . PHP_EOL;
	echo "Peak Mem Delta:   " . number_format($mem_peak / 1024 / 1024, 2) . "MB" . PHP_EOL;
	echo PHP_EOL;
}

function inject_data( $target_count, $current_count = 0 ) {
	global $wpdb;
	$table = $wpdb->prefix . 'content_relations';
	$to_add = $target_count - $current_count;
	
	if ( $to_add <= 0 ) return;
	
	echo "Injecting " . number_format($to_add) . " rows..." . PHP_EOL;
	
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
echo "Starting Infrastructure Performance Audit..." . PHP_EOL;
$wpdb->query( "TRUNCATE TABLE `{$table}`" );

// 100k
inject_data( 100000 );
run_benchmark( 100000 );

// 1M
inject_data( 1000000, 100000 );
run_benchmark( 1000000 );

echo "Audit Complete." . PHP_EOL;

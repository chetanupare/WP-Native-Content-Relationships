<?php
/**
 * WP-CLI Support
 * Developer-first command interface
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if WP-CLI is available
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class NATICORE_WP_CLI {

	/**
	 * List relationships
	 *
	 * ## OPTIONS
	 *
	 * [--post=<post_id>]
	 * : Filter by post ID
	 *
	 * [--type=<type>]
	 * : Filter by relationship type
	 *
	 * [--format=<format>]
	 * : Output format (table, json, csv)
	 * ---
	 * default: table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations list
	 *     wp content-relations list --post=123
	 *     wp content-relations list --type=references --format=json
	 *
	 * @when after_wp_load
	 */
	public function list( $args, $assoc_args ) {
		global $wpdb;

		$post_id = isset( $assoc_args['post'] ) ? absint( $assoc_args['post'] ) : null;
		$type    = isset( $assoc_args['type'] ) ? sanitize_text_field( $assoc_args['type'] ) : null;
		$format  = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

		// Use conditional queries for PHPCS compliance - ORDER BY and LIMIT are deterministic
		$has_post = ! empty( $post_id );
		$has_type = ! empty( $type );

		if ( $has_post && $has_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WP-CLI command
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}content_relations` WHERE (from_id = %d OR to_id = %d) AND type = %s ORDER BY created_at DESC LIMIT 100",
					$post_id,
					$post_id,
					$type
				)
			);
		} elseif ( $has_post ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WP-CLI command
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}content_relations` WHERE (from_id = %d OR to_id = %d) ORDER BY created_at DESC LIMIT 100",
					$post_id,
					$post_id
				)
			);
		} elseif ( $has_type ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WP-CLI command
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}content_relations` WHERE type = %s ORDER BY created_at DESC LIMIT 100",
					$type
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- WP-CLI command
			$results = $wpdb->get_results(
				"SELECT * FROM `{$wpdb->prefix}content_relations` ORDER BY created_at DESC LIMIT 100"
			);
		}

		if ( empty( $results ) ) {
			WP_CLI::success( 'No relationships found.' );
			return;
		}

		$items = array();
		foreach ( $results as $rel ) {
			$from_post = get_post( $rel->from_id );
			$to_post   = get_post( $rel->to_id );

			$items[] = array(
				'ID'        => $rel->id,
				'From'      => $from_post ? get_the_title( $rel->from_id ) . " ({$rel->from_id})" : "Deleted ({$rel->from_id})",
				'Type'      => $rel->type,
				'To'        => $to_post ? get_the_title( $rel->to_id ) . " ({$rel->to_id})" : "Deleted ({$rel->to_id})",
				'Direction' => $rel->direction,
				'Date'      => $rel->created_at,
			);
		}

		WP_CLI\Utils\format_items( $format, $items, array( 'ID', 'From', 'Type', 'To', 'Direction', 'Date' ) );
	}

	/**
	 * Add a relationship
	 *
	 * ## OPTIONS
	 *
	 * <from_id>
	 * : Source post ID
	 *
	 * <to_id>
	 * : Target post ID
	 *
	 * <type>
	 * : Relationship type
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations add 123 456 references
	 *
	 * @when after_wp_load
	 */
	public function add( $args, $assoc_args ) {
		if ( count( $args ) < 3 ) {
			WP_CLI::error( 'Usage: wp content-relations add <from_id> <to_id> <type>' );
			return;
		}

		$from_id = absint( $args[0] );
		$to_id   = absint( $args[1] );
		$type    = sanitize_text_field( $args[2] );

		$result = NATICORE_API::add_relation( $from_id, $to_id, $type );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		} else {
			WP_CLI::success( "Relationship added (ID: {$result})" );
		}
	}

	/**
	 * Remove a relationship
	 *
	 * ## OPTIONS
	 *
	 * <from_id>
	 * : Source post ID
	 *
	 * <to_id>
	 * : Target post ID
	 *
	 * [--type=<type>]
	 * : Relationship type (optional)
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations remove 123 456
	 *     wp content-relations remove 123 456 --type=references
	 *
	 * @when after_wp_load
	 */
	public function remove( $args, $assoc_args ) {
		if ( count( $args ) < 2 ) {
			WP_CLI::error( 'Usage: wp content-relations remove <from_id> <to_id> [--type=<type>]' );
			return;
		}

		$from_id = absint( $args[0] );
		$to_id   = absint( $args[1] );
		$type    = isset( $assoc_args['type'] ) ? sanitize_text_field( $assoc_args['type'] ) : null;

		$result = NATICORE_API::remove_relation( $from_id, $to_id, $type );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		} else {
			WP_CLI::success( 'Relationship removed' );
		}
	}

	/**
	 * Run integrity check
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations check
	 *
	 * @when after_wp_load
	 */
	public function check( $args, $assoc_args ) {
		$fix        = isset( $assoc_args['fix'] );
		$verbose    = isset( $assoc_args['verbose'] );
		$batch_size = isset( $assoc_args['batch-size'] ) ? absint( $assoc_args['batch-size'] ) : 1000;
		
		$integrity = NATICORE_Integrity::get_instance();
		
		$callback = function( $type, $ids ) use ( $verbose ) {
			if ( $verbose ) {
				foreach ( $ids as $id ) {
					WP_CLI::line( sprintf( '[%s] Found issue with ID: %d', strtoupper( $type ), $id ) );
				}
			}
		};

		if ( $fix ) {
			WP_CLI::line( sprintf( 'Starting integrity cleanup (Batch size: %d)...', $batch_size ) );
		} else {
			WP_CLI::line( sprintf( 'Starting integrity check (Batch size: %d, use --fix to clean up)...', $batch_size ) );
		}

		$results = $integrity->run_integrity_check( $fix, $batch_size, $callback );

		if ( $results['cleaned'] > 0 ) {
			if ( $fix ) {
				WP_CLI::success( sprintf( 'Cleaned up %d invalid relationships.', $results['cleaned'] ) );
			} else {
				WP_CLI::warning( sprintf( 'Found %d invalid relationships.', $results['cleaned'] ) );
			}

			// Show summary table if not already verbose stream
			if ( ! $verbose ) {
				$items = array();
				foreach ( $results['issues'] as $type => $count ) {
					if ( $count > 0 ) {
						$items[] = array(
							'Issue' => $type,
							'Count' => $count,
						);
					}
				}
				WP_CLI\Utils\format_items( 'table', $items, array( 'Issue', 'Count' ) );
			}
		} else {
			WP_CLI::success( 'All relationships are valid.' );
		}
	}

	/**
	 * Sync relationships (preview or execute)
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without executing
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations sync --dry-run
	 *     wp content-relations sync
	 *
	 * @when after_wp_load
	 */
	public function sync( $args, $assoc_args ) {
		$dry_run    = isset( $assoc_args['dry-run'] );
		$batch_size = isset( $assoc_args['batch-size'] ) ? absint( $assoc_args['batch-size'] ) : 1000;

		if ( $dry_run ) {
			WP_CLI::line( sprintf( 'Dry run mode - auditing relationships (Batch size: %d)', $batch_size ) );
		}

		// Run integrity check
		$integrity = NATICORE_Integrity::get_instance();
		$results   = $integrity->run_integrity_check( ! $dry_run, $batch_size );

		if ( $dry_run ) {
			if ( $results['cleaned'] > 0 ) {
				WP_CLI::line( sprintf( 'Would clean up %d invalid relationships.', $results['cleaned'] ) );
			} else {
				WP_CLI::success( 'All relationships are valid.' );
			}
		} elseif ( $results['cleaned'] > 0 ) {
				WP_CLI::success( sprintf( 'Cleaned up %d invalid relationships.', $results['cleaned'] ) );
		} else {
			WP_CLI::success( 'All relationships are valid.' );
		}
	}

	/**
	 * Export relationship schema
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (json, yaml)
	 * ---
	 * default: json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp content-relations schema
	 *     wp content-relations schema --format=json
	 *
	 * @when after_wp_load
	 */
	public function schema( $args, $assoc_args ) {
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';

		$types  = NATICORE_Relation_Types::get_types();
		$schema = array();

		foreach ( $types as $slug => $type_info ) {
			$schema[ $slug ] = array(
				'label'              => $type_info['label'],
				'direction'          => $type_info['bidirectional'] ? 'bidirectional' : 'one-way',
				'allowed_post_types' => empty( $type_info['allowed_post_types'] ) ? 'all' : $type_info['allowed_post_types'],
			);
		}

		if ( $format === 'json' ) {
			WP_CLI::line( json_encode( $schema, JSON_PRETTY_PRINT ) );
		} else {
			WP_CLI::error( 'Only JSON format is currently supported.' );
		}
	}
}

// Register WP-CLI commands
WP_CLI::add_command( 'content-relations', 'NATICORE_WP_CLI' );
WP_CLI::add_command( 'wpcr', 'NATICORE_WP_CLI' ); // Shorter alias

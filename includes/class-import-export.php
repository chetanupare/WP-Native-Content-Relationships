<?php
/**
 * Import / Export Functionality
 * JSON-based, MVP-safe
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NATICORE_Import_Export {

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
		// Only load admin functionality in admin context
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );
	}


	public function render_import_export_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="naticore-import-export-wrap">
			<div class="naticore-card">
				<h3><?php esc_html_e( 'Export Relationships', 'native-content-relationships' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Download all existing content relationships as a JSON file. This is recommended before performing any bulk imports or migrations.', 'native-content-relationships' ); ?></p>
				
				<form method="post" action="">
					<?php wp_nonce_field( 'naticore_export', 'naticore_export_nonce' ); ?>
					<input type="hidden" name="action" value="naticore_export" />
					<div class="naticore-actions-bar" style="justify-content: flex-start; margin-top: 15px;">
						<?php submit_button( esc_html__( 'Download Export File', 'native-content-relationships' ), 'primary', 'submit', false ); ?>
					</div>
				</form>
			</div>
			
			<div class="naticore-card">
				<h3><?php esc_html_e( 'Import Relationships', 'native-content-relationships' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Upload a previously exported JSON file to restore or migrate relationships. Existing identical relationships will be skipped to prevent duplicates.', 'native-content-relationships' ); ?></p>
				
				<form method="post" enctype="multipart/form-data" action="">
					<?php wp_nonce_field( 'naticore_import', 'naticore_import_nonce' ); ?>
					<input type="hidden" name="action" value="naticore_import" />
					<div class="naticore-import-field" style="margin: 20px 0;">
						<input type="file" name="import_file" accept=".json" required />
					</div>
					<div class="naticore-actions-bar" style="justify-content: flex-start;">
						<?php submit_button( __( 'Start Import', 'native-content-relationships' ), 'primary', 'submit', false ); ?>
					</div>
				</form>
				
				<div class="naticore-notice">
					<p><strong><?php esc_html_e( 'Important:', 'native-content-relationships' ); ?></strong> <?php esc_html_e( 'Importing will not delete existing relationships. It only adds new ones from your file. Ensure the destination site has the same content (IDs) for the import to work correctly.', 'native-content-relationships' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle export
	 */
	public function handle_export() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'naticore_export' ) {
			return;
		}

		if ( ! isset( $_POST['naticore_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['naticore_export_nonce'] ) ), 'naticore_export' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Environment-aware: Gate export in production
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env_type = wp_get_environment_type();
			if ( $env_type === 'production' ) {
				// Still allow, but log it
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Environment warning
					error_log( 'WPNCR: Export requested in production environment' );
				}
			}
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Export feature
		$relationships = $wpdb->get_results( "SELECT from_id, to_id, type, direction FROM `{$wpdb->prefix}content_relations`" );

		$export_data = array();
		foreach ( $relationships as $rel ) {
			$export_data[] = array(
				'from'      => (int) $rel->from_id,
				'to'        => (int) $rel->to_id,
				'type'      => $rel->type,
				'direction' => $rel->direction,
			);
		}

		$filename = 'naticore-relationships-' . gmdate( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( json_encode( $export_data, JSON_PRETTY_PRINT ) ) );

		echo json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Handle import
	 */
	public function handle_import() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'naticore_import' ) {
			return;
		}

		if ( ! isset( $_POST['naticore_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['naticore_import_nonce'] ) ), 'naticore_import' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Environment-aware: Warn in production
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env_type = wp_get_environment_type();
			if ( $env_type === 'production' ) {
				// Still allow, but log warning
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Environment warning
					error_log( 'WPNCR: Import requested in production environment' );
				}
			}
		}

		if ( ! isset( $_FILES['import_file'] ) || ! isset( $_FILES['import_file']['error'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Error uploading file.', 'native-content-relationships' ) . '</p></div>';
				}
			);
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is validated by is_uploaded_file()
		$tmp_name = isset( $_FILES['import_file']['tmp_name'] ) ? $_FILES['import_file']['tmp_name'] : '';
		if ( empty( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Error uploading file.', 'native-content-relationships' ) . '</p></div>';
				}
			);
			return;
		}

		// tmp_name is validated by is_uploaded_file() above, safe to use
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by is_uploaded_file()
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is validated by is_uploaded_file() above
		$tmp_name = $_FILES['import_file']['tmp_name'];

		// Use WP_Filesystem for safe file operations
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$file_content = $wp_filesystem->get_contents( $tmp_name );
		$data         = json_decode( $file_content, true );

		if ( ! is_array( $data ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid JSON file.', 'native-content-relationships' ) . '</p></div>';
				}
			);
			return;
		}

		$imported = 0;
		$skipped  = 0;

		foreach ( $data as $rel ) {
			// Validate structure
			if ( ! isset( $rel['from'] ) || ! isset( $rel['to'] ) || ! isset( $rel['type'] ) ) {
				++$skipped;
				continue;
			}

			$from_id = absint( $rel['from'] );
			$to_id   = absint( $rel['to'] );
			$type    = sanitize_text_field( $rel['type'] );

			// Validate IDs exist
			if ( ! get_post( $from_id ) || ! get_post( $to_id ) ) {
				++$skipped;
				continue;
			}

			// Check if already exists
			if ( NATICORE_API::is_related( $from_id, $to_id, $type ) ) {
				++$skipped;
				continue;
			}

			// Validate type exists
			if ( ! NATICORE_Relation_Types::exists( $type ) ) {
				++$skipped;
				continue;
			}

			// Add relationship
			$result = NATICORE_API::add_relation( $from_id, $to_id, $type );
			if ( ! is_wp_error( $result ) ) {
				++$imported;
			} else {
				++$skipped;
			}
		}

		add_action(
			'admin_notices',
			function () use ( $imported, $skipped ) {
				?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Import Complete:', 'native-content-relationships' ); ?></strong>
					<?php
					/* translators: 1: Number of relationships imported, 2: Number of relationships skipped */
					printf( esc_html__( '%1$d relationships imported, %2$d skipped.', 'native-content-relationships' ), (int) $imported, (int) $skipped );
					?>
				</p>
			</div>
				<?php
			}
		);
	}
}

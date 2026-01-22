<?php
/**
 * Import / Export Functionality
 * JSON-based, MVP-safe
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNCR_Import_Export {
	
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
		
		add_action( 'admin_menu', array( $this, 'add_import_export_page' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );
	}
	
	/**
	 * Add import/export page
	 */
	public function add_import_export_page() {
		add_submenu_page(
			'tools.php',
			__( 'Import/Export Relationships', 'native-content-relationships' ),
			__( 'Import/Export Relationships', 'native-content-relationships' ),
			'manage_options',
			'wpncr-import-export',
			array( $this, 'render_import_export_page' )
		);
	}
	
	/**
	 * Render import/export page
	 */
	public function render_import_export_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="wpncr-import-export">
				<div class="wpncr-export-section" style="margin-bottom: 30px; padding: 20px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 2px;">
					<h2><?php esc_html_e( 'Export Relationships', 'native-content-relationships' ); ?></h2>
					<p><?php esc_html_e( 'Export all relationships to a JSON file for backup or migration.', 'native-content-relationships' ); ?></p>
					<form method="post" action="">
						<?php wp_nonce_field( 'wpncr_export', 'wpncr_export_nonce' ); ?>
						<input type="hidden" name="action" value="wpncr_export" />
						<?php submit_button( esc_html__( 'Export Relationships', 'native-content-relationships' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
				
				<div class="wpncr-import-section" style="padding: 20px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 2px;">
					<h2><?php esc_html_e( 'Import Relationships', 'native-content-relationships' ); ?></h2>
					<p><?php esc_html_e( 'Import relationships from a JSON file. Duplicates and invalid entries will be skipped.', 'native-content-relationships' ); ?></p>
					<form method="post" enctype="multipart/form-data" action="">
						<?php wp_nonce_field( 'wpncr_import', 'wpncr_import_nonce' ); ?>
						<input type="hidden" name="action" value="wpncr_import" />
						<p>
							<input type="file" name="import_file" accept=".json" required />
						</p>
						<?php submit_button( __( 'Import Relationships', 'native-content-relationships' ), 'primary', 'submit', false ); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Handle export
	 */
	public function handle_export() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'wpncr_export' ) {
			return;
		}
		
		if ( ! isset( $_POST['wpncr_export_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpncr_export_nonce'] ) ), 'wpncr_export' ) ) {
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
		
		$filename = 'wpncr-relationships-' . gmdate( 'Y-m-d' ) . '.json';
		
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
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'wpncr_import' ) {
			return;
		}
		
		if ( ! isset( $_POST['wpncr_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpncr_import_nonce'] ) ), 'wpncr_import' ) ) {
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
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Error uploading file.', 'native-content-relationships' ) . '</p></div>';
			} );
			return;
		}
		
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is validated by is_uploaded_file()
		$tmp_name = isset( $_FILES['import_file']['tmp_name'] ) ? $_FILES['import_file']['tmp_name'] : '';
		if ( empty( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Error uploading file.', 'native-content-relationships' ) . '</p></div>';
			} );
			return;
		}
		
		// tmp_name is validated by is_uploaded_file() above, safe to use
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated by is_uploaded_file()
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is validated by is_uploaded_file() above
		$tmp_name = $_FILES['import_file']['tmp_name'];
		$file_content = file_get_contents( $tmp_name );
		$data = json_decode( $file_content, true );
		
		if ( ! is_array( $data ) ) {
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid JSON file.', 'native-content-relationships' ) . '</p></div>';
			} );
			return;
		}
		
		$imported = 0;
		$skipped = 0;
		
		foreach ( $data as $rel ) {
			// Validate structure
			if ( ! isset( $rel['from'] ) || ! isset( $rel['to'] ) || ! isset( $rel['type'] ) ) {
				$skipped++;
				continue;
			}
			
			$from_id = absint( $rel['from'] );
			$to_id = absint( $rel['to'] );
			$type = sanitize_text_field( $rel['type'] );
			
			// Validate IDs exist
			if ( ! get_post( $from_id ) || ! get_post( $to_id ) ) {
				$skipped++;
				continue;
			}
			
			// Check if already exists
			if ( WPNCR_API::is_related( $from_id, $to_id, $type ) ) {
				$skipped++;
				continue;
			}
			
			// Validate type exists
			if ( ! WPNCR_Relation_Types::exists( $type ) ) {
				$skipped++;
				continue;
			}
			
			// Add relationship
			$result = WPNCR_API::add_relation( $from_id, $to_id, $type );
			if ( ! is_wp_error( $result ) ) {
				$imported++;
			} else {
				$skipped++;
			}
		}
		
		add_action( 'admin_notices', function() use ( $imported, $skipped ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Import Complete:', 'native-content-relationships' ); ?></strong>
					<?php
					/* translators: 1: Number of relationships imported, 2: Number of relationships skipped */
					printf( esc_html__( '%1$d relationships imported, %2$d skipped.', 'native-content-relationships' ), esc_html( $imported ), esc_html( $skipped ) );
					?>
				</p>
			</div>
			<?php
		} );
	}
}

<?php
/**
 * Relationship Permissions
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Permissions
 *
 * Role-based access control for relationship types.
 */
class NATICORE_Permissions {

	/**
	 * Default permissions
	 *
	 * @var array
	 */
	private static $defaults = array(
		'administrator' => array( 'create', 'edit', 'delete', 'view' ),
		'editor'        => array( 'create', 'edit', 'delete', 'view' ),
		'author'        => array( 'create', 'edit', 'view' ),
		'contributor'   => array( 'create', 'view' ),
		'subscriber'    => array( 'view' ),
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'naticore_relation_is_allowed', array( $this, 'validate_permissions' ), 3, 2 );
	}

	/**
	 * Get permissions for a role
	 *
	 * @param string $role User role.
	 * @return array Capabilities.
	 */
	public static function get_role_permissions( $role ) {
		$custom = get_option( 'ncr_permissions', array() );
		return $custom[ $role ] ?? ( self::$defaults[ $role ] ?? array( 'view' ) );
	}

	/**
	 * Set permissions for a role
	 *
	 * @param string $role        User role.
	 * @param array  $permissions Capabilities.
	 */
	public static function set_role_permissions( $role, $permissions ) {
		$custom                = get_option( 'ncr_permissions', array() );
		$custom[ $role ]       = $permissions;
		update_option( 'ncr_permissions', $custom );
	}

	/**
	 * Check if user can perform action on relationship type
	 *
	 * @param string $capability Capability to check (create, edit, delete, view).
	 * @param string $type       Relationship type.
	 * @param int    $user_id    User ID (optional, defaults to current user).
	 * @return bool
	 */
	public static function can( $capability, $type = '', $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		// Administrators can always do everything
		if ( in_array( 'administrator', $user->roles, true ) ) {
			return true;
		}

		// Check type-specific permissions first
		$type_permissions = self::get_type_permissions( $type );
		if ( ! empty( $type_permissions ) ) {
			$user_roles = $user->roles;
			foreach ( $user_roles as $role ) {
				if ( isset( $type_permissions[ $role ] ) && in_array( $capability, $type_permissions[ $role ], true ) ) {
					return true;
				}
			}
			return false;
		}

		// Fall back to general role permissions
		foreach ( $user->roles as $role ) {
			$perms = self::get_role_permissions( $role );
			if ( in_array( $capability, $perms, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get type-specific permissions
	 *
	 * @param string $type Relationship type.
	 * @return array
	 */
	public static function get_type_permissions( $type ) {
		$all = get_option( 'ncr_type_permissions', array() );
		return $all[ $type ] ?? array();
	}

	/**
	 * Set type-specific permissions
	 *
	 * @param string $type        Relationship type.
	 * @param array  $permissions Permissions array.
	 */
	public static function set_type_permissions( $type, $permissions ) {
		$all                  = get_option( 'ncr_type_permissions', array() );
		$all[ $type ]         = $permissions;
		update_option( 'ncr_type_permissions', $all );
	}

	/**
	 * Validate permissions
	 *
	 * @param bool  $is_allowed Current allowed status.
	 * @param array $context    Relationship context.
	 * @return bool
	 */
	public function validate_permissions( $is_allowed, $context ) {
		if ( ! $is_allowed ) {
			return false;
		}

		$type = $context['type'] ?? '';

		// Determine capability based on action
		if ( isset( $context['relation_id'] ) ) {
			$capability = 'edit';
		} else {
			$capability = 'create';
		}

		return self::can( $capability, $type );
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$roles     = wp_roles()->get_names();
		$rel_types = NATICORE_Relation_Types::get_types();

		// Handle save
		if ( isset( $_POST['ncr_save_permissions'] ) && check_admin_referer( 'ncr_permissions_save' ) ) {
			$role_caps = $_POST['role_permissions'] ?? array();
			foreach ( $role_caps as $role => $caps ) {
				$capabilities = array_keys( array_filter( $caps ) );
				self::set_role_permissions( $role, $capabilities );
			}

			$type_perms = $_POST['type_permissions'] ?? array();
			foreach ( $type_perms as $type => $role_perms ) {
				self::set_type_permissions( $type, $role_perms );
			}

			add_settings_error( 'ncr', 'saved', __( 'Permissions saved.', 'native-content-relationships' ), 'success' );
		}

		settings_errors( 'ncr' );
		$all_permissions = get_option( 'ncr_permissions', array() );
		$type_all        = get_option( 'ncr_type_permissions', array() );

		$stitch_admin = NATICORE_Stitch_Admin::get_instance();
		$stitch_admin->render_wrapper_start( 'naticore-tools' );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Permissions', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Control who can create, edit, delete, and view each relationship type.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<form method="post">
			<?php wp_nonce_field( 'ncr_permissions_save' ); ?>

			<div class="nc-grid-12">
				<div class="nc-col-12 nc-card nc-mb-md">
					<div class="nc-card-header">
						<h3><?php esc_html_e( 'General Role Permissions', 'native-content-relationships' ); ?></h3>
					</div>
					<div style="overflow-x:auto;">
						<div class="nc-table-responsive">
							<table class="nc-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Role', 'native-content-relationships' ); ?></th>
										<th style="text-align:center;"><?php esc_html_e( 'Create', 'native-content-relationships' ); ?></th>
										<th style="text-align:center;"><?php esc_html_e( 'Edit', 'native-content-relationships' ); ?></th>
										<th style="text-align:center;"><?php esc_html_e( 'Delete', 'native-content-relationships' ); ?></th>
										<th style="text-align:center;"><?php esc_html_e( 'View', 'native-content-relationships' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $roles as $role_key => $role_name ) :
										$perms = self::get_role_permissions( $role_key );
										?>
										<tr>
											<td class="nc-font-semibold"><?php echo esc_html( $role_name ); ?></td>
											<td style="text-align:center;">
												<input type="checkbox" name="role_permissions[<?php echo esc_attr( $role_key ); ?>][create]" value="1" <?php checked( in_array( 'create', $perms, true ) ); ?>>
											</td>
											<td style="text-align:center;">
												<input type="checkbox" name="role_permissions[<?php echo esc_attr( $role_key ); ?>][edit]" value="1" <?php checked( in_array( 'edit', $perms, true ) ); ?>>
											</td>
											<td style="text-align:center;">
												<input type="checkbox" name="role_permissions[<?php echo esc_attr( $role_key ); ?>][delete]" value="1" <?php checked( in_array( 'delete', $perms, true ) ); ?>>
											</td>
											<td style="text-align:center;">
												<input type="checkbox" name="role_permissions[<?php echo esc_attr( $role_key ); ?>][view]" value="1" <?php checked( in_array( 'view', $perms, true ) ); ?>>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

				<div class="nc-col-12 nc-mb-md">
					<h2 class="nc-headline-sm" style="margin-bottom:8px;"><?php esc_html_e( 'Type-Specific Permissions (Optional)', 'native-content-relationships' ); ?></h2>
					<p class="nc-text-sm nc-text-muted">
						<?php esc_html_e( 'Override general permissions for specific relationship types. Leave empty to use general permissions.', 'native-content-relationships' ); ?>
					</p>
				</div>

				<?php foreach ( $rel_types as $type_key => $type_info ) :
					$type_perms = self::get_type_permissions( $type_key );
					?>
					<div class="nc-col-12 nc-card nc-mb-md">
						<div class="nc-card-header">
							<h3><?php echo esc_html( $type_info['label'] ); ?></h3>
						</div>
						<div style="overflow-x:auto;">
							<div class="nc-table-responsive">
								<table class="nc-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Role', 'native-content-relationships' ); ?></th>
											<th style="text-align:center;"><?php esc_html_e( 'Create', 'native-content-relationships' ); ?></th>
											<th style="text-align:center;"><?php esc_html_e( 'Edit', 'native-content-relationships' ); ?></th>
											<th style="text-align:center;"><?php esc_html_e( 'Delete', 'native-content-relationships' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $roles as $role_key => $role_name ) :
											$role_perms = $type_perms[ $role_key ] ?? array();
											?>
											<tr>
												<td class="nc-font-semibold"><?php echo esc_html( $role_name ); ?></td>
												<td style="text-align:center;">
													<input type="checkbox" name="type_permissions[<?php echo esc_attr( $type_key ); ?>][<?php echo esc_attr( $role_key ); ?>][create]" value="1" <?php checked( in_array( 'create', $role_perms, true ) ); ?>>
												</td>
												<td style="text-align:center;">
													<input type="checkbox" name="type_permissions[<?php echo esc_attr( $type_key ); ?>][<?php echo esc_attr( $role_key ); ?>][edit]" value="1" <?php checked( in_array( 'edit', $role_perms, true ) ); ?>>
												</td>
												<td style="text-align:center;">
													<input type="checkbox" name="type_permissions[<?php echo esc_attr( $type_key ); ?>][<?php echo esc_attr( $role_key ); ?>][delete]" value="1" <?php checked( in_array( 'delete', $role_perms, true ) ); ?>>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="nc-col-12">
					<button type="submit" name="ncr_save_permissions" class="nc-btn nc-btn-primary">
						<?php esc_html_e( 'Save Permissions', 'native-content-relationships' ); ?>
					</button>
				</div>
			</div>
		</form>
		<?php
		$stitch_admin->render_wrapper_end();
	}

	/**
	 * Register admin menu
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'naticore-hidden',
			__( 'Relationship Permissions', 'native-content-relationships' ),
			__( 'Relationship Permissions', 'native-content-relationships' ),
			'manage_options',
			'naticore-permissions',
			array( __CLASS__, 'render_admin_page' )
		);
	}
}

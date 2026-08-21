<?php
/**
 * Relationship Constraints & Cardinality
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Constraints
 *
 * Enforces relationship constraints and cardinality rules.
 */
class NATICORE_Constraints {

	/**
	 * Constraint storage key
	 */
	const OPTION_KEY = 'ncr_constraints';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'naticore_relation_is_allowed', array( $this, 'validate_constraints' ), 5, 2 );
		add_filter( 'naticore_relation_is_allowed', array( $this, 'validate_cardinality' ), 6, 2 );
		add_filter( 'naticore_relation_is_allowed', array( $this, 'validate_duplicate' ), 7, 2 );
	}

	/**
	 * Get all constraints
	 *
	 * @return array
	 */
	public static function get_constraints() {
		return get_option( self::OPTION_KEY, array() );
	}

	/**
	 * Save constraints
	 *
	 * @param array $constraints Constraints array.
	 */
	public static function save_constraints( $constraints ) {
		update_option( self::OPTION_KEY, $constraints );
	}

	/**
	 * Add a constraint rule
	 *
	 * @param string $from_type Source post type.
	 * @param string $to_type   Target post type.
	 * @param string $type      Relationship type key.
	 * @param bool   $allowed   Whether this combination is allowed.
	 */
	public static function add_constraint( $from_type, $to_type, $type, $allowed = true ) {
		$constraints              = self::get_constraints();
		$constraints[ $from_type . '_' . $to_type . '_' . $type ] = array(
			'from_type' => $from_type,
			'to_type'   => $to_type,
			'type'      => $type,
			'allowed'   => $allowed,
		);
		self::save_constraints( $constraints );
	}

	/**
	 * Remove a constraint rule
	 *
	 * @param string $from_type Source post type.
	 * @param string $to_type   Target post type.
	 * @param string $type      Relationship type key.
	 */
	public static function remove_constraint( $from_type, $to_type, $type ) {
		$constraints = self::get_constraints();
		unset( $constraints[ $from_type . '_' . $to_type . '_' . $type ] );
		self::save_constraints( $constraints );
	}

	/**
	 * Validate constraint rules
	 *
	 * @param bool  $is_allowed Current allowed status.
	 * @param array $context    Relationship context.
	 * @return bool
	 */
	public function validate_constraints( $is_allowed, $context ) {
		if ( ! $is_allowed ) {
			return false;
		}

		$from_post = get_post( $context['from_id'] );
		$to_post   = get_post( $context['to_id'] );

		if ( ! $from_post || ! $to_post ) {
			return true;
		}

		$constraints = self::get_constraints();
		$key         = $from_post->post_type . '_' . $to_post->post_type . '_' . $context['type'];

		if ( isset( $constraints[ $key ] ) ) {
			return (bool) $constraints[ $key ]['allowed'];
		}

		return true;
	}

	/**
	 * Validate cardinality rules
	 *
	 * @param bool  $is_allowed Current allowed status.
	 * @param array $context    Relationship context.
	 * @return bool
	 */
	public function validate_cardinality( $is_allowed, $context ) {
		if ( ! $is_allowed ) {
			return false;
		}

		$cardinality = self::get_cardinality_rules();
		$key         = $context['type'];

		if ( ! isset( $cardinality[ $key ] ) ) {
			return true;
		}

		$rule = $cardinality[ $key ];

		// Check max connections from source
		if ( isset( $rule['max_from'] ) && $rule['max_from'] > 0 ) {
			$count = NATICORE_API::count_relations( $context['from_id'], $context['type'] );
			if ( $count >= $rule['max_from'] ) {
				return false;
			}
		}

		// Check max connections to target
		if ( isset( $rule['max_to'] ) && $rule['max_to'] > 0 ) {
			$count = NATICORE_API::count_related_to( $context['to_id'], $context['type'] );
			if ( $count >= $rule['max_to'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate duplicate detection
	 *
	 * @param bool  $is_allowed Current allowed status.
	 * @param array $context    Relationship context.
	 * @return bool
	 */
	public function validate_duplicate( $is_allowed, $context ) {
		if ( ! $is_allowed ) {
			return false;
		}

		// Check if relationship already exists
		$existing = NATICORE_API::is_related( $context['from_id'], $context['to_id'], $context['type'] );

		if ( $existing ) {
			return false;
		}

		return true;
	}

	/**
	 * Get cardinality rules
	 *
	 * @return array
	 */
	public static function get_cardinality_rules() {
		return get_option( 'ncr_cardinality', array() );
	}

	/**
	 * Save cardinality rules
	 *
	 * @param array $rules Cardinality rules.
	 */
	public static function save_cardinality_rules( $rules ) {
		update_option( 'ncr_cardinality', $rules );
	}

	/**
	 * Add a cardinality rule
	 *
	 * @param string $type     Relationship type key.
	 * @param int    $max_from Max connections from source (0 = unlimited).
	 * @param int    $max_to   Max connections to target (0 = unlimited).
	 */
	public static function add_cardinality_rule( $type, $max_from = 0, $max_to = 0 ) {
		$rules               = self::get_cardinality_rules();
		$rules[ $type ]      = array(
			'max_from' => absint( $max_from ),
			'max_to'   => absint( $max_to ),
		);
		self::save_cardinality_rules( $rules );
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submissions
		if ( isset( $_POST['ncr_save_constraints'] ) && check_admin_referer( 'ncr_constraints_save' ) ) {
			$from_type = sanitize_text_field( wp_unslash( $_POST['from_type'] ?? '' ) );
			$to_type   = sanitize_text_field( wp_unslash( $_POST['to_type'] ?? '' ) );
			$type      = sanitize_text_field( wp_unslash( $_POST['type'] ?? '' ) );
			$allowed   = isset( $_POST['allowed'] ) ? 1 : 0;

			if ( $from_type && $to_type && $type ) {
				self::add_constraint( $from_type, $to_type, $type, $allowed );
				add_settings_error( 'ncr', 'saved', __( 'Constraint saved.', 'native-content-relationships' ), 'success' );
			}
		}

		if ( isset( $_POST['ncr_save_cardinality'] ) && check_admin_referer( 'ncr_cardinality_save' ) ) {
			$type     = sanitize_text_field( wp_unslash( $_POST['cardinality_type'] ?? '' ) );
			$max_from = absint( $_POST['max_from'] ?? 0 );
			$max_to   = absint( $_POST['max_to'] ?? 0 );

			if ( $type ) {
				self::add_cardinality_rule( $type, $max_from, $max_to );
				add_settings_error( 'ncr', 'saved', __( 'Cardinality rule saved.', 'native-content-relationships' ), 'success' );
			}
		}

		if ( isset( $_GET['delete_constraint'] ) ) {
			$delete_key = sanitize_text_field( wp_unslash( $_GET['delete_constraint'] ) );
			if ( check_admin_referer( 'ncr_delete_constraint_' . $delete_key ) ) {
				$constraints = self::get_constraints();
				unset( $constraints[ $delete_key ] );
				self::save_constraints( $constraints );
				add_settings_error( 'ncr', 'deleted', __( 'Constraint deleted.', 'native-content-relationships' ), 'success' );
			}
		}

		$constraints  = self::get_constraints();
		$cardinality  = self::get_cardinality_rules();
		$post_types   = get_post_types( array( 'public' => true ), 'objects' );
		$rel_types    = NATICORE_Relation_Types::get_types();
		settings_errors( 'ncr' );

		$stitch_admin = NATICORE_Stitch_Admin::get_instance();
		$stitch_admin->render_wrapper_start( 'naticore-tools' );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Constraints & Cardinality', 'native-content-relationships' ); ?>
			</h1>
		</div>

		<div class="nc-grid-12">
			<!-- Constraints Section -->
			<div class="nc-col-12 nc-card nc-mb-lg">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Relationship Constraints', 'native-content-relationships' ); ?></h3>
					<p class="nc-text-sm nc-text-muted" style="margin-top:4px;"><?php esc_html_e( 'Define which content type combinations are allowed for each relationship type.', 'native-content-relationships' ); ?></p>
				</div>
				<div class="nc-card-body">
					<form method="post">
						<?php wp_nonce_field( 'ncr_constraints_save' ); ?>
						<div class="nc-flex nc-flex-col nc-gap-md" style="max-width: 600px;">
							
							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="from_type"><?php esc_html_e( 'From Type', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1;">
									<select name="from_type" id="from_type" class="nc-select" required>
										<option value=""><?php esc_html_e( 'Select...', 'native-content-relationships' ); ?></option>
										<?php foreach ( $post_types as $pt ) : ?>
											<option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->labels->singular_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="to_type"><?php esc_html_e( 'To Type', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1;">
									<select name="to_type" id="to_type" class="nc-select" required>
										<option value=""><?php esc_html_e( 'Select...', 'native-content-relationships' ); ?></option>
										<?php foreach ( $post_types as $pt ) : ?>
											<option value="<?php echo esc_attr( $pt->name ); ?>"><?php echo esc_html( $pt->labels->singular_name ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="type"><?php esc_html_e( 'Relationship Type', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1;">
									<select name="type" id="type" class="nc-select" required>
										<option value=""><?php esc_html_e( 'Select...', 'native-content-relationships' ); ?></option>
										<?php foreach ( $rel_types as $key => $rel_type ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $rel_type['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="allowed"><?php esc_html_e( 'Allowed', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1; display: flex; align-items: center; gap: 8px;">
									<input type="checkbox" name="allowed" id="allowed" value="1" checked>
									<span class="nc-text-sm nc-text-muted"><?php esc_html_e( 'Allow this combination', 'native-content-relationships' ); ?></span>
								</div>
							</div>

							<div>
								<button type="submit" name="ncr_save_constraints" class="nc-btn nc-btn-primary">
									<?php esc_html_e( 'Add Constraint', 'native-content-relationships' ); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
				
				<?php if ( ! empty( $constraints ) ) : ?>
				<div style="overflow-x:auto;">
					<div class="nc-table-responsive" style="border-top: 1px solid var(--nc-border);">
						<table class="nc-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'From', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'To', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Status', 'native-content-relationships' ); ?></th>
									<th style="text-align:right;"><?php esc_html_e( 'Action', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $constraints as $key => $constraint ) : ?>
									<tr>
										<td class="nc-font-semibold"><?php echo esc_html( $constraint['from_type'] ); ?></td>
										<td class="nc-font-semibold"><?php echo esc_html( $constraint['to_type'] ); ?></td>
										<td><?php echo esc_html( $constraint['type'] ); ?></td>
										<td>
											<span class="nc-badge <?php echo $constraint['allowed'] ? 'nc-badge-success' : 'nc-badge-error'; ?>">
												<?php echo $constraint['allowed'] ? __( 'Allowed', 'native-content-relationships' ) : __( 'Blocked', 'native-content-relationships' ); ?>
											</span>
										</td>
										<td style="text-align:right;">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-constraints&delete_constraint=' . $key ), 'ncr_delete_constraint_' . $key ) ); ?>" class="nc-text-error" style="text-decoration:none;font-weight:500;" onclick="return confirm('<?php esc_attr_e( 'Delete this constraint?', 'native-content-relationships' ); ?>');">
												<?php esc_html_e( 'Delete', 'native-content-relationships' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<!-- Cardinality Section -->
			<div class="nc-col-12 nc-card">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Cardinality Rules', 'native-content-relationships' ); ?></h3>
					<p class="nc-text-sm nc-text-muted" style="margin-top:4px;"><?php esc_html_e( 'Set maximum connection limits per relationship type.', 'native-content-relationships' ); ?></p>
				</div>
				<div class="nc-card-body">
					<form method="post">
						<?php wp_nonce_field( 'ncr_cardinality_save' ); ?>
						<div class="nc-flex nc-flex-col nc-gap-md" style="max-width: 600px;">
							
							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="cardinality_type"><?php esc_html_e( 'Relationship Type', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1;">
									<select name="cardinality_type" id="cardinality_type" class="nc-select" required>
										<option value=""><?php esc_html_e( 'Select...', 'native-content-relationships' ); ?></option>
										<?php foreach ( $rel_types as $key => $rel_type ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $rel_type['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>

							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="max_from"><?php esc_html_e( 'Max From Source', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1; display: flex; align-items: center; gap: 8px;">
									<input type="number" name="max_from" id="max_from" class="nc-input" min="0" value="0" style="width: 80px;">
									<span class="nc-text-sm nc-text-muted"><?php esc_html_e( '0 = unlimited', 'native-content-relationships' ); ?></span>
								</div>
							</div>

							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="max_to"><?php esc_html_e( 'Max To Target', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1; display: flex; align-items: center; gap: 8px;">
									<input type="number" name="max_to" id="max_to" class="nc-input" min="0" value="0" style="width: 80px;">
									<span class="nc-text-sm nc-text-muted"><?php esc_html_e( '0 = unlimited', 'native-content-relationships' ); ?></span>
								</div>
							</div>

							<div>
								<button type="submit" name="ncr_save_cardinality" class="nc-btn nc-btn-primary">
									<?php esc_html_e( 'Add Rule', 'native-content-relationships' ); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
				
				<?php if ( ! empty( $cardinality ) ) : ?>
				<div style="overflow-x:auto;">
					<div class="nc-table-responsive" style="border-top: 1px solid var(--nc-border);">
						<table class="nc-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Type', 'native-content-relationships' ); ?></th>
									<th style="text-align:center;"><?php esc_html_e( 'Max From', 'native-content-relationships' ); ?></th>
									<th style="text-align:center;"><?php esc_html_e( 'Max To', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $cardinality as $key => $rule ) : ?>
									<tr>
										<td class="nc-font-semibold"><?php echo esc_html( $key ); ?></td>
										<td style="text-align:center;">
											<span class="nc-badge <?php echo $rule['max_from'] ? 'nc-badge-neutral' : 'nc-badge-success'; ?>">
												<?php echo $rule['max_from'] ? $rule['max_from'] : '∞'; ?>
											</span>
										</td>
										<td style="text-align:center;">
											<span class="nc-badge <?php echo $rule['max_to'] ? 'nc-badge-neutral' : 'nc-badge-success'; ?>">
												<?php echo $rule['max_to'] ? $rule['max_to'] : '∞'; ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$stitch_admin->render_wrapper_end();
	}

	/**
	 * Register admin menu
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'naticore-hidden',
			__( 'Constraints & Cardinality', 'native-content-relationships' ),
			__( 'Constraints & Cardinality', 'native-content-relationships' ),
			'manage_options',
			'naticore-constraints',
			array( __CLASS__, 'render_admin_page' )
		);
	}
}

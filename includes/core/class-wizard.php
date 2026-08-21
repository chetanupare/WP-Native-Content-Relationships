<?php
/**
 * Native Content Relationships - Setup Wizard
 *
 * Provides a step-by-step onboarding wizard for first-time setup.
 * Registers a hidden admin page under the naticore-hidden parent.
 *
 * @package    NativeContentRelationships
 * @subpackage Includes/Core
 * @since      1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class NC_Wizard
 *
 * Manages the setup wizard: menu registration, page rendering, AJAX state persistence,
 * and completion tracking.
 */
class NC_Wizard {

	/**
	 * Option key for wizard state (temporary, stored during wizard flow).
	 *
	 * @var string
	 */
	const STATE_KEY = 'naticore_wizard_state';

	/**
	 * Option key for wizard completion flag.
	 *
	 * @var string
	 */
	const COMPLETED_KEY = 'naticore_wizard_completed';

	/**
	 * The current wizard step.
	 *
	 * @var string
	 */
	private $current_step = '';

	/**
	 * Available wizard steps.
	 *
	 * @var array
	 */
	private $steps = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->define_steps();
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_naticore_wizard_save', array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_naticore_wizard_skip', array( $this, 'ajax_skip' ) );
		add_action( 'wp_ajax_naticore_wizard_complete', array( $this, 'ajax_complete' ) );
	}

	/**
	 * Define available wizard steps.
	 *
	 * @return void
	 */
	private function define_steps() {
		$this->steps = array(
			'welcome'    => array(
				'label'       => __( 'Welcome', 'native-content-relationships' ),
				'description' => __( 'Introduction', 'native-content-relationships' ),
			),
			'types'      => array(
				'label'       => __( 'Types', 'native-content-relationships' ),
				'description' => __( 'Relationship Types', 'native-content-relationships' ),
			),
			'presets'    => array(
				'label'       => __( 'Presets', 'native-content-relationships' ),
				'description' => __( 'Pre-built Templates', 'native-content-relationships' ),
			),
			'post-types' => array(
				'label'       => __( 'Post Types', 'native-content-relationships' ),
				'description' => __( 'Enabled Post Types', 'native-content-relationships' ),
			),
			'review'     => array(
				'label'       => __( 'Review', 'native-content-relationships' ),
				'description' => __( 'Summary & Finish', 'native-content-relationships' ),
			),
		);
	}

	/**
	 * Register the wizard admin menu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'naticore-hidden',
			__( 'Setup Wizard', 'native-content-relationships' ),
			__( '', 'native-content-relationships' ),
			'manage_options',
			'naticore-wizard',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue wizard assets on the wizard page only.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'naticore-wizard' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'nc-wizard',
			NATICORE_PLUGIN_URL . 'assets/css/wizard.css',
			array( 'nc-stitch-admin' ),
			NATICORE_VERSION
		);

		wp_enqueue_script(
			'nc-wizard',
			NATICORE_PLUGIN_URL . 'assets/js/wizard.js',
			array( 'jquery', 'nc-stitch-admin' ),
			NATICORE_VERSION,
			true
		);

		wp_localize_script(
			'nc-wizard',
			'ncWizardData',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'nc_wizard_nonce' ),
				'steps'    => array_keys( $this->steps ),
				'state'    => $this->get_state(),
				'presets'  => $this->get_presets_for_js(),
				'types'    => $this->get_types_for_js(),
				'postTypes'=> $this->get_post_types_for_js(),
			)
		);
	}

	/**
	 * Render the wizard page.
	 *
	 * @return void
	 */
	public function render_page() {
		$this->current_step = $this->get_current_step();
		$state              = $this->get_state();
		$completed          = get_option( self::COMPLETED_KEY, false );

		// Handle restart: clear state and completed flag.
		if ( $completed && isset( $_GET['restart'] ) && '1' === $_GET['restart'] ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'nc_wizard_restart' ) ) {
				delete_option( self::COMPLETED_KEY );
				delete_option( self::STATE_KEY );
				$completed = false;
				$state     = array();
			}
		}

		if ( $completed ) {
			$this->render_completed();
			return;
		}

		$step_index = array_search( $this->current_step, array_keys( $this->steps ) );
		$total_steps = count( $this->steps );
		$progress_pct = $total_steps > 0 ? round( ( ( $step_index ) / $total_steps ) * 100 ) : 0;

		?>
		<div class="nc-stitch">
		<div class="nc-wizard-wrap" id="nc-wizard" data-step="<?php echo esc_attr( $this->current_step ); ?>">
			<div class="nc-wizard-sidebar">
				<div class="nc-wizard-sidebar-header">
					<div class="nc-wizard-sidebar-brand">
						<div class="nc-wizard-sidebar-brand-icon">
							<span class="material-symbols-outlined">link</span>
						</div>
						<h1><?php esc_html_e( 'Setup Wizard', 'native-content-relationships' ); ?></h1>
					</div>
					<p><?php esc_html_e( 'Get started in a few steps', 'native-content-relationships' ); ?></p>
				</div>
				<nav class="nc-wizard-steps" aria-label="<?php esc_attr_e( 'Wizard steps', 'native-content-relationships' ); ?>">
					<ol class="nc-wizard-step-list">
						<?php
						$step_index_num = 0;
						foreach ( $this->steps as $step_key => $step_data ) :
							$step_index_num++;
							$is_active  = ( $step_key === $this->current_step );
							$is_done    = $step_index_num < array_search( $this->current_step, array_keys( $this->steps ) ) + 1;
							$class      = $is_active ? ' active' : ( $is_done ? ' done' : '' );
							?>
							<li class="nc-wizard-step-item<?php echo esc_attr( $class ); ?>"
								data-step="<?php echo esc_attr( $step_key ); ?>">
								<span class="nc-wizard-step-num">
									<?php if ( $is_done ) : ?>
										<span class="material-symbols-outlined">check</span>
									<?php else : ?>
										<?php echo esc_html( $step_index_num ); ?>
									<?php endif; ?>
								</span>
								<span class="nc-wizard-step-text">
									<span class="nc-wizard-step-label"><?php echo esc_html( $step_data['label'] ); ?></span>
									<span class="nc-wizard-step-desc"><?php echo esc_html( $step_data['description'] ); ?></span>
								</span>
							</li>
						<?php endforeach; ?>
					</ol>
				</nav>
				<div class="nc-wizard-sidebar-progress">
					<div class="nc-wizard-progress-text">
						<span><?php esc_html_e( 'Progress', 'native-content-relationships' ); ?></span>
						<span><?php echo esc_html( $progress_pct . '%' ); ?></span>
					</div>
					<div class="nc-wizard-progress-track">
						<div class="nc-wizard-progress-fill" style="width: <?php echo esc_attr( $progress_pct ); ?>%"></div>
					</div>
				</div>
			</div>
			<div class="nc-wizard-main">
				<div class="nc-wizard-content" id="nc-wizard-content">
					<?php $this->render_step( $this->current_step, $state ); ?>
				</div>
				<div class="nc-wizard-footer">
					<div class="nc-wizard-footer-left">
						<?php if ( 'welcome' !== $this->current_step ) : ?>
							<button type="button" class="nc-btn nc-btn-ghost nc-wizard-skip"
								id="nc-wizard-skip">
								<?php esc_html_e( 'Skip setup', 'native-content-relationships' ); ?>
							</button>
						<?php endif; ?>
					</div>
					<div class="nc-wizard-footer-right">
						<?php if ( 'welcome' !== $this->current_step ) : ?>
							<button type="button" class="nc-btn nc-btn-secondary nc-wizard-back"
								id="nc-wizard-back">
								<span class="material-symbols-outlined">arrow_back</span>
								<?php esc_html_e( 'Back', 'native-content-relationships' ); ?>
							</button>
						<?php endif; ?>
						<?php if ( 'review' === $this->current_step ) : ?>
							<button type="button" class="nc-btn nc-btn-primary nc-wizard-finish"
								id="nc-wizard-finish">
								<?php esc_html_e( 'Complete Setup', 'native-content-relationships' ); ?>
								<span class="material-symbols-outlined">check_circle</span>
							</button>
						<?php else : ?>
							<button type="button" class="nc-btn nc-btn-primary nc-wizard-next"
								id="nc-wizard-next">
								<?php esc_html_e( 'Continue', 'native-content-relationships' ); ?>
								<span class="material-symbols-outlined">arrow_forward</span>
							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Skip Confirmation Dialog -->
		<div class="nc-wizard-skip-dialog" id="nc-wizard-skip-dialog">
			<div class="nc-wizard-skip-dialog-box">
				<h3><?php esc_html_e( 'Skip the wizard?', 'native-content-relationships' ); ?></h3>
				<p><?php esc_html_e( 'No worries — you can configure everything manually from the Relationships menu later. Your current progress will be saved.', 'native-content-relationships' ); ?></p>
				<div class="nc-wizard-skip-dialog-actions">
					<button type="button" class="nc-btn nc-btn-secondary" id="nc-wizard-skip-cancel">
						<?php esc_html_e( 'Go back', 'native-content-relationships' ); ?>
					</button>
					<button type="button" class="nc-btn nc-btn-primary" id="nc-wizard-skip-confirm">
						<?php esc_html_e( 'Yes, skip it', 'native-content-relationships' ); ?>
					</button>
				</div>
			</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Render a specific wizard step.
	 *
	 * @param string $step  The step key.
	 * @param array  $state The current wizard state.
	 * @return void
	 */
	private function render_step( $step, $state ) {
		switch ( $step ) {
			case 'welcome':
				$this->render_step_welcome( $state );
				break;
			case 'types':
				$this->render_step_types( $state );
				break;
			case 'presets':
				$this->render_step_presets( $state );
				break;
			case 'post-types':
				$this->render_step_post_types( $state );
				break;
			case 'review':
				$this->render_step_review( $state );
				break;
			default:
				$this->render_step_welcome( $state );
				break;
		}
	}

	/**
	 * Render the Welcome step.
	 *
	 * @param array $state Current wizard state.
	 * @return void
	 */
	private function render_step_welcome( $state ) {
		?>
		<div class="nc-wizard-step-content nc-wizard-step-noscroll" data-step-content="welcome">
			<div class="nc-wizard-hero">
				<div class="nc-wizard-hero-icon">
					<span class="material-symbols-outlined" style="font-size:48px;">link</span>
				</div>
				<h2 class="nc-headline-lg"><?php esc_html_e( 'Welcome to Native Content Relationships', 'native-content-relationships' ); ?></h2>
				<p class="nc-text-sm nc-text-muted" style="max-width:480px;margin:0 auto;">
					<?php esc_html_e( 'Connect your content with a powerful, flexible relationship engine. This wizard will help you get started in just a few steps.', 'native-content-relationships' ); ?>
				</p>
			</div>
			<div class="nc-wizard-features">
				<div class="nc-wizard-feature-card">
					<div class="nc-wizard-feature-icon nc-kpi-icon-primary">
						<span class="material-symbols-outlined">category</span>
					</div>
					<div>
						<h3 class="nc-headline-sm"><?php esc_html_e( 'Custom Types', 'native-content-relationships' ); ?></h3>
						<p class="nc-text-xs nc-text-muted"><?php esc_html_e( 'Define relationship types that match your content model.', 'native-content-relationships' ); ?></p>
					</div>
				</div>
				<div class="nc-wizard-feature-card">
					<div class="nc-wizard-feature-icon nc-kpi-icon-secondary">
						<span class="material-symbols-outlined">auto_awesome</span>
					</div>
					<div>
						<h3 class="nc-headline-sm"><?php esc_html_e( 'Quick Presets', 'native-content-relationships' ); ?></h3>
						<p class="nc-text-xs nc-text-muted"><?php esc_html_e( 'Start with pre-built templates for common patterns.', 'native-content-relationships' ); ?></p>
					</div>
				</div>
				<div class="nc-wizard-feature-card">
					<div class="nc-wizard-feature-icon nc-kpi-icon-success">
						<span class="material-symbols-outlined">tune</span>
					</div>
					<div>
						<h3 class="nc-headline-sm"><?php esc_html_e( 'Full Control', 'native-content-relationships' ); ?></h3>
						<p class="nc-text-xs nc-text-muted"><?php esc_html_e( 'Choose which post types to enable and configure settings.', 'native-content-relationships' ); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Types step.
	 *
	 * @param array $state Current wizard state.
	 * @return void
	 */
	private function render_step_types( $state ) {
		$types      = $this->get_types_for_js();
		$raw_sel   = isset( $state['selected_types'] ) ? $state['selected_types'] : array( 'related_to' );
		$selected  = is_array( $raw_sel ) ? $raw_sel : array( 'related_to' );
		?>
		<div class="nc-wizard-step-content" data-step-content="types">
			<div class="nc-wizard-step-header">
				<h2 class="nc-headline-lg"><?php esc_html_e( 'Choose Relationship Types', 'native-content-relationships' ); ?></h2>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'Select which relationship types to enable. You can customize these later.', 'native-content-relationships' ); ?>
				</p>
			</div>
			<div class="nc-wizard-type-grid">
				<?php foreach ( $types as $type ) :
					$is_selected = in_array( $type['slug'], $selected, true );
					$is_locked   = ! empty( $type['locked'] );
					$is_bidir    = ! empty( $type['bidirectional'] );
					?>
					<label class="nc-wizard-type-card<?php echo $is_selected ? ' selected' : ''; ?><?php echo $is_locked ? ' locked' : ''; ?>"
						<?php echo $is_locked ? 'title="Built-in type — always enabled"' : ''; ?>>
						<input type="checkbox"
							name="nc_wizard_types[]"
							value="<?php echo esc_attr( $type['slug'] ); ?>"
							<?php checked( $is_selected ); ?>
							<?php disabled( $is_locked ); ?>
							class="nc-wizard-type-checkbox" />
						<div class="nc-wizard-type-card-bar"></div>
						<div class="nc-wizard-type-card-content">
							<div class="nc-wizard-type-card-header">
								<h4 class="nc-headline-sm"><?php echo esc_html( $type['label'] ); ?></h4>
								<?php if ( $is_locked ) : ?>
									<span class="nc-badge nc-badge-active"><?php esc_html_e( 'Built-in', 'native-content-relationships' ); ?></span>
								<?php endif; ?>
							</div>
							<p class="nc-text-xs nc-text-muted">
								<?php
								printf(
									/* translators: 1: from type, 2: to type */
									esc_html__( '%1$s → %2$s', 'native-content-relationships' ),
									esc_html( $type['from_type'] ),
									esc_html( $type['to_type'] )
								);
								?>
							</p>
							<span class="nc-badge <?php echo $is_bidir ? 'nc-badge-type' : 'nc-badge-muted'; ?>">
								<?php echo $is_bidir ? esc_html__( 'Bidirectional', 'native-content-relationships' ) : esc_html__( 'One-way', 'native-content-relationships' ); ?>
							</span>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
			<div class="nc-wizard-step-footer">
				<button type="button" class="nc-btn nc-btn-secondary nc-wizard-add-type"
					id="nc-wizard-add-type">
					<span class="material-symbols-outlined" style="font-size:16px;">add</span>
					<?php esc_html_e( 'Add Custom Type', 'native-content-relationships' ); ?>
				</button>
			</div>
			<!-- Inline Custom Type Form -->
			<div class="nc-wizard-add-type-form" id="nc-wizard-add-type-form" style="display:none;">
				<div class="nc-card">
					<div class="nc-card-header">
						<h3 class="nc-headline-sm"><?php esc_html_e( 'New Relationship Type', 'native-content-relationships' ); ?></h3>
					</div>
					<div class="nc-card-body">
						<div class="nc-form-row">
							<label class="nc-form-label"><?php esc_html_e( 'Label', 'native-content-relationships' ); ?></label>
							<input type="text" class="nc-form-input" id="nc-new-type-label"
								placeholder="<?php esc_attr_e( 'e.g. Member Of', 'native-content-relationships' ); ?>" />
							<p class="nc-form-help"><?php esc_html_e( 'A human-readable name for this relationship.', 'native-content-relationships' ); ?></p>
						</div>
						<div class="nc-form-row-group">
							<div class="nc-form-row">
								<label class="nc-form-label"><?php esc_html_e( 'From', 'native-content-relationships' ); ?></label>
								<select class="nc-form-select" id="nc-new-type-from">
									<option value="post"><?php esc_html_e( 'Post', 'native-content-relationships' ); ?></option>
									<option value="user"><?php esc_html_e( 'User', 'native-content-relationships' ); ?></option>
									<option value="term"><?php esc_html_e( 'Term', 'native-content-relationships' ); ?></option>
								</select>
							</div>
							<div class="nc-form-row">
								<label class="nc-form-label"><?php esc_html_e( 'To', 'native-content-relationships' ); ?></label>
								<select class="nc-form-select" id="nc-new-type-to">
									<option value="post"><?php esc_html_e( 'Post', 'native-content-relationships' ); ?></option>
									<option value="user"><?php esc_html_e( 'User', 'native-content-relationships' ); ?></option>
									<option value="term"><?php esc_html_e( 'Term', 'native-content-relationships' ); ?></option>
								</select>
							</div>
						</div>
						<div class="nc-form-row">
							<label class="nc-form-checkbox-inline">
								<input type="checkbox" id="nc-new-type-bidirectional" />
								<span><?php esc_html_e( 'Bidirectional (two-way relationship)', 'native-content-relationships' ); ?></span>
							</label>
						</div>
						<div class="nc-form-actions">
							<button type="button" class="nc-btn nc-btn-secondary" id="nc-new-type-cancel">
								<?php esc_html_e( 'Cancel', 'native-content-relationships' ); ?>
							</button>
							<button type="button" class="nc-btn nc-btn-primary" id="nc-new-type-save">
								<span class="material-symbols-outlined" style="font-size:16px;">add</span>
								<?php esc_html_e( 'Add Type', 'native-content-relationships' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Presets step.
	 *
	 * @param array $state Current wizard state.
	 * @return void
	 */
	private function render_step_presets( $state ) {
		$presets    = $this->get_presets_for_js();
		$raw_sel   = isset( $state['selected_presets'] ) ? $state['selected_presets'] : array();
		$selected  = is_array( $raw_sel ) ? $raw_sel : array();
		?>
		<div class="nc-wizard-step-content" data-step-content="presets">
			<div class="nc-wizard-step-header">
				<h2 class="nc-headline-lg"><?php esc_html_e( 'Start with a Preset', 'native-content-relationships' ); ?></h2>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'Pre-built templates that set up common relationship patterns. You can skip this and configure manually.', 'native-content-relationships' ); ?>
				</p>
			</div>
			<div class="nc-wizard-preset-grid">
				<?php foreach ( $presets as $preset_key => $preset ) :
					$is_selected = in_array( $preset_key, $selected, true );
					?>
					<label class="nc-wizard-preset-card<?php echo $is_selected ? ' selected' : ''; ?>">
						<input type="checkbox"
							name="nc_wizard_presets[]"
							value="<?php echo esc_attr( $preset_key ); ?>"
							<?php checked( $is_selected ); ?>
							class="nc-wizard-preset-checkbox" />
						<div class="nc-wizard-preset-card-bar"></div>
						<div class="nc-wizard-preset-card-content">
							<h4 class="nc-headline-sm"><?php echo esc_html( $preset['label'] ); ?></h4>
							<p class="nc-text-xs nc-text-muted">
								<?php
								$type_count = count( $preset['types'] );
								printf(
									/* translators: %d: number of types */
									esc_html( _n( '%d type', '%d types', $type_count, 'native-content-relationships' ) ),
									$type_count
								);
								?>
							</p>
							<div class="nc-wizard-preset-types">
								<?php foreach ( $preset['types'] as $type ) : ?>
									<span class="nc-badge nc-badge-type"><?php echo esc_html( $type['label'] ); ?></span>
								<?php endforeach; ?>
							</div>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Post Types step.
	 *
	 * @param array $state Current wizard state.
	 * @return void
	 */
	private function render_step_post_types( $state ) {
		$post_types    = $this->get_post_types_for_js();
		$raw_enabled   = isset( $state['enabled_post_types'] ) ? $state['enabled_post_types'] : array();
		$enabled       = is_array( $raw_enabled ) ? $raw_enabled : array_keys( $post_types );
		?>
		<div class="nc-wizard-step-content" data-step-content="post-types">
			<div class="nc-wizard-step-header">
				<h2 class="nc-headline-lg"><?php esc_html_e( 'Enable Post Types', 'native-content-relationships' ); ?></h2>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'Choose which post types can have relationships. You can change this later in Settings.', 'native-content-relationships' ); ?>
				</p>
			</div>
			<div class="nc-wizard-type-grid">
				<?php foreach ( $post_types as $pt ) :
					$is_enabled = in_array( $pt['slug'], $enabled, true );
					?>
					<label class="nc-wizard-type-card<?php echo $is_enabled ? ' selected' : ''; ?>">
						<input type="checkbox"
							name="nc_wizard_post_types[]"
							value="<?php echo esc_attr( $pt['slug'] ); ?>"
							<?php checked( $is_enabled ); ?>
							class="nc-wizard-posttype-checkbox" />
						<div class="nc-wizard-type-card-bar"></div>
						<div class="nc-wizard-type-card-content">
							<div class="nc-wizard-type-card-header">
								<h4 class="nc-headline-sm"><?php echo esc_html( $pt['label'] ); ?></h4>
							</div>
							<p class="nc-text-xs nc-text-muted"><?php echo esc_html( $pt['slug'] ); ?></p>
						</div>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Review step.
	 *
	 * @param array $state Current wizard state.
	 * @return void
	 */
	private function render_step_review( $state ) {
		$raw_types      = isset( $state['selected_types'] ) ? $state['selected_types'] : array();
		$raw_presets    = isset( $state['selected_presets'] ) ? $state['selected_presets'] : array();
		$raw_post_types = isset( $state['enabled_post_types'] ) ? $state['enabled_post_types'] : array();
		$selected_types     = is_array( $raw_types ) ? $raw_types : array();
		$selected_presets   = is_array( $raw_presets ) ? $raw_presets : array();
		$enabled_post_types = is_array( $raw_post_types ) ? $raw_post_types : array();

		$all_types     = $this->get_types_for_js();
		$types_by_slug = array();
		foreach ( $all_types as $t ) {
			$types_by_slug[ $t['slug'] ] = $t;
		}
		$all_presets     = $this->get_presets_for_js();
		$presets_by_key  = array();
		foreach ( $all_presets as $k => $p ) {
			$presets_by_key[ $k ] = $p;
		}
		$all_pts     = $this->get_post_types_for_js();
		$pts_by_slug = array();
		foreach ( $all_pts as $p ) {
			$pts_by_slug[ $p['slug'] ] = $p;
		}
		?>
		<div class="nc-wizard-step-content" data-step-content="review">
			<div class="nc-wizard-step-header">
				<h2 class="nc-headline-lg"><?php esc_html_e( 'Review Your Setup', 'native-content-relationships' ); ?></h2>
				<p class="nc-text-sm nc-text-muted">
					<?php esc_html_e( 'Here\'s a summary of your configuration. You can go back to make changes.', 'native-content-relationships' ); ?>
				</p>
			</div>

			<!-- Summary Stats -->
			<div class="nc-wizard-review-stats">
				<div class="nc-wizard-review-stat">
					<div class="nc-wizard-review-stat-icon nc-kpi-icon-primary">
						<span class="material-symbols-outlined">category</span>
					</div>
					<div class="nc-wizard-review-stat-info">
						<span class="nc-wizard-review-stat-num"><?php echo esc_html( count( $selected_types ) ); ?></span>
						<span class="nc-wizard-review-stat-label"><?php esc_html_e( 'Types', 'native-content-relationships' ); ?></span>
					</div>
				</div>
				<div class="nc-wizard-review-stat">
					<div class="nc-wizard-review-stat-icon nc-kpi-icon-secondary">
						<span class="material-symbols-outlined">auto_awesome</span>
					</div>
					<div class="nc-wizard-review-stat-info">
						<span class="nc-wizard-review-stat-num"><?php echo esc_html( count( $selected_presets ) ); ?></span>
						<span class="nc-wizard-review-stat-label"><?php esc_html_e( 'Presets', 'native-content-relationships' ); ?></span>
					</div>
				</div>
				<div class="nc-wizard-review-stat">
					<div class="nc-wizard-review-stat-icon nc-kpi-icon-success">
						<span class="material-symbols-outlined">article</span>
					</div>
					<div class="nc-wizard-review-stat-info">
						<span class="nc-wizard-review-stat-num"><?php echo esc_html( count( $enabled_post_types ) ); ?></span>
						<span class="nc-wizard-review-stat-label"><?php esc_html_e( 'Post Types', 'native-content-relationships' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Types Section -->
			<div class="nc-wizard-review-section">
				<div class="nc-wizard-review-section-header">
					<div class="nc-wizard-review-section-title">
						<span class="material-symbols-outlined">category</span>
						<h3><?php esc_html_e( 'Relationship Types', 'native-content-relationships' ); ?></h3>
					</div>
					<button type="button" class="nc-btn nc-btn-ghost nc-wizard-goto-step" data-goto="types">
						<span class="material-symbols-outlined" style="font-size:16px;">edit</span>
						<?php esc_html_e( 'Edit', 'native-content-relationships' ); ?>
					</button>
				</div>
				<?php if ( empty( $selected_types ) ) : ?>
					<p class="nc-wizard-review-empty"><?php esc_html_e( 'No types selected.', 'native-content-relationships' ); ?></p>
				<?php else : ?>
					<div class="nc-wizard-review-chips">
						<?php foreach ( $selected_types as $type_slug ) :
							$type = isset( $types_by_slug[ $type_slug ] ) ? $types_by_slug[ $type_slug ] : null;
							if ( $type ) :
								?>
								<div class="nc-wizard-review-chip">
									<span class="nc-wizard-review-chip-label"><?php echo esc_html( $type['label'] ); ?></span>
									<span class="nc-wizard-review-chip-meta"><?php echo esc_html( $type['from_type'] . ' → ' . $type['to_type'] ); ?></span>
									<?php if ( ! empty( $type['bidirectional'] ) ) : ?>
										<span class="nc-badge nc-badge-type" style="font-size:10px;"><?php esc_html_e( '2-way', 'native-content-relationships' ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Presets Section -->
			<div class="nc-wizard-review-section">
				<div class="nc-wizard-review-section-header">
					<div class="nc-wizard-review-section-title">
						<span class="material-symbols-outlined">auto_awesome</span>
						<h3><?php esc_html_e( 'Presets', 'native-content-relationships' ); ?></h3>
					</div>
					<button type="button" class="nc-btn nc-btn-ghost nc-wizard-goto-step" data-goto="presets">
						<span class="material-symbols-outlined" style="font-size:16px;">edit</span>
						<?php esc_html_e( 'Edit', 'native-content-relationships' ); ?>
					</button>
				</div>
				<?php if ( empty( $selected_presets ) ) : ?>
					<p class="nc-wizard-review-empty"><?php esc_html_e( 'No presets selected.', 'native-content-relationships' ); ?></p>
				<?php else : ?>
					<div class="nc-wizard-review-chips">
						<?php foreach ( $selected_presets as $preset_key ) :
							$preset = isset( $presets_by_key[ $preset_key ] ) ? $presets_by_key[ $preset_key ] : null;
							if ( $preset ) :
								?>
								<div class="nc-wizard-review-chip">
									<span class="nc-wizard-review-chip-label"><?php echo esc_html( $preset['label'] ); ?></span>
									<span class="nc-wizard-review-chip-meta"><?php echo esc_html( count( $preset['types'] ) . ' ' . __( 'types', 'native-content-relationships' ) ); ?></span>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<!-- Post Types Section -->
			<div class="nc-wizard-review-section">
				<div class="nc-wizard-review-section-header">
					<div class="nc-wizard-review-section-title">
						<span class="material-symbols-outlined">article</span>
						<h3><?php esc_html_e( 'Post Types', 'native-content-relationships' ); ?></h3>
					</div>
					<button type="button" class="nc-btn nc-btn-ghost nc-wizard-goto-step" data-goto="post-types">
						<span class="material-symbols-outlined" style="font-size:16px;">edit</span>
						<?php esc_html_e( 'Edit', 'native-content-relationships' ); ?>
					</button>
				</div>
				<?php if ( empty( $enabled_post_types ) ) : ?>
					<p class="nc-wizard-review-empty"><?php esc_html_e( 'No post types enabled.', 'native-content-relationships' ); ?></p>
				<?php else : ?>
					<div class="nc-wizard-review-chips">
						<?php foreach ( $enabled_post_types as $pt_slug ) :
							$pt = isset( $pts_by_slug[ $pt_slug ] ) ? $pts_by_slug[ $pt_slug ] : null;
							if ( $pt ) :
								?>
								<div class="nc-wizard-review-chip">
									<span class="nc-wizard-review-chip-label"><?php echo esc_html( $pt['label'] ); ?></span>
									<span class="nc-wizard-review-chip-meta"><?php echo esc_html( $pt['slug'] ); ?></span>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the already-completed screen.
	 *
	 * @return void
	 */
	private function render_completed() {
		?>
		<div class="nc-stitch">
		<div class="nc-wizard-wrap nc-wizard-completed" id="nc-wizard">
			<div class="nc-wizard-main" style="max-width:640px;margin:0 auto;">
				<div class="nc-wizard-hero">
					<div class="nc-wizard-hero-icon nc-kpi-icon-primary">
						<span class="material-symbols-outlined" style="font-size:48px;">check_circle</span>
					</div>
					<h2 class="nc-headline-lg"><?php esc_html_e( 'Setup Complete!', 'native-content-relationships' ); ?></h2>
					<p class="nc-text-sm nc-text-muted" style="max-width:400px;margin:0 auto;">
						<?php esc_html_e( 'Your relationships are ready to use. Start connecting your content from any post editor.', 'native-content-relationships' ); ?>
					</p>
					<div class="nc-wizard-completed-actions" style="margin-top:24px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=naticore-settings' ) ); ?>"
							class="nc-btn nc-btn-primary">
							<span class="material-symbols-outlined" style="font-size:16px;">settings</span>
							<?php esc_html_e( 'Go to Settings', 'native-content-relationships' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>"
							class="nc-btn nc-btn-secondary">
							<?php esc_html_e( 'View Posts', 'native-content-relationships' ); ?>
						</a>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-wizard&restart=1' ), 'nc_wizard_restart' ) ); ?>"
							class="nc-btn nc-btn-ghost"
							onclick="return confirm('<?php esc_attr_e( 'This will reset your wizard progress. Continue?', 'native-content-relationships' ); ?>');">
							<span class="material-symbols-outlined" style="font-size:16px;">restart_alt</span>
							<?php esc_html_e( 'Restart Wizard', 'native-content-relationships' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Get the current wizard step from the URL or state.
	 *
	 * @return string
	 */
	private function get_current_step() {
		$step = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';
		if ( empty( $step ) || ! isset( $this->steps[ $step ] ) ) {
			$step = 'welcome';
		}
		return $step;
	}

	/**
	 * Get wizard state from the database.
	 *
	 * @return array
	 */
	private function get_state() {
		$state = get_option( self::STATE_KEY, array() );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * Get preset data formatted for JavaScript.
	 *
	 * @return array
	 */
	private function get_presets_for_js() {
		$raw    = NATICORE_Presets::get_presets();
		$result = array();

		foreach ( $raw as $key => $preset ) {
			$types = array();
			if ( ! empty( $preset['types'] ) ) {
				foreach ( $preset['types'] as $type_slug => $type_data ) {
					$types[] = array(
						'slug'         => $type_slug,
						'label'        => isset( $type_data['label'] ) ? $type_data['label'] : $type_slug,
						'from_type'    => isset( $type_data['from_type'] ) ? $type_data['from_type'] : 'post',
						'to_type'      => isset( $type_data['to_type'] ) ? $type_data['to_type'] : 'post',
						'bidirectional'=> ! empty( $type_data['bidirectional'] ),
					);
				}
			}
			$result[ $key ] = array(
				'label' => isset( $preset['name'] ) ? $preset['name'] : $key,
				'types' => $types,
			);
		}

		return $result;
	}

	/**
	 * Get relationship types formatted for JavaScript.
	 *
	 * @return array
	 */
	private function get_types_for_js() {
		$types_obj = new NATICORE_Relation_Types();
		$raw       = $types_obj->get_types();
		$result    = array();

		foreach ( $raw as $slug => $type ) {
			$result[] = array(
				'slug'          => $slug,
				'label'         => isset( $type['label'] ) ? $type['label'] : $slug,
				'from_type'     => isset( $type['from_type'] ) ? $type['from_type'] : 'post',
				'to_type'       => isset( $type['to_type'] ) ? $type['to_type'] : 'post',
				'bidirectional' => ! empty( $type['bidirectional'] ),
				'locked'        => ! empty( $type['locked'] ),
			);
		}

		return $result;
	}

	/**
	 * Get enabled post types formatted for JavaScript.
	 *
	 * @return array
	 */
	private function get_post_types_for_js() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$result     = array();

		foreach ( $post_types as $pt ) {
			$result[] = array(
				'slug'  => $pt->name,
				'label' => $pt->labels->singular_name,
			);
		}

		return $result;
	}

	/**
	 * Recursively sanitize an array of values.
	 *
	 * @param array $data The data to sanitize.
	 * @return array
	 */
	private function sanitize_recursive( $data ) {
		$clean = array();
		foreach ( $data as $key => $value ) {
			$key = sanitize_text_field( $key );
			if ( is_array( $value ) ) {
				$clean[ $key ] = $this->sanitize_recursive( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}
		return $clean;
	}

	/**
	 * AJAX handler: Save wizard state for a step.
	 *
	 * @return void
	 */
	public function ajax_save() {
		check_ajax_referer( 'nc_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$step  = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : '';
		$raw   = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
		$data  = is_array( $raw ) ? $this->sanitize_recursive( $raw ) : array();
		$state = $this->get_state();

		if ( ! isset( $this->steps[ $step ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid step.', 'native-content-relationships' ) ) );
		}

		// Merge step data into state.
		$state[ $step ] = $data;

		// Flatten selected types and presets for review step.
		if ( 'types' === $step && isset( $data['selected_types'] ) ) {
			$state['selected_types'] = $data['selected_types'];
		}
		if ( 'presets' === $step && isset( $data['selected_presets'] ) ) {
			$state['selected_presets'] = $data['selected_presets'];
		}
		if ( 'post-types' === $step && isset( $data['enabled_post_types'] ) ) {
			$state['enabled_post_types'] = $data['enabled_post_types'];
		}

		update_option( self::STATE_KEY, $state, false );

		wp_send_json_success( array( 'message' => __( 'State saved.', 'native-content-relationships' ) ) );
	}

	/**
	 * AJAX handler: Skip wizard (clear state).
	 *
	 * @return void
	 */
	public function ajax_skip() {
		check_ajax_referer( 'nc_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		delete_option( self::STATE_KEY );
		update_option( self::COMPLETED_KEY, true, false );

		wp_send_json_success( array( 'redirect' => admin_url( 'admin.php?page=naticore-settings' ) ) );
	}

	/**
	 * AJAX handler: Complete wizard — apply selected configuration.
	 *
	 * @return void
	 */
	public function ajax_complete() {
		check_ajax_referer( 'nc_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'native-content-relationships' ) ) );
		}

		$state = $this->get_state();

		// Apply enabled post types to settings.
		if ( ! empty( $state['enabled_post_types'] ) && is_array( $state['enabled_post_types'] ) ) {
			$settings = get_option( 'naticore_settings', array() );
			$settings['enabled_post_types'] = $state['enabled_post_types'];
			update_option( 'naticore_settings', $settings, false );
		}

		// Apply selected types (create any custom types that were checked).
		if ( ! empty( $state['selected_types'] ) && is_array( $state['selected_types'] ) ) {
			$existing  = NATICORE_Relation_Types::get_types();
			$existing_slugs = array_keys( $existing );
			$all_types = $this->get_types_for_js();

			foreach ( $state['selected_types'] as $type_slug ) {
				if ( ! in_array( $type_slug, $existing_slugs, true ) ) {
					// Find the type data from available types.
					$type_data = null;
					foreach ( $all_types as $t ) {
						if ( $t['slug'] === $type_slug ) {
							$type_data = $t;
							break;
						}
					}
					if ( $type_data ) {
						NATICORE_Relation_Types::register( $type_slug, array(
							'label'         => $type_data['label'],
							'from'          => $type_data['from_type'],
							'to'            => $type_data['to_type'],
							'bidirectional' => $type_data['bidirectional'],
						) );
					}
				}
			}
		}

		// Apply selected presets (activate preset types).
		if ( ! empty( $state['selected_presets'] ) && is_array( $state['selected_presets'] ) ) {
			$presets_obj = new NATICORE_Presets();
			$all_presets = $presets_obj->get_presets();
			$existing    = NATICORE_Relation_Types::get_types();
			$existing_slugs = array_keys( $existing );

			foreach ( $state['selected_presets'] as $preset_key ) {
				if ( isset( $all_presets[ $preset_key ] ) && ! empty( $all_presets[ $preset_key ]['types'] ) ) {
					foreach ( $all_presets[ $preset_key ]['types'] as $slug => $type_data ) {
						if ( $slug && ! in_array( $slug, $existing_slugs, true ) ) {
							NATICORE_Relation_Types::register( $slug, array(
								'label'         => $type_data['label'],
								'from'          => isset( $type_data['from_type'] ) ? $type_data['from_type'] : 'post',
								'to'            => isset( $type_data['to_type'] ) ? $type_data['to_type'] : 'post',
								'bidirectional' => ! empty( $type_data['bidirectional'] ),
							) );
							$existing_slugs[] = $slug;
						}
					}
				}
			}
		}

		// Mark wizard as completed.
		update_option( self::COMPLETED_KEY, true, false );
		delete_option( self::STATE_KEY );

		wp_send_json_success( array(
			'message'  => __( 'Setup complete!', 'native-content-relationships' ),
			'redirect' => admin_url( 'admin.php?page=naticore-wizard' ),
		) );
	}
}

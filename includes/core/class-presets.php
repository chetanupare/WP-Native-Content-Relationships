<?php
/**
 * Relationship Preset Templates
 *
 * @package Native_Content_Relationships
 * @since 1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Presets
 *
 * Provides predefined relationship templates for common use cases.
 */
class NATICORE_Presets {

	/**
	 * Get all available preset templates
	 *
	 * @return array
	 */
	public static function get_presets() {
		return array(
			'event_speaker'      => array(
				'name'        => __( 'Event ↔ Speaker', 'native-content-relationships' ),
				'description' => __( 'Connect events to their speakers, presenters, or panelists.', 'native-content-relationships' ),
				'icon'        => '🎤',
				'types'       => array(
					'event_has_speaker' => array(
						'label'          => __( 'Event → Speaker', 'native-content-relationships' ),
						'from_type'      => 'event',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'role', 'label' => __( 'Role', 'native-content-relationships' ), 'placeholder' => __( 'Keynote, Panelist, Workshop Lead', 'native-content-relationships' ) ),
							array( 'key' => 'session_time', 'label' => __( 'Session Time', 'native-content-relationships' ), 'placeholder' => __( '10:00 AM - 11:00 AM', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'course_instructor'  => array(
				'name'        => __( 'Course ↔ Instructor', 'native-content-relationships' ),
				'description' => __( 'Link courses to their instructors and teaching assistants.', 'native-content-relationships' ),
				'icon'        => '📚',
				'types'       => array(
					'course_has_instructor' => array(
						'label'          => __( 'Course → Instructor', 'native-content-relationships' ),
						'from_type'      => 'page',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'role', 'label' => __( 'Role', 'native-content-relationships' ), 'placeholder' => __( 'Lead Instructor, TA, Guest Lecturer', 'native-content-relationships' ) ),
							array( 'key' => 'sort_order', 'label' => __( 'Display Order', 'native-content-relationships' ), 'placeholder' => __( '1', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'product_brand'     => array(
				'name'        => __( 'Product ↔ Brand', 'native-content-relationships' ),
				'description' => __( 'Associate products with their brands or manufacturers.', 'native-content-relationships' ),
				'icon'        => '🏷️',
				'types'       => array(
					'product_has_brand' => array(
						'label'          => __( 'Product → Brand', 'native-content-relationships' ),
						'from_type'      => 'product',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'is_official', 'label' => __( 'Official Brand', 'native-content-relationships' ), 'placeholder' => __( 'Yes/No', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'job_skill'         => array(
				'name'        => __( 'Job ↔ Skill', 'native-content-relationships' ),
				'description' => __( 'Map jobs to required or preferred skills.', 'native-content-relationships' ),
				'icon'        => '💼',
				'types'       => array(
					'job_requires_skill' => array(
						'label'          => __( 'Job → Skill', 'native-content-relationships' ),
						'from_type'      => 'post',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'proficiency', 'label' => __( 'Proficiency Level', 'native-content-relationships' ), 'placeholder' => __( 'Required, Preferred, Nice-to-have', 'native-content-relationships' ) ),
							array( 'key' => 'years_experience', 'label' => __( 'Years Experience', 'native-content-relationships' ), 'placeholder' => __( '3+', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'candidate_job'     => array(
				'name'        => __( 'Candidate ↔ Job', 'native-content-relationships' ),
				'description' => __( 'Track job applications and candidate status.', 'native-content-relationships' ),
				'icon'        => '📋',
				'types'       => array(
					'candidate_applies_for' => array(
						'label'          => __( 'Candidate → Job', 'native-content-relationships' ),
						'from_type'      => 'post',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'status', 'label' => __( 'Application Status', 'native-content-relationships' ), 'placeholder' => __( 'Applied, Interviewing, Offered, Hired', 'native-content-relationships' ) ),
							array( 'key' => 'applied_date', 'label' => __( 'Applied Date', 'native-content-relationships' ), 'placeholder' => __( '2025-01-15', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'post_author'       => array(
				'name'        => __( 'Post ↔ Author', 'native-content-relationships' ),
				'description' => __( 'Assign multiple authors or contributors to posts.', 'native-content-relationships' ),
				'icon'        => '✍️',
				'types'       => array(
					'post_has_author' => array(
						'label'          => __( 'Post → Author', 'native-content-relationships' ),
						'from_type'      => 'post',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'role', 'label' => __( 'Author Role', 'native-content-relationships' ), 'placeholder' => __( 'Primary Author, Co-author, Editor', 'native-content-relationships' ) ),
							array( 'key' => 'sort_order', 'label' => __( 'Display Order', 'native-content-relationships' ), 'placeholder' => __( '1', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'portfolio_project' => array(
				'name'        => __( 'Portfolio ↔ Project', 'native-content-relationships' ),
				'description' => __( 'Showcase projects within portfolio items.', 'native-content-relationships' ),
				'icon'        => '🎨',
				'types'       => array(
					'portfolio_has_project' => array(
						'label'          => __( 'Portfolio → Project', 'native-content-relationships' ),
						'from_type'      => 'post',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'role', 'label' => __( 'Role', 'native-content-relationships' ), 'placeholder' => __( 'Lead Designer, Developer, PM', 'native-content-relationships' ) ),
							array( 'key' => 'contribution', 'label' => __( 'Contribution', 'native-content-relationships' ), 'placeholder' => __( 'UI Design, Backend API, Database', 'native-content-relationships' ) ),
						),
					),
				),
			),

			'series_content'    => array(
				'name'        => __( 'Series ↔ Content', 'native-content-relationships' ),
				'description' => __( 'Organize posts into series or learning paths.', 'native-content-relationships' ),
				'icon'        => '📖',
				'types'       => array(
					'series_has_content' => array(
						'label'          => __( 'Series → Content', 'native-content-relationships' ),
						'from_type'      => 'post',
						'to_type'        => 'post',
						'bidirectional'  => true,
						'meta_fields'    => array(
							array( 'key' => 'episode_number', 'label' => __( 'Episode Number', 'native-content-relationships' ), 'placeholder' => __( '1', 'native-content-relationships' ) ),
							array( 'key' => 'is_premium', 'label' => __( 'Premium Only', 'native-content-relationships' ), 'placeholder' => __( 'Yes/No', 'native-content-relationships' ) ),
						),
					),
				),
			),
		);
	}

	/**
	 * Install a preset template
	 *
	 * @param string $preset_key The preset key to install.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function install_preset( $preset_key ) {
		$presets = self::get_presets();

		if ( ! isset( $presets[ $preset_key ] ) ) {
			return new WP_Error( 'invalid_preset', __( 'Invalid preset template.', 'native-content-relationships' ) );
		}

		$preset = $presets[ $preset_key ];
		$installed = 0;

		foreach ( $preset['types'] as $type_key => $type_data ) {
			// Check if type already exists
			$existing = NATICORE_Relation_Types::get_type( $type_key );
			if ( $existing ) {
				continue;
			}

			$args = array(
				'name'             => $type_data['label'],
				'from_post_type'   => $type_data['from_type'],
				'to_post_type'     => $type_data['to_type'],
				'bidirectional'    => $type_data['bidirectional'],
				'meta_fields'      => $type_data['meta_fields'],
				'description'      => $preset['description'],
			);

			$result = NATICORE_Relation_Types::register( $type_key, $args );
			if ( ! is_wp_error( $result ) ) {
				$installed++;
			}
		}

		return $installed > 0;
	}

	/**
	 * Render the presets admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$preset_key = isset( $_GET['preset'] ) ? sanitize_text_field( wp_unslash( $_GET['preset'] ) ) : '';

		if ( $preset_key && isset( $_GET['install_preset'] ) && check_admin_referer( 'naticore_install_preset_' . $preset_key ) ) {
			$result = self::install_preset( $preset_key );
			if ( is_wp_error( $result ) ) {
				add_settings_error( 'naticore', 'install_error', $result->get_error_message(), 'error' );
			} else {
				add_settings_error( 'naticore', 'installed', __( 'Preset template installed successfully!', 'native-content-relationships' ), 'success' );
			}
		}

		$presets    = self::get_presets();
		$installed  = NATICORE_Relation_Types::get_types();
		$stitch_admin = NATICORE_Stitch_Admin::get_instance();
		$stitch_admin->render_wrapper_start( 'naticore-tools' );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Templates', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Quickly set up common relationship patterns. Each template creates pre-configured relationship types with metadata fields.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<div class="nc-grid-12">
			<?php foreach ( $presets as $key => $preset ) : ?>
				<?php
				$already_installed = true;
				foreach ( $preset['types'] as $type_key => $type_data ) {
					if ( ! isset( $installed[ $type_key ] ) ) {
						$already_installed = false;
						break;
					}
				}
				?>
				<div class="nc-col-6">
					<div class="nc-card" style="height:100%;">
						<div class="nc-card-body nc-flex nc-flex-col nc-gap-md">
							<div class="nc-flex nc-items-center nc-gap-sm">
								<span class="material-symbols-outlined" style="font-size:24px;color:var(--nc-primary);"><?php echo esc_html( $preset['icon'] ); ?></span>
								<h3 style="margin:0;font-size:16px;font-weight:600;"><?php echo esc_html( $preset['name'] ); ?></h3>
							</div>
							<p class="nc-text-sm nc-text-muted" style="margin:0;">
								<?php echo esc_html( $preset['description'] ); ?>
							</p>

							<div style="margin-top:auto;padding-top:16px;">
								<?php if ( $already_installed ) : ?>
									<span class="nc-btn nc-btn-secondary" style="opacity: 0.6; cursor: not-allowed; display: inline-flex; justify-content: center; width: 100%;">
										<?php esc_html_e( 'Already Installed', 'native-content-relationships' ); ?>
									</span>
								<?php else : ?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-presets&preset=' . $key . '&install_preset=1' ), 'naticore_install_preset_' . $key ) ); ?>" class="nc-btn nc-btn-primary" style="display: inline-flex; justify-content: center; width: 100%;" onclick="return confirm('<?php esc_attr_e( 'Install this template?', 'native-content-relationships' ); ?>');">
										<?php esc_html_e( 'Install Template', 'native-content-relationships' ); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		$stitch_admin->render_wrapper_end();
	}

	/**
	 * Add admin menu
	 */
	public static function add_admin_menu() {
		add_submenu_page(
			'naticore-hidden',
			__( 'Relationship Templates', 'native-content-relationships' ),
			__( 'Relationship Templates', 'native-content-relationships' ),
			'manage_options',
			'naticore-presets',
			array( __CLASS__, 'render_admin_page' )
		);
	}
}

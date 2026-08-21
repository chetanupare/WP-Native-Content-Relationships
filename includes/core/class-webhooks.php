<?php
/**
 * Relationship Webhooks
 *
 * @package Native_Content_Relationships
 * @since 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class NATICORE_Webhooks
 *
 * Fires webhook events for relationship changes.
 */
class NATICORE_Webhooks {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'naticore_relation_added', array( $this, 'fire_created' ), 99, 4 );
		add_action( 'naticore_relation_removed', array( $this, 'fire_deleted' ), 99, 3 );
		add_action( 'naticore_relation_meta_updated', array( $this, 'fire_updated' ), 99, 3 );
	}

	/**
	 * Get all registered webhooks
	 *
	 * @return array
	 */
	public static function get_webhooks() {
		return get_option( 'ncr_webhooks', array() );
	}

	/**
	 * Save webhooks
	 *
	 * @param array $webhooks Webhooks array.
	 */
	public static function save_webhooks( $webhooks ) {
		update_option( 'ncr_webhooks', $webhooks );
	}

	/**
	 * Add a webhook
	 *
	 * @param string $url     Webhook URL.
	 * @param array  $events  Events to listen for.
	 * @param string $secret  Optional secret for HMAC verification.
	 * @return int Webhook ID.
	 */
	public static function add_webhook( $url, $events = array(), $secret = '' ) {
		$webhooks      = self::get_webhooks();
		$id            = wp_generate_password( 12, false );
		$webhooks[$id] = array(
			'id'        => $id,
			'url'       => $url,
			'events'    => $events,
			'secret'    => $secret,
			'active'    => true,
			'created'   => current_time( 'mysql' ),
		);
		self::save_webhooks( $webhooks );
		return $id;
	}

	/**
	 * Remove a webhook
	 *
	 * @param string $id Webhook ID.
	 */
	public static function remove_webhook( $id ) {
		$webhooks = self::get_webhooks();
		unset( $webhooks[ $id ] );
		self::save_webhooks( $webhooks );
	}

	/**
	 * Fire created event
	 *
	 * @param int    $relation_id Relation ID.
	 * @param int    $from_id     Source post ID.
	 * @param int    $to_id       Target post ID.
	 * @param string $type        Relationship type.
	 */
	public function fire_created( $relation_id, $from_id, $to_id, $type ) {
		$this->fire_event( 'ncr_relationship_created', array(
			'relation_id' => $relation_id,
			'from_id'     => $from_id,
			'to_id'       => $to_id,
			'type'        => $type,
			'timestamp'   => current_time( 'c' ),
		) );
	}

	/**
	 * Fire deleted event
	 *
	 * @param int    $from_id Source post ID.
	 * @param int    $to_id   Target post ID.
	 * @param string $type    Relationship type.
	 */
	public function fire_deleted( $from_id, $to_id, $type ) {
		$this->fire_event( 'ncr_relationship_deleted', array(
			'from_id'   => $from_id,
			'to_id'     => $to_id,
			'type'      => $type,
			'timestamp' => current_time( 'c' ),
		) );
	}

	/**
	 * Fire updated event
	 *
	 * @param int    $relation_id Relation ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $meta_value  Meta value.
	 */
	public function fire_updated( $relation_id, $meta_key, $meta_value ) {
		$this->fire_event( 'ncr_relationship_updated', array(
			'relation_id' => $relation_id,
			'meta_key'    => $meta_key,
			'meta_value'  => $meta_value,
			'timestamp'   => current_time( 'c' ),
		) );
	}

	/**
	 * Fire a webhook event
	 *
	 * @param string $event Event name.
	 * @param array  $data  Event data.
	 */
	private function fire_event( $event, $data ) {
		$webhooks = self::get_webhooks();

		foreach ( $webhooks as $webhook ) {
			if ( ! $webhook['active'] ) {
				continue;
			}

			if ( ! empty( $webhook['events'] ) && ! in_array( $event, $webhook['events'], true ) ) {
				continue;
			}

			$this->send_webhook( $webhook, $event, $data );
		}
	}

	/**
	 * Send a webhook
	 *
	 * @param array  $webhook Webhook config.
	 * @param string $event   Event name.
	 * @param array  $data    Event data.
	 */
	private function send_webhook( $webhook, $event, $data ) {
		$payload = wp_json_encode( array(
			'event' => $event,
			'data'  => $data,
		) );

		$headers = array(
			'Content-Type'  => 'application/json',
			'X-NCR-Event'   => $event,
			'X-NCR-Delivery' => wp_generate_password( 24, false ),
		);

		if ( ! empty( $webhook['secret'] ) ) {
			$signature = hash_hmac( 'sha256', $payload, $webhook['secret'] );
			$headers['X-NCR-Signature'] = 'sha256=' . $signature;
		}

		wp_remote_post( $webhook['url'], array(
			'headers' => $headers,
			'body'    => $payload,
			'timeout' => 30,
		) );
	}

	/**
	 * Render admin page
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle actions
		if ( isset( $_POST['ncr_add_webhook'] ) && check_admin_referer( 'ncr_webhook_save' ) ) {
			$url    = esc_url_raw( wp_unslash( $_POST['webhook_url'] ?? '' ) );
			$events = array_map( 'sanitize_text_field', $_POST['webhook_events'] ?? array() );
			$secret = sanitize_text_field( wp_unslash( $_POST['webhook_secret'] ?? '' ) );

			if ( $url ) {
				self::add_webhook( $url, $events, $secret );
				add_settings_error( 'ncr', 'saved', __( 'Webhook added.', 'native-content-relationships' ), 'success' );
			}
		}

		if ( isset( $_GET['delete_webhook'] ) && check_admin_referer( 'ncr_delete_webhook_' . $_GET['delete_webhook'] ) ) {
			self::remove_webhook( sanitize_text_field( wp_unslash( $_GET['delete_webhook'] ) ) );
			add_settings_error( 'ncr', 'deleted', __( 'Webhook deleted.', 'native-content-relationships' ), 'success' );
		}

		$webhooks  = self::get_webhooks();
		$all_events = array(
			'ncr_relationship_created' => __( 'Relationship Created', 'native-content-relationships' ),
			'ncr_relationship_updated' => __( 'Relationship Updated', 'native-content-relationships' ),
			'ncr_relationship_deleted' => __( 'Relationship Deleted', 'native-content-relationships' ),
		);

		settings_errors( 'ncr' );

		$stitch_admin = NATICORE_Stitch_Admin::get_instance();
		$stitch_admin->render_wrapper_start( 'naticore-tools' );
		?>
		<div class="nc-mb-lg">
			<h1 style="font-size:24px;font-weight:600;line-height:32px;letter-spacing:-0.02em;color:var(--nc-on-surface);margin:0 0 4px 0;">
				<?php esc_html_e( 'Relationship Webhooks', 'native-content-relationships' ); ?>
			</h1>
			<p class="nc-text-sm nc-text-muted">
				<?php esc_html_e( 'Send HTTP POST requests to external services when relationships change.', 'native-content-relationships' ); ?>
			</p>
		</div>

		<div class="nc-grid-12">
			<!-- Add Webhook -->
			<div class="nc-col-12 nc-card nc-mb-lg">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Add Webhook', 'native-content-relationships' ); ?></h3>
				</div>
				<div class="nc-card-body">
					<form method="post">
						<?php wp_nonce_field( 'ncr_webhook_save' ); ?>
						<div class="nc-flex nc-flex-col nc-gap-md" style="max-width: 600px;">
							
							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="webhook_url"><?php esc_html_e( 'URL', 'native-content-relationships' ); ?></label>
								</div>
								<div style="flex: 1;">
									<input type="url" name="webhook_url" id="webhook_url" class="nc-input" required placeholder="https://example.com/webhook">
								</div>
							</div>
							
							<div class="nc-setting-row" style="align-items: flex-start;">
								<div style="flex-basis: 30%;">
									<span class="nc-font-semibold"><?php esc_html_e( 'Events', 'native-content-relationships' ); ?></span>
								</div>
								<div style="flex: 1; display:flex; flex-direction:column; gap:8px;">
									<?php foreach ( $all_events as $key => $label ) : ?>
										<label style="display: flex; align-items: center; gap: 8px;">
											<input type="checkbox" name="webhook_events[]" value="<?php echo esc_attr( $key ); ?>" checked>
											<span class="nc-text-sm"><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
							
							<div class="nc-setting-row">
								<div style="flex-basis: 30%;">
									<label class="nc-font-semibold" for="webhook_secret"><?php esc_html_e( 'Secret', 'native-content-relationships' ); ?></label>
									<p class="nc-text-xs nc-text-muted" style="margin-top:4px;"><?php esc_html_e( '(Optional)', 'native-content-relationships' ); ?></p>
								</div>
								<div style="flex: 1;">
									<input type="text" name="webhook_secret" id="webhook_secret" class="nc-input" placeholder="<?php esc_attr_e( 'For HMAC signature verification', 'native-content-relationships' ); ?>">
								</div>
							</div>

							<div>
								<button type="submit" name="ncr_add_webhook" class="nc-btn nc-btn-primary">
									<?php esc_html_e( 'Add Webhook', 'native-content-relationships' ); ?>
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<!-- Registered Webhooks -->
			<?php if ( ! empty( $webhooks ) ) : ?>
			<div class="nc-col-12 nc-card">
				<div class="nc-card-header">
					<h3><?php esc_html_e( 'Registered Webhooks', 'native-content-relationships' ); ?></h3>
				</div>
				<div style="overflow-x:auto;">
					<div class="nc-table-responsive">
						<table class="nc-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'URL', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Events', 'native-content-relationships' ); ?></th>
									<th><?php esc_html_e( 'Status', 'native-content-relationships' ); ?></th>
									<th style="text-align:right;"><?php esc_html_e( 'Action', 'native-content-relationships' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $webhooks as $wh ) : ?>
									<tr>
										<td class="nc-font-mono nc-text-sm" style="color:var(--nc-primary);"><?php echo esc_html( $wh['url'] ); ?></td>
										<td class="nc-text-sm"><?php echo esc_html( implode( ', ', $wh['events'] ) ); ?></td>
										<td>
											<span class="nc-badge <?php echo $wh['active'] ? 'nc-badge-success' : 'nc-badge-error'; ?>">
												<?php echo $wh['active'] ? __( 'Active', 'native-content-relationships' ) : __( 'Inactive', 'native-content-relationships' ); ?>
											</span>
										</td>
										<td style="text-align:right;">
											<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=naticore-webhooks&delete_webhook=' . $wh['id'] ), 'ncr_delete_webhook_' . $wh['id'] ) ); ?>" class="nc-text-error" style="text-decoration:none;font-weight:500;" onclick="return confirm('<?php esc_attr_e( 'Delete this webhook?', 'native-content-relationships' ); ?>');">
												<?php esc_html_e( 'Delete', 'native-content-relationships' ); ?>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<?php endif; ?>
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
			__( 'Relationship Webhooks', 'native-content-relationships' ),
			__( 'Relationship Webhooks', 'native-content-relationships' ),
			'manage_options',
			'naticore-webhooks',
			array( __CLASS__, 'render_admin_page' )
		);
	}
}

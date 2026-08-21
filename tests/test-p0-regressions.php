<?php
/**
 * P0 Regression Tests — Phase 4 Security & Data Integrity Fixes
 *
 * Covers:
 *   P0 #1: AJAX handlers require edit_post capability
 *   P0 #2: Bidirectional cleanup on object deletion
 *   P0 #3: Targeted cache invalidation (no nuclear flush)
 *   P0 #4: Unified cache group (naticore_relationships)
 *   P0 #5: NATICORE_VERSION matches header version
 *   P0 #6: Cloning endpoint verifies nonce
 *   P0 #7: Revision history writes and queries post meta correctly
 *
 * @package Native_Content_Relationships
 * @group p0-regression
 */

/**
 * P0 #1: AJAX handlers must enforce edit_post capability.
 */
class Test_P0_AJAX_Capability_Checks extends WP_UnitTestCase {

	/**
	 * Verify the AJAX add handler calls current_user_can('edit_post').
	 *
	 * When a subscriber (no edit_posts) fires naticore_add_relation,
	 * the handler should bail with a permission error before touching the DB.
	 */
	public function test_ajax_add_relation_rejects_unauthorized_user() {
		// Create a subscriber (cannot edit posts).
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		// Simulate a valid AJAX request.
		$_POST['nonce']         = wp_create_nonce( 'naticore_ajax' );
		$_POST['from_id']       = $this->factory()->post->create();
		$_POST['to_id']         = $this->factory()->post->create();
		$_POST['relation_type'] = 'related_to';

		$admin = new NATICORE_Admin();
		ob_start();
		$admin->ajax_add_relation();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"success":false', $output,
			'P0 #1: ajax_add_relation must reject users without edit_post capability.' );
	}

	/**
	 * Verify the AJAX remove handler calls current_user_can('edit_post').
	 */
	public function test_ajax_remove_relation_rejects_unauthorized_user() {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		// Create a real relation so the handler has something to operate on.
		NATICORE_API::add_relation( $from_id, $to_id, 'related_to' );

		$_POST['nonce']         = wp_create_nonce( 'naticore_ajax' );
		$_POST['from_id']       = $from_id;
		$_POST['to_id']         = $to_id;
		$_POST['relation_type'] = 'related_to';

		$admin = new NATICORE_Admin();
		ob_start();
		$admin->ajax_remove_relation();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"success":false', $output,
			'P0 #1: ajax_remove_relation must reject users without edit_post capability.' );
	}

	/**
	 * Verify the AJAX save-meta handler calls current_user_can('edit_post').
	 */
	public function test_ajax_save_meta_rejects_unauthorized_user() {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		$rel_id = NATICORE_API::add_relation( $from_id, $to_id, 'related_to' );
		$this->assertNotWPError( $rel_id );

		$_POST['nonce']       = wp_create_nonce( 'naticore_ajax' );
		$_POST['relation_id'] = $rel_id;
		$_POST['meta_key']    = 'note';
		$_POST['meta_value']  = 'test';

		$admin = new NATICORE_Admin();
		ob_start();
		$admin->ajax_save_relation_meta();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"success":false', $output,
			'P0 #1: ajax_save_relation_meta must reject users without edit_post capability.' );
	}

	/**
	 * Verify status change handler calls current_user_can('edit_post').
	 */
	public function test_ajax_change_status_rejects_unauthorized_user() {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		$rel_id = NATICORE_API::add_relation( $from_id, $to_id, 'related_to' );
		$this->assertNotWPError( $rel_id );

		$_POST['nonce']       = wp_create_nonce( 'naticore_ajax' );
		$_POST['relation_id'] = $rel_id;
		$_POST['new_status']  = 'archived';

		ob_start();
		NATICORE_Status::ajax_change_status();
		$output = ob_get_clean();

		$this->assertStringContainsString( '"success":false', $output,
			'P0 #1: ajax_change_status must reject users without edit_post capability.' );
	}
}

/**
 * P0 #2: Deleting a post must clean up bidirectional reverse rows.
 */
class Test_P0_Bidirectional_Cleanup extends WP_UnitTestCase {

	/**
	 * When post B is deleted, the reverse row (from_id=B → to_id=A)
	 * must also be removed.
	 */
	public function test_cleanup_removes_reverse_rows() {
		global $wpdb;
		$table = $wpdb->prefix . 'content_relations';

		$post_a = $this->factory()->post->create();
		$post_b = $this->factory()->post->create();

		// Create a bidirectional relationship.
		// Forward: A → B
		NATICORE_API::add_relation( $post_a, $post_b, 'related_to' );
		// Reverse: B → A (simulates bidirectional sync).
		NATICORE_API::add_relation( $post_b, $post_a, 'related_to' );

		// Verify both rows exist.
		$count_before = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table}` WHERE from_id = %d OR to_id = %d",
			$post_b, $post_b
		) );
		$this->assertGreaterThanOrEqual( 2, (int) $count_before,
			'Pre-condition: both forward and reverse rows must exist.' );

		// Delete post B — triggers cleanup.
		wp_delete_post( $post_b, true );

		// Verify ALL rows involving post B are gone.
		$count_after = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$table}` WHERE from_id = %d OR (to_id = %d AND to_type = 'post')",
			$post_b, $post_b
		) );
		$this->assertEquals( 0, (int) $count_after,
			'P0 #2: All rows involving deleted post must be removed, including reverse rows.' );
	}
}

/**
 * P0 #3 & #4: Cache invalidation uses targeted deletion, not nuclear flush.
 * Both class-cache.php and class-api.php use the same group.
 */
class Test_P0_Cache_Fixes extends WP_UnitTestCase {

	/**
	 * Verify NATICORE_Cache::GROUP matches the group used in class-api.php.
	 */
	public function test_cache_groups_are_unified() {
		$this->assertEquals( 'naticore_relationships', NATICORE_Cache::GROUP,
			'P0 #4: NATICORE_Cache::GROUP must be "naticore_relationships" to match class-api.php.' );
	}

	/**
	 * Verify invalidate_post() does not call wp_cache_flush_group().
	 */
	public function test_invalidate_post_does_not_flush_group() {
		// Reflect on the class to inspect the method body.
		$ref = new ReflectionMethod( 'NATICORE_Cache', 'invalidate_post' );
		$file = $ref->getFileName();
		$start = $ref->getStartLine();
		$end   = $ref->getEndLine();
		$lines = file( $file );
		$method_body = implode( '', array_slice( $lines, $start - 1, $end - $start + 1 ) );

		$this->assertStringNotContainsString( 'wp_cache_flush_group', $method_body,
			'P0 #3: invalidate_post() must not call wp_cache_flush_group().' );
	}

	/**
	 * Verify invalidate_post() deletes known API cache key patterns.
	 */
	public function test_invalidate_post_deletes_api_keys() {
		$ref = new ReflectionMethod( 'NATICORE_Cache', 'invalidate_post' );
		$file = $ref->getFileName();
		$start = $ref->getStartLine();
		$end   = $ref->getEndLine();
		$lines = file( $file );
		$method_body = implode( '', array_slice( $lines, $start - 1, $end - $start + 1 ) );

		$this->assertStringContainsString( 'naticore_get_related_', $method_body,
			'P0 #3: invalidate_post() must delete API cache keys (naticore_get_related_...).' );
		$this->assertStringContainsString( 'naticore_exists_', $method_body,
			'P0 #3: invalidate_post() must delete existence check keys.' );
	}
}

/**
 * P0 #5: NATICORE_VERSION constant must match plugin header version.
 */
class Test_P0_Version_Constant extends WP_UnitTestCase {

	/**
	 * Verify the version constant matches the header.
	 */
	public function test_version_constant_matches_header() {
		$this->assertTrue( defined( 'NATICORE_VERSION' ), 'NATICORE_VERSION must be defined.' );
		$this->assertEquals( '1.4.1', NATICORE_VERSION,
			'P0 #5: NATICORE_VERSION must be 1.4.1 to match the plugin header.' );
	}

	/**
	 * Verify plugin header version matches the constant.
	 */
	public function test_plugin_header_version() {
		$plugin_data = file_get_contents( NATICORE_PLUGIN_DIR . 'native-content-relationships.php' );
		preg_match( '/^[ \t\/*#@]*Version:\s*(.*)$/im', $plugin_data, $matches );

		$this->assertNotEmpty( $matches, 'Plugin header must contain a Version tag.' );
		$this->assertEquals( '1.4.1', $matches[1],
			'P0 #5: Plugin header version must be 1.4.1.' );
	}
}

/**
 * P0 #6: Cloning endpoint must verify the nonce.
 */
class Test_P0_Cloning_CSRF extends WP_UnitTestCase {

	/**
	 * Verify handle_clone() calls check_admin_referer.
	 */
	public function test_handle_clone_verifies_nonce() {
		$ref = new ReflectionMethod( 'NATICORE_Cloning', 'handle_clone' );
		$file = $ref->getFileName();
		$start = $ref->getStartLine();
		$end   = $ref->getEndLine();
		$lines = file( $file );
		$method_body = implode( '', array_slice( $lines, $start - 1, $end - $start + 1 ) );

		$this->assertStringContainsString( 'check_admin_referer', $method_body,
			'P0 #6: handle_clone() must call check_admin_referer() to verify the nonce.' );
	}

	/**
	 * Verify the clone action URL includes a nonce.
	 */
	public function test_clone_action_url_includes_nonce() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$post_id = $this->factory()->post->create();
		NATICORE_API::add_relation( $post_id, $this->factory()->post->create(), 'related_to' );

		$actions = NATICORE_Cloning::add_clone_action( array(), get_post( $post_id ) );

		$this->assertArrayHasKey( 'ncr_clone', $actions, 'Clone action must be present.' );
		$this->assertStringContainsString( '_wpnonce', $actions['ncr_clone'],
			'P0 #6: Clone action URL must contain a nonce parameter.' );
	}
}

/**
 * P0 #7: Revision history writes post meta and get_history() finds it.
 */
class Test_P0_Revision_History extends WP_UnitTestCase {

	/**
	 * Verify log() creates the expected post meta rows.
	 */
	public function test_log_creates_post_meta() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		$log_id = NATICORE_Revision_History::log( 'added', $from_id, $to_id, 'related_to' );

		$this->assertNotFalse( $log_id, 'log() must return a valid post ID.' );

		$this->assertEquals( $from_id, get_post_meta( $log_id, '_ncr_from_id', true ),
			'P0 #7: log() must set _ncr_from_id meta.' );
		$this->assertEquals( $to_id, get_post_meta( $log_id, '_ncr_to_id', true ),
			'P0 #7: log() must set _ncr_to_id meta.' );
		$this->assertEquals( 'related_to', get_post_meta( $log_id, '_ncr_type', true ),
			'P0 #7: log() must set _ncr_type meta.' );
		$this->assertEquals( $admin_id, get_post_meta( $log_id, '_ncr_user_id', true ),
			'P0 #7: log() must set _ncr_user_id meta.' );
		$this->assertEquals( 'added', get_post_meta( $log_id, '_ncr_action', true ),
			'P0 #7: log() must set _ncr_action meta.' );
	}

	/**
	 * Verify get_history() returns entries that were logged.
	 */
	public function test_get_history_finds_logged_entries() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		$log_id = NATICORE_Revision_History::log( 'added', $from_id, $to_id, 'related_to' );
		$this->assertNotFalse( $log_id );

		$history = NATICORE_Revision_History::get_history( $from_id );

		$this->assertNotEmpty( $history, 'P0 #7: get_history() must find logged entries for the from_id.' );
		$this->assertEquals( 'added', $history[0]['action'] );
		$this->assertEquals( $from_id, $history[0]['from_id'] );
		$this->assertEquals( $to_id, $history[0]['to_id'] );
	}

	/**
	 * Verify get_history() also finds entries where the post is the target.
	 */
	public function test_get_history_finds_reverse_entries() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		NATICORE_Revision_History::log( 'added', $from_id, $to_id, 'related_to' );

		// Query by the target post.
		$history = NATICORE_Revision_History::get_history( $to_id );

		$this->assertNotEmpty( $history, 'P0 #7: get_history() must find entries where the post is the to_id.' );
	}

	/**
	 * Verify get_all_history() filters by from_id correctly.
	 */
	public function test_get_all_history_filters_by_from_id() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		NATICORE_Revision_History::log( 'added', $from_id, $to_id, 'related_to' );

		$result = NATICORE_Revision_History::get_all_history( array( 'from_id' => $from_id ) );

		$this->assertNotEmpty( $result['logs'], 'P0 #7: get_all_history() must find entries by from_id.' );
	}

	/**
	 * Verify remove logs are also recorded with meta.
	 */
	public function test_log_remove_creates_meta() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$from_id = $this->factory()->post->create();
		$to_id   = $this->factory()->post->create();

		$log_id = NATICORE_Revision_History::log( 'removed', $from_id, $to_id, 'related_to' );

		$this->assertNotFalse( $log_id );
		$this->assertEquals( 'removed', get_post_meta( $log_id, '_ncr_action', true ) );
	}
}

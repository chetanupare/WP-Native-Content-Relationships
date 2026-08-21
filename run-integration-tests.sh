#!/bin/bash
# Integration tests for WP Native Content Relationships 1.4.0
echo "=== Phase 6 Integration Tests ==="
echo ""

# Test 1: CRUD
echo "--- Test 1: CRUD ---"
POST_A=$(wp post create --post_title="Test A" --post_status=publish --porcelain 2>&1)
POST_B=$(wp post create --post_title="Test B" --post_status=publish --porcelain 2>&1)
echo "Posts: A=$POST_A B=$POST_B"
wp eval "echo wp_add_relation($POST_A, $POST_B, 'related_to');" 2>&1
echo "is_related: $(wp eval "var_export(wp_is_related($POST_A, $POST_B, 'related_to'), true);" 2>&1)"
echo "get_related: $(wp eval "print_r(wp_get_related($POST_A, 'related_to'), true);" 2>&1)"
wp eval "wp_remove_relation($POST_A, $POST_B, 'related_to');" 2>&1
echo "after_remove: $(wp eval "var_export(wp_is_related($POST_A, $POST_B, 'related_to'), true);" 2>&1)"
echo ""

# Test 2: Bidirectional
echo "--- Test 2: Bidirectional ---"
POST_C=$(wp post create --post_title="Test C" --post_status=publish --porcelain 2>&1)
POST_D=$(wp post create --post_title="Test D" --post_status=publish --porcelain 2>&1)
wp eval "wp_add_relation($POST_C, $POST_D, 'authored_by', 'both');" 2>&1
echo "C->D: $(wp eval "var_export(wp_is_related($POST_C, $POST_D), true);" 2>&1)"
echo "D->C: $(wp eval "var_export(wp_is_related($POST_D, $POST_C), true);" 2>&1)"
wp eval "wp_remove_relation($POST_C, $POST_D, 'authored_by');" 2>&1
echo "after_remove C->D: $(wp eval "var_export(wp_is_related($POST_C, $POST_D), true);" 2>&1)"
echo ""

# Test 3: Duplicate Prevention
echo "--- Test 3: Duplicate ---"
POST_E=$(wp post create --post_title="Test E" --post_status=publish --porcelain 2>&1)
POST_F=$(wp post create --post_title="Test F" --post_status=publish --porcelain 2>&1)
wp eval "echo wp_add_relation($POST_E, $POST_F, 'related_to');" 2>&1
DUP=$(wp eval "echo wp_add_relation($POST_E, $POST_F, 'related_to');" 2>&1)
echo "Duplicate add: $DUP"
wp eval "wp_remove_relation($POST_E, $POST_F, 'related_to');" 2>&1
echo ""

# Test 4: Post Deletion Cascade
echo "--- Test 4: Cascade ---"
POST_G=$(wp post create --post_title="Test G" --post_status=publish --porcelain 2>&1)
POST_H=$(wp post create --post_title="Test H" --post_status=publish --porcelain 2>&1)
wp eval "wp_add_relation($POST_G, $POST_H, 'related_to');" 2>&1
echo "before_delete: $(wp eval "global \$wpdb; echo \$wpdb->get_var(\$wpdb->prepare('SELECT COUNT(*) FROM {\$wpdb->prefix}content_relations WHERE from_id = %d OR to_id = %d', $POST_G, $POST_G));" 2>&1)"
wp post delete $POST_G --force 2>&1
echo "after_delete: $(wp eval "global \$wpdb; echo \$wpdb->get_var(\$wpdb->prepare('SELECT COUNT(*) FROM {\$wpdb->prefix}content_relations WHERE from_id = %d OR to_id = %d', $POST_G, $POST_G));" 2>&1)"
echo ""

# Test 5: REST API
echo "--- Test 5: REST ---"
REST_NONCE=$(wp eval "echo wp_create_nonce('wp_rest');" 2>&1)
echo "REST nonce: $REST_NONCE"
# Test REST endpoint exists
wp eval "\$r = rest_do_request(new WP_REST_Request('GET', '/naticore/v1/relations')); echo \$r->get_status();" 2>&1
echo ""

# Test 6: Cache
echo "--- Test 6: Cache ---"
wp eval "
wp_add_relation($POST_A, $POST_B, 'related_to');
wp_cache_delete('naticore_get_related_post_' . $POST_A, 'naticore_relationships');
\$r = wp_get_related($POST_A);
\$c = wp_cache_get('naticore_get_related_post_' . $POST_A, 'naticore_relationships');
echo 'cache_populated: ' . var_export(\$c !== false, true);
wp_remove_relation($POST_A, $POST_B, 'related_to');
" 2>&1
echo ""

# Test 7: Version Check
echo "--- Test 7: Versions ---"
echo "VERSION: $(wp eval "echo NATICORE_VERSION;" 2>&1)"
echo "SCHEMA: $(wp eval "echo NCR_SCHEMA_VERSION;" 2>&1)"
echo "WP: $(wp eval "echo get_bloginfo('version');" 2>&1)"
echo "PHP: $(wp eval "echo phpversion();" 2>&1)"
echo ""

# Test 8: Object Search
echo "--- Test 8: Search ---"
wp eval "
\$s = new NATICORE_Object_Search();
\$r = \$s->search_posts('Test');
echo 'search_results: ' . count(\$r);
" 2>&1
echo ""

# Cleanup
wp post delete $POST_A $POST_B $POST_C $POST_D $POST_E $POST_F $POST_H --force 2>&1 > /dev/null
echo "=== All Tests Complete ==="

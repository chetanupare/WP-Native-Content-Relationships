<?php
require_once __DIR__ . '/../../../../../wp-load.php';

// 1. Create a Post
$post_id = wp_insert_post([
    'post_title' => 'Test Post Cleanup',
    'post_status' => 'publish',
    'post_type' => 'post'
]);

// 2. Create a User
$user_id = wp_insert_user([
    'user_login' => 'testuser_cleanup',
    'user_pass' => 'password',
    'role' => 'subscriber'
]);

// 3. Create a Term
$term = wp_insert_term('Test Term Cleanup', 'category');
$term_id = $term['term_id'];

// Make sure the relation type exists
if (!NATICORE_Relation_Types::get_type('post_to_user')) {
    NATICORE_Relation_Types::add_type('post_to_user', [
        'from_type' => 'post',
        'to_type' => 'user',
        'bidirectional' => true
    ]);
}
if (!NATICORE_Relation_Types::get_type('post_to_term')) {
    NATICORE_Relation_Types::add_type('post_to_term', [
        'from_type' => 'post',
        'to_type' => 'term',
        'bidirectional' => true
    ]);
}

// 4. Create relationships
NATICORE_API::add_relation($post_id, $user_id, 'post_to_user', 'user');
NATICORE_API::add_relation($post_id, $term_id, 'post_to_term', 'term');

// Check counts before deletion
global $wpdb;
$count_before = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}content_relations WHERE from_id = $post_id OR to_id IN ($user_id, $term_id)");
echo "Relations before delete: " . $count_before . "\n";

// 5. Delete User
wp_delete_user($user_id);

$count_after_user = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}content_relations WHERE to_type = 'user' AND to_id = $user_id");
echo "User relations after user delete: " . $count_after_user . "\n";

// 6. Delete Term
wp_delete_term($term_id, 'category');

$count_after_term = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}content_relations WHERE to_type = 'term' AND to_id = $term_id");
echo "Term relations after term delete: " . $count_after_term . "\n";

// 7. Delete Post
wp_delete_post($post_id, true);

$count_after_post = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}content_relations WHERE from_id = $post_id");
echo "Post relations after post delete: " . $count_after_post . "\n";

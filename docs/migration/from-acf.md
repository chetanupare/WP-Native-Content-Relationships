---
title: Migrating from ACF Relationship Fields
---

# Migrating from ACF Relationship Fields

If you store relationships in **Advanced Custom Fields** relationship fields, you can move them into Native Content Relationships.

## Approach

1. **Export ACF data** — ACF stores relationship post IDs in post meta (e.g. `my_relation` as a serialized array or multiple `my_relation_0`, `my_relation_1` keys). Use WP-CLI, a custom script, or ACF’s export to get post ID → related post IDs.
2. **Choose a relation type** — Register or use an existing type (e.g. `related_to`) in NCR. See [Custom types](/extending/custom-types).
3. **Import** — For each post with ACF relations, call `ncr_add_relation( $post_id, 'post', $related_id, 'post', 'related_to' )` for each related post.
4. **Switch code** — Replace ACF `get_field( 'my_relation', $post_id )` with `ncr_get_related( $post_id, 'post', 'related_to' )` and [WP_Query](/api/wp-query) where needed.
5. **Optional** — Remove or repurpose the ACF field after verification.

## Copy-paste migration script

Assume ACF stores related post IDs in meta key `my_relation` (array of IDs, or ACF’s serialized format). Run once (e.g. via WP-CLI: `wp eval-file migrate-acf-to-ncr.php` or from a one-off admin page).

```php
<?php
// migrate-acf-to-ncr.php — run once. Replace meta key and type as needed.
if ( ! function_exists( 'ncr_add_relation' ) ) {
	return;
}
$acf_meta_key = 'my_relation';   // Your ACF relationship field name
$ncr_type     = 'related_to';    // NCR relation type

$posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => $acf_meta_key, 'compare' => 'EXISTS' ] ],
] );

foreach ( $posts as $post_id ) {
	$related = get_post_meta( $post_id, $acf_meta_key, true );
	if ( ! is_array( $related ) ) {
		$related = array_filter( (array) $related );
	}
	foreach ( $related as $related_id ) {
		$related_id = (int) $related_id;
		if ( $related_id && get_post_status( $related_id ) ) {
			ncr_add_relation( $post_id, 'post', $related_id, 'post', $ncr_type );
		}
	}
}
```

**Before (ACF):** `$related = get_field( 'my_relation', $post_id );`  
**After (NCR):** `$related = ncr_get_related( $post_id, 'post', 'related_to' );`

## Notes

- ACF relationship fields are one-way (post → posts). NCR supports direction; use `direction => 'outgoing'` when querying.
- Run a small batch first and compare counts; then run full migration and `wp content-relations check` to validate.

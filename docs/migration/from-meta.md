---
title: Migrating from Post Meta
---

# Migrating from Post Meta

If you store related post IDs in **custom post meta** (e.g. `_related_ids` or multiple `_related_0`, `_related_1`), you can migrate to Native Content Relationships.

## Approach

1. **Identify meta keys** — List all meta keys that hold related IDs (single value, serialized array, or multiple keys).
2. **Choose a relation type** — Use or register a type in NCR, e.g. `related_to`. See [Custom types](/extending/custom-types).
3. **Import script** — For each post that has the meta:
   - Read the related IDs from meta.
   - For each ID, call `ncr_add_relation( $post_id, 'post', $related_id, 'post', 'related_to' )`.
4. **Update code** — Replace meta reads with `ncr_get_related()` and [WP_Query](/api/wp-query) where applicable.
5. **Optional** — Delete or repurpose the meta after verification; keep a backup.

## Copy-paste migration script

Assume you store related post IDs in post meta: either one key with a serialized array (e.g. `_related_ids`) or multiple keys (`_related_0`, `_related_1`, …). Run once (e.g. `wp eval-file migrate-meta-to-ncr.php`).

```php
<?php
// migrate-meta-to-ncr.php — run once. Set $meta_key and $ncr_type for your site.
if ( ! function_exists( 'ncr_add_relation' ) ) {
	return;
}
$meta_key = '_related_ids';  // One meta key holding array of IDs
$ncr_type = 'related_to';

$posts = get_posts( [
	'post_type'      => 'post',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => $meta_key, 'compare' => 'EXISTS' ] ],
] );

foreach ( $posts as $post_id ) {
	$related = get_post_meta( $post_id, $meta_key, true );
	if ( ! is_array( $related ) ) {
		$related = array_filter( array_map( 'intval', (array) $related ) );
	}
	foreach ( $related as $related_id ) {
		if ( $related_id && get_post_status( $related_id ) ) {
			ncr_add_relation( $post_id, 'post', $related_id, 'post', $ncr_type );
		}
	}
}
```

If you use multiple meta keys (e.g. `_related_0`, `_related_1`):

```php
$related = array_filter( array_map( 'intval', get_post_meta( $post_id, '_related_0', true ) ?: [] ) );
// If you have _related_0, _related_1, ... use a loop or get_post_meta( $post_id, '_related', false ) if that returns all.
```

**Before:** `$ids = get_post_meta( $post_id, '_related_ids', true );`  
**After:** `$ids = ncr_get_related( $post_id, 'post', 'related_to' );`

## Notes

- If meta stores “related post IDs” without direction, model them as outgoing relations from the current post.
- Run `wp content-relations check` after migration to ensure referential consistency.

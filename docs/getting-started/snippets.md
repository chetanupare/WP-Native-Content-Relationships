---
title: Copy-Paste Snippets
description: Ready-to-run code for common tasks. Copy, paste, adjust IDs and type names.
---

# Copy-Paste Snippets

Use these snippets in your theme, plugin, or custom code. Replace IDs and relation type slugs as needed.

## Add relationships (PHP)

```php
// Post → Post (e.g. "Related posts")
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// User → Post (e.g. "Favorites")
ncr_add_relation( get_current_user_id(), 'user', get_the_ID(), 'post', 'favorite_posts' );

// Post → Term (e.g. "Primary category")
ncr_add_relation( get_the_ID(), 'post', $term_id, 'term', 'primary_category' );
```

## Get related IDs (PHP)

```php
// Related post IDs for current post, max 10
$related_ids = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 10 ] );
if ( ! empty( $related_ids ) ) {
	foreach ( $related_ids as $id ) {
		echo '<a href="' . get_permalink( $id ) . '">' . get_the_title( $id ) . '</a>';
	}
}
```

## Loop with WP_Query (PHP)

```php
$q = new WP_Query( [
	'content_relation' => [
		'post_id'   => get_the_ID(),
		'type'      => 'related_to',
		'direction' => 'outgoing',
	],
	'posts_per_page' => 5,
	'post_status'    => 'publish',
] );
if ( $q->have_posts() ) {
	while ( $q->have_posts() ) {
		$q->the_post();
		the_title( '<h3>', '</h3>' );
	}
	wp_reset_postdata();
}
```

## Remove one or all relations (PHP)

```php
// Remove single relation
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );

// Remove all relations of type from a post
ncr_remove_relation( 123, 'post', null, 'post', 'related_to' );
```

## Register a custom relation type (PHP)

```php
add_action( 'init', function() {
	ncr_register_relation_type( [
		'name'            => 'recommended_by',
		'label'           => __( 'Recommended by', 'your-textdomain' ),
		'from'            => 'post',
		'to'              => 'post',
		'bidirectional'   => false,
		'max_connections' => 20,
	] );
}, 20 );
```

## Shortcodes (copy into content)

```text
[naticore_related_posts type="related_to" limit="5" layout="list"]
[naticore_related_posts type="related_to" limit="6" layout="grid" class="my-related"]
[naticore_related_users type="favorite_posts" limit="10"]
```

---

## CLI — one-liners

```bash
# List relations for post 123 (JSON)
wp content-relations list --post=123 --type=related_to --format=json

# Add relation from post 123 to 456
wp content-relations add 123 456 --type=related_to

# Remove that relation
wp content-relations remove 123 456 --type=related_to

# Count relations for post 123
wp content-relations count --post=123

# Check integrity (fix orphaned rows)
wp content-relations check --fix --verbose

# Export schema
wp content-relations schema --format=json
```

## CLI — batch add from file

Create `related.txt` with one pair per line: `from_id to_id` (e.g. `123 456`). Then:

```bash
while read -r from to; do
  wp content-relations add "$from" "$to" --type=related_to
done < related.txt
```

---

## Quick API reference

| Function | Purpose |
|----------|---------|
| `ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $type )` | Create one relation |
| `ncr_get_related( $from_id, $from_type, $type, $args )` | Get related IDs/objects |
| `ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $type )` | Remove one or all (use `null` for $to_id) |
| `ncr_register_relation_type( $args )` | Register custom type |
| `ncr_get_registered_relation_types()` | List registered types |

Types: `'post'`, `'user'`, `'term'`. See [PHP API](/api/php-api) and [WP-CLI](/api/wp-cli) for full options.

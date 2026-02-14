---
title: Relationships
description: Create, query, and remove relationships from code. PHP, WP_Query, shortcodes.
---

# Relationships

Create, query, and remove relationships from code.

## Creating relationships

```php
// Post to post (recommended: ncr_* API)
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// User to post (e.g. favorites)
ncr_add_relation( $user_id, 'user', $post_id, 'post', 'favorite_posts' );

// Post to term
ncr_add_relation( $post_id, 'post', $term_id, 'term', 'categorized_as' );
```

Legacy: `wp_add_relation( $from_id, $to_id, 'related_to' )` — prefer `ncr_add_relation()` for new code.

## Querying related content

```php
// Get related post IDs
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );

// With WP_Query
$q = new WP_Query([
    'content_relation' => [
        'post_id'   => get_the_ID(),
        'type'      => 'related_to',
        'direction' => 'outgoing',
    ],
]);
```

See [PHP API](/api/php-api) and [WP_Query](/api/wp-query) for full options.

## Removing relationships

```php
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );

// Remove all relations of a type from a source
ncr_remove_relation( 123, 'post', null, 'post', 'related_to' );
```

## Shortcodes

| Shortcode | Purpose |
|-----------|---------|
| `[naticore_related_posts]` | List related posts |
| `[naticore_related_users]` | List related users |
| `[naticore_related_terms]` | List related terms |

Common attributes: `type`, `limit`, `order`, `post_id`, `layout` (list/grid), `class`.

## Theme template example

```php
<?php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 5 ] );
if ( ! empty( $related ) ) :
	?>
	<aside class="related-posts">
		<h3><?php esc_html_e( 'Related posts', 'your-textdomain' ); ?></h3>
		<ul>
			<?php foreach ( $related as $post_id ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</aside>
<?php endif; ?>
```

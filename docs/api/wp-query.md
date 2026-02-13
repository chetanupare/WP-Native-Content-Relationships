---
title: WP_Query
---

# WP_Query Integration

Query posts (or other post types) by relationship using standard `WP_Query`.

## Recommended: content_relation

```php
$query = new WP_Query([
    'content_relation' => [
        'post_id'   => get_the_ID(),
        'type'      => 'related_to',
        'direction' => 'outgoing', // or 'incoming'
    ],
    'posts_per_page' => 10,
]);
```

Registered query vars (so WordPress does not strip them): `content_relation`, `wpcr`, `related_to`, `relation_type`. See the plugin’s query class docblock for full shapes.

## Legacy formats

- **wpcr:** `'wpcr' => [ 'from' => 123, 'type' => 'related_to', 'direction' => 'outgoing' ]`
- **related_to:** `'related_to' => 123`, `'relation_type' => 'related_to'`

## Delegation (future core API)

If WordPress adds a native relationship API, you can skip this plugin’s JOIN/WHERE by returning `true` from the filter:

```php
add_filter( 'ncr_skip_relationship_query', function( $skip, $query ) {
    // e.g. if core query var is set, let core handle it
    return $skip;
}, 10, 2 );
```

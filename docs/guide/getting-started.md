---
title: Quick Start
---

# Quick Start

Get the plugin running and add your first relationship in a few minutes.

## Install

1. Install from [WordPress.org](https://wordpress.org/plugins/native-content-relationships/) or upload the plugin to `wp-content/plugins/`.
2. Activate **Native Content Relationships** in the WordPress admin.

## First relationship

1. Edit any post (or page).
2. Find the **Content relationships** meta box.
3. Choose a relationship type (e.g. “Related to”).
4. Search and add related posts, users, or terms.
5. Update the post.

Relationships are stored in the dedicated table and can be queried via WP_Query, shortcodes, blocks, or the REST API.

## Configure relationship types

Go to **Settings → Content Relationships** to:

- Enable/disable relationship types
- Set constraints (e.g. max connections per post)
- Enable manual ordering for related items
- Configure integrations (WooCommerce, duplicate-post, etc.)

## Use in code

**Shortcode (in post content or widget):**

```
[naticore_related_posts type="related_to" limit="5" layout="list"]
```

**WP_Query:**

```php
$query = new WP_Query([
    'content_relation' => [
        'post_id' => get_the_ID(),
        'type'    => 'related_to',
        'direction' => 'outgoing',
    ],
]);
```

**PHP API:**

```php
ncr_add_relation( get_the_ID(), 'post', $related_id, 'post', 'related_to' );
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 5 ] );
```

See the [main plugin readme](https://github.com/chetanupare/WP-Native-Content-Relationships#readme) and [Architecture](/technical/architecture) for more.

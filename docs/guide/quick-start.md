---
title: Quick Start
description: Get your first relationship working in minutes — UI, shortcode, and block.
---

# Quick Start

Get your first relationship working in a few minutes.

## 1. Add a relationship in the UI

1. Edit any **post** or **page**.
2. Find the **Content relationships** meta box.
3. Choose a relationship type (e.g. **Related to**).
4. Use the search to add related posts, users, or terms.
5. **Update** the post.

Relationships are stored in the dedicated table and are available to WP_Query, shortcodes, blocks, and the REST API.

## 2. Configure (optional)

Go to **Settings → Content Relationships** to:

- Enable or disable relationship types
- Set constraints (e.g. max connections per post)
- Enable manual ordering for related items
- Configure integrations (WooCommerce, duplicate-post, etc.)

## 3. Display related content

Use a shortcode in post content or a widget:

```
[naticore_related_posts type="related_to" limit="5" layout="list"]
```

Or use the **Related Content** block in the block editor.

Next: [Relationships](/guide/relationships) for PHP and [PHP API](/api/php-api) for the full reference.

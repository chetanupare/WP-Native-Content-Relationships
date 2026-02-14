---
title: Introduction
description: What is Native Content Relationships — relationship layer for WordPress. PHP API, WP_Query, REST.
---

# Introduction

**Native Content Relationships** (NCR) is a relationship layer for WordPress. It adds a first-class way to link content: post ↔ post, post ↔ user, post ↔ term.

You define relation types (e.g. `related_to`, `parent_of`, `favorite_posts`), create and remove links via a small API, and query them with WP_Query, REST, shortcodes, or blocks.

## Minimal example

```php
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );
```

## Why NCR

- **Structured storage** — Dedicated indexed table (`wp_content_relations`). No post meta or taxonomy hacks.
- **One API, many surfaces** — PHP API, WP_Query, REST, shortcodes, Gutenberg blocks, Elementor. Same relationships everywhere.
- **Schema stable** — Backward compatibility from 1.x onward.

## Next steps

- [Installation](/guide/installation) — Install the plugin
- [Quick Start](/guide/quick-start) — First relationship in minutes
- [Relationships](/guide/relationships) — Create, query, remove from code
- [Use Cases](/guide/use-cases) — Products, courses, related articles, favorites
- [Widget](/guide/widget) — Sidebar widget for related posts
- [FAQ](/guide/faq) — Common questions
- [PHP API](/api/php-api) — Full API reference
- [Admin & Tools](/tools/admin-tools) — Overview, integrity, import/export

---

## See also

- [Use cases](/guide/use-cases) — Products, courses, related articles, favorites
- [FAQ](/guide/faq) — Common questions and answers
- [Admin & Tools](/tools/admin-tools) — Integrity checks, import/export
- [Core concepts](/core-concepts/relationship-types) — Relation types and object kinds

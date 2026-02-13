---
title: Native Content Relationships
---

# Native Content Relationships

A **native, scalable relationship layer** for WordPress. Model real relationships between posts, users, and terms — without post meta or taxonomy hacks.

::: tip Stability promise
**Schema stable from 1.x onward. Backward compatibility guaranteed.**
:::

## Why use it?

- **Structured data** — Dedicated indexed table (`wp_content_relations`), not meta or taxonomies
- **Queryable** — WP_Query integration, REST API, shortcodes, blocks
- **Scalable** — Sub-2ms lookups at 1M+ rows; chunked integrity engine
- **Ecosystem-ready** — WooCommerce, Elementor, Gutenberg, WPML/Polylang

## Quick links

| Link | Description |
|------|-------------|
| [Getting started](./guide/getting-started) | Install, configure, first relationship |
| [Architecture](./technical/architecture) | Core components, registry, DB, integrity |
| [Performance](./technical/performance) | Benchmarks, latency, scaling |
| [Feature overview](./product/features) | Shortcodes, REST, widgets, roadmap |

## Supported relationship types

| From ↔ To | Use cases |
|-----------|-----------|
| Post ↔ Post | Related products, courses → lessons, related articles |
| Post ↔ User | Favorites, bookmarks, multiple authors |
| Post ↔ Term | Featured categories, curated collections |
| User ↔ Post | Same as Post ↔ User (bidirectional) |
| Term ↔ Post | Same as Post ↔ Term (bidirectional) |

## Get the plugin

- [WordPress.org](https://wordpress.org/plugins/native-content-relationships/)
- [GitHub](https://github.com/chetanupare/WP-Native-Content-Relationships)

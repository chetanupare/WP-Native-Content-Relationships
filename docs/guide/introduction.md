---
title: Introduction
---

# Introduction

You are reading the documentation for **Native Content Relationships**.

::: tip Stability
Schema stable from **1.x** onward. Backward compatibility guaranteed.
:::

## What is Native Content Relationships?

**Native Content Relationships** (NCR) is a WordPress plugin that adds a **first-class relationship layer** for your content. Instead of storing links between posts, users, and terms in post meta or taxonomies, NCR uses a dedicated, indexed database table and a simple API so you can model and query real relationships at scale.

Here is a minimal example:

```php
// Create a relationship from post 123 to post 456
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// Get all related posts
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );
```

Two ideas matter here:

- **Structured storage** — Relationships live in `wp_content_relations` with proper indexes, not in meta or term relationships.
- **One API** — Create, read, and remove relations with a small set of functions; use them from PHP, WP_Query, REST, shortcodes, or blocks.

The rest of the docs fill in the details: installation, relationship types, query options, hooks, REST API, and integrations.

::: info Prerequisites
The guide assumes you know WordPress basics (posts, plugins, themes). Some PHP is required for the API; the UI and shortcodes work without code.
:::

## The relationship layer

WordPress does not ship a generic way to say “this post is related to that post” or “this user bookmarked that post.” Plugins often use:

- **Post meta** — Hard to query and scale.
- **Taxonomies** — Tied to terms, not arbitrary object-to-object links.
- **Custom tables** — Often one-off and not reusable.

NCR adds a **single, consistent layer** for relationships between:

- Posts (any post type)
- Users
- Terms (any taxonomy)

You define **relationship types** (e.g. `related_to`, `parent_of`, `favorite_posts`), optionally with constraints (e.g. max one “author” per post). The same API and table back the admin UI, shortcodes, Gutenberg blocks, Elementor tags, and REST.

So: one way to model relations, many ways to use them — in the admin, in themes, and in headless setups.

## Key concepts

| Concept | Meaning |
|--------|---------|
| **Relation type** | A named kind of link (e.g. `related_to`, `parent_of`). Registered once, used everywhere. |
| **From / to** | Every relation has a source object (from_id + from_type) and a target object (to_id + to_type). |
| **Direction** | Some types are one-way (from → to only), others bidirectional. |
| **Object types** | `post`, `user`, or `term`. The plugin validates that a type allows the from/to pair you use. |

You’ll see these in the [Quick Start](/guide/getting-started), [Relationship types](/guide/developer-docs#relationship-types), and [API reference](/guide/developer-docs#api-reference).

## Still have questions?

Check the [Developer documentation](/guide/developer-docs) for the full API, hooks, REST, and WP-CLI, or the [Architecture](/technical/architecture) and [Performance](/technical/performance) pages for how it works under the hood.

## Pick your path

Different people learn in different ways. Choose what fits you:

| Path | Best for |
|------|----------|
| [**Quick Start**](/guide/getting-started) | Install, add your first relation in the UI, try a shortcode or block. |
| [**Developer documentation**](/guide/developer-docs) | Full API, hooks, REST, WP-CLI, custom relation types, and integrations. |
| [**Architecture**](/technical/architecture) | How the plugin is built: registry, database, integrity, and public API. |

You can follow one path and jump to another anytime from the sidebar.

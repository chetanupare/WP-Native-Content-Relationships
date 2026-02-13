---
title: Native Content Relationships
layout: home
hero:
  name: Native Content Relationships
  text: A relationship layer for WordPress
  tagline: Model and query relationships between posts, users, and terms — without meta or taxonomy hacks.
  image:
    src: /WP-Native-Content-Relationships/wordpress-logo-svgrepo-com.svg
    alt: NCR
  actions:
    - text: Get started
      link: /guide/introduction
      theme: brand
    - text: Developer docs
      link: /guide/developer-docs
      theme: alt
features:
  - title: Structured storage
    details: Dedicated indexed table (wp_content_relations). No post meta or taxonomy abuse; built to scale.
  - title: One API, many surfaces
    details: PHP API, WP_Query, REST, shortcodes, Gutenberg blocks, Elementor. Same relationships everywhere.
  - title: Schema stable
    details: Backward compatibility guaranteed from 1.x. Safe for themes, plugins, and agencies.
  - title: Ecosystem-ready
    details: WooCommerce, WPML/Polylang, Gutenberg, Elementor. Register custom relation types and constraints.
---

::: tip Stability promise
**Schema stable from 1.x onward. Backward compatibility guaranteed.**
:::

## What is it?

**Native Content Relationships** adds a first-class way to link content in WordPress: post ↔ post, post ↔ user, post ↔ term. You define relation types (e.g. `related_to`, `parent_of`, `favorite_posts`), create and remove links via a small API, and query them with WP_Query, REST, or the built-in shortcodes and blocks.

## Pick your path

| Path | Description |
|------|-------------|
| [**Introduction**](/guide/introduction) | What NCR is, key concepts, and how the docs are organized. |
| [**Quick Start**](/guide/getting-started) | Install, add your first relationship in the UI, use a shortcode or block. |
| [**Developer documentation**](/guide/developer-docs) | Full API reference, hooks, REST, WP-CLI, custom types, integrations. |
| [**Architecture**](/technical/architecture) | How the plugin is built — registry, database, integrity engine. |

---

[WordPress.org](https://wordpress.org/plugins/native-content-relationships/) · [GitHub](https://github.com/chetanupare/WP-Native-Content-Relationships)

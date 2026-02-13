---
title: Introduction
---

# Introduction

You are reading the documentation for **Native Content Relationships**.

::: tip Stability promise
**Schema stable from 1.x onward. Backward compatibility guaranteed.**
:::

## What is Native Content Relationships?

**Native Content Relationships** (NCR) is a relationship layer for WordPress. It adds a first-class way to link content: post ↔ post, post ↔ user, post ↔ term. You define relation types (e.g. `related_to`, `parent_of`, `favorite_posts`), create and remove links via a small API, and query them with WP_Query, REST, or the built-in shortcodes and blocks.

Here is a minimal example:

```php
// Create a relationship
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// Get related posts
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );
```

The above demonstrates the core idea:

- **Structured storage**: A dedicated indexed table (`wp_content_relations`). No post meta or taxonomy hacks.
- **One API, many surfaces**: PHP API, WP_Query, REST, shortcodes, Gutenberg blocks, Elementor. Same relationships everywhere.

You may already have questions — don't worry. The rest of the documentation covers installation, the full API, and integrations.

**Prerequisites:** Basic familiarity with WordPress (plugins, themes, PHP) is helpful. No prior relationship-plugin experience is required.

## The Progressive Framework

NCR is designed to be flexible and incrementally adoptable. Depending on your use case, you can:

- Add relationships only where needed (e.g. related posts on a single post type)
- Use the UI, shortcodes, and blocks without writing PHP
- Run a **blog**: related posts, recommended reads, and series with shortcodes and the Related Content block — see [Using NCR on your blog](/getting-started/blogs)
- Drive WooCommerce related products, upsells, and cross-sells from one layer
- Build custom relation types and query them with WP_Query
- Integrate with Elementor, Gutenberg, WPML/Polylang, and custom code

Despite the flexibility, the core concepts are the same: **relation types**, **direction** (incoming/outgoing), and a **stable schema**. Whether you start with the Quick Start or dive into the API, that knowledge stays useful as you scale.

## Still Got Questions?

Check out the [FAQ](https://github.com/chetanupare/WP-Native-Content-Relationships#readme) and [Stability & Backward Compatibility](https://github.com/chetanupare/WP-Native-Content-Relationships#readme) in the readme.

## Pick Your Learning Path

Different developers have different learning styles. Pick a path that suits you — we recommend covering the basics first, then the API.

<div class="path-cards">

<a class="path-card" href="/getting-started/quick-start">
  <h4>Try the Quick Start</h4>
  <p>For those who prefer learning by doing. Install, add your first relationship in the UI, use a shortcode or block.</p>
</a>

<a class="path-card" href="/getting-started/installation">
  <h4>Read the Guide</h4>
  <p>The guide walks you through installation, core concepts, and basic relationships in full detail.</p>
</a>

<a class="path-card" href="/api/php-api">
  <h4>Check the API</h4>
  <p>Explore the PHP API, WP_Query, REST, WP-CLI, and hooks. For developers who want to integrate NCR into code.</p>
</a>

</div>

---

[WordPress.org](https://wordpress.org/plugins/native-content-relationships/) · [GitHub](https://github.com/chetanupare/WP-Native-Content-Relationships)

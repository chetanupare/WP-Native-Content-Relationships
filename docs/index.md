---
layout: home
title: Native Content Relationships
description: The missing relationship engine for WordPress. Structured, indexed, scalable relationships between posts, users, and terms.
hero:
  name: ''
  text: The Missing Relationship Engine for WordPress
  tagline: Structured, indexed, scalable relationships between posts, users, and terms.
  actions:
    - theme: brand
      text: Get Started
      link: /guide/quick-start
    - theme: alt
      text: View on GitHub
      link: https://github.com/chetanupare/WP-Native-Content-Relationships
      external: true
    - theme: alt
      text: WordPress.org
      link: https://wordpress.org/plugins/native-content-relationships/
      external: true
features:
  - icon: 🧩
    title: One API, many surfaces
    details: ncr_add_relation, ncr_get_related, WP_Query content_relation. PHP, REST, shortcodes, Gutenberg, Elementor.
    link: /api/php-api
  - icon: ⚡
    title: Performance at scale
    details: Indexed relationship table. Sub-2ms lookups at 1M rows. Benchmarks and scaling guide.
    link: /performance/benchmarks
  - icon: 🔄
    title: Migrate from meta & ACF
    details: Escape technical debt. Migration guides for post meta, ACF relationship fields, and P2P.
    link: /migration/from-acf
  - icon: 🔌
    title: Integrations
    details: WooCommerce, Elementor, Gutenberg, WPML, headless REST. Same relationships everywhere.
    link: /integrations/gutenberg
---

<div class="landing-content">

<div class="hero-snippet">

```php
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );
```

</div>

::: tip Stability promise
**Schema stable from 1.x onward.** Backward compatibility guaranteed.
:::

## Pick your path

<div class="path-cards">

<a class="path-card" href="/guide/quick-start">
  <h4>Quick Start</h4>
  <p>Install, add your first relationship in the UI, and show it with a shortcode or block.</p>
</a>

<a class="path-card" href="/guide/introduction">
  <h4>Introduction</h4>
  <p>What is NCR, minimal example, and next steps to installation and API.</p>
</a>

<a class="path-card" href="/guide/relationships">
  <h4>Relationships</h4>
  <p>Create, query, and remove from code. PHP, WP_Query, shortcodes, theme example.</p>
</a>

<a class="path-card" href="/guide/use-cases">
  <h4>Use Cases</h4>
  <p>Products & accessories, courses & lessons, related articles, favorites, WooCommerce.</p>
</a>

<a class="path-card" href="/guide/faq">
  <h4>FAQ</h4>
  <p>WooCommerce, ACF migration, page builders, users & terms, data privacy, schema stability.</p>
</a>

<a class="path-card" href="/performance/benchmarks">
  <h4>Benchmarks</h4>
  <p>Latency at 100k and 1M rows, memory usage, and comparison with meta-based approaches.</p>
</a>

</div>

---

## The problem

WordPress does not have native relationships.

- **Post meta is unindexed** — `meta_query` on relationship-style keys doesn't scale; full table scans as data grows.
- **Taxonomies are semantic grouping, not relational modeling** — Categories and tags group posts; they don't model “post A links to post B” or directional graphs.
- **Meta queries degrade with scale** — Complex relationship logic in meta becomes slow and hard to maintain.
- **Complex modeling becomes messy** — Workarounds (custom tables, multiple meta keys, P2P-style plugins) add technical debt.

Developers need a **first-class, indexed relationship layer**. Not a workaround.

---

## The solution

A dedicated **relationship table** and a small API. Same data, every surface.

**Post → Indexed relationship table → Post / User / Term**

- **Dedicated relational storage** — One table, proper indexes. No post meta or taxonomy hacks.
- **Directional relationships** — From/to, bidirectional or one-way. Model courses→lessons, favorites, parent/child.
- **WP_Query integration** — Query by relationship in the loop. `content_relation` in your query args.
- **REST & CLI ready** — Optional REST embed; WP-CLI commands for scripting and migration.
- **Multilingual safe** — Works with WPML, Polylang. Relation IDs are stable across languages.

This is the architectural differentiation: one API, one schema, many surfaces (PHP, REST, shortcodes, Gutenberg, Elementor).

---

## Real-world use cases

Don’t list features. Show patterns.

**Content modeling**

- Courses → Lessons  
- Products → Accessories  
- Articles → Related content  

**User relationships**

- Favorites, bookmarks  
- Multi-author posts  

**Taxonomy extensions**

- Featured categories  
- Curated collections  

→ [Use cases](/guide/use-cases) — Products, courses, related articles, favorites, with code and shortcodes.

---

## Performance & benchmarks

Most WP plugins never show performance data. We do.

| Scenario | 100k relations | 1M relations |
| -------- | -------------- | ------------ |
| **Point lookup (mean)** | 0.49 ms | 1.00 ms |
| **Point lookup (P95)** | 0.85 ms | 2.73 ms |
| **Covering index mean** | 0.22 ms | 0.61 ms |
| **Peak memory delta** | — | ~2.21 MB |

Covering index: `(type, from_id, to_id)` — index-only lookups; query time O(log n). Sub-2ms typical at 1M rows.

→ [Benchmarks](/performance/benchmarks) — Full methodology, scaling guide, and comparison with meta-based approaches.

---

## Integrations

| [WooCommerce](/integrations/woocommerce) | [Elementor](/integrations/elementor) | [Gutenberg](/integrations/gutenberg) |
| --------------------------------------- | ------------------------------------ | ------------------------------------ |
| [WPML / Polylang](/integrations/multilingual) | Headless (REST) | [SEO](/integrations/seo) (Yoast, Rank Math) |

Same relationships everywhere. No lock-in.

---

## Architecture & stability promise

Critical for ecosystem and agency adoption.

- **Schema stability guarantee** — Stable from 1.x onward. No breaking schema changes in the 1.x line.
- **Backward compatibility commitment** — Public API and table structure remain compatible; new features are additive.
- **Semantic versioning** — Version numbers reflect compatibility expectations.

→ [Architecture overview](/architecture/overview) · [Schema](/architecture/schema)

---

## Developer section

Short and scannable.

| [PHP API](/api/php-api) | [WP_Query](/api/wp-query) | [REST API](/api/rest-api) |
| ----------------------- | ------------------------- | ------------------------- |
| [WP-CLI](/api/wp-cli) | [Hooks & filters](/api/hooks-filters) | [Shortcodes](/api/shortcodes) |

→ [Quick Start](/guide/quick-start) — First relationship in minutes.  
→ [Relationships](/guide/relationships) — Create, query, remove from code.

---

## Migration

Escape from technical debt. We offer a path off meta and legacy plugins.

- [Migrating from ACF Relationship Fields](/migration/from-acf) — Export ACF, choose a relation type, import, switch code.
- [Migrating from post meta](/migration/from-meta) — Move meta-based relationships into NCR.
- [Migrating from Posts 2 Posts](/migration/from-posts-2-posts) — P2P to NCR migration path.

---

## Community & roadmap

- **Repository** — [GitHub — WP-Native-Content-Relationships](https://github.com/chetanupare/WP-Native-Content-Relationships)
- **Contributing** — [How to contribute](/contributing): issues, docs, code. Standards and hooks documented.
- **Roadmap** — [Near term & future](/roadmap): documentation, stability, integrations; WPGraphQL, tutorials, ecosystem.

Transparency builds trust. No fluff.

</div>

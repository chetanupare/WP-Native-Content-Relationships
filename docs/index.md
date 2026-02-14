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

<div class="landing-section">
<div class="section-card">

WordPress does not have native relationships.

<ul class="problem-list">
<li><strong>Post meta is unindexed</strong> — <code>meta_query</code> on relationship-style keys doesn't scale; full table scans as data grows.</li>
<li><strong>Taxonomies are semantic grouping, not relational modeling</strong> — Categories and tags group posts; they don't model “post A links to post B” or directional graphs.</li>
<li><strong>Meta queries degrade with scale</strong> — Complex relationship logic in meta becomes slow and hard to maintain.</li>
<li><strong>Complex modeling becomes messy</strong> — Workarounds (custom tables, multiple meta keys, P2P-style plugins) add technical debt.</li>
</ul>

<p class="problem-cta">Developers need a first-class, indexed relationship layer. Not a workaround.</p>

</div>
</div>

---

## The solution

<div class="landing-section">
<div class="section-card">

A dedicated <strong>relationship table</strong> and a small API. Same data, every surface.

<p class="solution-flow">Post → Indexed relationship table → Post / User / Term</p>

<ul class="solution-list">
<li><strong>Dedicated relational storage</strong> — One table, proper indexes. No post meta or taxonomy hacks.</li>
<li><strong>Directional relationships</strong> — From/to, bidirectional or one-way. Model courses→lessons, favorites, parent/child.</li>
<li><strong>WP_Query integration</strong> — Query by relationship in the loop. <code>content_relation</code> in your query args.</li>
<li><strong>REST & CLI ready</strong> — Optional REST embed; WP-CLI commands for scripting and migration.</li>
<li><strong>Multilingual safe</strong> — Works with WPML, Polylang. Relation IDs are stable across languages.</li>
</ul>

<p>One API, one schema, many surfaces (PHP, REST, shortcodes, Gutenberg, Elementor).</p>

</div>
</div>

---

## Real-world use cases

<div class="landing-section">
<div class="section-card">

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

</div>
</div>

---

## Performance & benchmarks

<div class="landing-section">
<div class="section-card">

<p>Most WP plugins never show performance data. We do.</p>

<div class="perf-table-wrap">

| Scenario | 100k relations | 1M relations |
| -------- | -------------- | ------------ |
| **Point lookup (mean)** | 0.49 ms | 1.00 ms |
| **Point lookup (P95)** | 0.85 ms | 2.73 ms |
| **Covering index mean** | 0.22 ms | 0.61 ms |
| **Peak memory delta** | — | ~2.21 MB |
</div>

<p>Covering index: <code>(type, from_id, to_id)</code> — index-only lookups; query time O(log n). Sub-2ms typical at 1M rows.</p>

<p class="perf-cta">→ <a href="/performance/benchmarks">Benchmarks</a> — Full methodology, scaling guide, and comparison with meta-based approaches.</p>

</div>
</div>

---

## Integrations

<div class="landing-section">
<div class="section-card">

<div class="integration-grid">
<a class="integration-card" href="/integrations/woocommerce">WooCommerce</a>
<a class="integration-card" href="/integrations/elementor">Elementor</a>
<a class="integration-card" href="/integrations/gutenberg">Gutenberg</a>
<a class="integration-card" href="/integrations/multilingual">WPML / Polylang</a>
<a class="integration-card" href="/api/rest-api">Headless (REST)</a>
<a class="integration-card" href="/integrations/seo">SEO (Yoast, Rank Math)</a>
</div>

<p>Same relationships everywhere. No lock-in.</p>

</div>
</div>

---

## Architecture & stability promise

<div class="landing-section">
<div class="section-card">

<p>Critical for ecosystem and agency adoption.</p>

<div class="promise-cards">
<div class="promise-card"><strong>Schema stability guarantee</strong><span>Stable from 1.x onward. No breaking schema changes in the 1.x line.</span></div>
<div class="promise-card"><strong>Backward compatibility commitment</strong><span>Public API and table structure remain compatible; new features are additive.</span></div>
<div class="promise-card"><strong>Semantic versioning</strong><span>Version numbers reflect compatibility expectations.</span></div>
</div>

<p class="arch-cta">→ <a href="/architecture/overview">Architecture overview</a> · <a href="/architecture/schema">Schema</a></p>

</div>
</div>

---

## Developer section

<div class="landing-section">
<div class="section-card">

<p>Short and scannable.</p>

<div class="developer-grid">
<a href="/api/php-api">PHP API</a>
<a href="/api/wp-query">WP_Query</a>
<a href="/api/rest-api">REST API</a>
<a href="/api/wp-cli">WP-CLI</a>
<a href="/api/hooks-filters">Hooks & filters</a>
<a href="/api/shortcodes">Shortcodes</a>
</div>

<p class="developer-cta">→ <a href="/guide/quick-start">Quick Start</a> — First relationship in minutes.<br>→ <a href="/guide/relationships">Relationships</a> — Create, query, remove from code.</p>

</div>
</div>

---

## Migration

<div class="landing-section">
<div class="section-card">

<p>Escape from technical debt. We offer a path off meta and legacy plugins.</p>

<div class="migration-cards">
<a class="migration-card" href="/migration/from-acf"><strong>Migrating from ACF Relationship Fields</strong><p>Export ACF, choose a relation type, import, switch code.</p></a>
<a class="migration-card" href="/migration/from-meta"><strong>Migrating from post meta</strong><p>Move meta-based relationships into NCR.</p></a>
<a class="migration-card" href="/migration/from-posts-2-posts"><strong>Migrating from Posts 2 Posts</strong><p>P2P to NCR migration path.</p></a>
</div>

</div>
</div>

---

## Community & roadmap

<div class="landing-section">
<div class="section-card community-card">

<p><strong>Repository</strong> — <a href="https://github.com/chetanupare/WP-Native-Content-Relationships">GitHub — WP-Native-Content-Relationships</a></p>
<p><strong>Contributing</strong> — <a href="/contributing">How to contribute</a>: issues, docs, code. Standards and hooks documented.</p>
<p><strong>Roadmap</strong> — <a href="/roadmap">Near term & future</a>: documentation, stability, integrations; WPGraphQL, tutorials, ecosystem.</p>

<p>Transparency builds trust. No fluff.</p>

</div>
</div>

</div>

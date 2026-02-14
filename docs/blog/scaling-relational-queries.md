---
title: Scaling Relational Queries in WordPress
description: Performance starts with data modeling. Why meta queries grow linearly and indexed relationship tables scale predictably.
layout: doc
---

# Scaling Relational Queries in WordPress

Performance issues in WordPress often come from one source: **improper data modeling**. Relationships are a common offender.

---

## What happens as sites grow

**Small site:** 2k posts, few relationships. Everything feels fast.

**Growing site:** 20k posts, many relationships, complex filtering. Some pages slow down; you add caching, more indexes on meta, or workarounds.

**Large site:** 100k+ content objects, relationship-driven UI, headless frontends. Performance bottlenecks show up everywhere. Meta-based relationship queries grow linearly in cost. Indexed joins scale predictably. The gap between the two is where many projects hit a wall.

---

## Indexed relationship tables

A dedicated relationship table includes:

- `from_id`, `to_id`
- `from_type`, `to_type`
- `relationship_type`
- **Indexed columns** — e.g. a composite key like `(type, from_id, to_id)` so the database can do index-only lookups.

This allows:

- **Efficient joins** — One table, proper keys. No scanning serialized meta.
- **Fast lookups** — “All relations from post 123” or “All relations of type X where to_id = 456” become index seeks.
- **Directional queries** — Ask for outgoing or incoming relations explicitly.
- **Filtered relationship types** — Query by type without string matching.

Relational databases are optimized for this pattern. Post meta tables are not. [Native Content Relationships](/) uses a single table (`wp_content_relations`) with a [covering index](/performance/benchmarks); at 1M rows, typical lookups stay sub-2ms.

---

## Direction matters

Many relationship systems ignore direction. But direction enables:

- **Outgoing relationships** — “Posts this one points to” (e.g. “lessons in this course”).
- **Incoming relationships** — “Posts that point to this one” (e.g. “courses that contain this lesson”).
- **Reverse lookups** — Same data, different query intent.
- **Semantic clarity** — “Related to” can be bidirectional; “parent of” is one-way.

**Example:** Get posts that point *to* this one (incoming “related_to”):

```php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'direction' => 'to', 'limit' => 10 ] );
```

Clear intent. Predictable behavior. Same API for outgoing (`direction => 'from'`) or both. See [Direction](/core-concepts/direction) and [WP_Query content_relation](/api/wp-query) for more.

---

## Performance is architecture

Performance is not about caching everything. It starts with:

- **Proper schema** — Relationships in a table designed for them, not in meta or taxonomies.
- **Proper indexing** — Columns you query are indexed; lookups are O(log n), not O(n).
- **Predictable queries** — Same pattern at 10k and 1M rows.

If relationships are core to your content model, treat them as first-class data. Use a [dedicated layer](/), not meta or taxonomy hacks. Scalability is not an afterthought. It is a design decision.

---

**Next:** [Benchmarks](/performance/benchmarks) · [Direction](/core-concepts/direction) · [Stop Using Post Meta for Relationships at Scale](/blog/stop-using-post-meta-for-relationships)

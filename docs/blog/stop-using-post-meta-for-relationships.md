---
title: Stop Using Post Meta for Relationships at Scale
description: Why meta-based relationships hurt at scale, and what to use instead. Dedicated storage, indexing, and the NCR approach.
layout: doc
---

# Stop Using Post Meta for Relationships at Scale

WordPress stores relationships using post meta. For small sites, this works. At scale, it becomes a performance and architecture problem.

---

## The problem with meta-based relationships

When you store relationships like this:

```php
update_post_meta( $post_id, 'related_posts', [ 45, 72, 99 ] );
```

you are:

- **Storing unindexed serialized arrays** — MySQL can’t index the contents of a serialized list. Lookups become full scans.
- **Creating complex `meta_query` joins** — Every relationship query joins `wp_posts` with `wp_postmeta` and filters by key and value.
- **Increasing query cost with scale** — More posts and more meta rows mean slower joins and heavier temp tables.
- **Making bidirectional relationships difficult** — “Posts related to 45” requires scanning all rows where the value contains `"45"`.
- **Complicating headless usage** — Exposing relationships via REST usually means extra meta queries or denormalized data.

The bigger your dataset, the worse this gets.

---

## Why meta_query does not scale

A typical relationship query:

```php
new WP_Query( [
    'meta_query' => [
        [
            'key'     => 'related_posts',
            'value'   => '"45"',
            'compare' => 'LIKE',
        ],
    ],
] );
```

This:

- Uses **string matching** — You’re matching a substring in a serialized array, not a column value.
- **Cannot leverage proper indexing** — No B-tree index on “value contains X.”
- **Requires scanning large meta tables** — The database walks rows to find matches.
- **Grows slower as content grows** — Linear (or worse) growth in cost as posts and meta increase.

On sites with 50k+ posts, this becomes noticeable. On 100k+, it becomes painful.

---

## What scalable relationships require

A scalable relationship system needs:

- **Dedicated relational storage** — A table designed for rows like “A is related to B.”
- **Indexed columns** — `from_id`, `to_id`, and relation type so the database can use indexes.
- **Clear direction** — From → to. Bidirectional is then a query choice, not a data hack.
- **Type-aware relationships** — Different relation types (e.g. `related_to`, `parent_of`) without overloading one meta key.
- **Efficient joins** — Single table lookups or simple joins, not `LIKE` on serialized data.

That’s what relational databases are designed for.

---

## A better approach

Instead of storing relationships inside meta, use a dedicated relationship layer. One call, one row, one index:

```php
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );
```

This creates a structured row in an indexed relationship table. Queries use that table:

```php
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );
```

Or with [WP_Query](/api/wp-query) and a `content_relation` argument — no `meta_query` at all.

- **Queries become predictable** — Index lookups, not string scans.
- **Joins become efficient** — One relationship table, proper keys.
- **Bidirectional relationships become natural** — Query from either side with the same API.
- **Headless and REST stay simple** — Same data, same indexes, exposed via [REST](/api/rest-api) or your own endpoints.

[Native Content Relationships](/) gives you this: one table (`wp_content_relations`), one API, and [benchmarks](/performance/benchmarks) that stay sub-2ms at 1M rows.

---

## When meta is fine

Post meta is fine when:

- The dataset is small.
- Relationships are rarely queried (e.g. a single “featured” ID).
- You don’t need direction or filtering by relationship type.
- You’re not exposing relationships in a headless or public API.

But once relationships **drive** your content model — related posts, courses and lessons, products and accessories, favorites — you need structure. Structured data is not premature optimization. It’s long-term architecture.

---

**Next:** [Migrating from post meta](/migration/from-meta) · [PHP API](/api/php-api) · [Benchmarks](/performance/benchmarks)

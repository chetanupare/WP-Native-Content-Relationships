---
title: Modeling Complex Content in WordPress Properly
description: Flexibility is not structure. Why taxonomies and meta fall short for courses, products, and user relationships — and what proper modeling looks like.
layout: doc
---

# Modeling Complex Content in WordPress Properly

WordPress is flexible. But flexibility is not the same as structure. When building complex systems like **Courses → Lessons**, **Products → Accessories**, **Authors → Contributions**, or **Users → Favorites**, you need real relationships.

---

## The taxonomy trap

Many developers misuse taxonomies for relationships.

**Example:** Create a custom taxonomy called “related,” assign posts manually, query via `tax_query`.

Taxonomies are designed for **classification** — categories, tags, genres. Not relational modeling. They don’t handle direction. They don’t express intent (e.g. “this post is a *lesson of* this course”). They don’t scale cleanly when you have multiple relationship types (related, parent, favorite, contributor) on the same content. You end up with taxonomies that pretend to be relations, and queries that are hard to reason about.

---

## The meta trap

Meta fields allow storing anything. That’s the problem. Lack of structure leads to:

- **Inconsistent modeling** — One site uses `related_posts`, another uses `linked_items`; no shared semantics.
- **Difficult querying** — Serialized arrays and `LIKE`-based `meta_query` don’t index or join well.
- **Poor performance at scale** — [We’ve covered this](/blog/stop-using-post-meta-for-relationships): meta-based relationships degrade as data grows.
- **Hard migrations later** — Changing structure means rewriting queries and scripts everywhere.

---

## What proper modeling looks like

A structured relationship model should support:

- **Post ↔ Post** — Related articles, courses and lessons, products and accessories.
- **Post ↔ User** — Authors, contributors, favorites.
- **Post ↔ Term** — Curated collections, featured terms (without overloading taxonomies as relations).
- **Direction control** — Course → lessons is one-way; “related to” can be bidirectional.
- **Typed relationships** — `lesson_of`, `related_to`, `favorite_posts`, `accessory_of` — each with clear meaning.
- **Efficient querying** — Index-backed lookups and [WP_Query integration](/api/wp-query), not string matching.

**Example:** Course contains lessons. Clear. Explicit. Structured.

```php
ncr_add_relation( $course_id, 'post', $lesson_id, 'post', 'lesson_of' );
```

Query lessons for a course:

```php
$lessons = ncr_get_related( $course_id, 'post', 'lesson_of' );
// or with WP_Query and content_relation
$q = new WP_Query( [
    'post_type'        => 'lesson',
    'content_relation' => [
        'post_id'   => $course_id,
        'type'      => 'lesson_of',
        'direction' => 'to',
    ],
] );
```

Same idea for products and accessories, authors and contributions, users and favorites. One [relation type](/core-concepts/relationship-types), one API, one indexed table. See [Use cases](/guide/use-cases) for copy-paste patterns.

---

## Why this matters for headless & APIs

When using REST or GraphQL you want relationships that are:

- **Queryable** — “Give me posts where the current user is in favorites.”
- **Filterable** — “Give me lessons in this course, ordered by position.”
- **Direction-aware** — “From this course, get lessons” vs “From this lesson, get course.”
- **Index-backed** — Fast responses, no N+1 or full-table scans.

Meta-based “relationships” are not built for that. Structured relationships are. [Native Content Relationships](/) exposes relations via the [REST API](/api/rest-api) (optional embed on core endpoints) so headless and API consumers get the same model as the database: typed, directional, efficient.

Good content modeling reduces technical debt. Bad modeling compounds it.

---

**Next:** [Use cases](/guide/use-cases) · [Relationship types](/core-concepts/relationship-types) · [REST API](/api/rest-api)

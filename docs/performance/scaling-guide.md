---
title: Scaling Guide
---

# Scaling Guide

## Complexity

| Metric           | Complexity   |
| ---------------- | ------------ |
| Point lookups    | O(log n)     |
| Constraint checks| O(log n)     |
| Integrity scan   | O(n) (chunked, bounded memory) |

## At 10M rows (projected)

- Point lookups remain index-bound.
- Full graph scan ~10–12 minutes (linear).
- Memory &lt; 5 MB (chunked processing).

## Recommendations

1. **Indexing** — Rely on the built-in `type_lookup` index; see [Indexing](/performance/indexing).
2. **Object cache** — Enable Redis/Memcached for WordPress; relationship queries benefit from cached post/user/term data.
3. **Integrity scans** — Run `wp content-relations check` during low-traffic windows; use `--fix` only when needed.
4. **WP_Query** — Use `content_relation` with a single relation type and reasonable `posts_per_page` to avoid heavy JOINs.

See [Benchmarks](/performance/benchmarks) for measured latency and resource usage.

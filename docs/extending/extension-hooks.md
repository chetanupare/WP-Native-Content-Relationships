---
title: Extension Hooks
---

# Extension Hooks

Beyond the main [API hooks](/api/hooks-filters), you can use these extension points.

## Relation type registration

- **Action:** Run [ncr_register_relation_type](/extending/custom-types) on `init` (priority 20+).
- **Filter:** Check the codebase for filters that alter registered types (e.g. labels or caps) if the plugin exposes them.

## Query and limits

- **`ncr_get_related_args`** — Change `limit`, `orderby`, `order`, `direction` for `ncr_get_related()`.
- **`ncr_max_relationships`** — Override max connections per source for a relation type.
- **`ncr_skip_relationship_query`** — Bypass the plugin’s WP_Query JOIN/WHERE (e.g. for a future core API).

## Lifecycle

- **`ncr_relation_added`** — After a relation is created; use for cache invalidation or external sync.
- **`ncr_relation_removed`** — After a relation is removed.

## Legacy

- `naticore_relation_added`, `naticore_relation_removed`, `naticore_get_related_args`, `naticore_content_relations_allowed` — Prefer `ncr_*` equivalents for new code.

For building addons, see [Building addons](/extending/building-addons).

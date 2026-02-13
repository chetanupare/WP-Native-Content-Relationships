---
title: Hooks & Filters
---

# Hooks & Filters

## Actions

| Hook | Arguments | When |
|------|------------|------|
| `ncr_relation_added` | `$relation_id`, `$from_id`, `$to_id`, `$type` | After a relation is created |
| `ncr_relation_removed` | `$from_id`, `$to_id`, `$type` | After a relation is removed |
| `naticore_relation_added` | (legacy) | Same as above |
| `naticore_relation_removed` | (legacy) | Same as above |

## Filters

| Filter | Arguments | Purpose |
|--------|------------|---------|
| `ncr_get_related_args` | `$args`, `$from_id`, `$type` | Modify query args for `ncr_get_related()` |
| `ncr_max_relationships` | `$max`, `$from_id`, `$type` | Override max_connections for a type |
| `ncr_skip_relationship_query` | `$skip`, `$query` | Skip plugin JOIN/WHERE in WP_Query (e.g. for future core API) |
| `naticore_content_relations_allowed` | (legacy) | Allow/disallow relation queries |
| `naticore_get_related_args` | (legacy) | Same as ncr_get_related_args |

Use `ncr_*` hooks for new code.

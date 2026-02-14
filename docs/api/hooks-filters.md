---
title: Hooks & Filters
description: Actions and filters — relation added/removed, get_related args, duplicate post, allow/block relations.
---

# Hooks & Filters

## Actions

| Hook | Arguments | When |
|------|------------|------|
| `ncr_relation_added` | `$relation_id`, `$from_id`, `$to_id`, `$type` | After a relation is created |
| `ncr_relation_removed` | `$from_id`, `$to_id`, `$type` | After a relation is removed |
| `naticore_after_duplicate_post` | `$from_post_id`, `$to_post_id`, `$result` | After relations are copied (e.g. duplicate-post plugins). `$result` has `copied`, `skipped`, `errors` |
| `naticore_relation_added` | (legacy) | Same as ncr_relation_added |
| `naticore_relation_removed` | (legacy) | Same as ncr_relation_removed |

## Filters

| Filter | Arguments | Purpose |
|--------|------------|---------|
| `naticore_relation_is_allowed` | `$allowed`, `$context` | Block or allow a specific relation. `$context`: `from_id`, `to_id`, `type`. Return `false` to block. |
| `ncr_get_related_args` | `$args`, `$from_id`, `$type` | Modify query args for `ncr_get_related()` |
| `ncr_max_relationships` | `$max`, `$from_id`, `$type` | Override max_connections for a type |
| `ncr_skip_relationship_query` | `$skip`, `$query` | Skip plugin JOIN/WHERE in WP_Query (e.g. for future core API) |
| `naticore_content_relations_allowed` | (legacy) | Allow/disallow relation queries |
| `naticore_get_related_args` | (legacy) | Same as ncr_get_related_args |

## Helper: copy relations on duplicate

```php
naticore_copy_relations( $from_post_id, $to_post_id, $relation_types = null );
```

Returns array with `copied`, `skipped`, `errors`. Used by [Duplicate Post](/integrations/duplicate-post) integration; call from your own duplicate logic if needed.

Use `ncr_*` hooks for new code.

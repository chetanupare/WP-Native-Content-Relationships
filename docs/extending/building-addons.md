---
title: Building Addons
---

# Building Addons

You can ship a separate plugin or theme that uses and extends Native Content Relationships.

## Requirements

- Native Content Relationships installed and active.
- Use the public [PHP API](/api/php-api), [WP_Query](/api/wp-query), and [hooks](/api/hooks-filters).
- Register custom types with [ncr_register_relation_type](/extending/custom-types).

## Checklist

1. **Dependency check** — Ensure NCR is loaded (e.g. `class_exists( 'NATICORE_Relation_Types' )` or `function_exists( 'ncr_add_relation' )`) before calling its API.
2. **Relation types** — Register your types on `init` (priority 20 or later) so they appear in admin and in queries.
3. **Hooks** — Use `ncr_relation_added` / `ncr_relation_removed` for side effects (e.g. cache invalidation, sync to another system).
4. **Filters** — Use `ncr_get_related_args` or `ncr_max_relationships` to adjust behavior without forking the plugin.

## Distribution

- Do not bundle or rename the main plugin; require it as a dependency and document it in your readme.
- Follow WordPress plugin guidelines and the [Stability & Backward Compatibility](https://github.com/chetanupare/WP-Native-Content-Relationships#readme) notes when relying on specific API shapes.

See [Extension hooks](/extending/extension-hooks) for additional integration points.

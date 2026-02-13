---
title: Database Schema
---

# Database Schema

Relationships are stored in a single table.

## Table: `wp_content_relations`

The table name is prefixed with the WordPress table prefix (e.g. `wp_content_relations`).

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `from_id` | bigint | Source object ID |
| `from_type` | varchar | `post`, `user`, or `term` |
| `to_id` | bigint | Target object ID |
| `to_type` | varchar | `post`, `user`, or `term` |
| `type` | varchar | Relationship type slug |
| `relation_order` | int | Optional manual order (when enabled) |
| `created_at` | datetime | Creation time |

## Indexes

- Composite indexes on `(from_id, type)` and `(to_id, type)` for lookups by source or target.
- Covering index `type_lookup (type, from_id, to_id)` for type-scoped queries and integrity checks.

## Schema version

`NCR_SCHEMA_VERSION` (and the option `ncr_schema_version`) control when migrations run. Schema is stable in the 1.x line; changes are additive or migrated with a documented upgrade path.

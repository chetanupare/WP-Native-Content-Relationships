---
title: Architecture
---

# Architecture overview

High-level architecture of the Native Content Relationships plugin.

## System components

- **Core:** Public API (`ncr_add_relation`, etc.) → Relationship Type Registry → Database (`wp_content_relations`) + Object Cache
- **Integrity:** WP-CLI / Cron → Repair logic → Schema & constraint checks → reads/writes DB
- **Interfaces:** Admin Meta Box, REST API, Elementor Dynamic Tags → all use the Public API

*(A Mermaid diagram of this flow is in the repo: `docs/ARCHITECTURE.md`.)*

## Key components

### 1. Registry layer (`class-relation-types.php`)

- **Responsibility**: Defines allowed relationship types and their constraints.
- **Constraints**:
  - `max_connections` (e.g. One Post to One Author)
  - `direction` (e.g. `bidirectional`, `from_to`, `to_from`)
  - `object_types` (e.g. `post`, `user`, `term`)

### 2. Database layer (`class-database.php`)

- **Table**: `wp_content_relations`
- **Indexing**:
  - Composite indexes on `(from_id, type)` and `(to_id, type)`.
  - Covering index on `(type, from_id, to_id)`.
- **Schema guard**: `NCR_SCHEMA_VERSION` ensures DB structure updates run only when needed.

### 3. Integrity engine (`class-integrity.php`)

- **Responsibility**: Detects and fixes data inconsistencies.
- **Features**:
  - **Chunked processing**: Handles large datasets (1M+ rows) in small batches.
  - **Orphan detection**: Removes relationships pointing to deleted posts/users.
  - **Constraint enforcement**: Checks `max_connections`.

### 4. Public API (`class-api.php`)

- **Facade**: Simple, safe interface (`ncr_add_relation`, `ncr_get_related`).
- **Validation**: Enforces registry rules before writing to DB.
- **Caching**: Object caching to minimize DB queries.

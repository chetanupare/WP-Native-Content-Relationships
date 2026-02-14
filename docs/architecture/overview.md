---
title: Architecture Overview
description: High-level architecture of Native Content Relationships — core, registry, database, integrity, API.
---

# Architecture Overview

High-level architecture of the Native Content Relationships plugin.

## System components

- **Core:** Public API (`ncr_add_relation`, `ncr_get_related`, etc.) → Relationship Type Registry → Database (`wp_content_relations`) + Object Cache
- **Integrity:** WP-CLI / scheduled tasks → Repair logic → Schema and constraint checks → reads/writes DB
- **Interfaces:** Admin meta box, REST API, Elementor Dynamic Tags, Gutenberg block — all use the Public API

## Key components

### 1. Registry layer

Defines allowed relationship types and their constraints: `max_connections`, `direction`, `object_types`. Implemented in the relation-types layer.

### 2. Database layer

- **Table:** `wp_content_relations`
- **Indexes:** Composite on `(from_id, type)` and `(to_id, type)`; covering index on `(type, from_id, to_id)`.
- **Schema guard:** `NCR_SCHEMA_VERSION` ensures DB updates run only when needed.

See [Schema](/architecture/schema).

### 3. Integrity engine

- Chunked processing for large datasets (1M+ rows)
- Orphan detection (relations to deleted posts/users)
- Constraint enforcement (e.g. max_connections)

### 4. Public API

Facade for create/read/remove; validates against the registry and uses object cache where appropriate.

---
title: Relationship Types
---

# Relationship Types

Relationship types define the *kind* of link between two objects (e.g. "related to", "parent of").

## Built-in types

| Type | Direction | Typical use |
|------|-----------|-------------|
| **related_to** | Bidirectional | Related posts, related products |
| **parent_of** | Unidirectional | Parent–child (e.g. course → lessons) |
| **references** | Unidirectional | Citations, references |
| **depends_on** | Unidirectional | Dependencies |

## Object types

Each relation has a **from** (source) and **to** (target). Each side is one of:

- **post** — Any post type (post, page, product, etc.)
- **user** — WordPress user
- **term** — Taxonomy term

A relationship type declares which from/to combinations are allowed (e.g. post → post, post → user).

## Constraints

Types can define:

- **max_connections** — e.g. at most one "author" per post
- **direction** — `from_to`, `to_from`, or `bidirectional` (see [Direction](/core-concepts/direction))

## Custom types

Register custom types with `ncr_register_relation_type()`. See [Custom types](/extending/custom-types).

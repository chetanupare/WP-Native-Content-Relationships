---
title: Relationship Types
description: Built-in and custom relation types — post–post, user–post, post–term. Direction, constraints, ncr_register_relation_type.
---

# Relationship Types

Relationship types define the *kind* of link between two objects (e.g. "related to", "parent of").

## Built-in types (post–post)

| Type | Direction | Typical use |
|------|-----------|-------------|
| **related_to** | Bidirectional | Related posts, related products |
| **parent_of** | Unidirectional | Parent–child (e.g. course → lessons) |
| **references** | Unidirectional | Citations, references |
| **depends_on** | Unidirectional | Dependencies |

## Built-in types (user–post)

| Type | Direction | Typical use |
|------|-----------|-------------|
| **favorite_posts** | User → post | User’s favorite posts |
| **bookmarked_by** | User → post | User bookmarks (same storage, different label) |
| **authored_by** | Post → user | Multiple authors / contributors |

## Built-in types (post–term)

| Type | Direction | Typical use |
|------|-----------|-------------|
| **categorized_as** | Post → term | Featured categories, curated collections |

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

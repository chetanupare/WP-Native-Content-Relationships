---
title: Direction
---

# Direction

Relations can be one-way or bidirectional.

## Options

| Direction | Meaning |
|----------|---------|
| **from_to** | Only from → to is stored and queryable; reverse lookups are not automatic. |
| **to_from** | Same idea, but the stored direction is to → from. |
| **bidirectional** | One row represents both directions; you can query "from this post" or "to this post". |

## Choosing a direction

- Use **bidirectional** when the relationship is symmetric (e.g. "related to", "linked with").
- Use **from_to** or **to_from** when the link has a clear direction (e.g. "parent of", "depends on", "authored by").

The plugin enforces direction when you create relations: for one-way types, only the allowed direction can be written.

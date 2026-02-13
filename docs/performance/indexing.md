---
title: Indexing
---

# Indexing

The plugin relies on a composite index for fast lookups and integrity checks.

## Primary index

- **Name:** `type_lookup`
- **Columns:** `(type, from_id, to_id)`
- **Purpose:** Covering index for relation-type + source/target lookups; avoids full table scans for common queries and integrity audits.

## Query impact

- Point lookups and constraint checks scale as **O(log n)**.
- Index-only scans keep latency sub-2ms at 1M rows under typical workloads.

## Schema stability

The table and index layout are considered stable. Changes are versioned and documented in the [database schema](/core-concepts/database-schema). Do not add or drop indexes outside plugin upgrades.

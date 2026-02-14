---
title: Admin & Tools
description: Overview table, Integrity check, Orphaned relations, Import/Export, Site Health, Auto-relations.
---

# Admin & Tools

NCR provides several admin screens and background tools to view, clean, and maintain relationship data. Access most of them under **Settings → Content Relationships** (tabs or sub-pages depending on version).

---

## Overview table

A read-only **list table** of all relationships in the database.

- **Columns:** From, Type, To, Direction, Date.
- **Sortable:** By date, type.
- **Pagination:** Configurable per page (e.g. 20 items).
- **Use case:** Audit relations, find by type or post.

Location: typically **Content Relationships → Overview** or similar in the plugin menu.

---

## Integrity check

Finds and optionally **removes invalid relationships**: rows where the source or target post/user/term no longer exists (e.g. content was deleted).

- **Automatic:** Runs once per day in the background (admin init). No UI required.
- **Manual (WP-CLI):**  
  `wp content-relations check` — report only  
  `wp content-relations check --fix` — remove invalid rows  
  `wp content-relations check --fix --verbose` — list each cleaned ID  
  `wp content-relations sync` — same as check with optional `--fix` (alias behavior).
- **Batch size:** Use `--batch-size=500` for large tables.

See [WP-CLI](/api/wp-cli#count-and-verify).

---

## Orphaned relationships

**Orphaned** = relations whose from_id or to_id points to deleted content. The **Orphaned** tool checks for these (e.g. once per week) and can show an **admin notice** when the count is greater than zero, with a link to run cleanup (Integrity check) or view the overview.

- **Notice:** Dismissible; encourages running an integrity check or visiting the tools page.
- **Cleanup:** Use **Integrity check** with **Fix** (admin or WP-CLI) to delete orphaned rows.

---

## Import / Export

**Export:** Download all relationships as a **JSON file**. Use before migrations or bulk changes.

**Import:** Upload a previously exported JSON file. **Adds** relationships; does not delete existing ones. Duplicates (same from_id, to_id, type) are skipped.

- **Location:** **Settings → Content Relationships → Import/Export** (or Tools submenu).
- **Permissions:** `manage_options` (administrator).
- **Use case:** Backup, migrate between sites (ensure post/user/term IDs match on destination), or restore after a mistake.

---

## Site Health

NCR adds a **Relationship Integrity** test under **Tools → Site Health**.

- **What it does:** Runs a quick integrity audit (e.g. dry run, small batch). If invalid relations are found, status is “Recommended” and the description suggests running a manual integrity check.
- **Action link:** Points to the Integrity/Tools page so you can run a full check or fix.

---

## Auto-relations (optional)

**Automatic relations on publish:** When enabled in settings, publishing a post can automatically create a relationship to its **parent** (e.g. page hierarchy) using the type **part_of**.

- **Setting:** **Settings → Content Relationships** — enable “Auto relation” and choose post types (e.g. post, page).
- **Behavior:** On `publish_{post_type}`, if the post has a parent, NCR creates a `part_of` relation from the new post to the parent (if not already related).
- **Use case:** Keep “child” content linked to parent pages without manual linking.

---

## Summary

| Feature | Where | Purpose |
|---------|--------|---------|
| Overview table | Content Relationships menu | View all relations |
| Integrity check | Background + WP-CLI `check --fix` | Remove invalid/orphaned rows |
| Orphaned notice | Admin notice (weekly check) | Alert when orphans exist |
| Import/Export | Settings → Content Relationships | Backup, migrate, restore |
| Site Health test | Tools → Site Health | Quick integrity status |
| Auto-relations | Settings (optional) | Auto link to parent on publish |

---

## See also

- [WP-CLI](/api/wp-cli) — `list`, `add`, `remove`, `check`, `sync`, `schema`
- [Troubleshooting](/guide/troubleshooting) — Duplicate or wrong relations after migration

# FINAL CODE-VERIFIED PRODUCT IA & ADMIN UX REPORT

**Date**: August 21, 2026
**Status**: Code-level validation complete — every finding traced to source

---

## Executive Summary

All 19 phases of code-level validation complete. **3 critical findings** from the original audit require correction:

| # | Finding | Risk | Action |
|---|---------|------|--------|
| 1 | Constraints page is NOT just cardinality — it has constraint rules (from/to/type allowed) with no other UI | **HIGH** | KEEP Constraints page |
| 2 | Integrity/Orphaned classes have NO admin pages — they are silent background services | **MEDIUM** | No change needed |
| 3 | Auto Relations has NO admin page — configured entirely via Settings | **LOW** | No change needed |

---

## Phase 1: Admin Menu Registration (Verified)

### Stitch Admin Registration (`class-stitch-admin.php:141-241`)

| Line | Slug | Type | Label | Renderer |
|------|------|------|-------|----------|
| 155 | `naticore` | top-level | Relationships | — |
| 162 | `naticore` (default) | submenu | Relationships | `render_relationships()` |
| 168 | `naticore-types` | submenu | Relationship Types | `render_types()` |
| 174 | `naticore-settings` | submenu | Settings | `render_settings()` |
| 180 | `naticore-tools` | submenu | Tools | `render_tools()` |
| 188 | `naticore-explorer` | hidden | Explorer | `render_explorer()` |
| 195 | `naticore-reports` | hidden | Reports | `render_reports()` |
| 202 | `naticore-import-export` | hidden | Import/Export | `render_import_export()` |
| 209 | `naticore-developer` | hidden | Developer | `render_developer()` |

### Legacy Tool Classes (Still Registered)

| File | Line | Slug | Parent | Status |
|------|------|------|--------|--------|
| `class-graph.php:55` | 55 | `naticore-hidden-graph` | `naticore-hidden` | Duplicate of Explorer |
| `class-analytics.php:53` | 53 | `naticore-hidden-analytics` | `naticore-hidden` | Duplicate of Reports |
| `class-bulk-manager.php:54` | 54 | `naticore-hidden-bulk` | `naticore-hidden` | Separate feature |
| `class-overview.php:329` | 329 | `naticore-overview` | `naticore-settings` | Separate feature |

### Classes with NO Admin Page Registration

| File | Purpose | Access |
|------|---------|--------|
| `class-integrity.php` | Silent daily cleanup | Admin notice only |
| `class-orphaned.php` | Weekly orphan check | Admin notice only |
| `class-import-export.php` | Export/import logic | Stitch Admin page |
| `class-auto-relations.php` | Auto-relation on publish | Settings toggle |
| `class-status.php` | Workflow transitions | AJAX from Relations page |
| `class-expiration.php` | Cron-based expiration | Stitch Admin hidden page |
| `class-permissions.php` | Role-based access | Stitch Admin hidden page |
| `class-webhooks.php` | Webhook CRUD | Stitch Admin hidden page |
| `class-constraints.php` | Constraint + cardinality rules | Stitch Admin hidden page |
| `class-presets.php` | Template presets | Stitch Admin hidden page |
| `class-site-health.php` | WP Site Health test | No page (WP admin) |
| `class-graphql.php` | WPGraphQL types | No page (API only) |

---

## Phase 2: Graph vs Explorer (VERIFIED — DUPLICATE)

### Graph (`class-graph.php:112-159`)
- Registers under `edit.php?post_type=naticore_relation` (hidden)
- HTML wrapper: `<div class="wrap">`
- Controls: filter by type (Posts/Pages/Users), max nodes (20/50/100/200), Refresh button
- Canvas: `<canvas id="naticore-graph-canvas">`
- Legend: Posts (blue), Users (green), Terms (yellow), Pages (red)
- AJAX: `ajax_get_graph_data()` with nonce + `manage_options` cap
- JS: `graph.js` loaded separately

### Explorer (`class-stitch-admin.php:1108-1160`)
- Registers under main "Relationships" menu (hidden)
- HTML wrapper: Stitch Admin NC design system wrapper
- Controls: **IDENTICAL** — filter by type, max nodes, Refresh button
- Canvas: **IDENTICAL** — `<canvas id="naticore-graph-canvas">`
- Legend: **IDENTICAL** — same colors and labels
- JS: Same graph.js loaded via Stitch Admin

**VERDICT**: Explorer is a CSS-reskinned version of Graph. Both render the same canvas visualization with the same controls and data source. Graph is redundant.

**RECOMMENDATION**: Deprecate `class-graph.php`. Keep Explorer only.

---

## Phase 3: Reports vs Analytics (VERIFIED — OVERLAP)

### Analytics (`class-analytics.php:168-316`)
- Registers under `edit.php?post_type=naticore_relation` (hidden)
- Data source: `$this->get_analytics()` — custom SQL queries
- Layout:
  - 4 summary cards: Total Relationships, Connected Posts, Orphaned Posts, Avg per Post
  - Relationships by Type (table with progress bars)
  - Most Referenced (Incoming) / Most Connected (Outgoing) — **2-column side-by-side**
  - Activity (Last 30 Days) — CSS bar chart
- Uses standard WordPress `widefat` tables

### Reports (`class-stitch-admin.php:1165-1307+`)
- Registers under main "Relationships" menu (hidden)
- Data source: Same SQL queries but inline in render method
- Layout:
  - Relationship Growth Over Time — 12-month SVG line chart
  - Top Relationship Types — CSS pie chart with conic-gradient
  - Most Connected Content — top 5 list
- Uses NC design system CSS

**VERDICT**: Reports is a visually modernized version of Analytics. Analytics has more detailed tables (incoming/outgoing breakdown, percentage bars). Reports has SVG charts (line chart, pie chart). They overlap significantly.

**RECOMMENDATION**: Keep Reports as the modern replacement. Deprecate Analytics class, or merge Analytics' detailed tables into Reports.

---

## Phase 4: Constraints (VERIFIED — CRITICAL CORRECTION)

### Actual Feature Set (`class-constraints.php:39-482`)

**Feature 1: Constraint Rules** (lines 39-82, 91-111)
- Define which `from_type` + `to_type` + `relationship_type` combinations are ALLOWED
- Stored in `ncr_constraints` option as array
- Admin form: From Type, To Type, Relationship Type, Allowed (yes/no)
- Checked in `is_relation_allowed()` at line 160+

**Feature 2: Cardinality Rules** (lines 180-207, 120-151)
- Define `max_from` and `max_to` per relationship type
- Stored in `ncr_cardinality` option
- Admin form: Type, Max From, Max To
- Checked in `is_relation_allowed()` at line 160+

**Feature 3: Duplicate Prevention** (lines 160-173)
- Validates that a relationship doesn't already exist
- Uses `ncr_no_duplicates` option

**Admin UI** (`render_admin_page()` at line 380+):
- Two separate forms: Add Constraint Rule + Add Cardinality Rule
- Table showing existing constraint rules
- Table showing existing cardinality rules
- Uses Stitch Admin wrapper

### Previous Audit Claim
> "Constraints is just cardinality enforcement. DELETE the Constraints page. Fold cardinality into Relationship Types."

### Code-Level Truth
- Constraint rules (from/to/type allowed) are a SEPARATE feature from cardinality
- Constraint rules have NO OTHER UI location
- The admin page handles BOTH features
- Deleting this page would REMOVE the only UI for configuring which post type combinations are allowed

**VERDICT**: Previous audit was INCORRECT and DANGEROUS. Constraints page must be KEPT.

**RECOMMENDATION**: Keep Constraints page. Consider renaming to "Connection Rules" for clarity.

---

## Phase 5: Import/Export (VERIFIED — CONSOLIDATED)

### Legacy (`class-import-export.php:43-84`)
- `render_import_export_page()` — two sections: Export + Import
- Does NOT register admin page (no `add_submenu_page`)
- Logic: `handle_export()` and `handle_import()` on `admin_init`
- JSON format with duplicate prevention

### Stitch Admin (`class-stitch-admin.php:1404-1487`)
- `render_import_export()` — tabbed UI (Import/Export tabs)
- Same functionality, modernized design
- Uses NC design system with dropzone styling

**VERDICT**: Stitch Admin is the primary UI. Legacy class provides the logic but no page.

**RECOMMENDATION**: Keep both. Legacy handles form processing; Stitch Admin handles rendering.

---

## Phase 6: Status Workflows (VERIFIED — NO PAGE)

### Status (`class-status.php:1-251`)
- 3 built-in workflows defined in code (line 64-86):
  - **Hiring**: proposed → accepted → active → completed
  - **Editorial**: draft → in_review → approved → published
  - **Sponsorship**: proposal → negotiation → active → completed
- Custom workflows via `ncr_workflows` option
- AJAX handler `ajax_change_status()` (line 213) called from Relationships page
- Hooks: `naticore_relation_is_allowed` filter, `naticore_relation_added` action
- No admin page for managing workflows

**VERDICT**: Backend feature, no admin page. Workflows are configurable via `ncr_workflows` option but have no UI.

**RECOMMENDATION**: Consider adding a workflow management page in a future release.

---

## Phase 7: Permissions (VERIFIED — HAS PAGE)

### Permissions (`class-permissions.php:322 lines`)
- Full admin page with role-based access configuration
- Hooks into `map_meta_cap` filter (line 270+)
- Can restrict per relationship type
- Settings stored in `ncr_permissions` option
- Registered as hidden submenu under `naticore-hidden-permissions`

**VERDICT**: Complete feature with its own admin page.

**RECOMMENDATION**: Keep as-is.

---

## Phase 8: Webhooks (VERIFIED — HAS PAGE)

### Webhooks (`class-webhooks.php:342 lines`)
- Full CRUD for webhook URLs, events, HMAC secrets
- Admin UI page for managing webhooks
- Registered as hidden submenu under `naticore-hidden-webhooks`
- Handles: `created`, `deleted`, `updated` events

**VERDICT**: Complete feature with its own admin page.

**RECOMMENDATION**: Keep as-is.

---

## Phase 9: Expiration (VERIFIED — HAS PAGE)

### Expiration (`class-expiration.php:267 lines`)
- Cron-based relationship expiration (daily check)
- Admin UI page with "Run Expiration Check Now" button
- Shows expiring connections table with days remaining
- Registered as hidden submenu under `naticore-hidden-expiration`
- Settings: `expiration_date` and `is_expired` meta keys

**VERDICT**: Complete feature with its own admin page.

**RECOMMENDATION**: Keep as-is.

---

## Phase 10: Presets (VERIFIED — HAS PAGE)

### Presets (`class-presets.php:301 lines`)
- 8+ templates: Event/Speaker, Course/Instructor, Product/Brand, etc.
- Template cards in admin UI with one-click creation
- Registered as hidden submenu under `naticore-hidden-presets`
- Creates relationship types with pre-configured settings

**VERDICT**: Complete feature with its own admin page.

**RECOMMENDATION**: Keep as-is.

---

## Phase 11: Integrity/Orphaned (VERIFIED — NO PAGES)

### Integrity (`class-integrity.php:333 lines`)
- Silent daily cleanup of invalid relationships
- Runs on `admin_init` once per day
- Checks: duplicates, orphaned, unregistered types, constraint violations, directional inconsistencies
- Shows admin notice if issues found
- NO admin page

### Orphaned (`class-orphaned.php:122 lines`)
- Weekly check for orphaned relationships (from/to pointing to deleted posts)
- Shows admin notice with count
- NO admin page

**VERDICT**: Both are silent background services. No admin pages needed.

**RECOMMENDATION**: Keep as-is. Consider adding a manual "Run Check" button on Tools page.

---

## Phase 12: Auto Relations (VERIFIED — NO PAGE)

### Auto Relations (`class-auto-relations.php:93 lines`)
- Automatically creates `part_of` relationship when post is published with parent
- Only controlled via Settings toggle (`auto_relation_enabled`)
- NO admin page
- Registered types: `part_of` (unidirectional)

**VERDICT**: Backend-only feature, configured via Settings.

**RECOMMENDATION**: Keep as-is.

---

## Phase 13: Developer Page (VERIFIED — HAS PAGE)

### Developer (`class-developer.php:143+ lines`)
- PHP API reference, REST API endpoints table, hooks list, capabilities, security
- Registered as hidden submenu under `naticore-hidden-developer`
- Uses Stitch Admin wrapper

**VERDICT**: Complete feature with its own admin page.

**RECOMMENDATION**: Keep as-is.

---

## Phase 14-15: Site Health & GraphQL (VERIFIED — NO PAGES NEEDED)

### Site Health (`class-site-health.php:59 lines`)
- Adds test to WP Site Health dashboard
- No admin page needed

### GraphQL (`class-graphql.php:49 lines`)
- Registers WPGraphQL types and connections
- Requires WPGraphQL plugin
- No admin page needed

**VERDICT**: Background features, no pages needed.

**RECOMMENDATION**: Keep as-is.

---

## Phase 16: Settings Architecture (VERIFIED — DUAL PATH)

### Legacy Settings (`class-settings.php:1330 lines`)
- Tabbed system: Get Started, General, Relationship Types, WooCommerce, Import/Export, Privacy, Developer (debug-only)
- Menu registration COMMENTED OUT (line 57) — replaced by Stitch Admin
- Registers settings on `admin_init` priority 20
- Tab slug system: `naticore-settings`, `naticore-settings-get-started`, etc.

### Stitch Admin Settings (`class-stitch-admin.php:1492-1620+`)
- Flat grid with toggles for Performance, Developer, Privacy, System
- Reads from same `naticore_settings` option
- NO tab system — all sections on one page

**VERDICT**: Settings has TWO rendering paths. Stitch Admin is the primary UI. Legacy tabbed system is dead code (menu registration commented out).

**RECOMMENDATION**: Remove the legacy tabbed system. Keep Stitch Admin as the single UI.

---

## Phase 17: Overview (VERIFIED — SEPARATE FEATURE)

### Overview (`class-overview.php:425 lines`)
- WP_List_Table with from, type, to, direction, date columns
- Registers under `naticore-settings` parent (labeled "Internal Overview")
- Has pagination, search, bulk actions
- Different from Relationships page (which uses Stitch Admin table)

**VERDICT**: Internal admin table view, separate from Relationships.

**RECOMMENDATION**: Consider merging with Relationships page or keeping as "Advanced View."

---

## Phase 18: Bulk Manager (VERIFIED — SEPARATE FEATURE)

### Bulk Manager (`class-bulk-manager.php:301 lines`)
- Registers under `edit.php?post_type=naticore_relation` (hidden)
- Bulk operations UI with AJAX handlers
- Different from Relationships page

**VERDICT**: Separate feature for bulk operations.

**RECOMMENDATION**: Keep as-is or merge into Relationships page.

---

## Phase 19: Final Synthesis

### Admin Page Hierarchy (Code-Verified)

```
Relationships (top-level)
├── Relationships (visible) — render_relationships()
│   └── Uses: class-api.php, class-rest-api.php, class-status.php (AJAX)
├── Relationship Types (visible) — render_types()
│   └── Uses: class-relation-types.php
├── Settings (visible) — render_settings()
│   └── Uses: class-settings.php (register_settings only)
├── Tools (visible) — render_tools()
│   ├── Explorer (hidden) — render_explorer()
│   │   └── Uses: graph.js, AJAX from class-graph.php
│   ├── Reports (hidden) — render_reports()
│   │   └── Inline SQL queries
│   ├── Import/Export (hidden) — render_import_export()
│   │   └── Uses: class-import-export.php (logic)
│   └── Developer (hidden) — render_developer()
│       └── Uses: class-developer.php
├── Hidden Sub-Pages (via Stitch Admin routing)
│   ├── naticore-hidden-expiration — class-expiration.php
│   ├── naticore-hidden-permissions — class-permissions.php
│   ├── naticore-hidden-webhooks — class-webhooks.php
│   ├── naticore-hidden-constraints — class-constraints.php
│   └── naticore-hidden-presets — class-presets.php
└── Legacy Hidden (via old class registrations)
    ├── naticore-hidden — parent page (no renderer)
    ├── naticore-hidden-graph — class-graph.php (DUPLICATE of Explorer)
    ├── naticore-hidden-analytics — class-analytics.php (DUPLICATE of Reports)
    ├── naticore-hidden-bulk — class-bulk-manager.php
    └── naticore-overview — class-overview.php (under naticore-settings)
```

### Recommended Cleanup Actions

| Priority | Action | Files Affected | Risk |
|----------|--------|----------------|------|
| P0 | KEEP Constraints page — do NOT delete | — | — |
| P1 | Deprecate `class-graph.php` (duplicate of Explorer) | `class-graph.php` | Low |
| P1 | Deprecate `class-analytics.php` (duplicate of Reports) | `class-analytics.php` | Low |
| P1 | Remove legacy tabbed Settings system | `class-settings.php` | Low |
| P2 | Remove dead `class-settings-old.php` | `class-settings-old.php` | None |
| P2 | Remove dead `render_activation_notice()` | `class-admin.php:621` | None |
| P2 | Remove orphaned `add_submenu_page` from Overview | `class-overview.php:329` | Low |
| P3 | Add manual "Run Integrity Check" button on Tools page | `class-integrity.php`, `class-stitch-admin.php` | Low |
| P3 | Add workflow management UI for Status | `class-status.php` | Medium |

### Navigation Simplification

**Current**: 4 visible + ~15 hidden pages
**Recommended**: 4 visible + 5 hidden (remove duplicates, consolidate)

```
Relationships (top-level)
├── Relationships — CRUD + Status AJAX
├── Types — Type management
├── Settings — Performance, Developer, Privacy, System toggles
└── Tools — Hub linking to:
    ├── Explorer — Visual graph canvas
    ├── Reports — Charts and analytics
    ├── Import/Export — JSON import/export
    ├── Developer — API reference
    ├── Expiration — Cron management
    ├── Permissions — Role-based access
    ├── Webhooks — Webhook CRUD
    ├── Constraints — Connection rules + cardinality
    └── Presets — Template library
```

---

## Appendix: File Line References

| File | Key Lines | Purpose |
|------|-----------|---------|
| `class-stitch-admin.php` | 141-241 | Menu registrations |
| `class-stitch-admin.php` | 306-500 | Tools hub rendering |
| `class-stitch-admin.php` | 507-635 | Developer page |
| `class-stitch-admin.php` | 636-865 | Types management |
| `class-stitch-admin.php` | 869-1105 | Relationships table |
| `class-stitch-admin.php` | 1108-1160 | Explorer (graph) |
| `class-stitch-admin.php` | 1165-1400 | Reports & Analytics |
| `class-stitch-admin.php` | 1404-1487 | Import/Export |
| `class-stitch-admin.php` | 1492-1620 | Settings |
| `class-graph.php` | 112-159 | Graph render_page |
| `class-graph.php` | 264-277 | AJAX handler |
| `class-analytics.php` | 168-316 | Analytics render_page |
| `class-constraints.php` | 39-82 | Constraint rules |
| `class-constraints.php` | 120-151 | Cardinality rules |
| `class-constraints.php` | 160-173 | Duplicate prevention |
| `class-constraints.php` | 380-482 | Admin UI |
| `class-settings.php` | 56-57 | Menu registration commented out |
| `class-settings.php` | 106-122 | Tab system |
| `class-settings.php` | 176-200 | Settings registration |
| `class-import-export.php` | 43-84 | render_import_export_page |
| `class-integrity.php` | 57-89 | Daily check on admin_init |
| `class-orphaned.php` | 32-63 | Weekly check on admin_init |
| `class-auto-relations.php` | 32-39 | Settings-gated constructor |
| `class-status.php` | 64-86 | Default workflows |
| `class-status.php` | 213-250 | AJAX status change |
| `class-permissions.php` | 1-322 | Full admin page |
| `class-webhooks.php` | 1-342 | Full CRUD + admin page |
| `class-expiration.php` | 1-267 | Cron + admin page |
| `class-presets.php` | 1-301 | Templates + admin page |
| `class-admin.php` | 621+ | Dead activation notice |
| `class-overview.php` | 320-340 | Menu registration |

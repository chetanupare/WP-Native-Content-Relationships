# NATIVE CONTENT RELATIONSHIPS — FINAL PRODUCT IA & ADMIN UX AUDIT

**Date:** 2026-08-21
**Plugin Version:** 1.4.0
**Scope:** Code-only analysis — every recommendation references actual implementation files

---

## 1. Executive Recommendation

**The correct answer is: the problem is hiding too many features, not that the navigation is too simple.**

The current 4-item sidebar (Relationships, Types, Settings, Tools) is structurally sound but functionally broken. The Tools page is a card-grid hub that links to 8+ hidden sub-pages, but none of these sub-pages appear in the WordPress sidebar — they are only accessible via direct URL or by clicking through the Tools hub. This creates a "mystery meat navigation" problem where:

- Users who bookmark a sub-page (e.g., `naticore-explorer`) cannot find their way back via the sidebar
- The sidebar shows "Tools" but the Tools page is just a link directory, not a functional page
- Features like Permissions, Expiration, Webhooks, and Presets have their own registered hidden pages but no sidebar presence
- The Settings page in Stitch Admin is a flat grid of toggles — no tabs, no progressive disclosure

**Recommended architecture: 5-item sidebar with tabbed Tools and tabbed Settings.**

```
Relationships (top-level)
├── Relationships        ← primary CRUD + list
├── Types                ← type configuration (with Cardinality, Bidirectional, Status, Expiration, Permissions as advanced sections)
├── Tools                ← tabbed: Graph | Bulk Manager | Import/Export | Database Health
├── Settings             ← tabbed: General | Editor | Integrations | Developer | Privacy
└── (no 5th item)
```

This keeps the sidebar at 4 items (the WordPress-idiomatic maximum for a plugin menu) while making all 22+ hidden pages discoverable through tabs within Tools and Settings.

---

## 2. Product Philosophy

### What the plugin IS:
- A **lightweight relationship engine** for WordPress
- Connect posts ↔ posts, posts ↔ users, posts ↔ terms
- Define relationship types with cardinality and direction
- Query relationships via PHP, REST, GraphQL, WP_Query, WP-CLI
- Integrate with Gutenberg, Elementor, and any theme

### What the plugin is NOT:
- A content management suite
- A dashboard/analytics platform
- A general-purpose admin UI framework
- A replacement for WooCommerce, ACF, or any integration it supports

### Design principles:
1. **Core workflow in primary navigation** — creating/managing relationships must be effortless
2. **Advanced management in Tools** — power features accessible but not cluttering the primary flow
3. **Configuration in Settings** — everything else behind a settings page
4. **Editor features belong in the editor** — Gutenberg sidebar, Classic metabox, Elementor tags — no admin page needed
5. **Developer features are documentation** — REST API, hooks, PHP API don't need admin pages; they need docs

---

## 3. Final Navigation Tree

```
Relationships (dashicons-networking, position 30)
│
├── Relationships          ← Stitch Admin render_relationships()
│                            Table: from_id, type, to_id, status, date
│                            Actions: search, filter by type, bulk delete, new connection modal
│
├── Types                  ← Stitch Admin render_types()
│                            Table: name/slug, source, target, cardinality, status
│                            Actions: create type modal (Basic → Advanced progressive disclosure)
│
├── Tools                  ← Hub page with cards linking to tabbed sub-pages
│   ├── Graph              ← Stitch Admin render_explorer() — force-directed graph (graph.js)
│   ├── Bulk Manager       ← class-bulk-manager.php — bulk delete, bulk type change
│   ├── Import/Export      ← class-import-export.php — JSON import/export
│   └── Database Health    ← class-integrity.php + class-orphaned.php — integrity check, orphan cleanup
│
├── Settings               ← Stitch Admin render_settings() — restructured with tabs
│   ├── General            ← enabled_post_types, default_direction, manual_order, bidirectional_sync, cleanup
│   ├── Editor             ← meta box options, sidebar post types
│   ├── Integrations       ← WooCommerce, ACF, Elementor, WPML, SEO, Duplicate Post, GraphQL
│   ├── Developer          ← REST API toggle, GraphQL toggle, debug logging, system info
│   └── Privacy            ← anonymize logs, remove on uninstall
│
└── (hidden sub-pages accessible via Tools/Settings tabs or direct URL)
    ├── naticore-explorer      ← Graph (via Tools > Graph)
    ├── naticore-reports       ← Analytics (via future tab or removed)
    ├── naticore-import-export ← Import/Export (via Tools > Import/Export)
    ├── naticore-developer     ← Developer (via Settings > Developer)
    ├── naticore-overview      ← DEPRECATE (duplicate of Relationships page)
    ├── naticore-analytics     ← DEPRECATE (merge into Tools or remove)
    ├── naticore-auto-relations← DEPRECATE (move toggle to Settings > General)
    ├── naticore-bulk-manager  ← Bulk Manager (via Tools > Bulk Manager)
    ├── naticore-graph         ← DEPRECATE (use naticore-explorer)
    ├── naticore-integrity     ← Database Health (via Tools > Database Health)
    ├── naticore-orphaned      ← Database Health (via Tools > Database Health)
    ├── naticore-presets       ← DEPRECATE (presets are type templates — belong in Types page)
    ├── naticore-permissions   ← Move to Settings > General (as a section)
    ├── naticore-expiration    ← Move to Types page (as advanced section per type)
    ├── naticore-status        ← Move to Types page (as advanced section per type)
    ├── naticore-webhooks      ← Move to Settings > Developer
    ├── naticore-constraints   ← DEPRECATE (constraints = cardinality, already in Types)
    ├── naticore-acf           ← Settings > Integrations
    ├── naticore-hidden        ← Internal parent — keep hidden
```

---

## 4. Feature Classification Matrix

| Feature | Classification | Current Location | Recommended Location | Action | Priority |
|---------|---------------|-----------------|---------------------|--------|----------|
| **Relationships (list all)** | CORE | `class-stitch-admin.php:869` render_relationships | **Relationships** sidebar item | KEEP | P0 |
| **Relationship Types** | CORE | `class-stitch-admin.php:636` render_types | **Types** sidebar item | KEEP | P0 |
| **Gutenberg sidebar** | CORE (editor) | `class-sidebar.php:54` enqueue_block_editor_assets | **Gutenberg editor** (no admin page) | KEEP | P0 |
| **Classic Editor metabox** | CORE (editor) | `class-admin.php:37` add_meta_boxes | **Classic Editor** (no admin page) | KEEP | P0 |
| **Relationship search** | CORE (editor) | `class-admin.php:213` enqueue_scripts → admin.js | **Classic Editor metabox** | KEEP | P0 |
| **Relationship metadata** | CORE (editor) | `class-admin.php:129-136` meta role/note fields | **Classic Editor metabox** | KEEP | P0 |
| **Cardinality** | CORE (config) | `class-stitch-admin.php:798` modal section 3 | **Types page** — create/edit modal | KEEP | P0 |
| **Bidirectional relationships** | CORE (config) | `class-stitch-admin.php:834` toggle in modal | **Types page** — create/edit modal | KEEP | P0 |
| **Overview (WP_List_Table)** | DUPLICATE | `class-overview.php:668` | **DEPRECATE** — Relationships page already has full list | DELETE | P1 |
| **Explorer (graph)** | ADVANCED | `class-stitch-admin.php:1108` render_explorer | **Tools > Graph** | KEEP | P1 |
| **Analytics** | DUPLICATE | `class-analytics.php:52` + Stitch Admin render_reports | **DEPRECATE** — merge stats into Relationships page header or remove | MERGE | P2 |
| **Reports** | DUPLICATE | `class-stitch-admin.php` render_reports | **DEPRECATE** — same as Analytics | DELETE | P2 |
| **Bulk Manager** | ADVANCED | `class-bulk-manager.php:257` | **Tools > Bulk Manager** | KEEP | P1 |
| **Import/Export** | ADVANCED | `class-import-export.php:206` | **Tools > Import/Export** | KEEP | P1 |
| **Auto-Relations** | ADVANCED | `class-auto-relations.php:162` | **Settings > General** (as toggle only) | MOVE | P2 |
| **AI Suggestions** | WORKFLOW (editor) | `class-ai-suggestions.php:58` auto_link_on_publish | **Classic Editor metabox** (already there) + Settings toggle | KEEP | P0 |
| **AI Auto-Linking** | WORKFLOW (editor) | `class-ai-suggestions.php:58-59` publish hooks | **Settings > General** (toggle) | MOVE | P1 |
| **Status Workflows** | WORKFLOW | `class-status.php:23` filter + `class-stitch-admin.php` hidden submenu | **Types page** — advanced section per type | MOVE | P2 |
| **Expiration** | WORKFLOW | `class-expiration.php:258` + Stitch Admin hidden submenu | **Types page** — advanced section per type | MOVE | P2 |
| **Permissions** | WORKFLOW | `class-permissions.php:312` + Stitch Admin hidden submenu | **Settings > General** (as section) | MOVE | P2 |
| **Presets (Templates)** | WORKFLOW | `class-presets.php:292` + Stitch Admin hidden submenu | **Types page** — "Use Template" button | MOVE | P2 |
| **Cloning** | INTERNAL | `class-cloning.php:38` admin_action hook (no UI) | **DEPRECATE** — orphaned, no UI triggers it | DELETE | P2 |
| **Revision History** | INTERNAL | `class-revision-history.php` (hook-only, no UI) | **KEEP as internal** — hook-only, no admin page needed | KEEP | P3 |
| **Integrity Check** | ADVANCED | `class-integrity.php:188` + Stitch Admin hidden submenu | **Tools > Database Health** | KEEP | P1 |
| **Orphaned Relationships** | ADVANCED | `class-orphaned.php:188` + Stitch Admin hidden submenu | **Tools > Database Health** | KEEP | P1 |
| **Site Health** | INTERNAL | `class-site-health.php` (WP Site Health tab) | **KEEP as internal** — already in WP Site Health | KEEP | P3 |
| **WooCommerce** | INTEGRATION | `class-woocommerce.php` (auto-activate) | **Settings > Integrations** (auto-detect, toggle) | MOVE | P2 |
| **ACF** | INTEGRATION | `class-acf.php` (auto-activate) | **Settings > Integrations** (auto-detect, toggle) | MOVE | P2 |
| **Elementor** | INTEGRATION | `class-elementor-integration.php` (auto-activate) | **Settings > Integrations** (status only) | MOVE | P2 |
| **WPML/Polylang** | INTEGRATION | `class-wpml.php` (auto-activate) | **Settings > Integrations** (auto-detect) | MOVE | P2 |
| **Yoast/RankMath** | INTEGRATION | `class-seo.php` (auto-activate) | **Settings > Integrations** (auto-detect) | MOVE | P2 |
| **Duplicate Post** | INTEGRATION | `class-duplicate-post.php` (auto-activate) | **Settings > Integrations** (auto-detect) | MOVE | P2 |
| **WPGraphQL** | DEVELOPER | `class-graphql.php` (auto-activate) | **Settings > Developer** (toggle) | MOVE | P2 |
| **WordPress AI Client** | INTEGRATION | `class-ai-suggestions.php:70` check_ai_availability | **Settings > General** (toggle + status) | MOVE | P2 |
| **REST API** | DEVELOPER | `class-rest-api.php` (8 endpoints) | **Settings > Developer** (toggle + docs link) | MOVE | P1 |
| **PHP API** | DEVELOPER | `class-api.php` (global functions) | **Developer documentation** (no admin page) | KEEP | P3 |
| **Fluent API** | DEVELOPER | `class-fluent-api.php` (global function) | **Developer documentation** (no admin page) | KEEP | P3 |
| **WP_Query integration** | DEVELOPER | `class-query.php` (filter hook) | **Developer documentation** (no admin page) | KEEP | P3 |
| **GraphQL types** | DEVELOPER | `class-graphql.php` (register_types) | **Settings > Developer** (toggle) + docs | MOVE | P2 |
| **WP-CLI** | DEVELOPER | `class-wp-cli.php` (8+ commands) | **Developer documentation** (no admin page) | KEEP | P3 |
| **Webhooks** | DEVELOPER | `class-webhooks.php` (CRUD + hidden submenu) | **Settings > Developer** (config page) | MOVE | P2 |
| **Hooks & Filters** | DEVELOPER | Stitch Admin render_developer() | **Settings > Developer** (already there) | KEEP | P3 |
| **Capabilities** | DEVELOPER | `class-developer.php` hidden submenu | **Settings > Developer** (already there) | KEEP | P3 |
| **Security** | DEVELOPER | `class-developer.php` hidden submenu | **Settings > Developer** (already there) | KEEP | P3 |

---

## 5. Relationships Page — Final UX

The Relationships page (`class-stitch-admin.php:869`) is already well-designed. Recommendations:

### Current (KEEP):
- Search bar with type filter dropdown
- Table: Source, Type, Target, Status, Date, Actions (edit/delete)
- Bulk actions (delete, export)
- Sort by (date, source ID)
- Pagination
- "New Connection" modal with source/type/target search

### Enhance (P1):
- Add **status filter** dropdown (Active/Inactive) — backend `created_at` column supports it
- Add **source object type** filter — backend has `from_type` in schema
- Add **target object type** filter — backend has `to_type` in schema
- Wire **bulk delete** AJAX handler — `class-bulk-manager.php` already has `ajax_bulk_delete`
- Wire **bulk export** — `class-import-export.php` already has export functionality
- Show **direction indicator** (→/↔) per row — already in meta box, missing from table

### Do NOT add (unless backend supports it):
- Inline editing (no backend for inline relationship editing)
- Drag-and-drop sorting (only works in meta box, not list table)
- Relationship metadata in table (metadata is per-relation-id, not per-row; would require JOIN)

---

## 6. Relationship Types Page — Final UX

The Types page (`class-stitch-admin.php:636`) has a clean table and create modal. Recommendations:

### Current (KEEP):
- Table: Name/Slug, Source, Target, Cardinality, Status
- Create modal with 4 sections: Basic Info, Endpoints, Cardinality, Advanced Settings
- Advanced toggles: Bidirectional, Sortable, REST API, Metadata

### Enhance (P1):
- Wire the **edit button** (currently `more_vert` icon with no action)
- Add **delete action** per type (with confirmation)
- Add **status toggle** (Active/Draft) per type

### Move INTO this page from elsewhere (P2):
- **Status Workflows** — add "Status Workflow" as section 5 in the create/edit modal
  - Evidence: `class-status.php:33` has `get_default_workflows()` returning hiring, editorial, sponsorship
  - Implementation: Add toggle + select in modal, wire to `NATICORE_Status` class
- **Expiration** — add "Expiration" as section 6 in the create/edit modal
  - Evidence: `class-expiration.php:258` registers hidden submenu, but config belongs per-type
  - Implementation: Add date picker + cron toggle in modal
- **Presets/Templates** — add "Use Template" button above create modal
  - Evidence: `class-presets.php:40` has 8+ templates (Event/Speaker, Course/Instructor, etc.)
  - Implementation: Button opens template picker → pre-fills create modal fields

### Do NOT add:
- Permissions per type (too complex for modal — keep in Settings)
- REST/GraphQL exposure per type (global toggle in Settings is sufficient)
- Elementor exposure per type (auto-detected, no config needed)

---

## 7. Settings Architecture — Final Tabs

The Stitch Admin Settings page (`class-stitch-admin.php:1492`) currently renders a flat grid of toggles. The old `class-settings.php` has a proper tabbed system with `get_tabs()`. Recommended final tab structure:

### Tab 1: General
**Evidence:** `class-settings.php:212` `register_general_settings()`

| Setting | Existing? | File | Purpose |
|---------|-----------|------|---------|
| Enabled Post Types | Yes | `class-settings.php:222` | Which post types show meta box/sidebar |
| Default Direction | Yes | `class-settings.php:238` | New relationships default to one-way/bidirectional |
| Manual Ordering | Yes | `class-settings.php:246` | Enable drag-and-drop sorting |
| Bidirectional Sync | Yes | `class-settings.php:254` | Auto-sync metadata on bidirectional create |
| Cleanup on Delete | Yes | `class-settings.php:270` | Cascade delete relationships when post deleted |
| Max Relationships | Yes | `class-settings.php:286` | Limit per post (0 = unlimited) |
| Auto-Relation Toggle | Yes | `class-settings.php:294` | Enable auto-link on publish |
| AI Suggestions Toggle | Yes | `class-settings.php:302` | Enable AI suggestion button in editor |
| Auto-Link Toggle | Yes | `class-settings.php:310` | Enable auto-linking on publish |
| Prevent Circular | Yes | `class-settings.php:326` | Prevent A→B→A circular relationships |

### Tab 2: Editor
**New tab** — extract from General

| Setting | Existing? | File | Purpose |
|---------|-----------|------|---------|
| Meta Box Position | No | — Future: normal/side/hidden |
| Sidebar Post Types | Partial | `class-sidebar.php:66` `is_enabled_for_post_type()` | Which types get Gutenberg sidebar |
| Suggest Button Position | No | — Future: above/below list |

### Tab 3: Integrations
**New tab** — consolidate scattered integration settings

| Integration | Existing? | File | Show When |
|-------------|-----------|------|-----------|
| WooCommerce | Yes | `class-woocommerce.php` (auto-detect) | WooCommerce installed |
| ACF | Yes | `class-acf.php` (auto-detect) | ACF installed |
| Elementor | Yes | `class-elementor-integration.php` (auto-detect) | Elementor installed |
| WPML/Polylang | Yes | `class-wpml.php` (auto-detect) | WPML or Polylang installed |
| Yoast/RankMath | Yes | `class-seo.php` (auto-detect) | Yoast or RankMath installed |
| Duplicate Post | Yes | `class-duplicate-post.php` (auto-detect) | Duplicate Post installed |
| WordPress AI Client | Yes | `class-ai-suggestions.php:70` | WP 7.0+ with AI client |

### Tab 4: Developer
**Existing** — currently only visible in debug mode (`class-settings.php:117`)

| Setting | Existing? | File | Purpose |
|---------|-----------|------|---------|
| REST API Enable | Yes | `class-stitch-admin.php:1555` | Toggle REST endpoints |
| GraphQL Enable | Yes | `class-stitch-admin.php:1565` | Toggle WPGraphQL integration |
| Debug Logging | Yes | `class-stitch-admin.php:1575` | Write metrics to debug.log |
| Webhooks | Yes | `class-webhooks.php` (hidden submenu) | Configure webhook URLs |
| System Info | Yes | `class-stitch-admin.php:510` render_developer | PHP/WP/MySQL versions |

### Tab 5: Privacy
**Existing** — `class-settings.php:113` `privacy` tab

| Setting | Existing? | File | Purpose |
|---------|-----------|------|---------|
| Anonymize Logs | Yes | `class-stitch-admin.php:1594` | Scrub user IDs from logs |
| Remove Logs on Uninstall | Yes | `class-stitch-admin.php:1602` | Delete logs on plugin delete |
| Remove Data on Uninstall | Yes | `class-stitch-admin.php:1612` | Erase custom tables on delete |

---

## 8. Tools Architecture — Final Grouping

The current Tools hub (`class-stitch-admin.php:306`) is a card grid linking to hidden sub-pages. Recommended restructuring:

### Tools → Graph
**Evidence:** `class-stitch-admin.php:1108` render_explorer + `class-graph.php:162` ajax_get_graph_data
**What it does:** Interactive force-directed graph visualization
**Keep as-is:** Full-page graph explorer with search and filters

### Tools → Bulk Manager
**Evidence:** `class-bulk-manager.php:257` hidden submenu + AJAX handlers
**What it does:** WP_List_Table with bulk delete, bulk type change
**Enhance:** Add bulk status change (if status workflow is active)

### Tools → Import/Export
**Evidence:** `class-import-export.php:206` hidden submenu
**What it does:** JSON export of relationship types + data, JSON import
**Keep as-is**

### Tools → Database Health
**Consolidate these overlapping features:**
- **Integrity Check** (`class-integrity.php:188`) — verify referential integrity
- **Orphaned Relationships** (`class-orphaned.php:188`) — find relationships with deleted objects
- **Site Health** (`class-site-health.php`) — already in WP Site Health tab

**Recommended UI:**
```
Tools > Database Health
├── [Run Integrity Check] button → displays results
├── [Find Orphaned] button → displays results with [Fix] actions
└── System: tables, indexes, cache status
```

### REMOVE from Tools:
- **Overview** (`class-overview.php:668`) — **DEPRECATE**: duplicate of Relationships page. The Relationships page already shows the same data with better UX (search, filter, bulk actions, add modal). Overview is a plain WP_List_Table with no search/filter.
- **Reports/Analytics** (`class-analytics.php:52`, Stitch Admin render_reports) — **DEPRECATE**: The analytics are simple COUNT queries (`class-analytics.php:68`). Move the summary stats (total relationships, by type, activity trend) into the Relationships page header as a collapsible stats bar, then remove the standalone page.
- **Auto-Relations** (`class-auto-relations.php:162`) — **MOVE to Settings**: This is just a toggle + a manual trigger button. The toggle belongs in Settings > General. The manual trigger belongs in Relationships page header.

---

## 9. Developer Experience

### Where developer features should live:

| Feature | Current | Recommended | Rationale |
|---------|---------|-------------|-----------|
| REST API docs | Stitch Admin render_developer | **Settings > Developer** — keep existing | Shows endpoints, status, toggle |
| PHP API | `developer-guide.php` (standalone) | **External documentation** | No admin page needed |
| Fluent API | `class-fluent-api.php` (global function) | **External documentation** | No admin page needed |
| WP_Query | `class-query.php` (filter hook) | **External documentation** | No admin page needed |
| GraphQL | `class-graphql.php` (auto-register) | **Settings > Developer** — toggle only | Toggle on/off, docs externally |
| WP-CLI | `class-wp-cli.php` (8+ commands) | **External documentation** | CLI users don't need admin pages |
| Hooks & Filters | Stitch Admin render_developer | **Settings > Developer** — keep existing | Lists `naticore_relation_added`, `removed` |
| Capabilities | `class-developer.php` hidden submenu | **Settings > Developer** — keep existing | Maps `naticore_create_relation` → `edit_post` |
| Security | `class-developer.php` hidden submenu | **Settings > Developer** — keep existing | Shows nonce/cap patterns |
| Webhooks | `class-webhooks.php` hidden submenu | **Settings > Developer** — config section | URL, events, HMAC secret |

### Should Developer be a top-level menu?

**No.** Reasons:
1. WordPress convention: plugins have Settings, not Developer menus
2. The target audience is 70% site builders, 30% developers
3. Developers will find the REST API docs, WP-CLI, and hooks via external documentation
4. A top-level "Developer" menu would be empty for non-technical users

### Developer documentation (external):

The `developer-guide.php` file at the plugin root is already well-structured with PHP API examples, WP_Query integration, and REST API reference. This should be published to the plugin's documentation site (linked in readme.txt) rather than kept as a PHP file.

---

## 10. Integrations

### Visibility rule: Show only when integration is installed

| Integration | Auto-detects? | File | Settings needed? | Display rule |
|-------------|--------------|------|-----------------|-------------|
| WooCommerce | `class_exists('WooCommerce')` | `class-woocommerce.php` | Toggle: sync upsells/cross-sells | Show when WooCommerce active |
| ACF | `function_exists('acf_add_local_field_group')` | `class-acf.php` | Toggle: enable migration, sync | Show when ACF active |
| Elementor | `did_action('elementor/loaded')` | `class-elementor-integration.php` | None (auto-register tags) | Show when Elementor active |
| WPML | `defined('ICL_PLUGIN_VERSION')` | `class-wpml.php` | Toggle: mirror relationships | Show when WPML active |
| Polylang | `function_exists('pll__)` | `class-wpml.php` | Toggle: mirror relationships | Show when Polylang active |
| Yoast SEO | `class_exists('WPSEO_Options')` | `class-seo.php` | None (auto-add schema) | Show when Yoast active |
| RankMath | `class_exists('RankMath')` | `class-seo.php` | None (auto-add schema) | Show when RankMath active |
| Duplicate Post | `class_exists('Duplicate_Post')` | `class-duplicate-post.php` | Toggle: clone relationships | Show when Duplicate Post active |
| WPGraphQL | `defined('WPGRAPHQL_VERSION')` | `class-graphql.php` | Toggle: enable GraphQL fields | Show when WPGraphQL active |
| AI Client | `function_exists('wp_ai_client_prompt')` | `class-ai-suggestions.php` | Toggle + provider config | Show when WP 7.0+ |

### Recommended Integrations page UX:

```
Settings > Integrations

[Active Integrations]
┌─────────────────────────────────────────────┐
│ WooCommerce          [Connected] [Toggle]   │
│ Syncs product upsells and cross-sells.      │
├─────────────────────────────────────────────┤
│ Elementor            [Connected]            │
│ Dynamic tags auto-registered. No config.    │
├─────────────────────────────────────────────┤
│ Yoast SEO            [Connected]            │
│ Schema markup auto-added. No config.        │
└─────────────────────────────────────────────┘

[Available Integrations - Install to Activate]
┌─────────────────────────────────────────────┐
│ ACF                  [Not Active] [Enable]  │
│ WPGraphQL            [Not Active] [Enable]  │
│ Duplicate Post       [Not Active] [Enable]  │
└─────────────────────────────────────────────┘
```

---

## 11. Features Missing From UI

These features exist in code but have no discoverable admin UI:

| Feature | Code Evidence | Impact | Recommendation |
|---------|--------------|--------|----------------|
| Cloning | `class-cloning.php:38` `admin_action_naticore_clone` | Orphaned — no button/URL triggers it | **DELETE** — dead code, or wire into Relationships page |
| Revision History | `class-revision-history.php` (hook-only) | Stores add/remove history in post meta | **KEEP internal** — useful for debugging, expose in future version |
| Status workflow UI | `class-status.php:23` (filter-only) | Workflows defined in code, no admin UI to create custom ones | **MOVE to Types page** as advanced section |
| Expiration cron config | `class-expiration.php:258` (hidden submenu) | Cron job exists but no UI to configure schedule | **MOVE to Types page** as advanced section |
| Webhooks config | `class-webhooks.php` (hidden submenu) | CRUD exists but hidden | **MOVE to Settings > Developer** |
| Permissions config | `class-permissions.php:312` (hidden submenu) | Full admin page exists but hidden | **MOVE to Settings > General** |

---

## 12. Features That Should NOT Have UI

| Feature | Code Evidence | Reason |
|---------|--------------|--------|
| PHP API | `class-api.php` (global functions) | Developer documentation only |
| Fluent API | `class-fluent-api.php:342` | Developer documentation only |
| WP_Query integration | `class-query.php` (filter hook) | Developer documentation only |
| WP-CLI commands | `class-wp-cli.php` | CLI users don't need admin pages |
| Revision History | `class-revision-history.php` | Internal logging — expose in future version |
| Site Health test | `class-site-health.php` | Already in WP Site Health tab |
| Bidirectional auto-sync | `class-bidirectional-sync.php` | Internal logic — config in Settings |
| Cleanup on delete | `class-cleanup.php` | Internal logic — config in Settings |
| Constraints | `class-constraints.php` | Cardinality is in Types page; this is internal enforcement |

---

## 13. Features To Merge

### Merge 1: Overview → Relationships
**Evidence:** `class-overview.php` is a plain WP_List_Table showing from, type, to, direction, date. `class-stitch-admin.php:869` render_relationships shows the same columns plus search, filter, bulk actions, add modal. Overview is strictly inferior.
**Action:** DELETE Overview. Relationships page is the replacement.

### Merge 2: Reports → Relationships header
**Evidence:** `class-analytics.php:68` get_analytics() runs 5 COUNT queries (total, by type, by date, recent activity, most connected). These 5 stats can be displayed as a collapsible stats bar at the top of the Relationships page.
**Action:** DELETE Reports page. Add summary stats to Relationships page header.

### Merge 3: Auto-Relations → Settings + Relationships
**Evidence:** `class-auto-relations.php:162` is a toggle + manual trigger. The toggle belongs in Settings > General. The manual trigger button ("Scan for auto-relations") belongs in the Relationships page header.
**Action:** DELETE Auto-Relations page. Split into Settings toggle + Relationships header button.

### Merge 4: Constraints → Types (already there)
**Evidence:** `class-constraints.php` enforces cardinality limits. Cardinality is already configured in the Types create modal (`class-stitch-admin.php:798`). Constraints is just the enforcement layer.
**Action:** DELETE Constraints page. Cardinality config is already in Types.

### Merge 5: Explorer → Graph (same thing)
**Evidence:** `naticore-explorer` loads `graph.js` and `graph.css`. `naticore-graph` is registered by `class-graph.php:246`. Both are the same graph visualization.
**Action:** KEEP naticore-explorer, DELETE naticore-graph.

### Merge 6: Presets → Types page
**Evidence:** `class-presets.php:40` has 8 templates (Event/Speaker, Course/Instructor, Product/Brand, Job/Skill, Candidate/Job, Author, Portfolio, Series). These are just pre-filled type configurations.
**Action:** Add "Use Template" button to Types page → opens template picker → pre-fills create modal.

---

## 14. Features To Remove/Deprecate

| Feature | File | Action | Reason |
|---------|------|--------|--------|
| Overview | `class-overview.php:668` | **DELETE** | Strictly inferior to Relationships page |
| Reports | Stitch Admin render_reports | **DELETE** | Merge stats into Relationships header |
| Analytics | `class-analytics.php:52` | **DELETE** | Same as Reports |
| Explorer (as separate page) | Stitch Admin render_explorer | **MERGE into Tools > Graph** | Same as Graph |
| Auto-Relations (as page) | `class-auto-relations.php:162` | **DELETE** | Split into Settings + Relationships |
| Constraints (as page) | Stitch Admin hidden submenu | **DELETE** | Cardinality is in Types |
| Presets (as page) | `class-presets.php:292` | **DELETE** | Move to Types page |
| Cloning | `class-cloning.php:38` | **DELETE** | Orphaned, no UI triggers it |
| Settings Old | `class-settings-old.php` | **DELETE** | Dead code, excluded from PHPStan |
| Activation Notice | `class-admin.php:621` | **DELETE** | Transient never set, never fires |
| Developer Guide (PHP file) | `developer-guide.php` | **DELETE from plugin** | Publish to docs site |
| `naticore-hidden` parent | `class-stitch-admin.php:198` | **KEEP but reduce** | Still needed for tab-rendered sub-pages |

---

## 15. Beginner Journey

```
1. Install plugin
   → Database tables auto-created (class-install.php)

2. Go to Settings > General
   → Select enabled post types (post, page)
   → Save

3. Go to Relationship Types
   → Click "New Type"
   → Enter label: "Related Articles"
   → Enter slug: "related_to"
   → Source: Posts → Target: Posts
   → Cardinality: One to Many
   → Advanced: ✅ Bidirectional
   → Click "Create Type"

4. Open any post in Gutenberg
   → See "Related Content" sidebar panel
   → Search for related post
   → Click "Add"
   → Done

5. Open any post in Classic Editor
   → See "Related Content" meta box
   → Search for related post
   → Click "Add"
   → Done
```

**Total steps: 5. Time: <2 minutes.**

---

## 16. Developer Journey

```
1. Install plugin
   → REST API auto-available at /wp-json/naticore/v1/

2. Read Settings > Developer
   → See REST API endpoints table
   → See PHP API code examples
   → See Action Hooks list
   → See System Environment (PHP, WP, MySQL versions)

3. Query via REST:
   GET /wp-json/naticore/v1/post/123

4. Query via PHP:
   $related = wp_get_related( 123, 'related_to' );

5. Query via WP_Query:
   new WP_Query([
       'post_type' => 'post',
       'content_relation' => [
           'post_id' => 123,
           'type' => 'related_to',
       ],
   ]);

6. Query via WP-CLI:
   wp content-relations list --post=123

7. Integrate with Elementor:
   → Dynamic tags auto-registered (no config)

8. Integrate with GraphQL:
   → Toggle on in Settings > Developer
   → Query: naticoreRelationships { id type meta { ... } }
```

---

## 17. Release Priority

### P0 — Before 1.4.0 release
1. Wire the edit button on Types table row (currently no action)
2. Wire the bulk actions on Relationships page (currently placeholder)
3. Fix activation notice (set transient on activation hook)
4. Remove `class-settings-old.php` from codebase
5. Remove orphaned `admin_action_naticore_clone` or wire into UI

### P1 — Immediately after 1.4.0
1. Add status/source-type/target-type filters to Relationships page
2. Add direction indicator (→/↔) to Relationships table
3. Move Permissions hidden submenu to Settings > General section
4. Move Webhooks hidden submenu to Settings > Developer section
5. Consolidate Developer page tabs (remove debug-only restriction)

### P2 — 1.5
1. Restructure Settings with tabs (General, Editor, Integrations, Developer, Privacy)
2. Move Status Workflows and Expiration into Types page as advanced sections
3. Merge Presets into Types page ("Use Template" button)
4. Add Integrations page with auto-detect display
5. Merge Analytics/Reports stats into Relationships page header

### P3 — 1.6+
1. Move Auto-Relations toggle to Settings > General
2. Move Auto-Relations manual trigger to Relationships header
3. Consolidate Database Health (Integrity + Orphaned) into Tools tab
4. Delete Overview, Reports, Analytics, Auto-Relations, Constraints, Presets pages
5. Publish developer documentation externally
6. Add Revision History UI (future version)

---

## 18. Final Action Plan

### Phase 1: Fix P0 bugs (before 1.4.0)
1. `class-stitch-admin.php` — wire edit button in Types table
2. `class-stitch-admin.php` — wire bulk delete in Relationships table
3. `native-content-relationships.php` — add `register_activation_hook` to set `naticore_activation_notice` transient
4. Delete `includes/tools/class-settings-old.php`
5. `class-cloning.php` — either remove `admin_action_naticore_clone` or add clone button to Relationships table

### Phase 2: Navigation restructure (1.5)
1. Add Settings tabs to `class-stitch-admin.php` render_settings()
2. Move Permissions from hidden submenu to Settings > General section
3. Move Webhooks from hidden submenu to Settings > Developer section
4. Remove debug-only restriction on Developer tab
5. Add Integrations tab with auto-detect status display

### Phase 3: Feature consolidation (1.5-1.6)
1. Add "Status Workflow" and "Expiration" sections to Types create/edit modal
2. Add "Use Template" button to Types page
3. Merge Analytics/Reports stats into Relationships page header
4. Add status/source-type/target-type filters to Relationships page
5. Add direction indicator to Relationships table rows

### Phase 4: Deprecation (1.6+)
1. Remove Overview page (`class-overview.php`)
2. Remove Reports/Analytics pages
3. Remove Auto-Relations page
4. Remove Constraints page (cardinality is in Types)
5. Remove Presets page (moved to Types)
6. Remove Cloning page (orphaned)
7. Remove `class-settings-old.php`
8. Remove `developer-guide.php` from plugin (publish externally)
9. Reduce `naticore-hidden` sub-page count from 22 to ~8

---

## Critical Final Question

**"Is the current 4-item navigation actually too simple, or is the problem that the existing Tools/Settings architecture is hiding too many features?"**

**The problem is that the existing architecture is hiding too many features.**

The 4-item sidebar is the correct number for a WordPress plugin menu. The issue is:

1. **Tools is a dead-end hub** — it shows 8 cards linking to hidden sub-pages, but the sub-pages don't appear in the sidebar. A user who clicks "Open Graph" lands on `naticore-explorer` and has no sidebar link to get back to Tools or Relationships.

2. **Settings is a flat grid** — no tabs, no progressive disclosure. All toggles are visible at once, overwhelming new users while hiding advanced features.

3. **22 hidden pages under `naticore-hidden`** — these are functional pages with full UIs, but they have zero sidebar presence. They are only accessible via:
   - Direct URL bookmark
   - Clicking through the Tools hub
   - Manually constructing the URL

4. **Duplicate pages confuse the picture** — Overview duplicates Relationships, Reports duplicates Analytics, Explorer duplicates Graph, Constraints duplicates Types (cardinality), Presets are just type templates.

**The correct solution:**
- Keep 4 sidebar items (Relationships, Types, Tools, Settings)
- Add **tabs** within Tools and Settings to expose all features
- **Delete duplicate pages** (Overview, Reports, Analytics, Explorer-as-separate-page, Constraints, Presets-as-separate-page)
- **Move workflow features** (Status, Expiration, Permissions) into their natural homes (Types page, Settings)
- Reduce hidden sub-pages from 22 to ~8 (the ones that are actually rendered as tab content)

This creates a navigation that is **simple enough for beginners** (4 items, clear hierarchy) while **powerful enough for agencies and enterprises** (tabbed Tools and Settings with progressive disclosure).

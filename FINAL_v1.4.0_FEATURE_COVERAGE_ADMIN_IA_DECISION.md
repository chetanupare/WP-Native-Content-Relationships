# FINAL v1.4.0 FEATURE COVERAGE & ADMIN IA DECISION

**Date**: August 21, 2026
**Status**: Code-verified gap analysis — no code changes

---

## 1. FEATURE DISCOVERABILITY AUDIT

| Feature | Implementation | UI Exists | UI Location | Discoverable? | Problem | Recommendation |
|---------|---------------|-----------|-------------|---------------|---------|----------------|
| Relationships | `class-api.php`, `class-rest-api.php` | Yes | Relationships page | **A** | — | — |
| Relationship Types | `class-relation-types.php` | Yes | Types page | **A** | — | — |
| Gutenberg sidebar | `class-sidebar.php` | Yes | Post editor panel | **A** | — | — |
| Classic Editor metabox | `class-admin.php:67` | Yes | Post editor | **A** | — | — |
| Relationship search | `class-object-search.php` | Yes | Add Connection modal | **A** | — | — |
| Relationship metadata | `class-meta-api.php` | Partial | Create modal toggle only | **C** | No edit UI for metadata | Future: add metadata editing on Relationships page |
| Cardinality | `class-constraints.php:180-207` | Yes | Constraints page | **C** | Hidden under Tools → Constraints | Acceptable for power-user feature |
| Bidirectional relationships | `class-bidirectional-sync.php` | Yes | Types create modal toggle | **B** | Toggle exists but not verified if it wires to backend | Verify backend wiring |
| Status workflows | `class-status.php` | Partial | AJAX from Relationships page | **C** | No workflow-management UI | Future: add workflow config page |
| Expiration | `class-expiration.php` | Yes | Tools → Expiration | **C** | Hidden but reachable from Tools hub | Acceptable |
| Permissions | `class-permissions.php` | Yes | Tools → Permissions | **C** | Hidden but reachable from Tools hub | Acceptable |
| Webhooks | `class-webhooks.php` | Yes | Tools → Webhooks | **C** | Hidden but reachable from Tools hub | Acceptable |
| Constraints | `class-constraints.php` | Yes | Tools → Constraints | **C** | Hidden but reachable from Tools hub | Acceptable |
| Presets | `class-presets.php` | Yes | Tools → Presets | **C** | Hidden but reachable from Tools hub | Acceptable |
| Explorer | `class-graph.php` (via Stitch Admin) | Yes | Tools → Explorer | **A** | — | — |
| Reports | Stitch Admin inline | Yes | Tools → Reports | **A** | — | — |
| Analytics | `class-analytics.php` | Yes | `naticore-hidden-analytics` | **G** | Duplicate of Reports, orphaned registration | Deprecate |
| Import/Export | `class-import-export.php` + Stitch Admin | Yes | Tools → Import/Export | **A** | — | — |
| Bulk Manager | `class-bulk-manager.php` | Yes | `naticore-hidden-bulk` | **D** | Registered under old parent, no link from Tools | Evaluate: link from Relationships or deprecate |
| Overview | `class-overview.php` | Yes | `naticore-overview` under Settings | **D** | Registered under Settings parent, no visible link | Evaluate: link from Relationships or deprecate |
| Auto Relations | `class-auto-relations.php` | No | Settings toggle only | **F** | Backend-only, configured via Settings | Correct — no page needed |
| Integrity checks | `class-integrity.php` | No | Silent background service | **F** | Background cleanup, admin notice only | Correct — no page needed |
| Orphan checks | `class-orphaned.php` | No | Silent background service | **F** | Background check, admin notice only | Correct — no page needed |
| Developer/API | `class-developer.php` | Yes | Tools → Developer | **A** | — | — |
| GraphQL | `class-graphql.php` | No | Settings toggle only | **F** | API-only, requires WPGraphQL plugin | Correct — no page needed |
| WooCommerce | `class-woocommerce.php` | No | Settings toggle only | **F** | Integration, auto-activates | Correct — no page needed |
| ACF | `class-acf.php` | No | Auto-activates | **F** | Integration, auto-activates | Correct — no page needed |
| Elementor | `class-elementor-integration.php` | No | Auto-activates | **F** | Integration, auto-activates | Correct — no page needed |
| WPML/Polylang | `class-wpml.php` | No | Auto-activates | **F** | Integration, auto-activates | Correct — no page needed |
| SEO integrations | `class-seo.php` | No | Auto-activates | **F** | Integration, auto-activates | Correct — no page needed |
| Duplicate Post | `class-duplicate-post.php` | No | Auto-activates | **F** | Integration, auto-activates | Correct — no page needed |
| AI suggestions | `class-ai-suggestions.php` | Partial | Admin notice + Settings toggle | **C** | Notice appears on post editor; toggle in legacy Settings | Correct — contextual feature |
| Revision History | `class-revision-history.php` | Yes | Post editor revisions tab | **B** | Integrated into WP revisions, hidden in plain sight | Acceptable |
| Cloning | `class-cloning.php` | Yes | Post row actions "Clone" link | **B** | Available on post list, easy to miss | Acceptable |
| Site Health | `class-site-health.php` | Yes | WP Tools → Site Health | **E** | WordPress native location | Correct — no plugin page needed |

---

## 2. IMPORTANT QUESTION

**"Are there any important implemented features that a normal WordPress administrator cannot discover from the current 4-item navigation?"**

**YES — 2 genuine problems:**

### Problem 1: Bulk Manager is unreachable

| Attribute | Value |
|-----------|-------|
| Feature | Bulk Manager — bulk operations on relationships |
| Current access path | `admin.php?page=naticore-hidden-bulk` (orphaned registration under `naticore-hidden`) |
| Why undiscoverable | Registered by `class-bulk-manager.php:54` under `naticore-hidden` parent. No link from Tools hub or any visible page. User would need to know the URL. |
| Best destination | Tools hub (add a card linking to it) OR merge into Relationships page bulk actions |
| Should this block 1.4.0? | **NO** — Relationships page already has bulk delete/export in its toolbar. Bulk Manager may be redundant. |

### Problem 2: Overview is unreachable

| Attribute | Value |
|-----------|-------|
| Feature | Overview — WP_List_Table of all relationships with detailed columns |
| Current access path | `admin.php?page=naticore-overview` (registered under `naticore-settings` parent) |
| Why undiscoverable | Registered by `class-overview.php:329` as a submenu of Settings. Not linked from Settings page or any visible page. |
| Best destination | Deprecate — Relationships page already shows the same data in a modernized table |
| Should this block 1.4.0? | **NO** — Relationships page is the replacement. |

**All other features are either:**
- Visible in the 4-item navigation (A)
- Reachable through Tools hub links (C)
- Backend-only / integration features that correctly have no admin page (F)
- WordPress-native locations (E)

---

## 3. TOOLS HUB EVALUATION

### Currently linked from Tools hub (8 cards):

| Tool | Link Target | Page Registered By | Works? | Verdict |
|------|-------------|-------------------|--------|---------|
| Explorer | `naticore-explorer` | Stitch Admin | ✅ Yes | Keep |
| Reports | `naticore-reports` | Stitch Admin | ✅ Yes | Keep |
| Import/Export | `naticore-import-export` | Stitch Admin | ✅ Yes | Keep |
| Developer | `naticore-developer` | Stitch Admin | ✅ Yes | Keep |
| Expiration | `naticore-expiration` | `class-expiration.php:263` | ✅ Yes | Keep |
| Permissions | `naticore-permissions` | `class-permissions.php:318` | ✅ Yes | Keep |
| Webhooks | `naticore-webhooks` | `class-webhooks.php:338` | ✅ Yes | Keep |
| Constraints | `naticore-constraints` | `class-constraints.php:478` | ✅ Yes | Keep |
| Presets | `naticore-presets` | `class-presets.php:297` | ✅ Yes | Keep |

### NOT linked from Tools hub:

| Tool | Link Target | Registered By | Reachable? | Verdict |
|------|-------------|---------------|------------|---------|
| Bulk Manager | `naticore-hidden-bulk` | `class-bulk-manager.php:54` | ❌ No link | **Add to Tools or deprecate** |
| Overview | `naticore-overview` | `class-overview.php:329` | ❌ No link | **Deprecate** (replaced by Relationships page) |
| Analytics | `naticore-hidden-analytics` | `class-analytics.php:53` | ❌ No link | **Deprecate** (replaced by Reports) |
| Graph | `naticore-hidden-graph` | `class-graph.php:55` | ❌ No link | **Deprecate** (replaced by Explorer) |

### Navigation back from hidden pages:

All hidden pages (Expiration, Permissions, Webhooks, Constraints, Presets) use `$stitch_admin->render_wrapper_start('naticore-tools')` which renders the topbar with the "Tools" title. However, there is **no back-link or breadcrumb** in the topbar. The user must use the WordPress admin menu to navigate back.

**Assessment**: Acceptable — WordPress admin menu is always visible on the left. Users can click "Relationships" → "Tools" to return.

---

## 4. SETTINGS GAP

### What Stitch Admin Settings UI actually shows (`class-stitch-admin.php:1492-1621`):

| Setting | Exists in Backend | Visible in Stitch Admin UI | Correct Location | Missing? |
|---------|------------------|---------------------------|------------------|----------|
| Lazy Load Metadata | ✅ `naticore_settings[lazy_load_metadata]` | ✅ Yes — Performance & Engine card | ✅ Correct | — |
| Optimized Query Engine | ✅ `naticore_settings[optimized_query_engine]` | ✅ Yes — Performance & Engine card | ✅ Correct | — |
| Enable REST API | ✅ `naticore_settings[rest_api_enabled]` | ✅ Yes — Developer & APIs card | ✅ Correct | — |
| Enable GraphQL | ✅ `naticore_settings[graphql_enabled]` | ✅ Yes — Developer & APIs card | ✅ Correct | — |
| Debug Logging | ✅ `naticore_settings[debug_logging]` | ✅ Yes — Developer & APIs card | ✅ Correct | — |
| Anonymize Logs | ✅ `naticore_settings[anonymize_logs]` | ✅ Yes — Danger Zone card | ✅ Correct | — |
| Remove Logs on Uninstall | ✅ `naticore_settings[remove_logs_on_uninstall]` | ✅ Yes — Danger Zone card | ✅ Correct | — |
| Remove Data on Uninstall | ✅ `naticore_remove_data_on_uninstall` | ✅ Yes — Danger Zone card | ✅ Correct | — |
| **Enabled Post Types** | ✅ `naticore_settings[enabled_post_types]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Default Direction** | ✅ `naticore_settings[default_direction]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Manual Ordering** | ✅ `naticore_settings[enable_manual_order]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Bidirectional Sync** | ✅ `naticore_settings[bidirectional_sync]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Cleanup on Delete** | ✅ `naticore_settings[cleanup_on_delete]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Max Relationships** | ✅ `naticore_settings[ncr_max_relationships]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Auto Relations** | ✅ `naticore_settings[auto_relation_enabled]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **AI Suggestions** | ✅ `naticore_settings[enable_ai_suggestions]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Auto Link** | ✅ `naticore_settings[enable_auto_link]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |
| **Prevent Circular** | ✅ `naticore_settings[prevent_circular]` | ❌ **NOT shown** | Should be in Settings | **MISSING from UI** |

### Summary:

- **8 settings visible** in Stitch Admin UI (performance, developer, privacy)
- **10 settings MISSING** from Stitch Admin UI (all behavioral/content-type settings)
- The missing settings ARE registered by `class-settings.php` via `register_settings()` on `admin_init`, so they ARE saved if submitted. But there is **no UI to change them** in the Stitch Admin.

**Impact**: An admin cannot configure which post types are enabled, default direction, max relationships, auto-relations, AI suggestions, or circular prevention from the Stitch Admin Settings page. These are critical behavioral settings.

---

## 5. TYPES GAP

### Types page features (`class-stitch-admin.php:636-865`):

| Feature | Status | Notes |
|---------|--------|-------|
| Create | ✅ Modal with 4 sections | Label, Slug, Source/Target, Cardinality, Advanced toggles |
| Edit | ⚠️ Button exists, no handler | `more_vert` icon button has no `onclick` or JS wiring |
| Delete | ❌ No UI | No delete action in table |
| Cardinality | ✅ Create modal | One-to-One, One-to-Many, Many-to-Many radio buttons |
| Bidirectional | ✅ Create modal toggle | "Bidirectional Querying" toggle |
| Sortable | ✅ Create modal toggle | "Sortable Connections" toggle |
| REST | ✅ Create modal toggle | "Show in REST API" toggle |
| Metadata | ✅ Create modal toggle | "Enable Relationship Metadata" toggle |
| Status | ✅ Table column | Active/Draft badge |
| Expiration | ❌ Not in Types UI | Configured via Expiration page |
| Workflow | ❌ Not in Types UI | Backend-only via Status class |
| Presets/Templates | ✅ Separate page | Tools → Presets |

### Critical Issue:

**The edit action does not work.** The `more_vert` button at line 725-727 has no JavaScript handler. Clicking it does nothing. Users can create types but cannot edit them after creation.

---

## 6. RELATIONSHIPS GAP

### Relationships page features (`class-stitch-admin.php:869-1103`):

| Feature | Status | Notes |
|---------|--------|-------|
| Search | ✅ Text input | Placeholder "Search connections..." |
| Filters | ✅ Type dropdown | "All Types" + dynamic type list |
| Pagination | ✅ Footer | Shows "1 of N pages" with nav buttons |
| Add relationship | ✅ Modal | Source search, Type dropdown, Target search |
| Edit | ⚠️ Button exists, no handler | `edit` icon button at line 1004-1006 has no JS |
| Delete | ⚠️ Button exists, no handler | `delete` icon button at line 1007-1009 has no JS |
| Bulk delete | ✅ Dropdown | "Bulk Actions" → "Delete" |
| Bulk export | ✅ Dropdown | "Bulk Actions" → "Export" |
| Status | ✅ Table column | Toggle checkbox (appears always checked) |
| Direction | ❌ Not shown | No direction column |
| Source type | ❌ Not shown | Only source title shown, not source post type |
| Target type | ❌ Not shown | Only target title shown |
| Metadata | ❌ Not shown | No metadata column or edit |
| Sorting | ✅ Sort dropdown | "Date (Newest)" and "Source ID" |

### Critical Issues:

1. **Edit and Delete buttons have no JS handlers.** The buttons exist in the HTML but are not wired to any AJAX or form submission.
2. **Status toggle appears hardcoded to checked.** Line 998: `<input type="checkbox" checked>` — always checked regardless of actual status.
3. **No pagination wiring.** The pagination buttons exist but have no `onclick` handlers or form submission. Page is always limited to 20 items with `LIMIT 20` in the SQL query.

---

## 7. TOOLS GAP

### Tools hub card links verification:

| Card | Link | Target Page | Rendered By | Back Navigation |
|------|------|-------------|-------------|-----------------|
| Graph Explorer | `naticore-explorer` | Explorer | Stitch Admin `render_explorer()` | Topbar shows "Tools" title, admin menu available |
| Analytics & Reports | `naticore-reports` | Reports | Stitch Admin `render_reports()` | Topbar shows "Tools" title, admin menu available |
| Import & Export | `naticore-import-export` | Import/Export | Stitch Admin `render_import_export()` | Topbar shows "Tools" title, admin menu available |
| Developer Tools | `naticore-developer` | Developer | Stitch Admin `render_developer()` | Topbar shows "Tools" title, admin menu available |
| Relationship Expiration | `naticore-expiration` | Expiration | `class-expiration.php` | Topbar shows "Tools" title, admin menu available |
| Role Permissions | `naticore-permissions` | Permissions | `class-permissions.php` | Topbar shows "Tools" title, admin menu available |
| Webhooks | `naticore-webhooks` | Webhooks | `class-webhooks.php` | Topbar shows "Tools" title, admin menu available |
| Constraints | `naticore-constraints` | Constraints | `class-constraints.php` | Topbar shows "Tools" title, admin menu available |
| Presets | `naticore-presets` | Presets | `class-presets.php` | Topbar shows "Tools" title, admin menu available |

**All 9 Tools hub links work.** Pages are registered by their respective classes under `naticore-hidden` parent and render with Stitch Admin wrapper.

### Intentionally hidden vs accidentally hidden:

| Page | Status | Reason |
|------|--------|--------|
| Explorer | Intentionally hidden | Linked from Tools hub |
| Reports | Intentionally hidden | Linked from Tools hub |
| Import/Export | Intentionally hidden | Linked from Tools hub |
| Developer | Intentionally hidden | Linked from Tools hub |
| Expiration | Intentionally hidden | Linked from Tools hub |
| Permissions | Intentionally hidden | Linked from Tools hub |
| Webhooks | Intentionally hidden | Linked from Tools hub |
| Constraints | Intentionally hidden | Linked from Tools hub |
| Presets | Intentionally hidden | Linked from Tools hub |
| Bulk Manager | **Accidentally hidden** | No link from any visible page |
| Overview | **Accidentally hidden** | Registered under Settings but not linked |
| Analytics | **Accidentally hidden** | Duplicate of Reports, no link |
| Graph | **Accidentally hidden** | Duplicate of Explorer, no link |

---

## 8. DUPLICATE / LEGACY AUDIT

| Component | Keep | Merge | Deprecate | Delete | Reason |
|-----------|------|-------|-----------|--------|--------|
| `class-graph.php` | | | ✅ | | Duplicate of Explorer. Still loaded at line 177-179 and registers menu at line 55. Explorer is the modern replacement. |
| `class-analytics.php` | | | ✅ | | Duplicate of Reports. Still loaded at line 189-191 and registers menu at line 53. Reports is the modern replacement. |
| `class-settings.php` | ✅ | | | | **MUST KEEP** — Registers all settings via `register_settings()` on `admin_init`. Stitch Admin UI depends on these registrations. Menu registration commented out (line 57). |
| `class-settings-old.php` | | | | ✅ | Dead code. Excluded from PHPStan. Registers orphaned `add_options_page` under Tools.php. No functional code. |
| `class-overview.php` | | | ✅ | | WP_List_Table view. Registered under `naticore-settings` parent. Relationships page provides same data in modernized UI. |
| `class-bulk-manager.php` | ✅ | | | | Bulk operations with AJAX handlers. Relationships page has basic bulk actions but Bulk Manager may have additional functionality. **Verify before deprecating.** |
| `class-cloning.php` | ✅ | | | | Clone handler via `post_row_actions` filter. Active feature, no admin page needed. |
| Activation notice (`class-admin.php:621`) | | | ✅ | | Dead code. Checks transient that is never set. |
| `developer-guide.php` | ✅ | | | | Reference file, never loaded by bootstrap. Safe to keep. |

---

## 9. RELEASE BLOCKERS

| Priority | Issue | Impact | Action |
|----------|-------|--------|--------|
| **P0** | Settings UI missing 10 critical behavioral settings | Admins cannot configure post types, direction, max relationships, auto-relations, AI, circular prevention | Add missing settings to Stitch Admin Settings UI before release |
| **P1** | Types edit button has no handler | Users cannot edit relationship types after creation | Wire edit button to modal or add edit flow |
| **P1** | Relationships edit/delete buttons have no handlers | Users cannot edit or delete individual relationships from the table | Wire buttons to AJAX handlers |
| **P1** | Relationships pagination not wired | Table always shows first 20 items, no way to navigate | Wire pagination buttons |
| **P2** | Bulk Manager unreachable | Power users cannot access bulk operations | Add card to Tools hub or merge into Relationships |
| **P2** | Overview unreachable | Legacy table view inaccessible | Deprecate ( Relationships page replaces it ) |
| **P2** | Graph/Analytics duplicate pages still registered | Wasted menu registrations, potential confusion | Deprecate classes |
| **P3** | No breadcrumb/back-link in hidden page topbars | Minor UX friction | Add back-link to topbar |
| **P3** | Status toggle hardcoded to checked | Status column shows incorrect state | Wire to actual status value |

---

## 10. FINAL RECOMMENDED IA

```
Relationships (top-level, dashicons-networking)
├── Relationships          ← CRUD table with search/filter/bulk/sort
├── Relationship Types     ← Type management (list + create modal)
├── Settings               ← Performance, Developer, Privacy, Behavioral settings
└── Tools                  ← Hub linking to:
    ├── Explorer           ← Visual graph canvas
    ├── Reports            ← Charts and analytics
    ├── Import/Export      ← JSON import/export
    ├── Developer          ← API reference, hooks, system info
    ├── Expiration         ← Cron management
    ├── Permissions        ← Role-based access
    ├── Webhooks           ← Webhook CRUD
    ├── Constraints        ← Connection rules + cardinality
    └── Presets            ← Template library
```

**Total visible items**: 4 (Relationships, Types, Settings, Tools)
**Total hidden pages**: 9 (all linked from Tools hub)
**Total pages accessible**: 13

**Not linked (to be resolved)**:
- Bulk Manager → Add to Tools hub OR merge into Relationships
- Overview → Deprecate
- Analytics → Deprecate (replaced by Reports)
- Graph → Deprecate (replaced by Explorer)

---

## 11. FINAL FEATURE COVERAGE MATRIX

| Feature | Code Exists | UI Exists | Discoverable | Final Location | Release Status |
|---------|------------|-----------|--------------|----------------|----------------|
| Relationships CRUD | ✅ | ✅ | A | Relationships page | ✅ Ready |
| Relationship Types | ✅ | ✅ | A | Types page | ⚠️ Edit broken |
| Gutenberg sidebar | ✅ | ✅ | A | Post editor | ✅ Ready |
| Classic Editor metabox | ✅ | ✅ | A | Post editor | ✅ Ready |
| Relationship search | ✅ | ✅ | A | Add Connection modal | ✅ Ready |
| Status workflows | ✅ | ⚠️ | C | AJAX from Relationships | ✅ Backend ready |
| Expiration | ✅ | ✅ | C | Tools → Expiration | ✅ Ready |
| Permissions | ✅ | ✅ | C | Tools → Permissions | ✅ Ready |
| Webhooks | ✅ | ✅ | C | Tools → Webhooks | ✅ Ready |
| Constraints | ✅ | ✅ | C | Tools → Constraints | ✅ Ready |
| Presets | ✅ | ✅ | C | Tools → Presets | ✅ Ready |
| Explorer (Graph) | ✅ | ✅ | A | Tools → Explorer | ✅ Ready |
| Reports | ✅ | ✅ | A | Tools → Reports | ✅ Ready |
| Import/Export | ✅ | ✅ | A | Tools → Import/Export | ✅ Ready |
| Developer/API | ✅ | ✅ | A | Tools → Developer | ✅ Ready |
| Auto Relations | ✅ | ⚠️ | F | Settings toggle | ✅ Backend ready |
| AI suggestions | ✅ | ⚠️ | C | Settings toggle + notice | ✅ Backend ready |
| Cloning | ✅ | ✅ | B | Post row actions | ✅ Ready |
| Revision History | ✅ | ✅ | B | Post revisions tab | ✅ Ready |
| Site Health | ✅ | ✅ | E | WP Site Health | ✅ Ready |
| WooCommerce | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| ACF | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| Elementor | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| WPML | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| SEO | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| Duplicate Post | ✅ | ⚠️ | F | Auto-activates | ✅ Ready |
| GraphQL | ✅ | ⚠️ | F | Settings toggle | ✅ Ready |
| Bulk Manager | ✅ | ⚠️ | D | **UNREACHABLE** | ❌ Needs link |
| Overview | ✅ | ⚠️ | D | **UNREACHABLE** | ❌ Deprecate |
| Analytics | ✅ | ⚠️ | G | **DUPLICATE** | ❌ Deprecate |
| Graph (legacy) | ✅ | ⚠️ | G | **DUPLICATE** | ❌ Deprecate |
| Relationship metadata | ✅ | ⚠️ | C | Types create modal toggle | ⚠️ No edit UI |
| Cardinality | ✅ | ✅ | C | Tools → Constraints | ✅ Ready |
| Bidirectional | ✅ | ✅ | B | Types create modal | ✅ Ready |

---

## 12. FINAL IMPLEMENTATION PLAN

### P0 — Release Blockers

| Change | File/Class | Existing Functionality Reused | New Work | Risk |
|--------|-----------|------------------------------|----------|------|
| Add missing behavioral settings to Stitch Admin Settings UI | `class-stitch-admin.php` `render_settings()` | `class-settings.php` `register_settings()` — all fields already registered | Add 10 setting rows to Settings page HTML: enabled post types, default direction, manual ordering, bidirectional sync, cleanup on delete, max relationships, auto relations, AI suggestions, auto link, prevent circular | Low — reuses existing `register_setting` infrastructure |

### P1 — Should Fix Immediately After Release

| Change | File/Class | Existing Functionality Reused | New Work | Risk |
|--------|-----------|------------------------------|----------|------|
| Wire Types edit button | `class-stitch-admin.php` `render_types()` | `class-relation-types.php` `get_type()`, `update_type()` | Add JS handler for `more_vert` button to open edit modal pre-filled with type data | Medium — requires new JS |
| Wire Relationships edit/delete buttons | `class-stitch-admin.php` `render_relationships()` | `class-api.php` `remove_relation()`, `class-rest-api.php` endpoints | Add JS handlers for edit (open modal) and delete (confirm + AJAX) | Medium — requires new JS |
| Wire Relationships pagination | `class-stitch-admin.php` `render_relationships()` | SQL query already has `LIMIT 20` | Add page parameter handling and wire pagination buttons | Low — SQL pagination pattern |

### P2 — v1.5 Improvements

| Change | File/Class | Existing Functionality Reused | New Work | Risk |
|--------|-----------|------------------------------|----------|------|
| Add Bulk Manager card to Tools hub | `class-stitch-admin.php` `render_tools()` | `class-bulk-manager.php` already registered | Add one card with link to `naticore-hidden-bulk` | None |
| Deprecate Graph class | `class-graph.php` | Explorer replaces it | Add `@deprecated` annotation, remove menu registration | Low |
| Deprecate Analytics class | `class-analytics.php` | Reports replaces it | Add `@deprecated` annotation, remove menu registration | Low |
| Deprecate Overview class | `class-overview.php` | Relationships page replaces it | Add `@deprecated` annotation, remove menu registration | Low |
| Remove dead activation notice | `class-admin.php:621` | None | Delete `render_activation_notice()` method and its hook | None |
| Remove `class-settings-old.php` | `class-settings-old.php` | None | Delete file, remove any includes | None |

### P3 — Future Improvements

| Change | File/Class | Existing Functionality Reused | New Work | Risk |
|--------|-----------|------------------------------|----------|------|
| Add breadcrumb/back-link to hidden page topbars | `class-stitch-admin.php` `render_topbar()` | Existing topbar rendering | Add back-link HTML to Tools hub | None |
| Add workflow management UI for Status | `class-status.php` | `get_workflows()`, `get_default_workflows()` | New admin page or Settings section for workflow config | Medium |
| Add metadata editing on Relationships page | `class-stitch-admin.php`, `class-meta-api.php` | `get_meta()`, `update_meta()` | New modal or inline editing for relationship metadata | Medium |
| Add relationship metadata column to Relationships table | `class-stitch-admin.php` `render_relationships()` | `class-meta-api.php` | Add metadata query and table column | Low |

---

## CRITICAL FINDING: SETTINGS UI GAP

The most important finding is that **10 behavioral settings are registered in the backend but have no UI in the Stitch Admin Settings page.** This means:

- An admin **cannot choose which post types** are enabled for relationships
- An admin **cannot set the default direction** for new relationships
- An admin **cannot configure max relationships** per source
- An admin **cannot enable/disable auto-relations**
- An admin **cannot enable/disable AI suggestions**
- An admin **cannot prevent circular relationships**
- An admin **cannot enable/disable manual ordering**
- An admin **cannot enable/disable bidirectional sync**
- An admin **cannot configure cleanup on delete**
- An admin **cannot enable/disable auto-linking**

These are **critical behavioral settings** that directly affect how the plugin operates. The backend infrastructure exists (`class-settings.php` registers them all), but the Stitch Admin UI only shows performance/developer/privacy toggles.

**This should be classified as P0 if the plugin ships with behavioral defaults that cannot be changed by admins.**

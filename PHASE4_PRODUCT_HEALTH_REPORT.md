# Native Content Relationships — Phase 4 Product Health Report

## Executive Summary

Native Content Relationships is a feature-rich WordPress relationship management plugin with 63 PHP files, 8 JS files, and 6 CSS files across 8 modules. The architecture is ambitious — covering REST API, Gutenberg sidebar, Elementor integration, WP_Query integration, shortcodes, widgets, WP-CLI, webhooks, AI suggestions, graph visualization, and more.

**The plugin works and has strong foundational architecture.** However, the audit reveals **7 P0 critical findings** that must be resolved before public release, primarily around security (missing capability checks on AJAX handlers), data integrity (broken revision history, bidirectional cleanup gaps), and a misleading version constant.

**Overall Product Health: 5.8/10**

The plugin is NOT ready for public release in its current state. It requires a focused security and data integrity hardening pass before launch.

---

## Architecture

### Module Map

```
WordPress Core
    │
    ├── Admin (class-admin.php, class-stitch-admin.php)
    │     └── Metaboxes, AJAX handlers, settings pages
    │
    ├── Gutenberg (class-sidebar.php, class-editors.php)
    │     ├── PluginDocumentSettingPanel (relationship-panel.js)
    │     └── Related Posts Block (gutenberg.js)
    │
    ├── REST API (class-rest-api.php)
    │     └── 7 endpoints: CRUD, pagination, types, bulk, exists
    │
    ├── AJAX (class-admin.php, class-user-relations-ajax.php)
    │     └── 8 handlers: search, add, remove, suggest, status, user relations
    │
    ├── PHP API (class-api.php)
    │     └── Core CRUD: add_relation, get_related, is_related, remove_relation
    │
    ├── Frontend (class-shortcodes.php, class-widget.php, class-templates.php, class-fluent-api.php)
    │     └── Shortcodes, widget, fluent API, templates
    │
    ├── Integrations (6 files)
    │     ├── WooCommerce, ACF, WPML, SEO, Editors, Duplicate Post
    │     └── Elementor (5 files: tags, AJAX, controls)
    │
    ├── Tools (11 files)
    │     ├── Overview, Analytics, Graph, Bulk Manager
    │     ├── Import/Export, Integrity, Orphaned, Auto-Relations
    │     ├── Settings (old), Site Health
    │     └── Integrity Helpers
    │
    ├── User Relations (2 files)
    │     └── Profile metabox, post metabox, AJAX search
    │
    ├── CLI (1 file)
    │     └── WP-CLI commands
    │
    └── Core Infrastructure (25 files)
          ├── Database, Settings, Capabilities, Cleanup
          ├── Cache, Query, Permissions, Constraints
          ├── Bidirectional Sync, Revision History
          ├── Status, Expiration, Webhooks, Cloning
          ├── Presets, Object Search, GraphQL, AI Suggestions
          └── Stitch Admin UI
```

### Dependencies

All modules depend on `class-api.php` as the central API layer. `class-relation-types.php` is the type registry. `class-database.php` manages schema. No circular dependencies detected.

### Dead Code

| File | Status | Evidence |
|------|--------|----------|
| `includes/tools/class-settings-old.php` | **DEAD CODE** | Defines duplicate `NATICORE_Settings` class; never loaded in main plugin file; excluded from PHPCS/PHPStan |
| `test_cleanup.php` | **DEV ARTIFACT** | Test file at root; should not ship |
| `benchmarks/performance-report.php` | **DEV ARTIFACT** | Benchmark file; should not ship |

### Duplicate Business Logic

| Logic | Location 1 | Location 2 | Issue |
|-------|-----------|-----------|-------|
| Settings class | `class-settings.php` (core) | `class-settings-old.php` (tools) | Duplicate `NATICORE_Settings` — old file is dead |
| Permission system | `class-permissions.php` | `class-api.php` (inline checks) | Disconnected — see P0 #1 |
| Cache system | `class-cache.php` (group: `naticore`) | `class-api.php` (group: `naticore_relationships`) | Dual cache groups — see P0 #3 |

---

## REST API

### Endpoint Table

| Endpoint | Method | Purpose | Auth | Permission | Nonce | Validation | Pagination |
|----------|--------|---------|------|------------|-------|------------|------------|
| `/naticore/v1/post/{id}` | GET | Get relationships | Cookie/Basic | `edit_post` | Optional | `absint`, `sanitize_text_field`, enum | Yes (page/per_page) |
| `/naticore/v1/post/{id}/type/{type}` | GET | Paginated by type | Cookie/Basic | `edit_post` | Optional | `absint`, `sanitize_text_field` | Yes (page/per_page) |
| `/naticore/v1/types` | GET | List types | Cookie/Basic | `edit_posts` | Optional | None needed | No |
| `/naticore/v1/relationships` | POST | Create relationship | Cookie/Basic | `edit_post` (from_id) | Optional | `absint`, `sanitize_text_field` | No |
| `/naticore/v1/relationships` | DELETE | Remove relationship | Cookie/Basic | `edit_post` (from_id) | Optional | `absint`, `sanitize_text_field` | No |
| `/naticore/v1/relationships/bulk` | POST | Bulk operations | Cookie/Basic | `manage_options` | Optional | Array validation | No |
| `/naticore/v1/relationships/exists` | GET | Check existence | Cookie/Basic | `edit_posts` | Optional | **Missing absint** | No |

### Pagination Endpoint Security (`GET /naticore/v1/post/{id}/type/{type}`)

| Check | Status | Evidence |
|-------|--------|----------|
| 1. Authentication required | ✅ | `permissions_check()` requires `edit_posts` or `edit_post` |
| 2. Source post permission | ✅ | Checks `edit_post` against `$id` |
| 3. Type exists | ✅ | `NATICORE_Relation_Types::get_type()` validates; returns 400 if not |
| 4. Type applies to source | ⚠️ | Not checked — type may not be valid for the source's post type |
| 5. Object type valid | ✅ | `to_type` derived from type registry |
| 6. page validated | ✅ | `absint`, minimum 1 |
| 7. per_page validated/capped | ✅ | `absint`, min 1, max 100 |
| 8. Search sanitized | ✅ | `sanitize_text_field`, minimum 2 chars enforced in SQL |
| 9. SQL prepared | ✅ | `$wpdb->prepare()` used for both COUNT and SELECT |
| 10. Cannot enumerate others' posts | ✅ | `edit_post` check on `$id` prevents this |

### Missing: `relationship_exists` endpoint has no `absint` `validate_callback` on `from_id`/`to_id` params — values pass through raw.

---

## Pagination Query Audit

### SQL Analysis: `get_type_page()`

```sql
-- COUNT query
SELECT COUNT(*) FROM `{prefix}content_relations` AS r
[INNER JOIN {users|terms|posts} ON ...]  -- only when search >= 2 chars
WHERE from_id = %d AND type = %s AND to_type = %s [AND search_condition]
-- Uses: type_lookup index (type, from_id, to_id) ✅

-- SELECT query
SELECT r.to_id, r.type, r.to_type FROM `{prefix}content_relations` AS r
[INNER JOIN ...]
WHERE from_id = %d AND type = %s AND to_type = %s [AND search_condition]
ORDER BY r.created_at DESC
LIMIT %d OFFSET %d
-- Uses: type_lookup index for WHERE, filesort for ORDER BY ⚠️
```

### Index Coverage

| Query Pattern | Expected Index | Actual | Risk |
|---------------|---------------|--------|------|
| `WHERE from_id = ? AND type = ? AND to_type = ?` | `type_lookup (type, from_id, to_id)` | ✅ Exists | Low |
| `WHERE from_id = ? AND type = ?` | `from_type (from_id, type)` | ✅ Exists | Low |
| `WHERE to_id = ? AND type = ?` | `to_type (to_id, type)` | ✅ Exists | Low |
| `ORDER BY created_at DESC` | `created_at` index | ❌ Missing | Medium — filesort on large tables |
| `WHERE to_type = ? AND to_id = ?` | `to_type_combined (to_type, to_id)` | ✅ Exists | Low |
| Search JOIN on `user_login` | `user_login` (WP core) | ✅ Exists | Low |
| Search JOIN on `post_title` | `post_title` (WP core) | ⚠️ Partial | Medium — no fulltext |
| Search JOIN on `term.name` | `name` (WP core) | ✅ Exists | Low |

### N+1 Queries

**VERIFIED**: The `get_type_page()` endpoint executes:
1. 1 COUNT query
2. 1 SELECT query
3. **N** calls to `get_userdata()` / `get_term()` / `get_post()` + `get_post_thumbnail_id()` + `get_post_type_object()`

For a page of 5 items, this is **1 + 1 + 5 = 7 queries minimum**, potentially more with thumbnail lookups.

**Bootstrap (`get_initial_relationships()`)** has the same N+1 pattern.

---

## Relationship Search Audit

**Static query analysis only** — no runtime benchmarks available.

### SQL LIKE Analysis

```sql
-- User search
WHERE (u.display_name LIKE '%{term}%' OR u.user_login LIKE '%{term}%')

-- Term search
WHERE t.name LIKE '%{term}%'

-- Post search
WHERE p.post_title LIKE '%{term}%'
```

### Performance Characteristics

| Scale | Leading Wildcard | Index Usable | Assessment |
|-------|-----------------|--------------|------------|
| 10K | Yes (`%term%`) | No | Acceptable |
| 100K | Yes | No | Potential bottleneck |
| 500K | Yes | No | Critical bottleneck |
| 1M | Yes | No | Critical bottleneck |

**Leading wildcard (`%term%`) prevents index usage.** MySQL must perform full table scans on the joined table. For post_title searches, this scans the entire `wp_posts` table.

**Result limit**: No explicit LIMIT on search results in `get_type_page()` — the COUNT query limits results, but the JOIN itself scans all matching rows before LIMIT is applied.

**Mitigation**: The JOIN is on the target table (users/terms/posts), not the relationship table. The relationship table is filtered first by `from_id + type + to_type` (uses index), then the JOIN narrows results. This is acceptable for moderate scales.

---

## Database Index Audit

### Schema: `{prefix}content_relations`

```sql
PRIMARY KEY (id),
KEY from_id (from_id),
KEY to_id (to_id),
KEY type (type),
KEY from_type (from_id, type),
KEY to_type (to_id, type),
KEY to_user_id (to_user_id),
KEY to_term_id (to_term_id),
KEY to_type_combined (to_type, to_id),
KEY type_lookup (type, from_id, to_id),
UNIQUE KEY unique_relation (from_id, to_id, type, to_type)
```

### Index Coverage

| Query | Best Index | Notes |
|-------|-----------|-------|
| `get_related($id, $type, 'post')` | `from_type` | ✅ Covered |
| `get_related($id, null, 'all')` | `from_id` | ✅ Covered |
| `is_related($from, $to, $type)` | `unique_relation` | ✅ Covered |
| `get_type_page($id, $type)` | `type_lookup` | ✅ Covered |
| `COUNT(*) by type` | `type_lookup` | ✅ Covered |
| `ORDER BY created_at` | None | ⚠️ Missing `created_at` index |
| `cleanup` orphaned | `from_id`, `to_id` | ✅ Covered (LEFT JOIN) |

### Missing Index

| Column | Reason | Impact |
|--------|--------|--------|
| `created_at` | Used in ORDER BY for pagination and `get_related()` | Filesort on large result sets |

---

## Data Integrity Audit

### Relationship Lifecycle

| Operation | Status | Issue |
|-----------|--------|-------|
| Create | ✅ | UNIQUE constraint prevents duplicates |
| Read | ✅ | Proper SQL with prepared statements |
| Update | ⚠️ | No `update` operation exists — only create/delete |
| Delete | ✅ | Fires `naticore_relation_removed` action |
| Post deletion | ⚠️ | Cleanup hooks exist but bidirectional reverse not cleaned — **P0 #2** |
| User deletion | ⚠️ | Same bidirectional gap |
| Term deletion | ⚠️ | Same bidirectional gap |
| Uninstall | ✅ | Respects user choice; drops table if opted in |
| Import | ⚠️ | No transaction wrapping; no batching on export — **P1** |
| Bulk operations | ✅ | Properly uses `$wpdb->prepare()` |

### Critical: Bidirectional Cleanup Gap

`cleanup_object_relationships()` deletes rows where the deleted object is `from_id` OR `to_id`. For bidirectional relations stored as TWO rows (A→B and B→A), deleting object A only cleans up rows where A is `from_id` or `to_id`. **The reverse row (B→A where B is the from_id) is NOT cleaned up if A is deleted**, because the cleanup only looks at the deleted object's ID. This leaves orphaned reverse relations.

---

## Cache Audit

### Cache Lifecycle Map

| What | Cache Key Pattern | Group | Invalidation | Issue |
|------|------------------|-------|-------------|-------|
| `get_related()` results | `naticore_get_related_{id}_{type}_{to_type}_{limit}_{orderby}_{manual}` | `naticore_relationships` | `clear_cache()` on relation add/remove | Key doesn't include offset — pagination stale |
| `is_related()` existence | `naticore_exists_{from}_{to}_{type}_{to_type}` | `naticore_relationships` | Same | ✅ |
| `NATICORE_Cache::remember()` | Custom callback-based | `naticore` | `invalidate_post()` flushes entire group | **Nuclear invalidation — P0 #3** |
| Overview counts | Transient | `_transient_naticore_*` | 5-min TTL | ✅ |
| Overview items | Transient | `_transient_naticore_*` | 5-min TTL | ✅ |

### Critical: Dual Cache Groups

`class-api.php` uses group `naticore_relationships` while `class-cache.php` uses group `naticore`. These are different groups. Flushing `naticore` does NOT invalidate `naticore_relationships`. Data can go stale across the two systems.

### Critical: Nuclear Cache Invalidation

`NATICORE_Cache::invalidate_post()` calls `wp_cache_flush_group(self::GROUP)` on every post change. This flushes the ENTIRE `naticore` cache group for ALL posts, not just the affected one. On busy sites, this destroys cache effectiveness.

---

## Security Audit

### Protection Matrix

| Surface | Auth | Nonce | Capability | Object Auth | Input | SQL | Output |
|---------|------|-------|------------|-------------|-------|-----|--------|
| REST GET | ✅ | Optional | `edit_posts`/`edit_post` | ✅ `edit_post` on id | ✅ | ✅ | ✅ |
| REST POST | ✅ | Optional | Implicit via API | ✅ `edit_post` on from_id | ✅ | ✅ | ✅ |
| REST DELETE | ✅ | Optional | Implicit via API | ✅ `edit_post` on from_id | ✅ | ✅ | ✅ |
| REST bulk | ✅ | Optional | `manage_options` | N/A | ✅ | ✅ | ✅ |
| AJAX search | ✅ | ✅ | `edit_post`/`edit_posts` | ⚠️ Post ID only | ✅ | ✅ | ✅ |
| **AJAX add relation** | ✅ | ✅ | **❌ NONE** | **❌ NONE** | ✅ | ✅ | ✅ |
| **AJAX remove relation** | ✅ | ✅ | **❌ NONE** | **❌ NONE** | ✅ | ✅ | ✅ |
| **AJAX save meta** | ✅ | ✅ | **❌ NONE** | **❌ NONE** | ✅ | ✅ | ✅ |
| **AJAX change status** | ✅ | ✅ | **❌ NONE** | **❌ NONE** | ✅ | ✅ | ✅ |
| User AJAX add | ✅ | ✅ | Implicit via API | Via API | ✅ | ✅ | ✅ |
| User AJAX remove | ✅ | ✅ | Implicit via API | Via API | ✅ | ✅ | ✅ |
| Graph AJAX | ✅ | ✅ | `manage_options` | N/A | ✅ | ✅ | ⚠️ Leaks titles |
| Cloning | ✅ | **❌ MISSING** | `edit_posts` | ❌ | ✅ | ✅ | ✅ |
| Import | ✅ | ✅ | `manage_options` | N/A | ✅ | ✅ | ✅ |
| Export | ✅ | ✅ | `manage_options` | N/A | N/A | ✅ | ✅ |

### Critical Security Findings

1. **AJAX handlers lack capability checks** — `ajax_add_relation()`, `ajax_remove_relation()`, `ajax_save_relation_meta()`, and `ajax_change_status()` only verify nonce. Any logged-in user (including Subscribers) with a valid nonce can modify any relationship.

2. **Cloning CSRF** — `handle_clone()` checks `edit_posts` but never verifies the nonce from the clone URL.

3. **`relationship_exists` missing validation** — `from_id`/`to_id` params have no `validate_callback`.

---

## User Data Privacy

| Data Point | Where Exposed | Necessary? |
|-----------|--------------|------------|
| `user_email` | `class-api.php:886` in `get_related()` | ❌ Not needed for relationship display |
| `user_login` | `class-object-search.php:148`, `class-rest-api.php:769` | ⚠️ Exposed in search results and pagination endpoint |
| `display_name` | Multiple locations | ✅ Required for display |
| `user_pass` | Never | ✅ |
| `user_meta` | Never exposed via API/AJAX | ✅ |

**Finding**: `user_email` is returned by `get_related()` when `$to_type = 'user'`. This is not used by the sidebar or admin UI but is available to any caller. The REST endpoint `get_type_page()` correctly does NOT return email.

---

## Gutenberg Sidebar Audit

### State Management
- Per-group state with pagination/search — well structured
- Functional `setGroups()` updates prevent stale closures
- No unnecessary re-renders detected

### API Calls
- Bootstrap via `wp_localize_script` — efficient
- Pagination via REST `apiFetch` — correct
- Search via AJAX `jQuery.ajax` — inconsistent with REST pattern but functional

### Search
- Debounced (300ms) — good
- Minimum 2 chars enforced — good
- Connected results grayed out — good UX

### Pagination
- Server-side with 5 items per page — good
- "Show More" appends correctly — good
- `hasMore` computed from `items.length < total` — correct

### CRUD
- Add via REST POST — proper
- Remove via REST DELETE — proper
- Optimistic UI with removing state — good

### Cardinality
- Shows "X of Y connections" — good
- Max connections from type registry — good

### Accessibility
- `aria-live="polite"` on loading states — good
- `role="listbox"` on search results — good
- Keyboard support on search results — good

### Error Handling
- Notice component for errors — good
- Timeout auto-dismiss (6s) — good
- Error messages from server — good

### Memory
- No memory leaks detected — state updates are functional
- ~725 lines — maintainable for a single-panel component

---

## Frontend Block Audit

### `naticore/related-posts` Block
- Server-side rendered (save returns null) — correct
- Block preview fetches via REST API — good
- InspectorControls for configuration — good
- Uses `NATICORE_Shortcodes` for rendering — DRY

### Gutenberg Sidebar vs Frontend Block
- Sidebar manages relationships (CRUD)
- Frontend block displays relationships (read-only)
- Both use the same core API layer — correct separation
- No architectural reason to merge them

---

## User Relationship System

### Architecture
- `class-user-relations.php` — Profile metabox, post metabox, search
- `class-user-relations-ajax.php` — Add/remove handlers
- `user-relations.js` — Client-side interaction

### Security
- AJAX handlers verify nonce ✅
- Delegates to `wp_add_relation()`/`wp_remove_relation()` for permission checks ✅
- Search delegates to `NATICORE_Object_Search` ✅

### Issues
- **Not unified with main relationship system** — uses separate AJAX handlers instead of REST API
- Comment at line 70: "Permissions are handled in NATICORE_API::add_relation via NATICORE_Capabilities" — relies on the disconnected permission system

---

## Admin UX Audit

### Stitch Admin UI
- Custom design system ("Nexus") with CSS custom properties — well structured
- Responsive breakpoints at 1024px and 768px — good
- Tab navigation, settings sidebar, modals — comprehensive

### Issues
| Area | Issue |
|------|-------|
| Settings | `class-settings-old.php` is dead code but still referenced in README |
| Settings | `ReflectionClass` used to access private `$default_types` — fragile |
| Settings | `enable_rest_api` always saves as 1 (bug in old file) |
| Overview | N+1 queries on `get_post()` per row |
| Analytics | Zero caching; N+1 queries |
| Graph | No SQL filtering by type; no caching |
| Integrity | Batch boundary breaks `max_connections` counting |
| Site Health | Broken link to non-existent integrity page |

---

## Developer Experience Audit

### Can a developer answer these questions?

| Question | Answer Available? | Where |
|----------|------------------|-------|
| How do I create a relationship? | ✅ | `wp_add_relation()`, Fluent API, developer-guide.php |
| How do I get related content? | ✅ | `wp_get_related()`, Fluent API, developer-guide.php |
| How do I query relationships? | ✅ | WP_Query `content_relation` arg, developer-guide.php |
| How do I remove a relationship? | ✅ | `wp_remove_relation()`, developer-guide.php |
| How do I use REST? | ⚠️ | Endpoint exists but no OpenAPI/Swagger docs |
| How do I integrate with WP_Query? | ✅ | `content_relation` arg documented in developer-guide.php |
| How do I hook into creation/removal? | ✅ | `naticore_relation_added`/`naticore_relation_removed` actions |
| How do I use relationships in Gutenberg? | ✅ | Block + sidebar documented in readme.txt |

### Missing Documentation
- REST API endpoint documentation (no formal API docs)
- Webhook payload documentation
- WP-CLI command reference (exists in code but not in docs)
- Migration guide for schema changes
- Performance guidelines for large datasets

---

## WP_Query Integration

### Current State

Developers CAN do:
- ✅ Get posts related to post X: `content_relation => ['post_id' => X]`
- ✅ Get posts related to user X: `content_relation => ['from_id' => X, 'direction' => 'incoming']`
- ✅ Filter WP_Query by relationship
- ❌ Order by relationship count — not supported
- ❌ Get relationship metadata in query results — not supported

### Developer Experience Problem

The `content_relation` query var works for filtering but:
1. No `ORDER BY` support for relationship-based ordering
2. No way to include relationship metadata in results
3. The legacy `wpcr` and `related_to` args create confusion
4. The `direction` swap uses `str_replace` on the entire WHERE clause — fragile

---

## Page Builder Readiness

### Elementor
- ✅ Dynamic tags for related posts, users, terms
- ✅ Custom relationship type control
- ✅ AJAX search for relationship types
- ⚠️ Incoming direction queries use direct SQL (not via API)

### Other Builders (Bricks, etc.)
- Not supported
- Would require similar dynamic tag implementation
- The PHP API layer (`wp_get_related()`) is builder-agnostic — good foundation

---

## Headless Readiness

### REST API Assessment

| Requirement | Status |
|-------------|--------|
| CRUD endpoints | ✅ |
| Pagination | ✅ |
| Filtering | ✅ |
| Search | ✅ |
| Relationship metadata | ⚠️ Not exposed in GET responses |
| Object types (post/user/term) | ✅ |
| Schema documentation | ❌ No OpenAPI spec |
| Batch operations | ✅ Bulk endpoint exists |
| Webhooks | ✅ But no retry/logging |

### Missing for Headless
- Relationship metadata in REST responses
- GraphQL support (class-graphql.php exists but depends on WPGraphQL plugin)
- REST API documentation
- Response pagination headers (X-WP-Total, X-WP-TotalPages)

---

## Import/Export Audit

### Export
- Fetches ALL rows at once — **memory bomb on large datasets**
- No batching or streaming
- JSON format — simple but no CSV option

### Import
- Per-row validation (ID exists, type registered, not duplicate)
- **No transaction wrapping** — partial failure leaves inconsistent data
- **No batch size limit** — could timeout on large imports
- Duplicate detection via `is_related()` — N+1 query problem

### Risks
| Risk | Impact | Mitigation |
|------|--------|-----------|
| Export OOM | PHP fatal on 100k+ relations | Add LIMIT/OFFSET batching |
| Import partial failure | Inconsistent data | Wrap in transaction |
| Import invalid IDs | Skipped silently | Good — but no user feedback on skipped count |
| Large import timeout | PHP max_execution_time | Add chunked processing |

---

## Uninstall/Migration

### Uninstall Behavior
- Respects `naticore_remove_data_on_uninstall` option
- If false: removes options only, preserves table
- If true: removes options, transients, table, and user meta
- ✅ Proper permission check (`activate_plugins`)

### Migration Framework
- Schema version tracked via `ncr_schema_version` option
- `maybe_upgrade()` runs on every admin init
- Uses `dbDelta()` + manual ALTER TABLE fallback
- ⚠️ No transaction wrapping on migration — partial failure leaves inconsistent schema
- ⚠️ `dbDelta()` is notoriously unreliable for index changes

### Version Constant Mismatch
- Plugin header: `Version: 1.4.0`
- `NATICORE_VERSION` constant: `'1.2.2'`
- `NCR_SCHEMA_VERSION`: `'1.4'`
- **This is a P0 issue** — version comparison logic may malfunction

---

## Observability/Debugging

### Available
- `WP_DEBUG` logging in API layer
- Query debug mode (`query_debug` setting)
- Site Health integration
- Integrity checker (daily)
- WP-CLI `check` command

### Gaps
- No structured logging (just `error_log`)
- No REST API error logging
- No webhook delivery logging
- No performance metrics collection
- No admin dashboard for relationship health

---

## Automated Testing Audit

### Current State
- **No PHPUnit tests found** — zero test files in the repository
- `composer.json` has `phpunit/phpunit` in `scripts.test` but no test directory
- `phpcs.xml` excludes `tests/` directory (which doesn't exist)
- No JavaScript tests
- No E2E tests

### Highest-Value Tests Needed

| Priority | Test | Why |
|----------|------|-----|
| 1 | Relationship CRUD | Core functionality |
| 2 | Duplicate prevention | UNIQUE constraint + PHP check |
| 3 | Bidirectional sync | Complex logic, easy to break |
| 4 | Cardinality enforcement | Race condition risk |
| 5 | Object deletion cleanup | Bidirectional gap exists |
| 6 | Permission checks | Security-critical |
| 7 | REST API endpoints | Input validation |
| 8 | WP_Query integration | SQL modification |

---

## WordPress Coding Standards

### PHP
- ✅ PHPCS configured with WordPress standard
- ✅ Many exclusions documented in `phpcs.xml`
- ⚠️ `phpstan.neon` excludes 4 files (including dead `class-settings-old.php`)
- ❌ No test coverage for PHPCS to validate against

### JavaScript
- ⚠️ No ESLint configuration
- ⚠️ Mix of jQuery (`admin.js`) and React (`relationship-panel.js`)
- ⚠️ `admin.js` uses global `NCR` object — not modular

### CSS
- ✅ Scoped to `.ncr-panel` / `.ncr-*` prefixes
- ✅ CSS custom properties in Stitch UI
- ⚠️ Inline CSS in `class-templates.php` (carousel)

---

## Product Health Scores

| Category | Score | Notes |
|----------|-------|-------|
| Architecture | 7/10 | Well-modularized but dual cache systems, dead code, disconnected permissions |
| Security | 4/10 | AJAX handlers missing capability checks; cloning CSRF; email exposure |
| Data Integrity | 5/10 | Bidirectional cleanup gap; broken revision history; no transaction safety |
| Performance | 6/10 | Good indexes; N+1 queries; nuclear cache invalidation; LIKE without index |
| Gutenberg UX | 8/10 | Polished Phase 1-3; pagination, search, cardinality all working |
| Admin UX | 6/10 | Stitch UI is comprehensive but dead settings file; broken health links |
| Developer Experience | 7/10 | Good PHP API; WP_Query integration; missing REST docs |
| Documentation | 5/10 | developer-guide.php is good; no API docs; no migration guide |
| Extensibility | 8/10 | Hooks, filters, presets, Elementor, WPML, ACF integrations |
| Testing | 1/10 | Zero tests |

### Overall Product Health: 5.7/10

---

## P0 Findings (Critical — Release Blockers)

### P0 #1: AJAX Handlers Missing Capability Checks
- **Problem**: `ajax_add_relation()`, `ajax_remove_relation()`, `ajax_save_relation_meta()`, `ajax_change_status()` only verify nonce, no `current_user_can()` call
- **Impact**: Any logged-in user (including Subscribers) can modify any relationship
- **Evidence**: `class-admin.php:531-599`, `class-status.php:213`
- **Solution**: Add `current_user_can('edit_post', $from_id)` check to each handler
- **Effort**: Low (1 line per handler)
- **Risk**: Low

### P0 #2: Bidirectional Cleanup Gap on Object Deletion
- **Problem**: Deleting an object only cleans up rows where it is `from_id` or `to_id`. Reverse bidirectional rows (where the deleted object is the target of a reverse relation) are NOT cleaned up.
- **Impact**: Orphaned reverse relationships persist after object deletion
- **Evidence**: `class-cleanup.php:73-149`
- **Solution**: For each bidirectional type, also delete the reverse row where `to_id = deleted_id AND type = $type`
- **Effort**: Medium
- **Risk**: Medium — must not delete non-bidirectional reverse rows

### P0 #3: Nuclear Cache Invalidation
- **Problem**: `NATICORE_Cache::invalidate_post()` calls `wp_cache_flush_group()` on every post change, flushing ALL cached relationships for ALL posts
- **Impact**: Severe performance regression on busy sites; cache is effectively useless
- **Evidence**: `class-cache.php:68-76`
- **Solution**: Replace with targeted `wp_cache_delete()` for affected post IDs
- **Effort**: Medium
- **Risk**: Low

### P0 #4: Dual Cache Groups
- **Problem**: `class-api.php` uses group `naticore_relationships`; `class-cache.php` uses group `naticore`. Invalidating one doesn't affect the other.
- **Impact**: Stale data can persist across the two cache systems
- **Evidence**: `class-api.php:820,908` vs `class-cache.php:52`
- **Solution**: Unify to a single cache group
- **Effort**: Low
- **Risk**: Low

### P0 #5: Version Constant Mismatch
- **Problem**: Plugin header says `1.4.0` but `NATICORE_VERSION` constant is `'1.2.2'`
- **Impact**: Version comparison logic, cache busting, and upgrade routines may malfunction
- **Evidence**: `native-content-relationships.php:6,24`
- **Solution**: Update `NATICORE_VERSION` to `'1.4.0'`
- **Effort**: Trivial
- **Risk**: Trivial

### P0 #6: Cloning CSRF
- **Problem**: `handle_clone()` checks `edit_posts` but never verifies the nonce from the clone URL
- **Impact**: Any page on the site can trigger a post clone via CSRF
- **Evidence**: `class-cloning.php:169-194`
- **Solution**: Add `wp_verify_nonce()` check
- **Effort**: Low
- **Risk**: Low

### P0 #7: Broken Revision History
- **Problem**: `NATICORE_Revision_History::log()` writes to `post_content` but `get_history()` queries via `meta_query` on `_ncr_from_id`/`_ncr_to_id` — these meta keys are never set. History queries always return empty.
- **Impact**: Revision history feature is completely non-functional
- **Evidence**: `class-revision-history.php` — `log()` vs `get_history()`
- **Solution**: Either set post meta during `log()` or query by `post_content` instead of `meta_query`
- **Effort**: Medium
- **Risk**: Low

---

## P1 Findings (High)

### P1 #1: Object Search Lacks Authentication
- **Problem**: `search_posts()`, `search_users()`, `search_products()` have no auth checks
- **Impact**: Any caller can enumerate posts/users/products
- **Evidence**: `class-object-search.php:62,129,166`
- **Solution**: Add `current_user_can('edit_posts')` check
- **Effort**: Low
- **Risk**: Low

### P1 #2: User Email Exposure
- **Problem**: `get_related()` returns `user_email` when `$to_type = 'user'`
- **Impact**: Unnecessary PII exposure
- **Evidence**: `class-api.php:886`
- **Solution**: Remove `user_email` from response or gate behind permission
- **Effort**: Trivial
- **Risk**: Low

### P1 #3: Permission System Disconnected
- **Problem**: `NATICORE_Permissions` stores role permissions in options, but `class-api.php` checks WordPress capabilities (`naticore_create_relation`). The two systems are not connected.
- **Impact**: Custom role permissions configured in the admin have no effect
- **Evidence**: `class-permissions.php:71-111` vs `class-api.php:120`
- **Solution**: Map custom permissions to WordPress capabilities on save
- **Effort**: High
- **Risk**: Medium

### P1 #4: Import No Transaction Safety
- **Problem**: Import processes rows individually with no transaction wrapping
- **Impact**: Partial failure leaves inconsistent data
- **Evidence**: `class-import-export.php:200-260`
- **Solution**: Wrap in `$wpdb->query('START TRANSACTION')` / `COMMIT` / `ROLLBACK`
- **Effort**: Medium
- **Risk**: Low

### P1 #5: Export Memory Bomb
- **Problem**: Export fetches ALL rows at once with no batching
- **Impact**: PHP OOM on sites with 100k+ relationships
- **Evidence**: `class-import-export.php:117`
- **Solution**: Add LIMIT/OFFSET batching or streaming
- **Effort**: Medium
- **Risk**: Low

### P1 #6: Constraint Validation Skips Non-Post Objects
- **Problem**: `validate_constraints()` calls `get_post()` for both IDs — returns null for users/terms, silently skipping validation
- **Impact**: Cardinality and constraint rules don't apply to user/term relationships
- **Evidence**: `class-constraints.php:96-101`
- **Solution**: Use `get_userdata()` / `get_term()` as appropriate based on `to_type`
- **Effort**: Medium
- **Risk**: Low

### P1 #7: Integrity Check Batch Boundary Bug
- **Problem**: `max_connections` counting is per-batch, not global. Batch boundaries can split constraint groups, causing violations to be missed.
- **Impact**: Integrity checker may report false "clean" status
- **Evidence**: `class-integrity.php:198-212`
- **Solution**: Track counts globally across batches or use SQL-level counting
- **Effort**: Medium
- **Risk**: Low

### P1 #8: Missing `created_at` Index
- **Problem**: `ORDER BY created_at DESC` used in pagination and `get_related()` but no index on `created_at`
- **Impact**: Filesort on large tables
- **Evidence**: Schema in `class-database.php:127-149`
- **Solution**: Add `KEY created_at (created_at)` to schema
- **Effort**: Low
- **Risk**: Low (requires migration)

---

## P2 Findings (Medium)

| # | Finding | Evidence |
|---|---------|----------|
| 1 | Test mode (`NCR_TEST_MODE`) bypasses all validation | `class-api.php:120,148,170,261` |
| 2 | `escape_table_name()` exists but is never called | `class-database.php:296-301` |
| 3 | Schema migration has no rollback | `class-database.php:172-216` |
| 4 | Bidirectional sync `$syncing` flag never cleared on error | `class-bidirectional-sync.php:170` |
| 5 | `ajax_change_status` lacks capability check | `class-status.php:213` |
| 6 | Webhook secrets stored in plaintext | `class-webhooks.php` |
| 7 | Graph AJAX leaks post titles without per-post permission check | `class-graph.php:216-217` |
| 8 | Settings `ReflectionClass` access to private property | `class-settings.php:599-602` |
| 9 | `class-settings-old.php` is dead code but still referenced | `includes/README.md:13` |
| 10 | Orphaned checker only detects post orphans, not user/term | `class-orphaned.php:73-78` |
| 11 | Auto-relations has no circular reference prevention | `class-auto-relations.php:67` |
| 12 | Site Health links to non-existent integrity page | `class-site-health.php:66` |
| 13 | POT file version is `1.0.23` — stale | `languages/native-content-relationships.pot:5` |
| 14 | `test_cleanup.php` and `benchmarks/` should not ship | Root directory |

---

## P3 Findings (Nice-to-have)

| # | Finding |
|---|---------|
| 1 | No ESLint configuration for JS |
| 2 | No OpenAPI/Swagger documentation for REST API |
| 3 | No structured logging (only `error_log`) |
| 4 | No webhook delivery retry mechanism |
| 5 | No performance metrics collection |
| 6 | Carousel template uses inline CSS/JS |
| 7 | `get_stats()` in cache class returns no useful data |
| 8 | Shortcode `[naticore_related_carousel]` duplicates functionality of other layout options |

---

## Release Blockers

The following MUST be fixed before public release:

1. **P0 #1**: AJAX capability checks — security vulnerability
2. **P0 #2**: Bidirectional cleanup — data corruption risk
3. **P0 #5**: Version constant mismatch — upgrade routine malfunction
4. **P0 #6**: Cloning CSRF — security vulnerability
5. **P0 #7**: Broken revision history — non-functional feature

**Recommended**: Also fix P0 #3 (nuclear cache) and P0 #4 (dual cache) before release, as they affect performance on production sites.

---

## Recommended Roadmap

### Next 1–2 Weeks (Pre-Release Hardening)
1. Fix all P0 findings (7 items)
2. Fix P1 #1 (object search auth) and P1 #2 (email exposure)
3. Delete dead code (`class-settings-old.php`, `test_cleanup.php`)
4. Add `created_at` index
5. Write basic PHPUnit tests for CRUD, duplicate prevention, permissions

### Next 1–2 Months (Post-Release Stabilization)
1. Fix remaining P1 findings
2. Unify permission system (P1 #3)
3. Add REST API documentation
4. Add transaction safety to import
5. Fix N+1 queries in admin pages
6. Add webhook delivery logging

### Next 3–6 Months (Growth)
1. WPGraphQL support
2. Order by relationship in WP_Query
3. Relationship metadata in REST responses
4. Page builder dynamic tags (Bricks, etc.)
5. Performance benchmarks at scale
6. Elasticsearch integration for search (optional)

---

## What NOT to Build Yet

| Feature | Why Not |
|---------|---------|
| AI suggestions | Foundation not ready; security issues first |
| Graph visualization improvements | Caching and performance issues first |
| Elementor deep integration | Existing tags work; polish later |
| WPGraphQL | REST API needs documentation first |
| External search (Elasticsearch) | Current LIKE search acceptable for most sites |
| Advanced analytics | Current analytics has N+1 and caching issues |
| WP All Import integration | Import system needs transaction safety first |

---

## Final Release Recommendation

## NO — Not Ready for Public Release

**Release blockers:**
1. AJAX handlers lack capability checks (security vulnerability)
2. Bidirectional cleanup gap (data corruption)
3. Version constant mismatch (upgrade malfunction)
4. Cloning CSRF (security vulnerability)
5. Broken revision history (non-functional feature)

**Conditions for YES:**
- All P0 findings resolved
- P1 #1 and P1 #2 resolved
- Dead code removed
- Basic test coverage (CRUD, permissions, duplicate prevention)
- `NATICORE_VERSION` updated to `1.4.0`

**Estimated effort to reach YES: 3–5 days of focused work.**

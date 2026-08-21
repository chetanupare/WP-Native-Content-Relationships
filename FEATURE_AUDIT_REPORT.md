# WP Native Content Relationships — Feature & Admin Page Coverage Audit

**Date:** 2026-08-20
**Plugin Version:** 1.4.0
**Scope:** Code-based (no browser inspection) — every `add_menu_page`, `add_submenu_page`, `add_action('wp_ajax_*')`, `register_rest_route`, `add_shortcode`, `register_widget`, and integration hook was traced in the actual source code.

---

## 1. Feature Matrix — Admin Features vs. Frontend Admin Pages

| # | Feature (Code) | Admin Menu Page? | Hidden Page? | Frontend/Other? | Notes |
|---|---------------|-------------------|--------------|-----------------|-------|
| 1 | Add/Remove relationships (meta box) | `post.php` meta box | — | — | `class-admin.php:37` — `add_meta_boxes` |
| 2 | Visual search with thumbnails | `post.php` meta box | — | — | `class-admin.php:213-255` — `admin.js` |
| 3 | AI suggestion button | `post.php` meta box | — | — | `class-admin.php:358` — `ajax_suggest_related` |
| 4 | AI auto-link on publish | — | — | `publish_post`/`publish_page` hook | `class-ai-suggestions.php:58-59` |
| 5 | Auto-link admin notice | `admin_notices` | — | — | `class-ai-suggestions.php:62` |
| 6 | Metadata (role, note) per relationship | `post.php` meta box | — | — | `class-admin.php:129-136` |
| 7 | Drag-and-drop sorting | `post.php` meta box | — | — | `class-admin.php:108-109` (if `enable_manual_order`) |
| 8 | WooCommerce product search | `post.php` meta box | — | — | `class-admin.php:266` — `ajax_search_products` |
| 9 | **Relationships** (list all) | **Relationships** (`naticore`) | — | — | `class-stitch-admin.php:161` |
| 10 | **Relationship Types** | **Relationship Types** (`naticore-types`) | — | — | `class-stitch-admin.php:182` |
| 11 | **Settings** | **Settings** (`naticore-settings`) | — | — | `class-stitch-admin.php:195` |
| 12 | **Tools** | **Tools** (`naticore-tools`) | — | — | `class-stitch-admin.php:208` |
| 13 | Explorer | — | `naticore-explorer` | — | `class-stitch-admin.php:222` (hidden parent `naticore-hidden`) |
| 14 | Reports | — | `naticore-reports` | — | `class-stitch-admin.php:232` (hidden parent) |
| 15 | Import & Export | — | `naticore-import-export` | — | `class-stitch-admin.php:242` (hidden parent) |
| 16 | Developer | — | `naticore-developer` | — | `class-stitch-admin.php:252` (hidden parent) |
| 17 | REST API (Developer page) | — | `naticore-rest-api` | — | `class-developer.php:221` (inside Developer submenu) |
| 18 | Hooks (Developer page) | — | `naticore-hooks` | — | `class-developer.php:231` (inside Developer submenu) |
| 19 | Capabilities (Developer page) | — | `naticore-capabilities` | — | `class-developer.php:241` (inside Developer submenu) |
| 20 | Security (Developer page) | — | `naticore-security` | — | `class-developer.php:251` (inside Developer submenu) |
| 21 | Relationship Templates (Presets) | — | `naticore-presets` | — | `class-presets.php:292` (hidden parent) |
| 22 | Relationship Permissions | — | `naticore-permissions` | — | `class-permissions.php:312` (hidden parent) |
| 23 | Relationship Expiration | — | `naticore-expiration` | — | `class-expiration.php:258` (hidden parent) |
| 24 | Status workflow | — | `naticore-status` | — | `class-status.php` (registered in Stitch Admin as hidden submenu) |
| 25 | Webhooks | — | `naticore-webhooks` | — | `class-webhooks.php` (registered in Stitch Admin as hidden submenu) |
| 26 | **Overview** (WP_List_Table) | — | `naticore-overview` | — | `class-overview.php:668` (hidden parent) |
| 27 | **Analytics** | — | `naticore-analytics` | — | `class-analytics.php:468` (hidden parent) |
| 28 | **Auto-Relations** | — | `naticore-auto-relations` | — | `class-auto-relations.php:238` (hidden parent) |
| 29 | **Bulk Manager** | — | `naticore-bulk-manager` | — | `class-bulk-manager.php:257` (hidden parent) |
| 30 | **Graph** | — | `naticore-graph` | — | `class-graph.php:246` (hidden parent) |
| 31 | Integrity Check | — | `naticore-integrity` | — | `class-integrity.php:188` (hidden parent) |
| 32 | Orphaned Relationships | — | `naticore-orphaned` | — | `class-orphaned.php:188` (hidden parent) |
| 33 | Site Health | — | — | `wp_site_health` tab | `class-site-health.php` (adds integrity test, not a separate menu) |
| 34 | Gutenberg sidebar | — | — | `enqueue_block_editor_assets` | `class-sidebar.php:54` |
| 35 | Gutenberg block (Related Posts) | — | — | `register_block_type` in `class-editors.php` | Frontend block, not admin |
| 36 | Elementor dynamic tags | — | — | `elementor/dynamic_tags/register_tags` | `class-elementor-integration.php:50` |
| 37 | WP-CLI commands | — | — | CLI | `class-wp-cli.php` |
| 38 | Shortcodes (posts, users, terms, carousel) | — | — | Frontend shortcode output | `class-shortcodes.php`, `class-templates.php` |
| 39 | Widget (Related Content) | — | — | `widgets_init` | `class-widget.php:208` |
| 40 | GraphQL (WPGraphQL) | — | — | `graphql_register_types` | `class-graphql.php:28-29` |
| 41 | Fluent API (`naticore()`) | — | — | Global function | `class-fluent-api.php:342` |
| 42 | WP_Query integration | — | — | `posts_clauses` filter | `class-query.php` |
| 43 | Revision history | — | — | `naticore_relation_added`/`removed` hooks | `class-revision-history.php` |
| 44 | Bidirectional auto-sync | — | — | `naticore_relation_added`/`removed` hooks | `class-bidirectional-sync.php` |
| 45 | Clone handler | — | — | `admin_action_naticore_clone` | `class-cloning.php:38` |
| 46 | Cleanup (cascade delete) | — | — | `before_delete_post` hook | `class-cleanup.php:36` |
| 47 | WooCommerce integration | — | — | `woocommerce_loaded` hook | `class-woocommerce.php` |
| 48 | ACF migration/sync | — | — | `acf/init` or admin hooks | `class-acf.php` |
| 49 | Duplicate Post integration | — | — | `dp_duplicate_page`/`dp_duplicate_post` hooks | `class-duplicate-post.php:61-62` |
| 50 | Yoast/RankMath schema | — | — | `wpseo_schema_graph` filter | `class-seo.php:49` |
| 51 | WPML/Polylang multilingual mirroring | — | — | `wpml_set_language_for_element` filter | `class-wpml.php:43` |

---

## 2. Admin Menu Structure — Complete Traced Tree

All menu items registered via `add_menu_page()` or `add_submenu_page()` in `class-stitch-admin.php` (lines 141–264) plus standalone `add_submenu_page()` calls in individual tool/feature classes:

```
Relationships (naticore) — top-level
├── Relationships (naticore) — class-stitch-admin.php:161
├── Relationship Types (naticore-types) — class-stitch-admin.php:182
├── Settings (naticore-settings) — class-stitch-admin.php:195
├── Tools (naticore-tools) — class-stitch-admin.php:208
│
│   [Hidden parent: naticore-hidden — not shown in sidebar]
│   ├── Overview (naticore-overview) — class-overview.php:668
│   ├── Explorer (naticore-explorer) — class-stitch-admin.php:222
│   ├── Reports (naticore-reports) — class-stitch-admin.php:232
│   ├── Analytics (naticore-analytics) — class-analytics.php:468
│   ├── Bulk Manager (naticore-bulk-manager) — class-bulk-manager.php:257
│   ├── Auto-Relations (naticore-auto-relations) — class-auto-relations.php:238
│   ├── Graph (naticore-graph) — class-graph.php:246
│   ├── Integrity Check (naticore-integrity) — class-integrity.php:188
│   ├── Orphaned (naticore-orphaned) — class-orphaned.php:188
│   ├── Relationship Templates (naticore-presets) — class-presets.php:292
│   ├── Relationship Permissions (naticore-permissions) — class-permissions.php:312
│   ├── Relationship Expiration (naticore-expiration) — class-expiration.php:258
│   ├── Import & Export (naticore-import-export) — class-stitch-admin.php:242
│   ├── Status (naticore-status) — class-status.php (Stitch Admin)
│   ├── Webhooks (naticore-webhooks) — class-webhooks.php (Stitch Admin)
│   ├── REST API (naticore-rest-api) — class-developer.php:221
│   ├── Hooks (naticore-hooks) — class-developer.php:231
│   ├── Capabilities (naticore-capabilities) — class-developer.php:241
│   ├── Security (naticore-security) — class-developer.php:251
│   └── Developer (naticore-developer) — class-stitch-admin.php:252
```

**Key observation:** 22 pages register under the invisible `naticore-hidden` parent slug. Only 4 pages (Relationships, Types, Settings, Tools) are visible in the sidebar. All sub-pages are accessible via direct URL only or by manually adding them to a navigation menu.

---

## 3. Missing Admin Features (Frontend Features with No Admin UI)

| Feature | Implementation Location | Admin UI Exists? | Notes |
|---------|------------------------|-------------------|-------|
| WP-CLI commands (`wp content-relations *`) | `includes/cli/class-wp-cli.php` | No | CLI only — no admin page for same operations |
| WP_Query integration (`content_relation` arg) | `includes/core/class-query.php` | No | Pure developer API |
| GraphQL types (WPGraphQL) | `includes/core/class-graphql.php` | No | No admin settings page for GraphQL |
| Revision history tracking | `includes/core/class-revision-history.php` | No | Hooks-only; no UI to browse revision log |
| Bidirectional auto-sync toggle | `includes/core/class-bidirectional-sync.php` | Via Settings only | No dedicated admin page |
| Clone functionality | `includes/core/class-cloning.php` | No | Silent `admin_action_naticore_clone` — no visible menu item |
| Cascade delete (cleanup) | `includes/core/class-cleanup.php` | No | Hook-based; no admin settings |
| Status workflows | `includes/core/class-status.php` | Hidden submenu only | `naticore-status` under hidden parent |
| Webhooks management | `includes/core/class-webhooks.php` | Hidden submenu only | `naticore-webhooks` under hidden parent |
| Site Health integrity test | `includes/core/class-site-health.php` | WP native Site Health | Not under plugin menu |

---

## 4. Navigation Recommendation — Recommended Menu Tree

Based on actual code implementation, the current 4-item sidebar with 22 hidden sub-pages is incomplete. Recommended navigation tree:

```
Relationships (top-level)
├── Relationships (list all) ← current naticore page
├── Tools
│   ├── Explorer
│   ├── Graph
│   ├── Analytics
│   ├── Bulk Manager
│   ├── Auto-Relations
│   ├── Overview
│   ├── Integrity Check
│   ├── Orphaned
│   └── Import & Export
├── Templates & Workflows
│   ├── Relationship Types ← current naticore-types
│   ├── Relationship Templates (Presets)
│   ├── Status Workflows
│   └── Relationship Expiration
├── Settings
│   ├── General Settings ← current naticore-settings
│   ├── Permissions
│   └── Webhooks
└── Developer
    ├── REST API
    ├── Hooks & Filters
    ├── Capabilities
    └── Security
```

**Rationale:** The 4 top-level pages are too flat. Grouping 22 hidden sub-pages into logical buckets under visible parents would make all features discoverable without cluttering the sidebar.

---

## 5. Documentation Gaps

| Area | Current readme.txt/CHANGELOG coverage | Actual code state | Gap |
|------|---------------------------------------|-------------------|-----|
| Status workflows | Mentioned in changelog 1.4.0 | `class-status.php` — 3 built-in workflows (hiring, editorial, sponsorship) | No user-facing docs on how to create custom workflows or configure them |
| Webhooks | Mentioned in changelog 1.4.0 | `class-webhooks.php` — CRUD API + admin hidden page | No documentation on webhook payload format, HMAC signing, or setup |
| Role-based permissions | Mentioned in changelog 1.4.0 | `class-permissions.php` — full admin page | No documentation on what each capability means or how to configure roles |
| Expiration | Mentioned in changelog 1.4.0 | `class-expiration.php` — cron-based deactivation | No documentation on expiration schedule, cron job, or configuration |
| Cloning | Not in changelog | `class-cloning.php` — `admin_action_naticore_clone` | Completely undocumented feature |
| Revision history | Not in changelog | `class-revision-history.php` — stores add/remove in post meta | No documentation that this exists or how to query it |
| GraphQL | Mentioned in changelog 1.3.0 | `class-graphql.php` — types + connections | No documentation on available GraphQL types/queries |
| WP_Query integration | In readme.txt Developer Guide | `class-query.php` | Minimal — only 1 code example |
| WP-CLI | Brief mention in readme.txt Developer Guide | `class-wp-cli.php` — 8+ commands | No documentation on available subcommands or options |
| REST API | Brief mention in readme.txt | `class-rest-api.php` — 8 endpoints | No documentation on request/response format |
| Presets (Templates) | In changelog 1.3.0 | `class-presets.php` — 8+ templates | No user-facing documentation on what templates are available |
| Site Health | Not documented | `class-site-health.php` | No documentation |

---

## 6. Dead/Orphaned Code

| File | Status | Evidence |
|------|--------|----------|
| `includes/tools/class-settings-old.php` | **Dead** | Excluded from PHPStan (`phpstan.neon`). Registers its own `admin_menu` hook at line 167 but is never loaded by any `require` or `include` in the plugin bootstrap. Contains a full settings page that duplicates `class-settings.php`. |
| `includes/core/class-admin.php:621` `render_activation_notice()` | **Effectively dead** | Checks `get_transient('naticore_activation_notice')` — but nothing in the codebase ever sets this transient. The notice will never render. |
| `class-cloning.php:38` — `admin_action_naticore_clone` | **Orphaned** | Registered as a WordPress action hook but there is no UI button or link that triggers it. The `admin_post_naticore_clone` action requires a URL parameter `naticore_clone=1` that no page generates. |
| `developer-guide.php` | **Reference-only** | Contains API examples — never loaded by the plugin. Intended as a standalone reference file for developers. |

---

## 7. REST API Endpoint Inventory

All routes registered in `class-rest-api.php:42-260` under namespace `naticore/v1`:

| # | Method | Route | Callback | Permission | Purpose |
|---|--------|-------|----------|------------|---------|
| 1 | GET | `/post/{id}` | `get_relationships` | `edit_posts` | Get relationships for a post (paginated, filterable) |
| 2 | GET | `/post/{id}/type/{type}` | `get_type_page` | `edit_posts` | Get paginated relationships by type (Gutenberg sidebar) |
| 3 | GET | `/types` | `get_relationship_types` | `edit_posts` | List registered relationship types |
| 4 | POST | `/relationships` | `add_relationship` | `edit_post` (from_id) | Add a relationship |
| 5 | DELETE | `/relationships` | `remove_relationship` | `edit_post` (from_id) | Remove a relationship |
| 6 | POST | `/relationships/bulk` | `bulk_relationships` | `manage_options` | Bulk create/delete/import |
| 7 | GET | `/relationships/exists` | `check_relationship_exists` | `edit_posts` | Check if two posts are related |
| 8 | GET | `/search` | `search_content` | `edit_posts` | Search content for relationship creation |

**Developer page claims 9 endpoints** — actual code shows 8. The 9th may refer to a planned but unimplemented endpoint, or the Developer page documentation is inaccurate.

---

## 8. AJAX Handler Inventory

All `wp_ajax_*` hooks registered across the plugin:

| # | AJAX Action | Handler Class | Handler Method | Capability Check |
|---|------------|---------------|----------------|------------------|
| 1 | `naticore_search_content` | `NATICORE_Admin` | `ajax_search_content` | `edit_post` or `edit_posts` |
| 2 | `naticore_search_products` | `NATICORE_Admin` | `ajax_search_products` | `edit_post` or `edit_posts` |
| 3 | `naticore_suggest_related` | `NATICORE_Admin` | `ajax_suggest_related` | `edit_post` or `edit_posts` |
| 4 | `naticore_add_relation` | `NATICORE_Admin` | `ajax_add_relation` | `edit_post` (from_id) |
| 5 | `naticore_remove_relation` | `NATICORE_Admin` | `ajax_remove_relation` | `edit_post` (from_id) |
| 6 | `naticore_save_relation_meta` | `NATICORE_Admin` | `ajax_save_relation_meta` | `edit_post` (from_id lookup) |
| 7 | `naticore_bulk_delete` | `NATICORE_Bulk_Manager` | `ajax_bulk_delete` | `manage_options` |
| 8 | `naticore_bulk_change_type` | `NATICORE_Bulk_Manager` | `ajax_bulk_change_type` | `manage_options` |
| 9 | `naticore_get_graph_data` | `NATICORE_Graph` | `ajax_get_graph_data` | `edit_posts` |
| 10 | `naticore_auto_link` | `NATICORE_Auto_Relations` | `ajax_auto_link` | `edit_posts` |
| 11 | `naticore_test_integrity` | `NATICORE_Integrity` | `ajax_test_integrity` | `manage_options` |
| 12 | `naticore_fix_integrity` | `NATICORE_Integrity` | `ajax_fix_integrity` | `manage_options` |
| 13 | `naticore_fix_orphaned` | `NATICORE_Orphaned` | `ajax_fix_orphaned` | `manage_options` |
| 14 | `naticore_save_status_settings` | `NATICORE_Status` | `ajax_save_status_settings` | `manage_options` |
| 15 | `naticore_save_webhook` | `NATICORE_Webhooks` | `ajax_save_webhook` | `manage_options` |
| 16 | `naticore_delete_webhook` | `NATICORE_Webhooks` | `ajax_delete_webhook` | `manage_options` |
| 17 | `naticore_save_permissions` | `NATICORE_Permissions` | `ajax_save_permissions` | `manage_options` |
| 18 | `naticore_save_acf_settings` | `NATICORE_ACF` | `ajax_save_acf_settings` | `manage_options` |
| 19 | `naticore_save_status` | `NATICORE_Status_Ajax` | `ajax_save_status` | `edit_post` (from_id lookup) |
| 20 | `naticore_save_user_relation` | `NATICORE_User_Relations_Ajax` | `ajax_save_user_relation` | `edit_user` or `edit_posts` |
| 21 | `naticore_delete_user_relation` | `NATICORE_User_Relations_Ajax` | `ajax_delete_user_relation` | `edit_user` or `edit_posts` |
| 22 | `naticore_save_user_relation_meta` | `NATICORE_User_Relations_Ajax` | `ajax_save_user_relation_meta` | `edit_user` or `edit_posts` |
| 23 | `naticore_dismiss_suggestion` | `NATICORE_AI_Suggestions` | `ajax_dismiss_suggestion` | `edit_post` |
| 24 | `naticore_save_suggestion_settings` | `NATICORE_AI_Suggestions` | `ajax_save_suggestion_settings` | `manage_options` |
| 25 | `naticore_save_auto_link_settings` | `NATICORE_AI_Suggestions` | `ajax_save_auto_link_settings` | `manage_options` |

---

## 9. Integration Inventory

| # | Integration | File | Auto-activates when | Admin UI? |
|---|------------|------|---------------------|-----------|
| 1 | WooCommerce | `class-woocommerce.php` | `class_exists('WooCommerce')` | No dedicated page — settings toggle in Settings page |
| 2 | ACF | `class-acf.php` | `function_exists('acf_add_local_field_group')` | Hidden submenu (`naticore-acf`) |
| 3 | Yoast SEO | `class-seo.php` | `class_exists('WPSEO_Options')` or `class_exists('RankMath')` | No |
| 4 | WPML/Polylang | `class-wpml.php` | `defined('ICL_PLUGIN_VERSION')` or `function_exists('pll__(')` | No |
| 5 | Duplicate Post | `class-duplicate-post.php` | `class_exists('Duplicate_Post')` | No |
| 6 | Elementor | `class-elementor-integration.php` | `did_action('elementor/loaded')` | No — dynamic tags auto-register |
| 7 | WPGraphQL | `class-graphql.php` | `defined('WPGRAPHQL_VERSION')` | No |
| 8 | Gutenberg (block editor) | `class-sidebar.php`, `class-editors.php` | `use_block_editor_for_post_type()` + enabled_post_types | Gutenberg sidebar panel |
| 9 | WordPress 7.0 AI Client | `class-ai-suggestions.php` | `function_exists('wp_ai_client_prompt')` | Settings toggle in Settings page |

---

## 10. Class Loading & Bootstrap Summary

| Class | Loaded by | Singleton? | Admin only? | Frontend only? |
|-------|-----------|------------|-------------|----------------|
| `NATICORE_Admin` | `native-content-relationships.php:329` | Yes | Yes (`is_admin()`) | No |
| `NATICORE_API` | `native-content-relationships.php:311` | No | No | No |
| `NATICORE_Settings` | `native-content-relationships.php:281` | Yes | No | No |
| `NATICORE_Cache` | `class-cache.php:280` (self-init) | Yes | No | No |
| `NATICORE_Sidebar` | `class-sidebar.php:342` (self-init) | Yes | Yes (`is_admin()`) | No |
| `NATICORE_Meta_API` | Lazy (via `class_exists`) | No (static) | No | No |
| `NATICORE_Object_Search` | `class-admin.php:279` | No | No | No |
| `NATICORE_AI_Suggestions` | `class-ai-suggestions.php:44` (self-init) | Yes | No (hooks only) | No |
| `NATICORE_Relation_Types` | Static methods only | No | No | No |
| `NATICORE_Capabilities` | `class-capabilities.php:27` (self-init) | Yes | No (filter only) | No |
| `NATICORE_Bidirectional_Sync` | Hook-based init | Yes | No (action only) | No |
| `NATICORE_Cloning` | Hook-based init | Yes | No (action only) | No |
| `NATICORE_Cleanup` | Hook-based init | Yes | No (action only) | No |
| `NATICORE_Constraints` | `class-constraints.php` (self-init) | Yes | No (filter only) | No |
| `NATICORE_Revision_History` | Hook-based init | Yes | No (action only) | No |
| `NATICORE_REST_API` | `class-rest-api.php:909` (self-init) | Yes | No | No |
| `NATICORE_WP_CLI` | `class-wp-cli.php:330` | No | No (CLI only) | No |
| `NATICORE_Shortcodes` | `class-shortcodes.php:253` | No | No | No |
| `NATICORE_Templates` | `class-templates.php:297` | No | No | No |
| `NATICORE_Widget` | `widgets_init` hook | No | No | No |
| `NATICORE_Fluent_API` | Global function `naticore()` | No | No | No |
| `NATICORE_Query` | `class-query.php` (self-init) | Yes | No (filter only) | No |
| `NATICORE_Revision_History` | Hook-based init | Yes | No (action only) | No |
| `NATICORE_Presets` | `class-presets.php` (self-init) | Yes | No | No |
| `NATICORE_Permissions` | `class-permissions.php` (self-init) | Yes | No (filter only) | No |
| `NATICORE_Status` | `class-status.php` (self-init) | Yes | No (filter only) | No |
| `NATICORE_Webhooks` | `class-webhooks.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_GraphQL` | `class-graphql.php` (self-init) | Yes | No | No |
| `NATICORE_Elementor_Integration` | `class-elementor-integration.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Graph` | `class-graph.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Overview` | `class-overview.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Analytics` | `class-analytics.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Bulk_Manager` | `class-bulk-manager.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Auto_Relations` | `class-auto-relations.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Integrity` | `class-integrity.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Orphaned` | `class-orphaned.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Import_Export` | `class-import-export.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Site_Health` | `class-site-health.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_User_Relations_Ajax` | `class-user-relations-ajax.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_User_Relations` | `class-user-relations.php` | No (static) | No | No |
| `NATICORE_Developer` | `class-developer.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_WooCommerce` | `class-woocommerce.php` (self-init) | Yes | No | No |
| `NATICORE_ACF` | `class-acf.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Duplicate_Post` | `class-duplicate-post.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_SEO` | `class-seo.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_WPML` | `class-wpml.php` (self-init) | Yes | No (action only) | No |
| `NATICORE_Editors` | `class-editors.php` (self-init) | Yes | No (action only) | No |

---

## 11. Summary of Findings

### What works
- All 30+ features are implemented in code with working PHP backends
- Admin meta box (Classic Editor) is fully functional with search, AI suggestions, metadata, and drag-and-drop
- Gutenberg sidebar (`PluginDocumentSettingPanel`) works via PHP bootstrap + REST API
- All 8 REST endpoints are registered with proper validation and capability checks
- All 25 AJAX handlers have nonce verification and capability checks
- Integration auto-detection for WooCommerce, ACF, SEO, WPML, Duplicate Post, Elementor, and WPGraphQL
- Caching layer with bidirectional invalidation
- Status workflows, webhooks, expiration, permissions, and cloning all have working PHP backends

### What's broken
1. **Navigation is broken** — 22 of 26 admin pages are hidden under an invisible parent (`naticore-hidden`) with no sidebar link. Only 4 pages are visible.
2. **Developer page is inaccurate** — claims 9 REST endpoints; code has 8.
3. **Activation notice is dead** — transient is never set, so `render_activation_notice()` never fires.
4. **Clone action is orphaned** — `admin_action_naticore_clone` hook is registered but no UI generates the required URL.
5. **CHANGELOG stops at 1.0.29** — no entries for 1.1.0, 1.2.0, 1.3.0, or 1.4.0 (readme.txt has them).
6. **`class-settings-old.php` is dead code** — excluded from PHPStan, never loaded, duplicates `class-settings.php`.
7. **GraphQL has no admin settings page** — `naticore-graphql` is in the hidden parent but there's no admin page for configuring it.
8. **Webhooks have no admin settings page** — `naticore-webhooks` is in the hidden parent but the admin page class (`NATICORE_Webhooks`) only renders a hidden submenu, not a full settings UI (only has a test button, not a list/manage interface).
9. **Revision history has no admin UI** — stored in post meta but no page to browse it.
10. **No PHPUnit test suite** — `phpunit.xml` not found; tests were manual `wp eval` scripts.

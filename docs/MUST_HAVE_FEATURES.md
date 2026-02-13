# Must-Have Features to Stand Out

Features the Native Content Relationships plugin is missing to stand out vs. competitors (Posts 2 Posts, MB Relationships) and meet user expectations. Resolve one by one.

---

## Priority overview

| Priority | Feature | Status |
|----------|---------|--------|
| P0 | [Built-in shortcode(s)](#1-built-in-shortcodes--p0) | ✅ Done |
| P0 | [REST embed for relations](#2-rest-embed-for-relations--p0) | ✅ Done |
| P1 | [Manual order for related items](#3-manual-order-for-related-items--p1) | ⬜ Todo |
| P1 | [Classic widget](#4-classic-widget--p1) | ✅ Done |
| P2 | [Duplicate-post integration](#5-duplicate-post-integration--p2) | ✅ Done |
| P2 | [Block/shortcode layout options](#6-block--shortcode-layout-options--p2) | ✅ Done |
| P2 | [Onboarding / guided setup](#7-onboarding--guided-setup--p2) | ✅ Done |
| P3 | [Suggest related / auto-relations](#8-suggest-related--auto-relations--p3) | ✅ Done |

---

## 1. Built-in shortcode(s) — P0

**Why:** Block and Elementor exist; the only shortcode is an example in the developer guide. Many sites still rely on shortcodes.

**Gap:** No `[naticore_related_posts]` (and siblings) registered by the plugin.

**Tasks:**
- [x] Register `[naticore_related_posts]` with atts: `type`, `limit`, `order`, `post_id`, `layout` (list/grid), optional `class`
- [x] Optionally add `[naticore_related_users]` and `[naticore_related_terms]` with similar atts
- [x] Use existing `wp_get_related()` / `wp_get_related_users()` / `wp_get_related_terms()`; keep output minimal but semantic (e.g. `<ul>` or `<div class="naticore-related">`)
- [x] Document shortcodes in readme and in-plugin help

**Notes:** Implemented in `includes/class-shortcodes.php`. Minimal frontend CSS in `assets/css/shortcodes.css` (enqueued when shortcode is used).

---

## 2. REST embed for relations — P0

**Why:** Headless clients currently need two requests (post + relations). Embedding relations in core REST responses makes NCR the natural choice for headless.

**Gap:** No `register_rest_field()` on `wp/v2/posts`, `wp/v2/users`, or terms to expose relations.

**Tasks:**
- [x] Add optional REST field (e.g. `naticore_relations` or `content_relations`) to `wp/v2/posts` (and optionally pages/CPTs)
- [x] Add same for `wp/v2/users` and `wp/v2/categories`/tags if applicable
- [x] Respect `_embed` or a query param (e.g. `?context=edit` or `?naticore_relations=1`) so it’s opt-in and performant
- [x] Document in REST API / developer docs

**Notes:** Implemented in `includes/core/class-rest-api.php`: `register_embed_fields()` hooks `rest_prepare_{post_type}`, `rest_prepare_user`, `rest_prepare_{taxonomy}`. Opt-in via `?naticore_relations=1`. Response shape: `naticore_relations` array of `{ to_id, to_type, type, title?|display_name?|name? }`.

---

## 3. Manual order for related items — P1

**Why:** Editors need to control the order of “related products” or “related posts” per post. Currently order is only by date/title/random.

**Gap:** No `relation_order` (or similar) column and no UI to reorder relations.

**Tasks:**
- [x] Add optional `relation_order` (integer) column to `wp_content_relations` (migration in `NATICORE_Database`)
- [x] Add setting "Enable manual ordering" (default: off) in General tab; when on: sortable list in meta box, save order on post save
- [x] In `get_all_relations()` and `get_related()`, when setting on: order by `relation_order ASC, created_at DESC`
- [x] Expose order in REST and in block/shortcode when using “manual” order

**Optional, off by default:** Controlled from Settings → Content Relationships → General: "Manual Order for Related Items".

**Notes:** Column via `maybe_add_relation_order_column()`. Order saved in `save_relationships()`. Cache key in `get_related` includes manual_order.

---

## 4. Classic widget — P1

**Why:** Users who don’t use blocks or Elementor have no built-in way to show related content in sidebars.

**Gap:** No `WP_Widget` for “Related content”.

**Tasks:**
- [x] Create a widget class extending `WP_Widget`: title, relation type, limit, order, optional “post ID” (default current)
- [x] Register widget on `widgets_init`
- [x] Reuse same output logic as shortcode for consistency
- [x] Document in readme

**Notes:** Widget: `includes/frontend/class-widget.php`. Registered in main plugin. Uses shortcode `render_related_posts()` for output.

---

## 5. Duplicate-post integration — P2

**Why:** When duplicating a post, relationships are usually not copied. Common user expectation.

**Gap:** No hook or helper to copy relations when a post is cloned.

**Tasks:**
- [x] Add helper `naticore_copy_relations( $from_post_id, $to_post_id, $relation_types = null )` in API
- [x] Fire hook `naticore_after_duplicate_post` after copy; document for duplicate-post plugins
- [x] Integrate with Yoast Duplicate Post (dp_duplicate_post, dp_duplicate_page) so relations copy automatically

**Notes:** Helper in `NATICORE_API::copy_relations()`. Integration in `includes/integrations/class-duplicate-post.php`. Supported: Yoast Duplicate Post (dp_duplicate_post/dp_duplicate_page), Post Duplicator (mtphr_post_duplicator_created), Copy & Delete Posts (_cdp_origin meta).

---

## 6. Block / shortcode layout options — P2

**Why:** The Related Content block is minimal (plain list). Better layout and style options improve first-run experience without custom CSS.

**Gap:** No built-in layout (list vs grid) or style controls.

**Tasks:**
- [x] Add block attributes: layout, showThumbnail, excerptLength, wrapperClass
- [x] Add shortcode attributes show_thumbnail, excerpt_length (layout and class already existed)
- [x] Output semantic markup and BEM classes; minimal CSS in shortcodes.css
- [x] Defaults (no thumbnail, no excerpt) keep output backward compatible

**Notes:** Block delegates to shortcode `render_related_posts()`. Gutenberg sidebar controls added in gutenberg.js.

---

## 7. Onboarding / guided setup — P2

**Why:** New users may not know where to start; activation notice exists but no clear “first steps”.

**Gap:** No short “Get started” or “Recommended relationship types” flow.

**Tasks:**
- [x] Extend activation notice with a link to the Get started tab (e.g. “Enable for Posts & Pages”, “Add Related Content block once”)
- [x] Add a “Recommended setup” or “Get started” checklist in Settings or Overview (e.g. enable types, add block/shortcode)
- [x] Optional: one-time “Quick setup” wizard (2–3 steps) and set a transient so it doesn’t show again

**Notes:** Get started tab in class-settings.php (`render_get_started_tab()`). Activation notice in class-admin.php.

---

## 8. Suggest related / auto-relations — P3

**Why:** “Suggest related by category/tag” or title similarity gives editors one-click suggestions and positions NCR as “smart”.

**Gap:** Auto-relations only do “on publish → link to parent as part_of”. No taxonomy-based or similarity-based suggestions.

**Tasks:**
- [x] In meta box, add “Suggest related” (or “Find related”) that suggests posts by same category/tag (or post type) with one-click add
- [ ] Optional: settings for auto-relation rules (e.g. same category, same tag) that create relations on save
- [x] Keep suggestions cheap (limit 10, tax_query only)

**Notes:** "Suggest related" button in meta box (class-admin.php) calls AJAX `naticore_suggest_related`; suggests by same category/tag or same post type; one-click add reuses existing add-relation flow. Auto-relation on save not implemented.

---

## Progress log

| Date | Feature | Outcome |
|------|---------|--------|
| 2026-02-13 | Built-in shortcodes | Added [naticore_related_posts], [naticore_related_users], [naticore_related_terms] in class-shortcodes.php |
| 2026-02-13 | REST embed for relations | Opt-in naticore_relations on wp/v2 posts, users, terms via ?naticore_relations=1 |

---

*Last updated: 2026-02-13*

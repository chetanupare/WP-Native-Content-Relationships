---
title: Feature overview
---

# Feature overview

Must-have features to stand out vs. competitors (e.g. Posts 2 Posts, MB Relationships) and meet user expectations.

## Priority summary

| Priority | Feature | Status |
| -------- | ------- | ------ |
| P0 | Built-in shortcodes | ✅ Done |
| P0 | REST embed for relations | ✅ Done |
| P1 | Manual order for related items | ✅ Done |
| P1 | Classic widget | ✅ Done |
| P2 | Duplicate-post integration | ✅ Done |
| P2 | Block/shortcode layout options | ✅ Done |
| P2 | Onboarding / guided setup | ✅ Done |
| P3 | Suggest related / auto-relations | ✅ Done |

## Highlights

### Shortcodes

- `[naticore_related_posts]`, `[naticore_related_users]`, `[naticore_related_terms]`
- Atts: `type`, `limit`, `order`, `post_id`, `layout` (list/grid), `class`
- Implemented in `includes/frontend/class-shortcodes.php`

### REST API

- Optional embed on `wp/v2/posts`, users, terms via `?naticore_relations=1`
- Response: `naticore_relations` array of `{ to_id, to_type, type, title? }`
- In `includes/core/class-rest-api.php`

### Widget

- Classic **Related content** widget (title, type, limit, order)
- Uses same output as shortcode; registered on `widgets_init`

### Duplicate-post

- Helper `ncr_copy_relations( $from, $to, $types )` and hook `naticore_after_duplicate_post`
- Integrated with Yoast Duplicate Post, Post Duplicator, Copy & Delete Posts

### Block & layouts

- Related Content block with layout, thumbnail, excerpt options
- Shortcode atts: `show_thumbnail`, `excerpt_length`, layout, `class`

### Onboarding

- Get started tab in Settings; activation notice with link to first steps

### Suggest related

- "Suggest related" in meta box: same category/tag or post type; one-click add

---

For the full task list and implementation notes, see the source doc: [MUST_HAVE_FEATURES.md](https://github.com/chetanupare/WP-Native-Content-Relationships/blob/main/docs/MUST_HAVE_FEATURES.md) in the repo.

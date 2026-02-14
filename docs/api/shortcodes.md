---
title: Shortcodes
description: naticore_related_posts, naticore_related_users, naticore_related_terms — attributes and examples.
---

# Shortcodes

Use these shortcodes in posts, pages, or widgets. When `post_id` is omitted, the **current post** is used (in the loop).

---

## [naticore_related_posts]

Shows related posts for the current (or given) post.

| Attribute | Values | Default | Description |
|----------|--------|---------|-------------|
| `type` | relation type slug | `related_to` | e.g. `related_to`, `parent_of`, `depends_on` |
| `limit` | 1–50 | `5` | Max number of posts |
| `order` | `date`, `title` | `date` | Sort order |
| `post_id` | post ID or `0` | current post | Source post; `0` = current |
| `layout` | `list`, `grid` | `list` | List or grid layout |
| `title` | text | “Related Content” | Heading above the list |
| `class` | CSS classes | — | Extra wrapper class(es) |
| `show_thumbnail` | `0`, `1` | `0` | Show post thumbnail |
| `excerpt_length` | number (words), `0` = none | `0` | Excerpt length |

**Examples:**

```
[naticore_related_posts]
[naticore_related_posts type="related_to" limit="6" layout="grid" title="You might also like"]
[naticore_related_posts type="parent_of" limit="10" order="title" show_thumbnail="1" excerpt_length="15"]
[naticore_related_posts post_id="123" type="related_to" limit="5"]
```

---

## [naticore_related_users]

Shows related users (e.g. authors, who bookmarked/favorited).

| Attribute | Values | Default | Description |
|----------|--------|---------|-------------|
| `type` | relation type slug | `authored_by` | e.g. `authored_by`, `favorite_posts`, `bookmarked_by` |
| `limit` | 1–50 | `5` | Max number of users |
| `order` | `date`, `name` | `date` | Sort order |
| `post_id` | post ID or `0` | current post | Source post; `0` = current |
| `layout` | `list`, `grid` | `list` | List or grid layout |
| `title` | text | “Related Users” | Heading above the list |
| `class` | CSS classes | — | Extra wrapper class(es) |

**Examples:**

```
[naticore_related_users]
[naticore_related_users type="authored_by" limit="5" title="Contributors"]
[naticore_related_users type="bookmarked_by" limit="10" layout="grid" title="Saved by"]
```

---

## [naticore_related_terms]

Shows related taxonomy terms for the current (or given) post.

| Attribute | Values | Default | Description |
|----------|--------|---------|-------------|
| `type` | relation type slug | `categorized_as` | e.g. `categorized_as` |
| `limit` | 1–50 | `5` | Max number of terms |
| `order` | `date`, `name` | `date` | Sort order |
| `post_id` | post ID or `0` | current post | Source post; `0` = current |
| `layout` | `list`, `grid` | `list` | List or grid layout |
| `title` | text | “Related Terms” | Heading above the list |
| `class` | CSS classes | — | Extra wrapper class(es) |

**Examples:**

```
[naticore_related_terms]
[naticore_related_terms type="categorized_as" limit="5" layout="grid" title="In these collections"]
[naticore_related_terms post_id="456" type="categorized_as" limit="10"]
```

---

## Styling

When any of these shortcodes is used, the plugin enqueues `shortcodes.css`. You can override with your own CSS by targeting:

- `.naticore-related-posts`, `.naticore-related-users`, `.naticore-related-terms`
- `.naticore-related-list`, `.naticore-related-grid`
- Optional `class` attribute for your own wrapper class

---

## Widget

The **Related Content (NCR)** widget uses the same output as the posts shortcode. Add it under **Appearance → Widgets**; options include title, relationship type, number of items, order, and optional post ID. See [Widget](/guide/widget).

---

## See also

- [Use cases](/guide/use-cases) — Examples by scenario
- [Widget](/guide/widget) — Sidebar widget
- [Quick Start](/guide/quick-start) — First shortcode in the editor
- [PHP API](/api/php-api) — Same data via `ncr_get_related()`

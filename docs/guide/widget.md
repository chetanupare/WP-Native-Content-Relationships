---
title: Related Content Widget
description: Sidebar widget to display related posts — Appearance → Widgets, options and output.
---

# Related Content Widget

The **Related Content (NCR)** widget displays related posts in sidebars or any widget area. It uses the same data and markup as the [shortcode](/api/shortcodes#naticore_related_posts); only the configuration UI is different.

---

## Where to add it

**Appearance → Widgets** (or **Appearance → Customize → Widgets** in block themes). Add the widget **Related Content (NCR)** to a sidebar or widget block.

---

## Widget options

| Option | Description |
|--------|--------------|
| **Title** | Optional widget title. Leave blank to use the shortcode default heading (“Related Content”). |
| **Relationship type** | e.g. Related To, Parent Of, Depends On. Same as shortcode `type`. |
| **Number of items** | 1–50. Same as shortcode `limit`. |
| **Order by** | Date or Title. Same as shortcode `order`. |
| **Post ID (optional)** | Leave **0** to use the current post (in the loop). Set a post ID to show relations for a specific post. |

---

## Behavior

- **Context:** On single post/page, “current post” is the one being viewed. On archives or when no post is in context, set **Post ID** explicitly or the widget may output nothing.
- **Output:** Same HTML and CSS as `[naticore_related_posts]` (list layout, same wrapper classes). Shortcode CSS is enqueued when the widget renders.
- **Empty:** If there are no related posts, the widget outputs nothing (no empty box).

---

## Example

Add “Related Content (NCR)” to the main sidebar with:

- Title: **You might also like**
- Relationship type: **Related To**
- Number of items: **5**
- Order by: **Date**
- Post ID: **0**

Every single post will show up to 5 “related to” posts in the sidebar.

---

## See also

- [Shortcodes](/api/shortcodes) — Same data via `[naticore_related_posts]`
- [Quick Start](/guide/quick-start) — First relationship and display

---
title: Troubleshooting
description: Empty results, permissions, WP_Query, REST, shortcodes — common issues and fixes.
---

# Troubleshooting

## No related posts / empty shortcode

**Check:**

1. **Relations exist** — In the post editor, open the relationship panel and confirm links are saved. Or query the table: `SELECT * FROM wp_content_relations WHERE from_id = <post_id> AND type = 'related_to';` (use your prefix).
2. **Correct type** — Shortcode `type` and PHP `relation_type` must match a registered type (e.g. `related_to`). Typos or wrong type = no results.
3. **Post ID** — Shortcode uses current post in the loop. On archive or outside the loop, pass `post_id="123"` or use PHP and `ncr_get_related( 123, 'post', 'related_to', ... )`.
4. **Status** — Shortcodes only list **published** posts. Draft/private targets are excluded from output (they may still be stored as relations).

## WP_Query with content_relation returns nothing

- **Keys:** Use `content_relation` (not `ncr_relation`). Required keys: `post_id`, `type`. Optional: `direction` (`'from'` or `'to'`).
- **Direction:** For “posts that are related TO this one” use `'direction' => 'to'`. For “posts this one points TO” use `'direction' => 'from'` (often default).
- **Post type:** Ensure `post_type` matches the related posts (e.g. `post`, `page`, or your CPT).

Example:

```php
$q = new WP_Query( [
	'post_type'        => 'post',
	'content_relation' => [
		'post_id'   => get_the_ID(),
		'type'      => 'related_to',
		'direction' => 'to',
	],
] );
```

See [WP_Query](/api/wp-query).

## Permission errors when creating relations

- **Capability:** Creating relations requires `naticore_create_relation` (or equivalent). Editors and above usually have it. If you use custom roles, grant this capability or use a filter to allow creation.
- **Filter:** `naticore_relation_is_allowed` can block a relation. Return `false` to disallow. Check custom code that uses this filter.
- **Self-relation:** Post cannot relate to itself. Same for user–user if you add such a type. The API returns an error in that case.

## REST API: 404 or “route not found”

- **Base:** NCR routes live under `/wp-json/naticore/v1/`. Ensure permalinks are not plain (Settings → Permalinks: use any non-plain structure) and that REST is not disabled by another plugin.
- **Auth:** Creating or deleting relations via REST usually requires authentication (cookie or application password). Unauthenticated GET may work for public content depending on your setup.

## REST embed (?naticore_relations=1) empty

- **Query param:** Use `?naticore_relations=1` on the **single resource** URL, e.g. `GET /wp-json/wp/v2/posts/123?naticore_relations=1`. Not on listing endpoints.
- **Response key:** Look for `naticore_relations` in the JSON response. If the post has no relations, the array is empty.

## Shortcode outputs nothing

- **post_id:** On homepage or archive there may be no “current post”. Set `post_id="123"` explicitly.
- **Type and limit:** Ensure `type` is valid and `limit` ≥ 1.
- **CSS:** Content may be there but hidden by your theme. Inspect the HTML; wrapper classes are `.naticore-related-posts`, `.naticore-related-list`, etc.

## Duplicate or wrong relations after migration

- Run the migration **once**. Running ACF (or other) migration again can duplicate links.
- Use the **Integrity** or **Orphaned** tools (if available in your version) under **Settings → Content Relationships** to find and clean orphaned or duplicate relations.

## Performance with many relations

- NCR uses an indexed table. For very large datasets (tens of thousands of relations per post), use `limit` in queries and consider caching `ncr_get_related()` results (e.g. transients) for heavy pages. See [Performance](/performance/benchmarks) and [Scaling](/performance/scaling-guide).

## Getting help

- [GitHub Issues](https://github.com/chetanupare/WP-Native-Content-Relationships/issues) — bugs and feature requests.
- [WordPress.org support forum](https://wordpress.org/support/plugin/native-content-relationships/) — general support.

Include: WordPress and PHP version, NCR version, and what you tried (shortcode/PHP/REST + expected vs actual result).

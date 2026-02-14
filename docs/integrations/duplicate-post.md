---
title: Duplicate Post
description: Copy relationships when duplicating posts — Yoast Duplicate Post, Post Duplicator, Copy & Delete Posts.
---

# Duplicate Post Integration

When you duplicate a post with a supported duplicate-post plugin, NCR can **copy all relationships** from the original to the new post so the duplicate keeps the same related content links.

---

## Supported plugins

| Plugin | Hook / detection | Behavior |
|--------|------------------|----------|
| **Yoast Duplicate Post** | `dp_duplicate_post`, `dp_duplicate_page` | Copies relations from original to new post |
| **Post Duplicator** (metaphorcreations) | `mtphr_post_duplicator_created` | Same |
| **Copy & Delete Posts** (Inisev) | `_cdp_origin` post meta on new post | When meta is added, copies from original to duplicate |

Other duplicate-post plugins can integrate by calling the helper or firing the action below.

---

## Helper: copy relations manually

```php
naticore_copy_relations( $from_post_id, $to_post_id, $relation_types = null );
```

- **$from_post_id** — Original post ID.
- **$to_post_id** — New (duplicate) post ID.
- **$relation_types** — Optional array of relation type slugs to copy; `null` = all types.

**Returns:** Array with keys `copied`, `skipped`, `errors` (counts).

**Example:**

```php
// After your plugin creates a duplicate
$result = naticore_copy_relations( $original_id, $new_id );
if ( ! empty( $result['copied'] ) ) {
	// Relations were copied
}
```

---

## Action: after copy

```php
do_action( 'naticore_after_duplicate_post', $from_post_id, $to_post_id, $result );
```

Fired after NCR (or your code) has finished copying relations. Arguments: original post ID, new post ID, and the result array (`copied`, `skipped`, `errors`). Use to log or run follow-up logic.

---

## Use case

- Duplicate a product/course/post and keep “related to”, “parent of”, etc. on the new post.
- Avoid re-linking manually after cloning content.

---

## See also

- [Hooks & Filters](/api/hooks-filters) — All NCR actions and filters
- [PHP API](/api/php-api) — Add/remove relations programmatically

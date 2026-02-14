---
title: Use Cases
description: Real-world scenarios — products & accessories, courses, related articles, favorites, WooCommerce, multilingual.
---

# Use Cases

Real-world ways to use Native Content Relationships: products and accessories, courses and lessons, related content, user favorites, and more.

---

## Products and accessories

Link WooCommerce (or custom) products to accessories, upsells, or bundles.

**In code:**

```php
// Product 100 “recommended with” products 101, 102
ncr_add_relation( 100, 'post', 101, 'post', 'related_to' );
ncr_add_relation( 100, 'post', 102, 'post', 'related_to' );
```

**In template or shortcode:**

```php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 6 ] );
foreach ( $related as $post_id ) {
	// Output product card
}
```

**Shortcode (any post type):**

```
[naticore_related_posts type="related_to" limit="6" layout="grid" title="You might also like" show_thumbnail="1"]
```

Optional: use the [WooCommerce integration](/integrations/woocommerce) to sync or mirror product relationships.

---

## Courses and lessons

Model a course as a parent post and lessons as children with `parent_of` / child relationship.

**Register a type (once, e.g. in a mu-plugin or theme):**

```php
ncr_register_relation_type( [
	'slug'         => 'lesson_of',
	'label'        => 'Lesson Of',
	'from_type'    => 'post',
	'to_type'      => 'post',
	'bidirectional' => false,
] );
```

**Link course → lessons:**

```php
$course_id = 200;
$lesson_ids = [ 201, 202, 203 ];
foreach ( $lesson_ids as $lid ) {
	ncr_add_relation( $course_id, 'post', $lid, 'post', 'lesson_of' );
}
```

**Query lessons for a course (WP_Query):**

```php
$q = new WP_Query( [
	'post_type'        => 'lesson',
	'content_relation' => [
		'post_id' => $course_id,
		'type'    => 'lesson_of',
		'direction' => 'to', // lessons that are “lesson_of” this course
	],
] );
```

---

## Related articles and content

Link posts with `related_to` (bidirectional) for “Related posts” sections.

**PHP:**

```php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 5, 'orderby' => 'created', 'order' => 'DESC' ] );
```

**Shortcode (in post content or block):**

```
[naticore_related_posts type="related_to" limit="5" order="date" title="Related articles" show_thumbnail="1" excerpt_length="20"]
```

**Gutenberg:** Use the **Related Content** block and choose the “Related To” relationship type.

---

## User favorites and bookmarks

Let users mark posts as favorites or bookmarks. Store as user → post relationships.

**Built-in types:** `favorite_posts` (user → post), `bookmarked_by` (same, different label). Use the **User** relationship UI in the post editor or user profile to link users to posts.

**Display “Posts this user favorited” (author context):**

```php
$user_id = get_the_author_meta( 'ID' );
$favorites = ncr_get_related( $user_id, 'user', 'favorite_posts', [ 'limit' => 10 ] );
```

**Shortcode for “Related users” (e.g. “Who bookmarked this”):**

```
[naticore_related_users type="bookmarked_by" limit="10" title="Saved by" layout="grid"]
```

---

## Categories and curated collections (post ↔ term)

Link posts to taxonomy terms for featured categories or curated collections beyond default taxonomies.

**Built-in type:** `categorized_as` (post → term).

```php
ncr_add_relation( $post_id, 'post', $term_id, 'term', 'categorized_as' );
$terms = ncr_get_related( $post_id, 'post', 'categorized_as', [ 'limit' => 5 ] );
```

**Shortcode:**

```
[naticore_related_terms type="categorized_as" limit="5" title="In these collections" layout="grid"]
```

---

## Multilingual (WPML / Polylang)

Relationship data can be mirrored across translations so that when you relate a post in one language, the linked translation is related too. See [Multilingual integration](/integrations/multilingual) for setup and behavior.

---

## Headless and REST

For headless WordPress, use the REST API to create/read/delete relations and to embed relations in core resources:

- [REST API](/api/rest-api) — `GET/POST/DELETE` under `/wp-json/naticore/v1/`
- Embed: `GET /wp-json/wp/v2/posts/<id>?naticore_relations=1` to get a `naticore_relations` array in the response

---

## Next steps

- [Quick Start](/guide/quick-start) — First relationship in the UI
- [Relationships](/guide/relationships) — Create and query from code
- [PHP API](/api/php-api) — Full function reference
- [Shortcodes](/api/shortcodes) — All shortcode attributes

---
title: Developer documentation
---

# Developer documentation

Complete guide to integrating Native Content Relationships into your WordPress projects: PHP API, hooks, REST API, WP-CLI, and integrations.

## Installation

**From WordPress.org**

1. Go to **Plugins → Add New**, search for "Native Content Relationships".
2. Click **Install Now**, then **Activate**.

**From GitHub**

1. Clone or download [WP-Native-Content-Relationships](https://github.com/chetanupare/WP-Native-Content-Relationships).
2. Place the plugin folder in `wp-content/plugins/` and activate in the admin.

---

## Basic usage

### Creating relationships

Use the public API (prefixed `ncr_`) or the legacy `wp_*` wrappers:

```php
// Post-to-post (recommended: ncr_*)
$relation_id = ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// Legacy wrapper
$relation_id = wp_add_relation( 123, 456, 'related_to' );

// User-to-post (e.g. favorites)
ncr_add_relation( $user_id, 'user', $post_id, 'post', 'favorite_posts' );

// Post-to-term
ncr_add_relation( $post_id, 'post', $term_id, 'term', 'categorized_as' );
```

### Querying related content

```php
// Related posts (outgoing from post 123)
$related = ncr_get_related( 123, 'post', 'related_to', [ 'limit' => 10 ] );

// Legacy
$related_posts = wp_get_related( 123, 'related_to' );

// User's related posts (e.g. favorites)
$favorites = ncr_get_related( $user_id, 'user', 'favorite_posts', [ 'limit' => 5 ] );

// With WP_Query
$q = new WP_Query([
    'content_relation' => [
        'post_id'   => get_the_ID(),
        'type'      => 'related_to',
        'direction' => 'outgoing',
    ],
]);
```

### Removing relationships

```php
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );

// Legacy: remove one or all of a type
wp_remove_relation( 123, 456, 'related_to' );
wp_remove_relation( 123, null, 'related_to' ); // all from 123
```

---

## Relationship types

### Built-in types

- **related_to** — Bidirectional post-to-post.
- **parent_of** — Unidirectional parent–child.
- **references** — Unidirectional reference.
- **depends_on** — Unidirectional dependency.

### Registering custom types

Register on `init` (before the plugin locks the registry at `init:20`):

```php
add_action( 'init', function() {
    ncr_register_relation_type( 'sponsored_by', [
        'label'              => 'Sponsored By',
        'from_object_types'  => [ 'post' ],
        'to_object_types'    => [ 'post', 'user' ],
        'direction'          => 'from_to', // or 'to_from', 'bidirectional'
        'max_connections'    => 1,         // optional
    ] );
}, 5 );
```

---

## API reference

### ncr_add_relation()

Creates a relationship between two objects.

```php
ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

| Parameter        | Type   | Description                          |
|------------------|--------|--------------------------------------|
| `$from_id`       | int    | Source object ID                     |
| `$from_type`     | string | `'post'`, `'user'`, or `'term'`     |
| `$to_id`         | int    | Target object ID                     |
| `$to_type`       | string | `'post'`, `'user'`, or `'term'`      |
| `$relation_type` | string | Registered relation type slug        |

**Returns:** `int` relation ID on success, or `WP_Error` on failure.

### ncr_get_related()

Retrieves related objects.

```php
ncr_get_related( $from_id, $from_type, $relation_type, $args = [] );
```

| Parameter        | Type   | Description                          |
|------------------|--------|--------------------------------------|
| `$from_id`       | int    | Source object ID                     |
| `$from_type`     | string | `'post'`, `'user'`, or `'term'`       |
| `$relation_type` | string | Relation type slug                   |
| `$args`          | array  | `limit`, `orderby`, `order`, `direction` |

**Returns:** Array of related IDs or objects (depending on args), or `WP_Error`.

### ncr_remove_relation()

Removes one or more relationships.

```php
ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

Use `$to_id = null` to remove all relations of that type from `$from_id`.

**Returns:** `true` on success, `WP_Error` on failure.

### Legacy wrappers

For backward compatibility the plugin also exposes:

- `wp_add_relation( $from_id, $to_id, $type, $direction, $to_type )`
- `wp_get_related( $post_id, $type, $args, $to_type )`
- `wp_remove_relation( $from_id, $to_id, $type, $to_type )`
- `wp_get_related_users()`, `wp_get_related_terms()`, `wp_get_related_products()` (when WooCommerce active)

Prefer `ncr_*` for new code.

---

## Hooks and filters

### Actions

| Hook                       | Arguments                          | When                          |
|----------------------------|------------------------------------|-------------------------------|
| `ncr_relation_added`       | `$relation_id`, `$from_id`, `$to_id`, `$type` | After a relation is created   |
| `ncr_relation_removed`    | `$from_id`, `$to_id`, `$type`      | After a relation is removed   |
| `naticore_relation_added`  | (legacy)                           | Same as above                 |
| `naticore_relation_removed`| (legacy)                           | Same as above                 |

### Filters

| Filter                         | Arguments              | Purpose                          |
|--------------------------------|------------------------|----------------------------------|
| `ncr_get_related_args`         | `$args`, `$from_id`, `$type` | Modify query args for get_related |
| `ncr_max_relationships`        | `$max`, `$from_id`, `$type`  | Override max_connections         |
| `ncr_skip_relationship_query` | `$skip`, `$query`     | Skip plugin JOIN/WHERE in WP_Query (e.g. for future core API) |
| `naticore_content_relations_allowed` | (legacy)        | Allow/disallow relation queries  |
| `naticore_get_related_args`    | (legacy)               | Same as ncr_get_related_args     |

---

## REST API

### Optional embed on core endpoints

Request relations on core post/user/term endpoints with:

```
GET /wp-json/wp/v2/posts/123?naticore_relations=1
```

Response includes `naticore_relations`: array of `{ to_id, to_type, type, title? }`.

### Plugin namespace (if enabled)

- **GET** `/wp-json/naticore/v1/post/{id}` — Relationships for a post.
- **POST** `/wp-json/naticore/v1/relationships` — Create (body: `from_id`, `to_id`, `type`).
- **DELETE** `/wp-json/naticore/v1/relationships` — Remove (body: `from_id`, `to_id`, `type`).

Authentication and exact routes may vary; check the plugin’s REST registration.

---

## WP-CLI

```bash
# List relations for a post
wp content-relations list --post=123 --type=related_to --format=json

# Add relation
wp content-relations add 123 456 --type=related_to

# Remove relation
wp content-relations remove 123 456 --type=related_to

# Count
wp content-relations count --post=123

# Integrity check/fix
wp content-relations check [--fix] [--verbose]

# Schema (relation types)
wp content-relations schema [--format=json]
```

---

## Integrations

### WooCommerce

- Product relationships (e.g. related products, upsells) can use the same API; plugin adds types and helpers.
- `wp_get_related_products( $product_id, $type, $args )` when WooCommerce integration is enabled.

### WPML / Polylang

- Relationship mirroring across languages is supported when the integration is enabled; no extra code required for basic sync.

### Gutenberg

- **Related Content** block: `naticore/related-posts` with block attributes for type, limit, layout.

### Elementor

- Dynamic tags: Related Posts, Related Users, Related Terms (output format and direction configurable).

---

## Shortcodes

| Shortcode                    | Purpose                |
|-----------------------------|------------------------|
| `[naticore_related_posts]`  | Related posts list     |
| `[naticore_related_users]`  | Related users list     |
| `[naticore_related_terms]`  | Related terms list     |

Common attributes: `type`, `limit`, `order`, `post_id`, `layout` (list/grid), `class`.

---

For stability and versioning, see the [Stability & Backward Compatibility](https://github.com/chetanupare/WP-Native-Content-Relationships#readme) section in the readme.

---
title: PHP API
description: ncr_add_relation, ncr_get_related, ncr_remove_relation, and custom types. Full reference and examples.
---

# PHP API

The primary API uses the `ncr_` prefix. Legacy `wp_*` wrappers remain for backward compatibility.

## Quick reference

| Function | Purpose |
|----------|---------|
| `ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type )` | Create one relationship |
| `ncr_get_related( $from_id, $from_type, $relation_type, $args = [] )` | Get related IDs or objects |
| `ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $relation_type )` | Remove one or all (use `$to_id = null` for all) |
| `ncr_register_relation_type( $args )` | Register a custom relation type |
| `ncr_get_registered_relation_types()` | List registered types |

Object types: `'post'`, `'user'`, `'term'`. See [Relationships](/guide/relationships) for copy-paste examples.

---

## ncr_add_relation()

Creates a relationship between two objects.

```php
ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

| Parameter        | Type   | Description                    |
|------------------|--------|--------------------------------|
| `$from_id`       | int    | Source object ID                |
| `$from_type`     | string | `'post'`, `'user'`, or `'term'` |
| `$to_id`         | int    | Target object ID                |
| `$to_type`       | string | `'post'`, `'user'`, or `'term'` |
| `$relation_type` | string | Registered relation type slug   |

**Returns:** `int` relation ID on success, `WP_Error` on failure.

**Example:**

```php
// Link post 123 to post 456 as "related_to"
$relation_id = ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );
if ( is_wp_error( $relation_id ) ) {
	// Handle error
}
```

## ncr_get_related()

Retrieves related objects.

```php
ncr_get_related( $from_id, $from_type, $relation_type, $args = [] );
```

| Parameter        | Type   | Description                    |
|------------------|--------|--------------------------------|
| `$from_id`       | int    | Source object ID                |
| `$from_type`     | string | `'post'`, `'user'`, or `'term'` |
| `$relation_type` | string | Relation type slug              |
| `$args`          | array  | `limit`, `orderby`, `order`, `direction` |

**Returns:** Array of related IDs or objects, or `WP_Error`.

**Example:**

```php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 5, 'orderby' => 'created', 'order' => 'DESC' ] );
foreach ( (array) $related as $post_id ) {
	echo get_the_title( $post_id );
}
```

## ncr_remove_relation()

Removes one or more relationships.

```php
ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

Use `$to_id = null` to remove all relations of that type from `$from_id`.

**Returns:** `true` on success, `WP_Error` on failure.

**Example:**

```php
// Remove one relation
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );

// Remove all "related_to" from post 123
ncr_remove_relation( 123, 'post', null, 'post', 'related_to' );
```

## Legacy wrappers

- `wp_add_relation( $from_id, $to_id, $type, $direction, $to_type )`
- `wp_get_related( $post_id, $type, $args, $to_type )`
- `wp_remove_relation( $from_id, $to_id, $type, $to_type )`
- `wp_get_related_users()`, `wp_get_related_terms()`, `wp_get_related_products()` (WooCommerce)

Prefer `ncr_*` for new code.

---

## See also

- [Relationships](/guide/relationships) — Create, query, remove from code with examples
- [Fluent API](/api/fluent-api) — Chainable `naticore()` wrapper
- [Shortcodes](/api/shortcodes) — `[naticore_related_posts]`, `[naticore_related_users]`, `[naticore_related_terms]`
- [WP_Query](/api/wp-query) — Query by relationship in the loop
- [Hooks & Filters](/api/hooks-filters) — `ncr_relation_added`, `naticore_relation_is_allowed`

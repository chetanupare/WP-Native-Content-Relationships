---
title: Fluent API
description: Chainable naticore() API — from(), to(), type(), create(), get(), exists(), remove().
---

# Fluent API

A chainable, IDE-friendly wrapper around the relationship API. Entry point: **`naticore()`**.

Best for post-to-post relations in theme or plugin code where you prefer a fluent style over function calls.

---

## Entry point

```php
naticore();
```

Returns a `NATICORE_Fluent_API` instance. Chain methods and end with `create()`, `get()`, `exists()`, or `remove()`.

---

## Methods

| Method | Arguments | Returns | Purpose |
|--------|------------|---------|---------|
| `from( $post_id )` | int | `$this` | Set source post ID |
| `to( $post_id )` | int | `$this` | Set target post ID |
| `type( $type )` | string | `$this` | Set relationship type (default `related_to`) |
| `direction( $direction )` | string | `$this` | Set direction (optional) |
| `create()` | — | `int\|WP_Error` | Create the relationship |
| `get( $args = [] )` | array | array | Get related posts (uses `from` + `type`) |
| `exists()` | — | `bool\|WP_Error` | Check if relationship exists |
| `remove()` | — | `bool\|WP_Error` | Remove the relationship |

After `create()`, `get()`, `exists()`, or `remove()`, internal state is reset.

---

## Examples

**Create a relationship:**

```php
$result = naticore()
	->from( 123 )
	->to( 456 )
	->type( 'related_to' )
	->create();

if ( is_wp_error( $result ) ) {
	// Handle error
} else {
	// $result is the relation ID
}
```

**Get related posts:**

```php
$related = naticore()
	->from( get_the_ID() )
	->type( 'related_to' )
	->get( [ 'limit' => 10 ] );
// $related is array of post IDs or objects (same as ncr_get_related)
```

**Check if related:**

```php
$exists = naticore()
	->from( 123 )
	->to( 456 )
	->type( 'related_to' )
	->exists();
// true or false
```

**Remove relationship:**

```php
naticore()
	->from( 123 )
	->to( 456 )
	->type( 'related_to' )
	->remove();
```

---

## Scope

The fluent API uses the same backend as `ncr_add_relation` / `ncr_get_related` / etc. It is currently oriented to **post-to-post** relations (from_id and to_id as posts). For user or term relations, use the [PHP API](/api/php-api) directly.

---

## See also

- [PHP API](/api/php-api) — Full function reference
- [Relationships](/guide/relationships) — Create and query from code

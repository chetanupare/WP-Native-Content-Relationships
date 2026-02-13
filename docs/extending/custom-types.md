---
title: Custom Relation Types
---

# Custom Relation Types

Register your own relationship types with `ncr_register_relation_type()`.

## Registration

```php
add_action( 'init', function() {
    ncr_register_relation_type( [
        'name'            => 'recommended_by',
        'label'           => __( 'Recommended by', 'your-textdomain' ),
        'from'            => 'post',
        'to'              => 'post',
        'bidirectional'   => false,
        'max_connections' => 20,
    ] );
}, 20 );
```

## Arguments

| Key | Type | Description |
|-----|------|--------------|
| `name` | string | Unique slug (e.g. `recommended_by`). |
| `label` | string | Display label in admin. |
| `from` | string | Source object type: `post`, `user`, or `term`. |
| `to` | string | Target object type: `post`, `user`, or `term`. |
| `bidirectional` | bool | Whether the relationship is two-way. |
| `max_connections` | int | Max relations of this type from a single source. |

**Returns:** `true` on success, `WP_Error` on failure (e.g. invalid types or duplicate name).

## Listing types

```php
$types = ncr_get_registered_relation_types();
```

Use the same type slug in [ncr_add_relation](/api/php-api#ncr_add_relation), [ncr_get_related](/api/php-api#ncr_get_related), and [WP_Query](/api/wp-query).

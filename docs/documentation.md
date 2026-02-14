---
title: Developer Documentation
description: Complete guide to integrating Native Content Relationships — installation, basic usage, API reference, hooks, REST, WP-CLI, and integrations.
---

# Developer Documentation

Complete guide to integrating **Native Content Relationships** into your WordPress projects. Create, manage, and query content relationships with the PHP API, WP_Query, REST, and WP-CLI.

## Installation {#installation}

### From WordPress.org

1. Go to **Plugins → Add New** in your WordPress admin.
2. Search for **Native Content Relationships**.
3. Click **Install Now**, then **Activate**.

### From GitHub

1. Download the plugin from the [GitHub repository](https://github.com/chetanupare/WP-Native-Content-Relationships).
2. Upload the `native-content-relationships` folder to `/wp-content/plugins/`.
3. Activate the plugin in **Plugins**.

## Basic Usage {#basic-usage}

### Creating Relationships

```php
// Post to post
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );

// User to post (e.g. favorites)
ncr_add_relation( get_current_user_id(), 'user', $post_id, 'post', 'favorite_posts' );

// Post to term
ncr_add_relation( $post_id, 'post', $term_id, 'term', 'categorized_as' );
```

### Querying Related Content

```php
// Get related post IDs
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 10 ] );

// With WP_Query
$q = new WP_Query( [
	'content_relation' => [
		'post_id'   => get_the_ID(),
		'type'      => 'related_to',
		'direction' => 'outgoing',
	],
] );
```

### Removing Relationships

```php
// Remove one relation
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );

// Remove all relations of a type from a post
ncr_remove_relation( 123, 'post', null, 'post', 'related_to' );
```

## Relationship Types {#relationship-types}

### Built-in types

| Type | Direction | Use |
|------|-----------|-----|
| **related_to** | Bidirectional | Related posts, related products |
| **parent_of** | Unidirectional | Parent–child (e.g. course → lessons) |
| **references** | Unidirectional | Citations, references |
| **depends_on** | Unidirectional | Dependencies |

### Custom types

Register custom types with `ncr_register_relation_type()`. See [Custom types](/extending/custom-types).

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

## API Reference {#api-reference}

### ncr_add_relation()

Creates a relationship between two objects.

```php
ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

**Returns:** `int` relation ID on success, `WP_Error` on failure.

### ncr_get_related()

Retrieves related objects.

```php
ncr_get_related( $from_id, $from_type, $relation_type, $args = [] );
```

**Returns:** Array of related IDs or objects, or `WP_Error`.

### ncr_remove_relation()

Removes one or all relationships of a type.

```php
ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $relation_type );
```

Use `$to_id = null` to remove all relations of that type from the source.

**Returns:** `true` on success, `WP_Error` on failure.

→ Full details: [PHP API](/api/php-api) · [WP_Query](/api/wp-query)

## Hooks & Filters {#hooks-filters}

### Actions

| Hook | When |
|------|------|
| `ncr_relation_added` | After a relation is created |
| `ncr_relation_removed` | After a relation is removed |

```php
do_action( 'ncr_relation_added', $relation_id, $from_id, $to_id, $type );
do_action( 'ncr_relation_removed', $from_id, $to_id, $type );
```

### Filters

| Filter | Purpose |
|--------|---------|
| `ncr_get_related_args` | Modify query args for `ncr_get_related()` |
| `ncr_max_relationships` | Override max_connections per type |
| `ncr_skip_relationship_query` | Skip plugin JOIN/WHERE in WP_Query |

→ [Hooks & Filters](/api/hooks-filters)

## REST API {#rest-api}

### Embed on core endpoints

```
GET /wp-json/wp/v2/posts/123?naticore_relations=1
```

Response includes `naticore_relations` with related items.

### Plugin namespace

- **GET** `/wp-json/naticore/v1/post/{id}` — Relationships for a post
- **POST** `/wp-json/naticore/v1/relationships` — Create (body: `from_id`, `to_id`, `type`)
- **DELETE** `/wp-json/naticore/v1/relationships` — Remove (body: `from_id`, `to_id`, `type`)

→ [REST API](/api/rest-api)

## WP-CLI {#wp-cli}

```bash
# List relations
wp content-relations list --post=123 --type=related_to --format=json

# Add / remove
wp content-relations add 123 456 --type=related_to
wp content-relations remove 123 456 --type=related_to

# Count
wp content-relations count --post=123
```

→ [WP-CLI](/api/wp-cli)

## Integrations {#integrations}

- **[WooCommerce](/integrations/woocommerce)** — Related products, upsells, cross-sells
- **[Elementor](/integrations/elementor)** — Widgets and dynamic tags
- **[Gutenberg](/integrations/gutenberg)** — Related Content block
- **[Multilingual](/integrations/multilingual)** — WPML / Polylang

---

[Quick Start](/getting-started/quick-start) · [Snippets](/getting-started/snippets) · [Migration](/migration/from-acf)

---
title: Developer Documentation
description: Complete guide to integrating Native Content Relationships — API reference, relationship types, code examples, and integrations.
---

# Developer Documentation

Complete guide to integrating **Native Content Relationships** into your WordPress projects. Create, manage, and query content relationships with the PHP API, WP_Query, REST, and WP-CLI.

## Essentials

### Relationship Types

Relationship types define the kind of link between two objects (e.g. "related to", "parent of").

| Type | Direction | Use |
|------|-----------|-----|
| **related_to** | Bidirectional | Related posts, related products |
| **parent_of** | Unidirectional | Parent–child (e.g. course → lessons) |
| **references** | Unidirectional | Citations, references |
| **depends_on** | Unidirectional | Dependencies |

→ [Relationship Types](/core-concepts/relationship-types)

### Creating Relationships

```php
ncr_add_relation( 123, 'post', 456, 'post', 'related_to' );
ncr_add_relation( get_current_user_id(), 'user', $post_id, 'post', 'favorite_posts' );
```

### Querying Related Content

```php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 10 ] );
```

With WP_Query:

```php
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
ncr_remove_relation( 123, 'post', 456, 'post', 'related_to' );
ncr_remove_relation( 123, 'post', null, 'post', 'related_to' ); // remove all of type
```

## API Reference

| Function | Purpose |
|----------|---------|
| `ncr_add_relation()` | Create one relationship |
| `ncr_get_related()` | Get related IDs or objects |
| `ncr_remove_relation()` | Remove one or all relations |
| `ncr_register_relation_type()` | Register a custom type |

→ [PHP API](/api/php-api) · [WP_Query](/api/wp-query) · [REST API](/api/rest-api) · [WP-CLI](/api/wp-cli) · [Hooks & Filters](/api/hooks-filters)

## Integrations

- [WooCommerce](/integrations/woocommerce) — related products, upsells, cross-sells  
- [Elementor](/integrations/elementor) — widgets and dynamic tags  
- [Gutenberg](/integrations/gutenberg) — Related Content block  
- [Multilingual](/integrations/multilingual) — WPML / Polylang

## Next Steps

- [Quick Start](/getting-started/quick-start) — get your first relationship working  
- [Snippets](/getting-started/snippets) — copy-paste PHP, shortcodes, CLI  
- [Custom types](/extending/custom-types) — register your own relation types  
- [Migration](/migration/from-acf) — move from ACF, meta, or P2P

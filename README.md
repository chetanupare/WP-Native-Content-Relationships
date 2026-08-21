# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

> A native, scalable relationship layer for WordPress that supports structured relationships between posts, users, and terms. Escape technical debt and meta-based performance walls.

---

## Why this plugin exists (Escape Meta-Data Technical Debt)

WordPress has no first-class way to model real relationships between content items.  
Most sites rely on post meta or taxonomies, which break down as content and queries grow. **If you've outgrown ACF relationship fields or post meta arrays, Native Content Relationships provides a clean, predictable, and highly performant foundation.**

---

## Core capabilities

| Capability | Description |
|---------|-------------|
| Relationship types | Semantic, validated relationship definitions |
| Direction | One-way or bidirectional |
| Data storage | Dedicated indexed database table |
| Querying | WP_Query, REST API, WP-CLI |
| Scope | Posts, Users, Terms |
| Compatibility | Multilingual, headless, WooCommerce |
| Headless readiness | Embed relationships directly in REST API responses |

---

## Architecture & Performance Advantage

> Built for enterprise scale, not shortcuts.

- **No post meta or taxonomy abuse**: Uses a dedicated, highly optimized relational table.
- **Index Optimization**: Built with composite covering indexes (`type_lookup`) and a smart query planner.
- **Micro-Optimized Validations**: SQL-native validations ensure sub-2ms P95 latency even at 1M+ rows.
- **Performance Diagnostics**: Ships with a powerful CLI diagnostic utility to check and fix integrity without UI bloat.
- **Object Cache Integration**: Full compatibility with object cache (group isolation, relationship cache invalidation, and bulk priming strategies).

---

## Developer API & Advanced Features

The plugin is designed to be an infrastructure-grade modeling layer:

- **Relationship Type Registration API**: Register semantic types programmatically and enforce directional logic.
- **Relationship Constraints**: Define modeling rules like 1:1, 1:N, M:N, enforce max counts per type, and strictly prevent duplicates.
- **Advanced Query Arguments**: Use `content_relation` in `WP_Query` with deep support for multiple types, excluded types, `OR`/`AND` relations, and min/max counts. Depth querying planned for future graph potential.
- **Bulk Operations API**: Performance-optimized endpoints and WP-CLI commands for bulk attach, bulk detach, and data import operations.
- **Graph Utility Layer (Minimal)**: CLI and API tools to retrieve relationship counts, identify the most connected posts, or pinpoint orphaned items.
- **Lightweight Relationship Metadata**: Add optional context to relationships (e.g., *Weight*, *Order*, *Role*, *Timestamp* overrides) cleanly without altering core architecture.

---

## Headless & REST Readiness

Native Content Relationships is built for headless and decoupled applications:

- **Lazy Relationship Loading in REST**: Simply append `?naticore_relations=1` or `?_embed=relations` to core WordPress API endpoints to lazy-load and embed relationships inline without additional round-trips.

---

## ACF Migration

If you are using Advanced Custom Fields relationship fields, you can seamlessly migrate to Native Content Relationships to unlock these performance advantages immediately. 

A dedicated one-time migration guide and script is provided in the [docs/migration/from-acf.md](docs/migration/from-acf.md) file to quickly transition `meta`-based relationships to the dedicated relational table.

---

## Supported relationships

| From | To |
|----|----|
| Post | Post |
| Post | User |
| Post | Term |
| User | Post |
| Term | Post |

---

## Common use cases

### Content modeling
- Products → Accessories
- Courses → Lessons
- Articles → Related content

### User interactions
- Favorite posts
- Bookmarks
- Multiple authors or contributors

### Taxonomy extensions
- Featured categories
- Curated collections
- Semantic groupings beyond default taxonomies

---

## Admin experience

- Relationship management in post editor
- Relationship management in user profiles
- Relationship management in term editors
- AJAX-powered search for large datasets
- UI aligned with WordPress core patterns

---

## Integrations

| Area | Support |
|----|--------|
| WooCommerce | Product relationships |
| Multilingual | WPML, Polylang |
| Page builders | Gutenberg, Elementor |
| ACF | One-time relationship migration |

---

## Installation

### WordPress.org
1. Plugins → Add New
2. Search **Native Content Relationships**
3. Install and activate

### GitHub
```bash
cd wp-content/plugins
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

---

## Quick start

```php
// Add a relationship
wp_add_relation( $from_id, $to_id, 'related_to' );

// Get related items
$related = wp_get_related( $from_id, 'related_to' );

// Check relationship
if ( wp_is_related( $from_id, $to_id, 'related_to' ) ) {
    // Do something
}
```

---

## Query integration

```php
$query = new WP_Query( array(
    'post_type' => 'post',
    'content_relation' => array(
        'post_id'   => 123,
        'type'      => 'related_to',
        'direction' => 'outgoing',
    ),
) );
```

---

## REST & CLI access

**REST API**
```
/wp-json/naticore/v1/
```

**WP-CLI**
```bash
wp naticore list --post=123
wp naticore add --from=123 --to=456 --type=related_to
wp naticore remove --from=123 --to=456 --type=related_to
```

---

## Comparison

| Feature | Native Content Relationships | Posts 2 Posts | MB Relationships |
|------|------------------------------|---------------|------------------|
| Posts ↔ Posts | Yes | Yes | Yes |
| Posts ↔ Users | Yes | No | No |
| Posts ↔ Terms | Yes | No | No |
| Semantic types | Yes | No | No |
| REST API | Yes | No | Yes |
| Active maintenance | Yes | No | Yes |

---

## Contributing

Contributions are welcome.

1. Fork the repository  
2. Create a feature branch  
3. Add tests where applicable  
4. Submit a Pull Request  

Repository:  
https://github.com/chetanupare/WP-Native-Content-Relationships

---

## License

GPLv2 or later

---

## Links

- WordPress.org  
  https://wordpress.org/plugins/native-content-relationships/

- GitHub  
  https://github.com/chetanupare/WP-Native-Content-Relationships

- Issues  
  https://github.com/chetanupare/WP-Native-Content-Relationships/issues

---

If this plugin helps your project, consider starring the repository.

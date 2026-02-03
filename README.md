# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

> A native, scalable relationship layer for WordPress that supports structured relationships between posts, users, and terms.

---

## Why this plugin exists

WordPress has no first-class way to model real relationships between content items.  
Most sites rely on post meta or taxonomies, which break down as content and queries grow.

**Native Content Relationships** provides a clean, predictable foundation for relationship-driven WordPress sites.

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

## Architecture & performance

> Built for scale, not shortcuts.

- No post meta or taxonomy abuse
- Indexed relational storage
- Cache-friendly queries
- Safe for shared hosting
- Designed for large, multilingual sites

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

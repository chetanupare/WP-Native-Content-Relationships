# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

A native, scalable relationship layer for WordPress that supports structured relationships between posts, users, and terms.

---

## Overview

WordPress does not provide a first-class way to model real relationships between content items.  
Most implementations rely on post meta or taxonomies, which become difficult to query, scale, and maintain over time.

Native Content Relationships introduces a structured relationship system built on WordPress core APIs and a dedicated indexed database table. It is designed to be reliable, predictable, and suitable for long-term use across multiple projects.

---

## What This Plugin Solves

- Many-to-many relationships between content entities
- Clean and predictable querying without meta hacks
- Long-term maintainability and data portability
- Support for modern, multilingual, and headless WordPress setups

---

## Features

- Relationships between posts, users, and terms
- One-way or bidirectional relationships
- Semantic relationship types with validation
- Dedicated indexed database table for performance
- WP_Query integration
- REST API endpoints
- WP-CLI commands
- Modern admin UI with AJAX search
- Multilingual support (WPML, Polylang)
- Optional WooCommerce integration
- Editor- and theme-agnostic design

---

## Supported Relationship Types

- Post ↔ Post
- Post ↔ User
- Post ↔ Term
- User ↔ Post
- Term ↔ Post

---

## Common Use Cases

### Posts
- Products → Accessories
- Courses → Lessons
- Articles → Related content

### Users
- Favorite posts
- Bookmarked content
- Multiple authors or contributors

### Terms
- Featured categories
- Curated collections
- Semantic grouping beyond default taxonomies

---

## Admin Interface

- Manage relationships directly from the post editor
- Manage related content from user profile screens
- Manage related posts from term edit screens
- AJAX-powered search for posts, users, and terms
- UI aligned with WordPress admin standards

---

## Architecture and Performance

- Dedicated indexed database table
- No reliance on post meta or taxonomy abuse
- Optimized for large datasets and multilingual sites
- Cache-friendly and safe for shared hosting
- Designed to scale to millions of relationships

---

## Integrations

- WooCommerce (product relationships)
- WPML / Polylang (relationship mirroring)
- Elementor (dynamic content support)
- Gutenberg (related content block)
- Advanced Custom Fields (one-time migration tool)

---

## Compatibility

- WordPress 5.0+
- PHP 7.4+
- All themes
- All custom post types
- All custom taxonomies

---

## Installation

### From WordPress.org

1. Go to Plugins → Add New
2. Search for “Native Content Relationships”
3. Install and activate

### From GitHub

```bash
cd wp-content/plugins
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

Activate the plugin from the WordPress admin.

---

## Quick Start

```php
// Add a relationship
wp_add_relation( $from_id, $to_id, 'related_to' );

// Get related items
$related = wp_get_related( $from_id, 'related_to' );

// Check if related
if ( wp_is_related( $from_id, $to_id, 'related_to' ) ) {
    // Do something
}
```

---

## User Relationships

```php
// User favorites a post
wp_add_relation( $user_id, $post_id, 'favorite_posts', null, 'post' );

// Get user's favorite posts
$favorites = wp_get_related_users( $user_id, 'favorite_posts' );

// Get users who favorited a post
$users = wp_get_related( $post_id, 'favorite_posts', array(), 'user' );
```

---

## Term Relationships

```php
// Relate post to term
wp_add_relation( $post_id, $term_id, 'categorized_as', null, 'term' );

// Get related terms
$terms = wp_get_related_terms( $post_id, 'categorized_as' );

// Get related posts
$posts = wp_get_term_related_posts( $term_id, 'categorized_as' );
```

---

## WP_Query Integration

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

## REST API

Endpoints are available under:

```
/wp-json/naticore/v1/
```

- Fetch relationships
- Create relationships
- Delete relationships

---

## WP-CLI

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
| Semantic Types | Yes | No | No |
| REST API | Yes | No | Yes |
| Active Development | Yes | No | Yes |

---

## Contributing

Contributions are welcome.

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests where applicable
5. Submit a Pull Request

Repository:  
https://github.com/chetanupare/WP-Native-Content-Relationships

---

## License

This project is licensed under the GPLv2 or later.

---

## Links

- WordPress.org Plugin Page  
  https://wordpress.org/plugins/native-content-relationships/

- GitHub Repository  
  https://github.com/chetanupare/WP-Native-Content-Relationships

- Issue Tracker  
  https://github.com/chetanupare/WP-Native-Content-Relationships/issues

---

If you find this plugin useful, consider starring the repository.

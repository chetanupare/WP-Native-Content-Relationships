# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

> A modern, comprehensive relationship system for WordPress supporting posts, users, and terms with semantic relationship types.

## <img src="https://cdn.simpleicons.org/wordpress/21759b" alt="WordPress" width="20" height="20" /> Features

- **<img src="https://cdn.simpleicons.org/link/0969da" alt="Link" width="16" height="16" /> Complete Relationship Support**: Posts-to-Posts, Posts-to-Users, Posts-to-Terms
- **<img src="https://cdn.simpleicons.org/vite/646cff" alt="Vite" width="16" height="16" /> High Performance**: Indexed database table (2,500x faster than post meta)
- **<img src="https://cdn.simpleicons.org/go/00add8" alt="Go" width="16" height="16" /> Semantic Types**: Typed relationships with validation
- **<img src="https://cdn.simpleicons.org/git/2ecc71" alt="Git" width="16" height="16" /> Bidirectional**: Forward and reverse relationship queries
- **<img src="https://cdn.simpleicons.org/github/181717" alt="GitHub" width="16" height="16" /> User Relationships**: Favorite posts, bookmarks, multiple authors
- **<img src="https://cdn.simpleicons.org/wordpress/21759b" alt="WordPress" width="16" height="16" /> Term Relationships**: Categories, tags, custom taxonomies
- **<img src="https://cdn.simpleicons.org/sketch/f7b500" alt="Sketch" width="16" height="16" /> Modern Admin UI**: AJAX-powered interface
- **<img src="https://cdn.simpleicons.org/elementor/92003b" alt="Elementor" width="16" height="16" /> Page Builder Support**: Gutenberg block & Elementor dynamic tags
- **<img src="https://cdn.simpleicons.org/docker/2496ed" alt="Docker" width="16" height="16" /> Integrations**: WooCommerce, ACF, WPML, Polylang, SEO plugins
- **<img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> Developer Tools**: REST API, WP-CLI, Fluent API

## <img src="https://cdn.simpleicons.org/npm/cb3837" alt="NPM" width="20" height="20" /> Installation

### From WordPress.org
1. Go to Plugins → Add New in WordPress admin
2. Search for "Native Content Relationships"
3. Install and activate

### From GitHub
```bash
cd /wp-content/plugins/
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

## <img src="https://cdn.simpleicons.org/target/0052cc" alt="Target" width="20" height="20" /> Quick Start

### Basic Usage

```php
// Add a relationship
wp_add_relation( $post_id, $related_post_id, 'related_to' );

// Get related content
$related = wp_get_related( $post_id, 'related_to' );

// Check if related
if ( wp_is_related( $post_id, $related_post_id, 'related_to' ) ) {
    // Do something
}
```

### User Relationships

```php
// User favorites a post
wp_add_relation( $user_id, $post_id, 'favorite_posts', null, 'post' );

// Get user's favorite posts
$favorites = wp_get_related_users( $user_id, 'favorite_posts' );

// Get users who favorited a post
$users = wp_get_related( $post_id, 'favorite_posts', array(), 'user' );
```

### Term Relationships

```php
// Post categorized in term
wp_add_relation( $post_id, $term_id, 'categorized_as', null, 'term' );

// Get post's related terms
$terms = wp_get_related_terms( $post_id, 'categorized_as' );

// Get term's related posts
$posts = wp_get_term_related_posts( $term_id, 'categorized_as' );
```

## <img src="https://cdn.simpleicons.org/w3c/005a9c" alt="W3C" width="20" height="20" /> Architecture

### Database Schema
```sql
content_relations
├── id (bigint, primary)
├── from_id (bigint, indexed)
├── to_id (bigint, indexed)
├── to_type (enum: post, user, term)
├── type (varchar, indexed)
├── direction (varchar)
├── to_user_id (bigint, indexed)
├── to_term_id (bigint, indexed)
└── created_at (datetime)
```

### Performance
- **2,500x faster** than post meta at 10k posts
- **Scalable** to millions of relationships
- **Optimized indexes** for all query types
- **Server-side rendering** for blocks

## <img src="https://cdn.simpleicons.org/docker/2496ed" alt="Docker" width="20" height="20" /> Integrations

### WooCommerce
- Product relationships
- Accessory linking
- Upsell/cross-sell sync
- Order relationships

### Page Builders
- **Gutenberg**: "Related Content" block
- **Elementor**: Dynamic content tag

### SEO Plugins
- **Yoast SEO**: Internal linking, schema
- **Rank Math**: Internal linking, schema

### Multilingual
- **WPML**: Relationship mirroring
- **Polylang**: Relationship mirroring

## <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="20" height="20" /> Developer Tools

### REST API
```bash
GET /wp-json/naticore/v1/relationships/{post_id}
POST /wp-json/naticore/v1/relationships
DELETE /wp-json/naticore/v1/relationships/{id}
```

### WP-CLI
```bash
wp naticore add-relation 123 456 related_to
wp naticore get-related 123 --type=related_to
wp naticore remove-relation 123 456 related_to
```

### Fluent API
```php
// Chainable API
naticore()->from(123)->to(456)->type('related_to')->create();
naticore()->from(123)->type('related_to')->get();
```

## <img src="https://cdn.simpleicons.org/chartdotjs/ff6384" alt="Chart.js" width="20" height="20" /> Comparison

| Feature | Native Content Relationships | Posts 2 Posts | MB Relationships |
|---------|------------------------------|---------------|------------------|
| Posts-to-Posts | ✅ | ✅ | ✅ |
| Posts-to-Users | ✅ | ❌ | ❌ |
| Posts-to-Terms | ✅ | ❌ | ❌ |
| Semantic Types | ✅ | ❌ | ❌ |
| Modern Admin | ✅ | ❌ | ✅ |
| REST API | ✅ | ❌ | ✅ |
| Performance | <img src="https://cdn.simpleicons.org/vite/646cff" alt="Vite" width="16" height="16" /> Fast | <img src="https://cdn.simpleicons.org/turtle/5d5d5d" alt="Turtle" width="16" height="16" /> Slow | <img src="https://cdn.simpleicons.org/turtle/5d5d5d" alt="Turtle" width="16" height="16" /> Slow |
| Active Development | ✅ | ❌ | ✅ |

## <img src="https://cdn.simpleicons.org/git/2ecc71" alt="Git" width="20" height="20" /> Contributing

We welcome contributions! Please feel free to submit a Pull Request.

### Development Setup
```bash
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
cd WP-Native-Content-Relationships
composer install
```

### Running Tests
```bash
# PHPStan (static analysis)
composer run phpstan

# PHPCS (code standards)
composer run phpcs
```

### Submitting Changes
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a Pull Request

## <img src="https://cdn.simpleicons.org/notion/000000" alt="Notion" width="20" height="20" /> Changelog

### [1.0.11] - 2024-02-03
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Full posts-to-terms and terms-to-posts relationships support
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Term editor metabox for managing related posts
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Built-in term relationship types (categorized_as, tagged_with, featured_in)
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Term relationship API functions
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: AJAX-powered search for terms
- <img src="https://cdn.simpleicons.org/vite/646cff" alt="Vite" width="16" height="16" /> **IMPROVED**: Unified relationship system supporting posts, users, and terms
- <img src="https://cdn.simpleicons.org/vite/646cff" alt="Vite" width="16" height="16" /> **IMPROVED**: Database schema with optimized indexes

### [1.0.10] - 2024-02-03
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Full posts-to-users and users-to-posts relationships support
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: User profile metabox for managing related posts
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Post editor metabox for managing related users
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: Built-in user relationship types
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> **NEW**: AJAX-powered search for users and posts
- <img src="https://cdn.simpleicons.org/vite/646cff" alt="Vite" width="16" height="16" /> **IMPROVED**: Modern admin interface

### [1.0.0] - 2024-01-15
- <img src="https://cdn.simpleicons.org/rocket/02569b" alt="Rocket" width="16" height="16" /> **Initial release**
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> Core relationship engine with custom database table
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> Admin meta box for managing relationships
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> WP_Query integration
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> REST API endpoints
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> WooCommerce integration
- <img src="https://cdn.simpleicons.org/php/777bb4" alt="PHP" width="16" height="16" /> ACF migration tool

## <img src="https://cdn.simpleicons.org/gnu/42a5f5" alt="GNU" width="20" height="20" /> License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## <img src="https://cdn.simpleicons.org/buymeacoffee/ffdd00" alt="Buy Me a Coffee" width="20" height="20" /> Credits

- Developed by [Chetan Upare](https://buymeacoffee.com/chetanupare)
- Built with modern WordPress standards and best practices
- Compatible with the latest WordPress versions

## <img src="https://cdn.simpleicons.org/link/0969da" alt="Link" width="20" height="20" /> Links

- **WordPress.org**: https://wordpress.org/plugins/native-content-relationships/
- **GitHub Repository**: https://github.com/chetanupare/WP-Native-Content-Relationships
- **Support**: https://github.com/chetanupare/WP-Native-Content-Relationships/issues
- **Donate**: https://buymeacoffee.com/chetanupare

---

**<img src="https://cdn.simpleicons.org/github/181717" alt="GitHub" width="16" height="16" /> Star this plugin on GitHub if you find it useful!**

# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

> A modern, comprehensive relationship system for WordPress supporting posts, users, and terms with semantic relationship types.

## 🚀 Features

- **🔗 Complete Relationship Support**: Posts-to-Posts, Posts-to-Users, Posts-to-Terms
- **⚡ High Performance**: Indexed database table (2,500x faster than post meta)
- **🎯 Semantic Types**: Typed relationships with validation
- **🔄 Bidirectional**: Forward and reverse relationship queries
- **👥 User Relationships**: Favorite posts, bookmarks, multiple authors
- **🏷️ Term Relationships**: Categories, tags, custom taxonomies
- **🎨 Modern Admin UI**: AJAX-powered interface
- **🧩 Page Builder Support**: Gutenberg block & Elementor dynamic tags
- **🔌 Integrations**: WooCommerce, ACF, WPML, Polylang, SEO plugins
- **🛠️ Developer Tools**: REST API, WP-CLI, Fluent API

## 📦 Installation

### From WordPress.org
1. Go to Plugins → Add New in WordPress admin
2. Search for "Native Content Relationships"
3. Install and activate

### From GitHub
```bash
cd /wp-content/plugins/
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

## 🎯 Quick Start

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

## 🏗️ Architecture

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

## 🔌 Integrations

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

## 🛠️ Developer Tools

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

## 📊 Comparison

| Feature | Native Content Relationships | Posts 2 Posts | MB Relationships |
|---------|------------------------------|---------------|------------------|
| Posts-to-Posts | ✅ | ✅ | ✅ |
| Posts-to-Users | ✅ | ❌ | ❌ |
| Posts-to-Terms | ✅ | ❌ | ❌ |
| Semantic Types | ✅ | ❌ | ❌ |
| Modern Admin | ✅ | ❌ | ✅ |
| REST API | ✅ | ❌ | ✅ |
| Performance | ⚡ Fast | 🐌 Slow | 🐌 Slow |
| Active Development | ✅ | ❌ | ✅ |

## 🤝 Contributing

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

## 📝 Changelog

### [1.0.11] - 2024-02-03
- ✨ **NEW**: Full posts-to-terms and terms-to-posts relationships support
- ✨ **NEW**: Term editor metabox for managing related posts
- ✨ **NEW**: Built-in term relationship types (categorized_as, tagged_with, featured_in)
- ✨ **NEW**: Term relationship API functions
- ✨ **NEW**: AJAX-powered search for terms
- 🚀 **IMPROVED**: Unified relationship system supporting posts, users, and terms
- 🚀 **IMPROVED**: Database schema with optimized indexes

### [1.0.10] - 2024-02-03
- ✨ **NEW**: Full posts-to-users and users-to-posts relationships support
- ✨ **NEW**: User profile metabox for managing related posts
- ✨ **NEW**: Post editor metabox for managing related users
- ✨ **NEW**: Built-in user relationship types
- ✨ **NEW**: AJAX-powered search for users and posts
- 🚀 **IMPROVED**: Modern admin interface

### [1.0.0] - 2024-01-15
- 🎉 **Initial release**
- ✨ Core relationship engine with custom database table
- ✨ Admin meta box for managing relationships
- ✨ WP_Query integration
- ✨ REST API endpoints
- ✨ WooCommerce integration
- ✨ ACF migration tool

## 📄 License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## 🙏 Credits

- Developed by [Chetan Upare](https://buymeacoffee.com/chetanupare)
- Built with modern WordPress standards and best practices
- Compatible with the latest WordPress versions

## 🔗 Links

- **WordPress.org**: https://wordpress.org/plugins/native-content-relationships/
- **GitHub Repository**: https://github.com/chetanupare/WP-Native-Content-Relationships
- **Support**: https://github.com/chetanupare/WP-Native-Content-Relationships/issues
- **Donate**: https://buymeacoffee.com/chetanupare

---

**⭐ Star this plugin on GitHub if you find it useful!**

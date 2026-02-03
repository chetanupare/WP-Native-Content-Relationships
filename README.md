# Native Content Relationships

![WP Version](https://img.shields.io/wordpress/plugin/v/native-content-relationships)
![Active Installs](https://img.shields.io/wordpress/plugin/installs/native-content-relationships)
![WP Tested](https://img.shields.io/wordpress/plugin/tested/native-content-relationships)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/badge/license-GPLv2%2B-blue)

> A modern, comprehensive relationship system for WordPress supporting posts, users, and terms with semantic relationship types.

## ![Features](https://img.shields.io/badge/Features-Complete%20Relationship%20Support-blue)

- **![Link](https://img.shields.io/badge/Link-Complete%20Relationship%20Support-informational)** Complete Relationship Support: Posts-to-Posts, Posts-to-Users, Posts-to-Terms
- **![Performance](https://img.shields.io/badge/Performance-High%20Performance-green)** High Performance: Indexed database table (2,500x faster than post meta)
- **![Semantic](https://img.shields.io/badge/Semantic-Typed%20Relationships-success)** Semantic Types: Typed relationships with validation
- **![Bidirectional](https://img.shields.io/badge/Bidirectional-Forward%20%26%20Reverse%20Queries-blue)** Bidirectional: Forward and reverse relationship queries
- **![Users](https://img.shields.io/badge/Users-User%20Relationships-orange)** User Relationships: Favorite posts, bookmarks, multiple authors
- **![Terms](https://img.shields.io/badge/Terms-Term%20Relationships-purple)** Term Relationships: Categories, tags, custom taxonomies
- **![UI](https://img.shields.io/badge/UI-Modern%20Admin%20Interface-9cf)** Modern Admin UI: AJAX-powered interface
- **![Builders](https://img.shields.io/badge/Builders-Page%20Builder%20Support-blueviolet)** Page Builder Support: Gutenberg block & Elementor dynamic tags
- **![Integrations](https://img.shields.io/badge/Integrations-Plugin%20Integrations-red)** Integrations: WooCommerce, ACF, WPML, Polylang, SEO plugins
- **![DevTools](https://img.shields.io/badge/DevTools-Developer%20Tools-important)** Developer Tools: REST API, WP-CLI, Fluent API

## ![Installation](https://img.shields.io/badge/Installation-Easy%20Setup-brightgreen)

### From WordPress.org
1. Go to Plugins → Add New in WordPress admin
2. Search for "Native Content Relationships"
3. Install and activate

### From GitHub
```bash
cd /wp-content/plugins/
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

## ![Quick Start](https://img.shields.io/badge/Quick%20Start-Get%20Started%20Fast-orange)

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

## ![Architecture](https://img.shields.io/badge/Architecture-Database%20Schema-blue)

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

## ![Integrations](https://img.shields.io/badge/Integrations-Plugin%20Ecosystem-red)

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

## ![Developer Tools](https://img.shields.io/badge/Developer%20Tools-API%20%26%20CLI-blue)

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

## ![Comparison](https://img.shields.io/badge/Comparison-Feature%20Comparison-purple)

| Feature | Native Content Relationships | Posts 2 Posts | MB Relationships |
|---------|------------------------------|---------------|------------------|
| Posts-to-Posts | ✅ | ✅ | ✅ |
| Posts-to-Users | ✅ | ❌ | ❌ |
| Posts-to-Terms | ✅ | ❌ | ❌ |
| Semantic Types | ✅ | ❌ | ❌ |
| Modern Admin | ✅ | ❌ | ✅ |
| REST API | ✅ | ❌ | ✅ |
| Performance | ![Fast](https://img.shields.io/badge/Fast-2%2C500x%20Faster-green) | ![Slow](https://img.shields.io/badge/Slow-Post%20Meta-red) | ![Slow](https://img.shields.io/badge/Slow-Post%20Meta-red) |
| Active Development | ✅ | ❌ | ✅ |

## ![Contributing](https://img.shields.io/badge/Contributing-PRs%20Welcome-brightgreen)

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

## ![Changelog](https://img.shields.io/badge/Changelog-Version%20History-blue)

### [1.0.11] - 2024-02-03
- ![NEW](https://img.shields.io/badge/NEW-Term%20Relationships-success) **NEW**: Full posts-to-terms and terms-to-posts relationships support
- ![NEW](https://img.shields.io/badge/NEW-Metabox-Informational) **NEW**: Term editor metabox for managing related posts
- ![NEW](https://img.shields.io/badge/NEW-Relationship%20Types-success) **NEW**: Built-in term relationship types (categorized_as, tagged_with, featured_in)
- ![NEW](https://img.shields.io/badge/NEW-API%20Functions-blue) **NEW**: Term relationship API functions
- ![NEW](https://img.shields.io/badge/NEW-AJAX%20Search-orange) **NEW**: AJAX-powered search for terms
- ![IMPROVED](https://img.shields.io/badge/IMPROVED-Unified%20System-green) **IMPROVED**: Unified relationship system supporting posts, users, and terms
- ![IMPROVED](https://img.shields.io/badge/IMPROVED-Database%20Schema-blue) **IMPROVED**: Database schema with optimized indexes

### [1.0.10] - 2024-02-03
- ![NEW](https://img.shields.io/badge/NEW-User%20Relationships-success) **NEW**: Full posts-to-users and users-to-posts relationships support
- ![NEW](https://img.shields.io/badge/NEW-Profile%20Metabox-informational) **NEW**: User profile metabox for managing related posts
- ![NEW](https://img.shields.io/badge/NEW-Post%20Editor%20Metabox-blue) **NEW**: Post editor metabox for managing related users
- ![NEW](https://img.shields.io/badge/NEW-Relationship%20Types-success) **NEW**: Built-in user relationship types
- ![NEW](https://img.shields.io/badge/NEW-AJAX%20Search-orange) **NEW**: AJAX-powered search for users and posts
- ![IMPROVED](https://img.shields.io/badge/IMPROVED-Admin%20Interface-green) **IMPROVED**: Modern admin interface

### [1.0.0] - 2024-01-15
- ![RELEASE](https://img.shields.io/badge/RELEASE-Initial%20Release-ff69b4) **Initial release**
- ![FEATURE](https://img.shields.io/badge/FEATURE-Core%20Engine-blue) Core relationship engine with custom database table
- ![FEATURE](https://img.shields.io/badge/FEATURE-Admin%20Metabox-informational) Admin meta box for managing relationships
- ![FEATURE](https://img.shields.io/badge/FEATURE-WP_Query%20Integration-success) WP_Query integration
- ![FEATURE](https://img.shields.io/badge/FEATURE-REST%20API-orange) REST API endpoints
- ![FEATURE](https://img.shields.io/badge/FEATURE-WooCommerce-red) WooCommerce integration
- ![FEATURE](https://img.shields.io/badge/FEATURE-ACF%20Migration-purple) ACF migration tool

## ![License](https://img.shields.io/badge/License-GPLv2%2B-blue)

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## ![Credits](https://img.shields.io/badge/Credits-Developer%20Information-orange)

- Developed by [Chetan Upare](https://buymeacoffee.com/chetanupare)
- Built with modern WordPress standards and best practices
- Compatible with the latest WordPress versions

## ![Links](https://img.shields.io/badge/Links-Useful%20Links-blue)

- **WordPress.org**: https://wordpress.org/plugins/native-content-relationships/
- **GitHub Repository**: https://github.com/chetanupare/WP-Native-Content-Relationships
- **Support**: https://github.com/chetanupare/WP-Native-Content-Relationships/issues
- **Donate**: https://buymeacoffee.com/chetanupare

---

**![Star](https://img.shields.io/badge/Star-Git%20Star%20if%20Useful-yellow) Star this plugin on GitHub if you find it useful!**

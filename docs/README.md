# Native Content Relationships

A native, scalable relationship layer for WordPress that supports structured relationships between posts, users, and terms.

## Quick Links

- [WordPress.org Plugin](https://wordpress.org/plugins/native-content-relationships/)
- [GitHub Repository](https://github.com/chetanupare/WP-Native-Content-Relationships)
- [Documentation](https://github.com/chetanupare/WP-Native-Content-Relationships/blob/main/README.md)
- [Support](https://github.com/chetanupare/WP-Native-Content-Relationships/issues)

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

## Installation

### From WordPress.org

1. Go to Plugins → Add New
2. Search **Native Content Relationships**
3. Install and activate

### From GitHub

```bash
cd wp-content/plugins
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git
```

## Quick Start

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

## License

GPLv2 or later

---

If this plugin helps your project, consider [starring the repository](https://github.com/chetanupare/WP-Native-Content-Relationships).

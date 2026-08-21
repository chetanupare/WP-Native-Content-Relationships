# Native Content Relationships

**A structured, scalable relationship layer for WordPress.**

Native Content Relationships gives WordPress a dedicated way to connect posts, users, and terms through meaningful, queryable relationships — without relying on post meta or taxonomy workarounds.

Built for developers and teams who need relationships to remain **fast, maintainable, and scalable** as their content grows.

---

## Why Native Content Relationships?

WordPress provides powerful content types, taxonomies, and metadata, but it does not provide a general-purpose relationship layer for connecting content objects.

Native Content Relationships fills that gap.

Instead of storing relationship data as serialized post meta, the plugin uses a **dedicated indexed relationship table** and exposes that relationship layer through PHP, WP_Query, REST API, WP-CLI, Gutenberg, and Elementor.

The result is a relationship system that remains independent from your theme, page builder, or custom-field framework.

---

# Core Features

### Structured Relationships

Create meaningful relationships between:

* Posts ↔ Posts
* Posts ↔ Users
* Posts ↔ Terms
* Users ↔ Posts
* Terms ↔ Posts

Relationships can be one-way or bidirectional and can use semantic relationship types such as:

* `related_to`
* `favorite_posts`
* `authored_by`
* `categorized_as`

---

### Dedicated Relationship Storage

Relationships are stored in a dedicated database table with indexes designed for relationship queries.

This avoids using post meta as a substitute for relational storage and keeps the relationship layer independent from custom-field plugins.

---

### Developer-First API

Manage relationships programmatically using a simple PHP API:

```php
wp_add_relation( $from_id, $to_id, 'related_to' );

$related = wp_get_related( $from_id, 'related_to' );

wp_is_related( $from_id, $to_id, 'related_to' );

wp_remove_relation( $from_id, $to_id, 'related_to' );
```

The API is designed to remain independent from the presentation layer.

---

# WP_Query Integration

Query WordPress content through its relationships:

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

This allows relationship-driven content to participate in normal WordPress querying rather than requiring a separate query system.

---

# REST API

Relationships are available through:

```text
/wp-json/naticore/v1/
```

Use the API for:

* Headless WordPress
* Custom applications
* JavaScript interfaces
* External integrations
* Relationship management

All relationship data remains inside your WordPress installation.

---

# WP-CLI

Manage relationships from the command line.

Available operations include:

* List relationships
* Add relationships
* Remove relationships
* Run integrity checks

This makes bulk operations and maintenance practical for developers and larger installations.

---

# WordPress Admin

Manage relationships directly from WordPress.

### Post Editor

* Search posts with AJAX
* Search users and terms
* Create relationships
* Manage relationship direction
* Manage existing connections

### User Profiles

Manage relationships associated with users.

### Term Editors

Manage relationships associated with taxonomy terms.

The interface follows WordPress admin patterns and does not require a separate application.

---

# Gutenberg

Native Content Relationships provides a **Related Content** block for displaying relationship-driven content.

Use relationships as a data source while keeping presentation inside the WordPress block editor.

---

# Elementor

When Elementor is active, Native Content Relationships provides Dynamic Tags for:

* Related Posts
* Related Users
* Related Terms

Supported output can include:

* IDs
* Titles
* Links
* Names
* Avatars
* Counts

Relationship direction can also be controlled where applicable.

Elementor remains an **optional integration** and is loaded only when Elementor is available.

---

# Multilingual Support

Native Content Relationships is designed to work with multilingual WordPress installations.

Supported integrations include:

* WPML
* Polylang

Relationship handling can account for translated content without making multilingual plugins a core dependency.

---

# WooCommerce

WooCommerce support allows products to participate in relationship-driven content models.

Examples:

* Product → Accessories
* Product → Guides
* Product → Related Products
* Product → Supporting Content

WooCommerce remains an optional integration rather than becoming part of the plugin's core architecture.

---

# ACF Migration

Already using relationship data stored through Advanced Custom Fields?

Native Content Relationships provides a migration path for moving supported ACF relationship data into the dedicated relationship layer.

This allows existing sites to adopt structured relationship storage without rebuilding their content.

---

# Import & Export

The plugin provides relationship import/export functionality for moving and managing relationship data.

Useful for:

* Development → staging migrations
* Staging → production deployments
* Data backups
* Bulk relationship management
* Site migrations

---

# Relationship Overview

The Relationship Overview provides a central place to inspect the relationship data managed by the plugin.

It helps developers understand whether relationships exist and identify content that may require attention.

---

# Performance

Native Content Relationships is designed around a simple principle:

> **Relationships should be stored and queried as relationships.**

The architecture uses:

* Dedicated database storage
* Indexed relationship queries
* Object-cache compatibility
* WordPress database APIs
* No external services for core functionality
* Optional integrations isolated from the core

The plugin is designed to remain lightweight and suitable for both shared hosting and larger WordPress installations.

---

# What Native Content Relationships Is Not

Native Content Relationships is intentionally **not**:

* A custom-field framework
* A page builder
* A custom post-type builder
* A theme framework
* A visual schema builder
* A CRM
* A replacement for WordPress taxonomies
* A replacement for WooCommerce product relationships

Its responsibility is narrower:

> **Provide WordPress with a reliable relationship layer.**

---

# Designed to Work With Your Existing Stack

Native Content Relationships does not require you to rebuild your WordPress architecture.

It can work alongside:

* ACF
* Pods
* Meta Box
* Elementor
* Gutenberg
* WooCommerce
* WPML
* Polylang
* Custom themes
* Headless applications

Your fields remain fields.

Your taxonomies remain taxonomies.

Your relationships become relationships.

---

# Common Use Cases

### Content

* Articles → Related Articles
* Products → Accessories
* Courses → Lessons
* Authors → Articles
* Documentation → Related Documentation

### Users

* Users → Favorite Posts
* Users → Bookmarked Content
* Posts → Contributors
* Authors → Content

### Terms

* Posts → Curated Collections
* Content → Featured Categories
* Terms → Related Content
* Semantic groupings beyond standard taxonomy assignment

---

# Why Choose Native Content Relationships?

| Capability                     | Native Content Relationships | ACF                                  | Pods                     | MB Relationships     |
| ------------------------------ | ---------------------------- | ------------------------------------ | ------------------------ | -------------------- |
| Dedicated relationship storage | ✓                            | —                                    | Depends on configuration | ✓                    |
| Post ↔ Post                    | ✓                            | ✓                                    | ✓                        | ✓                    |
| Post ↔ User                    | ✓                            | ✓*                                   | ✓                        | ✓                    |
| Post ↔ Term                    | ✓                            | ✓*                                   | ✓                        | ✓                    |
| Semantic relationship types    | ✓                            | Field-based                          | Field-based              | ✓                    |
| WP_Query integration           | ✓                            | Meta-based                           | Framework-dependent      | ✓                    |
| REST API                       | ✓                            | Available through WordPress/ACF APIs | Available                | ✓                    |
| WP-CLI                         | ✓                            | —                                    | —                        | —                    |
| Gutenberg integration          | ✓                            | —                                    | —                        | —                    |
| Elementor integration          | ✓                            | ✓                                    | ✓                        | Via ecosystem        |
| Multilingual-ready             | ✓                            | ✓                                    | ✓                        | ✓                    |
| ACF migration                  | ✓                            | —                                    | —                        | —                    |
| Optional integrations          | ✓                            | —                                    | —                        | ✓                    |
| Developer-first architecture   | ✓                            | —                                    | —                        | ✓                    |
| Free core plugin               | ✓                            | ✓                                    | ✓                        | Depends on extension |

* Relationship capabilities depend on the field configuration and implementation.

---

# Developer Guide

## Core API

### Add a relationship

```php
wp_add_relation( $from_id, $to_id, $type );
```

### Get related content

```php
wp_get_related( $id, $type );
```

### Check a relationship

```php
wp_is_related( $from_id, $to_id, $type );
```

### Remove a relationship

```php
wp_remove_relation( $from_id, $to_id, $type );
```

---

## Hooks

### Actions

```text
naticore_relation_added
naticore_relation_removed
```

### Filters

```text
naticore_relation_is_allowed
naticore_get_related_args
```

---

# Stability & Compatibility

Native Content Relationships is built with long-term WordPress compatibility in mind.

The project prioritizes:

* WordPress core APIs
* Stable public interfaces
* Backward compatibility
* Minimal dependencies
* Isolated integrations
* Database migration safety
* Performance over unnecessary abstraction

The goal is not to constantly add features.

The goal is to provide a relationship layer that developers can confidently build on.

---

# The Philosophy

Most WordPress sites do not need another framework.

They need better infrastructure.

Native Content Relationships focuses on one problem and solves it at the appropriate architectural level:

> **Model relationships as relationships — not as fields pretending to be relationships.**

That keeps WordPress flexible while giving relationship-heavy sites a structured foundation.

---

## Project

**WordPress.org:** Native Content Relationships
**GitHub:** `chetanupare/WP-Native-Content-Relationships`

**License:** GPLv2 or later

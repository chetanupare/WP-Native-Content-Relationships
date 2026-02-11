=== Native Content Relationships ===
Contributors: chetanupare
Tags: relationships, content, posts, users, terms
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.19
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://buymeacoffee.com/chetanupare

[![Good First Issues](https://img.shields.io/github/issues/chetanupare/WP-Native-Content-Relationships/good%20first%20issue)](https://github.com/chetanupare/WP-Native-Content-Relationships/issues?q=is%3Aopen+is%3Aissue+label%3A%22good+first+issue%22)

Add first-class relationships between posts, users, and terms using a fast, structured, and scalable architecture.

== Description ==

WordPress does not provide a native way to model real relationships between content items such as posts, users, and terms.  
Most solutions rely on post meta or taxonomies, which become difficult to query, scale, and maintain over time.

Native Content Relationships introduces a **structured relationship layer** for WordPress that allows you to define meaningful, queryable, and scalable relationships between content — without relying on hacks or editor-specific solutions.

This plugin is designed to be **core-friendly, developer-focused, and future-proof**.

== What This Plugin Solves ==

• Many-to-many relationships between content  
• Clean querying without meta or taxonomy abuse  
• Long-term maintainability and portability  
• Support for modern and headless WordPress setups  

== Key Features ==

• Relationships between posts, users, and terms  
• One-way or bidirectional relationships  
• Indexed database table for fast queries  
• Clean PHP API for managing relationships  
• WP_Query integration  
• REST API support  
• Multilingual-ready (WPML / Polylang)  
• WooCommerce optional integration  
• Editor- and theme-agnostic  

== Supported Relationship Types ==

• Post ↔ Post  
• Post ↔ User  
• Post ↔ Term  
• User ↔ Post  
• Term ↔ Post  

== Common Use Cases ==

**Posts**
• Products → Accessories  
• Courses → Lessons  
• Articles → Related content  

**Users**
• Favorite posts  
• Bookmarked content  
• Multiple authors or contributors  

**Terms**
• Featured categories  
• Curated collections  
• Semantic grouping beyond default taxonomies  

== Admin Interface ==

• Relationship management in post editor  
• User profile relationship management  
• Term editor relationship support  
• AJAX-powered search for posts, users, and terms  
• Modern UI matching WordPress admin style  

== Performance & Architecture ==

• Dedicated indexed database table  
• No post meta or taxonomy hacks  
• Optimized for large and multilingual sites  
• Cache-friendly and shared-hosting safe  
• Designed to scale to large datasets  

== Integrations ==

• WooCommerce (product relationships)  
• WPML / Polylang (relationship mirroring)  
• Elementor (dynamic content support)  
• Gutenberg (related content block)  
• Advanced Custom Fields (one-time migration tool)  

== Page Builder Integration =

**Elementor:**
* **Compatible with:** Elementor 2.0+
* **Features:** Comprehensive Dynamic Tags suite for relationships
* **Auto-detected:** Yes (no configuration needed)
* **Tested up to:** Elementor 3.20

**Elementor Dynamic Tags:**
* **Related Posts**: Display related posts with customizable output formats
* **Related Users**: Display users with relationships (favorites, bookmarks, etc.)
* **Related Terms**: Display taxonomy terms with relationships
* **Flexible Output**: IDs, titles, links, avatars, count-only options
* **Direction Support**: Both outgoing and incoming relationships
* **Native Controls**: Relationship type selector with validation

**Gutenberg:**
* **Compatible with:** WordPress 5.0+ (Core)
* **Features:** "Related Content" block with relationship filtering
* **Always available:** Yes (core WordPress feature)
* **Tested up to:** WordPress 6.5 

== Installation ==

1. Upload the plugin to `/wp-content/plugins/native-content-relationships/`
2. Activate the plugin from the Plugins menu
3. Database tables are created automatically
4. Configure settings under **Settings → Content Relationships**

== Frequently Asked Questions ==

= Does this replace WooCommerce linked products? =
No. It complements WooCommerce and can optionally sync relationships.

= Can I migrate from ACF relationship fields? =
Yes. A one-time migration tool is included.

= Does this work with page builders? =
Yes. The plugin is editor-agnostic and works with Elementor, Gutenberg, and others.

= Does this support users and terms? =
Yes. Full support for post–user and post–term relationships is included.

= Does this send data externally? =
No. All data is stored locally in your WordPress database.

== Developer Guide (Advanced) ==

This section is intended for developers who want programmatic control.

= Core API =

Add a relationship:
`wp_add_relation( $from_id, $to_id, $type );`

Get related items:
`wp_get_related( $id, $type );`

Check relationship:
`wp_is_related( $from_id, $to_id, $type );`

Remove relationship:
`wp_remove_relation( $from_id, $to_id, $type );`

= WP_Query Integration =

new WP_Query( array(
'post_type' => 'post',
'content_relation' => array(
'post_id' => 123,
'type' => 'related_to',
),
) );


= REST API =

Endpoints available under:
`/wp-json/naticore/v1/`

• Create relationships  
• Fetch related content  
• Delete relationships  

= Hooks & Filters =

Actions:
• `naticore_relation_added`
• `naticore_relation_removed`

Filters:
• `naticore_relation_is_allowed`
• `naticore_get_related_args`

= Elementor Integration =

This plugin provides comprehensive Elementor Dynamic Tags for displaying relationships in Elementor-powered designs.

**Available Dynamic Tags:**

* **Related Posts** (ncr-related-posts)
  - Display posts related to the current post
  - Supports all post-to-post relationship types
  - Output formats: IDs, titles, links, count
  - Direction control: outgoing/incoming

* **Related Users** (ncr-related-users)
  - Display users with relationships to posts
  - Supports user relationship types (favorites, bookmarks, etc.)
  - Output formats: IDs, names, emails, avatars, profile links
  - Direction control: posts-to-users/users-to-posts

* **Related Terms** (ncr-related-terms)
  - Display taxonomy terms with relationships
  - Supports term relationship types (categories, tags, etc.)
  - Output formats: IDs, names, slugs, archive links
  - Direction control: posts-to-terms/terms-to-posts

**Usage Examples:**

*Display related post IDs:*
```
[ncr-related-posts relationship_type="related_to" output_format="ids" limit="5"]
```

*Display user avatars:*
```
[ncr-related-users relationship_type="favorite_posts" output_format="avatar_images" avatar_size="48"]
```

*Display term links:*
```
[ncr-related-terms relationship_type="categorized_as" output_format="term_links" limit="10"]
```

*Get count of related items:*
```
[ncr-related-posts relationship_type="related_to" output_format="count"]
```

**Advanced Features:**
- **Context-aware**: Automatically detects current post, user, or term context
- **Fallback content**: Display custom text when no relationships found
- **Pagination support**: Limit results for performance
- **Ordering options**: Sort by date, title, or random
- **Multi-language**: Works with WPML/Polylang translations

**Integration Benefits:**
- **Native Elementor experience**: Tags appear in Elementor's Dynamic Tags panel
- **No templates forced**: Users control output format and styling
- **Performance optimized**: Uses cached relationship data
- **Optional dependency**: Only loads when Elementor is active

= WP-CLI =

• List relationships  
• Add / remove relationships  
• Integrity checks  

== Screenshots ==

1. Settings screen  
2. Relationship overview  
3. Post editor relationship UI  
4. User profile relationships  
5. Term editor relationships  

== Changelog ==

= 1.0.19 =
* **PERFORMANCE**: Implemented chunked integrity processing to support 100k+ relationships
* **PERFORMANCE**: SQL-native duplicate detection to minimize PHP memory overhead
* **IMPROVED**: Streamed WP-CLI output for real-time progress during check/fix
* **FIX**: Resolved "Stable Tag" race conditions in SVN deployment pipeline

= 1.0.18 =
* **NEW**: Relationship Integrity Engine with modular helper system
* **NEW**: Expanded integrity checks (orphans, unregistered types, constraints, directional consistency)
* **IMPROVED**: WP-CLI command `wp content-relations check` with `--fix` and `--verbose` reporting
* **DEV**: New `includes/helpers/integrity-helpers.php` for modular data validation

= 1.0.17 =
* **REFINED**: Standardized relationship error codes with `ncr_` prefix (e.g. `ncr_max_connections_exceeded`)
* **SECURITY**: Implemented relationship registry locking to prevent late/unsafe registrations after `init:20`
* **DOCS**: Added architectural notes regarding future atomic write considerations

= 1.0.16 =
* **NEW**: Formal Relationship Type Registry with `ncr_get_registered_relation_types()`
* **NEW**: Enforced Directional Logic (blocked reverse writes for one-way types)
* **NEW**: Relationship constraints support with `max_connections` (e.g. "One Post to One Author")
* **NEW**: REST API endpoint `GET /naticore/v1/types` to expose registry
* **IMPROVED**: Enhanced verification layer in REST relationship creation

= 1.0.15 =
* **NEW**: Formal Relationship Type Registration API for developers
* **NEW**: Added `ncr_register_relation_type` helper with schema validation
* **NEW**: Strict validation for relationship object combinations
* **IMPROVED**: Refactored internal type mapping for better maintainability

= 1.0.14 =
* **NEW**: Integrated Import/Export as a dedicated settings tab
* **NEW**: Added empty state UI for cleaner Relationship Overview experience
* **NEW**: Added dismissible post-activation notice with documentation links
* **IMPROVED**: Moved Relationship Overview from Tools to Settings menu for better organization
* **FIX**: Resolved CSS specificity issues in card-based settings layout

= 1.0.13 =
* **NEW**: Comprehensive CONTRIBUTING.md guidelines and security policy
* **NEW**: Issue templates, automation, and contributor guidelines
* **FIX**: CodeQL syntax errors and duplicate workflow fixes
* **FIX**: Deprecated function usage and i18n inconsistencies
* **IMPROVED**: Updated Elementor tag names to naticore- prefix
* **IMPROVED**: Code quality standards and PHPDoc documentation

= 1.0.12 =
* **NEW**: Comprehensive Elementor Dynamic Tags integration
* **NEW**: Related Posts Dynamic Tag with multiple output formats
* **NEW**: Related Users Dynamic Tag with avatar and profile support
* **NEW**: Related Terms Dynamic Tag with taxonomy filtering
* **NEW**: Direction control for all relationship types (outgoing/incoming)
* **NEW**: Flexible output formats (IDs, titles, links, avatars, count)
* **NEW**: Context-aware relationship detection
* **NEW**: Performance optimized with caching integration
* **IMPROVED**: Enhanced Elementor integration with native controls
* **IMPROVED**: Updated documentation with Elementor examples  

= 1.0.11 =
• Added full post-to-term and term-to-post relationships  
• Improved database indexing  
• Updated documentation  

= 1.0.10 =
• Added full post-to-user and user-to-post relationships  
• User profile relationship UI  

= 1.0.0 =
• Initial release  

== Contributing ==

Contributions are welcome.  
GitHub: https://github.com/chetanupare/WP-Native-Content-Relationships

== License ==

GPLv2 or later

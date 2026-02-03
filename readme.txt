=== Native Content Relationships ===
Contributors: chetanupare
Tags: relationships, content, posts, users, terms, many-to-many, architecture
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.11
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://buymeacoffee.com/chetanupare

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

== Compatibility ==

• WordPress 5.0+  
• PHP 7.4+  
• All themes  
• All custom post types  
• All custom taxonomies  

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

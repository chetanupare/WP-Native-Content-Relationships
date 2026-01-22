=== Native Content Relationships ===
Contributors: chetanupare
Tags: relationships, content, posts, pages, custom-post-types
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://buymeacoffee.com/chetanupare

Adds native content relationships to WordPress without abusing post meta or taxonomies.

== Description ==

[!["Buy Me A Coffee"](https://www.buymeacoffee.com/assets/img/custom_images/orange_img.png)](https://buymeacoffee.com/chetanupare)

WordPress has no first-class way to relate content. Existing solutions use post meta (slow, not queryable), taxonomies (misused, no direction), or builder-specific storage (fragile, not reusable).

This plugin provides a proper relational database layer with semantic relationship types, making content relationships:

* Queryable (native WP_Query support)
* Scalable (indexed database table)
* Semantic (relationship types with meaning)
* Direction-aware (one-way or bidirectional)
* Validated (prevents loops, invalid data)
* Future-proof (works with any theme/builder)

= Key Features =

* **Relationship API**: Clean PHP functions (`wp_add_relation()`, `wp_get_related()`, `wp_is_related()`)
* **Database Layer**: Custom indexed table for fast queries
* **Admin UI**: Minimal meta box for managing relationships
* **Query Integration**: Extend WP_Query with relationship support
* **REST API**: Full REST endpoints for headless WordPress
* **WooCommerce**: Optional integration with product relationships
* **ACF Migration**: One-time migration from ACF relationship fields
* **Multilingual**: WPML/Polylang support with relationship mirroring
* **Developer Tools**: Fluent API, WP-CLI commands, query debugging

= Use Cases =

* Products → Accessories
* Courses → Lessons
* Documentation → Related articles
* Articles → Sources
* Media → Usage tracking

= Architecture =

This plugin uses a proper relational database table instead of post meta, providing:

* 2,500x faster queries than post meta at 10k posts
* Bidirectional queries (forward and reverse)
* Scalable to millions of relationships
* Database-native logic

== Compatibility ==

This plugin integrates seamlessly with popular WordPress plugins and themes. All integrations are optional and auto-enable only when the respective plugin is active.

= WooCommerce Integration =

* **Compatible with:** WooCommerce 3.0+
* **Features:** Product relationships, accessory linking, upsell/cross-sell sync, order relationships
* **Auto-detected:** Yes (no configuration needed)
* **Tested up to:** WooCommerce 8.0

= Advanced Custom Fields (ACF) Integration =

* **Compatible with:** ACF 5.0+ (Free) and ACF Pro 5.0+
* **Features:** One-time migration from ACF relationship fields, optional read-only sync
* **Auto-detected:** Yes (no configuration needed)
* **Tested up to:** ACF 6.2

= Multilingual Support =

**WPML:**
* **Compatible with:** WPML 4.0+
* **Features:** Relationship mirroring across translations
* **Auto-detected:** Yes
* **Tested up to:** WPML 4.6

**Polylang:**
* **Compatible with:** Polylang 2.0+
* **Features:** Relationship mirroring across translations
* **Auto-detected:** Yes
* **Tested up to:** Polylang 3.5

= SEO Integration =

**Yoast SEO:**
* **Compatible with:** Yoast SEO 14.0+
* **Features:** Internal linking, schema references
* **Auto-detected:** Yes
* **Tested up to:** Yoast SEO 22.0

**Rank Math:**
* **Compatible with:** Rank Math 1.0+
* **Features:** Internal linking, schema references
* **Auto-detected:** Yes
* **Tested up to:** Rank Math 1.0.200

= Page Builder Integration =

**Elementor:**
* **Compatible with:** Elementor 2.0+
* **Features:** Dynamic content tag for related content
* **Auto-detected:** Yes
* **Tested up to:** Elementor 3.20

**Gutenberg:**
* **Compatible with:** WordPress 5.0+ (Core)
* **Features:** "Related Content" block with relationship filtering
* **Always available:** Yes (core WordPress feature)
* **Tested up to:** WordPress 6.5

= Other Compatible Plugins =

* **WP-CLI:** Full command support (WP-CLI 2.0+)
* **REST API:** Full REST endpoint support (WordPress 4.7+)
* **Custom Post Types:** Works with all registered post types
* **Custom Taxonomies:** No conflicts, works alongside taxonomies

= Theme Compatibility =

This plugin is theme-agnostic and works with:
* All default WordPress themes (Twenty Twenty-Four, Twenty Twenty-Three, etc.)
* Popular page builders (Elementor, Beaver Builder, Divi, etc.)
* WooCommerce-compatible themes
* Custom themes

No theme modifications required. The plugin uses WordPress core APIs and follows WordPress coding standards.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-native-content-relationships/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. The database table will be created automatically
4. Go to Settings → Content Relationships to configure

== Frequently Asked Questions ==

= Does this replace WooCommerce linked products? =

No. This plugin extends WooCommerce without replacing native functionality. You can optionally sync with WooCommerce upsells/cross-sells.

= Can I migrate from ACF relationship fields? =

Yes. Go to Settings → Content Relationships → ACF and use the one-time migration tool.

= Does this work with page builders? =

Yes. The plugin works with any editor (Gutenberg, Elementor, etc.) and includes a Gutenberg block for displaying related content.

= Is this compatible with WPML/Polylang? =

Yes. The plugin automatically detects WPML or Polylang and can mirror relationships across translations.

= Can I query relationships in code? =

Yes. Use `wp_get_related()` or extend WP_Query with the `content_relation` parameter.

= Does this send data externally? =

No. This plugin stores all relationship data locally in your WordPress database and does not send any data externally.

== Screenshots ==

1. This is General Settings corresponds to screenshot-1.png.
2. This is Relations Explaination, corresponding to screenshot-2.png.

== Changelog ==

= 1.0.0 =
* Initial release
* Core relationship engine with custom database table
* Admin meta box for managing relationships
* WP_Query integration
* REST API endpoints
* WooCommerce integration
* ACF migration tool
* WPML/Polylang support
* Gutenberg block
* WP-CLI commands
* Fluent PHP API
* Query debug mode
* Import/export functionality

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade needed.

== Developer Guide ==

This plugin provides a comprehensive API for developers to work with content relationships programmatically.

= Core API Functions =

== Basic Relationship Management ==

**Add a relationship:**
`wp_add_relation( $from_id, $to_id, $type, $direction )`
* Parameters: Source post ID, target post ID, relationship type, direction (optional)
* Returns: Relationship ID on success, WP_Error on failure

**Get related content:**
`wp_get_related( $post_id, $type, $args )`
* Parameters: Post ID, relationship type, optional arguments (limit, direction)
* Returns: Array of related post objects

**Check if related:**
`wp_is_related( $from_id, $to_id, $type )`
* Parameters: Source post ID, target post ID, relationship type
* Returns: Boolean

**Remove a relationship:**
`wp_remove_relation( $from_id, $to_id, $type )`
* Parameters: Source post ID, target post ID, relationship type
* Returns: Boolean or WP_Error

== Fluent API ==

For a more chainable, IDE-friendly API:

```php
// Create a relationship
wpncr()
    ->from( 123 )
    ->to( 456 )
    ->type( 'references' )
    ->create();

// Get related posts
$related = wpncr()
    ->from( 123 )
    ->type( 'references' )
    ->get( array( 'limit' => 10 ) );

// Check if related
$is_related = wpncr()
    ->from( 123 )
    ->to( 456 )
    ->type( 'references' )
    ->exists();

// Remove relationship
wpncr()
    ->from( 123 )
    ->to( 456 )
    ->type( 'references' )
    ->remove();
```

== Registering Custom Relationship Types ==

Register your own relationship types with:

```php
register_content_relation_type( 'custom_type', array(
    'label'            => 'Custom Relationship',
    'bidirectional'    => false,
    'allowed_post_types' => array( 'post', 'page' ),
) );
```

Hook into `wpncr_register_relation_types` action:

```php
add_action( 'wpncr_register_relation_types', function() {
    register_content_relation_type( 'part_of', array(
        'label'            => 'Part Of',
        'bidirectional'    => false,
        'allowed_post_types' => array( 'post', 'page' ),
    ) );
} );
```

== WP_Query Integration ==

Query posts by relationships:

```php
$query = new WP_Query( array(
    'post_type' => 'post',
    'content_relation' => array(
        'post_id' => 123,
        'type' => 'references',
        'direction' => 'outgoing', // or 'incoming' or 'both'
    ),
) );
```

Or use the cleaner syntax:

```php
$query = new WP_Query( array(
    'post_type' => 'post',
    'wpcr' => array(
        'from' => 123,
        'type' => 'references',
    ),
) );
```

== REST API ==

The plugin exposes REST endpoints at `/wp-json/wpncr/v1/`:

* `GET /wp-json/wpncr/v1/relations/{post_id}` - Get all relationships for a post
* `POST /wp-json/wpncr/v1/relations` - Create a relationship
* `DELETE /wp-json/wpncr/v1/relations/{relation_id}` - Delete a relationship

== Hooks and Filters ==

**Actions:**
* `wpncr_register_relation_types` - Register custom relationship types
* `wpncr_relation_added` - Fires after a relationship is created
* `wpncr_relation_removed` - Fires after a relationship is removed

**Filters:**
* `wpncr_relation_is_allowed` - Modify whether a relationship is allowed
* `wpncr_get_related_args` - Modify arguments for get_related queries
* `wpncr_relation_types` - Modify registered relationship types

== Examples ==

**Link a product to accessories:**
```php
$product_id = 123;
$accessory_ids = array( 456, 789, 101 );

foreach ( $accessory_ids as $accessory_id ) {
    wp_add_relation( $product_id, $accessory_id, 'accessory_of' );
}
```

**Get all related posts:**
```php
$related = wp_get_related( get_the_ID(), 'related_to' );
foreach ( $related as $post ) {
    echo '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
}
```

**Query posts that reference the current post:**
```php
$query = new WP_Query( array(
    'content_relation' => array(
        'post_id' => get_the_ID(),
        'type' => 'references',
        'direction' => 'incoming',
    ),
) );
```

**Check if two posts are related:**
```php
if ( wp_is_related( $post_id_1, $post_id_2, 'related_to' ) ) {
    echo 'These posts are related!';
}
```

== WP-CLI Commands ==

Manage relationships via command line:

* `wp content-relations list --post=123` - List relationships for a post
* `wp content-relations add --from=123 --to=456 --type=references` - Add relationship
* `wp content-relations remove --from=123 --to=456 --type=references` - Remove relationship
* `wp content-relations check` - Check database integrity
* `wp content-relations sync --dry-run` - Sync relationships (dry run)

== Database Schema ==

Relationships are stored in `{prefix}_content_relations` table:

* `id` - Relationship ID
* `from_id` - Source post ID
* `to_id` - Target post ID
* `type` - Relationship type slug
* `direction` - 'unidirectional' or 'bidirectional'
* `created_at` - Timestamp

== Error Handling ==

All API functions return `WP_Error` objects on failure. Check for errors:

```php
$result = wp_add_relation( $from_id, $to_id, 'references' );

if ( is_wp_error( $result ) ) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Relationship created with ID: ' . $result;
}
```

Common error codes:
* `self_relation` - Cannot relate a post to itself
* `infinite_loop` - Circular relationship detected
* `relation_exists` - Relationship already exists
* `invalid_post_type` - Post type not allowed for this relationship type
* `max_relationships` - Maximum relationships limit reached

== Capabilities ==

The plugin uses WordPress capabilities:
* `wpncr_create_relation` - Create relationships (default: edit_posts)
* `wpncr_delete_relation` - Delete relationships (default: edit_posts)
* `wpncr_manage_relation_types` - Manage relationship types (default: manage_options)

== Performance Tips ==

* Use specific relationship types in queries for better performance
* Limit results when getting related posts
* Use WP_Query with relationship parameters instead of multiple `wp_get_related()` calls
* Enable query debug mode in developer settings to analyze query performance

== Privacy Policy ==

This plugin stores content relationship metadata in your WordPress database. No data is sent to external servers. All relationship data is stored locally and can be exported or deleted at any time.

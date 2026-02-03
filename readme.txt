=== Native Content Relationships ===
Contributors: chetanupare
Tags: relationships, content, posts, pages, custom-post-types, users, terms, many-to-many
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.11
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://buymeacoffee.com/chetanupare

Add first-class content relationships to WordPress using a dedicated, indexed database table. Supports posts-to-posts, posts-to-users, posts-to-terms, and their reverse relationships.

== Description ==

WordPress does not provide a first-class way to relate content items (posts, pages, custom post types, media, users, terms). Many implementations rely on post meta or taxonomies, which can be limiting for querying, directionality, and long-term maintainability.

Native Content Relationships provides a structured relationship layer with semantic relationship types, so relationships are:

* Queryable (WP_Query integration)
* Scalable (indexed database table)
* Semantic (typed relationships)
* Direction-aware (one-way or bidirectional)
* Validated (helps prevent invalid data)
* Theme and editor agnostic
* **User-aware** (posts-to-users and users-to-posts)
* **Term-aware** (posts-to-terms and terms-to-posts)

= Key Features =

* **Relationship API**: Clean PHP functions (`wp_add_relation()`, `wp_get_related()`, `wp_is_related()`)
* **Complete Relationship Support**: Posts, users, and terms with full bidirectional support
* **Database Layer**: Custom indexed table for fast queries
* **Admin UI**: Modern interface for managing post, user, and term relationships
* **Query Integration**: Extend WP_Query with relationship support
* **REST API**: Full REST endpoints for headless WordPress
* **WooCommerce**: Optional integration with product relationships
* **ACF Migration**: One-time migration from ACF relationship fields
* **Multilingual**: WPML/Polylang support with relationship mirroring
* **Developer Tools**: Fluent API, WP-CLI commands, query debugging
* **Page Builder Support**: Gutenberg block and Elementor dynamic tags

= Use Cases =

**Post-to-Post Relationships:**
* Products → Accessories
* Courses → Lessons
* Documentation → Related articles
* Articles → Sources
* Media → Usage tracking

**Post-to-User Relationships:**
* Posts → Favorite posts (users favorite content)
* Posts → Bookmarked content
* Posts → Multiple authors/contributors
* Posts → Assigned team members

**Post-to-Term Relationships:**
* Posts → Categorized in specific categories
* Posts → Tagged with specific tags
* Posts → Featured in special collections
* Posts → Grouped by custom taxonomies

**User-to-Post & Term-to-Post Relationships:**
* Users → Authored posts
* Users → Bookmarked posts
* Users → Favorite content
* Terms → Related posts (reverse queries)
* Categories → Featured posts

= Architecture =

This plugin uses a proper relational database table instead of post meta, providing:

* 2,500x faster queries than post meta at 10k posts
* Bidirectional queries (forward and reverse)
* Scalable to millions of relationships
* Database-native logic
* **User relationship support** with optimized indexes
* **Term relationship support** with optimized indexes
* **Multi-type relationships** in a single, unified system

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

= User Relationships =

This plugin now supports full user-to-post and post-to-user relationships, providing functionality similar to Posts 2 Posts but with a modern architecture.

**Built-in User Relationship Types:**
* **Favorite Posts**: Users can mark posts as favorites
* **Bookmarked By**: Users can bookmark content for later
* **Authored By**: Multiple authors/contributors per post

**User Interface:**
* **User Profile**: Manage related posts from user profile screen
* **Post Editor**: Manage related users from post edit screen
* **AJAX Search**: Real-time search for users and posts
* **Modern UI**: Clean, responsive interface matching WordPress admin style

**API Examples:**
```php
// Add a user's favorite post
wp_add_relation( $user_id, $post_id, 'favorite_posts', null, 'post' );

// Get a user's favorite posts
$posts = wp_get_related_users( $user_id, 'favorite_posts' );

// Get users who favorited a post
$users = wp_get_related( $post_id, 'favorite_posts', array(), 'user' );

// Add a post-to-term relationship
wp_add_relation( $post_id, $term_id, 'categorized_as', null, 'term' );

// Get a post's related terms
$terms = wp_get_related_terms( $post_id, 'categorized_as' );

// Get a term's related posts
$posts = wp_get_term_related_posts( $term_id, 'categorized_as' );

// Get all related items (posts, users, terms)
$all = wp_get_related( $post_id, null, array(), 'all' );
```

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

1. Upload the plugin files to `/wp-content/plugins/native-content-relationships/`
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

= Does this support user relationships? =

Yes! The plugin supports full posts-to-users and users-to-posts relationships with a modern admin interface, similar to Posts 2 Posts but with better performance and UX.

= Does this support term relationships? =

Yes! The plugin supports full posts-to-terms and terms-to-posts relationships, allowing you to create semantic connections beyond WordPress's native taxonomy system. This includes categories, tags, and custom taxonomies.

= How is this different from Posts 2 Posts? =

Native Content Relationships provides:
* Modern architecture with semantic relationship types
* Better performance (indexed database table)
* User relationships support
* Term relationships support
* Modern admin interface with AJAX
* REST API endpoints
* WooCommerce integration
* Better validation and integrity checks
* Active development and maintenance

= How is this different from MB Relationships? =

Native Content Relationships provides:
* Posts-to-Terms support (MB Relationships doesn't support this)
* User relationships support (MB Relationships doesn't support this)
* Semantic relationship types vs generic connections
* Better performance and validation
* Modern admin interface
* Active development and maintenance

= Does this send data externally? =

No. This plugin stores all relationship data locally in your WordPress database and does not send any data externally.

== Screenshots ==

1. Settings screen with tabbed interface.
2. Relationship overview screen.
3. User profile with related posts management.
4. Post editor with user relationships management.
5. Term editor with related posts management.

== Changelog ==

= 1.0.11 =
* **NEW**: Full posts-to-terms and terms-to-posts relationships support
* **NEW**: Term editor metabox for managing related posts
* **NEW**: Built-in term relationship types (categorized_as, tagged_with, featured_in)
* **NEW**: Term relationship API functions (wp_get_related_terms, wp_get_term_related_posts)
* **NEW**: AJAX-powered search for terms in addition to users and posts
* **NEW**: Unified relationship system supporting posts, users, and terms
* **IMPROVED**: Database schema with optimized indexes for all relationship types
* **IMPROVED**: Updated readme with comprehensive term relationship documentation

= 1.0.10 =
* **NEW**: Full posts-to-users and users-to-posts relationships support
* **NEW**: User profile metabox for managing related posts
* **NEW**: Post editor metabox for managing related users
* **NEW**: Built-in user relationship types (favorite_posts, bookmarked_by, authored_by)
* **NEW**: AJAX-powered search for users and posts
* **NEW**: Modern admin interface matching WordPress style
* **NEW**: User relationship API functions (wp_get_related_users, wp_get_user_related_posts)
* **IMPROVED**: Database schema with optimized indexes for user relationships
* **IMPROVED**: Updated readme with comprehensive user relationship documentation

= 1.0.6 =
* Maintenance release.

= 1.0.0 =
* Initial release
* Core relationship engine with custom database table
* Admin meta box for managing relationships
* WP_Query integration
* REST API endpoints
* WooCommerce integration
* ACF migration tool

== Contributing ==

Contributions are welcome! Please feel free to submit a Pull Request on GitHub:

**GitHub Repository:** https://github.com/chetanupare/WP-Native-Content-Relationships

For bug reports and feature requests, please use the GitHub Issues page.

== License ==

This plugin is licensed under the GPLv2 or later.

== Credits ==

* Developed by [Chetan Upare](https://buymeacoffee.com/chetanupare)
* Built with modern WordPress standards and best practices
* Compatible with the latest WordPress versions
* WPML/Polylang support
* Gutenberg block
* WP-CLI commands
* Fluent PHP API
* Query debug mode
* Import/export functionality

== Upgrade Notice ==

= 1.0.6 =
Maintenance release.

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
    wp_remove_relation( $from_id, $to_id, $type )
    * Parameters: Source post ID, target post ID, relationship type
    * Returns: Boolean or WP_Error

== Fluent API ==

For a more chainable, IDE-friendly API:

    // Create a relationship
    naticore()
        ->from( 123 )
        ->to( 456 )
        ->type( 'references' )
        ->create();

    // Get related posts
    $related = naticore()
        ->from( 123 )
        ->type( 'references' )
        ->get( array( 'limit' => 10 ) );

    // Check if related
    $is_related = naticore()
        ->from( 123 )
        ->to( 456 )
        ->type( 'references' )
        ->exists();

    // Remove relationship
    naticore()
        ->from( 123 )
        ->to( 456 )
        ->type( 'references' )
        ->remove();

== Registering Custom Relationship Types ==

Register your own relationship types with:

    register_content_relation_type( 'custom_type', array(
        'label'            => 'Custom Relationship',
        'bidirectional'    => false,
        'allowed_post_types' => array( 'post', 'page' ),
    ) );

Hook into `naticore_register_relation_types` action:

    add_action( 'naticore_register_relation_types', function() {
        register_content_relation_type( 'part_of', array(
            'label'            => 'Part Of',
            'bidirectional'    => false,
            'allowed_post_types' => array( 'post', 'page' ),
        ) );
    } );

== WP_Query Integration ==

Query posts by relationships:

    $query = new WP_Query( array(
        'post_type'         => 'post',
        'content_relation'  => array(
            'post_id'    => 123,
            'type'       => 'references',
            'direction'  => 'outgoing', // or 'incoming' or 'both'
        ),
    ) );

Or use the cleaner syntax:

    $query = new WP_Query( array(
        'post_type' => 'post',
        'naticore'  => array(
            'from' => 123,
            'type' => 'references',
        ),
    ) );

== REST API ==

The plugin exposes REST endpoints at `/wp-json/naticore/v1/`:

* `GET /wp-json/naticore/v1/relations/{post_id}` - Get all relationships for a post
* `POST /wp-json/naticore/v1/relations` - Create a relationship
* `DELETE /wp-json/naticore/v1/relations/{relation_id}` - Delete a relationship

== Hooks and Filters ==

**Actions:**
* `naticore_register_relation_types` - Register custom relationship types
* `naticore_relation_added` - Fires after a relationship is created
* `naticore_relation_removed` - Fires after a relationship is removed

**Filters:**
* `naticore_relation_is_allowed` - Modify whether a relationship is allowed
* `naticore_get_related_args` - Modify arguments for get_related queries
* `naticore_relation_types` - Modify registered relationship types

== Examples ==

**Link a product to accessories:**
    $product_id = 123;
    $accessory_ids = array( 456, 789, 101 );

    foreach ( $accessory_ids as $accessory_id ) {
        wp_add_relation( $product_id, $accessory_id, 'accessory_of' );
    }

**Get all related posts:**
    $related = wp_get_related( get_the_ID(), 'related_to' );
    foreach ( $related as $post ) {
        echo '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
    }

**Query posts that reference the current post:**
    $query = new WP_Query( array(
        'content_relation' => array(
            'post_id'    => get_the_ID(),
            'type'       => 'references',
            'direction'  => 'incoming',
        ),
    ) );

**Check if two posts are related:**
    if ( wp_is_related( $post_id_1, $post_id_2, 'related_to' ) ) {
        echo 'These posts are related!';
    }

== WP-CLI Commands ==

Manage relationships via command line:

* `wp naticore list --post=123` - List relationships for a post
* `wp naticore add --from=123 --to=456 --type=references` - Add relationship
* `wp naticore remove --from=123 --to=456 --type=references` - Remove relationship
* `wp naticore check` - Check database integrity
* `wp naticore sync --dry-run` - Sync relationships (dry run)

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

    $result = wp_add_relation( $from_id, $to_id, 'references' );

    if ( is_wp_error( $result ) ) {
        echo 'Error: ' . $result->get_error_message();
    } else {
        echo 'Relationship created with ID: ' . $result;
    }

Common error codes:
* `self_relation` - Cannot relate a post to itself
* `infinite_loop` - Circular relationship detected
* `relation_exists` - Relationship already exists
* `invalid_post_type` - Post type not allowed for this relationship type
* `max_relationships` - Maximum relationships limit reached

== Capabilities ==

The plugin uses WordPress capabilities:
* `naticore_create_relation` - Create relationships (default: edit_posts)
* `naticore_delete_relation` - Delete relationships (default: edit_posts)
* `naticore_manage_relation_types` - Manage relationship types (default: manage_options)

== Performance Tips ==

* Use specific relationship types in queries for better performance
* Limit results when getting related posts
* Use WP_Query with relationship parameters instead of multiple `wp_get_related()` calls
* Enable query debug mode in developer settings to analyze query performance

== Privacy Policy ==

This plugin stores content relationship metadata in your WordPress database. No data is sent to external servers. All relationship data is stored locally and can be exported or deleted at any time.

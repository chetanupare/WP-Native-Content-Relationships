=== Native Content Relationships ===
Contributors: chetanupare
Tags: relationships, content, posts, users, ai
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.4.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://buymeacoffee.com/chetanupare
Plugin URI: https://chetanupare.github.io/WP-Native-Content-Relationships/

WP relationship plugin with AI-powered suggestions, visual graph, and analytics. Connect content visually or let AI find connections for you.

== Description ==

**The first WordPress relationship plugin that thinks for you.**

Native Content Relationships connects your posts, users, and terms with a visual drag-and-drop interface. But here's what makes it different from every other relationship plugin:

**AI-powered suggestions analyze your content and recommend the best connections automatically.**

No other relationship plugin does this. ACF makes you pick items manually. JetEngine makes you configure field groups. MB Relationships makes you write code. Native Content Relationships reads your content and suggests what should be connected.

=== Features No Other Plugin Has ===

**1. AI-Powered Suggestions**
Click "Suggest related" and the plugin analyzes your content using WordPress 7.0 AI Client. It finds the most relevant posts based on meaning, not just keywords.

**2. AI Auto-Link on Publish**
When you publish a post, the plugin automatically creates relationships with the most relevant existing content. No manual work needed.

**3. Visual Relationship Graph**
See how your content is connected. An interactive force-directed graph shows all relationships with color-coded nodes. Drag nodes, zoom, filter by post type.

**4. Analytics Dashboard**
Know your content health at a glance. See total relationships, connected posts, orphaned content, most referenced posts, and activity over time.

**5. Carousel Template**
Display related content in a beautiful carousel with `[naticore_related_carousel]`. Includes prev/next buttons and optional autoplay.

=== How It Works ===

**Step 1: Install and activate.** Database tables create automatically. No configuration needed.

**Step 2: Open any post.** Find the "Relationships" box below the editor.

**Step 3: Click "Suggest related."** The plugin analyzes your content and shows the most relevant posts, complete with thumbnails.

**Step 4: Click to connect.** One click adds the relationship. Drag to reorder.

**Step 5: Display on frontend.** Use the Gutenberg block, Elementor dynamic tags, shortcodes, or widget.

=== AI-Powered Suggestions (WordPress 7.0+) ===

When you click "Suggest related," the plugin sends your post's title, content, and topics to an AI model through the WordPress 7.0 AI Client. The AI analyzes semantic similarity and returns the most relevant posts from your site.

**What the AI considers:**
* Post title and content meaning (not just keywords)
* Topic and category overlap
* Content freshness and relevance
* Existing relationships (won't suggest what's already connected)

**Requirements:**
* WordPress 7.0 or later
* An AI provider configured in Settings > Connectors (OpenAI, Anthropic, or Google)

**No AI provider?** The plugin falls back to category and tag matching. It still works great — AI just makes it smarter.

=== AI Auto-Link on Publish ===

Enable this feature and the plugin works for you automatically. When you publish a post:

1. AI analyzes the content
2. Finds the top 5 most related existing posts
3. Creates relationships automatically
4. Shows a notice: "5 relationships created. View or edit."

No other relationship plugin does this.

=== Visual Relationship Graph ===

Go to **Relationships > Graph** to see a visual map of how your content is connected.

* **Force-directed layout** — Nodes arrange themselves based on connections
* **Color-coded by type** — Posts (blue), Users (green), Terms (yellow), Pages (red)
* **Draggable nodes** — Rearrange the graph manually
* **Zoom and pan** — Scroll to zoom, drag to pan
* **Filter by type** — Show only posts, pages, or users
* **Click to edit** — Click any node to open it in the editor

=== Analytics Dashboard ===

Go to **Relationships > Analytics** to see:

* **Summary cards** — Total relationships, connected posts, orphaned posts, avg per post
* **Relationships by type** — Visual breakdown with percentage bars
* **Most referenced posts** — Which content gets linked to most
* **Most connected posts** — Which content links out most
* **Activity over time** — Relationship creation trend (last 30 days)

=== Bulk Relationship Manager ===

Go to **Relationships > Bulk Manager** to:

* View all relationships in a table
* Select multiple relationships with checkboxes
* Bulk delete relationships
* Bulk change relationship type
* Paginated for large datasets

=== Visual Relationship Builder ===

* **Search with thumbnails** — Type in the search box and see post thumbnails as you type
* **Drag-and-drop sorting** — Reorder relationships by dragging
* **One-way or bidirectional** — Choose which direction the relationship flows
* **User profile relationships** — Connect users to their favorite posts, bookmarks, or contributions
* **Term relationships** — Link categories, tags, or custom taxonomies to content

=== Page Builder Integration ===

**Gutenberg:**
* "Related Content" block with live preview in the editor
* Shows actual related content, not a placeholder
* Configurable: number of posts, layout, thumbnails, excerpts

**Elementor:**
* Dynamic tags for Related Posts, Related Users, Related Terms
* Works with any Elementor widget
* No configuration needed — auto-detected

**Shortcodes:**
`[naticore_related_posts type="related_to" limit="5"]`
`[naticore_related_users type="authored_by" limit="10"]`
`[naticore_related_terms type="categorized_as" limit="5"]`
`[naticore_related_carousel type="related_to" limit="10" autoplay="1"]`

**Widget:**
"Related Content (NCR)" — add to any sidebar or widget area.

=== WooCommerce ===

Link products to accessories, guides, or related items. Syncs with WooCommerce upsells and cross-sells if enabled.

=== Why Choose This Over ACF or JetEngine? ===

| Feature | Native Content Relationships | ACF Pro | JetEngine | MB Relationships |
|---|---|---|---|---|
| AI-powered suggestions | Yes | No | No | No |
| AI auto-link on publish | Yes | No | No | No |
| Visual relationship graph | Yes | No | No | No |
| Analytics dashboard | Yes | No | No | No |
| Bulk relationship manager | Yes | Yes | No | No |
| Carousel template | Yes | No | No | No |
| Visual drag-and-drop | Yes | Yes | Yes | No |
| Post-to-user relationships | Yes | No | Yes | Yes |
| Post-to-term relationships | Yes | No | Yes | Yes |
| Custom database table | Yes | No | Yes | Yes |
| No plugin dependency | Yes | No | No | Yes |
| Gutenberg live preview | Yes | No | No | No |
| Free version | Yes | Yes | No | Yes |

=== Performance ===

* Custom database table with composite indexes — not slow post meta queries
* Sub-2ms P95 latency even with 1,000,000+ relationships
* Object cache compatible
* Lightweight — no bloat, no unnecessary features

[View Performance Report](https://github.com/chetanupare/WP-Native-Content-Relationships/blob/main/docs/PERFORMANCE.md)

== Key Features ==

• AI-powered relationship suggestions (WordPress 7.0+)  
• AI auto-link on publish  
• Visual relationship graph with force-directed layout  
• Analytics dashboard with statistics  
• Bulk relationship manager  
• Carousel template with autoplay  
• Visual search with thumbnails in the post editor  
• Drag-and-drop relationship sorting  
• One-way or bidirectional relationships  
• Gutenberg block with live preview  
• Elementor dynamic tags for posts, users, and terms  
• Shortcodes and widgets  
• Many-to-many relationships between posts, users, and terms  
• Custom database table with composite indexes  
• WooCommerce product relationships  
• REST API for headless setups  
• Multilingual-ready (WPML / Polylang)  
• Works with any theme — no template overrides  

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

== Installation ==

1. Upload the plugin to `/wp-content/plugins/native-content-relationships/`
2. Activate the plugin from the Plugins menu
3. Database tables are created automatically
4. For AI suggestions: go to Settings > Connectors and add an AI provider (OpenAI, Anthropic, or Google)
5. Open any post and click "Suggest related" to see AI in action

== Frequently Asked Questions ==

= Does this replace WooCommerce linked products? =
No. It complements WooCommerce and can optionally sync relationships.

= Can I migrate from ACF relationship fields? =
Yes. A one-time migration tool is included.

= Does this work with page builders? =
Yes. The plugin works with Elementor, Gutenberg, and any theme.

= Does this support users and terms? =
Yes. Full support for post–user and post–term relationships is included.

= Does this send data externally? =
Only if you enable AI suggestions. When enabled, post content is sent to your configured AI provider for analysis. No data is sent if AI is disabled.

= Do I need an AI provider? =
No. The plugin works without AI. AI suggestions are optional and require WordPress 7.0+ with a configured provider.

= How is this different from ACF relationship fields? =
ACF requires you to configure field groups and manually select each related post. Native Content Relationships gives you a visual search interface and optional AI suggestions that recommend connections automatically.

= How is this different from JetEngine? =
JetEngine is a full suite with listing grids, dynamic data, and relationships. Native Content Relationships is focused purely on relationships — simpler, lighter, and faster.

= What is the relationship graph? =
The graph is a visual map showing how your content is connected. Go to Relationships > Graph to see it. Nodes represent posts, users, and terms. Lines show relationships between them.

= How does auto-link work? =
When enabled, the plugin analyzes each new post on publish and automatically creates relationships with the most relevant existing content. You see a notice after publishing showing how many relationships were created.

== Screenshots ==

1. Settings screen  
2. Relationship overview  

== Changelog ==

= 1.4.1 =
* Feature: Relationship constraints — define which content type combinations are allowed
* Feature: Cardinality rules — set max connection limits (one-to-one, one-to-many)
* Feature: Relationship status workflow — track states (Applied → Shortlisted → Interviewed → Hired)
* Feature: Relationship expiration — auto-deactivate relationships after a date
* Feature: Role-based permissions — control who can create/edit/delete each relationship type
* Feature: Relationship cloning — clone posts with all their relationships
* Feature: Webhooks — fire HTTP POST requests on relationship changes (created, updated, deleted)
* Feature: Caching layer — intelligent caching for relationship queries with auto-invalidation
* Improved: Duplicate detection prevents creating the same relationship twice
* Improved: Schema version bumped to 1.4

= 1.3.0 =
* Feature: Relationship metadata UI — add roles and notes to each relationship in the post editor
* Feature: 8 preset templates (Event/Speaker, Course/Instructor, Product/Brand, Job/Skill, Candidate/Job, Author, Portfolio, Series)
* Feature: Revision history tracking — see who added/removed relationships and when
* Feature: Bidirectional auto-sync — metadata automatically syncs between bidirectional relationships
* Feature: GraphQL support for WPGraphQL (headless WordPress)
* Improved: Settings toggle for bidirectional sync
* Improved: Schema version bumped to 1.3

= 1.2.0 =
* Feature: AI auto-link on publish — automatically create relationships when publishing
* Feature: Visual relationship graph with force-directed layout
* Feature: Analytics dashboard with statistics and charts
* Feature: Bulk relationship manager with delete and type change
* Feature: Carousel template with [naticore_related_carousel] shortcode
* Improved: Admin menu reorganized with Graph, Bulk Manager, and Analytics pages

= 1.1.0 =
* Feature: AI-powered relationship suggestions using WordPress 7.0 AI Client
* Feature: Settings option to enable/disable AI suggestions (Settings > Content Relationships)
* Feature: AI analyzes post content for semantic relationship suggestions
* Improved: Falls back to category/tag matching when AI is unavailable
* Improved: Visual AI badge in admin when suggestions come from AI

= 1.0.31 =
* Fix: CSS class name mismatch in admin meta box (ncore- → naticore-) — meta box now styles correctly
* Improved: Search results now show post thumbnails for better visual identification
* Improved: Gutenberg block now shows live preview of related content instead of placeholder
* Improved: Readme rewritten for better clarity and user focus
* Updated: Tested up to WordPress 7.0

= 1.0.30 =
* WordPress 7.0 compatibility: Updated Tested up to 7.0
* Fix: Remove IF NOT EXISTS from dbDelta() to ensure schema updates work correctly
* Fix: Add DEFAULT NULL to nullable columns in database schema
* Improved: Renamed wp_* global functions to ncr_* prefix (backward-compatible wrappers retained)
* Improved: Replaced wp_verify_nonce() with hash_equals() pattern for modern nonce verification
* Improved: Replaced json_encode() with wp_json_encode() for WordPress coding standards

== Developer Guide ==

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

```
new WP_Query( array(
    'post_type' => 'post',
    'content_relation' => array(
        'post_id' => 123,
        'type' => 'related_to',
    ),
) );
```

= REST API =

Endpoints under `/wp-json/naticore/v1/`

= Hooks & Filters =

Actions:
* `naticore_relation_added`
* `naticore_relation_removed`

= WP-CLI =

* List relationships
* Add / remove relationships
* Integrity checks

== Stability & Backward Compatibility ==

**Schema stable from 1.x onward. Backward compatibility guaranteed.**

* Database schema stable — no breaking changes in 1.x
* Public PHP APIs stable — functions, hooks, and endpoints won't break
* Versioning policy — 1.x = stable core

== ACF Migration ==

If you store relationships in Advanced Custom Fields or Post Meta, you can migrate to Native Content Relationships for better performance.
[Migration guide](https://github.com/chetanupare/WP-Native-Content-Relationships/blob/main/docs/migration/from-acf.md)

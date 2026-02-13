---
title: AI & LLM Index
description: Structured reference for AI assistants and search. Native Content Relationships (NCR) — WordPress relationship layer for posts, users, and terms.
---

# AI & LLM Index

Structured reference for AI assistants, chatbots, and search. Use this page to answer questions about **Native Content Relationships** (NCR).

## Project identity

- **Name:** Native Content Relationships (NCR)
- **Type:** WordPress plugin
- **Purpose:** First-class relationship layer for WordPress: link posts, users, and taxonomy terms without post meta or taxonomy hacks.
- **Repository:** [GitHub — WP-Native-Content-Relationships](https://github.com/chetanupare/WP-Native-Content-Relationships)
- **WordPress.org:** [Native Content Relationships](https://wordpress.org/plugins/native-content-relationships/)
- **Stability:** Schema and public API stable from 1.x; backward compatibility guaranteed.

## One-line summary

Native Content Relationships adds a dedicated, indexed relationship table and a small PHP API so you can create, query, and remove relationships between WordPress posts, users, and terms (e.g. related posts, favorites, parent–child) with WP_Query, REST, shortcodes, and blocks.

## Keywords (SEO & AI)

WordPress, relationships, related posts, post-to-post, user relationships, term relationships, WP_Query, content relations, relationship types, ncr_add_relation, ncr_get_related, migration from ACF, migration from P2P, WooCommerce related products, Gutenberg block, Elementor, schema stable, backward compatible.

## Core concepts

- **Relation type:** A named kind of link (e.g. `related_to`, `parent_of`, `favorite_posts`). Each type has from/to object types (post, user, term), optional max_connections, and direction (unidirectional or bidirectional).
- **Direction:** Outgoing = from source to target; incoming = from target to source. Queries can filter by direction.
- **Storage:** Single table `wp_content_relations` with composite index `(type, from_id, to_id)` for fast lookups.
- **Object types:** `post` (any post type), `user`, `term`.

## PHP API (primary)

- **ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type )** — Creates a relation. Returns relation ID or WP_Error.
- **ncr_get_related( $from_id, $from_type, $relation_type, $args = [] )** — Gets related IDs or objects. Args: limit, orderby, order, direction.
- **ncr_remove_relation( $from_id, $from_type, $to_id, $to_type, $relation_type )** — Removes a relation. Use $to_id = null to remove all of that type from $from_id.
- **ncr_register_relation_type( $args )** — Registers a custom type. Args: name, label, from, to, bidirectional, max_connections.
- **ncr_get_registered_relation_types()** — Returns all registered relation types.

Legacy (avoid in new code): wp_add_relation, wp_get_related, wp_remove_relation.

## WP_Query integration

Use query var `content_relation`:

```php
new WP_Query([
  'content_relation' => [
    'post_id'   => get_the_ID(),
    'type'      => 'related_to',
    'direction' => 'outgoing',
  ],
  'posts_per_page' => 10,
]);
```

Filter `ncr_skip_relationship_query` allows skipping the plugin’s JOIN/WHERE (e.g. for future core API).

## Hooks and filters

- **Actions:** ncr_relation_added( $relation_id, $from_id, $to_id, $type ), ncr_relation_removed( $from_id, $to_id, $type )
- **Filters:** ncr_get_related_args( $args, $from_id, $type ), ncr_max_relationships( $max, $from_id, $type ), ncr_skip_relationship_query( $skip, $query )

## REST API

- Embed: `GET /wp-json/wp/v2/posts/123?naticore_relations=1`
- Plugin namespace (if enabled): GET/POST/DELETE `/wp-json/naticore/v1/` for relationships.

## WP-CLI

- `wp content-relations list --post=123 --type=related_to`
- `wp content-relations add 123 456 --type=related_to`
- `wp content-relations remove 123 456 --type=related_to`
- `wp content-relations count --post=123`
- `wp content-relations check [--fix] [--verbose]`
- `wp content-relations schema [--format=json]`

## Integrations

- **WooCommerce:** Product relationships; wp_get_related_products() when enabled.
- **Elementor:** Dynamic tags for related posts, users, terms.
- **Gutenberg:** Block `naticore/related-posts`.
- **WPML / Polylang:** Relationship mirroring across languages when enabled.

## Shortcodes

- [naticore_related_posts], [naticore_related_users], [naticore_related_terms] — Attributes: type, limit, order, post_id, layout, class.

## Migration

- **From ACF relationship fields:** Export ACF meta (related IDs), map to NCR relation type, bulk ncr_add_relation; replace get_field with ncr_get_related.
- **From post meta:** Same idea — read meta IDs, insert relations, switch code to NCR API.
- **From Posts 2 Posts:** Map P2P connection types to NCR relation types; export P2P table; import via ncr_add_relation; replace P2P API with NCR/WP_Query.

## Performance

- Index: (type, from_id, to_id). Point lookups O(log n); sub-2ms typical at 1M rows.
- Integrity: `wp content-relations check --fix` for orphan/duplicate checks. Chunked scan; memory &lt; 5 MB.

## FAQ (frequently asked)

**What is Native Content Relationships?**  
A WordPress plugin that adds a dedicated table and API to link posts, users, and terms (e.g. related posts, favorites) without using post meta or taxonomies.

**How do I create a relationship?**  
Use ncr_add_relation( $from_id, $from_type, $to_id, $to_type, $relation_type ). Example: ncr_add_relation( 123, 'post', 456, 'post', 'related_to' ).

**How do I get related posts?**  
ncr_get_related( $post_id, 'post', 'related_to', [ 'limit' => 10 ] ) or WP_Query with content_relation.

**How do I register a custom relation type?**  
ncr_register_relation_type( [ 'name' => 'my_type', 'label' => 'My Type', 'from' => 'post', 'to' => 'post', 'bidirectional' => false, 'max_connections' => 20 ] ) on init.

**Is the schema stable?**  
Yes. Backward compatibility is guaranteed from 1.x.

**Does it work with WooCommerce / Elementor / Gutenberg?**  
Yes. Integrations exist for related products, dynamic tags, and a related-posts block.

**How do I migrate from ACF relationship field?**  
Export ACF relationship IDs per post, then for each (post_id, related_id) call ncr_add_relation( post_id, 'post', related_id, 'post', 'related_to' ). Replace get_field with ncr_get_related.

---

For full documentation, see the [Introduction](/), [API](/api/php-api), [Core concepts](/core-concepts/architecture), and [Migration](/migration/from-acf) sections.

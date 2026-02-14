---
title: FAQ
description: Frequently asked questions — WooCommerce, ACF migration, page builders, users & terms, data privacy.
---

# FAQ

## Does this replace WooCommerce linked products?

No. NCR is independent of WooCommerce. It can **complement** WooCommerce: you can link products to other products (e.g. accessories) with NCR and optionally use the [WooCommerce integration](/integrations/woocommerce) to sync or mirror relationships. WooCommerce’s own linked/upsell/cross-sell features remain separate.

## Can I migrate from ACF relationship fields?

Yes. A one-time migration tool is included. Use it to copy relationship data from ACF relationship fields into NCR’s table. See [Migration from ACF](/migration/from-acf).

## Does this work with page builders?

Yes. NCR is editor-agnostic:

- **Gutenberg** — “Related Content” block with relationship type selector.
- **Elementor** — Dynamic Tags for related posts, users, and terms.
- Shortcodes work in any editor that supports shortcodes.

See [Gutenberg](/integrations/gutenberg) and [Elementor](/integrations/elementor).

## Does it support users and terms, not just posts?

Yes. You can create:

- **Post ↔ Post** — related posts, parent/child, etc.
- **Post ↔ User** — e.g. favorites, bookmarks, authored by.
- **Post ↔ Term** — e.g. categorized as, featured in collection.

Relation types define `from_type` and `to_type` (`post`, `user`, `term`). See [Relationship types](/core-concepts/relationship-types) and [PHP API](/api/php-api).

## Does NCR send data externally?

No. All relationship data is stored in your WordPress database in the `wp_content_relations` table. No external APIs or tracking.

## What relationship types are built in?

- **Post–post:** `related_to`, `parent_of`, `depends_on`, `references` (and you can register more).
- **User–post:** `favorite_posts`, `bookmarked_by`, `authored_by`.
- **Post–term:** `categorized_as`.

You can register custom types with `ncr_register_relation_type()`. See [PHP API](/api/php-api#ncr_register_relation_type).

## Is the schema stable? Will upgrades break my site?

**Schema stable from 1.x onward.** Backward compatibility is guaranteed in the 1.x line. The relationship table and public PHP API (functions, WP_Query args, shortcodes, REST) will not change in breaking ways. Any future schema change will be additive or come with a documented migration. See the [stability promise](/guide/introduction#why-ncr) and the plugin readme.

## Can I use NCR in a headless setup?

Yes. Use the [REST API](/api/rest-api) to create, read, and delete relationships. You can also embed relations in core REST responses with `?naticore_relations=1` on posts, users, and terms endpoints.

## Where is the admin UI?

- **Posts/Pages:** In the post editor (sidebar or meta box), under the relationship panel. Search and link posts, users, or terms by relationship type.
- **Users:** In the user profile (or edit user) screen, relationship section.
- **Terms:** In the term edit screen where the integration is enabled.

Settings (relation types, post types) are under **Settings → Content Relationships**.

## See also

- [Troubleshooting](/guide/troubleshooting) — Empty results, permissions, REST
- [Introduction](/guide/introduction) — What NCR is and why use it

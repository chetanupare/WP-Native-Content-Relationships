---
title: WooCommerce
---

# WooCommerce

Native Content Relationships can drive product relationships (related products, upsells, etc.) when the WooCommerce integration is enabled.

## Setup

1. Install and activate WooCommerce and Native Content Relationships.
2. In **Settings → Content Relationships**, enable the WooCommerce integration and choose which object types (e.g. products) can use relations.

## Usage

- Use the same [PHP API](/api/php-api) and relation types for products.
- When the integration is enabled, `wp_get_related_products( $product_id, $type, $args )` is available.
- Relationship data can be synced to WooCommerce’s linked products (upsell/cross-sell) when configured.

See the plugin’s WooCommerce integration class and settings for sync options and supported types.

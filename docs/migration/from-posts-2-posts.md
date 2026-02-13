---
title: Migrating from Posts 2 Posts
---

# Migrating from Posts 2 Posts

If you use the **Posts 2 Posts** (P2P) plugin, you can migrate relationship data to Native Content Relationships.

## Comparison

- **P2P** uses a custom table and defines “connection types” with direction and cardinality.
- **NCR** uses a single relations table and “relation types” with `from`/`to` object types and optional `max_connections`.

## Approach

1. **Map connection types** — For each P2P connection type, define an NCR relation type (same or similar name) with [ncr_register_relation_type](/extending/custom-types). Map P2P “from”/“to” post types to NCR `from`/`to` (use `post` for any post type unless you need user/term).
2. **Export P2P data** — P2P stores rows in its table (e.g. `p2p_id`, `p2p_from`, `p2p_to`, `p2p_type`). Use a SQL export or P2P’s API to get (from_id, to_id, type).
3. **Import** — For each row, call `ncr_add_relation( $from_id, 'post', $to_id, 'post', $mapped_type )`. Handle duplicate prevention if your script runs multiple times.
4. **Replace code** — Replace P2P API calls with [ncr_get_related](/api/php-api) and [WP_Query](/api/wp-query) using `content_relation`. Update templates and any P2P-specific shortcodes or blocks to NCR equivalents.
5. **Deactivate P2P** — After verification, deactivate and optionally uninstall P2P.

## Copy-paste migration example

**Step 1 — Export P2P data.** If you have DB access, you can export (from the P2P table, e.g. `wp_p2p`) rows with `p2p_from`, `p2p_to`, `p2p_type`. Save to a CSV or PHP array.

**Step 2 — Map P2P type to NCR type.** Register the NCR type if needed (see [Custom types](/extending/custom-types)), e.g. map P2P `my_connection` → NCR `my_connection`.

**Step 3 — Import.** Example: you have an array of rows `[ 'from' => id, 'to' => id, 'type' => 'my_connection' ]`.

```php
<?php
// migrate-p2p-to-ncr.php — run once. $p2p_rows = your exported data.
if ( ! function_exists( 'ncr_add_relation' ) ) {
	return;
}
$p2p_rows = [ /* e.g. from DB or CSV: [ ['from'=>123,'to'=>456,'type'=>'my_connection'], ... ] */ ];
$type_map = [ 'my_connection' => 'my_connection' ]; // P2P type => NCR type

foreach ( $p2p_rows as $row ) {
	$ncr_type = $type_map[ $row['type'] ] ?? $row['type'];
	$from = (int) $row['from'];
	$to   = (int) $row['to'];
	if ( $from && $to && get_post_status( $from ) && get_post_status( $to ) ) {
		ncr_add_relation( $from, 'post', $to, 'post', $ncr_type );
	}
}
```

**Before (P2P):** `p2p_get_related_posts( $post_id, 'my_connection' )` or similar.  
**After (NCR):** `ncr_get_related( $post_id, 'post', 'my_connection' )` and [WP_Query](/api/wp-query) with `content_relation`.

## Notes

- P2P supports multiple connection types per pair; NCR supports multiple relation types. Map each P2P connection type to one NCR relation type.
- Run `wp content-relations check` and spot-check front-end and admin before removing P2P.

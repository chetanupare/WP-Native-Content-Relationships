---
title: Using NCR on Your Blog
description: Related posts, recommended reads, and series for blog owners. Copy-paste ready.
---

# Using NCR on Your Blog

Native Content Relationships fits blogs well: **related posts**, **recommended reads**, and **series** without post meta or custom code. Use the UI, shortcode, or block—no PHP required for basics.

## What you can do

| Use case | How |
|----------|-----|
| **Related posts** | Link posts manually in the editor; show them with shortcode or block. |
| **Recommended reads** | Same as related—add a type "recommended" and display per post. |
| **Series** | Use a relation type like `part_of` (post → post) and list "Others in this series." |
| **Author favorites** | User → post relation; list "Editor’s picks" from a user ID. |

## 1. In the editor (no code)

1. Edit a **post**.
2. Open the **Content relationships** meta box.
3. Choose **Related to** (or your custom type).
4. Search and add the posts you want.
5. **Update** the post.

Repeat for each post where you want related content. Use **Suggest related** to get one-click suggestions by category/tag.

## 2. Show related posts (copy-paste)

### Shortcode (in post content or widget)

```text
[naticore_related_posts type="related_to" limit="5" layout="list"]
```

Grid of 6 with a custom class:

```text
[naticore_related_posts type="related_to" limit="6" layout="grid" class="related-blog-posts"]
```

### Block

Add the **Related Content** block (search “Related” or “Naticore”). Set type to **Related to**, limit, and layout (list/grid).

### Theme template (single post)

Drop this in `single.php` or your single-post template to output a “Related posts” list:

```php
<?php
$related = ncr_get_related( get_the_ID(), 'post', 'related_to', [ 'limit' => 5 ] );
if ( ! empty( $related ) ) :
	?>
	<aside class="related-posts">
		<h3><?php esc_html_e( 'Related posts', 'your-textdomain' ); ?></h3>
		<ul>
			<?php foreach ( $related as $post_id ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</aside>
<?php endif; ?>
```

## 3. Blog-specific relation types (optional)

If you want “Recommended reads” separate from “Related to”, register a type (e.g. in a small mu-plugin or your theme):

```php
add_action( 'init', function() {
	ncr_register_relation_type( [
		'name'            => 'recommended_reads',
		'label'           => __( 'Recommended reads', 'your-textdomain' ),
		'from'            => 'post',
		'to'              => 'post',
		'bidirectional'   => false,
		'max_connections' => 10,
	] );
}, 20 );
```

Then in the meta box you’ll see **Recommended reads**; use `type="recommended_reads"` in the shortcode.

## 4. Sidebar widget

**Appearance → Widgets**: add **Related content** (Naticore). Set relation type (e.g. **Related to**), limit, and which post to use (default: current). Works for blog sidebars without blocks.

## 5. CLI for bulk (optional)

To add or inspect relations from the command line (e.g. after migrating from another plugin):

```bash
# Count related_to relations for post 123
wp content-relations count --post=123 --type=related_to

# List them
wp content-relations list --post=123 --type=related_to --format=json
```

See [WP-CLI](/api/wp-cli) and [Snippets](/getting-started/snippets) for more.

---

**Next:** [Snippets](/getting-started/snippets) for more copy-paste code, or [Basic relationships](/getting-started/basic-relationships) for the full API.

# Includes structure

Plugin PHP includes are grouped by role. **Do not change paths** without updating every `require_once` in the main plugin file (`native-content-relationships.php`) and any internal references.

## Directories

| Directory       | Purpose | Files |
|----------------|---------|--------|
| **core/**      | Always loaded; database, API, admin, query, REST, capabilities, cleanup, settings | class-database.php, class-relation-types.php, class-settings.php, class-api.php, class-admin.php, class-query.php, class-rest-api.php, class-capabilities.php, class-cleanup.php |
| **frontend/**  | Shortcodes, widget, fluent API | class-shortcodes.php, class-widget.php, class-fluent-api.php |
| **integrations/** | Third-party: WooCommerce, ACF, WPML, SEO, Gutenberg/Elementor | class-woocommerce.php, class-acf.php, class-wpml.php, class-seo.php, class-editors.php |
| **user/**      | User relationship UI and AJAX | class-user-relations.php, class-user-relations-ajax.php |
| **tools/**     | Overview, import/export, site health, integrity, orphaned, auto-relations | class-overview.php, class-import-export.php, class-site-health.php, class-integrity.php, class-orphaned.php, class-auto-relations.php, class-settings-old.php |
| **tools/helpers/** | Integrity helper functions | integrity-helpers.php |
| **cli/**       | WP-CLI commands | class-wp-cli.php |
| **elementor/** | Elementor dynamic tags and AJAX (unchanged) | class-elementor-integration.php, class-ajax-handler.php, class-related-*-tag.php |

## Loading order

1. **core/** – loaded in `load_includes()` (and activation uses core/class-database.php, core/class-relation-types.php).
2. Optional components – loaded in `init()` with `file_exists()` checks; paths use `NATICORE_PLUGIN_DIR . 'includes/<dir>/...'`.

## Internal references

- `includes/tools/class-integrity.php` loads `helpers/integrity-helpers.php` via `plugin_dir_path( __FILE__ ) . 'helpers/integrity-helpers.php'` (resolves to `includes/tools/helpers/integrity-helpers.php`).
- `includes/integrations/class-editors.php` may require `includes/elementor/class-related-content-tag.php` (fallback exists in same file if missing).

---
title: SEO (Yoast & Rank Math)
description: Internal linking and schema integration with Yoast SEO and Rank Math.
---

# SEO Integration

When **Yoast SEO** or **Rank Math** is active, NCR can expose relationships as **internal links** and add **schema** references so relationships are visible to search engines and SEO tools.

---

## Supported plugins

- **Yoast SEO** — detected via `WPSEO_VERSION` or `WPSEO_Options`
- **Rank Math** — detected via `RANK_MATH_VERSION` or `RankMath` class

If neither is active, NCR does not add SEO-specific output.

---

## Internal links

NCR filters **post content** (`the_content`) to add internal links based on relationships (e.g. related posts). This helps SEO and discovery.

- **Filter:** `naticore_seo_internal_links` — you can modify or disable the list of links before they are applied.
- **Behavior:** Relationship data is passed to the SEO layer so related URLs can be included in internal linking logic.

---

## Schema (structured data)

- **Yoast:** NCR adds graph pieces via `wpseo_schema_graph_pieces` so relationship data can appear in the schema output.
- **Rank Math:** NCR hooks into `rank_math/schema/validated` to add relationship references.

Details depend on the SEO plugin’s schema API; NCR integrates so that relationship context is available for Article, WebPage, or custom types.

---

## Use case

Sites using Yoast or Rank Math get:

- Better internal linking from relationship data.
- Optional schema representation of relations (e.g. “related to” or “part of”) for rich results or SEO tools.

No configuration required; enable an SEO plugin and NCR will hook in when detected.

---

## See also

- [REST API](/api/rest-api) — Headless and embed options
- [PHP API](/api/php-api) — Query relations in templates

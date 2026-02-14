# SEO checklist (docs site)

The VitePress config and theme already include the following. Use this list to verify or add assets.

## Already configured

- **Document title** — `titleTemplate: '%s | Native Content Relationships'` so every page gets a proper `<title>`.
- **Meta description** — Default in config; per-page from frontmatter `description:` (and fallback to site description).
- **Canonical URL** — Per-page canonical in `transformHead` (prevents duplicate indexing).
- **Robots** — `index, follow` in head.
- **Open Graph** — `og:title`, `og:description`, `og:url`, `og:image`, `og:image:alt`, `og:image:type`, `og:type`, `og:locale`, `og:site_name` (default + per-page).
- **Twitter Card** — `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`, `twitter:image:alt`.
- **Favicon** — Primary icon: `wordpress-logo-svgrepo-com.svg` (SVG). Links for `favicon.ico` and `apple-touch-icon.png` are in head; add those files to `docs/public/` for full support.
- **Theme color** — `#0d9488` (meta theme-color + manifest).
- **Viewport** — `width=device-width, initial-scale=1`.
- **Author** — `Native Content Relationships`.
- **JSON-LD** — Organization, WebSite, SoftwareApplication, SoftwareSourceCode (site-level); TechArticle per page with `dateModified` when `lastUpdated` is set; **BreadcrumbList** on every page (in `transformHead`); **FAQPage** on the FAQ page for FAQ rich results.
- **Breadcrumbs** — BreadcrumbList structured data is injected in `transformHead` so every page has breadcrumb JSON-LD (Home → section → page).
- **FAQ schema** — The FAQ page (`/guide/faq`) gets FAQPage JSON-LD with main Q&As so it’s eligible for FAQ rich results in search.
- **Internal linking** — “See also” blocks on key docs (e.g. [PHP API](/api/php-api), [Quick Start](/guide/quick-start), [Introduction](/guide/introduction)) link to other relevant pages.
- **Sitemap** — Generated at `/sitemap.xml`; linked in head and in `robots.txt`.
- **Manifest** — `manifest.webmanifest` with name, short_name, description, icons, theme_color.

## Optional improvements

1. **favicon.ico** — Add a 32×32 or 16×16 ICO to `docs/public/favicon.ico` for older browsers and some bookmarks.
2. **apple-touch-icon.png** — Add a 180×180 PNG to `docs/public/apple-touch-icon.png` for iOS home screen.
3. **OG image for social** — When you have a branded image, add a 1200×630 PNG to `docs/public/og-image.png` and set `og:image` in `transformHead` to `SITE_URL + '/og-image.png'` (some platforms render PNG/JPEG better than SVG).

## Per-page frontmatter

Ensure important pages have:

```yaml
---
title: Page Title
description: Short, unique description for search and social (under ~160 chars).
---
```

The theme uses `title` for the document title and OG/Twitter title, and `description` for meta description and OG/Twitter description.

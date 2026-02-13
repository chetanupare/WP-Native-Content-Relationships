# Native Content Relationships — Documentation

This folder is the source for the **VitePress** documentation site.

## Develop locally

From the **plugin root** (one level up):

```bash
npm install
npm run docs:dev
```

Open the URL VitePress prints (e.g. with base path `/WP-Native-Content-Relationships/` for GitHub Pages).

## Build for production

```bash
npm run docs:build
```

Output is in `docs/.vitepress/dist`. Deploy that folder to GitHub Pages or any static host.

## Structure

| Path | Purpose |
|------|--------|
| `index.md` | Home page |
| `guide/getting-started.md` | First steps |
| `technical/architecture.md` | System design, components |
| `technical/performance.md` | Benchmarks, scaling |
| `product/features.md` | Feature overview |
| `internal/expansion-plan.md` | 90-day plan (internal) |
| `.vitepress/config.mts` | VitePress config |
| `public/` | Static assets (logo, etc.) |

Original long-form docs (e.g. `ARCHITECTURE.md`, `PERFORMANCE.md`, `MUST_HAVE_FEATURES.md`, `90-DAY-EXPANSION-PLAN.md`) remain in this folder as canonical sources; the VitePress pages either mirror them or summarize and link.

## Legacy

The old Jekyll site (`_config.yml`, `*.html` pages) is no longer used. The live site is built with VitePress. The HTML files are kept for reference only.

## Links

- [WordPress.org Plugin](https://wordpress.org/plugins/native-content-relationships/)
- [GitHub](https://github.com/chetanupare/WP-Native-Content-Relationships)

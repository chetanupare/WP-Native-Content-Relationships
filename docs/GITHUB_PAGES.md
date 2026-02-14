# GitHub Pages setup

Docs are **VitePress**. The **GitHub Pages** workflow builds the site and deploys it via **GitHub Actions** (upload-pages-artifact + deploy-pages). You must set the Pages source to **GitHub Actions** so the built-in Jekyll job is not used (it fails with "No such file or directory - /github/workspace/docs" when triggered by branch updates).

## Required: Use GitHub Actions as source

1. In the repo go to **Settings → Pages**.
2. Under **Build and deployment**:
   - **Source:** **GitHub Actions**
3. Click **Save**.

The first time you use this, you may need to approve the **github-pages** environment (Actions run → Environment → Approve).

## How it works

- The **GitHub Pages** workflow runs on push to `main` (when `docs/` or workflow/package files change) or via **Run workflow**.
- It builds with `npm run docs:build`, uploads the `docs/.vitepress/dist` folder as a Pages artifact, then the **deploy** job deploys it. No Jekyll, no gh-pages branch push.
- The built-in "pages build and deployment" (Jekyll) will not be used for your site once the source is **GitHub Actions**.

## If the site is 404 or blank

- In **Actions**, confirm the **GitHub Pages** workflow run succeeded (both **build** and **deploy** jobs).
- In **Settings → Pages**, confirm **Source** is **GitHub Actions**.
- If the deploy job is waiting for approval, approve the **github-pages** environment.
- Wait 1–2 minutes after a successful run.

**Manual deploy:** Actions → **GitHub Pages** → **Run workflow**.

## Sitemap & Search Console

- **Sitemap URL (submit this in Google Search Console):**  
  `https://chetanupare.github.io/WP-Native-Content-Relationships/sitemap.xml`
- The sitemap is **auto-generated** by VitePress on each build and includes **all pages**: home, **blog** (index + all posts), guide, API, integrations, migration, performance, tools, etc. Entries are sorted (index → blog → guide → api → rest).
- **robots.txt** in the build already contains:  
  `Sitemap: https://chetanupare.github.io/WP-Native-Content-Relationships/sitemap.xml`
- If Search Console says "Sitemap could not be read", confirm the URL above returns XML (not 404 or HTML). After a fresh deploy, wait a few minutes and try again.

# GitHub Pages setup

Docs are **VitePress**. The **GitHub Pages** workflow builds the site and pushes it to the **gh-pages** branch. The built output includes **.nojekyll** so GitHub serves static files and does not run Jekyll.

## Required: Deploy from gh-pages branch

1. In the repo go to **Settings → Pages**.
2. Under **Build and deployment**:
   - **Source:** **Deploy from a branch**
   - **Branch:** **gh-pages**
   - **Folder:** **/ (root)**
3. Click **Save**.

No environment approval or "GitHub Actions" source is needed. After the workflow runs (on push to `main` when `docs/` or package files change), the site is available at your Pages URL.

## How it works

- The **GitHub Pages** workflow runs on push to `main` (when `docs/` or workflow/package files change) or via **Run workflow**.
- It builds with `npm run docs:build`, adds `.nojekyll` to the output, and pushes `docs/.vitepress/dist` to the **gh-pages** branch.
- GitHub serves the branch; `.nojekyll` ensures static files are served (no Jekyll).

## If the site is 404 or blank

- In **Actions**, confirm the **GitHub Pages** workflow run succeeded.
- In **Settings → Pages**, confirm **Source** is **Deploy from a branch**, **Branch** is **gh-pages**, **Folder** is **/ (root)**.
- Wait 1–2 minutes after a successful run.

**Manual deploy:** Actions → **GitHub Pages** → **Run workflow**.

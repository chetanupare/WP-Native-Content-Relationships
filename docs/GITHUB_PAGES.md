# GitHub Pages setup

Docs are **VitePress**, not Jekyll. The live site is built by the **GitHub Pages** workflow and deployed to the **gh-pages** branch.

## Required: Use the gh-pages branch

1. In the repo go to **Settings → Pages**.
2. Under **Build and deployment**:
   - **Source:** Deploy from a branch
   - **Branch:** choose **gh-pages** (not `main`)
   - **Folder:** **/ (root)**
3. Click **Save**.

**Do not** set the source to **main** with folder **/docs**. That makes GitHub run **Jekyll** on the `docs/` folder, which fails (this repo is VitePress, not Jekyll).

## How it works

- The **GitHub Pages** workflow runs on push to `main` (when `docs/` or package files change).
- It runs `npm run docs:build` and pushes the contents of `docs/.vitepress/dist` to the **gh-pages** branch.
- GitHub Pages then serves that branch. No Jekyll run.

## If the site is blank or 404

- In **Actions**, confirm the **GitHub Pages** workflow run succeeded.
- In **Settings → Pages**, confirm the branch is **gh-pages** and the folder is **/ (root)**.

**Manual deploy:** Actions → **GitHub Pages** → **Run workflow**.

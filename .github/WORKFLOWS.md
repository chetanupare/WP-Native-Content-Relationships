# Workflows in this repo

This repo defines **only 2 workflows**:

| Workflow file | Purpose |
|---------------|--------|
| **lint.yml** | PHPCS + PHPStan on push/PR to `main` |
| **pages.yml** | Build VitePress docs and push to **gh-pages** (static site; .nojekyll skips Jekyll) |

## Why you still see more runs in the Actions tab

- **CodeQL** — Run by **GitHub's "Code scanning" default setup**, not by a file in this repo. To stop it: **Settings → Code security and analysis → Code scanning** (or **Security → Code security**) and turn off **Default setup** / CodeQL analysis.
- **pages-build-deployment** — Runs when the **gh-pages** branch is updated. With **.nojekyll** in that branch, Jekyll is skipped and static files are served.
- **Dependabot Updates** — From **Settings → Code security → Dependabot** (or `.github/dependabot.yml`). To stop it, disable Dependabot or remove `dependabot.yml`.

Use **Settings → Pages → Deploy from a branch → gh-pages → / (root)** so the site is served from the branch our workflow updates.

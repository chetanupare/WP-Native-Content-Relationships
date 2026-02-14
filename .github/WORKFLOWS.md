# Workflows in this repo

This repo defines **only 2 workflows**:

| Workflow file | Purpose |
|---------------|--------|
| **lint.yml** | PHPCS + PHPStan on push/PR to `main` |
| **pages.yml** | Build VitePress docs and push to `gh-pages` (on `docs/` or package changes) |

## Why you still see more runs in the Actions tab

- **CodeQL** — Run by **GitHub’s “Code scanning” default setup**, not by a file in this repo. To stop it: **Settings → Code security and analysis → Code scanning** (or **Security → Code security**) and turn off **Default setup** / CodeQL analysis.
- **pages-build-deployment** — Run by **GitHub** when the `gh-pages` branch is updated. You can’t remove it; it’s how Pages deploys when the source is “Deploy from a branch”.
- **Dependabot Updates** — From **Settings → Code security → Dependabot** (or `.github/dependabot.yml`). To stop it, disable Dependabot or remove `dependabot.yml`.

After disabling CodeQL (and optionally Dependabot), the only workflows you’ll see from this repo are **Lint** and **GitHub Pages**; **pages-build-deployment** will still run when `gh-pages` is updated.

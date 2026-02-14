#!/usr/bin/env bash
# Rebuild docs and push to deploy (GitHub Actions will build and deploy on push to main).
set -e
cd "$(dirname "$0")/.."
echo "Building docs..."
npm run docs:build
echo "Build OK. Staging and committing..."
# Repo root = plugin dir (native-content-relationships); we're already here
if [ -f "native-content-relationships.php" ]; then
  git add docs/.vitepress/config.mts
  git add scripts/rebuild-and-deploy.sh
  if git diff --cached --quiet; then
    echo "No config changes to commit."
  else
    git commit -m "docs: cleanUrls true, align sitemap and canonicals for SEO"
    git push origin main
    echo "Pushed to main. GitHub Actions will deploy."
  fi
else
  echo "Repo root not found (expected native-content-relationships.php in current dir). Run from plugin root. Current: $(pwd)"
  exit 1
fi

---
title: Multilingual (WPML / Polylang)
---

# Multilingual

Native Content Relationships can mirror relationships across languages when used with WPML or Polylang.

## Supported

- **WPML** — Detected via `ICL_SITEPRESS_VERSION` or `SitePress` class. When the integration is enabled, relationship changes can be mirrored to translated posts/users/terms according to the plugin’s logic.
- **Polylang** — Similar integration for language pairs.

## Behavior

- Creating or removing a relation on one language version can trigger sync to the corresponding translation (when the integration is enabled and configured).
- Querying related content respects the current language context where the integration provides it.

Check **Settings → Content Relationships** for multilingual options and the integration classes in the plugin for exact behavior and filters.

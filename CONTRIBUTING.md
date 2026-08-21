# Contributing to Native Content Relationships

Thank you for your interest in contributing to Native Content Relationships! This document provides guidelines and information to help you get started.

## Table of Contents

- [Quick Start (< 10 Minutes)](#quick-start)
- [Development Setup](#development-setup)
- [Project Architecture](#project-architecture)
- [Testing & Quality](#testing--quality)
- [Submitting Changes](#submitting-changes)
- [Good First Issues](#good-first-issues)

## Quick Start

Get up and running in under 10 minutes.

### Prerequisites

- PHP 7.4+
- Composer
- Local WordPress environment (LocalWP, DevKinsta, Docker, etc.)

### 1. Clone & Install

```bash
cd wp-content/plugins/
git clone https://github.com/chetanupare/WP-Native-Content-Relationships.git native-content-relationships
cd native-content-relationships
composer install
```

### 2. Verify Install

Activate the plugin and check the status:

```bash
wp plugin activate native-content-relationships
wp content-relations check
```

**That's it!** You are ready to code.

## Development Setup

### Project Structure

```text
native-content-relationships/
├── includes/                 # Core PHP logic
│   ├── class-api.php        # Public API
│   ├── class-integrity.php  # Integrity Engine
│   └── ...
├── assets/                   # Frontend assets (JS/CSS)
├── languages/               # Translation files (.pot, .po, .mo)
├── tests/                   # Automated tests
├── docs/                    # Architecture & Performance docs
├── .github/                 # CI Workflows
└── readme.txt              # Plugin directory readme
```

### Architecture Overview

See [Architecture Documentation](docs/ARCHITECTURE.md) for a detailed diagram of the Registry, DB Layer, and Integrity Engine.

### Schema Guard

This plugin uses a **Schema Versioning Guard** to ensure database consistency.
- Defined in `NATICORE_SCHEMA_VERSION` constant.
- If you modify the database schema, you must increment this version.
- The `ncr_maybe_upgrade_db()` function runs on `init` to apply changes.

## Testing & Quality

We use `composer` scripts to make testing easy.

### 1. Linting & Static Analysis

Before submitting a PR, run:

```bash
# Check code style (PHPCS)
composer lint

# Fix code style automatically (PHPCBF)
composer fix

# Static Analysis (PHPStan)
composer analyze
```

### 2. Integrity Engine Tests

The plugin includes a robust integrity engine. You can test it via WP-CLI:

```bash
# Run a dry-run integrity check
wp content-relations check --verbose

# Run a repair operation
wp content-relations check --fix --verbose
```

### 3. Regression Tests

Run the automated regression suite to verify core logic:

```bash
wp eval-file tests/regression-suite.php
```

## Submitting Changes

1. **Branch**: Create a feature branch (e.g., `feature/add-new-type` or `fix/integrity-bug`).
2. **Commit**: Use descriptive commit messages.
3. **Verify**: Run `composer test` to ensure all checks pass.
4. **Pull Request**: Submit to `main`. CI will automatically run tests.

## Good First Issues

We label issues as "good first issue" for new contributors. These are scoped, well-documented, and safe to tackle.

[View Good First Issues](https://github.com/chetanupare/WP-Native-Content-Relationships/issues?q=is%3Aopen+is%3Aissue+label%3A%22good+first+issue%22)

## License

GPLv2 or later.

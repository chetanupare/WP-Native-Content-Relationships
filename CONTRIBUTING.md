# Contributing to Native Content Relationships

Thank you for your interest in contributing to Native Content Relationships! This document provides guidelines and information to help you get started.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Code Standards](#code-standards)
- [Submitting Changes](#submitting-changes)
- [Reporting Issues](#reporting-issues)
- [Good First Issues](#good-first-issues)

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Git
- Composer (for development dependencies)
- Node.js and npm (for frontend assets)

### Setting Up Your Development Environment

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/WP-Native-Content-Relationships.git
   cd WP-Native-Content-Relationships
   ```
3. Add the upstream repository:
   ```bash
   git remote add upstream https://github.com/chetanupare/WP-Native-Content-Relationships.git
   ```
4. Install development dependencies:
   ```bash
   composer install
   npm install
   ```

## Development Setup

### Running Tests

1. **PHP CodeSniffer (PHPCS):**
   ```bash
   vendor/bin/phpcs --standard=WordPress --extensions=php includes/
   ```

2. **PHP CodeSniffer Fixer:**
   ```bash
   vendor/bin/phpcbf --standard=WordPress --extensions=php includes/
   ```

3. **JavaScript Linting:**
   ```bash
   npm run lint
   ```

### Project Structure

```
WP-Native-Content-Relationships/
├── includes/                 # Core PHP files
├── assets/                   # Frontend assets
│   ├── js/                  # JavaScript files
│   └── css/                 # CSS files
├── assets/templates/        # Template files
├── languages/               # Translation files
├── .github/workflows/      # GitHub Actions
└── readme.txt              # WordPress plugin readme
```

## Code Standards

### PHP Standards

We follow WordPress Coding Standards:

1. **Indentation:** Use tabs, not spaces
2. **Naming Conventions:** Use `NATICORE_` prefix for functions/classes
3. **Documentation:** All functions and classes must have PHPDoc comments
4. **Security:** Always sanitize and validate input
5. **Database:** Use `$wpdb->prepare()` for all queries

### Example PHP Code

```php
/**
 * Example function with proper documentation
 *
 * @since 1.0.10
 * @param int $post_id The post ID.
 * @return array|false Array of relationships or false on failure.
 */
function naticore_get_relationships( $post_id ) {
	if ( empty( $post_id ) ) {
		return false;
	}
	
	global $wpdb;
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}content_relations WHERE from_id = %d",
			$post_id
		)
	);
	
	return $results;
}
```

### JavaScript Standards

1. **Indentation:** Use tabs
2. **Comments:** JSDoc style for functions
3. **jQuery:** Use jQuery instead of $
4. **Security:** Always escape output

### Example JavaScript Code

```javascript
/**
 * Initialize the admin interface
 *
 * @since 1.0.10
 */
jQuery( document ).ready(
	function () {
		'use strict';

		// Initialize functionality
		naticoreAdmin.init();
	}
);
```

## Submitting Changes

### Branch Naming

Use descriptive branch names:
- `fix/fix-some-bug`
- `feature/add-new-feature`
- `docs/update-documentation`

### Commit Messages

Follow conventional commit format:
- `fix: Fix the relationship deletion bug`
- `feat: Add user relationship support`
- `docs: Update contributing guidelines`

### Pull Request Process

1. Create a new branch from `main`
2. Make your changes
3. Run tests and ensure they pass
4. Commit your changes with clear messages
5. Push to your fork
6. Create a pull request with:
   - Clear title and description
   - Reference any related issues
   - Screenshots if applicable

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] PHPCS passes
- [ ] Tested in WordPress admin
- [ ] Tested with different post types

## Checklist
- [ ] Code follows WordPress standards
- [ ] Self-review of the code
- [ ] Documentation updated
- [ ] No breaking changes
```

## Reporting Issues

### Bug Reports

When reporting bugs, please include:
1. WordPress version
2. Plugin version
3. PHP version
4. Steps to reproduce
5. Expected vs actual behavior
6. Screenshots if applicable

### Feature Requests

For feature requests, please include:
1. Clear description of the feature
2. Use case and benefits
3. Possible implementation approach
4. Any relevant examples or references

## Good First Issues

We label issues as "good first issue" for new contributors. These issues:
- Are well-documented
- Have clear success criteria
- Don't require deep architectural knowledge
- Provide immediate impact

Look for the "good first issue" label in the [Issues tab](https://github.com/chetanupare/WP-Native-Content-Relationships/issues?q=is%3Aopen+is%3Aissue+label%3A%22good+first+issue%22).

## Development Guidelines

### Security

- Always validate and sanitize user input
- Use WordPress nonce verification
- Follow WordPress security best practices
- Use `$wpdb->prepare()` for database queries

### Performance

- Use WordPress caching functions
- Optimize database queries
- Avoid expensive operations in hooks
- Use appropriate WordPress APIs

### Internationalization

- Use WordPress i18n functions: `__()`, `_e()`, `_x()`
- Always include text domain: `'native-content-relationships'`
- Make strings translatable and context-aware

### Testing

- Test in different WordPress versions
- Test with different PHP versions
- Test with various plugins active
- Test multisite compatibility

## Getting Help

- **GitHub Issues:** [Report bugs or request features](https://github.com/chetanupare/WP-Native-Content-Relationships/issues)
- **Discussions:** [Ask questions or share ideas](https://github.com/chetanupare/WP-Native-Content-Relationships/discussions)
- **WordPress.org:** [Plugin support forum](https://wordpress.org/support/plugin/native-content-relationships/)

## Code of Conduct

Please be respectful and professional in all interactions. Follow the [WordPress Code of Conduct](https://wordpress.org/about/code-of-conduct/).

## License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (GPLv2 or later).

---

Thank you for contributing to Native Content Relationships! Your contributions help make WordPress better for everyone.

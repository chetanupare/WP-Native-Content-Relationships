# Changelog

All notable changes to Native Content Relationships will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.17] - 2026-02-11

### Refined
- Standardized relationship error codes with `ncr_` prefix (e.g., `ncr_max_connections_exceeded`)
- Implemented relationship registry locking to prevent registrations after `init:20` for improved security
- Added architectural documentation regarding future atomic write considerations in `NATICORE_API`

## [1.0.16] - 2026-02-11

### Added
- Formal Relationship Type Registry helper `ncr_get_registered_relation_types()`
- Enforced directional logic in `add_relation` based on type registry
- Support for `max_connections` constraint to limit relationships per type
- REST API endpoint `GET /naticore/v1/types` to expose formal registry

### Improved
- Bypassed cache for connection constraint checks to ensure real-time enforcement
- Enhanced REST API validation for relationship creation

## [1.0.15] - 2026-02-11

### Added
- Formal Relationship Type Registration API (ncr_register_relation_type)
- Strict schema validation for relationship definitions
- Validation layer for preventing invalid object types and combinations

### Improved
- Refactored internal relationship type registration mapping
- Enhanced developer API with a formal registry

## [1.0.14] - 2026-02-11

### Added
- Dedicated Import/Export tab in settings
- Empty state UI for Relationship Overview page
- Post-activation admin notice with quick links
- Quick link to documentation (https://chetanupare.github.io/WP-Native-Content-Relationships/)

### Changed
- Moved Relationship Overview from Tools to Settings menu
- Improved layout organization in settings page

### Fixed
- CSS specificity issues affecting table headers in settings cards

## [1.0.13] - 2026-02-09

### Added
- GitHub Issues badge in readme
- Comprehensive CONTRIBUTING.md guidelines
- Security policy and vulnerability reporting
- Issue templates and automation
- Good first issues for new contributors

### Fixed
- CodeQL syntax errors and duplicate workflows
- Inline comment formatting throughout codebase
- Documentation and PHPDoc improvements
- Deprecated function usage (file_get_contents → WP_Filesystem)
- I18n text domain inconsistencies

### Changed
- Updated Elementor tag names from ncr- to naticore- prefix
- Improved code quality standards compliance
- Enhanced GitHub repository setup

## [1.0.12] - 2026-02-04

### Added
- Elementor dynamic tags for related posts, users, and terms
- AJAX handlers for Elementor integration
- User relationship management in admin interface
- Import/export functionality for relationships
- WooCommerce integration support
- WPML and Polylang multilingual support
- Advanced search and filtering capabilities
- Bulk operations for relationship management
- Performance optimizations with caching
- Enhanced security with nonce verification

### Fixed
- Database query optimization
- Permission checks for all operations
- Memory usage improvements
- Compatibility with WordPress 6.9
- PHP 8.x compatibility fixes

### Changed
- Improved admin interface design
- Enhanced relationship type management
- Better error handling and user feedback
- Optimized database schema

## [1.0.11] - 2026-01-15

### Added
- Gutenberg block integration
- REST API endpoints for relationships
- Advanced relationship querying
- Relationship validation and integrity checks
- Orphaned relationship detection and cleanup
- Developer hooks and filters
- Performance monitoring tools

### Fixed
- Relationship deletion edge cases
- Import/export data validation
- Memory leaks in large datasets
- Compatibility with PHP 7.4+

### Changed
- Improved database query performance
- Enhanced error reporting
- Better compatibility with other plugins

## [1.0.10] - 2025-12-01

### Added
- User relationship support
- Term relationship support
- Bidirectional relationship types
- Relationship type management interface
- Import/export functionality
- Bulk relationship operations
- Advanced search and filtering

### Fixed
- Relationship creation validation
- Permission checking improvements
- Database query optimization
- UI responsiveness issues

### Changed
- Redesigned admin interface
- Improved relationship type system
- Enhanced performance for large datasets

## [1.0.9] - 2025-10-15

### Added
- Elementor integration
- Dynamic tags for Elementor
- AJAX-powered relationship management
- Real-time relationship updates
- Enhanced admin interface

### Fixed
- Relationship deletion confirmation
- UI consistency issues
- Performance improvements
- Compatibility fixes

### Changed
- Modern admin interface design
- Improved user experience
- Better accessibility support

## [1.0.8] - 2025-08-20

### Added
- REST API support
- Webhook integration
- Relationship validation
- Import/export functionality
- Performance monitoring

### Fixed
- Database query optimization
- Memory usage improvements
- Security enhancements
- Compatibility issues

### Changed
- Improved error handling
- Better performance monitoring
- Enhanced security measures

## [1.0.7] - 2025-06-10

### Added
- Advanced search capabilities
- Bulk relationship operations
- Relationship validation
- Import/export tools
- Performance optimizations

### Fixed
- Search functionality bugs
- Bulk operation issues
- Performance problems
- Security vulnerabilities

### Changed
- Improved search performance
- Better bulk operation handling
- Enhanced security measures

## [1.0.6] - 2025-04-05

### Added
- Relationship type management
- Custom relationship types
- Relationship validation
- Import/export functionality
- Performance improvements

### Fixed
- Relationship creation bugs
- Validation issues
- Performance problems
- Security fixes

### Changed
- Improved relationship type system
- Better validation handling
- Enhanced performance

## [1.0.5] - 2025-02-15

### Added
- Admin interface improvements
- Relationship management tools
- Search and filtering
- Bulk operations
- Performance monitoring

### Fixed
- UI consistency issues
- Search functionality bugs
- Performance problems
- Security enhancements

### Changed
- Redesigned admin interface
- Improved user experience
- Better performance

## [1.0.4] - 2024-12-01

### Added
- Advanced relationship features
- Search and filtering
- Bulk operations
- Performance optimizations
- Security enhancements

### Fixed
- Relationship management bugs
- Search functionality issues
- Performance problems
- Security vulnerabilities

### Changed
- Improved relationship management
- Better search functionality
- Enhanced performance

## [1.0.3] - 2024-10-10

### Added
- Enhanced admin interface
- Search capabilities
- Bulk operations
- Performance improvements
- Security fixes

### Fixed
- Admin interface bugs
- Search functionality issues
- Performance problems
- Security vulnerabilities

### Changed
- Improved admin interface
- Better search functionality
- Enhanced performance

## [1.0.2] - 2024-08-15

### Added
- Basic admin interface
- Relationship management
- Search functionality
- Performance optimizations

### Fixed
- Core functionality bugs
- Performance issues
- Security vulnerabilities
- Compatibility problems

### Changed
- Improved core functionality
- Better performance
- Enhanced security

## [1.0.1] - 2024-06-20

### Added
- Initial admin interface
- Basic relationship management
- Search functionality
- Performance improvements

### Fixed
- Initial bugs and issues
- Performance problems
- Security vulnerabilities
- Compatibility issues

### Changed
- Improved initial functionality
- Better performance
- Enhanced security

## [1.0.0] - 2024-04-01

### Added
- Initial release
- Core relationship functionality
- Basic admin interface
- Database schema
- WordPress integration

### Features
- Create relationships between posts
- Query relationships efficiently
- Admin interface for management
- Database optimization
- WordPress standards compliance

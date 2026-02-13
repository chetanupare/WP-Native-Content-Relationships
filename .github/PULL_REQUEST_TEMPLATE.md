## Description
Please include a summary of the changes and the related issue. Also include relevant motivation and context. List any dependencies that are required for this change.

Fixes # (issue)

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update (documentation only changes)

## How Has This Been Tested?
Please describe the tests that you ran to verify your changes. Also list any relevant details for your test configuration.

- [ ] Manual testing in WordPress admin
- [ ] Tested with different post types
- [ ] Tested with different user roles
- [ ] Tested with various relationship types
- [ ] Tested with other plugins active
- [ ] Tested on different WordPress versions

## Test Configuration
- WordPress version: [e.g. 6.9]
- PHP version: [e.g. 7.4]
- Plugins active: [list relevant plugins]
- Browser: [e.g. Chrome, Firefox]

## Checklist
- [ ] My code follows the [WordPress coding standards](https://developer.wordpress.org/coding-standards/)
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published in downstream modules

## Code Quality
- [ ] PHPCS passes: `vendor/bin/phpcs --standard=WordPress --extensions=php includes/`
- [ ] No deprecated functions used
- [ ] All strings are internationalized with correct text domain
- [ ] Security best practices followed
- [ ] Database queries use `$wpdb->prepare()`
- [ ] User capabilities checked appropriately

## Documentation
- [ ] Documentation is updated for new functionality
- [ ] README.md is updated if needed
- [ ] Inline comments are added where necessary
- [ ] User-facing strings are translatable

## Performance
- [ ] No performance regression
- [ ] Database queries are optimized
- [ ] Caching is implemented where appropriate
- [ ] Memory usage is reasonable

## Security
- [ ] All user input is validated and sanitized
- [ ] Nonce verification is implemented for forms
- - [ ] Capability checks are implemented
- [ ] SQL injection protection is in place
- [ ] XSS protection is implemented

## Additional Context
Add any other context about the pull request here.

# GitHub Issues Draft

Copy and paste these into GitHub Issues to help new contributors get started.

---

## Issue 1: Improve inline PHPDoc clarity for `ncr_get_related()`

**Title**: Improve inline PHPDoc clarity for `ncr_get_related()`
**Labels**: `good first issue`, `documentation`

### Description
The `ncr_get_related()` function in `includes/class-api.php` needs better documentation for its return values.

### Acceptance Criteria
- [ ] Update PHPDoc for `ncr_get_related()`
- [ ] Explicitly state what happens when no relations are found (returns empty array? false?)
- [ ] Add an example usage in the docblock.

### File
`includes/class-api.php`

---

## Issue 2: Add unit test for `max_connections` edge case

**Title**: Add unit test for `max_connections` edge case
**Labels**: `good first issue`, `testing`

### Description
We need to verify that `max_connections` strictly enforces the limit.

### Acceptance Criteria
- [ ] Create a test case in `tests/regression-suite.php` (or new unit test file)
- [ ] Set `max_connections` to 1 for a test type.
- [ ] Try to add 2 relationships.
- [ ] Assert the second one fails.

### File
`tests/regression-suite.php`

---

## Issue 3: Improve error message consistency

**Title**: Improve error message consistency in API
**Labels**: `good first issue`, `enhancement`

### Description
Some error messages start with "Error:" and some do not. We should standardize them.

### Acceptance Criteria
- [ ] Review `includes/class-api.php` error return values (WP_Error).
- [ ] Ensure all error messages are sentence-cased and end with a period.
- [ ] Ensure they are translatable.

### File
`includes/class-api.php`

---

## Issue 4: Refactor helper naming in `integrity-helpers.php`

**Title**: Refactor helper naming in `integrity-helpers.php`
**Labels**: `good first issue`, `refactoring`

### Description
Some helper functions might be missing the `ncr_` prefix or have unclear names.

### Acceptance Criteria
- [ ] Audit `includes/helpers/integrity-helpers.php`.
- [ ] Ensure all functions are prefixed with `ncr_`.
- [ ] If renaming, deprecate the old one properly or just update internal usage since it's internal API.

### File
`includes/helpers/integrity-helpers.php`

---

## Issue 5: Add missing type validation tests

**Title**: Add missing type validation tests
**Labels**: `good first issue`, `testing`

### Description
We need to verify that invalid object types (e.g., 'invalid_post_type') are rejected.

### Acceptance Criteria
- [ ] Add a test case that tries to relate a 'post' to a non-existent post type.
- [ ] Assert it returns a `WP_Error` with code `ncr_invalid_type`.

### File
`tests/regression-suite.php`

---

## Issue 6: Improve README wording for "Performance & Scale"

**Title**: Improve README wording for "Performance & Scale"
**Labels**: `good first issue`, `documentation`

### Description
The "Performance & Scale" section in `readme.txt` could be more concise.

### Acceptance Criteria
- [ ] Review `readme.txt`.
- [ ] Suggest clearer phrasing for the memory usage bullet point.
- [ ] Submit a PR with the copy edit.

### File
`readme.txt`

---

## Issue 7: Add benchmark percentile measurement (P99)

**Title**: Add benchmark percentile measurement (P99)
**Labels**: `good first issue`, `enhancement`

### Description
Currently `benchmarks/performance-report.php` measures Mean and P95. Adding P99 would be great for high-scale sites.

### Acceptance Criteria
- [ ] Update `benchmarks/performance-report.php`.
- [ ] Calculate P99 latency.
- [ ] Output it in the CLI report table.

### File
`benchmarks/performance-report.php`

---

## Issue 8: Add clearer specific exception for circular dependency

**Title**: Add clearer specific exception for circular dependency
**Labels**: `good first issue`, `enhancement`

### Description
When a circular dependency is detected, we return a generic error. We should return a specific error code.

### Acceptance Criteria
- [ ] In `includes/class-api.php`, find circular check.
- [ ] Change error code from `ncr_error` to `ncr_circular_dependency`.
- [ ] Ensure message is clear.

### File
`includes/class-api.php`

---

## Issue 9: Document CLI commands in CONTRIBUTING.md

**Title**: Document CLI commands in CONTRIBUTING.md
**Labels**: `good first issue`, `documentation`

### Description
Add a table of all available WP-CLI commands to `CONTRIBUTING.md` or a new `docs/CLI.md`.

### Acceptance Criteria
- [ ] List `wp content-relations check`
- [ ] List `wp content-relations check --fix`
- [ ] Describe arguments and flags.

### File
`CONTRIBUTING.md`

---

## Issue 10: Standardize array return types

**Title**: Standardize array return types
**Labels**: `good first issue`, `cleanup`

### Description
Ensure all internal methods that return lists always return arrays, never null.

### Acceptance Criteria
- [ ] Check `includes/class-database.php` query methods.
- [ ] Ensure `get_results` return value is cast to array if null.
- [ ] Add return type type-hinting if PHP 7.4+ allows (documentation-level at least).

### File
`includes/class-database.php`

# FINAL 1.4.0 RELEASE REPORT

**Date:** 2026-08-21  
**Release Gate Decision:** READY FOR RELEASE (pending browser verification)

---

## Executive Summary

All Phase 6.1 hardening tasks are complete. The plugin passes 40/40 regression tests, PHPStan level 5, PHP syntax checks on all 4 modified files, and all 9 core classes load correctly with 8 REST routes registered.

---

## Fixes Applied

### P0: Bidirectional Cache Invalidation Fix
**File:** `includes/core/class-api.php:668`  
**Bug:** `clear_cache()` only deleted the forward-direction cache key, leaving stale data when the reverse direction was queried.  
**Additional bug discovered during testing:** The null-type wildcard cache key (used when `is_related()` is called without a type) uses PHP's null-to-empty-string interpolation producing `__post` (double underscore), not `_null_post`. The initial fix targeted the wrong key format.  
**Fix:** `clear_cache()` now deletes:
- Forward typed key: `naticore_exists_{from}_{to}_{type}_post`
- Reverse typed key: `naticore_exists_{to}_{from}_{type}_post`
- Forward null-type key: `naticore_exists_{from}_{to}__post`
- Reverse null-type key: `naticore_exists_{to}_{from}__post`

**Verified:** Cache warm → remove → both keys invalidated → `is_related()` returns false without manual flush.

### P1: Input Sanitization
**Files:** `includes/user/class-user-relations.php:321,357` and `includes/core/class-constraints.php:241`

- `$_POST['search']`: Added `sanitize_text_field()` wrapper around existing `wp_unslash()`
- `$_GET['delete_constraint']`: Reordered to sanitize/unslash before use in nonce check, avoiding raw superglobal in nonce action string

### Phase 6.1 Fix (prior session): `NATICORE_Meta_API::get_all_meta()`
**File:** `includes/core/class-meta-api.php:65`  
Implemented missing method wrapping `get_metadata('content_relation', ...)`, fixing fatal errors in bidirectional-sync, cloning, and graphql modules.

---

## Test Results

### Regression Tests: 40/40 PASS
| Category | Tests | Status |
|----------|-------|--------|
| 1. CRUD (Unidirectional) | 6 | PASS |
| 2. Bidirectional | 5 | PASS |
| 3. Cache Invalidation | 6 | PASS |
| 4. Duplicate Prevention | 2 | PASS |
| 5. Cascade | 2 | PASS |
| 6. REST API | 4 | PASS |
| 7. Object Search | 1 | PASS |
| 8. get_all_meta | 1 | PASS |
| 9. Version | 5 | PASS |
| 10. API Contracts | 8 | PASS |

### Static Analysis
- **PHPStan Level 5:** Clean (0 errors)
- **PHP Syntax (4 modified files):** Clean
- **Class Loading:** All 9 core classes verified loaded
- **REST Routes:** 8 endpoints registered

---

## Files Modified in This Session

| File | Change |
|------|--------|
| `includes/core/class-api.php` | Cache invalidation: 4-key bidirectional + null-type wildcard |
| `includes/user/class-user-relations.php` | Added `sanitize_text_field()` to 2 `$_POST['search']` usages |
| `includes/core/class-constraints.php` | Reordered sanitize-then-nonce-check for `$_GET['delete_constraint']` |

---

## Outstanding Items

### Must-complete before 1.4.0 ships:
- [ ] **Gutenberg browser test:** Open `https://testwp.ddev.site/wp-admin/post.php?post=31&action=edit` — verify Relationship sidebar panel appears and is interactive
- [ ] **Classic Editor verification:** Open same URL with Classic Editor — verify metabox renders

### Nice-to-have (not blocking):
- [ ] PHPUnit test suite (no `phpunit.xml` configured)
- [ ] Security matrix (5 roles × 9 endpoints) — current tests verify admin works; need non-admin negative tests

---

## WPDB Notices

The `WordPress.DB.PreparedStatement.NotPrepared` notices during cascade test are from the plugin's raw `$wpdb->query()` calls in `class-cleanup.php` for custom table cleanup. These are pre-existing and do not affect functionality — the queries use hardcoded table names with no user input.

---

## Release Checklist

- [x] All P0/P1 bugs fixed
- [x] 40/40 regression tests pass
- [x] PHPStan level 5 clean
- [x] All files pass PHP syntax check
- [x] All 9 core classes load
- [x] All 8 REST routes registered
- [x] Version 1.4.0 consistent (plugin header, constant, readme.txt, changelog)
- [x] Test files cleaned from trunk
- [ ] Browser verification (requires manual step)

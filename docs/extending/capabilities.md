---
title: Capabilities & Permissions
description: naticore_create_relation, naticore_delete_relation, map_meta_cap, and custom roles.
---

# Capabilities & Permissions

NCR gates **creating** and **deleting** relationships with two meta capabilities. Who can edit the source object (post, user, or term) can manage its relationships.

---

## Meta capabilities

| Capability | Used for | Mapped to |
|------------|----------|-----------|
| `naticore_create_relation` | Adding a relation (from_id → to_id, type) | Edit permission on the **source** object |
| `naticore_delete_relation` | Removing a relation | Same |

**Mapping:**

- **Post** as source → `edit_post` for that post (so authors can manage relations on their own posts, editors on editable posts).
- **User** as source → `edit_user` for that user (so users can manage their own profile relations if they can edit their profile).
- **Term** as source → `edit_term` for that term (so users who can edit the term can manage its relations).

If the source ID is missing, the check falls back to requiring a high-level capability so the UI doesn’t leak.

---

## Allowing or blocking specific relations

**Filter:** `naticore_relation_is_allowed`

```php
add_filter( 'naticore_relation_is_allowed', function ( $allowed, $context ) {
	// $context: [ 'from_id' => int, 'to_id' => int, 'type' => string ]
	if ( $context['type'] === 'favorite_posts' && some_custom_rule( $context ) ) {
		return false;
	}
	return $allowed;
}, 10, 2 );
```

Return `false` to block that relation even if the user has the capability. Use for business rules (e.g. “no relations from this post type to that one”).

---

## Custom roles

If you use custom roles:

- **Grant** `naticore_create_relation` and `naticore_delete_relation` to allow relationship management without mapping to edit_post/edit_user/edit_term; or
- **Leave unmapped:** NCR will map them to the appropriate edit_* meta cap, so anyone who can edit the post/user/term can manage its relations.

For a role that should only manage relationships without editing content, you’d need to add the naticore capabilities and ensure your UI checks those caps.

---

## Test / bypass (development)

When `NCR_TEST_MODE` is defined and true, capability checks are bypassed so tests can create/remove relations without setting up roles. Do not enable in production.

---

## See also

- [Troubleshooting](/guide/troubleshooting#permission-errors-when-creating-relations) — Permission errors
- [Hooks & Filters](/api/hooks-filters) — `naticore_relation_is_allowed`

---
title: WP-CLI
---

# WP-CLI

The plugin registers the `content-relations` command group. All examples assume the relation type is `related_to` unless noted; change `--type=` as needed.

## Quick reference

| Command | Purpose |
|---------|---------|
| `wp content-relations list --post=ID [--type=TYPE]` | List relations for a post |
| `wp content-relations add FROM_ID TO_ID --type=TYPE` | Add one relation |
| `wp content-relations remove FROM_ID TO_ID --type=TYPE` | Remove one relation |
| `wp content-relations count --post=ID [--type=TYPE]` | Count relations |
| `wp content-relations check [--fix] [--verbose]` | Integrity check (optionally fix) |
| `wp content-relations schema [--format=json]` | Export relation type schema |

Run `wp content-relations --help` and `wp content-relations <command> --help` for full options.

---

## List relations

```bash
# All relations for post 123 (table)
wp content-relations list --post=123

# Only type "related_to", output as JSON
wp content-relations list --post=123 --type=related_to --format=json

# CSV for scripting
wp content-relations list --post=123 --format=csv
```

## Add and remove

```bash
# Add: post 123 → post 456, type "related_to"
wp content-relations add 123 456 --type=related_to

# Remove that relation
wp content-relations remove 123 456 --type=related_to
```

## Count and verify

```bash
# Total relations for post 123
wp content-relations count --post=123

# Count only "related_to"
wp content-relations count --post=123 --type=related_to

# Check DB integrity (orphaned rows, missing posts); fix with --fix
wp content-relations check --verbose
wp content-relations check --fix
```

## Schema export

```bash
# All registered relation types as JSON
wp content-relations schema --format=json
```

## Batch import from a file

Create a file `pairs.txt` with one pair per line: `from_id to_id` (e.g. `123 456`). Then:

```bash
while read -r from to; do
  wp content-relations add "$from" "$to" --type=related_to
done < pairs.txt
```

Or with xargs (one pair per line, space-separated):

```bash
cat pairs.txt | xargs -L1 sh -c 'wp content-relations add $1 $2 --type=related_to' _
```

Use after [migration](/migration/from-meta) to bulk-import relations from exported data.

---
title: REST API
---

# REST API

## Embed on core endpoints

Request relations on core post/user/term endpoints:

```
GET /wp-json/wp/v2/posts/123?naticore_relations=1
```

Response includes `naticore_relations`: array of `{ to_id, to_type, type, title? }` (or equivalent for users/terms).

## Plugin namespace

When the plugin registers its REST routes:

- **GET** `/wp-json/naticore/v1/post/{id}` — Relationships for a post
- **POST** `/wp-json/naticore/v1/relationships` — Create (body: `from_id`, `to_id`, `type`)
- **DELETE** `/wp-json/naticore/v1/relationships` — Remove (body: `from_id`, `to_id`, `type`)

Authentication follows WordPress REST conventions. Exact routes and capabilities are defined in the plugin’s REST registration.

## OpenAPI spec

An [OpenAPI 3.0](https://spec.openapis.org/oas/3.0) specification is available at [openapi.json](https://chetanupare.github.io/WP-Native-Content-Relationships/openapi.json) for code generation and API tooling.

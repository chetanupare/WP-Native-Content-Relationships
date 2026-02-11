# Architecture Overview

This document outlines the high-level architecture of the Native Content Relationships plugin.

## System Components

```mermaid
graph TD
    subgraph Core ["Core System"]
        API[Public API<br/>(ncr_add_relation)]
        Registry[Relationship Type Registry]
        DB[(Database Table<br/>wp_content_relations)]
        Cache[Object Cache]
    end

    subgraph Integrity ["Integrity Engine"]
        CLI[WP-CLI Commands]
        Cron[Scheduled Tasks]
        Repair[Repair Logic]
        Validation[Schema & Constraint Checks]
    end

    subgraph Interfaces ["Interfaces"]
        MetaBox[Admin Meta Box]
        REST[REST API]
        Elementor[Elementor Dynamic Tags]
    end

    %% Connections
    API --> Registry : Validates Type
    API --> DB : Writes Data
    API --> Cache : Invalidate/Read
    
    Registry --> Validation : Defines Constraints
    
    CLI --> Repair : Triggers
    Cron --> Repair : Triggers
    Repair --> DB : Reads/Fixes
    
    MetaBox --> API : Uses
    REST --> API : Uses
    Elementor --> API : Reads
```

## Key Components

### 1. Registry Layer (`class-relation-types.php`)
- **Responsibility**: Defines allowed relationship types and their constraints.
- **Constraints**: 
    - `max_connections` (e.g., One Post to One Author)
    - `direction` (e.g., `bidirectional`, `from_to`, `to_from`)
    - `object_types` (e.g., `post`, `user`, `term`)

### 2. Database Layer (`class-database.php`)
- **Table**: `wp_content_relations`
- **Indexing**: 
    - Composite indexes on `(from_id, type)` and `(to_id, type)`.
    - Covering index on `(type, from_id, to_id)`.
- **Schema Guard**: `NATICORE_SCHEMA_VERSION` ensures DB structure updates run only when needed.

### 3. Integrity Engine (`class-integrity.php`)
- **Responsibility**: Detects and fixes data inconsistencies.
- **Features**:
    - **Chunked Processing**: Handles large datasets (1M+ rows) in small batches to manage memory.
    - **orphan detection**: Removes relationships pointing to deleted posts/users.
    - **Constraint Enforcement**: checks `max_connections`.

### 4. Public API (`class-api.php`)
- **Facade**: Provides a simple, safe interface for developers (`ncr_add_relation`, `ncr_get_related`).
- **Validation**: Enforces registry rules before writing to DB.
- **Caching**: Implements intelligent object caching to minimize DB queries.

# Performance Benchmarks

This document outlines the performance characteristics of the Relationship Integrity Engine under enterprise-scale loads.

## Test Environment
- **Rows**: 100,000 to 1,000,000
- **Storage Engine**: InnoDB
- **Platform**: WordPress 6.x / PHP 7.4+

## Latency Metrics

| Metric | 100k Rows | 1.0M Rows |
| :--- | :--- | :--- |
| **Point Lookup (ID + Type)** | 0.49 ms | 1.92 ms |
| **Covering Index Lookup** | 1.25 ms | 0.22 ms |
| **Integrity Scan (per batch)** | ~0.07 s | ~0.08 s |
| **Full Graph Scan (1M rows)** | - | ~75s |

## Resource Efficiency

### Memory Management
The Relationship Integrity Engine uses chunked processing to ensure memory usage remains bounded regardless of dataset size.

- **Peak Memory Delta (1.0M rows)**: ~2.21 MB
- **Memory Boundary**: < 5MB (Shared hosting safe)

### Database Optimization
The schema uses composite covering indexes to satisfy queries directly from the index tree:
- `KEY type_lookup (type, from_id, to_id)`

This ensures that lookups remain fast even as the table grows toward 10M+ records.

> [!NOTE]
> All benchmarks were performed using the automated `benchmarks/performance-report.php` utility.

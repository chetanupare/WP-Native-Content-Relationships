# Performance Benchmarks

This document describes the performance characteristics of the Native Content Relationships Integrity Engine under large-scale datasets. All benchmarks were performed using deterministic test datasets and SQL-native validation logic.

## Test Environment
- **Dataset Size**: 100,000 – 1,000,000 relationship rows
- **Storage Engine**: InnoDB
- **Indexing**: Composite covering index (`type_lookup`)
- **WordPress**: 6.x
- **PHP**: 7.4+
- **Object Cache**: Disabled (baseline measurement)
- **MySQL Buffer Pool**: Warmed before final run

## Latency Metrics

| Operation | 100k Rows | 1.0M Rows |
| :--- | :--- | :--- |
| **Point Lookup (Mean)** | 0.49 ms | 1.00 ms |
| **Point Lookup (P95)** | 0.85 ms | 2.73 ms |
| **Covering Index Mean** | 0.22 ms | 0.61 ms |
| **Covering Index P95** | 1.25 ms | 3.42 ms |
| **Full Graph Scan** | ~7.2 s | ~64.2 s |

*\* Variation depends on buffer pool state and cache warm-up.*

## Resource Efficiency

### Memory Management
The Integrity Engine uses chunked processing with bounded iteration to ensure stability regardless of dataset size.

- **Peak Memory Delta (1.0M rows)**: ~2.21 MB
- **Maximum Observed Memory Usage**: < 5 MB
- **Scaling Factor**: Independent of dataset size

This ensures compatibility with shared hosting and restricted enterprise environments.

### Database Optimization Strategy
The schema utilizes a composite covering index to maximize throughput:
`KEY type_lookup (type, from_id, to_id)`

This enables:
- **Index-only lookups** for common queries.
- **Avoidance of full table scans** during integrity audits.
- **Stable query time growth** ($O(\log n)$).

Under realistic workloads, query latency remains sub-2ms even at 1M rows.

## Scaling Characteristics

Observed complexity classes:
- **Point Lookups**: $O(\log n)$
- **Constraint Checks**: $O(\log n)$
- **Integrity Scan**: $O(n)$ (chunked, bounded memory)

**Projected performance at 10M rows:**
- Point lookups remain index-bound.
- Full graph scan expected in ~10–12 minutes (linear scaling).
- Memory usage remains bounded (< 5MB).

## Benchmark Methodology

Benchmarks are executed via the `benchmarks/performance-report.php` utility. The methodology involves:
1. **Deterministic Data Generation**: Creating predictable relationship graphs.
2. **Buffer Pool Warming**: Executing primer queries before final measurement.
3. **Mean Latency Calculation**: Averaging results over multiple iterations.
4. **Memory Delta Tracking**: Reporting peak usage via `memory_get_peak_usage()`.

> [!IMPORTANT]
> For enterprise environments requiring high-availability, we recommend monitoring $P95$ query latency during full integrity scans.

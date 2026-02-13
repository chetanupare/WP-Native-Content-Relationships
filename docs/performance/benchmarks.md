---
title: Benchmarks
---

# Performance Benchmarks

Performance characteristics of the Native Content Relationships engine at scale. Benchmarks use deterministic test datasets and SQL-native validation.

## Test environment

- **Dataset size**: 100,000 – 1,000,000 relationship rows
- **Storage engine**: InnoDB
- **Indexing**: Composite covering index (`type_lookup`)
- **WordPress**: 6.x · **PHP**: 7.4+
- **Object cache**: Disabled (baseline)
- **MySQL buffer pool**: Warmed before final run

## Latency metrics

| Operation            | 100k rows | 1.0M rows |
| -------------------- | --------- | --------- |
| **Point lookup (mean)** | 0.49 ms   | 1.00 ms   |
| **Point lookup (P95)**  | 0.85 ms   | 2.73 ms   |
| **Covering index mean** | 0.22 ms   | 0.61 ms   |
| **Covering index P95**  | 1.25 ms   | 3.42 ms   |
| **Full graph scan**     | ~7.2 s    | ~64.2 s   |

*Variation depends on buffer pool state and cache warm-up.*

## Resource efficiency

### Memory

- **Peak memory delta (1.0M rows)**: ~2.21 MB
- **Max observed**: &lt; 5 MB
- **Scaling**: Independent of dataset size

Suitable for shared hosting and restricted environments.

### Database strategy

Covering index: `KEY type_lookup (type, from_id, to_id)` — index-only lookups for common queries; query time growth O(log n). Under typical workloads, query latency stays **sub-2ms** at 1M rows.

## Methodology

Benchmarks run via the `benchmarks/performance-report.php` utility: deterministic graph generation, buffer pool warming, mean latency over iterations, peak memory via `memory_get_peak_usage()`.

::: tip
For high-availability setups, monitor P95 query latency during full integrity scans.
:::

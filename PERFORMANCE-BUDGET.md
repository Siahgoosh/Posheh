# PERFORMANCE-BUDGET

Configuration source: `backend/config/performance.php` (env-overridable).

| Metric | Default budget | Notes |
|--------|----------------|-------|
| Page weight | 1800 KB | Config target |
| JS | 450 KB | |
| CSS | 120 KB | |
| Images | 800 KB | |
| Requests | 80 | |
| TTFB | 800 ms | |
| LCP | 2500 ms | Core Web Vital |
| INP | 200 ms | Core Web Vital |
| CLS | 0.1 | Core Web Vital |

These are **internal budgets**, not field CrUX and not a Google score.

Env examples:

```
PERF_BUDGET_LCP_MS=2500
PERF_BUDGET_INP_MS=200
PERF_BUDGET_CLS=0.1
```

Regression: after major frontend/deploy changes, re-check important templates on mobile + desktop and compare against this budget. If field data becomes available, display it separately as Field vs Lab.

# SEO Monitoring Plan

## Daily (Automatic)

| Job | Command | Purpose |
|-----|---------|---------|
| GSC collect | `seo:collect-gsc` | Pull Search Console rows (or DATA_UNAVAILABLE) |
| Publish scheduled | `blog:publish-scheduled` | Existing CMS |

## Weekly (Automatic + Assisted review)

| Job | Command | Purpose |
|-----|---------|---------|
| Opportunity analysis | `seo:analyze` | Quick wins, decay, gaps, links, topics |
| Weekly snapshot | `seo:analyze --weekly-report` | Executive archive |

## Monthly (Assisted)

- Content portfolio review (STAR/GROW/FIX/RETIRE)
- Topic authority gaps
- Cannibalization decisions (no auto-merge)
- Recommendation ROI review (only with enough sample size)

## Alerts

| Severity | Examples |
|----------|----------|
| Critical | GSC collector exception |
| High | Missing credentials / API failure |
| Medium | GSC disabled |
| Low | Informational notices |

## Dashboard

`/admin/seo-growth` — top 10 priorities, KPIs (or UNKNOWN), alerts, topics, queries.

## Failure handling

- GSC failure ≠ blog outage
- Incomplete/small samples: confidence lowered; UI warns
- Never attribute multi-change weeks to a single experiment without evidence

## Privacy

- Internal search stores `ip_hash` only
- No unnecessary PII
- Toggle: `SEO_INTERNAL_SEARCH_LOGGING`

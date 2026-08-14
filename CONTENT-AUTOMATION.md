# CONTENT-AUTOMATION

## Schedule

| Cadence | Command |
|---------|---------|
| Every 5 min | `content:process-ai-jobs --limit=20` |
| Daily 03:10 | `content:ops-audit --process=5` (decay scan + jobs) |
| Weekly Mon 05:30 | `content:ops-audit --weekly` |
| Monthly 1st 06:00 | `content:ops-audit --monthly` |

## After publish

Sitemap invalidate + ops_status=PUBLISHED. **No index guarantee.**

## Alerts (surfaced in dashboard)

Failed jobs, high-risk claims, refresh needed, cost usage. Wire to existing SEO alerts when GSC decay exists.

## Performance

AI work is queued — does not block public website request path.

# LOCAL-SEO-AUTOMATION

## Daily

- `seo:local-audit` — NAP/entity consistency + sitemap invalidate
- Property status naturally reflected (queries filter Active + show_on_website)
- Location sitemap only published indexable URLs

## Weekly

- `seo:local-audit --opportunities` — regenerate ≤10 local opportunities
- Review open opportunities in admin
- Entity consistency scan

## Monthly (ops checklist)

- Location performance (when GSC data exists)
- Topical authority review
- Retire/FIX portfolio locations
- Expire stale local knowledge (`expires_at`)

## Alerts (via audit severities)

- Critical: unverified coords on profile, missing business name
- High: publish blocked thin locations, NAP conflicts
- Medium: partial NAP, local articles without location link

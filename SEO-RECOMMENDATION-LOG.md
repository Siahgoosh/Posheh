# SEO Recommendation Log

Living log of recommendation workflow. Runtime source of truth: table `seo_recommendations`.

## Status machine

`NEW` → `REVIEWED` → `APPROVED` → `EXECUTED`  
        ↘ `REJECTED`  
`EXECUTED` → `ROLLED_BACK`

## Required fields (every recommendation)

- Problem
- Evidence (first-party)
- Recommendation
- Expected Benefit
- Risk
- Effort
- Priority Score = Impact × Confidence / Effort

## Seed / bootstrap entries

| ID | When | Note |
|----|------|------|
| — | 2026-08-12 | Engine shipped; queue empty until GSC sync + `seo:analyze` |

## Execution rules

| Action | Automation | Notes |
|--------|------------|-------|
| title_opt / meta_opt | ASSISTED | Snapshot only; editor applies text |
| internal_link | ASSISTED | May update `related_slugs` after Approve |
| expand / faq / refresh | ASSISTED | No auto body rewrite |
| merge / redirect / rewrite / noindex | MANUAL | CMS + human only |

Append executed decisions here after production runs for audit trail.

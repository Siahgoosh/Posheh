# SEO Data Dictionary

## blog_gsc_metrics

| Column | Meaning |
|--------|---------|
| page_url | Landing page URL from GSC |
| query / query_raw / query_normalized | Raw + Persian-normalized query |
| clicks, impressions, ctr, position | First-party only |
| device, country, search_appearance | Optional dimensions |
| date, synced_at | Grain + sync time |

## seo_search_queries

Aggregated 28d query intelligence: intent, topic, entity, funnel, business_value, cluster_key, current/potential page.

## seo_page_daily

Daily page aggregates for decay and health.

## seo_content_health

Per-article lifecycle, portfolio (STAR/GROW/MAINTAIN/FIX/RETIRE), health, decay_pct/reason, orphan_risk.

## seo_opportunities

Detected opportunities: quick_win, striking_distance, ctr, cannibalization, content_gap, zero_result, link, pillar, decay.

## seo_recommendations

Action queue with status NEW→REVIEWED→APPROVED→REJECTED→EXECUTED→ROLLED_BACK. Stores evidence, risk, effort, snapshots.

## seo_content_experiments

Title/meta experiments log (hypothesis, variants, metrics, decision).

## seo_internal_searches

Privacy-safe internal blog search log (ip_hash, zero_result).

## seo_topic_scores

Topic authority coverage scores and gaps.

## seo_alerts

Operational alerts (GSC down, credentials, exceptions).

## seo_report_snapshots

Weekly/monthly executive payloads.

## UNKNOWN policy

If a metric is missing, APIs/UI must show `UNKNOWN` / `DATA_UNAVAILABLE` — never invent values.

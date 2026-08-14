# Content → CRM

## Attribution stored on every cro_lead

- article_slug / article_url / blog_post_id / category_slug
- landing_page, first_touch_path, last_touch_path, conversion_page
- utm_*, gclid, keyword, campaign
- source, visitor_hash (hashed)

## CRM bridge

If `CRO_CRM_SYNC_OFFICE_ID` is set:

1. Find Customer by mobile in that office
2. Else create Customer with `source=blog_cro`, `lifecycle=lead`
3. Store `customer_id` on cro_lead

Without office id: leads stay in `cro_leads` for platform admin (no fabricated CRM rows).

## Sales feedback loop

Admin can set `quality_feedback`: good|bad|wrong_intent|duplicate|converted  
This feeds Lead Quality dashboard and future SEO priority (Phase 5 learning).

## Follow-up

Status CONTACTED records `response_seconds` (SLA signal).  
Qualified/Won emit conversion events for funnel rates.

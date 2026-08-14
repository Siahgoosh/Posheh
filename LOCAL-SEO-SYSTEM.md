# LOCAL-SEO-SYSTEM

## Policy

Location pages only with real Business Value + Unique Content. No doorway spam for every city.

## Trust / NAP

- Source of truth: `seo_business_profiles` (admin Local SEO)
- Public mirror: `/about`, `/contact`, `SITE_CONTACT` emails
- Organization JSON-LD on trust pages
- **LocalBusiness** schema only when verified phone + address + coords exist
- No fake reviews/testimonials/coords/prices

## Entity layer (Phase 9)

- `seo_entities` + `seo_entity_relationships` (internal graph — not a Google Knowledge Graph claim)
- `seo_locations` hierarchy with **Quality Gate** before publish
- `seo_topics` topical tree + coverage
- `seo_local_knowledge` human-approved local facts (source required for market data)
- `seo_local_opportunities` weekly max 10 recommendations

## Commands

```bash
php artisan seo:local-bootstrap
php artisan seo:local-audit
php artisan seo:local-audit --opportunities
```

## Admin

`/admin/seo-local`

## Public

`/locations/{slug}` — only published + quality-passed + indexable

## Lead routing

`city` / `request_type` stored on CRO leads. Location pages use `source=LOCATION`.

## Forbidden

- Mass “املاک در شهر X” thin pages
- Invented NAP / coords / reviews / market stats
- Publishing location without unique_value + description quality gate

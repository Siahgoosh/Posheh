# CRO System — پوشه

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/production-release-a876`

## اصل

User Value → Trust → Conversion. CTA تهاجمی سراسری نداریم.

## اجزا

| قطعه | مسیر |
|------|------|
| CTA Manager | `cro_ctas`, `cro_cta_rules`, `CroCtaResolver` |
| Lead Capture | `POST /api/v1/cro/leads`, `CroLeadService` |
| Tracking | `POST /api/v1/cro/track` + `cro_conversion_events` |
| Dashboard | `/admin/cro` |
| Bootstrap | `php artisan cro:bootstrap` |

## CTA Rules

IF category/intent/funnel matches → soft CTA. Post-level `cta_text`/`cta_url`/`cro_cta_key` می‌تواند override کند.

## Experiments

A/B فقط با sample کافی (`CRO_AB_MIN_SAMPLE`, `CRO_AB_MIN_DAYS`). برنده خودکار اعلام نمی‌شود.

## Sticky / Exit Intent

- Sticky موبایل: اختیاری روی مقاله (`showSticky`)
- Exit Intent: پیش‌فرض خاموش (`CRO_EXIT_INTENT=false`)

## Safety

- Honeypot + rate limit
- Consent checkbox
- ip_hash فقط
- بدون fake review / fake conversion

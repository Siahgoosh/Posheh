# CRM Phase 1 — Database & Architecture Design

## انجام‌شده
- Audit کامل: `docs/CRM-EXISTING-SYSTEM-AUDIT-FA.md`
- Migration افزایشی: `2026_08_12_090000_create_professional_crm_extensions.php`
- مدل‌ها: PipelineStage, Source, Tag, FollowUp, ScoreRule, LostReason
- `CrmBootstrapService` برای seed پیش‌فرض per office
- ارتقای `CrmService`: customer_id، lost_reason اجباری، consultant scope، duplicate mobile، next best action، score band
- API متا: `/crm/stages|sources|tags|lost-reasons|score-rules|duplicates|bootstrap`
- تست: `tests/Feature/Crm/CrmCoreTest.php`

## اصول رعایت‌شده
- هیچ جدول/ستون موجودی حذف یا Rename نشد
- Communication `comm_*` دست نخورده
- Stage string روی `crm_deals` حفظ شد (سازگاری Frontend فعلی)
- Tenant isolation سخت‌تر برای مشاور (فقط assigned)

## Phase بعدی (۲)
- Pagination/filters روی deals
- UI: JalaliDatePicker، lost reason، customer link، score badge
- Task CRUD API
- Feature gate پلن `crm`

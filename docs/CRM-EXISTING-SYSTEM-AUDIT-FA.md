# POSHE CRM — EXISTING SYSTEM AUDIT

**تاریخ:** ۲۰۲۶-۰۸-۱۲  
**شاخه کاری:** `cursor/crm-professional-a876`  
**مخزن:** Posheh (Laravel 12 API + React/Vite frontend)  
**وضعیت:** Phase 0 کامل — بدون تغییر مخرب در دیتای Production

---

## A. Architecture

### Backend
- Laravel 12 monolith در `backend/`
- Controllers نازک + Services دامنه‌ای (`app/Services/{Crm,Customer,Visit,Commission,Contract,…}`)
- Models در `app/Models` با Trait چندمستاجری `BelongsToOffice`
- ماژول‌های جدا: `app/Modules/Communication` (CRM بازاریابی پلتفرم)، Virtual Tour
- API: Sanctum (`auth:sanctum`) + `EnsureOfficeIsActive` + `EnsureSubscriptionAccess`
- Spatie Permission در composer هست ولی **برای CRM دفتر استفاده نمی‌شود** (فقط `UserRole` enum)

### Frontend
- React + Vite + TanStack Query + Tailwind
- صفحه اصلی CRM: `frontend/src/pages/CrmPage.tsx` (Kanban)
- صفحات مرتبط اما جدا: Customers / Visits / Commissions / Contracts / Reports
- تقویم شمسی واقعی (`JalaliDatePicker`) در حسابداری هست؛ **CRM هنوز `datetime-local` میلادی دارد**

### Database
- MySQL/MariaDB، مبالغ `unsignedBigInteger` (تومان)
- Tenant key: `office_id` روی تقریباً همه جداول دفتر
- Soft delete روی بعضی مدل‌ها (Office/Property)؛ `crm_deals` soft delete ندارد

### Authentication / Authorization
- نقش‌ها: `super_admin`, `platform_*`, `office_manager`, `consultant`
- `canManageOffice()` = مدیر دفتر یا سوپرادمین
- CRM فعلی: **هر کاربر دفتر همه معاملات دفتر را می‌بیند و ویرایش می‌کند** (بدون فیلتر assignee)
- Feature پلن `crm` / `lead_scoring` / `visit_calendar` در seeder هست ولی **روی APIهای CRM enforce نمی‌شود** (برخلاف accounting)

### API
- `/api/v1/crm/deals`, `/crm/pipeline`, `/crm/follow-ups`, activities
- `/customers`, `/visits`, `/commissions`, `/contracts`
- Leadهای جدا: Communication `/leads`, Virtual Tour lead, Office website visit-request

### Storage / Notification
- فایل‌ها: storage Laravel (قرارداد PDF/DOCX، مدیا ملک)
- جدول `notifications` Laravel موجود؛ **نوتیفیکیشن CRM پیاده‌سازی نشده**
- `ActivityLogger` برای audit دفتر/ملک؛ CRM از `crm_activities` جداگانه استفاده می‌کند
- یادآوری بازدید: دستور `visits:remind` + SMS به مشاور

---

## B. Existing Modules

| ماژول | وضعیت | مسیر کلیدی |
|--------|--------|------------|
| Office CRM (Deals Kanban) | فعال / ناقص | `CrmService`, `CrmPage` |
| Customers | فعال / جدا از Deal | `CustomerService`, `CustomersPage` |
| Owners | فعال | `Owner` |
| Property Filing | فعال | `Property` |
| Property Visits | فعال | `VisitService`, `VisitsPage` |
| Commissions | فعال + وصل به closed_won | `CommissionService` |
| Contracts | فعال / بدون FK به Deal | `ContractService` |
| Tasks | Schema + داشبورد فقط | `Task` — **بدون API CRUD** |
| Accounting | شاخه جدا / وصل مالی Deal | ledger + `/accounting/deals/{id}/finance` |
| Communication CRM | پلتفرم (بدون office_id) | `CommLead`, pipelines |
| Virtual Tour Leads | فعال / بدون تبدیل به Deal | `VirtualTourLead` |
| Office Website Visit Request | فعال / بدون تبدیل | `OfficeVisitRequest` |
| Reports | KPI جزئی CRM | `ReportsPage`, `ReportService` |
| Admin Panel CRM | لیست سراسری | `AdminCrmPage` |

---

## C. Existing Database (CRM-related)

### `crm_deals`
| ستون | نوع | توضیح |
|------|-----|--------|
| id | PK | |
| office_id | FK offices | Index با stage |
| assigned_to | FK users nullable | |
| property_id | FK properties nullable | |
| title | string | |
| contact_name / contact_mobile | string | **جایگزین Contact/Customer** |
| stage | string(30) default lead | Hardcoded stages |
| value / offer_amount | unsignedBigInteger | |
| lead_score | tinyint default 50 | Heuristic |
| priority | string default medium | |
| source | string nullable | آزاد / غیرقابل مدیریت |
| notes | text | |
| expected_close_at / follow_up_at | timestamp | |
| timestamps | | |

**ندارد:** customer_id, lost_reason, pipeline_id, soft deletes, tags, probability, next_action

### `crm_activities`
crm_deal_id, user_id, type, body, meta(json), timestamps — فقط وابسته به Deal، بدون office_id مستقیم

### `customers`
office_id, created_by, assigned_to, name, mobile, national_id, priority(normal|vip), بودجه/ترجیحات ملک، notes  
**ندارد:** roles چندگانه، source، lead_score، tags، last_contact، next_followup، second_mobile

### `property_visits`
office_id, property_id, customer_id, assigned_to, visit_at, status, notes, sms_reminder_sent  
**ندارد:** crm_deal_id

### `owners`
office_id, name, mobile, national_id, email, address, notes

### `commissions` / `commission_settings`
وصل به crm_deal_id؛ نرخ فروش/اجاره؛ سهم دفتر/مشاور (در ماژول حسابداری)

### `contracts`
office_id, property_id, template, parties names, pdf/docx  
**ندارد:** crm_deal_id, customer_id

### `tasks`
office_id, assigned_to, property_id, title, due_at, status  
**ندارد:** crm_deal_id, customer_id — و **بدون CRUD API**

### `activities` / `notifications` / `feature_flags`
Audit عمومی دفتر؛ نوتیفیکیشن Laravel خام؛ پرچم‌های feature

### جداول جدا (نباید با CRM دفتر قاطی شوند)
- `comm_*` (Communication platform)
- `virtual_tour_leads`
- `office_visit_requests`

---

## D. Existing CRM — Capabilities Matrix

| قابلیت | وضعیت |
|--------|--------|
| Kanban Deal با ۶ Stage ثابت | ✅ EXISTS |
| CRUD معامله | ✅ EXISTS |
| Activities روی Deal | ✅ EXISTS |
| Follow-up list (۷ روز) | ⚠️ PARTIAL |
| Lead Score ساده | ⚠️ PARTIAL (قابل‌پیکربندی نیست) |
| Priority / Source روی Deal | ⚠️ PARTIAL |
| کمیسیون خودکار closed_won | ✅ EXISTS |
| Customer جدا + Match ملک | ✅ EXISTS |
| Visit Calendar | ✅ EXISTS |
| Contract generator | ✅ EXISTS |
| Customer ↔ Deal link | ❌ MISSING |
| Contact entity / چند Role | ❌ MISSING |
| Pipeline قابل‌پیکربندی | ❌ MISSING |
| Lost Reason اجباری | ❌ MISSING |
| Duplicate Detection موبایل | ❌ MISSING |
| Task CRUD + Reminder CRM | ❌ MISSING |
| Follow-up Engine واقعی | ❌ MISSING |
| Customer 360 / Timeline واحد | ❌ MISSING |
| Property Matching در Pipeline | ⚠️ PARTIAL (فقط صفحه مشتری) |
| Offer / Negotiation entity | ❌ MISSING (فقط stage + offer_amount) |
| Referral / Campaign / Tags | ❌ MISSING |
| CRM Dashboard مشاور/مدیر | ⚠️ PARTIAL (Reports؛ Dashboard خالی از CRM) |
| Agent Performance کامل | ⚠️ PARTIAL |
| Notification CRM | ❌ MISSING |
| Permission دانه‌ای CRM | ❌ MISSING |
| Import/Export مشتریان | ❌ MISSING |
| تبدیل Lead وب‌سایت/تور → Deal | ❌ MISSING |
| Jalali date picker در CRM | ❌ MISSING |
| Pagination/Search روی Deals | ❌ MISSING |
| Consultant visibility scope | ❌ MISSING |
| Feature gate پلن CRM | ❌ MISSING |
| تست Feature CRM | ❌ MISSING |

---

## E. Problems

### معماری
1. **سه سیستم Lead موازی:** `CrmDeal` (دفتر) / `CommLead` (پلتفرم) / `VirtualTourLead` + `OfficeVisitRequest` — بدون تبدیل یکپارچه
2. **Customer و Deal جدا** با فیلدهای تماس تکراری روی Deal
3. Stageها Hardcoded در چند فایل (`CrmService`, Frontend, `ReportService`)
4. Tasks Schema بدون لایه سرویس/API
5. Business logic CRM فقط در یک Service بزرگ؛ بدون FormRequest/Resource استاندارد

### امنیت
1. مشاور می‌تواند همه Deals دفتر را ببیند/حذف کند (IDOR منطقی در سطح نقش)
2. `CustomerController`: `assigned_to` فقط `exists:users,id` — بدون قید office
3. `CrmActivity` بدون office scope مستقیم
4. Featureهای پلن CRM enforce نمی‌شوند
5. Spatie permissions استفاده‌نشده → RBAC درشت‌دانه

### Performance
1. `CrmService::list` همه Deals دفتر را یکجا می‌آورد (بدون pagination)
2. `CustomerService::matchProperties` کل املاک فعال را در PHP امتیازدهی می‌کند
3. Index موبایل روی customers محدود است؛ جستجوی فارسی پیشرفته نیست

### UX
1. تاریخ میلادی در فرم Follow-up CRM
2. Drag-drop Kanban روی موبایل ضعیف
3. ID عددی خام برای property/customer در Visits
4. Customer Detail بدون تاریخچه بازدید/معامله (برخلاف وعده لندینگ)
5. Dashboard اصلی بدون Urgent Follow-ups / Hot Leads

### Business Logic
1. کمیسیون از Deal همیشه `sale_rate_percent` (نرخ اجاره نادیده)
2. Lost بدون دلیل
3. بدون Duplicate موبایل
4. بدون Next Best Action
5. Source آزاد و غیرقابل مدیریت Admin

### Code Quality
1. تکرار STAGE_LABELS در Frontend/Admin/Backend
2. تست CRM تقریباً صفر
3. ناهماهنگی gating پلن بین accounting (سخت) و CRM (نرم)

---

## F. Gap Analysis

### باید ایجاد شوند (جدید / افزایشی)
- Entity لینک: `customer_id` روی deals؛ `crm_deal_id` روی visits/contracts/tasks
- `crm_pipeline_stages` قابل‌تنظیم per office (با seed از stageهای فعلی)
- `crm_sources`, `crm_tags` + pivot
- `crm_follow_ups` یا ارتقای follow_up با outcome/reminder
- `lost_reason`, `next_action`, `last_contacted_at` روی deal/customer
- Customer roles (JSON یا pivot)
- Lead score rules قابل‌پیکربندی
- Task API + اتصال به Deal/Customer
- Customer 360 API/UI + Timeline واحد
- Duplicate check by mobile
- CRM Dashboard widgets
- Notification hooks برای follow-up/assignment
- Permissionهای `crm.*` / `lead.*` (حداقل روی نقش‌های موجود)
- JalaliDatePicker در تمام فرم‌های CRM
- Feature tests tenant isolation + pipeline + duplicate

### باید اصلاح / تکمیل شوند
- اتصال Customer ↔ Deal ↔ Visit ↔ Contract ↔ Commission
- Enforce پلن `crm` / `lead_scoring` / `visit_calendar`
- Pagination + filters + search روی deals
- Consultant scope (مشاور فقط assigned + مشترک)
- Lead intake: VisitRequest / TourLead → Customer + Deal
- Scoring configurable + badge Cold…Very Hot
- Lost reason اجباری هنگام `closed_lost`

### نباید حذف شوند (فقط Extend/Refactor)
- جدول `crm_deals` و stageهای فعلی (مهاجرت به pipeline_stages با نگه‌داشتن string stage)
- `customers`, `owners`, `property_visits`, `commissions`, `contracts`, `tasks`
- `crm_activities` (گسترش typeها)
- Communication `comm_*` به‌عنوان محصول جدا — **ادغام اجباری نه**؛ فقط Bridge اختیاری بعداً
- کیف پول/پرداخت SaaS

### ریسک‌های پرخطر (قبل از اجرا گزارش می‌شود)
| تغییر | ریسک | تصمیم پیشنهادی |
|--------|------|----------------|
| Rename/drop ستون stage روی crm_deals | شکستن Frontend/API | نگه داشتن `stage` string + جدول stages موازی |
| Merge اجباری CommLead با CrmDeal | خراب کردن محصول Communication | جدا بمانند |
| Hard delete migration down روی production data | از دست رفتن داده | down فقط drop جدول‌های جدید؛ ستون‌های افزوده را drop اختیاری |
| تغییر معنای lead_score ناگهانی | گزارش‌های قبلی | rules جدید؛ امتیازهای قدیمی حفظ |

---

## Proposed CRM Architecture (خلاصه)

```text
Lead Intake (Website / Tour / Manual / Import)
        ↓
   Customer (Contact 360)  ←→  Tags / Sources / Roles
        ↓
   CrmDeal (Pipeline Stage) ←→ Property / Visits / Offers
        ↓
   Activities / Tasks / Follow-ups / Notes / Attachments
        ↓
   Contract → Deal Won → Commission → Accounting
        ↓
   After-sales / Referral → New Lead
```

**اصول:**
1. Extend جداول موجود — بدون rebuild
2. Tenant isolation با `office_id` + scope سرور
3. Presentation تاریخ = جلالی؛ Storage = استاندارد
4. API-first برای Flutter بعدی
5. AI-ready: فیلدهای score/next_action/summary قابل‌پرشدن بعدی

---

## ERD Description (هدف Phase 1–3)

```text
offices 1─* customers 1─* property_visits
customers 1─* crm_deals *─1 properties
crm_deals 1─* crm_activities
crm_deals 1─* crm_follow_ups (new)
crm_deals 0─1 commissions
crm_deals 0─* contracts (new FK)
tasks → customer_id / crm_deal_id (new FKs)
crm_tags *─* customers|crm_deals
crm_sources 1─* crm_deals|customers
crm_pipeline_stages (per office) → deals.stage maps to stage_key
```

---

## Database Migration Plan (Phase 1)

1. **Additive only** migration `create_professional_crm_extensions`
2. ستون‌های nullable روی `crm_deals`, `customers`, `property_visits`, `contracts`, `tasks`
3. جداول جدید: `crm_pipeline_stages`, `crm_sources`, `crm_tags`, `crm_taggables`, `crm_follow_ups`, `crm_score_rules`
4. Seed stage/source پیش‌فرض per office (lazy on first CRM access — مثل accounting bootstrap)
5. هیچ DROP/RENAME ستون موجود در `up()`
6. Index روی: office_id+mobile, next_followup, lead_score, customer_id, stage

---

## Module Dependency Map

```text
CRM Core → Customer, Property, User/Office
Visits → Customer, Property, (Deal)
Commissions → Deal, User, Accounting(optional)
Contracts → Property, (Deal), Customer
Tasks → User, (Deal), (Customer)
Reports/Dashboard → all above
Communication/Tour → optional Lead Bridge (Phase 9)
```

---

## Security Risk Report (اولویت)

| # | ریسک | شدت | اقدام Phase |
|---|------|------|-------------|
| 1 | عدم فیلتر assignee برای مشاور | High | Phase 7 |
| 2 | assigned_to بدون office check در Customer | High | Phase 1–3 |
| 3 | لیست بدون pagination | Medium | Phase 2 |
| 4 | Feature CRM بدون enforce | Medium | Phase 2 |
| 5 | فایل پیوست مشتری (هنوز نیست) — طراحی امن از روز اول | Medium | Phase 4 |
| 6 | Mass assignment روی Deal update | Low–Med | FormRequest Phase 2 |

---

## Implementation Roadmap

| Phase | محتوا | مخرب؟ |
|-------|--------|--------|
| 0 | Audit (همین سند) | خیر |
| 1 | Migration افزایشی + Bootstrap stages/sources + مدل‌ها | خیر |
| 2 | CRM Core API: pagination, customer_id, lost_reason, feature gate | خیر |
| 3 | Lead/Contact: duplicate mobile, roles, tags, sources admin | خیر |
| 4 | Activities/Tasks/Follow-ups engine + Jalali UI | خیر |
| 5 | Pipeline configurable + Lost reason | کم‌ریسک |
| 6 | Dashboard مشاور/مدیر + Next Best Action rules | خیر |
| 7 | Permissions / consultant scope | خیر |
| 8 | Reports / KPI / Agent performance | خیر |
| 9 | Lead intake bridges + notifications | خیر |
| 10 | Tests + security hardening | خیر |

---

## Definition of Ready برای Phase 1

- معماری فعلی شناخته شد
- جداول موجود map شدند
- تصمیم: **Extend نه Rebuild**
- هیچ تغییر مخرب فوری لازم نیست
- می‌توان Migration افزایشی را با خیال راحت شروع کرد

**Phase 1 شروع می‌شود با Migration و Bootstrap امن — بدون حذف داده و بدون ادغام اجباری Communication CRM.**

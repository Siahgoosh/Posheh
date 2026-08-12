# POSHE CRM — SALES ENGINE IMPLEMENTATION REPORT

**Phase:** 2 — Sales Operating System  
**Branch:** `cursor/crm-professional-a876`  
**Date:** 2026-08-12  
**Principle:** Additive only — no production data overwrite, Communication `comm_*` untouched, existing CRM Kanban preserved.

---

## 1. Modules Created

| Module | Location | Role |
|--------|----------|------|
| Need Profile | `CrmNeedProfile` + Customer APIs | Customer need analysis linked 1:1 to customer |
| Property Matching Engine | `PropertyMatchingService` | Configurable weights, explainable score, exact/strong/alternative |
| Reverse Matching | same service | Property → potential customers + hot/warm/cold summary |
| Sales Queue | `SalesQueueService` | Today's follow-ups, visits, negotiations, hot leads |
| Opportunities | `SalesQueueService::opportunities` | Hot / overdue / stalled / pending offers / reactivation |
| Daily Briefing | `SalesQueueService::dailyBriefing` | Rule-based morning summary |
| Automation Engine | `CrmAutomationService` | Trigger → Condition → Action + audit logs |
| Offer & Negotiation | `OfferNegotiationService` | Timeline of offers, accept → deal convert |
| Presentations & Feedback | models + API | Property sent + learning from feedback |
| Deal Checklist | `CrmDealChecklistItem` | Default 10-step checklist per deal |
| Campaigns | `CrmCampaign` | Campaign entity for lead attribution |

Frontend tabs on `/crm`: **صف امروز · فرصت‌ها · پیشنهاد/مذاکره · قیف**

---

## 2. Database Changes

Migration: `2026_08_12_091000_create_crm_sales_engine.php` (additive)

**New tables:**
- `crm_need_profiles`
- `crm_matching_weights`
- `crm_property_presentations`
- `crm_property_feedback`
- `crm_negotiations` (soft deletes)
- `crm_offers` (soft deletes)
- `crm_automation_rules`
- `crm_automation_logs`
- `crm_deal_checklist_items`
- `crm_campaigns`

**Extended columns:**
- `property_visits`: customer_reaction, property_rating, price_opinion, likelihood_to_buy, next_action
- `crm_deals`: campaign_id, first_contacted_at, deal_status
- `customers`: lifecycle, referred_by_customer_id, campaign_id
- `properties`: listed_at, minimum_acceptable_price, seller_motivation
- `saved_searches`: customer_id, match_threshold

`down()` only drops Phase-2 tables; does not remove extended columns (safe rollback of new entities).

---

## 3. APIs Created

```
GET  /crm/sales-queue
GET  /crm/opportunities
GET  /crm/briefing
GET|PUT /crm/matching-weights
GET  /crm/properties/{id}/reverse-matches
GET|POST /crm/negotiations
GET  /crm/negotiations/{id}
GET|POST /crm/offers
PUT  /crm/offers/{id}/status
POST /crm/offers/{id}/convert-deal
POST /crm/presentations
POST /crm/feedback
GET|POST /crm/automation/rules
PUT  /crm/automation/rules/{id}
GET  /crm/automation/logs
GET  /crm/deals/{id}/checklist
POST /crm/deals/{id}/checklist/{itemId}/toggle
GET|POST /crm/campaigns
GET|PUT /customers/{id}/need-profile
POST /visits/{id}/complete
```

All under Sanctum + office tenant scope. Consultant scope applied on queue/deals where relevant.

---

## 4. Matching Algorithm

Weights stored in `crm_matching_weights` (defaults if missing):

| Key | Default % |
|-----|-----------|
| location | 25 |
| budget | 25 |
| area | 15 |
| property_type | 10 |
| bedrooms | 5 |
| parking | 5 |
| elevator | 5 |
| age | 5 |
| features | 5 |

- Score = earned / possible × 100  
- Soft budget: ≤10% over max → alternative match  
- **match_type:** exact (≥85, budget OK) · strong (≥70) · alternative (≥45 or soft budget)  
- Response includes `checks`, `reasons`, `warnings` (explainable)  
- Feedback reactions (`no_parking`, `no_elevator`) update Need Profile and penalize future matches

---

## 5. Lead Scoring

Phase 1 scoring retained (`CrmService::calculateLeadScore`).  
On create / score change: automation `score_changed` when score > 80 → urgent priority + follow-up.

---

## 6. Automation Rules (defaults, not hard-coded only)

| Trigger | Actions |
|---------|---------|
| lead_created | Follow-up in 15 min + task |
| score_changed (score>80) | Priority urgent + follow-up 30 min |
| visit_completed | Follow-up next day |
| offer_created | Follow-up 60 min |
| no_activity (≥7d) | Overdue follow-up |

Every run writes `crm_automation_logs` (rule, reason, actions_taken).

Office managers can CRUD custom rules via API.

---

## 7. Follow-up Engine

- `crm_follow_ups` (Phase 1) synced from deal `follow_up_at`
- Automation creates follow-ups
- Sales Queue merges follow-ups + deal follow-ups + today's visits + open negotiations
- Statuses surfaced as overdue / hot / normal in UI

---

## 8. Viewing System

- Double-booking prevention (portable Carbon overlap, MySQL+SQLite)
- `POST /visits/{id}/complete` with reaction / rating / price opinion / likelihood
- Completing visit fires `visit_completed` automation
- Extended visit statuses in validation

---

## 9. Offer System

- Full offer entity with side, amount, payment terms, deadline, status lifecycle
- Soft delete for auditability
- Accept → updates deal `offer_amount` + negotiation status

---

## 10. Negotiation System

- Independent negotiation record with price targets
- Offers append-only timeline (never overwrite history)
- Stalled negotiations appear in Opportunities + Sales Queue

---

## 11. Deal System

- Offer convert → closed_won + checklist seed
- Checklist: buyer/seller info, docs, price, payment, contract, sign, commission, settlement
- `deal_status` field for agreement / contract / payment progression

---

## 12. Commission Integration

- Phase 1/Accounting path unchanged: `closed_won` → `CommissionService::createFromDeal`
- Checklist item «دریافت کمیسیون» tracks operational completion
- Commission rates remain configurable in existing commission settings (not hard-coded)

---

## 13. Referral System

- `customers.referred_by_customer_id` + `lifecycle` including `referral_source` / `dormant`
- Reactivation list: dormant or 90+ days inactive

---

## 14. Analytics

- Sales Queue summary KPIs
- Opportunities center
- Daily briefing (rule-based; AI-ready message slot)
- Matching reverse summary (hot/warm/cold counts)
- Campaign entity ready for ROI (leads/deals aggregation can extend ReportService)

---

## 15. Security Changes

- All new queries filter `office_id`
- Negotiation show returns 404 cross-tenant
- Matching weights / automation / campaigns: manager-only mutations
- Consultant assignee scope on queue & deals retained
- Soft delete on offers/negotiations; no hard delete of financial trail

---

## 16. Tests Executed

File: `backend/tests/Feature/Crm/CrmSalesEngineTest.php`

Coverage:
- Explainable property matching
- Reverse matching summary
- Lead-created automation log
- Visit double-booking prevention
- Negotiation timeline (3 offers preserved)
- Offer accept + convert-deal API
- Sales queue / opportunities / briefing APIs
- Configurable matching weights
- Tenant isolation on negotiations
- Need profile API

**Note:** Agent environment has no local PHP/Docker; tests are authored for CI / deploy host:

```bash
docker compose exec app php artisan test --filter=CrmSalesEngineTest
docker compose exec app php artisan test --filter=CrmCoreTest
```

---

## 17. Errors Found

- Prior matching used hard-coded weights in `CustomerService` → replaced with engine
- MySQL-only `DATE_ADD` double-booking would break SQLite tests → rewritten to Carbon overlap
- Task model missing `crm_deal_id` fillable → fixed for automation create_task
- Visit / Property / Customer / Deal fillables missing Phase-2 columns → fixed

---

## 18. Errors Fixed

- Double-booking portable check
- Task / Visit / Property / Deal / Customer fillables
- Customer matches API returns checks/warnings/match_type
- Automation hooked into deal create / score change / visit complete / offer create
- Bootstrap seeds matching weights + automation defaults

---

## 19. Remaining TODOs (Phase 2.5 / 3)

1. Full Need Analysis Wizard UI (8-step) — API ready; multi-step form UI pending
2. Saved Search runner on property create + New Property Alert notifications
3. Viewing calendar day/week/month + configurable reminders (24h/2h/30m)
4. SLA engine + agent workload + round-robin assignment
5. Manager Intelligence Dashboard + Agent Leaderboard + Source/Campaign ROI charts
6. Lead Inbox filters (Hot/New/Unassigned/Overdue) as dedicated page
7. Keyboard shortcuts + Quick Add FAB
8. NLP search structure (`خریدار آپارتمان…`) — schema ready via need profile
9. Large seed dataset (50 customers / 100 leads / …) — optional seeder for local only
10. Enforce plan feature gate middleware on all CRM routes (declared but not all gated)

---

## Deploy

```bash
./scripts/deploy.sh cursor/crm-professional-a876
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

---

## Architecture (as shipped)

```
LEAD → NEED PROFILE → SCORING → MATCH ENGINE (↔ reverse)
  → PRESENTATION → FOLLOW-UP → VIEWING → FEEDBACK
  → NEGOTIATION → OFFER → DEAL → COMMISSION → REFERRAL
```

Automation + audit logs sit beside the pipeline; Sales Queue / Opportunities are the agent operating surfaces.

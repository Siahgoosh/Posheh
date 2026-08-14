# POSHE CRM — PRODUCTION READINESS REPORT

**Phase:** 3 — Intelligence, AI Architecture, Analytics, Communication, Admin & Production  
**Branch:** `cursor/crm-professional-a876`  
**Date:** 2026-08-12  
**Standard:** Extend Phase 1+2 — no duplicate CRM, no production data wipe, Communication `comm_*` untouched.

---

## Architecture

```
CRM Core (P1) → Sales Engine (P2) → Intelligence Layer (P3)
                                      ├── Executive / Agent Analytics
                                      ├── Funnel + Bottlenecks + Forecast
                                      ├── Source & Property Intelligence
                                      ├── AI Service Layer (rule + provider-ready)
                                      ├── Communication / Notifications / Integrations
                                      ├── Custom Fields / Saved Views / Onboarding
                                      └── Data Quality
```

Financial source of truth for commissions remains **Commission module** (+ Accounting ledger when settled). CRM forecast uses configurable pipeline probabilities × deal value; expected commission uses `commission_settings.sale_rate_percent` when present.

---

## Database

Migration (additive): `2026_08_12_092000_create_crm_intelligence_layer.php`

| Table | Purpose |
|-------|---------|
| `crm_pipeline_probabilities` | Weighted pipeline % per stage |
| `crm_agent_score_weights` | Reserved for configurable agent scoring |
| `crm_property_health_rules` | Reserved health rule config |
| `crm_custom_fields` / `_values` | Per-tenant custom fields (JSON values, no schema change) |
| `crm_message_templates` | Dynamic placeholders |
| `crm_notification_preferences` | Per-user channel/digest |
| `crm_notifications` | In-app notification center |
| `crm_integrations` / `_logs` | Modular SMS/WA/TG/Email adapters |
| `ai_providers` / `ai_prompt_templates` / `ai_usage_logs` | AI-ready + usage/cost control |
| `crm_saved_views` | Saved filters |
| `crm_onboarding_checklist` | Office CRM onboarding |

Extended: `properties.demand_score/health_status/previous_price/price_reduced_at`, `offices.brand_color/timezone/currency/crm_onboarded_at`.

---

## CRM / Sales Engine

Unchanged core; Phase 3 **reuses** `SalesQueueService`, matching, offers, automation, commissions.

---

## Automation

Phase 2 automation retained. Phase 3 notifications can be emitted via `CrmCommunicationService::notify` (in-app; SMS/Telegram gated by prefs + integration status).

---

## Analytics

| API | Capability |
|-----|------------|
| `GET /crm/executive` | KPIs + period comparison + funnel + forecast + agents + sources + top properties |
| `GET /crm/agent-dashboard` | Personal KPIs |
| `GET /crm/funnel-analytics` | Funnel + bottlenecks |
| `GET /crm/forecast` | Pipeline / weighted / expected commission |
| `GET /crm/agents/performance` | Leaderboard + performance score |
| `GET /crm/sources/intelligence` | Source → deals → revenue |
| `GET /crm/properties/{id}/intelligence` | Demand score + health |
| `GET /crm/data-quality` | Health score + issues |

Tenant-aware cache: `crm:exec:{office}:{period}:…` TTL 60s.

---

## AI Architecture

```
AiService → AiProviderManager
   ├── local (active): customer summary, message, listing — facts only
   └── openai (inactive stub): requires permission + credentials
AiPromptTemplate (versioned)
AiUsageLog (tenant/user/feature/tokens/cost/status)
```

**Safety:** no PII to external providers by default; mobile masked in summaries; listing assistant never invents amenities/price; usage logged.

Levels: **L1 rule-based shipped** · **L2 assisted stubs ready** · **L3 autonomous architecture only**.

---

## Communication

- Notification center + preferences (in-app/sms/telegram/email/push + digest)
- Message templates with `{placeholders}`
- Integration registry (sms/whatsapp/telegram/email) — **disabled until credentials**; logs without secrets
- Extends existing POSHE Telegram/SMS infra conceptually; does not fake WhatsApp API

---

## Security

- All new endpoints: Sanctum + office_id scoping
- Manager-only: probabilities write, custom fields create, templates create, agent leaderboard, bulk assign
- Consultant bulk assign cannot reassign others’ deals beyond own scope
- AI endpoints assert same-office entities
- Credentials never returned in integrations list (`has_credentials` flag only)
- Custom field keys regex-validated

---

## Performance

- Executive dashboard cached 60s per tenant/period/role
- Aggregations use scoped queries; property demand limited to 40 recent actives for top list
- Indexes on notification/user, custom field values, AI usage, integrations

---

## Testing

`tests/Feature/Crm/CrmIntelligenceTest.php`:
- Executive KPIs/comparison structure
- Funnel/forecast/agents/data-quality APIs
- AI summary masks mobile + usage log
- AI message fact-bound
- Custom fields tenant isolation
- Notifications/onboarding/integrations
- Bulk assign permission
- Executive tenant isolation

Also retain: `CrmCoreTest`, `CrmSalesEngineTest`.

```bash
docker compose exec app php artisan test --filter=Crm
```

---

## SaaS / Subscription / Feature Flags

- Plan features (`crm`, `advanced_analytics`, …) already on plans
- `officeHasFeature()` exists but **full route middleware gating still partial** (documented debt)
- FeatureFlag admin API exists platform-wide — CRM product flags can map later (`crm_ai`, `crm_automation`)

---

## Integrations

| Provider | Status |
|----------|--------|
| SMS | Adapter row + existing Sms services |
| Telegram | Adapter row + existing office bot |
| WhatsApp | Adapter stub (no fake API) |
| Email | Adapter stub |
| OpenAI | Provider stub disabled |

---

## Backup / Monitoring

Recommended production (ops doc, not code):
- Daily DB dump, weekly full, monthly archive; retain 30/90/365
- File storage backup (property media)
- Exception tracker with tenant/user context; never show stack to end users
- Separate log channels: application / security / integration / automation / ai

---

## Frontend

CRM page tabs: **داشبورد (Executive) · صف امروز · فرصت‌ها · پیشنهاد · قیف · AI**

Executive answers in &lt;30s: leads, hot leads, funnel bottleneck, pipeline/weighted, top agents, source money, property demand, data health.

---

## Known Issues / Technical Debt

1. Campaign cost → full ROI still null until campaign spend entry UI
2. Price-reduction auto-notify on property update not wired (columns ready)
3. Viewing reminders 24h/2h/30m scheduler not fully productized
4. Plan feature middleware not on every CRM route
5. Agent score weights table reserved; score formula still code defaults (configurable path ready)
6. Export Excel/PDF for reports not in this PR
7. Session device management / full audit center UI deferred to platform security module
8. PHPUnit not runnable in this agent VM (no PHP) — run on deploy host

---

## Future Roadmap

- L2 LLM with consent + minimization + monthly limits
- Workflow visual builder UI
- White-label domain/email
- Global search NLP
- Autonomous follow-up agent (L3)
- Scheduled digest reports via Telegram/Email

---

## Deploy

```bash
./scripts/deploy.sh cursor/crm-professional-a876
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test --filter=Crm
```

---

## Acceptance Mapping (Phase 3 scenarios)

| Scenario | Support |
|----------|---------|
| Instagram lead → score → follow-up | P1/P2 automation |
| Need → matching ranked | P2 + need profile |
| Property → reverse match | P2 (+ demand score P3) |
| Viewing → feedback → NBA | P2 + P3 NBA extensions |
| Offer → negotiation → deal → commission | P2 + existing commission |
| Dormant 90d → reactivation | P2 opportunities |
| Executive &lt;30s office pulse | **P3 Executive Dashboard** |

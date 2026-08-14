# CRM Phase 2 — Sales Engine (notes)

See full report: `docs/CRM-SALES-ENGINE-PHASE2-REPORT.md`

## Shipped
- Need profiles, matching engine (configurable weights, explainable, reverse)
- Sales queue, opportunities, daily briefing
- Automation rules + logs
- Offers, negotiations, presentations, feedback learning
- Deal checklist, campaigns
- Visit double-booking + complete workflow
- Frontend CRM tabs: queue / opportunities / offers / pipeline

## Deploy
```bash
./scripts/deploy.sh cursor/crm-professional-a876
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan test --filter=Crm
```

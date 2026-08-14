# FINAL-SECURITY-AUDIT

## Fixed / Hardened
- Scheduled publish claim gate  
- AI HTML sanitize on article save  
- Cron soft-fail (no deploy crash on audit findings)  
- Image prompt injection filter  
- Admin image/content APIs behind platform staff auth + throttles  

## Tests covered (unit)
- Prompt injection filter  
- Mock image illustrative disclosure  
- Refresh engine schema guard  

## Accepted residual risks
| Risk | Severity | Mitigation |
|------|----------|------------|
| Regex HTML sanitizer vs full allowlist | P1 accepted | Admin-only editors; strip script/iframe/on* |
| Demo seeder password in repo | P2 | `SKIP_DEMO_SEED=1` |
| CSP off by default | P2 | intentional until allowlist ready |
| MySQL port in compose | P2 | firewall / non-prod only |

## No live secrets found in frontend; API keys env-only.

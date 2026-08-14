# DISASTER-RECOVERY

## Scope

Blog/CMS + public site technical continuity (DB, media, config, SEO surfaces).

## Backup expectations

| Asset | Expectation |
|-------|-------------|
| Database | Regular automated backups (hosting/ops) with retention policy |
| Media (`storage/app/public`) | Included in backup or object storage versioning |
| Config / `.env` | Secured offline; never in git |
| Redirects / CMS content | In DB |

## Restore test

A backup is valid only after a **restore test** in an isolated environment (not production). Document last successful restore date in ops runbook.

## Failure playbooks

### Database failure
1. Stop writers if needed  
2. Restore latest verified backup to staging → validate  
3. Promote / restore production  
4. Re-run `migrate --force` only if schema lag  
5. `seo:technical-audit` + sitemap invalidate  

### Server / container failure
1. Redeploy release branch via `./scripts/deploy.sh cursor/production-release-a876`  
2. Health `/up`  
3. Verify `/robots.txt`, `/sitemap.xml`, sample `/blog/{slug}` 200  

### Storage failure
1. Restore media volume  
2. Confirm `/storage/blog/*`  
3. Avoid mass re-upload without inventory  

### API / queue failure
1. Check failed jobs / horizon/worker logs  
2. Retry safe jobs (`blog:publish-scheduled`, `seo:technical-audit`)  
3. Do not silent-fail cron — schedules are registered in `bootstrap/app.php`  

## SEO safety during recovery

- Do not ship emergency `Disallow: /`  
- Do not noindex entire blog  
- Preserve redirect map before URL surgery  

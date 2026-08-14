# PRODUCTION-OPERATIONS-MANUAL

## Daily
- Check failed jobs: Content Ops + Image queues  
- `blog:publish-scheduled` (every minute via scheduler)  
- Review critical SEO/NAP warnings in logs  

## Weekly
- `seo:analyze` / opportunities  
- `content:ops-audit --weekly`  
- `blog:image-audit`  
- Review decay / refresh P0–P1  

## Monthly
- `content:ops-audit --monthly`  
- Budget review (AI + images)  
- Dependency/security updates  

## Backup / Restore
1. DB dump + `storage/` media  
2. Test restore on staging before cutover  
3. Before mass rewrite/image replace: versions + previous cover URL retained on job  

## Incident
- AI down → local/mock fallback; editor remains usable  
- Budget exceeded → generation stops  
- Bad deploy → rollback release + migrate:rollback only with plan  

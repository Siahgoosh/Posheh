# PRODUCTION-DEPLOYMENT-CHECKLIST

```bash
./scripts/deploy.sh cursor/production-release-a876
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan seo:local-bootstrap
docker compose exec -T app php artisan content:ops-audit --bootstrap --process=5
docker compose exec -T app php artisan blog:image-audit --bootstrap --dry-run
docker compose exec -T app php artisan seo:technical-audit --scope=manual
```

## Checklist
- [ ] HTTPS  
- [ ] `APP_DEBUG=false`  
- [ ] robots.txt / sitemaps 200  
- [ ] Canonical host aligned  
- [ ] Scheduler running (`schedule:work` or cron)  
- [ ] Queue workers if using redis queues  
- [ ] Backup verified  
- [ ] Restore drill documented  
- [ ] OpenAI flags off unless intended  
- [ ] Image budget configured before paid provider  
- [ ] Admin `/admin/content-ops` and `/admin/blog-images` reachable  
- [ ] `content:ops-audit` no longer errors on `seo_content_health.status`  

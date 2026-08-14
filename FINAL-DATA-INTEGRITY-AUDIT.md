# FINAL-DATA-INTEGRITY-AUDIT

## Fixed
- Wrong column `seo_content_health.status` → `health`  
- NAP scan guards missing `seo_business_profiles`  
- Property listings on location pages: Active + show_on_website only  

## Checks
- Blog unique slugs (validation)  
- Redirect loop protection on slug change  
- Image job idempotency keys  
- Content AI job idempotency keys  
- Location quality gate before publish  

## Ops after migrate
```bash
php artisan migrate --force
php artisan seo:local-bootstrap
php artisan content:ops-audit --bootstrap
php artisan blog:image-audit --bootstrap --dry-run
```

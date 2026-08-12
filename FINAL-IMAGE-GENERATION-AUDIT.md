# FINAL-IMAGE-GENERATION-AUDIT

## Status: IMPLEMENTED (human approval required)

### Capabilities
- Full article image inventory audit (`blog_image_audits`)  
- Eligibility: skips thin / archive / low opportunity  
- Brief + prompt from article context (not title-only)  
- Providers: Mock (default) / OpenAI Images (opt-in)  
- Queue statuses + retries + idempotency  
- Dry-run + estimated cost + explicit mass confirm  
- Pause / resume / cancel batches (keeps completed)  
- Approve / reject / regenerate with reject reason feedback  
- Illustrative metadata — never claims real property photo  
- Admin UI: `/admin/blog-images`  

### Policy
Goal is **not** “an image for every article”.  
Goal is valuable, relevant images for eligible articles at controlled cost.

### Deploy
```bash
php artisan migrate --force
php artisan blog:image-audit --bootstrap --dry-run
php artisan blog:image-process --limit=10
```

### Counts
Filled after production `blog:image-audit` run (depends on live article set).

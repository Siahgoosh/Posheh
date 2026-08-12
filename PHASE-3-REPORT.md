# PHASE-3-REPORT — Professional Blog CMS + SEO Automation

**تاریخ:** 2026-08-12  
**شاخه:** `cursor/seo-blog-cms-a876`  
**پایه:** ادامه PHASE 1 (Audit) + PHASE 2 (Batch 1 drafts)  
**قانون توقف:** بدون mass publish / بدون bulk rewrite جدید

---

## Implemented Features

### Public Blog
- Homepage غنی‌تر: Hero + Featured + Latest + Popular + Categories + Search + CTA + Pagination
- Article page: Cover در بدنه، Excerpt، Updated date، TOC (collapsible/sticky)، Share (Telegram/WhatsApp/X/Copy)، Prev/Next، Related، FAQ، CTA
- Search: `/blog/search` + API — نرمال‌سازی فارسی (ی/ي، ک/ك، نیم‌فاصله)، `noindex,follow`
- Category landing با description/SEO از جدول `blog_categories` + pagination
- Tag page `/blog/tag/{slug}` (پیش‌فرض noindex مگر `is_indexable`)
- Author page `/blog/author/{slug}`
- Preview noindex: `/blog/preview/{token}`
- RSS: `/feed` و `/blog/feed`
- 404 با Search + Popular (بدون redirect اشتباه به Home)

### CMS Admin
- Dashboard: counts، freshness buckets، duplicate titles signal، GSC placeholder، SEO health داخلی
- Workflow: Draft / In Review / Approve / Schedule / Publish / Unpublish (+ Quality Gate)
- Preview token، Version restore، Suggest internal links
- Category / Tag / Author / Redirect CRUD
- Tag merge، Bulk actions (category/status/export)، CSV export
- Bootstrap دسته‌ها + نویسنده «تیم محتوای پوشه»

### SEO / Indexing infra
- Sitemap Index: `/sitemap.xml` → pages / posts / categories
- Cache + invalidate روی publish/update/delete
- lastmod از `content_updated_at` (نه view)
- robots: Disallow search/preview/admin/api — Allow blog/feed
- Canonical / robots / OG fields روی پست
- Redirect middleware (301 default) + hit count
- Related scoring قابل تنظیم در `config/blog.php`
- View counter با anti-bot + cooldown ۳۰ دقیقه
- SearchAction Schema به `/blog/search`

### URL Architecture (حفظ‌شده)
- `/blog`
- `/blog/category/{slug}` ← **عمداً حفظ شد** (نه `/blog/{category}` چون با اسلاگ مقاله conflict دارد)
- `/blog/{article}`
- جدید: `/blog/tag/{slug}`, `/blog/author/{slug}`, `/blog/search`

---

## Database Changes

Migration: `2026_08_12_110000_create_blog_cms_entities.php`

| Table / Column | Purpose |
|----------------|---------|
| `blog_categories` | CMS دسته + SEO landing |
| `blog_tags` + `blog_post_tag` | تگ کنترل‌شده |
| `blog_authors` | نویسنده واقعی (بدون جعل) |
| `blog_related_posts` | related دستی/خودکار (آماده) |
| `blog_audit_logs` | audit اقدامات |
| `blog_broken_links` | مانیتور لینک شکسته (جدول آماده) |
| `blog_gsc_metrics` | cache GSC (placeholder) |
| posts: `blog_category_id`, `blog_author_id`, OG fields, `content_updated_at`, `is_featured`, `is_editors_pick`, `view_score`, `preview_token` | |

Rollback: `down()` جداول/ستون‌ها را برمی‌گرداند.

---

## API Changes

### Public
- `GET /api/v1/blog/home`
- `GET /api/v1/blog/search`
- `GET /api/v1/blog/category/{slug}`
- `GET /api/v1/blog/tag/{slug}`
- `GET /api/v1/blog/author/{slug}`
- `GET /api/v1/blog/preview/{token}`
- `GET /api/v1/blog/feed`
- `GET /api/v1/blog/{slug}/related-suggestions`
- Existing index/show/categories/sitemap retained

### Admin
- `/admin/blog/dashboard`, `/bootstrap`, `/export.csv`, `/bulk`
- `/admin/blog/cms/categories|tags|authors|redirects`
- `/admin/blog/{id}/publish|unpublish|schedule|preview-token|submit-review|approve`
- `/admin/blog/{id}/versions/{versionId}/restore`
- `/admin/blog/{id}/suggest-links`

---

## SEO Changes
- Sitemap index + child maps
- noindex برای search/preview/non-indexable tags
- Robots Disallow search/preview
- BlogPosting JSON-LD + Person author page
- Content-aware lastmod

## Blog UI Changes
- SPA BlogList/Search/Post upgraded (RTL, cards, pagination, share)
- SSR Blade index/category/search/tag/author/404 upgraded
- Admin list → dashboard cards + bootstrap/export

## Sitemap Changes
- `/sitemap.xml` = index
- `/sitemap-pages.xml`, `/sitemap-posts.xml`, `/sitemap-categories.xml`
- Nginx routes added
- Cache TTL: `config('blog.sitemap_cache_ttl')`

## Search Console Integration
- **Placeholder architecture only**
- Table `blog_gsc_metrics` + dashboard status
- Requires `BLOG_GSC_ENABLED=true` + `BLOG_GSC_CREDENTIALS_JSON`
- Explicit message: Sitemap ≠ indexing guarantee

## Security Changes
- Redirect manager admin-only
- Preview URLs noindex
- Upload MIME allowlist via `config/blog.php`
- Publish blocked by Quality Gate
- View increment ignores common bots
- Audit log with optional IP/user

## Performance Changes
- Sitemap payload cached
- Search limited to 500 recent candidates (acceptable for current scale; upgrade to fulltext later)
- Pagination on list/category
- Cover eager on article; lazy on cards
- Related suggestions capped

## Cron
- `blog:publish-scheduled` every minute in `bootstrap/app.php`

## Deploy
- `blog:cms-bootstrap` after migrate
- Mass seed still opt-in (`BLOG_SEED_ON_DEPLOY`)

---

## Testing Results

| Test | Result |
|------|--------|
| Unit: Persian normalizer / reading time / short search | Added (`BlogCmsPhase3ServicesTest`) |
| Unit: Batch1 rebuild gate (Phase 2) | Retained |
| Live PHP/Docker in agent env | Not available — run on server via compose |
| Manual QA checklist | See below — execute after deploy |

### Server commands
```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/seo-blog-cms-a876
# or:
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan blog:cms-bootstrap
docker compose exec -T app php artisan test --filter=Blog
```

### Manual QA after deploy
1. Create draft in Admin → Preview token → open `/blog/preview/...` (noindex)
2. Submit review → Approve → Schedule (optional) → Publish (must pass gate)
3. Confirm URL `/blog/{slug}`, canonical, schema, sitemap-posts includes URL
4. Edit content → `content_updated_at` / sitemap lastmod changes
5. Create 301 in Redirect manager → hit old path
6. Search `/blog/search?q=crm` → noindex
7. Do **not** mass-publish Batch 1 drafts without human approval

---

## Known Issues / Pending Tasks

- Broken-link crawler job not yet scheduled (table ready)
- GSC live sync not wired (needs credentials)
- Fulltext / Meilisearch for large corpus later
- Author/Tag SPA pages (SSR+API ready; SPA routes optional next)
- AI assistant inside editor deferred (safety rules documented)
- Permission roles granular (Editor/SEO Manager) deferred — currently platform admin
- Active TOC highlight on scroll (basic TOC shipped)
- AVIF conversion pipeline not added (WebP/upload MIME ready)
- Infinite scroll intentionally avoided

---

## Final STOP

Phase 3 infrastructure complete.  
**No mass article publish. No Batch 2 rewrite started.**  
Next stage (when approved): SEO Audit + Mass Content Rebuild + GSC Optimization.

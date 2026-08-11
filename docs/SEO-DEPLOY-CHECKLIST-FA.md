# SEO readiness — Posheh (2026-08-11)

## Fixed in this branch (deploy to activate)

| Item | Status |
|------|--------|
| `robots.txt` with Disallow + Sitemap lines | Fixed (backend/public + frontend/public) |
| `/sitemap.xml` + `/sitemap-blog.xml` + `/sitemap-pages.xml` + `/sitemap-tours.xml` | Live Laravel routes + nginx force to PHP |
| `php artisan sitemap:generate` | Implemented (also in deploy.sh) |
| Soft-404 `*` → homepage | Replaced with `NotFoundPage` + noindex |
| Blog title `\| پوشه \| پوشه` | Deduped |
| Blog `noindex` / article times in SSR layout | Added |
| Tours in sitemap | Via `TourSeoService` |
| www → apex 301 | nginx |
| `VITE_SITE_URL` / `FRONTEND_URL` on deploy | Set to https://posheapp.ir |
| Property / office / embed / auth SEO | SeoHead + schema / noindex where needed |
| `og-default.png` | Added |

## After deploy — Google Search Console

1. Confirm:
   ```bash
   curl -sI https://posheapp.ir/robots.txt | head -5
   curl -sI https://posheapp.ir/sitemap.xml | head -5
   curl -sI https://posheapp.ir/sitemap-blog.xml | head -5
   # Content-Type must be application/xml (not text/html)
   ```
2. Submit sitemaps in GSC:
   - `https://posheapp.ir/sitemap.xml`
   - `https://posheapp.ir/sitemap-blog.xml`
   - `https://posheapp.ir/sitemap-pages.xml`
   - `https://posheapp.ir/sitemap-tours.xml`
3. Request indexing for `/`, `/blog`, `/register`, `/download`, top pillar posts.
4. Fix any Coverage issues (soft 404s should drop after NotFoundPage deploy).

## Still medium-term (not blockers)

- Full SSR/prerender for landing/register (currently JS meta; blog already SSR)
- Dedicated `/features` and `/pricing` pages from SEO-POSHE plan
- Host blog cover images locally (avoid hotlinked Unsplash)
- Pagination for `/blog` SSR list beyond 50 posts

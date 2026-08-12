# BLOG-API-DOCUMENTATION

Base: `/api/v1` — Admin routes require Sanctum + platform staff.

## Public

| Method | Path | Notes |
|--------|------|-------|
| GET | `/blog` | List |
| GET | `/blog/home` | Home blocks |
| GET | `/blog/search` | Search |
| GET | `/blog/categories` | Categories |
| GET | `/blog/category/{slug}` | Archive |
| GET | `/blog/tag/{slug}` | Tag archive |
| GET | `/blog/author/{slug}` | Author archive |
| GET | `/blog/preview/{token}` | Preview |
| GET | `/blog/feed` | RSS |
| GET | `/blog/sitemap` | Sitemap helper |
| GET | `/blog/{slug}` | Article |
| GET | `/blog/{slug}/related-suggestions` | Related |

## Admin — articles

| Method | Path | Notes |
|--------|------|-------|
| GET | `/admin/blog` | Filters: review_status, category_slug, q, robots, pagination |
| GET | `/admin/blog/health` | Counts |
| GET | `/admin/blog/dashboard` | CMS dashboard |
| GET/POST | `/admin/blog/{id}` / `/admin/blog` | Show / create |
| PUT | `/admin/blog/{id}` | Update (+ slug protection) |
| DELETE | `/admin/blog/{id}` | Soft trash |
| POST | `/admin/blog/{id}/restore-trash` | Restore |
| DELETE | `/admin/blog/{id}/force` | Hard delete |
| POST | `/admin/blog/{id}/publish` | Publish + checklist |
| POST | `/admin/blog/{id}/unpublish` | |
| POST | `/admin/blog/{id}/schedule` | |
| POST | `/admin/blog/{id}/archive` | |
| POST | `/admin/blog/{id}/submit-review` | |
| POST | `/admin/blog/{id}/approve` | |
| POST | `/admin/blog/{id}/preview-token` | |
| POST | `/admin/blog/{id}/autosave` | |
| GET | `/admin/blog/{id}/autosave` | Recover |
| POST/DELETE | `/admin/blog/{id}/lock` | Concurrent edit |
| GET | `/admin/blog/{id}/versions/{versionId}/compare` | |
| POST | `/admin/blog/{id}/versions/{versionId}/restore` | |
| POST | `/admin/blog/analyze-seo` | Internal guidance |
| POST | `/admin/blog/publish-checklist` | |
| GET | `/admin/blog/ai/actions` | |
| POST | `/admin/blog/ai/assist` | Throttled; never publishes |
| POST | `/admin/blog/upload-image` | |
| POST | `/admin/blog/upload-cover` | |

## Admin — CMS

| Method | Path | Notes |
|--------|------|-------|
| GET | `/admin/blog/calendar` | |
| GET/DELETE | `/admin/blog/media` | Usage-checked delete |
| CRUD | `/admin/blog/cms/categories\|tags\|authors\|redirects` | |
| POST | `/admin/blog/bulk` | Requires confirm_affected (+ confirm_destructive) |
| GET | `/admin/blog/export.csv` | |
| GET | `/admin/blog/{id}/suggest-links` | |

## AuthZ / rate limits

Authenticated platform staff. AI assist throttled `30/min`. Uploads size/MIME limited.

# TECHNICAL-SEO-AUDIT

## Scope

Full-site technical SEO controls for Posheh public surfaces (Home, Blog, Category, Article, Contact, Login) plus admin tooling.

## Severity legend

CRITICAL / HIGH / MEDIUM / LOW / INFO

## Findings (codebase audit + automated engine)

| Severity | Area | Finding | Status |
|----------|------|---------|--------|
| CRITICAL | Sitemap cache | Index XML cache key `v3` was not invalidated on publish | **Fixed** |
| HIGH | SPA parity | Article SPA ignored `canonical_url` / `robots_directive` | **Fixed** |
| HIGH | Category SPA | Always indexable; now respects `is_indexable` | **Fixed** |
| HIGH | Meta | Published posts may still miss meta_description (content debt) | Detected by `seo:technical-audit` |
| MEDIUM | Images | Blog lacks automated AVIF/WebP responsive variants | Documented; upload allows WebP/AVIF |
| MEDIUM | Orphans | Many posts lack related_slugs/tags | Detected; no blind mass rewrite |
| INFO | robots | Laravel dynamic robots is canonical; SPA static aligned | OK |
| INFO | Hreflang | Site is FA-only — hreflang **not** added | Correct |
| INFO | Search | `/blog/search` noindex + robots Disallow | OK |
| INFO | Field CWV | No CrUX wired | UNKNOWN |

## How to run

```bash
php artisan seo:technical-audit --scope=weekly --scan-links
# optional live probes:
php artisan seo:technical-audit --probe
```

Admin: `/admin/seo-technical`

## URL policy

- HTTPS preferred
- Host: `posheapp.ir` (www→apex nginx redirect)
- Trailing slash: omit
- UTM/click IDs are tracking params (canonical should ignore; self-canonical on content URLs)

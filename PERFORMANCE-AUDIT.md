# PERFORMANCE-AUDIT

## Principles

- Primary metrics: **LCP, INP, CLS** (not outdated primary metrics).
- Field data vs Lab data must not be mixed.
- Current Field/Lab in this environment: **UNKNOWN**.

## Budgets (config)

See `config/performance.php` and `PERFORMANCE-BUDGET.md`.

## Changes in Phase 8

1. Hero/cover images: **not lazy**, width/height set → reduce CLS/LCP risk.
2. Nginx **gzip** for text/JS/CSS/JSON/SVG.
3. Bundle: Vite dual entry already (main/panel); further route-level splitting remains incremental.
4. Analytics: first-party async tracker only (no GTM/GA third-party in app shell).
5. Blog list APIs paginated; admin list capped; new DB indexes for filter/sitemap hot paths.
6. Sitemap payload cached; invalidate on content change (including xml v3).

## Residual

- Full blog image CDN + srcset pipeline not complete (Virtual Tour has variants).
- HTTP/2/HTTP3 / Brotli depend on TLS edge — not forced in HTTP docker nginx.
- No Lighthouse CI in repo yet — add when CI available; treat as Lab only.

## Important pages to retest after deploy

Home, Blog, Category, Article, Landing, Contact, Login — Desktop + Mobile.

# SECURITY-AUDIT

## Baseline (Phase 8 + prior)

| Control | Status |
|---------|--------|
| AuthN Sanctum | Present |
| Admin AuthZ staff gate | Present |
| Login/OTP rate limits | Present |
| Lead honeypot + rate limit | Phase 6 |
| AI assist throttle + backend-only | Phase 7 |
| Upload MIME/size/sanitize | Phase 7 |
| Soft trash for articles | Phase 7 |
| Security headers middleware | **Added** (nosniff, frame, referrer, permissions, HSTS on HTTPS) |
| CSP | **Off by default** — enable via `SECURITY_HEADERS_CSP=true` after staging |
| Nginx headers | Present |
| Cookies | Sanctum/session — review Secure/SameSite in production `.env` |
| IDOR | Admin blog IDs behind staff; tenant resources use office scoping elsewhere |
| XSS | Admin HTML trust surface; public SSR content is staff-authored |
| SQLi | Eloquent parameterization |
| CSRF | Sanctum SPA |

## Critical alerts (ops)

Alert if: sitewide noindex, robots blocking `/blog`, sitemap 5xx, HTTPS/cert failure, 5xx spike, DB down.

## Do not

- Force CSP in production without test.
- Expose AI keys in frontend.
- Blind-redirect all 404s to home.

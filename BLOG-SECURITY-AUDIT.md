# BLOG-SECURITY-AUDIT

## Scope

Admin Blog CMS, editor uploads, AI assist, redirects, publish workflow (Phase 7).

## Controls present

| Area | Control |
|------|---------|
| AuthN | Sanctum session/token |
| AuthZ | Platform staff middleware on admin routes |
| XSS / HTML | Content stored as HTML; public render must keep sanitization/CSP as deployed; AI HTML not auto-saved without human action |
| CSRF | Sanctum/SPA cookie flow |
| SQLi | Eloquent / query builder |
| Uploads | `image` validation, MIME `jpeg,jpg,png,webp,gif`, size caps, filename sanitization, stored under `public/blog` |
| SVG | Not accepted in blog upload allow-list |
| Path traversal | Media delete requires `blog/` prefix |
| IDOR | Admin IDs scoped behind staff gate (not tenant-user) |
| Unauthorized publish | Quality gate + checklist; AI cannot publish |
| AI keys | Backend only; none in frontend |
| Rate limit | AI assist throttled |
| Audit | `blog_audit_logs` on create/update/trash/approve/publish paths |
| Slug hijack | Unique slug + published change protection + 301 |
| Concurrent edit | Lock + 409 conflict |

## Residual risks / follow-ups

1. Fine-grained CMS roles not yet enforced in middleware (documented matrix).
2. Stored HTML in articles remains a trust-admin surface — keep admin accounts tightly controlled.
3. Media listing scans storage + usage queries; large libraries may need indexed usage table later.
4. External LLM provider (if enabled later) must keep keys server-side and log token usage.

## Verdict

Phase 7 security baseline is acceptable for staff-only CMS with soft delete, upload allow-list, AI backend proxy, and publish gates. No claim of “penetration-test complete.”

# REDIRECT-MAP

## Infrastructure redirects

| From | To | Type | Notes |
|------|----|------|-------|
| `http://www.posheapp.ir/*` | `https://posheapp.ir/*` | 301 | nginx |
| Prefer HTTPS apex | — | policy | `performance.url_policy` |

## Blog redirects

Managed in `blog_redirects` via Admin CMS + `BlogRedirectMiddleware` on `/blog*`.

- Permanent moves: **301**
- Slug change on published article: auto-create `/blog/{old}` → `/blog/{new}` with loop/chain checks
- Store-time safety: reject from=to, A→B when B→A or B already redirects (chain risk)

## 404 policy

- Real missing article: **404** (not soft home)
- Decide per URL: Keep 404 / 301 to replacement / restore / 410
- **Forbidden:** redirect all 404 → Home

## Ops

Export active redirects from admin Redirect Manager. After bulk slug changes, run `seo:technical-audit` to detect chains/loops.

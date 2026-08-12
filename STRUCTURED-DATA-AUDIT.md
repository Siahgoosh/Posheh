# STRUCTURED-DATA-AUDIT

## Emitted types (SSR article)

- `BlogPosting` (title, dates, author, image, mainEntityOfPage, inLanguage fa-IR)
- `BreadcrumbList` (Home → Blog → Category? → Article)
- `FAQPage` only when FAQ items exist and are rendered in template

## SPA mirrors

Article / list helpers emit Article/Breadcrumb/FAQ/Organization/WebSite — keep consistent with visible content.

## Rules

- Do not claim ratings, reviews, or inventory that are not on-page.
- FAQ schema only if FAQ visible.
- No hreflang (single language).
- Avoid duplicate contradictory entity graphs for the same article.

## Validation

Re-validate with Google Rich Results Test / Schema validators after deploy. Automated engine only checks presence signals — not live Google validation.

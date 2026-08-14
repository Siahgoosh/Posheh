# BLOG-CMS-DOCUMENTATION

## Overview

Posheh Blog CMS is the admin publishing layer for `/blog`. Phase 7 completes the professional editor, workflow, media library, calendar, AI assist (backend-only), and SEO panel on top of Phases 3–6.

## Lifecycle statuses

`draft` → `in_review` → (`seo_review` / `content_review`) → `approved` → `scheduled` | `published` → `archived` | `unpublished` | `trash`

Trash is soft (`review_status=trash`, `noindex`). Force delete requires explicit confirmation.

## Admin UI routes

| Path | Purpose |
|------|---------|
| `/admin/blog` | Dashboard + article list + filters + bulk |
| `/admin/blog/new` | Create |
| `/admin/blog/:id/edit` | Professional editor |
| `/admin/blog/calendar` | Content calendar |
| `/admin/blog/media` | Media library + unused detection |

(Panel subdomain uses the same segments without `/admin` prefix.)

## Article fields (core)

Title, slug, excerpt, content, cover, author, category, tags, status, published_at, scheduled_at, reading_time, word_count, SEO/OG/canonical/robots, focus topic, search intent, content type, funnel stage, FAQ, CTA/`cro_cta_key`, sources, schema_type, content_brief, revisions, autosave payload, edit lock.

## Editor features

- RTL Persian editor blocks (headings, lists, checklist, table, embeds, CTA, FAQ, TOC)
- Autosave (local + server) + Recover Draft
- Revisions restore
- Publish checklist (blocking vs advisory)
- SERP/OG previews labeled **Preview**
- AI assistant panel (suggestions only)
- Cannibalization warning
- Internal link Accept/Reject
- Schedule with timezone `Asia/Tehran`
- Concurrent edit lock warning

## Related services

- `BlogPublishService`, `BlogQualityGate`, `BlogPublishChecklistService`
- `BlogVersioningService`, `BlogAiAssistantService`
- `BlogSitemapService`, `BlogRelatedArticlesService`
- `PersianTextNormalizer`, `BlogAuditLogger`
- Phase 6 CRO for CTA resolution priority: Article → Category → Global

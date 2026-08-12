# PUBLISHING-WORKFLOW

## Default flow

```
AUTHOR creates Draft
  → Autosave / Save
  → AI Brief / Outline (optional, human-approved)
  → Submit Review (in_review)
  → SEO / Content check
  → Approve (approved, last_reviewed_at)
  → Schedule (optional) OR Publish
  → Automation: version snapshot, audit log, sitemap invalidate, RSS reflects published set
```

## Publish checklist (blocking examples)

- Title present
- Content length adequate
- Valid slug
- Category
- Author
- SEO title + meta description
- Internal quality floor (internal guidance, not Google Score)

Advisory: cover image, FAQ, CTA, intent, internal links, canonical override warning.

## Schedule

`POST /admin/blog/{id}/schedule` with `scheduled_at` (future) + optional `timezone`.

## Unpublish / Archive / Trash

- Unpublish: public off, status unpublished
- Archive: off + `noindex,follow`, removed from indexable sitemap policy
- Trash: soft delete + `noindex,nofollow`; force delete separate

## Slug change (published)

Blocked unless `confirm_slug_change=1` → creates 301 `/blog/{old}` → `/blog/{new}` with loop check.

## Simplification

Small teams may Approve then Publish without separate SEO/Content review roles (roles documented; runtime is platform staff today).

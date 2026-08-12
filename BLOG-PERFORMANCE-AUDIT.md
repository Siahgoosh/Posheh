# BLOG-PERFORMANCE-AUDIT

## Editor

- Autosave debounced (~4s) to localStorage + server JSON payload
- SEO/checklist analysis debounced (~700ms)
- Rich text is `contentEditable` (lightweight vs full ProseMirror); suitable for current scale
- Avoid loading all revisions beyond last 20

## Admin list

- Paginated (`per_page` max 50)
- Filters on `review_status`, search on title/slug/focus_keyword
- Bulk actions operate on selected IDs only (never “all articles” by default)

## Media library

- Lists files under `storage/app/public/blog` with pagination
- Usage checks query `cover_image` / `content` / `og_image` — OK for moderate libraries; consider usage index if file count grows large

## Public blog

- Existing sitemap/RSS invalidation on publish/update
- Related articles limited candidate scan (200) with scoring
- Reading time / word count computed on save, not on every public request

## Cache

- Sitemap invalidate on content changes
- Page/object cache (if enabled in infra) must purge on publish — follow deploy cache policy
- Simple saves that do not change body should not blindly bump SEO `content_updated_at` / lastmod semantics (Phase 7 save path bumps only when content differs)

## Acceptable for Phase 7

Editor and list performance are acceptable for staff workflows. No bulk rewrite of ~400 articles was run in this phase.

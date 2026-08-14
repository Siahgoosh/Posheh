# AI-WRITING-ASSISTANT

## Policy

AI is an **assistant**, never the publisher.

- Does **not** auto-publish articles, titles, meta, facts, statistics, or legal claims.
- All requests go through backend: `POST /api/v1/admin/blog/ai/assist`
- No AI API keys in frontend.
- On failure: editor stays usable; message `AI Assistant Temporarily Unavailable`.

## Prompt versions

| Version | Use |
|---------|-----|
| `CONTENT_WRITER_V1` | Outline, titles, intro, conclusion, FAQ, brief, CTA |
| `CONTENT_EDITOR_V1` | Simplify, expand, Persian normalize preview |
| `SEO_ANALYZER_V1` | Internal links, cannibalization, intent suggest |

## Actions

`outline`, `titles`, `meta_description`, `excerpt`, `slug`, `faq`, `brief`, `intro`, `conclusion`, `simplify`, `expand`, `cta`, `normalize_persian`, `internal_links`, `cannibalization_check`, `intent_suggest`, `draft`, `image_brief`, `refresh_plan`, `repurpose_hints`

## Phase 10

Full Content OS jobs/queue/cost/editorial live under `/admin/content-ops` — see `AI-CONTENT-OPERATIONS.md`. Same rule: **never auto-publish**.

## Output rules

- Meta/excerpt built from existing title/content only — no invented claims.
- Normalize Persian is preview-only until human applies/saves (revision protects undo).
- Cannibalization returns WARNING + UPDATE EXISTING / CREATE NEW recommendation.
- Rate limit: throttle 30/min on assist endpoint.
- Usage: local heuristic by default (deterministic, no external LLM required). External LLM can be plugged later behind the same API without UI changes.

## Human review required

Every AI result must be Accept/Edit by an admin before Save/Publish.

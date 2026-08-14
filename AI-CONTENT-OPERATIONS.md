# AI-CONTENT-OPERATIONS

Content Operating System for Posheh Blog/SEO.

## Principle

AI = **ASSISTANT**. Not autonomous publisher (unless admin explicitly enables workflow flags — all off by default).

## Components

- **Pipeline statuses** on `blog_posts.ops_status`
- **Job queue** `content_ai_jobs` (async via `content:process-ai-jobs`)
- **Provider abstraction** Mock / Local / OpenAI
- **Cost control** limits + `ai_usage_logs` bridge
- **Fact check** `content_claims`
- **Internal links / images / refresh / repurpose** engines
- **Admin** `/admin/content-ops` + APIs under `/api/v1/admin/content-ops/*`

## Persian-first writing

Drafts/outlines/briefs prefer useful, natural Persian. No mandatory word-count targets. Anti-generic rules in style profile + assistant prompts.

# IMAGE-GENERATION-WORKFLOW

```
Scan articles → Score opportunity → Skip thin/archive
→ Dry run (cost estimate) → Admin confirm
→ Enqueue batch → Async process → Preview
→ Approve | Reject(+reason) | Regenerate
→ Optional sitemap invalidate if published
```

Auto-approve off by default (`BLOG_IMAGE_AUTO_APPROVE`).

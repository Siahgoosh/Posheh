# FINAL-CONTENT-AUDIT

## Method
Code-level inventory + quality scorers + image audit eligibility. Live 400+ classification requires production DB after deploy.

## Categories (process)

| Bucket | Action |
|--------|--------|
| High quality | Maintain / light refresh |
| Medium | Improve FAQ/links/images |
| Thin / low value | Do **not** generate AI images; Improve/Merge/Archive |
| Duplicate / cannibalization | Prefer Update Existing |
| Outdated / decay | Content Ops refresh (surgical) |
| Archive candidates | No mass rewrite; redirect strategy first |

## Rules enforced
- No mass rewrite without backup/dry-run  
- AI drafts never auto-publish  
- Sensitive claims need human fact review  

## Image eligibility
`blog:image-audit` skips thin/low-traffic/archive candidates before generation.

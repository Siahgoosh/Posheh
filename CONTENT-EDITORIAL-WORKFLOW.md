# CONTENT-EDITORIAL-WORKFLOW

## Roles (practical mapping)

| Capability | Who |
|------------|-----|
| Generate AI jobs | Admin / SEO / Editor (admin API auth) |
| Approve / publish | Admin / Editor |
| Prompt/config/cost limits | Admin |
| Billing/users/security | Super Admin only (existing) |

AI_OPERATOR-style users must not change billing/security — Content Ops APIs sit behind existing admin auth; cost/prompt edits are admin-only routes.

## Actions

Approve · Reject · Request changes · Comment (section/block) · Transition ops_status · Review claims

## Approval log

`content_approval_logs`: user, time, action, from/to status, meta.

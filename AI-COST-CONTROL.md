# AI-COST-CONTROL

## Per job

Stores: prompt/completion/total tokens, estimated + actual cost (toman), model, duration, status.

## Limits (`content_ai_cost_limits`)

Scopes: `daily`, `monthly`, `per_article`, `per_user`  
Bootstrap creates rows **inactive** until admin sets budget.

## Dashboard

`/admin/content-ops` + `GET /admin/content-ops/usage`  
Shows jobs, tokens, cost, avg duration, top tasks.

## Abuse protection

Enqueue rejects when limits exceeded. Throttle on job create (30/min). Secrets never logged.

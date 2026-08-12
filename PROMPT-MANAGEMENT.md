# PROMPT-MANAGEMENT

## Task configs (`content_ai_task_configs`)

Per task: model, temperature, max_tokens, system_prompt, timeout, retry, cost_limit, active flag.

Admin: `GET/PUT /admin/content-ops/task-configs`

## Versioning

Legacy CRM `ai_prompt_templates` (key+version) remains. Content Writer versions:

- `CONTENT_WRITER_V1`  
- `CONTENT_EDITOR_V1`  
- `SEO_ANALYZER_V1`  

## Security

System prompts redacted for secrets on save. Untrusted article text wrapped in `USER_CONTENT` and filtered for injection.

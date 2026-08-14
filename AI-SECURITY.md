# AI-SECURITY

## Hard rules

- No API keys in frontend, prompts, or plain logs  
- Prompt injection patterns filtered  
- System instructions separated from user/article content  
- AI HTML sanitized (no script/iframe/on*)  
- No hidden text / keyword stuffing / fake reviews generation  
- Graceful failure: editor usable; message “AI Assistant Temporarily Unavailable”  
- IDOR: admin routes require auth; jobs scoped by IDs with existence checks  

## Tests

`Phase10ContentOpsTest` covers injection, sanitizer, sensitive claims, mock provider.

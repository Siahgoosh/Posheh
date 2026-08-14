# FINAL-AI-AUDIT

## Principle
AI = assistant. Not autonomous publisher.

## Systems
- Blog AI assist (Phase 7)  
- Content Ops jobs (Phase 10)  
- Blog Image jobs (Phase 11.150)  

## Safety
- Prompt isolation + injection filter  
- Output HTML sanitize on save  
- Cost limits (content + images)  
- Idempotent jobs + exponential retry  
- Fact-check blocking claims on publish/schedule  
- Image: dry-run → confirm → queue → preview → human approve  

## Providers
- Local/Mock default  
- OpenAI content/images **off** unless env flags + keys  

## Open items
- Full HTML allowlist sanitizer (accepted residual risk)  
- Enable budgets before paid providers  

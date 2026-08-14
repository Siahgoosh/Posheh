# IMAGE-PROVIDER-CONFIG

Env:
- `BLOG_IMAGE_PROVIDER=mock|openai`  
- `BLOG_IMAGE_OPENAI_ENABLED=false`  
- `BLOG_IMAGE_OPENAI_KEY` / `OPENAI_API_KEY`  
- `BLOG_IMAGE_OPENAI_MODEL`  
- `BLOG_IMAGE_RESOLUTION`  
- `BLOG_IMAGE_AUTO_APPROVE=false`  
- `BLOG_IMAGE_REQUIRE_BUDGET=true`  

API keys never exposed to frontend. DB config stores model/cost flags, not secrets.

# IMAGE-SECURITY

- Admin-only APIs + throttles  
- Prompt treats article as untrusted  
- Injection patterns filtered  
- Storage under `blog/ai-images/Y/m`  
- Upload endpoints remain mime/size validated (existing)  
- SVG mock is generated server-side (not user upload)  
- No infinite generation without budget/throttle  

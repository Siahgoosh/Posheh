# FINAL-PERFORMANCE-AUDIT

## Status: CODE-READY / FIELD UNKNOWN

Lab/Field CWV must be measured on production — values not invented.

## Hardening present
- Nginx gzip  
- Hero eager + dimensions guidance  
- Lazy content images (frontend patterns)  
- Sitemap/app caches with invalidation  
- AI/image jobs async (do not block public requests)  
- DB indexes from Phase 8 blog/SEO migrations  

## Image generation impact
- Mock SVG lightweight  
- Approved covers should set width/height; WebP preferred when provider supports  
- Compare article with/without hero after first batch  

## Recommendations
1. Run Lighthouse/CrUX on top templates post-deploy  
2. Compress uploaded covers  
3. Keep AI image work on queue workers  

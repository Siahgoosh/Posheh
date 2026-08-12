# IMAGE-BATCH-GENERATION

Default batch size 50 (configurable).

1. `POST /admin/blog-images/dry-run`  
2. Review count + estimated cost  
3. `POST /admin/blog-images/batches/confirm` with `confirm=true`  
4. `blog:image-process` / Process Queue  
5. Approve individually (mass approve requires explicit future confirmation UX)

Pause/Resume/Cancel supported; completed images retained on cancel.

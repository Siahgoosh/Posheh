# CRAWLABILITY-AUDIT

## Crawl entry points

- `/robots.txt` (Laravel)
- `/sitemap.xml` index → pages / posts / categories / tours
- Internal links from Home → Blog → Category → Article
- SSR `/blog*` via nginx → PHP (crawler-friendly HTML)

## Allow / Disallow

Allowed: `/`, `/blog`, register, download, contact, feed, tour public paths.  
Disallowed: dashboard/admin/api/app surfaces, `/blog/search`, `/blog/preview/`.

## Depth

Important articles should be linked from category + related + sitemap. Orphan signal counted in technical audit (related_slugs/tags missing).

## Broken links

`BrokenLinkScannerService` scans published HTML anchors; stores unresolved rows in `blog_broken_links`. Resolve via Technical SEO admin.

## JS rendering

Primary indexable blog content is SSR. SPA is progressive enhancement; do not rely on client-only HTML for blog SEO.

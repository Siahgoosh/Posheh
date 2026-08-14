# INDEXABILITY-AUDIT

## Indexable when

- `is_published = true`
- `robots_directive` does **not** contain `noindex`
- Canonical is self (or empty → self)
- HTTP 200 on SSR URL
- Listed in appropriate sitemap child

## Not indexable (by design)

- Draft / trash / archived / unpublished
- Preview tokens
- Blog search results
- Admin / app authenticated areas
- Categories/tags with `is_indexable = false`

## Canonical conflicts

Custom `canonical_url` off-self → excluded from sitemap payload; audit flags HIGH.

## Soft 404

Published posts with very short content flagged HIGH by technical audit. SSR missing posts return real **404** + noindex.

## Pagination / filters

Public blog listing paginated. Faceted explosion: avoid indexing arbitrary query filters; search is noindex.

# LOCAL-SEO-ARCHITECTURE

## Goal stack

Google → Entity → Business → Location → Service → Topic → Content → Property → Lead

## Layers

1. **Business Profile** — NAP source (`seo_business_profiles`)
2. **Entities** — typed nodes (`seo_entities`)
3. **Relationships** — typed edges (`seo_entity_relationships`)
4. **Locations** — hierarchy country→…→neighborhood with publish gate
5. **Topics** — topical authority tree
6. **Knowledge** — human local facts with source/date/confidence
7. **Content** — blog posts link via `seo_location_id` / `seo_topic_id`
8. **Properties** — live inventory filtered by location name + active status
9. **Leads** — CRO attribution (`city`, `source=LOCATION`)

## URL policy

- Public location: `/locations/{slug}`
- Only 200 + indexable + quality-passed in `sitemap-locations.xml`
- No thin programmatic explosion

## Schema

- Organization: always (available fields)
- LocalBusiness / Geo / OpeningHours: only when verified NAP+coords
- No Review schema without real moderated reviews

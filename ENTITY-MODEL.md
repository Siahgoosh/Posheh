# ENTITY-MODEL

## Types

`BUSINESS`, `BRAND`, `PERSON`, `LOCATION`, `CITY`, `NEIGHBORHOOD`, `SERVICE`, `PROPERTY`, `ARTICLE`, `CATEGORY`, `TOPIC`, `PRODUCT`

## Fields (seo_entities)

ID, type, name, slug, description, status, SEO metadata (title/description/canonical/robots/OG), schema_type, is_indexable, payload JSON, external_id/type

## Business profile fields

business_name, legal_name, brand, phone, email, support_email, website, address, city, region, country, postal_code, working_hours, description, services, social_profiles, logo, lat/lng + coords_verified, nap_complete

## Location fields

name, slug, type, parent_id, description, unique_value, coords (+ verified), status, is_indexable, quality_score/gate, portfolio (STAR/GROW/MAINTAIN/FIX/RETIRE), SEO meta

## Topic fields

name, slug, parent, description, search_intent, business_value, coverage, pillar_slug, related topics/locations, gaps, authority_score (internal)

## Rules

- No invented entities
- Indexable requires real description/value
- Coords require `coords_verified=true`

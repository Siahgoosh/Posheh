# ENTITY-RELATIONSHIP-GRAPH

## Relation types

`OWNS`, `OPERATES`, `SERVES`, `LOCATED_IN`, `PART_OF`, `RELATED_TO`, `AUTHORED_BY`, `ABOUT`, `OFFERS`, `HAS_PROPERTY`, `HAS_ARTICLE`, `NEAR`, `WORKS_FOR`

## Canonical Posheh graph (bootstrap)

```
BUSINESS:پوشه
  OWNS → BRAND:پوشه
  OFFERS → PRODUCT/SERVICE entities
TOPIC:real-estate
  PART_OF children: buying, selling, renting, investment, legal, finance, property-management, local-market
```

Locations are **not** auto-created. When a city/neighborhood is approved:

```
BUSINESS —SERVES→ CITY
CITY —HAS_ARTICLE→ ARTICLE
CITY —HAS_PROPERTY→ PROPERTY (runtime match)
ARTICLE —ABOUT→ TOPIC
PERSON —WORKS_FOR→ BUSINESS (when real author/team data exists)
```

## Important

This is an **internal** relationship graph for content/admin.

It is **not** a claim of presence in Google’s Knowledge Graph.

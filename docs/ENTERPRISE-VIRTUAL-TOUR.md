# Enterprise Virtual Tour Platform

Poshe unifies **360° Panorama Tours** and **Smart Walk** under one enterprise backend. Only the rendering engine differs; tour management, media, hotspots, analytics, permissions, sharing, security, and SEO are shared.

## Architecture

| Layer | 360 Tour | Smart Walk |
|-------|----------|------------|
| Renderer | Photo Sphere Viewer (`TourViewer`) | Flat pan/zoom (`SmartWalkViewer`) |
| Scene media | Equirectangular panorama | Flat image + variants |
| Routing | `UnifiedTourViewer` branches on `tour_type` | same |
| API | `/api/v1/virtual-tours/*` | same |
| Public URL | `/tour/{slug}` | same |

Future engines are registered in `TourEngineType` (Matterport, LiDAR, NeRF, Gaussian Splatting, etc.) without redesigning the core schema.

## Shared platform features

- **Tour Manager** — create, publish, duplicate, archive, version history
- **Media Library** — signed URLs when `VT_SIGNED_URLS=true`
- **Hotspot Engine** — scene links, info, gallery, audio, video, PDF
- **Analytics** — scene views, viewing time, hotspot clicks, completion rate, device/screen, heatmap
- **Permissions** — office scope, private tours + share token, optional password
- **Sharing** — public/private links, QR, embed iframe, embed domain allowlist
- **Security** — signed media URLs, optional watermark, disable right-click download, rate limits
- **SEO** — per-tour and per-scene URLs, OpenGraph, JSON-LD, sitemap entries
- **Admin** — analytics tab, sharing panel, settings, hotspot editor

## Public URLs

| URL | Purpose |
|-----|---------|
| `/tour/{slug}` | Full public tour page |
| `/tour/{slug}/scene/{id}` | Deep link to a scene |
| `/embed/tour/{slug}` | Embed iframe (checks `embed=1` + domain allowlist) |

API: `GET /api/v1/tour/{slug}`, `POST /api/v1/tour/{slug}/events`, `GET /api/v1/tour/{slug}/meta`

## Analytics events

Frontend batches events via `useTourSessionAnalytics`:

- `session_start` / `session_end` (device, screen size)
- `scene_view` / `tour_complete`
- `hotspot_click` (with optional position for heatmap)

Dashboard: editor → Analytics tab or `GET /api/v1/virtual-tours/{id}/analytics`

## Security

| Setting | Description |
|---------|-------------|
| `VT_SIGNED_URLS` | Panorama/media served via signed `/tour-media` URLs |
| `watermark_enabled` | Overlay on public viewer |
| `disable_direct_download` | Blocks context menu on public page |
| `embed_allowed_domains` | Referer/Origin check when `embed=1` |
| Throttle | Public tour 60/min, events 120/min |

## AI integration (scaffolding)

Stub endpoints under `/api/v1/virtual-tours/{id}/ai/*`:

- Hotspot suggestions, room/door/window detection
- Scene ordering, property description, voice narration
- Thumbnail and cover image selection

`GET /api/v1/virtual-tours/ai/capabilities` lists planned features.

## Smart Walk vs 360

See also [SMART-WALK.md](./SMART-WALK.md) for immersive viewer details (800% zoom, cinematic transitions, timeline, minimap).

## Deploy

```bash
cd /var/www/posheh && ./scripts/deploy.sh cursor/smart-walk-module-e117 && ./scripts/migrate.sh
```

Migration `2026_08_04_000002_enterprise_virtual_tour_analytics` adds `virtual_tour_analytics_events` and extends `virtual_tour_views`.

# Poshe Smart Walk

Premium interactive virtual tours built from **real smartphone photos** — no 360° camera, no panorama stitching, no AI-generated environments.

Runs **alongside** the existing **360 Tour** module. Both share one unified backend and database.

## Tour types

| Type | `tour_type` | Viewer | Scene images |
|------|-------------|--------|--------------|
| Smart Walk | `smart_walk` | Flat pan/zoom viewer | Phone JPEG/PNG/WebP/AVIF |
| 360 Tour | `panorama_360` | Photo Sphere Viewer | Equirectangular 2:1 panoramas |

## Creation wizard

Dashboard → **تور جدید** → Step 1: choose Smart Walk or 360 Tour → Step 2: title → editor.

## Database (unified model)

- `virtual_tours.tour_type` — `panorama_360` | `smart_walk`
- `virtual_tour_scenes.scene_type` — `equirectangular` | `flat_image`
- `virtual_tour_scenes.image_variants` — JSON: original, thumbnail, medium, large, ultra
- `virtual_tour_scenes.metadata` — GPS, audio, video, preview
- `virtual_tour_hotspots.position_x`, `position_y` — Smart Walk (% on image)
- `virtual_tour_hotspots.position_z` — 360° depth (optional)

## API

- `POST /api/virtual-tours` — body: `{ title, tour_type: "smart_walk" | "panorama_360" }`
- `POST /api/virtual-tours/{id}/scenes/upload` — 360 panoramas (2:1 equirectangular)
- `POST /api/virtual-tours/{id}/scenes/upload-image` — Smart Walk flat images

## Image pipeline

`ImageVariantService` auto-generates responsive variants on upload:

- Thumbnail 320px, Medium 1280px, Large 2560px, Ultra 4096px
- Max dimension 12000px
- Formats: JPEG, PNG, WebP input; optimized JPEG variants

## Frontend

- `UnifiedTourViewer` — routes to immersive `SmartWalkViewer` or `TourViewer` by `tour_type`
- `useSmartWalkCamera` — physics pan, momentum, pinch, double-tap, wheel, keyboard, up to **800% zoom**
- `transitionEngine` — cinematic scene transitions + walk-to-hotspot camera motion
- `SmartWalkTimeline` — bottom thumbnail strip with drag-scroll
- `SmartWalkMiniMap` — floor plan, visited rooms, clickable navigation
- `SmartWalkSidebar` — property specs, share, contact, dark/light/auto theme
- `SmartWalkHotspotLayer` — drag, resize, rotate, snap guides (editor mode)
- `useHotspotEditor` — undo/redo, copy/paste, duplicate, layer ordering helpers

## Migration

```bash
./scripts/migrate.sh
# or
php artisan migrate --force
```

Migration file: `2026_08_04_000001_smart_walk_unified_tour_schema.php`

## Deploy

```bash
cd /var/www/posheh
./scripts/deploy.sh cursor/smart-walk-module-e117
```

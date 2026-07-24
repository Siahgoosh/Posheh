#!/bin/sh
# Verify panel build exists before deploy completes
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIST="$ROOT/frontend/dist"

fail() { echo "[panel-verify] ERROR: $1" >&2; exit 1; }

[ -f "$DIST/panel.html" ] || fail "frontend/dist/panel.html missing — run: cd frontend && npm run build"

if ! grep -q 'پنل مدیریت' "$DIST/panel.html" 2>/dev/null; then
  fail "panel.html does not look like admin entry"
fi

# Built panel bundle should exist (vite names it panel-*.js or references panel-main)
if ! grep -qE 'panel|PanelApp|panel-main' "$DIST/panel.html" 2>/dev/null; then
  fail "panel.html has no panel JS bundle reference"
fi

echo "[panel-verify] OK — panel.html ready for panel.posheapp.ir"

#!/bin/sh
# Set Cafe Bazaar API token on the server (run from repo root).
# Usage: ./scripts/set-cafe-bazaar-env.sh 'YOUR_JWT_TOKEN'
set -eu

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$ROOT/backend/.env"
TOKEN="${1:-}"

if [ -z "$TOKEN" ]; then
  echo "Usage: $0 'JWT_TOKEN_FROM_CAFE_BAZAAR_PANEL'" >&2
  exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
  cp "$ROOT/backend/.env.example" "$ENV_FILE"
fi

if [ -s "$ENV_FILE" ] && [ -n "$(tail -c 1 "$ENV_FILE" | tr -d '\n')" ]; then
  echo >> "$ENV_FILE"
fi

set_var() {
  _key="$1"
  _val="$2"
  if grep -q "^${_key}=" "$ENV_FILE" 2>/dev/null; then
    sed -i "s|^${_key}=.*|${_key}=${_val}|" "$ENV_FILE"
  else
    echo "${_key}=${_val}" >> "$ENV_FILE"
  fi
}

set_var CAFE_BAZAAR_API_TOKEN "$TOKEN"
set_var CAFE_BAZAAR_PACKAGE_NAME ir.posheapp.posheh
set_var CAFE_BAZAAR_SKU_SOLO solo01
set_var CAFE_BAZAAR_SKU_OFFICE office01
set_var CAFE_BAZAAR_SKU_PREMIUM office02

echo "Cafe Bazaar env vars updated in backend/.env"
echo "Run: docker compose exec app php artisan config:clear"

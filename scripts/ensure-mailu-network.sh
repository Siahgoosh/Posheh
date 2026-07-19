#!/bin/bash
# Ensure posheh_mailu exists and mailu.env SUBNET matches the live network (never deletes/recreates).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAILU_ENV="$ROOT/docker/mail/mailu.env"
NET_NAME="posheh_mailu"

log() { printf '[mailu-net] %s\n' "$1"; }

if ! docker network inspect "$NET_NAME" >/dev/null 2>&1; then
  log "Creating network $NET_NAME"
  docker network create "$NET_NAME"
fi

actual=$(docker network inspect "$NET_NAME" -f '{{range .IPAM.Config}}{{.Subnet}}{{end}}' 2>/dev/null || true)
if [ -z "$actual" ]; then
  log "WARNING: could not read subnet for $NET_NAME"
  exit 0
fi

if [ -f "$MAILU_ENV" ]; then
  if grep -q '^SUBNET=' "$MAILU_ENV"; then
    sed -i "s|^SUBNET=.*|SUBNET=${actual}|" "$MAILU_ENV"
  else
    echo "SUBNET=${actual}" >> "$MAILU_ENV"
  fi
  log "SUBNET synced: $actual"
else
  log "mailu.env not found — subnet is $actual (run setup-mail.sh to create mailu.env)"
fi

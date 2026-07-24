#!/bin/bash
# Quick status check for Mailu email stack
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSE="docker compose -f $ROOT/docker-compose.yml -f $ROOT/docker-compose.mail.yml"

echo "=== Mailu containers ==="
$COMPOSE ps

echo ""
echo "=== Last admin logs ==="
$COMPOSE logs mailu-admin --tail=15 2>/dev/null || true

echo ""
echo "=== Last webmail logs ==="
$COMPOSE logs mailu-webmail --tail=15 2>/dev/null || true

echo ""
echo "=== HTTP checks ==="
curl -s -o /dev/null -w "mail.posheapp.ir/admin → %{http_code}\n" -H "Host: mail.posheapp.ir" http://127.0.0.1:8000/admin 2>/dev/null || echo "curl failed"
curl -s -o /dev/null -w "mail.posheapp.ir/webmail → %{http_code}\n" -H "Host: mail.posheapp.ir" http://127.0.0.1:8000/webmail 2>/dev/null || true

echo ""
echo "=== Ports ==="
ss -tlnp 2>/dev/null | grep -E ':25|:587|:993' || netstat -tlnp 2>/dev/null | grep -E ':25|:587|:993' || echo "(install ss/netstat to see ports)"

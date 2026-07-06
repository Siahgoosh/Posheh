#!/bin/sh
# Quick diagnostics when deploy fails — run on server:
#   chmod +x scripts/deploy-debug.sh && ./scripts/deploy-debug.sh

set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "=== Posheh deploy debug ==="
echo "Path: $ROOT"
echo "Date: $(date -u 2>/dev/null || date)"
echo ""

echo "--- Git ---"
git branch --show-current 2>/dev/null || echo "not a git repo"
git status -sb 2>/dev/null | head -5
echo ""

echo "--- Docker ---"
docker --version 2>/dev/null || echo "docker: NOT FOUND"
docker compose version 2>/dev/null || echo "docker compose: NOT FOUND"
docker compose ps 2>/dev/null || echo "docker compose ps failed"
echo ""

echo "--- MySQL ---"
docker compose exec -T mysql mysqladmin ping -h localhost -uroot -psecret 2>/dev/null \
  && echo "MySQL: OK" || echo "MySQL: NOT READY"
echo ""

echo "--- App logs (last 30 lines) ---"
docker compose logs app --tail=30 2>/dev/null || true
echo ""

echo "--- Nginx logs (last 15 lines) ---"
docker compose logs nginx --tail=15 2>/dev/null || true
echo ""

echo "--- API test ---"
curl -s -o /dev/null -w "GET /api/v1/plans => HTTP %{http_code}\n" http://localhost:8000/api/v1/plans 2>/dev/null || echo "curl failed"
echo ""

echo "--- Frontend build dir ---"
ls -la frontend/dist 2>/dev/null | head -5 || echo "frontend/dist missing — run: cd frontend && npm run build"
echo ""

echo "=== End debug ==="

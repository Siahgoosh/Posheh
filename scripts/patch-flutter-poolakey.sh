#!/usr/bin/env bash
# flutter_poolakey still references jcenter(), removed in Gradle 8+.
set -euo pipefail

CACHE_ROOT="${PUB_CACHE:-$HOME/.pub-cache}"
mapfile -t files < <(find "$CACHE_ROOT" -path '*/flutter_poolakey-*/android/build.gradle' 2>/dev/null || true)

if [[ ${#files[@]} -eq 0 ]]; then
  echo "flutter_poolakey android/build.gradle not found in pub cache — skipping patch"
  exit 0
fi

for file in "${files[@]}"; do
  if grep -q 'jcenter()' "$file"; then
    sed -i 's/jcenter()/mavenCentral()/g' "$file"
    echo "Patched jcenter() in $file"
  fi
done

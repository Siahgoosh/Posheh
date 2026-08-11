#!/usr/bin/env bash
# Fetch trimmed royalty-free preview clips (SoundHelix license) for virtual tour music presets.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
OUT="$ROOT/frontend/public/audio/virtual-tour"
TMP="${TMPDIR:-/tmp}/posheh-tour-audio"
mkdir -p "$OUT" "$TMP"

if ! command -v ffmpeg >/dev/null 2>&1; then
  echo "ffmpeg is required" >&2
  exit 1
fi

fetch() {
  local url="$1" dest="$2"
  if [[ -f "$dest" ]]; then
    echo "skip existing $dest"
    return
  fi
  curl -fsSL --max-time 180 "$url" -o "$dest"
}

fetch "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3" "$TMP/song1.mp3"
fetch "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3" "$TMP/song2.mp3"
fetch "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3" "$TMP/song3.mp3"

ffmpeg -y -i "$TMP/song1.mp3" -t 70 -acodec libvorbis -q:a 5 "$OUT/coffee-shop.ogg"
ffmpeg -y -i "$TMP/song1.mp3" -t 50 -ss 30 -acodec libmp3lame -ab 128k "$OUT/ambient-calm.mp3"
ffmpeg -y -i "$TMP/song2.mp3" -t 50 -ss 20 -acodec libmp3lame -ab 128k "$OUT/soft-piano.mp3"
ffmpeg -y -i "$TMP/song2.mp3" -t 50 -ss 90 -acodec libmp3lame -ab 128k "$OUT/dreamscape.mp3"
ffmpeg -y -i "$TMP/song3.mp3" -t 50 -ss 40 -acodec libmp3lame -ab 128k "$OUT/modern-lounge.mp3"
ffmpeg -y -i "$TMP/song3.mp3" -t 50 -ss 120 -acodec libmp3lame -ab 128k "$OUT/nature-peace.mp3"

echo "Tour audio assets written to $OUT"

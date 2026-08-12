#!/usr/bin/env bash
# Purge *.tmp files in ./System/Cache/Data for a Smartest install.
# Run from the Smartest project root (must contain System/Core/Info/system.yml).
# Usage:
#   sudo ./smartest-purge-tmp.sh [--yes|-y] [--dry-run|-n] [--quiet|-q] [--progress]

set -euo pipefail

YES=0; DRY=0; QUIET=0; PROGRESS=0
while [[ $# -gt 0 ]]; do
  case "$1" in
    -y|--yes) YES=1 ;;
    -n|--dry-run) DRY=1 ;;
    -q|--quiet) QUIET=1 ;;
    --progress) PROGRESS=1 ;;
    -h|--help) echo "Usage: sudo $0 [--yes|-y] [--dry-run|-n] [--quiet|-q] [--progress]"; exit 0 ;;
    *) echo "Unknown option: $1" >&2; exit 2 ;;
  esac; shift
done

# Preflight
[[ ${EUID:-$(id -u)} -eq 0 ]] || { echo "Please run as root (use sudo)." >&2; exit 1; }
[[ -f "System/Core/Info/system.yml" ]] || { echo "Not a Smartest root: System/Core/Info/system.yml missing in $(pwd)." >&2; exit 1; }
TARGET="System/Cache/Data"
[[ -d "$TARGET" ]] || { echo "Directory not found: $TARGET" >&2; exit 1; }

# Helpers
hr() {
  if command -v numfmt >/dev/null 2>&1; then numfmt --to=iec --suffix=B "$1"; else echo "$1B"; fi
}

# Count and (for dry-run) total size
COUNT=$(find "$TARGET" -maxdepth 1 -type f -name '*.tmp' -printf x | wc -c | tr -d ' ')
if [[ "$COUNT" -eq 0 ]]; then
  [[ $QUIET -eq 1 ]] || echo "No .tmp files found in $TARGET."
  exit 0
fi

if [[ $DRY -eq 1 ]]; then
  BYTES=$(find "$TARGET" -maxdepth 1 -type f -name '*.tmp' -printf '%s\0' | awk -v RS='\0' '{s+=$1} END{print s+0}')
  [[ $QUIET -eq 1 ]] || { echo "Would remove $COUNT file(s) from $TARGET"; echo "Disk space that would be freed: $(hr "$BYTES")"; }
  exit 0
fi

[[ $QUIET -eq 1 ]] || echo "Found $COUNT .tmp file(s) in $TARGET."

if [[ $YES -ne 1 ]]; then
  read -r -p "Delete $COUNT .tmp file(s)? [y/N] " reply
  case "${reply,,}" in y|yes) ;; *) echo "Aborted."; exit 1 ;; esac
fi

SECONDS=0
processed=0
freed=0
barw=30
# Show progress automatically on a TTY unless suppressed
if [[ -t 1 && $QUIET -eq 0 ]]; then PROGRESS=1; fi

# --- Deletion loop (no subshell): process substitution preserves counters ---
while IFS= read -r -d '' path; do
  size=$(stat -c %s -- "$path" 2>/dev/null || echo 0)
  freed=$((freed + size))
  rm -f -- "$path" 2>/dev/null || true
  processed=$((processed + 1))

  if [[ $PROGRESS -eq 1 ]]; then
    pct=$(( processed * 100 / COUNT ))
    filled=$(( processed * barw / COUNT ))
    rest=$(( barw - filled ))
    printf "\r[%s%s] %3d%%  %d/%d  freed %s" \
      "$(printf '#%.0s' $(seq 1 $filled))" "$(printf '.%.0s' $(seq 1 $rest))" \
      "$pct" "$processed" "$COUNT" "$(hr "$freed")"
  fi
done < <(find "$TARGET" -maxdepth 1 -type f -name '*.tmp' -print0)

[[ $PROGRESS -eq 1 ]] && echo

# Verify remainder
REMAIN=$(find "$TARGET" -maxdepth 1 -type f -name '*.tmp' -printf x | wc -c | tr -d ' ')
if [[ $REMAIN -eq 0 ]]; then
  [[ $QUIET -eq 1 ]] || { echo "Deleted $processed .tmp file(s)."; echo "Disk space freed: $(hr "$freed") in ${SECONDS}s"; }
else
  echo "Warning: $REMAIN .tmp file(s) remain (permissions or concurrent writes?). Freed $(hr "$freed") in ${SECONDS}s." >&2
  exit 3
fi
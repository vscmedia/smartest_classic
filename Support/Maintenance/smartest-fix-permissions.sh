#!/usr/bin/env bash
# smartest-writeable-simple.sh — make Smartest dirs group-writable by www-data,
# and purge stale .tmp files in System/Cache/Data/ (older than 28 days).
# Run it from the Smartest project root (must contain System/Core/Info/system.yml).

set -euo pipefail

# --- Preflight ---------------------------------------------------------------
[[ $EUID -eq 0 ]] || { echo "Please run as root (use sudo)." >&2; exit 1; }
[[ -f "System/Core/Info/system.yml" ]] || {
  echo "Not a Smartest root: System/Core/Info/system.yml not found in $(pwd)" >&2
  exit 1
}

TARGET_GROUP="www-data"
project_root="$(pwd)"
echo "[*] Smartest root: $project_root"
echo "[*] Ensuring group write access for group: $TARGET_GROUP"

# Directories (relative to project root)
mapfile -t DIRS <<'EOF'
Sites/
Library/ObjectModel/
System/Cache/Smarty/
System/Cache/Pages/
System/Cache/Data/
System/Cache/Includes/
System/Cache/ObjectModel/Models/
System/Cache/ObjectModel/DataObjects/
System/Cache/Settings/
System/Cache/Controller/
System/Cache/TextFragments/Previews/
System/Cache/TextFragments/Live/
System/Cache/TextFragments/OEmbed/
System/Logs/
Public/Resources/System/Cache/Images/
Public/Resources/System/Cache/CSS/
Logs/
Documents/Deleted/
Documents/Downloads/
Public/Resources/Images/
Public/Resources/Assets/
Public/Resources/Stylesheets/
Public/Resources/Javascript/
Public/Resources/Fonts/
System/Temporary/
Presentation/Masters/
Presentation/Layouts/
Presentation/ListItems/
Presentation/SingleItem/
System/Temporary/
EOF

# Deduplicate while preserving order
declare -A seen
uniq_dirs=()
for rel in "${DIRS[@]}"; do
  [[ -z "${rel// }" ]] && continue
  [[ ${seen["$rel"]+yes} ]] || { seen["$rel"]=1; uniq_dirs+=("$rel"); }
done

# --- Work --------------------------------------------------------------------
for rel in "${uniq_dirs[@]}"; do
  dir="${project_root}/${rel%/}"

  # Create if missing
  if [[ ! -d "$dir" ]]; then
    mkdir -p "$dir"
    echo "[+] Created: $rel"
  fi

  # Make www-data the group owner of the subtree
  chgrp -R "$TARGET_GROUP" "$dir"

  # Directories: rwxr-sr-x (2775) — setgid so new items inherit the group
  find "$dir" -type d -print0 | xargs -0r chmod 2775

  # Files: rw-rw-r-- (0664)
  find "$dir" -type f -print0 | xargs -0r chmod 0664

  # Ensure the top dir has setgid as well
  chmod g+s "$dir"

  echo "[✓] Group '$TARGET_GROUP' can write: $rel"
done

# Purge .tmp files older than 28 days in System/Cache/Data/ (non-recursive)
CACHE_DATA_DIR="${project_root}/System/Cache/Data"
if [[ -d "$CACHE_DATA_DIR" ]]; then
  echo "[*] Purging .tmp files older than 28 days in System/Cache/Data/…"
  find "$CACHE_DATA_DIR" -maxdepth 1 -type f -name '*.tmp' -mtime +28 -print -delete
fi

# Warn if www-data cannot traverse to the project root
if ! su -s /bin/sh -c "cd '$project_root' 2>/dev/null" www-data; then
  echo "⚠  www-data cannot traverse to $(pwd)."
  echo "   Consider: chmod 711 /home/<user> and chmod 755 /home/<user>/sites"
  echo "   (or adjust parent directory group/permissions accordingly)."
fi

echo "Done."

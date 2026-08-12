#!/bin/bash

REG_DIR="/var/lib/smartest/instances"
LOG_FILE="/var/log/smartest_maintenance.log"
AGE="+1" # days
HTML_AGE="+30"
NOW=$(date '+%Y-%m-%d %H:%M:%S')
TOTAL_CACHE_FILES_REMOVED=0
TOTAL_PAGE_CACHE_FILES_REMOVED=0
#PAGE_CACHE_PATTERN='*_m[0-9][0-9](_d[0-9][0-9])?(_H[0-9][0-9])?.html'
PAGE_CACHE_PATTERN='.*/[^/]*_m[0-9]{2}(_d[0-9]{2})?(_H[0-9]{2})?\.html'
 
echo "[$NOW] Starting Smartest maintenance..." >> "$LOG_FILE"

parse_yaml_value() {

  local key="$1"
  local file="$2"

  awk -F ':' -v key="$key" '
    /^[ \t]*#/ { next }                   # Skip commented lines
    /^[ \t]*---/ { next }                 # Skip YAML separators
    $1 ~ key {
      gsub(/^[ \t]+/, "", $2)            # Trim leading whitespace in value
      gsub(/["'\''"]/, "", $2)           # Strip quotes
      gsub(/[ \t\r\n]+$/, "", $2)        # Trim trailing whitespace

      key_clean = $1
      gsub(/^[ \t]+/, "", key_clean)
      gsub(/[ \t\r\n]+$/, "", key_clean)

      if (length(key_clean) < 7 || length($2) < 7)
        next

      if (key_clean ~ /^[0-9]+$/ || $2 ~ /^[0-9]+$/)
        next

      print $2
      exit
    }
  ' "$file"
}

for regfile in "$REG_DIR"/*.smreg; do
    
#    echo "Debug: Accessing $regfile..."
    
    [ -e "$regfile" ] || continue
 
    source "$regfile"
    [ -z "$path" ] && echo "[$NOW] Invalid reg file: $regfile" >> "$LOG_FILE" && continue
 
#    SMARTEST_DIR="$path"
    SMARTEST_DIR="${path%/}/"
    CACHE_DIR="${SMARTEST_DIR}System/Cache/Data/"
    PAGE_CACHE_DIR="${SMARTEST_DIR}System/Cache/Pages/"
    INFO_FILE="${SMARTEST_DIR}System/Core/Info/system.yml"
    SM_LOG_FILE="${SMARTEST_DIR}/System/Logs/maintenance.log"
    
#    echo "Debug: Going to $path..."
    
#    echo "Debug: Looking in $CACHE_DIR..."
 
    # Clean old .tmp files
    if [ -d "$CACHE_DIR" ]; then
        DELETED_FILES=$(find "$CACHE_DIR" -type f -name '*.tmp' -mtime "$AGE" -print -delete | wc -l)
    else
        DELETED_FILES=0
    fi
    
    if [ -d "$PAGE_CACHE_DIR" ]; then
      set -o pipefail
#      DELETED_PAGE_FILES=$(find "$PAGE_CACHE_DIR" -type f -regex "$PAGE_CACHE_PATTERN" -mtime "$HTML_AGE" -print -delete | wc -l)
      DELETED_PAGE_FILES=$(find "$PAGE_CACHE_DIR" -regextype posix-extended -type f -regex "$HTML_REGEX" -mtime "$HTML_AGE" -print -delete | wc -l)
      if [[ -z "$DELETED_PAGE_FILES" || "$DELETED_PAGE_FILES" =~ [^0-9] ]]; then
	printf "Error: Failed to count deleted .html files in %s\n" "$PAGE_CACHE_DIR" >&2
        return 1
      fi
    else
      DELETED_PAGE_FILES=0
    fi
     
    DB_YML="${SMARTEST_DIR}Configuration/database.yml"
 #   echo "Debug: Checking Mysql connection info in $DB_YML" 
     
    # MySQL connectivity check
    MYSQL_OK=0
    if [ -f "$DB_YML" ]; then
    
        DB_USER=$(parse_yaml_value username "$DB_YML")
	DB_PASS=$(parse_yaml_value password "$DB_YML")
	DB_NAME=$(parse_yaml_value database "$DB_YML")
	DB_HOST="localhost"
	
#        DB_USER=$(awk '/username:/ {gsub(/^[ \t]+/, "", $0); print $2}' "$DB_YML")
#	 DB_USER=$(awk '/username:/ {print $2}' "$DB_YML")
#	 DB_PASS=$(awk '/password:/ {gsub(/^[ \t]+/, "", $0); print $2}' "$DB_YML")
#	 DB_PASS=$(awk '/password:/ {print $2}' "$DB_YML")
#	 DB_NAME=$(awk '/database:/ {print $2}' "$DB_YML")
#        DB_HOST=$(awk '/host:/ {print $2}' "$DB_YML")
#        DB_HOST="localhost"
	
#	 echo "Debug: DSN: ${DB_USER}:${DB_PASS}@${DB_HOST}/${DB_NAME}"
	
	# Test MySQL connection
	mysql -u "$DB_USER" -p"$DB_PASS" -h "$DB_HOST" "$DB_NAME" -e "SELECT 1;" >/dev/null 2>&1
	[ $? -eq 0 ] && MYSQL_OK=1
    fi
    
    touch $SM_LOG_FILE
    
    if [[ "$MYSQL_OK" -eq 1 ]]; then
      mysql_status_msg="Connected ok"
    else
      mysql_status_msg="Could not connect"
    fi
    
    # Log/report via system.yml (append or modify in real system)
    if [ -f "$SM_LOG_FILE" ]; then
        echo -e "\n# Maintenance Report ($NOW)" >> "$SM_LOG_FILE"
#        echo "Maintenance check: true" >> "$SM_LOG_FILE"
        echo "Deleted old .tmp cache files: $DELETED_FILES" >> "$SM_LOG_FILE"
	echo "Deleted old .html page cache files: $DELETED_PAGE_FILES" >> "$SM_LOG_FILE"
        echo "MySQL connection check: $mysql_status_msg" >> "$SM_LOG_FILE"
    fi
    
#    $TOTAL_CACHE_FILES_REMOVED += $DELETED_FILES
     TOTAL_CACHE_FILES_REMOVED=$((TOTAL_CACHE_FILES_REMOVED + DELETED_FILES))
     TOTAL_PAGE_CACHE_FILES_REMOVED=$((TOTAL_PAGE_CACHE_FILES_REMOVED + DELETED_PAGE_FILES))
    
    # Log to central file
    echo "[$NOW] Checked $SMARTEST_DIR - deleted $DELETED_FILES .tmp files - deleted $DELETED_PAGE_FILES cached HTML files - MySQL: $mysql_status_msg" >> "$LOG_FILE"
 done
 
 /usr/local/bin/security_notify.sh "Smartest auto-maintenance completed" "minerva2.vscmedia.com" "low" >/dev/null 2>&1
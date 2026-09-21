#!/bin/bash
set -euo pipefail

BACKUP_TMP=/tmp/backup-staging
DB_PATH="${BACKUP_DB_PATH:-/app/dbdata/database.sqlite}"
STORAGE_PATH="${BACKUP_STORAGE_PATH:-/app/storage/app}"

# Retention settings (with defaults)
KEEP_DAILY="${KEEP_DAILY:-7}"
KEEP_WEEKLY="${KEEP_WEEKLY:-4}"
KEEP_MONTHLY="${KEEP_MONTHLY:-3}"
KEEP_YEARLY="${KEEP_YEARLY:-0}"

if [ ! -d "$BORG_REPO" ]; then
    echo "ERROR: borg repository $BORG_REPO does not exist. Run 'make borg' first." >&2
    exit 1
fi

mkdir -p "$BACKUP_TMP"
trap 'rm -rf "$BACKUP_TMP"' EXIT

# Safe SQLite snapshot (skip silently only when the DB is stored elsewhere)
if [ -f "$DB_PATH" ]; then
    sqlite3 "$DB_PATH" ".backup '$BACKUP_TMP/database.sqlite'"
else
    echo "WARNING: database not found at $DB_PATH, skipping DB snapshot" >&2
fi

# Create backup
borg create --compression zstd,6 \
    --exclude-caches \
    "${BORG_REPO}::{now:%Y-%m-%d_%H%M%S}" \
    "$STORAGE_PATH" \
    ${DB_PATH:+$BACKUP_TMP/database.sqlite}

# Build prune args
PRUNE_ARGS="--keep-daily=${KEEP_DAILY} --keep-weekly=${KEEP_WEEKLY} --keep-monthly=${KEEP_MONTHLY}"
if [ "${KEEP_YEARLY}" -gt 0 ]; then
    PRUNE_ARGS="${PRUNE_ARGS} --keep-yearly=${KEEP_YEARLY}"
fi

# Prune old backups
borg prune $PRUNE_ARGS "$BORG_REPO"

borg compact "$BORG_REPO"

echo "Backup completed: $(date)"
echo "Retention: daily=${KEEP_DAILY}, weekly=${KEEP_WEEKLY}, monthly=${KEEP_MONTHLY}, yearly=${KEEP_YEARLY}"
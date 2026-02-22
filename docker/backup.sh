#!/bin/bash
set -euo pipefail

BACKUP_TMP=/tmp/backup-staging
DB_PATH=/data/app/database.sqlite
STORAGE_PATH=/data/app

# Retention settings (with defaults)
KEEP_DAILY="${KEEP_DAILY:-7}"
KEEP_WEEKLY="${KEEP_WEEKLY:-4}"
KEEP_MONTHLY="${KEEP_MONTHLY:-3}"
KEEP_YEARLY="${KEEP_YEARLY:-0}"

mkdir -p "$BACKUP_TMP"

# Safe SQLite snapshot
if [ -f "$DB_PATH" ]; then
    sqlite3 "$DB_PATH" ".backup '$BACKUP_TMP/database.sqlite'"
fi

# Create backup
borg create --compression zstd,6 \
    --exclude-caches \
    "${BORG_REPO}::{now:%Y-%m-%d_%H%M%S}" \
    "$STORAGE_PATH/public" \
    "$STORAGE_PATH/private" \
    ${DB_PATH:+$BACKUP_TMP/database.sqlite}

# Build prune args
PRUNE_ARGS="--keep-daily=${KEEP_DAILY} --keep-weekly=${KEEP_WEEKLY} --keep-monthly=${KEEP_MONTHLY}"
if [ "${KEEP_YEARLY}" -gt 0 ]; then
    PRUNE_ARGS="${PRUNE_ARGS} --keep-yearly=${KEEP_YEARLY}"
fi

# Prune old backups
borg prune $PRUNE_ARGS "$BORG_REPO"

borg compact "$BORG_REPO"

rm -rf "$BACKUP_TMP"

echo "Backup completed: $(date)"
echo "Retention: daily=${KEEP_DAILY}, weekly=${KEEP_WEEKLY}, monthly=${KEEP_MONTHLY}, yearly=${KEEP_YEARLY}"

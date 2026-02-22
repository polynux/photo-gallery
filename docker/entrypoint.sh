#!/bin/sh
set -e

# Run migrations in production mode
# Only the php service runs migrations (worker will skip due to missing APP_RUN_MIGRATIONS)
if [ "${APP_RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Execute the main command
exec "$@"
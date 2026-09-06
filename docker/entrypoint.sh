#!/bin/sh
# =============================================================================
# 21 LMS container entrypoint.
#
# Waits for PostgreSQL, runs one-time boot tasks (migrations, caches,
# storage link) and then drops privileges to www-data and execs the CMD.
#
# Controlled by env vars:
#   MIGRATE_ON_BOOT  — run `php artisan migrate --force` (default: true)
#   CACHE_ON_BOOT    — run config/route/view caching (default: true)
# =============================================================================
set -e

cd /var/www/html

# ----------------------------------------------------------------------------
# 1. Guard rails
# ----------------------------------------------------------------------------
if [ -z "$APP_KEY" ] && [ "$APP_ENV" != "local" ]; then
    echo "ERROR: APP_KEY is empty. Generate one with: make key" >&2
    exit 1
fi

# ----------------------------------------------------------------------------
# 2. Wait for PostgreSQL (up to 60s)
# ----------------------------------------------------------------------------
if [ -n "$DB_HOST" ]; then
    echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT:-5432}..."
    i=0
    until php -r '
        try {
            new PDO(sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: 5432, getenv("DB_DATABASE")), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "ERROR: PostgreSQL is not reachable after 60s" >&2
            exit 1
        fi
        sleep 2
    done
    echo "PostgreSQL is up."
fi

# ----------------------------------------------------------------------------
# 3. Fix writable dirs (idempotent, fast on named volumes)
# ----------------------------------------------------------------------------
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# ----------------------------------------------------------------------------
# 4. Boot tasks (run as www-data)
# ----------------------------------------------------------------------------
as_www_data() {
    su-exec www-data "$@"
}

if [ "${MIGRATE_ON_BOOT:-true}" = "true" ]; then
    as_www_data php artisan migrate --force --no-interaction
fi

if ! [ -e public/storage ]; then
    as_www_data php artisan storage:link || true
fi

if [ "${CACHE_ON_BOOT:-true}" = "true" ]; then
    as_www_data php artisan config:cache
    as_www_data php artisan route:cache || true
    # a single broken view must not prevent the container from serving traffic
    as_www_data php artisan view:cache || echo "WARN: view:cache failed — views compile lazily" >&2
fi

# ----------------------------------------------------------------------------
# 5. Exec the container command
# ----------------------------------------------------------------------------
# php-fpm must run as root: its default error_log (/proc/self/fd/2) cannot be
# re-opened by a non-root master (FPM initialization failed). Its workers
# still run as www-data via the pool config. Everything else (artisan, queue,
# scheduler) drops privileges to www-data.
case "$1" in
    php-fpm) exec "$@" ;;
    *) exec su-exec www-data "$@" ;;
esac

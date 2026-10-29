#!/bin/sh
# Prepares the site on every start, then hands over to FrankenPHP: checks the app key, makes the
# storage folders and the SQLite file, migrates, seeds the demo the first time, caches the
# configuration, and runs the scheduler and the queue worker beside the server.
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Make one with: php artisan key:generate --show" >&2
    exit 1
fi

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs

if [ "$DB_CONNECTION" = "sqlite" ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi

php artisan storage:link --force
php artisan migrate --force
php artisan site:seed-once
php artisan optimize

# Restarted when they stop: the worker every hour by design, either of them on an error.
keep_running() {
    while true; do
        "$@" || echo "[$*] stopped with an error, restarting." >&2
        sleep 5
    done
}

keep_running php artisan schedule:work &
keep_running php artisan queue:work --tries=3 --max-time=3600 &

exec docker-php-entrypoint "$@"

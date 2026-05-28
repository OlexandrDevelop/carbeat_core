#!/usr/bin/env sh
# Custom entrypoint for Laravel production container
# 1. Runs pending migrations (idempotent)
# 2. Warm-ups caches (config/route/view)
# 3. Delegates to the standard Webdevops entrypoint with supervisord

set -e

DEPLOY_FAILED=0

send_telegram() {
  [ -z "$TELEGRAM_TOKEN" ] && return 0
  [ -z "$TELEGRAM_CHAT_ID" ] && return 0
  curl -s --max-time 15 -X POST "https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage" \
    -d "chat_id=${TELEGRAM_CHAT_ID}" \
    --data-urlencode "text=$1" \
    >/dev/null 2>&1 || true
}

# If DB variables are present, wait until the database is reachable (max 60s)
if [ -n "$DB_HOST" ]; then
  echo "[entrypoint] Waiting for database $DB_HOST:$DB_PORT ..."
  for i in $(seq 1 60); do
    if php -r "exit(@mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), (int)getenv('DB_PORT')) ? 0 : 1);" 2>/dev/null; then
      echo "[entrypoint] Database is up" && break
    fi
    echo "[entrypoint] Still waiting... ($i)"
    sleep 1
  done
fi

# Run migrations — log output for error details
php /app/artisan migrate --force >/tmp/migrate.log 2>&1 || DEPLOY_FAILED=1

# Warm up caches
php /app/artisan config:cache || true
php /app/artisan route:cache || true
php /app/artisan view:cache || true
php /app/artisan optimize:clear || true
php /app/artisan storage:link || true

# Ensure storage and cache are writable
mkdir -p /app/storage /app/bootstrap/cache /app/storage/app/public
chown -R application:application /app/storage /app/bootstrap/cache || true
chmod -R ug+rwX /app/storage /app/bootstrap/cache || true

# Generate sitemap
php /app/artisan sitemap:generate || true

# Remove public sitemap symlink
rm -f /app/public/sitemap.xml || true

# Send single final notification
if [ "$DEPLOY_FAILED" = "1" ]; then
  ERR=$(tail -15 /tmp/migrate.log | cut -c1-600)
  MSG=$(printf "❌ Deploy FAILED on PRODUCTION\nTime: %s\n\nMigration error:\n%s" \
    "$(date '+%Y-%m-%d %H:%M:%S')" "$ERR")
  send_telegram "$MSG"
else
  send_telegram "✅ Deployment finished on PRODUCTION at $(date '+%Y-%m-%d %H:%M:%S')"
fi

# Hand over to the original entrypoint (keeps supervisor & nginx/php-fpm)
exec /opt/docker/bin/entrypoint.sh supervisord -c /opt/docker/etc/supervisor.conf

#!/usr/bin/env sh
# Custom entrypoint for Laravel production container
# 1. Runs pending migrations (idempotent)
# 2. Warm-ups caches (config/route/view)
# 3. Delegates to the standard Webdevops entrypoint with supervisord

set -e

DEPLOY_OK=1
DEPLOY_ERROR_MSG=""

send_telegram() {
  if [ -z "$TELEGRAM_TOKEN" ] || [ -z "$TELEGRAM_CHAT_ID" ]; then
    return 0
  fi
  local text="$1"
  for i in $(seq 1 5); do
    curl -s --max-time 10 -X POST "https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage" \
      -d "chat_id=${TELEGRAM_CHAT_ID}" \
      --data-urlencode "text=${text}" \
      -d "parse_mode=Markdown" >/dev/null && return 0
    echo "[entrypoint] Telegram attempt $i failed, retrying in 3s..."
    sleep 3
  done
  echo "[entrypoint] Telegram notify failed after 5 attempts"
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

# Run migrations — capture output for error details
MIGRATE_OUT=$(php /app/artisan migrate --force 2>&1) || {
  DEPLOY_OK=0
  SHORT_ERR=$(echo "$MIGRATE_OUT" | tail -15 | cut -c1-600)
  DEPLOY_ERROR_MSG=$(printf "Migration failed:\n\`\`\`\n%s\n\`\`\`" "$SHORT_ERR")
}

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
if [ "$DEPLOY_OK" = "1" ]; then
  send_telegram "✅ *Deploy SUCCESS* on PRODUCTION
Time: $(date '+%Y-%m-%d %H:%M:%S')"
else
  send_telegram "❌ *Deploy FAILED* on PRODUCTION
Time: $(date '+%Y-%m-%d %H:%M:%S')
${DEPLOY_ERROR_MSG}"
fi

# Hand over to the original entrypoint (keeps supervisor & nginx/php-fpm)
exec /opt/docker/bin/entrypoint.sh supervisord -c /opt/docker/etc/supervisor.conf

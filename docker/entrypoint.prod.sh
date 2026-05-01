#!/usr/bin/env sh
# Custom entrypoint for Laravel production container
# 1. Runs pending migrations (idempotent)
# 2. Warm-ups caches (config/route/view)
# 3. Delegates to the standard Webdevops entrypoint with supervisord

set -e

# Function to post message to Telegram
send_telegram() {
  # Requires TELEGRAM_TOKEN and TELEGRAM_CHAT_ID env vars
  if [ -z "$TELEGRAM_TOKEN" ] || [ -z "$TELEGRAM_CHAT_ID" ]; then
    return 0
  fi

  local text="$1"
  # Use simple curl call to Telegram Bot API
  curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage" \
    -d "chat_id=${TELEGRAM_CHAT_ID}" \
    --data-urlencode "text=${text}" \
    -d "parse_mode=Markdown" >/dev/null || true
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

# Run migrations (do not fail container if there are no migrations to run)
php /app/artisan migrate --force || true

# Warm up caches
php /app/artisan config:cache
php /app/artisan route:cache
php /app/artisan view:cache
php /app/artisan optimize:clear
php /app/artisan storage:link

# Ensure storage and cache are writable
mkdir -p /app/storage /app/bootstrap/cache /app/storage/app/public
chown -R application:application /app/storage /app/bootstrap/cache || true
chmod -R ug+rwX /app/storage /app/bootstrap/cache || true

# Generate sitemap on each production deploy so new canonical /sto URLs are published immediately.
php /app/artisan sitemap:generate || true

# Create symlink for sitemap.xml if it doesn't exist
if [ ! -f /app/public/sitemap.xml ] || [ -L /app/public/sitemap.xml ]; then
  ln -sf /app/storage/app/public/sitemap.xml /app/public/sitemap.xml || true
fi

# Notify Telegram that deployment finished
send_telegram "✅ Deployment finished on PRODUCTION at $(date '+%Y-%m-%d %H:%M:%S')"

# Hand over to the original entrypoint (keeps supervisor & nginx/php-fpm)
exec /opt/docker/bin/entrypoint.sh supervisord -c /opt/docker/etc/supervisor.conf

#!/bin/sh
set -e

cd /var/www/html

echo "==> CometaX Backend arrancando..."

# ── 1. Crear .env si no existe ──────────────────────────────────────────────
if [ ! -f ".env" ]; then
  echo "    Creando .env desde variables de entorno..."
  cat > .env <<ENVEOF
APP_NAME=${APP_NAME:-CometaX}
APP_ENV=${APP_ENV:-local}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8000}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE=${APP_MAINTENANCE:-false}
PAYMENTS_MAINTENANCE=${PAYMENTS_MAINTENANCE:-true}
BCRYPT_ROUNDS=12
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=${LOG_LEVEL:-debug}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}
SESSION_DRIVER=${SESSION_DRIVER:-database}
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
CACHE_STORE=${CACHE_STORE:-database}
MAIL_MAILER=log
STRIPE_KEY=${STRIPE_KEY:-}
STRIPE_SECRET=${STRIPE_SECRET:-}
STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET:-}
CASHIER_CURRENCY=${CASHIER_CURRENCY:-mxn}
ENVEOF
fi

# ── 2. Generar APP_KEY (ANTES de config:cache) ──────────────────────────────
if grep -q "^APP_KEY=$" .env 2>/dev/null || grep -q "^APP_KEY= *$" .env 2>/dev/null; then
  echo "    Generando APP_KEY con PHP puro..."
  NEW_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));")
  sed -i "s|^APP_KEY=.*|APP_KEY=${NEW_KEY}|" .env
  echo "    APP_KEY generado correctamente."
fi

# ── 3. SQLite ────────────────────────────────────────────────────────────────
mkdir -p database
if [ ! -f "database/database.sqlite" ]; then
  echo "    Creando database.sqlite..."
  touch database/database.sqlite
fi
chown www-data:www-data database/database.sqlite

# ── 4. Permisos de storage ───────────────────────────────────────────────────
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── 5. Limpiar cache viejo (por si hubo restart con config malo) ─────────────
php artisan config:clear  --no-interaction 2>/dev/null || true
php artisan cache:clear   --no-interaction 2>/dev/null || true

# ── 6. Migraciones ───────────────────────────────────────────────────────────
echo "    Ejecutando migraciones..."
php artisan migrate --force --no-interaction

# ── 7. Cachear (AHORA que APP_KEY ya esta seteado) ──────────────────────────
echo "    Cacheando configuracion, rutas y vistas..."
php artisan config:cache --no-interaction
php artisan route:cache  --no-interaction
php artisan view:cache   --no-interaction

echo "==> Listo! Arrancando supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

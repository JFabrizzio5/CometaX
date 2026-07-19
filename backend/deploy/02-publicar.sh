#!/usr/bin/env bash
#
# Paso 2 de 2 — migra la base, reordena public_html y publica el panel.
#
# Correr DESPUÉS de haber editado el .env:
#   bash ~/domains/cometax.click/laravel/deploy/02-publicar.sh
#
# Pide confirmación antes de vaciar public_html. El respaldo lo hizo el paso 1.

set -euo pipefail

DOMAIN_DIR="$HOME/domains/cometax.click"
LARAVEL="$DOMAIN_DIR/laravel"
PUB="$DOMAIN_DIR/public_html"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[31mABORTA: %s\033[0m\n' "$*" >&2; exit 1; }

[ -f "$DOMAIN_DIR/.deploy-env" ] || die "Falta .deploy-env — corré primero 01-preparar.sh"
PHP_BIN="$(sed -n 1p "$DOMAIN_DIR/.deploy-env")"

[ -f "$LARAVEL/.env" ] || die "No hay .env en $LARAVEL"

# -------------------------------------------------------- 1. validar .env

say "Validando .env"
if grep -qE '^[A-Z_]+=<.*>' "$LARAVEL/.env"; then
    echo "Estos valores siguen sin rellenar:"
    grep -nE '^[A-Z_]+=<.*>' "$LARAVEL/.env" | sed 's/^/  /'
    die "Editá $LARAVEL/.env y volvé a correr."
fi
echo "Sin marcadores <...> pendientes."

cd "$LARAVEL"

say "Probando la conexión a la base"
"$PHP_BIN" artisan db:show >/dev/null 2>&1 \
    || die "No conecta a MySQL. Revisá DB_DATABASE / DB_USERNAME / DB_PASSWORD en el .env."
echo "Conecta bien."

# ------------------------------------------------------------ 2. migrar

say "Migrando"
"$PHP_BIN" artisan migrate --force

# --------------------------------------------------- 3. reordenar público

say "Contenido actual de public_html"
ls -la "$PUB" | sed 's/^/  /'

cat <<EOF

Se va a VACIAR public_html y dejar solamente:
  index.html      (la landing, desde el repo)
  panel/          (front controller de Laravel)

El respaldo está en $DOMAIN_DIR/_backup-public_html-*.tar.gz
La landing no usa archivos locales — todo su contenido es CDN — así que no
se pierde nada visible.

EOF
read -r -p "Escribí SI para continuar: " ok
[ "$ok" = "SI" ] || die "Cancelado por el usuario. No se tocó nada."

say "Reconstruyendo public_html"
find "$PUB" -mindepth 1 -maxdepth 1 -exec rm -rf {} +

cp "$DOMAIN_DIR/repo/index.html" "$PUB/index.html"

mkdir -p "$PUB/panel"
cp "$LARAVEL/deploy/panel/index.php" "$PUB/panel/index.php"
cp "$LARAVEL/deploy/panel/.htaccess" "$PUB/panel/.htaccess"
cp "$LARAVEL/public/favicon.ico"     "$PUB/panel/" 2>/dev/null || true
cp "$LARAVEL/public/robots.txt"      "$PUB/panel/" 2>/dev/null || true

mkdir -p "$LARAVEL/storage/app/public"
ln -sfn ../../laravel/storage/app/public "$PUB/panel/storage"

# ------------------------------------------------------------ 4. permisos

say "Permisos y caché"
chmod -R 775 "$LARAVEL/storage" "$LARAVEL/bootstrap/cache"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

# --------------------------------------------------------- 5. verificar

say "Verificando exposición de archivos (todo debe dar 403 o 404)"
fuga=0
for path in .env backend/ backend/.env docker-compose.yml CLAUDE.md t.md nice.md \
            panel/.env panel/composer.json panel/vendor/autoload.php .git/config; do
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "https://cometax.click/$path")"
    printf '  %-32s %s' "$path" "$code"
    if [ "$code" = "200" ]; then printf '   <-- FUGA\n'; fuga=1; else printf '\n'; fi
done

say "Verificando el panel"
for path in "" panel panel/login; do
    printf '  /%-14s %s\n' "$path" \
        "$(curl -s -o /dev/null -w '%{http_code}' -L --max-time 15 "https://cometax.click/$path")"
done

if [ "$fuga" -eq 1 ]; then
    printf '\n\033[31mHay archivos expuestos. Revisá qué quedó en public_html.\033[0m\n'
    exit 1
fi

cat <<EOF

────────────────────────────────────────────────────────────────
DEPLOY COMPLETO.

  Landing:  https://cometax.click
  Panel:    https://cometax.click/panel

Falta configurar a mano (está en DEPLOY-HOSTINGER.md):

  1. Google Cloud Console — URI de redirección autorizada:
       https://cometax.click/panel/auth/google/callback

  2. hPanel > Avanzado > Trabajos Cron, cada minuto:
       cd $LARAVEL && $PHP_BIN artisan schedule:run >> /dev/null 2>&1
       cd $LARAVEL && $PHP_BIN artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1

  3. Stripe — webhook a https://cometax.click/panel/stripe/webhook

Hasta que hagas el punto 1, el botón de Google devuelve redirect_uri_mismatch.
────────────────────────────────────────────────────────────────
EOF

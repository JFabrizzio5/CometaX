#!/usr/bin/env bash
#
# Paso 1 de 2 — deja el código listo y se detiene para que edites el .env.
#
# Uso:
#   ssh -p 65002 u413918370@89.117.139.222
#   curl -sO https://raw.githubusercontent.com/JFabrizzio5/CometaX/main/backend/deploy/01-preparar.sh
#   bash 01-preparar.sh
#
# No borra nada sin hacer copia primero. Se puede volver a correr sin romper
# lo ya hecho.

set -euo pipefail

DOMAIN_DIR="$HOME/domains/cometax.click"
REPO_URL="https://github.com/JFabrizzio5/CometaX.git"
STAMP="$(date +%Y%m%d-%H%M%S)"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[31mABORTA: %s\033[0m\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------- 1. entorno

say "Buscando PHP 8.3+"

PHP_BIN=""
for candidate in php php8.4 php8.3 /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php; do
    command -v "$candidate" >/dev/null 2>&1 || continue
    ver="$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"
    if [ "$ver" -ge 80300 ]; then
        PHP_BIN="$candidate"
        break
    fi
done

[ -n "$PHP_BIN" ] || die "No hay PHP 8.3+. Cambiá la versión en hPanel > Avanzado > Versión PHP y volvé a correr esto."

echo "PHP: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

say "Verificando extensiones"
faltan=""
for ext in bcmath ctype curl fileinfo intl mbstring openssl pdo_mysql tokenizer xml zip; do
    "$PHP_BIN" -m | grep -qix "$ext" || faltan="$faltan $ext"
done
[ -z "$faltan" ] || die "Faltan extensiones PHP:$faltan — habilitalas en hPanel y volvé a correr."
echo "Todas presentes."

say "Buscando Composer"
if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
else
    echo "No está instalado. Bajando composer.phar a ~/bin"
    mkdir -p "$HOME/bin"
    curl -sS https://getcomposer.org/installer | "$PHP_BIN" -- --install-dir="$HOME/bin" --filename=composer.phar
    COMPOSER="$HOME/bin/composer.phar"
fi
echo "Composer: $COMPOSER"

# ------------------------------------------------------- 2. respaldo público

[ -d "$DOMAIN_DIR" ] || die "No existe $DOMAIN_DIR — revisá el nombre del dominio."

say "Respaldando public_html actual"
BACKUP="$DOMAIN_DIR/_backup-public_html-$STAMP.tar.gz"
tar -czf "$BACKUP" -C "$DOMAIN_DIR" public_html
echo "Copia en: $BACKUP"
echo "Tamaño:   $(du -h "$BACKUP" | cut -f1)"

# ------------------------------------------------------------- 3. código

say "Clonando/actualizando el repositorio fuera de public_html"
if [ -d "$DOMAIN_DIR/repo/.git" ]; then
    git -C "$DOMAIN_DIR/repo" pull --ff-only
else
    git clone "$REPO_URL" "$DOMAIN_DIR/repo"
fi

ln -sfn repo/backend "$DOMAIN_DIR/laravel"
echo "laravel -> $(readlink "$DOMAIN_DIR/laravel")"

say "Instalando dependencias (tarda varios minutos)"
cd "$DOMAIN_DIR/laravel"
"$PHP_BIN" -d memory_limit=-1 "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction
"$PHP_BIN" -d memory_limit=-1 "$COMPOSER" require laravel/socialite --no-interaction

# ------------------------------------------------------------- 4. .env

say "Preparando .env"
if [ -f .env ]; then
    echo ".env ya existe, no lo toco."
else
    cp .env.production.example .env
    "$PHP_BIN" artisan key:generate --force
    echo ".env creado desde la plantilla, con APP_KEY nueva."
fi
chmod 600 .env

# guarda la ruta de PHP para el script 2
printf '%s\n%s\n' "$PHP_BIN" "$COMPOSER" > "$DOMAIN_DIR/.deploy-env"

cat <<EOF

────────────────────────────────────────────────────────────────
PASO 1 COMPLETO.

Ahora editá el .env y rellená los valores marcados con <...>:

    nano $DOMAIN_DIR/laravel/.env

Los que importan:
  DB_DATABASE / DB_USERNAME / DB_PASSWORD   <- de hPanel > Bases de datos
  GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET   <- de Google Cloud Console
  STRIPE_KEY / STRIPE_SECRET                <- de Stripe (podés dejarlos vacíos por ahora)

Ya vienen correctos, no los cambies:
  APP_URL=https://cometax.click/panel
  ADMIN_EMAILS=josephfabrizizo@gmail.com
  SESSION_PATH=/panel

Cuando termines, corré el paso 2:

    bash $DOMAIN_DIR/laravel/deploy/02-publicar.sh
────────────────────────────────────────────────────────────────
EOF

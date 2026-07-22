#!/usr/bin/env bash
#
# Instalador completo del panel para Hostinger compartido.
#
# Un solo comando desde la sesión SSH:
#   curl -sL https://raw.githubusercontent.com/JFabrizzio5/CometaX/main/backend/deploy/instalar.sh | bash
#
# Detecta la estructura de la cuenta, instala dependencias, arma el .env,
# migra y publica el panel en <dominio>/panel.
#
# Pide la contraseña de MySQL por prompt oculto: no queda en el historial.
# No borra nada de public_html — solo agrega la carpeta panel/.

set -uo pipefail

REPO_URL="https://github.com/JFabrizzio5/CometaX.git"
RUTA_PANEL="panel"

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
say()   { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
die()   { rojo "ABORTA: $*"; exit 1; }

# ---------------------------------------------------------- 1. estructura

say "Detectando la estructura de la cuenta"

PUB=""
for candidato in "$HOME"/domains/*/public_html "$HOME/public_html"; do
    [ -d "$candidato" ] || continue
    PUB="$candidato"
    break
done

[ -n "$PUB" ] || die "No encuentro public_html bajo $HOME."

RAIZ="$(dirname "$PUB")"
LARAVEL="$RAIZ/laravel"

echo "  public_html : $PUB"
echo "  laravel     : $LARAVEL   (hermana, fuera del árbol público)"

[ -f "$PUB/index.html" ] && echo "  landing     : encontrada, no se toca"

# ---------------------------------------------------------------- 2. PHP

say "Buscando PHP 8.3+"

PHP_BIN=""
for c in php php8.4 php8.3 /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php \
         /usr/local/bin/php /usr/bin/php8.3 /usr/bin/php8.4; do
    command -v "$c" >/dev/null 2>&1 || continue
    v="$("$c" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"
    [ "${v:-0}" -ge 80300 ] 2>/dev/null || continue
    PHP_BIN="$c"; break
done

[ -n "$PHP_BIN" ] || die "No hay PHP 8.3+. hPanel > Avanzado > Versión PHP, poné 8.3, y volvé a correr esto."
echo "  $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

faltan=""
for ext in bcmath ctype curl fileinfo mbstring openssl pdo_mysql tokenizer xml zip; do
    "$PHP_BIN" -m | grep -qix "$ext" || faltan="$faltan $ext"
done
[ -z "$faltan" ] || die "Faltan extensiones PHP:$faltan — habilitalas en hPanel."
echo "  extensiones: completas"

# ----------------------------------------------------------- 3. composer

say "Composer"
if command -v composer >/dev/null 2>&1; then
    COMPOSER="$(command -v composer)"
else
    mkdir -p "$HOME/bin"
    curl -sS https://getcomposer.org/installer | "$PHP_BIN" -- --install-dir="$HOME/bin" --filename=composer.phar >/dev/null 2>&1 \
        || die "No pude bajar Composer."
    COMPOSER="$HOME/bin/composer.phar"
fi
echo "  $COMPOSER"

# -------------------------------------------------------------- 4. código

say "Descargando el código"
if [ -d "$LARAVEL/.git" ]; then
    git -C "$LARAVEL" pull --ff-only >/dev/null 2>&1 && echo "  actualizado"
elif [ -d "$RAIZ/repo/.git" ]; then
    git -C "$RAIZ/repo" pull --ff-only >/dev/null 2>&1
    ln -sfn repo/backend "$LARAVEL"
    echo "  actualizado"
else
    git clone --depth 1 "$REPO_URL" "$RAIZ/repo" >/dev/null 2>&1 || die "Falló el git clone."
    ln -sfn repo/backend "$LARAVEL"
    echo "  clonado"
fi

cd "$LARAVEL" || die "No puedo entrar a $LARAVEL"

say "Instalando dependencias (varios minutos)"
"$PHP_BIN" -d memory_limit=-1 "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction \
    || die "Falló composer install."

# ---------------------------------------------------------------- 5. .env

say "Configurando el .env"

if [ -f .env ]; then
    echo "  .env ya existe, conservo los valores actuales"
else
    cp .env.production.example .env
    "$PHP_BIN" artisan key:generate --force >/dev/null 2>&1
    echo "  .env creado con APP_KEY nueva"
fi
chmod 600 .env

DOMINIO="$(basename "$(dirname "$PUB")")"
case "$DOMINIO" in
    */*|"$USER"|home) DOMINIO="cometax.click" ;;
esac
[ "$(basename "$PUB")" = "public_html" ] && [ "$RAIZ" = "$HOME" ] && DOMINIO="cometax.click"

APP_URL="https://$DOMINIO/$RUTA_PANEL"

poner() {
    local k="$1" v="$2"
    sed -i "/^${k}=/d" .env
    printf "%s='%s'\n" "$k" "$(printf '%s' "$v" | sed "s/'/'\\\\''/g")" >> .env
}

poner APP_URL "$APP_URL"
poner SESSION_PATH "/$RUTA_PANEL"
poner DB_CONNECTION mysql
poner DB_HOST localhost
poner DB_PORT 3306

echo "  APP_URL = $APP_URL"

if grep -qE "^DB_PASSWORD='?<" .env || ! grep -q "^DB_PASSWORD=" .env; then
    # Ejecutado como `curl ... | bash`, stdin es la tubería y no el teclado:
    # un `read` normal consumiría el propio script y devolvería vacío. Por eso
    # se lee de la terminal directamente.
    [ -r /dev/tty ] || die "No hay terminal para pedir las credenciales. Bajá el script y corrélo:
    curl -sO https://raw.githubusercontent.com/JFabrizzio5/CometaX/main/backend/deploy/instalar.sh
    bash instalar.sh"

    echo
    echo "  Credenciales MySQL (hPanel > Bases de datos)."
    echo "  La contraseña no se muestra ni queda en el historial."
    read -r -p "  DB_DATABASE : " DBN < /dev/tty
    read -r -p "  DB_USERNAME : " DBU < /dev/tty
    read -r -s -p "  DB_PASSWORD : " DBP < /dev/tty; echo
    [ -n "$DBN" ] && [ -n "$DBU" ] || die "Base y usuario no pueden ir vacíos."
    poner DB_DATABASE "$DBN"
    poner DB_USERNAME "$DBU"
    poner DB_PASSWORD "$DBP"
    unset DBP
fi

"$PHP_BIN" artisan config:clear >/dev/null 2>&1

say "Probando la conexión a MySQL"
if ! "$PHP_BIN" artisan db:show >/dev/null 2>&1; then
    rojo "  No conecta."
    "$PHP_BIN" artisan db:show 2>&1 | tail -4
    die "Revisá las credenciales y volvé a correr el instalador."
fi
verde "  Conecta."

# ------------------------------------------------------------- 6. migrar

say "Creando las tablas"
"$PHP_BIN" artisan migrate --force || die "Falló la migración."

# -------------------------------------------------------- 7. publicar

say "Publicando en $PUB/$RUTA_PANEL"
mkdir -p "$PUB/$RUTA_PANEL"
cp deploy/panel/index.php  "$PUB/$RUTA_PANEL/index.php"
cp deploy/panel/.htaccess  "$PUB/$RUTA_PANEL/.htaccess"
cp public/favicon.ico      "$PUB/$RUTA_PANEL/" 2>/dev/null
cp public/robots.txt       "$PUB/$RUTA_PANEL/" 2>/dev/null

# El front controller busca ../../laravel desde public_html/panel/
DESTINO_REAL="$(cd "$PUB/$RUTA_PANEL/../../laravel" 2>/dev/null && pwd -P)"
ESPERADO="$(cd "$LARAVEL" && pwd -P)"
if [ "$DESTINO_REAL" != "$ESPERADO" ]; then
    rojo "  El front controller no encuentra laravel/."
    echo "  busca:    $PUB/$RUTA_PANEL/../../laravel"
    echo "  deberia:  $ESPERADO"
    die "Estructura inesperada."
fi
echo "  ../../laravel resuelve bien"

mkdir -p storage/app/public
ln -sfn ../../laravel/storage/app/public "$PUB/$RUTA_PANEL/storage" 2>/dev/null

chmod -R 775 storage bootstrap/cache 2>/dev/null

say "Cacheando configuración"
"$PHP_BIN" artisan optimize:clear >/dev/null 2>&1
"$PHP_BIN" artisan config:cache >/dev/null 2>&1
# NO route:cache: en subdirectorio (/panel) rompe la ruta raíz '/' (405 en GET).
"$PHP_BIN" artisan route:clear  >/dev/null 2>&1
"$PHP_BIN" artisan view:cache   >/dev/null 2>&1
echo "  listo"

# ------------------------------------------------------------ 8. probar

say "Verificando"
fuga=0
for ruta in ".env" "laravel/.env" "$RUTA_PANEL/.env" "$RUTA_PANEL/composer.json" "$RUTA_PANEL/vendor/autoload.php"; do
    c="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "https://$DOMINIO/$ruta")"
    printf '  %-34s %s' "/$ruta" "$c"
    if [ "$c" = "200" ]; then rojo "  <-- EXPUESTO"; fuga=1; else printf '\n'; fi
done

echo
for ruta in "" "$RUTA_PANEL" "$RUTA_PANEL/login"; do
    printf '  %-34s %s\n' "/$ruta" \
        "$(curl -s -o /dev/null -w '%{http_code}' -L --max-time 15 "https://$DOMINIO/$ruta")"
done

echo
if [ "$fuga" -eq 1 ]; then
    rojo "Hay archivos expuestos — revisá qué quedó dentro de public_html."
fi

cat <<FIN

────────────────────────────────────────────────────────────
PANEL PUBLICADO

  $APP_URL

Falta un paso para que el botón de Google funcione:

  console.cloud.google.com > Credenciales > ID de OAuth (web)

    Orígenes JavaScript:  https://$DOMINIO
    URI de redirección:   $APP_URL/auth/google/callback

  Copiá el ID y el secreto a:
    nano $LARAVEL/.env      (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET)

  Después:
    cd $LARAVEL && $PHP_BIN artisan config:cache

Entrando con josephfabrizizo@gmail.com caés en el panel de administración.
Con cualquier otra cuenta se crea un cliente nuevo.
────────────────────────────────────────────────────────────
FIN

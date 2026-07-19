#!/usr/bin/env bash
#
# Carga las credenciales de MySQL en el .env sin que pasen por el historial
# del shell ni sufran expansión de variables.
#
# Uso:
#   bash ~/domains/cometax.click/laravel/deploy/03-configurar-db.sh
#
# La contraseña se pide por prompt oculto: no queda en ~/.bash_history, no se
# ve en `ps`, y se escribe entre comillas simples para que Laravel la lea
# literal aunque contenga $, / o {}.

set -euo pipefail

ENV_FILE="${1:-$HOME/domains/cometax.click/laravel/.env}"

say() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
die() { printf '\n\033[31mABORTA: %s\033[0m\n' "$*" >&2; exit 1; }

[ -f "$ENV_FILE" ] || die "No existe $ENV_FILE — corré antes 01-preparar.sh"

say "Credenciales MySQL (hPanel > Bases de datos > MySQL)"

read -r -p "DB_DATABASE : " DB_NAME
read -r -p "DB_USERNAME : " DB_USER
read -r -s -p "DB_PASSWORD : " DB_PASS; echo
read -r -s -p "repetir     : " DB_PASS2; echo

[ -n "$DB_NAME" ] || die "El nombre de la base no puede quedar vacío."
[ -n "$DB_USER" ] || die "El usuario no puede quedar vacío."
[ "$DB_PASS" = "$DB_PASS2" ] || die "Las contraseñas no coinciden."

# En Hostinger compartido MySQL escucha en el socket local.
DB_HOST="localhost"

# Escapa comillas simples para poder envolver el valor en comillas simples:
# ' -> '\''  — así el valor queda literal para el parser de dotenv.
esc() { printf "%s" "$1" | sed "s/'/'\\\\''/g"; }

say "Escribiendo en $ENV_FILE"
cp "$ENV_FILE" "$ENV_FILE.bak"

set_var() {
    local key="$1" val="$2"
    # Se borra cualquier definición previa y se agrega la nueva al final,
    # así no importa si la clave estaba comentada o repetida.
    sed -i "/^${key}=/d" "$ENV_FILE"
    printf "%s='%s'\n" "$key" "$(esc "$val")" >> "$ENV_FILE"
}

set_var DB_CONNECTION mysql
set_var DB_HOST "$DB_HOST"
set_var DB_PORT 3306
set_var DB_DATABASE "$DB_NAME"
set_var DB_USERNAME "$DB_USER"
set_var DB_PASSWORD "$DB_PASS"

chmod 600 "$ENV_FILE"
unset DB_PASS DB_PASS2

say "Probando la conexión"
PHP_BIN="php"
[ -f "$HOME/domains/cometax.click/.deploy-env" ] && \
    PHP_BIN="$(sed -n 1p "$HOME/domains/cometax.click/.deploy-env")"

cd "$(dirname "$ENV_FILE")"
"$PHP_BIN" artisan config:clear >/dev/null 2>&1 || true

if "$PHP_BIN" artisan db:show >/dev/null 2>&1; then
    printf '\n\033[32mConecta correctamente.\033[0m\n'
    rm -f "$ENV_FILE.bak"
    echo "Seguí con: bash $(dirname "$ENV_FILE")/deploy/02-publicar.sh"
else
    printf '\n\033[31mNo conecta.\033[0m Se restauró el .env anterior en %s.bak\n' "$ENV_FILE"
    echo "Revisá que el usuario tenga permisos sobre la base en hPanel."
    "$PHP_BIN" artisan db:show 2>&1 | tail -5
    exit 1
fi

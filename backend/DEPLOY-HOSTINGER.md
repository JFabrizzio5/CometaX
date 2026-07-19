# Deploy en Hostinger (hosting compartido)

Restricciones que mandan sobre todo lo demás: **no hay root**, **no hay daemons**
(nada de `queue:work` persistente ni Horizon) y el document root del dominio
apunta a `public_html`, no a `public/`.

Objetivo: servir el panel en `https://panel.cometax.click` con el código de
Laravel **fuera** de la carpeta pública.

---

## 0. Antes de empezar

En hPanel, **Avanzado > Versión PHP**: seleccionar **PHP 8.3 o superior**.
Laravel 13 no arranca con 8.1/8.2. Habilitar las extensiones `bcmath`, `intl`,
`mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `curl`, `fileinfo`, `zip`.

En hPanel, **Bases de datos > MySQL**: crear base y usuario. Anotar nombre,
usuario y contraseña (Hostinger les pone prefijo, ej. `u413918370_panel`).

En hPanel, **Dominios > Subdominios**: crear `panel`. Genera la carpeta
`~/domains/panel.cometax.click/public_html`.

## 1. Subir el código

```bash
ssh -p 65002 u413918370@89.117.139.222

cd ~/domains/panel.cometax.click
git clone https://github.com/JFabrizzio5/CometaX.git repo
ln -s repo/backend app
```

Queda `~/domains/panel.cometax.click/app` apuntando al backend, y
`public_html` sigue siendo la única carpeta expuesta a internet.

## 2. Dependencias

```bash
cd ~/domains/panel.cometax.click/app
php -d memory_limit=-1 $(which composer) install --no-dev --optimize-autoloader
php -d memory_limit=-1 $(which composer) require laravel/socialite
```

`-d memory_limit=-1` es obligatorio: el límite del shell compartido mata a
Composer a mitad de la resolución de dependencias.

## 3. `.env`

```bash
cp .env.production.example .env
nano .env          # rellenar los <...>
php artisan key:generate
chmod 600 .env
```

`APP_KEY` se genera en el servidor. No copies la clave de local: si rotas la
clave, las sesiones y todo lo cifrado con la anterior quedan ilegibles.

## 4. Base de datos

```bash
php artisan migrate --force
```

`--force` es necesario porque `APP_ENV=production` pide confirmación interactiva.

## 5. Exponer solo `public/`

Hostinger no siempre deja mover el document root del subdominio. El método que
funciona en cualquier caso: dejar `public_html` como espejo de `public/`.

```bash
cd ~/domains/panel.cometax.click
rm -rf public_html
ln -s app/public public_html
```

Si el plan no permite symlinks para el document root, la alternativa es copiar
el contenido de `app/public/` a `public_html/` y editar `public_html/index.php`
para que apunte al proyecto:

```php
// public_html/index.php — reemplazar las dos rutas require
require __DIR__.'/../app/vendor/autoload.php';
$app = require_once __DIR__.'/../app/bootstrap/app.php';
```

Con este método hay que volver a copiar `app/public/` en cada deploy que toque
assets.

## 6. Permisos

```bash
cd ~/domains/panel.cometax.click/app
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

## 7. Caché de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Ojo:** después de `config:cache`, las llamadas a `env()` fuera de los archivos
de `config/` devuelven `null`. Todo el código nuevo lee de `config(...)`, así que
está bien — pero si agregás un `env()` suelto, se rompe solo en producción.

Cada deploy posterior: `php artisan optimize:clear` y volver a cachear.

## 8. Cron (reemplaza a los daemons)

hPanel > **Avanzado > Trabajos Cron**. Dos entradas, cada minuto:

```
* * * * * cd ~/domains/panel.cometax.click/app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd ~/domains/panel.cometax.click/app && php artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
```

`--stop-when-empty` y `--max-time=50` hacen que el proceso muera antes del
siguiente tick. Sin eso se acumulan procesos y el hosting te suspende la cuenta.

---

## Google OAuth

En [console.cloud.google.com](https://console.cloud.google.com) >
**APIs y servicios > Credenciales > Crear ID de cliente de OAuth > Aplicación web**:

- **Orígenes autorizados de JavaScript:** `https://panel.cometax.click`
- **URI de redirección autorizados:** `https://panel.cometax.click/auth/google/callback`

La URI de redirección tiene que coincidir **carácter por carácter** con
`GOOGLE_REDIRECT_URI` del `.env`. Una barra final de más y Google devuelve
`redirect_uri_mismatch`.

En **Pantalla de consentimiento de OAuth**: mientras esté en modo *Testing* solo
entran los correos que agregues como usuarios de prueba. Para abrirlo a
cualquiera hay que publicarla. Con los scopes `openid/profile/email` no hace
falta verificación de Google.

## Stripe

Webhook en [dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks):

- **Endpoint:** `https://panel.cometax.click/stripe/webhook`
- **Eventos:** `customer.subscription.created`, `customer.subscription.updated`,
  `customer.subscription.deleted`, `customer.updated`, `customer.deleted`,
  `invoice.payment_action_required`, `invoice.payment_succeeded`

Copiar el *signing secret* a `STRIPE_WEBHOOK_SECRET`. Sin él, Cashier rechaza
todos los webhooks y el panel nunca se entera de quién pagó.

`CASHIER_MODEL=App\Models\Client` — el modelo facturable es `Client`, no `User`.

## Verificación

### Funcional

1. `https://panel.cometax.click` redirige a `/login`.
2. Entrar con una cuenta de Google cualquiera → crea `Client` + `User` y cae en `/dashboard`.
3. Entrar con el correo de `ADMIN_EMAILS` → crea `Consultant` con `role=super_admin` y cae en `/admin/suscripciones`.

### Exposición de archivos

Correr esto **después** de cada deploy. Cualquier `200` es una fuga:

```bash
for path in .env .env.production.example .git/config .git/HEAD \
            composer.json composer.lock artisan \
            storage/logs/laravel.log vendor/autoload.php \
            DEPLOY-HOSTINGER.md; do
  printf '%-32s %s\n' "$path" \
    "$(curl -s -o /dev/null -w '%{http_code}' "https://panel.cometax.click/$path")"
done
```

Esperado: `403` o `404` en todas. Un `200` en `.git/config` significa que se
puede reconstruir el código fuente completo con `git-dumper`.

Si alguna devuelve `200`, el document root está mal apuntado — volver al paso 5.
El `.htaccess` de la raíz del proyecto es red de seguridad, no la solución.

**No apuntes nunca el document root a la raíz del clon** (`~/domains/panel.cometax.click/repo`).
Ahí vive `.git/` y el `.htaccess` del backend está un nivel más abajo, así que no
lo protege. El docroot va a `backend/public`, punto.

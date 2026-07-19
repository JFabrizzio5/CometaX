# Deploy en Hostinger (hosting compartido)

El panel se sirve en **`https://cometax.click/panel`**, como subdirectorio del
dominio principal. Sin subdominio: no hay que esperar DNS ni emitir un
certificado nuevo, reusa el SSL de `cometax.click`.

Restricciones que mandan sobre el diseño: **no hay root**, **no hay daemons**
(nada de `queue:work` persistente ni Horizon) y el document root es fijo en
`public_html` — no se puede apuntar a la carpeta `public/` de Laravel.

## Estructura final en el servidor

El invariante es **`laravel/` hermana de `public_html/`**, no la ruta absoluta.
Según el plan, la raíz puede ser `~/` o `~/domains/cometax.click/`; el
`../../laravel` del front controller resuelve bien en ambos casos siempre que
se respete esa relación.

```
<raíz>/
├── public_html/              <- document root, lo único expuesto a internet
│   ├── index.html            <- landing estática
│   ├── assets/
│   └── panel/                <- hace de public/ de Laravel
│       ├── index.php         <- de backend/deploy/panel/index.php
│       ├── .htaccess         <- de backend/deploy/panel/.htaccess
│       ├── favicon.ico
│       ├── robots.txt
│       └── storage -> ../../laravel/storage/app/public
└── laravel/                  <- código de la app, FUERA de public_html
    ├── app/ bootstrap/ config/ database/ resources/ routes/ storage/
    ├── vendor/
    └── .env
```

La clave: `.env`, `vendor/` y el código quedan fuera del árbol servido. El
servidor web no puede leerlos aunque quiera, porque no existen para él.

---

## 0. Antes de empezar

En hPanel, **Avanzado > Versión PHP**: seleccionar **PHP 8.3 o superior**.
Laravel 13 no arranca con 8.1/8.2. Habilitar `bcmath`, `intl`, `mbstring`,
`openssl`, `pdo_mysql`, `tokenizer`, `xml`, `curl`, `fileinfo`, `zip`.

En hPanel, **Bases de datos > MySQL**: crear base y usuario. Anotar nombre,
usuario y contraseña (Hostinger les pone prefijo, ej. `u413918370_panel`).

## 1. Subir el código

```bash
ssh -p 65002 u413918370@89.117.139.222

cd ~/domains/cometax.click
git clone https://github.com/JFabrizzio5/CometaX.git repo
ln -s repo/backend laravel
```

`~/domains/cometax.click/laravel` apunta al backend y queda fuera de
`public_html`. El `.git` del clon también: nunca se expone.

## 2. Dependencias

```bash
cd ~/domains/cometax.click/laravel
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

`APP_KEY` se genera en el servidor. No copies la clave de local: si la rotás,
las sesiones y todo lo cifrado con la anterior quedan ilegibles.

Verificar que `APP_URL=https://cometax.click/panel` — **con la ruta incluida**.
De ahí salen `asset()` y las URLs de los correos; sin el `/panel` apuntan a la
landing.

## 4. Base de datos

```bash
php artisan migrate --force
```

`--force` es necesario porque `APP_ENV=production` pide confirmación interactiva.

## 5. Montar el front controller

```bash
cd ~/domains/cometax.click
mkdir -p public_html/panel

cp laravel/deploy/panel/index.php   public_html/panel/index.php
cp laravel/deploy/panel/.htaccess   public_html/panel/.htaccess
cp laravel/public/favicon.ico       public_html/panel/
cp laravel/public/robots.txt        public_html/panel/

ln -s ../../laravel/storage/app/public public_html/panel/storage
```

El symlink de `storage` reemplaza a `php artisan storage:link`, que apuntaría a
`laravel/public/storage` — una carpeta que acá no se sirve.

**No copies nada más de `laravel/` a `public_html/`.** Todo lo que entre ahí
queda accesible desde internet.

## 6. Permisos

```bash
cd ~/domains/cometax.click/laravel
chmod -R 775 storage bootstrap/cache
```

## 7. Caché de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Ojo:** después de `config:cache`, las llamadas a `env()` fuera de los archivos
de `config/` devuelven `null`. Todo el código actual lee de `config(...)`, así que
está bien — pero si agregás un `env()` suelto, se rompe solo en producción.

Cada deploy posterior:

```bash
cd ~/domains/cometax.click/repo && git pull
cd ../laravel && php artisan optimize:clear && php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Si el `git pull` tocó `deploy/panel/`, repetir el paso 5.

## 8. Cron (reemplaza a los daemons)

hPanel > **Avanzado > Trabajos Cron**. Dos entradas, cada minuto:

```
* * * * * cd ~/domains/cometax.click/laravel && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd ~/domains/cometax.click/laravel && php artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> /dev/null 2>&1
```

`--stop-when-empty` y `--max-time=50` hacen que el proceso muera antes del
siguiente tick. Sin eso se acumulan procesos y el hosting suspende la cuenta.

---

## Google OAuth

En [console.cloud.google.com](https://console.cloud.google.com) >
**APIs y servicios > Credenciales > Crear ID de cliente de OAuth > Aplicación web**:

- **Orígenes autorizados de JavaScript:** `https://cometax.click`
- **URI de redirección autorizados:** `https://cometax.click/panel/auth/google/callback`

El origen va **sin** la ruta; la URI de redirección **con** la ruta. Tiene que
coincidir carácter por carácter con `GOOGLE_REDIRECT_URI` del `.env`. Una barra
final de más y Google devuelve `redirect_uri_mismatch`.

En **Pantalla de consentimiento de OAuth**: mientras esté en modo *Testing* solo
entran los correos que agregues como usuarios de prueba. Para abrirlo a
cualquiera hay que publicarla. Con los scopes `openid/profile/email` no hace
falta verificación de Google.

## Stripe

Webhook en [dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks):

- **Endpoint:** `https://cometax.click/panel/stripe/webhook`
- **Eventos:** `customer.subscription.created`, `customer.subscription.updated`,
  `customer.subscription.deleted`, `customer.updated`, `customer.deleted`,
  `invoice.payment_action_required`, `invoice.payment_succeeded`

Copiar el *signing secret* a `STRIPE_WEBHOOK_SECRET`. Sin él, Cashier rechaza
todos los webhooks y el panel nunca se entera de quién pagó.

`CASHIER_MODEL=App\Models\Client` — el modelo facturable es `Client`, no `User`.

---

## Verificación

### Funcional

1. `https://cometax.click` sigue mostrando la landing, intacta.
2. `https://cometax.click/panel` redirige a `/panel/login`.
3. Entrar con una cuenta de Google cualquiera → crea `Client` + `User` y cae en `/panel/dashboard`.
4. Entrar con el correo de `ADMIN_EMAILS` → crea `Consultant` con `role=super_admin` y cae en `/panel/admin/suscripciones`.

### Exposición de archivos

Correr esto **después** de cada deploy. Cualquier `200` es una fuga:

```bash
for path in panel/.env panel/../.env .env \
            panel/composer.json panel/artisan panel/vendor/autoload.php \
            panel/storage/logs/laravel.log panel/app/Models/User.php \
            .git/config; do
  printf '%-40s %s\n' "$path" \
    "$(curl -s -o /dev/null -w '%{http_code}' "https://cometax.click/$path")"
done
```

Esperado: `403` o `404` en todas. Con esta estructura ninguna debería existir
siquiera — el código vive fuera de `public_html`.

Un `200` en cualquiera significa que se copió de más dentro de `public_html`.
Revisar el paso 5: ahí van cuatro archivos y un symlink, nada más.

### Conflicto con la landing

Si `public_html/.htaccess` (el de la landing) tiene reglas de reescritura
propias, pueden capturar `/panel/*` antes de que llegue al `.htaccess` del
panel. Si el panel devuelve 404 en todo menos la home, es esto. Se arregla
agregando al principio del `.htaccess` de la raíz:

```apache
RewriteEngine On
RewriteRule ^panel/ - [L]
```
